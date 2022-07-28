<?php require_once $_SERVER['DOCUMENT_ROOT']. '/vendor/autoload.php'; ?>

<div id="tip" style="position: fixed; left: 0; right: 0; opacity: 0.89; color: #c04444; z-index: 33; top: 82px;
text-align: center; display: none">
    <span style="margin: auto; border-radius: 4px; border: 3px #c04444 solid; font-size: 16px; padding: 6px;
    background-color: rgba(245, 245, 245, 0.9);">
        <?php echo memtxt(2393,131/*REMAP%maintenance_issued_alert*/);?>
    </span>
</div>
<script>
    let originText = null;
    const grab = function ()
    {
        $.get("/calls/maint_check").done((data)=>
        {
            const ret = data.toString();
            if (ret==='true')location.reload();
            else if (ret!=='false')
            {
                const hrs = ret.split(',')[0];
                const min = ret.split(',')[1];
                const spanObj = $('#tip>span');
                spanObj.text(originText.replace('%H',hrs).replace('%I',min));
                $('#tip').css('display','block');
            }
        });
    };
    $(document).ready(()=>{
        if ($('#noscript_checker').length===0) return;
        originText = $('#tip>span').text();
        grab();
        setInterval(grab, 5000);
    });
</script>
