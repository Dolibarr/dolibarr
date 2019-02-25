<?php 
/*
 Badge style is based on boostrap framework
 */

if (! defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet'); ?>
/* <style type="text/css" > */


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
    background-color: #007bff;
}
a.badge-primary.focus, a.badge-primary:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.5);
}
a.badge-primary:focus, a.badge-primary:hover {
    color: #fff !important;
    background-color: #0062cc;
}

/* SECONDARY */
.badge-secondary {
    color: #fff !important;
    background-color: #6c757d;
}
a.badge-secondary.focus, a.badge-secondary:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(108,117,125,.5);
}
a.badge-secondary:focus, a.badge-secondary:hover {
    color: #fff !important;
    background-color: #545b62;
}

/* SUCCESS */
.badge-success {
    color: #fff !important;
    background-color: #28a745;
}
a.badge-success.focus, a.badge-success:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(40,167,69,.5);
}
a.badge-success:focus, a.badge-success:hover {
    color: #fff !important;
    background-color: #1e7e34;
}

/* DANGER */
.badge-danger {
    color: #fff !important;
    background-color: #dc3545;
}
a.badge-danger.focus, a.badge-danger:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(220,53,69,.5);
}
a.badge-danger:focus, a.badge-danger:hover {
    color: #fff !important;
    background-color: #bd2130;
}

/* WARNING */
.badge-warning {
    color: #212529 !important;
    background-color: #ffc107;
}
a.badge-warning.focus, a.badge-warning:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(255,193,7,.5);
}
a.badge-warning:focus, a.badge-warning:hover {
    color: #212529 !important;
    background-color: #d39e00;
}

/* INFO */
.badge-info {
    color: #fff !important;
    background-color: #17a2b8;
}
a.badge-info.focus, a.badge-info:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(23,162,184,.5);
}
a.badge-info:focus, a.badge-info:hover {
    color: #fff !important;
    background-color: #117a8b;
}

/* LIGHT */
.badge-light {
    color: #212529 !important;
    background-color: #f8f9fa;
}
a.badge-light.focus, a.badge-light:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(248,249,250,.5);
}
a.badge-light:focus, a.badge-light:hover {
    color: #212529 !important;
    background-color: #dae0e5;
}

/* DARK */
.badge-dark {
    color: #fff !important;
    background-color: #343a40;
}
a.badge-dark.focus, a.badge-dark:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(52,58,64,.5);
}
a.badge-dark:focus, a.badge-dark:hover {
    color: #fff !important;
    background-color: #1d2124;
}