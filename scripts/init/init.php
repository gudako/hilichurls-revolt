<?php
require_once('init_session.php');
if (!isset($_SESSION['init_stat'])) {
    $_SESSION['init_stat'] = false;
} elseif ($_SESSION['init_stat'] === true) {
    goto post_init;
}
require_once('init_errhandler.php');
require_once('init_objects.php');
$_SESSION['init_stat'] = true;
post_init:
