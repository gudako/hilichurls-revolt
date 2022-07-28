<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

/** The server is down?? */
#[JetBrains\PhpStorm\NoReturn]
function server_fault():void{
    echo 'SERVER IS DOWN';
    http_response_code(500);
    exit();
}

$db_connect = Database::init(conf('db_root_name'), null, conf('db_username'),
    conf('db_password'), conf('db_schema'));
if ($db_connect === false) server_fault();
$_SESSION['db'] = $db_connect;

// todo
