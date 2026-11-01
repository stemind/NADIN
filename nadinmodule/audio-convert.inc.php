<?php
// Optional server-side conversion. No shell, network input or original-file replacement.
class NadinAudioException extends RuntimeException {
    public $status;
    public function __construct($message, $status = 422) { parent::__construct($message); $this->status = $status; }
}
function nadin_audio_formats() {
    return array('wav'=>'wav', 'aif'=>'aiff', 'aiff'=>'aiff', 'm4a'=>'mov', 'aac'=>'aac',
        'flac'=>'flac', 'ogg'=>'ogg', 'oga'=>'ogg', 'opus'=>'ogg', 'wma'=>'asf');
}
function nadin_audio_filename($original) {
    $clean = mkascii(striptime($original));
    while (strpos($clean, '..') !== false) $clean = str_replace('..', '.', $clean);
    return pathinfo($clean, PATHINFO_FILENAME).'.mp3';
}
function nadin_audio_previous($directory, $filename) {
    $matches = array();
    foreach (new DirectoryIterator($directory) as $entry) {
        $name = $entry->getFilename();
        if ($entry->isDot() || $entry->isLink() || !$entry->isFile() || $name[0]==='.' || strpos($name,'zzDEL!')===0) continue;
        if (strtolower(pathinfo($name,PATHINFO_EXTENSION))==='mp3' && nadin_audio_filename($name)===$filename) $matches[]=$entry->getPathname();
    }
    if (count($matches)>1) throw new NadinAudioException('Several recordings match this filename. Rename the new upload or the existing recordings first. No recording was changed.',409);
    return $matches ? $matches[0] : null;
}
function nadin_audio_run($arguments, $seconds, $output = null, $maxDuration = 0) {
    $process = @proc_open($arguments, array(0=>array('pipe','r'), 1=>array('pipe','w'), 2=>array('pipe','w')),
        $pipes, null, null, array('bypass_shell'=>true));
    if (!is_resource($process)) throw new NadinAudioException('Audio conversion is unavailable on this server. Upload an MP3 instead.', 503);
    fclose($pipes[0]); stream_set_blocking($pipes[1], false); stream_set_blocking($pipes[2], false);
    $started = microtime(true); $text = ''; $errors = ''; $exit = -1;
    try {
        while (true) {
            $text .= (string)stream_get_contents($pipes[1]); $errors .= (string)stream_get_contents($pipes[2]);
            // FFmpeg progress is bounded; retain the last complete duration report.
            if ($maxDuration > 0 && preg_match_all('/out_time_us=(\d+)/', $text, $matches)) {
                if (max(array_map('floatval', $matches[1])) > ($maxDuration + 0.1) * 1000000)
                    throw new NadinAudioException('This recording exceeds the conversion duration limit. Upload a shorter recording or an MP3.', 413);
            }
            $text = substr($text, -65536); $errors = substr($errors, -16384);
            if ($output !== null) {
                clearstatcache(true, $output);
                if (is_file($output) && filesize($output) > 100 * 1024 * 1024)
                    throw new NadinAudioException('The converted recording is too large. Upload a smaller recording.', 413);
            }
            $state = proc_get_status($process);
            if (!$state['running']) {
                $exit = $state['exitcode'];
                $text .= (string)stream_get_contents($pipes[1]); $errors .= (string)stream_get_contents($pipes[2]);
                break;
            }
            if (microtime(true) - $started > $seconds)
                throw new NadinAudioException('Audio conversion took too long. The existing recording was not changed. Upload an MP3 or a shorter recording.', 408);
            usleep(20000);
        }
    } finally {
        $state = proc_get_status($process);
        if ($state['running']) {
            proc_terminate($process);
            usleep(100000);
            if (proc_get_status($process)['running']) proc_terminate($process, 9);
        }
        fclose($pipes[1]); fclose($pipes[2]); proc_close($process);
    }
    return array('exit'=>$exit, 'text'=>$text, 'error'=>$errors);
}
function nadin_audio_capability() {
    static $cached;
    if ($cached !== null) return $cached;
    $cached = array('available'=>false, 'binary'=>'', 'seconds'=>90, 'duration'=>1800, 'bytes'=>100*1024*1024);
    $cached['seconds'] = max(1, min(90, (int)($GLOBALS['audioconversiontimeout'] ?? 90)));
    $cached['duration'] = max(1, min(1800, (int)($GLOBALS['audiomaxseconds'] ?? 1800)));
    foreach (array('proc_open','proc_get_status','proc_terminate','proc_close') as $function)
        if (!function_exists($function)) return $cached;
    $configured = $GLOBALS['audioffmpeg'] ?? 'auto';
    if (!is_string($configured) || $configured === '' || strlen($configured)>4096 || strpos($configured,"\0")!==false) return $cached;
    $candidates = $configured === 'auto' ? array('/usr/bin/ffmpeg','/usr/local/bin/ffmpeg','ffmpeg') : array($configured);
    foreach ($candidates as $binary) {
        if (strpos($binary, '/') !== false && (!is_file($binary) || !is_executable($binary))) continue;
        try {
            $result = nadin_audio_run(array($binary,'-hide_banner','-encoders'), 3);
            if ($result['exit'] === 0 && preg_match('/\blibmp3lame\b/', $result['text'])) {
                $cached['available'] = true; $cached['binary'] = $binary; break;
            }
        } catch (Throwable $exception) { /* Missing or unusable tools leave MP3 upload available. */ }
    }
    return $cached;
}
function nadin_audio_convert($input, $extension) {
    $formats = nadin_audio_formats();
    if (!isset($formats[$extension])) throw new NadinAudioException('Choose an MP3 or one of the supported audio formats.', 415);
    $capability = nadin_audio_capability();
    if (!$capability['available']) throw new NadinAudioException('Audio conversion is unavailable on this server. Upload an MP3 instead.', 503);
    if (filesize($input) > $capability['bytes']) throw new NadinAudioException('Other audio formats may be up to 100 MB. Upload a smaller recording or an MP3.', 413);
    $output = tempnam(sys_get_temp_dir(), 'nadin-audio-');
    if ($output === false) throw new NadinAudioException('The server has no temporary storage for audio conversion. Try again later.', 503);
    $complete = false;
    try {
        // Force the selected audio demuxer: renamed playlists and other containers are rejected.
        // MOV external data references stay disabled; only the uploaded local file is read.
        $arguments = array($capability['binary'],'-hide_banner','-loglevel','error','-nostdin','-nostats','-y',
            '-protocol_whitelist','file','-f',$formats[$extension]);
        if($extension==='m4a')$arguments=array_merge($arguments,array('-enable_drefs','0','-use_absolute_path','0'));
        $arguments=array_merge($arguments,array('-threads','1','-i',$input,
            '-map','0:a:0','-vn','-sn','-dn','-map_metadata','0','-threads','1','-filter_threads','1',
            '-c:a','libmp3lame','-q:a','2','-t',(string)($capability['duration']+1),'-progress','pipe:1','-f','mp3',$output));
        // Conversion runs outside the global media-write lock; commit remains protected.
        $result = nadin_audio_run($arguments, $capability['seconds'], $output, $capability['duration']);
        clearstatcache(true, $output);
        if ($result['exit'] !== 0 || !is_file($output) || filesize($output)<100)
            throw new NadinAudioException('This audio file could not be converted. It may be damaged, protected or in an unsupported format. The existing recording was not changed.');
        // The final progress report can arrive after the child process has exited.
        if (preg_match_all('/out_time_us=(\d+)/', $result['text'], $times) && max(array_map('floatval',$times[1]))>($capability['duration']+0.1)*1000000)
            throw new NadinAudioException('This recording exceeds the conversion duration limit. Upload a shorter recording or an MP3.', 413);
        $complete = true;
        return $output;
    } finally {
        if (!$complete && is_file($output)) unlink($output);
    }
}
