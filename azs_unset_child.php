<?php
require_once('header.php');
require_once('functions.php');

	if (!empty($_GET['pid']) && !empty($_GET['cid']))
		azs_unset_child((int)$_GET['pid'], (int)$_GET['cid']);
	header('Location: /azs.php');
