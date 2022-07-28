<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(404);
    die();
}
$lang = $_GET['lang'] ?? 'en';
$logcode = $_GET['logcode'] ?? null;
$msg = $_GET['msg'] ?? null;
if (!($logcode === null xor $msg === null)){
    http_response_code(404);
    die();
}
$query = $logcode === null ? "msg={$_GET['msg']}" : "logcode={$_GET['logcode']}";
$allowed = isset($_GET['auth']) && hash_equals(sha1($query.(new DateTimeImmutable())->format('Y-m-d').'You got that?'), $_GET['auth']);
if (!$allowed) {
    http_response_code(404);
    die();
}
http_response_code(500);
?>
<head>
    <title>500 Internal Server Error</title>
</head>
<style>
    body{
        margin: 0;
        padding: 0;
        font-family: 'Noto Sans SC', sans-serif;
    }
    #topic{
        background-color: darkred;
        padding: 0;
        margin: 0;
        height: min(calc(66.6667px + 21.6667vw),240px);
    }
    #topic>div#stuck{
        font-weight: 700;
        color: antiquewhite;
        font-size: min(calc(26.6667px + 3.66667vw),56.00006px);
        padding-left: min(calc(-20px + 8.5vw),48px);
        padding-top: min(7.2vw,57.6px);
    }
    #topic>div{
        color: wheat;
        font-size: min(calc(9.33333px + 1.13333vw),18.39997px);
        padding-left: min(calc(-113.333px + 33.6667vw),156.0006px);
        padding-top: min(calc(13.3333px + 4.33333vw),47.99994px);
    }
    #topic>img{
        display: block;
        height: auto;
        position: absolute;
        width: min(calc(-40px + 38vw),264px);
        top: max(calc(106.667px - 11.3333vw),16.0006px);
        left:min(calc(-270.4px + 97.8vw),609.8px);
    }
    .texts{
        margin:11px 30px 0 30px;
        font-size: min(calc(13.3333px + 0.533333vw),17.599964px);
    }
    a.small{
        display: block;
        font-size: 14px;
        margin-top: 12px;
        margin-left: 4px;
    }
    #outline{
        max-width: 900px;
        margin: auto;
        background-color: floralwhite;
        position: relative;
    }
    @media only screen and (max-width: 800px) {
        #topic>img{
            left: calc(80px + 54vw);
        }
    }
</style>
<body>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC&display=swap" rel="stylesheet">
<div id="outline">
    <div id="topic">
        <div id="stuck"><?php if ($lang === 'en')echo 'WE ARE STUCK!';
            elseif ($lang ==='zh')echo '服务器拉跨了!';?></div>
        <div><?php if ($lang === 'en')echo 'That saying, we got those bugs in files......';
            elseif ($lang ==='zh')echo '指的是，这些文件里有一些奇怪的BUG......';?></div>
        <img src="/resources/img/pages/bug.png" alt>
    </div>
    <a class="small" href="<?php echo set_url_parameter((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
        "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",'lang',$lang === 'en'?'zh':'en'); ?>">
        <?php if ($lang === 'en')echo '使用中文浏览';
        elseif ($lang ==='zh')echo 'View in English';?></a>
    <div class="texts" style="margin-top:40px;">
        <?php if ($lang === 'en')echo "It's not your problem. It's our fault. There are (again?) something buggy in the server.";
        elseif ($lang ==='zh')echo '这不是你的问题——是我们的问题。服务器里可能(又?)出了一些奇奇怪怪的BUG......';?></div>
    <div class="texts" style="<?php if ($logcode===null)echo 'display:none';?>">
        <?php if ($lang === 'en')echo 'The error is successfully logged to the database, and we will deal with it as soon as possible.';
        elseif ($lang ==='zh')echo '错误信息已经被成功发送到了数据库中，我们将尽快修复该问题。';?></div>
    <div class="texts" style="<?php if ($msg===null)echo 'display:none';?>">
        <?php if ($lang === 'en') echo 'Meanwhile, we are not able to log the error data to the database :(';
        elseif ($lang ==='zh') echo '同时，我们未能成功将此次错误信息发送至数据库 :(';?></div>
    <?php
    $mailto= 'mailto:' .conf('email_report_to'). '?subject=%5BERR-REPORT%5D%20We%20need%20your%20attention%20on%20this%20error&' .
        'body=There%20is%20a%20persistent%20error%20in%20your%20online%20game%20%22Hilichurls%20Revolt%22.%0D%0AIt%27s%20so%20' .
        'serious%20that%20has%20made%20us%20unable%20to%20continue%20playing%20the%20game.%0D%0A%0D%0AThe%20code%20of%20' .
        'the%20error%20is%20as%20below%3A%0D%0A' .($msg??"ERR$logcode"). '%0D%0A%0D%0APlease%20fix%20it%20as%20soon%20as%20' .
        'possible%20and%20give%20me%20a%20reply%20in%20time.'; ?>
    <div class="texts"><?php if ($lang === 'en') echo "If the problem persists, you can get in contact with us by sending us the following code to email <a href='$mailto'>".conf('email_report_to')."</a>, we'll reply you fast.";
        elseif ($lang ==='zh')echo "如果该问题持续存在，你可以把下面的代码发送到邮箱 <a href='$mailto'>".conf('email_report_to'). '</a> 中，和我们获得联系。我们会在第一时间内处理此问题。'; ?></div>
    <div class="texts" style="font-size: 34px;<?php if ($logcode===null)echo 'display:none';?>">
        <?php echo 'ERR'.$logcode;?></div>
    <div class="texts" style="border:1px rgba(128,128,128,0.31) solid;margin:19px 30px;padding:10px;color:gray;word-break:break-all;
    <?php if ($msg===null)echo 'display:none';?>"><?php echo $msg;?></div>
    <div class="texts"><?php if ($lang === 'en') echo 'Sincere apology to all the inconveniences we may have caused to you.';
        elseif ($lang ==='zh') echo '同时，我们诚挚地为所有可能对您造成的不便表示抱歉。';?></div><br>
    <a href="<?php echo$mailto;?>" class="texts"><?php if ($lang === 'en') echo 'Click here to send the email.';elseif ($lang ==='zh') echo '点击此处发送邮件。';?></a>
    <div class="texts"><?php if ($lang === 'en') echo "If you can't send the email by clicking the button, you can also do it manually. Just make sure to include the code above.";
    elseif ($lang ==='zh') echo '如果你无法通过点击按钮的方式来发送此邮件，你也可以手动发送。确保你在邮件中包含了上述的错误代码。';?></div>
    <?php echo str_repeat('<br>',7);?>
</div>
</body>
