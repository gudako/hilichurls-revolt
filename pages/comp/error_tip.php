<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

$config = new \local\ConfigSystem();
$isServerDown = $config->IsServerDown();
$inMaintenance = $config->InMaintenance();
$alright = !$isServerDown && !$inMaintenance;
?>
<?php echo $alright?'<noscript id="noscript_checker">':'';?>
    <link href="/css/window.css" rel="stylesheet"/>
    <div id="window_modal_back">
        <div id="window" style="font-size: 21px; padding: 16px 6px; text-align: center;
         border: 3px #d70303 solid;">
            <img src="/img/pages/icons/<?php if ($isServerDown)echo 'server_down';elseif ($inMaintenance)echo 'maintenance';else echo 'warning';?>.png"
                 style="width: 42px; height: auto; display: block; margin: 0 auto 8px auto">
            <div>
                <?php
                if ($isServerDown) echo "<font style='font-weight: bold;'>SERVER ERROR<br>The server is currently down.</font>";
                elseif ($inMaintenance){
                    echo '<font style="font-weight: bold;">'.memtxt(1942,63/*REMAP%maintenance_alert_title*/). '</font>';
                    echo '<br><div style="border: 1px gray solid; padding: 6px; margin: 10px 20px 5px 20px;'.
                        ' font-size: 14px; text-align: left; max-height: 200px; overflow-y: auto; color: #3d3b3b;">';
                    $mText = file_get_contents($_SERVER['DOCUMENT_ROOT']. '/_maint.txt');
                    $lang = getlang();
                    $matches = array();
                    preg_match('/(?<=<' .$lang.">)(.|\r|\n)*(?=<\/".$lang. '>)/m',$mText,$matches);
                    echo str_replace(["\r\n","\n"], '<br>', trim($matches[0])). '</div>';
                }
                else echo memtxt(2524,137/*REMAP%pages_js_alert_text*/);?>
            </div>
        </div>
    </div>
<?php echo $alright?'</noscript>':'';?>
