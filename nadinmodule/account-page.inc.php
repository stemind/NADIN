<?php
function nadin_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function nadin_account_head($title) {
    header('Content-Type: text/html; charset=UTF-8'); header('Cache-Control: no-store'); header('Referrer-Policy: no-referrer');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>NADIN - '.nadin_h($title).'</title><link rel="stylesheet" href="style.css"><link rel="icon" href="favicon.ico"></head><body class="nadin-account-page"><main class="nadin-account-panel"><img src="nadink.gif" width="40" height="40" alt="" class="nadin-account-logo"><h1>NADIN <small>V14</small></h1><h2>'.nadin_h($title).'</h2>';
}
function nadin_account_end() { echo '<script src="nadinmodule/account.js"></script></main></body></html>'; }
function nadin_csrf_field($scope, $binding) { echo '<input type="hidden" name="csrf" value="'.nadin_h(nadin_auth()->csrf($scope,$binding)).'">'; }
function nadin_password_fields() {
    echo '<label for="new-password">New personal password</label><input id="new-password" name="new_password" type="password" autocomplete="new-password" required minlength="12" aria-describedby="password-hint"><p id="password-hint" class="nadin-form-hint">At least 12 characters. Spaces and paste are allowed. Capital letters matter.</p><label for="confirm-password">Repeat new password</label><input id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" required minlength="12">';
}
