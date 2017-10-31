<?php



// match BHL or IA URL to BHL PageID/BioStor id

require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');

require_once (dirname(__FILE__) . '/api_utils.php');

//--------------------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}

//--------------------------------------------------------------------------------------------------
function biostor_from_pageid($PageID)
{
	global $config;
	global $couch;
	
	$biostor = array();
	

	$url = '_design/bhl/_view/pageid_to_biostor?key=' . $PageID;	
	
	$url .= "&include_docs=true";
	
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}	
	
	//echo $url . "<br/>";
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
		
	$response_obj = json_decode($resp);
	
	if (isset($response_obj->error))
	{
	}
	else
	{
		if (count($response_obj->rows) == 0)
		{
		}
		else
		{	
			foreach ($response_obj->rows as $row)
			{
				$biostor[] = $row->value;

			}	
		}
	}
	
	return $biostor;
}


//----------------------------------------------------------------------------------------
function bhl_item_from_ia($ia)
{
	$ItemID = 0;
	
	$parameters = array(
		'op' => 'GetItemByIdentifier',
		'type' => 'ia',
		'value' => $ia,
		'format' => 'json',
		'apikey' => '0d4f0303-712e-49e0-92c5-2113a5959159'
	);

	$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?' . http_build_query($parameters);
	
	//echo $url . "\n";
	
	$json = get($url);
	
	//echo $json;

	if ($json != '')
	{
		$obj = json_decode($json);
		
		//print_r($obj);
			
		if ($obj->Status == 'ok')
		{
			$ItemID = $obj->Result->ItemID;
		}
	}	
	
	//echo "ItemID=$ItemID\n";

	return $ItemID;
}

//----------------------------------------------------------------------------------------
function bhl_item_from_pageid($PageID)
{
	$ItemID = 0;
	
	$parameters = array(
		'op' => 'GetPageMetadata',
		'pageid' => $PageID,
		'ocr' => 'false',
		'names' => 'false',
		'format' => 'json',
		'apikey' => '0d4f0303-712e-49e0-92c5-2113a5959159'
	);

	$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?' . http_build_query($parameters);
	
	//echo $url . "\n";
	
	$json = get($url);
	
	//echo $json;

	if ($json != '')
	{
		$obj = json_decode($json);
		
		//print_r($obj);
			
		if ($obj->Status == 'ok')
		{
			$ItemID = $obj->Result->ItemID;
		}
	}	
	
	//echo "ItemID=$ItemID\n";

	return $ItemID;
}

//----------------------------------------------------------------------------------------
// Get BHL PageID from a IA URL
// e.g. $url = 'http://www.archive.org/stream/deutscheentomolo121899gese#page/218/mode/2up'
// Note that if IA URL has a mode such as 2up, the target may be either the left or right page,
// so we give user option of saing what the page number is. For the above URL, if the link
// is targeting page 219 then we can add set $target_page=219
function bhl_page_from_ia_url($url, $target_page = '')
{
	$PageID = 0;
	$ItemID = 0;
	$ia = '';

	if (preg_match('/http:\/\/(www\.)?archive.org\/stream\/(?<ia>[A-Za-z0-9]+)#page\/(?<page>\d+)(\/mode\/\d+up)?/', $url, $m))
	{
		$ia = $m['ia'];
				
		if ($target_page == '')
		{
			$target_page = $m['page'];
		}
	}
	
	if ($ia != '')
	{		
		$ItemID = bhl_item_from_ia($ia);
			
		if ($ItemID != 0)
		{
			$list = bhl_pages_with_number($ItemID, $target_page);
			
			//print_r($list);

			if (count($list) == 1)
			{
				$PageID = $list[0];
			}
		}
	}
	
	return $PageID;
}


//----------------------------------------------------------------------------------------
// Get PageID of page pointed to by a BHL URL 
// e.g. http://www.biodiversitylibrary.org/item/96891#page/697/mode/1up
function bhl_page_from_bhl_url($url)
{
	$PageiD = 0;
	
	$ItemID = 0;
	$PageID = 0;
	
	// Link to page URL shown in web browser when visiting BHL
	if (preg_match('/http[s]?:\/\/(www\.)?biodiversitylibrary.org\/item\/(?<item>\d+)#page\/(?<page>\d+)(\/mode\/\d+up)?/', $url, $m))
	{
		//print_r($m);
		$ItemID = $m['item'];
		$page_number = $m['page'];
	}

	// Link to BHL item with page offset
	if (preg_match('/http[s]?:\/\/(www\.)?biodiversitylibrary.org\/item\/(?<item>\d+)#(?<page>\d+)/', $url, $m))
	{
		//print_r($m);
		$ItemID = $m['item'];
		$page_number = $m['page'];
	}

	// Link to BHL page with page offset
	// http://www.biodiversitylibrary.org/page/15733891%23page/417/mode/1up
	if (preg_match('/http[s]?:\/\/(www\.)?biodiversitylibrary.org\/page\/(?<pageid>\d+)#page\/(?<page>\d+)(\/mode\/\d+up)?/', $url, $m))
	{
		//print_r($m);
		$ItemID = bhl_item_from_pageid($m['pageid']);
		
		if ($ItemID != 0)
		{
			$page_number = $m['page'] + 1;
		}
	}
	
	
	
	// Link to BHL page using PageID, just extract the PageID
	if (preg_match('/http[s]?:\/\/(www\.)?biodiversitylibrary.org\/page\/(?<page>\d+)$/', $url, $m))
	{
		$PageID = $m['page'];
	}
	
	if ($ItemID != 0)
	{
		$PageID = bhl_ith_page_item($ItemID, $page_number);
	}
	
	return $PageID;
}
	

//----------------------------------------------------------------------------------------
// Get list of PageIDs of scanned pages in item that are numbered "$page", may be more than one
function bhl_pages_with_number($ItemID, $target_page)
{
	$pages = array();
	
	$parameters = array(
		'op' => 'GetItemMetadata',
		'itemid' => $ItemID,
		'pages' => 'true',
		'ocr' => 'false',
		'parts' => 'true',
		'format' => 'json',
		'apikey' => '0d4f0303-712e-49e0-92c5-2113a5959159'
	);

	$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?' . http_build_query($parameters);

	$json = get($url);

	if ($json != '')
	{
		$obj = json_decode($json);
		
		foreach ($obj->Result->Pages as $page)
		{
			foreach ($page->PageNumbers as $p)
			{
				if ($p->Number == $target_page)
				{
					$pages[] = $page->PageID;
				}
			}
		}
	}

	return $pages;	
}

//----------------------------------------------------------------------------------------
// Find ith page in BHL item. BHL URLs number pages by orde rin list of page scans)
function bhl_ith_page_item($ItemID, $page_number)
{
	$PageID = 0;
	
	$parameters = array(
		'op' => 'GetItemMetadata',
		'itemid' => $ItemID,
		'pages' => 'true',
		'ocr' => 'false',
		'parts' => 'true',
		'format' => 'json',
		'apikey' => '0d4f0303-712e-49e0-92c5-2113a5959159'
	);

	$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?' . http_build_query($parameters);

	$json = get($url);

	if ($json != '')
	{
		$obj = json_decode($json);

		//print_r($obj);

		$page = $obj->Result->Pages[$page_number - 1];

		//print_r($page);
		 
		$PageID = $page->PageID;

	}
	
	return $PageID;
}


//--------------------------------------------------------------------------------------------------
function display_url($url, $page = '', $callback = '')
{
	$obj = new stdclass;
	$obj->status = 404;
	
	$PageID = 0;
	
	if (preg_match('/archive.org/', $url))
	{
		$PageID = bhl_page_from_ia_url($url, $page);
	}

	if (preg_match('/biodiversitylibrary.org/', $url))
	{
		$PageID = bhl_page_from_bhl_url($url);
	}

	if ($PageID != 0)
	{
		$result = new stdclass;
		$result->PageID = $PageID;	
		
		$result->biostor = biostor_from_pageid($PageID);

			
		$obj->results[] = $result;
		
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
	
	
	if (!$handled)
	{
		if (isset($_GET['url']))
		{
			$url = urldecode($_GET['url']);
			$page = '';
			
			if (isset($_GET['page']))
			{
				$page = $_GET['page'];
			}
		
			display_url($url, $page, $callback);
			$handled = true;
		}
	}	
	

	

}



main();

?>
