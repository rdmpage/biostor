<?php

// Import BioStor references and add to CouchDB

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');
require_once (dirname(__FILE__) . '/reference_code.php');

$ids = array(
146646
);

// pintrest
$ids = array(
145995,
146005,
146493,
146511
);


$ids = array(
115643,73934,146550,146551,142664,146640,146644,146655,146656
);

foreach ($ids as $id)
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
					$reference->thumbnail = $obj->thumbnails[0];		
				}
				
				print_r($reference);
				
				$couch->add_update_or_delete_document($reference,  $reference->_id);
			}
		}		
	}
}

?>