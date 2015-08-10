<?php

require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/couchsimple.php');
// BHL import

//----------------------------------------------------------------------------------------
function fetch_title($TitleID)
{	
	global $couch;

	$title = null;
	
	$parameters = array(
		'op' => 'GetTitleMetadata',
		'titleid' => $TitleID,
		'items' => 'true',
		'format' => 'json',
		'apikey' => '0d4f0303-712e-49e0-92c5-2113a5959159'
	);
	
	$url = 'http://www.biodiversitylibrary.org/api2/httpquery.ashx?' . http_build_query($parameters);
	
	//echo $url;
	
	$json = get($url);
	
	if ($json != '')
	{
		$obj = json_decode($json);
		if (isset($obj->Result))
		{
			$title = $obj->Result;
			$title->_id = 'title/' . $TitleID;
			
			$couch->add_update_or_delete_document($title,  $title->_id);
		}
	}
	
	return $title;
}


//----------------------------------------------------------------------------------------
function fetch_item($ItemID)
{	
	global $couch;
	
	$obj = null;
	
	$parameters = array(
		'op' => 'GetItemMetadata',
		'itemid' => $ItemID,
		'pages' => 'true',
		'ocr' => 'false',
		'parts' => 'true',
		'format' => 'json',
		'apikey' => '0d4f0303-712e-49e0-92c5-2113a5959159'
	);
	
	$url = 'http://www.biodiversitylibrary.org/api2/httpquery.ashx?' . http_build_query($parameters);
		
	$json = get($url);
	
	if ($json != '')
	{
		$obj = json_decode($json);
		
		if (isset($obj->Result))
		{
			$item = $obj->Result;
			$item->_id = 'item/' . $ItemID;
			
			$couch->add_update_or_delete_document($item,  $item->_id);
		}
	}
	
	return $item;
}

//----------------------------------------------------------------------------------------
function fetch_page($PageID)
{	
	global $couch;
	
	$page = null;
	
	$parameters = array(
		'op' => 'GetPageMetadata',
		'pageid' => $PageID,
		'ocr' => 'true',
		'names' => 'true',
		'format' => 'json',
		'apikey' => '0d4f0303-712e-49e0-92c5-2113a5959159'
	);
	
	$url = 'http://www.biodiversitylibrary.org/api2/httpquery.ashx?' . http_build_query($parameters);
		
	$json = get($url);
	
	if ($json != '')
	{
		$obj = json_decode($json);
		if (isset($obj->Result))
		{
			$page = $obj->Result;
			$page->_id = 'page/' . $PageID;
			
			$couch->add_update_or_delete_document($page,  $page->_id);
		}
	}
	
	return $page;
}


//$title = fetch_title(46639);
//$item = fetch_item(125530);

//foreach ($item->Pages as $Page)
//{
	fetch_page($Page->PageID);
//}
//$page = fetch_page(40551130);
//$page = fetch_page(40551129);

//$item = fetch_item(100931);
//$page = fetch_page(32428883);


// fetch an entire title
if (0)
{
	$TitleID = 46639;

	$title = fetch_title($TitleID);

	foreach ($title->Items as $Item)
	{
		$item = fetch_item($Item->ItemID);
		foreach ($item->Pages as $Page)
		{
			fetch_page($Page->PageID);
		}	
	}	
}

// fetch an item
if (1)
{
	$itemID = 125340;
	$item = fetch_item($ItemID);
	foreach ($item->Pages as $Page)
	{
		fetch_page($Page->PageID);
	}	
}




?>