<?php

require_once(dirname(__FILE__) . '/lib.php');

//--------------------------------------------------------------------------------------------------
function api_output($obj, $callback)
{
	switch ($obj->status)
	{
		case 303:
			header('HTTP/1.1 404 See Other');
			break;

		case 404:
			header('HTTP/1.1 404 Not Found');
			break;
			
		case 410:
			header('HTTP/1.1 410 Gone');
			break;
			
		case 500:
			header('HTTP/1.1 500 Internal Server Error');
			break;
			 			
		default:
			break;
	}
	
	header("Content-type: text/plain");
	
	if ($callback != '')
	{
		echo $callback . '(';
	}
	//echo json_encode($obj, JSON_PRETTY_PRINT);
	echo json_format(json_encode($obj));
	if ($callback != '')
	{
		echo ')';
	}
}

?>