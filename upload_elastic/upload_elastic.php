<?php

// Upload Elastic documents from CouchDB to Elastic

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/lib.php');
require_once (dirname(__FILE__) . '/elastic.php');



$rows_per_page = 100;
$skip = 1700;

$count = 0;

$done = false;
while (!$done)
{
	$url = 'http://direct.biostor.org:5984/biostor/_design/elastic/_view/biostor';
	
	//$url .= '?key=' . urlencode('"biostor/136987"');
	
	
	$url .= '?limit=' . $rows_per_page . '&skip=' . $skip;
		
	// debug
	//$url .= '&stale=ok';
	
	echo $url . "\n";

	$resp = get($url);

	if ($resp)
	{
		
		$response_obj = json_decode($resp);
		if (!isset($response_obj->error))
		{
			$n = count($response_obj->rows);
		
			foreach ($response_obj->rows as $row)
			{
				echo $row->id . "\n";
				$doc = $row->value;

				$elastic_doc = new stdclass;
				$elastic_doc->doc = $doc;
				$elastic_doc->doc_as_upsert = true;

				//print_r($elastic_doc);

				$elastic->send('POST',  '_doc/' . urlencode($doc->id). '/_update', json_encode($elastic_doc));					
				
			}	
		}
	}

	$count += $n;
		

	$skip += $rows_per_page;
	
	//$done = ($n < $rows_per_page);	
	$done = ($count > 10000);	
	
	
	
		
}



	
?>