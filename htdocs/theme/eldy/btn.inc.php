<?php
if (! defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet'); ?>
/* <style type="text/css" > */



/* ============================================================================== */
/* Boutons actions                                                                */
/* ============================================================================== */

div.divButAction {
    margin-bottom: 1.4em;
}
div.tabsAction > a.butAction, div.tabsAction > a.butActionRefused {
    margin-bottom: 1.4em !important;
}
div.tabsActionNoBottom > a.butAction, div.tabsActionNoBottom > a.butActionRefused {
    margin-bottom: 0 !important;
}

span.butAction, span.butActionDelete {
    cursor: pointer;
}

.butAction {
    background: rgb(225, 231, 225)
    /* background: rgb(230, 232, 239); */
}
.butActionRefused, .butAction, .butAction:link, .butAction:visited, .butAction:hover, .butAction:active, .butActionDelete, .butActionDelete:link, .butActionDelete:visited, .butActionDelete:hover, .butActionDelete:active {
    text-decoration: none;
    text-transform: uppercase;
    font-weight: bold;

    margin: 0em <?php echo ($dol_optimize_smallscreen?'0.6':'0.9'); ?>em !important;
    padding: 0.6em <?php echo ($dol_optimize_smallscreen?'0.6':'0.7'); ?>em;
    font-family: <?php print $fontlist ?>;
    display: inline-block;
    text-align: center;
    cursor: pointer;
    /* color: #fff; */
    /* background: rgb(<?php echo $colorbackhmenu1 ?>); */
    color: #444;
    /* border: 1px solid #aaa; */
    /* border-color: rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.25); */

    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
}
.butActionNew, .butActionNewRefused, .butActionNew:link, .butActionNew:visited, .butActionNew:hover, .butActionNew:active {
    text-decoration: none;
    text-transform: uppercase;
    font-weight: normal;

    margin: 0em 0.3em 0 0.3em !important;
    padding: 0.2em <?php echo ($dol_optimize_smallscreen?'0.4':'0.7'); ?>em 0.3em;
    font-family: <?php print $fontlist ?>;
    display: inline-block;
    /* text-align: center; New button are on right of screen */
    cursor: pointer;
    /*color: #fff !important;
    background: rgb(<?php echo $colorbackhmenu1 ?>);
border: 1px solid rgb(<?php echo $colorbackhmenu1 ?>);
border-color: rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.25);
border-top-right-radius: 0 !important;
border-bottom-right-radius: 0 !important;
border-top-left-radius: 0 !important;
border-bottom-left-radius: 0 !important;*/
}
a.butActionNew>span.fa-plus-circle, a.butActionNew>span.fa-plus-circle:hover { padding-left: 6px; font-size: 1.5em; border: none; box-shadow: none; webkit-box-shadow: none; }
a.butActionNewRefused>span.fa-plus-circle, a.butActionNewRefused>span.fa-plus-circle:hover { padding-left: 6px; font-size: 1.5em; border: none; box-shadow: none; webkit-box-shadow: none; }

.butAction:hover   {
    -webkit-box-shadow: 0px 0px 6px 1px rgba(50, 50, 50, 0.4), 0px 0px 0px rgba(60,60,60,0.1);
    box-shadow: 0px 0px 6px 1px rgba(50, 50, 50, 0.4), 0px 0px 0px rgba(60,60,60,0.1);
}
.butActionNew:hover   {
    text-decoration: underline;
    box-shadow: unset !important;
}

.butActionDelete, .butActionDelete:link, .butActionDelete:visited, .butActionDelete:hover, .butActionDelete:active, .buttonDelete {
    background: rgb(234, 228, 225);
    /* border: 1px solid #633; */
    color: #633;
}

.butActionDelete:hover {
    -webkit-box-shadow: 0px 0px 6px 1px rgba(50, 50, 50, 0.4), 0px 0px 0px rgba(60,60,60,0.1);
    box-shadow: 0px 0px 6px 1px rgba(50, 50, 50, 0.4), 0px 0px 0px rgba(60,60,60,0.1);
}

.butActionRefused {
    text-decoration: none !important;
    text-transform: uppercase;
    font-weight: bold !important;

    white-space: nowrap !important;
    cursor: not-allowed !important;
    margin: 0em <?php echo ($dol_optimize_smallscreen?'0.6':'0.9'); ?>em;
    padding: 0.6em <?php echo ($dol_optimize_smallscreen?'0.6':'0.7'); ?>em;
    font-family: <?php print $fontlist ?> !important;
    display: inline-block;
    text-align: center;
    cursor: pointer;
    color: #999 !important;
    border: 1px solid #ccc;
    box-sizing: border-box;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
}
.butActionNewRefused, .butActionNewRefused:link, .butActionNewRefused:visited, .butActionNewRefused:hover, .butActionNewRefused:active {
    text-decoration: none !important;
    text-transform: uppercase;
    font-weight: normal !important;

    white-space: nowrap !important;
    cursor: not-allowed !important;
    margin: 0em <?php echo ($dol_optimize_smallscreen?'0.7':'0.9'); ?>em;
    padding: 0.2em <?php echo ($dol_optimize_smallscreen?'0.4':'0.7'); ?>em;
    font-family: <?php print $fontlist ?> !important;
    display: inline-block;
    /* text-align: center;  New button are on right of screen */
    cursor: pointer;
    color: #999 !important;
    padding-top: 0.2em;
    box-shadow: none !important;
    -webkit-box-shadow: none !important;
}

.butActionTransparent {
    color: #222 ! important;
    background-color: transparent ! important;
}


/*
TITLE BUTTON
 */

.title-button, a.title-button {
    display: inline-block;
    padding: 6px 12px;
    font-size: 14px
    font-weight: 400;
    line-height: 1.4;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    -ms-touch-action: manipulation;
    touch-action: manipulation;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    box-shadow: none;
    text-decoration: none;
    position: relative;
    margin: 0 0 0 10px;
    min-width: 80px;
    text-align: center;
    color: rgb(<?php print $colortextlink; ?>);
    border: none;
    font-size: 12px;
    font-weight: 300;
    background-color: #fbfbfb;
}

.title-button > .title-button-icon{

}

.title-button > .title-button-label{
    color: #666666;
}

.title-button:hover, a.title-button:hover {
    border-radius: 3px;
    position: relative;
    margin: 0 0 0 10px;
    text-align: center;
    color: #ffffff;
    background-color: rgb(<?php print $colortextlink; ?>);
    font-size: 12px;
    text-decoration: none;
    box-shadow: none;
}

.title-button.title-button-refused, a.title-button.title-button-refused, .title-button.title-button-refused:hover, a.title-button.title-button-refused:hover {
        color: #8a8a8a;
        cursor: not-allowed;
        background-color: #fbfbfb;
        background: repeating-linear-gradient( 45deg, #ffffff, #f1f1f1 4px, #f1f1f1 4px, #f1f1f1 4px );
}

.title-button:hover .title-button-label{
    color: #ffffff;
}

.title-button.title-button-refused .title-button-label, .title-button.title-button-refused:hover .title-button-label{
    color: #8a8a8a;
}

.title-button>.fa {
    font-size: 20px;
    display: block;
}





<?php if (! empty($conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED) && (! $user->admin)) { ?>
.butActionRefused, .butActionNewRefused, .title-button.title-button-refused {
    display: none !important;
}
<?php } ?>