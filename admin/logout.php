<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset','UTF-8');
require __DIR__ . '/config.php';

unset($_SESSION['authed']);
if (function_exists('session_regenerate_id')) @session_regenerate_id(true);

header('Location: /admin/login.php');