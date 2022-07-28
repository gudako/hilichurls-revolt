<?php
$composer = require($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');
$loader = new ConstructStatic\Loader($composer);
$handler = new DbSessionHandler();
session_set_save_handler($handler, true);
session_start();
