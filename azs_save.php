<?php

require_once 'header.php';
require_once 'functions.php';

if ($ret = azs_save($_POST))
	echo json_encode(['success'=>'Данные по АЗС успешно сохранены','id'=>$ret]);
