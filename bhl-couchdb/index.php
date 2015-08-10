<?php

require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/couchsimple.php');



$q = 'Serinus mozambicus';

if (isset($_GET['q']))
{
	$q = $_GET['q'];
}

$parameters = array(
		'q'					=> 'text:"' . $q . '"',
		'highlight_fields' 	=> '["text"]',
		'highlight_pre_tag' => '"<span style=\"color:white;background-color:green;\">"',
		'highlight_post_tag'=> '"</span>"',
		'highlight_number'	=> 5,
		'include_docs' 		=> 'true',
		'limit' 			=> 10,
		
		'group_field'		=> 'ItemID',
		'counts'			=> '["ItemID"]'
	);
	
if ($bookmark != '')
{
	$parameters['bookmark'] = $bookmark;
}
			
$url = '/_design/search/_search/pages?' . http_build_query($parameters);

$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
$obj = json_decode($resp);

//print_r($obj);

	// Display...
	echo 
'<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
		
		<script>
					
			function show_bhl(PageID, term)
			{
				$("#details").html("");
				$.getJSON("display_page.php?PageID=" + PageID + "&term=" + term,
					function(data){
						var html = data.html;
						$("#details").html(html);
					}
					
				);	
			}
		</script>
	</head>
	<body style="font-family:sans-serif">';
	
echo '<div>';
echo '<form >
  <input style="font-size:24px;" name="q" placeholder="Search term" value="' . $q . '" >
  <input style="font-size:24px;" type="submit" value="Search">
</form>';
echo '</div>';

echo '<div style="position:relative">';
echo '<div style="width:600px;line-height:1.2em;">'; // border:1px solid rgb(128,128,128);
echo '<ol>';
foreach ($obj->groups as $group)
{
	echo '<li>';
	echo $group->by;
	
	echo '<ul>';
	foreach ($group->rows as $row)
	{
		echo '<li style="font-size:12px;color:green;">';
		//echo '<a href="http://biodiversitylibrary.org/' . $row->id . '" target="_new">' . $row->id . '</a><br/>';
		//echo '<a href="display_page.php?PageID=' . str_replace('page/', '', $row->id) . '&term=' . strtolower($q) . '" target="_new">' . $row->id . '</a><br/>';
		
		echo '<span style="background-color:blue;color:white;" onclick="show_bhl(\'' . str_replace('page/', '', $row->id) . '\',\'' . $q . '\');">';		
		echo $row->id . ' [click to view page]';
		echo '</span>';
		
		echo $row->doc->PageNumbers[0]->Number;
		//echo '<img src="' . $row->doc->ThumbnailUrl . '" width="100" />';
		foreach ($row->highlights->text as $highlight)
		{
			echo $highlight . '<br />';
		}
		echo '</li>';
	}
	echo '</ul>';
	
	echo '</li>';
	
}
echo '</ol>';
echo '</div>';
	echo '<div style="font-size:12px;position:absolute;top:0px;left:600px;width:auto;padding-left:10px;">';
	echo '<p style="padding:0px;margin:0px;" id="details"></p>';
	echo '</div>';
	
	echo '</div>';
	echo
'	</body>
</html>';

?>
