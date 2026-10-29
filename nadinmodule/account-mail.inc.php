<?php
/** Keep a hosting-specific mail adapter here when deploying: see docs/V11.md. */
function nadin_account_mail($to, $subject, $message) {
    global $mymail, $useheadersinphpmailcommand;
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    // An optional local adapter survives future source updates and must return a boolean.
    $adapter = dirname(__DIR__).'/userdatei/mail-adapter.php';
    if (is_file($adapter)) require_once $adapter;
    if (function_exists('nadin_mail_transport')) return (bool)nadin_mail_transport($to, $subject, $message);
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8";
    if (($useheadersinphpmailcommand ?? 'no') === 'yes') {
        if (!filter_var($mymail, FILTER_VALIDATE_EMAIL)) return false;
        $headers .= "\r\nFrom: NADIN <".$mymail.">\r\nReply-To: ".$mymail;
    }
    return mail($to, $subject, $message, $headers);
}
