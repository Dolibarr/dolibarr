<?php
if (! defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet'); ?>
/* <style type="text/css" > */
/*
 Badge style is based on boostrap framework
 */

.badge {
    display: inline-block;
    padding: .25em .4em;
    font-size: 80%;
    font-weight: 700 !important;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: .25rem;
    transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}

.badge-pill {
    padding-right: .6em;
    padding-left: .6em;
    border-radius: 10rem;
}

a.badge:focus, a.badge:hover {
    text-decoration: none;
}





/* PRIMARY */
.badge-primary{
    color: #fff !important;
    background-color: <?php print $badgePrimary; ?>;
}
a.badge-primary.focus, a.badge-primary:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgePrimary, 0.5); ?>;
}
a.badge-primary:focus, a.badge-primary:hover {
    color: #fff !important;
    background-color: <?php print colorDarker($badgePrimary, 10); ?>;
}

/* SECONDARY */
.badge-secondary {
    color: #fff !important;
    background-color: <?php print $badgeSecondary; ?>;
}
a.badge-secondary.focus, a.badge-secondary:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgeSecondary, 0.5); ?>;
}
a.badge-secondary:focus, a.badge-secondary:hover {
    color: #fff !important;
    background-color: <?php print colorDarker($badgeSecondary, 10); ?>;
}

/* SUCCESS */
.badge-success {
    color: #fff !important;
    background-color: <?php print $badgeSuccess; ?>;
}
a.badge-success.focus, a.badge-success:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgeSuccess, 0.5); ?>;
}
a.badge-success:focus, a.badge-success:hover {
    color: #fff !important;
    background-color: <?php print colorDarker($badgeSuccess, 10); ?>;
}

/* DANGER */
.badge-danger {
    color: #fff !important;
    background-color:  <?php print $badgeDanger; ?>;
}
a.badge-danger.focus, a.badge-danger:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgeDanger, 0.5); ?>;
}
a.badge-danger:focus, a.badge-danger:hover {
    color: #fff !important;
    background-color: <?php print colorDarker($badgeDanger, 10); ?>;
}

/* WARNING */
.badge-warning {
    color: #212529 !important;
    background-color: <?php print $badgeWarning; ?>;
}
a.badge-warning.focus, a.badge-warning:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgeWarning, 0.5); ?>;
}
a.badge-warning:focus, a.badge-warning:hover {
    color: #212529 !important;
    background-color: <?php print colorDarker($badgeWarning, 10); ?>;
}

/* INFO */
.badge-info {
    color: #fff !important;
    background-color: <?php print $badgeInfo; ?>;
}
a.badge-info.focus, a.badge-info:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgeInfo, 0.5); ?>;
}
a.badge-info:focus, a.badge-info:hover {
    color: #fff !important;
    background-color: <?php print colorDarker($badgeInfo, 10); ?>;
}

/* LIGHT */
.badge-light {
    color: #212529 !important;
    background-color: <?php print $badgeLight; ?>;
}
a.badge-light.focus, a.badge-light:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgeLight, 0.5); ?>;
}
a.badge-light:focus, a.badge-light:hover {
    color: #212529 !important;
    background-color: <?php print colorDarker($badgeLight, 10); ?>;
}

/* DARK */
.badge-dark {
    color: #fff !important;
    background-color: <?php print $badgeDark; ?>;
}
a.badge-dark.focus, a.badge-dark:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem <?php print colorHexToRgb($badgeDark, 0.5); ?>;
}
a.badge-dark:focus, a.badge-dark:hover {
    color: #fff !important;
    background-color: <?php print colorDarker($badgeDark, 10); ?>;
}

/* 
* STATUS BADGES
*/

