<?php

$coordinates = array();

if (isset($_GET['coordinates']))
{
	$coordinates = json_decode($_GET['coordinates']);
}

if (isset($_POST['coordinates']))
{
	$coordinates = json_decode($_POST['coordinates']);
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns:xlink="http://www.w3.org/1999/xlink" 
xmlns="http://www.w3.org/2000/svg" 
width="360px" height="180px">
   <style type="text/css">
      <![CDATA[     
      .region 
      { 
        fill:blue; 
        opacity:0.4; 
        stroke:blue;
      }
      ]]>
   </style>
<!--  <rect id="dot" x="-3" y="-3" width="6" height="6" style="stroke:black; stroke-width:1; fill:white"/> -->
<!--  <rect id="dot" x="-1" y="-1" width="3" height="3" style="stroke:none; stroke-width:0; fill:yellow;opacity:0.7;"/> -->
 <!-- <rect id="dot" x="-10" y="-1" width="2" height="2" style="stroke:none; stroke-width:0; fill:black"/> -->

<circle id="dot" x="-2" y="-2" r="2" style="stroke:none; stroke-width:0; fill:black; opacity:0.7;"/>


 <image x="0" y="0" width="360" height="180" xlink:href="' . 'static/map.jpg"/>

 <g transform="translate(180,90) scale(1,-1)">';
 

foreach ($coordinates as $loc)
{
	$xml .= '   <use xlink:href="#dot" transform="translate(' . $loc[0] . ',' . $loc[1] . ')" />';
}

$xml .= '
      </g>
	</svg>';
	
	
header("Content-type: image/svg+xml");

echo $xml;

?>
