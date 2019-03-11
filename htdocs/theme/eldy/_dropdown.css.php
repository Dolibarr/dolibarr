<?php
if (! defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet'); ?>
/* <style type="text/css" > */
/*
 Dropdown
*/
 

.open>.dropdown-menu {
    display: block;
}
.dropdown-menu {
    box-shadow: none;
    border-color: #eee;
}
.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    display: none;
    float: left;
    min-width: 160px;
    padding: 5px 0;
    margin: 2px 0 0;
    font-size: 14px;
    text-align: left;
    list-style: none;
    background-color: #fff;
    -webkit-background-clip: padding-box;
    background-clip: padding-box;
    border: 1px solid #ccc;
    border: 1px solid rgba(0,0,0,.15);
    border-radius: 4px;
    -webkit-box-shadow: 0 6px 12px rgba(0,0,0,.175);
    box-shadow: 0 6px 12px rgba(0,0,0,.175);
}



/*
* MENU Dropdown 
*/
 
.tmenu .dropdown-menu, .login_block .dropdown-menu {
    position: absolute;
    right: 0;
    left: auto;
}
.tmenu .dropdown-menu, .login_block  .dropdown-menu .user-body {
    border-bottom-right-radius: 4px;
    border-bottom-left-radius: 4px;
}
.side-nav-vert .user-menu .dropdown-menu {
    border-top-right-radius: 0;
    border-top-left-radius: 0;
    padding: 1px 0 0 0;
    border-top-width: 0;
    width: 280px;
}
.side-nav-vert .user-menu .dropdown-menu {
    margin-top: 0;
    border-top-left-radius: 0;
    border-top-right-radius: 0;
}

.side-nav-vert .user-menu .dropdown-menu > .user-header {
    height: 175px;
    padding: 10px;
    text-align: center;
}

 
.dropdown-menu > li.user-header > img {
    z-index: 5;
    height: 90px;
    width: 90px;
    border: 3px solid;
    border-color: transparent;
    border-color: rgba(255, 255, 255, 0.2);
}


.dropdown-user-image {
    border-radius: 50%;
    vertical-align: middle;
    z-index: 5;
    height: 90px;
    width: 90px;
    border: 3px solid;
    border-color: transparent;
    border-color: rgba(255, 255, 255, 0.2);
    max-width: 100%;
    max-height :100%;
}

.dropdown-menu > .user-header{
    background: rgb(<?php echo $colorbackhmenu1 ?>);
}

 