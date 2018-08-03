<?php

/*
 OpenURL resolver that uses BioStor as source database.
 
 Uses CouchDB views for identifiers and [ISSN,volume,spage] triple, and also full text search
 
 Note that it accepts unparsed citations if they are supplied using the "rft.dat" key, e.g. 
 
http://bionames.org/api/openurl.php?rft.dat=Uchikawa%2C%20K.%20%281989%29%20Ten%20new%20taxa%20of%20chiropteran%20myobiids%20of%20the%20genus%20Pteracarus%20%28Acarina%3A%20Myobiidae%29.%20Bull.%20Br.%20Mus.%20nat.%20Hist.%20%28Zool.%29%2C%2055%3A%2097-108 
 
*/


require_once (dirname(__FILE__) . '/couchsimple.php');


require_once (dirname(__FILE__) . '/nameparse.php');
require_once (dirname(__FILE__) . '/reference_code.php');

require_once (dirname(__FILE__) . '/find.php');

require_once (dirname(__FILE__) . '/api_utils.php');

$debug_openurl = true;



//--------------------------------------------------------------------------------------------------
// From http://snipplr.com/view/6314/roman-numerals/
// Expand subtractive notation in Roman numerals.
function roman_expand($roman)
{
	$roman = str_replace("CM", "DCCCC", $roman);
	$roman = str_replace("CD", "CCCC", $roman);
	$roman = str_replace("XC", "LXXXX", $roman);
	$roman = str_replace("XL", "XXXX", $roman);
	$roman = str_replace("IX", "VIIII", $roman);
	$roman = str_replace("IV", "IIII", $roman);
	return $roman;
}
    
//--------------------------------------------------------------------------------------------------
// From http://snipplr.com/view/6314/roman-numerals/
// Convert Roman number into Arabic
function arabic($roman)
{
	$result = 0;
	
	$roman = strtoupper($roman);

	// Remove subtractive notation.
	$roman = roman_expand($roman);

	// Calculate for each numeral.
	$result += substr_count($roman, 'M') * 1000;
	$result += substr_count($roman, 'D') * 500;
	$result += substr_count($roman, 'C') * 100;
	$result += substr_count($roman, 'L') * 50;
	$result += substr_count($roman, 'X') * 10;
	$result += substr_count($roman, 'V') * 5;
	$result += substr_count($roman, 'I');
	return $result;
} 

//--------------------------------------------------------------------------------------------------
// Convert Arabic numerals into Roman numerals.
function roman($arabic)
{
	$ones = Array("", "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX");
	$tens = Array("", "X", "XX", "XXX", "XL", "L", "LX", "LXX", "LXXX", "XC");
	$hundreds = Array("", "C", "CC", "CCC", "CD", "D", "DC", "DCC", "DCCC", "CM");
	$thousands = Array("", "M", "MM", "MMM", "MMMM");

	if ($arabic > 4999)
	{
		// For large numbers (five thousand and above), a bar is placed above a base numeral to indicate multiplication by 1000.
		// Since it is not possible to illustrate this in plain ASCII, this function will refuse to convert numbers above 4999.
		die("Cannot represent numbers larger than 4999 in plain ASCII.");
	}
	elseif ($arabic == 0)
	{
		// About 725, Bede or one of his colleagues used the letter N, the initial of nullae,
		// in a table of epacts, all written in Roman numerals, to indicate zero.
		return "N";
	}
	else
	{
		// Handle fractions that will round up to 1.
		if (round(fmod($arabic, 1) * 12) == 12)
		{
			$arabic = round($arabic);
		}

		// With special cases out of the way, we can proceed.
		// NOTE: modulous operator (%) only supports integers, so fmod() had to be used instead to support floating point.
		$roman = $thousands[($arabic - fmod($arabic, 1000)) / 1000];
		$arabic = fmod($arabic, 1000);
		$roman .= $hundreds[($arabic - fmod($arabic, 100)) / 100];
		$arabic = fmod($arabic, 100);
		$roman .= $tens[($arabic - fmod($arabic, 10)) / 10];
		$arabic = fmod($arabic, 10);
		$roman .= $ones[($arabic - fmod($arabic, 1)) / 1];
		$arabic = fmod($arabic, 1);


		return $roman;
	}
}
//--------------------------------------------------------------------------------------------------
/**
 * @brief Parse OpenURL parameters and return context object
 *
 * @param params Array of OpenURL parameters
 * @param context_object Context object to populate
 *
 */
function parse_openurl($params, &$context_object)
{
	global $debug_openurl;
	
	$context_object->referring_entity = new stdClass;
	$context_object->referent = new stdClass;
	$context_object->referent->type = 'unknown';
	$context_object->redirect = true;
		
	foreach ($params as $key => $value)
	{
		switch ($key)
		{
			case 'redirect':
				switch ($value[0])
				{
					case 'true':
						$context_object->redirect = true;
						break;
					case 'false':
					default:
						$context_object->redirect = false;
						break;
				}
						
				break;
		
			case 'ctx_ver':
				$context_object->version = $value[0];
				break;
				
			case 'rfe_id':
				$context_object->referring_entity->id = $value[0];
				break;
		
			case 'rft_val_fmt':
				switch ($value)
				{
					case 'info:ofi/fmt:kev:mtx:journal':
						$context_object->referent->type = 'article';
						break;

					case 'info:ofi/fmt:kev:mtx:book':
						$context_object->referent->type = 'book';
						break;
						
					default:
						if (!isset($context_object->referent->type))
						{
							$context_object->referent->type = 'Unknown';
						}
						break;
				}
				break;
			
			// Article title
			case 'rft.atitle':
			case 'atitle':
				$title = $value[0];
				$title = preg_replace('/\.$/', '', $title);
				$title = strip_tags($title);
				$title = html_entity_decode($title, ENT_NOQUOTES, 'UTF-8');
				$context_object->referent->title = $title;
				$context_object->referent->type = 'article';
				break;

			// Book title
			case 'rft.btitle':
			case 'btitle':
				$context_object->referent->title = $value[0];
				$context_object->referent->type = 'book';
				break;
				
			// Journal title
			case 'rft.jtitle':
			case 'rft.title':
			case 'title':
				$publication_outlet = trim($value[0]);
				$publication_outlet = preg_replace('/^\[\[/', '', $publication_outlet);
				$publication_outlet = preg_replace('/\]\]$/', '', $publication_outlet);
				
				
				if (!isset($context_object->referent->journal))
				{
					$context_object->referent->journal = new stdclass;
				}
				$context_object->referent->journal->name = $publication_outlet;
				$context_object->referent->type = 'article';
				break;
				
			// ISSN
			case 'rft.issn':
			case 'issn':
				$identifier = new stdclass;
				$identifier->type = 'issn';
				$identifier->id = trim($value[0]);

				if (!isset($context_object->referent->journal))
				{
					$context_object->referent->journal = new stdclass;
				}
				$context_object->referent->journal->identifier[] = $identifier;
				break;

			// Identifiers
			case 'rft_id':
			case 'id':
				foreach ($value as $v)
				{		
					// DOI
					if (preg_match('/^(info:doi\/|doi:)(?<doi>.*)/', $v, $match))
					{
						$identifier = new stdclass;
						$identifier->type = 'doi';
						$identifier->id = $match['doi'];
					
						$context_object->referent->identifier[] = $identifier;
					}
					// Handle
					if (preg_match('/^(info:hdl\/|hdl:)(?<hdl>.*)/', $v, $match))
					{
						$identifier = new stdclass;
						$identifier->type = 'handle';
						$identifier->id = $match['hdl'];
					
						$context_object->referent->identifier[] = $identifier;
					}
					// PMID
					if (preg_match('/^(info:pmid\/|pmid:)(?<pmid>.*)/', $v, $match))
					{
						$identifier = new stdclass;
						$identifier->type = 'pmid';
						$identifier->id = $match['pmid'];
						
						$context_object->referent->identifier[] = $identifier;
					}
					// PMC
					if (preg_match('/^(pmc:)(?<pmc>.*)/', $v, $match))
					{
						$identifier = new stdclass;
						$identifier->type = 'pmc';
						$identifier->id = $match['pmc'];
						
						$context_object->referent->identifier[] = $identifier;
					}
					
					// Without INFO-URI prefix
					// LSID
					if (preg_match('/^urn:lsid:/', $v))
					{
						$identifier = new stdclass;
						$identifier->type = 'lsid';
						$identifier->id = $v;
						
						$context_object->referent->identifier[] = $identifier;
					}
					// URL (including PDFs)
					if (preg_match('/^http:\/\//', $v))
					{
						$matched = false;
						// PDF
						if (!$matched)
						{
							if (preg_match('/\.pdf/', $v))
							{
								$matched = true;
								$context_object->referent->pdf = $v;
							}
						}
						// BioStor
						if (!$matched)
						{
							if (preg_match('/http:\/\/biostor.org\/reference\/(?<id>\d+)$/', $v, $match))
							{
								$matched = true;
								
								$identifier = new stdclass;
								$identifier->type = 'biostor';
								$identifier->id = $match['id'];
								
								$context_object->referent->identifier[] = $identifier;
							}
						}
						if (!$matched)
						{
							$context_object->referent->link = new stdclass;
							$context_object->referent->link->url = $v;
						}						
					}					
				}
				break;

			// Authors 
			case 'rft.au':
			case 'au':
				foreach ($value as $v)
				{
					$parts = parse_name($v);					
					$author = new stdClass();
					if (isset($parts['last']))
					{
						$author->lastname = $parts['last'];
					}
					if (isset($parts['suffix']))
					{
						$author->suffix = $parts['suffix'];
					}
					if (isset($parts['first']))
					{
						$author->forename = $parts['first'];
						
						if (array_key_exists('middle', $parts))
						{
							$author->forename .= ' ' . $parts['middle'];
						}
					}
					$context_object->referent->author[] = $author;					
				}
				break;
				
			// article details
			case 'rft.volume':
			case 'volume':
				if (!isset($context_object->referent->journal))
				{
					$context_object->referent->journal = new stdclass;
				}
				$context_object->referent->journal->volume = $value[0];
				break;

			case 'rft.issue':
			case 'issue':
				if (!isset($context_object->referent->journal))
				{
					$context_object->referent->journal = new stdclass;
				}
				$context_object->referent->journal->issue = $value[0];
				break;

			case 'rft.spage':
			case 'spage':
				if (!isset($context_object->referent->journal))
				{
					$context_object->referent->journal = new stdclass;
				}
				$context_object->referent->journal->pages = $value[0];
				break;

			case 'rft.epage':
			case 'epage':
				if (!isset($context_object->referent->journal))
				{
					$context_object->referent->journal = new stdclass;
				}
				if (isset($context_object->referent->journal->pages))
				{
					$context_object->referent->journal->pages .= '--' . $value[0];
				}
				else
				{
					$context_object->referent->journal->pages = $value[0];
				}
				break;

			case 'rft.pages':
			case 'pages':
				if (!isset($context_object->referent->journal))
				{
					$context_object->referent->journal = new stdclass;
				}
				$context_object->referent->journal->pages = $value[0];
				$context_object->referent->journal->pages = preg_replace('/–/u', '--', $context_object->referent->journal->pages);
				break;
				
			default:
				$k = str_replace("rft.", '', $key);
				$context_object->referent->$k = $value[0];				
				break;
		} 
	}
	
	// Clean
	
	//print_r($context_object);
	
	
	// Dates
	if (isset($context_object->referent->date))
	{
		if (preg_match('/^[0-9]{4}$/', $context_object->referent->date))
		{
			$context_object->referent->year = $context_object->referent->date;
			$context_object->referent->date = $context_object->referent->date . '-00-00';
		}
		if (preg_match('/^(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})$/', $context_object->referent->date, $match))
		{
			$context_object->referent->year = $match['year'];
			$context_object->referent->date = $match['year'] . '-' . $match['month'] . '-' . $match['day'];
		}
	}	
	
	// Zotero
	/*
	// Endnote epage may have leading "-" as it splits spage-epage to generate OpenURL
	// would need  to fix mode, as here we don't have epage
	if (isset($context_object->referent->epage))
	{
		$context_object->referent->epage = preg_replace('/^\-/', '', $context_object->referent->epage);
	}
	*/
	
	if (isset($context_object->referent->journal))
	{
		// Journal titles with series numbers are split into title,series fields
		if (preg_match('/(?<title>.*),?\s+series\s+(?<series>[0-9]+)$/i', $context_object->referent->journal->name, $match))
		{
			$context_object->referent->journal->name= $match['title'];
			$context_object->referent->journal->series= $match['series'];
		}		

		// Volume might have series information
		if (isset($context_object->referent->journal->volume))
		{
			if (preg_match('/^series\s+(?<series>[0-9]+),\s*(?<volume>[0-9]+)$/i', $context_object->referent->journal->volume, $match))
			{
				$context_object->referent->journal->volume= $match['volume'];
				$context_object->referent->journal->series= $match['series'];
			}		
		}
	}
			
	// Author array might not be populated, in which case add author from aulast and aufirst fields
	if (isset($context_object->referent->author))
	{
		if ((count($context_object->referent->author) == 0) && (isset($context_object->referent->aulast) && isset($context_object->referent->aufirst)))
		{
			$author = new stdClass();
			$author->surname = $context_object->referent->aulast;
			$author->forename = $context_object->referent->aufirst;
			$context_object->referent->author[] = $author;
		}	
	}
	
	// Use aulast and aufirst to ensure first author name properly parsed
	if (isset($context_object->referent->aulast) && isset($context_object->referent->aufirst))
	{
		$author = new stdClass();
		$author->surname = $context_object->referent->aulast;
		$author->forename = $context_object->referent->aufirst;
		$context_object->referent->author[0] = $author;
	}	
	
	// EndNote encodes accented characters, which break journal names
	if (isset($context_object->referent->publication_outlet))
	{
		$context_object->referent->publication_outlet = preg_replace('/%9F/', 'ü', $context_object->referent->publication_outlet);
	}
}



//--------------------------------------------------------------------------------------------------
/**
 * @brief Handle OpenURL request
 *
 * We may have more than one parameter with same name, so need to access QUERY_STRING, not _GET
 * http://stackoverflow.com/questions/353379/how-to-get-multiple-parameters-with-same-name-from-a-url-in-php
 *
 */
function main()
{
	global $config;
	global $couch;
	
	$debug_openurl = false;
	
	$webhook = '';
	$callback = '';
			
	// If no query parameters 
	if (count($_GET) == 0)
	{
		//display_form();
		exit(0);
	}	
	
	if (isset($_GET['webhook']))
	{
		$webhook = $_GET['webhook'];
	}
	
	if (isset($_GET['debug']))
	{	
		$debug_openurl = true;
	}
	
	if (isset($_GET['callback']))
	{	
		$callback = $_GET['callback'];
	}
	
	// Handle query and display results.
	$query = explode('&', html_entity_decode($_SERVER['QUERY_STRING']));
	$params = array();
	foreach( $query as $param )
	{
	  list($key, $value) = explode('=', $param);
	  
	  $key = preg_replace('/^\?/', '', urldecode($key));
	  $params[$key][] = trim(urldecode($value));
	}
	
	// This is what we got from user
	$context_object = new stdclass;
	parse_openurl($params, $context_object);
	
	//print_r($context_object);
	
	// OK, can we find this?
	
	// result object
	$openurl_result = new stdclass;
	$openurl_result->status = 404;
	
	$openurl_result->context_object = $context_object;
	
	$openurl_result->results = array();
	
	if ($debug_openurl)
	{
		$openurl_result->debug = new stdclass;
	}
			
	// via DOI or other identifier
	if (count($openurl_result->results) == 0)
	{			
		// identifier search
		if (isset($context_object->referent->identifier))
		{
			//print_r($context_object->referent->identifier);
			$found = false;
			foreach ($context_object->referent->identifier as $identifier)
			{
			
				if ($debug_openurl)
				{
					$openurl_result->debug->identifiers[] = $identifier;
				}
			
				if (!$found)
				{
					switch ($identifier->type)
					{
						case 'doi':
							$url =  $config['couchdb_options']['database'] . "/_design/identifier/_view/doi?key=" . urlencode('"' . $identifier->id . '"');
							//echo $url . '<br />';
							
							if ($config['stale'])
							{
								$url .= '&stale=ok';
							}									
		
							$resp = $couch->send("GET", "/" . $url . '&limit=1' );
							$result = json_decode($resp);
							
							if (isset($result->rows))
							{				
								if (count($result->rows) == 1)
								{
									if ($debug_openurl)
									{								
										$openurl_result->debug->found_from_identifiers = true;
									}
							
									$found = true;
								
									$match = new stdclass;
									$match->match = true;
									$match->id = $result->rows[0]->id;
									$match->{$identifier->type} = $identifier->id;
									$openurl_result->results[] = $match;
									$openurl_result->status = 200;
								}
							}
							break;
							
						case 'pmid':
							$url =  $config['couchdb_options']['database'] . "/_design/identifier/_view/pmid?key=" . $identifier->id;
							//echo $url . '<br />';
							
							if ($config['stale'])
							{
								$url .= '&stale=ok';
							}		
									
							$resp = $couch->send("GET", "/" . $url . '&limit=1' );
							$result = json_decode($resp);
				
							if (count($result->rows) == 1)
							{
								$openurl_result->debug->found_from_identifiers = true;
							
								$found = true;
								
								$match = new stdclass;
								$match->match = true;
								$match->id = $result->rows[0]->id;
								$match->{$identifier->type} = $identifier->id;
								$openurl_result->results[] = $match;
								$openurl_result->status = 200;
							}
							break;
							
							
						default:
							break;
					}
				}
			}
		}
	}
	
	// classical OpenURL lookup using [ISSN,volume,spage] triple
	if (count($openurl_result->results) == 0)
	{
		$triple = false;
		if (isset($context_object->referent->journal->name))
		{
			$journal = $context_object->referent->journal->name;
			
			// Convert accented characters
			$journal = strtr(utf8_decode($journal), 
					utf8_decode("ÀÁÂÃÄÅàáâãäåĀāĂăĄąÇçĆćĈĉĊċČčÐðĎďĐđÈÉÊËèéêëĒēĔĕĖėĘęĚěĜĝĞğĠġĢģĤĥĦħÌÍÎÏìíîïĨĩĪīĬĭĮįİıĴĵĶķĸĹĺĻļĽľĿŀŁłÑñŃńŅņŇňŉŊŋÒÓÔÕÖØòóôõöøŌōŎŏŐőŔŕŖŗŘřŚśŜŝŞşŠšſŢţŤťŦŧÙÚÛÜùúûüŨũŪūŬŭŮůŰűŲųŴŵÝýÿŶŷŸŹźŻżŽž"),
					"aaaaaaaaaaaaaaaaaaccccccccccddddddeeeeeeeeeeeeeeeeeegggggggghhhhiiiiiiiiiiiiiiiiiijjkkkllllllllllnnnnnnnnnnnoooooooooooooooooorrrrrrsssssssssttttttuuuuuuuuuuuuuuuuuuuuwwyyyyyyzzzzzz");
				 
			$journal = utf8_encode($journal);
						
			$journal = strtolower($journal);
			
			$journal = preg_replace('/\bfor\b/', '', $journal);
			$journal = preg_replace('/\band\b/', '', $journal);
			$journal = preg_replace('/\bof\b/', '', $journal);
			$journal = preg_replace('/\bthe\b/', '', $journal);

			$journal = preg_replace('/\bde\b/', '', $journal);
			$journal = preg_replace('/\bla\b/', '', $journal);
			$journal = preg_replace('/\bet\b/', '', $journal);
			
			// whitespace
			$journal = preg_replace('/\s+/', '', $journal);
		
			// punctuation
			$journal = preg_replace('/[\.|,|\'|\(|\)]+/', '', $journal);
	
			if (isset($context_object->referent->journal->volume))
			{
				$volume = $context_object->referent->journal->volume;
				
				if (isset($context_object->referent->journal->pages))
				{
					if (is_numeric($context_object->referent->journal->pages))
					{
						$spage = $context_object->referent->journal->pages;
						$triple = true;
					}
					else
					{
						if (preg_match('/^(?<spage>\d+)--(?<epage>\d+)$/', $context_object->referent->journal->pages, $m))
						{
							$spage = $m['spage'];
							$triple = true;					
						}
					}
				}
			}
			
			if ($triple)
			{
				if ($debug_openurl)
				{
					$openurl_result->debug->triple = $triple;
					$openurl_result->debug->journal = $journal;
				}
			
				$openurl_result->status = 200;
			
				// i. get ISSN
				
				$url =  $config['couchdb_options']['database'] . "/_design/openurl/_view/journal_to_issn?key=" . urlencode('"' . $journal . '"');
				
				//echo $url;
		
				if ($config['stale'])
				{
					$url .= '&stale=ok';
				}	
				
				//echo $url;				
							
				$resp = $couch->send("GET", "/" . $url . '&limit=1' );
				$result = json_decode($resp);
				
				//print_r($resp);
				
				if (count($result->rows) == 1)
				{
					// we have an ISSN for this journal
					$issn = $result->rows[0]->value;
					
					if ($debug_openurl)
					{
						$openurl_result->debug->issn = $issn;
					}
	
					// build triple [ISSN, volume, spage]			
					$keys = array(
						$issn, 
						$volume,
						$spage
						);
						
					if ($debug_openurl)
					{
						$openurl_result->debug->keys = $keys;
					}
			
					$url = $config['couchdb_options']['database'] . "/_design/openurl/_view/issn_triple?key=" . urlencode(json_encode($keys));
					
					//echo $url . '<br />';
	
					if ($config['stale'])
					{
						$url .= '&stale=ok';
					}			
	
					$resp = $couch->send("GET", "/" . $url );
					$r = json_decode($resp);
					
					//print_r($r);
					
					if (count($r->rows) > 0)
					{
						foreach ($r->rows as $row)
						{
							$match = new stdclass;
							$match->match = true;
							$match->triple = $keys;
							$match->id = $row->id;
							$openurl_result->results[] = $match;
						}
					}
					else
					{
						// No hit, try converting volume to/from Roman
						if (is_numeric($volume))
						{
							$volume = strtolower (roman($volume));
						}
						else
						{
							$volume = arabic($volume);
						}
						$keys = array(
							$issn, 
							$volume,
							$spage
							);
							
						if ($debug_openurl)
						{
							$openurl_result->debug->keys = $keys;
						}
				
						$url = $config['couchdb_options']['database'] . "/_design/openurl/_view/triple?key=" . urlencode(json_encode($keys));
						//echo $url . '<br />';
		
						if ($config['stale'])
						{
							$url .= '&stale=ok';
						}			
		
						$resp = $couch->send("GET", "/" . $url );
						$r = json_decode($resp);
						//print_r($r);
						
						if (count($r->rows) > 0)
						{
							foreach ($r->rows as $row)
							{
								$match = new stdclass;
								$match->match = true;
								$match->triple = $keys;
								$match->id = $row->id;
								$openurl_result->results[] = $match;
							}
						}
					}
				}
			}
				
		}
	}
	
	// full text search
	if (count($openurl_result->results) == 0)
	{			
		// Has user supplied an unparsed string?
		if (isset($context_object->referent->dat))
		{
			find_citation($context_object->referent->dat, $openurl_result, 0.7);
		}
	}	
	
	// ok, if we have one or more results we return these and let user/agent decide what to do
	
	$num_results = count($openurl_result->results);
	
	if ($context_object->redirect)
	{
		$id = 0;
		if ($num_results == 1)
		{
			if ($openurl_result->results[0]->match)
			{
				// I'm feeling lucky
				$id = $openurl_result->results[0]->id;
			}
		}
		// Redirect to reference display, if not found then id is 0 and so we get default error message
		header('Location: reference/' . $id . "\n\n");
	}
	else
	{
		// API call return, populated with details
		for ($i = 0; $i < $num_results; $i++)
		{
			$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($openurl_result->results[$i]->id));
			$reference = json_decode($resp);
			if (!isset($reference->error))
			{
				$openurl_result->results[$i]->reference = $reference;
				
				// unset large fields
				if (isset($openurl_result->results[$i]->reference->text))
				{
					unset($openurl_result->results[$i]->reference->text);
				}
				if (isset($openurl_result->results[$i]->reference->names))
				{
					unset($openurl_result->results[$i]->reference->names);
				}
				
			}
		}
		api_output($openurl_result, $callback);
	}
	
}

main();

?>