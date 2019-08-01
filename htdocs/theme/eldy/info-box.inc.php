<?php
if (! defined('ISLOADEDBYSTEELSHEET'))  die('Must be call by steelsheet'); ?>
/* <style type="text/css" > */

/*
 * Component: Info Box
 * -------------------
 */
.info-box {
	display: block;
	min-height: 90px;
	background: #fff;
	width: 100%;
	box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
	border-radius: 2px;
	margin-bottom: 15px;
}
.info-box small {
	font-size: 14px;
}
.info-box .progress {
	background: rgba(0, 0, 0, 0.2);
	margin: 5px -10px 5px -10px;
	height: 2px;
}
.info-box .progress,
.info-box .progress .progress-bar {
	border-radius: 0;
}
.info-box .progress .progress-bar {
	background: #fff;
}
.info-box-icon {
	border-top-left-radius: 2px;
	border-top-right-radius: 0;
	border-bottom-right-radius: 0;
	border-bottom-left-radius: 2px;
	display: block;
	float: left;
	height: 90px;
	width: 90px;
	text-align: center;
	font-size: 45px;
	line-height: 90px;
	background: rgba(0, 0, 0, 0.2);
}
.info-box-icon > img {
	max-width: 100%;
}
.info-box-content {
	padding: 5px 10px;
	margin-left: 90px;
}
.info-box-number {
	display: block;
	font-weight: bold;
	font-size: 18px;
}
.progress-description,
.info-box-text {
	display: block;
	font-size: 14px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.info-box-text {
	text-transform: uppercase;
}
.info-box-more {
	display: block;
}
.progress-description {
	margin: 0;
}

.info-box-icon {
	color: #fff !important;
}

.bg-infoxbox-action{
	background-color: #d81b60 !important;
}
.fa-dol-action:before {
	content: "\f073";
}

.bg-infoxbox-project{
	background-color: #605ca8 !important;
}
.fa-dol-project:before {
	content: "\f0e8";
}





.box-flex-container{
	display: flex; /* or inline-flex */
	flex-direction: row;
	flex-wrap: wrap;
	width: 100%;
	/*justify-content: space-between;*/
}

.box-flex-item{
	flex-grow : 2;
	flex-shrink: 1;
	flex-basis: auto;

	width: 280px;
	padding: 5px 10px;
}
