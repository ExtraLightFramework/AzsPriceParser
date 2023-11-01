<?php
require_once 'header.php';
require_once 'functions.php';

if (!empty($_POST['pass']) && ((md5($_POST['pass']) == '2c009feb8fdca0a5e89dc0431f8e62bd') || ($_POST['pass'] == 'MeAdminGNP'))) {
	$_SESSION['uid'] = true;
	$_SESSION['admin'] = false;
	if ($_POST['pass'] == 'MeAdminGNP')
		$_SESSION['admin'] = true;
	write_log('Вход в систему'.($_SESSION['admin']?' (admin)':''));
	header('Location: '.(!empty($_POST['redirect'])?$_POST['redirect']:'/'));
	exit;
}
header('Location: /');
exit;
