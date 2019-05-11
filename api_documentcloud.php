<?php

// document cloud support

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/api_utils.php');
require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/reference_code.php');


//--------------------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}


//--------------------------------------------------------------------------------------------------
// Documentcloud file
function display_documentcloud ($id, $callback = '')
{
	global $config;
	global $couch;
	
	global $memcache;
	global $cacheAvailable;

	$obj = null;
	
	// grab JSON from CouchDB
	$couch_id = $id;
	
	if ($cacheAvailable == true)
	{
		$obj = $memcache->get($couch_id);
	}
	
	if ($obj)
	{
		$obj->status = 200;
	}
	else
	{
		// fetch from CouchDB
		$obj = new stdclass;
	
		$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($couch_id));
	
		$reference = json_decode($resp);
		if (isset($reference->error))
		{
			$obj->status = 404;
		}
		else
		{
			$obj = $reference;
			$obj->status = 200;
			
			if ($cacheAvailable == true)
			{
				$memcache->set($couch_id, $reference);
			}
		}
	}
	
	$dc = new stdclass;
	
	if ($obj)
	{
		/*
		echo '<pre>';
		print_r($obj);
		echo '</pre>';
		*/
		
		$dc->title = $obj->title;
		$dc->description = $obj->citation;
		$dc->id = str_replace('biostor/', '', $id);
		$dc->canonical_url = $config['web_server'] . $config['web_root'] . 'reference/' . str_replace('biostor/', '', $id);
		$dc->pages = count((array)$obj->bhl_pages);
		
		$dc->resources = new stdclass;
		$dc->resources->page = new stdclass;

		$dc->resources->page->image = $config['web_server'] . $config['web_root'] . 'documentcloud/' . $id . '/pages/{page}-{size}';		
		$dc->resources->page->text  = $config['web_server'] . $config['web_root'] . 'documentcloud/' . $id . '/pages/{page}';		
				
		// classic
		//$dc->resources->page->image = "http://direct.biostor.org/reference" . str_replace("biostor/", '', $id) . '/pages/{page}-{size}';		
		//$dc->resources->page->text  = "http://direct.biostor.org/reference" . str_replace("biostor/", '', $id) . '/pages/{page}';		

		$dc->resources->search = "http://direct.biostor.org/dvs/100700/json?q={query}";			
				
		$dc->sections = array();
		$dc->annotations = array();
	}	
	
	header("Content-type: text/plain");
	
	if ($callback != '')
	{
		echo $callback . '(';
	}
	echo json_encode($dc);
	if ($callback != '')
	{
		echo ')';
	}
}

//--------------------------------------------------------------------------------------------------
// Documentcloud page
function display_documentcloud_page ($id, $page, $size, $callback = '')
{
	global $config;
	global $couch;
	
	global $memcache;
	global $cacheAvailable;
	
	$image_url = '';
	
	$image = false;
	
	if ($size !=  '')
	{
		$image = true;
	}

	$obj = null;
	
	// grab JSON from CouchDB
	$couch_id = $id;
	
	if ($cacheAvailable == true)
	{
		$obj = $memcache->get($couch_id);
	}
	
	if ($obj)
	{
		$obj->status = 200;
	}
	else
	{
		// fetch from CouchDB
		$obj = new stdclass;
	
		$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($couch_id));
	
		$reference = json_decode($resp);
		if (isset($reference->error))
		{
			$obj->status = 404;
		}
		else
		{
			$obj = $reference;
			$obj->status = 200;
			
			if ($cacheAvailable == true)
			{
				$memcache->set($couch_id, $reference);
			}
		}
	}
	
	if ($obj)
	{
		$keys = array();
		foreach ($obj->bhl_pages as $k => $v)
		{
			$pages[] = $v;
		}
		$PageID = $pages[$page - 1];

		if ($image)
		{
			switch ($size)
			{
				case 'small':
					$image_url = 'http://www.biodiversitylibrary.org/pagethumb/' .  $PageID . ',100,100';
					if ($config['use_cloudimage'])
					{
						$image_url = 'http://exeg5le.cloudimg.io/s/width/700/http://www.biodiversitylibrary.org/pagethumb/' .  $PageID . ',60,60';
					}		
					if ($config['use_image_proxy'])
					{
						$image_url = $config['web_root'] . 'page/image/' . $PageID . '-small.jpg';
					}		
					break;
					
				case 'normal':
				default:
					$image_url = 'http://www.biodiversitylibrary.org/pagethumb/' .  $PageID . ',800,800';
					
					if ($config['use_cloudimage'])
					{
						$image_url = 'http://exeg5le.cloudimg.io/s/width/700/http://www.biodiversitylibrary.org/pagethumb/' .  $PageID . ',500,500';
					}		
					if ($config['use_image_proxy'])
					{
						$image_url = $config['web_root'] . 'page/image/' . $PageID . '-normal.jpg';
					}		
					break;
			}
		}
		else
		{
			// dummy text for now
			$text = "[dummy text]";
			
			header('Content-type: text/plain');
			if ($callback != '')
			{
				echo $callback .'(';
			}
			echo json_encode($text);
			if ($callback != '')
			{
				echo ')';
			}
			
		}
	}
	
	if ($image)
	{
		header("Cache-control: max-age=3600");
		header("Location: $image_url\n\n");
		exit(0);	
	}			

}

//--------------------------------------------------------------------------------------------------
function main()
{
	$callback = '';
	$handled = false;
	
	//print_r($_GET);
	
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
	
	// Submit job
	if (!$handled)
	{
		if (isset($_GET['id']))
		{	
			$id = $_GET['id'];
			
			if (isset($_GET['page']))
			{
				$page = $_GET['page'];
				
				$size = '';
				
				if (isset($_GET['size']))
				{
					$size = $_GET['size'];
				}
				
				display_documentcloud_page($id, $page, $size, $callback);
				$handled = true;
			}
			
			if (!$handled)
			{
				display_documentcloud($id, $callback);
				$handled = true;
			}
			
		}
	}
	

	
	if (!$handled)
	{
		default_display();
	}
	
		

}


main();

?>