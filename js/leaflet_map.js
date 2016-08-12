        var map = L.map('map').setView([0, 0], 2);
        mapLink = 
            '<a href="http://openstreetmap.org">OpenStreetMap</a>';
        L.tileLayer(
            'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; ' + mapLink + ' Contributors',
            maxZoom: 20,
            }).addTo(map);
            
            
        $(window).resize();


		var geojson = null;
		
		//--------------------------------------------------------------------------------
		function onEachFeature(feature, layer) {
			// does this feature have a property named popupContent?
			if (feature.properties && feature.properties.popupContent) {
				//console.log(feature.properties.popupContent);
				// content must be a string, see http://stackoverflow.com/a/22476287
				layer.bindPopup(String(feature.properties.popupContent));
			}
		}	
		

		var drawnItems = new L.FeatureGroup();
		map.addLayer(drawnItems);

		var drawControl = new L.Control.Draw({
			position: 'topleft',
			draw: {
				marker: false, // turn off marker
				polygon: {
					shapeOptions: {
						color: 'purple'
					},
					allowIntersection: false,
					drawError: {
						color: 'orange',
						timeout: 1000
					},
					showArea: true,
					metric: false,
					repeatMode: true
				},
				polyline: false,
				rect: {
					shapeOptions: {
						color: 'green'
					},
				},
				circle: false
			},
			edit: {
				featureGroup: drawnItems
			}
		});
		map.addControl(drawControl);

		map.on('draw:created', function (e) {
			var type = e.layerType,
				layer = e.layer;


			drawnItems.addLayer(layer);
			
			//alert(JSON.stringify(layer.toGeoJSON()));
			
			var search_polygon = layer.toGeoJSON();
			
			if (search_polygon.geometry.type == 'Polygon') {
				for (var i in search_polygon.geometry.coordinates) {
				  minx = 180;
				  miny = 90;
				  maxx = -180;
				  maxy = -90;
			  
				  for (var j in search_polygon.geometry.coordinates[i]) {
					minx = Math.min(minx, search_polygon.geometry.coordinates[i][j][0]);
					miny = Math.min(miny, search_polygon.geometry.coordinates[i][j][1]);
					maxx = Math.max(maxx, search_polygon.geometry.coordinates[i][j][0]);
					maxy = Math.max(maxy, search_polygon.geometry.coordinates[i][j][1]);
				  }
				}
				
				bounds = L.latLngBounds(L.latLng(miny,minx), L.latLng(maxy,maxx));
				map.fitBounds(bounds);
			}
			
			var shape = JSON.stringify(layer.toGeoJSON());
			$('#data').html(shape);
			
			var wkt = new Wkt.Wkt();
            wkt.read(shape);
			$('#data').html('<b>GeoJSON</b><br/>' + shape + '<br/>' + '<b>WKT</b><br/>' + wkt.write());
			
			// bounds
			
			
			
			// add data points
			if (geojson) {
				map.removeLayer(geojson);
			}
			
			//----------------------------------------------------------------------------
			// Query Cloudant
			$.getJSON('api_geo.php?wkt=' + wkt.write() + '&limit=200&callback=?',
				function(data){
					if (data.rows) {
						var x = {};
						
						x.type = "FeatureCollection";
						x.features = [];
						
						
						for (var i in data.rows) {
							var feature = {};
							feature.type = "Feature";
							feature.geometry = data.rows[i].geometry;
							
							feature.properties = {};
							
							var id = data.rows[i].id;
							id = id.replace(/biostor\//, '');
							
							// Content for the popup							
							feature.properties.name = id;
							feature.properties.popupContent = '';
							feature.properties.popupContent = id;							
							
							if (data.rows[i].doc.title) {
								feature.properties.popupContent = '<a href="http://biostor.org/reference/' + id + '" target="_new">' + data.rows[i].doc.title + '</a>' + '<br />';
							}
							x.features.push(feature);	
													
						}
						//add_data(geojson);
						
						//L.geoJson(x).addTo(map);
						
						geojson = L.geoJson(x, { 
							style: function (feature) {
							return feature.properties;
							},
							onEachFeature: onEachFeature,
							}).addTo(map);

					
					}
				}
			); 
			 			
			
			
		});
