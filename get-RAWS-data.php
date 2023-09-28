<?php
#---------------------------------------------------------------------------
/*
Program: get-RAWS-data.php

Purpose: generate data for RAWS.php to generate a GRLevelX placefile to display RAWS data

Usage:   invoke as a cron job at 5 minute intervals in the same directory as RAWS.php

Creates: decoded RAWS info and data:
           RAWS-data-inc.php from National Interagency Fire Center
   https://data-nifc.opendata.arcgis.com/datasets/nifc::public-view-interagency-remote-automatic-weather-stations-raws/about 
	 (the feature server will be used for data query)
				 This file is included by RAWS.php to generate the GRLeelX placefile

Author: Ken True - webmaster@saratoga-weather.org

Acknowledgement:
  
   Special thanks to Mike Davis, W1ARN of the National Weather Service, Nashville TN office
   for his testing/feedback during development.   
    
Copyright (C) 2023  Ken True - webmaster@saratoga-weather.org

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	If you enhance or bug-fix the program, please share your modifications
  to the GitHub distribution so others can enjoy your updates.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <https://www.gnu.org/licenses/>.

Version 1.00 - 28-Sep-2023 - initial release

*/
$Version = "get-RAWS-data.php V1.00 - 28-Sep-2023 - webmaster@saratoga-weather.org";
#---------------------------------------------------------------------------
$maxAge = 2*(3600)+300;  # maximum age of observations to display in seconds 2h5m
#-----------settings (don't change)--------------------------------------------------------
#
$dataURL       = 'https://services3.arcgis.com/T4QMspbfLg3qTGWY/arcgis/rest/services/PublicView_RAWS/FeatureServer/1/query?f=json&maxRecordCountFactor=4&resultOffset=0&resultRecordCount=8000&where=1%3D1&orderByFields=OBJECTID&outFields=*&spatialRel=esriSpatialRelIntersects';
$dataJSONfile  = 'RAWS_data.json';      # cache file name
$dataFile      = 'RAWS-data-inc.php';   # this will be included in RAWS.php
#-----------end of settings-------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors','1');
header('Content-type: text/plain;charset=ISO-8859-1');

print "$Version\n";

$STRopts = array(
	'http' => array(
		'method' => "GET",
		'protocol_version' => 1.1,
		'header' => "Cache-Control: no-cache, must-revalidate\r\n" . 
			"Cache-control: max-age=0\r\n" . 
			"Connection: close\r\n" . 
			"User-agent: Mozilla/5.0 (get-RAWS-data - saratoga-weather.org)\r\n" . 
			"Accept: text/plain,application/xml\r\n"
	) ,
	'ssl' => array(
		'method' => "GET",
		'protocol_version' => 1.1,
		'verify_peer' => false,
		'header' => "Cache-Control: no-cache, must-revalidate\r\n" . 
			"Cache-control: max-age=0\r\n" . 
			"Connection: close\r\n" . 
			"User-agent: Mozilla/5.0 (get-RAWS-data - saratoga-weather.org)\r\n" . 
			"Accept: text/plain,application/xml\r\n"
	)
);
$STRcontext = stream_context_set_default($STRopts);

#---------------------------------------------------------------------------
#  get/process the current buoy cnditions data
#---------------------------------------------------------------------------

$rawJSON = file_get_contents($dataURL);
print ".. Loaded $dataURL\n   which has ".strlen($rawJSON)." bytes.\n";
$success = file_put_contents($dataJSONfile,$rawJSON);
if($success) {
  print ".. saved raw JSON data to $dataJSONfile.\n";
} else {
	print "-- unable to save JSON data to $dataJSONfile\n";
}

$JSON = json_decode($rawJSON,true,512,JSON_BIGINT_AS_STRING+JSON_OBJECT_AS_ARRAY);

if(function_exists('json_last_error')) {
  switch (json_last_error()) {
  case JSON_ERROR_NONE:
    $JSONerror = '- No errors';
    break;

  case JSON_ERROR_DEPTH:
    $JSONerror = '- Maximum stack depth exceeded';
    break;

  case JSON_ERROR_STATE_MISMATCH:
    $JSONerror = '- Underflow or the modes mismatch';
    break;

  case JSON_ERROR_CTRL_CHAR:
    $JSONerror = '- Unexpected control character found';
    break;

  case JSON_ERROR_SYNTAX:
    $JSONerror = '- Syntax error, malformed JSON';
    break;

  case JSON_ERROR_UTF8:
    $JSONerror = '- Malformed UTF-8 characters, possibly incorrectly encoded';
    break;

  default:
    $JSONerror = '- Unknown error';
    break;
  }
    
  print ".. JSON decode $JSONerror\n";    
}

if (!isset($JSON['features'][0])) {
		print "-- no data found .. exiting\n";
		return;
}
		
/*
    {
      "attributes": {
        "OBJECTID": 3080,
        "StationName": "LOS GATOS",
        "WXID": "17112094",
        "ObservedDate": 1695832351000,
        "NESSID": "CA4A56A8",
        "NWSID": "043913",
        "Elevation": 1842,
        "SiteDescription": null,
        "Latitude": 37.20417,
        "Longitude": -121.95083,
        "State": "CA",
        "County": "Santa Clara",
        "Agency": "S&PF",
        "Region": "CALIFORNIA",
        "Unit": "SANTA CLARA COUNTY",
        "SubUnit": "FIRE DEPT",
        "Status": "A",
        "RainAccumulation": "41.82 inches",
        "WindSpeedMPH": "5 mph",
        "WindDirDegrees": "11 degrees ",
        "AirTempStandPlace": "65 deg. F",
        "FuelTemp": "68 deg. F ",
        "RelativeHumidity": "43 % ",
        "BatteryVoltage": "13.7 volts",
        "FuelMoisture": "14.3 (unk)",
        "WindDirPeak": "344 degrees",
        "WindSpeedPeak": "8 mph",
        "SolarRadiation": "357 w/m2",
        "StationID": "17112093",
        "MesoWestStationID": "LSGC1",
        "MesoWestURL": "https://mesowest.utah.edu/cgi-bin/droman/meso_base_dyn.cgi?stn=LSGC1&unit=0&timetype=GMT"
      },
      "geometry": {
        "x": -121.95082999999994,
        "y": 37.204170000000029
      }
    },

*/
$RAWSData = array();
$now = time();
foreach ($JSON['features'] as $i => $J) {
	$D = array();
	$V = $J['attributes'];
	
	$utctime  = $V['ObservedDate']/1000;
	$age = $now - $utctime;
	if($age > $maxAge) {continue;} # only observations within 1 hour are used.
	if($V['AirTempStandPlace'] == "NO DATA" or $V['WindSpeedMPH'] == "NO DATA") {continue;}
	
	if($V['Status'] !== 'A') { continue; }
	
	$D['stationid']= $V['MesoWestStationID'];
	$D['stationname'] = $V['StationName'];
	$D['elev']        = $V['Elevation'];
	$D['lat'] = sprintf("%01.6f",$J['geometry']['y']);
	$D['lon'] = sprintf("%01.6f",$J['geometry']['x']);
	$D['obsdate'] = gmdate('r',$utctime);
	$D['UTC'] = gmdate('c',$utctime);
	$D['age'] = $age.' secs';
	$D['loc'] = $V['County'].', '.$V['State'];
	$D['owner'] = $V['Agency'].','.$V['Unit'].', '.$V['SubUnit'];
	$D['site'] = isset($V['SiteDescription'])?
	  str_replace('|',' ',wordwrap(preg_replace('!(\\n)!Uis',' ',$V['SiteDescription']),52,'|')):'n/a';

	if(!empty($V['WindDirDegrees'])) {
		list($D['dwinddir'],$D['wdir']) = getWindDir($V['WindDirDegrees']);
	}
	if(!empty($V['WindSpeedMPH'])){
		list($D['dwind'],$D['wind']) = convertWind($V['WindSpeedMPH']);
	}
	if(!empty($V['WindSpeedPeak'])){
		list($D['dgust'],$D['gust']) = convertWind($V['WindSpeedPeak']);
	}
	if(!empty($V['WindDirPeak'])) {
		list($D['dgustdir'],$D['gustdir']) = getWindDir($V['WindDirPeak']);
	}
	if(!empty($V['AirTempStandPlace'])) {
		list($D['dtemp'],$D['temp']) = convertTemp($V['AirTempStandPlace']);
	}
	
	if(!empty($V['FuelTemp'])) {
		list($D['dftemp'],$D['ftemp']) = convertTemp($V['FuelTemp']);
	}
	if(!empty($V['FuelMoisture'])) {
		list($D['dfmoist'],$D['fmoist']) = convertMoist($V['FuelMoisture']);
	}
	if(!empty($V['RelativeHumidity'])) {
		list($D['dhum'],$D['hum']) = convertHum($V['RelativeHumidity']);
	}
	if(is_numeric($D['dtemp']) and is_numeric($D['dhum'])) {
		list($D['ddew'],$D['dew']) = calcDewPoint($D['dtemp'],$D['dhum']);
		list($D['dheatidx'],$D['heatidx']) = calcHeatIndex($D['dtemp'],$D['dhum']);
	}
	if(is_numeric($D['dtemp']) and is_numeric($D['dwind'])) {
		list($D['dwindch'],$D['windch']) = calcWindChill($D['dtemp'],$D['dwind']);
	}
	if(!empty($V['RainAccumulation'])) {
		list($D['drain'],$D['rain']) = convertRain($V['RainAccumulation']);
	}
	if(!empty($V['SolarRadiation'])) {
		list($D['dsolar'],$D['solar']) = convertSolar($V['SolarRadiation']);
	}

  $RAWSData[] = $D;
	unset($D);
}
ksort($RAWSData);

$success = file_put_contents($dataFile,
"<?php\n# RAWS Data updated " . gmdate('r')."\n".
"# by $Version\n".
"#\n".
"\$RAWSData = ".var_export($RAWSData,true).";\n"
);
if($success) {
	print ".. saved $dataFile with ".count($RAWSData). " RAWS entries.\n";
} else {
	print "-- unable to save $dataFile\n";
}


print ".. Done\n";

#---------------------------------------------------------------------------
# functions
#---------------------------------------------------------------------------

#---------------------------------------------------------------------------

function convertTemp ($rawTemp) {
	 # input in F
	 $t = explode(' ',$rawTemp);
	 return(array($t[0],$t[0].' F'));
}
#---------------------------------------------------------------------------

function convertWind  ( $rawwind ) {
	 # input in mph
	 $t = explode(' ',$rawwind);
	 return(array($t[0],$t[0].' mph'));
}

#---------------------------------------------------------------------------

function getWindDir ($in) {
   // figure out a text value for compass direction
// Given the wind direction, return the text label
// for that value.  16 point compass
  list($degrees,$junk) = explode(' ',$in);
  
  if (!is_numeric($degrees)) { return(array(0,'?')); }
  static $windlabel = array ("N","NNE", "NE", "ENE", "E", "ESE", "SE", "SSE", "S",
	 "SSW","SW", "WSW", "W", "WNW", "NW", "NNW");
  $dir = $windlabel[ (integer)fmod((($degrees + 11) / 22.5),16) ];
  return(array($degrees,$dir));
} 

#---------------------------------------------------------------------------

function convertHum($rawHum) {
	 $t = explode(' ',$rawHum);
	 return(array($t[0],$t[0].'%'));
}

#---------------------------------------------------------------------------

function convertSolar($rawSolar) {
	$t = explode(' ',$rawSolar);
	 return(array($t[0],$t[0].' W/m2'));
}

#---------------------------------------------------------------------------

function convertMoist($rawMoist) {
	$t = explode(' ',$rawMoist);
	 return(array($t[0],$t[0].'%'));
}

#---------------------------------------------------------------------------

function convertRain($rawRain) {
	$t = explode(' ',$rawRain);
	 return(array($t[0],$t[0].' in (accum. total)'));
}

function calcDewPoint ( $tempF, $hum ) {
		$tempC = 5*($tempF -32)/9;
	
	$dewpointC = round(((pow(($hum/100), 0.125))*(112+0.9*$tempC)+(0.1*$tempC)-112),1);
	// temp in C, humidity in % -- thanks to Jachym.
	$dewpointF = round(32 +(9*$dewpointC/5),0);
  return(array($dewpointF,"$dewpointF F"));
	
}

#---------------------------------------------------------------------------

function calcHeatIndex ($temp,$humidity) {
// Calculate Heat Index from temperature in F and humidity
// Source of calculation: http://woody.cowpi.com/phpscripts/getwx.php.txt	
	$tempF = round($temp,1);
  $rh = $humidity;
  
  
  // Calculate Heat Index based on temperature in F and relative humidity (65 = 65%)
  if ($tempF > 79 && $rh > 39) {
	  $hiF = -42.379 + 2.04901523 * $tempF + 10.14333127 * $rh - 0.22475541 * $tempF * $rh;
	  $hiF += -0.00683783 * pow($tempF, 2) - 0.05481717 * pow($rh, 2);
	  $hiF += 0.00122874 * pow($tempF, 2) * $rh + 0.00085282 * $tempF * pow($rh, 2);
	  $hiF += -0.00000199 * pow($tempF, 2) * pow($rh, 2);
	  $hiF = round($hiF,0);
  } else {
	  $hiF = '';
  }

  return(array($hiF,$hiF.' F'));	
/* note from NWS:
 The Heat Index Equation http://www.wpc.ncep.noaa.gov/html/heatindex_equation.shtml
 
The computation of the heat index is a refinement of a result obtained by multiple regression analysis carried out by Lans P. Rothfusz and described in a 1990 National Weather Service (NWS) Technical Attachment (SR 90-23).  The regression equation of Rothfusz is

    HI = -42.379 + 2.04901523*T + 10.14333127*RH - .22475541*T*RH - .00683783*T*T - 
		     .05481717*RH*RH + .00122874*T*T*RH + .00085282*T*RH*RH - .00000199*T*T*RH*RH 

where T is temperature in degrees F and RH is relative humidity in percent.  
HI is the heat index expressed as an apparent temperature in degrees F.  
If the RH is less than 13% and the temperature is between 80 and 112 degrees F, 
then the following adjustment is subtracted from HI:

    ADJUSTMENT = [(13-RH)/4]*SQRT{[17-ABS(T-95.)]/17} 

where ABS and SQRT are the absolute value and square root functions, respectively.
On the other hand, if the RH is greater than 85% and the temperature is between 80 and 87 degrees F,
then the following adjustment is added to HI:

    ADJUSTMENT = [(RH-85)/10] * [(87-T)/5] 

The Rothfusz regression is not appropriate when conditions of temperature and humidity warrant
a heat index value below about 80 degrees F. In those cases,
a simpler formula is applied to calculate values consistent with Steadman's results:

    HI = 0.5 * {T + 61.0 + [(T-68.0)*1.2] + (RH*0.094)} 

In practice, the simple formula is computed first and the result averaged with the temperature.
If this heat index value is 80 degrees F or higher, the full regression equation along
with any adjustment as described above is applied.

The Rothfusz regression is not valid for extreme temperature and relative humidity
conditions beyond the range of data considered by Steadman. 

*/
}

#---------------------------------------------------------------------------

function calcWindChill($tempF,$windMPH) {
	
// http://www.nws.noaa.gov/om/cold/wind_chill.shtml
// wind = wind speed in mph 
// temp = temperature in Fahrenheit
// WindChill(F) = 35.74 + 0.6215T - 35.75(V^0.16) + 0.4275T(V^0.16)
// valid for wind > 3.0 mph, temp <= 50.0 F (else use temp)


  $wind2 = pow($windMPH,0.16);
  $wind_chill = 35.74 + 0.6215*$tempF - 35.75*$wind2 + 0.4275*$tempF*$wind2;
	
  $wind_chill = round($wind_chill, 0);
  $wind_chill = ($tempF > 50.0) ? '' : $wind_chill;
  $wind_chill = ($windMPH <= 3.0) ? '' : $wind_chill;
		 
	return(array($wind_chill,$wind_chill." F"));

/* NOAA Note from http://www.nws.noaa.gov/om/cold/faqs.shtml
The windchill temperature is calculated using the following formula:

Windchill (ºF) = 35.74 + 0.6215T - 35.75(V^0.16) + 0.4275T(V^0.16)

Where: T = Air Temperature (F)
V = Wind Speed (mph)
^ = raised to a power (exponential)

Windchill Temperature is only defined for temperatures at or below 50°F and wind speeds above 3 mph. 
Bright sunshine may increase the windchill temperature by 10°F to 18°F.

*/	
}

# end of get-RAWS-data.php