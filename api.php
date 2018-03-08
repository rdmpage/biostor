<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/api_utils.php');
require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/reference_code.php');

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
		if ($style == 'ris')
		{
			$html = '<pre>' . reference_to_ris($reference) . '</pre>';
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
				case 'wikipedia':
				case 'zookeys':
				case 'zootaxa':
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
	}
	
	echo $html;

}

//--------------------------------------------------------------------------------------------------
// One record
function display_one ($id, $format= '', $callback = '')
{
	global $config;
	global $couch;
	
	global $memcache;
	global $cacheAvailable;

	$obj = null;
	
	// grab JSON from CouchDB
	$couch_id = $id;
	
	if ($cacheAvailable == true)
	{
		$obj = $memcache->get($couch_id);
	}
	
	if ($obj)
	{
		$obj->status = 200;
	}
	else
	{
		// fetch from CouchDB
		$obj = new stdclass;
	
		$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($couch_id));
	
		$reference = json_decode($resp);
		if (isset($reference->error))
		{
			$obj->status = 404;
		}
		else
		{
			$obj = $reference;
			$obj->status = 200;
			
			if ($cacheAvailable == true)
			{
				$memcache->set($couch_id, $reference);
			}
		}
	}
	
	// Format object (if needed)
	if ($obj->status == 200)
	{
		switch ($format)
		{
			case 'citeproc':
				$obj = reference_to_citeprocjs($obj);
				$obj['status'] = 200;
				break;

			default:
				break;
		}
	}
		
	api_output($obj, $callback);
}

//--------------------------------------------------------------------------------------------------
// One BHL page
function display_one_page ($PageID, $callback = '')
{
	global $config;
	global $couch;
	
	// Do we have this page in the database
	$couch_id = 'page/' . $PageID;	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($couch_id));
	
	$page = json_decode($resp);
	if (isset($page->error))
	{
		$obj->status = 404;
	}
	else
	{
		$obj = $page;
		$obj->status = 200;
	}
	
	api_output($obj, $callback);
}

//--------------------------------------------------------------------------------------------------
// One BHL page as HTML
function display_one_page_html ($PageID, $format =  'html', $callback = '')
{
	global $config;
	global $couch;
	
	$obj = new stdclass;
	$obj->html = '';
	$obj->page = 'page/' . $PageID;	
	$obj->status = 404;
	
	// Do we have this page in the database, with XML?
	$xml = '';
	$couch_id = 'page/' . $PageID;	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($couch_id));

	$page = json_decode($resp);
	if (isset($page->error))
	{
		// we don't have this page
	}
	else
	{
		if (isset($page->xml))
		{
			$xml = $page->xml;
		}
	}
	
	if ($xml != '')
	{
		// Source of image
		if ($config['image_source'] == 'bhl')
		{
			$image_url = 'http://www.biodiversitylibrary.org/pagethumb/' .  $PageID . ',500,500"';	
			
			if ($config['use_cloudimage'])
			{
				$image_url = 'http://exeg5le.cloudimg.io/s/width/700/' . $image_url;
			}
			
			if ($config['use_weserv'])
			{
				$image_url = 'https://images.weserv.nl/?url=' . str_replace('http://', '', $image_url);
			}		
			
		}
		else
		{
			$image_url = 'http://direct.biostor.org/bhl_image.php?PageID=' . $PageID;
		}	
	
		// Enable text selection	
		$xp = new XsltProcessor();
		$xsl = new DomDocument;
		$xsl->load(dirname(__FILE__) . '/djvu2html.xsl');
		$xp->importStylesheet($xsl);

		$doc = new DOMDocument;
		$doc->loadXML($xml);

		$xp->setParameter('', 'widthpx', '700');
		$xp->setParameter('', 'imageUrl', $image_url);

		$obj->html = $xp->transformToXML($doc);
		
		$obj->status =  200;
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
				'counts' 			=> '["publication","year","author","type"]',
				'limit' 			=> $rows_per_page
			);
			
		if ($bookmark != '')
		{
			$parameters['bookmark'] = $bookmark;
		}
					
		$url = '/_design/citation/_search/all?' . http_build_query($parameters);
		
		$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
		$obj = json_decode($resp);
		
		// delete large fields from results, such as OCR text and list of names extracted
		if (isset($obj->rows))
		{
			$n = count($obj->rows);
			for ($i = 0; $i < $n; $i++)
			{
				if (isset($obj->rows[$i]->doc->text))
				{
					unset($obj->rows[$i]->doc->text);
				}
				if (isset($obj->rows[$i]->doc->names))
				{
					unset($obj->rows[$i]->doc->names);
				}
				
			}
		}
		
		// Add status
		$obj->status = 200;
	}
	
	api_output($obj, $callback);
}

//--------------------------------------------------------------------------------------------------
function display_images($callback = '')
{
	global $config;
	global $couch;
	
	global $memcache;
	global $cacheAvailable;

	$obj = null;
	
	if ($cacheAvailable == true)
	{
		$obj = $memcache->get('pintrest');
	}
	
	if ($obj)
	{
	}
	else
	{
		// fetch
		$obj = new stdclass;
	
		$url = '_design/pintrest/_view/date_pin';	
	
		
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
			
				$obj->images = array();

				foreach ($response_obj->rows as $row)
				{
					$image = new stdclass;
					$image->src = $row->value->thumbnail;
					$image->biostor = $row->value->biostor;
				
					$obj->images[] = $image;
				}
				/* Notice: MemcachePool::set(): Server 192.168.0.3 (tcp 11211, udp 0) failed with: SERVER_ERROR object too large for cache
 (3) in /data/api.php on line 336 */
 				// Comment out memcache as we get above notice in API output, breaking JSON
				//$memcache->set('pintrest', $obj);
			}
		}
	}
	
	api_output($obj, $callback);
}


//--------------------------------------------------------------------------------------------------
// Citations extracted from OCR text
function display_cites ($id, $callback = '')
{
	global $config;
	global $couch;
	
	$url = '_design/jats/_view/cites?key=' . urlencode('"' . $id . '"');	

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
			
			// citations
			$obj->cites = array();
			foreach ($response_obj->rows as $row)
			{
				$obj->cites[(Integer)$row->value[0]] = $row->value[1];
			}	
			
			// sort
			ksort($obj->cites);
			
		}
	}
	
	api_output($obj, $callback);	
}


//--------------------------------------------------------------------------------------------------
function main()
{
	$callback = '';
	$handled = false;
	
	//print_r($_GET);
	
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
			
			
			if (isset($_GET['cites']))
			{
				display_cites($id, $callback);
				$handled = true;
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
		if (isset($_GET['images']))
		{
			display_images();
			$handled = true;
		}
	}
	
	if (!$handled)
	{
		if (isset($_GET['page']))
		{
			$PageID = $_GET['page'];
			
			$format = '';
			
			if (isset($_GET['format']))
			{
				$format = $_GET['format'];

				if ($format == 'html')
				{
					display_one_page_html($PageID, $format, $callback);
					$handled = true;
				}
			}
			
			if (!$handled)
			{
				display_one_page($PageID, $callback);
				$handled = true;
			}
		}
	}
	
	
	if (!$handled)
	{
		default_display();
	}
	
		

}


main();

?>