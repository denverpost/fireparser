<?php

require 'constants.php';
require 'getstate.php';

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

/** A bunch of arrays we're going to use to push data into and
 * then later move it elsewhere or output is as json
 **/
$iw_output = array();
$gm_array = array();
$gm_pushed = array('AL' => array(), 'AK' => array(), 'AS' => array(), 'AZ' => array(), 'AR' => array(), 'CA' => array(), 'CO' => array(), 'CT' => array(), 'DE' => array(), 'DC' => array(), 'FM' => array(), 'FL' => array(), 'GA' => array(), 'GU' => array(), 'HI' => array(), 'ID' => array(), 'IL' => array(), 'IN' => array(), 'IA' => array(), 'KS' => array(), 'KY' => array(), 'LA' => array(), 'ME' => array(), 'MH' => array(), 'MD' => array(), 'MA' => array(), 'MI' => array(), 'MN' => array(), 'MS' => array(), 'MO' => array(), 'MT' => array(), 'NE' => array(), 'NV' => array(), 'NH' => array(), 'NJ' => array(), 'NM' => array(), 'NY' => array(), 'NC' => array(), 'ND' => array(), 'MP' => array(), 'OH' => array(), 'OK' => array(), 'OR' => array(), 'PW' => array(), 'PA' => array(), 'PR' => array(), 'RI' => array(), 'SC' => array(), 'SD' => array(), 'TN' => array(), 'TX' => array(), 'UT' => array(), 'VT' => array(), 'VI' => array(), 'VA' => array(), 'WA' => array(), 'WV' => array(), 'WI' => array(), 'WY' => array(), 'AE' => array(), 'AA' => array(), 'AP' => array());
$gm_output = array();
$perim_output = array();

// Set up the Inciweb feed
$iw_feed = implode(file('https://inciweb.nwcg.gov/feeds/rss/incidents/'));
$iw_feed = preg_replace('~(</?|\s)([a-z0-9_]+):~is', '$1$2_', $iw_feed);
$iw_xml = simplexml_load_string($iw_feed);
$iw_json = json_encode($iw_xml);
$iw_array = json_decode($iw_json, TRUE);

// Get the inciweb feed and parse out only the wildfires
foreach ($iw_array['channel']['item'] as $fire) {
	//echo $fire['title']."\n";
	if (strpos(strtolower($fire['title']), '(wildfire)')) {
		$stripterms = array(' Fire (Wildfire)', ' Complex (Wildfire)', ' Fires (Wildfire)');
		$fire['title'] = trim(str_replace($stripterms, '', $fire['title']));
		$fire['title'] = str_replace('  ', ' ', $fire['title']);
		$stripterms = array(' Fire (Wildfire)', ' Complex (Wildfire)', ' Fires (Wildfire)');
		$fire['title'] = trim(str_replace($stripterms, '', $fire['title']));
		$coords = array_map('trim', explode(' ', $fire['georss_point']));
		$fire['state'] = getAddress($coords[0], $coords[1]);
		array_push($iw_output, $fire);
	}
}
echo 'INITIAL from INCIWEB: ' . count($iw_array['channel']['item']) . "\n";
echo 'FOUND (Wildfire)s: ' . count($iw_output) . "\n";

$gm_file_two = implode(file('https://wildfire.cr.usgs.gov/arcgis/rest/services/geomac_dyn/MapServer/1/query?where=&text=&objectIds=&time=2018&geometry=&geometryType=esriGeometryPoint&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=*&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR=&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance=&returnExtentsOnly=false&datumTransformation=&parameterValues=&rangeValues=&f=geojson&__ncforminfo=DMimt_s_f-IEDhoYyhysyIfKhOTs-VdxFSs-7Szu-ukQTRIZ6lvYGF7AaYXs1llK1GIPOdZetx5zRVUYE22A1xv5grxpnngzeac6ct-ExMo_-MJsoteVkGgWw0ssrXR_KkYtnnNh6iUiU2ab4eXzMCLJWUy1cWMD4APf4MtlqEcGdAZGJbrihrydrB8aOMf2xp8102yN6WOno4UPaUW8sdbt_bqr-ZWgn6Gruu48yQ6gbBJbZw_V-V06HQMB9KFxhvdQ_YIJDQtVCxvz5KC7XTb4ox644NMlv_iUpdQhINkxHw7XpIzvGYO09EYWZDL0k-T3z2KbRNUOIaKQ_Mkdo2xGnlGbFats-32BuxnN4D11wBGPLWOf8NP4f1L3FJTgqRS6Jsj1HindzmgNj-f6Njxdzoi2vw8olDlccEZhwLKMN6MYUwVj5k_07szk1ueW5a0QJJCrGZnh-mSVAKR0qtvjCkC-zYYsJccDsg0qziKZOWIB1tEgBccPmzlBnmgoxqWCyytsF_efZpwYE7J6HFGNIZzgJYzPO5oahV5GDZava-jUtyfgqDlHuuVlbdR23Ap_dT-XGOQV9IDImn-NxZrYdY9jsSga3xZX47kjN7VIhZ-oF5iCbzpqHqGUZCJzHyubsnPl7uS3dVaf5FNKQJW0G7oZqlm5Jowyagcxf7giDhouAmmniw%3D%3D'));
$gm_array_two = json_decode($gm_file_two, TRUE);

$gm_file_three = implode(file('https://wildfire.cr.usgs.gov/arcgis/rest/services/geomac_dyn/MapServer/7/query?where=&text=&objectIds=&time=2018&geometry=&geometryType=esriGeometryPoint&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=*&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR=&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance=&returnExtentsOnly=false&datumTransformation=&parameterValues=&rangeValues=&f=geojson&__ncforminfo=c0t2_KSaod0r6feIJhyxYh0p2Zsa7pEeh1CLlitqhpwYCxBugGVyWZ8N_72C1AWvRFzeHIlQzRqR872J0nKNhKcKjx7ELyxsTp4oKLKO6jjXANreDaavmfKd86bewHs4Aki_nFbRhIABt_hwAuOoWDdmKyaYgmEPbEO1GADx1eO80yrmwBzHWWBYBrBTqFCVDV1Q-ee2JrN_c0RQMBnmmCWx-ISeM6YXszzjLnPfriJBigQR9o0MnT0ZArkSO0YnjqXIxboKXO2GZKIeifLGpUJklHLsWkGK14gqbqu5RxBgaaRluNkccg7AhdN07s-wAxNiqNQVKvjcZG1-mtogvAN3rmJYkbjJuZCREDxHLyIsiS3zN5PZ_4Zd-b35jpdKUyIOhhY2Exl6-OHxv8-Df5SWXnpemoP2J1M1r0J7FF7vJwx5zQfHZXWSYBmxxc8uhT5IEL85Vcejth7frcEYPr9GoVbg4RXC5jPH_ROWTPxJfu2CG-Wh66-XtzEs4j4OvZqZ7B4G0_Z7kfmYACEHAaVAZyZ-UANAEALtsuCuSwiRLj4n-3rnsZ4j7esn6kB4gc-ih4yJ_hDtpso1sgIxwGNO7XyxV1nTP8iBS_AafotLafTlsRWH99Ny5UJTWuuEPm48ZF6j6uq3JWmm-oBeYrzZXdINmhtqP5RFPOrAxgEHW8CucnI2JA%3D%3D'));
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
    $gm_feature['properties']['incidentname'] = ucwords(strtolower($gm_feature['properties']['incidentname']));
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
    $gm_feature['properties']['incidentname'] = ucwords(strtolower($gm_feature['properties']['incidentname']));
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
		$sim = similar_text(strtolower(trim($gm_feature['properties']['incidentname'])), strtolower(trim($iw_item['title'])), $perc);
		if ((int) $perc > 90 && $iw_item['state'] === $state) {
			$gm_feature['properties']['iw_link'] = $iw_item['link'];
			if (isset($iw_item['description'])) {
				$gm_feature['properties']['iw_description'] = trim($iw_item['description']);
				$count_two++;
			}
		}
	}
	// REMOVE UNNEEDED FIELDS HERE
	array_push($gm_output, $gm_feature);
	//var_dump($gm_feature['properties']['incidentname']);
}
echo 'MATCHES added to GeoMAC: ' . $count_two . "\n";

// Set up and fetch the GeoMAC perimeter data feed
$perim_raw_file = implode(file('https://wildfire.cr.usgs.gov/arcgis/rest/services/geomac_dyn/MapServer/3/query?where=&text=&objectIds=&time=2018&geometry=&geometryType=esriGeometryPolyline&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=*&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR=&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance=&returnExtentsOnly=false&datumTransformation=&parameterValues=&rangeValues=&f=geojson&__ncforminfo=6l0csKPzjIWR7iEqR7vPK7NwWSp4R7b9Gu3DFZDCbs-aXQbh8UazEo-_SRSFx5P-xe2vv54bLIrdLmXSTJsdOMFZh53Za9Y77n3lW4ogEwm8aeVaLZKiXZEjuJMCG0gBgS-GN1XajaNmcy-iKg5ttGGevEWexPZEqWvbMGWLgLUm4okDf0sQg7tX6ERau2tE6Pbi-8UjGNJJAw_RIuT4RMnZoM3-g8PEKgnCXBIn3AGPcxQbS9zT2Ts2d04Fx_DKeD877kyBWkj2EbpvxoRNQg6I-jNUaNgUK8FG1F8m_HazcnECytvYDG__Bwmbxl9YPKmDDd6iD9nmkoOZ3k9FDg_0M24Ah-Box4kroRCtdoWsmJK_zzV4ErqtDylkSYk3tP-dVZaIOFbF29Pc_Dc7j99QvVeWSVEKSSsUv75BuUdCTWh6AZ0yaM36YH8mzRenJTlsXh0fZxxtZfsjhoGEONlI7WqsOP6ZBEPW2ZB6j8CENRoetpj3okBL5YWVc4jI2v8f5AZLiM7KuPyiAkO7B2q6in72XjzdBiKzhX69sVLDqlVYA1jfkU-WUUv_1RLIkhjy5BN_8S4jXrLsjpb-5stObI2yuP72dWIpjsUTN2woRpr_dCLz9kU8DZ5wSpQ_e9Mi83ZITABQ5abVHs18n01Jyd5Z3ncndTCS51Mv9Os8CovloMnesw%3D%3D'));
$perim_raw = json_decode($perim_raw_file, TRUE);

// tolerance and include for the RDP simplification
require 'douglas-peucker.php';
$tolerance = .0002;

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
	if (isset($perim_feature['properties']['inciwebid']) && is_int($perim_feature['properties']['inciwebid'])) {
		$perim_feature['properties']['inciwebid'] = 'http://inciweb.nwcg.org/incident/' . $perim_feature['properties']['inciwebid'] . '/';
	}
	if ($perim_feature['geometry']['type'] == 'Polygon') {
		$i = 0;
		foreach ($perim_feature['geometry']['coordinates'] as $coords) {
			$perim_feature['geometry']['coordinates'][$i] = simplify_RDP($coords, $tolerance);
			$i++;
		}
		unset($i);
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

// Set up FTP connection
$conn_id = ftp_connect($FTP_SERVER) or die("Couldn't connect to $FTP_SERVER");
$ftp_logged_in = ftp_login($conn_id, $FTP_USER_NAME, $FTP_USER_PASS);
ftp_pasv($conn_id, TRUE);

// FTP the combined data
$ftp_uploaded = ftp_put($conn_id, $FTP_DIRECTORY . '/' . $output_all_file, './cache/wildfires-combined-all.json', FTP_ASCII);

if ($ftp_uploaded) {
	$error_out = 'Datafile uploaded!';
	purgeFTP($output_all_file);
} else {
	$error_out = 'An oops has occurred...';
}
echo "\n" . $error_out;

// FTP the perimeter data
$perim_uploaded = ftp_put($conn_id, $FTP_DIRECTORY . '/' . $perim_all_file, './cache/wildfires-combined-perims.json', FTP_ASCII);

if ($perim_uploaded) {
	$perim_error_out = 'Perimeter file uploaded!';
	purgeFTP($perim_all_file);
} else {
	$perim_error_out = 'An oops has occurred...';
}
echo "\n" . $perim_error_out;

//close the FTP connection
ftp_close($conn_id);
