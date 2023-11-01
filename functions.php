<?php
/////////////////////////////////////////
/////////// REPORTS /////////////////////
/////////////////////////////////////////
function report_create_selector($dt = '') {
	global $report_data;
	$st = 0;
	$ret = '<b>отчетные данные не обнаружены</b>';
	if ($res = _query("SELECT *,FROM_UNIXTIME(`tm`,'%d.%m.%Y') AS `tm_h` FROM apar_prices GROUP BY `tm_h` ORDER BY `tm` DESC")) {
		$ret = '<form action="/" method="post" class="ilb" id="report-by-date">';
		$ret .= '<select name="report_date" onchange="showWW();$(\'#report-by-date\').submit()">';
		$ret .= '<option value="">укажите дату</option>';
		$cdt = '';
		foreach ($res as $v) {
			if ($cdt != $v['tm_h']) {
				$cdt = $v['tm_h'];
				$ret .= "<option value='{$cdt}' ".($cdt==$dt?'selected="selected"':'').">{$cdt}</option>";
			}
		}
		$ret .= '</select></form>';
		if ($dt)
			$report_data = report_data($dt);
	}
	return $ret;
}
function report_data($dt) {
	$ret = [];
	if ($beg = date2timestamp($dt)) {
		$end = $beg + SECONDS_IN_DAY;
		if (_query("SELECT * FROM apar_prices WHERE `tm`>={$beg} AND `tm`<{$end} LIMIT 1")
			&& ($fuels = fuels_for_view())
			&& ($regions = get_regions_ids())) {//_query("SELECT * FROM apar_fuels"))) {
			foreach ($regions as $kr=>$r) {
				$region_own_prices = $region_child_prices = [];
				if ($azs = _query("SELECT
										(SELECT t2.`name` FROM apar_brands t2 WHERE t2.`id`=t1.`brand_id`) AS `brand_name`,
										'{$r['name']}' AS `region_name`,
										'{$kr}' AS `region_code`,
										t1.* 
									FROM apar_azs t1
									WHERE `brand_own`=1 AND `azs_id`>0 AND `region_id` IN ({$r['ids']})
									GROUP BY `azs_id`
									ORDER BY `azs_id`")) { // OWN
					foreach ($azs as $k=>$v) {
						if ($v['prices'] = _query("SELECT
															(SELECT t2.`name` FROM apar_fuels t2 WHERE t2.`id`=t1.`fuel_id`) AS `fuel_original_name`,
															(SELECT t3.`name` FROM apar_fuel_groups t3 WHERE t3.`id`=(SELECT t4.`fuel_group_id` FROM apar_fuels t4 WHERE t4.`id`=t1.`fuel_id`)) AS `fuel_name`,
															t1.`price`,
															t1.`yandex_date`
														FROM apar_prices t1
														WHERE t1.`azs_id`={$v['id']} AND t1.`tm`>={$beg} AND t1.`tm`<{$end}
														GROUP BY t1.`fuel_id`")) {
							$v['yandex_date'] = $v['prices'][0]['yandex_date'];
							$v['prices'] = normalize_prices($v['prices']);
						}
						$region_own_prices[] = $v['prices'];
						if ($v['childs'] = _query("SELECT 
															t1.*,
															(SELECT t3.`name` FROM apar_brands t3 WHERE t3.`id`=t2.`brand_id`) AS `brand_name`,
															'{$r['name']}' AS `region_name`,
															'{$kr}' AS `region_code`,
															t2.*
														FROM apar_azs_loc_markets t1, apar_azs t2
														WHERE t1.`azs_parent`={$v['id']}
															AND t2.`id`=t1.`azs_child`
															AND t2.`brand_own`=0
															AND t2.`has_prices`=1")) { // CHILDS
							$azs_loc_prices = [];
							foreach ($v['childs'] as $cc=>$c) {
								if ($v['childs'][$cc]['prices'] = _query("SELECT
																			(SELECT t2.`name` FROM apar_fuels t2 WHERE t2.`id`=t1.`fuel_id`) AS `fuel_original_name`,
																			(SELECT t3.`name` FROM apar_fuel_groups t3 WHERE t3.`id`=(SELECT t4.`fuel_group_id` FROM apar_fuels t4 WHERE t4.`id`=t1.`fuel_id`)) AS `fuel_name`,
																				t1.`price`,
																				t1.`yandex_date`
																				FROM apar_prices t1
																				WHERE t1.`azs_id`={$c['id']} AND t1.`tm`>={$beg} AND t1.`tm`<{$end}
																				GROUP BY t1.`fuel_id`")) {
									$v['childs'][$cc]['yandex_date'] = $v['childs'][$cc]['prices'][0]['yandex_date'];
									$v['childs'][$cc]['prices'] = normalize_prices($v['childs'][$cc]['prices']);
									$region_child_prices[] = $azs_loc_prices[] = $v['childs'][$cc]['prices'];
								}
							}
							$v['loc_prices'] = normalize_avr_prices($azs_loc_prices, $fuels);
						}

						$ret[] = $v;
						
					} // end foreach OWN azs
					
				} // end if OWN azs
				$regions[$kr]['own_prices'] = normalize_avr_prices($region_own_prices, $fuels);
				$regions[$kr]['child_prices'] = normalize_avr_prices($region_child_prices, $fuels);
				
			} // end foreach regions
			$ret['regions'] = $regions;
			$ret['fuels'] = $fuels;
			$ret['dt'] = $dt;
		}
	}
	return $ret;
}
function normalize_prices($prices) {
	foreach ($prices as $v)
		$ret[$v['fuel_name']] = $v['price'];
	return $ret;
}
function normalize_avr_prices($prices, $fuels) {
	$ret = [];
	if (sizeof($prices)) {
		foreach ($fuels as $f)
			$ret[$f['name']] = 0;
		$cnt = [];
		foreach ($prices as $azs_prices) {
			if (is_array($azs_prices) && sizeof($azs_prices)) {
				foreach ($azs_prices as $fuel=>$price) {
//					if (!isset($ret[$fuel]))
//						$ret[$fuel] = 0;
					if ($price > 0 && isset($ret[$fuel])) {
						$ret[$fuel] += $price;
						if (!isset($cnt[$fuel]))
							$cnt[$fuel] = 0;
						$cnt[$fuel] ++;
					}
				}
			}
		}
		foreach ($ret as $fuel=>$price)
			if (isset($cnt[$fuel]) && $cnt[$fuel])
				$ret[$fuel] = number_format($price/$cnt[$fuel], 2, '.', '');
			else
				$ret[$fuel] = 0;
	}
	return $ret;
}

/////////////////////////////////////////////
///////////// AZS ///////////////////////////
/////////////////////////////////////////////

function get_own_azs($with_nums = false) {
	return _query("SELECT (SELECT (SELECT t2.`name` FROM apar_regions t2
									WHERE t2.`code`=t3.`code`
									GROUP BY t2.`code` ORDER BY t2.`code`,t2.`id`)
							FROM apar_regions t3 WHERE t3.`id`=t1.`region_id`) AS `region_name`,
						  (SELECT t3.`name` FROM apar_brands t3 WHERE t3.`id`=t1.`brand_id`) AS `brand_name`,
							t1.*
					FROM apar_azs t1 WHERE t1.`brand_own`=1 ".($with_nums?"AND `azs_id`>0":"")." ORDER BY t1.`id`");
}
function get_all_azs_id($data) {
	if ($ret = _query("SELECT * FROM `apar_parse_monitor` WHERE `tm`='".date('Y-m-d')."'"))
		$data['from_id'] = $ret[0]['azs_id'];
	return _query("SELECT `id` FROM apar_azs WHERE `id`>".(!empty($data['from_id'])?(int)$data['from_id']:1000000)." AND `has_prices`=1 ".(!empty($data['test_pr'])?" AND `test_pr`=1":"")." ORDER BY `id`");
}
function get_all_azs($own = true) {
	if (!empty($_SESSION['region_master_code']))
		$region_ids = get_regions_ids($_SESSION['region_master_code'])[$_SESSION['region_master_code']]['ids'];
	$ret =  _query("SELECT (SELECT (SELECT t2.`name` FROM apar_regions t2
									WHERE t2.`code`=t3.`code`
									GROUP BY t2.`code` ORDER BY t2.`code`,t2.`id`)
							FROM apar_regions t3 WHERE t3.`id`=t1.`region_id`) AS `region_name`,
						  (SELECT t3.`name` FROM apar_brands t3 WHERE t3.`id`=t1.`brand_id`) AS `brand_name`,
							t1.*
						FROM apar_azs t1 WHERE t1.`brand_own`=1 ".
										(!empty($region_ids)?" AND `region_id` IN ({$region_ids})":"")." ORDER BY t1.`azs_id`");
	if ($own) {
		if ($ret) {
			foreach ($ret as $k=>$v) {
				$ids = '';
				if ($childs = _query("SELECT * FROM  apar_azs_loc_markets WHERE `azs_parent`={$v['id']}")) {
					foreach ($childs as $c)
						$ids .= ($ids?',':'').$c['azs_child'];
				}
				if ($ids)
					$ret[$k]['childs'] = _query("SELECT (SELECT (SELECT t2.`name` FROM apar_regions t2
															WHERE t2.`code`=t3.`code`
															GROUP BY t2.`code` ORDER BY t2.`code`,t2.`id`)
															FROM apar_regions t3 WHERE t3.`id`=t1.`region_id`) AS `region_name`,
											  (SELECT t3.`name` FROM apar_brands t3 WHERE t3.`id`=t1.`brand_id`) AS `brand_name`,
												t1.*
											FROM apar_azs t1 WHERE t1.`id` IN ({$ids}) ORDER BY t1.`azs_id`");
			}
		}
		return $ret;
	}
	else {
		$selector = '-';
		if ($ret) {
			$selector = '<select title="установить данную АЗС конкурентом из списка" onchange="_set_childs_azs_for(this.value,%%cid%%)">';
			$selector .= '<option value="0">настройка конкурентов</option>';
			$selector .= '<option value="unset">НЕ конкурент для ВСЕХ АЗС</option>';
			foreach ($ret as $v) {
				$selector .= '<option value="'.$v['id'].'">'.($v['azs_id']?$v['azs_id']:$v['id']).'</option>';
			}
			$selector .= '</select>';
		}
		return [_query("SELECT (SELECT (SELECT t2.`name` FROM apar_regions t2
									WHERE t2.`code`=t3.`code`
									GROUP BY t2.`code` ORDER BY t2.`code`,t2.`id`)
									FROM apar_regions t3 WHERE t3.`id`=t1.`region_id`) AS `region_name`,
						  (SELECT t3.`name` FROM apar_brands t3 WHERE t3.`id`=t1.`brand_id`) AS `brand_name`,
							t1.*
						FROM apar_azs t1 WHERE t1.`brand_own`=0 ".
												(!empty($region_ids)?" AND `region_id` IN ({$region_ids})":"")." ORDER BY t1.`azs_id`"), $selector];
	}
}
function get_all_foreing_azs() {
	$selector = '-';
	if ($ret =  _query("SELECT (SELECT (SELECT t2.`name` FROM apar_regions t2
									WHERE t2.`code`=t3.`code`
									GROUP BY t2.`code` ORDER BY t2.`code`,t2.`id`)
							FROM apar_regions t3 WHERE t3.`id`=t1.`region_id`) AS `region_name`,
						  (SELECT t3.`name` FROM apar_brands t3 WHERE t3.`id`=t1.`brand_id`) AS `brand_name`,
							t1.*
						FROM apar_azs t1 WHERE t1.`brand_own`=1 ORDER BY t1.`azs_id`")) {
		$selector = '<select title="установить данную АЗС конкурентом из списка" onchange="_set_childs_azs_for(this.value,%%cid%%)">';
		$selector .= '<option value="0">настройка конкурентов</option>';
		$selector .= '<option value="unset">НЕ конкурент для ВСЕХ АЗС</option>';
		foreach ($ret as $v) {
			if ($v['azs_id'])
				$selector .= '<option value="'.$v['id'].'" title="'.$v['id'].'">'.$v['azs_id'].' ('.$v['region_name'].')</option>';
		}
		$selector .= '</select>';
	}
	return [_query("SELECT (SELECT (SELECT t2.`name` FROM apar_regions t2
									WHERE t2.`code`=t3.`code`
									GROUP BY t2.`code` ORDER BY t2.`code`,t2.`id`)
									FROM apar_regions t3 WHERE t3.`id`=t1.`region_id`) AS `region_name`,
						  (SELECT t3.`name` FROM apar_brands t3 WHERE t3.`id`=t1.`brand_id`) AS `brand_name`,
							t1.*
						FROM apar_azs t1
						WHERE t1.`brand_own`=0
							AND (SELECT COUNT(*) FROM apar_azs_loc_markets t4 WHERE t4.`azs_child`=t1.`id`)=0 ORDER BY t1.`id`"),$selector];
}
function get_all_azs_with_price() {
	$ret = [];
	if (!empty($_SESSION['fuel_name'])
		&& (empty($_SESSION['map_zoom']) || ($_SESSION['map_zoom'] >= 7))
		&& !empty($_GET['bbox'])) {
		if (empty($_SESSION['map_zoom']))
			$_SESSION['map_zoom'] = 17;
		$zoomc = [
			4  => 65000,
			5  => 65000,
			6  => 65000,
			7  => 50000,
			8  => 30000,
			9  => 25000,
			10 => 20000,
			11 => 11000,
			12 => 7000,
			13 => 3000,
			14 => 800,
			15 => 650,
			16 => 300,
			17 => 150,
			18 => 100,
			19 => 50,
			20 => 25,
			21 => 12,
		];
		list($fuel_name, $fuel_ids) = explode(":",$_SESSION['fuel_name']);
		$coords = explode(',',$_GET['bbox']);
		$from = !empty($_SESSION['from'])?date2timestamp($_SESSION['from']):null;
		$to = !empty($_SESSION['to'])?date2timestamp($_SESSION['to'])+SECONDS_IN_DAY:null;
/*		if (!$from && !$to) {
			$to = time();
			$from = $to - SECONDS_IN_MONTH;
		}
		elseif (!$from) {
			$from = $to - SECONDS_IN_MONTH;
		}
		elseif (!$to) {
			$to = $from + SECONDS_IN_MONTH;
		}
		elseif ($from > $to) {
			$v = $from;
			$from = $to;
			$to = $v;
		}*/
//		echo date('d.m.Y', $from).' '.date('d.m.Y', $to);
//		$_SESSION['from'] = date('d.m.Y', $from);
//		$_SESSION['to'] = date('d.m.Y', $to);
		$spills = [];
		$max_spill = 0;
		$total_spill = 0;
		if ($out = _query("SELECT *,
							(SELECT SUM(t2.`price`)/COUNT(t2.`price`)
								FROM `apar_prices` t2
								WHERE t2.`azs_id`=t1.`id`
										AND t2.`fuel_id` IN ({$fuel_ids})
										AND t2.`tm`>={$from} AND t2.`tm`<={$to}) AS `price`,
							(SELECT SUM(t3.`volume`)
								FROM `apar_spillage` t3
								WHERE t3.`azs_id`=t1.`id`
										AND t3.`fuel_id` IN ({$fuel_ids})
										AND t3.`tm`>={$from} AND t3.`tm`<={$to}) AS `spillage`,
							(SELECT MIN(t3.`tm`)
								FROM `apar_spillage` t3
								WHERE t3.`azs_id`=t1.`id`
										AND t3.`fuel_id` IN ({$fuel_ids})
										AND t3.`tm`>={$from} AND t3.`tm`<={$to}) AS `spill_dt_start`,
							(SELECT MAX(t3.`tm`)
								FROM `apar_spillage` t3
								WHERE t3.`azs_id`=t1.`id`
										AND t3.`fuel_id` IN ({$fuel_ids})
										AND t3.`tm`>={$from} AND t3.`tm`<={$to}) AS `spill_dt_end`
							FROM apar_azs t1
							WHERE t1.`brand_own`=1
									AND t1.`id`>1000000
									
									AND t1.`has_prices`=1
									AND t1.`lat`>={$coords[0]}
									AND t1.`lat`<={$coords[2]}
									AND t1.`lng`>={$coords[1]}
									AND t1.`lng`<={$coords[3]}
							ORDER BY t1.`azs_id`")) {
			$ret = ['type'=>'FeatureCollection',
						'features'=> [],
						'spillage'=> [
							'type'=>'FeatureCollection',
							'features'=> []
						],
						'tlt'=> [
							'type'=>'FeatureCollection',
							'features'=> []
						],
						'sql' => str_replace(["\n","\t"],[" ",""],"SELECT *,
							".date('Y-m-d',$from)." AS `from_dt`,
							".date('Y-m-d',$to)." AS `to_dt`,
							(SELECT SUM(t2.`price`)/COUNT(t2.`price`)
								FROM `apar_prices` t2
								WHERE t2.`azs_id`=t1.`id`
										AND t2.`fuel_id` IN ({$fuel_ids})
										AND t2.`tm`>={$from} AND t2.`tm`<={$to}) AS `price`,
							(SELECT SUM(t3.`volume`)
								FROM `apar_spillage` t3
								WHERE t3.`azs_id`=t1.`id`
										AND t3.`fuel_id` IN ({$fuel_ids})
										AND t3.`tm`>={$from} AND t3.`tm`<={$to}) AS `spillage`
							FROM apar_azs t1
							WHERE t1.`brand_own`=1
									AND t1.`id`>1000000
									
									AND t1.`has_prices`=1
									AND t1.`lat`>={$coords[0]}
									AND t1.`lat`<={$coords[2]}
									AND t1.`lng`>={$coords[1]}
									AND t1.`lng`<={$coords[3]}
							ORDER BY t1.`azs_id`")
					];
			$childs_added = [];
			foreach ($out as $k=>$v) {
				$v['price'] = (float)number_format($v['price'], 2, '.', '');
//				$v['spillage'] = get_fuel_spillage_by_period($v['id'], $fuel_ids, $from, $to);
				if ($v['price']) {
					$feat = [
								'type'=>'Feature',
								'id'=>$v['id'],
								'geometry'=>[
									'type'=>'Point',
									'coordinates'=>[$v['lat'],$v['lng']]
								],
								'properties'=>[
									'iconContent'=> "№".($v['azs_id']?$v['azs_id']:'-')." {$v['price']}",
									'hintContent'=> "АЗС ".($v['azs_id']?$v['azs_id']:'-')." \"{$fuel_name}\" {$v['price']} руб.",
									'balloonContent'=> "АЗС ".($v['azs_id']?$v['azs_id']:'-')."\nСр.цена {$fuel_name} {$v['price']} Проливка {$v['spillage']}"
								],
								'options'=>[
									'preset'=>'islands#blueStretchyIcon',
									'zIndex'=>1000
								]
							];
				}
				if ($v['spillage']) {
					if ($max_spill < $v['spillage'])
						$max_spill = $v['spillage'];
					$dt1 = new DateTime(date('Y-m-d',$v['spill_dt_start']));
					$dt2 = new DateTime(date('Y-m-d',$v['spill_dt_end']));
					if ($v['avr_spill'] = (int)$dt1->diff($dt2)->format('%a'))
						$v['avr_spill'] = number_format($v['spillage']/$v['avr_spill'],2,'.','');
					else
						$v['avr_spill'] = $v['spillage'];
					$total_spill += $v['avr_spill'];
					
					$spill =[
								'type'=>'Feature',
								'id'=>$v['id'].'_spill',
								'geometry'=>[
									'type'=>'Circle',
									'coordinates'=>[$v['lat'],$v['lng']],
									'radius'=>$zoomc[$_SESSION['map_zoom']]
								],
								'properties'=>[
									'hintContent'=>"Проливка \"{$fuel_name}\" ".number_format($v['avr_spill'],2,'.','&nbsp;')." л./сут./%%percent%%",
									'spillage'=>$v['spillage'],
									'avr_spill'=>$v['avr_spill']
								],
								'options'=>[
									'fillColor'=>'#880000',
									'opacity'=>.9//,
									//'outline'=>false
								]
							];
					$tlt = 	[
								'type'=>'Feature',
								'id'=>$v['id'].'_tlt',
								'geometry'=>[
									'type'=>'Point',
									'coordinates'=>[$v['lat'],$v['lng']]
								],
								'properties'=>[
									'iconContent'=>number_format($v['avr_spill'],0,'.','&nbsp;')."л."
								],
								'options'=>[
									'iconLayout'=> 'default#imageWithContent',
									'iconImageHref'=> '/img/empty_point.png',
									'iconImageSize'=> [400, 50],
									'iconImageOffset'=> [-24, -60],
								]
							];
				}
				$childs_avr_price = 0;//
				if ($v['price'] && ($v['childs'] = _query("SELECT 
												t1.*,
												(SELECT t3.`name` FROM apar_brands t3 WHERE t3.`id`=t2.`brand_id`) AS `brand_name`,
												t2.*,
												(SELECT SUM(t4.`price`)/COUNT(t4.`price`)
													FROM `apar_prices` t4
													WHERE t4.`azs_id`=t1.`azs_child`
															AND t4.`fuel_id` IN ({$fuel_ids})
															AND t4.`tm`>={$from} AND t4.`tm`<={$to}) AS `price`
											FROM apar_azs_loc_markets t1, apar_azs t2
											WHERE t1.`azs_parent`={$v['id']}
												AND t2.`id`=t1.`azs_child`
												AND t2.`brand_own`=0
												AND t2.`has_prices`=1"))) {
					$cnt = 0;
					foreach ($v['childs'] as $kk=>$c) {
						$price = (float)number_format($c['price'], 2, '.', '');
						$childs_avr_price += $price;
						if ($price)
							$cnt ++;// 
						if ($price && !in_array($c['id'], $childs_added)) {
							$childs_added[] = $c['id'];
							$child = [
										'type'=>'Feature',
										'id'=>$c['id'],
										'geometry'=>[
											'type'=>'Point',
											'coordinates'=>[$c['lat'],$c['lng']]
										],
										'properties'=>[
											'iconContent'=> "{$price}",
											'hintContent'=> "{$c['brand_name']} \"{$fuel_name}\" {$price} руб.",
											'balloonContent'=> "Ср.цена {$fuel_name} {$price}"
										],
										'options'=>[
											'preset'=>'islands#darkGreenStretchyIcon'
										]
									];
							$ret['features'][] = $child;
						}
	//					$v['childs'][$kk]['spillage'] = get_fuel_spillage_by_period($c['id'], $fuel_ids, $from, $to);
					}
					if ($cnt)
						$childs_avr_price /= $cnt;
				}
				$percent = (float)number_format(($v['price']-$childs_avr_price)*($childs_avr_price/100), 2, '.', '');
				if ($percent < -.5) {
					$fillcolor = '#ffff00';
				}
				elseif ($percent >= -.5 && $percent < -.1) {
					$fillcolor = '#ffd700';
				}
				elseif ($percent >= -.1 && $percent < .1) {
					$fillcolor = '#c6c6fa';
				}
				elseif ($percent >= .1 && $percent < .5) {
					$fillcolor = '#9370db';
					$layLigth = true;
				}
				else {
					$fillcolor = '#483d8b';
					$layLigth = true;
				}
				if ($v['price']) {
					$feat['properties']['balloonContent'] .= ' (AVR: '.number_format($childs_avr_price, 2, '.', '').' '.$percent.'%)';
					$ret['features'][] = $feat;
				}
				if ($v['spillage']) {
					$spill['options']['fillColor'] = $fillcolor;
					$spill['properties']['hintContent'] .= ' (AVR: '.number_format($childs_avr_price, 2, '.', '').' '.$percent.'%)';
					if (!empty($layLigth))
						$tlt['options']['layLigth'] = true;
					$spills[] = $spill;
					$ret['tlt']['features'][] = $tlt;
				}
			}
		}
		if ($spills) {
			foreach ($spills as $k=>$s) {
				$percent = $s['properties']['avr_spill']/($total_spill/100);
				$spills[$k]['properties']['hintContent'] = str_replace("%%percent%%",ceil($percent).'%',$spills[$k]['properties']['hintContent']);
				$spills[$k]['geometry']['radius'] = (int)($zoomc[$_SESSION['map_zoom']]*(($percent<15?15:$percent)/100));
				$spills[$k]['options']['zIndex'] = (int)(101 - $percent);
			}
			$ret['spillage']['features'] = $spills;
		}
	}
	return $ret;
}
function get_avr_fuel_price_by_period($aid, $fids, $from, $to) {
	$ret = 0;
/*	echo "SELECT `price`
								FROM `apar_prices`
								WHERE `azs_id`={$aid}
										AND `fuel_id` IN ({$fids})
										AND `tm`>={$from} AND `tm`<={$to}";
*/		if ($res = _query("SELECT `price`
								FROM `apar_prices`
								WHERE `azs_id`={$aid}
										AND `fuel_id` IN ({$fids})
										AND `tm`>={$from} AND `tm`<={$to}")) {
		$ret = 0;
		foreach ($res as $k=>$v) {
			$ret += $v['price'];
		}
		$ret /= ($k+1);
	}
	return $ret?number_format($ret, 2, '.', ''):$ret;
}
function get_fuel_spillage_by_period($aid, $fids, $from, $to) {
	return ($ret = _query("SELECT SUM(`volume`) AS `volume`
								FROM `apar_spillage`
								WHERE `azs_id`={$aid}
										AND `fuel_id` IN ({$fids})
										AND `tm`>={$from} AND `tm`<={$to}"))?(int)$ret[0]['volume']:0;
}
function azs_type_selector($sel = '') {
	$types = [
		1=>'Городские АЗС',
		2=>'Трассовые АЗС',
		3=>'Мини-АЗС'
	];
	$ret = '<select id="azs_type" name="azs_type" onchange="save_filter_data(\'azs_type\',this.value)">';
	$ret .= '<option value="0">все АЗС</option>';
	foreach ($types as $k=>$v)
		$ret .= "<option value='{$k}' ".($k==$sel?"selected='selected'":"").">{$v}</option>";
	$ret .= '</select>';
	return $ret;
}
function azs_get_next() {
/*	if (!$data || !is_array($data) || !sizeof($data))
		throw new Exception('Неверный формат входных данных');
	if (!isset($data['id']))
		throw new Exception('Не указан ID АЗС {id: ""}');
*/	$dt = date('Y-m-d');
	if (!($last_azs = _query("SELECT * FROM apar_parse_monitor WHERE `tm`='{$dt}'"))) {
		if ($ret = _query("SELECT `id` FROM apar_azs WHERE `id`>1000000 ORDER BY `id` LIMIT 1", true)) {
			_query("INSERT INTO apar_parse_monitor (azs_id, tm) VALUES({$ret[0]['id']},'{$dt}')");
		}
	}
	else {
		if ($ret = _query("SELECT `id` FROM apar_azs WHERE `id`>{$last_azs[0]['azs_id']} ORDER BY `id` LIMIT 1", true)) {
			_query("UPDATE apar_parse_monitor SET `azs_id`={$ret[0]['id']} WHERE `tm`='{$dt}'");
		}
	}
	if ($cnt = _query("SELECT COUNT(*) AS `cnt` FROM apar_azs WHERE `id`>1000000 ORDER BY `id`")) {
		$out['cnt'] = $cnt[0]['cnt'];
		if (!empty($ret)
			&& ($azs_ptr = _query("SELECT COUNT(*) AS `cnt` FROM apar_azs WHERE `id`>1000000 AND `id`<={$ret[0]['id']} ORDER BY `id`")))
			$out['azs_ptr'] = $azs_ptr[0]['cnt'];
		else
			$out['azs_ptr'] = 0;
	}
	if (!empty($ret))
		$out['azs_id'] = $ret[0]['id'];
	else
		$out['azs_id'] = 0;
	return $out;
}
function azs_set_unprices($data) {
	$ret = false;
	if (!$data || !is_array($data) || !sizeof($data))
		throw new Exception('Неверный формат входных данных');
	if (empty($data['id']) || !(int)$data['id'])
		throw new Exception('Не указан ID АЗС {id: ""}');
	if (!($azs = get_by_id($data['id'], 'azs')))
		throw new Exception('Не найдена АЗС по указанному ID:'.$data['id']);
	else {
		$ret = _query("UPDATE apar_azs SET `has_prices`=0 WHERE `id`={$azs['id']}");
	}
	return $ret;
}
function azs_save_prices($data) {
	$ret = false;
	if (!$data || !is_array($data) || !sizeof($data))
		throw new Exception('Неверный формат входных данных');
	if (empty($data['id']) || !(int)$data['id'])
		throw new Exception('Не указан ID АЗС {id: ""}');
	if (!empty($data['prices']))
		$data['prices'] = json_decode($data['prices'], true);
	if (empty($data['prices']) || !is_array($data['prices']) || !sizeof($data['prices']))
		throw new Exception('Не найдены, либо имеют неверный формат цены по АЗС {prices: []}'.print_r($data, true));
	if (!($azs = get_by_id($data['id'], 'azs')))
		throw new Exception('Не найдена АЗС по указанному ID:'.$data['id']);
	else {
//		throw new Exception(print_r($data['prices'], true));
		foreach ($data['prices'] as $p) {
			if (!empty($p['fuel'])
				&& !empty($p['price'])
				&& ($p['price'] = (float)str_replace(',','.',$p['price']))) {
				if ($fuel = fuel_upd($p['fuel'])) {
					//if (!$ret)
					if (!_query("SELECT `tm` FROM apar_prices
									WHERE `azs_id`={$azs['id']}
										AND `fuel_id`={$fuel['id']}
										AND FROM_UNIXTIME(`tm`,'%d.%m.%Y')='".date('d.m.Y')."' limit 1")) {
//						$p['yandex_date'] = normalize_yandex_date($p['yandex_date']);
						$tm_upd = $tm = time();//normalize_yandex_date($p['yandex_date']);
						if ($prev_price = _query("SELECT `price`,`yandex_date`
													FROM apar_prices
													WHERE `azs_id`={$azs['id']}
														AND `fuel_id`={$fuel['id']}
													ORDER BY `yandex_date` DESC LIMIT 1")) {
							if ($prev_price[0]['price'] == $p['price']) {
								$tm_upd = $prev_price[0]['yandex_date'];
							}
						}
						_query("UPDATE apar_azs SET `has_prices`=1".(!empty($data['address'])?",`address`='".addslashes($data['address'])."'":"")." WHERE `id`={$azs['id']}");
						$ret = _query("INSERT INTO apar_prices (`azs_id`,`fuel_id`,`price`,`tm`,`yandex_date`)
										VALUES({$azs['id']},{$fuel['id']},{$p['price']},".$tm.",".$tm_upd.")");
//						if () {
//							if (!_query("UPDATE apar_parse_monitor SET `azs_id`={$azs['id']} WHERE `tm`='".date('Y-m-d')."'", true))
//								_query("INSERT INTO apar_parse_monitor (`azs_id`,`tm`) VALUES({$azs['id']},'".date('Y-m-d')."')", true);
//						}
					}
				}
			}
		}
	}
	return azs_get_next();
}
function azs_set_child($pid, $cid) {
	if ($pid == 'unset')
		_query("DELETE FROM `apar_azs_loc_markets` WHERE `azs_child`={$cid}");
	else
		_query("INSERT INTO `apar_azs_loc_markets` VALUES({$pid},{$cid})");
}
function azs_unset_child($pid, $cid) {
	_query("DELETE FROM `apar_azs_loc_markets` WHERE `azs_parent`={$pid} AND `azs_child`={$cid}");
}
function azs_save($data) {
	if (!$data || !is_array($data) || !sizeof($data))
		throw new Exception('Неверный формат входных данных');

	if (empty($data['azs_id']))
		$data['azs_id'] = 0;
	else
		$data['azs_id'] = (int)$data['azs_id'];

	if (empty($data['brand']))
		throw new Exception('Не указан бренд АЗС {brand: ""}');
	if (empty($data['region']))
		throw new Exception('Не указан регион АЗС {region: ""}');
	
	if (empty($data['ymaps_id'])
		|| !($data['ymaps_id'] = (int)$data['ymaps_id']))
		throw new Exception('Не указан или имеет формат отличный от INT YMAPS.ID АЗС (ИД организации) {ymaps_id: ""}');

	if (empty($data['brand_own']))
		$data['brand_own'] = 0;
	else
		$data['brand_own'] = 1;

	if (empty($data['address']))
		$data['address'] = '';
	else
		$data['address'] = addslashes($data['address']);
	if (empty($data['phone']))
		$data['phone'] = '';
	else
		$data['phone'] = addslashes($data['phone']);
	
	if (empty($data['lat']))
		$data['lat'] = 0;
	else
		$data['lat'] = (float)$data['lat'];

	if (empty($data['lng']))
		$data['lng'] = 0;
	else
		$data['lng'] = (float)$data['lng'];

	return azs_upd($data);
}
function azs_upd($data) {
	$ret = false;
	if (($brand = brand_upd($data['brand']))
		&& ($region = region_upd($data['region']))) {
		if ($data['azs_id'])
			$azs = azs_get_by_azs_id($data['azs_id']);
		if (($rec = get_by_id($data['ymaps_id'], 'azs')) && empty($azs)) { // update
			if ($ret = _query("UPDATE apar_azs SET
												".(!$rec['azs_id']?"`azs_id`={$data['azs_id']},":"")."
												`region_id`={$region['id']},
												`brand_id`={$brand['id']},
												`brand_own`={$data['brand_own']},
												`address`='{$data['address']}',
												`phone`='{$data['phone']}',
												`lat`={$data['lat']},
												`lng`={$data['lng']},
												`tm_update`=".time()."
									WHERE `id`={$rec['id']}"))
				$ret = $data['ymaps_id'];
		}
		elseif (!$rec && !empty($azs)) {
			if ($ret = _query("UPDATE apar_azs SET `id`={$data['ymaps_id']},
													`region_id`={$region['id']},
													`tm_update`=".time()."
									WHERE `id`={$azs['id']}"))
				$ret = $data['ymaps_id'];
		}
		elseif ($rec && !empty($azs)) {
			//_query("DELETE FROM apar_azs WHERE `id` IN ({$rec['id']},{$azs['id']})");
			$ret = $rec['id'];
		}
//		elseif (!empty($azs))
//			_query("DELETE FROM apar_azs WHERE `id`={$azs['id']}");
		if (!$ret) { // new
			_query("INSERT INTO apar_azs (`id`,`azs_id`,`region_id`,`brand_id`,`brand_own`,`address`,`phone`,`lat`,`lng`,`tm_create`,`tm_update`)
							VALUES ({$data['ymaps_id']},{$data['azs_id']},{$region['id']},
									{$brand['id']},{$data['brand_own']},'{$data['address']}','{$data['phone']}',
									{$data['lat']},{$data['lng']},".time().",".time().")");
			$ret = $data['ymaps_id'];
		}
	}
	else
		throw new Exception('Ошибка обновления бренда и/или региона АЗС');
	if ($ret && !empty($data['parents'])) {
		$parents = explode(',', $data['parents']);
		foreach ($parents as $p) {
			if ($p = (int)$p) {
				_query("INSERT INTO apar_azs_loc_markets (`azs_parent`,`azs_child`) VALUES({$p},{$ret})", true);
			}
		}
	}
	return $ret;
}
function brand_upd($name) {
	if (!($ret = brand_get($name))) {
		$ret['name'] = addslashes($name);
		$ret['id'] = _query("INSERT INTO apar_brands (`name`) VALUES('{$ret['name']}')");
	}
	return !empty($ret['id'])?$ret:null;
}
function region_upd($name) {
	if (!($ret = region_get($name))) {
		$ret['name'] = addslashes($name);
		$ret['id'] = _query("INSERT INTO apar_regions (`name`) VALUES('{$ret['name']}')");
	}
	return !empty($ret['id'])?$ret:null;
}
function fuel_upd($name) {
	$strick_fuels = ['ПРОПАН','КПГ','ГПГ'];
	$name = addslashes(trim(str_replace(['-'],[' '],$name)));
	if (!in_array($name, $strick_fuels)) {
		if (!($ret = fuel_get($name))) {
			$ret['name'] = $name;
			$ret['id'] = _query("INSERT INTO apar_fuels (`name`) VALUES('{$ret['name']}')");
		}
	}
	return !empty($ret['id'])?$ret:null;
}
function fuels_for_view() {
	return _query("SELECT * FROM apar_fuel_groups t1
					WHERE (SELECT COUNT(t2.`id`) FROM apar_fuels t2
							WHERE t2.`fuel_group_id`=t1.`id`) > 0");
}
function get_fuel_groups() {
	if ($ret['groups'] = _query("SELECT * FROM apar_fuel_groups ORDER BY `name`")) {
		foreach ($ret['groups'] as $k=>$v) {
			if ($ret['groups'][$k]['fuels'] = _query("SELECT * FROM apar_fuels WHERE `fuel_group_id`={$v['id']} ORDER BY `name`"))
				foreach ($ret['groups'][$k]['fuels'] as $kk=>$vv)
					$ret['groups'][$k]['fuels'][$kk]['selector'] = fuel_group_selector($vv['id'], $ret['groups'], $v['id']);
		}
	}
	if ($ret['ungroup_fuels'] = _query("SELECT * FROM apar_fuels WHERE `fuel_group_id`=0 ORDER BY `name`"))
		foreach ($ret['ungroup_fuels'] as $kk=>$vv)
			$ret['ungroup_fuels'][$kk]['selector'] = fuel_group_selector($vv['id'], $ret['groups']);
	return $ret;
}
function fuel_group_selector($fuel_id, $groups, $group_sel = 0) {
	$ret = null;
	$ret = '<select onchange="location.href=\'/fuels.php?fuel_id='.$fuel_id.'&fuel_group_id=\'+this.value">';
	$ret .= '<option value="0">------------------</option>';
	$ret .= '<option value="0">исключить из групп</option>';
	if ($groups) {
		foreach ($groups as $v) {
			if ($v['id']!=$group_sel)
				$ret .= '<option value="'.$v['id'].'" '.($v['id']==$group_sel?'selected="selected" disabled="disabled"':'').'>поместить в группу '.$v['name'].'</option>';
		}
	}
	else
		$ret .= '<option value="0" disabled="disabled">группы не найдены</option>';
	$ret .= '</select>';
	return $ret;
}
function fuel_selector($sel = '') {
	$ret = '<select id="fuel_name" name="fuel_name" onchange="save_filter_data(\'fuel_name\',this.value)">';
	if ($fuels = fuels_for_view()) {
		$ret .= '<option value="">укажите вид топлива</option>';
		foreach ($fuels as $v) {
			if ($res = _query("SELECT `id` FROM apar_fuels
								WHERE `fuel_group_id`={$v['id']}")) {
				$ids = '';
				foreach ($res as $f)
					$ids .= ($ids?',':'').$f['id'];
				$ret .= "<option value='{$v['name']}:{$ids}' ".($sel==$v['name'].':'.$ids?"selected='selected'":"").">{$v['name']}</option>";
			}
		}
	}
	else
		$ret .= '<option value="">виды топлива не найдены</option>';
	$ret .= '</select>';
	return $ret;
}
//////////////////////////////////////////////////////////

function azs_get_by_azs_id($azs_id) {
	return ($ret = _query("SELECT * FROM apar_azs WHERE `azs_id`=".(int)$azs_id." LIMIT 1"))?$ret[0]:null;
}
function brand_get($name) {
	return ($ret = _query("SELECT * FROM apar_brands WHERE `name`='".addslashes($name)."' LIMIT 1"))?$ret[0]:null;
}
function region_get($name) {
	return ($ret = _query("SELECT * FROM apar_regions WHERE `name`='".addslashes($name)."' LIMIT 1"))?$ret[0]:null;
}
function region_name_get($region_id) {
	return ($ret = _query("SELECT (SELECT t2.`name` FROM apar_regions t2
									WHERE t2.`code`=t1.`code`
									GROUP BY t2.`code` ORDER BY t2.`code`,t2.`id`) AS `region_name`
							FROM apar_regions t1 WHERE t1.`id`={$region_id}"))?$ret[0]['region_name']:null;
}
function get_regions() {
	return _query("SELECT * FROM apar_regions WHERE `code`>0 GROUP BY `code` ORDER BY `code`,`id`");
}
function get_regions_ids($master_code = 0) {
	$code = -1;
	$ids = [];
	if ($ret = _query("SELECT * FROM apar_regions ".($master_code?"WHERE `code`={$master_code}":"")." ORDER BY `code`,`id`")) {
		foreach ($ret as $v) {
			if ($code != $v['code']) {
				$code = $v['code'];
				$ids[$code] = [];
				$ids[$code]['name'] = $code?$v['name']:"необходимо установить код региона ({$v['name']})";
				$ids[$code]['ids'] = '';
			}
			$ids[$code]['ids'] .= $ids[$code]['ids']?','.$v['id']:$v['id'];
		}
	}
	return $ids;
}
function regions_selector($add = '') {
	$ret = '<select name="region_id" '.($add?'onchange="'.$add.'"':'').'>';
	if ($res = get_regions()) {
		$ret .= '<option value="0">выберите регион</option>';
		foreach ($res as $v)
			$ret .= '<option value="'.$v['code'].'" '.(isset($_SESSION['region_master_code']) && ($_SESSION['region_master_code']==$v['code'])?'selected="selected"':'').'>'.$v['name'].'</option>';
	}
	else
		$ret .= '<option value="0">регионы не найдены</option>';
	$ret .= '</select>';
	return $ret;
}
function fuel_get($name) {
	return ($ret = _query("SELECT * FROM apar_fuels WHERE `name`='".addslashes($name)."' LIMIT 1"))?$ret[0]:null;
}
function get_by_id($id, $table) {
	return ($ret = _query("SELECT * FROM apar_{$table} WHERE `id`=".(int)$id." LIMIT 1"))?$ret[0]:null;
}
////////////////////////////////////////////////////
function normalize_yandex_date($dt) {
	//29 марта 2022 
	$ret = 0;
	if ($dt) {
		$months = ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабра'];
		$dt = explode(' ', $dt);
		if (($k = array_search($dt[1], $months)) !== false) {
			$ret = date2timestamp($dt[0].'.'.($k+1).'.'.$dt[2]);
		}
	}
	return $ret;
}
//////////////////////// LOGS //////////////////////
function write_log($mess = '') {
	_query("INSERT INTO apar_logs (`dt`,`ip`,`mess`)
		VALUES('".date('Y-m-d H:i:s')."','".ip_addr()."','".addslashes($mess)."')");	
}
