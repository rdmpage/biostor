<?php

// Wrapper around Cloudant geo API

require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');

require_once (dirname(__FILE__) . '/api_utils.php');
require_once (dirname(__FILE__) . '/elastic.php');

//--------------------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}


//--------------------------------------------------------------------------------------------------
function display_wkt($wkt, $limit = 200,  $callback = '')
{
	global $config;
	global $couch;
	
	$url = $config['couchdb_options']['prefix']
		. $config['couchdb_options']['host']
		.  '/' . $config['couchdb_options']['database']
		. '/_design/geodd/_geo/points?g=' . urlencode($wkt) 
		. '&relation=intersects'
		. '&include_docs=true'
		. '&limit=' . $limit;
		
	//echo $url;

	$json = get($url);
	$obj = json_decode($json);
	$obj->status = 200;
	
	// trim excess?
		

	
	api_output($obj, $callback);
}

//--------------------------------------------------------------------------------------------------
function display_elastic_geo($geojson, $limit=50, $callback='')
{
	global $config;
	global $elastic;
	
	$query_json = '{
	"size" : <LIMIT>,
    "query": {        
        "bool" : {
            "must" : {
                "match_all" : {}
            },
            "filter" : {
                "geo_shape" : {
                    "search_data.geometry" : {
                        "shape": <SHAPE>
                    }
                }
            }
        }
    }
}';

	$query_json = str_replace('<LIMIT>', $limit, $query_json);

	$geojson_obj = json_decode($geojson);	
	$query_json = str_replace('<SHAPE>', json_encode($geojson_obj->geometry), $query_json);
	
	//echo $query_json ;
	
	$resp = $elastic->send('POST', '_search?pretty', $post_data = $query_json);
	
	$obj = json_decode($resp);

	// Add status
	$obj->status = 200;
	
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
		if (isset($_GET['wkt']))
		{
			display_wkt($_GET['wkt'], 100, $callback);
			$handled = true;
		}
		
		if (isset($_GET['geojson']))
		{
			display_elastic_geo($_GET['geojson'], 50, $callback);
			$handled = true;
		}
		
		
	}	
	
}



main();

?>
