<?php

require_once 'header.php';
require_once 'functions.php';

$ret = '';
if ($ret = get_all_azs_with_price()) // параметры вида топлива и периода будут браться
									// из $_SESSION['fuel_name']
									// из $_SESSION['from']
									// из $_SESSION['to']
	echo 'clbk('.json_encode($ret).')';
else
	echo 'clbk('.json_encode(['error'=>'Данные не найдены']).')';
