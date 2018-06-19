<?php

// This function is for string null or empty check in the main function

function get_state_abbr($state) {
	$states = array(
		'Alabama' => 'AL',
		'Alaska' => 'AK',
		'Arizona' => 'AZ',
		'Arkansas' => 'AR',
		'California' => 'CA',
		'Colorado' => 'CO',
		'Connecticut' => 'CT',
		'Delaware' => 'DE',
		'Florida' => 'FL',
		'Georgia' => 'GA',
		'Hawaii' => 'HI',
		'Idaho' => 'ID',
		'Illinois' => 'IL',
		'Indiana' => 'IN',
		'Iowa' => 'IA',
		'Kansas' => 'KS',
		'Kentucky' => 'KY',
		'Louisiana' => 'LA',
		'Maine' => 'ME',
		'Maryland' => 'MD',
		'Massachusetts' => 'MA',
		'Michigan' => 'MI',
		'Minnesota' => 'MN',
		'Mississippi' => 'MS',
		'Missouri' => 'MO',
		'Montana' => 'MT',
		'Nebraska' => 'NE',
		'Nevada' => 'NV',
		'New Hampshire' => 'NH',
		'New Jersey' => 'NJ',
		'New Mexico' => 'NM',
		'New York' => 'NY',
		'North Carolina' => 'NC',
		'North Dakota' => 'ND',
		'Ohio' => 'OH',
		'Oklahoma' => 'OK',
		'Oregon' => 'OR',
		'Pennsylvania' => 'PA',
		'Rhode Island' => 'RI',
		'South Carolina' => 'SC',
		'South Dakota' => 'SD',
		'Tennessee' => 'TN',
		'Texas' => 'TX',
		'Utah' => 'UT',
		'Vermont' => 'VT',
		'Virginia' => 'VA',
		'Washington' => 'WA',
		'West Virginia' => 'WV',
		'Wisconsin' => 'WI',
		'Wyoming' => 'WY',
	);
	return $states[$state];
}

function IsNullOrEmptyString($question) {
	return (!isset($question) || trim($question) === '');
}

function getAddress($latitude, $longitude) {
	$url = "http://maps.googleapis.com/maps/api/geocode/json?latlng=$latitude,$longitude&sensor=false&token=" . 'AIzaSyDFesAMjYEKk6hCIxnQ_3SIwJ6rImbSch8';
	$response = file_get_contents($url);
	$json = json_decode($response, TRUE); //set json response to array based
	$address_arr = (isset($json['results'][0])) ? $json['results'][0]['address_components'] : false;
	if ($address_arr) {
		foreach ($address_arr as $address) {
			if ($address['types'][0] == "administrative_area_level_1") {
				$state_block = $address;
				break;
			}
		}
		return (!IsNullOrEmptyString($state_block['short_name'])) ? $state_block['short_name'] : get_state_abbr($state_block['long_name']);
	} else {
		return false;
	}
	
}