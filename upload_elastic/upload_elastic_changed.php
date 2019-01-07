<?php

// Upload Elastic documents from CouchDB to Elastic

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/lib.php');
require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');
require_once (dirname(dirname(__FILE__)) . '/elastic.php');

$limit = 20;

$url = '_changes?limit=' . $limit . '&descending=true';

$url .= '&filter=' . urlencode('filters/works');

echo $url . "\n";

$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);


$obj = json_decode($resp);

foreach ($obj->results as $result)
{
	$id = $result->id;
	
	echo $id . "\n";
	
	// 
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/_design/elastic/_view/biostor?key=" . urlencode('"' . $id . '"'));
	
	$r = json_decode($resp);
	
	if (isset($r->rows))
	{
		if (count($r->rows == 0))
		{
			$elastic_doc = new stdclass;
			$elastic_doc->doc = $r->rows[0]->value;
			$elastic_doc->doc_as_upsert = true;

			//print_r($elastic_doc);

			$elastic->send('POST',  '_doc/' . urlencode($elastic_doc->doc->id). '/_update', json_encode($elastic_doc));					
		
		
		}
	}
	


}


	
?>