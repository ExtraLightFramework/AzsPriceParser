<?php require_once('header.php')?>
<?php require_once('functions.php')?>
<?php require_once('html_header.php');
	if (!empty($_POST['edit'])) {
		if (empty($_POST['name'])) {
			$error = 'Укажите название топливной группы';
			$edit = $_POST;
		}
		else {
			if ($fg = get_by_id((int)$_POST['id'], 'fuel_groups')) { // edit
				_query("UPDATE apar_fuel_groups SET `name`='{$_POST['name']}' WHERE `id`={$fg['id']}");
			} 
			else { //new
				_query("INSERT INTO apar_fuel_groups (`name`) VALUES('{$_POST['name']}')");
			}
		}
	}
	if (isset($_GET['fuel_group_id']) && isset($_GET['fuel_id'])) {
		_query("UPDATE apar_fuels SET `fuel_group_id`={$_GET['fuel_group_id']} WHERE `id`={$_GET['fuel_id']}");
	}
	$data = get_fuel_groups();
	if (empty($edit)) {
		if (isset($_GET['get_fuel_group'])) {
			if (!(int)$_GET['get_fuel_group'])
				$edit = true;
			else
				$edit = get_by_id($_GET['get_fuel_group'], 'fuel_groups');
		}
		else
			$edit = false;
	}
?>
<body>
	<?php require_once('menu.php');?>
	<?php if (!empty($error)):?>
	<div class="error">
		<?=$error?>
		<a href="javascript:;" title="закрыть" onclick="$(this).closest('div').remove()">X</a>
	</div>
	<?php endif;?>
	<h1>Топливо</h1>
	<?php if ($edit):?>
	<form action="/fuels.php" method="post" class="frm-edt">
		<input type="hidden" name="edit" value="1" />
		<input type="hidden" name="id" value="<?=!empty($edit['id'])?$edit['id']:''?>" />
		<h3>Редактор топливных групп</h3>
		<h4>Название</h4>
		<input type="text" name="name" value="<?=!empty($edit['name'])?$edit['name']:''?>" required="required" />
		<div>
			<input type="submit" value="Сохранить" />
			<input type="button" value="Отмена" onclick="location.href='/fuels.php'" />
		</div>
	</form>
	<?php endif;?>
	<a href="/fuels.php?get_fuel_group=0" title="добавить новую топливную группу">+ Новая группа</a>
	<?php if ($data['groups']):?>
	<?php foreach ($data['groups'] as $g):?>
		<h5 class="fuel-group">Группа "<?=$g['name']?>" (<a href="/fuels.php?get_fuel_group=<?=$g['id']?>">ред.</a>):</h5>
		<?php if ($g['fuels']):?>
		<table>
		<?php foreach ($g['fuels'] as $v):?>
		<tr><td width="150"><?=$v['name']?></td><td><?=$v['selector']?></td></tr>
		<?php endforeach;?>
		</table>
		<?php else:?>
		<i>группа пуста</i>
		<?php endif;?>
	<?php endforeach;?>
	<?php else:?>
	<h4>Топливные группы не найдены</h4>
	<?php endif;?>
	<?php if ($data['ungroup_fuels']):?>
		<h5 class="fuel-group">Виды топлива вне группы:</h5>
		<table>
		<?php foreach ($data['ungroup_fuels'] as $v):?>
		<tr><td width="150"><?=$v['name']?></td><td><?=$v['selector']?></td></tr>
		<?php endforeach;?>
		</table>
	<?php endif;?>
</body>
</html>