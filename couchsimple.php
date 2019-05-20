<?php

require_once (dirname(__FILE__) . '/config.inc.php');

$couch = new CouchSimple($config['couchdb_options']);

//--------------------------------------------------------------------------------------------------
class CouchSimple
{
	//----------------------------------------------------------------------------------------------
     function __construct($options)
     {
         foreach($options AS $key => $value) {
             $this->$key = $value;
         }
     }
     
     //-----------------------------------------------------------------------------------
	// Do HTTP HEAD to see if a document exists
	function exists($id)
	{
		$ch = curl_init(); 
		
		$url = $this->prefix . $this->host . ':' . $this->port . '/' . $this->database . '/' . urlencode($id);
		
		echo $url . "\n";

		curl_setopt ($ch, CURLOPT_URL, $url); 
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 		
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		if (isset($this->proxy))
		{
			curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
		}
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "HEAD");
		
		// http://stackoverflow.com/a/770200
		curl_setopt($ch, CURLOPT_NOBODY, true);

   		$response = curl_exec($ch);
    	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    	
    	//echo $response;
    	
   		return ($http_code == 200);
	}     

	//----------------------------------------------------------------------------------------------
     function send($method, $url, $post_data = NULL)
     {
     	global $config;
     	
		$ch = curl_init(); 
		
		$url = $this->prefix . $this->host . ':' . $this->port . $url;
		
		//echo $url;
		
		curl_setopt ($ch, CURLOPT_URL, $url); 
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
		
		//curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, true);
		//curl_setopt ($ch, CURLOPT_CAINFO, dirname(__FILE__) . "/cacert.pem");
		
		// Set HTTP headers
		$headers = array();
		$headers[] = 'Content-type: application/json'; // we are sending JSON
		
		// Override Expect: 100-continue header (may cause problems with HTTP proxies
		// http://the-stickman.com/web-development/php-and-curl-disabling-100-continue-header/
		$headers[] = 'Expect:'; 
    	curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
		
		if (isset($this->proxy))
		{
			curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
		}
		switch ($method) {
		  case 'POST':
			curl_setopt($ch, CURLOPT_POST, TRUE);
			if (!empty($post_data)) {
			  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			}
			break;
		  case 'PUT':
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			if (!empty($post_data)) {
			  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			}
			break;
		  case 'DELETE':
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			break;
		}
   		$response = curl_exec($ch);
    	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    	
    	//echo $response;
    	
		if (curl_errno ($ch) != 0 )
		{
			echo "CURL error: ", curl_errno ($ch), " ", curl_error($ch);
		}
    		
   		return $response;
     }
     
     // Add, update, or delete object. This method is handy if you are unsure of whether object
     // already exists. If it does, we get the revision number and then update the record using PUT 
     function add_update_or_delete_document($obj, $id, $operation = 'add')
     {
		if ($operation == 'add')
		{
			// add (PUT as we have identifier)
			$resp = $this->send("PUT", "/" . $this->database . "/" . urlencode($id), json_encode($obj));
			$r = json_decode($resp);
			
			if (isset($r->error))
			{
				if ($r->error == 'conflict')
				{
					// Document exists, try update instead
					$operation = 'update';
				}
			}
		}
		
		switch ($operation)
		{			
			case 'delete':
			case 'update':
				$resp = $this->send("GET", "/" . $this->database . "/" . urlencode($id));	
				$r = json_decode($resp);
				$rev = $r->_rev;
				
				if ($operation == 'delete')
				{
					$resp = $this->send("DELETE", "/" . $this->database . "/" . urlencode($id) . '?rev=' . $rev);
				}
				else
				{
					$obj->_rev = $rev;
					$resp = $this->send("PUT", "/" . $this->database . "/" . urlencode($id), json_encode($obj));
				}
				var_dump($resp);
	
			default:
				break;			
		}
		var_dump($resp);
     }
 }


 
?>