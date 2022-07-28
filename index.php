<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/vendor/autoload.php';
if (isset($_GET['lang'])) {
    setcookie('lang', $_GET['lang'], time()+(60*60*24*365));
    header('Location: index');
    die();
}
?>
<!DOCTYPE html>
<html lang="<?php echo getlang();?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo memtxt(1889,53/*REMAP%game_name*/);?></title>
</head>
<body>
<link href="css/commonplace.css" rel="stylesheet"/>
<link href="css/index.css" rel="stylesheet"/>
<div class="clip">
    <?php require_once 'comp/page.php';?>
    <div id="bar1">
        <div id="bar1_clip_img" class="clip_img"><img class="clip_img_movable" src="resources/img/pages/mainpage/mainpage1.jpg"></div>
        <div id="bar1_advocate"><div><?php echo memtxt(2661,154/*REMAP%mainpage_bar1_text*/);?></div></div>
    </div>
    <div id="bar2">
        <?php echo memtxt(2815,124/*REMAP%mainpage_bar2_text*/);?>
    </div>
    <div id="bar3">
        <div id="bar3_clip_img" class="clip_img"><img class="clip_img_movable" src="resources/img/pages/mainpage/mainpage2.jpg"></div>
        <div id="bar3_sub_img" class="clip_img"><img src="resources/img/pages/mainpage/mainpage3.png"></div>
        <div id="bar3_statement"><?php echo memtxt(2939,162/*REMAP%mainpage_bar3_text1*/);?></div>
        <div id="bar3_advocate"><?php echo memtxt(3101,212/*REMAP%mainpage_bar3_text2*/);?></div>
    </div>
    <div id="bar4">
        <div>
            <?php echo memtxt(3313,833/*REMAP%mainpage_bar4_text*/);?>
        </div>
    </div>
    <div id="bar5">
        <div id="bar5_text1_div">
            <?php echo memtxt(4146,110/*REMAP%mainpage_bar5_text1*/);?>
            <sup><sup>(1)</sup></sup>
        </div>
        <div id="bar5_text2_div">
            <?php echo memtxt(4256,139/*REMAP%mainpage_bar5_text2*/);?>
        </div>
        <div id="bar5_img_div" class="clip_img">
            <img class="clip_img_movable" src="resources/img/pages/mainpage/mainpage4.jpg">
        </div>
        <div id="bar5_text3_div">
            <?php echo memtxt(4395,319/*REMAP%mainpage_bar5_text3*/);?>
        </div>
    </div>
    <div id="bar6">
        <img src="resources/img/pages/mainpage/mainpage_hilichurl_left.png"
             alt="<?php echo memtxt(5017,74/*REMAP%mainpage_slime_complaint!*/);?>">
        <div id="bar6_text">
            <img src="resources/img/pages/icons/fight.png">
            <div>
                <?php echo memtxt(4714,303/*REMAP%mainpage_bar6_text*/);?>
            </div>
        </div>
        <img src="resources/img/pages/mainpage/mainpage_hilichurl_right.png">
    </div>
    <a id="play_button">
        <span>let the revolt begin</span>
    </a>
    <div class="credit">
        <sup>(1)</sup> <?php echo memtxt(5208,74/*REMAP%mainpage_credit*/);?>
    </div>
</div>
</body>
