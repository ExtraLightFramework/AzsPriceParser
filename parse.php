<?php require_once('header.php')?>
<?php require_once('functions.php')?>
<?php require_once('html_header.php')?>
<script>
	let azs, map, searchControl;
	ymaps.ready(init);
	function init() {
		map = new ymaps.Map('map', {
				center: [59.975644, 30.331301],
				zoom: 17,
				controls: []
			});
		searchControl = new ymaps.control.SearchControl({
			options: {
				provider: 'yandex#search'
			}
		});
		azs = [];
		$.post('https://parser.azsgazprom.ru/azs_get_own_with_nums.php', function(data) {
			if (data) {
				if (data.error)
					alert(data.error);
				else if (data.success) {
					azs = data.data;
//					console.log(azs);
					$('#butt-go-search').removeProp('disabled');
				}
				else
					alert('Собственные АЗС не найдены. Воспользуйтесь меню "Наши АЗС" для их добавления.');
			}
			else
				console.log('Ошибка запроса АЗС');
		}, 'json');
		map.controls.add(searchControl);
	}
	function _ch_zoom(z) {
		map.setZoom(z);
	}
</script>
<body>
	<?php require_once('menu.php');?>
	<h1>Парсинг АЗС</h1>
	<div id="map"></div>
	<center>
		Zoom: <select name="zoom" onchange="_ch_zoom(this.value)">
			<option value="12">12</option>
			<option value="13">13</option>
			<option value="14">14</option>
			<option value="15">15</option>
			<option value="16">16</option>
			<option value="17" selected="selected">17</option>
		</select>
		<button onclick="_parse(0)" disabled="disabled" id="butt-go-search">Парсинг!</button>
		<div>
		Чем больше Zoom, тем "ближе" карта и меньше АЗС конкурентов будет добавлено и наоборот.
		</div>
	</center>
</body>
</html>