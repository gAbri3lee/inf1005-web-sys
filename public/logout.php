<?php
require_once __DIR__ . '/../app/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    auth_redirect('index.php');
}

if (!csrf_validate('logout_form', $_POST['csrf_token'] ?? '')) {
    auth_flash_set('auth_notice', 'Your logout request expired. Please try again.');
    auth_redirect(auth_is_logged_in() ? 'dashboard.php' : 'index.php');
}

auth_logout_user();
auth_redirect('index.php');
