<?php
require_once __DIR__ . '/../config/config.php';

unset($_SESSION['user_id']);
session_regenerate_id(true);
flash('You have been signed out of the admin panel.');
redirect('admin/login.php');
