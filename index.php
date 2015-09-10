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
	
	echo '<div class="alert alert-warning" role="alert"><strong>Heads up!</strong> BioStor is evolving, so things will look different and some things may be missing.</div>';
	
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
				case 'doi':
					echo 'DOI: ';
					echo ' <a href="http://dx.doi.org/' . $identifier->id . '" target="_new"></i>' . $identifier->id . ' <span class="glyphicon glyphicon-new-window" aria-hidden="true"></span></a>';
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
	
	$reference = null;
	
	// API call
	$ok = false;
	
	$url = $config['web_server'] . $config['web_root'] . 'api.php?id=' . urlencode($id);
	$json = get($url);
			
	if ($json != '')
	{
		$reference = json_decode($json);
		$ok = $reference->status = 200;
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
				$image_url = 'http://www.biodiversitylibrary.org/pagethumb/' .  $PageID . ',60,60';	
			}
			else
			{
				$image_url = 'http://direct.biostor.org/bhl_image.php?PageID=' . $PageID . '&thumbnail';
			}
		
			echo '<div style="position:relative;display:inline-block;padding:20px;">';			
			echo '<a href="reference/' . str_replace('biostor/', '', $id) . '/page/' . $page_count . '" >';
			echo '<img style="box-shadow:2px 2px 2px #ccc;border:1px solid #ccc;" src="' . $image_url . '" alt="' . $label . '" />';
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
				$image_url = 'http://www.biodiversitylibrary.org/pagethumb/' .  $PageID . ',500,500"';	
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
			}
			
			if ($html == '')
			{
				$html = '<img width="700" style="box-shadow:2px 2px 2px #ccc;-webkit-user-drag: none;-webkit-user-select: none;" src="' . $image_url . '" />';				
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
	
	// Author-specific stuff
	$author_search = false;
	$lastname = $firstname = '';
	if (preg_match('/^author:/', $q))
	{
		$author_search = true;
		
		// parse author name
		$authorstring = preg_replace('/^author:"/u', '', $q);
		$authorstring = preg_replace('/"$/u', '', $authorstring);
		$parts = parse_name($authorstring);
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
	
	//echo $firstname . '|' . $lastname . '<br />';
	
	echo '<div id="query_suggest"></div>' . "\n";
	
	echo '   </div>';
	
	
	echo '  </div>';
	echo '</div>';
	
	if ($author_search)
	{
		echo '<script>';
		echo 'show_similar_authors("' . addcslashes($lastname, '"') . '","' . addcslashes($firstname, '"') . '");';
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
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1">-->
    
    <!-- Google -->
    <meta name="google-site-verification" content="G0IJlAyehsKTOUGWSc-1V2RMtYQLnqXs440NUSxbYgA" />
    <!-- Twitter -->
	<meta name="twitter:site" content="@biostor_org" />
	<!-- Pintrest -->
	<meta name="p:domain_verify" content="5f60c8da3099dba7fd452ca1b9668c0a"/>'

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
 	<script src="http://www.google.com/jsapi"></script>
 	<script src="js/map.js"></script>
	'	
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

	echo '</body>
	<!-- hypothes.is -->
	<script defer async src="//hypothes.is/embed.js"></script>
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
	echo '<a href="bhl-couchdb/?q=Serinus mozambicus">BHL CouchDB full-text indexing</a></li>';
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