<?php

// Parse list of ids, check they exist, if not replicate

require_once(dirname(dirname(__FILE__)) . '/couchsimple.php');


$filename = 'biostor.txt';
$filename = 'missing.txt';
$filename = 'pinterest.txt';

$skip = 0;
$count = 1;

$chunksize = 50;

$missing = array();

$done = false;

$file_handle = fopen($filename, "r");
while (!feof($file_handle) && !$done) 
{

	$id = trim(fgets($file_handle));
	
	//echo "$id\n";
	
	if ($couch->exists($id))
	{
		//echo "OK\n";
	}
	else
	{
		echo "missing $id\n";
		$missing[] = $id;
	}
	
	if (count($missing) > $chunksize)
	{
		$doc = new stdclass;
		
		
		$doc->source = "biostor";
		$doc->target = "http://admin:3h0kylo8ljfp@35.204.147.240:5984/biostor";
		$doc->doc_ids = $missing;
		
		print_r($doc);
		
		$command = "curl http://direct.biostor.org:5984/_replicate -H 'Content-Type: application/json' -d '" . json_encode($doc) . "'";
		
		echo $command . "\n";
		
		system($command);
		
		echo "Sleep...\n";
		sleep(2);		
		
		$missing=[];
		
	}
	
	// Give server a break every 100 items
	if (($count++ % 100) == 0)
	{
		$rand = rand(100000, 300000);
		echo "[$count]...sleeping for " . round(($rand / 1000000),2) . ' seconds' . "\n";
		usleep($rand);
	}	

	
	
}

// Left over?
if (count($missing) > 0)
{
		$doc = new stdclass;
		
		
		$doc->source = "biostor";
		$doc->target = "http://admin:3h0kylo8ljfp@35.204.147.240:5984/biostor";
		$doc->doc_ids = $missing;
		
		print_r($doc);
		
		$command = "curl http://direct.biostor.org:5984/_replicate -H 'Content-Type: application/json' -d '" . json_encode($doc) . "'";
		
		echo $command . "\n";
		
		system($command);


}

	
?>	

