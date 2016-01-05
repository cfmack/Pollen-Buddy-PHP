<?php

/**
 * This is a tester file for Pollen Buddy
 */

require_once("PollenBuddy.php");

$data = new PollenBuddy(78758, 10);
//echo $data->getSiteHTML();
//echo $data->getCity();
//echo $data->getZipCode();
//echo $data->getPollenType();
//var_dump($data->getFourDates());
//var_dump($data->getFourLevels());
var_dump($data->getFourDayForecast());

var_dump($data->getKeys());