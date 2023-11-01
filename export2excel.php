<?php
require_once 'header.php';
require_once 'functions.php';

include 'classes/PHPExcel.php';
define ('START_CELL_FULES_DATA', 'J');

if (empty($_GET['dt'])) {
	$_SESSION['error'] = "Не указана дата выгрузки";
}
elseif (!($report_data = report_data($_GET['dt']))) {
	$_SESSION['error'] = "Данные за указанную дату не найдены";
}
if (!empty($_SESSION['error'])) {
	header('Location: /');
	exit;
}

$dt = $_GET['dt'];

$excel = new PHPExcel;
$excel->setActiveSheetIndex(0);
$sheet = $excel->getActiveSheet();
$sheet->setTitle('Цены на АЗС за '.$dt);

$sheet->setCellValue('A1','№ пп');
$sheet->getStyle('A1')->getFont()->setBold(true);
$sheet->setCellValue('B1','№ АЗС');
$sheet->getStyle('B1')->getFont()->setBold(true);
$sheet->setCellValue('C1','YaID');
$sheet->getStyle('C1')->getFont()->setBold(true);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->setCellValue('D1','Регион');
$sheet->getStyle('D1')->getFont()->setBold(true);
$sheet->setCellValue('E1','Адрес  АЗС');
$sheet->getStyle('E1')->getFont()->setBold(true);
$sheet->setCellValue('F1','собственная/чужая');
$sheet->getStyle('F1')->getFont()->setBold(true);
$sheet->setCellValue('G1','Бренд');
$sheet->getStyle('G1')->getFont()->setBold(true);
$sheet->setCellValue('H1','Тип Бренда АЗС Газпром');
$sheet->getStyle('H1')->getFont()->setBold(true);
$sheet->setCellValue('I1','Тип расположения АЗС');
$sheet->getStyle('I1')->getFont()->setBold(true);

$j = ord(START_CELL_FULES_DATA);
foreach ($report_data['fuels'] as $k=>$v) {
	$sheet->setCellValue(chr($j).'1',$v['name']);
	$sheet->getStyle(chr($j++).'1')->getFont()->setBold(true);
}
$sheet->setCellValue(chr($j).'1','Дата мониторинга');
$sheet->getStyle(chr($j++).'1')->getFont()->setBold(true);
$sheet->setCellValue(chr($j).'1','Дата изменения цены');
$sheet->getStyle(chr($j).'1')->getFont()->setBold(true);

$i = 1;
$region_code = -1;
foreach ($report_data as $k=>$v) {
	if(is_numeric($k)) {
		if ($region_code != $v['region_code']) {
			$region_code = $v['region_code'];
			$i ++;
			$sheet->mergeCells("A{$i}:H{$i}");
			$sheet->setCellValue("A{$i}", "Средняя розничная цена по АЗС ОП {$report_data['regions'][$region_code]['name']}");
			$sheet->getStyle("A{$i}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
			$sheet->getStyle("A{$i}")->getFill()->getStartColor()->setRGB('D4EAFF');
			$sheet->getStyle("A{$i}")->getFont()->setBold(true);
			$sheet->getStyle("A{$i}")->getFont()->setSize(14);
			$j = ord(START_CELL_FULES_DATA);
			foreach ($report_data['regions'][$region_code]['own_prices'] as $price) {
				$sheet->getStyle(chr($j).$i)
					->getNumberFormat()
					->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
				$sheet->setCellValue(chr($j).$i, $price);
				$sheet->getStyle(chr($j).$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
				$sheet->getStyle(chr($j).$i)->getFill()->getStartColor()->setRGB('D4EAFF');
				$sheet->getStyle(chr($j).$i)->getFont()->setBold(true);
				$sheet->getStyle(chr($j++).$i)->getFont()->setSize(14);
			}

			$i ++;
			$sheet->mergeCells("A{$i}:H{$i}");
			$sheet->setCellValue("A{$i}", "Средняя розничная цена конкурентов по ОП {$report_data['regions'][$region_code]['name']}");
			$sheet->getStyle("A{$i}")->getFont()->getColor()->applyFromArray(['rgb' => 'AA0000']);
			$sheet->getStyle("A{$i}")->getFont()->setBold(true);
			$sheet->getStyle("A{$i}")->getFont()->setSize(13);
			$j = ord(START_CELL_FULES_DATA);
			foreach ($report_data['regions'][$region_code]['child_prices'] as $price) {
				$sheet->getStyle(chr($j).$i)
					->getNumberFormat()
					->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
				$sheet->setCellValue(chr($j).$i, $price);
				$sheet->getStyle(chr($j).$i)->getFont()->getColor()->applyFromArray(['rgb' => 'AA0000']);
				$sheet->getStyle(chr($j).$i)->getFont()->setBold(true);
				$sheet->getStyle(chr($j++).$i)->getFont()->setSize(13);
			}
		} // endif change REGION code
		$i ++;
////////////////// OWN AZS ////////////////
		$sheet->setCellValue('A'.$i, $k+1);
		$sheet->getStyle("A{$i}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$sheet->getStyle("A{$i}")->getFill()->getStartColor()->setRGB('F4FFFF');

		$sheet->setCellValue('B'.$i, "{$v['azs_id']}");
		$sheet->getStyle("B{$i}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$sheet->getStyle("B{$i}")->getFill()->getStartColor()->setRGB('F4FFFF');

		$sheet->getStyle("C{$i}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
		$sheet->setCellValue('C'.$i, "{$v['id']}");
		$sheet->getStyle("C{$i}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$sheet->getStyle("C{$i}")->getFill()->getStartColor()->setRGB('F4FFFF');

		$sheet->setCellValue('D'.$i, $v['region_name']);
		$sheet->getStyle("D{$i}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$sheet->getStyle("D{$i}")->getFill()->getStartColor()->setRGB('F4FFFF');

		$sheet->setCellValue('E'.$i, $v['address']?$v['address']:$v['id']);
		$sheet->getStyle("E{$i}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$sheet->getStyle("E{$i}")->getFill()->getStartColor()->setRGB('F4FFFF');

		$sheet->setCellValue('F'.$i, 'собственная');
		$sheet->getStyle("F{$i}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$sheet->getStyle("F{$i}")->getFill()->getStartColor()->setRGB('F4FFFF');

		$sheet->setCellValue('G'.$i, $v['brand_name']);
		$sheet->getStyle("G{$i}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$sheet->getStyle("G{$i}")->getFill()->getStartColor()->setRGB('F4FFFF');

		$sheet->setCellValue('H'.$i, $v['own_brand_type']);
		$sheet->getStyle("H{$i}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$sheet->getStyle("H{$i}")->getFill()->getStartColor()->setRGB('F4FFFF');

		$sheet->setCellValue('I'.$i, $v['comment']);
		$sheet->getStyle("I{$i}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$sheet->getStyle("I{$i}")->getFill()->getStartColor()->setRGB('F4FFFF');

		$j = ord(START_CELL_FULES_DATA);
		$report_data['fuels'][0]['loc_cnt'] = $report_data['fuels'][0]['loc_avg'] = 0;
		foreach ($report_data['fuels'] as $ff=>$f) {
			if (!empty($v['prices'][$f['name']])) {
				$sheet->getStyle(chr($j).$i)
					->getNumberFormat()
					->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
				$sheet->setCellValue(chr($j).$i,$v['prices'][$f['name']]);
			}
			else
				$sheet->setCellValue(chr($j).$i,'');
			$sheet->getStyle(chr($j).$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
			$sheet->getStyle(chr($j++).$i)->getFill()->getStartColor()->setRGB('F4FFFF');
		}
		$sheet->setCellValue(chr($j).$i,$report_data['dt']);
		$sheet->getStyle(chr($j).$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$sheet->getStyle(chr($j++).$i)->getFill()->getStartColor()->setRGB('F4FFFF');
		if (!empty($v['yandex_date'])) {
			$sheet->setCellValue(chr($j).$i,date('d.m.y', $v['yandex_date']));
			$sheet->getStyle(chr($j).$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
			$sheet->getStyle(chr($j).$i)->getFill()->getStartColor()->setRGB('F4FFFF');
		}
		if (!empty($v['loc_prices'])) {
			$i ++;
			$sheet->mergeCells("A{$i}:H{$i}");
			$sheet->setCellValue("A{$i}", "Среднее по локальному рынку АЗС №{$v['azs_id']}");
			$sheet->getStyle("A{$i}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
			$sheet->getStyle("A{$i}")->getFill()->getStartColor()->setRGB('FCFFB8');
			$sheet->getStyle("A{$i}")->getFont()->setBold(true);
			$j = ord(START_CELL_FULES_DATA);
			foreach ($v['loc_prices'] as $price) {
				$sheet->getStyle(chr($j).$i)
					->getNumberFormat()
					->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
				$sheet->setCellValue(chr($j).$i, $price);
				$sheet->getStyle(chr($j).$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
				$sheet->getStyle(chr($j).$i)->getFill()->getStartColor()->setRGB('FCFFB8');
				$sheet->getStyle(chr($j++).$i)->getFont()->setBold(true);
			}
		} // endif LOC_PRICES
/////////////// CHILDS ////////////////////
		if ($v['childs']) {
			foreach ($v['childs'] as $cc=>$c) {
				if(is_numeric($cc)) {
					$i ++;
					$sheet->getStyle("C{$i}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
					$sheet->setCellValue('C'.$i, $c['id']);
					$sheet->setCellValue('D'.$i, $v['region_name']);
					$sheet->setCellValue('E'.$i, $c['address']?$c['address']:$cc);
					$sheet->setCellValue('F'.$i, 'чужая');
					$sheet->setCellValue('G'.$i, $c['brand_name']);
					$sheet->setCellValue('H'.$i, $c['own_brand_type']);
					$sheet->setCellValue('I'.$i, $c['comment']);
					
					$j = ord(START_CELL_FULES_DATA);
					foreach ($report_data['fuels'] as $ff=>$f) {
						if (!empty($c['prices'][$f['name']])) {
							$sheet->getStyle(chr($j).$i)
								->getNumberFormat()
								->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
							$sheet->setCellValue(chr($j++).$i, $c['prices'][$f['name']]);
						}
						else
							$sheet->setCellValue(chr($j++).$i,'');
					}
					$sheet->setCellValue(chr($j++).$i,$report_data['dt']);
					if (!empty($c['yandex_date'])) {
						$sheet->setCellValue(chr($j).$i,date('d.m.y', $c['yandex_date']));
					}
				}
			}
		}
	}
}

ob_end_clean();
$fname = "xls/Сводный анализ цен АЗС за {$dt}.xlsx";

$writer = new PHPExcel_Writer_Excel2007($excel);
$writer->save($fname);

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=' . basename($fname));
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($fname));
 
readfile($fname);
@unlink($fname);
write_log('Экспорт в Excel '.basename($fname));
