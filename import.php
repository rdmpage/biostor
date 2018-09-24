<?php

// Import BioStor references and add to CouchDB

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');
require_once (dirname(__FILE__) . '/reference_code.php');

$biostor = 'http://biostor.org';
$biostor = 'http://130.209.46.234'; // need IP when working on biostor-classic machine


// 2015-07-30 latest BioStor 146770

$start = 1;
$end = 146770;

$end = 1;

$replicate = array();

for ($id = $start; $id <= $end; $id++)
{
	$json = get($biostor . '/reference/' . $id . '.bibjson');
	
	if ($json != '')
	{
		$reference = json_decode($json);
		
		if ($reference)
		{
			// ignore stuff tnot linked to BHL
			$go = true;
			
			if ($reference->link)
			{
				foreach ($reference->link as $link)
				{
					if ($link->url == 'http://www.biodiversitylibrary.org/page/0')
					{
						$go = false;
					}
				}
			}
			
			if ($go)
			{			
				$reference->_id = 'biostor/' . $id;
				
				$reference->citation = reference_to_citation_string($reference);	
				
				// thumbnail
				$url = $biostor . '/reference/' . $id . '.json';
				$json = get($url);
				
				if ($json != '')
				{				
					$obj = json_decode($json);
					
					// thumbnail
					$reference->thumbnail = $obj->thumbnails[0];
					
					// date
					if (isset($obj->date))
					{
						$reference->date = array();
						if (preg_match('/(?<year>[0-9]{4})/', $obj->date, $m))
						{
							$reference->date[] = (Integer)$m['year'];
						}
						if (preg_match('/(?<year>[0-9]{4})-(?<month>\d+)/', $obj->date, $m))
						{
							$month = preg_replace('/^0+/', '', $m['month']);
							if ($month != '')
							{
								$reference->date[] = (Integer)$month;
							}
						}
						if (preg_match('/(?<year>[0-9]{4})\-(?<month>\d+)\-(?<day>\d+)$/', $obj->date, $m))
						{
							$day = preg_replace('/^0+/', '', $m['day']);
							if ($day != '')
							{
								$reference->date[] = (Integer)$day;
							}
						}
							
					}		
					
					
					// names
					if (isset($obj->names))
					{
						$reference->names = $obj->names;
					}		
					
					// classification
					if (isset($obj->expanded))
					{
						$reference->classification = $obj->expanded;
					}
					
				}
				
				print_r($reference);
				
				$couch->add_update_or_delete_document($reference,  $reference->_id);
				
				// replicate to cloud
				if (count($replicate) >= 10)
				{
					$doc = new stdclass;

					$doc->source = "biostor";
					$doc->target = "https://4c577ff8-0f3d-4292-9624-41c1693c433b-bluemix:6727bfccd5ac5213a9a05f87e5161c153131af6b2c0f3355fe1aa0fe2f97a35f@4c577ff8-0f3d-4292-9624-41c1693c433b-bluemix.cloudant.com/biostor";
					$doc->doc_ids = $replicate;

					print_r($doc);


					$command = "curl http://localhost:5984/_replicate -H 'Content-Type: application/json' -d '" . json_encode($doc) . "'";

					echo $command . "\n";
					system($command);
					
					$replicate = array();

				}				
			}
		}		
	}
}

// replicate to cloud
if (count($replicate) > 0)
{
	$doc = new stdclass;

	$doc->source = "biostor";
	$doc->target = "https://4c577ff8-0f3d-4292-9624-41c1693c433b-bluemix:6727bfccd5ac5213a9a05f87e5161c153131af6b2c0f3355fe1aa0fe2f97a35f@4c577ff8-0f3d-4292-9624-41c1693c433b-bluemix.cloudant.com/biostor";
	$doc->doc_ids = $replicate;

	print_r($doc);


	$command = "curl http://localhost:5984/_replicate -H 'Content-Type: application/json' -d '" . json_encode($doc) . "'";

	echo $command . "\n";
	system($command);

}

?>