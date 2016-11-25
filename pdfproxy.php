<?php

error_reporting(E_ALL);

/*
Based on https://github.com/andrieslouw/imagesweserv, also borrows from 
http://stackoverflow.com/questions/16847015/php-stream-remote-pdf-to-client-browser

Make remote PDF's cachable and accessible by pdf.js 

*/

function download_file($path,$fname){
	$options = array(
		CURLOPT_FILE => fopen($fname, 'w'),
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_URL => $path,
		CURLOPT_FAILONERROR => true, // HTTP code > 400 will throw curl error
		CURLOPT_TIMEOUT => 60,
		CURLOPT_CONNECTTIMEOUT => 5,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ImageFetcher/5.6; +http://images.weserv.nl/)',
	);
	
	//print_r($options);
	
	$ch = curl_init();
	curl_setopt_array($ch, $options);
	$return = curl_exec($ch);
	
	if ($return === false){
		$error = curl_error($ch);
		$errno = curl_errno($ch);
		curl_close($ch);
		unlink($fname);
		$error_code = substr($error,0,3);
		
		if($errno == 6){
			header('HTTP/1.1 410 Gone');
			header('X-Robots-Tag: none');
			header('X-Gone-Reason: Hostname not in DNS or blocked by policy');
			echo 'Error 410: Server could parse the ?url= that you were looking for "' . $path . '", because the hostname of the origin is unresolvable (DNS) or blocked by policy.';
			echo 'Error: $error';
			die;
		}
		
		if(in_array($error_code,array('400','403','404','500','502'))){
			trigger_error('cURL Request error: '.$error.' URL: '.$path,E_USER_WARNING);
		}
		return array(false,$error);
	}else{
		curl_close($ch);
		return array(true,NULL);
	}
}

$url = '';
if (isset($_GET['url']))
{
	$url = $_GET['url'];
}

if ($url != '')
{

	// fetch PDF
	$path = $url;
	$path = str_replace(' ','%20',$path);
	$fname = tempnam('/tmp', 'imo_');
	$curl_result = download_file($path,$fname);
	if($curl_result[0] === false){
		header("HTTP/1.0 404 Not Found");
		echo 'Error 404: Server could parse the ?url= that you were looking for, error it got: '.$curl_result[1];
		die;
	}
	
	header('Expires: '.gmdate("D, d M Y H:i:s", (time()+2678400)).' GMT'); //31 days
	header('Cache-Control: max-age=2678400'); //31 days

	header('Content-Type: application/pdf');	
	header('Content-Length: ' . filesize($fname));

	ob_start();
	readfile($fname);
	ob_end_flush();
}

?>


