<?php

// Import BioStor references and add to CouchDB

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');
require_once (dirname(__FILE__) . '/reference_code.php');

// 2015-07-30 latest BioStor 146770

$start = 1;
$end = 146770;

for ($id = $start; $id <= $end; $id++)
{
	$json = get('http://biostor.org/reference/' . $id . '.bibjson');
	
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
				$url = 'http://biostor.org/reference/' . $id . '.json';
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
			}
		}		
	}
}

?>