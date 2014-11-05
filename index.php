<?php
/**
* Current NOAA Weather Grabber
*
* This lightweight PHP script gets the current weather condition,
* temperature, and the name of a corresponding condition image
* from NOAA and makes the data available for use in a PHP document
* It includes a built-in JSON cache.
*
*

*




/**
* Configuration
* Set these variables to match your desired configuration.
* More information is available in readme.md.
*
**/

// Enter the four letter NOAA city code from http://weather.gov/.
define('WEATHER_CITY_CODE', 'KOSU'); //KOSU = OSU Airport

// Enter the full file path to your cache data folder. Make sure the folder is writable.
define('CACHEDATA_FILE_PATH', '/var/www/kosucashe');

// Enter your timezone code from http://php.net/manual/en/timezones.php. This is set to America/Chicago by default.
define('TIMEZONE', 'America/Chicago');

// Enter the cache duration, in seconds.
define('WEATHER_CACHE_DURATION', 1);

// You probably won't need to touch these.
define('WEATHER_URL', 'http://w1.weather.gov/xml/current_obs/'.WEATHER_CITY_CODE.'.xml');
define('CACHEDATA_FILE', CACHEDATA_FILE_PATH.'weather_data_'.WEATHER_CITY_CODE.'.json');

// End of configuration -- you're done!
// See readme.md for more information about including this in your script.


/**
* Returns either previously cached data or newly fetched data
* depending on whether or not it exists and whether or not the
* cache time has expired.
*
* @return array
**/
function get_weather_data() {

date_default_timezone_set(TIMEZONE);

// Check if cached weather data already exists and if the cache has expired
if ( ( file_exists(CACHEDATA_FILE) ) && ( date('YmdHis', filemtime(CACHEDATA_FILE)) > date('YmdHis', strtotime('Now -'.WEATHER_CACHE_DURATION.' seconds')) ) ) {
$weather_string = file_get_contents(CACHEDATA_FILE) or make_new_cachedata();
$weather = (json_decode($weather_string, true));
return $weather;
}

else {
return make_new_cachedata();
}

}

/**
* Returns an array of weather data and saves the data
* to a cache file for later use.
*
* @return array
**/
function make_new_cachedata() {

// End this function if the weather feed cannot be found
$weather_url_headers = get_headers(WEATHER_URL);
if ($weather_url_headers[0] == "HTTP/1.1 200 OK") {}
elseif ($weather_url_headers[0] == "HTTP/1.0 200 OK") {}
else {
return;
}

// Setup an empty array for weather data
$weather = array(
'condition'	=> '',
'temp'	=> '',
//'imgCode'	=> '',
//'feedUpdatedAt'	=> '',
//'temp_c' => '',
//'windchill' => '',
);

// Set a timeout and grab the weather feed
$opts = array('http' => array(
'method' => 'GET',
'timeout' => 5	// seconds
));
$context = stream_context_create($opts);
$raw_weather = file_get_contents(WEATHER_URL, false, $context);

// If the weather feed can be fetched, grab and sanatize the needed information
if ($raw_weather) {
$xml = simplexml_load_string($raw_weather);

$imgCodeNoExtension = explode(".png", $xml->icon_url_name);

$weather = $xml;

$weather['condition'] = htmlentities((string)$xml->weather);
$weather['temp']	= htmlentities(number_format((int)$xml->temp_f));
$weather['location'] = htmlentities((string)$xml->location); 
$weather['temperature_string']	= htmlentities((string)$xml->temperature_string);
$weather['humidity']	= htmlentities((string)$xml->relative_humidity);
$weather['imgCode']	= htmlentities((string)$imgCodeNoExtension[0]);
$weather['wind_string']	= htmlentities((string)$xml->wind_string);
$weather['windchill_string']	= htmlentities((string)$xml->windchill_string);
//$weather['feedUpdatedAt'] = htmlentities((string)$xml->observation_time_rfc822);
//$weather['temp_c'] = htmlentities((string)$xml->temp_c);
//$weather['windchill'] = htmlentities((string)$xml->windchill_string);
}

// Setup a new string for caching
$weather_string = json_encode($weather);

// Write the new string of data to the cache file
$filehandle = fopen(CACHEDATA_FILE, 'w') or die('Cache file open failed.');
fwrite($filehandle, $weather_string);
fclose($filehandle);

// Return the newly grabbed content
return $weather;
}


/**
* Grab, sanatize, and put the data in variables.
*
**/
$weather_array = get_weather_data();

$weather_condition = htmlentities($weather_array['condition']);
$weather_temp = htmlentities($weather_array['temp']);
$weather_windchillString = htmlentities($weather_array['windchill_string']);
$weather_location = htmlentities($weather_array['location']);
$weather_imgCode = htmlentities($weather_array['imgCode']);
$weather_tempstring = htmlentities($weather_array['temperature_string']);
$weather_windString = htmlentities($weather_array['wind_string']);
$weather_humidity = htmlentities($weather_array['humidity']);
//$weather_feedUpdatedAt = htmlentities($weather_array['feedUpdatedAt']);

//echo $weather;

//echo $weather_condition; 
//echo $weather_temp;
//echo $weather_imgCode;
//echo $weather_feedUpdatedAt;

?>

<html>
<head>
<title>Weather</title>
<link rel="stylesheet" href="weather.css">
</head> 
<body>
<div>
<h2><?php echo $weather_location ;?></h2>
<h3>
<?php 
if ($weather_imgCode == 'skc'){
	echo '<img src="/icons/skc.png" width="100" height="100"/>';
	}
if ($weather_imgCode == 'nskc'){
	echo '<img src="/icons/nskc.png" width="100" height="100"/>';
	}
if ($weather_imgCode == 'sct'){
	echo '<img src="/icons/sct.png" width="100" height="100"/>';
	}
if ($weather_imgCode == 'bkn'){
	echo '<img src="/icons/bkn.png" width="100" height="100"/>';
	}
if ($weather_imgCode == 'novc'){
	echo '<img src="/icons/novc.png" width="100" height="100"/>';
	}
?> <br>
<?php echo $weather_condition ;?> <br>
Temperature:  <?php echo $weather_tempstring;?> <br> 
Wind Chill:   <?php echo $weather_windchillString;?> <br>
Humidity:     <?php echo $weather_humidity;?> % <br>
Wind from:    <?php echo $weather_windString;?> <br>
<br>
<img src="http://sirocco.accuweather.com/nx_mosaic_640x480_public/sir/inmasiroh_.gif" width="500" height="300"/>
</h3>

</div>
</body>
</html>


