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
//$site = 'pagoda';
//$site = 'heroku';

$site = 'nanobox';
//$site = 'nanobox-local';

switch ($site)
{

	case 'nanobox':
		// Server-------------------------------------------------------------------------
		$config['web_server']	= 'http://happy-hog.nanoapp.io'; 
		$config['web_server']	= 'http://biostor.org'; 
		$config['site_name']	= 'BioStor';

		// Memcache-----------------------------------------------------------------------
		$config['use_memcache']	= false;

		// Files--------------------------------------------------------------------------
		$config['web_dir']		= dirname(__FILE__);
		$config['web_root']		= '/';		
		break;


	case 'nanobox-local':
		// Server-------------------------------------------------------------------------
		$config['web_server']	= 'http://172.21.0.5'; 
		$config['site_name']	= 'BioStor';

		// Memcache-----------------------------------------------------------------------
		$config['use_memcache']	= false;

		// Files--------------------------------------------------------------------------
		$config['web_dir']		= dirname(__FILE__);
		$config['web_root']		= '/';		
		break;

	case 'heroku':
		// Server-------------------------------------------------------------------------
		//$config['web_server']	= 'https://biostor.herokuapp.com'; 
		$config['web_server']	= 'http://biostor.org'; 
		$config['site_name']	= 'BioStor';

		// Memcache-----------------------------------------------------------------------
		$config['use_memcache']	= false;

		// Files--------------------------------------------------------------------------
		$config['web_dir']		= dirname(__FILE__);
		$config['web_root']		= '/';		
		break;


	case 'pagoda':
		// Server-------------------------------------------------------------------------
		//$config['web_server']	= 'http://biostor.gopagoda.io'; 
		$config['web_server']	= 'http://biostor.org'; 
		$config['site_name']	= 'BioStor';

		// Files--------------------------------------------------------------------------
		$config['web_dir']		= dirname(__FILE__);
		$config['web_root']		= '/';
		
		// Memcache-----------------------------------------------------------------------
		$config['use_memcache']	= false;
		// $config['use_memcache']	= true;
		// $config['memcache_host']= $_SERVER['CACHE1_HOST'];
		// $config['memcache_port']= $_SERVER['CACHE1_PORT'];
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
$config['use_image_proxy']	= true; // if true use local proxy to trigger caching

// Logging--------------------------------------------------------------------------------
// View logging (requires writing to CouchDB and POSTs are expensive)
$config['use_view_counter']	= false; // if true record and display number of article views

// Hypothesis--------------------------------------------------------------------------------
$config['use_hypothesis'] = false; // if true display hypothesis annotation tools

// Ads------------------------------------------------------------------------------------
$config['show_ads']	= false; // if true display Google ads

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
	case 'nanobox-local':
	case 'nanobox':
	case 'pagoda':
	case 'heroku':
		// Cloudant
		$config['couchdb_options'] = array(
				'database' => 'biostor',
				'host' => 'rdmpage:GGu-h5x-dLw-vYTcloudant@rdmpage.cloudant.com',
				'port' => 443,
				'prefix' => 'https://'
				);	


		// access using https://4c577ff8-0f3d-4292-9624-41c1693c433b-bluemix.cloudant.com/dashboard.html#/_all_dbs
		
		// IBM Cloud Cloudant
		$config['couchdb_options'] = array(
				'database' => 'biostor',
				'host' => '4c577ff8-0f3d-4292-9624-41c1693c433b-bluemix:6727bfccd5ac5213a9a05f87e5161c153131af6b2c0f3355fe1aa0fe2f97a35f@4c577ff8-0f3d-4292-9624-41c1693c433b-bluemix.cloudant.com',
				'port' => 443,
				'prefix' => 'https://'
				);	
		
		
		/*
		// Bitnami Google Cloud CouchDB
		// Need to figure out how much resource this will need to be effective
		// very, very slow in indexing, probably not useful without massive resources
		$config['couchdb_options'] = array(
				'database' => 'biostor',
				'host' => 'admin:B3Ov4CFf6Byo@104.155.41.89',
				'port' => 5984,
				'prefix' => 'http://'
				);	
		*/
		break;
		
	case 'local':
		/*
		// local
		$config['couchdb_options'] = array(
				'database' => 'biostor',
				'host' => 'localhost',
				'port' => 5984,
				'prefix' => 'http://'
				);			
		break;
		*/
	default:
		/*
		// Cloudant
		$config['couchdb_options'] = array(
				'database' => 'biostor',
				'host' => 'rdmpage:GGu-h5x-dLw-vYTcloudant@rdmpage.cloudant.com',
				'port' => 5984,
				'prefix' => 'http://'
				);	
		*/
		$config['couchdb_options'] = array(
				'database' => 'biostor',
				'host' => '4c577ff8-0f3d-4292-9624-41c1693c433b-bluemix:6727bfccd5ac5213a9a05f87e5161c153131af6b2c0f3355fe1aa0fe2f97a35f@4c577ff8-0f3d-4292-9624-41c1693c433b-bluemix.cloudant.com',
				'port' => 443,
				'prefix' => 'https://'
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