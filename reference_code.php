<?php

// bibliographic reference

require_once('php-json-ld/jsonld.php');

//--------------------------------------------------------------------------------------------------
/**
 * @brief Get identifiers for a reference
 * *
 * @param reference Reference object
 *
 * @return Array of key-value pairs where the key is the identifier type 
 * and the value is the identifier
 */
function reference_identifiers($reference)
{
	$identifiers = array();
	
	//print_r($reference->identifier);
	
	if (isset($reference->identifier))
	{
		foreach ($reference->identifier as $identifier)
		{
			$identifiers[$identifier->type] = $identifier->id;
		}
	}
	
	return $identifiers;
}

//--------------------------------------------------------------------------------------------------
/**
 * @brief Create a citation string for indexing
 * *
 * @param reference Reference object to be encoded
 *
 * @return OpenURL
 */
function reference_to_citation_string($reference)
{
	$citation = '';
	
	//echo "citation=$citation\n";
	if (isset($reference->author))
	{
		$authors = array();
		foreach ($reference->author as $author)
		{
			if (isset($author->forename) && isset($author->lastname))
			{
				$authors[] = $author->lastname . ' ' . $author->forename;
			}
			else
			{
				$authors[] = $author->name;
			}
		
		}
		$citation .= join(', ', $authors);
		$citation .= ' ';
	}
	//echo "citation=$citation\n";
	
	
	if (isset($reference->year))
	{
		$citation .= '(' . $reference->year . ')';
	}
	
	if (isset($reference->title))
	{
		$citation .= ' ' . $reference->title . '.';
	}
	//echo "citation=$citation\n";
	
	
	if (isset($reference->journal))
	{
		$citation .= ' ' . $reference->journal->name;
		if (isset($reference->journal->volume))
		{
			$citation .= ', ' . $reference->journal->volume;
		}
		if (isset($reference->journal->issue))
		{
			$citation .= '(' . $reference->journal->issue . ')';
		}		
		if (isset($reference->journal->pages))
		{
			$citation .= ': ' . $reference->journal->pages;
		}
	}
	else
	{
		// not a journal...
		$citation .= ': ' . $reference->pages;		
	}
	
	//echo "citation=$citation\n";

	return $citation;
}


//--------------------------------------------------------------------------------------------------
/**
 * @brief Convert BibJSON object to citeproc-js object
 *
 * @param reference Reference object to be converted
 * @param id Local id for citeproc-js object 
 *
 * @return citeproc-js object
 */
function reference_to_citeprocjs($reference, $id = 'ITEM-1')
{
	$citeproc_obj = array();
	$citeproc_obj['id'] = $id;
	$citeproc_obj['source'] = 'BioStor';	
	
	$citeproc_obj['title'] = $reference->title;
	
	$citeproc_obj['alternative-id'] = array();
	
	/*
	if (isset($reference->journal))
	{	
		$citeproc_obj['type'] = 'article-journal';
	}
	*/
	switch ($reference->type)
	{
		case 'article':
			$citeproc_obj['type'] = 'article-journal';
			break;
			
		default:
			$citeproc_obj['type'] = $reference->type;
			break;
	}	
	
	if (isset($reference->year))
	{
		$citeproc_obj['issued']['date-parts'][] = array((Integer)$reference->year);
	}
	
	if (isset($reference->author))
	{
		$citeproc_obj['author'] = array();
		foreach ($reference->author as $author)
		{
			$a = new stdclass;
			
			// hack while we work with old BioStor BibJSON
			if (isset($author->forename))
			{
				$author->firstname = trim($author->forename);
			}
			
			
			if (isset($author->firstname))
			{
				$a->given = trim($author->firstname);
				$a->family = $author->lastname;
			}
			else
			{
				$a->literal = $author->name;
			}
			$citeproc_obj['author'][] = $a;			
		}
	}
	
	// Book
	if (isset($reference->publisher))
	{
		if (isset($reference->publisher->name))
		{
			$citeproc_obj['publisher'] = $reference->publisher->name;
		}
		if (isset($reference->publisher->address))
		{
			$citeproc_obj['publisher-place'] = $reference->publisher->address;
		}
		if (isset($reference->page))
		{
			$citeproc_obj['page'] = str_replace('--', '-', $reference->journal->pages);
		}
	}
		
	// Article
	if (isset($reference->journal))
	{
		$citeproc_obj['container-title'] = $reference->journal->name;
		if (isset($reference->journal->series))
		{
			$citeproc_obj['collection-title'] = $reference->journal->series;
		}
		
		if (isset($reference->journal->volume))
		{
			$citeproc_obj['volume'] = $reference->journal->volume;
		}
		if (isset($reference->journal->issue))
		{
			$citeproc_obj['issue'] = $reference->journal->issue;
		}
		if (isset($reference->journal->pages))
		{
			$citeproc_obj['page'] = str_replace('--', '-', $reference->journal->pages);
		}
		
		if (isset($reference->journal->identifier))
		{
			foreach ($reference->journal->identifier as $identifier)
			{
				if ($identifier->type == 'issn')
				{
					$citeproc_obj['ISSN'] = array();
					$citeproc_obj['ISSN'][] = $identifier->id;
				}
			}
		}
		
	}
	
	// Chapter
	if (isset($reference->book))
	{
		if (isset($reference->book->title))
		{
			$citeproc_obj['container-title'] = $reference->book->title;
		}
		if (isset($reference->pages))
		{
			$citeproc_obj['page'] = str_replace('--', '-', $reference->pages);
		}
	}
	
	$url = '';
	
	if (isset($reference->identifier))
	{
		foreach ($reference->identifier as $identifier)
		{
			switch ($identifier->type)
			{
				// DOI
				case 'doi':
					$citeproc_obj['DOI'] = $identifier->id;
					
					$citeproc_obj['alternative-id'][] = $identifier->id;
					break;
					
				// Convert identifiers to URLs
				case 'ark':
					$url = 'http://gallica.bnf.fr/ark:/' . $identifier->id;
					
					$citeproc_obj['alternative-id'][] = $identifier->id;
					break;
					
				case 'biostor':
					$url = 'http://biostor.org/reference/' . $identifier->id;
					$citeproc_obj['alternative-id'][] = $url;
					$citeproc_obj['BIOSTOR'] = $identifier->id;
					break;
					
				case 'handle':
					if ($url == '')
					{
						$url = 'http://hdl.handle.net/' . $identifier->id;
					}
					$citeproc_obj['HANDLE'] = $identifier->id;
					$citeproc_obj['alternative-id'][] = $identifier->id;
					break;
					
				case 'isbn':
				case 'isbn13':
					$citeproc_obj['ISBN'] = $identifier->id;
					break;
					
				case 'jstor':
					$url = 'http://www.jstor.org/' . $identifier->id;
					$citeproc_obj['alternative-id'][] = $url;
					$citeproc_obj['JSTOR'] = $identifier->id;
					break;
					
				default:
					break;
			}
		}
	}
	
	if ($url == '')
	{
		if (isset($reference->link))
		{
			foreach ($reference->link as $link)
			{
				switch ($link->anchor)
				{
					case 'LINK':
						$url = $link->url;
						break;
					
					default:
						break;
				}
			}
		}
	}
	
	if ($url != '')
	{
		$citeproc_obj['URL'] = $url;
	}
	
	// scanned images
	if ($reference->bhl_pages)
	{
		$citeproc_obj['bhl_pages'] = $reference->bhl_pages;
	}
	
	if (isset($reference->text))
	{
		$citeproc_obj['text_pages'] = $reference->text;
	}
	
	if (count($citeproc_obj['alternative-id']) == 0)
	{
		unset($citeproc_obj['alternative-id']);
	}
	
	return $citeproc_obj;
}

//--------------------------------------------------------------------------------------------------
/**
 * @brief Create a COinS (ContextObjects in Spans) for a reference
 *
 * COinS encodes an OpenURL in a <span> tag. See http://ocoins.info/.
 *
 * @param reference Reference object to be encoded
 *
 * @return HTML <span> tag containing a COinS
 */
function reference_to_coins($reference)
{
	global $config;
	
	$coins = '<span class="Z3988" title="' 
		. reference_to_openurl($reference) 
//		. '&amp;webhook=' . urlencode($config['web_server'] . $config['web_root'] . 'webhook.php')
		. '"></span>';
	return $coins;
}

//--------------------------------------------------------------------------------------------------
/**
 * @brief Create an OpenURL for a reference
 * *
 * @param reference Reference object to be encoded
 *
 * @return OpenURL
 */
function reference_to_openurl($reference)
{
	$openurl = '';
	$openurl .= 'ctx_ver=Z39.88-2004';

	// Local publication identifier
	if (isset($reference->id))
	{
		$openurl .= '&amp;rfe_id=' . urlencode($reference->id);
	}
	
	//print_r($reference);
	
	if (isset($reference->journal) || $reference->type == 'article')
	{
		$openurl .= '&amp;rft_val_fmt=info:ofi/fmt:kev:mtx:journal';
		$openurl .= '&amp;genre=article';
		$openurl .= '&amp;rft.atitle=' . urlencode($reference->title);
		$openurl .= '&amp;rft.jtitle=' . urlencode($reference->journal->name);
	
		if (isset($reference->journal->series))
		{
			$openurl .= '&amp;rft.series=' . urlencode($reference->journal->series);
		}
		
		if (isset($reference->journal->identifier))
		{
			foreach ($reference->journal->identifier as $identifier)
			{
				switch ($identifier->type)
				{
					case 'issn':
						$openurl .= '&amp;rft.issn=' . $identifier->id;
						break;
						
					default:
						break;
				}
			}
		}
		
		if (isset($reference->journal->volume))
		{
			$openurl .= '&amp;rft.volume=' . $reference->journal->volume;
		}
		if (isset($reference->journal->issue))
		{
			$openurl .= '&amp;rft.issue=' . $reference->journal->issue;
		}		
		if (isset($reference->journal->pages))
		{
			if (preg_match('/^(?<spage>.*)--(?<epage>.*)/', $reference->journal->pages, $m))
			{
				$openurl .= '&amp;rft.spage=' . $m['spage'];
				$openurl .= '&amp;rft.epage=' . $m['epage'];
			}
			else
			{
				$openurl .= '&amp;rft.pages=' . $reference->journal->pages;
			}
		}
	}
	else
	{
		if ($reference->type == 'book')
		{
			$openurl .= '&amp;rft.btitle=' . urlencode($reference->title);
		}
		else
		{
			$openurl .= '&amp;rft.title=' . urlencode($reference->title);		
		}
		
		//$openurl .= '&amp;rft.pages=' . $reference->journal->pages;
		
	}
	
	// generic stuff
	
	// authors
	// authors
	if (isset($reference->author))
	{
		if (count($reference->author) > 0)
		{
			if (isset($reference->author[0]->lastname))
			{
				$openurl .= '&amp;rft.aulast=' . urlencode($reference->author[0]->lastname);
				if (isset($reference->author[0]->firstname))
				{
					$openurl .= '&amp;rft.aufirst=' . urlencode($reference->author[0]->firstname);
				}
			}
		}
		foreach ($reference->author as $author)
		{
			$openurl .= '&amp;rft.au=' . urlencode($author->name);
		}
	}
	
	// date
	if (isset($reference->year))
	{
		$openurl .= '&amp;rft.date=' . $reference->year;
	}
	
	// identifiers
	if (isset($reference->identifier))
	{
		foreach ($reference->identifier as $identifier)
		{
			switch ($identifier->type)
			{
				case 'doi':
					$openurl .= '&amp;rft_id=info:doi/' . urlencode($identifier->id);
					break;
					
				case 'handle':
					$openurl .= '&amp;rft_id=info:hdl/' . urlencode($identifier->id);
					break;

				case 'pmid':
					$openurl .= '&amp;rft_id=info:pmid/' . urlencode($identifier->id);
					break;

				default:
					break;
			}
		}				
	}
	
	if (isset($reference->link))
	{
		foreach ($reference->link as $link)
		{
			if (isset($link->anchor))
			{
				if ($link->anchor == 'LINK')
				{
					$openurl .= '&amp;rft_id='. urlencode($link->url);
				}
			}
		}
	}
	
	
	return $openurl;
}

//--------------------------------------------------------------------------------------------------
function reference_to_ris($reference)
{
	$ris = '';
	
	if (isset($reference->journal))
	{
		$ris .= "TY  - JOUR\n";
	}
	else
	{
		$ris .= "TY  - GEN\n";
	}

	
	if (isset($reference->id))
	{
		$ris .=  "ID  - " . $reference->id . "\n";
	}
	
	if (isset($reference->author))
	{
		foreach ($reference->author as $a)
		{
			if (is_object($a))
			{
				$ris .= "AU  - " . $a->name . "\n";
			}
		}
	}
	
	if (isset($reference->title))
	{
		$ris .=  "TI  - " . strip_tags($reference->title) . "\n";
	}
	
	if (isset($reference->journal)) 
	{
		$ris .=  "JF  - " . $reference->journal->name . "\n";
		if (isset($reference->journal->volume))
		{
			$ris .=  "VL  - " . $reference->journal->volume . "\n";
		}
		if (isset($reference->journal->issue))
		{
			$ris .=  "IS  - " . $reference->journal->issue . "\n";
		}
		
		foreach ($reference->journal->identifier as $identifier)
		{
			switch ($identifier->type)
			{
				case 'issn':
					$ris .=  "SN  - " . $identifier->id . "\n";
					break;
					
				default:
					break;
			}
		}
		
		if (isset($reference->journal->pages))
		{
			if (preg_match('/^(?<spage>.*)--(?<epage>.*)/', $reference->journal->pages, $m))
			{
				$ris .=  "SP  - " . $m['spage'] . "\n";
				$ris .=  "EP  - " . $m['epage'] . "\n";
			}
			else
			{
				$ris .=  "SP  - " . $reference->journal->pages . "\n";
			}
		}
		
		
	}
	
	if (isset($reference->year))
	{
		$ris .=  "Y1  - " . $reference->year . "\n";
	}
	
	if (isset($reference->identifier))
	{
		foreach ($reference->identifier as $identifier)
		{
			switch ($identifier->type)
			{
				case 'doi':
					$ris .=  "DO  - " . $identifier->id . "\n";
					break;
					
				default:
					break;
			}
		}
	}	
	
	$ris .=  "ER  - \n";
	$ris .=  "\n";
	
	return $ris;
}

//--------------------------------------------------------------------------------------------------
function reference_to_google_scholar($reference)
{
	$meta = '';
	
	$dc_meta = '';

	// Google Scholar
	$meta .= "\n<!-- Google Scholar metadata -->\n";
	$meta .= '<meta name="citation_title" content="' . htmlentities($reference->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') . '" />' . "\n";
	$meta .= '<meta name="citation_date" content="' . $reference->year . '" />' . "\n";

	if (isset($reference->author))
	{
		$author_names = array();
		foreach ($reference->author as $author)
		{
			$author_names[] = htmlentities($author->name, ENT_COMPAT | ENT_HTML5, 'UTF-8');
		}
		$meta .= '<meta name="citation_authors" content="' . join(";", $author_names) . '" />' . "\n";
	}

	if ($reference->type == 'article')
	{
		$meta .= '<meta name="citation_journal_title" content="' . htmlentities($reference->journal->name, ENT_COMPAT | ENT_HTML5, 'UTF-8') . '" />' . "\n";
	
		// ISSN
		if (isset($reference->journal->identifier))
		{
			foreach ($reference->journal->identifier as $identifier)
			{
				if ($identifier->type == 'issn')
				{
					$meta .= '<meta name="citation_issn" content="' . $identifier->id . '" />' . "\n";
				}
			}
		}
	
		if (isset($reference->journal->volume))
		{
			$meta .= '<meta name="citation_volume" content="' . $reference->journal->volume . '" />' . "\n";
		}
		if (isset($reference->journal->issue))
		{
			$meta .= '<meta name="citation_issue" content="' . $reference->journal->issue . '" />' . "\n";
		}
	
		if (isset($reference->journal->pages))
		{
			if (preg_match('/^(?<spage>.*)-[-]?(?<epage>.*)$/', $reference->journal->pages, $m))
			{
				$meta .= '<meta name="citation_firstpage" content="' . str_replace('-', '', $m['spage']) . '" />' . "\n";
				$meta .= '<meta name="citation_lastpage" content="' . str_replace('-', '', $m['epage']) . '" />' . "\n";
			}
		}
	}

	if (isset($reference->identifier))
	{
		foreach ($reference->identifier as $identifier)
		{
			switch ($identifier->type)
			{
				case 'doi':
					$meta .= '<meta name="citation_doi" content="' . $identifier->id . '" />' . "\n";

					// Dublin Core
					$dc_meta .= '<meta name="dc.identifier" content="' . $identifier->id . '" />' . "\n";
					break;
				
				default:
					break;
			}
		}
	}

	if (isset($reference->link))
	{
		foreach ($reference->link as $link)
		{
			if (isset($link->anchor))
			{		
				switch ($link->anchor)
				{
					case 'PDF':
						$meta .= '<meta name="citation_pdf_url" content="' . $link->url . '" />' . "\n";
						break;
				
					default:
						break;
				}
			}
		}
	}
				
	//$meta .= '<meta name="citation_abstract_html_url" content="http://bionames.org/references/' . $doc->_id . '" />' . "\n";
	//$meta .= '<meta name="citation_fulltext_html_url" content="' . $config['web_root'] . 'reference/' . $reference->reference_id . '" />' . "\n";
	//$meta .= '<meta name="citation_pdf_url" content="' . $config['web_root'] . 'reference/' . $reference->reference_id . '.pdf" />' . "\n";
	
	// Dublin Core
	$dc_meta .= '<meta name="dc.identifier" content="http://biostor.org/reference/' . str_replace('biostor/', '', $reference->_id) . '" />' . "\n";
	
	
	$meta .= "\n" . $dc_meta;

	return $meta;
}

//--------------------------------------------------------------------------------------------------
function reference_to_twitter($reference)
{
	global $config;

	$twitter = '';
	$twitter .= '<meta name="twitter:card" content="summary"/>' . "\n";

	//$twitter .= '<meta name="twitter:title" content="' . htmlentities($reference->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') . '" />' . "\n";
	
	$clean_title = $reference->title;
	$clean_title = str_replace('<', '&lt;', $clean_title);
	$clean_title = str_replace('>', '&gt;', $clean_title);
	
	$twitter .= '<meta name="twitter:title" content="' . $clean_title . '" />' . "\n";
	$twitter .= '<meta name="twitter:site" content="BioStor"/>' . "\n";

	$twitter .= '<meta name="twitter:image" content="' 
		. $config['web_server'] 
		. $config['web_root']
		. 'reference/' . str_replace('biostor/', '', $reference->_id) . '/thumbnail" />' . "\n";
	
	
	$description = '';
	
	if (isset($reference->journal))
	{
		if (isset($reference->journal->name))
		{
			$description = $reference->journal->name;
		}
	}
		
	if ($description == '' && isset($reference->citation))
	{
		$description = $reference->citation;
	}	
	
	$twitter .= '<meta name="twitter:description" content="' . $description . '" />' . "\n";
	
	return $twitter;
}

//--------------------------------------------------------------------------------------------------
/**
 * @brief Convert BibJSON object to JSON-LD
 *
 * @param reference Reference object to be converted
 *
 * @return JSON-LD object
 */
function reference_to_jsonld($reference)
{
	global $config;

	$triples = array();
	
	$subject_id = 
		$config['web_server'] 
		. $config['web_root']
		. 'reference/' . str_replace('biostor/', '', $reference->_id);

	$s = '<' . $subject_id . '>';
	
	// type-------------------------------------------------------------------------------
	switch ($reference->type)
	{	
		case 'book':
			$type = 'http://schema.org/Book';
			break;

		case 'chapter':
			$type = 'http://schema.org/Chapter';
			break;
	
		case 'article':
			$type = 'http://schema.org/ScholarlyArticle';
			break;
			
		default:
			$type = 'http://schema.org/CreativeWork';
			break;
	}
	
	$triples[] = $s . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <' . $type . '> .';
	
	// url--------------------------------------------------------------------------------
	$triples[] = $s . ' <http://schema.org/url> "' . $subject_id . '" .';

	// title------------------------------------------------------------------------------
	if (isset($reference->title))
	{
		$title = $reference->title;
		$title = strip_tags($title);
		
		$title = preg_replace('/\.$/', '', $title);
		$title = preg_replace('/\n/', '', $title);
		$title = preg_replace('/\r/', '', $title);
	
		$triples[] = $s . ' <http://schema.org/name> ' . '"' . addcslashes($title, '"\\') . '" .';
	}
	
	// date-------------------------------------------------------------------------------
	if (isset($reference->date))
	{
		$date = $reference->date;
		while (count($date) < 3)
		{
			$date[] = '00';
		}
		$triples[] = $s . ' <http://schema.org/datePublished> ' . '"' . join('-', $date) . '" .';		
	}
	else
	{
		if (isset($reference->year))
		{
			$triples[] = $s . ' <http://schema.org/datePublished> ' . '"' . $reference->year . '" .';		
		}	
	}
	
	// authors----------------------------------------------------------------------------
	if (isset($reference->author))
	{
		$use_role = true;
		
		$n = count($reference->author);
		for ($i = 0; $i < $n; $i++)
		{
			$index = $i + 1;
		
			// Author
			$author_id = '<' . $subject_id . '#creator/' . $index . '>';
			
			$triples[] = $author_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/Person> .';			
			
			if (isset($reference->author[$i]->name))
			{
				$name = $reference->author[$i]->name;
				$name = preg_replace('/\s\s+/u', ' ', $name);
				
				$triples[] = $author_id . ' <http://schema.org/name> ' . '"' . addcslashes($name, '"') . '"' . ' .';
			}

			if (isset($reference->author[$i]->firstname))
			{
				$triples[] = $author_id . ' <http://schema.org/givenName> ' . '"' . addcslashes($reference->author[$i]->firstname, '"') . '"' . ' .';
			}

			if (isset($reference->author[$i]->lastname))
			{
				$triples[] = $author_id . ' <http://schema.org/familyName> ' . '"' . addcslashes($reference->author[$i]->lastname, '"') . '"' . ' .';
			}			
			
			if ($use_role)
			{
				// Role to hold author position
				$role_id = '<' . $subject_id . '#role/' . $index . '>';
				
				$triples[] = $role_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ' . ' <http://schema.org/Role>' . ' .';			
				$triples[] = $role_id . ' <http://schema.org/roleName> "' . $index . '" .';			
			
				$triples[] = $s . ' <http://schema.org/creator> ' .  $role_id . ' .';
				$triples[] = $role_id . ' <http://schema.org/creator> ' .  $author_id . ' .';			
			}
			else
			{
				$triples[] = $s . ' <http://schema.org/creator> ' . $author_id  . ' .';							
			}			
		}
	
	}
	
	// identifiers------------------------------------------------------------------------
	
	// biostor
	$identifier_id = '<' . $subject_id . '#biostor' . '>';

	$triples[] = $s . ' <http://schema.org/identifier> ' . $identifier_id . '.';			
	$triples[] = $identifier_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/PropertyValue> .';
	$triples[] = $identifier_id . ' <http://schema.org/propertyID> ' . '"biostor"' . '.';
	$triples[] = $identifier_id . ' <http://schema.org/value> ' . '"' . str_replace('biostor/', '', $reference->_id) . '"' . '.';

	$triples[] = $s . ' <http://schema.org/sameAs> ' . '"https://biostor.org/reference/' . str_replace('biostor/', '', $reference->_id) . '" ' . '. ';	
	
	// other identifiers (DOI, JSTOR, etc.)
	if (isset($reference->identifier))
	{
		foreach ($reference->identifier as $identifier)
		{
			switch ($identifier->type)
			{
				case 'doi':
					$identifier_id = '<' . $subject_id . '#doi' . '>';

					$triples[] = $s . ' <http://schema.org/identifier> ' . $identifier_id . '.';			
					$triples[] = $identifier_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/PropertyValue> .';
					$triples[] = $identifier_id . ' <http://schema.org/propertyID> ' . '"doi"' . '.';
					$triples[] = $identifier_id . ' <http://schema.org/value> ' . '"' . $identifier->id . '"' . '.';

					$triples[] = $s . ' <http://schema.org/sameAs> ' . '"https://doi.org/' . $identifier->id . '" ' . '. ';
					break;

				case 'handle':
					$identifier_id = '<' . $subject_id . '#handle' . '>';

					$triples[] = $s . ' <http://schema.org/identifier> ' . $identifier_id . '.';			
					$triples[] = $identifier_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/PropertyValue> .';
					$triples[] = $identifier_id . ' <http://schema.org/propertyID> ' . '"handle"' . '.';
					$triples[] = $identifier_id . ' <http://schema.org/value> ' . '"' . $identifier->id . '"' . '.';

					$triples[] = $s . ' <http://schema.org/sameAs> ' . '"https://hdl.handle.net/' . $identifier->id . '" ' . '. ';
					break;

				case 'jstor':
					$identifier_id = '<' . $subject_id . '#jstor' . '>';

					$triples[] = $s . ' <http://schema.org/identifier> ' . $identifier_id . '.';			
					$triples[] = $identifier_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/PropertyValue> .';
					$triples[] = $identifier_id . ' <http://schema.org/propertyID> ' . '"jstor"' . '.';
					$triples[] = $identifier_id . ' <http://schema.org/value> ' . '"' . $identifier->id . '"' . '.';

					$triples[] = $s . ' <http://schema.org/sameAs> ' . '"https://www.jstor.org/stable/' . $identifier->id . '" ' . '. ';
					break;
				
				default:
					break;
			}
		}
	}	
	
	// container--------------------------------------------------------------------------
	if ($reference->type == 'article')
	{
		$container_id = '<' . $subject_id . '#container' . '>';
		
		$issn = array();
		if (isset($reference->journal->identifier))
		{
			foreach ($reference->journal->identifier as $identifier)
			{
				if ($identifier->type == 'issn')
				{
					$issn[] = $identifier->id;
				}
			}
		}
		
		if (count($issn) > 0)
		{
			$container_id = '<http://worldcat.org/issn/' . $issn[0] . '>';
			$triples[] = $container_id . ' <http://schema.org/issn> ' .  '"' . addcslashes($issn[0], '"') . '" .';			
		}
		
		$triples[] = $s . ' <http://schema.org/isPartOf> ' . $container_id . ' .';
		
		if (isset($reference->journal->name))
		{
			$triples[] = $container_id . ' <http://schema.org/name> ' .  '"' . addcslashes($reference->journal->name, '"') . '" .';
		}
		if (isset($reference->journal->volume))
		{
			$triples[] = $s . ' <http://schema.org/volumeNumber> ' .  '"' . addcslashes($reference->journal->volume, '"') . '" .';
		}
		if (isset($reference->journal->issue))
		{
			$triples[] = $s . ' <http://schema.org/issueNumber> ' .  '"' . addcslashes($reference->journal->issue, '"') . '" .';
		}
		if (isset($reference->journal->pages))
		{
			if (preg_match('/^(?<spage>[^-]+)(-[-]?(?<epage>.*))?$/', $reference->journal->pages, $m))
			{
				$triples[] = $s . ' <http://schema.org/pageStart> ' .  '"' . addcslashes($m['spage'], '"') . '" .';
				if ($m['epage'] != '')
				{
					$triples[] = $s . ' <http://schema.org/pageEnd> ' .  '"' . addcslashes($m['epage'], '"') . '" .';				
				}
			}
			else
			{
				$triples[] = $s . ' <http://schema.org/pagination> ' .  '"' . addcslashes(str_replace('--', '-', $reference->journal->pages), '"') . '" .';							
			}
		}
	}
	
	// page images------------------------------------------------------------------------
	if (1)
	{
		// itemList
		if (isset($reference->bhl_pages))
		{
			$triples[] = $s . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/itemList> .';
	
			$count = 1;
			foreach($reference->bhl_pages as $page_name => $PageID)
			{
				$ListItem_id = '<' . $subject_id . '#listitem_' . $count . '>';
				$triples[] = $ListItem_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/ListItem> .';
				$triples[] = $ListItem_id . ' <http://schema.org/position> "' . $count . '" . ';

				// image
				$image_id = '<https://biodiversitylibrary.org/page/' . $PageID . '>';
		
				// ImageObject
				$triples[] = $image_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/ImageObject> .';

				// do we add details here, or load the images separately?
				if (0)
				{
					$triples[] = $image_id . ' <http://schema.org/fileFormat> "image/jpeg" .';

					// URLs to images
					$triples[] = $image_id . ' <http://schema.org/contentUrl> ' . '"' . addcslashes('https://www.biodiversitylibrary.org/pagethumb/' . $PageID . ',700,1000', '"') . '"' . ' .';
					$triples[] = $image_id . ' <http://schema.org/thumbnailUrl> ' . '"' . addcslashes('https://www.biodiversitylibrary.org/pagethumb/' . $PageID . ',100,150', '"') . '"' . ' .';
		
					// page name
					$triples[] = $image_id . ' <http://schema.org/name> ' . '"' . addcslashes($page_name, '"') . '"' . ' .';
				}
			
				$triples[] = $ListItem_id . ' <http://schema.org/item> ' . $image_id . ' . ';
					
				// page image is part of the encoding
				$triples[] = $s . ' <http://schema.org/itemListElement> ' .  $ListItem_id . ' .';	
		
				$count++;				
			}
		}	
	}
	else
	{
		// simple part list
		if (isset($reference->bhl_pages))
		{
			$count = 1;
			foreach($reference->bhl_pages as $page_name => $PageID)
			{
				// image
				$image_id = '<https://biodiversitylibrary.org/page/' . $PageID . '>';
		
				// ImageObject
				$triples[] = $image_id . ' <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://schema.org/ImageObject> .';

				// order (note this may break SPARQL queries if we have > one article that includes the same page)
				$triples[] = $image_id . ' <http://schema.org/position> ' . '"' . addcslashes($count, '"') . '"' . ' .';

				// do we add details here, or load the images separately?
				if (0)
				{
					$triples[] = $image_id . ' <http://schema.org/fileFormat> "image/jpeg" .';

					// URLs to images
					$triples[] = $image_id . ' <http://schema.org/contentUrl> ' . '"' . addcslashes('https://www.biodiversitylibrary.org/pagethumb/' . $PageID . ',700,1000', '"') . '"' . ' .';
					$triples[] = $image_id . ' <http://schema.org/thumbnailUrl> ' . '"' . addcslashes('https://www.biodiversitylibrary.org/pagethumb/' . $PageID . ',100,150', '"') . '"' . ' .';
		
					// page name
					$triples[] = $image_id . ' <http://schema.org/name> ' . '"' . addcslashes($page_name, '"') . '"' . ' .';
				}
			
					
				// page image is part of the encoding
				$triples[] = $s . ' <http://schema.org/hasPart> ' .  $image_id . ' .';	
		
				$count++;				
			}
		}							
	}					

	//------------------------------------------------------------------------------------
	$t = join("\n", $triples);
	
	$doc = jsonld_from_rdf($t, array('format' => 'application/nquads'));
		
	// Context to set vocab to schema
	$context = new stdclass;

	$context->{'@vocab'} = "http://schema.org/";

	// sameAs is always an array
	$sameAs = new stdclass;
	$sameAs->{'@id'} = "http://schema.org/sameAs";
	$sameAs->{'@container'} = "@set";
	$context->sameAs = $sameAs;

	/*
	// identifier is always an array
	$identifier = new stdclass;
	$identifier->{'@id'} = "http://schema.org/identifier";
	$identifier->{'@container'} = "@set";
	$context->identifier = $identifier;
	*/

	// issn is always an array
	$issn = new stdclass;
	$issn->{'@id'} = "http://schema.org/issn";
	$issn->{'@container'} = "@set";
	$context->issn = $issn;
	
	$frame = (object)array(
		'@context' => $context,

		// Root on article
		'@type' => $type,
	);	

	$framed = jsonld_frame($doc, $frame);
	
	//print_r($framed);

	return $framed;
}	




?>