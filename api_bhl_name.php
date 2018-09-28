<?php

// BHL API to get distribution of names in BHL

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/lib.php');
require_once (dirname(__FILE__) . '/api_utils.php');

//----------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}

//----------------------------------------------------------------------------------------
// http://www.php.net/manual/en/function.str-getcsv.php#95132
function csv_to_array($csv, $delimiter = ',', $enclosure = '"', $escape = '\\', $terminator = "\n") { 
    $r = array(); 
    $rows = explode($terminator,trim($csv)); 
    $names = array_shift($rows); 
    $names = str_getcsv($names,$delimiter,$enclosure,$escape); 
    $nc = count($names); 
    foreach ($rows as $row) { 
        if (trim($row)) { 
            $values = str_getcsv($row,$delimiter,$enclosure,$escape); 
            if (!$values) $values = array_fill(0,$nc,null); 
            $r[] = array_combine($names,$values); 
        } 
    } 
    return $r; 
} 

//----------------------------------------------------------------------------------------

function display_bhl_name($name, $callback = '')
{
	$obj = new stdclass;
	$obj->hits = array();
	$obj->query = $name;
	
	// Add status
	$obj->status = 404;

	$url = 'https://www.biodiversitylibrary.org/Services/NameListDownloadService.ashx?type=c&name=' . urlencode($name) . '&lang=';
		
	$csv = get($url);
	
	$r = csv_to_array($csv);
	
	$hits = array();

	foreach ($r as $row)
	{
		if (is_array($row))
		{
			if (isset($row['Url']))
			{	
				$PageID = preg_replace('/http[s]?:\/\/www.biodiversitylibrary.org\/page\//', '', $row['Url']);
						
				//$id = $row['Title'] . $row['Volume']; 
				$id = $PageID;
				
				if (!isset($hits[$id]))
				{
					$hit = new stdclass;
					$hit->PageID = $PageID;
					$hit->identifiers = array();
					
					$hit->title = $row['Title'];	
					
					
					$info = new stdclass;
					
					// to do:
					//parse_bhl_date($row['Volume'], $info);
					
					if (isset($info->start))
					{
						$hit->year = $info->start;
					}
					else
					{
						$hit->year = $row['Date'];
					}
					
					$identifier = new stdclass;
					$identifier->type = 'bhl';
					$identifier->id = $PageID;
					$hit->identifiers[] = $identifier;
					
		
					$hits[$id] = $hit;
				}
			}
		}
	}
	
	// sort
	$keys = array();
	$years = array();
	foreach ($hits as $k => $hit)
	{
		$keys[] = $k;
		$years[] = $hit->year;
	}

	array_multisort($years, SORT_NUMERIC, $keys);
	
	$obj->hits = array();
	foreach ($keys as $k) {
		$obj->hits[$k] = $hits[$k];
	}		
	
	if (count($obj->hits) > 0)
	{
		$obj->status = 200;
	}
	
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
		if (isset($_GET['name']))
		{
			display_bhl_name($_GET['name'], $callback);
			$handled = true;
		}
	}	

}



main();

?>
