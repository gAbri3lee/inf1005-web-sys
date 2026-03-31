<?php
require_once __DIR__ . '/../app/includes/auth.php';

auth_require_login(
    'dashboard.php',
    'Please sign in or create an account to access your dashboard.'
);

auth_redirect('dashboard.php');
