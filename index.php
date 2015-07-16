<?php


require_once (dirname(__FILE__) . '/couchsimple.php');
//require_once (dirname(__FILE__) . '/lib.php');
require_once (dirname(__FILE__) . '/reference.php');

//--------------------------------------------------------------------------------------------------
function default_display()
{
	global $config;
	global $couch;


	echo "hi";
}

//--------------------------------------------------------------------------------------------------
function display_record($id)
{
	global $config;
	global $couch;
	
	$reference = null;
	
	// grab JSON from CouchDB
	$couch_id = $id;
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($couch_id));
	
	$reference = json_decode($resp);
	if (isset($reference->error))
	{
		// bounce
		header('Location: ' . $config['web_root'] . "\n\n");
		exit(0);
	}
	
	if (isset($reference->journal))
	{
		$issn = '';
		if (isset($reference->journal->identifier))
		{
			foreach ($reference->journal->identifier as $identifier)
			{
				if ($identifier->type == 'issn')
				{
					$issn = $identifier->id;
				}
			}
		}
		if ($issn != '')
		{
			echo '<a href="issn/' . $issn . '">' . $reference->journal->name . '</a>';			
		}
		else
		{
			echo $reference->journal->name;
		}
		
		if (isset($reference->journal->series))
		{
			echo ' series ' . $reference->journal->series;
		}		
		
		if (isset($reference->year))
		{
			echo ' ' . $reference->year;
		}
				
		if (isset($reference->journal->volume))
		{
			echo ' ' . $reference->journal->volume;
		}
		if (isset($reference->journal->issue))
		{
			echo ' (' . $reference->journal->issue . ')';
		}		
		if (isset($reference->journal->pages))
		{
			echo ':' . str_replace('--', '-', $reference->journal->pages) . '</b>';
		}
	}
	
	
	
	echo "<h1>" . $reference->title . "</h1>";	
	
			if (isset($reference->author))
			{
				$authors = array();
				foreach ($reference->author as $author)
				{
					$string = '';
					if (isset($author->firstname))
					{
						$string = $author->firstname . ' ' . $author->lastname;
					}
					else
					{
						$string = $author->name;
					}
					
					$authors[] = '<a href="' . '?q=author:&quot;' . $string . '&quot;' . '">' . $string . '</a>';
			
				}
				echo 'Authors: ' . join(', ', $authors);
			}
	
	
	echo '<pre>';
	echo htmlentities(reference_to_google_scholar($reference));
	echo '</pre>';

	echo '<pre>';
	echo htmlentities(reference_to_twitter($reference));
	echo '</pre>';

	echo '<pre>';
	echo htmlentities(reference_to_coins($reference));
	echo '</pre>';
	
	
	echo '<pre>';
	echo json_encode(reference_to_citeprocjs($reference), JSON_PRETTY_PRINT);
	echo '</pre>';
	
}

//--------------------------------------------------------------------------------------------------
function display_search($text, $bookmark = '')
{
	global $couch;
	global $config;
	
	$rows_per_page = 10;
	
	$q = $text;
		
	if ($q == '')
	{
		$obj = new stdclass;
		$obj->rows = array();
		$obj->total_rows = 0;
		$obj->bookmark = '';		
	}
	else
	{		
		
		$parameters = array(
				'q'					=> $q,
				'highlight_fields' 	=> '["default"]',
				'highlight_pre_tag' => '"<strong>"',
				'highlight_post_tag'=> '"</strong>"',
				'highlight_number'	=> 5,
				'include_docs' 		=> 'true',
				'limit' 			=> $rows_per_page
			);
			
		if ($bookmark != '')
		{
			$parameters['bookmark'] = $bookmark;
		}
					
		$url = '/_design/citation/_search/all?' . http_build_query($parameters);
		
		$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
		$obj = json_decode($resp);
	}
	
	if (isset($obj->error))
	{
		echo '<h3>Error</h3>';
		echo '<p>' . $obj->reason . '</p>';
	}
	else
	{	
		$total_rows = $obj->total_rows;
		$bookmark = $obj->bookmark;
	
		echo '<h3>' . $total_rows . ' hit(s)' . '</h3>';
	
	
		if ($total_rows > $rows_per_page)
		{
			echo '<p><a class="btn" href="?q=' . urlencode($q) . '&bookmark=' . $bookmark . '">More »</a></p>';
		}


		echo '<table class="table" cellpadding="20">';
		echo '<thead>';
		echo '</thead>';
		echo '<tbody>';
	
		foreach ($obj->rows as $row)
		{
			$reference = $row->doc;
	
			echo '<tr>';
		
			echo '<td valign="top" style="text-align:center;width:100px;">';
			if (isset($reference->thumbnail))
			{
				echo '<img style="box-shadow:2px 2px 2px #ccc;width:64px;" src="' . $reference->thumbnail .  '">';								
			}
			echo '</td>';
		
			echo '<td valign="top" class="item-data">';
				
			echo '<div style="font-size:24px;font-weight:100;">';
			echo '<a href="?id=' . $reference->_id . '">' . $reference->title . '</a>';
			//echo $reference->title;
			echo '</div>';
		
			echo '<div style="color:rgb(128,128,128);">';
			if (isset($reference->year))
			{
				echo 'Published in <b>' . $reference->year . '</b>';
			}
			if (isset($reference->journal))
			{
				$issn = '';
				if (isset($reference->journal->identifier))
				{
					foreach ($reference->journal->identifier as $identifier)
					{
						if ($identifier->type == 'issn')
						{
							$issn = $identifier->id;
						}
					}
				}
				if ($issn != '')
				{
					echo ' in <b><a href="issn/' . $issn . '">' . $reference->journal->name . '</a></b>';			
				}
				else
				{
					echo ' in <b>' . $reference->journal->name . '</b>';
				}
				if (isset($reference->journal->volume))
				{
					echo ', volume <b>' . $reference->journal->volume . '</b>';
				}
				if (isset($reference->journal->issue))
				{
					echo ', issue <b>' . $reference->journal->issue . '</b>';
				}		
				if (isset($reference->journal->pages))
				{
					echo ', on pages <b>' . str_replace('--', '-', $reference->journal->pages) . '</b>';
				}
			}
			else
			{
				// not a journal...
				echo ', on pages <b>' . str_replace('--', '-', $reference->pages) . '</b>';
			}		
			echo '</div>';
		
			echo '<div>';
		
			if (isset($reference->author))
			{
				$authors = array();
				foreach ($reference->author as $author)
				{
					$string = '';
					if (isset($author->firstname))
					{
						$string = $author->firstname . ' ' . $author->lastname;
					}
					else
					{
						$string = $author->name;
					}
					
					$authors[] = '<a href="' . '?q=author:&quot;' . $string . '&quot;' . '">' . $string . '</a>';
			
				}
				echo 'Authors: ' . join(', ', $authors);
			}
		
		
			echo '</div>';
		
		
			//echo '<span style="color:green;">' . $row->highlights->default[0] . '</span>';
			echo '<div>';
			echo '<span style="font-family:sans-serif;font-size:12px;color:#222;">' . $row->highlights->default[0] . '</span>';
			echo '</div>';
		
		
			echo '<div class="item-links">';
		
			//echo '<a href="">cite</a>';
				
			if (isset($reference->identifier))
			{
				foreach ($reference->identifier as $identifier)
				{
					switch ($identifier->type)
					{
						case 'bhl':
							echo '<a href="http://biodiversitylibrary.org/page/' . $identifier->id . '" target="_new"><i class="icon-external-link"></i>http://biodiversitylibrary.org/page/' . $identifier->id . '</a>';
							break;
							
						case 'doi':
							echo '<a href="http://dx.doi.org/' . $identifier->id . '" target="_new"><i class="icon-external-link"></i>http://doi.dx.org/' . $identifier->id . '</a>';
							break;
						/*					
						case 'biostor':
							echo '<a href="http://biostor.org/reference/' . $identifier->id . '" target="_new"><i class="icon-external-link"></i>biostor.org/reference/' . $identifier->id . '</a>';
							break;
						
						case 'cinii':
							echo '<a href="http://ci.nii.ac.jp/naid/' . $identifier->id . '" target="_new"><i class="icon-external-link"></i>ci.nii.ac.jp/naid/' . $identifier->id . '</a>';
							break;										
	
						case 'doi':
							echo '<a href="http://dx.doi.org/' . $identifier->id . '" target="_new"><i class="icon-external-link"></i>doi.dx.org/' . $identifier->id . '</a>';
							break;
					
						case 'handle':
							echo '<a href="http://hdl.handle.net/' . $identifier->id . '" target="_new"><i class="icon-external-link"></i>hdl.handle.net/' . $identifier->id . '</a>';
							break;

						case 'jstor':
							echo '<a href="http://www.jstor.org/stable/' . $identifier->id . '" target="_new"><i class="icon-external-link"></i>www.jstor.org/stable/' . $identifier->id . '</a>';
							break;
						*/	
						default:
							break;
					}
				}
			}
			echo '</div>';
		
		
			echo '</td>';
			echo '</tr>';
		}
	
		echo '</tbody>';
		echo '</table>';
		
		if ($total_rows > $rows_per_page)
		{
			echo '<p><a class="btn" href="?q=' . urlencode($q) . '&bookmark=' . $bookmark . '">More »</a></p>';
		}
		
	}
}

//--------------------------------------------------------------------------------------------------
function main()
{	
	$query = '';
	$bookmark = '';
		
	// If no query parameters 
	if (count($_GET) == 0)
	{
		default_display();
		exit(0);
	}
	
	// If show a single record
	if (isset($_GET['id']))
	{	
		$id = $_GET['id'];
		display_record($id);
	}
	
	// Show search (text, author)
	if (isset($_GET['q']))
	{	
		$query = $_GET['q'];
		
		if (isset($_GET['bookmark']))
		{
			$bookmark = $_GET['bookmark'];
		}
		display_search($query, $bookmark);
		exit(0);
	}	
	
	// Show journal
	
}


main();

		
?>