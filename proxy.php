<?php

// 


$img_data = '';

function download_file($path,$fname){
	$options = array(
		CURLOPT_FILE => fopen($fname, 'w'),
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_URL => $path,
		CURLOPT_FAILONERROR => true, // HTTP code > 400 will throw curl error
		CURLOPT_TIMEOUT => 10,
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
			$img_data['mime'] = 'text/plain';
			echo 'Error 410: Server could parse the ?url= that you were looking for, because the hostname of the origin is unresolvable (DNS) or blocked by policy.';
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

function create_image($path){
	global $img_data,$parts;
	$path = str_replace(' ','%20',$path);
	$fname = tempnam(sys_get_temp_dir(), 'imo_');
	$curl_result = download_file($path,$fname);
	if($curl_result[0] === false){
		header("HTTP/1.0 404 Not Found");
		$img_data['mime'] = 'text/plain';
		echo 'Error 404: Server could parse the ?url= that you were looking for, error it got: '.$curl_result[1];
		if(isset($_GET['detail'])){ echo '<small><br /><br />Debug: <br />Path: '.$path.'<br />Fname: '.$fname.'</small>'; }
		echo '<small><br /><br />Also, if possible, please replace any occurences of of + in the ?url= with %2B (see <a href="//imagesweserv.uservoice.com/forums/144259-images-weserv-nl-general/suggestions/2586863-bug">+ bug</a>)</small>';
		die;
	}

	$add_msg = '';
	try{
		$img_data = @getimagesize($fname);
		
		if(isset($img_data[2]) && in_array($img_data[2],array(IMAGETYPE_JPEG,IMAGETYPE_GIF,IMAGETYPE_PNG))){
			if($img_data[0]*$img_data[1] > 71000000){
				unlink($fname);
				throw new Exception('Image too large for processing. Width x Height should be less than 70 megapixels.');
			}
			switch($img_data[2]){
				case IMAGETYPE_JPEG:
					$img_data['exif'] = @exif_read_data($fname);
					$gd_stream = imagecreatefromjpeg($fname);
				break;
				
				case IMAGETYPE_GIF:
					$gd_stream = imagecreatefromgif($fname);
				break;
				
				case IMAGETYPE_PNG:
					$gd_stream = imagecreatefrompng($fname);
				break;
			}
		}
				
		if(!isset($gd_stream) || $gd_stream === false){
			unlink($fname);
			$gd_steam = false;
			throw new Exception('This is no valid image format!');
		}
		
		unlink($fname);
		return $gd_stream;
	}catch(Exception $e){
		@unlink($fname);
		$error_msg = $e->getMessage();
		if(strpos($error_msg,'no decode delegate for this image format') !== false){
			$error_msg = 'This is no valid image format!';
		}elseif(strpos($error_msg,'unable to open image') !== false){
			$error_msg = 'Unable to open this file!';
		}
		
		header("HTTP/1.0 404 Not Found");
		$img_data['mime'] = 'text/plain';
		echo $add_msg.'Error 404: Server could parse the ?url= that you were looking for, because it isn\'t a valid (supported) image, error: '.$error_msg;
		if(isset($_GET['detail'])){ echo '<small><br /><br />Debug: <br />Path: '.$path.'<br />Fname: '.$fname.'</small>'; }
		echo '<small><br /><br />Also, if possible, please replace any occurences of of + in the ?url= with %2B (see <a href="//imagesweserv.uservoice.com/forums/144259-images-weserv-nl-general/suggestions/2586863-bug">+ bug</a>)</small>';
		if($error_msg != 'This is no valid image format!' && $error_msg != 'Unable to open this file!'){
			trigger_error('URL failed. Message: '.$error_msg.' URL: '.$path,E_USER_WARNING);
		}
		die;
	}
}

function show_image($image,$quality, $filename){
	global $img_data;
	switch($img_data['mime']){
		case 'image/jpeg':
			header('Content-Disposition: inline; filename=' . $filename . '.jpg');
			return imagejpeg($image,NULL,$quality);
		break;
		
		case 'image/gif':
			header('Content-Disposition: inline; filename=' . $filename . '.gif');
			return imagegif($image);
		break;
		
		case 'image/png':
			header('Content-Disposition: inline; filename=' . $filename . '.png');
			imagesavealpha($image,true);
			return imagepng($image);
		break;
	}
}

$PageID 	= $_GET['PageID'];

$image_size = 'normal';

if (isset($_GET['size']))
{
	$image_size = $_GET['size'];
}


$url = 'http://www.biodiversitylibrary.org/pagethumb/' .  $PageID;	

switch ($image_size)
{
	case 'small':
		$url .= ',60,60';
		break;
		
	case 'normal':
	default:
		$url .= ',500,500';
		break;
}

$filename = $PageID . '-' . $image_size;


$image = create_image($url);

$q = 85;

$output_formats = array('png' => 'image/png','jpg' => 'image/jpeg','gif' => 'image/gif');
if(isset($_GET['output']) && isset($output_formats[$_GET['output']])){
	$img_data['mime'] = $output_formats[$_GET['output']];
}

header('Expires: '.gmdate("D, d M Y H:i:s", (time()+2678400)).' GMT'); //31 days
header('Cache-Control: max-age=2678400'); //31 days
if(isset($_GET['encoding']) && $_GET['encoding'] == 'base64'){
	header('Content-Type: text/plain');
	ob_start('custom_base64');
}else{
	header('Content-Type: '.$img_data['mime']);
	ob_start();
}
show_image($image,$q, $filename);
if(!isset($_GET['encoding']) || $_GET['encoding'] != 'base64'){
	header('Content-Length: '.ob_get_length());
}
ob_end_flush();


?>


