<?php

date_default_timezone_set('America/Denver');
$run_date = date('Y-m-d H:i:s', time());
echo $run_date . ' parse beginning...'."\n";

ini_set('memory_limit', '512M');

require 'constants.php';
require 'getstate.php';

function msleep($time) {
    usleep($time * 1000000);
}

// A function to purge the final files from Fastly cache
function purgeFTP($file_name_full) {
	$url = 'http://extras.denverpost.com/app/wildfire/combined/' . $file_name_full;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PURGE');
	curl_exec($ch);
	curl_close($ch);
}

function isInColorado($coords) {
	if ($coords[0] > 36.9 && $coords[0] < 41 && $coords[1] > 102 && $coords[1] < 109.05) {
		return true;
	} else {
		return false;
	}
}

/** A bunch of arrays we're going to use to push data into and
 * then later move it elsewhere or output is as json
 **/
$iw_output = array();
$gm_array = array();
$gm_pushed = array('AL' => array(), 'AK' => array(), 'AS' => array(), 'AZ' => array(), 'AR' => array(), 'CA' => array(), 'CO' => array(), 'CT' => array(), 'DE' => array(), 'DC' => array(), 'FM' => array(), 'FL' => array(), 'GA' => array(), 'GU' => array(), 'HI' => array(), 'ID' => array(), 'IL' => array(), 'IN' => array(), 'IA' => array(), 'KS' => array(), 'KY' => array(), 'LA' => array(), 'ME' => array(), 'MH' => array(), 'MD' => array(), 'MA' => array(), 'MI' => array(), 'MN' => array(), 'MS' => array(), 'MO' => array(), 'MT' => array(), 'NE' => array(), 'NV' => array(), 'NH' => array(), 'NJ' => array(), 'NM' => array(), 'NY' => array(), 'NC' => array(), 'ND' => array(), 'MP' => array(), 'OH' => array(), 'OK' => array(), 'OR' => array(), 'PW' => array(), 'PA' => array(), 'PR' => array(), 'RI' => array(), 'SC' => array(), 'SD' => array(), 'TN' => array(), 'TX' => array(), 'UT' => array(), 'VT' => array(), 'VI' => array(), 'VA' => array(), 'WA' => array(), 'WV' => array(), 'WI' => array(), 'WY' => array(), 'AE' => array(), 'AA' => array(), 'AP' => array());
$gm_output = array();
$perim_output = array();
$co_gm_output = array();
$co_perim_output = array();

// Set up the Inciweb feed
$iw_feed = implode(file('https://inciweb.nwcg.gov/feeds/rss/incidents/'));
$iw_feed = preg_replace('~(</?|\s)([a-z0-9_]+):~is', '$1$2_', $iw_feed);
$iw_xml = simplexml_load_string($iw_feed);
$iw_json = json_encode($iw_xml);
$iw_array = json_decode($iw_json, TRUE);

// Get the inciweb feed and parse out only the wildfires
foreach ($iw_array['channel']['item'] as $fire) {
	if (strpos(strtolower($fire['title']), '(wildfire)')) {
		$stripterms = array(' Fire (Wildfire)', ' Complex (Wildfire)', ' Fires (Wildfire)', '(Wildfire)');
		$fire['title'] = trim(str_replace($stripterms, '', $fire['title']));
		$fire['title'] = preg_replace('/\((.+?)\)/', ' ', $fire['title']);
		$fire['title'] = str_replace('  ', ' ', $fire['title']);
		$fire['title'] = trim(str_replace('Fire', '', trim($fire['title'])));
		$fire['state'] = false;
		$coords[0] = round($fire['geo_lat'],6);
		$coords[1] = round($fire['geo_long'],6);
		if (isInColorado($coords)) {
			$fire['state'] = 'CO';
		} else {
			$fire['state'] = getAddress($coords[0], $coords[1]);
			msleep(.25);
		}
		if (!$fire['state']) {
			echo 'Warning: Couldn\'t get state data for fire with name "' . $fire['title'] .'."' ."\n";
		}
		array_push($iw_output, $fire);
	}
}
echo 'FOUND ' . count($iw_output) . ' wildfires in Inciweb feed!' . "\n";

$gm_file_two = implode(file('https://wildfire.cr.usgs.gov/arcgis/rest/services/geomac_dyn/MapServer/1/query?where=&text=&objectIds=&time=2018&geometry=&geometryType=esriGeometryPoint&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=*&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR=&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance=&returnExtentsOnly=false&datumTransformation=&parameterValues=&rangeValues=&f=geojson&__ncforminfo=DMimt_s_f-IEDhoYyhysyIfKhOTs-VdxFSs-7Szu-ukQTRIZ6lvYGF7AaYXs1llK1GIPOdZetx5zRVUYE22A1xv5grxpnngzeac6ct-ExMo_-MJsoteVkGgWw0ssrXR_KkYtnnNh6iUiU2ab4eXzMCLJWUy1cWMD4APf4MtlqEcGdAZGJbrihrydrB8aOMf2xp8102yN6WOno4UPaUW8sdbt_bqr-ZWgn6Gruu48yQ6gbBJbZw_V-V06HQMB9KFxhvdQ_YIJDQtVCxvz5KC7XTb4ox644NMlv_iUpdQhINkxHw7XpIzvGYO09EYWZDL0k-T3z2KbRNUOIaKQ_Mkdo2xGnlGbFats-32BuxnN4D11wBGPLWOf8NP4f1L3FJTgqRS6Jsj1HindzmgNj-f6Njxdzoi2vw8olDlccEZhwLKMN6MYUwVj5k_07szk1ueW5a0QJJCrGZnh-mSVAKR0qtvjCkC-zYYsJccDsg0qziKZOWIB1tEgBccPmzlBnmgoxqWCyytsF_efZpwYE7J6HFGNIZzgJYzPO5oahV5GDZava-jUtyfgqDlHuuVlbdR23Ap_dT-XGOQV9IDImn-NxZrYdY9jsSga3xZX47kjN7VIhZ-oF5iCbzpqHqGUZCJzHyubsnPl7uS3dVaf5FNKQJW0G7oZqlm5Jowyagcxf7giDhouAmmniw%3D%3D'));
$gm_array_two = json_decode($gm_file_two, TRUE);

$gm_file_three = implode(file('https://wildfire.cr.usgs.gov/arcgis/rest/services/geomac_dyn/MapServer/7/query?where=&text=&objectIds=&time=2019&geometry=&geometryType=esriGeometryPoint&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=*&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR=&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance=&returnExtentsOnly=false&datumTransformation=&parameterValues=&rangeValues=&f=geojson&__ncforminfo=2siCWbmYOGV2hoCX8gQzatl0CoRd4xrgYe1UaQaTmXPTPnioGfWrRKBqFChpGTLAC8F7l8gLqMmKle7wfkjO5Xvf8iPJVkFSXGyM3F_3oSed4tj9r8LLDNhBXds4Sok8FbF-Xt4JsBiIxaueR_q_CctVdcFL6tVeili3L9b2706iHKJHoLEfEaUKaRCD8dIxpWNuzCRn71ZSnEKT7B3rHpVCvxeOU0EzUFdWo49fF4qNcXZ_qkYLsRfbTYb974mpidXtrMCzuNfcX_0MpYv7xmmAZiyAuQlIjcjsOJF6z5JwgSBkPYu0a0LXzh1pvyLPuNXs0a8ngC2qzS1GvHLIbnalEfykoMXzvwmrLTUR-rk0xWM1fjuSsAE7dy6YQUKKucaRfGsgGjaMzgkmjSB1ZmqB60UF9AnMQ2SmPVB6SOusWsLW37HZOaLpDDIEg6Jpm2Ra4XHP9mYANodqTQgvXxpTHPEpbOIKwzZ3wy02FlT-ha3vsHj3A-sTTJ-a03l07_x0MWOe41kLD6iEBEWIMUPQq-m2WP9u0yHTE8mt_nrO3FrDSTwmbM0TaXd_O8DPMKi-oftdqvcDGiKS58O26tozrIJC6kN3cqdrHv9o3h4QJK1p7iQgBruS0o_oYQWM-CSM8a41BLwyj4VHvRvfO-n-EojcZ_rIBbepnaCysnJB-y4m0HhKig%3D%3D'));
$gm_array_three = json_decode($gm_file_three, TRUE);

// GeoMAC data elements we don't need
$junk_keys = array('objectid', 'latitude', 'longitude', 'hotlink', 'status', 'pooresponsibleunit', 'pooprotectingagency', 'pooownerunit', 'poolandownercategory', 'complexparentirwinid', 'irwinid', 'irwinmodifiedon', 'mapsymbol', 'datecurrent', 'localincidentidentifier', 'incidenttypecategory', 'owneragency', 'perimexist', 'complexname', 'iscomplex', 'uniquefireidentifier', 'invalid', 'mergeid');

/**
 * iterate through each of the three feeds' arrays unsetting the stuff we
 * don't need and adding the fire names to an array that we can use
 * later to compare to the names of fires in the inciweb data we gathered,
 * and pushing the rest into one unified array
 */
foreach ($gm_array_two['features'] as $gm_feature) {
	$gm_feature['properties']['is_active'] = true;
    $gm_feature['properties']['incidentname'] = trim(ucwords(strtolower($gm_feature['properties']['incidentname'])));
	$name = strtolower(trim($gm_feature['properties']['incidentname']));
	$state = $gm_feature['properties']['state'];
	if (!isset($gm_pushed[$state])) {
		$state_arr = array($state);
		array_push($state_arr, $gm_pushed);
	}
	if (!in_array($name, $gm_pushed[$state])) {
		$gm_pushed[$state][] = $name;
		foreach ($junk_keys as $junk_key) {
			unset($gm_feature['properties'][$junk_key]);
		}
		array_push($gm_array, $gm_feature);
	}
}
foreach ($gm_array_three['features'] as $gm_feature) {
	$gm_feature['properties']['is_active'] = false;
    $gm_feature['properties']['incidentname'] = trim(ucwords(strtolower($gm_feature['properties']['incidentname'])));
	$name = strtolower(trim($gm_feature['properties']['incidentname']));
	$state = ($gm_feature['properties']['state'] == 'TA') ? 'CA' : $gm_feature['properties']['state'];
	if (!isset($gm_pushed[$state])) {
		$state_arr = array($state);
		array_push($state_arr, $gm_pushed);
	}
	if (!in_array($name, $gm_pushed[$state])) {
		$gm_pushed[$state][] = $name;
		foreach ($junk_keys as $junk_key) {
			unset($gm_feature['properties'][$junk_key]);
		}
		array_push($gm_array, $gm_feature);
	}
}

/**
 * Here's where we go through the unified GeoMAC array and compare
 * the incident names we stored in $gm_pushed to the names in
 * the InciWeb data and, if they are substantially similar,
 * append the InciWeb link and description to the GeoMAC data
 */
$count_two = 0;
foreach ($gm_array as $gm_feature) {
	$state = ($gm_feature['properties']['state'] == 'TA') ? 'CA' : $gm_feature['properties']['state'];
	foreach ($iw_output as $iw_item) {
		// CALCULATE SIMILARITY OF NAME
		$sim = similar_text(strtolower(trim($gm_feature['properties']['incidentname'])), strtolower(trim($iw_item['title'])), $perc);
		// IF NAME AND STATE ARE THE SAME
		if ((int)$perc > 90 && $iw_item['state'] == $state) {
			$gm_feature['properties']['iw_link'] = $iw_item['link'];
			if (isset($iw_item['description'])) {
				$gm_feature['properties']['iw_description'] = trim($iw_item['description']);
				$count_two++;
			}
		}
	}
	if ($state == 'CO') {
		// PUSH ONLY CO FIRES TO CO LIST
		array_push($co_gm_output, $gm_feature);	
	}
	array_push($gm_output, $gm_feature);
}
echo 'ADDED ' . $count_two . ' Inciweb links to GeoMAC data.' . "\n";

// Set up and fetch the GeoMAC perimeter data feed
$perim_raw_file = implode(file('https://wildfire.cr.usgs.gov/arcgis/rest/services/geomac_dyn/MapServer/3/query?where=&text=&objectIds=&time=2019&geometry=&geometryType=esriGeometryPolyline&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=*&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR=&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance=&returnExtentsOnly=false&datumTransformation=&parameterValues=&rangeValues=&f=geojson&__ncforminfo=ADoQy8hDNj1izqKKnL_QV6WaxMTRcF4t-BohZFBOuGziMyw1OzuPxATIcnd-fsjGPNBNJf_b9NsTZfuxhuPqBVBBBdbEBDcAQnHWvqMirgx0KVKe_oyaNaslO8aU3DdODGlIAIWhJ2I9p8397Wy4h5t1uJyjpjpefpZ2qMgMtDyp19cFndGiJxTbLiP6MsH75t1gandz0d-URayze6GE5tnJc2-ymQVhbdtvJjxvQCwBTsbR1T9WC66BPrHY8nw44Dt_yia7u-7SKXNnKR8q8OPNO-tlG4X6Vx1IMTG6Z06eOyX5XDMK4p19ZE3LUI34_E0mLpnv1dxcM6IKSzmnvSEcxo3mV8KAjoLoUHASjb48y40teA0Vkt9AzSp0-HEZBa5yNaItELmP5TIJLHou_6GdvJdSzmasgqi7OED38CFHwqnitRVzbPC4ENi2nIV2uGDYu8NyMjpZi1CVFKcM9nEfjIRA4LUMxMk0-D3LwVKxOb3SR08LjKPI2fk0WZsaeI4jigzC5uFqf2SGchNMD2rJ9vn8uQyq8fJFHyMjVblKte1OXzGhtyhRSU25uua2k-l_ptlpSRPF2z0vY5oSGZX9EFrRpMn7Qty1ld_NeT0JwN9wS8MfiKcTfsxwTtLl_HWb9Lz08sG5v3fVSi43JUt_wnd-r7Oa8ebLJJ4Rh9cASSt9vxKjNQ%3D%3D'));
$perim_raw = json_decode($perim_raw_file, TRUE);

if ($perim_raw) {
	echo 'Starting permiter parse...'."\n";
}

// tolerance and include for the RDP simplification
require 'douglas-peucker.php';
$tolerance = .0003;

// GeoMAC perimiter data elements we don't need
$junk_props = array('objectid', 'agency', 'comments', 'mapmethod', 'uniquefireidentifier', 'pooownerunit', 'complexname', 'firecode', 'complexparentirwinid', 'pooresponsibleunit', 'localincidentidentifier', 'irwinid', 'incomplex', 'complexfirecode', 'mergeid', 'st_area(shape)', 'st_length(shape)');

/**
 * Go through each entry in the GeoMAC perimeter data file,
 * removing properties we don't need, formatting the InciWeb ID
 * into an actual URL, and simplifying the GeoJSON coordinates
 * using the Ramer-Douglas-Peucker formula (adjust tolerance above),
 * and then pushing the results into a new array we'll use to write
 * a finished file to cache and FTP the results to Extras
 */
foreach ($perim_raw['features'] as $perim_feature) {
	foreach ($junk_props as $junk_prop) {
		unset($perim_feature['properties'][$junk_prop]);
	}
	// MAKE AN INCIWEB LINK FROM THE INCIWEB ID IF PROVIDED
	if (isset($perim_feature['properties']['inciwebid']) && is_int($perim_feature['properties']['inciwebid'])) {
		$perim_feature['properties']['inciwebid'] = 'http://inciweb.nwcg.org/incident/' . $perim_feature['properties']['inciwebid'] . '/';
	}
	if ($perim_feature['geometry']['type'] == 'Polygon') {
		// SIMPLIFY THE GEOJSON A LITTLE
		$i = 0;
		foreach ($perim_feature['geometry']['coordinates'] as $coords) {
			$tolerance = ($perim_feature['properties']['state'] == 'CO') ? $tolerance : .0005;
			$perim_feature['geometry']['coordinates'][$i] = simplify_RDP($coords, $tolerance);
			$i++;
		}
		unset($i);
	}
	foreach ($gm_output as $key => $value) {
		$sim = similar_text(strtolower(trim($value['properties']['incidentname'])), strtolower(trim($perim_feature['properties']['incidentname'])), $perc);
		// IF NAME AND STATE ARE THE SAME
		if ((int)$perc > 90 && $perim_feature['properties']['state'] == $value['properties']['state']) {
			if ($value['properties']['reportdatetime'] < $perim_feature['properties']['perimeterdatetime']) {
				$gm_output[$key]['properties']['acres'] = (round($perim_feature['properties']['gisacres'],0) > round($value['properties']['acres'],0)) ? round($perim_feature['properties']['gisacres'],0) : $value['properties']['acres'];
			}
		}
	}
	if ($perim_feature['properties']['state'] == 'CO') {
		// PUSH ONLY CO FIRE TO CO-ONLY DATA
		array_push($co_perim_output, $perim_feature);
	}
	array_push($perim_output, $perim_feature);
}

/**
 * Encode the wildfires' combined data array into json,
 * store it as a file (making a backup of the old file if
 * it exists), and then FTP it to Extras
 */
$output_all = json_encode($gm_output);
$output_all_file = 'wildfires-combined-all.json';
if (file_get_contents('./cache/' . $output_all_file)) {
	file_put_contents('./cache/wildfires-combined-all.json.old', file_get_contents('./cache/' . $output_all_file));
}
file_put_contents('./cache/wildfires-combined-all.json', $output_all);

$co_output_all = json_encode($co_gm_output);
$co_output_all_file = 'wildfires-combined-all-co.json';
if (file_get_contents('./cache/' . $co_output_all_file)) {
	file_put_contents('./cache/wildfires-combined-all-co.json.old', file_get_contents('./cache/' . $co_output_all_file));
}
file_put_contents('./cache/wildfires-combined-all-co.json', $co_output_all);

/**
 * Encode the processed perimeter data array into json,
 * store it as a file (making a backup of the old file if
 * it exists), and then FTP it to Extras
 */
$perim_all = json_encode($perim_output);
$perim_all_file = 'wildfires-combined-perims.json';
if (file_get_contents('./cache/' . $perim_all_file)) {
	file_put_contents('./cache/wildfires-combined-perims.json.old', file_get_contents('./cache/' . $perim_all_file));
}
file_put_contents('./cache/wildfires-combined-perims.json', $perim_all);

$co_perim_all = json_encode($co_perim_output);
$co_perim_all_file = 'wildfires-combined-perims-co.json';
if (file_get_contents('./cache/' . $co_perim_all_file)) {
	file_put_contents('./cache/wildfires-combined-perims-co.json.old', file_get_contents('./cache/' . $co_perim_all_file));
}
file_put_contents('./cache/wildfires-combined-perims-co.json', $co_perim_all);

// Set up FTP connection
$conn_id = ftp_connect($FTP_SERVER) or die("Couldn't connect to $FTP_SERVER");
$ftp_logged_in = ftp_login($conn_id, $FTP_USER_NAME, $FTP_USER_PASS);
ftp_pasv($conn_id, TRUE);

// FTP the combined data
$ftp_uploaded = ftp_put($conn_id, $FTP_DIRECTORY . '/' . $output_all_file, './cache/wildfires-combined-all.json', FTP_ASCII);
msleep(.25);
if ($ftp_uploaded) {
	$error_out = 'Fire data file uploaded!';
	purgeFTP($output_all_file);
} else {
	$error_out = 'An oops has occurred...';
}
echo $error_out."\n";

// FTP the CO combined data
$co_ftp_uploaded = ftp_put($conn_id, $FTP_DIRECTORY . '/' . $co_output_all_file, './cache/wildfires-combined-all-co.json', FTP_ASCII);
msleep(.25);
if ($co_ftp_uploaded) {
	$co_error_out = 'Colorado fire data file uploaded!';
	purgeFTP($co_output_all_file);
} else {
	$co_error_out = 'An oops has occurred...';
}
echo $co_error_out."\n";

// FTP the perimeter data
$perim_uploaded = ftp_put($conn_id, $FTP_DIRECTORY . '/' . $perim_all_file, './cache/wildfires-combined-perims.json', FTP_ASCII);
msleep(.25);
if ($perim_uploaded) {
	$perim_error_out = 'Fire perimeter file uploaded!';
	purgeFTP($perim_all_file);
} else {
	$perim_error_out = 'An oops has occurred...';
}
echo $perim_error_out."\n";

// FTP the CO perimeter data
$co_perim_uploaded = ftp_put($conn_id, $FTP_DIRECTORY . '/' . $co_perim_all_file, './cache/wildfires-combined-perims-co.json', FTP_ASCII);
msleep(.25);
if ($co_perim_uploaded) {
	$co_perim_error_out = 'Colorado fire perimeter file uploaded!';
	purgeFTP($co_perim_all_file);
} else {
	$co_perim_error_out = 'An oops has occurred...';
}
echo $co_perim_error_out."\n";

//close the FTP connection
ftp_close($conn_id);

$run_date = date('Y-m-d H:i:s', time());
echo $run_date . ' parse completed!'."\n";
