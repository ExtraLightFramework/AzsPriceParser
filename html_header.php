<!DOCTYPE html>
<html>
<head>
    <title>АЗС парсер</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<script src="/js/jquery.js"></script>
	<script src="/js/scripts.js?<?=rand()?>"></script>
	<script src="/js/parser.js?<?=rand()?>"></script>
	<script src="/js/calendar.js"></script>
	
	<link href="//use.fontawesome.com/releases/v5.5.0/css/all.css" rel="stylesheet" />
	<link href="/css/styles.css?<?=rand()?>" type="text/css" rel="stylesheet">
	
	<link rel="shortcut icon" href="/favicon.png" />

    <!--
        Укажите свой API-ключ. Тестовый ключ НЕ БУДЕТ работать на других сайтах.
        Получить ключ можно в Кабинете разработчика: https://developer.tech.yandex.ru/keys/
    -->
	<script src="https://api-maps.yandex.ru/2.1/?load=package.standard,package.controls,package.geoObjects&lang=ru_RU&apikey=9c27e354-0df8-4cc0-ad2c-3d60a1847d21" type="text/javascript"></script>
</head>
	<div id="wait-window">
		<i class="fas fa-circle-notch fa-spin fa-5x fa-fw"></i>
		<a href="javascript:$('#wait-window').hide()">закрыть</a>
	</div>

<?php
if (($_SERVER['REQUEST_URI'] == '/prices_on_map.php')
	&& empty($_SESSION['uid'])) {
	$_SESSION['uid'] = true;
	$_SESSION['admin'] = false;
}
if (empty($_SESSION['uid'])):?>
<body>
	<form action="/login.php" method="post" class="frm-edt">
		<h3>Авторизация</h3>
		<h4>Введите пароль</h4>
		<input type="hidden" name="redirect" value="<?=$_SERVER['REQUEST_URI']?>" />
		<input type="password" name="pass" required="required" />
		<div>
			<input type="submit" value="Войти" />
		</div>
	</form>
</body>
</html>
<?php 
exit;
endif;?>