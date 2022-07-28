<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/vendor/autoload.php';
if (isset($_SESSION['server_auth'])){
    // todo
} elseif ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['hostname'],$_POST['username'],$_POST['password'])){
    if ($_POST['hostname']===conf('db_hostname')&&
        $_POST['username']===conf('db_username')&&
        $_POST['password']===conf('db_password')){
        $_SESSION['server_auth'] = true;
    }
    header('Location: /server');
    exit();
} else{
    echo '<form action="/server">Hostname:<input name="hostname" type="text"><br>Username:<input name="username" type="text"><br>Password:<input name="password" type="password"><br><input type="submit" formmethod="post"></form>';
}

