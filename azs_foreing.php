<?php require_once('header.php')?>
<?php require_once('functions.php')?>
<?php require_once('html_header.php');
	$azs = get_all_foreing_azs();
?>
<body>
	<?php require_once('menu.php');?>
	<h2>Сторонние АЗС</h2>
	<?php //print_r($azs[0]);?>
	<?php if ($azs[0]):?>
	<table class="tbl-view">
		<tr class="tbl-view-top tbl-view-top-azs">
			<td>#</td>
			<td>Бренд</td>
			<td>Информация</td>
		</tr>
	<?php foreach ($azs[0] as $v):?>
		<tr class="tbl-view-data">
			<td><a title="перейти на страницу АЗС на Яндекс картах" href="https://yandex.ru/maps/org/<?=$v['id']?>" target="_blank">lnk</a>
				<br /><a href="/azs.php?get_azs=<?=$v['id']?>" title="редактировать АЗС">ред.</a>
			</td>
			<td>
				ID: <?=$v['id']?><br />
				<?=$v['brand_name']?$v['brand_name']:'<b><i title="данные появятся после парсинга">&lt;появится позже&gt;</i></b>'?><br />
				<?=str_replace('%%cid%%',$v['id'],$azs[1])?>
			</td>
			<td>
				<div class="tbl-view-data-region">Регион: <?=$v['region_name']?$v['region_name']:'<b><i title="данные появятся после парсинга">&lt;появится позже&gt;</i></b>'?></div>
				<div class="tbl-view-data-address">Адрес: <?=$v['address']?$v['address']:'<b><i title="данные появятся после парсинга">&lt;появится позже&gt;</i></b>'?></div>
				<div class="tbl-view-data-latlng">Долгота: <?=$v['lat']?> Широта: <?=$v['lng']?></div>
			</td>
		</tr>
	<?php endforeach;?>
	</table>
	<?php endif; // endif $own?>
<script>
function _set_childs_azs_for(pid,cid) {
	location.href='/azs_set_child.php?pid='+pid+'&cid='+cid+'&loc=azs_foreing';
}
</script>
</body>
</html>
