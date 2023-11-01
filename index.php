<?php require_once('header.php')?>
<?php require_once('functions.php')?>
<?php require_once('html_header.php');
	$report_data = null; // вычисляется в report_create_selector
?>
<script>
	setTimeout(() => showWW(), 200);
</script>
<body>
	<?php require_once('menu.php');?>
	<?php if (!empty($_SESSION['error'])):?>
	<div class="error">
		<?=$_SESSION['error']?>
		<a href="javascript:;" title="закрыть" onclick="$(this).closest('div').remove()">X</a>
	</div>
	<?php
	unset($_SESSION['error']);
	endif;?>
	<h1>Сводка <?=report_create_selector(!empty($_POST['report_date'])?$_POST['report_date']:'')?></h1>
	<?php if ($report_data):?>
	<?php //if (is_admin()):?>
	<div class="add-mnu">
		<a href="/export2excel.php?dt=<?=!empty($_POST['report_date'])?$_POST['report_date']:''?>" onclick="showWW()" title="экспортировать в Excel"><img src="img/excel.jpg" /></a>
	</div>
	<?php //endif;?>
	<div class="azs-prices" id="azs-prices-blck">
	<div class="azs-prices-cont">
	<table class="tbl-view">
		<thead class="fx-tbl-hd">
			<tr class="tbl-view-top">
				<td><div>№ пп</div></td>
				<td><div>№ АЗС</div></td>
<!--				<td width="100"><div>YandexID</div></td> -->
				<td><div>Регион</div></td>
				<td><div>Адрес  АЗС</div></td>
				<td><div>собственная/чужая</div></td>
				<td><div>Бренд</div></td>
				<td class="min-w"><div>Тип Бренда АЗС Газпром</div></td>
				<td class="min-w"><div>Тип расположения АЗС</div></td>
				<?php foreach ($report_data['fuels'] as $k=>$v):?>
					<td><div><?=$v['name']?></div></td>
				<?php $report_data['fuels'][$k] = $v;endforeach;?>
				<td><div>Дата мониторинга</div></td>
				<td><div>Дата изменения цены</div></td>
				<td class="min-w"><div>Страница</div></td>
<!--			<td>Цены</td>
-->			</tr>
		</thead>
		<?php
			$region_code = -1;
			foreach ($report_data as $k=>$v):if(is_numeric($k)):
			if ($region_code != $v['region_code']):
				$region_code = $v['region_code'];
		?>
		<tr class="tbl-view-data tbl-view-data-avg tbl-view-data-avg-itog">
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td colspan="6">Средняя розничная цена АЗС по ОП <?=$report_data['regions'][$region_code]['name']?></td>
			<?php foreach ($report_data['regions'][$region_code]['own_prices'] as $price):?>
				<td><?=$price?></td>
			<?php endforeach;?>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>

		</tr>
		<tr class="tbl-view-data tbl-view-data-avg tbl-view-data-avg-itog-childs">
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td colspan="6">Средняя розничная цена конкурентов по ОП <?=$report_data['regions'][$region_code]['name']?></td>
			<?php foreach ($report_data['regions'][$region_code]['child_prices'] as $price):?>
				<td><?=$price?></td>
			<?php endforeach;?>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>

		</tr>

			<?php 
			endif; // endif change REGION code
			////////////////// OWN //////////////// ?>
			<tr class="tbl-view-data tbl-view-data-parent">
				<td>
					<a href="javascript:;" onclick="$('#azs<?=$v['azs_id']?>').slideToggle();$(this).find('i').toggleClass('fa-minus-circle fa-plus-circle')"><i class="fas fa-minus-circle"></i></a>
					<?=''//$k+1?>
				</td>
				<td title="<?=$v['id']?>"><?=$v['azs_id']?></td>
<!--				<td><?=$v['id']?></td> -->
				<td><?=$v['region_name']?></td>
				<td><?=$v['address']?$v['address']:$v['id']?></td>
				<td>собственная</td>
				<td><?=$v['brand_name']?></td>
				<td><?=$v['own_brand_type']?></td>
				<td><?=$v['comment']?></td>
				<?php
					$report_data['fuels'][0]['loc_cnt'] = $report_data['fuels'][0]['loc_avg'] = 0;
					foreach ($report_data['fuels'] as $ff=>$f):
				?>
					<td>
						<?php if (!empty($v['prices'][$f['name']])) {
									echo $v['prices'][$f['name']];
								}
								else echo '-';
						?>
					</td>
				<?php endforeach;?>
				<td><?=$report_data['dt']?></td>
				<td><?=!empty($v['yandex_date'])?date('d.m.y', $v['yandex_date']):''?></td>
				<td><a title="<?=$v['id']?>" href="https://yandex.ru/maps/?orgpage%5Bid%5D=<?=$v['id']?>&utm_source=api-maps&from=api-maps" target="_blank">lnk</a></td>
<!--				<td><a href="javascript:;" onclick="_surf_once(<?=$v['id']?>)" title="получить цены по АЗС">цены</a></td>
-->			</tr>
			<?php if (!empty($v['loc_prices'])):?>
			<tr class="tbl-view-data tbl-view-data-avg">
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td colspan="6">Среднее по локальному рынку АЗС №<?=$v['azs_id']?></td>
				<?php foreach ($v['loc_prices'] as $price):?>
					<td><?=$price?></td>
				<?php endforeach;?>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>

			</tr>
			<?php endif;?>
			<tbody class="tbl-view-data-loc" id="azs<?=$v['azs_id']?>">
			<?php 
				/////////////// CHILDS ////////////////////
				if ($v['childs']):foreach ($v['childs'] as $cc=>$c):if(is_numeric($cc)):?>
				<tr class="tbl-view-data tbl-view-data-child">
					<td>&nbsp;</td>
					<td title="<?=$c['id']?>"><?=$c['id']?></td>
<!--					<td><?=$c['id']?></td> -->
					<td><?=$v['region_name']?></td>
					<td><?=$c['address']?$c['address']:$cc?></td>
					<td>чужая</td>
					<td><?=$c['brand_name']?></td>
					<td><?=$c['own_brand_type']?></td>
					<td><?=$c['comment']?></td>
					<?php foreach ($report_data['fuels'] as $ff=>$f):?>
						<td>
						<?php if (!empty($c['prices'][$f['name']])) {
									echo $c['prices'][$f['name']];
								}
								else echo '-';
						?>
						</td>
					<?php endforeach;?>
					<td><?=$report_data['dt']?></td>
					<td><?=!empty($c['yandex_date'])?date('d.m.y', $c['yandex_date']):''?></td>
					<td><a title="<?=$c['id']?>" href="https://yandex.ru/maps/?orgpage%5Bid%5D=<?=$c['id']?>&utm_source=api-maps&from=api-maps" target="_blank">lnk</a></td>
<!--					<td><a href="javascript:;" onclick="_surf_once(<?=$c['id']?>)" title="получить цены по АЗС">цены</a></td>
-->				</tr>
			<?php endif;//numeric key
				endforeach;//childs loop
				endif;//childs AZS?>
			</tbody>

		<?php endif;//numeric OWN key
			endforeach;//own AZS?>

	</table>
	</div>
	</div>
		<?php //print_r($report_data)?>
	<?php else:?>
	<h3>выберите дату отчета</h3>
	<?php endif;?>
</body>
<script>
$(function() {
	setTimeout(() => hideWW(), 1000);
	$('#azs-prices-blck').height($(window).height()-140);
	let maxh = 0;
	$('.tbl-view-top td').each(function() {
		$($(this).find('div')).width($(this).width());
		maxh = maxh < $($(this).find('div')).height()?$($(this).find('div')).height():maxh;
	});
//	console.log(maxh);
	$('.tbl-view-top td').height(maxh+40);
	$('.tbl-view-top td div').height(maxh+20);
//	alert($(window).height()-200);
});
</script>
</html>