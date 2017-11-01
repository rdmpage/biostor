<?php

// author names

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
function display_author_lastname_prefix($lastname, $firstname, $callback = '')
{
	global $config;
	global $couch;
	
	$first_letter = mb_substr($firstname, 0, 1);

	$startkey = array($lastname, $first_letter);
	//$endkey = array($lastname, $first_letter . mb_convert_encoding('&#xfff0;', 'UTF-8', 'HTML-ENTITIES'));
	$endkey = array($lastname, $first_letter . 'zzz');

	$url = '_design/author/_view/lastname_firstname?startkey=' . json_encode($startkey) . '&endkey=' . json_encode($endkey) . '&group_level=2';	
	
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
				$author = new stdclass;
				$author->firstname = $row->key[1];
				$author->lastname = $row->key[0];
				$author->name = $author->firstname . ' ' . $author->lastname;
				
				$obj->results[] = $author;
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
		if (isset($_GET['lastname']) && isset($_GET['firstname']))
		{
			display_author_lastname_prefix($_GET['lastname'], $_GET['firstname'], $callback);
			$handled = true;
		}
	}	

}



main();

?>
