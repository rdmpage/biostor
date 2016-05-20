//----------------------------------------------------------------------------
function wikidata(issn) {
	// Find Wikidata item that has this ISSN
	$.getJSON('https://wdq.wmflabs.org/api?q=' +  encodeURIComponent('string[236:"' + issn + '"]')  + '&callback=?',
		function(data){
			if (data.status) {
				if ((data.status.error == 'OK') && data.items.length == 1) {
				   var item = 'Q' + data.items[0];
			   
				   // get details for this Wikidata item
					$.getJSON('https://www.wikidata.org/wiki/Special:EntityData/' + item + '.json',
						function(d){
							if (d.entities) {
								var html = '';
								for (var i in d.entities[item].claims) {
									// title
									if (i == 'P1476') {
										//alert(i);
										for (var j in d.entities[item].claims[i]) {
											var title = d.entities[item].claims[i][j].mainsnak.datavalue.value.text;
											var language = d.entities[item].claims[i][j].mainsnak.datavalue.value.language;
											html += '<h3>' + title + ' (' + language + ')' + '</h3>';
										}
									}
								}
								html += '<small>Data from <a href="https://www.wikidata.org/wiki/' + item + '">Wikidata</a></small>';
								$('#journal_info').html(html);
							}
						}
					);
				}
			}
		}
	); 
}