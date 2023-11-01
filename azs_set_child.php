<?php
require_once('header.php');
require_once('functions.php');

	if (!empty($_GET['pid']) && !empty($_GET['cid']))
		azs_set_child((int)$_GET['pid'], (int)$_GET['cid']);
	header('Location: /'.(!empty($_GET['loc'])?$_GET['loc'].'.php':'azs.php'));
