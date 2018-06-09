<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');
require_once (dirname(__FILE__) . '/nameparse.php');
require_once (dirname(__FILE__) . '/reference_code.php');

//----------------------------------------------------------------------------------------
function default_display($error_msg = '')
{
	global $config;
	
	display_html_start('BioStor');
	display_navbar();
	
	echo  '<div class="container-fluid">';
	
	if ($error_msg != '')
	{
		echo '<div class="alert alert-danger" role="alert"><strong>Error!</strong> ' . $error_msg . '</div>';
	}
	
	//echo '<div class="alert alert-warning" role="alert"><strong>Heads up!</strong> BioStor is evolving, so things will look different and some things may be missing.</div>';
	
	echo '<div class="jumbotron" style="text-align:center">
        <h1>BioStor</h1>
        <p>Articles from the Biodiversity Heritage Library</p>
      </div>';
      
    echo '<div class="row">      
      	
       		
        	<div class="col-md-4">
      			<h3>Recent additions</h3>
				<div>
				<a class="twitter-timeline" href="https://twitter.com/biostor_org" data-widget-id="567310691699552256">Tweets by @biostor_org</a> <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?\'http\':\'https\';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
				</div>
      			
      		</div>
     		
      	
      		<div class="col-md-4">
      			<h3>Geography</h3>
				<div class="media">
					<a class="pull-left" href="map">
						<img src="static/homepage_map.png">
					</a>
					<div class="media-body">
						Browse an interactive map of localities in BioStor articles.
					</div>
				</div>
      		</div>
      		
      		<div class="col-md-4">
      			<h3>Images</h3>
				<div class="media">
					<a class="pull-left" href="images">
						<img src="static/homepage_page.png">
					</a>
					<div class="media-body">
						Examples of interesting images from BioStor articles.
					</div>
				</div>
      		</div>
      		
      	</div>';
      	
      echo '</div>';
      


	display_html_end();
	
}

//----------------------------------------------------------------------------------------
// List of all years that we have articles for, grouped by decade.
// Display on page for entire journal, and on page for a given year
function display_journal_volumes($namespace = 'issn', $identifier, $year = '')
{
	global $config;

	// all volumes for journal
	$url = $config['web_server'] . $config['web_root'] . 'api_journal.php?';
	switch ($namespace)
	{
		case 'issn':
			$url .= 'issn=' . $identifier;
			break;
			
		case 'oclc':
			$url .= 'oclc=' . $identifier;
			break;
			
		default:
			break;
	}
	$url .= '&volumes';
		
	$json = get($url);
	
	if ($json != '')
	{
		$obj = json_decode($json);
		
		
		echo '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">' . "\n";

		foreach ($obj->decades as $k => $decade)
		{
			$current_decade = false;
			if ($year != '')
			{
				$current_decade = (floor($year / 10) == $k);
			}
			
			echo '  <div class="panel panel-default">' . "\n";

			// heading
			
    		echo '<div class="panel-heading" role="tab" id="heading' . $k . '">' . "\n";
      		echo '<h4 class="panel-title">' . "\n";
        	echo '<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse' . $k . '"';
        	
			if ($current_decade)
			{
				echo 'aria-expanded="true"';
			}
			else
			{
				echo 'aria-expanded="false"';
			}
			
        	
        	echo ' aria-controls="collapse' . $k .'">' . "\n";
          	echo $k . '0\'s';
        	echo '</a>' . "\n";
      		echo '</h4>' . "\n";
    		echo '</div>' . "\n";
			
			// content
			
			/*
			echo '<div style="border:1px solid black;">';
			if ($current_decade)
			{
				echo "Active<br />";
			}
			
			echo '<ul>';
			*/
			
			
			echo '<div id="collapse' . $k . '" class="panel-collapse collapse';
			if ($current_decade)
			{
				echo ' in';
			}
			echo '" role="tabpanel" aria-labelledby="id' . $k . '">' . "\n";
      		echo '<div class="panel-body">' . "\n";

			echo '<ul>';
			foreach ($decade as $k => $v)
			{
				$current_year = false;
				if ($year != '')
				{
					$current_year = ($year == $k);
				}
				echo '<li>';
				if ($current_year)
				{
					echo '<b>';
				}
				
				//echo $k;
				
				//echo '<a href="?' . $namespace . '=' . $identifier . '&year=' . $k . '">' . $k . '</a>';
				echo '<a href="' . $namespace . '/' . $identifier . '/year/' . $k . '">' . $k . '</a>';
				
				
				if ($current_year)
				{
					echo '</b>';
				}
				
				echo ' <span class="badge">' .$v . '</span>';
				echo '</li>';
			}
			echo '</ul>';

			echo '</div>';	
			echo '</div>';	
				
			echo '</div>';
			
		}
		
		echo '</div>';
	}		
}

//----------------------------------------------------------------------------------------
function display_record_summary ($reference, $highlights = null)
{
  echo '<div class="media" style="padding-bottom:5px;">
  <div class="media-left media-top" style="padding:10px;">';
    //echo '<a href="?id=' . $reference->_id . '">';
    echo '<a href="reference/' . $reference->_id . '">';
    echo '<img style="box-shadow:2px 2px 2px #ccc;width:64px;" src="' . $reference->thumbnail .  '">';	
  echo '  </a>
  </div>
  <div class="media-body" style="padding:10px;">
    <h4 class="media-heading">';
//	echo '<a href="?id=' . $reference->_id . '">' . $reference->title . '</a>';    
	echo '<a href="reference/' . str_replace('biostor/', '', $reference->_id) . '">' . $reference->title . '</a>';    
    echo '</h4>';
    
		echo '<div style="color:rgb(128,128,128);">';
		if (isset($reference->year))
		{
			echo 'Published in <b>' . $reference->year . '</b>';
		}
		if (isset($reference->journal))
		{
			$issn = '';
			if (isset($reference->journal->identifier))
			{
				foreach ($reference->journal->identifier as $identifier)
				{
					if ($identifier->type == 'issn')
					{
						$issn = $identifier->id;
					}
				}
			}
			if ($issn != '')
			{
//				echo ' in <b><a href="?issn=' . $issn . '">' . $reference->journal->name . '</a></b>';			
				echo ' in <b><a href="issn/' . $issn . '">' . $reference->journal->name . '</a></b>';			
			}
			else
			{
				echo ' in <b>' . $reference->journal->name . '</b>';
			}
			if (isset($reference->journal->volume))
			{
				echo ', volume <b>' . $reference->journal->volume . '</b>';
			}
			if (isset($reference->journal->issue))
			{
				echo ', issue <b>' . $reference->journal->issue . '</b>';
			}		
			if (isset($reference->journal->pages))
			{
				echo ', on pages <b>' . str_replace('--', '-', $reference->journal->pages) . '</b>';
			}
		}
		else
		{
			// not a journal...
			if (isset($reference->pages))
			{
			  echo ', on pages <b>' . str_replace('--', '-', $reference->pages) . '</b>';
			}
		}		
		echo '</div>';
	
		echo '<div>';
	
		if (isset($reference->author))
		{
			$authors = array();
			foreach ($reference->author as $author)
			{
				$string = '';
				if (isset($author->firstname))
				{
					$string = $author->firstname . ' ' . $author->lastname;
				}
				else
				{
					$string = $author->name;
				}
				
				//$authors[] = '<a href="' . '?q=author:&quot;' . $string . '&quot;' . '">' . $string . '</a>';
				$authors[] = '<a href="' . 'search/author:&quot;' . $string . '&quot;' . '">' . $string . '</a>';
		
			}
			echo 'Authors: ' . join(', ', $authors);
		}
	
	
		echo '</div>';
	
	
		//echo '<span style="color:green;">' . $row->highlights->default[0] . '</span>';
		if (isset($highlights) && isset($highlights->default[0]))
		{
			echo '<div>';
			echo '<span style="color:green;">' . $highlights->default[0] . '</span>';
			echo '</div>';
		}	
	
		echo '<div class="item-links">';
	
		//echo '<a href="">cite</a>';
			
		if (isset($reference->identifier))
		{
			foreach ($reference->identifier as $identifier)
			{
				switch ($identifier->type)
				{
					case 'bhl':
						echo ' <span class="bhl"><a href="https://biodiversitylibrary.org/page/' . $identifier->id . '" target="_new">http://biodiversitylibrary.org/page/' . $identifier->id . '</a></span>'  . '<br />';
						break;
						
					case 'doi':
						echo ' <span class="doi"><a href="https://doi.org/' . $identifier->id . '" target="_new">' . $identifier->id . '</a></span>' . '<br />';
						break;

					case 'jstor':
						echo ' <span class="jstor"><a href="http://www.jstor.org/stable/' . $identifier->id . '" target="_new">' . $identifier->id . '</a></span>' . '<br />';
						break;
						
					case 'lsid':
						echo ' <span class="lsid"><a href="http://lsid.tdwg.org/' . $identifier->id . '" target="_new">' . $identifier->id . '</a></span>' . '<br />';
						break;
						
					default:
						break;
				}
			}
		}
		echo '</div>';    
    
    
  echo '</div>
</div>';

}

//----------------------------------------------------------------------------------------
// Display articles for a given year
function display_journal_year($namespace = 'issn', $identifier, $year)
{
	global $config;
	
	display_html_start();
	display_navbar();

	// Breadcrumbs -----------------------------------------------------------------------
	echo '<ol class="breadcrumb">' . "\n";	
	//echo '<li><a href="?titles">All titles</a></li>' . "\n";	
	echo '<li><a href="titles">All titles</a></li>' . "\n";	
	
	echo '<li>';
	switch ($namespace)
	{
		case 'issn':
		case 'oclc':
//			echo '<a href="?' . $namespace . '=' . $identifier . '">' . $identifier . '</a>';					
			echo '<a href="' . $namespace . '/' . $identifier . '">' . $identifier . '</a>';					
			break;
		default:
			echo $identifier;
			break;					
	}
	echo '</li>' . "\n";
	
	echo '<li class="active">' . $year . '</li>' . "\n";
	
	echo '</ol>';
	
	echo '<div class="container-fluid">' . "\n";
	echo '  <div class="row">' . "\n";
	echo '		<div class="col-md-2">' . "\n";
	
	display_journal_volumes($namespace, $identifier, $year);
	
	echo '      </div>' . "\n";
			
	echo '      <div class="col-md-10">' . "\n";

	// all volumes for journal
	$url = $config['web_server'] . $config['web_root'] . 'api_journal.php?';
	switch ($namespace)
	{
		case 'issn':
		case 'oclc':
			$url .= $namespace . '=' . $identifier;
			break;
			
		default:
			break;
	}
	$url .= '&year=' . $year;
	
	$json = get($url);
	
	if ($json != '')
	{
		$obj = json_decode($json);
		
		foreach ($obj->articles as $reference)
		{
			// display
			display_record_summary ($reference);
		}
	}

	echo '      </div>' . "\n";
	echo '   </div>' . "\n";
	echo '</div>' . "\n";
	
	
	display_html_end();
}

//----------------------------------------------------------------------------------------
// Display articles for a given year
function display_journal($namespace = 'issn', $identifier)
{
	global $config;
	
	display_html_start();
	display_navbar();

	// Breadcrumbs -----------------------------------------------------------------------
	echo '<ol class="breadcrumb">' . "\n";	
//	echo '<li><a href="?titles">All titles</a></li>' . "\n";		
	echo '<li><a href="titles">All titles</a></li>' . "\n";		
	echo '<li class="active">' .$identifier . '</li>' . "\n";
	echo '</ol>';
	
	echo '<div class="container-fluid">' . "\n";
	echo '  <div class="row">' . "\n";
	echo '		<div class="col-md-2">' . "\n";
	display_journal_volumes($namespace, $identifier);
	echo '      </div>' . "\n";
			
	echo '      <div class="col-md-10">' . "\n";
	
	echo        '<div id="journal_info"></div>';
	
	echo '      </div>' . "\n";
	echo '   </div>' . "\n";
	echo '</div>' . "\n";
	
	echo '<script>' . "\n";
	echo '   wikidata("' . $identifier . '");' . "\n";
	echo '</script>' . "\n";
	
	
	display_html_end();
}

//----------------------------------------------------------------------------------------
function display_article_metadata($reference)
{
	// Metadata --------------------------------------------------------------------------
	if (isset($reference->journal))
	{
		echo $reference->journal->name;		
		if (isset($reference->journal->series))
		{
			echo ' series ' . $reference->journal->series;
		}				
		
		if (isset($reference->date))
		{
			switch (count($reference->date))
			{
				case 3:
					echo ' ' . date('j F Y', strtotime(join('-', $reference->date)));
					break;
				case 2:	
					// Set date to first of month, but only show month
					echo ' ' . date('F Y', strtotime(join('-', $reference->date) . '-01'));
					break;
				case 1:	
					echo ' ' . $reference->date[0];
					break;
				default:
					break;
			}
		}
		else
		{
			if (isset($reference->year))
			{
				echo ' ' . $reference->year;
			}
		}
						
		if (isset($reference->journal->volume))
		{
			echo ' ' . $reference->journal->volume;
		}
		if (isset($reference->journal->issue))
		{
			echo '(' . $reference->journal->issue . ')';
		}		
		if (isset($reference->journal->pages))
		{
			echo ':' . str_replace('--', '-', $reference->journal->pages);
		}
		echo '<br />';
	}
	
	if (isset($reference->identifier))
	{
		foreach ($reference->identifier as $identifier)
		{
			switch ($identifier->type)
			{
				case 'bhl':
					echo '<span class="bhl"><a href="https://biodiversitylibrary.org/page/' . $identifier->id . '" target="_new">' . $identifier->id  . '</a></span>';
					echo '<br />';
					break;			
			
				case 'doi':
					echo '<span class="doi"><a href="https://doi.org/' . $identifier->id . '" target="_new">' . $identifier->id  . '</a></span>';
					echo '<br />';
					break;
					
				case 'jstor':
					echo ' <span class="jstor"><a href="http://www.jstor.org/stable/' . $identifier->id . '" target="_new">' . $identifier->id . '</a></span>';
					echo '<br />';
					break;					

				case 'lsid':
					echo '<span class="lsid"><a href="http://lsid.tdwg.org/' . $identifier->id . '" target="_new">' . $identifier->id  . '</a></span>';
					echo '<br />';
					break;
					
				default:
					break;
			}
		}
	}	
	
	// Title -----------------------------------------------------------------------------	
	echo "<h3>" . $reference->title . "</h3>";	
	
	// Authors ---------------------------------------------------------------------------
	if (isset($reference->author))
	{
		if (count($reference->author) > 0)
		{
			$authors = array();
			foreach ($reference->author as $author)
			{
				$string = '';
				if (isset($author->firstname))
				{
					$string = $author->firstname . ' ' . $author->lastname;
				}
				else
				{
					$string = $author->name;
				}
			
				//$authors[] = '<a href="' . '?q=author:&quot;' . $string . '&quot;' . '">' . $string . '</a>';
				$authors[] = '<a href="' . 'search/author:&quot;' . $string . '&quot;' . '">' . $string . '</a>';
	
			}
			echo join(', ', $authors);
		}
	}

	// COinS -----------------------------------------------------------------------------
	echo reference_to_coins($reference);
	
	/*
	echo '<pre>';
	echo htmlentities(reference_to_google_scholar($reference));
	echo '</pre>';

	echo '<pre>';
	echo htmlentities(reference_to_twitter($reference));
	echo '</pre>';

	echo '<pre>';
	echo htmlentities(reference_to_coins($reference));
	echo '</pre>';
	
	
	echo '<pre>';
	echo json_encode(reference_to_citeprocjs($reference), JSON_PRETTY_PRINT);
	echo '</pre>';
	*/
}

//----------------------------------------------------------------------------------------
function altmetric_data_string($reference)
{
	$data_string = '';
	if (isset($reference->identifier))
	{
		foreach ($reference->identifier as $identifier)
		{
			switch ($identifier->type)
			{		
				case "doi":
					$data_string = 'data-doi="' . trim($identifier->id) . '"';
					break;
				
				case "handle":
					if ($data_string == '')
					{
						$data_string = 'data-handle="' . trim($identifier->id) . '"';
					}
					break;			

				case "pmid":
					if ($data_string == '')
					{
						$data_string = 'data-pmid="' . trim($identifier->id) . '"';
					}
					break;			
													
				default:
					break;
			}
		}
	}
	return $data_string;
}

//----------------------------------------------------------------------------------------
// Display one article
function display_record($id, $page = 0)
{
	global $config;
	global $couch;
	
	if (0)
	{
		echo '<pre>';
		print_r($_SERVER);
		echo '</pre>';
	}
	
	if ($config['use_view_counter'])
	{
		if ($page == 0)
		{
			// Remove any extraneous keys inserted by hosting environment
			if (isset($_SERVER['_']))
			{
				unset($_SERVER['_']);
			}
			// comment out to stop logging
			$resp = $couch->send("POST", "/" . $config['couchdb_options']['database'], json_encode($_SERVER));	
			//var_dump($resp);
		}
	}
		
	$reference = null;
	
	// API call
	$ok = false;
	
	$url = $config['web_server'] . $config['web_root'] . 'api.php?id=' . urlencode($id);
	$json = get($url);
			
	if ($json != '')
	{
		$reference = json_decode($json);
		$ok = ($reference->status == 200);
	}
	
	if (!$ok)
	{
		// bounce
		header('Location: ' . $config['web_root'] . '?error=Record not found' . "\n\n");
		exit(0);
	}
		
	$script = '<script>
		function show_formatted_citation(format) {
			$.get("api.php?id=' . $id . '&format=citationprocjs&style=" + format + "&callback=?",
				function(data){
					$("#citation").html(data);
			});
		}
	</script>';
	
	$script .= '<script>
		function openurl(id, q) {
		       //$("#biblink" + id).html("Searching...");
				$.getJSON("api_openurl.php?dat=" + encodeURIComponent(q) + "&redirect=false&callback=?",
				function(data){
					if (data.results.length > 0) {
						if (data.results.length == 1) {
							html = "[<a href=\"reference/" + data.results[0].id + "\">BioStor</a>]";
							$("#biblink" + id).html(html);
						} else {
						    //$("#biblink" + id).html("");
						}
					} else {
					 //$("#biblink" + id).html("");
					}
				}
			);
		}
	</script>';	
	
	$script .= '<script>
				function show_cites(id) {
					$.getJSON("api.php?id=" + encodeURIComponent(id) + "&cites&callback=?",
						function(data){
						  if (data.cites) {
							var html = "";
							html += "<h4>Cites</h4>";
							html += "<p>References automatically extracted from OCR text</p>"
							html += "<ol>";
							for (var i in data.cites) {
								html += "<li><a name=\"bib" + i + "\"/>";
								html += data.cites[i];
								
								var q = data.cites[i];
								q = q.replace(/\\\'/, "");
								
								html += "<a class=\"btn\" onclick=\"openurl(\'" + i + "\',\'" + q + "\')\"><i class=\"glyphicon glyphicon-search\"></i></a>";
								html += "<span id=\"biblink" + i + "\"/>";
								
								html += "</li>";
							}
							html += "</ol>";
							$("#cites").html(html);
						}
					});
				}
				
			</script>';	
	
	$meta = reference_to_google_scholar($reference);
	
	$canonical_url = $config['web_server'] . $config['web_root'] . 'reference/' . str_replace('biostor/', '', $id);
	if ($page != 0)
	{
		$canonical_url .= '/page/' . $page;
	}
	$meta .= '<link rel="canonical" href="' . $canonical_url . '" />';
	
	display_html_start($reference->title, $meta, $script);
	display_navbar();	
	
	/*
	echo '<pre>';
	print_r($reference);
	echo '</pre>';
	*/
	
	
	// Breadcrumbs -----------------------------------------------------------------------
	echo '<ol class="breadcrumb">' . "\n";	
//	echo '<li><a href="?titles">All titles</a></li>' . "\n";	
	echo '<li><a href="titles">All titles</a></li>' . "\n";	
	
	if (isset($reference->journal))
	{
		$journal_namespace = '';
		$journal_identifier = '';
	
		$issn = '';
		$oclc = '';
		if (isset($reference->journal->identifier))
		{
			foreach ($reference->journal->identifier as $identifier)
			{
				switch ($identifier->type)
				{
					case 'issn':
						$journal_namespace = 'issn';
						$journal_identifier = $identifier->id;
						break;
					case 'oclc':
						$journal_namespace = 'oclc';
						$journal_identifier = $identifier->id;
						break;
					default:
						break;					
				}
			}
		}
		echo '<li>';
		if ($journal_namespace != '')
		{
			echo '<a href="' . $journal_namespace . '/' . $journal_identifier . '">' . $reference->journal->name . '</a>';			
		}
		else
		{
			echo $reference->journal->name;
		}		
		if (isset($reference->journal->series))
		{
			echo ' series ' . $reference->journal->series;
		}	
		echo '</li>' . "\n";	
		
		if (isset($reference->year))
		{
			echo '<li>';
			if ($journal_namespace != '')
			{
				echo '<a href="' . $journal_namespace . '/' . $journal_identifier  . '/year/' . $reference->year . '">' . $reference->year . '</a>';			
			}
			else
			{		
				echo ' ' . $reference->year;
			}
			echo '</li>' . "\n";	
		}				
		echo '<li class="active">' . $id . '</li>' . "\n";
	}
	echo '</ol>';
	
	
	// display article
	$num_pages = count((array)$reference->bhl_pages);
	
	echo '<div class="container">' . "\n";	
	echo '<div class="row">' . "\n";

	if (($page == 0) || ($page > $num_pages))
	{
		// thumbnails
		echo '<div class="col-md-8">' . "\n";
		echo '<div class="row">' . "\n";
		echo '  <div>';
		display_article_metadata($reference);
		echo '  </div>';	    
		echo '</div>';  
		
		// thumbnail images
		echo '<div class="row">' . "\n";
	
		$page_count = 1;
		foreach ($reference->bhl_pages as $label => $PageID)
		{
			if ($config['image_source'] == 'bhl')
			{
				$image_url = 'https://www.biodiversitylibrary.org/pagethumb/' .  $PageID . ',60,60';	
				
				if ($config['use_cloudimage'])
				{
					$image_url = 'http://exeg5le.cloudimg.io/s/height/100/' . $image_url;
				}		

				if ($config['use_weserv'])
				{
					$image_url = 'https://images.weserv.nl/?url=' . str_replace('http://', '', $image_url);
				}		

				if ($config['use_image_proxy'])
				{
					$image_url = $config['web_root'] . 'page/image/' . $PageID . '-small.jpg';
				}		
			}
			else
			{
				$image_url = 'http://direct.biostor.org/bhl_image.php?PageID=' . $PageID . '&thumbnail';
			}
		
			echo '<div style="position:relative;display:inline-block;padding:20px;">';			
			echo '<a href="reference/' . str_replace('biostor/', '', $id) . '/page/' . $page_count . '" >';
			echo '<img style="box-shadow:2px 2px 2px #ccc;border:1px solid #ccc;" src="' . $image_url . '" alt="' . $label . '" width="100" />';
			echo '<p style="text-align:center">' . $label . '</p>';
			echo '</a>';
			echo '</div>';					
			$page_count++;		
		} 

		echo '</div>';  
		
		// cites
		echo '<div id="cites">';
		echo '</div>';
		
		echo '</div>'; // <div class="col-md-8">

		// tools, linked stuff, etc.
		echo '	<div class="col-md-4">' . "\n";
		
		// PDF
		$pdf_url = 'https://archive.org/download/' . str_replace('biostor/', 'biostor-', $id) . '/' . str_replace('biostor/', 'biostor-', $id) . '.pdf';
		if (head($pdf_url))
		{
			// Need to urlencode URL we pass to viewer, which means PDF URL is double URL encoded
			$cached_pdf_url = $config['web_server'] . $config['web_root']. 'pdfproxy.php?url=' . urlencode($pdf_url);
			
			$pdf_viewer_url = 'external/pdf.js-hypothes.is/viewer/web/viewer.html?file=' . urlencode($cached_pdf_url);
		
			echo '<div class="row">';
			echo '<a class="btn btn-info" style="width:100%" href="' . $pdf_url . '" onClick="_gaq.push([\'_trackEvent\', \'Export\', \'pdf\', \'' . str_replace('biostor/', 'biostor-', $id) . '\', 0]);">Download PDF</a>';
			//echo '<br />';
			//echo '<a class="btn btn-warning" style="width:100%" href="' . $pdf_viewer_url . '" onClick="_gaq.push([\'_trackEvent\', \'View\', \'pdf\', \'' . str_replace('biostor/', 'biostor-', $id) . '\', 0]);">View PDF</a>';
			echo '</div>';
		}
		
		// ads
		/*
		if ($config['show_ads'])
		{
			echo '<div class="row" style="border:1px solid rgb(228,228,228)">';
			echo '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<!-- biostor-side-panel -->
<ins class="adsbygoogle"
     style="display:inline-block;width:336px;height:280px"
     data-ad-client="ca-pub-7354682617866492"
     data-ad-slot="4714332968"></ins>
<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>';
			echo '</div>';
		}
		*/
		
			
		// citation formatter
		echo '<div class="row">';
		echo '<select id="format" onchange="show_formatted_citation(this.options[this.selectedIndex].value);">
			<option label="Citation format" disabled="disabled" selected="selected"></option>
			<option label="APA" value="apa"></option>
			<option label="BibTeX" value="bibtex"></option>
			<option label="RIS" value="ris"></option>
			<option label="Wikipedia" value="wikipedia">
			<option label="ZooKeys" value="zookeys">
			<option label="Zootaxa" value="zootaxa"></option>
		</select>';	
	
		echo '<div id="citation" style=font-size:11px;"width:300px;height:100px;border:1px solid black;"><br/><br/><br/><br/><br/><br/></div>';
		echo '</div>';
		
	
		/* echo '<textarea id="citation" style="font-size:10px;" rows="6" readonly></textarea>'; */
		
		
		// view counts
		if ($config['use_view_counter'])
		{
			echo '<div class="row">';
			echo '<div id="view_counter"></div>';
			echo '</div>';
		}
				
				
				
		echo '<div class="row">';
		// altmetric badge
		$data_string = altmetric_data_string($reference);
		if ($data_string != '')
		{
			echo '<div>';
			echo '<div data-badge-details="right" data-badge-type="medium-donut" ' . $data_string . ' data-hide-no-mentions="true" class="altmetric-embed"></div>';					
			echo '</div>';
		}
		echo '</div>';
		
		echo '<div class="row">';
		
		if (isset($reference->geometry)) 
		{
			echo '<p class="muted">Localities in publication.</p>';
			echo '<object id="mapsvg" type="image/svg+xml" width="360" height="180" data="api_map.php?coordinates=' . urlencode(json_encode($reference->geometry->coordinates)) . '"></object>';
	
			// schema.org
			foreach ($reference->geometry->coordinates as $point)
			{
				echo '<span itemscope itemtype="http://schema.org/GeoCoordinates">';
				echo '<meta itemprop="latitude" content="' . $point[1] . '" />';
				echo '<meta itemprop="longitude" content="' . $point[0] . '" />';
				echo '</span>';
			}	
		}		
		echo '</div>';
		
		// Disqus
		echo '<div class="row">';
		
		echo '<div id="disqus_thread"></div>
<script type="text/javascript">
	/* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
	var disqus_shortname = "biostor"; // required: replace example with your forum shortname

	/* * * DON\'T EDIT BELOW THIS LINE * * */
	(function() {
		var dsq = document.createElement(\'script\'); dsq.type = \'text/javascript\'; dsq.async = true;
		dsq.src = \'//\' + disqus_shortname + \'.disqus.com/embed.js\';
		(document.getElementsByTagName(\'head\')[0] || document.getElementsByTagName(\'body\')[0]).appendChild(dsq);
	})();
</script>
<noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
<a href="http://disqus.com" class="dsq-brlink">comments powered by <span class="logo-disqus">Disqus</span></a>';

		echo '</div>';
	
		echo '</div>' . "\n"; // <div class="col-md-4">
		
		if ($config['use_view_counter'])
		{
			$script .= '<script>
				function show_view_count() {
					$.getJSON("api_counter.php?id=' . $id . '&callback=?",
						function(data){
						  if (data.results) {
							var html = \'Views <span class="badge badge-important">\' + data.results[0] + \'</span>\';
							$("#view_counter").html(html);
						}
					});
				}
				show_view_count();
			</script>';	
			echo $script;
		}
		
		
	
		echo '<script>';
		echo 'show_cites("' . $id . '");';
		echo '</script>';
		
		
	}
	else
	{
		// page viewer
		echo '<div class="row">' . "\n";
		echo '	<div class="col-md-12">' . "\n";
	
		echo '<div>';
		display_article_metadata($reference);
		echo '</div>';
	
			// Navigation
			echo '<nav>';
			echo '  <ul class="pager">';
			echo '    <li class="previous';
			if ($page == 1)
			{
				echo ' disabled';
			}
			echo '"><a href="reference/' . str_replace('biostor/', '', $id) . '/page/' . ($page - 1) . '"><span aria-hidden="true">&larr;</span> Previous</a></li>';		
			echo '<li><a href="reference/' . str_replace('biostor/', '', $id) . '">Thumbnails</a></li>';
			echo '    <li class="next';
			if ($page == $num_pages)
			{
				echo ' disabled';
			}
			echo '"><a href="reference/' . str_replace('biostor/', '', $id) . '/page/' . ($page + 1) . '">Next <span aria-hidden="true">&rarr;</span></a></li>';
			echo '  </ul>';
			echo '</nav>';

			$keys = array();
			foreach ($reference->bhl_pages as $k => $v)
			{
				$pages[] = $v;
			}
			$PageID = $pages[$page - 1];
			
			// Source of image
			if ($config['image_source'] == 'bhl')
			{				
				$image_url = 'https://www.biodiversitylibrary.org/pagethumb/' .  $PageID . ',500,500"';	
				
				if ($config['use_cloudimage'])
				{
					$image_url = 'http://exeg5le.cloudimg.io/s/width/700/' . $image_url;
				}
				
				if ($config['use_weserv'])
				{
					$image_url = 'https://images.weserv.nl/?url=' . str_replace('http://', '', $image_url);
				}
				
				if ($config['use_image_proxy'])
				{
					$image_url = $config['web_root'] . 'page/image/'  . $PageID . '-normal.jpg';
				}				
				
			}
			else
			{
				$image_url = 'http://direct.biostor.org/bhl_image.php?PageID=' . $PageID;
			}

			// Do we have this page in the database, with XML?
			$html = '';
			$url = $config['web_server'] . $config['web_root'] . 'api.php?page=' . $PageID . '&format=html';
			$json = get($url);
			
			if ($json != '')
			{
				$page = json_decode($json);
				$html = $page->html;
				
				// wierd bug with DjVu XML and image cache where space ets added at end of URL
				$html = preg_replace('/,500,500%22"/u', ',500,500"', $html);
			}
			
			if ($html == '')
			{
				$html = '<img width="700" style="box-shadow:2px 2px 2px #ccc;border:1px solid #ccc;-webkit-user-drag: none;-webkit-user-select: none;" src="' . $image_url . '" />';				
			}

			echo '<div class="col-md-2"></div>';
			echo '<div class="col-md-8">';

			echo $html;

			echo '</div>';
			echo '<div class="col-md-2"></div>';
	
		echo '  </div>' . "\n";
	
		echo '</div>' . "\n";
	
	
	}
		
	echo '</div>'; // row
	echo '</div>'; // container
	
	
	display_html_end();	
}

//----------------------------------------------------------------------------------------
// Display a list of search results, possibly with a Cloudant bookmark to indicate 
// the next set of results
function display_search($q, $bookmark = '')
{
	global $config;
	
	$rows_per_page = 10;
	
	
	// Parse query
	
	$query = array();
	$query['q'] = $q;
	
	$matched = false;
	
	if (!$matched)
	{
		if (preg_match('/(?<q>author:"(?<author>.*)")\s+AND\s+year:"(?<year>[0-9]{4})"/u', $q, $m))
		{
			$query['q'] = $m['q'];
			$query['author'] = $m['author'];
			$query['year'] = $m['year'];
			$matched = true;
		}
	}

	if (!$matched)
	{
		if (preg_match('/(?<q>author:"(?<author>.*)")/u', $q, $m))
		{
			$query['q'] = $m['q'];
			$query['author'] = $m['author'];
			$matched = true;
		}
	}
	
	if (!$matched)
	{
		if (preg_match('/(?<q>.*)\s+AND\s+year:"(?<year>[0-9]{4})"/u', $q, $m))
		{
			$query['q'] = $m['q'];
			$query['year'] = $m['year'];
			$matched = true;
		}
	}
	
	
	//print_r($query);
	
	$script = '';
	
	// Author-specific stuff
	if (isset($query['author']))
	{
		$lastname = $firstname = '';
		
		// parse author name
		$parts = parse_name($query['author']);
		if (isset($parts['last']))
		{
			$lastname = $parts['last'];		
		}
		if (isset($parts['first']))
		{
			$firstname = $parts['first'];

			if (array_key_exists('middle', $parts))
			{
				$firstname .= ' ' . $parts['middle'];
			}
		}	
	
		$script = '<script>
			function show_similar_authors(lastname, firstname) {
				$.getJSON("api_author.php?lastname=" + encodeURIComponent(lastname) + "&firstname=" + encodeURIComponent(firstname) + "&callback=?",
					function(data){
					  if (data.results.length > 0) {
						 var html = \'<h4>Similar names</h4>\';
						 html += \'<ul>\';
						 for (var i in data.results) {
							var name = data.results[i].name;
							html += \'<li><a href="?q=author:&quot;\' + name + \'&quot;">\' + name + \'</a></li>\';
						 }
						 html += \'</ul>\';
						 $("#query_suggest").html(html);
					  }
				});
			}
		</script>';
	
		$sparql = 'SELECT *
	WHERE
	{
	  ?item rdfs:label "' . $query['author'] . '"@en .
	  ?article schema:about ?item .
	  ?article schema:isPartOf <https://species.wikimedia.org/> .
	  OPTIONAL {
	   ?item wdt:P213 ?isni .
		}
	  OPTIONAL {
	   ?item wdt:P214 ?viaf .
		}    
	  OPTIONAL {
	   ?item wdt:P18 ?image .
		} 
	  OPTIONAL {
	   ?item wdt:P496 ?orcid .
		} 		
	  OPTIONAL {
	   ?item wdt:P586 ?ipni .
		} 
	  OPTIONAL {
	   ?item wdt:P2006 ?zoobank .
		} 		
	}';

	 $sparql = str_replace("\n", " ", $sparql);
	
		$script .= '<script>
			function show_wikidata() {
				$.getJSON("https://query.wikidata.org/bigdata/namespace/wdq/sparql?query=" + encodeURIComponent(\'' . $sparql . '\') + "",
					function(data){
					  if (data.results.bindings.length > 0) {
						 var html = \'<h4>Wikidata</h4>\';
					 
						 if (data.results.bindings[0].item) {
						   html += \'<a href="\' + data.results.bindings[0].item.value + \'" target="_new">Wikidata: \' + data.results.bindings[0].item.value.replace(/http:\/\/www.wikidata.org\/entity\//, "") + \'</a><br />\';
						 }

						 if (data.results.bindings[0].article) {
						   html += \'<a href="\' + data.results.bindings[0].article.value + \'" target="_new">Wikispecies</a><br />\';
						 }
					 
						 if (data.results.bindings[0].isni) {
						   html += \'<a href="http://isni.org/isni/\' + data.results.bindings[0].isni.value.replace(/ /g, \'\') + \'" target="_new">ISNI: \' + data.results.bindings[0].isni.value + \'</a><br />\';
						 }
						 if (data.results.bindings[0].viaf) {
						   html += \'<a href="http://viaf.org/viaf/\' + data.results.bindings[0].viaf.value + \'" target="_new">VIAF: \' + data.results.bindings[0].viaf.value + \'</a><br />\';
						 }
						 
						 if (data.results.bindings[0].orcid) {
						   html += \'<a href="http://orcid.org/\' + data.results.bindings[0].orcid.value + \'" target="_new">ORCID: \' + data.results.bindings[0].orcid.value + \'</a><br />\';
						 }
						 
						 
						 
						 if (data.results.bindings[0].ipni) {
						   html += \'<a href="http://www.ipni.org/ipni/idAuthorSearch.do?id=\' + data.results.bindings[0].ipni.value + \'" target="_new">IPNI: \' + data.results.bindings[0].ipni.value + \'</a><br />\';
						 }
						 if (data.results.bindings[0].zoobank) {
						   html += \'<a href="http://zoobank.org/Authors/\' + data.results.bindings[0].zoobank.value + \'" target="_new">ZooBank: \' + data.results.bindings[0].zoobank.value + \'</a><br />\';
						 }
					 
					 
						 if (data.results.bindings[0].image) {
						   html += \'<img src="\' + data.results.bindings[0].image.value + \'" width="64" />\';
						 }
					 
						 $("#wikidata").html(html);
					  }
				});
			}
		</script>';
	}
	
	display_html_start('Search results', '', $script);
	display_navbar(htmlentities($q));	
	
	echo '<h4>Search results for "' . htmlentities($q) . '"</h4>';
	
	echo '<div class="container-fluid">' . "\n";
	echo '  <div class="row">' . "\n";
	echo '	  <div class="col-md-8">' . "\n";
		
	$url = $config['web_server'] . $config['web_root'] . 'api.php?q=' . urlencode($q);
	
	if ($bookmark != '')
	{
		$url .= '&bookmark=' . $bookmark;
	}
					
	$json = get($url);
	//echo $json;
	
	if ($json != '')
	{
		$obj = json_decode($json);
	

		echo '<h5>' . $obj->total_rows . ' hit(s)' . '</h3>';
	
	
		if ($obj->total_rows > $rows_per_page)
		{
			echo '<nav>';
			echo '  <ul class="pager">';
			echo '    <li class="next">';
			//echo '      <a class="btn" href="?q=' . urlencode($q) . '&bookmark=' . $obj->bookmark . '">More results »</a>';
			echo '      <a class="btn" href="search/' . urlencode($q) . '/bookmark/' . $obj->bookmark . '">More results »</a>';
			echo '   </li>';
			echo '  </ul>';
			echo '</nav>';
		}
	
		if (isset($obj->rows))
		{
			foreach ($obj->rows as $row)
			{
				$reference = $row->doc;
			
				display_record_summary($reference, $row->highlights);	
			}
		}
				
		if ($obj->total_rows > $rows_per_page)
		{
			echo '<nav>';
			echo '  <ul class="pager">';
			echo '    <li class="next">';
			//echo '      <a class="btn" href="?q=' . urlencode($q) . '&bookmark=' . $obj->bookmark . '">More results »</a>';
			echo '      <a class="btn" href="search/' . urlencode($q) . '/bookmark/' . $obj->bookmark . '">More results »</a>';
			echo '   </li>';
			echo '  </ul>';
			echo '</nav>';
		}
		
	}
	
	echo '   </div>';
	
	// Put further info about results here...
	echo '	 <div class="col-md-4">' . "\n";
	echo '      <div id="query_suggest"></div>' . "\n";	
	echo '      <div id="wikidata"></div>' . "\n";	
	
	if (isset($obj->counts))
	{
		if (isset($obj->counts->year))
		{
			echo 	'<h4>Year</h4>';
			echo '<ul class="nav nav-list">';
			
			foreach ($obj->counts->year as $year => $count)
			{
				echo '<li>';
				
				if (isset($query['year']) && ($query['year'] == $year))
				{
					echo '<a href="search/' . urlencode($query['q']) . '">';
					echo '<i class="glyphicon glyphicon-check"></i>';				
				}
				else
				{
					echo '<a style="padding:2px;" href="search/' . urlencode($query['q'] . ' AND year:"' . $year . '"') . '">';
					echo '<i class="glyphicon glyphicon-unchecked"></i>';
				}
				
//				echo  $year . ' (' . $count . ')';
				echo  $year . ' <span class="badge">' . $count . '</span>';
				echo '</a>';
				echo '</li>';
			}
			
			echo '</ul>';	
		}
	}
	
	echo '   </div>';
	
	
	echo '  </div>';
	echo '</div>';
	
	if (isset($query['author']))
	{
		echo '<script>';
		echo 'show_similar_authors("' . addcslashes($lastname, '"') . '","' . addcslashes($firstname, '"') . '");';
		echo 'show_wikidata();';
		echo '</script>';
	}
	
	display_html_end();	
}

//----------------------------------------------------------------------------------------
// Displaynavigation bar
function display_navbar($q = "")
{

	global $config;

echo '<nav class="navbar navbar-default navbar-fixed-top">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href=".">
        <img alt="Brand" src="static/biostor-shadow32x32.png" height="20">
      </a>      
     <form class="navbar-form navbar-left" role="search" action="' . $config['web_root'] . '">
       <div class="form-group">
         <input type="text" class="form-control" placeholder="Search" name="q" value="' . $q . '">
       </div>
      </form>     
      <ul class="nav navbar-nav">
        <!-- <li><a href="?titles">Titles</a></li> -->
        <li><a href="titles">Titles</a></li>
        <li><a href="images">Images</a></li>
        <li><a href="map">Map</a></li>
        <li><a href="labs">Labs</a></li>
        <li><a href="https://github.com/rdmpage/biostor/blob/master/api-doc.md">API</a></li>
      </ul>
    </div>
  </div>
</nav>';
}

//----------------------------------------------------------------------------------------
function display_html_start($title = '', $meta = '', $script = '')
{
	global $config;
	
	echo '<!DOCTYPE html>
<html lang="en">
<head>';

	if ($config['show_ads'])
	{
		echo "\n";
		echo '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<script>
  (adsbygoogle = window.adsbygoogle || []).push({
    google_ad_client: "ca-pub-7354682617866492",
    enable_page_level_ads: true
  });
</script>';
		echo "\n";
	}

	echo '<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1">-->
    
    <!-- Google -->
    <meta name="google-site-verification" content="G0IJlAyehsKTOUGWSc-1V2RMtYQLnqXs440NUSxbYgA" />
    <!-- Twitter -->
	<meta name="twitter:site" content="@biostor_org" />
	<!-- Pintrest -->
	<meta name="p:domain_verify" content="5f60c8da3099dba7fd452ca1b9668c0a"/>
	<!-- OpenSearch -->
	<link href="' . $config['web_root'] . '/opensearch.xml" rel="search" title="BioStor Search" type="application/opensearchdescription+xml">'

    . $meta . 
    
    '<!-- base -->
    <base href="' . $config['web_root'] . '" /><!--[if IE]></base><![endif]-->
    <!-- favicon -->
	<link href="static/biostor-shadow32x32.png" rel="icon" type="image/png">    
    <!-- Boostrap -->
    <!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
	<!-- Latest compiled and minified JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
	<!-- altmetric -->
	<script type="text/javascript" src="https://d1bxh8uas1mnw7.cloudfront.net/assets/embed.js"></script>
	
	<!-- Google maps 
 	<script src="http://www.google.com/jsapi"></script>
 	<script type="text/javascript" src="js/map.js"></script>
 	-->
 	
 	<!-- Leaflet -->
	<!--    <link rel="stylesheet" href="https://cdn.leafletjs.com/leaflet-0.7/leaflet.css" /> -->
    <link rel="stylesheet" href="external/leaflet-0.7.3/leaflet.css" />
    <link rel="stylesheet" href="external/leaflet.draw/leaflet.draw.css" />
    
    <!-- <script src="https://cdn.leafletjs.com/leaflet-0.7/leaflet.js"></script> -->
    <script src="external/leaflet-0.7.3/leaflet.js"></script>
    <script src="external/leaflet.draw/leaflet.draw.js"></script>
    
    <script src="external/Wicket/wicket.js"></script>
     

	<!-- Wicket -->
    <script src="external/Wicket/wicket.js"></script>
 	
 	
 	<script type="text/javascript" src="js/wikidata.js"></script>
	'	
	. $script . '
	<title>' . $title . '</title>
	
	<style>
	/* Zenodo-style DOI */
	span.doi {
		font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
		color: white;
		background: black;
		font-size: 13px;
		text-decoration: none;
		padding: 2px 0px 2px 4px;
		border-radius: 5px;
		border-color: black;
	}

	span.doi:before {
		content: "DOI";
	}

	span.doi a {
		color: white;
		background: #0099cc;
		text-decoration: none;
		text-transform: lowercase;
		margin-left: 4px;
		padding: 2px 5px 2px 4px;
		border-radius: 0px 5px 5px 0px;
	}
	
	/* ZooBank-style LSID */
	span.lsid {
		font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
		color: white;
		background: rgb(255,113,72);
		font-size: 13px;
		text-decoration: none;
		padding: 2px 0px 2px 4px;
		border-radius: 5px;
		border-color: black;
	}

	span.lsid:before {
		content: "LSID";
	}

	span.lsid a {
		color: black;
		background: #f5f5f5; /* white disappears */
		text-decoration: none;
		text-transform: lowercase;
		margin-left: 4px;
		padding: 2px 5px 2px 4px;
		border-radius: 0px 5px 5px 0px;
	}
	
	/* JSTOR */
	span.jstor {
		font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
		color: white;
		background: rgb(149,38,55);
		font-size: 13px;
		text-decoration: none;
		padding: 2px 0px 2px 4px;
		border-radius: 5px;
		border-color: black;
	}

	span.jstor:before {
		content: "JSTOR";
	}

	span.jstor a {
		color: black;
		background: #f5f5f5; /* white disappears */
		text-decoration: none;
		text-transform: lowercase;
		margin-left: 4px;
		padding: 2px 5px 2px 4px;
		border-radius: 0px 5px 5px 0px;
	}
	
	/* BHL */
	span.bhl {
		font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
		color: white;
		background: rgb(60,150,200);
		font-size: 13px;
		text-decoration: none;
		padding: 2px 0px 2px 4px;
		border-radius: 5px;
		border-color: black;
	}

	span.bhl:before {
		content: "BHL";
	}

	span.bhl a {
		color: black;
		background: #f5f5f5; /* white disappears */
		text-decoration: none;
		text-transform: lowercase;
		margin-left: 4px;
		padding: 2px 5px 2px 4px;
		border-radius: 0px 5px 5px 0px;
	}	
	
	</style>	
	
	</head>
<body style="padding-top:70px;padding-left:20px;padding-right:20px;padding-bottom:20px;">';

if ($config['show_ads'])
{
echo '<div class="row">';
echo '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<!-- biostor-leaderboard -->
<ins class="adsbygoogle"
     style="display:inline-block;width:728px;height:90px"
     data-ad-client="ca-pub-7354682617866492"
     data-ad-slot="4574732162"></ins>
<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>';
}
echo '</div>';

}

//----------------------------------------------------------------------------------------
function display_html_end()
{
	global $config;
	
	echo '<p/>'; // hack to put some space between content and footer
	echo '<div class="panel panel-default" id="footer">
  <div class="panel-body">
    <p style="vertical-align: top">BioStor is built by <a href="https://twitter.com/rdmpage">@rdmpage</a>, code on <a href="https://github.com/rdmpage/biostor">github</a>. 
    Page images from the <a href="https://biodiversitylibrary.org">Biodiversity Heritage Library</a>.';
    /*
    echo "<a href=\"https://twitter.com/biostor_org\" class=\"twitter-follow-button\" data-show-count=\"false\">Follow @biostor_org</a> <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>"; */
    echo '
    </p>
  </div>
</div>';

	
	echo "<script type=\"text/javascript\">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-12127487-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>\n";

	echo '</body>';
	
	if ($config['use_hypothesis'])
	{
		echo '<!-- hypothes.is -->
	<script defer async src="//hypothes.is/embed.js"></script>';
	}
echo '</html>';
}


//----------------------------------------------------------------------------------------
// Display all the titles in the database (essentially all the journals)
// Split by first letter of journal name
function display_titles($letter= 'A')
{
	global $config;
	global $couch;
	
	display_html_start('Titles');

	display_navbar();
	
	echo '<div  class="container-fluid">' . "\n";
	
	echo '<h1>Titles starting with the letter "' . $letter . '"</h1>';

	// all volumes for journal
	$url = $config['web_server'] . $config['web_root'] . 'api_journal.php?titles&letter=' . $letter;
	
	
	$json = get($url);
	
	if ($json != '')
	{
		$obj = json_decode($json);
					
		echo '<nav>' . "\n";
  		echo '<ul class="pagination">' . "\n";
		
		$all_letters = range("A", "Z");
		foreach ($all_letters as $starting_letter)
		{
			echo '<li';
			if ($letter == $starting_letter)
			{
				echo ' class="active"';
			}
//			echo '><a href="?titles&letter=' . $starting_letter . '">' .  $starting_letter . '</a>';
			echo '><a href="titles/letter/' . $starting_letter . '">' .  $starting_letter . '</a>';
			echo '</li>' . "\n";
		}
		echo '</ul>';
		echo '</nav>';
		
		// journals
		
		echo '<div>';
		echo '<ul>';
		foreach ($obj->titles as $identifier => $title)
		{
			echo '<li>';
			
			if (isset($title->issn))
			{
				//echo '<a href="issn/' . $title->issn . '">';
				echo '<a href="issn/' . $title->issn . '">';
			}
			if (isset($title->oclc))
			{
				//echo '<a href="?oclc=' . $title->oclc . '">';
				echo '<a href="oclc/' . $title->oclc . '">';
			}
			
			echo $title->title;
			 
			if (isset($title->issn) || isset($title->oclc))
			{
				echo '</a>';
			}
			 
			 
			echo '</li>';
		}
		echo '</ul>';
		echo '</div>';
	}
	
	echo '</div>' . "\n";
	
	display_html_end();
}

//----------------------------------------------------------------------------------------
// Display images from Pintrest
function display_images()
{
	global $config;
	global $couch;
	
	display_html_start('Images');

	display_navbar();
	
	echo '<div  class="container-fluid">' . "\n";
	
	echo '<h1>Images from BioStor</h1>';
	echo '<p>These images are from the <a href="https://www.pinterest.com/rdmpage/biostor/">BioStor board on Pintrest</a>.</p>';
	
	$url = $config['web_server'] . $config['web_root'] . 'api.php?images';
	
	$json = get($url);
	$obj = null;
	if ($json != '')
	{
		$obj = json_decode($json);
	}
	
	//echo $json;
	//print_r($obj);
	
	echo '<div class="container">' . "\n";	
	echo '<div class="row">' . "\n";

	if ($obj)
	{
		foreach ($obj->images as $image)
		{
			echo '<div style="position:relative;display:inline-block;padding:20px;">';			
			echo '<a href="reference/' . $image->biostor . '" >';
			echo '<img style="width:100px;box-shadow:2px 2px 2px #ccc;border:1px solid #ccc;" src="' . $image->src . '"/>';
			echo '</a>';
			echo '</div>';
		}
	}				
	echo '</div>';  
	echo '</div>'; // <div class="col-md-8">

	
	display_html_end();
}


//----------------------------------------------------------------------------------------
function display_map()
{
	global $config;
	global $couch;
	
	display_html_start('Map');
	display_navbar();
	
	/*
	echo '<div class="container-fluid">' . "\n";
	echo '  <div class="row">' . "\n";
	echo '	  <div class="col-md-8">' . "\n";
	echo '      <div style="width:600px;height:500px;" id="map"></div>';
	echo '    </div>';
	echo '	  <div class="col-md-4">' . "\n";
	echo '       <div id="hit" style="font-size:11px;"></div>';
	echo '    </div>';
	echo '  </div>';
	echo '</div>';
	*/
	
	
	echo '<div class="container-fluid">' . "\n";
	echo '  <div class="row">' . "\n";
	echo '      <div style="width:100%;height:500px;" id="map" ></div>';
	echo '  </div>';
	echo '</div>';
	
	// echo '<script type="text/javascript" src="http://maps.stamen.com/js/tile.stamen.js?v1.3.0"></script>'; 
	echo '<script src="js/leaflet_map.js"></script>';
	
	/*
	echo '<script>';
	echo "	// http://stackoverflow.com/questions/6762564/setting-div-width-according-to-the-screen-size-of-user 
	$(window).resize(function() { 
			var windowWidth = $(window).width() - 40;
			var windowHeight =$(window).height() -  $('#map').offset().top - $('#footer').height();
			$('#map').css({'height':windowHeight, 'width':windowWidth });
	});	";
	echo '</script>';
	*/

	
	
	display_html_end();
}

//----------------------------------------------------------------------------------------
function display_labs()
{
	global $config;
	global $couch;
	
	display_html_start('Labs');
	display_navbar();
	
	echo '<div class="container-fluid">' . "\n";

	echo '<h1>Experiments with BioStor and BHL content</h1>';
	echo '<p>This is a playground for various ideas.</p>';
	
	echo '<ul>';
	echo '<li><a href="bhl-couchdb/?q=Serinus mozambicus">BHL CouchDB full-text indexing</a></li>';
	echo '<li><a href="match.html">Match references using reconcile service</a></li>';
	echo '<li><a href="timeline.php?q=Aspidoscelis costata, Cnemidophorus costatus">Timeline of name in BHL</a></li>';
	echo '</ul>';
	echo '</div>';

	
	display_html_end();
}


//----------------------------------------------------------------------------------------
// Main...
function main()
{	
	$query = '';
	$bookmark = '';
		
	// If no query parameters 
	if (count($_GET) == 0)
	{
		default_display();
		exit(0);
	}
	
	// Error message
	if (isset($_GET['error']))
	{	
		$error_msg = $_GET['error'];
		
		default_display($error_msg);
		exit(0);			
	}
	
	
	// Show a single record
	if (isset($_GET['id']))
	{	
		$id = $_GET['id'];
		
		if (isset($_GET['page']))
		{
			// we are vieiwng pages
			$page = $_GET['page'];
			// to do: sanity check
			
			display_record($id, $page);
			exit(0);			
		}
		
		
		display_record($id);
		exit(0);
	}
	
	// Show journals
	if (isset($_GET['titles']))
	{	
		$letter = 'A';
		if (isset($_GET['letter']))
		{
			$letter = $_GET['letter'];
			// sanity check
			if (!in_array($letter, range('A', 'Z')))
			{
				$letter = 'A';
			}			
		}
				
		display_titles($letter);
		exit(0);
	}
	
	// Show images
	if (isset($_GET['images']))
	{	
		display_images();
		exit(0);
	}
	
	// Show map
	if (isset($_GET['map']))
	{	
		display_map();
		exit(0);
	}
	
	// Show labs
	if (isset($_GET['labs']))
	{	
		display_labs();
		exit(0);
	}
	
	
	// Show journal (ISSN)
	if (isset($_GET['issn']))
	{	
		$issn = $_GET['issn'];
		
		$year = '';
		
		if (isset($_GET['year']))
		{
			$year = $_GET['year'];
			
			display_journal_year('issn', $issn, $year);
			exit(0);
		}
		
		display_journal('issn', $issn, $year);
		exit(0);
	}
	
	// Show journal (OCLSC
	if (isset($_GET['oclc']))
	{	
		$oclc = $_GET['oclc'];
		
		$year = '';
		
		if (isset($_GET['year']))
		{
			$year = $_GET['year'];
			
			display_journal_year('oclc', $oclc, $year);
			exit(0);
		}
		
		display_journal('oclc', $oclc, $year);
		exit(0);
	}
	
	
	
	// Show search (text, author)
	if (isset($_GET['q']))
	{	
		$query = $_GET['q'];
		$bookmark = '';
		if (isset($_GET['bookmark']))
		{
			$bookmark = $_GET['bookmark'];
		}
		display_search($query, $bookmark);
		exit(0);
	}	
	
}


main();

		
?>
