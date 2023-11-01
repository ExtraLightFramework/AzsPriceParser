<?php

require_once 'header.php';
require_once 'functions.php';

if ($ret = get_own_azs(true))
	echo json_encode(['success'=>'АЗС найдены','data'=>$ret]);
else
	echo json_encode(['error'=>'Не найдено ни одной собственной АЗС. Для добавления и начала работы добавьте их через меню "Наши АЗС".']);
