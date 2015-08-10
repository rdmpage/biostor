<?php

require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/couchsimple.php');


$ItemID = 125289;
$ItemID = 125664;
$ItemID = 125657;
$ItemID = 125673;
$ItemID = 125677;
$ItemID = 126838;
$ItemID = 125530;
$ItemID = 126873;

$couch_id = 'item/' . $ItemID;	
$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($couch_id));

$item = json_decode($resp);

if (isset($item->error))
{
	// badness
}
else
{


	$pages = array();

	foreach ($item->Pages as $Page)
	{
		$pages[] = $Page->PageID;
	}

	print_r($pages);

	$filename = 'xml/' . $item->SourceIdentifier . '_djvu.xml';
	
	// Ensure cache subfolder exists for this item
	if (!file_exists($filename))
	{
		$url = 'http://www.archive.org/download/' . $item->SourceIdentifier  . '/' . $item->SourceIdentifier . '_djvu.xml';
		
		$command = "curl";
		
		if ($config['proxy_name'] != '')
		{
			$command .= " --proxy " . $config['proxy_name'] . ":" . $config['proxy_port'];
		}
		$command .= " --location " . $url . " > " . $filename;
		echo $command . "\n";
		system ($command);
	}
	

	// scan XML file
	$xml = '';
	$in_page = false;
	$page_counter = 0;
	$pages_found = 0;
	$num_pages = count($pages);

	$file_handle = fopen($filename, "r");
	while (!feof($file_handle) && ($pages_found < $num_pages)) 
	{
		$line = fgets($file_handle);
	
		if (preg_match('/<OBJECT/', $line))
		{
			$in_page = true;
		}
		
		if ($in_page)
		{
			$xml .= $line;
		}

		// We've got one page, if it matches one we need, output it
		if (preg_match('/<\/OBJECT>/', $line))
		{
	
			//	$xml_page_filename = 'xml/' . $pages[$page_counter] . '.xml';
			// file_put_contents($xml_page_filename, $xml);
		
		
			$couch_id = 'page/' . $pages[$page_counter];	
			$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($couch_id));
	
			$page = json_decode($resp);
			if (isset($page->error))
			{
				// badness
			}
			else
			{
				$page->xml = $xml;
			
				$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . urlencode($page->_id), json_encode($page));
				var_dump($resp);
			}
		
			$page_counter++;
			$in_page = false;
			$xml = '';
		
		}
	}
}

?>