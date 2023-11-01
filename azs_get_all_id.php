<?php

require_once 'header.php';
require_once 'functions.php';

if ($ret = get_all_azs_id($_POST))
	echo json_encode(['success'=>'Данные по АЗС получены','azs'=>$ret]);
else
	echo json_encode(['error'=>'Не найдено ни одной АЗС']);