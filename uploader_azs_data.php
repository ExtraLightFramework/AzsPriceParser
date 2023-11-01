<?php
require_once 'header.php';
require_once 'functions.php';

	if (file_exists('uploader_azs.csv')
		&& ($f = fopen('uploader_azs.csv', 'rb'))) {
		$i = 0;
		while ($s = fgets($f)) {
			$s = explode(';', $s);
			_query("
				INSERT INTO `apar_azs`
					(`id`,`azs_id`,`brand_id`,`brand_own`,`address`,`lat`,`lng`,`tm_create`,`tm_update`,
						`own_brand_type`,`comment`)
					VALUES (".(++$i).",{$s[0]},1,1,'{$s[3]}',{$s[1]},{$s[2]},".time().",".time().",'{$s[4]}','{$s[5]}')");
			echo $s[0].'<br />';
		}
		fclose($f);
		unlink('uploader_azs.csv');
	}
	else
		echo 'Can`t find uploader_azs.csv';