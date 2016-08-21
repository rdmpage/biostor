<?php

error_reporting(E_ALL);

// journal

require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');

require_once (dirname(__FILE__) . '/api_utils.php');

//--------------------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}

//--------------------------------------------------------------------------------------------------
// One journal (ISSN)
function display_issn ($issn, $callback = '')
{
	global $config;
	global $couch;
	
	$couch_id = 'issn/' . $issn;
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($couch_id));
	
	$response_obj = json_decode($resp);
	
	$obj = new stdclass;
	$obj->status = 404;
	if (isset($response_obj->error))
	{
		$obj->error = $response_obj->error;
	}
	else
	{
		$obj = json_decode($resp);
		$obj->status = 200;
	}

	api_output($obj, $callback);
}	

//--------------------------------------------------------------------------------------------------
// One journal (OCLC)
function display_oclc ($oclc, $callback = '')
{
	global $config;
	global $couch;
	
	
	$couch_id = 'oclc/' . $oclc;
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($couch_id));
	
	$response_obj = json_decode($resp);
	
	$obj = new stdclass;
	$obj->status = 404;
	if (isset($response_obj->error))
	{
		$obj->error = $response_obj->error;
	}
	else
	{
		$obj = json_decode($resp);
		$obj->status = 200;
	}

	api_output($obj, $callback);
}	



//--------------------------------------------------------------------------------------------------
// Journal articles clustered by decade, then year. Return counts for each year.
function display_decade_volumes ($namespace, $value, $callback = '')
{
	global $config;
	global $couch;
	
	switch ($namespace)
	{
		case 'oclc':
			$startkey = array((Integer)$value);
			$endkey = array((Integer)$value, new stdclass);
			break;
			
		case 'issn':
		default:
			$startkey = array($value);
			$endkey = array($value, new stdclass);
			break;			
	}

	$url = '_design/journal_articles/_view/decade_year_count?startkey=' . json_encode($startkey) . '&endkey=' . json_encode($endkey) . '&group_level=4';	

	
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}	
	
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
	
	$response_obj = json_decode($resp);
	
	$obj = new stdclass;
	$obj->status = 404;
	$obj->url = $url;
	
	if (isset($response_obj->error))
	{
		$obj->error = $response_obj->error;
	}
	else
	{
		if (count($response_obj->rows) == 0)
		{
			$obj->error = 'Not found';
		}
		else
		{	
			$obj->status = 200;
			
			// group into decades	
			$obj->decades = array();
			foreach ($response_obj->rows as $row)
			{
				if (!isset($obj->decades[$row->key[1]]))
				{
					$obj->decades[$row->key[1]] = array();
				}
		
				if (!isset($obj->decades[$row->key[1]][$row->key[2]]))
				{
					$obj->decades[$row->key[1]][$row->key[2]] = array();
				}
				
				$obj->decades[$row->key[1]][$row->key[2]] = $row->value;
			}	
		}
	}
	
	api_output($obj, $callback);
}

//----------------------------------------------------------------------------------------
function cmp($a, $b)
{
	$result = 0;
	
	$volume_a = 0;
	$volume_b = 0;
	
	if (isset($a->journal->volume))
	{
		$volume_a = $a->journal->volume;
	}

	if (isset($b->journal->volume))
	{
		$volume_b = $b->journal->volume;
	}
	
	if ($volume_a == $volume_b)
	{
		$result = 0;
	}
	else
	{
		$result = ($volume_a < $volume_b) ? -1 : 1;
	}
	
	if ($result == 0)
	{			
		// spage
		$spage_a = 0;
		$spage_b = 0;
		
		if (isset($a->journal->pages))
		{
			if (preg_match('/^(?<spage>.*)--(?<epage>.*)/', $a->journal->pages, $m))
			{
				$spage_a = $m['spage'];
			}
			else
			{
				$spage_a = $a->journal->pages;
			}
		}

		if (isset($b->journal->pages))
		{
			if (preg_match('/^(?<spage>.*)--(?<epage>.*)/', $b->journal->pages, $m))
			{
				$spage_b = $m['spage'];
			}
			else
			{
				$spage_b = $b->journal->pages;
			}
		}
		
		if ($spage_a == $spage_b)
		{
			$result = 0;
		}
		else
		{
			$result = ($spage_a < $spage_b) ? -1 : 1;
		}
		
	}
	
	return $result;
}


//--------------------------------------------------------------------------------------------------
// Journal articles for a given journal and year
function display_articles_year ($namespace, $value, $year, $callback = '')
{
	global $config;
	global $couch;
	
	
	
	
	
	switch ($namespace)
	{
		case 'oclc':
			$startkey = array((Integer)$value, (Integer)$year);
			$endkey = array((Integer)$value, (Integer)($year + 1));
			$url = '_design/journal_articles/_view/oclc_year_page?startkey=' . json_encode($startkey) . '&endkey=' . json_encode($endkey) . '&include_docs=true';	
			break;
			
		case 'issn':
		default:
			$startkey = array($value, (Integer)$year);
			$endkey = array($value, (Integer)($year + 1));
			$url = '_design/journal_articles/_view/issn_year_page?startkey=' . json_encode($startkey) . '&endkey=' . json_encode($endkey) . '&include_docs=true';				
			break;			
	}

	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}	
	
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
	
	$response_obj = json_decode($resp);
	
	$obj = new stdclass;
	$obj->status = 404;
	$obj->url = $url;
	
	if (isset($response_obj->error))
	{
		$obj->error = $response_obj->error;
	}
	else
	{
		if (count($response_obj->rows) == 0)
		{
			$obj->error = 'Not found';
		}
		else
		{	
			$obj->status = 200;
			
			$obj->articles = array();
			foreach ($response_obj->rows as $row)
			{
				$obj->articles[] = $row->doc;
			}
			
			// sort 
			usort($obj->articles, "cmp");
		}
	}
	
	api_output($obj, $callback);
}

//--------------------------------------------------------------------------------------------------

function display_starting_with($letter='A', $callback)
{
	global $config;
	global $couch;
	
	$startkey = array($letter);
	if ($letter == 'Z')
	{
		$endkey = array(new stdclass);
	}
	else
	{
		$endkey = array(chr(ord($letter) + 1));
	}

	$url = '_design/journal_articles/_view/name_sort?startkey=' . json_encode($startkey) . '&endkey=' . json_encode($endkey) . '&group_level=3';	

	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}	
	
	//echo $url;
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
	
	$response_obj = json_decode($resp);
	
	$obj = new stdclass;
	$obj->status = 404;
	$obj->url = $url;
	
	if (isset($response_obj->error))
	{
		$obj->error = $response_obj->error;
	}
	else
	{
		$obj->status = 200;
		$obj->titles = array();
		
		
		// group by identifier
		$sort_order			 = array();
		$key_to_identifier   = array();
		$identifier_to_key   = array();
		$identifier_to_names = array();
		
		
		foreach ($response_obj->rows as $row)
		{
			$key		 = $row->key[0];
			$identifier  = $row->key[1];
			$name	 	 = $row->key[2];
			
			if (!isset($identifier_to_key[$identifier]))
			{
				$identifier_to_key[$identifier] = $key;
				$key_to_identifier[$key] = $identifier;
				$sort_order[] = $identifier;
				$identifier_to_names[$identifier] = array();
			}
			$identifier_to_names[$identifier][] = $name;
		}	
		
		/*
		echo '<pre>';
		print_r($sort_order);
		print_r($identifier_to_names);
		echo '</pre>';
		*/
		
		foreach ($sort_order as $id)
		{
			$title = new stdclass;
			if (is_numeric($id))
			{
				$title->oclc = $id;
			}
			else
			{
				$title->issn = $id;
			}
			// for now just take first name
			$title->title = $identifier_to_names[$id][0];
			
			$obj->titles[$id] = $title;
		}
	}
	
	api_output($obj, $callback);
	


}


//--------------------------------------------------------------------------------------------------
// ROMEO status
function display_romeo($issn, $callback = '')
{
	global $config;
	
	$url = 'http://www.sherpa.ac.uk/romeo/api29.php?issn=' . $issn;

	$obj = new stdclass;
	$obj->status = 404;
	$obj->url = $url;
	$obj->issn = $issn;
	
	$xml = get($url);
	if ($xml != '')
	{
		$obj->status = 200;
	
		$dom= new DOMDocument;
		$dom->loadXML($xml);
		$xpath = new DOMXPath($dom);
		
		$xpath_query = '//romeocolour';
		$nodeCollection = $xpath->query ($xpath_query);
		foreach($nodeCollection as $node)
		{
			$obj->romeocolour =  $node->firstChild->nodeValue;
		}

		$xpath_query = '//publisher/preprints/prearchiving';
		$nodeCollection = $xpath->query ($xpath_query);
		foreach($nodeCollection as $node)
		{
			$obj->prearchiving =  $node->firstChild->nodeValue;
		}

		$xpath_query = '//publisher/postprints/postarchiving';
		$nodeCollection = $xpath->query ($xpath_query);
		foreach($nodeCollection as $node)
		{
			$obj->postarchiving =  $node->firstChild->nodeValue;
		}

		$xpath_query = '//publisher/pdfversion/pdfarchiving';
		$nodeCollection = $xpath->query ($xpath_query);
		foreach($nodeCollection as $node)
		{
			$obj->pdfarchiving =  $node->firstChild->nodeValue;
		}

		$xpath_query = '//publisher[1]/paidaccess/paidaccessnotes';
		$nodeCollection = $xpath->query ($xpath_query);
		foreach($nodeCollection as $node)
		{
			$obj->paidaccessnotes =  $node->firstChild->nodeValue;
		}
	}
	
	
	api_output($obj, $callback);

}




//--------------------------------------------------------------------------------------------------
function main()
{
	$callback = '';
	$handled = false;
	
	
	// If no query parameters 
	if (count($_GET) == 0)
	{
		default_display();
		exit(0);
	}
	
	if (isset($_GET['callback']))
	{	
		$callback = $_GET['callback'];
	}
	
	// Optional fields to include
	$fields = array('all');
	if (isset($_GET['fields']))
	{	
		$field_string = $_GET['fields'];
		$fields = explode(",", $field_string);
	}
	
	if (!$handled)
	{
		if (isset($_GET['letter']))
		{
			display_starting_with(strtoupper($_GET['letter']), $callback);
			$handled = true;
		}
	
	}	
	
	
	if (!$handled)
	{
		// OCLC
		if (isset($_GET['oclc']))
		{	
			$oclc = $_GET['oclc'];
			
			if (!$handled)
			{
				if (isset($_GET['volumes']))
				{
					display_decade_volumes('oclc', $oclc, $callback);
					$handled = true;
				}
			}
			
			if (!$handled)
			{
				if (isset($_GET['year']))
				{
					$year = $_GET['year'];
					display_articles_year('oclc', $oclc, $year, $callback);
					$handled = true;
				}	
			}
			
			
			if (!$handled)
			{
				display_oclc($oclc, $callback);
				$handled = true;			
			}
			
		}
		
	
		// ISSN	
		if (isset($_GET['issn']))
		{	
			$issn = $_GET['issn'];
			
			if (!$handled)
			{
				if (isset($_GET['volumes']))
				{
					display_decade_volumes('issn', $issn, $callback);
					$handled = true;
				}
			}
			
		
			if (!$handled)
			{
				if (isset($_GET['year']))
				{
					$year = $_GET['year'];
					display_articles_year('issn', $issn, $year, $callback);
					$handled = true;
				}	
			}
								
			
			if (!$handled)
			{
				if (isset($_GET['romeo']) )
				{
					display_romeo($issn, $callback);
					$handled = true;
				}			
			}
			
				
			
			if (!$handled)
			{
				display_issn($issn, $callback);
				$handled = true;			
			}
			
		}
		

	}

	




}



main();

?>
