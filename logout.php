<?php
require_once __DIR__ . '/lib/auth.php';
session_destroy();
redirect('index.php');
