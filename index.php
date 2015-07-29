<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');
require_once (dirname(__FILE__) . '/reference_code.php');

//----------------------------------------------------------------------------------------
function default_display($error_msg = '')
{
	global $config;
	
	display_html_start('BioStor');
	display_navbar();
	
	if ($error_msg != '')
	{
		echo '<div class="alert alert-danger" role="alert"><strong>Error!</strong> ' . $error_msg . '</div>';
	}
	
	echo '<div class="alert alert-warning" role="alert"><strong>Heads up!</strong> BioStor is evolving, so things will look different and some things may be missing.</div>';
	
	echo '<div class="jumbotron" style="text-align:center">
        <h1>BioStor</h1>
        <p>Articles from the Biodiversity Heritage Library</p>
      </div>';


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
			echo ', on pages <b>' . str_replace('--', '-', $reference->pages) . '</b>';
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
						echo ' <a href="http://biodiversitylibrary.org/page/' . $identifier->id . '" target="_new"><span class="glyphicon glyphicon-new-window" aria-hidden="true"></span></i>http://biodiversitylibrary.org/page/' . $identifier->id . '</a>';
						break;
						
					case 'doi':
						echo ' <a href="http://dx.doi.org/' . $identifier->id . '" target="_new"><span class="glyphicon glyphicon-new-window" aria-hidden="true"></span></i>http://doi.dx.org/' . $identifier->id . '</a>';
						break;
					/*					
					case 'biostor':
						echo '<a href="http://biostor.org/reference/' . $identifier->id . '" target="_new"><i class="icon-external-link"></i>biostor.org/reference/' . $identifier->id . '</a>';
						break;
					
					case 'cinii':
						echo '<a href="http://ci.nii.ac.jp/naid/' . $identifier->id . '" target="_new"><i class="icon-external-link"></i>ci.nii.ac.jp/naid/' . $identifier->id . '</a>';
						break;										

					case 'doi':
						echo '<a href="http://dx.doi.org/' . $identifier->id . '" target="_new"><i class="icon-external-link"></i>doi.dx.org/' . $identifier->id . '</a>';
						break;
				
					case 'handle':
						echo '<a href="http://hdl.handle.net/' . $identifier->id . '" target="_new"><i class="icon-external-link"></i>hdl.handle.net/' . $identifier->id . '</a>';
						break;

					case 'jstor':
						echo '<a href="http://www.jstor.org/stable/' . $identifier->id . '" target="_new"><i class="icon-external-link"></i>www.jstor.org/stable/' . $identifier->id . '</a>';
						break;
					*/	
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
	
	echo        'Info on journal';
	
	echo '      </div>' . "\n";
	echo '   </div>' . "\n";
	echo '</div>' . "\n";
	
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
		if (isset($reference->year))
		{
			echo ' ' . $reference->year;
		}
				
		if (isset($reference->journal->volume))
		{
			echo ' ' . $reference->journal->volume;
		}
		if (isset($reference->journal->issue))
		{
			echo ' (' . $reference->journal->issue . ')';
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
				case 'doi':
					echo 'DOI: ' . $identifier->id . '<br />';
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
	
	$reference = null;
	
	// grab JSON from CouchDB
	$couch_id = $id;
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($couch_id));
	
	$reference = json_decode($resp);
	if (isset($reference->error))
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
	
	display_html_start($reference->title, reference_to_google_scholar($reference), $script);
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
			echo '<div style="position:relative;display:inline-block;padding:20px;">';			
			echo '<a href="reference/' . $id . '/page/' . $page_count . '" >';
			echo '<img style="box-shadow:2px 2px 2px #ccc;border:1px solid #ccc;" src="http://www.biodiversitylibrary.org/pagethumb/' .  $PageID . ',60,60" alt="' . $label . '" />';
			echo '<p style="text-align:center">' . $label . '</p>';
			echo '</a>';
			echo '</div>';					
			$page_count++;		
		} 

		echo '</div>';  
		echo '</div>'; // <div class="col-md-8">

		// tools, linked stuff, etc.
		echo '	<div class="col-md-4">' . "\n";
			
		// citation formatter
		echo '<div class="row">';
		echo '<select id="format" onchange="show_formatted_citation(this.options[this.selectedIndex].value);">
			<option label="Citation format" disabled="disabled" selected="selected"></option>
			<option label="APA" value="apa"></option>
			<option label="BibTeX" value="bibtex"></option>
			<option label="Wikipedia" value="wikipedia">
			<option label="ZooKeys" value="zookeys">
			<option label="Zootaxa" value="zootaxa"></option>
		</select>';	
	
		echo '<div id="citation" style=font-size:11px;"width:300px;height:100px;border:1px solid black;"><br/><br/><br/><br/><br/><br/></div>';
		echo '</div>';
	
		/* echo '<textarea id="citation" style="font-size:10px;" rows="6" readonly></textarea>'; */
			
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
			echo '<object id="mapsvg" type="image/svg+xml" width="360" height="180" data="map.php?coordinates=' . urlencode(json_encode($reference->geometry->coordinates)) . '"></object>';
	
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
	
		echo '</div>' . "\n"; // <div class="col-md-4">

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
			echo '"><a href="reference/' . $id . '/page/' . ($page - 1) . '"><span aria-hidden="true">&larr;</span> Previous</a></li>';		
			echo '<li><a href="reference/' . $id . '">Thumbnails</a></li>';
			echo '    <li class="next';
			if ($page == $num_pages)
			{
				echo ' disabled';
			}
			echo '"><a href="reference/' . $id . '/page/' . ($page + 1) . '">Next <span aria-hidden="true">&rarr;</span></a></li>';
			echo '  </ul>';
			echo '</nav>';

			$keys = array();
			foreach ($reference->bhl_pages as $k => $v)
			{
				$pages[] = $v;
			}
			$PageID = $pages[$page - 1];

			//$xml_url = 'http://biostor.org/bhl_page_xml.php?PageID=' . $PageID;
			$image_url = 'http://biostor.org/bhl_image.php?PageID=' . $PageID;
			
			// BHL
			$image_url = 'http://www.biodiversitylibrary.org/pagethumb/' .  $PageID . ',500,500" alt="Page ' . $PageID;			

			//$xml = get($xml_url);
			$xml = '';

			//echo $xml;

			//$xml = file_get_contents('43642463.xml');

			if (0)//$xml != '')
			{
					// Enable text selection	
					$xp = new XsltProcessor();
					$xsl = new DomDocument;
					$xsl->load(dirname(__FILE__) . '/djvu2html.xsl');
					$xp->importStylesheet($xsl);
	
					$doc = new DOMDocument;
					$doc->loadXML($xml);
	
					$xp->setParameter('', 'widthpx', '800');
					$xp->setParameter('', 'imageUrl', $image_url);
	
					$html = $xp->transformToXML($doc);
					echo $html;


			}
			else
			{
				echo '<div class="col-md-2"></div>';
				echo '<div class="col-md-8">';
				echo '<img width="700" style="box-shadow:2px 2px 2px #ccc;-webkit-user-drag: none;-webkit-user-select: none;" src="' . $image_url . '" />';
				echo '</div>';
				echo '<div class="col-md-2"></div>';
			}
	
	
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
	
	display_html_start('Search results');
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
	
		foreach ($obj->rows as $row)
		{
			$reference = $row->doc;
			
			display_record_summary($reference, $row->highlights);
	
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
	
	// Put furthe rinfo about results here...
	echo '	 <div class="col-md-4">' . "\n";
	echo '.';
	echo '   </div>';
	
	
	echo '  </div>';
	echo '</div>';
	
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
        <img alt="Brand" src="images/biostor-shadow32x32.png" height="20">
      </a>      
     <form class="navbar-form navbar-left" role="search" action="' . $config['web_root'] . '">
       <div class="form-group">
         <input type="text" class="form-control" placeholder="Search" name="q" value="' . $q . '">
       </div>
      </form>     
      <ul class="nav navbar-nav">
        <!-- <li><a href="?titles">Titles</a></li> -->
        <li><a href="titles">Browse titles</a></li>
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
<head>
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1">-->'
    . $meta . '
    
    <!-- base -->
    <base href="' . $config['web_root'] . '" />
    
    <!-- Boostrap -->
    <!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
	<!-- Latest compiled and minified JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
	<!-- almetric -->
	<script type="text/javascript" src="https://d1bxh8uas1mnw7.cloudfront.net/assets/embed.js"></script>'
	
	. $script . '
	<title>' . $title . '</title>
	</head>
<body style="padding-top:70px;padding-left:20px;padding-right:20px;padding-bottom:20px;">';

}

//----------------------------------------------------------------------------------------
function display_html_end()
{
	echo '<div class="panel panel-default">
  <div class="panel-body">
    <p style="vertical-align: top">BioStor is built by <a href="https://twitter.com/rdmpage">@rdmpage</a>, code on <a href="https://github.com/rdmpage/biostor">github</a>. 
    Page images from the <a href="http://biodiversitylibrary.org">Biodiversity Heritage Library</a>.';
    /*
    echo "<a href=\"https://twitter.com/biostor_org\" class=\"twitter-follow-button\" data-show-count=\"false\">Follow @biostor_org</a> <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>"; */
    echo '
    </p>
  </div>
</div>';

	echo '</body>
</html>';
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
		
		if (isset($_GET['bookmark']))
		{
			$bookmark = $_GET['bookmark'];
		}
		display_search($query, $bookmark);
		exit(0);
	}	
	
	// Show journal
	
}


main();

		
?>