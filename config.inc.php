<?php

// $Id: //

/**
 * @file config.php
 *
 * Global configuration variables (may be added to by other modules).
 *
 */

global $config;

// Date timezone
date_default_timezone_set('UTC');

if (0)
{
	// Server-------------------------------------------------------------------------------------------
	$config['web_server']	= 'http://localhost'; 
	$config['site_name']	= 'BioStor';

	// Files--------------------------------------------------------------------------------------------
	$config['web_dir']		= dirname(__FILE__);
	$config['web_root']		= '/~rpage/biostor/';
}
if (1)
{
	// Server-------------------------------------------------------------------------------------------
	$config['web_server']	= 'http://biostor.gopagoda.io'; 
	$config['site_name']	= 'BioStor';

	// Files--------------------------------------------------------------------------------------------
	$config['web_dir']		= dirname(__FILE__);
	$config['web_root']		= '/';
}
if (0)
{
	// Server-------------------------------------------------------------------------------------------
	$config['web_server']	= 'http://biostor.org'; 
	$config['site_name']	= 'BioStor';

	// Files--------------------------------------------------------------------------------------------
	$config['web_dir']		= dirname(__FILE__);
	$config['web_root']		= '/';
}

// Proxy settings for connecting to the web--------------------------------------------------------- 
// Set these if you access the web through a proxy server. 
$config['proxy_name'] 	= '';
$config['proxy_port'] 	= '';

//$config['proxy_name'] 	= 'wwwcache.gla.ac.uk';
//$config['proxy_port'] 	= '8080';


// CouchDB------------------------------------------------------------------------------------------
		
// Cloudant
$config['couchdb_options'] = array(
		'database' => 'biostor',
		'host' => 'rdmpage:peacrab280398@rdmpage.cloudant.com',
		'port' => 5984,
		'prefix' => 'http://'
		);		
		
// HTTP proxy
if ($config['proxy_name'] != '')
{
	if ($config['couchdb_options']['host'] != 'localhost')
	{
		$config['couchdb_options']['proxy'] = $config['proxy_name'] . ':' . $config['proxy_port'];
	}
}

$config['stale'] = true;


	
?>