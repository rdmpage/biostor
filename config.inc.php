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

$site = 'local';
//$site = 'biostor';
$site = 'pagoda';

switch ($site)
{
	case 'pagoda':
		// Server-------------------------------------------------------------------------
		//$config['web_server']	= 'http://biostor.gopagoda.io'; 
		$config['web_server']	= 'http://biostor.org'; 
		$config['site_name']	= 'BioStor';

		// Files--------------------------------------------------------------------------
		$config['web_dir']		= dirname(__FILE__);
		$config['web_root']		= '/';
		
		// Memcache-----------------------------------------------------------------------
		$config['use_memcache']	= true;
		$config['memcache_host']= $_SERVER['CACHE1_HOST'];
		$config['memcache_port']= $_SERVER['CACHE1_PORT'];
		break;
		
	case 'biostor':
		// Server-------------------------------------------------------------------------
		//$config['web_server']	= 'http://biostor.org'; 
		$config['web_server']	= 'http://130.209.46.234'; 
		$config['site_name']	= 'BioStor';

		// Files--------------------------------------------------------------------------
		$config['web_dir']		= dirname(__FILE__);
		$config['web_root']		= '/';	
		break;

	case 'local':
	default:
		// Server-------------------------------------------------------------------------
		$config['web_server']	= 'http://localhost'; 
		$config['site_name']	= 'BioStor';

		// Files--------------------------------------------------------------------------
		$config['web_dir']		= dirname(__FILE__);
		$config['web_root']		= '/~rpage/biostor/';
		
		// Memcache-----------------------------------------------------------------------
		$config['use_memcache']	= false;
		break;
		
		
		break;
}

// Proxy settings for connecting to the web----------------------------------------------- 
// Set these if you access the web through a proxy server. 
$config['proxy_name'] 	= '';
$config['proxy_port'] 	= '';

//$config['proxy_name'] 	= 'wwwcache.gla.ac.uk';
//$config['proxy_port'] 	= '8080';

// Image source---------------------------------------------------------------------------
//$config['image_source']	= 'biostor'; // bhl to use remote images, biostor for local
$config['image_source']		= 'bhl'; // bhl to use remote images, biostor for local

// Image caching--------------------------------------------------------------------------
$config['use_cloudimage']	= false; // if true use https://cloudimage.io/
$config['use_weserv']		= false; // if true use https://images.weserv.nl/
$config['use_image_proxy']	= true; // if true use https://images.weserv.nl/


// Memcache-------------------------------------------------------------------------------
$memcache = false;
$cacheAvailable = false;

if ($config['use_memcache'])
{
	if (class_exists('Memcache'))
	{
		$memcache = new Memcache;
		$cacheAvailable = $memcache->connect($config['memcache_host'], $config['memcache_port']);
	}	
}

// CouchDB--------------------------------------------------------------------------------
switch ($site)
{
	case 'pagoda':
		// Cloudant
		$config['couchdb_options'] = array(
				'database' => 'biostor',
				'host' => 'rdmpage:peacrab280398@rdmpage.cloudant.com',
				'port' => 5984,
				'prefix' => 'http://'
				);	
		break;
		
	case 'local':
	default:	
		/*
		// local
		$config['couchdb_options'] = array(
				'database' => 'biostor',
				'host' => 'localhost',
				'port' => 5984,
				'prefix' => 'http://'
				);		
		*/
	
		// Cloudant
		$config['couchdb_options'] = array(
				'database' => 'biostor',
				'host' => 'rdmpage:peacrab280398@rdmpage.cloudant.com',
				'port' => 5984,
				'prefix' => 'http://'
				);	
		
		break;
}	
		
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