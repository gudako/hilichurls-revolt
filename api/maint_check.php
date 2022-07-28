<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
use local\ConfigSystem;

$config = new ConfigSystem();
$maintainTime = $config->GetMaintenanceTime();
if ($maintainTime===false){
    echo 'false'; //means no maintenance or issues of
    die();
}
if ($maintainTime->invert == 0){
    echo 'true'; //means already started maintenance
    die();
}
echo $maintainTime->format('%h,%i'); //return the time remaining before maintain
die();
