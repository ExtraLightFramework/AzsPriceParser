	function _sleep(ms) {
		return new Promise(resolve => setTimeout(resolve, ms));
	}
	function _parseAddress(data) {
					let region = '', address = '', i, addr;
					let a_from = 2;
					if (addr = data.address.split(',')) {
						if (addr[0]) {
							if ((addr[0] == 'Россия') && addr[1])
								region = $.trim(addr[1]);
							else if (addr[0] != 'Россия') {
								region = $.trim(addr[0]);
								a_from = 1;
							}
							else if (addr[1])
								region = $.trim(addr[1]);
						}
						for (i in addr) {
							if (parseInt(i) >= a_from) {
								address += (address?', ':'')+$.trim(addr[i]);
							}
						}
					}
					return {region:region, address:address};
	}
	function _azsOwnLoop(geoObjectsArray, azs_id, lat, lng) {
//		return new Promise(resolve => {
				let parents = '';
				let defs = [];
				for (let k in geoObjectsArray) {
					let data = geoObjectsArray[k].properties.getAll();
//					console.log(data.name);
					if (data.name == 'Газпром'
						|| data.name == 'Газэнергосеть розница'
						|| (data.name.indexOf('Газпром ') != -1)) {
						let addr = _parseAddress(data);
						let coords = geoObjectsArray[k].geometry.getCoordinates();
						let x = parseInt(Math.abs(coords[0] - lat)*1000);
						let y = parseInt(Math.abs(coords[1] - lng)*1000);

//						alert(azs_id+' '+x+' '+y);
						let out = {address: addr.address,
									region: addr.region,
									azs_id: (x==0 && y==0)?azs_id:0,
									ymaps_id: data.id,
									brand: data.name,
									brand_own: true,
									phone: data.phoneNumbers&&data.phoneNumbers[0]?data.phoneNumbers[0]:'',
									lat: coords[0],
									lng: coords[1]
								};
//						console.log(out);
						defs.push($.post('https://parser.azsgazprom.ru/138cfdde550914cec868286efb001f81.php',out,function(data) {
							if (data) {
								if (data.error)
									console.log('ERROR: '+data.error);
								else if (data.exception)
									console.log('EXCEPTION: '+data.exception);
								else if (data.success) {
//									console.log(data.success+' OWN:'+data.id);
									parents += (parents?',':'')+data.id;
								}
								else
									console.log(data);
							}
							else
								console.log('Data no received');
						}, 'json'));
					}
				}
				$.when.apply($, defs).done(function () {
					/* здесь код, который должен выполниться по окончанию всех аякс-запросов */
					_azsForeingLoop(geoObjectsArray, parents);
//					return parents;
				})
//				setTimeout(() => resolve(parents), 5000);
//		});
	}
	function _azsForeingLoop(geoObjectsArray, parents) {
				for (let k in geoObjectsArray) {
					let data = geoObjectsArray[k].properties.getAll();
					if (data.name != 'Газпром'
						&& data.name != 'Газэнергосеть розница'
						&& (data.name.indexOf('Газпром ') == -1)) {
						let addr = _parseAddress(data);
						let coords = geoObjectsArray[k].geometry.getCoordinates();


						let out = {address: addr.address,
									region: addr.region,
									ymaps_id: data.id,
									brand: data.name,
									parents: parents,
									phone: data.phoneNumbers&&data.phoneNumbers[0]?data.phoneNumbers[0]:'',
									lat: coords[0],
									lng: coords[1]
								};
						$.post('https://parser.azsgazprom.ru/138cfdde550914cec868286efb001f81.php',out,function(data) {
							if (data) {
								if (data.error)
									console.log('ERROR: '+data.error);
								else if (data.exception)
									console.log('EXCEPTION: '+data.exception);
								else if (data.success) {
//									console.log(data.success+' FRN:'+data.id);
								}
								else
									console.log(data);
							}
							else
								console.log('Data no received');
						}, 'json');
					}
				}
				//setTimeout(() => console.log('new AZS'), 1000);
	}
	async function _parse(ptr) {
		searchControl.azs_id =  azs[ptr].azs_id;
		searchControl.lat =  azs[ptr].lat;
		searchControl.lng =  azs[ptr].lng;
		map.setCenter([azs[ptr].lat,azs[ptr].lng], $('select[name=zoom]').val()).then(function() {
			searchControl.search('АЗС').then(function () {
				let geoObjectsArray = searchControl.getResultsArray();
				if (geoObjectsArray.length) {
//					console.log(azs[ptr].azs_id);
					_azsOwnLoop(geoObjectsArray,
								searchControl.azs_id,
								searchControl.lat,
								searchControl.lng);//.then(parents => _azsForeingLoop(geoObjectsArray,
													//								parents));
				}
				else
					console.log('Not found '+geoObjectsArray.length);
				ptr ++;
				if (ptr >= azs.length) {
					alert('Парсинг АЗС закончен');
					return;
				}
				else
					setTimeout(() => _parse(ptr), 3000);
			}); // and SEARCH then
		}); // end MAP then
	}
