#!/usr/bin/php
<?php
require_once 'header.php';
require_once 'functions.php';

	if (file_exists('update_azs.csv')
		&& ($f = fopen('update_azs.csv', 'rb'))) {
		$i = 0;
		while ($s = fgets($f)) {
			$s = explode(';', $s);
			if ($azs = azs_get_by_azs_id($s[0])) {
				_query("UPDATE `apar_azs`
							SET `own_brand_type`='{$s[4]}',`comment`='{$s[5]}'
							WHERE `azs_id`={$s[0]}");
				echo $s[0]." - OK\n";
			}
			else
				echo $s[0]." - Not Found\n";
		}
		fclose($f);
		unlink('update_azs.csv');
	}
	else
		echo 'Can`t find update_azs.csv';