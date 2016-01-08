<?php

/**
 * This is a tester file for Pollen Buddy
 */

require_once("PollenBuddy.php");


$pb = new PollenBuddy(1, 1);
$forecasts = $pb->run(81089);
echo print_r($forecasts, true);

$forecasts = $pb->run(78757);
echo print_r($forecasts, true);

$day = $forecasts[0];
echo "$day";
