<?php require_once('header.php')?>
<?php require_once('functions.php')?>
<?php require_once('html_header.php');
	if (isset($_GET['region_master_code'])) {
		$_SESSION['region_master_code'] = (int)$_GET['region_master_code'];
	}
	if (!empty($_POST['edit'])) {
/*		if (empty($_POST['azs_id']) || !(int)$_POST['azs_id']) {
			$error = 'Некорректно указан ID АЗС. Формат - целое число';
			$edit = $_POST;
		}
		else
*/		if (!preg_match("/^\d{2}\.\d{6}$/",$_POST['lat'])
			|| !preg_match("/^\d{2}\.\d{6}$/",$_POST['lng'])) {
			$error = 'Некорректно указаны долгота или широта. Формат 43.120976 47.4016672';
			$edit = $_POST;
		}
		else {
			if ($azs = get_by_id($_POST['id'], 'azs')) { // edit
				if (!empty($_POST['azs_id'])
					&& ($rec = azs_get_by_azs_id($_POST['azs_id']))
					&& ($rec['id'] != $azs['id'])) {
					$error = 'АЗС с указанным ID уже существует в системе. Укажите другой ID, либо удалите АЗС с ID '.$_POST['azs_id'];
					$edit = $_POST;
				}
				else
					_query("UPDATE apar_azs SET `azs_id`=".(!empty($_POST['azs_id'])?(int)$_POST['azs_id']:0).",
							`lat`={$_POST['lat']},
							`lng`={$_POST['lng']},
							`comment`='{$_POST['comment']}',
							`brand_own`=".(isset($_POST['brand_own'])?1:0).",
							`own_brand_type`='{$_POST['own_brand_type']}' WHERE `id`={$azs['id']}");
			} 
			else { //new
				_query("INSERT INTO apar_azs (`id`,`azs_id`,`brand_own`,`lat`,`lng`,`tm_create`)
							VALUES(".rand(1,900000).",".(!empty($_POST['azs_id'])?(int)$_POST['azs_id']:0).",".(isset($_POST['brand_own'])?1:0).",".$_POST['lat'].",".$_POST['lng'].",".time().")");
			}
		}
	}
	if (!empty($_SESSION['region_master_code'])) {
		$own = get_all_azs();
		list($foreing, $own_selector) = get_all_azs(false);
		if (empty($edit)) {
			if (isset($_GET['get_azs'])) {
				if (!(int)$_GET['get_azs'])
					$edit = true;
				else
					$edit = get_by_id($_GET['get_azs'], 'azs');
			}
			else
				$edit = false;
		}
	}
	else
		$edit = false;
?>
<body>
	<?php require_once('menu.php');?>
	<h2>Собственные АЗС</h2>
	<?php if (!empty($error)):?>
	<div class="error">
		<?=$error?>
		<a href="javascript:;" title="закрыть" onclick="$(this).closest('div').remove()">X</a>
	</div>
	<?php endif;?>
	<?php if ($edit):?>
	<form action="/azs.php" method="post" class="frm-edt">
		<input type="hidden" name="edit" value="1" />
		<input type="hidden" name="id" value="<?=!empty($edit['id'])?$edit['id']:''?>" />
		<h3>Редактор АЗС</h3>
		<h4>АЗС № (ID)</h4>
		<input type="text" name="azs_id" value="<?=!empty($edit['azs_id'])?$edit['azs_id']:''?>" />
		<h4>Собственная</h4>
		<input type="checkbox" name="brand_own" <?=!empty($edit['brand_own'])?'checked="checked"':''?> />
		<h4>Долгота (lat)</h4>
		<input type="text" name="lat" value="<?=!empty($edit['lat'])?$edit['lat']:''?>" required="required" />
		<h4>Широта (lon)</h4>
		<input type="text" name="lng" value="<?=!empty($edit['lng'])?$edit['lng']:''?>" required="required" />
		<h4>Тип Бренда АЗС Газпром</h4>
		<input type="text" name="own_brand_type" value="<?=!empty($edit['own_brand_type'])?$edit['own_brand_type']:''?>" />
		<h4>Тип расположения АЗС</h4>
		<textarea name="comment" rows="5"><?=!empty($edit['comment'])?$edit['comment']:''?></textarea>
		<div>
			<input type="submit" value="Сохранить" />
			<input type="button" value="Отмена" onclick="location.href='/azs.php'" />
		</div>
	</form>
	<?php endif;?>
	<a href="/azs.php?get_azs=0" title="добавить новую АЗС">+ Добавить</a>
	<div>
	<br />
	<?=regions_selector("location.href='/azs.php?region_master_code='+this.value")?>
	</div>
	<?php if (!empty($_SESSION['region_master_code'])):?>
	<div class="azs-info-cont">
	
	<div class="azs-info-cont-blck">
	<?php if ($own):?>
	<h3>Собственные АЗС</h3>
	<table class="tbl-view">
		<tr class="tbl-view-top tbl-view-top-azs">
			<td>#</td>
			<td width="30">АЗС № (ID)</td>
			<td width="50">Бренд</td>
			<td>Тип Бренда<br />АЗС Газпром</td>
			<td>Информация</td>
			<td>Тип расположения АЗС</td>
			<td width="180">АЗС конкуренты</td>
		</tr>
	<?php foreach ($own as $v):?>
		<tr class="tbl-view-data">
			<td><a title="перейти на страницу АЗС на Яндекс картах" href="https://yandex.ru/maps/org/<?=$v['id']?>" target="_blank">lnk</a>
				<br /><a href="/azs.php?get_azs=<?=$v['id']?>" title="редактировать АЗС">ред.</a>
			</td>
			<td><?=$v['azs_id']?$v['azs_id']:'<b><i title="для отражения данной АЗС в отчетах, укажите её номер">&lt;укаж ите&nbsp;ном ер&gt;</i></b>'?></td>
			<td><?=$v['brand_name']?$v['brand_name']:'<b><i title="данные появятся после парсинга">&lt;появится позже&gt;</i></b>'?></td>
			<td><?=$v['own_brand_type']?></td>
			<td>
				<div class="tbl-view-data-region">Регион: <?=$v['region_name']?$v['region_name']:'<b><i title="данные появятся после парсинга">&lt;появится позже&gt;</i></b>'?></div>
				<div class="tbl-view-data-address">Адрес: <?=$v['address']?$v['address']:'<b><i title="данные появятся после парсинга">&lt;появится позже&gt;</i></b>'?></div>
				<div class="tbl-view-data-latlng">Долгота: <?=$v['lat']?> Широта: <?=$v['lng']?></div>
			</td>
			<td><?=$v['comment']?></td>
			<td>
				<?php if(!empty($v['childs'])):?>
					<?php foreach ($v['childs'] as $c):?>
						<?=$c['brand_name']?>
						<i class="far fa-question-circle green" title="<?=$c['region_name'].' '.$c['address'].' ID: '.$c['id']?>"></i>
						<a href="/azs_unset_child.php?pid=<?=$v['id']?>&cid=<?=$c['id']?>" title="убрать из конкурентов для данной АЗС"><i class="fas fa-times-circle red"></i></a><br />
					<?php endforeach;?>
				<?php else:?>
					<b>конкуренты не заданы</b>
				<?php endif;?>
			</td>
		</tr>
	<?php endforeach;?>
	</table>
	<?php endif;?>
	</div>
	
	<div class="azs-info-cont-blck">
	<?php if ($foreing):?>
	<h3>АЗС конкуренты</h3>
	<table class="tbl-view">
		<tr class="tbl-view-top tbl-view-top-azs">
			<td>#</td>
			<td>Бренд</td>
			<td>Информация</td>
			<td>Тип расположения АЗС</td>
		</tr>
	<?php foreach ($foreing as $v):?>
		<tr class="tbl-view-data">
			<td><a title="перейти на страницу АЗС на Яндекс картах" href="https://yandex.ru/maps/org/<?=$v['id']?>" target="_blank">lnk</a>
				<br /><a href="/azs.php?get_azs=<?=$v['id']?>" title="редактировать АЗС">ред.</a>
			</td>
			<td>
				ID: <?=$v['id']?><br />
				<?=$v['brand_name']?$v['brand_name']:'<b><i title="данные появятся после парсинга">&lt;появится позже&gt;</i></b>'?><br />
				<?=str_replace('%%cid%%',$v['id'],$own_selector)?>
			</td>
			<td>
				<div class="tbl-view-data-region">Регион: <?=$v['region_name']?$v['region_name']:'<b><i title="данные появятся после парсинга">&lt;появится позже&gt;</i></b>'?></div>
				<div class="tbl-view-data-address">Адрес: <?=$v['address']?$v['address']:'<b><i title="данные появятся после парсинга">&lt;появится позже&gt;</i></b>'?></div>
				<div class="tbl-view-data-latlng">Долгота: <?=$v['lat']?> Широта: <?=$v['lng']?></div>
			</td>
			<td><?=$v['comment']?></td>
		</tr>
	<?php endforeach;?>
	</table>
	<?php endif; // endif $own?>
	</div>

	</div>
	<?php else: //$_SESSION['region_master_code']?>
	<h3>Выберите регион из списка</h3>
	<?php endif; //$_SESSION['region_master_code'] ?>

<script>
function _set_childs_azs_for(pid,cid) {
	location.href='/azs_set_child.php?pid='+pid+'&cid='+cid;
}
</script>	
</body>
</html>
