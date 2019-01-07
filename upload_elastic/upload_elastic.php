<?php

// Upload Elastic documents from CouchDB to Elastic

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');
require_once (dirname(dirname(__FILE__)) . '/elastic.php');



$start   = 246887;
$end     = 247041;

$start   = 248609;
$end     = 248892;


for ($id = $start; $id <= $end; $id++)
{
//	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode('biostor/' . $id));
		

	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/_design/elastic/_view/biostor?key=" . urlencode('"biostor/' . $id . '"'));
	
	//echo $resp;
	
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
		
}



	
?>