<?php
set_time_limit(0);
date_default_timezone_set('UTC');
require __DIR__.'/vendor/autoload.php';
\InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = false;
$username = "USERNAME";
$password = "PASSWORD";
$debug = false;
$truncatedDebug = false;
?>
