<?php // BEGIN PHP
$websitekey=basename(__DIR__);
if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once __DIR__.'/master.inc.php'; } // Load env if not already loaded
require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';
ob_start();
if (! headers_sent()) {	/* because file is included inline when in edit mode and we don't want warning */
header('Cache-Control: max-age=3600, public, must-revalidate');
header('Content-type: text/css');
}
// END PHP ?>
/* CSS content (all pages) */
div.bodywebsite { margin: 0; font-family: 'Open Sans', sans-serif; }
.bodywebsite h1 { margin-top: 0; margin-bottom: 0; padding: 10px;}

html {
	scroll-behavior: smooth
}

.bodywebsite .centpercent {
	width: 100%;
}
.bodywebsite .center {
	text-align: center;
}

.bodywebsite span.icon.alt {
	font-size: 0.5em;
}
.bodywebsite .fab.alt:before {
	font-family: "Font Awesome 5 Brands" !important;
}

.bodywebsite .logowebsite {
	width: 128px;
	height: 128px;
	border-radius: 50%;
	background-size: contain;
	background-size: contain;
}

.bodywebsite .blog-box {
	box-shadow: -1px -1px 12px 5px rgba(85, 85, 85, 0.1) !important;
}

html.bodywebsite,
.bodywebsite,
.bodywebsite div,
.bodywebsite span,
.bodywebsite applet,
.bodywebsite object,
.bodywebsite iframe,
.bodywebsite h1,
.bodywebsite h2,
.bodywebsite h3,
.bodywebsite h4,
.bodywebsite h5,
.bodywebsite h6,
.bodywebsite p,
.bodywebsite blockquote,
.bodywebsite pre,
.bodywebsite a,
.bodywebsite abbr,
.bodywebsite acronym,
.bodywebsite address,
.bodywebsite big,
.bodywebsite cite,
.bodywebsite code,
.bodywebsite del,
.bodywebsite dfn,
.bodywebsite em,
.bodywebsite img,
.bodywebsite ins,
.bodywebsite kbd,
.bodywebsite q,
.bodywebsite s,
.bodywebsite samp,
.bodywebsite small,
.bodywebsite strike,
.bodywebsite strong,
.bodywebsite sub,
.bodywebsite sup,
.bodywebsite tt,
.bodywebsite var,
.bodywebsite b,
.bodywebsite u,
.bodywebsite i,
.bodywebsite center,
.bodywebsite dl,
.bodywebsite dt,
.bodywebsite dd,
.bodywebsite ol,
.bodywebsite ul,
.bodywebsite li,
.bodywebsite fieldset,
.bodywebsite form,
.bodywebsite label,
.bodywebsite legend,
.bodywebsite table,
.bodywebsite caption,
.bodywebsite tbody,
.bodywebsite tfoot,
.bodywebsite thead,
.bodywebsite tr,
.bodywebsite th,
.bodywebsite td,
.bodywebsite article,
.bodywebsite aside,
.bodywebsite canvas,
.bodywebsite details,
.bodywebsite embed,
.bodywebsite figure,
.bodywebsite figcaption,
.bodywebsite footer,
.bodywebsite header,
.bodywebsite hgroup,
.bodywebsite menu,
.bodywebsite nav,
.bodywebsite output,
.bodywebsite ruby,
.bodywebsite section,
.bodywebsite summary,
.bodywebsite time,
.bodywebsite mark,
.bodywebsite audio,
.bodywebsite video {
  margin: 0;
  padding: 0;
  border: 0;
  font-size: 100%;
  font: inherit;
  vertical-align: baseline;
}
.bodywebsite article,
.bodywebsite aside,
.bodywebsite details,
.bodywebsite figcaption,
.bodywebsite figure,
.bodywebsite footer,
.bodywebsite header,
.bodywebsite hgroup,
.bodywebsite menu,
.bodywebsite nav,
.bodywebsite section {
  display: block;
}
.bodywebsite {
	line-height: 1;
	background-image: -moz-linear-gradient(45deg, #4376e2 15%, #5f4d93 85%);
	background-image: -webkit-linear-gradient(45deg, #4376e2 15%, #5f4d93 85%);
	background-image: -ms-linear-gradient(45deg, #4376e2 15%, #5f4d93 85%);
	background-image: linear-gradient(45deg, #4376e2 15%, #5f4d93 85%);
}
.bodywebsite ol,
.bodywebsite ul {
  list-style: none;
}
.bodywebsite blockquote,
.bodywebsite q {
  quotes: none;
}
.bodywebsite blockquote:before,
.bodywebsite blockquote:after,
.bodywebsite q:before,
.bodywebsite q:after {
  content: '';
  content: none;
}
.bodywebsite table {
  border-collapse: collapse;
  border-spacing: 0;
}
.bodywebsite {
  -webkit-text-size-adjust: none;
}
.bodywebsite mark {
  background-color: transparent;
  color: inherit;
}
.bodywebsite input::-moz-focus-inner {
  border: 0;
  padding: 0;
}
.bodywebsite input,
.bodywebsite select,
.bodywebsite textarea {
  -moz-appearance: none;
  -webkit-appearance: none;
  -ms-appearance: none;
  appearance: none;
}
@-ms-viewport {
  width: device-width;
}
.bodywebsite {
  -ms-overflow-style: scrollbar;
}
@media screen and (max-width: 480px) {
  .bodywebsite html,
  .bodywebsite {
	min-width: 320px;
  }
}
.bodywebsite html {
  box-sizing: border-box;
}
.bodywebsite *,
.bodywebsite *:before,
.bodywebsite *:after {
  box-sizing: inherit;
}
.bodywebsite  {
  background-color: #935d8c;
}
.bodywebsite.is-preload *,
.bodywebsite.is-preload *:before,
.bodywebsite.is-preload *:after {
  -moz-animation: none !important;
  -webkit-animation: none !important;
  -ms-animation: none !important;
  animation: none !important;
  -moz-transition: none !important;
  -webkit-transition: none !important;
  -ms-transition: none !important;
  transition: none !important;
}
.bodywebsite {
  background-color: #935d8c;
  color: rgba(255, 255, 255, 0.65);
}
.bodywebsite {
  font-family: "Source Sans", Helvetica, sans-serif;
  font-weight: 300;
  line-height: 1.65;
}
@media screen and (max-width: 1680px) {
  .bodywebsite body,
  .bodywebsite input,
  .bodywebsite select,
  .bodywebsite textarea {
	font-size: 14pt;
  }
}
@media screen and (max-width: 1280px) {
  .bodywebsite body,
  .bodywebsite input,
  .bodywebsite select,
  .bodywebsite textarea {
	font-size: 12pt;
  }
}
@media screen and (max-width: 360px) {
  .bodywebsite body,
  .bodywebsite input,
  .bodywebsite select,
  .bodywebsite textarea {
	font-size: 11pt;
  }
}
.bodywebsite a {
  -moz-transition: color 0.2s ease, border-bottom 0.2s ease;
  -webkit-transition: color 0.2s ease, border-bottom 0.2s ease;
  -ms-transition: color 0.2s ease, border-bottom 0.2s ease;
  transition: color 0.2s ease, border-bottom 0.2s ease;
  text-decoration: none;
  border-bottom: dotted 1px;
  color: inherit;
}
.bodywebsite a:hover {
  border-bottom-color: transparent;
}
.bodywebsite strong,
.bodywebsite b {
  font-weight: 400;
}
.bodywebsite em,
.bodywebsite i {
  font-style: italic;
}
.bodywebsite p {
  margin: 0 0 2em 0;
}
.bodywebsite p.content {
  -moz-columns: 20em 2;
  -webkit-columns: 20em 2;
  -ms-columns: 20em 2;
  columns: 20em 2;
  -moz-column-gap: 2em;
  -webkit-column-gap: 2em;
  -ms-column-gap: 2em;
  column-gap: 2em;
  text-align: justify;
}
.bodywebsite h1,
.bodywebsite h2,
.bodywebsite h3,
.bodywebsite h4,
.bodywebsite h5,
.bodywebsite h6 {
  font-weight: 300;
  line-height: 1.5;
  margin: 0 0 0.7em 0;
  letter-spacing: -0.025em;
}
.bodywebsite h1 a,
.bodywebsite h2 a,
.bodywebsite h3 a,
.bodywebsite h4 a,
.bodywebsite h5 a,
.bodywebsite h6 a {
  color: inherit;
  text-decoration: none;
}
.bodywebsite h1 {
  font-size: 2.5em;
  line-height: 1.2;
}
.bodywebsite h2 {
  font-size: 1.5em;
}
.bodywebsite h3 {
  font-size: 1.25em;
}
.bodywebsite h4 {
  font-size: 1.1em;
}
.bodywebsite h5 {
  font-size: 0.9em;
}
.bodywebsite h6 {
  font-size: 0.7em;
}
@media screen and (max-width: 736px) {
  .bodywebsite h1 {
	font-size: 2em;
  }
}
.bodywebsite sub {
  font-size: 0.8em;
  position: relative;
  top: 0.5em;
}
.bodywebsite sup {
  font-size: 0.8em;
  position: relative;
  top: -0.5em;
}
.bodywebsite blockquote {
  border-left: solid 4px;
  font-style: italic;
  margin: 0 0 2em 0;
  padding: 0.5em 0 0.5em 2em;
}
.bodywebsite code {
  border-radius: 8px;
  border: solid 1px;
  font-family: "Courier New", monospace;
  font-size: 0.9em;
  margin: 0 0.25em;
  padding: 0.25em 0.65em;
}
.bodywebsite pre {
  -webkit-overflow-scrolling: touch;
  font-family: "Courier New", monospace;
  font-size: 0.9em;
  margin: 0 0 2em 0;
}
.bodywebsite pre code {
  display: block;
  line-height: 1.75;
  padding: 1em 1.5em;
  overflow-x: auto;
}
.bodywebsite hr {
  border: 0;
  border-bottom: solid 1px;
  margin: 2em 0;
}
.bodywebsite hr.major {
  margin: 3em 0;
}
.bodywebsite .align-left {
  text-align: left;
}
.bodywebsite .align-center {
  text-align: center;
}
.bodywebsite .align-right {
  text-align: right;
}
.bodywebsite input,
.bodywebsite select,
.bodywebsite textarea {
  color: #ffffff;
}
.bodywebsite a:hover {
  color: #ffffff;
}
.bodywebsite strong,
.bodywebsite b {
  color: #ffffff;
}
.bodywebsite h1,
.bodywebsite h2,
.bodywebsite h3,
.bodywebsite h4,
.bodywebsite h5,
.bodywebsite h6 {
  color: #ffffff;
}
.bodywebsite blockquote {
  border-left-color: rgba(255, 255, 255, 0.35);
}
.bodywebsite code {
  background: rgba(255, 255, 255, 0.075);
  border-color: rgba(255, 255, 255, 0.35);
}
.bodywebsite hr {
  border-bottom-color: rgba(255, 255, 255, 0.35);
}
.bodywebsite .row {
  display: flex;
  flex-wrap: wrap;
  box-sizing: border-box;
  align-items: stretch;
}
.bodywebsite .row > * {
  box-sizing: border-box;
}
.bodywebsite .row.aln-left {
  justify-content: flex-start;
}
.bodywebsite .row.aln-center {
  justify-content: center;
}
.bodywebsite .row.aln-right {
  justify-content: flex-end;
}
.bodywebsite .row.aln-top {
  align-items: flex-start;
}
.bodywebsite .row.aln-middle {
  align-items: center;
}
.bodywebsite .row.aln-bottom {
  align-items: flex-end;
}
.bodywebsite .row > .imp {
  order: -1;
}
.bodywebsite .row > .col-1 {
  width: 8.33333%;
}
.bodywebsite .row > .off-1 {
  margin-left: 8.33333%;
}
.bodywebsite .row > .col-2 {
  width: 16.66667%;
}
.bodywebsite .row > .off-2 {
  margin-left: 16.66667%;
}
.bodywebsite .row > .col-3 {
  width: 25%;
}
.bodywebsite .row > .off-3 {
  margin-left: 25%;
}
.bodywebsite .row > .col-4 {
  width: 33.33333%;
}
.bodywebsite .row > .off-4 {
  margin-left: 33.33333%;
}
.bodywebsite .row > .col-5 {
  width: 41.66667%;
}
.bodywebsite .row > .off-5 {
  margin-left: 41.66667%;
}
.bodywebsite .row > .col-6 {
  width: 50%;
}
.bodywebsite .row > .off-6 {
  margin-left: 50%;
}
.bodywebsite .row > .col-7 {
  width: 58.33333%;
}
.bodywebsite .row > .off-7 {
  margin-left: 58.33333%;
}
.bodywebsite .row > .col-8 {
  width: 66.66667%;
}
.bodywebsite .row > .off-8 {
  margin-left: 66.66667%;
}
.bodywebsite .row > .col-9 {
  width: 75%;
}
.bodywebsite .row > .off-9 {
  margin-left: 75%;
}
.bodywebsite .row > .col-10 {
  width: 83.33333%;
}
.bodywebsite .row > .off-10 {
  margin-left: 83.33333%;
}
.bodywebsite .row > .col-11 {
  width: 91.66667%;
}
.bodywebsite .row > .off-11 {
  margin-left: 91.66667%;
}
.bodywebsite .row > .col-12 {
  width: 100%;
}
.bodywebsite .row > .off-12 {
  margin-left: 100%;
}
.bodywebsite .row {
  margin-top: 0;
}
.bodywebsite .row > * {
  padding: 0 0 0 0;
}
@media screen and (max-width: 1680px) {
  .bodywebsite .row {
	display: flex;
	flex-wrap: wrap;
	box-sizing: border-box;
	align-items: stretch;
  }
  .bodywebsite .row > * {
	box-sizing: border-box;
  }
  .bodywebsite .row.aln-left {
	justify-content: flex-start;
  }
  .bodywebsite .row.aln-center {
	justify-content: center;
  }
  .bodywebsite .row.aln-right {
	justify-content: flex-end;
  }
  .bodywebsite .row.aln-top {
	align-items: flex-start;
  }
  .bodywebsite .row.aln-middle {
	align-items: center;
  }
  .bodywebsite .row.aln-bottom {
	align-items: flex-end;
  }
  .bodywebsite .row > .imp-xlarge {
	order: -1;
  }
  .bodywebsite .row > .col-1-xlarge {
	width: 8.33333%;
  }
  .bodywebsite .row > .off-1-xlarge {
	margin-left: 8.33333%;
  }
  .bodywebsite .row > .col-2-xlarge {
	width: 16.66667%;
  }
  .bodywebsite .row > .off-2-xlarge {
	margin-left: 16.66667%;
  }
  .bodywebsite .row > .col-3-xlarge {
	width: 25%;
  }
  .bodywebsite .row > .off-3-xlarge {
	margin-left: 25%;
  }
  .bodywebsite .row > .col-4-xlarge {
	width: 33.33333%;
  }
  .bodywebsite .row > .off-4-xlarge {
	margin-left: 33.33333%;
  }
  .bodywebsite .row > .col-5-xlarge {
	width: 41.66667%;
  }
  .bodywebsite .row > .off-5-xlarge {
	margin-left: 41.66667%;
  }
  .bodywebsite .row > .col-6-xlarge {
	width: 50%;
  }
  .bodywebsite .row > .off-6-xlarge {
	margin-left: 50%;
  }
  .bodywebsite .row > .col-7-xlarge {
	width: 58.33333%;
  }
  .bodywebsite .row > .off-7-xlarge {
	margin-left: 58.33333%;
  }
  .bodywebsite .row > .col-8-xlarge {
	width: 66.66667%;
  }
  .bodywebsite .row > .off-8-xlarge {
	margin-left: 66.66667%;
  }
  .bodywebsite .row > .col-9-xlarge {
	width: 75%;
  }
  .bodywebsite .row > .off-9-xlarge {
	margin-left: 75%;
  }
  .bodywebsite .row > .col-10-xlarge {
	width: 83.33333%;
  }
  .bodywebsite .row > .off-10-xlarge {
	margin-left: 83.33333%;
  }
  .bodywebsite .row > .col-11-xlarge {
	width: 91.66667%;
  }
  .bodywebsite .row > .off-11-xlarge {
	margin-left: 91.66667%;
  }
  .bodywebsite .row > .col-12-xlarge {
	width: 100%;
  }
  .bodywebsite .row > .off-12-xlarge {
	margin-left: 100%;
  }
  .bodywebsite .row {
	margin-top: 0;
	margin-left: 0;
  }
  .bodywebsite .row > * {
	padding: 0 0 0 0;
  }
}
@media screen and (max-width: 1280px) {
  .bodywebsite .row {
	display: flex;
	flex-wrap: wrap;
	box-sizing: border-box;
	align-items: stretch;
  }
  .bodywebsite .row > * {
	box-sizing: border-box;
  }
  .bodywebsite .row.aln-left {
	justify-content: flex-start;
  }
  .bodywebsite .row.aln-center {
	justify-content: center;
  }
  .bodywebsite .row.aln-right {
	justify-content: flex-end;
  }
  .bodywebsite .row.aln-top {
	align-items: flex-start;
  }
  .bodywebsite .row.aln-middle {
	align-items: center;
  }
  .bodywebsite .row.aln-bottom {
	align-items: flex-end;
  }
  .bodywebsite .row > .imp-large {
	order: -1;
  }
  .bodywebsite .row > .col-1-large {
	width: 8.33333%;
  }
  .bodywebsite .row > .off-1-large {
	margin-left: 8.33333%;
  }
  .bodywebsite .row > .col-2-large {
	width: 16.66667%;
  }
  .bodywebsite .row > .off-2-large {
	margin-left: 16.66667%;
  }
  .bodywebsite .row > .col-3-large {
	width: 25%;
  }
  .bodywebsite .row > .off-3-large {
	margin-left: 25%;
  }
  .bodywebsite .row > .col-4-large {
	width: 33.33333%;
  }
  .bodywebsite .row > .off-4-large {
	margin-left: 33.33333%;
  }
  .bodywebsite .row > .col-5-large {
	width: 41.66667%;
  }
  .bodywebsite .row > .off-5-large {
	margin-left: 41.66667%;
  }
  .bodywebsite .row > .col-6-large {
	width: 50%;
  }
  .bodywebsite .row > .off-6-large {
	margin-left: 50%;
  }
  .bodywebsite .row > .col-7-large {
	width: 58.33333%;
  }
  .bodywebsite .row > .off-7-large {
	margin-left: 58.33333%;
  }
  .bodywebsite .row > .col-8-large {
	width: 66.66667%;
  }
  .bodywebsite .row > .off-8-large {
	margin-left: 66.66667%;
  }
  .bodywebsite .row > .col-9-large {
	width: 75%;
  }
  .bodywebsite .row > .off-9-large {
	margin-left: 75%;
  }
  .bodywebsite .row > .col-10-large {
	width: 83.33333%;
  }
  .bodywebsite .row > .off-10-large {
	margin-left: 83.33333%;
  }
  .bodywebsite .row > .col-11-large {
	width: 91.66667%;
  }
  .bodywebsite .row > .off-11-large {
	margin-left: 91.66667%;
  }
  .bodywebsite .row > .col-12-large {
	width: 100%;
  }
  .bodywebsite .row > .off-12-large {
	margin-left: 100%;
  }
  .bodywebsite .row {
	margin-top: 0;
  }
  .bodywebsite .row > * {
	padding: 0 0 0 1.5em;
  }
}
@media screen and (max-width: 980px) {
  .bodywebsite .row {
	display: flex;
	flex-wrap: wrap;
	box-sizing: border-box;
	align-items: stretch;
  }
  .bodywebsite .row > * {
	box-sizing: border-box;
  }
  .bodywebsite .row.aln-left {
	justify-content: flex-start;
  }
  .bodywebsite .row.aln-center {
	justify-content: center;
  }
  .bodywebsite .row.aln-right {
	justify-content: flex-end;
  }
  .bodywebsite .row.aln-top {
	align-items: flex-start;
  }
  .bodywebsite .row.aln-middle {
	align-items: center;
  }
  .bodywebsite .row.aln-bottom {
	align-items: flex-end;
  }
  .bodywebsite .row > .imp-medium {
	order: -1;
  }
  .bodywebsite .row > .col-1-medium {
	width: 8.33333%;
  }
  .bodywebsite .row > .off-1-medium {
	margin-left: 8.33333%;
  }
  .bodywebsite .row > .col-2-medium {
	width: 16.66667%;
  }
  .bodywebsite .row > .off-2-medium {
	margin-left: 16.66667%;
  }
  .bodywebsite .row > .col-3-medium {
	width: 25%;
  }
  .bodywebsite .row > .off-3-medium {
	margin-left: 25%;
  }
  .bodywebsite .row > .col-4-medium {
	width: 33.33333%;
  }
  .bodywebsite .row > .off-4-medium {
	margin-left: 33.33333%;
  }
  .bodywebsite .row > .col-5-medium {
	width: 41.66667%;
  }
  .bodywebsite .row > .off-5-medium {
	margin-left: 41.66667%;
  }
  .bodywebsite .row > .col-6-medium {
	width: 50%;
  }
  .bodywebsite .row > .off-6-medium {
	margin-left: 50%;
  }
  .bodywebsite .row > .col-7-medium {
	width: 58.33333%;
  }
  .bodywebsite .row > .off-7-medium {
	margin-left: 58.33333%;
  }
  .bodywebsite .row > .col-8-medium {
	width: 66.66667%;
  }
  .bodywebsite .row > .off-8-medium {
	margin-left: 66.66667%;
  }
  .bodywebsite .row > .col-9-medium {
	width: 75%;
  }
  .bodywebsite .row > .off-9-medium {
	margin-left: 75%;
  }
  .bodywebsite .row > .col-10-medium {
	width: 83.33333%;
  }
  .bodywebsite .row > .off-10-medium {
	margin-left: 83.33333%;
  }
  .bodywebsite .row > .col-11-medium {
	width: 91.66667%;
  }
  .bodywebsite .row > .off-11-medium {
	margin-left: 91.66667%;
  }
  .bodywebsite .row > .col-12-medium {
	width: 100%;
  }
  .bodywebsite .row > .off-12-medium {
	margin-left: 100%;
  }
  .bodywebsite .row {
	margin-top: 0;
  }
  .bodywebsite .row > * {
	padding: 0 0 0 1.5em;
  }
}
@media screen and (max-width: 736px) {
  .bodywebsite .row {
	display: flex;
	flex-wrap: wrap;
	box-sizing: border-box;
	align-items: stretch;
  }
  .bodywebsite .row > * {
	box-sizing: border-box;
  }
  .bodywebsite .row.aln-left {
	justify-content: flex-start;
  }
  .bodywebsite .row.aln-center {
	justify-content: center;
  }
  .bodywebsite .row.aln-right {
	justify-content: flex-end;
  }
  .bodywebsite .row.aln-top {
	align-items: flex-start;
  }
  .bodywebsite .row.aln-middle {
	align-items: center;
  }
  .bodywebsite .row.aln-bottom {
	align-items: flex-end;
  }
  .bodywebsite .row > .imp-small {
	order: -1;
  }
  .bodywebsite .row > .col-1-small {
	width: 8.33333%;
  }
  .bodywebsite .row > .off-1-small {
	margin-left: 8.33333%;
  }
  .bodywebsite .row > .col-2-small {
	width: 16.66667%;
  }
  .bodywebsite .row > .off-2-small {
	margin-left: 16.66667%;
  }
  .bodywebsite .row > .col-3-small {
	width: 25%;
  }
  .bodywebsite .row > .off-3-small {
	margin-left: 25%;
  }
  .bodywebsite .row > .col-4-small {
	width: 33.33333%;
  }
  .bodywebsite .row > .off-4-small {
	margin-left: 33.33333%;
  }
  .bodywebsite .row > .col-5-small {
	width: 41.66667%;
  }
  .bodywebsite .row > .off-5-small {
	margin-left: 41.66667%;
  }
  .bodywebsite .row > .col-6-small {
	width: 50%;
  }
  .bodywebsite .row > .off-6-small {
	margin-left: 50%;
  }
  .bodywebsite .row > .col-7-small {
	width: 58.33333%;
  }
  .bodywebsite .row > .off-7-small {
	margin-left: 58.33333%;
  }
  .bodywebsite .row > .col-8-small {
	width: 66.66667%;
  }
  .bodywebsite .row > .off-8-small {
	margin-left: 66.66667%;
  }
  .bodywebsite .row > .col-9-small {
	width: 75%;
  }
  .bodywebsite .row > .off-9-small {
	margin-left: 75%;
  }
  .bodywebsite .row > .col-10-small {
	width: 83.33333%;
  }
  .bodywebsite .row > .off-10-small {
	margin-left: 83.33333%;
  }
  .bodywebsite .row > .col-11-small {
	width: 91.66667%;
  }
  .bodywebsite .row > .off-11-small {
	margin-left: 91.66667%;
  }
  .bodywebsite .row > .col-12-small {
	width: 100%;
  }
  .bodywebsite .row > .off-12-small {
	margin-left: 100%;
  }
  .bodywebsite .row {
	margin-top: 0;
  }
  .bodywebsite .row > * {
	padding: 0 0 0 1em;
  }
}
@media screen and (max-width: 480px) {
  .bodywebsite .row {
	display: flex;
	flex-wrap: wrap;
	box-sizing: border-box;
	align-items: stretch;
  }
  .bodywebsite .row > * {
	box-sizing: border-box;
  }
  .bodywebsite .row.aln-left {
	justify-content: flex-start;
  }
  .bodywebsite .row.aln-center {
	justify-content: center;
  }
  .bodywebsite .row.aln-right {
	justify-content: flex-end;
  }
  .bodywebsite .row.aln-top {
	align-items: flex-start;
  }
  .bodywebsite .row.aln-middle {
	align-items: center;
  }
  .bodywebsite .row.aln-bottom {
	align-items: flex-end;
  }
  .bodywebsite .row > .imp-xsmall {
	order: -1;
  }
  .bodywebsite .row > .col-1-xsmall {
	width: 8.33333%;
  }
  .bodywebsite .row > .off-1-xsmall {
	margin-left: 8.33333%;
  }
  .bodywebsite .row > .col-2-xsmall {
	width: 16.66667%;
  }
  .bodywebsite .row > .off-2-xsmall {
	margin-left: 16.66667%;
  }
  .bodywebsite .row > .col-3-xsmall {
	width: 25%;
  }
  .bodywebsite .row > .off-3-xsmall {
	margin-left: 25%;
  }
  .bodywebsite .row > .col-4-xsmall {
	width: 33.33333%;
  }
  .bodywebsite .row > .off-4-xsmall {
	margin-left: 33.33333%;
  }
  .bodywebsite .row > .col-5-xsmall {
	width: 41.66667%;
  }
  .bodywebsite .row > .off-5-xsmall {
	margin-left: 41.66667%;
  }
  .bodywebsite .row > .col-6-xsmall {
	width: 50%;
  }
  .bodywebsite .row > .off-6-xsmall {
	margin-left: 50%;
  }
  .bodywebsite .row > .col-7-xsmall {
	width: 58.33333%;
  }
  .bodywebsite .row > .off-7-xsmall {
	margin-left: 58.33333%;
  }
  .bodywebsite .row > .col-8-xsmall {
	width: 66.66667%;
  }
  .bodywebsite .row > .off-8-xsmall {
	margin-left: 66.66667%;
  }
  .bodywebsite .row > .col-9-xsmall {
	width: 75%;
  }
  .bodywebsite .row > .off-9-xsmall {
	margin-left: 75%;
  }
  .bodywebsite .row > .col-10-xsmall {
	width: 83.33333%;
  }
  .bodywebsite .row > .off-10-xsmall {
	margin-left: 83.33333%;
  }
  .bodywebsite .row > .col-11-xsmall {
	width: 91.66667%;
  }
  .bodywebsite .row > .off-11-xsmall {
	margin-left: 91.66667%;
  }
  .bodywebsite .row > .col-12-xsmall {
	width: 100%;
  }
  .bodywebsite .row > .off-12-xsmall {
	margin-left: 100%;
  }
  .bodywebsite .row {
	margin-top: 0;
  }
  .bodywebsite .row > * {
	padding: 0 0 0 1.25em;
  }
}
.bodywebsite .box {
  border-radius: 8px;
  border: solid;
  margin-bottom: 2em;
  padding: 1.5em;
}
.bodywebsite .box > :last-child,
.bodywebsite .box > :last-child > :last-child,
.bodywebsite .box > :last-child > :last-child > :last-child {
  margin-bottom: 0;
}
.bodywebsite .box.alt {
  border: 0;
  border-radius: 0;
  padding: 0;
}
.bodywebsite .box {
  border-color: rgba(255, 255, 255, 0.35);
}
.bodywebsite input[type="submit"],
.bodywebsite input[type="reset"],
.bodywebsite input[type="button"],
.bodywebsite button,
.bodywebsite .buttonwebsite {
  -moz-appearance: none;
  -webkit-appearance: none;
  -ms-appearance: none;
  appearance: none;
  -moz-transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
  -webkit-transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
  -ms-transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
  transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
  border-radius: 8px;
  border: 0;
  cursor: pointer;
  display: inline-block;
  font-weight: 300;
  height: 2.75em;
  line-height: 2.75em;
  min-width: 9.25em;
  padding: 0 1.5em;
  text-align: center;
  text-decoration: none;
  white-space: nowrap;
}
.bodywebsite input[type="submit"].icon,
.bodywebsite input[type="reset"].icon,
.bodywebsite input[type="button"].icon,
.bodywebsite button.icon,
.bodywebsite .buttonwebsite.icon {
  padding-left: 1.35em;
}
.bodywebsite input[type="submit"].icon:before,
.bodywebsite input[type="reset"].icon:before,
.bodywebsite input[type="button"].icon:before,
.bodywebsite button.icon:before,
.bodywebsite .buttonwebsite.icon:before {
  margin-right: 0.5em;
}
.bodywebsite input[type="submit"].fit,
.bodywebsite input[type="reset"].fit,
.bodywebsite input[type="button"].fit,
.bodywebsite button.fit,
.bodywebsite .buttonwebsite.fit {
  width: 100%;
}
.bodywebsite input[type="submit"].small,
.bodywebsite input[type="reset"].small,
.bodywebsite input[type="button"].small,
.bodywebsite button.small,
.bodywebsite .buttonwebsite.small {
  font-size: 0.8em;
}
.bodywebsite input[type="submit"].large,
.bodywebsite input[type="reset"].large,
.bodywebsite input[type="button"].large,
.bodywebsite button.large,
.bodywebsite .buttonwebsite.large {
  font-size: 1.35em;
}
.bodywebsite input[type="submit"].disabled,
.bodywebsite input[type="submit"]:disabled,
.bodywebsite input[type="reset"].disabled,
.bodywebsite input[type="reset"]:disabled,
.bodywebsite input[type="button"].disabled,
.bodywebsite input[type="button"]:disabled,
.bodywebsite button.disabled,
.bodywebsite button:disabled,
.bodywebsite .buttonwebsite.disabled,
.bodywebsite .buttonwebsite:disabled {
  pointer-events: none;
  opacity: 0.25;
}
@media screen and (max-width: 736px) {
  .bodywebsite input[type="submit"],
  .bodywebsite input[type="reset"],
  .bodywebsite input[type="button"],
  .bodywebsite button,
  .bodywebsite .buttonwebsite {
	min-width: 0;
  }
}
.bodywebsite input[type="submit"],
.bodywebsite input[type="reset"],
.bodywebsite input[type="button"],
.bodywebsite button,
.bodywebsite .buttonwebsite {
  background-color: transparent;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35);
  color: #ffffff !important;
}
.bodywebsite input[type="submit"]:hover,
.bodywebsite input[type="reset"]:hover,
.bodywebsite input[type="button"]:hover,
.bodywebsite button:hover,
.bodywebsite .buttonwebsite:hover {
  background-color: rgba(255, 255, 255, 0.075);
}
.bodywebsite input[type="submit"]:active,
.bodywebsite input[type="reset"]:active,
.bodywebsite input[type="button"]:active,
.bodywebsite button:active,
.bodywebsite .buttonwebsite:active {
  background-color: rgba(255, 255, 255, 0.2);
}
.bodywebsite input[type="submit"].icon:before,
.bodywebsite input[type="reset"].icon:before,
.bodywebsite input[type="button"].icon:before,
.bodywebsite button.icon:before,
.bodywebsite .buttonwebsite.icon:before {
  color: rgba(255, 255, 255, 0.5);
}
.bodywebsite input[type="submit"].primary,
.bodywebsite input[type="reset"].primary,
.bodywebsite input[type="button"].primary,
.bodywebsite button.primary,
.bodywebsite .buttonwebsite.primary {
  background-color: #8cc9f0;
  color: #ffffff !important;
  box-shadow: none;
}
.bodywebsite input[type="submit"].primary:hover,
.bodywebsite input[type="reset"].primary:hover,
.bodywebsite input[type="button"].primary:hover,
.bodywebsite button.primary:hover,
.bodywebsite .buttonwebsite.primary:hover {
  background-color: #9acff2;
}
.bodywebsite input[type="submit"].primary:active,
.bodywebsite input[type="reset"].primary:active,
.bodywebsite input[type="button"].primary:active,
.bodywebsite button.primary:active,
.bodywebsite .buttonwebsite.primary:active {
  background-color: #7ec3ee;
}
.bodywebsite input[type="submit"].primary.icon:before,
.bodywebsite input[type="reset"].primary.icon:before,
.bodywebsite input[type="button"].primary.icon:before,
.bodywebsite button.primary.icon:before,
.bodywebsite .buttonwebsite.primary.icon:before {
  color: #ffffff !important;
}
.bodywebsite form {
  margin: 0 0 2em 0;
}
.bodywebsite label {
  display: block;
  font-size: 0.9em;
  font-weight: 400;
  margin: 0 0 1em 0;
}
.bodywebsite input[type="text"],
.bodywebsite input[type="password"],
.bodywebsite input[type="email"],
.bodywebsite select,
.bodywebsite textarea {
  -moz-appearance: none;
  -webkit-appearance: none;
  -ms-appearance: none;
  appearance: none;
  border-radius: 8px;
  border: solid 1px;
  color: inherit;
  display: block;
  outline: 0;
  padding: 0 1em;
  text-decoration: none;
  width: 100%;
}
.bodywebsite input[type="text"]:invalid,
.bodywebsite input[type="password"]:invalid,
.bodywebsite input[type="email"]:invalid,
.bodywebsite select:invalid,
.bodywebsite textarea:invalid {
  box-shadow: none;
}
.bodywebsite select {
  background-size: 1.25rem;
  background-repeat: no-repeat;
  background-position: calc(100% - 1rem) center;
  height: 2.75em;
  padding-right: 2.75em;
  text-overflow: ellipsis;
}
.bodywebsite select:focus::-ms-value {
  background-color: transparent;
}
.bodywebsite select::-ms-expand {
  display: none;
}
.bodywebsite input[type="text"],
.bodywebsite input[type="password"],
.bodywebsite input[type="email"],
.bodywebsite select {
  height: 2.75em;
}
.bodywebsite textarea {
  padding: 0.75em 1em;
}
.bodywebsite input[type="checkbox"],
.bodywebsite input[type="radio"] {
  -moz-appearance: none;
  -webkit-appearance: none;
  -ms-appearance: none;
  appearance: none;
  display: block;
  float: left;
  margin-right: -2em;
  opacity: 0;
  width: 1em;
  z-index: -1;
}
.bodywebsite input[type="checkbox"] + label,
.bodywebsite input[type="radio"] + label {
  text-decoration: none;
  cursor: pointer;
  display: inline-block;
  font-size: 1em;
  font-weight: 300;
  padding-left: 2.4em;
  padding-right: 0.75em;
  position: relative;
}
.bodywebsite input[type="checkbox"] + label:before,
.bodywebsite input[type="radio"] + label:before {
  -moz-osx-font-smoothing: grayscale;
  -webkit-font-smoothing: antialiased;
  display: inline-block;
  font-style: normal;
  font-variant: normal;
  text-rendering: auto;
  line-height: 1;
  text-transform: none !important;
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
}
.bodywebsite input[type="checkbox"] + label:before,
.bodywebsite input[type="radio"] + label:before {
  border-radius: 8px;
  border: solid 1px;
  content: '';
  display: inline-block;
  font-size: 0.8em;
  height: 2.0625em;
  left: 0;
  line-height: 2.0625em;
  position: absolute;
  text-align: center;
  top: 0;
  width: 2.0625em;
}
.bodywebsite input[type="checkbox"]:checked + label:before,
.bodywebsite input[type="radio"]:checked + label:before {
  content: '\f00c';
}
.bodywebsite input[type="checkbox"] + label:before {
  border-radius: 8px;
}
.bodywebsite input[type="radio"] + label:before {
  border-radius: 100%;
}
.bodywebsite ::-webkit-input-placeholder {
  opacity: 1;
}
.bodywebsite :-moz-placeholder {
  opacity: 1;
}
.bodywebsite ::-moz-placeholder {
  opacity: 1;
}
.bodywebsite :-ms-input-placeholder {
  opacity: 1;
}
.bodywebsite label {
  color: #ffffff;
}
.bodywebsite input[type="text"],
.bodywebsite input[type="password"],
.bodywebsite input[type="email"],
.bodywebsite select,
.bodywebsite textarea {
  background-color: rgba(255, 255, 255, 0.075);
  border-color: rgba(255, 255, 255, 0.35);
}
.bodywebsite input[type="text"]:focus,
.bodywebsite input[type="password"]:focus,
.bodywebsite input[type="email"]:focus,
.bodywebsite select:focus,
.bodywebsite textarea:focus {
  border-color: #8cc9f0;
  box-shadow: 0 0 0 1px #8cc9f0;
}
.bodywebsite select {
  background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' preserveAspectRatio='none' viewBox='0 0 40 40'%3E%3Cpath d='M9.4,12.3l10.4,10.4l10.4-10.4c0.2-0.2,0.5-0.4,0.9-0.4c0.3,0,0.6,0.1,0.9,0.4l3.3,3.3c0.2,0.2,0.4,0.5,0.4,0.9 c0,0.4-0.1,0.6-0.4,0.9L20.7,31.9c-0.2,0.2-0.5,0.4-0.9,0.4c-0.3,0-0.6-0.1-0.9-0.4L4.3,17.3c-0.2-0.2-0.4-0.5-0.4-0.9 c0-0.4,0.1-0.6,0.4-0.9l3.3-3.3c0.2-0.2,0.5-0.4,0.9-0.4S9.1,12.1,9.4,12.3z' fill='rgba(255, 255, 255, 0.35)' /%3E%3C/svg%3E");
}
.bodywebsite select option {
  color: #ffffff;
  background: #935d8c;
}
.bodywebsite input[type="checkbox"] + label,
.bodywebsite input[type="radio"] + label {
  color: rgba(255, 255, 255, 0.65);
}
.bodywebsite input[type="checkbox"] + label:before,
.bodywebsite input[type="radio"] + label:before {
  background: rgba(255, 255, 255, 0.075);
  border-color: rgba(255, 255, 255, 0.35);
}
.bodywebsite input[type="checkbox"]:checked + label:before,
.bodywebsite input[type="radio"]:checked + label:before {
  background-color: #ffffff;
  border-color: #ffffff;
  color: #935d8c;
}
.bodywebsite input[type="checkbox"]:focus + label:before,
.bodywebsite input[type="radio"]:focus + label:before {
  border-color: #8cc9f0;
  box-shadow: 0 0 0 1px #8cc9f0;
}
.bodywebsite ::-webkit-input-placeholder {
  color: rgba(255, 255, 255, 0.5) !important;
}
.bodywebsite :-moz-placeholder {
  color: rgba(255, 255, 255, 0.5) !important;
}
.bodywebsite ::-moz-placeholder {
  color: rgba(255, 255, 255, 0.5) !important;
}
.bodywebsite :-ms-input-placeholder {
  color: rgba(255, 255, 255, 0.5) !important;
}
.bodywebsite .formerize-placeholder {
  color: rgba(255, 255, 255, 0.5) !important;
}
.bodywebsite .icon {
  text-decoration: none;
  -moz-transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
  -webkit-transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
  -ms-transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
  transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
  border-bottom: none;
  position: relative;
}
.bodywebsite .icon:before {
  -moz-osx-font-smoothing: grayscale;
  -webkit-font-smoothing: antialiased;
  display: inline-block;
  font-style: normal;
  font-variant: normal;
  text-rendering: auto;
  line-height: 1;
  text-transform: none !important;
  font-family: "Font Awesome 5 Free";
}
.bodywebsite .icon > .label {
  display: none;
}
.bodywebsite .icon:before {
  line-height: solid;
}
.bodywebsite .icon.solid:before {
  font-weight: 900;
}
.bodywebsite .icon.brands:before {
  font-family: 'Font Awesome 5 Brands';
}
.bodywebsite .icon.major {
  border: solid 1px;
  display: inline-block;
  border-radius: 100%;
  padding: 0.65em;
  margin: 0 0 2em 0;
  cursor: default;
}
.bodywebsite .icon.major:before {
  display: inline-block;
  font-size: 6.25rem;
  font-weight: 600;
  font-family: "Font Awesome 5 Free";
  width: 2.25em;
  height: 2.25em;
  line-height: 2.2em;
  border-radius: 100%;
  border: solid 1px;
  text-align: center;
}
.bodywebsite .icon.alt {
  display: inline-block;
  border: solid 1px;
  border-radius: 100%;
}
.bodywebsite .icon.alt:before {
  display: block;
  font-size: 1.25em;
  font-family: "Font Awesome 5 Free";
  width: 2em;
  height: 2em;
  text-align: center;
  line-height: 2em;
}
.bodywebsite .icon.style1 {
  color: #efa8b0;
}
.bodywebsite .icon.style2 {
  color: #c79cc8;
}
.bodywebsite .icon.style3 {
  color: #a89cc8;
}
.bodywebsite .icon.style4 {
  color: #9bb2e1;
}
.bodywebsite .icon.style5 {
  color: #8cc9f0;
}
@media screen and (max-width: 1680px) {
  .bodywebsite .icon.major:before {
	font-size: 5.5rem;
  }
}
@media screen and (max-width: 1280px) {
  .bodywebsite .icon.major:before {
	font-size: 4.75rem;
  }
}
@media screen and (max-width: 736px) {
  .bodywebsite .icon.major {
	margin: 0 0 1.5em 0;
	padding: 0.35em;
  }
  .bodywebsite .icon.major:before {
	font-size: 3.5rem;
  }
}
.bodywebsite .icon.major {
  border-color: rgba(255, 255, 255, 0.35);
}
.bodywebsite .icon.major:before {
  border-color: rgba(255, 255, 255, 0.35);
}
.bodywebsite .icon.alt {
  border-color: rgba(255, 255, 255, 0.35);
  color: #ffffff;
}
.bodywebsite .icon.alt:hover {
  background-color: rgba(255, 255, 255, 0.075);
}
.bodywebsite .icon.alt:active {
  background-color: rgba(255, 255, 255, 0.2);
}
.bodywebsite .image {
  border-radius: 8px;
  border: 0;
  display: inline-block;
  position: relative;
}
.bodywebsite .image img {
  border-radius: 8px;
  display: block;
}
.bodywebsite .image.left,
.bodywebsite .image.right {
  max-width: 40%;
}
.bodywebsite .image.left img,
.bodywebsite .image.right img {
  width: 100%;
}
.bodywebsite .image.left {
  float: left;
  margin: 0 1.5em 1em 0;
  top: 0.25em;
}
.bodywebsite .image.right {
  float: right;
  margin: 0 0 1em 1.5em;
  top: 0.25em;
}
.bodywebsite .image.fit {
  display: block;
  margin: 0 0 2em 0;
  width: 100%;
}
.bodywebsite .image.fit img {
  width: 100%;
}
.bodywebsite .image.main {
  display: block;
  margin: 0 0 3em 0;
  width: 100%;
}
.bodywebsite .image.main img {
  width: 100%;
}
.bodywebsite ol {
  list-style: decimal;
  margin: 0 0 2em 0;
  padding-left: 1.25em;
}
.bodywebsite ol li {
  padding-left: 0.25em;
}
.bodywebsite ul {
  list-style: disc;
  margin: 0 0 2em 0;
  padding-left: 1em;
}
.bodywebsite ul li {
  padding-left: 0.5em;
}
.bodywebsite ul.alt {
  list-style: none;
  padding-left: 0;
}
.bodywebsite ul.alt li {
  border-top: solid 1px;
  padding: 0.5em 0;
}
.bodywebsite ul.alt li:first-child {
  border-top: 0;
  padding-top: 0;
}
.bodywebsite dl {
  margin: 0 0 2em 0;
}
.bodywebsite dl dt {
  display: block;
  font-weight: 400;
  margin: 0 0 1em 0;
}
.bodywebsite dl dd {
  margin-left: 2em;
}
.bodywebsite dl.alt dt {
  display: block;
  width: 3em;
  margin: 0;
  clear: left;
  float: left;
}
.bodywebsite dl.alt dd {
  margin: 0 0 0.85em 5.5em;
}
.bodywebsite dl.alt:after {
  content: '';
  display: block;
  clear: both;
}
.bodywebsite ul.alt li {
  border-top-color: rgba(255, 255, 255, 0.35);
}
.bodywebsite dl dt {
  color: #ffffff;
}
.bodywebsite ul.actions {
  display: -moz-flex;
  display: -webkit-flex;
  display: -ms-flex;
  display: flex;
  cursor: default;
  list-style: none;
  margin-left: -1em;
  padding-left: 0;
}
.bodywebsite ul.actions li {
  padding: 0 0 0 1em;
  vertical-align: middle;
}
.bodywebsite ul.actions.special {
  -moz-justify-content: center;
  -webkit-justify-content: center;
  -ms-justify-content: center;
  justify-content: center;
  width: 100%;
  margin-left: 0;
}
.bodywebsite ul.actions.special li:first-child {
  padding-left: 0;
}
.bodywebsite ul.actions.stacked {
  -moz-flex-direction: column;
  -webkit-flex-direction: column;
  -ms-flex-direction: column;
  flex-direction: column;
  margin-left: 0;
}
.bodywebsite ul.actions.stacked li {
  padding: 1.3em 0 0 0;
}
.bodywebsite ul.actions.stacked li:first-child {
  padding-top: 0;
}
.bodywebsite ul.actions.fit {
  width: calc(100% + 1em);
}
.bodywebsite ul.actions.fit li {
  -moz-flex-grow: 1;
  -webkit-flex-grow: 1;
  -ms-flex-grow: 1;
  flex-grow: 1;
  -moz-flex-shrink: 1;
  -webkit-flex-shrink: 1;
  -ms-flex-shrink: 1;
  flex-shrink: 1;
  width: 100%;
}
.bodywebsite ul.actions.fit li > * {
  width: 100%;
}
.bodywebsite ul.actions.fit.stacked {
  width: 100%;
}
.bodywebsite .list-inline {
	list-style: none;
}

@media screen and (max-width: 480px) {
  .bodywebsite ul.actions:not(.fixed) {
	-moz-flex-direction: column;
	-webkit-flex-direction: column;
	-ms-flex-direction: column;
	flex-direction: column;
	margin-left: 0;
  }
  .bodywebsite ul.actions:not(.fixed) li {
	-moz-flex-grow: 1;
	-webkit-flex-grow: 1;
	-ms-flex-grow: 1;
	flex-grow: 1;
	-moz-flex-shrink: 1;
	-webkit-flex-shrink: 1;
	-ms-flex-shrink: 1;
	flex-shrink: 1;
	padding: 1em 0 0 0;
	text-align: center;
  }
  .bodywebsite ul.actions:not(.fixed) li > * {
  }
  .bodywebsite ul.actions:not(.fixed) li:first-child {
	padding-top: 0;
  }
  .bodywebsite ul.actions:not(.fixed) li input[type="submit"].icon:before,
  .bodywebsite ul.actions:not(.fixed) li input[type="reset"].icon:before,
  .bodywebsite ul.actions:not(.fixed) li input[type="button"].icon:before,
  .bodywebsite ul.actions:not(.fixed) li button.icon:before,
  .bodywebsite ul.actions:not(.fixed) li .buttonwebsite.icon:before {
	margin-left: -0.5rem;
  }
}
.bodywebsite ul.icons {
  cursor: default;
  list-style: none;
  padding-left: 0;
}
.bodywebsite ul.icons li {
  display: inline-block;
  padding: 0 0.65em 0 0;
}
.bodywebsite ul.icons li:last-child {
  padding-right: 0 !important;
}
.bodywebsite section.special,
.bodywebsite article.special {
  text-align: center;
}
.bodywebsite header.major {
  margin-bottom: 3em;
}
.bodywebsite header.major h2 {
  font-size: 2em;
}
.bodywebsite header.major h2:after {
  display: block;
  content: '';
  width: 3.25em;
  height: 2px;
  margin: 0.7em 0 1em 0;
  border-radius: 2px;
}
.bodywebsite section.special header.major h2:after,
.bodywebsite article.special header.major h2:after {
  margin-left: auto;
  margin-right: auto;
}
.bodywebsite header.major p {
  font-size: 1.25em;
  letter-spacing: -0.025em;
}
.bodywebsite header.major.special {
  text-align: center;
}
.bodywebsite header.major.special h2:after {
  margin-left: auto;
  margin-right: auto;
}
.bodywebsite footer.major {
  margin-top: 3em;
}
@media screen and (max-width: 736px) {
  .bodywebsite header.major {
	margin-bottom: 0;
  }
  .bodywebsite header.major h2 {
	font-size: 1.5em;
  }
  .bodywebsite header.major p {
	font-size: 1em;
	letter-spacing: 0;
  }
  .bodywebsite header.major p br {
	display: none;
  }
  .bodywebsite footer.major {
	margin-top: 0;
  }
}
.bodywebsite header.major h2:after {
  background-color: rgba(255, 255, 255, 0.35);
}
.bodywebsite .table-wrapper {
  -webkit-overflow-scrolling: touch;
  overflow-x: auto;
}
.bodywebsite table {
  margin: 0 0 2em 0;
  width: 100%;
}
.bodywebsite table tbody tr {
  border: solid 1px;
  border-left: 0;
  border-right: 0;
}
.bodywebsite table td {
  padding: 0.75em 0.75em;
}
.bodywebsite table th {
  font-size: 0.9em;
  font-weight: 400;
  padding: 0 0.75em 0.75em 0.75em;
  text-align: left;
}
.bodywebsite table thead {
  border-bottom: solid 2px;
}
.bodywebsite table tfoot {
  border-top: solid 2px;
}
.bodywebsite table.alt {
  border-collapse: separate;
}
.bodywebsite table.alt tbody tr td {
  border: solid 1px;
  border-left-width: 0;
  border-top-width: 0;
}
.bodywebsite table.alt tbody tr td:first-child {
  border-left-width: 1px;
}
.bodywebsite table.alt tbody tr:first-child td {
  border-top-width: 1px;
}
.bodywebsite table.alt thead {
  border-bottom: 0;
}
.bodywebsite table.alt tfoot {
  border-top: 0;
}
.bodywebsite table tbody tr {
  border-color: rgba(255, 255, 255, 0.35);
}
.bodywebsite table tbody tr:nth-child(2n + 1) {
  background-color: rgba(255, 255, 255, 0.075);
}
.bodywebsite table th {
  color: #ffffff;
}
.bodywebsite table thead {
  border-bottom-color: rgba(255, 255, 255, 0.35);
}
.bodywebsite table tfoot {
  border-top-color: rgba(255, 255, 255, 0.35);
}
.bodywebsite table.alt tbody tr td {
  border-color: rgba(255, 255, 255, 0.35);
}
.bodywebsite .features {
  display: -moz-flex;
  display: -webkit-flex;
  display: -ms-flex;
  display: flex;
  -moz-flex-wrap: wrap;
  -webkit-flex-wrap: wrap;
  -ms-flex-wrap: wrap;
  flex-wrap: wrap;
  -moz-justify-content: center;
  -webkit-justify-content: center;
  -ms-justify-content: center;
  justify-content: center;
  width: calc(100% + 2em);
  margin: 0 0 3em -2em;
  padding: 0;
  list-style: none;
}
.bodywebsite .features li {
  width: calc(33.33333% - 2em);
  margin-left: 2em;
  margin-top: 3em;
  padding: 0;
}
.bodywebsite .features li:nth-child(1),
.bodywebsite .features li:nth-child(2),
.bodywebsite .features li:nth-child(3) {
  margin-top: 0;
}
.bodywebsite .features li > :last-child {
  margin-bottom: 0;
}
@media screen and (max-width: 980px) {
  .bodywebsite .features li {
	width: calc(50% - 2em);
  }
  .bodywebsite .features li:nth-child(3) {
	margin-top: 3em;
  }
}
@media screen and (max-width: 736px) {
  .bodywebsite .features {
	width: 100%;
	margin: 0 0 2em 0;
  }
  .bodywebsite .features li {
	width: 100%;
	margin-left: 0;
	margin-top: 2em;
  }
  .bodywebsite .features li:nth-child(2),
  .bodywebsite .features li:nth-child(3) {
	margin-top: 2em;
  }
}
.bodywebsite .statistics {
  display: -moz-flex;
  display: -webkit-flex;
  display: -ms-flex;
  display: flex;
  width: 100%;
  margin: 0 0 3em 0;
  padding: 0;
  list-style: none;
  cursor: default;
}
.bodywebsite .statistics li {
  -moz-flex: 1;
  -webkit-flex: 1;
  -ms-flex: 1;
  flex: 1;
  padding: 1.5em;
  color: #ffffff;
  text-align: center;
}
.bodywebsite .statistics li.style1 {
  background-color: #efa8b0;
}
.bodywebsite .statistics li.style2 {
  background-color: #c79cc8;
}
.bodywebsite .statistics li.style3 {
  background-color: #a89cc8;
}
.bodywebsite .statistics li.style4 {
  background-color: #9bb2e1;
}
.bodywebsite .statistics li.style5 {
  background-color: #8cc9f0;
}
.bodywebsite .statistics li strong,
.bodywebsite .statistics li b {
  display: block;
  font-size: 2em;
  line-height: 1.1;
  color: inherit !important;
  font-weight: 300;
  letter-spacing: -0.025em;
}
.bodywebsite .statistics li:first-child {
  border-top-left-radius: 8px;
  border-bottom-left-radius: 8px;
}
.bodywebsite .statistics li:last-child {
  border-top-right-radius: 8px;
  border-bottom-right-radius: 8px;
}
.bodywebsite .statistics li .icon {
  display: inline-block;
}
.bodywebsite .statistics li .icon:before {
  font-size: 2.75rem;
  line-height: 1.3;
}
@media screen and (max-width: 980px) {
  .bodywebsite .statistics li strong,
  .bodywebsite .statistics li b {
	font-size: 1.5em;
  }
}
@media screen and (max-width: 736px) {
  .bodywebsite .statistics {
	display: block;
	width: 20em;
	max-width: 100%;
	margin: 0 auto 2em auto;
  }
  .bodywebsite .statistics li:first-child {
	border-bottom-left-radius: 0;
	border-top-right-radius: 8px;
  }
  .bodywebsite .statistics li:last-child {
	border-top-right-radius: 0;
	border-bottom-left-radius: 8px;
  }
  .bodywebsite .statistics li .icon:before {
	font-size: 3.75rem;
  }
  .bodywebsite .statistics li strong,
  .bodywebsite .statistics li b {
	font-size: 2.5em;
  }
}
.bodywebsite .spotlight {
  display: -moz-flex;
  display: -webkit-flex;
  display: -ms-flex;
  display: flex;
  -moz-align-items: center;
  -webkit-align-items: center;
  -ms-align-items: center;
  align-items: center;
  margin: 0 0 2em 0;
}
.bodywebsite .spotlight .content {
  -moz-flex: 1;
  -webkit-flex: 1;
  -ms-flex: 1;
  flex: 1;
}
.bodywebsite .spotlight .content > :last-child {
  margin-bottom: 0;
}
.bodywebsite .spotlight .content header.major {
  margin: 0 0 2em 0;
}
.bodywebsite .spotlight .image {
  display: inline-block;
  margin-left: 4em;
  padding: 0.65em;
  border-radius: 100%;
  border: solid 1px;
}
.bodywebsite .spotlight .image img {
  display: block;
  border-radius: 100%;
  width: 14em;
  height: 14em;
}
@media screen and (max-width: 980px) {
  .bodywebsite .spotlight {
	-moz-flex-direction: column-reverse;
	-webkit-flex-direction: column-reverse;
	-ms-flex-direction: column-reverse;
	flex-direction: column-reverse;
	text-align: center;
  }
  .bodywebsite .spotlight .content {
	-moz-flex: 0 1 auto;
	-webkit-flex: 0 1 auto;
	-ms-flex: 0 1 auto;
	flex: 0 1 auto;
	width: 100%;
  }
  .bodywebsite .spotlight .content header.major h2:after {
	margin-left: auto;
	margin-right: auto;
  }
  .bodywebsite .spotlight .content .actions {
	-moz-justify-content: center;
	-webkit-justify-content: center;
	-ms-justify-content: center;
	justify-content: center;
	width: calc(100% + 1em);
  }
  .bodywebsite .spotlight .image {
	-moz-flex: 0 1 auto;
	-webkit-flex: 0 1 auto;
	-ms-flex: 0 1 auto;
	flex: 0 1 auto;
	margin-left: 0;
	margin-bottom: 2em;
  }
}
@media screen and (max-width: 736px) {
  .bodywebsite .spotlight .image {
	padding: 0.35em;
  }
  .bodywebsite .spotlight .image img {
	width: 12em;
  }
}
.bodywebsite .spotlight .image {
  border-color: rgba(255, 255, 255, 0.35);
}
.bodywebsite #header {
  padding: 5em 5em 1em 5em ;
  text-align: center;
}
.bodywebsite #header h1 {
  margin: 0 0 0.25em 0;
}
.bodywebsite #header p {
  font-size: 1.25em;
  letter-spacing: -0.025em;
}
.bodywebsite #header.alt {
  padding: 7em 5em 4em 5em ;
}
.bodywebsite #header.alt h1 {
  font-size: 3.25em;
}
.bodywebsite #header.alt > * {
  -moz-transition: opacity 3s ease;
  -webkit-transition: opacity 3s ease;
  -ms-transition: opacity 3s ease;
  transition: opacity 3s ease;
  -moz-transition-delay: 0.5s;
  -webkit-transition-delay: 0.5s;
  -ms-transition-delay: 0.5s;
  transition-delay: 0.5s;
  opacity: 1;
}
.bodywebsite #header.alt .logo {
  -moz-transition: opacity 1.25s ease, -moz-transform 0.5s ease;
  -webkit-transition: opacity 1.25s ease, -webkit-transform 0.5s ease;
  -ms-transition: opacity 1.25s ease, -ms-transform 0.5s ease;
  transition: opacity 1.25s ease, transform 0.5s ease;
  -moz-transition-delay: 0s;
  -webkit-transition-delay: 0s;
  -ms-transition-delay: 0s;
  transition-delay: 0s;
  display: block;
  margin: 0 0 1.5em 0;
}
.bodywebsite #header.alt .logo img {
  display: block;
  margin: 0 auto;
  max-width: 75%;
}
@media screen and (max-width: 1280px) {
  .bodywebsite #header {
	padding: 4em 4em 0.1em 4em;
  }
  .bodywebsite #header.alt {
	padding: 6em 4em 3em 4em ;
  }
}
@media screen and (max-width: 980px) {
  .bodywebsite #header {
	padding: 4em 3em 0.1em 3em;
  }
  .bodywebsite #header.alt {
	padding: 5em 3em 2em 3em ;
  }
}
@media screen and (max-width: 736px) {
  .bodywebsite #header {
	padding: 3em 2em 0.1em 2em;
  }
  .bodywebsite #header p {
	font-size: 1em;
	letter-spacing: 0;
  }
  .bodywebsite #header p br {
	display: none;
  }
  .bodywebsite #header.alt {
	padding: 4em 2em 1em 2em ;
  }
  .bodywebsite #header.alt h1 {
	font-size: 2.5em;
  }
}
@media screen and (max-width: 480px) {
  .bodywebsite #header {
	padding: 3em 1.5em 0.1em 1.5em;
  }
  .bodywebsite #header.alt {
	padding: 4em 1.5em 1em 1.5em;
  }
}
@media screen and (max-width: 360px) {
  .bodywebsite #header {
	padding: 2.5em 1em 0.1em 1em;
  }
  .bodywebsite #header.alt {
	padding: 3.5em 1em 0.5em 1em;
  }
}
div.bodywebsite .is-preload #header.alt > * {
  opacity: 0;
}
div.bodywebsite .is-preload #header.alt .logo {
  -moz-transform: scale(0.8) rotate(-30deg);
  -webkit-transform: scale(0.8) rotate(-30deg);
  -ms-transform: scale(0.8) rotate(-30deg);
  transform: scale(0.8) rotate(-30deg);
}
.bodywebsite #nav {
  -moz-transition: background-color 0.2s ease, border-top-left-radius 0.2s ease, border-top-right-radius 0.2s ease, padding 0.2s ease;
  -webkit-transition: background-color 0.2s ease, border-top-left-radius 0.2s ease, border-top-right-radius 0.2s ease, padding 0.2s ease;
  -ms-transition: background-color 0.2s ease, border-top-left-radius 0.2s ease, border-top-right-radius 0.2s ease, padding 0.2s ease;
  transition: background-color 0.2s ease, border-top-left-radius 0.2s ease, border-top-right-radius 0.2s ease, padding 0.2s ease;
  background-color: #ffffff;
  color: #636363;
  position: absolute;
  width: 64em;
  max-width: calc(100% - 4em);
  padding-top: 1em;
  padding-bottom: 1em;
  background-color: #f7f7f7;
  border-top-left-radius: 0.25em;
  border-top-right-radius: 0.25em;
  cursor: default;
  text-align: center;
}
.bodywebsite #nav input,
.bodywebsite #nav select,
.bodywebsite #nav textarea {
  color: #636363;
}
.bodywebsite #nav a:hover {
  color: #636363;
}
.bodywebsite #nav strong,
.bodywebsite #nav b {
  color: #636363;
}
.bodywebsite #nav h1,
.bodywebsite #nav h2,
.bodywebsite #nav h3,
.bodywebsite #nav h4,
.bodywebsite #nav h5,
.bodywebsite #nav h6 {
  color: #636363;
}
.bodywebsite #nav blockquote {
  border-left-color: #dddddd;
}
.bodywebsite #nav code {
  background: rgba(222, 222, 222, 0.25);
  border-color: #dddddd;
}
.bodywebsite #nav hr {
  border-bottom-color: #dddddd;
}
.bodywebsite #nav + #main {
  padding-top: 4.25em;
}
.bodywebsite #nav ul {
  margin: 0;
  padding: 0;
  list-style: none;
}
.bodywebsite #nav ul li {
  -moz-transition: margin 0.2s ease;
  -webkit-transition: margin 0.2s ease;
  -ms-transition: margin 0.2s ease;
  transition: margin 0.2s ease;
  display: inline-block;
  margin: 0 0.35em;
  padding: 0;
  vertical-align: middle;
}
.bodywebsite #nav ul li a {
  -moz-transition: font-size 0.2s ease;
  -webkit-transition: font-size 0.2s ease;
  -ms-transition: font-size 0.2s ease;
  transition: font-size 0.2s ease;
  display: inline-block;
  height: 2.25em;
  line-height: 2.25em;
  padding: 0 1.25em;
  border: 0;
  border-radius: 8px;
  box-shadow: inset 0 0 0 1px transparent;
}
.bodywebsite #nav ul li a:hover {
  background-color: rgba(222, 222, 222, 0.25);
}
.bodywebsite #nav ul li a.active {
  background-color: #ffffff;
  box-shadow: none;
}
.bodywebsite #nav.alt {
  position: fixed;
  top: 0;
  padding-top: 0.5em;
  padding-bottom: 0.5em;
  background-color: rgba(247, 247, 247, 0.95);
  border-top-left-radius: 0;
  border-top-right-radius: 0;
  z-index: 10000;
}
.bodywebsite #nav.alt ul li {
  margin: 0 0.175em;
}
.bodywebsite #nav.alt ul li a {
  font-size: 0.9em;
}
@media screen and (max-width: 736px) {
  .bodywebsite #nav {
	display: none;
  }
  .bodywebsite #nav + #main {
	padding-top: 0;
  }
}
.bodywebsite #main {
  background-color: #ffffff;
  color: #636363;
  border-radius: 0.25em;
}
.bodywebsite #main input,
.bodywebsite #main select,
.bodywebsite #main textarea {
  color: #636363;
}
.bodywebsite #main a:hover {
  color: #636363;
}
.bodywebsite #main strong,
.bodywebsite #main b {
  color: #636363;
}
.bodywebsite #main h1,
.bodywebsite #main h2,
.bodywebsite #main h3,
.bodywebsite #main h4,
.bodywebsite #main h5,
.bodywebsite #main h6 {
  color: #636363;
}
.bodywebsite #main blockquote {
  border-left-color: #dddddd;
}
.bodywebsite #main code {
  background: rgba(222, 222, 222, 0.25);
  border-color: #dddddd;
}
.bodywebsite #main hr {
  border-bottom-color: #dddddd;
}
.bodywebsite #main .box {
  border-color: #dddddd;
}
.bodywebsite #main input[type="submit"],
.bodywebsite #main input[type="reset"],
.bodywebsite #main input[type="button"],
.bodywebsite #main button,
.bodywebsite #main .buttonwebsite {
  background-color: transparent;
  box-shadow: inset 0 0 0 1px #dddddd;
  color: #636363 !important;
}
.bodywebsite #main input[type="submit"]:hover,
.bodywebsite #main input[type="reset"]:hover,
.bodywebsite #main input[type="button"]:hover,
.bodywebsite #main button:hover,
.bodywebsite #main .buttonwebsite:hover {
  background-color: rgba(222, 222, 222, 0.25);
}
.bodywebsite #main input[type="submit"]:active,
.bodywebsite #main input[type="reset"]:active,
.bodywebsite #main input[type="button"]:active,
.bodywebsite #main button:active,
.bodywebsite #main .buttonwebsite:active {
  background-color: rgba(222, 222, 222, 0.5);
}
.bodywebsite #main input[type="submit"].icon:before,
.bodywebsite #main input[type="reset"].icon:before,
.bodywebsite #main input[type="button"].icon:before,
.bodywebsite #main button.icon:before,
.bodywebsite #main .buttonwebsite.icon:before {
  color: rgba(99, 99, 99, 0.25);
}
.bodywebsite #main input[type="submit"].primary,
.bodywebsite #main input[type="reset"].primary,
.bodywebsite #main input[type="button"].primary,
.bodywebsite #main button.primary,
.bodywebsite #main .buttonwebsite.primary {
  background-color: #8cc9f0;
  color: #ffffff !important;
  box-shadow: none;
}
.bodywebsite #main input[type="submit"].primary:hover,
.bodywebsite #main input[type="reset"].primary:hover,
.bodywebsite #main input[type="button"].primary:hover,
.bodywebsite #main button.primary:hover,
.bodywebsite #main .buttonwebsite.primary:hover {
  background-color: #9acff2;
}
.bodywebsite #main input[type="submit"].primary:active,
.bodywebsite #main input[type="reset"].primary:active,
.bodywebsite #main input[type="button"].primary:active,
.bodywebsite #main button.primary:active,
.bodywebsite #main .buttonwebsite.primary:active {
  background-color: #7ec3ee;
}
.bodywebsite #main input[type="submit"].primary.icon:before,
.bodywebsite #main input[type="reset"].primary.icon:before,
.bodywebsite #main input[type="button"].primary.icon:before,
.bodywebsite #main button.primary.icon:before,
.bodywebsite #main .buttonwebsite.primary.icon:before {
  color: #ffffff !important;
}
.bodywebsite #main label {
  color: #636363;
}
.bodywebsite #main input[type="text"],
.bodywebsite #main input[type="password"],
.bodywebsite #main input[type="email"],
.bodywebsite #main select,
.bodywebsite #main textarea {
  background-color: rgba(222, 222, 222, 0.25);
  border-color: #dddddd;
}
.bodywebsite #main input[type="text"]:focus,
.bodywebsite #main input[type="password"]:focus,
.bodywebsite #main input[type="email"]:focus,
.bodywebsite #main select:focus,
.bodywebsite #main textarea:focus {
  border-color: #8cc9f0;
  box-shadow: 0 0 0 1px #8cc9f0;
}
.bodywebsite #main select {
  background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' preserveAspectRatio='none' viewBox='0 0 40 40'%3E%3Cpath d='M9.4,12.3l10.4,10.4l10.4-10.4c0.2-0.2,0.5-0.4,0.9-0.4c0.3,0,0.6,0.1,0.9,0.4l3.3,3.3c0.2,0.2,0.4,0.5,0.4,0.9 c0,0.4-0.1,0.6-0.4,0.9L20.7,31.9c-0.2,0.2-0.5,0.4-0.9,0.4c-0.3,0-0.6-0.1-0.9-0.4L4.3,17.3c-0.2-0.2-0.4-0.5-0.4-0.9 c0-0.4,0.1-0.6,0.4-0.9l3.3-3.3c0.2-0.2,0.5-0.4,0.9-0.4S9.1,12.1,9.4,12.3z' fill='%23dddddd' /%3E%3C/svg%3E");
}
.bodywebsite #main select option {
  color: #636363;
  background: #ffffff;
}
.bodywebsite #main input[type="checkbox"] + label,
.bodywebsite #main input[type="radio"] + label {
  color: #636363;
}
.bodywebsite #main input[type="checkbox"] + label:before,
.bodywebsite #main input[type="radio"] + label:before {
  background: rgba(222, 222, 222, 0.25);
  border-color: #dddddd;
}
.bodywebsite #main input[type="checkbox"]:checked + label:before,
.bodywebsite #main input[type="radio"]:checked + label:before {
  background-color: #636363;
  border-color: #636363;
  color: #ffffff;
}
.bodywebsite #main input[type="checkbox"]:focus + label:before,
.bodywebsite #main input[type="radio"]:focus + label:before {
  border-color: #8cc9f0;
  box-shadow: 0 0 0 1px #8cc9f0;
}
.bodywebsite #main ::-webkit-input-placeholder {
  color: rgba(99, 99, 99, 0.25) !important;
}
.bodywebsite #main :-moz-placeholder {
  color: rgba(99, 99, 99, 0.25) !important;
}
.bodywebsite #main ::-moz-placeholder {
  color: rgba(99, 99, 99, 0.25) !important;
}
.bodywebsite #main :-ms-input-placeholder {
  color: rgba(99, 99, 99, 0.25) !important;
}
.bodywebsite #main .formerize-placeholder {
  color: rgba(99, 99, 99, 0.25) !important;
}
.bodywebsite #main .icon.major {
  border-color: #dddddd;
}
.bodywebsite #main .icon.major:before {
  border-color: #dddddd;
}
.bodywebsite #main .icon.alt {
  border-color: #dddddd;
  color: #636363;
}
.bodywebsite #main .icon.alt:hover {
  background-color: rgba(222, 222, 222, 0.25);
}
.bodywebsite #main .icon.alt:active {
  background-color: rgba(222, 222, 222, 0.5);
}
.bodywebsite #main ul.alt li {
  border-top-color: #dddddd;
}
.bodywebsite #main dl dt {
  color: #636363;
}
.bodywebsite #main header.major h2:after {
  background-color: #dddddd;
  background-image: -moz-linear-gradient(90deg, #efa8b0, #a89cc8, #8cc9f0);
  background-image: -webkit-linear-gradient(90deg, #efa8b0, #a89cc8, #8cc9f0);
  background-image: -ms-linear-gradient(90deg, #efa8b0, #a89cc8, #8cc9f0);
  background-image: linear-gradient(90deg, #efa8b0, #a89cc8, #8cc9f0);
}
.bodywebsite #main table tbody tr {
  border-color: #dddddd;
}
.bodywebsite #main table tbody tr:nth-child(2n + 1) {
  background-color: rgba(222, 222, 222, 0.25);
}
.bodywebsite #main table th {
  color: #636363;
}
.bodywebsite #main table thead {
  border-bottom-color: #dddddd;
}
.bodywebsite #main table tfoot {
  border-top-color: #dddddd;
}
.bodywebsite #main table.alt tbody tr td {
  border-color: #dddddd;
}
.bodywebsite #main .spotlight .image {
  border-color: #dddddd;
}
.bodywebsite #main > .main {
  padding: 5em 5em 3em 5em ;
  border-top: solid 1px #dddddd;
}
.bodywebsite #main > .main:first-child {
  border-top: 0;
}
.bodywebsite #main > .main .image.main:first-child {
  margin: -5em 0 5em -5em;
  width: calc(100% + 10em);
  border-top-right-radius: 0.25em;
  border-top-left-radius: 0.25em;
  border-bottom-right-radius: 0;
  border-bottom-left-radius: 0;
}
.bodywebsite #main > .main .image.main:first-child img {
  border-top-right-radius: 0.25em;
  border-top-left-radius: 0.25em;
  border-bottom-right-radius: 0;
  border-bottom-left-radius: 0;
}
@media screen and (max-width: 1280px) {
  .bodywebsite #main > .main {
	padding: 4em 4em 2em 4em ;
  }
  .bodywebsite #main > .main .image.main:first-child {
	margin: -4em 0 4em -4em;
	width: calc(100% + 8em);
  }
}
@media screen and (max-width: 980px) {
  .bodywebsite #main > .main {
	padding: 4em 3em 2em 3em ;
  }
  .bodywebsite #main > .main .image.main:first-child {
	margin: -4em 0 4em -3em;
	width: calc(100% + 6em);
  }
}
@media screen and (max-width: 736px) {
  .bodywebsite #main > .main {
	padding: 3em 2em 1em 2em ;
  }
  .bodywebsite #main > .main .image.main:first-child {
	margin: -3em 0 2em -2em;
	width: calc(100% + 4em);
  }
}
@media screen and (max-width: 480px) {
  .bodywebsite #main > .main {
	padding: 3em 1.5em 1em 1.5em;
  }
  .bodywebsite #main > .main .image.main:first-child {
	margin: -3em 0 1.5em -1.5em;
	width: calc(100% + 3em);
  }
}
@media screen and (max-width: 360px) {
  .bodywebsite #main {
	border-radius: 0;
  }
  .bodywebsite #main > .main {
	padding: 2.5em 1em 0.5em 1em;
  }
  .bodywebsite #main > .main .image.main:first-child {
	margin: -2.5em 0 1.5em -1em;
	width: calc(100% + 2em);
	border-radius: 0;
  }
  .bodywebsite #main > .main .image.main:first-child img {
	border-radius: 0;
  }
}
.bodywebsite #footer section {
	width: calc(50% - 22px);
	padding: 10px;
}
.bodywebsite #footer {
  display: -moz-flex;
  display: -webkit-flex;
  display: -ms-flex;
  display: flex;
  -moz-flex-wrap: wrap;
  -webkit-flex-wrap: wrap;
  -ms-flex-wrap: wrap;
  flex-wrap: wrap;
  padding: 5em 5em 3em 5em ;
}
.bodywebsite #footer .copyright {
  font-size: 0.8em;
  text-align: center;
}
@media screen and (max-width: 1280px) {
  .bodywebsite #footer {
	padding: 4em 4em 2em 4em ;
  }
}
@media screen and (max-width: 980px) {
  .bodywebsite #footer {
	padding: 4em 3em 2em 3em ;
	display: block;
  }
  .bodywebsite #footer .copyright {
	text-align: left;
  }
}
@media screen and (max-width: 736px) {
  .bodywebsite #footer {
	padding: 3em 2em 1em 2em ;
  }
}
@media screen and (max-width: 480px) {
  .bodywebsite #footer {
	padding: 3em 1.5em 1em 1.5em;
  }
}
@media screen and (max-width: 480px) {
  .bodywebsite #footer {
	padding: 2.5em 1em 0.5em 1em;
  }
}
.bodywebsite #wrapper {
  width: 64em;
  max-width: calc(100% - 4em);
  margin: 0 auto;
}
@media screen and (max-width: 480px) {
  .bodywebsite #wrapper {
	max-width: calc(100% - 2em);
  }
}
@media screen and (max-width: 360px) {
  .bodywebsite #wrapper {
	max-width: 100%;
  }
}

/* CSS for ToTop button */

#myBtnToTop {
  display: none; /* Hidden by default */
  position: fixed; /* Fixed/sticky position */
  bottom: 20px; /* Place the button at the bottom of the page */
  right: 30px; /* Place the button 30px from the right */
  z-index: 99; /* Make sure it does not overlap */
  border: none; /* Remove borders */
  outline: none; /* Remove outline */
  background-color: #868; /* Set a background color */
  color: white; /* Text color */
  cursor: pointer; /* Add a mouse pointer on hover */
  padding: 15px; /* Some padding */
  border-radius: 10px; /* Rounded corners */
  font-size: 18px; /* Increase font size */
  min-width: unset;
  height: unset;
  line-height: unset;
}

#myBtnToTop:hover {
  background-color: #555; /* Add a dark-grey background on hover */
}
<?php // BEGIN PHP
$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "css");
// END PHP
