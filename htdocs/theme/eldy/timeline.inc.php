<?php
/*!
	Source :
 *   AdminLTE v2.4.8
 *   Author: Almsaeed Studio
 *	 Website: Almsaeed Studio <https://adminlte.io>
 *   License: Open source - MIT
 *           Please visit http://opensource.org/licenses/MIT for more information
 */

if (!defined('ISLOADEDBYSTEELSHEET')) {
	die('Must be call by steelsheet');
} ?>
/* <style type="text/css" > */


/*
* Component: Timeline
* -------------------
*/
.timeline {
	position: relative;
	margin: 0 0 30px 0;
	padding: 0;
	list-style: none;
}
.timeline:before {
	content: '';
	position: absolute;
	top: 0;
	bottom: 0;
	width: 4px;
	background: #ddd;
	left: 25px;
	margin: 0;
	border-radius: 2px;
}
.timeline > li {
	position: relative;
	margin-right: 0;
	margin-bottom: 15px;
}
.timeline > li:before,
.timeline > li:after {
	content: " ";
	display: table;
}
.timeline > li:after {
	clear: both;
}
.timeline > li > .timeline-item {
	-webkit-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	box-shadow:  0 1px 3px rgba(0, 0, 0, 0.1);
	border:1px solid #d2d2d2;
	border-radius: 3px;
	margin-top: 0;
	background: #fff;
	color: #444;
	margin-left: 50px;
	margin-right: 0px;
	padding: 0;
	position: relative;
}

.timeline > li.timeline-code-ticket_msg_private  > .timeline-item,
.timeline > li.timeline-code-ticket_msg_private_sentbymail > .timeline-item {
		background: #fffbe5;
		border-color: #d0cfc0;
}


.timeline > li > .timeline-item > .time{
	color: #6f6f6f;
	float: right;
	padding: 10px;
	font-size: 12px;
}


.timeline > li > .timeline-item > .timeline-header-action{
	color: #6f6f6f;
	float: right;
	padding: 7px;
	font-size: 12px;
}


a.timeline-btn:link,
a.timeline-btn:visited,
a.timeline-btn:hover,
a.timeline-btn:active
{
	display: inline-block;
	margin-bottom: 0;
	font-weight: 400;
	border-radius: 0;
	box-shadow: none;
	padding: 1px 5px;
	font-size: 12px;
	line-height: 1.5;
	text-align: center;
	white-space: nowrap;
	vertical-align: middle;
	touch-action: manipulation;
	cursor: pointer;
	user-select: none;
	background-image: none;
	text-decoration: none;
	background-color: #f4f4f4;
	color: #444;
	border: 1px solid #ddd;
}

a.timeline-btn:hover
{
	background-color: #e7e7e7;
	color: #333;
	border-color: #adadad;
}


.timeline > li > .timeline-item > .timeline-header {
	margin: 0;
	color: #333;
	border-bottom: 1px solid #f4f4f4;
	padding: 10px;
	font-size: 1em;
	font-weight: normal;
	line-height: 1.1;
}

.timeline > li > .timeline-item > .timeline-footer {
	border-top: 1px solid #f4f4f4;
}

.timeline > li.timeline-code-ticket_msg_private  > .timeline-item > .timeline-header, .timeline > li.timeline-code-ticket_msg_private  > .timeline-item > .timeline-footer {
	border-color: #ecebda;
}

.timeline > li > .timeline-item > .timeline-header > a {
	font-weight: 600;
}
.timeline > li > .timeline-item > .timeline-body,
.timeline > li > .timeline-item > .timeline-footer {
	padding: 10px;
}
.timeline > li > .fa,
.timeline > li > .fas,
.timeline > li > .glyphicon,
.timeline > li > .ion {
	width: 30px;
	height: 30px;
	font-size: 1em;
	line-height: 30px;
	position: absolute;
	color: #666;
	background: #d2d6de;
	border-radius: 50%;
	text-align: center;
	left: 12px;
	top: 5px;
}
.timeline > .time-label > span {
	font-weight: 600;
	padding: 5px;
	display: inline-block;
	background-color: #fff;
	border-radius: 4px;
}
.timeline-inverse > li > .timeline-item {
	background: #f0f0f0;
	border: 1px solid #ddd;
	-webkit-box-shadow: none;
	box-shadow: none;
}
.timeline-inverse > li > .timeline-item > .timeline-header {
	border-bottom-color: #ddd;
}

.timeline-icon-todo,
.timeline-icon-in-progress,
.timeline-icon-done{
	color: #fff !important;
}

.timeline-icon-not-applicble{
	color: #000;
	background-color: #f7f7f7;
}

.timeline-icon-todo{
	background-color: #dd4b39 !important;
}

.timeline-icon-in-progress{
	background-color: #00c0ef !important;
}
.timeline-icon-done{
	background-color: #00a65a !important;
}


.timeline-badge-date{
	background-color: #0073b7 !important;
	color: #fff !important;
}

.timeline-item .messaging-title {
	word-break: break-all;
}

.timeline-documents-container{

}

.timeline-documents{
	margin-right: 5px;
}

.messaging-author {
	width: 100px;
}

.readmore-block.--closed .readmore-block__full-text, .readmore-block.--open .readmore-block__excerpt{
	display: none;
}

.read-less-link, .read-more-link{
	font-weight: bold;
}

.read-less-link{
	display: block;
	text-align: center;
}


	.read-less-link .fa, .read-more-link .fa{
	color: inherit;
}

/* Force values for small screen 767 */
@media only screen and (max-width: 767px)
{
	.messaging-author.inline-block {
		padding-bottom: 10px;
	}
}
