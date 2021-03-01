<?php
if (!defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet'); ?>
/* <style type="text/css" > */
/*
 progress style is based on boostrap and admin lte framework
 */


/*
 * Component: Progress Bar
 * -----------------------
 */

.progress * {
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-sizing: border-box;
}

.progress {
    height: 20px;
    overflow: hidden;
    background-color: #f5f5f5;
    background-color: rgba(128, 128, 128, 0.1);
    border-radius: 4px;
    -webkit-box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}

.progress.spaced{
    margin-bottom: 20px;
}

.progress-bar {
    float: left;
    width: 0;
    height: 100%;
    font-size: 12px;
    line-height: 20px;
    color: #fff;
    text-align: center;
    background-color: #337ab7;
    -webkit-box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
    box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
    -webkit-transition: width .6s ease;
    -o-transition: width .6s ease;
    transition: width .6s ease;
}



.progress-group > .progress{
    clear: both;
}

.progress,
.progress > .progress-bar {
    -webkit-box-shadow: none;
    box-shadow: none;
}
.progress,
.progress > .progress-bar,
.progress .progress-bar,
.progress > .progress-bar .progress-bar {
    border-radius: 1px;
}
/* size variation */
.progress.sm,
.progress-sm {
    height: 10px;
}
.progress.sm,
.progress-sm,
.progress.sm .progress-bar,
.progress-sm .progress-bar {
    border-radius: 1px;
}
.progress.xs,
.progress-xs {
    height: 7px;
}
.progress.xs,
.progress-xs,
.progress.xs .progress-bar,
.progress-xs .progress-bar {
    border-radius: 1px;
}
.progress.xxs,
.progress-xxs {
    height: 3px;
}
.progress.xxs,
.progress-xxs,
.progress.xxs .progress-bar,
.progress-xxs .progress-bar {
    border-radius: 1px;
}


/* Vertical bars */
.progress.vertical {
    position: relative;
    width: 30px;
    height: 200px;
    display: inline-block;
    margin-right: 10px;
}
.progress.vertical > .progress-bar {
    width: 100%;
    position: absolute;
    bottom: 0;
}
.progress.vertical.sm,
.progress.vertical.progress-sm {
    width: 20px;
}
.progress.vertical.xs,
.progress.vertical.progress-xs {
    width: 10px;
}
.progress.vertical.xxs,
.progress.vertical.progress-xxs {
    width: 3px;
}
.progress-group .progress-text {
    /* font-weight: 600; */
}
.progress-group .progress-number {
    float: right;
}



/* Remove margins from progress bars when put in a table */
.table tr > td .progress {
    margin: 0;
}
.progress-bar-light-blue,
.progress-bar-primary {
    background-color: #3c8dbc;
}
.progress-striped .progress-bar-light-blue,
.progress-striped .progress-bar-primary {
    background-image: -webkit-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: -o-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
}
.progress-bar-green, .progress-bar-success {
    background-color: <?php echo $badgeSuccess ?>;
}
.progress-striped .progress-bar-green, .progress-striped .progress-bar-success {
    background-image: -webkit-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: -o-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
}
body[class*="colorblind-"] .progress-bar-green, body[class*="colorblind-"] .progress-bar-success {
    background-color: <?php echo $colorblind_deuteranopes_badgeSuccess ?>;
}
body[class*="colorblind-"] .progress-bar-red, body[class*="colorblind-"] .progress-bar-danger {
    background-color: <?php echo $colorblind_deuteranopes_badgeDanger ?>;
}

.progress-bar-aqua,
.progress-bar-info {
    background-color: #00c0ef;
}
.progress-striped .progress-bar-aqua,
.progress-striped .progress-bar-info {
    background-image: -webkit-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: -o-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
}
.progress-bar-yellow,
.progress-bar-warning {
    background-color: <?php echo $badgeWarning ?>;
}
.progress-striped .progress-bar-yellow,
.progress-striped .progress-bar-warning {
    background-image: -webkit-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: -o-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
}
.progress-bar-red,
.progress-bar-danger {
    background-color: <?php echo $badgeDanger ?>;
}
.progress-striped .progress-bar-red,
.progress-striped .progress-bar-danger {
    background-image: -webkit-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: -o-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
}
.progress-bar-consumed {
	background-color: rgb(0, 0, 0, 0.15);
}