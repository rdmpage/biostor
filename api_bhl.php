<?php

// Try and partition a BHL item into articles based on page numbering
// For example http://www.biodiversitylibrary.org/item/104640 has pages like
// Text
// Page 2 (Text)
// Page 3 (Text)
// Text
// Text
// Page 2 (Text)
// Page 3 (Text)
// 
// So we can guess the page boundaries of an article
// 

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/lib.php');

//----------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}


//----------------------------------------------------------------------------------------
function display_article_breaks($ItemID)
{
	$url = 'http://www.biodiversitylibrary.org/api2/httpquery.ashx?op=GetItemPages&itemid='
	 . $ItemID . '&apikey=0d4f0303-712e-49e0-92c5-2113a5959159&format=json';

	$json = get($url);

	$obj = json_decode($json);

	$articles = array();
	$count = 0;

	$article_counter_start = 0;

	$last_page_type = 'text';

	$pages = array();

	foreach ((array)$obj->Result as $page)
	{
		$s = new stdclass;
		$s->PageID =  $page->PageID;
		$s->name =  $page->PageTypes[0]->PageTypeName;
		if (isset($page->PageNumbers[0]))
		{
			$s->number = $page->PageNumbers[0]->Number;
		}

		$pages[] = $s;
	}

	//print_r($pages);

	$last_page_type = 'text';

	$state = 0;

	$article_count = $article_counter_start;

	$spages = array();
	$epages = array();
	$authors = array();

	$n = count($pages);
	for ($i = 0; $i < $n; $i++)
	{
		/*
		echo "$i $last_page_type";
		if (isset($pages[$i]->number))
		{
			echo $pages[$i]->number;
		}
		echo "<br />";
		*/

		if (isset($pages[$i]->number) && preg_match('/^\d+$/', $pages[$i]->number))
		{
			if ($last_page_type == 'text')
			{
				$location = $i;
				$location -= ($pages[$i]->number - 1);
				
				if ($location > 0)
				{
					$articles[$article_count] = $pages[$location]->PageID;
				}
			
				$spages[$article_count] = 1;
			}
	
			$last_page_type = 'number';
		
			$state = 1;
			$epages[$article_count] = $pages[$i]->number;
		}
		else
		{
			if ($state == 1)
			{
				//$authors[$article_count] = get_author($articles[$article_count]);
				$article_count++;
			}
	
			$last_page_type = 'text';
			$state = 0;
		}
	}

	//echo "articles\n";
	// print_r($articles);
	// print_r($spages);
	// print_r($epages);

	header("Content-type: text/plain\n\n");
	for ($i = $article_counter_start; $i <= $article_count; $i++)
	{
		if (isset($spages[$i]) && isset($epages[$i]) && isset($articles[$i]))
		{
			echo $spages[$i] . "\t" . $epages[$i] . "\t" . $articles[$i] . "\n";
		}
	}
}

//--------------------------------------------------------------------------------------------------
function main()
{
	$callback = '';
	$handled = false;
	
	
	// If no query parameters 
	if (count($_GET) == 0)
	{
		default_display();
		exit(0);
	}
	
	if (isset($_GET['callback']))
	{	
		$callback = $_GET['callback'];
	}
	
	
	if (!$handled)
	{
		if (isset($_GET['item']))
		{
			display_article_breaks($_GET['item']);

			$handled = true;
		}
	}	
	
}



main();


?>