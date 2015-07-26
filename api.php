<?php

require_once (dirname(__FILE__) . '/api_utils.php');
require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/reference.php');

require_once(dirname(__FILE__) . '/CiteProc.php');


//--------------------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}

//--------------------------------------------------------------------------------------------------
function display_formatted_citation($id, $style)
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
		$html = "Oops";
	}
	else
	{
		$citeproc_obj = reference_to_citeprocjs($reference);
	
		$json = json_encode($citeproc_obj);
		$citeproc_obj = json_decode($json);
		
		//echo $json;
		
		$cslfilename = dirname(__FILE__) . '/style/';
		switch ($style)
		{
			case 'apa':
			case 'bibtex':
			case 'zookeys':
				$cslfilename .= $style . '.csl';
				break;
				
			default:
				$cslfilename .= 'apa.csl';
				break;
		}
	
		$csl = file_get_contents($cslfilename);
		
		$citeproc = new citeproc($csl);
		$html = $citeproc->render($citeproc_obj, 'bibliography');
		
	}
	
	echo $html;

}

//--------------------------------------------------------------------------------------------------
// One record
function display_one ($id, $format= '', $callback = '')
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
		$obj->status = 404;
	}
	else
	{
		switch ($format)
		{
			case 'citeproc':
				$obj = reference_to_citeprocjs($reference);
				$obj['status'] = 200;
				break;
		
			default:
				$obj = $reference;
				$obj->status = 200;
				break;
		}
	}
	
	api_output($obj, $callback);
}

//--------------------------------------------------------------------------------------------------
// Full text search
function display_search ($q, $bookmark = '', $callback = '')
{
	global $config;
	global $couch;
	
	$rows_per_page = 10;
			
	if ($q == '')
	{
		$obj = new stdclass;
		$obj->rows = array();
		$obj->total_rows = 0;
		$obj->bookmark = '';	
		
		// Add status
		$obj->status = 404;
			
	}
	else
	{		
		
		$parameters = array(
				'q'					=> $q,
				'highlight_fields' 	=> '["default"]',
				'highlight_pre_tag' => '"<span style=\"color:white;background-color:green;\">"',
				'highlight_post_tag'=> '"</span>"',
				'highlight_number'	=> 5,
				'include_docs' 		=> 'true',
				'limit' 			=> $rows_per_page
			);
			
		if ($bookmark != '')
		{
			$parameters['bookmark'] = $bookmark;
		}
					
		$url = '/_design/citation/_search/all?' . http_build_query($parameters);
		
		$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
		$obj = json_decode($resp);
		
		// Add status
		$obj->status = 200;
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
	
	// Submit job
	if (!$handled)
	{
		if (isset($_GET['id']))
		{	
			$id = $_GET['id'];
			
			$format = '';
			
			if (isset($_GET['format']))
			{
				$format = $_GET['format'];
				
				if (isset($_GET['style']))
				{
					$style = $_GET['style'];
					display_formatted_citation($id, $style);
					$handled = true;
				}
			}
			
			if (!$handled)
			{
				display_one($id, $format, $callback);
				$handled = true;
			}
			
		}
	}
	
	if (!$handled)
	{
		if (isset($_GET['q']))
		{	
			$q = $_GET['q'];
			
			$bookmark = '';
			if (isset($_GET['bookmark']))
			{
				$bookmark = $_GET['bookmark'];
			}			
			
			display_search($q, $bookmark, $callback);
			$handled = true;
		}
			
	}
	
	
	if (!$handled)
	{
		default_display();
	}
	
		

}


main();

?>