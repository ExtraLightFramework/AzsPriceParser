<?php

require_once 'header.php';
require_once 'functions.php';

if ($ret = azs_set_unprices($_POST))
	echo json_encode(['success'=>'АЗС отмечена как не имеющая информации по ценам']);
