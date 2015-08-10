<?php

require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/couchsimple.php');

// fetch a BHL page image and highlight search terms 

$PageID = $_GET['PageID'];
$term = $_GET['term'];

$couch_id = 'page/' . $PageID;	
$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($couch_id));

$page = json_decode($resp);
if (isset($page->error))
{
	// badness
	$html = 'Oops';
}
else
{
	$imageUrl = 'http://www.biodiversitylibrary.org/pagethumb/' .  $PageID . ',400,400';
	
	if (isset($page->xml))
	{
	
		$xp = new XsltProcessor();
		$xsl = new DomDocument;
		$xsl->load(dirname(__FILE__) . '/djvu2html.xsl');
		$xp->importStylesheet($xsl);	
	
		// Load XML
		$dom= new DOMDocument;
		$dom->loadXML($page->xml);
		$xpath = new DOMXPath($dom);
	
		// Export HTML with background image using XSLT
	
		$xp->setParameter('', 'imageUrl', $imageUrl);
		$xp->setParameter('', 'widthpx', 500);
		$xp->setParameter('', 'term', $term);
	
		$html = $xp->transformToXML($dom);
	}
	else
	{
		$html = '<span style="background-color:orange;color-white;">Warning: no XML!</span><img style="border:1px solid rgb(228,228,228);-webkit-filter: grayscale(100%) contrast(200%);" src="' . $imageUrl . '" width="500" />';
	}
}

//echo $html;

$data = new stdclass;
$data->html = $html;

echo json_encode($data);



?>