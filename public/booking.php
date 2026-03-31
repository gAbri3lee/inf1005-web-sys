<?php
require_once __DIR__ . '/../app/includes/auth.php';

auth_flash_set('auth_notice', 'Choose your room and travel dates first, then continue to checkout from the suites page.');
auth_redirect('rooms_and_suites.php');
