<?php require_once $_SERVER['DOCUMENT_ROOT']. '/vendor/autoload.php'; ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<link href="/css/mainbar.css" rel="stylesheet"/>
<div id="mainbar">
    <img id="mainbar_logo" src="img/pages/logo_<?php echo getlang();?>.png">

    <!--only shown on larger screen-->
    <?php
    function add_normal_link(string $name, int $textMemOffset, int $textMemSize): void{
        echo '<a class="norm" href="'. $name .'.php"><img src="img/pages/icons/'. $name .'.png"><span>'.
            memtxt($textMemOffset, $textMemSize) . '</span></a>' . PHP_EOL;
    }
    function add_normal_links(): void{
        add_normal_link('mainpage',2005,39/*REMAP%mainbar_mainpage*/);
        add_normal_link('achievements',2044,35/*REMAP%mainbar_achievements*/);
        add_normal_link('handbook',2079,38/*REMAP%mainbar_handbook*/);
    }
    add_normal_links();
    ?>

    <!--only shown on smaller screen-->
    <span id="mainbar_menu_button">
        <img id="top_list_icon" src="/resources/img/pages/icons/top_list.png">
    </span>

    <!--the login button-->
    <span id="mainbar_login_button">
        <img src="/resources/img/pages/icons/play.png">
        <?php echo memtxt(2117,40/*REMAP%mainbar_play*/);?>
    </span>

    <!--dropdown list to set language-->
    <script>
        function setLangCookie(target = "lang"){
            const date = new Date();
            date.setTime(date.getTime() + (365*24*60*60*1000));
            const expires = "; expires=" + date.toUTCString();
            document.cookie = "lang=" + ($("#"+target+" option:selected").val() || "")  + expires + "; path=/";
            location.reload();
        }
    </script>
    <select id="mainbar_langselect" onchange="setLangCookie('lang');">
        <option value="en" <?php echo getlang()== 'en' ? 'selected' : '';?>>English</option>
        <option value="zh" <?php echo getlang()== 'zh' ? 'selected' : '';?>>中文</option>
    </select>

    <!--dropdown list for smaller screen-->
    <div id="mainbar_menu" style="display: none">
        <?php add_normal_links();?>
        <a class="mainbar_normal" id="mainbar_menu_setlang"><span><?php echo memtxt(2157,48/*REMAP%mainbar_menu_change_lang*/);?></span></a>
    </div>

    <!--for the language change-->
    <link href="/css/window.css" rel="stylesheet"/>
    <script src="/layout/old/ModelWindow.js"></script>
    <script>
        let modelWindow;
        $("#mainbar_menu_setlang").click(()=> {
            whenSthMoved("");
            const titleSetting = ModelWindow.createTitleSetting("<?php echo memtxt(2205,51/*REMAP%setlang_window_title*/);?>");
            modelWindow = new ModelWindow(titleSetting, "setlang");
            modelWindow.open();
        });
    </script>

    <!--script for the dropdown list-->
    <script>
        const topMenu = $("#mainbar_menu");
        const topMenuButton = $("#mainbar_menu_button");
        const fadeOutTopMenu = function(){
            $(document).add($(window)).off(".closeMenu");
            topMenu.fadeOut("fast", ()=>topMenuButton.removeAttr("opened"));
        };
        topMenuButton.click(function(){
            if (topMenuButton.attr("opened") !== "true"){
                let pos = topMenuButton.offset();
                pos.top += 40;
                topMenu.fadeIn("fast", ()=> topMenuButton.attr("opened", true));
                topMenu.offset(pos);

                $(document).on("click.closeMenu", whenSthMoved);
                $(window).on("resize.closeMenu", whenSthMoved);
                $(window).on("scroll.closeMenu", whenSthMoved);
            }
            else fadeOutTopMenu();
        });
        const whenSthMoved = function(event) {
            if (event.type === "click"){
                const target = $(event.target);
                if (!target.closest(topMenu).length && !target.closest(topMenuButton).length) fadeOutTopMenu();
            }
            else fadeOutTopMenu();
        };
    </script>
</div>
