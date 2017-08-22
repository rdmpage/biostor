<?php

// bibliographic reference

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
	$twitter = '';
	$twitter .= '<meta name="twitter:card" content="summary"/>' . "\n";
	$twitter .= '<meta name="twitter:title" content="' . htmlentities($reference->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') . '" />' . "\n";
	$twitter .= '<meta name="twitter:site" content="BioStor"/>' . "\n";

	if (isset($reference->thumbnail))
	{
		//$twitter .= '<meta name="twitter:image" content="' . 'http://bionames.org/api/id/' . $doc->_id . '/thumbnail/image" />' . "\n";
	}
	if (isset($doc->citation_string))
	{
		$twitter .= '<meta name="twitter:description" content="' . htmlentities($reference->citation_string, ENT_COMPAT | ENT_HTML5, 'UTF-8') . '" />' . "\n";
	}
	
	return $twitter;
}




?>