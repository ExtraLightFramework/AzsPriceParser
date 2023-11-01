<?php

require_once 'header.php';
require_once 'functions.php';
if (!empty($_POST['mess']))
	write_log($_POST['mess']);
