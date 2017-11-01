<?php

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/lib.php');

$debug = false;

$data = array();
$queries = array();

if (isset($_GET['q']))
{
	$q = trim($_GET['q']);

	if (preg_match('/[A-Z]\w+(\s+\w+)?(,\s+[A-Z]\w+(\s+\w+)?)?/u', $q))
	{
		$queries = preg_split('/\s*,\s*/u', $q);
	}
}

//$queries = array('Aspidoscelis costata', 'Cnemidophorus costatus');
//$queries = array('Aspidoscelis', 'Cnemidophorus');
//$queries = array('Physeter macrocephalus', 'Physeter catodon');
//$queries = array('Serinus mozambicus');
//$queries=array('Asclepias engelmanniana');
//$queries=array('Aerodramus', 'Collocalia');


foreach ($queries as $q)
{
	$url = 'http://direct.biostor.org/bhlapi_names.php?q=' . urlencode($q);
	
	$json = get($url);
	if ($json != '')
	{
		$data[] = json_decode($json);
	}
}

//print_r($data);

$have_hits = true;

if (count($data) == 0)
{
	$have_hits = false;
}
else
{
	$hit_count = 0;
	foreach ($data as $d)
	{
		$hit_count += count($d->hits);
	}
	if ($hit_count == 0)
	{
		$have_hits = false;
	}	
}

// Any hits?
if (!$have_hits)
{
	// Nope
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1">-->
    <!-- base -->
    <base href="<?php echo $config['web_root']; ?>" /><!--[if IE]></base><![endif]-->
    <!-- favicon -->
	<link href="static/biostor-shadow32x32.png" rel="icon" type="image/png">    
    <!-- Boostrap -->
    <!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
	<!-- Latest compiled and minified JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

	<title>BHL timeline</title>
	</head>
<body>
	<div class="container-fluid">
	<div class="row">
	<div class="col-md-12">
  	<h1>BHL timeline</h1>
  	
<form class="form-inline">
  <input type="text" placeholder="scientific name, scientific name" name="q" style="width:300px;" value="<?php echo join(", ", $queries); ?>">
  <button type="submit" class="btn">Search BHL</button>
  <span class="help-block">Enter one or more scientific names, separated by commas.</span>
</form> 

<div class="alert alert-error">
<strong>No hits!</strong> Couldn't find names in BHL, please try another search.
</div>

</div>
</div>
</div>

</body>
</html> 	

<?php
	exit();
}	

$years = array();

$n = count($data);
for ($i = 0; $i < $n; $i++)
{
	foreach ($data[$i]->hits as $k => $v)
	{
		$hit = new stdclass;
	
		if (isset($v->year))
		{
			if (preg_match('/(?<year>[0-9]{4})/', $v->year, $m))
			{
				$hit->year = $m['year'];
			}
		
			// clean
		
		}
	
		if (isset($v->title))
		{
			$hit->title = $v->title;
		}

		if (isset($v->PageID))
		{
			$hit->PageID = $v->PageID;
		}

		if (isset($v->biostor))
		{
			$hit->biostor = $v->biostor;
		}
	
		if (isset($hit->year))
		{
			if (!isset($years[$hit->year]))
			{
				$years[$hit->year] = array();
			}
			if (!isset($years[$hit->year][$i]))
			{
				$years[$hit->year][$i] = array();
			}
			
			$years[$hit->year][$i][] = $hit;
		}
	}
}

if ($debug)
{
	echo '<pre>';
	print_r($years);
	echo '</pre>';	
}
	
$xy = array();

foreach ($years as $year => $hits)
{
	for ($j = 0; $j < $n; $j++)
	{
		if (isset($hits[$j]))
		{
			$xy[$year][$j] = count($hits[$j]);
		}
		else
		{
			$xy[$year][$j] = 0;
		}
	}
}

$sorted_years = array_keys($xy);
sort($sorted_years);

if ($debug)
{
	echo '<pre>';
	print_r($xy);
	echo '</pre>';
}

$headings = array();
$headings[] = '"Year"';
foreach ($data as $d)
{
	$headings[] = '"' . addcslashes($d->query, '"') . '"';	
}

$str = '[' . join(',', $headings) . ']';

$min = $sorted_years[0];

$min = max($min, 1758);

$max = $sorted_years[count($sorted_years) - 1];

//foreach ($sorted_years as $year)
for ($year = $min; $year <= $max; $year++)
{
	$value = array();
	if (isset($xy[$year]))
	{
		$value = $xy[$year];
	}
	else
	{
		for ($j = 0; $j < $n; $j++)
		{
			$value[$j] = 0;
		}
	}
	$str .= ",\n" . '["' . $year . '",' . join(",", $value) . ']';
}



// group by year

// google chart

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1">-->
    <!-- base -->
    <base href="<?php echo $config['web_root']; ?>" /><!--[if IE]></base><![endif]-->
    <!-- favicon -->
	<link href="static/biostor-shadow32x32.png" rel="icon" type="image/png">    
    <!-- Boostrap -->
    <!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
	<!-- Latest compiled and minified JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

	<title>BHL timeline</title>
	
	<style>
	/* Zenodo-style DOI */
	span.doi {
		font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
		color: white;
		background: black;
		font-size: 13px;
		text-decoration: none;
		padding: 2px 0px 2px 4px;
		border-radius: 5px;
		border-color: black;
	}

	span.doi:before {
		content: "DOI";
	}

	span.doi a {
		color: white;
		background: #0099cc;
		text-decoration: none;
		text-transform: lowercase;
		margin-left: 4px;
		padding: 2px 5px 2px 4px;
		border-radius: 0px 5px 5px 0px;
	}
	
	/* ZooBank-style LSID */
	span.lsid {
		font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
		color: white;
		background: rgb(255,113,72);
		font-size: 13px;
		text-decoration: none;
		padding: 2px 0px 2px 4px;
		border-radius: 5px;
		border-color: black;
	}

	span.lsid:before {
		content: "LSID";
	}

	span.lsid a {
		color: black;
		background: #f5f5f5; /* white disappears */
		text-decoration: none;
		text-transform: lowercase;
		margin-left: 4px;
		padding: 2px 5px 2px 4px;
		border-radius: 0px 5px 5px 0px;
	}
	
	/* JSTOR */
	span.jstor {
		font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
		color: white;
		background: rgb(149,38,55);
		font-size: 13px;
		text-decoration: none;
		padding: 2px 0px 2px 4px;
		border-radius: 5px;
		border-color: black;
	}

	span.jstor:before {
		content: "JSTOR";
	}

	span.jstor a {
		color: black;
		background: #f5f5f5; /* white disappears */
		text-decoration: none;
		text-transform: lowercase;
		margin-left: 4px;
		padding: 2px 5px 2px 4px;
		border-radius: 0px 5px 5px 0px;
	}
	
	
	/* BHL */
	span.bhl {
		font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
		color: white;
		background: rgb(60,150,200);
		font-size: 13px;
		text-decoration: none;
		padding: 2px 0px 2px 4px;
		border-radius: 5px;
		border-color: black;
	}

	span.bhl:before {
		content: "BHL";
	}

	span.bhl a {
		color: black;
		background: #f5f5f5; /* white disappears */
		text-decoration: none;
		text-transform: lowercase;
		margin-left: 4px;
		padding: 2px 5px 2px 4px;
		border-radius: 0px 5px 5px 0px;
	}

	/* BioStor */
	span.biostor {
		font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
		color: black;
		background: rgb(240,230,200);
		font-size: 13px;
		text-decoration: none;
		padding: 2px 0px 2px 4px;
		border-radius: 5px;
		border-color: black;
	}

	span.biostor:before {
		content: "BioStor";
	}

	span.biostor a {
		color: black;
		background: #f5f5f5; /* white disappears */
		text-decoration: none;
		text-transform: lowercase;
		margin-left: 4px;
		padding: 2px 5px 2px 4px;
		border-radius: 0px 5px 5px 0px;
	}
	
	
	</style>	
	
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);
      
      
function drawChart() {
        var data = google.visualization.arrayToDataTable([
          <?php echo $str; ?>
        ]);

        var options = {
          legend: { position: "bottom" },
 hAxis: {
          title: "Year"
        },
        vAxis: {
          title: "Number of mentions"
        } 
        };

        var chart = new google.visualization.LineChart(document.getElementById("chart"));

        chart.draw(data, options);
        
google.visualization.events.addListener(chart, "select", 
        					function() {
        						var selection = chart.getSelection();
        						var item = selection[0];
        						location.hash = "#" + data.getFormattedValue(item.row,0);
        						//alert(JSON.stringify(data.getFormattedValue(item.row,0)));
  								
  							});        
        
        
      }      
      
    </script>	
	
	</head>
<body>
	<div class="container-fluid">
	<div class="row">
	<div class="col-md-12">
  	<h1>BHL timeline</h1>
  	
<form class="form-inline">
  <input type="text" placeholder="scientific name, scientific name" name="q" style="width:300px;" value="<?php echo join(", ", $queries); ?>">
  <button type="submit" class="btn">Search BHL</button>
  <span class="help-block">Enter one or more scientific names, separated by commas.</span>
</form>  	
  
  
  
  <div id="chart" style="width: 100%; height: 400px"></div>
  
  
  <p>List of BHL pages and/or BioStor articles with OCR text that containing the query term.</p>
  
<?php
  
  // dump list of references
  	//echo '<div style="height:300px;border:1px solid red;overflow:auto;">';
  	
  	
  	
  	echo '<div style="padding:20px;">';

  
  
  //foreach ($sorted_years as $year)
for ($year = $min; $year <= $max; $year++)
{
	echo '<div>';
	if (isset($years[$year]))
	{
		echo '<h4><a name="' . $year . '" />' . $year . '</h4>';
		//echo '<ul>';
		for ($j = 0; $j < $n; $j++)
		{
			if (isset($years[$year][$j]))
			{
				$pages = array();
				foreach ($years[$year][$j] as $hit)
				{
					if (!in_array($hit->PageID, $pages))
					{
  						echo '<div class="media" style="padding-bottom:5px;">';
  						echo '  <div class="media-left media-top" style="padding:10px;">';
					    echo '  <a href="https://biodiversitylibrary.org/page/' . $hit->PageID . '" target="_new">';
    					echo '  <img style="box-shadow:2px 2px 2px #ccc;width:32px;" src="https://www.biodiversitylibrary.org/pagethumb/' .  $hit->PageID . ',60,60" />';	
  						echo '  </a>';
  						echo '  </div>';
  						echo '  <div class="media-body" style="padding:10px;">';
    					echo '     <h4 class="media-heading">';
    					echo $hit->title;
    					echo '</h4>';
    					echo ' <span class="bhl"><a href="https://biodiversitylibrary.org/page/' . $hit->PageID . '" target="_new">' . $hit->PageID . '</a></span>';
						if (isset($hit->biostor))
						{
							echo '&nbsp;<span class="biostor"><a href="' . $config['web_server'] . $config['web_root'] . 'reference/' . $hit->biostor . '" target="_new">' . $hit->biostor . '</a></span>';							
						}

    					echo  '  </div>';
    					echo  '</div>';
											
						$pages[] = $hit->PageID;
					}
				}
			}
		}
		echo '</ul>';


	}
	
	echo '</div>';
}
?>

</div>

</div>
</div>
</div>

</body>
</html>
