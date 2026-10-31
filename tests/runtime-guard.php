<?php
/** Test-only PHP 9 readiness guard; never loaded by the application.
 * Promote notices that can be hidden by config.inc.php or @ to exceptions.
 * This is stricter than the accepted PHP 9 RFCs (also missing keys/offsets).
 * Run with: php -d auto_prepend_file=tests/runtime-guard.php ...
 */
set_error_handler(function ($level, $message, $file, $line) {
    if ($level === E_DEPRECATED || $level === E_USER_DEPRECATED ||
        preg_match('/^(Undefined (variable|array key)|Uninitialized string offset)/', $message)) {
        throw new ErrorException($message, 0, $level, $file, $line);
    }
    return false;
});
