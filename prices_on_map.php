<?php require_once('header.php')?>
<?php require_once('functions.php')?>
<?php require_once('html_header.php');
//	print_r($_SESSION);
?>
<script>
	<?php if (empty($_SESSION['from'])):?>
	save_filter_data('from', '<?=date('d.m.Y', time()-SECONDS_IN_MONTH)?>', false);
	<?php endif;?>
	<?php if (empty($_SESSION['to'])):?>
	save_filter_data('to', '<?=date('d.m.Y', time())?>', false);
	<?php endif;?>
	showWW();

	var zoom = <?=!empty($_SESSION['map_zoom'])?$_SESSION['map_zoom']:'17'?>,
		lom, spillom, tlts, map, tlt, layoutLigth, layoutDark;
	ymaps.ready(init);
	function test_lom() {
		alert('lom');
	}
	function init() {
		map = new ymaps.Map('map', {
				center: [<?=!empty($_SESSION['map_lat'])?$_SESSION['map_lat']:'59.975644'?>, <?=!empty($_SESSION['map_lat'])?$_SESSION['map_lng']:'30.331301'?>],//[46.467834, 47.958614],
				zoom: <?=!empty($_SESSION['map_zoom'])?$_SESSION['map_zoom']:'17'?>,
				controls: []
			});
		lom = new ymaps.LoadingObjectManager('https://parser.azsgazprom.ru/azs_get_with_prices.php?bbox=%b',
			{
				clusterize: true
			}
		);
		spillom = new ymaps.ObjectManager();
		tlts = new ymaps.ObjectManager();
		tlt = new ymaps.Placemark([59.975644, 30.331301],
			{
				'iconContent': 'Центральный офис'
			},
			{
				iconLayout: 'default#imageWithContent',
				iconImageHref: '/img/empty_point.png',
				iconImageSize: [200, 50]
			}
		);
		layoutLigth = ymaps.templateLayoutFactory.createClass(
            '<div style="color: #FFFFFF; font-weight: bold;">$[properties.iconContent]</div>'
        );
		layoutDark = ymaps.templateLayoutFactory.createClass(
            '<div style="color: #000000; font-weight: bold;">$[properties.iconContent]</div>'
        );
		lom.events.add('statechange', function(e) {
			showWW();
		});
		map.geoObjects.add(lom);
		map.geoObjects.add(spillom);
		map.geoObjects.add(tlts);
		map.geoObjects.add(tlt);
		map.events.add('boundschange', function (e) {
			let c = map.getCenter();
			save_filter_data('map_lat', c[0], false);
			save_filter_data('map_lng', c[1], false);
			if (zoom != map.getZoom()) {
				zoom = map.getZoom();
				save_filter_data('map_zoom', zoom, false);
				$('#zoom-val').text(zoom);
			}
		});
	}
	function clbk(data) {
		if (data.error) {
			console.log(data.error);
		}
		else {
//			console.log(data);
			let k;
			if (zoom >= 12) {
				lom.objects.add(data.features);
				if (data.tlt) {
					for (k in data.tlt.features) {
						if (data.tlt.features[k].options.layLigth)
							data.tlt.features[k].options.iconContentLayout = layoutLigth;
						else
							data.tlt.features[k].options.iconContentLayout = layoutDark;
					}
					tlts.objects.add(data.tlt);
				}
			}
			else {
				lom.objects.removeAll();
				tlts.objects.removeAll();
			}
			if (data.spillage) {
//				spillom.objects.removeAll();
				for (k in data.spillage.features) {
					let o;
					if (o = spillom.objects.getById(data.spillage.features[k].id)) {
						spillom.objects.remove(o);
					}
				}
				setTimeout(() => spillom.objects.add(data.spillage), 100);
			}
		}
		hideWW();
	}
	function save_filter_data(name, val, reload) {
		if (typeof reload == 'undefined')
			reload = true;
		$.post('https://parser.azsgazprom.ru/save_filter_data.php',{name:name,val:val}, function() {if (reload) location.reload()});
	}
	function set_map_center(v) {
		let coor = v.split(',');
//		alert(coor[0]+' '+coor[1]);
		if (parseInt(coor[1]) && parseInt(coor[0])) {
			showWW();
			map.setCenter([coor[0],coor[1]]);
			map.setZoom(10);
		}
		else if (v != 'null')
			alert('Координаты региона не заданы. Оратитесь к администратору');
	}
</script>
<body>
	<?php //require_once('menu.php');?>
	<h1>Тепловая карта цен на АЗС</h1>
	<?php
		//print_r(get_regions());
	?>
	Топливо: <?=fuel_selector(!empty($_SESSION['fuel_name'])?$_SESSION['fuel_name']:'')?>
	За период: с 
	<input class="nowide date" size="12" type="text" name="from"
			value="<?=!empty($_SESSION['from'])?$_SESSION['from']:date('d.m.Y', time()-SECONDS_IN_MONTH)?>" onfocus="this.select();lcs(this)"
			onclick="event.cancelBubble=true;this.select();lcs(this)" readonly="readonly" />
	по
	<input class="nowide date" size="12" type="text" name="to"
			value="<?=!empty($_SESSION['to'])?$_SESSION['to']:date('d.m.Y')?>" onfocus="this.select();lcs(this)"
			onclick="event.cancelBubble=true;this.select();lcs(this)" readonly="readonly" />
	Тип АЗС: <?=azs_type_selector(!empty($_SESSION['azs_type'])?$_SESSION['azs_type']:'')?>
	<?php
		if ($rs = get_regions()):
	?>
	Регион:
	<select onchange="set_map_center(this.value)">
		<option value="null">укажите регион</option>
		<?php foreach ($rs as $v):?>
		<option value="<?=$v['lat_center']?>,<?=$v['lng_center']?>"><?=$v['name']?></option>
		<?php endforeach;?>
	</select>
	<?php
		endif;
	?>
	Zoom: <span id="zoom-val"><?=!empty($_SESSION['map_zoom'])?$_SESSION['map_zoom']:'17'?></span>
	<div id="map"></div>
</body>
</html>