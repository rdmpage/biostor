<?php

require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lcs.php');

//--------------------------------------------------------------------------------------------------
function clean_string($str)
{
	$str = preg_replace("/[\.\?!\-|\s|:|,|;|\(|\)\[|\]]+/", ' ', $str);
	$str = preg_replace("/\s\s+/", ' ', $str);
	
	return $str;
}

//--------------------------------------------------------------------------------------------------
function find_citation($citation, &$result, $threshold = 0.8)
{
	global $config;
	global $couch;
	
	$q = clean_string($citation);
	
	$rows_per_page = 5;
	$parameters = array(
			'q'					=> $q,
			//'include_docs' 		=> 'true',
			'limit' 			=> $rows_per_page
		);
				
	$url = '/_design/citation/_search/all?' . http_build_query($parameters);
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
	$obj = json_decode($resp);
	
	//print_r($q);
	
	if (isset($obj->error))
	{
	}
	else
	{
		$result->status = 200;
		if ($obj->total_rows > 0)
		{
			$best_hit = 0;
		
			$q = strtolower($q);
		
			foreach ($obj->rows as $row)
			{
				$hit = $row->fields->default;
				$hit_original = $hit;
				$hit = clean_string($hit);
		
				$hit = strtolower($hit);
					
				$query_length = strlen($q);
				$hit_length = strlen($hit);
				
				$lcs = new LongestCommonSequence($hit, $q);
				$d = $lcs->score();
				
				$score = min($d / strlen($hit), $d / strlen($q));
				
				if ($score > $threshold)
				{
					if ($score >= $best_hit)
					{
						$best_hit = $score;
						
						$match = new stdclass;
						$match->text = $citation;
						$match->hit = $hit_original;
						$match->match = true;
						$match->id = $row->id;
						$match->score = $score;
						
						if ($score > $best_hit)
						{
							$result->results = array();
						}						
						$result->results[] = $match;
					}
				}
			}
		}
	}
	return (count($result->results) > 1);
}

if (0)
{
	// test
	
	$result = new stdclass;
	$result->results = array();
	$result->query_ok = false;
	
	
	$q = 'Monroe, R (1977) A new species of Euastacus (Decapoda: Parastacidae) from north Queensland. Memoirs of the Queensland Museum 18:65-67';
	
/*	$q = 'Clark, E (1936) The freshwater and land crayfishes of Australia. Memoirs of the Natural Museum of Victoria 10:5-58';

	$q = 'D J Williams (1967) A new genus and species of mealybug from the Philippine Islands (Homoptera: Pseudococcidae). Proceedings of the Biological Society of Washington, 80: 27--30';	
	
	$q = 'A revision of the genera Taphozovs and Sacco-laimus (Chiroptera) in Australia and New Guinea, including a new species and a note on two Malayan forms';
	
	//$q = 'Records of the Australian Museum, 14: 313--339';
	
	$q = 'Hayman RW (1947) A new race of Glauconycteris superba from West Africa. Annals and Magazine of Natural History, Series 11, 13: 547–550.';
	
	$q = 'Peterson RL, Smith DA (1973) A new species of Glauconycteris (Vespertilionidae, Chiroptera). Royal Ontario Museum, Life Sciences Occasional Papers 22: 1-9.';
	
	$q = 'Uchikawa, K. (1989) Ten new taxa of chiropteran myobiids of the genus Pteracarus (Acarina: Myobiidae). Bull. Br. Mus. nat. Hist. (Zool.), 55: 97-108';*/
	
	find_citation($q, $result, 0.5);
	
	print_r($result);
}


?>