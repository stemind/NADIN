<?php
/** File-based credentials and revocable sessions. No changes to userdatei.txt's format. */
final class NadinAuth {
    private $root, $salt, $timeout, $bindIp, $ip;
    private $loginRetry = 0;
    const MAX_RATES = 10000;
    const GUARD = "<?php http_response_code(404); exit; ?>\n";
    public function __construct($root, $salt, $timeout = 3600, $bindIp = false, $ip = '') {
        $this->root = rtrim($root, '/'); $this->salt = $salt;
        $this->timeout = max(60, (int)$timeout); $this->bindIp = $bindIp; $this->ip = $ip;
    }
    public static function email($value) {
        return is_string($value) && strlen($value) <= 254 ? strtolower(trim($value)) : '';
    }
    public function members() {
        $text = @file_get_contents($this->root.'/userdatei/userdatei.txt');
        if ($text === false) throw new RuntimeException('The user list cannot be read.');
        $members = array();
        foreach (preg_split('/\r\n|\n|\r/', $text) as $line) {
            $fields = array_map('trim', explode(',', strtolower($line)));
            if (count($fields) < 3 || !filter_var($fields[1], FILTER_VALIDATE_EMAIL)) continue;
            $date = DateTimeImmutable::createFromFormat('!d.m.Y', $fields[2]);
            $errors = DateTimeImmutable::getLastErrors();
            if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count']))) continue;
            $rights = array(); foreach (array_slice($fields, 3) as $right) {
                if (preg_match('/^([a-z]+)\s*=\s*1$/', $right, $m)) $rights[$m[1]] = true;
            }
            // Preserve the legacy access-expiry boundary (midnight at the given date).
            $members[$fields[1]] = array('expires'=>$date->getTimestamp(), 'rights'=>$rights,
                'stamp'=>hash('sha256', implode(',', $fields)));
        }
        return $members;
    }
    public function active($email, $right = null) {
        $m = $this->members(); $u = $m[$email] ?? null;
        return $u && $u['expires'] >= time() && ($right === null || !empty($u['rights'][$right]));
    }
    public function initial($email) { return substr(sha1($email.$this->salt), 7, 6); }
    private function emptyAccount() { return array('hash'=>'', 'sessions'=>array(), 'resets'=>array()); }
    private function transaction($fn) {
        $path = $this->root.'/userdatei/auth-v11.php';
        $lockPath = $this->root.'/userdatei/auth-v11-lock.php';
        if (is_link($path) || is_link($lockPath)) throw new RuntimeException('Unsafe authentication storage.');
        $lock = @fopen($lockPath, 'c+');
        if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Authentication storage is unavailable.');
        @chmod($lockPath, 0600);
        try {
            if (is_file($path)) {
                $raw = file_get_contents($path);
                if (substr($raw, 0, strlen(self::GUARD)) !== self::GUARD) throw new RuntimeException('Invalid authentication storage.');
                $db = json_decode(substr($raw, strlen(self::GUARD)), true);
                if (!is_array($db) || ($db['version'] ?? 0) !== 11 || !is_string($db['key'] ?? null) || strlen($db['key']) !== 64 || !is_array($db['accounts'] ?? null) || !is_array($db['rates'] ?? null)) throw new RuntimeException('Invalid authentication storage.');
                foreach ($db['accounts'] as $a) {
                    if (!is_array($a) || !is_string($a['hash'] ?? null) || !is_array($a['sessions'] ?? null) || !is_array($a['resets'] ?? null)) throw new RuntimeException('Invalid account storage.');
                }
                foreach ($db['rates'] as $r) {
                    if (!is_array($r) || !is_int($r['n'] ?? null) || $r['n'] < 0 || !is_int($r['until'] ?? null)) throw new RuntimeException('Invalid attempt storage.');
                    if (isset($r['blocked']) && !is_int($r['blocked'])) throw new RuntimeException('Invalid attempt storage.');
                    if (isset($r['attempts'])) {
                        if (!is_array($r['attempts']) || count($r['attempts']) > 100) throw new RuntimeException('Invalid attempt storage.');
                        foreach ($r['attempts'] as $t) if (!is_int($t)) throw new RuntimeException('Invalid attempt storage.');
                    }
                }
            } else {
                if (fstat($lock)['size'] > 0) throw new RuntimeException('Authentication data is missing. Restore it from backup; do not recreate initial passwords.');
                $db = array('version'=>11, 'key'=>bin2hex(random_bytes(32)), 'accounts'=>array(), 'rates'=>array());
            }
            $before = json_encode($db); $now = time();
            foreach ($db['rates'] as $k=>$rate) if (($rate['until'] ?? 0) <= $now) unset($db['rates'][$k]);
            foreach ($db['accounts'] as &$a) {
                foreach ($a['sessions'] as $k=>$s) if (($s['until'] ?? 0) <= $now) unset($a['sessions'][$k]);
                foreach ($a['resets'] as $k=>$r) if (($r['until'] ?? 0) <= $now) unset($a['resets'][$k]);
            } unset($a);
            $result = $fn($db);
            $json = json_encode($db, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            if (!is_file($path) || $before !== $json) {
                // A .php suffix also protects a temporary file if .htaccess is ignored.
                $tmp = $this->root.'/userdatei/auth-v11-'.bin2hex(random_bytes(12)).'.php';
                $bytes = self::GUARD.$json;
                try {
                    if (file_put_contents($tmp, $bytes, LOCK_EX) !== strlen($bytes)) throw new RuntimeException('Could not save authentication data.');
                    @chmod($tmp, 0600);
                    if (!rename($tmp, $path)) throw new RuntimeException('Could not replace authentication data.');
                } finally { if (is_file($tmp)) @unlink($tmp); }
            }
            if (fstat($lock)['size'] === 0) {
                if (fwrite($lock, self::GUARD) !== strlen(self::GUARD) || !fflush($lock)) throw new RuntimeException('Could not mark authentication storage.');
            }
            return $result;
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }
    private function rate(&$db, $scope, $value, $limit, $seconds) {
        $key = hash_hmac('sha256', $scope.'|'.$value, $db['key']);
        if (!isset($db['rates'][$key]) && count($db['rates']) >= self::MAX_RATES) return false;
        $r = $db['rates'][$key] ?? array('n'=>0, 'until'=>time()+$seconds);
        $r['n'] = min($limit+1, $r['n']+1); $db['rates'][$key] = $r;
        return $r['n'] <= $limit;
    }
    private function loginKey($db, $scope, $value) { return hash_hmac('sha256', 'login-v13-'.$scope.'|'.$value, $db['key']); }
    public function loginRetryAfter() { return $this->loginRetry; }
    private function clearLoginFailures(&$db, $email) {
        unset($db['rates'][$this->loginKey($db, 'account', $email)]);
        unset($db['rates'][hash_hmac('sha256', 'login-account|'.$email, $db['key'])]);
    }
    private function loginEvent(&$db, $event, $account, $network, $delay = 0) {
        // Bounded diagnostic history: keyed identifiers only, never submitted credentials.
        $events = $db['loginEvents'] ?? array();
        if (!is_array($events)) throw new RuntimeException('Invalid login history.');
        $events[] = array('at'=>time(), 'event'=>$event, 'account'=>$account, 'network'=>$network, 'delay'=>$delay);
        $db['loginEvents'] = array_slice($events, -200);
    }
    private function verify($a, $email, $password) {
        if (!is_string($password) || strlen($password) > 1024) return false;
        if ($a['hash'] !== '') return password_verify($password, $a['hash']);
        return hash_equals($this->initial($email), strtolower(trim($password)));
    }
    private function newSession(&$a, $email, $members) {
        $token = bin2hex(random_bytes(32));
        $a['sessions'][hash('sha256', $token)] = array('until'=>time()+$this->timeout, 'stamp'=>$members[$email]['stamp'], 'ip'=>$this->bindIp ? $this->ip : '');
        while (count($a['sessions']) > 20) array_shift($a['sessions']);
        return $token;
    }
    private function sessionUser($db, $token, $members) {
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) return '';
        $id = hash('sha256', $token);
        foreach ($db['accounts'] as $email=>$a) {
            $s = $a['sessions'][$id] ?? null; $m = $members[$email] ?? null;
            if ($s && $m && $m['expires'] >= time() && ($s['until'] ?? 0) > time() && hash_equals($m['stamp'], $s['stamp'] ?? '') && (!$this->bindIp || hash_equals($this->ip, $s['ip'] ?? ''))) return $email;
        }
        return '';
    }
    public function login($email, $password) {
        $email = self::email($email); $this->loginRetry = 0;
        return $this->transaction(function(&$db) use($email, $password) {
            $now = time();
            $accountKey = $this->loginKey($db, 'account', $email);
            $networkKey = $this->loginKey($db, 'network', $this->ip);
            $account = $db['rates'][$accountKey] ?? array('n'=>0, 'until'=>$now+86400, 'blocked'=>0);
            $network = $db['rates'][$networkKey] ?? array('n'=>0, 'until'=>$now+600, 'blocked'=>0, 'attempts'=>array());
            if (!is_int($account['blocked'] ?? null) || !is_int($network['blocked'] ?? null) || !is_array($network['attempts'] ?? null)) throw new RuntimeException('Invalid login attempt storage.');
            // Honour active V11/V12 limits while upgrading an existing installation.
            foreach (array('login-account'=>array($email,12), 'login-ip'=>array($this->ip,60)) as $scope=>$old) {
                $r = $db['rates'][hash_hmac('sha256', $scope.'|'.$old[0], $db['key'])] ?? null;
                if ($r && $r['n'] >= $old[1]) $this->loginRetry = max($this->loginRetry, $r['until']-$now);
            }
            $this->loginRetry = max($this->loginRetry, $account['blocked']-$now, $network['blocked']-$now, 0);
            if ($this->loginRetry > 0) return false; // Blocked requests never prolong the lock.
            $needed = (int)!isset($db['rates'][$accountKey]) + (int)!isset($db['rates'][$networkKey]);
            if (count($db['rates'])+$needed > self::MAX_RATES) { $this->loginRetry=60; return false; }
            $members = $this->members(); $m = $members[$email] ?? null;
            if (!$m || $m['expires'] < $now || !$this->verify($db['accounts'][$email] ?? $this->emptyAccount(), $email, $password)) {
                $account['n'] = min(9, $account['n']+1);
                $delay = $account['n'] < 6 ? 0 : array(6=>30, 7=>120, 8=>600, 9=>3600)[$account['n']];
                $account['blocked'] = $now+$delay; $account['until']=$now+86400;
                $network['attempts'] = array_values(array_filter($network['attempts'], function($t) use($now) { return $t > $now-600; }));
                $network['attempts'][] = $now; $network['n'] = count($network['attempts']);
                $network['blocked'] = $network['n'] >= 100 ? $now+600 : 0;
                $network['until'] = $now+600;
                $db['rates'][$accountKey]=$account; $db['rates'][$networkKey]=$network;
                $this->loginRetry = max($delay, $network['blocked']-$now);
                $this->loginEvent($db, $this->loginRetry ? 'lock' : 'failure', $accountKey, $networkKey, $this->loginRetry);
                return false;
            }
            if (!isset($db['accounts'][$email])) $db['accounts'][$email] = $this->emptyAccount();
            $this->clearLoginFailures($db, $email);
            // Successful band members do not consume the shared network's failure budget.
            $this->loginEvent($db, 'success', $accountKey, $networkKey);
            return $this->newSession($db['accounts'][$email], $email, $members);
        });
    }
    public function session($token, $touch = false) {
        return $this->transaction(function(&$db) use($token, $touch) {
            $email = $this->sessionUser($db, $token, $this->members());
            if ($email && $touch) {
                $id = hash('sha256', $token);
                if ($db['accounts'][$email]['sessions'][$id]['until'] < time()+$this->timeout-30) $db['accounts'][$email]['sessions'][$id]['until'] = time()+$this->timeout;
            }
            return $email;
        });
    }
    public function logout($token) {
        $this->transaction(function(&$db) use($token) { foreach ($db['accounts'] as &$a) unset($a['sessions'][hash('sha256', (string)$token)]); });
    }
    public function hasPersonal($email) {
        return $this->transaction(function(&$db) use($email) { return ($db['accounts'][$email]['hash'] ?? '') !== ''; });
    }
    public static function passwordError($password, $confirmation) {
        if (!is_string($password) || !is_string($confirmation) || !hash_equals($password, $confirmation)) return 'The two passwords do not match.';
        if (strpos($password, "\0") !== false || preg_match('//u', $password) !== 1 || preg_match_all('/./us', $password) < 12 || strlen($password) > 72) return 'Use at least 12 characters and at most 72 bytes. Spaces are allowed.';
        return '';
    }
    private function hashPassword($password) {
        $hash = password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT);
        if (!$hash) throw new RuntimeException('Could not protect the new password.');
        return $hash;
    }
    public function change($token, $current, $password, $confirmation) {
        if (self::passwordError($password, $confirmation)) return false;
        return $this->transaction(function(&$db) use($token, $current, $password) {
            $members = $this->members(); $email = $this->sessionUser($db, $token, $members);
            if (!$email || !$this->rate($db, 'change', $email, 8, 900) || !$this->verify($db['accounts'][$email], $email, $current)) return false;
            $db['accounts'][$email] = array('hash'=>$this->hashPassword($password), 'sessions'=>array(), 'resets'=>array());
            $this->clearLoginFailures($db, $email);
            return $this->newSession($db['accounts'][$email], $email, $members);
        });
    }
    /** Return a message for the registered address only. Caller always displays the same response. */
    public function recovery($email) {
        $email = self::email($email);
        return $this->transaction(function(&$db) use($email) {
            $ipOK = $this->rate($db, 'recovery-ip', $this->ip, 20, 3600);
            $userOK = $ipOK && $this->rate($db, 'recovery-account', $email, 3, 3600);
            if (!$ipOK || !$userOK || !$this->active($email)) return null;
            $a = $db['accounts'][$email] ?? $this->emptyAccount();
            if ($a['hash'] === '') return array('to'=>$email, 'kind'=>'initial', 'secret'=>$this->initial($email));
            $code = strtoupper(bin2hex(random_bytes(6)));
            $id = hash_hmac('sha256', $code, $db['key']);
            $a['resets'][$id] = array('until'=>time()+1800, 'stamp'=>$this->members()[$email]['stamp']);
            while (count($a['resets']) > 3) array_shift($a['resets']);
            $db['accounts'][$email] = $a;
            return array('to'=>$email, 'kind'=>'reset', 'secret'=>implode('-', str_split($code, 4)));
        });
    }
    public function reset($email, $code, $password, $confirmation) {
        $email = self::email($email);
        if (self::passwordError($password, $confirmation) || !is_string($code)) return false;
        return $this->transaction(function(&$db) use($email, $code, $password) {
            $ipOK = $this->rate($db, 'reset-ip', $this->ip, 30, 900);
            $userOK = $ipOK && $this->rate($db, 'reset-account', $email, 5, 900);
            $members = $this->members(); $m = $members[$email] ?? null;
            $code = strtoupper(preg_replace('/[\s-]/', '', $code));
            $id = hash_hmac('sha256', $code, $db['key']);
            $r = $db['accounts'][$email]['resets'][$id] ?? null;
            if (!$ipOK || !$userOK || !$m || $m['expires'] < time() || !$r || $r['until'] <= time() || !hash_equals($m['stamp'], $r['stamp'])) return false;
            $db['accounts'][$email] = array('hash'=>$this->hashPassword($password), 'sessions'=>array(), 'resets'=>array());
            $this->clearLoginFailures($db, $email);
            return true;
        });
    }
    public function csrf($scope, $binding, $token = null) {
        return $this->transaction(function(&$db) use($scope, $binding, $token) {
            if (!is_string($binding) || strlen($binding) < 32) return $token === null ? '' : false;
            if ($token === null) { $until = time()+3600; return $until.'.'.hash_hmac('sha256', $scope.'|'.$binding.'|'.$until, $db['key']); }
            if (!is_string($token) || !preg_match('/^([0-9]+)\.([a-f0-9]{64})$/D', $token, $m) || (int)$m[1] < time() || (int)$m[1] > time()+3600) return false;
            return hash_equals(hash_hmac('sha256', $scope.'|'.$binding.'|'.$m[1], $db['key']), $m[2]);
        });
    }
    public function saveMembers($text, $expected = null, $keepToken = null) {
        if (!is_string($text) || strlen($text)>1000000) throw new RuntimeException('Invalid user list.');
        $this->transaction(function(&$db) use($text,$expected,$keepToken) {
            $path=$this->root.'/userdatei/userdatei.txt';
            if ($expected !== null && !hash_equals(hash_file('sha256',$path),$expected)) throw new DomainException('The user list changed. Reload before saving.');
            $keepUser=$keepToken ? $this->sessionUser($db,$keepToken,$this->members()) : '';
            $keepId=$keepToken ? hash('sha256',$keepToken) : '';
            $keepSession=$keepUser ? $db['accounts'][$keepUser]['sessions'][$keepId] : null;
            $tmp = $this->root.'/userdatei/users-'.bin2hex(random_bytes(12)).'.php';
            try {
                if (file_put_contents($tmp, $text) !== strlen($text) || !rename($tmp, $this->root.'/userdatei/userdatei.txt')) throw new RuntimeException('Could not save the user list.');
            } finally { if (is_file($tmp)) @unlink($tmp); }
            $members = $this->members();
            foreach ($db['accounts'] as $email=>&$a) {
                if (!isset($members[$email])) { unset($db['accounts'][$email]); continue; }
                foreach ($a['sessions'] as $id=>$s) if (!hash_equals($members[$email]['stamp'], $s['stamp'])) unset($a['sessions'][$id]);
                foreach ($a['resets'] as $id=>$r) if (!hash_equals($members[$email]['stamp'], $r['stamp'])) unset($a['resets'][$id]);
            } unset($a);
            if ($keepUser && isset($members[$keepUser]) && $members[$keepUser]['expires']>=time()) {
                $keepSession['stamp']=$members[$keepUser]['stamp'];
                $db['accounts'][$keepUser]['sessions'][$keepId]=$keepSession;
            }
        });
    }
}

function nadin_auth() {
    static $auth; global $salt, $timeout, $sticksessiontoip;
    if (!$auth) $auth = new NadinAuth(dirname(__DIR__), $salt, $timeout, $sticksessiontoip === 'yes', $_SERVER['REMOTE_ADDR'] ?? '');
    return $auth;
}
function nadin_cookie_name($kind = 'session') { return 'nadin_'.$kind.'_'.substr(hash('sha256', dirname(__DIR__)), 0, 12); }
function nadin_cookie_path() {
    $relative = substr(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? ''), strlen(str_replace('\\', '/', dirname(__DIR__))));
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    return $relative && substr($script, -strlen($relative)) === $relative ? (substr($script, 0, -strlen($relative)) ?: '').'/' : '/';
}
function nadin_cookie($name, $value, $expires) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443;
    setcookie($name, $value, array('expires'=>$expires, 'path'=>nadin_cookie_path(), 'secure'=>$secure, 'httponly'=>true, 'samesite'=>'Lax'));
    $_COOKIE[$name] = $value;
}
function nadin_session_token() { $s = $_COOKIE[nadin_cookie_name()] ?? ''; return is_string($s) ? $s : ''; }
function nadin_set_session($token) { global $timeout; nadin_cookie(nadin_cookie_name(), $token, time()+max(60, (int)$timeout)); }
function nadin_form_binding() {
    $name = nadin_cookie_name('form'); $nonce = $_COOKIE[$name] ?? '';
    if (!is_string($nonce) || !preg_match('/^[a-f0-9]{64}$/D', $nonce)) {
        $nonce = bin2hex(random_bytes(32)); nadin_cookie($name, $nonce, time()+7200);
    }
    return $nonce;
}
function nadin_require_login() {
    global $uc; if ($uc !== '') return;
    $url = nadin_cookie_path().'login.php';
    if (!headers_sent()) { header('Location: '.$url, true, 303); }
    else echo '<script>location.href='.json_encode($url, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';</script><a href="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'">Log in</a>';
    exit;
}
