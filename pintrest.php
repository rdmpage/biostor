<?php

// Grab pintrest RSS feed and add images to CouchDB

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');

$url = 'http://pinterest.com/rdmpage/feed.rss';

$xml = get($url);

$dom= new DOMDocument;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);

$itemCollection = $xpath->query ('//item');
foreach ($itemCollection as $item)
{
	$obj = new stdclass;
	
	$nodeCollection = $xpath->query ('title', $item);
	foreach ($nodeCollection as $node)
	{
		$obj->title = $node->firstChild->nodeValue;
	}
			
	$nodeCollection = $xpath->query ('link', $item);
	foreach ($nodeCollection as $node)
	{
		$obj->link = $node->firstChild->nodeValue;
		
		$html = get($obj->link);
		
		$html = str_replace("\n", "", $html);
		$html = str_replace("\r", "", $html);	
		
		if (preg_match('/<meta property="og:see_also"\s+name="og:see_also"\s+content="http:\/\/biostor.org\/reference\/(?<id>\d+)"/Uu', $html, $m))
		{
			$obj->biostor = $m['id'];
		}		
	}
	
	$nodeCollection = $xpath->query ('description', $item);
	foreach ($nodeCollection as $node)
	{
		$obj->description = $node->firstChild->nodeValue;
		
		if (preg_match('/src="(?<image>https:\/\/(.*).jpg)"/Uu', $obj->description, $m))
		{
			$obj->image = $m['image'];
			
			// grab
			$image = get($obj->image);
			if ($image != '')
			{
				$mime_type = 'image/jpg';
				$base64 = chunk_split(base64_encode($image));
				$obj->thumbnail = 'data:' . $mime_type . ';base64,' . $base64;		
			}
		}
	}
	
	$nodeCollection = $xpath->query ('pubDate', $item);
	foreach ($nodeCollection as $node)
	{
		$obj->pubDate = $node->firstChild->nodeValue;
		// Date as array
		$obj->date = explode("-", date("Y-n-j", strtotime($obj->pubDate)));
	}
	
	
	
	
	$obj->_id = $obj->link;
	$obj->type = "pintrest";
	
	print_r($obj);
	
	// only grab pins from BioStor
	if (isset($obj->biostor))
	{
		$couch->add_update_or_delete_document($obj,  $obj->_id);
	}
	
	
}



?>