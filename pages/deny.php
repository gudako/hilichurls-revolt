<?php
if (!isset($_GET['code'])) {
    endUp:
    http_response_code(404);
    die();
} elseif ($_GET['code']=='403') {
    $back = '#3b0909';
    $title = '403 Forbidden';
} elseif ($_GET['code']=='404') {
    $back = '#091f3b';
    $title = '404 Not Found';
}
else goto endUp;
?>
<head><title>404 Not Found</title></head>
<body style="margin: 0;padding: 0;background-color: <?php echo $back;?>;color: lightgoldenrodyellow;">
<div style="margin: 100px auto 0 auto; border: 2px lightyellow solid; font-size: 30px; padding: 20px; width: 77vw;font-weight: 550;">
<?php echo $title;?><br>
<div style="font-size: 17px; margin-top: 35px; font-weight: 200; word-wrap: break-word;">Request Uri: <?php echo $_SERVER['REQUEST_URI']?></div>
</div>
</body>