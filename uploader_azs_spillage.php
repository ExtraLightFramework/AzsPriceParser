#!/usr/bin/php
<?php
// в текущей директории должен находиться файл csv с разделителями TAB
// и с названием uploader_azs_spillage.csv
// запускать лучше из терминала ))
require_once 'header.php';
require_once 'functions.php';

	if (file_exists('uploader_azs_spillage.csv')
		&& ($f = fopen('uploader_azs_spillage.csv', 'rb'))) {
		$i = 0;
		$periods = [];
		while ($s = fgets($f)) {
			$v = explode("\t", $s);
			if (($i >= 10) && !$periods)
				break;
			if (!$periods) {
				if (isset($v[0], $v[1])
					&& $v[0]=='АЗС'
					&& $v[1]=='Номенклатура') {
					$k = 1;
					while (isset($v[++$k])) {
						if ($tm = date2timestamp($v[$k])) {
							$periods[] = $tm;
						}
					}
				}
			}
			elseif (isset($v[0], $v[1])
				&& preg_match("/^АЗС (\d+)$/", trim($v[0]), $matches)) {
				$v[0] = $matches[1];
				$v[1] = str_replace("-"," ",$v[1]);
				if (($azs = _query("SELECT * FROM apar_azs WHERE `azs_id`={$v[0]} LIMIT 1"))
					&& ($fuel = _query("SELECT * FROM apar_fuels WHERE `name`='{$v[1]}' AND `fuel_group_id`!=0 LIMIT 1"))) {
					echo $azs[0]['azs_id']." ".$fuel[0]['name']."\n";
					foreach ($periods as $k=>$p) {
						if (isset($v[$k+2])) {
							$volume = str_replace(" ","",$v[$k+2])?(int)str_replace(" ","",$v[$k+2]):0;
							if ($res = _query("SELECT id FROM apar_spillage
													WHERE `azs_id`={$azs[0]['id']}
															AND `fuel_id`={$fuel[0]['id']}
															AND `tm`={$p}"))
								@_query("UPDATE apar_spillage SET `volume`={$volume} WHERE `id`={$res[0]['id']}");
							else
								@_query("INSERT INTO apar_spillage (`azs_id`,`azs_own_id`,`fuel_id`,
													`volume`,`tm`)
											VALUES({$azs[0]['id']},{$azs[0]['azs_id']},{$fuel[0]['id']},
													{$volume},{$p})");
						}
					}
				}
			}
			$i ++;
		}
		fclose($f);
//		unlink('uploader_azs_spillage.csv');
		if (!$periods) {
			echo "Spillage periods not found\n";
			echo "In first 10 rows. Check uploader_azs_spillage.csv file format\n";
		}
	}
	else
		echo "Can`t find uploader_azs_spillage.csv\n";