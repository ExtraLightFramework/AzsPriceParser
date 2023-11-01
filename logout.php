<?php
require_once 'header.php';
require_once 'functions.php';
$_SESSION['uid'] = false;
$_SESSION['admin'] = false;
unset($_SESSION['uid'],
		$_SESSION['admin'],
		$_SESSION['from'],
		$_SESSION['to'],
		$_SESSION['fuel_name'],
		$_SESSION['map_zoom']);
write_log('Выход из системы');
header('Location: /');
exit;