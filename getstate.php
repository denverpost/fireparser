<?php

// This function is for string null or empty check in the main function

function underscoresIt($string){
	return strtolower(str_replace(' ', '_', trim($string)));
}

function get_state_abbr($state) {
	$states = array(
		'alabama' => 'AL',
		'alaska' => 'AK',
		'arizona' => 'AZ',
		'arkansas' => 'AR',
		'california' => 'CA',
		'colorado' => 'CO',
		'connecticut' => 'CT',
		'delaware' => 'DE',
		'florida' => 'FL',
		'georgia' => 'GA',
		'hawaii' => 'HI',
		'idaho' => 'ID',
		'illinois' => 'IL',
		'indiana' => 'IN',
		'iowa' => 'IA',
		'kansas' => 'KS',
		'kentucky' => 'KY',
		'louisiana' => 'LA',
		'maine' => 'ME',
		'maryland' => 'MD',
		'massachusetts' => 'MA',
		'michigan' => 'MI',
		'minnesota' => 'MN',
		'mississippi' => 'MS',
		'missouri' => 'MO',
		'montana' => 'MT',
		'nebraska' => 'NE',
		'nevada' => 'NV',
		'new_hampshire' => 'NH',
		'new_jersey' => 'NJ',
		'new_mexico' => 'NM',
		'new_york' => 'NY',
		'north_carolina' => 'NC',
		'north_dakota' => 'ND',
		'ohio' => 'OH',
		'oklahoma' => 'OK',
		'oregon' => 'OR',
		'Pennsylvania' => 'PA',
		'rhode_island' => 'RI',
		'south_carolina' => 'SC',
		'south_dakota' => 'SD',
		'tennessee' => 'TN',
		'texas' => 'TX',
		'utah' => 'UT',
		'vermont' => 'VT',
		'virginia' => 'VA',
		'washington' => 'WA',
		'west_virginia' => 'WV',
		'wisconsin' => 'WI',
		'wyoming' => 'WY',
	);
	return $states[$state];
}

function IsNullOrEmptyString($question) {
	return (!isset($question) || trim($question) === '');
}


function curlTheBitch($url) {
	$headers = array(
		'Accept: application/json',
		'Content-Type: application/json',
		'Content-length: 0',
		'Email: dpo@denverpost.com'
	);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_USERAGENT, "dpo@denverpost.com");
	$response = curl_exec($ch);
	curl_close($ch);
	return $response;
}


function getAddress($latitude, $longitude) {
	$url = "https://nominatim.openstreetmap.org/reverse?email=dpo@denverpost.com&format=json&lat=$latitude&lon=$longitude&zoom=18&addressdetails=1";

	$response = curlTheBitch($url);
	$json = json_decode($response, TRUE); //set json response to array based
	
	if (isset($json['address'])) {
		return get_state_abbr(underscoresIt($json['address']['state']));
	}
}