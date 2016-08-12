<?php

// article "hashing" names

require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');

require_once (dirname(__FILE__) . '/api_utils.php');

//--------------------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}


//--------------------------------------------------------------------------------------------------
function display_hash_all_pages($hash,  $callback = '')
{
	global $config;
	global $couch;
	
	$key = array();
	
	$input = json_decode($hash);
	//$key = $input;
	
	foreach ($input as $h)
	{
		$key[] = (String)$h;
	}


	$url = '_design/biblife/_view/hash_all_pages?key=' . urlencode(json_encode($key)) . '&reduce=false';	
	
	$url .= "&include_docs=true";
	
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}	
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
		
	$response_obj = json_decode($resp);
	
	$obj = new stdclass;
	$obj->status = 404;
	$obj->url = $url;
	
	if (isset($response_obj->error))
	{
		$obj->error = $response_obj->error;
	}
	else
	{
		if (count($response_obj->rows) == 0)
		{
			$obj->error = 'Not found';
		}
		else
		{	
			$obj->status = 200;
			
			$obj->results = array();
			
			foreach ($response_obj->rows as $row)
			{
				$doc = $row->doc;
				
				// debugging
				if (isset($doc->thumbnail))
				{
					unset($doc->thumbnail);
				}
				if (isset($doc->names))
				{
					unset($doc->names);
				}
				if (isset($doc->bhl_pages))
				{
					unset($doc->bhl_pages);
				}
				if (isset($doc->classification))
				{
					unset($doc->classification);
				}
				$obj->results[] = $doc;
			}	
		}
	}
	
	api_output($obj, $callback);
}

//--------------------------------------------------------------------------------------------------
function display_hash_numbers($hash,  $callback = '')
{
	global $config;
	global $couch;
	
	$key = array();
	
	$input = json_decode($hash);
	//$key = $input;
	
	foreach ($input as $h)
	{
		$key[] = (String)$h;
	}


	$url = '_design/biblife/_view/hash_numbers?key=' . urlencode(json_encode($key)) . '&reduce=false';	
	
	$url .= "&include_docs=true";
	
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}	
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
		
	$response_obj = json_decode($resp);
	
	$obj = new stdclass;
	$obj->status = 404;
	$obj->url = $url;
	
	if (isset($response_obj->error))
	{
		$obj->error = $response_obj->error;
	}
	else
	{
		if (count($response_obj->rows) == 0)
		{
			$obj->error = 'Not found';
		}
		else
		{	
			$obj->status = 200;
			
			$obj->results = array();
			
			foreach ($response_obj->rows as $row)
			{
				$doc = $row->doc;
				
				// debugging
				if (isset($doc->thumbnail))
				{
					unset($doc->thumbnail);
				}
				if (isset($doc->names))
				{
					unset($doc->names);
				}
				if (isset($doc->bhl_pages))
				{
					unset($doc->bhl_pages);
				}
				if (isset($doc->classification))
				{
					unset($doc->classification);
				}
				$obj->results[] = $doc;
			}	
		}
	}
	
	api_output($obj, $callback);
}

//--------------------------------------------------------------------------------------------------
function display_hash_author($hash,  $callback = '')
{
	global $config;
	global $couch;
	

	$url = '_design/biblife/_view/hash_author?key=' . urlencode('"' . $hash . '"') . '&reduce=false';	
	
	$url .= "&include_docs=true";
	
	if ($config['stale'])
	{
		$url .= '&stale=ok';
	}	
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
		
	$response_obj = json_decode($resp);
	
	$obj = new stdclass;
	$obj->status = 404;
	$obj->url = $url;
	
	if (isset($response_obj->error))
	{
		$obj->error = $response_obj->error;
	}
	else
	{
		if (count($response_obj->rows) == 0)
		{
			$obj->error = 'Not found';
		}
		else
		{	
			$obj->status = 200;
			
			$obj->results = array();
			
			foreach ($response_obj->rows as $row)
			{
				$doc = $row->doc;
				
				// debugging
				if (isset($doc->thumbnail))
				{
					unset($doc->thumbnail);
				}
				if (isset($doc->names))
				{
					unset($doc->names);
				}
				if (isset($doc->bhl_pages))
				{
					unset($doc->bhl_pages);
				}
				if (isset($doc->classification))
				{
					unset($doc->classification);
				}
				$obj->results[] = $doc;
			}	
		}
	}
	
	api_output($obj, $callback);
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
		if (isset($_GET['hash_numbers']))
		{
			display_hash_numbers($_GET['hash_numbers'], $callback);
			$handled = true;
		}
	}	
	
	if (!$handled)
	{
		if (isset($_GET['hash_all_pages']))
		{
			display_hash_all_pages($_GET['hash_all_pages'], $callback);
			$handled = true;
		}
	}	
	
	
	if (!$handled)
	{
		if (isset($_GET['hash_author']))
		{
			display_hash_author($_GET['hash_author'], $callback);
			$handled = true;
		}
	}	
	

}



main();

?>
