		var map;
		var marker = null;
	
		google.load('maps', '3', {
			other_params: 'sensor=false'
		  });
		google.setOnLoadCallback(initialize);
		  
		  
		//------------------------------------------------------------------------------------------
		// Normalizes the coords that tiles repeat across the x axis (horizontally)
		// like the standard Google map tiles.
		function getNormalizedCoord(coord, zoom) {
		  var y = coord.y;
		  var x = coord.x;
		
		  // tile range in one direction range is dependent on zoom level
		  // 0 = 1 tile, 1 = 2 tiles, 2 = 4 tiles, 3 = 8 tiles, etc
		  var tileRange = 1 << zoom;
		
		  // don't repeat across y-axis (vertically)
		  if (y < 0 || y >= tileRange) {
			return null;
		  }
		
		  // repeat across x-axis
		  if (x < 0 || x >= tileRange) {
			x = (x % tileRange + tileRange) % tileRange;
		  }
		
		  return {
			x: x,
			y: y
		  };
		}
			  
      
		//--------------------------------------------------------------------------------------------
		/** @constructor */
		function BoldMapType(tileSize) {
		  this.tileSize = tileSize;
		}
		
		//--------------------------------------------------------------------------------------------
		BoldMapType.prototype.getTile = function(coord, zoom, ownerDocument) {	
		  	var div = ownerDocument.createElement('div');
		  
			var normalizedCoord = getNormalizedCoord(coord, zoom);
			  if (!normalizedCoord) {
				return null;
			  }  
		  
		  	// Get tile from CouchDB
		  	var url = 'api_tile.php?x=' + normalizedCoord.x 
		  		+ '&y=' + normalizedCoord.y 
		  		+ '&z=' + zoom;
		  
			div.innerHTML = '<img src="' + url + '"/>';
		  	div.style.width = this.tileSize.width + 'px';
		 	div.style.height = this.tileSize.height + 'px';
		  
		  	return div;
		};
		
		//--------------------------------------------------------------------------------------------
		function display_publications(id)
		{
		    var url = 'api.php?id=' + id + '&format&style=apa';
			$.get(url,
				function(html){
					var element_id = id;
					element_id = element_id.replace(/\//g, '_');
					
					id = id.replace(/biostor\//, '');
					var citation = '<a href="reference/' + id + '">' + html + '</a>';
					
					$('#' + element_id).html(citation);
			});
		}		
		
		//--------------------------------------------------------------------------------------------
		// handle user click on map
		function placeMarker(position, map) {

			if (marker) {
			   marker.setMap(null);
			   marker = null;
			}
  			 marker = new google.maps.Marker({
      			position: position,
      			map: map
 			});
		
		
			$('#hit').html('');
			
			$('#hit').html('Loading...');
			
			// http://wiki.openstreetmap.org/wiki/Slippy_map_tilenames
			// Compute the tile the user has clicked on, and the relative position of the click
			// within that tile
			var tile_size = 256;
			var pixels = 4;
			var zoom = map.getZoom();
			
			var x_pos = (parseFloat(position.lng()) + 180)/360 * Math.pow(2, zoom);
			var x = Math.floor(x_pos);
			
			// position within tile
			var relative_x = Math.round(tile_size * (x_pos - x));
		
			var y_pos = (1-Math.log(Math.tan(parseFloat (position.lat())*Math.PI/180) + 1/Math.cos(parseFloat(position.lat())*Math.PI/180))/Math.PI)/2 *Math.pow(2,zoom);
			var y = Math.floor(y_pos);
			
			// position within tile
			var relative_y = Math.round(tile_size * (y_pos - y));
		
			// cluster into square defined by pixel size
			relative_x = Math.floor(relative_x / pixels) * pixels;
		    relative_y = Math.floor(relative_y / pixels) * pixels;
		
			// key to query CouchDB
			var key = [];
			key.push(zoom);
			key.push(x);
			key.push(y);
			key.push(relative_x);
			key.push(relative_y);
			

			var url = "api_tilehit.php?key=" + JSON.stringify(key) + "&callback=?";
				
			console.log(url);
			
			
			$.getJSON(url,
				function(data){
					if (data.status == 200) {
						if (data.results.length != 0) {
							var html = '';
							
							html += '<div>' + '<b>Number of publications: ' + data.results.length + '</b>' + '</div>';
							
							//html += '<span class="explain">Tile [x,y,z,rx,ry] = [' + x + ',' + y + ',' + zoom + ',' + relative_x + ',' + relative_y + ']</span>';
							
							html += '<div>';
							var ids = [];
							for (var i in data.results) {
								var id = data.results[i];
								
								var element_id = id;
								element_id = element_id.replace(/\//g, '_');
								html += '<div id="' + element_id + '">' + id + '</div>';
								ids.push(id);
						    }
						    
						    html += '</div>';
							
							$('#hit').html(html);
							
						for (var id in ids) {
							display_publications(ids[id]);
						}
							
							
						} else {
						    $('#hit').html('No hits (try clicking again)');
						}
						
					}
				});
		
		
		}
		


      //--------------------------------------------------------------------------------------------
      function initialize() {
    	
		var center = new google.maps.LatLng(0,0);
		
        map = new google.maps.Map(document.getElementById('map'), {
          zoom: 2,
          center: center,
          mapTypeId: google.maps.MapTypeId.TERRAIN,
          draggableCursor: 'auto'
        });
        
        // hit test
		google.maps.event.addListener(map, 'click', function(e) {
    		placeMarker(e.latLng, map);
  			});
        
		// Insert this overlay map type as the first overlay map type at
		// position 0. Note that all overlay map types appear on top of
		// their parent base map.
		map.overlayMapTypes.insertAt(
		  0, new BoldMapType(new google.maps.Size(256, 256)));
      
		/* http://stackoverflow.com/questions/6762564/setting-div-width-according-to-the-screen-size-of-user */
		/*
		$(window).resize(function() { 
			var windowHeight = $(window).height();
			$('#map').css({'height':windowHeight });
			//$('#hit').css({'height':(windowHeight - 310)});
			$('#hit').css({'height':windowHeight});
		});	
		*/
	
      }
      
