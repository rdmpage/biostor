<?php

// log visits
/*
echo '<pre>';
print_r($_SERVER);
echo '</pre>';
*/

require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');

require_once (dirname(__FILE__) . '/api_utils.php');

//--------------------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}



//--------------------------------------------------------------------------------------------------
// Authors with same last name and similar first names
function display_view_counter($id, $callback = '')
{
	global $config;
	global $couch;
	
	$id = str_replace('biostor/', '', $id);
	
	$startkey = array($id);
	$endkey = array($id, 'z'); //mb_convert_encoding('&#xfff0;', 'UTF-8', 'HTML-ENTITIES'));

	$url = '_design/counter/_view/views?startkey=' . json_encode($startkey) . '&endkey=' . json_encode($endkey);// . '&group_level=2';	
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
	
	//echo $url . '<br/>';
	
	//echo $resp;
		
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
				$obj->results[] = $row->value;
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
		if (isset($_GET['id']))
		{
			display_view_counter($_GET['id'], $callback);
			$handled = true;
		}
	}	

}



main();

?>
