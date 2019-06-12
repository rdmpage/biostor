<?php

// Delete reference from CouchDB

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');


$ids=array();

$ids=array();

$ids=array(
127420,
127421,
127422,
127423,
127424,
127425,
127426,
127427,
127428,
127429,
127430,
127431,
127432,
127433,
127434,
127435,
127436,
127437,
127438,
127439,
127440,
127441,
127442,
127443,
127444,
127445,
127446,
127447,
127448,
127449,
127450,
127451,
127452,
127453,
127454,
127455,
127456,
127457,
127458,
127459,
127460,
127461
);

$ids=array(
72378,
235151,
53335,
59235,
59240,
59242,
59246,
60700,
65952,
66401,
66402,
66415,
66416,
69661,
77749,
97647,
97651,
);

$replicate = array();


foreach ($ids as $id)
{				
	$biostor_id = 'biostor/' . $id;

	$couch->add_update_or_delete_document(null, $biostor_id, 'delete');
	
	$replicate[] = $biostor_id ;
}

// replicate to cloud
if (count($replicate) > 0)
{
	$doc = new stdclass;

	$doc->source = "biostor";
	$doc->target = "https://4c577ff8-0f3d-4292-9624-41c1693c433b-bluemix:6727bfccd5ac5213a9a05f87e5161c153131af6b2c0f3355fe1aa0fe2f97a35f@4c577ff8-0f3d-4292-9624-41c1693c433b-bluemix.cloudant.com/biostor";
	$doc->doc_ids = $replicate;
	
	$doc->source = "biostor";
	$doc->target = "http://admin:3h0kylo8ljfp@34.90.120.208:5984/biostor";
	$doc->doc_ids = $replicate;	

	print_r($doc);


	$command = "curl http://localhost:5984/_replicate -H 'Content-Type: application/json' -d '" . json_encode($doc) . "'";

	echo $command . "\n";
	system($command);

}

?>