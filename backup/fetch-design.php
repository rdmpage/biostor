<?php

// export views from BioNames CouchDB

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/lib.php');


// Databases and views
$views = array(
	'biostor' => array('author', 'bhl', 'biblife', 'citation', 'cleaning', 'count', 'counter', 
	'elastic', 'export', 'filters', 'geo', 'geodd', 'jats', 'journal_articles', 'openurl',
	'pintrest', 'reference'),
);

foreach ($views as $database => $views)
{
	// Folder for each database
	$db_dir = dirname(__FILE__) . "/" . $database;
	if (!file_exists($db_dir))
	{
		$oldumask = umask(0); 
		mkdir($db_dir, 0777);
		umask($oldumask);
	}

	// Get views
	foreach ($views as $view)
	{
		$url = 'http://admin:3h0kylo8ljfp@34.90.120.208:5984/' . $database . '/_design/' . $view;
		$resp = get($url);
	
		file_put_contents($db_dir . '/' . $view . '.js', $resp);
	}
}
		


?>