<?php

// Border Patrol keeps all them sumbitches out of 'Merica!

ini_set('include_path', ini_get('include_path') . ':' . getcwd() . '/vendors/PEAR');

require 'vendors/PEAR/Net/GeoIP.php';

try
{
	$geoip    = Net_GeoIP::getInstance('vendors/maxmind/GeoLiteCity-201111.dat');
	$location = $geoip->lookupLocation($_SERVER['REMOTE_ADDR']);
	//$location = $geoip->lookupLocation('96.228.210.198'); // St Petersburg (The on in 'Merica!) IP
	//$location = $geoip->lookupLocation('107.2.159.37'); // Denver IP
	//$location = $geoip->lookupLocation('58.14.0.0'); // Dirty Chinaman IP
	//$location = $geoip->lookupLocation('58'); // Some Terrorist trying to Hack In!

	$merican = ($_SERVER['SERVER_ADDR'] == '127.0.0.1' || ($location != null && isset($location->countryCode) && $location->countryCode == 'US'));
}
catch (Exception $e)
{
	$merican = false;
}

if (!$merican)
{
	exit('GO BACK TO WHERE YOU CAME FROM, COMMIE!');
}

?>
