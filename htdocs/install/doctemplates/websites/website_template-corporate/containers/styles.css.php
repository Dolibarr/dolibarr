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
body.bodywebsite { margin: 0; font-family: 'Open Sans', sans-serif; }
.bodywebsite h1 { margin-top: 0; margin-bottom: 0; padding: 10px;}
.bodywebsite a:focus,
.bodywebsite button:focus {
  outline: none !important;
}
.bodywebsite button::-moz-focus-inner {
  border: 0;
}
.bodywebsite :focus {
  outline: none;
}
.bodywebsite input,
.bodywebsite select,
.bodywebsite textarea {
  outline: 0;
}
.bodywebsite p {
  margin: 0;
}
.bodywebsite q {
  font-size: 18px;
  color: #fff;
}
.bodywebsite dl {
  margin-bottom: 0;
}
.bodywebsite dt {
  font-weight: 400;
}
html .bodywebsite p a:hover {
  text-decoration: none;
}
.bodywebsite form {
  margin-bottom: 0;
}
.bodywebsite .text-left {
  text-align: left;
}
.bodywebsite .text-center {
  text-align: center;
}
.bodywebsite .text-right {
  text-align: right;
}
.bodywebsite .page .text-middle {
  vertical-align: middle;
}
.bodywebsite .centpercent {
	width: 100%;
}
.bodywebsite .page-head {
  position: relative;
  z-index: 90;
  /* must be lower than 100 */
  background-color: #fff;
}
.bodywebsite .page-content {
  position: relative;
  z-index: 1;
}
.bodywebsite .page-foot {
  background-color: #000;
}
.bodywebsite input,
.bodywebsite button,
.bodywebsite select,
.bodywebsite textarea {
  font-family: inherit;
  font-size: inherit;
  line-height: inherit;
}
.bodywebsite a {
  display: inline-block;
  text-decoration: none;
  transition: 0.33s all ease-out;
}
.bodywebsite a,
.bodywebsite a:active,
.bodywebsite a:focus {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite h3 a,
.bodywebsite h3 a:active
{
  font-weight: normal !important;
}
.bodywebsite a:hover,
.bodywebsite a:focus {
  color: <?php echo empty($website->maincolorbis) ? '#6ca' : '#'.$website->maincolorbis; ?>;
  text-decoration: none;
}
.bodywebsite a:focus {
  outline: 0;
}
.bodywebsite a[href*='callto'],
.bodywebsite a[href*='mailto'] {
  white-space: nowrap;
}
.bodywebsite img {
  vertical-align: middle;
  max-width: 100%;
}
.bodywebsite .img-responsive {
  max-width: 100%;
  height: auto;
}
.bodywebsite .img-circle {
  border-radius: 50%;
}
.bodywebsite hr {
  margin-top: 0;
  margin-bottom: 0;
  border: 0;
  border-top: 1px solid #2a2b2b;
}
.bodywebsite .margin-lr-30 {
  margin-left: 30px !important;
  margin-right: 30px !important;
}
.bodywebsite .unit-left,
.bodywebsite .unit-body {
  white-space: nowrap;
  display: inline-block;
  vertical-align: middle;
}
.bodywebsite .unit-left {
	padding-right: 10px;
}
.bodywebsite [role="button"] {
  cursor: pointer;
}
.bodywebsite #sectionnews .nohover {
  color: #000;
}
.bodywebsite .blog-box {
  box-shadow: -1px -1px 12px 5px rgba(85, 85, 85, 0.1) !important;
}
.bodywebsite .blog-box:hover {
  box-shadow: -1px -1px 12px 5px rgba(65, 65, 65, 0.3) !important;
}
.bodywebsite .margin-top-5 {
  margin-top: 5px !important;
}
.bodywebsite .margin-top-10 {
  margin-top: 10px !important;
}
.bodywebsite .rights {
  display: inline-block;
  margin: 0;
  line-height: 1.5;
  letter-spacing: .025em;
  vertical-align: baseline;
}
.bodywebsite .rights * {
  display: inline;
  margin-right: .25em;
}
.bodywebsite .page-foot-default .rights {
  color: #fff;
  font-weight: 300;
}
.bodywebsite .page-foot .brand + * {
  margin-top: 22px;
}
.bodywebsite .page-foot * + .link-block {
  margin-top: 15px;
}
.bodywebsite .page-foot .footer-title + * {
  margin-top: 30px;
}
.bodywebsite .page-foot .contact-info * + .unit {
  margin-top: 15px;
}
.bodywebsite .privacy-link {
  margin-top: 30px;
}
.bodywebsite .one-page-section * + .group-xl {
  margin-top: 40px;
}
@media (min-width: 768px) {
  .bodywebsite .one-page-section * + .group-xl {
	margin-top: 60px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .one-page-section * + .group-xl {
	margin-top: 100px;
  }
}
.bodywebsite h1,
.bodywebsite h2,
.bodywebsite h3,
.bodywebsite h4,
.bodywebsite h5,
.bodywebsite h6,
.bodywebsite .h1,
.bodywebsite .h2,
.bodywebsite .h3,
.bodywebsite .h4,
.bodywebsite .h5,
.bodywebsite .h6 {
  margin-top: 0;
  margin-bottom: 0;
  font-family: "Roboto", Helvetica, Arial, sans-serif;
  font-weight: 700;
  color: #000;
}
.bodywebsite h1 > span,
.bodywebsite h2 > span,
.bodywebsite h3 > span,
.bodywebsite h4 > span,
.bodywebsite h5 > span,
.bodywebsite h6 > span,
.bodywebsite .h1 > span,
.bodywebsite .h2 > span,
.bodywebsite .h3 > span,
.bodywebsite .h4 > span,
.bodywebsite .h5 > span,
.bodywebsite .h6 > span {
  display: inline-block;
  font-size: inherit;
}
.bodywebsite h1 a,
.bodywebsite h2 a,
.bodywebsite h3 a,
.bodywebsite h4 a,
.bodywebsite h5 a,
.bodywebsite h6 a,
.bodywebsite .h1 a,
.bodywebsite .h2 a,
.bodywebsite .h3 a,
.bodywebsite .h4 a,
.bodywebsite .h5 a,
.bodywebsite .h6 a {
  display: inline;
  font: inherit;
  letter-spacing: inherit;
  transition: .33s all ease;
}
.bodywebsite h1 a:hover,
.bodywebsite h2 a:hover,
.bodywebsite h3 a:hover,
.bodywebsite h4 a:hover,
.bodywebsite h5 a:hover,
.bodywebsite h6 a:hover,
.bodywebsite .h1 a:hover,
.bodywebsite .h2 a:hover,
.bodywebsite .h3 a:hover,
.bodywebsite .h4 a:hover,
.bodywebsite .h5 a:hover,
.bodywebsite .h6 a:hover {
  color: <?php echo empty($website->maincolorbis) ? '#6ca' : '#'.$website->maincolorbis; ?>;
}
.bodywebsite h1,
.bodywebsite .h1 {
  font-size: 18px;
  line-height: 1.35;
  text-transform: uppercase;
}
@media (min-width: 1200px) {
  .bodywebsite h1,
  .bodywebsite .h1 {
	line-height: 1.2;
	font-size: 33px;
  }
}
.bodywebsite h1.small,
.bodywebsite .h1.small {
  font-size: 40px;
}
@media (min-width: 768px) {
  .bodywebsite h1.small,
  .bodywebsite .h1.small {
	font-size: 40px;
  }
}
@media (min-width: 992px) {
  .bodywebsite h1.small,
  .bodywebsite .h1.small {
	font-size: 60px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite h1.small,
  .bodywebsite .h1.small {
	font-size: 72px;
	line-height: 1.2;
  }
}
.bodywebsite h2,
.bodywebsite .h2 {
  font-weight: 500;
  font-size: 15px;
  line-height: 1.2;
}
@media (min-width: 576px) {
  .bodywebsite h2,
  .bodywebsite .h2 {
	line-height: 1.33333;
	font-size: 18px;
  }
}
.bodywebsite .text-big-18 {
  font-size: 18px;
}
.bodywebsite .text-big-19 {
  font-size: 19px;
}
.bodywebsite .text-small {
  font-size: 12px;
}
.bodywebsite .text-small-16 {
  font-size: 16px;
}
.bodywebsite small,
.bodywebsite .small {
  font-size: 12px;
  line-height: 18px;
}
.bodywebsite code {
  padding: 5px 7px;
  font-size: 75%;
  color: #fe4a21;
  background-color: #f9f9f9;
  border-radius: 2px;
}
.bodywebsite em {
  font-family: Helvetica, Arial, sans-serif;
  font-size: inherit;
  font-style: italic;
  font-weight: 700;
  line-height: inherit;
  color: #767877;
}
.bodywebsite address {
  margin-top: 0;
  margin-bottom: 0;
}
.bodywebsite .context-dark,
.bodywebsite .bg-black,
.bodywebsite .bg-accent {
  color: rgba(255, 255, 255, 0.5);
}
.bodywebsite .context-dark a,
.bodywebsite .bg-black a,
.bodywebsite .bg-accent a,
.bodywebsite .bg-black a:active,
.bodywebsite .bg-accent a:active,
.bodywebsite .context-dark a:focus,
.bodywebsite .bg-black a:focus,
.bodywebsite .bg-accent a:focus,
.bodywebsite .bg-cello a:focus {
  color: #fff;
}
.bodywebsite .context-dark a:hover,
.bodywebsite .bg-black a:hover,
.bodywebsite .bg-accent a:hover
{
  color: <?php echo empty($website->maincolorbis) ? '#6ca' : '#'.$website->maincolorbis; ?>;
}
.bodywebsite .context-dark .text-extra-large-bordered,
.bodywebsite .bg-black .text-extra-large-bordered,
.bodywebsite .bg-gray-darker .text-extra-large-bordered,
.bodywebsite .bg-gray-dark .text-extra-large-bordered,
.bodywebsite .bg-mine-shaft .text-extra-large-bordered,
.bodywebsite .bg-cod-gray .text-extra-large-bordered,
.bodywebsite .bg-accent .text-extra-large-bordered,
.bodywebsite .bg-cello .text-extra-large-bordered {
  color: #fff;
}
.bodywebsite .bg-black {
  background: #000;
  fill: #000;
}
.bodywebsite .bg-accent {
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  fill: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .bg-accent.bg-default-outline-btn .btn-white-outline:hover {
  background: #3a3c3e;
  border-color: #3a3c3e;
}
.bodywebsite .bg-porcelain {
  background: #e5e7e9;
  fill: #e5e7e9;
}
.bodywebsite .bg-cape-cod {
  background: #444;
  fill: #3a3c3e;
}
.bodywebsite #sectionfirstclass .bg-cape-cod {
  background: #fff;
  fill: #3a3c3e;
}
.bodywebsite .page .text-primary {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?> !important;
}
.bodywebsite .page a.text-primary:focus,
.bodywebsite .page a.text-primary:hover {
  color: <?php echo empty($website->maincolorbis) ? '#6ca' : '#'.$website->maincolorbis; ?> !important;
}
.bodywebsite .page .text-secondary {
  color: #00030a !important;
}
.bodywebsite .page a.text-secondary:focus,
.bodywebsite .page a.text-secondary:hover {
  color: black !important;
}
.bodywebsite .page .text-red-orange {
  color: #ff4b22 !important;
}
.bodywebsite .page a.text-red-orange:focus,
.bodywebsite .page a.text-red-orange:hover {
  color: #ee2c00 !important;
}
.bodywebsite .page .text-black {
  color: #000 !important;
}
.bodywebsite .page a.text-black:focus,
.bodywebsite .page a.text-black:hover {
  color: black !important;
}
.bodywebsite .page .text-silver {
  color: #cdcdcd !important;
}
.bodywebsite .page a.text-silver:focus,
.bodywebsite .page a.text-silver:hover {
  color: #b4b4b4 !important;
}
.bodywebsite .page .text-dark {
  color: #2a2b2b !important;
}
.bodywebsite .page a.text-dark:focus,
.bodywebsite .page a.text-dark:hover {
  color: #111111 !important;
}
.bodywebsite .page .text-gray {
  color: #9f9f9f !important;
}
.bodywebsite .page a.text-gray:focus,
.bodywebsite .page a.text-gray:hover {
  color: #868686 !important;
}
.bodywebsite .page .text-gray-light {
  color: #dedede !important;
}
.bodywebsite .page a.text-gray-light:focus,
.bodywebsite .page a.text-gray-light:hover {
  color: #c5c5c5 !important;
}
.bodywebsite .page .text-white {
  color: #fff !important;
  text-shadow: 1px 1px 8px #222;
}
.bodywebsite .page a.text-white:focus,
.bodywebsite .page a.text-white:hover {
  color: #e6e6e6 !important;
}
.bodywebsite .page .text-white-05 {
  color: rgba(255, 255, 255, 0.5) !important;
}
.bodywebsite .page a.text-white-05:focus,
.bodywebsite .page a.text-white-05:hover {
  color: rgba(230, 230, 230, 0.5) !important;
}
.bodywebsite .page .text-white-03 {
  color: rgba(255, 255, 255, 0.3) !important;
}
.bodywebsite .page a.text-white-03:focus,
.bodywebsite .page a.text-white-03:hover {
  color: rgba(230, 230, 230, 0.3) !important;
}
.bodywebsite .page .text-white-08 {
  color: rgba(255, 255, 255, 0.8) !important;
}
.bodywebsite .page a.text-white-08:focus,
.bodywebsite .page a.text-white-08:hover {
  color: rgba(230, 230, 230, 0.8) !important;
}
.bodywebsite .page .text-tundora {
  color: #414141 !important;
}
.bodywebsite .page a.text-tundora:focus,
.bodywebsite .page a.text-tundora:hover {
  color: #282828 !important;
}
.bodywebsite .page .text-black-05 {
  color: rgba(0, 0, 0, 0.5) !important;
}
.bodywebsite .page a.text-black-05:focus,
.bodywebsite .page a.text-black-05:hover {
  color: rgba(0, 0, 0, 0.5) !important;
}
.bodywebsite .page .text-bismark {
  color: #496a8a !important;
}
.bodywebsite .page a.text-bismark:focus,
.bodywebsite .page a.text-bismark:hover {
  color: #375069 !important;
}
.bodywebsite .page .text-black-08 {
  color: rgba(0, 0, 0, 0.8) !important;
}
.bodywebsite .page a.text-black-08:focus,
.bodywebsite .page a.text-black-08:hover {
  color: rgba(0, 0, 0, 0.8) !important;
}
.bodywebsite .page .text-gray-darker {
  color: #00030a !important;
}
.bodywebsite .page a.text-gray-darker:focus,
.bodywebsite .page a.text-gray-darker:hover {
  color: black !important;
}
.bodywebsite .page .text-abbey {
  color: #464a4d !important;
}
.bodywebsite .page a.text-abbey:focus,
.bodywebsite .page a.text-abbey:hover {
  color: #2e3032 !important;
}
.bodywebsite .page .text-rolling-stone {
  color: #74787C !important;
}
.bodywebsite .page a.text-rolling-stone:focus,
.bodywebsite .page a.text-rolling-stone:hover {
  color: #5b5f62 !important;
}
.bodywebsite .page .text-fuel-yellow {
  color: #F0B922 !important;
}
.bodywebsite .page a.text-fuel-yellow:focus,
.bodywebsite .page a.text-fuel-yellow:hover {
  color: #d19d0e !important;
}
.bodywebsite .hidden {
  display: none;
}
.bodywebsite .text-italic {
  font-style: italic;
}
.bodywebsite .text-normal {
  font-style: normal;
}
.bodywebsite .text-none {
  text-transform: none;
}
.bodywebsite .text-underline {
  text-decoration: underline;
}
.bodywebsite .text-strike {
  text-decoration: line-through;
}
.bodywebsite .text-thin {
  font-weight: 100;
}
.bodywebsite .text-light {
  font-weight: 300;
}
.bodywebsite .text-regular {
  font-weight: 400;
}
.bodywebsite .text-medium {
  font-weight: 500;
}
.bodywebsite .text-sbold {
  font-weight: 600;
}
.bodywebsite .text-bold,
.bodywebsite strong {
  font-weight: 700;
}
.bodywebsite .text-ubold {
  font-weight: 900;
}
.bodywebsite .text-spacing-0 {
  letter-spacing: 0;
}
.bodywebsite .text-spacing-40 {
  letter-spacing: 0.04em;
}
.bodywebsite .text-spacing-inverse-20 {
  letter-spacing: -0.02em;
}
.bodywebsite .text-spacing-120 {
  letter-spacing: 0.12em;
}
.bodywebsite .btn {
  max-width: 100%;
  font-family: "Roboto", Helvetica, Arial, sans-serif;
  font-size: 14px;
  font-weight: 700;
  border-radius: 0;
  border: 2px solid;
  text-transform: uppercase;
  transition: 0.3s ease-out;
  padding: 11px 15px;
}
@media (min-width: 992px) {
  .bodywebsite .btn {
	padding: 12px 35px;
  }
}
.bodywebsite .btn:focus,
.bodywebsite .btn:active,
.bodywebsite .btn:active:focus {
  outline: none;
}
.bodywebsite .btn:active,
.bodywebsite .btn.active {
  box-shadow: none;
}
.bodywebsite .btn-smaller {
  padding: 8px 25px;
}
.bodywebsite .btn-small {
  padding-left: 20px;
  padding-right: 20px;
}
@media (min-width: 768px) {
  .bodywebsite .btn {
	min-width: 190px;
  }
}
html .bodywebsite .btn-default,
html .bodywebsite .btn-default:active,
html .bodywebsite .btn-default.active,
html .bodywebsite .btn-default:active:focus,
html .bodywebsite .btn-default.active:focus,
html .bodywebsite .btn-default:focus:active,
html .bodywebsite .btn-default:focus {
  color: #fff;
  background-color: #464a4d;
  border-color: #464a4d;
}
.bodywebsite .open > html .btn-default.dropdown-toggle,
html .bodywebsite .btn-default:hover {
  color: #fff;
  background-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  border-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
html .bodywebsite .btn-default.disabled,
html .bodywebsite .btn-default[disabled],
.bodywebsite fieldset[disabled] html .btn-default {
  pointer-events: none;
  opacity: .5;
}
html .bodywebsite .btn-default .badge {
  color: #464a4d;
  background-color: #fff;
}
html .bodywebsite .btn-primary,
html .bodywebsite .btn-primary:active,
html .bodywebsite .btn-primary.active,
html .bodywebsite .btn-primary:active:focus,
html .bodywebsite .btn-primary.active:focus,
html .bodywebsite .btn-primary:focus:active,
html .bodywebsite .btn-primary:focus {
  color: #fff;
  background-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  border-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  /* border: 0; */
}
.bodywebsite .open > html .btn-primary.dropdown-toggle {
  color: #fff;
  background-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  border-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  /* border: none; */
}
html .bodywebsite .btn-primary:hover {
  color: #fff;
  box-shadow: 1px 1px 8px #aaa;
}
html .bodywebsite .btn-primary.disabled,
html .bodywebsite .btn-primary[disabled],
.bodywebsite fieldset[disabled] html .btn-primary {
  pointer-events: none;
  opacity: .5;
}
html .bodywebsite .btn-primary .badge {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  background-color: #fff;
}
html .bodywebsite .btn-primary-contrast,
html .bodywebsite .btn-primary-contrast:active,
html .bodywebsite .btn-primary-contrast.active,
html .bodywebsite .btn-primary-contrast:active:focus,
html .bodywebsite .btn-primary-contrast.active:focus,
html .bodywebsite .btn-primary-contrast:focus:active,
html .bodywebsite .btn-primary-contrast:focus {
  color: #fff;
  background-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  border-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .open > html .btn-primary-contrast.dropdown-toggle,
html .bodywebsite .btn-primary-contrast:hover {
  color: #fff;
  background-color: #42b294;
  border-color: #42b294;
}
html .bodywebsite .btn-primary-contrast.disabled,
html .bodywebsite .btn-primary-contrast[disabled],
.bodywebsite fieldset[disabled] html .btn-primary-contrast {
  pointer-events: none;
  opacity: .5;
}
html .bodywebsite .btn-primary-contrast .badge {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  background-color: #fff;
}
html .bodywebsite .btn-primary-outline,
html .bodywebsite .btn-primary-outline:active,
html .bodywebsite .btn-primary-outline.active,
html .bodywebsite .btn-primary-outline:active:focus,
html .bodywebsite .btn-primary-outline.active:focus,
html .bodywebsite .btn-primary-outline:focus:active,
html .bodywebsite .btn-primary-outline:focus {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  background-color: transparent;
  border-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .open > html .btn-primary-outline.dropdown-toggle,
html .bodywebsite .btn-primary-outline:hover {
  color: #fff;
  background-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  border-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
html .bodywebsite .btn-primary-outline.disabled,
html .bodywebsite .btn-primary-outline[disabled],
.bodywebsite fieldset[disabled] html .btn-primary-outline {
  pointer-events: none;
  opacity: .5;
}
html .bodywebsite .btn-primary-outline .badge {
  color: transparent;
  background-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
html .bodywebsite .btn-cello-outline,
html .bodywebsite .btn-cello-outline:active,
html .bodywebsite .btn-cello-outline.active,
html .bodywebsite .btn-cello-outline:active:focus,
html .bodywebsite .btn-cello-outline.active:focus,
html .bodywebsite .btn-cello-outline:focus:active,
html .bodywebsite .btn-cello-outline:focus {
  color: #1e3953;
  background-color: transparent;
  border-color: #1e3953;
}
.bodywebsite .open > html .btn-cello-outline.dropdown-toggle,
html .bodywebsite .btn-cello-outline:hover {
  color: #fff;
  background-color: #1e3953;
  border-color: #1e3953;
}
html .bodywebsite .btn-cello-outline.disabled,
html .bodywebsite .btn-cello-outline[disabled],
.bodywebsite fieldset[disabled] html .btn-cello-outline {
  pointer-events: none;
  opacity: .5;
}
html .bodywebsite .btn-cello-outline .badge {
  color: transparent;
  background-color: #1e3953;
}
html .bodywebsite .btn-white-outline,
html .bodywebsite .btn-white-outline:active,
html .bodywebsite .btn-white-outline.active,
html .bodywebsite .btn-white-outline:active:focus,
html .bodywebsite .btn-white-outline.active:focus,
html .bodywebsite .btn-white-outline:focus:active,
html .bodywebsite .btn-white-outline:focus {
  color: #fff;
  background-color: transparent;
  border-color: #fff;
}
.bodywebsite .open > html .btn-white-outline.dropdown-toggle,
html .bodywebsite .btn-white-outline:hover {
  color: #fff;
  background-color: <?php echo empty($website->maincolorbis) ? '#6ca' : '#'.$website->maincolorbis; ?>;
  border-color: <?php echo empty($website->maincolorbis) ? '#6ca' : '#'.$website->maincolorbis; ?>;
}
html .bodywebsite .btn-white-outline.disabled,
html .bodywebsite .btn-white-outline[disabled],
.bodywebsite fieldset[disabled] html .btn-white-outline {
  pointer-events: none;
  opacity: .5;
}
html .bodywebsite .btn-white-outline .badge {
  color: transparent;
  background-color: #fff;
}
html .bodywebsite .btn-white-outline-variant-1,
html .bodywebsite .btn-white-outline-variant-1:active,
html .bodywebsite .btn-white-outline-variant-1.active,
html .bodywebsite .btn-white-outline-variant-1:active:focus,
html .bodywebsite .btn-white-outline-variant-1.active:focus,
html .bodywebsite .btn-white-outline-variant-1:focus:active,
html .bodywebsite .btn-white-outline-variant-1:focus {
  color: #fff;
  background-color: transparent;
  border-color: #fff;
}
.bodywebsite .open > html .btn-white-outline-variant-1.dropdown-toggle,
html .bodywebsite .btn-white-outline-variant-1:hover {
  color: #fff;
  background-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  border-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
html .bodywebsite .btn-white-outline-variant-1.disabled,
html .bodywebsite .btn-white-outline-variant-1[disabled],
.bodywebsite fieldset[disabled] html .btn-white-outline-variant-1 {
  pointer-events: none;
  opacity: .5;
}
html .bodywebsite .btn-white-outline-variant-1 .badge {
  color: transparent;
  background-color: #fff;
}
html .bodywebsite .btn-silver-outline,
html .bodywebsite .btn-silver-outline:active,
html .bodywebsite .btn-silver-outline.active,
html .bodywebsite .btn-silver-outline:active:focus,
html .bodywebsite .btn-silver-outline.active:focus,
html .bodywebsite .btn-silver-outline:focus:active,
html .bodywebsite .btn-silver-outline:focus {
  color: #000;
  background-color: transparent;
  border-color: #cdcdcd;
}
.bodywebsite .open > html .btn-silver-outline.dropdown-toggle,
html .bodywebsite .btn-silver-outline:hover {
  color: #fff;
  background-color: #cdcdcd;
  border-color: #cdcdcd;
}
html .bodywebsite .btn-silver-outline.disabled,
html .bodywebsite .btn-silver-outline[disabled],
.bodywebsite fieldset[disabled] html .btn-silver-outline {
  pointer-events: none;
  opacity: .5;
}
html .bodywebsite .btn-silver-outline .badge {
  color: transparent;
  background-color: #000;
}
html .bodywebsite .btn-black-outline,
html .bodywebsite .btn-black-outline:active,
html .bodywebsite .btn-black-outline.active,
html .bodywebsite .btn-black-outline:active:focus,
html .bodywebsite .btn-black-outline.active:focus,
html .bodywebsite .btn-black-outline:focus:active,
html .bodywebsite .btn-black-outline:focus {
  color: #000;
  background-color: transparent;
  border-color: #000;
}
.bodywebsite .open > html .btn-black-outline.dropdown-toggle,
html .bodywebsite .btn-black-outline:hover {
  color: #fff;
  background-color: #000;
  border-color: #000;
}
html .bodywebsite .btn-black-outline.disabled,
html .bodywebsite .btn-black-outline[disabled],
.bodywebsite fieldset[disabled] html .btn-black-outline {
  pointer-events: none;
  opacity: .5;
}
html .bodywebsite .btn-black-outline .badge {
  color: transparent;
  background-color: #000;
}
html .bodywebsite .btn-cello,
html .bodywebsite .btn-cello:active,
html .bodywebsite .btn-cello.active,
html .bodywebsite .btn-cello:active:focus,
html .bodywebsite .btn-cello.active:focus,
html .bodywebsite .btn-cello:focus:active,
html .bodywebsite .btn-cello:focus {
  color: #fff;
  background-color: #1e3953;
  border-color: #1e3953;
}
.bodywebsite .open > html .btn-cello.dropdown-toggle,
html .bodywebsite .btn-cello:hover {
  color: #fff;
  background-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  border-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
html .bodywebsite .btn-cello.disabled,
html .bodywebsite .btn-cello[disabled],
.bodywebsite fieldset[disabled] html .btn-cello {
  pointer-events: none;
  opacity: .5;
}
html .bodywebsite .btn-cello .badge {
  color: #1e3953;
  background-color: #fff;
}
.bodywebsite .btn-xs {
  padding: 12px 25px;
  font-size: 11px;
  line-height: 1.71429;
  border-radius: 0;
}
@media (min-width: 768px) {
  .bodywebsite .btn-xs {
	min-width: 165px;
  }
}
.bodywebsite .btn-sm {
  padding: 10px 20px;
  font-size: 13px;
  line-height: 1.71429;
  border-radius: 0;
}
@media (min-width: 768px) {
  .bodywebsite .btn-sm {
	min-width: 170px;
  }
}
.bodywebsite .btn-lg {
  padding: 14px 30px;
  font-size: 14px;
  line-height: 1.71429;
  border-radius: 0;
}
@media (min-width: 768px) {
  .bodywebsite .btn-lg {
	min-width: 270px;
	padding: 18px 40px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .btn-lg-bigger {
	padding-top: 28px;
	padding-bottom: 28px;
  }
}
.bodywebsite .btn-xl {
  padding: 20px 35px;
  font-size: 15px;
  line-height: 1.71429;
  border-radius: 0;
}
@media (min-width: 768px) {
  .bodywebsite .btn-xl {
	padding: 21px 50px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .btn-xl {
	min-width: 270px;
  }
}
.bodywebsite .btn-min-width-0 {
  min-width: 0;
}
.bodywebsite .btn-block {
  min-width: 30px;
  max-width: 100%;
}
.bodywebsite .btn-rect {
  border-radius: 0;
}
.bodywebsite .btn-round {
  border-radius: 12px;
}
.bodywebsite .btn-circle {
  border-radius: 35px;
}
.bodywebsite .btn-round-bottom {
  border-radius: 0 0 5px 5px;
}
.bodywebsite .btn-shadow {
  box-shadow: -3px 3px 3px 0 rgba(0, 0, 0, 0.14);
}
.bodywebsite .btn.btn-icon {
  display: -webkit-inline-box;
  display: -webkit-inline-flex;
  display: -ms-inline-flexbox;
  display: inline-flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: center;
  -ms-flex-pack: center;
  justify-content: center;
  vertical-align: middle;
}
.bodywebsite .btn.btn-icon .icon {
  position: relative;
  top: 1px;
  display: inline-block;
  width: auto;
  height: auto;
  line-height: 0;
  vertical-align: middle;
  transition: 0s;
}
.bodywebsite .btn.btn-icon-left .icon {
  margin-right: 10px;
}
.bodywebsite .btn.btn-icon-right {
  -webkit-flex-direction: row-reverse;
  -ms-flex-direction: row-reverse;
  flex-direction: row-reverse;
}
.bodywebsite .btn.btn-icon-right .icon {
  margin-left: 10px;
}
.bodywebsite .btn-icon-only {
  background: none;
  border: none;
  display: inline-block;
  padding: 0;
  outline: none;
  outline-offset: 0;
  cursor: pointer;
  -webkit-appearance: none;
  font-size: 0;
  line-height: 0;
  transition: .33s all ease;
}
.bodywebsite .btn-icon-only::-moz-focus-inner {
  border: none;
  padding: 0;
}
.bodywebsite .btn-icon-only.btn-icon-only-primary,
.bodywebsite .btn-icon-only.btn-icon-only-primary:active,
.bodywebsite .btn-icon-only.btn-icon-only-primary:focus {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .btn-icon-only.btn-icon-only-primary:hover {
  color: #000;
}
.bodywebsite .btn-icon-only {
  padding: 9px 18px;
}
.bodywebsite .btn-icon-single {
  display: inline-block;
  padding: 0;
  min-width: 0;
}
.bodywebsite .btn-icon-default {
  color: #000;
}
.bodywebsite .btn-icon-default:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .btn-cello-outline.btn-icon .icon {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  transition: .33s all ease;
}
.bodywebsite .btn-cello-outline.btn-icon:hover.btn-icon .icon {
  color: #fff;
}
.bodywebsite .button-block * + .btn {
  margin-top: 0;
}
.bodywebsite .icon {
  display: inline-block;
  text-align: center;
}
.bodywebsite .icon:before {
  display: inline-block;
  font-style: normal;
  speak: none;
  text-transform: none;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
.bodywebsite [class*='icon-circle'] {
  border-radius: 50%;
  overflow: hidden;
}
.bodywebsite [class*='icon-round'] {
  border-radius: 4px;
  overflow: hidden;
}
.bodywebsite .page .icon-default {
  color: #9f9f9f;
}
.bodywebsite .page .icon-black {
  color: #000;
}
.bodywebsite .page .icon-primary {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page .icon-gunsmoke {
  color: #767877;
}
.bodywebsite .page .icon-tundora {
  color: #414141;
}
.bodywebsite .page .icon-gray-dark-filled {
  color: #fff;
  background: #2a2b2b;
}
.bodywebsite .page .icon-san-juan-filled {
  color: #fff;
  background: #2e5275;
}
.bodywebsite .page .icon-silver-chalice-filled {
  color: #fff;
  background: #ababab;
}
.bodywebsite .page .icon-abbey-filled {
  color: #fff;
  background: #464a4d;
}
.bodywebsite .page .icon-white {
  color: #fff;
}
.bodywebsite .page a.icon-default,
.bodywebsite .page a.icon-default:active,
.bodywebsite .page a.icon-default:focus {
  color: #9f9f9f;
}
.bodywebsite .page a.icon-default:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page a.icon-primary,
.bodywebsite .page a.icon-primary:active,
.bodywebsite .page a.icon-primary:focus {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page a.icon-primary:hover {
  color: #fff;
}
.bodywebsite .page a.icon-abbey-filled:hover {
  color: #fff;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page a.icon-tundora-inverse,
.bodywebsite .page a.icon-tundora-inverse:active,
.bodywebsite .page a.icon-tundora-inverse:focus {
  color: #414141;
}
.bodywebsite .page a.icon-tundora-inverse:hover {
  color: #fff;
}
.bodywebsite .page a.icon-gray-dark-filled,
.bodywebsite .page a.icon-gray-dark-filled:active,
.bodywebsite .page a.icon-gray-dark-filled:focus {
  color: #fff;
  background: #2a2b2b;
}
.bodywebsite .page a.icon-gray-dark-filled:hover {
  color: #fff;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page a.icon-silver-chalice-filled,
.bodywebsite .page a.icon-silver-chalice-filled:active,
.bodywebsite .page a.icon-silver-chalice-filled:focus {
  color: #fff;
  background: #ababab;
}
.bodywebsite .page a.icon-silver-chalice-filled:hover {
  color: #fff;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page a.icon-san-juan-filled,
.bodywebsite .page a.icon-san-juan-filled:active,
.bodywebsite .page a.icon-san-juan-filled:focus {
  color: #fff;
  background: #2e5275;
}
.bodywebsite .page a.icon-san-juan-filled:hover {
  color: #fff;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page .icon-xxs {
  width: 18px;
  height: 18px;
  font-size: 18px;
  line-height: 18px;
}
.bodywebsite .page .icon-xxs-small {
  width: 16px;
  height: 16px;
  font-size: 16px;
  line-height: 16px;
}
.bodywebsite .page .icon-xxs-smaller {
  width: 14px;
  height: 14px;
  font-size: 14px;
  line-height: 14px;
}
.bodywebsite .page .icon-xxs-smallest {
  width: 12px;
  height: 12px;
  font-size: 12px;
  line-height: 12px;
}
.bodywebsite .page .icon-xs {
  width: 22px;
  height: 22px;
  font-size: 22px;
  line-height: 22px;
}
.bodywebsite .page .icon-xs-smaller {
  width: 20px;
  height: 20px;
  font-size: 20px;
  line-height: 20px;
}
.bodywebsite .page .icon-sm {
  width: 24px;
  height: 24px;
  font-size: 24px;
  line-height: 24px;
}
.bodywebsite .page .icon-sm-custom {
  width: 24px;
  height: 24px;
  font-size: 24px;
  line-height: 24px;
}
@media (min-width: 992px) {
  .bodywebsite .page .icon-sm-custom {
	width: 30px;
	height: 30px;
	font-size: 30px;
	line-height: 30px;
  }
}
.bodywebsite .page .icon-md {
  width: 36px;
  height: 36px;
  font-size: 36px;
  line-height: 36px;
}
.bodywebsite .page .icon-md-custom {
  width: 26px;
  height: 26px;
  font-size: 26px;
  line-height: 26px;
}
@media (min-width: 992px) {
  .bodywebsite .page .icon-md-custom {
	width: 36px;
	height: 36px;
	font-size: 36px;
	line-height: 36px;
  }
}
.bodywebsite .page .icon-md-smaller {
  width: 30px;
  height: 30px;
  font-size: 30px;
  line-height: 30px;
}
.bodywebsite .page .icon-lg {
  width: 45px;
  height: 45px;
  font-size: 45px;
  line-height: 45px;
}
.bodywebsite .page .icon-lg-variant-1 {
  width: 42px;
  height: 42px;
  font-size: 42px;
  line-height: 42px;
}
.bodywebsite .page .icon-lg-variant-2 {
  width: 44px;
  height: 44px;
  font-size: 44px;
  line-height: 44px;
}
.bodywebsite .page .icon-lg-bigger {
  width: 50px;
  height: 50px;
  font-size: 50px;
  line-height: 50px;
}
.bodywebsite .page .icon-xl {
  width: 60px;
  height: 60px;
  font-size: 60px;
  line-height: 60px;
}
.bodywebsite .page [class*='icon-round'].icon-xxs-smallest,
.bodywebsite .page [class*='icon-circle'].icon-xxs-smallest {
  width: 26px;
  height: 26px;
  line-height: 26px;
}
.bodywebsite .icon-shift-1 {
  position: relative;
  top: 2px;
}
.bodywebsite .icon-shift-2 {
  position: relative;
  top: 2px;
}
@media (min-width: 992px) {
  .bodywebsite .icon-shift-2 {
	top: 4px;
  }
}
.bodywebsite .icon-1:before,
.bodywebsite .icon-2:before,
.bodywebsite .icon-4:before,
.bodywebsite .icon-5:before,
.bodywebsite .icon-6:before,
.bodywebsite .icon-3:before {
  content: '';
  display: inline-block;
  width: 40px;
  height: 40px;
}
.bodywebsite .thumbnail {
  position: relative;
  z-index: 1;
  width: 100%;
  max-height: 100%;
  overflow: hidden;
  padding: 0;
  margin: 0;
  border: none;
  border-radius: 0;
  background-color: transparent;
}
.bodywebsite .thumbnail .caption {
  padding: 0;
}
.bodywebsite .thumbnail {
  box-shadow: none;
}
.bodywebsite .thumbnail-variant-1 {
  background-color: transparent;
  text-align: center;
}
.bodywebsite .thumbnail-variant-1 .thumbnail-image {
  position: relative;
  display: inline-block;
  overflow: hidden;
  pointer-events: none;
}
.bodywebsite .thumbnail-variant-1 .thumbnail-image,
.bodywebsite .thumbnail-variant-1 .thumbnail-image > img {
  border-radius: 600px;
}
.bodywebsite .thumbnail-variant-1 .thumbnail-image > img {
  width: auto;
  pointer-events: auto;
}
.bodywebsite .thumbnail-variant-1 .thumbnail-image-inner {
  position: absolute;
  top: 0;
  right: 1px;
  bottom: 0;
  left: 1px;
  z-index: 2;
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: center;
  -ms-flex-pack: center;
  justify-content: center;
  padding: 20px 5px 5px;
  background: rgba(0, 0, 0, 0.4);
  border-radius: 600px;
}
.bodywebsite .thumbnail-variant-1 .thumbnail-image-inner * {
  pointer-events: auto;
}
.bodywebsite .thumbnail-variant-1 .thumbnail-image-inner > * + * {
  margin-top: 0;
  margin-left: 20px;
}
.bodywebsite .thumbnail-variant-1 .header {
  line-height: 1.2;
}
.bodywebsite .thumbnail-variant-1 * + p {
  margin-top: 0;
}
.bodywebsite .thumbnail-variant-1 * + .thumbnail-caption {
  margin-top: 18px;
}
@media (min-width: 992px) {
  .bodywebsite .desktop .thumbnail-variant-1 .thumbnail-image-inner {
	opacity: 0;
	visibility: hidden;
	transform: rotate3d(0, 1, 0, 60deg);
	transition: .55s all ease;
	background: rgba(0, 0, 0, 0.6);
  }
  .bodywebsite .desktop .thumbnail-variant-1 .thumbnail-image:hover .thumbnail-image-inner {
	opacity: 1;
	visibility: visible;
	transform: rotate3d(0, 1, 0, 0deg);
  }
}
@media (min-width: 1200px) {
  .bodywebsite .thumbnail-variant-1 * + .thumbnail-caption {
	margin-top: 30px;
  }
}
.bodywebsite .thumbnail-variant-2 {
  min-height: 300px;
  padding: 30px 0 0;
  overflow: visible;
  text-align: center;
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: column;
  -ms-flex-direction: column;
  flex-direction: column;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: flex-end;
  -ms-flex-pack: end;
  justify-content: flex-end;
}
.bodywebsite .thumbnail-variant-2-wrap {
  padding-bottom: 25px;
}
.bodywebsite .thumbnail-variant-2 .thumbnail-image {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  height: 100%;
  width: 100%;
  overflow: hidden;
}
.bodywebsite .thumbnail-variant-2 .thumbnail-image > img {
  position: absolute;
  top: 20%;
  left: 50%;
  transform: translate(-50%, -20%);
  width: auto;
  min-width: 101%;
  max-width: none;
  height: auto;
  min-height: 100%;
  max-height: none;
}
.bodywebsite .thumbnail-variant-2:before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  z-index: 1;
  background: rgba(0, 0, 0, 0.5);
}
.bodywebsite .thumbnail-variant-2 .thumbnail-inner {
  position: relative;
  z-index: 2;
  padding: 30px 10px;
}
.bodywebsite .thumbnail-variant-2 .thumbnail-caption {
  position: relative;
  z-index: 3;
  width: calc(66%);
  padding: 17px 8px 25px;
  margin: 31px 17px -25px 17px;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .thumbnail-variant-2 .thumbnail-caption * {
  color: #fff;
}
.bodywebsite .thumbnail-variant-2 .thumbnail-caption a,
.bodywebsite .thumbnail-variant-2 .thumbnail-caption a:active,
.bodywebsite .thumbnail-variant-2 .thumbnail-caption a:focus {
  color: #fff;
}
.bodywebsite .thumbnail-variant-2 .thumbnail-caption a:hover {
  color: #9f9f9f;
}
.bodywebsite .thumbnail-variant-2 .text-header {
  font-size: 18px;
  font-weight: 700;
}
.bodywebsite .thumbnail-variant-2 .text-caption {
  font-style: italic;
  line-height: 1.3;
  font-family: "Roboto", Helvetica, Arial, sans-serif;
}
@media (min-width: 768px) {
  .bodywebsite .thumbnail-variant-2 .text-caption {
	font-size: 16px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .desktop .thumbnail-variant-2:before {
	top: 40px;
  }
  .bodywebsite .desktop .thumbnail-variant-2 .thumbnail-inner > * {
	position: relative;
	transform: translateY(14px);
	transition: 0.4s all ease-in-out;
  }
  .bodywebsite .desktop .thumbnail-variant-2:before,
  .bodywebsite .desktop .thumbnail-variant-2 .thumbnail-inner {
	opacity: 0;
	visibility: hidden;
	transition: 0.33s all ease-out;
  }
  .bodywebsite .desktop .thumbnail-variant-2:hover:before {
	top: 0;
	left: 0;
	right: 0;
  }
  .bodywebsite .desktop .thumbnail-variant-2:hover .thumbnail-inner > * {
	transform: translateY(0);
  }
  .bodywebsite .desktop .thumbnail-variant-2:hover:before,
  .bodywebsite .desktop .thumbnail-variant-2:hover .thumbnail-inner {
	opacity: 1;
	visibility: visible;
  }
}
@media (min-width: 992px) {
  .bodywebsite .thumbnail-variant-2 .thumbnail-caption {
	width: calc(84%);
	margin: 31px 8px -25px 8px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .thumbnail-variant-2 {
	width: calc(78%);
	margin: 0 11px 0;
  }
  .bodywebsite .thumbnail-variant-2 .thumbnail-caption {
	width: calc(66%);
	margin: 31px 17px -25px 17px;
  }
}
.bodywebsite .ie-11 .thumbnail-variant-2 {
  min-height: 0;
}
.bodywebsite .thumbnail-variant-3 {
  width: 100.025%;
  text-align: center;
}
.bodywebsite .thumbnail-variant-3 img {
  position: relative;
  left: 50%;
  transform: translateX(-50%);
  width: auto;
  max-width: none;
  min-width: 100.5%;
}
.bodywebsite .thumbnail-variant-3 .link-external {
  position: absolute;
  top: -30px;
  right: -30px;
  z-index: 1;
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: center;
  -ms-flex-pack: center;
  justify-content: center;
  width: 200px;
  height: 110px;
  padding: 55px 15px 5px;
  vertical-align: bottom;
  line-height: 40px;
  background: #fafafa;
  transform-origin: 74% 110%;
  transform: rotate(45deg);
  will-change: transform;
  text-align: center;
  /**
	  @bugfix: color flickering in child objects on hover
	  @affected: IE Edge
	*/
  transition: top 0.28s cubic-bezier(0.79, 0.14, 0.15, 0.86), right 0.28s cubic-bezier(0.79, 0.14, 0.15, 0.86), opacity 0.28s cubic-bezier(0.79, 0.14, 0.15, 0.86), visibility 0.28s cubic-bezier(0.79, 0.14, 0.15, 0.86);
}
.bodywebsite .thumbnail-variant-3 .link-external .icon {
  transition: none;
  transform: rotate(-45deg);
  color: #000;
  vertical-align: bottom;
}
.bodywebsite .thumbnail-variant-3 .link-external:hover {
  top: -12px;
  right: -12px;
}
.bodywebsite .thumbnail-variant-3 .link-original {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: flex-end;
  -ms-flex-align: end;
  align-items: flex-end;
  -webkit-justify-content: flex-start;
  -ms-flex-pack: start;
  justify-content: flex-start;
}
.bodywebsite .thumbnail-variant-3 .link-original,
.bodywebsite .thumbnail-variant-3 .link-original:active,
.bodywebsite .thumbnail-variant-3 .link-original:focus,
.bodywebsite .thumbnail-variant-3 .link-original:hover {
  color: #fff;
}
.bodywebsite .thumbnail-variant-3 .link-original:before {
  content: '\e8ff';
  position: relative;
  left: 20px;
  bottom: 30px;
  z-index: 3;
  font-family: 'Material Icons';
  font-size: 140px;
  line-height: 1;
  opacity: .2;
  transition: .33s all ease;
}
.bodywebsite .thumbnail-variant-3 .caption {
  position: absolute;
  top: -2px;
  right: 0;
  bottom: -2px;
  left: 0;
  padding: 15px;
  transition: 0.33s all ease-in-out;
  background: rgba(0, 0, 0, 0.6);
}
@media (min-width: 992px) {
  .bodywebsite .desktop .thumbnail-variant-3 figure img {
	will-change: transform;
	transition: 0.4s ease-out;
  }
  .bodywebsite .desktop .thumbnail-variant-3 .caption,
  .bodywebsite .desktop .thumbnail-variant-3 .link-external {
	opacity: 0;
	visibility: hidden;
  }
  .bodywebsite .desktop .thumbnail-variant-3 .link-external {
	right: -50px;
	top: -50px;
  }
  .bodywebsite .desktop .thumbnail-variant-3:hover .caption,
  .bodywebsite .desktop .thumbnail-variant-3:hover .link-external {
	opacity: 1;
	visibility: visible;
  }
  .bodywebsite .desktop .thumbnail-variant-3:hover figure img {
	transform: translateX(-50%) scale(1.08);
  }
  .bodywebsite .desktop .thumbnail-variant-3:hover .link-external {
	right: -30px;
	top: -30px;
  }
  .bodywebsite .desktop .thumbnail-variant-3:hover .link-external:hover {
	top: -20px;
	right: -20px;
  }
}
.bodywebsite .thumbnail-variant-3 > * + * {
  margin-top: 0;
}
@media (min-width: 768px) {
  .bodywebsite .thumbnail-wrap {
	padding: 0 5px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .thumbnail-wrap {
	padding: 0 9px;
  }
}
.bodywebsite .thumbnail-variant-4 {
  position: relative;
  overflow: hidden;
  box-shadow: 0px 0px 13px 0px rgba(1, 3, 4, 0.15);
}
.bodywebsite .thumbnail-variant-4 .thumbnail-image {
  background: #000;
}
.bodywebsite .thumbnail-variant-4 .thumbnail-image img {
  opacity: .92;
}
.bodywebsite .thumbnail-variant-4 .caption {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 16px 15px;
  text-align: center;
  color: #000;
  background: #fff;
}
.bodywebsite .thumbnail-variant-4 .text-light {
  color: #0d0d0d;
}
@media (min-width: 992px) {
  .bodywebsite .desktop .thumbnail-variant-4 .thumbnail-image img {
	position: relative;
	will-change: transform;
	opacity: 1;
	transition: opacity .7s, transform .7s;
	transform: scale3d(1.0001, 1.0001, 1);
  }
  .bodywebsite .desktop .thumbnail-variant-4 .caption,
  .bodywebsite .desktop .thumbnail-variant-4 .caption-header {
	transition: transform 0.55s;
	transform: translate3d(0, 200%, 0);
  }
  .bodywebsite .desktop .thumbnail-variant-4 .caption-header {
	transition-delay: 0.05s;
  }
  .bodywebsite .desktop .thumbnail-variant-4:hover .thumbnail-image img {
	opacity: .9;
	transform: scale3d(1.07, 1.07, 1);
  }
  .bodywebsite .desktop .thumbnail-variant-4:hover .caption,
  .bodywebsite .desktop .thumbnail-variant-4:hover .caption-header {
	transform: translate3d(0, 0, 0);
  }
}
@media (min-width: 992px) {
  .bodywebsite .thumbnail-variant-4 .caption {
	padding: 20px 15px;
  }
}
.bodywebsite .thumbnail-profile .thumbnail-image img {
  width: 100%;
}
.bodywebsite .thumbnail-profile .thumbnail-caption {
  padding: 20px;
  background: #f2f3f7;
}
.bodywebsite .thumbnail-profile .thumbnail-caption-inner {
  margin-bottom: -12px;
  -webkit-align-items: flex-end;
  -ms-flex-align: end;
  align-items: flex-end;
  transform: translateY(-12px);
  text-align: center;
}
.bodywebsite .thumbnail-profile .thumbnail-caption-inner > * {
  display: inline-block;
  margin-top: 12px;
  -webkit-flex-shrink: 0;
  -ms-flex-negative: 0;
  flex-shrink: 0;
}
.bodywebsite .thumbnail-profile .thumbnail-caption-inner,
.bodywebsite .thumbnail-profile .thumbnail-caption-inner > ul {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: center;
  -ms-flex-pack: center;
  justify-content: center;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-flex-wrap: wrap;
  -ms-flex-wrap: wrap;
  flex-wrap: wrap;
}
.bodywebsite .thumbnail-profile .thumbnail-caption-inner ul {
  position: relative;
  margin-bottom: -3px;
  transform: translateY(-3px);
  -webkit-flex-grow: 2;
  -ms-flex-positive: 2;
  flex-grow: 2;
}
.bodywebsite .thumbnail-profile .thumbnail-caption-inner ul > li {
  display: inline-block;
  margin-top: 3px;
  padding: 0 7px;
}
.bodywebsite .thumbnail-profile .thumbnail-caption-inner .btn-wrap {
  -webkit-flex-grow: 1;
  -ms-flex-positive: 1;
  flex-grow: 1;
}
@media (min-width: 576px) {
  .bodywebsite .thumbnail-profile .thumbnail-caption-inner,
  .bodywebsite .thumbnail-profile .thumbnail-caption-inner ul {
	-webkit-justify-content: center;
	-ms-flex-pack: center;
	justify-content: center;
  }
}
@media (min-width: 992px) {
  .bodywebsite .thumbnail-profile .thumbnail-caption-inner ul {
	-webkit-justify-content: space-around;
	-ms-flex-pack: distribute;
	justify-content: space-around;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .thumbnail-profile .thumbnail-caption-inner {
	text-align: left;
	-webkit-justify-content: space-between;
	-ms-flex-pack: justify;
	justify-content: space-between;
  }
  .bodywebsite .thumbnail-profile .thumbnail-caption-inner .btn-wrap {
	text-align: right;
  }
}
@media (max-width: 767px) {
  .bodywebsite .thumbnail-variant-2 {
	max-width: 300px;
	margin-left: auto;
	margin-right: auto;
  }
  .bodywebsite .thumbnail-variant-3,
  .bodywebsite .thumbnail-profile {
	max-width: 370px;
	margin-left: auto;
	margin-right: auto;
  }
}
.bodywebsite .thumbnail-block {
  display: block;
}
.bodywebsite .thumbnail-block > img,
.bodywebsite .thumbnail-block a > img {
  width: 100%;
  height: auto;
}
.bodywebsite .thumbnail-variant-5 {
  padding: 40px 20px;
  display: inline-block;
}
.bodywebsite .thumbnail-variant-5,
.bodywebsite .thumbnail-variant-5 img {
  transition: 0.2s ease-in-out;
}
@media (min-width: 992px) {
  .bodywebsite .thumbnail-variant-5 {
	border-top: 5px solid transparent;
	border-bottom: 5px solid transparent;
  }
  .bodywebsite .thumbnail-variant-5 .thumbnail-variant-5-img-wrap {
	position: relative;
	display: inline-block;
  }
  .bodywebsite .thumbnail-variant-5 .thumbnail-variant-5-img-wrap:before {
	content: '';
	position: absolute;
	top: 0;
	right: 0;
	left: 0;
	width: 100%;
	height: 100%;
	border-radius: 50%;
	background: rgba(0, 0, 0, 0.4);
	transition: 0.2s ease-in-out;
  }
  .bodywebsite .thumbnail-variant-5 {
	box-shadow: 0 1px 4px 0 rgba(0, 0, 0, 0.15);
	border-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  }
  .bodywebsite .thumbnail-variant-5:hover .thumbnail-variant-5-img-wrap:before {
	opacity: 0;
  }
  .bodywebsite .thumbnail-variant-5:hover img {
	will-change: transform;
	-webkit-transform: scale(1.18);
	transform: scale(1.18);
  }
}
@media (min-width: 992px) {
  .bodywebsite .thumbnail-variant-5 {
	padding: 40px 50px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .thumbnail-variant-5 {
	padding: 65px 50px;
  }
}
.bodywebsite .thumbnail-variant-5 * + h2 {
  margin-top: 36px;
}
.bodywebsite .thumbnail-variant-5 h2 + * {
  margin-top: 0;
}
.bodywebsite .thumbnail-variant-5 .link-group + .link-group {
  margin-top: 7px;
}
.bodywebsite .thumbnail-variant-5 .divider-fullwidth {
  margin-top: 12px;
  margin-bottom: 17px;
}
.bodywebsite .thumbnail-with-img * + .thumbnail-title {
  margin-top: 22px;
}
.bodywebsite .thumbnail-with-img .thumbnail-title + * {
  margin-top: 10px;
}
.bodywebsite .thumbnail-profile-info h4 + * {
  margin-top: 0;
}
.bodywebsite .thumbnail-profile-info * + .profile-quote {
  margin-top: 15px;
}
.bodywebsite .thumbnail-profile-info .profile-quote + * {
  margin-top: 15px;
}
.bodywebsite .thumbnail-profile-info * + .list-progress {
  margin-top: 35px;
}
@media (min-width: 992px) {
  .bodywebsite .thumbnail-profile-info * + .profile-quote {
	margin-top: 0;
  }
  .bodywebsite .thumbnail-profile-info .profile-quote + * {
	margin-top: 0;
  }
}
.bodywebsite figure img {
  margin: 18px;
  border: 1px solid #ccc;
  box-shadow: 1px 1px 25px #aaa;
  max-width: calc(44%);
}
.bodywebsite figure img {
  width: 100%;
  height: auto;
  max-width: none;
}
.bodywebsite .figure .caption {
  padding: 15px;
}
.bodywebsite .rd-mailform {
  position: relative;
}
.bodywebsite label {
  margin-bottom: 0;
}
.bodywebsite input::-webkit-autofill + .form-label {
  display: none;
  transition: none;
}
.bodywebsite .form-label,
.bodywebsite .form-input {
  font-weight: 400;
}
.bodywebsite .input-sm,
.bodywebsite .input-lg,
.bodywebsite .form-input {
  font-size: 14px;
}
.bodywebsite .input-sm,
.bodywebsite .input-sm:focus,
.bodywebsite .input-lg,
.bodywebsite .input-lg:focus,
.bodywebsite .form-input,
.bodywebsite .form-input:focus {
  box-shadow: none;
}
.bodywebsite textarea.form-input {
  height: 166px;
  min-height: 52px;
  max-height: 249px;
  resize: vertical;
}
.bodywebsite .form-input {
  height: auto;
  min-height: 20px;
  border: 0px solid #dedede;
  border-radius: 0;
  -webkit-appearance: none;
  line-height: 24px;
}
.bodywebsite .form-input:focus {
  outline: 0;
}
.bodywebsite .form-wrap {
  position: relative;
  margin-bottom: 0;
}
.bodywebsite .form-wrap + .form-wrap {
  margin-top: 10px;
}
.bodywebsite .form-label {
  position: absolute;
  top: 26px;
  left: 19px;
  font-size: 14px;
  color: #9f9f9f;
  pointer-events: none;
  z-index: 9;
  transition: .3s;
  transform: translateY(-50%);
  will-change: transform;
}
.bodywebsite .form-label.focus {
  opacity: 0;
}
.bodywebsite .form-label.auto-fill {
  color: #9f9f9f;
}
@media (min-width: 768px) {
  .bodywebsite .form-label-outside {
	position: static;
	margin-bottom: 8px;
  }
  .bodywebsite .form-label-outside,
  .bodywebsite .form-label-outside.focus,
  .bodywebsite .form-label-outside.auto-fill {
	transform: none;
	color: #9f9f9f;
	font-size: 14px;
  }
}
.bodywebsite .form-wrap-outside {
  margin-top: 10px;
}
.bodywebsite .form-wrap-outside .form-label-outside {
  position: absolute;
  top: -15px;
  left: 0;
}
.bodywebsite .form-wrap-outside .form-label-outside.focus {
  opacity: 1;
}
@media (min-width: 768px) {
  .bodywebsite .form-wrap-outside .form-label-outside {
	top: -30px;
  }
}
.bodywebsite .form-border-bottom {
  border-bottom: 3px solid <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .form-validation {
  position: absolute;
  right: 10px;
  top: 2px;
  font-size: 11px;
  line-height: 11px;
  color: #fe4a21;
  margin-top: 2px;
  transition: .3s;
}
.bodywebsite form.label-outside .form-validation {
  top: 12px;
}
.bodywebsite .has-error .help-block,
.bodywebsite .has-error .control-label,
.bodywebsite .has-error .radio,
.bodywebsite .has-error .checkbox,
.bodywebsite .has-error .radio-inline,
.bodywebsite .has-error .checkbox-inline,
.bodywebsite .has-error.radio label,
.bodywebsite .has-error.checkbox label,
.bodywebsite .has-error.radio-inline label,
.bodywebsite .has-error.checkbox-inline label {
  color: #fe4a21;
}
.bodywebsite .has-error .form-input:not(.form-input-impressed),
.bodywebsite .has-error .form-input:not(.form-input-impressed):focus {
  border-color: #fe4a21;
  box-shadow: none;
}
.bodywebsite .has-error .form-input-impressed,
.bodywebsite .has-error .form-input-impressed:focus {
  box-shadow: inset 0 0 0 1px #fe4a21;
}
.bodywebsite .has-error .input-group-addon {
  color: #fff;
  border-color: #fe4a21;
  background-color: #fe4a21;
}
.bodywebsite .form-inline .has-error ~ button[type='submit'] {
  border-color: #fe4a21;
  background: #fe4a21;
}
.bodywebsite .has-error .form-validation {
  color: #fe4a21;
}
.bodywebsite .has-success .help-block,
.bodywebsite .has-success .control-label,
.bodywebsite .has-success .radio,
.bodywebsite .has-success .checkbox,
.bodywebsite .has-success .radio-inline,
.bodywebsite .has-success .checkbox-inline,
.bodywebsite .has-success.radio label,
.bodywebsite .has-success.checkbox label,
.bodywebsite .has-success.radio-inline label,
.bodywebsite .has-success.checkbox-inline label {
  color: #58c476;
}
.bodywebsite .has-success .form-input:not(.form-input-impressed),
.bodywebsite .has-success .form-input:not(.form-input-impressed):focus {
  border-color: #dff0d8;
  box-shadow: none;
}
.bodywebsite .has-success .form-input-impressed,
.bodywebsite .has-success .form-input-impressed:focus {
  box-shadow: inset 0 0 0 1px #dff0d8;
}
.bodywebsite .has-success .input-group-addon {
  color: #fff;
  border-color: #dff0d8;
  background-color: #dff0d8;
}
.bodywebsite .form-inline .has-success ~ button[type='submit'] {
  border-color: #dff0d8;
  background: #dff0d8;
}
.bodywebsite .has-success .form-validation {
  color: #58c476;
}
.bodywebsite .has-warning .help-block,
.bodywebsite .has-warning .control-label,
.bodywebsite .has-warning .radio,
.bodywebsite .has-warning .checkbox,
.bodywebsite .has-warning .radio-inline,
.bodywebsite .has-warning .checkbox-inline,
.bodywebsite .has-warning.radio label,
.bodywebsite .has-warning.checkbox label,
.bodywebsite .has-warning.radio-inline label,
.bodywebsite .has-warning.checkbox-inline label {
  color: #c49558;
}
.bodywebsite .has-warning .form-input:not(.form-input-impressed),
.bodywebsite .has-warning .form-input:not(.form-input-impressed):focus {
  border-color: #fcf8e3;
  box-shadow: none;
}
.bodywebsite .has-warning .form-input-impressed,
.bodywebsite .has-warning .form-input-impressed:focus {
  box-shadow: inset 0 0 0 1px #fcf8e3;
}
.bodywebsite .has-warning .input-group-addon {
  color: #fff;
  border-color: #fcf8e3;
  background-color: #fcf8e3;
}
.bodywebsite .form-inline .has-warning ~ button[type='submit'] {
  border-color: #fcf8e3;
  background: #fcf8e3;
}
.bodywebsite .has-warning .form-validation {
  color: #c49558;
}
.bodywebsite .has-info .help-block,
.bodywebsite .has-info .control-label,
.bodywebsite .has-info .radio,
.bodywebsite .has-info .checkbox,
.bodywebsite .has-info .radio-inline,
.bodywebsite .has-info .checkbox-inline,
.bodywebsite .has-info.radio label,
.bodywebsite .has-info.checkbox label,
.bodywebsite .has-info.radio-inline label,
.bodywebsite .has-info.checkbox-inline label {
  color: #3e9cf6;
}
.bodywebsite .has-info .form-input:not(.form-input-impressed),
.bodywebsite .has-info .form-input:not(.form-input-impressed):focus {
  border-color: #d9edf7;
  box-shadow: none;
}
.bodywebsite .has-info .form-input-impressed,
.bodywebsite .has-info .form-input-impressed:focus {
  box-shadow: inset 0 0 0 1px #d9edf7;
}
.bodywebsite .has-info .input-group-addon {
  color: #fff;
  border-color: #d9edf7;
  background-color: #d9edf7;
}
.bodywebsite .form-inline .has-info ~ button[type='submit'] {
  border-color: #d9edf7;
  background: #d9edf7;
}
.bodywebsite .has-info .form-validation {
  color: #3e9cf6;
}
.bodywebsite #form-output-global {
  position: fixed;
  bottom: 30px;
  left: 15px;
  visibility: hidden;
  transform: translateX(-500px);
  transition: .3s all ease;
  z-index: 9999999;
}
.bodywebsite #form-output-global.active {
  transform: translateX(0);
  visibility: visible;
}
@media (min-width: 576px) {
  .bodywebsite #form-output-global {
	left: 30px;
  }
}
.bodywebsite .form-output {
  position: absolute;
  top: 100%;
  left: 0;
  font-size: 14px;
  line-height: 1.5;
  margin-top: 2px;
  transition: .3s;
  opacity: 0;
  visibility: hidden;
}
.bodywebsite .form-output.active {
  opacity: 1;
  visibility: visible;
}
.bodywebsite .form-output.error {
  color: #fe4a21;
}
.bodywebsite .form-output.success {
  color: #58c476;
}
.bodywebsite .radio .radio-custom,
.bodywebsite .radio-inline .radio-custom,
.bodywebsite .checkbox .checkbox-custom,
.bodywebsite .checkbox-inline .checkbox-custom {
  opacity: 0;
}
.bodywebsite .radio .radio-custom,
.bodywebsite .radio .radio-custom-dummy,
.bodywebsite .radio-inline .radio-custom,
.bodywebsite .radio-inline .radio-custom-dummy,
.bodywebsite .checkbox .checkbox-custom,
.bodywebsite .checkbox .checkbox-custom-dummy,
.bodywebsite .checkbox-inline .checkbox-custom,
.bodywebsite .checkbox-inline .checkbox-custom-dummy {
  position: absolute;
  width: 18px;
  height: 18px;
  margin-left: -20px;
  margin-top: 3px;
  outline: none;
  cursor: pointer;
}
.bodywebsite .radio .radio-custom-dummy,
.bodywebsite .radio-inline .radio-custom-dummy,
.bodywebsite .checkbox .checkbox-custom-dummy,
.bodywebsite .checkbox-inline .checkbox-custom-dummy {
  pointer-events: none;
}
.bodywebsite .radio .radio-custom-dummy:after,
.bodywebsite .radio-inline .radio-custom-dummy:after,
.bodywebsite .checkbox .checkbox-custom-dummy:after,
.bodywebsite .checkbox-inline .checkbox-custom-dummy:after {
  position: absolute;
  opacity: 0;
  transition: .22s;
}
.bodywebsite .radio .radio-custom:focus,
.bodywebsite .radio-inline .radio-custom:focus,
.bodywebsite .checkbox .checkbox-custom:focus,
.bodywebsite .checkbox-inline .checkbox-custom:focus {
  outline: none;
}
.bodywebsite .radio-custom:checked + .radio-custom-dummy:after,
.bodywebsite .checkbox-custom:checked + .checkbox-custom-dummy:after {
  opacity: 1;
}
.bodywebsite .radio,
.bodywebsite .radio-inline {
  padding-left: 30px;
}
.bodywebsite .radio .radio-custom-dummy,
.bodywebsite .radio-inline .radio-custom-dummy {
  margin-top: 2px;
  border-radius: 50%;
  margin-left: -30px;
  background: transparent;
  border: 2px solid #000;
}
.bodywebsite .radio .radio-custom-dummy:after,
.bodywebsite .radio-inline .radio-custom-dummy:after {
  content: '';
  top: 3px;
  right: 3px;
  bottom: 3px;
  left: 3px;
  background: #00030a;
  border-radius: 50%;
}
.bodywebsite .form-wrap-color .radio-inline,
.bodywebsite .form-wrap-size .radio-inline {
  padding-left: 0;
}
.bodywebsite .form-wrap-color .radio-control,
.bodywebsite .form-wrap-size .radio-control {
  position: relative;
  display: block;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  margin-top: 23px;
  margin-bottom: 23px;
}
.bodywebsite .form-wrap-color .radio-control:after,
.bodywebsite .form-wrap-size .radio-control:after {
  bottom: 0;
}
.bodywebsite .form-wrap-color .radio-control:after,
.bodywebsite .form-wrap-size .radio-control:after {
  content: '';
  position: absolute;
  left: 50%;
  bottom: -23px;
  transform: translateX(-50%);
  width: 0;
  max-width: 100%;
  height: 3px;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  visibility: hidden;
  transition: .2s;
}
.bodywebsite .form-wrap-color .radio-custom:checked ~ .radio-control:after,
.bodywebsite .form-wrap-size .radio-custom:checked ~ .radio-control:after {
  visibility: visible;
  width: 100%;
}
.bodywebsite .form-wrap-color .radio-custom-dummy,
.bodywebsite .form-wrap-size .radio-custom-dummy {
  display: none;
}
.bodywebsite .form-wrap-size .radio-inline {
  padding-left: 2px;
  padding-right: 2px;
}
.bodywebsite .form-wrap-size .radio-inline + .radio-inline {
  margin-left: 1px;
}
.bodywebsite .form-wrap-size .radio-control {
  color: #9f9f9f;
  text-align: center;
  text-transform: uppercase;
  transition: .2s;
}
.bodywebsite .form-wrap-size .radio-control:hover {
  color: #000;
}
.bodywebsite .form-wrap-size .radio-custom:checked ~ .radio-control {
  color: #000;
}
.bodywebsite .checkbox,
.bodywebsite .checkbox-inline {
  padding-left: 38px;
  color: #000;
}
.bodywebsite .checkbox .checkbox-custom-dummy,
.bodywebsite .checkbox-inline .checkbox-custom-dummy {
  pointer-events: none;
  border-radius: 2px;
  margin-left: 0;
  left: 0;
  background: #fff;
  box-shadow: none;
  border: 2px solid #dedede;
}
.bodywebsite .checkbox .checkbox-custom-dummy:after,
.bodywebsite .checkbox-inline .checkbox-custom-dummy:after {
  content: '\e5ca';
  font-family: 'Material Icons';
  font-size: 22px;
  line-height: 10px;
  position: absolute;
  top: 0;
  left: -1px;
  color: #2a2b2b;
}
.bodywebsite .checkbox-small {
  padding-left: 26px;
}
.bodywebsite .checkbox-small .checkbox-custom-dummy {
  margin-top: 6px;
  width: 12px;
  height: 12px;
  border-width: 1px;
  border-radius: 1px;
}
.bodywebsite .checkbox-small .checkbox-custom-dummy:after {
  top: -1px;
  left: -2px;
  font-size: 18px;
}
.bodywebsite .textarea-lined-wrap {
  position: relative;
  line-height: 2.39;
}
.bodywebsite .textarea-lined-wrap textarea {
  height: 203px;
  resize: none;
  overflow: hidden;
  line-height: 2.39;
  background-color: transparent;
}
.bodywebsite .textarea-lined-wrap-xs textarea {
  height: 68px;
}
.bodywebsite .page .form-classic-bordered .form-label,
.bodywebsite .page .form-classic-bordered .form-label-outside,
.bodywebsite .page .form-classic-bordered .form-input {
  color: #000;
}
.bodywebsite .page .form-classic-bordered .form-input {
  border: 1px solid #dedede;
}
.bodywebsite .page .form-modern .form-input,
.bodywebsite .page .form-modern .form-label {
  color: #9f9f9f;
}
.bodywebsite .page .form-modern input {
  height: auto;
  min-height: 20px;
}
.bodywebsite .page .form-modern .form-input:focus {
  border-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page .form-modern .form-input {
  padding: 6px 0;
  border-radius: 0;
  border-width: 0 0 1px 0;
  border-color: #dedede;
  background-color: transparent;
}
.bodywebsite .page .form-modern .form-label {
  left: 0;
  top: 18px;
}
.bodywebsite .page .form-modern .form-validation {
  top: auto;
  left: auto;
  right: 0;
  bottom: -12px;
  font-style: italic;
}
.bodywebsite .page .form-modern .has-error .help-block,
.bodywebsite .page .form-modern .has-error .control-label,
.bodywebsite .page .form-modern .has-error .radio,
.bodywebsite .page .form-modern .has-error .checkbox,
.bodywebsite .page .form-modern .has-error .radio-inline,
.bodywebsite .page .form-modern .has-error .checkbox-inline,
.bodywebsite .page .form-modern .has-error.radio label,
.bodywebsite .page .form-modern .has-error.checkbox label,
.bodywebsite .page .form-modern .has-error.radio-inline label,
.bodywebsite .page .form-modern .has-error.checkbox-inline label {
  color: #fe4a21;
}
.bodywebsite .page .form-modern .has-error .form-input:not(.form-input-impressed),
.bodywebsite .page .form-modern .has-error .form-input:not(.form-input-impressed):focus {
  border-color: #fe4a21;
  box-shadow: none;
}
.bodywebsite .page .form-modern .has-error .form-input-impressed,
.bodywebsite .page .form-modern .has-error .form-input-impressed:focus {
  box-shadow: inset 0 0 0 1px #fe4a21;
}
.bodywebsite .page .form-modern .has-error .input-group-addon {
  color: #fff;
  border-color: #fe4a21;
  background-color: #fe4a21;
}
.bodywebsite .form-inline .page .form-modern .has-error ~ button[type='submit'] {
  border-color: #fe4a21;
  background: #fe4a21;
}
.bodywebsite .page .form-modern .has-error .form-validation {
  color: #fe4a21;
}
.bodywebsite .page .form-modern.form-darker .form-input,
.bodywebsite .page .form-modern.form-darker .form-label {
  color: #000;
}
.bodywebsite .page .form-modern.form-darker .form-label:not(.focus) + .form-input {
  border-color: #cdcdcd;
}
.bodywebsite .page .form-modern.form-inverse .form-label,
.bodywebsite .page .form-modern.form-inverse .form-input {
  color: #9f9f9f;
  background-color: transparent;
}
.bodywebsite .page .form-modern.form-inverse .form-label.text-white-05,
.bodywebsite .page .form-modern.form-inverse .form-input.text-white-05 {
  color: rgba(255, 255, 255, 0.5);
}
.bodywebsite .stacktable {
  width: 100%;
  text-align: left;
}
.bodywebsite .st-head-row {
  padding-top: 1em;
}
.bodywebsite .st-head-row.st-head-row-main {
  font-size: 1.5em;
  padding-top: 0;
}
.bodywebsite .st-key {
  width: 49%;
  text-align: right;
  padding-right: 1%;
}
.bodywebsite .st-val {
  width: 49%;
  padding-left: 1%;
}
.bodywebsite .stacktable.large-only {
  display: none;
}
.bodywebsite .stacktable.small-only {
  display: table;
}
@media (min-width: 768px) {
  .bodywebsite .stacktable.large-only {
	display: table;
  }
  .bodywebsite .stacktable.small-only {
	display: none;
  }
}
.bodywebsite .section-relative {
  position: relative;
}
.bodywebsite #sectionfooter h4,
.bodywebsite #sectiontestimonies h1 {
  color: #fff;
}
@media (min-width: 768px) {
  .bodywebsite .section-with-counters {
	padding-top: 1px;
	padding-bottom: 1px;
  }
  .bodywebsite .section-with-counters > div {
	position: relative;
	box-shadow: 2px 2px 27px 0px rgba(1, 3, 4, 0.35);
	z-index: 2;
	margin-top: -30px;
	margin-bottom: -30px;
  }
}
@media (min-width: 768px) {
  .bodywebsite .section-image-aside {
	position: relative;
  }
}
.bodywebsite .section-image-aside-img {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 190%;
  -webkit-background-size: cover;
  background-size: cover;
}
@media (min-width: 768px) {
  .bodywebsite .section-image-aside-img {
	width: 50vw;
  }
}
.bodywebsite .section-image-aside-left .section-image-aside-img {
  right: -50%;
}
@media (min-width: 768px) {
  .bodywebsite .section-image-aside-left .section-image-aside-img {
	right: 0;
  }
}
.bodywebsite .section-image-aside-right .section-image-aside-img {
  left: -50%;
}
@media (min-width: 768px) {
  .bodywebsite .section-image-aside-right .section-image-aside-img {
	left: 0;
  }
}
.bodywebsite .section-15 {
  padding-top: 15px;
  padding-bottom: 15px;
}
.bodywebsite .section-30 {
  padding-top: 30px;
  padding-bottom: 30px;
}
.bodywebsite .section-35 {
  padding-top: 35px;
  padding-bottom: 35px;
}
.bodywebsite .section-40 {
  padding-top: 40px;
  padding-bottom: 40px;
}
.bodywebsite .section-45 {
  padding-top: 45px;
  padding-bottom: 45px;
}
.bodywebsite .section-50 {
  padding-top: 50px;
  padding-bottom: 50px;
}
.bodywebsite .section-60 {
  padding-top: 60px;
  padding-bottom: 60px;
}
.bodywebsite .section-66 {
  padding-top: 66px;
  padding-bottom: 66px;
}
.bodywebsite .section-75 {
  padding-top: 75px;
  padding-bottom: 75px;
}
.bodywebsite .section-90 {
  padding-top: 90px;
  padding-bottom: 90px;
}
.bodywebsite .section-100 {
  padding-top: 100px;
  padding-bottom: 100px;
}
.bodywebsite .section-120 {
  padding-top: 120px;
  padding-bottom: 120px;
}
.bodywebsite .section-130 {
  padding-top: 130px;
  padding-bottom: 130px;
}
.bodywebsite .section-145 {
  padding-top: 145px;
  padding-bottom: 145px;
}
.bodywebsite .section-165 {
  padding-top: 165px;
  padding-bottom: 165px;
}
@media (min-width: 576px) {
  .bodywebsite .section-sm-15 {
	padding-top: 15px;
	padding-bottom: 15px;
  }
  .bodywebsite .section-sm-30 {
	padding-top: 30px;
	padding-bottom: 30px;
  }
  .bodywebsite .section-sm-35 {
	padding-top: 35px;
	padding-bottom: 35px;
  }
  .bodywebsite .section-sm-40 {
	padding-top: 40px;
	padding-bottom: 40px;
  }
  .bodywebsite .section-sm-45 {
	padding-top: 45px;
	padding-bottom: 45px;
  }
  .bodywebsite .section-sm-50 {
	padding-top: 50px;
	padding-bottom: 50px;
  }
  .bodywebsite .section-sm-60 {
	padding-top: 60px;
	padding-bottom: 60px;
  }
  .bodywebsite .section-sm-66 {
	padding-top: 66px;
	padding-bottom: 66px;
  }
  .bodywebsite .section-sm-75 {
	padding-top: 75px;
	padding-bottom: 75px;
  }
  .bodywebsite .section-sm-90 {
	padding-top: 90px;
	padding-bottom: 90px;
  }
  .bodywebsite .section-sm-100 {
	padding-top: 100px;
	padding-bottom: 100px;
  }
  .bodywebsite .section-sm-120 {
	padding-top: 120px;
	padding-bottom: 120px;
  }
  .bodywebsite .section-sm-130 {
	padding-top: 130px;
	padding-bottom: 130px;
  }
  .bodywebsite .section-sm-145 {
	padding-top: 145px;
	padding-bottom: 145px;
  }
  .bodywebsite .section-sm-165 {
	padding-top: 165px;
	padding-bottom: 165px;
  }
}
@media (min-width: 768px) {
  .bodywebsite .section-md-15 {
	padding-top: 15px;
	padding-bottom: 15px;
  }
  .bodywebsite .section-md-30 {
	padding-top: 30px;
	padding-bottom: 30px;
  }
  .bodywebsite .section-md-35 {
	padding-top: 35px;
	padding-bottom: 35px;
  }
  .bodywebsite .section-md-40 {
	padding-top: 40px;
	padding-bottom: 40px;
  }
  .bodywebsite .section-md-45 {
	padding-top: 45px;
	padding-bottom: 45px;
  }
  .bodywebsite .section-md-50 {
	padding-top: 50px;
	padding-bottom: 50px;
  }
  .bodywebsite .section-md-60 {
	padding-top: 60px;
	padding-bottom: 60px;
  }
  .bodywebsite .section-md-66 {
	padding-top: 66px;
	padding-bottom: 66px;
  }
  .bodywebsite .section-md-75 {
	padding-top: 75px;
	padding-bottom: 75px;
  }
  .bodywebsite .section-md-90 {
	padding-top: 90px;
	padding-bottom: 90px;
  }
  .bodywebsite .section-md-100 {
	padding-top: 100px;
	padding-bottom: 100px;
  }
  .bodywebsite .section-md-120 {
	padding-top: 120px;
	padding-bottom: 120px;
  }
  .bodywebsite .section-md-130 {
	padding-top: 130px;
	padding-bottom: 130px;
  }
  .bodywebsite .section-md-145 {
	padding-top: 145px;
	padding-bottom: 145px;
  }
  .bodywebsite .section-md-165 {
	padding-top: 165px;
	padding-bottom: 165px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .section-lg-15 {
	padding-top: 15px;
	padding-bottom: 15px;
  }
  .bodywebsite .section-lg-30 {
	padding-top: 30px;
	padding-bottom: 30px;
  }
  .bodywebsite .section-lg-35 {
	padding-top: 35px;
	padding-bottom: 35px;
  }
  .bodywebsite .section-lg-40 {
	padding-top: 40px;
	padding-bottom: 40px;
  }
  .bodywebsite .section-lg-45 {
	padding-top: 45px;
	padding-bottom: 45px;
  }
  .bodywebsite .section-lg-50 {
	padding-top: 50px;
	padding-bottom: 50px;
  }
  .bodywebsite .section-lg-60 {
	padding-top: 60px;
	padding-bottom: 60px;
  }
  .bodywebsite .section-lg-66 {
	padding-top: 66px;
	padding-bottom: 66px;
  }
  .bodywebsite .section-lg-75 {
	padding-top: 75px;
	padding-bottom: 75px;
  }
  .bodywebsite .section-lg-90 {
	padding-top: 90px;
	padding-bottom: 90px;
  }
  .bodywebsite .section-lg-100 {
	padding-top: 100px;
	padding-bottom: 100px;
  }
  .bodywebsite .section-lg-120 {
	padding-top: 120px;
	padding-bottom: 120px;
  }
  .bodywebsite .section-lg-130 {
	padding-top: 130px;
	padding-bottom: 130px;
  }
  .bodywebsite .section-lg-145 {
	padding-top: 145px;
	padding-bottom: 145px;
  }
  .bodywebsite .section-lg-165 {
	padding-top: 165px;
	padding-bottom: 165px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .section-xl-15 {
	padding-top: 15px;
	padding-bottom: 15px;
  }
  .bodywebsite .section-xl-30 {
	padding-top: 30px;
	padding-bottom: 30px;
  }
  .bodywebsite .section-xl-35 {
	padding-top: 35px;
	padding-bottom: 35px;
  }
  .bodywebsite .section-xl-40 {
	padding-top: 40px;
	padding-bottom: 40px;
  }
  .bodywebsite .section-xl-45 {
	padding-top: 45px;
	padding-bottom: 45px;
  }
  .bodywebsite .section-xl-50 {
	padding-top: 50px;
	padding-bottom: 50px;
  }
  .bodywebsite .section-xl-60 {
	padding-top: 60px;
	padding-bottom: 60px;
  }
  .bodywebsite .section-xl-66 {
	padding-top: 66px;
	padding-bottom: 66px;
  }
  .bodywebsite .section-xl-75 {
	padding-top: 75px;
	padding-bottom: 75px;
  }
  .bodywebsite .section-xl-90 {
	padding-top: 90px;
	padding-bottom: 90px;
  }
  .bodywebsite .section-xl-100 {
	padding-top: 100px;
	padding-bottom: 100px;
  }
  .bodywebsite .section-xl-120 {
	padding-top: 120px;
	padding-bottom: 120px;
  }
  .bodywebsite .section-xl-130 {
	padding-top: 130px;
	padding-bottom: 130px;
  }
  .bodywebsite .section-xl-145 {
	padding-top: 145px;
	padding-bottom: 145px;
  }
  .bodywebsite .section-xl-165 {
	padding-top: 165px;
	padding-bottom: 165px;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .section-xxl-15 {
	padding-top: 15px;
	padding-bottom: 15px;
  }
  .bodywebsite .section-xxl-30 {
	padding-top: 30px;
	padding-bottom: 30px;
  }
  .bodywebsite .section-xxl-35 {
	padding-top: 35px;
	padding-bottom: 35px;
  }
  .bodywebsite .section-xxl-40 {
	padding-top: 40px;
	padding-bottom: 40px;
  }
  .bodywebsite .section-xxl-45 {
	padding-top: 45px;
	padding-bottom: 45px;
  }
  .bodywebsite .section-xxl-50 {
	padding-top: 50px;
	padding-bottom: 50px;
  }
  .bodywebsite .section-xxl-60 {
	padding-top: 60px;
	padding-bottom: 60px;
  }
  .bodywebsite .section-xxl-66 {
	padding-top: 66px;
	padding-bottom: 66px;
  }
  .bodywebsite .section-xxl-75 {
	padding-top: 75px;
	padding-bottom: 75px;
  }
  .bodywebsite .section-xxl-90 {
	padding-top: 90px;
	padding-bottom: 90px;
  }
  .bodywebsite .section-xxl-100 {
	padding-top: 100px;
	padding-bottom: 100px;
  }
  .bodywebsite .section-xxl-120 {
	padding-top: 120px;
	padding-bottom: 120px;
  }
  .bodywebsite .section-xxl-130 {
	padding-top: 130px;
	padding-bottom: 130px;
  }
  .bodywebsite .section-xxl-145 {
	padding-top: 145px;
	padding-bottom: 145px;
  }
  .bodywebsite .section-xxl-165 {
	padding-top: 165px;
	padding-bottom: 165px;
  }
}
.bodywebsite .section-top-15 {
  padding-top: 15px;
}
.bodywebsite .section-top-30 {
  padding-top: 30px;
}
.bodywebsite .section-top-35 {
  padding-top: 35px;
}
.bodywebsite .section-top-40 {
  padding-top: 40px;
}
.bodywebsite .section-top-45 {
  padding-top: 45px;
}
.bodywebsite .section-top-50 {
  padding-top: 50px;
}
.bodywebsite .section-top-60 {
  padding-top: 60px;
}
.bodywebsite .section-top-66 {
  padding-top: 66px;
}
.bodywebsite .section-top-75 {
  padding-top: 75px;
}
.bodywebsite .section-top-90 {
  padding-top: 90px;
}
.bodywebsite .section-top-100 {
  padding-top: 100px;
}
.bodywebsite .section-top-120 {
  padding-top: 120px;
}
.bodywebsite .section-top-130 {
  padding-top: 130px;
}
.bodywebsite .section-top-145 {
  padding-top: 145px;
}
.bodywebsite .section-top-165 {
  padding-top: 165px;
}
@media (min-width: 576px) {
  .bodywebsite .section-sm-top-15 {
	padding-top: 15px;
  }
  .bodywebsite .section-sm-top-30 {
	padding-top: 30px;
  }
  .bodywebsite .section-sm-top-35 {
	padding-top: 35px;
  }
  .bodywebsite .section-sm-top-40 {
	padding-top: 40px;
  }
  .bodywebsite .section-sm-top-45 {
	padding-top: 45px;
  }
  .bodywebsite .section-sm-top-50 {
	padding-top: 50px;
  }
  .bodywebsite .section-sm-top-60 {
	padding-top: 60px;
  }
  .bodywebsite .section-sm-top-66 {
	padding-top: 66px;
  }
  .bodywebsite .section-sm-top-75 {
	padding-top: 75px;
  }
  .bodywebsite .section-sm-top-90 {
	padding-top: 90px;
  }
  .bodywebsite .section-sm-top-100 {
	padding-top: 100px;
  }
  .bodywebsite .section-sm-top-120 {
	padding-top: 120px;
  }
  .bodywebsite .section-sm-top-130 {
	padding-top: 130px;
  }
  .bodywebsite .section-sm-top-145 {
	padding-top: 145px;
  }
  .bodywebsite .section-sm-top-165 {
	padding-top: 165px;
  }
}
@media (min-width: 768px) {
  .bodywebsite .section-md-top-15 {
	padding-top: 15px;
  }
  .bodywebsite .section-md-top-30 {
	padding-top: 30px;
  }
  .bodywebsite .section-md-top-35 {
	padding-top: 35px;
  }
  .bodywebsite .section-md-top-40 {
	padding-top: 40px;
  }
  .bodywebsite .section-md-top-45 {
	padding-top: 45px;
  }
  .bodywebsite .section-md-top-50 {
	padding-top: 50px;
  }
  .bodywebsite .section-md-top-60 {
	padding-top: 60px;
  }
  .bodywebsite .section-md-top-66 {
	padding-top: 66px;
  }
  .bodywebsite .section-md-top-75 {
	padding-top: 75px;
  }
  .bodywebsite .section-md-top-90 {
	padding-top: 90px;
  }
  .bodywebsite .section-md-top-100 {
	padding-top: 100px;
  }
  .bodywebsite .section-md-top-120 {
	padding-top: 120px;
  }
  .bodywebsite .section-md-top-130 {
	padding-top: 130px;
  }
  .bodywebsite .section-md-top-145 {
	padding-top: 145px;
  }
  .bodywebsite .section-md-top-165 {
	padding-top: 165px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .section-lg-top-15 {
	padding-top: 15px;
  }
  .bodywebsite .section-lg-top-30 {
	padding-top: 30px;
  }
  .bodywebsite .section-lg-top-35 {
	padding-top: 35px;
  }
  .bodywebsite .section-lg-top-40 {
	padding-top: 40px;
  }
  .bodywebsite .section-lg-top-45 {
	padding-top: 45px;
  }
  .bodywebsite .section-lg-top-50 {
	padding-top: 50px;
  }
  .bodywebsite .section-lg-top-60 {
	padding-top: 60px;
  }
  .bodywebsite .section-lg-top-66 {
	padding-top: 66px;
  }
  .bodywebsite .section-lg-top-75 {
	padding-top: 75px;
  }
  .bodywebsite .section-lg-top-90 {
	padding-top: 90px;
  }
  .bodywebsite .section-lg-top-100 {
	padding-top: 100px;
  }
  .bodywebsite .section-lg-top-120 {
	padding-top: 120px;
  }
  .bodywebsite .section-lg-top-130 {
	padding-top: 130px;
  }
  .bodywebsite .section-lg-top-145 {
	padding-top: 145px;
  }
  .bodywebsite .section-lg-top-165 {
	padding-top: 165px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .section-xl-top-15 {
	padding-top: 15px;
  }
  .bodywebsite .section-xl-top-30 {
	padding-top: 30px;
  }
  .bodywebsite .section-xl-top-35 {
	padding-top: 35px;
  }
  .bodywebsite .section-xl-top-40 {
	padding-top: 40px;
  }
  .bodywebsite .section-xl-top-45 {
	padding-top: 45px;
  }
  .bodywebsite .section-xl-top-50 {
	padding-top: 50px;
  }
  .bodywebsite .section-xl-top-60 {
	padding-top: 60px;
  }
  .bodywebsite .section-xl-top-66 {
	padding-top: 66px;
  }
  .bodywebsite .section-xl-top-75 {
	padding-top: 75px;
  }
  .bodywebsite .section-xl-top-90 {
	padding-top: 90px;
  }
  .bodywebsite .section-xl-top-100 {
	padding-top: 100px;
  }
  .bodywebsite .section-xl-top-120 {
	padding-top: 120px;
  }
  .bodywebsite .section-xl-top-130 {
	padding-top: 130px;
  }
  .bodywebsite .section-xl-top-145 {
	padding-top: 145px;
  }
  .bodywebsite .section-xl-top-165 {
	padding-top: 165px;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .section-xxl-top-15 {
	padding-top: 15px;
  }
  .bodywebsite .section-xxl-top-30 {
	padding-top: 30px;
  }
  .bodywebsite .section-xxl-top-35 {
	padding-top: 35px;
  }
  .bodywebsite .section-xxl-top-40 {
	padding-top: 40px;
  }
  .bodywebsite .section-xxl-top-45 {
	padding-top: 45px;
  }
  .bodywebsite .section-xxl-top-50 {
	padding-top: 50px;
  }
  .bodywebsite .section-xxl-top-60 {
	padding-top: 60px;
  }
  .bodywebsite .section-xxl-top-66 {
	padding-top: 66px;
  }
  .bodywebsite .section-xxl-top-75 {
	padding-top: 75px;
  }
  .bodywebsite .section-xxl-top-90 {
	padding-top: 90px;
  }
  .bodywebsite .section-xxl-top-100 {
	padding-top: 100px;
  }
  .bodywebsite .section-xxl-top-120 {
	padding-top: 120px;
  }
  .bodywebsite .section-xxl-top-130 {
	padding-top: 130px;
  }
  .bodywebsite .section-xxl-top-145 {
	padding-top: 145px;
  }
  .bodywebsite .section-xxl-top-165 {
	padding-top: 165px;
  }
}
.bodywebsite .section-bottom-15 {
  padding-bottom: 15px;
}
.bodywebsite .section-bottom-30 {
  padding-bottom: 30px;
}
.bodywebsite .section-bottom-35 {
  padding-bottom: 35px;
}
.bodywebsite .section-bottom-40 {
  padding-bottom: 40px;
}
.bodywebsite .section-bottom-45 {
  padding-bottom: 45px;
}
.bodywebsite .section-bottom-50 {
  padding-bottom: 50px;
}
.bodywebsite .section-bottom-60 {
  padding-bottom: 60px;
}
.bodywebsite .section-bottom-66 {
  padding-bottom: 66px;
}
.bodywebsite .section-bottom-75 {
  padding-bottom: 75px;
}
.bodywebsite .section-bottom-90 {
  padding-bottom: 90px;
}
.bodywebsite .section-bottom-100 {
  padding-bottom: 100px;
}
.bodywebsite .section-bottom-120 {
  padding-bottom: 120px;
}
.bodywebsite .section-bottom-130 {
  padding-bottom: 130px;
}
.bodywebsite .section-bottom-145 {
  padding-bottom: 145px;
}
.bodywebsite .section-bottom-165 {
  padding-bottom: 165px;
}
@media (min-width: 576px) {
  .bodywebsite .section-sm-bottom-15 {
	padding-bottom: 15px;
  }
  .bodywebsite .section-sm-bottom-30 {
	padding-bottom: 30px;
  }
  .bodywebsite .section-sm-bottom-35 {
	padding-bottom: 35px;
  }
  .bodywebsite .section-sm-bottom-40 {
	padding-bottom: 40px;
  }
  .bodywebsite .section-sm-bottom-45 {
	padding-bottom: 45px;
  }
  .bodywebsite .section-sm-bottom-50 {
	padding-bottom: 50px;
  }
  .bodywebsite .section-sm-bottom-60 {
	padding-bottom: 60px;
  }
  .bodywebsite .section-sm-bottom-66 {
	padding-bottom: 66px;
  }
  .bodywebsite .section-sm-bottom-75 {
	padding-bottom: 75px;
  }
  .bodywebsite .section-sm-bottom-90 {
	padding-bottom: 90px;
  }
  .bodywebsite .section-sm-bottom-100 {
	padding-bottom: 100px;
  }
  .bodywebsite .section-sm-bottom-120 {
	padding-bottom: 120px;
  }
  .bodywebsite .section-sm-bottom-130 {
	padding-bottom: 130px;
  }
  .bodywebsite .section-sm-bottom-145 {
	padding-bottom: 145px;
  }
  .bodywebsite .section-sm-bottom-165 {
	padding-bottom: 165px;
  }
}
@media (min-width: 768px) {
  .bodywebsite .section-md-bottom-15 {
	padding-bottom: 15px;
  }
  .bodywebsite .section-md-bottom-30 {
	padding-bottom: 30px;
  }
  .bodywebsite .section-md-bottom-35 {
	padding-bottom: 35px;
  }
  .bodywebsite .section-md-bottom-40 {
	padding-bottom: 40px;
  }
  .bodywebsite .section-md-bottom-45 {
	padding-bottom: 45px;
  }
  .bodywebsite .section-md-bottom-50 {
	padding-bottom: 50px;
  }
  .bodywebsite .section-md-bottom-60 {
	padding-bottom: 60px;
  }
  .bodywebsite .section-md-bottom-66 {
	padding-bottom: 66px;
  }
  .bodywebsite .section-md-bottom-75 {
	padding-bottom: 75px;
  }
  .bodywebsite .section-md-bottom-90 {
	padding-bottom: 90px;
  }
  .bodywebsite .section-md-bottom-100 {
	padding-bottom: 100px;
  }
  .bodywebsite .section-md-bottom-120 {
	padding-bottom: 120px;
  }
  .bodywebsite .section-md-bottom-130 {
	padding-bottom: 130px;
  }
  .bodywebsite .section-md-bottom-145 {
	padding-bottom: 145px;
  }
  .bodywebsite .section-md-bottom-165 {
	padding-bottom: 165px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .section-lg-bottom-15 {
	padding-bottom: 15px;
  }
  .bodywebsite .section-lg-bottom-30 {
	padding-bottom: 30px;
  }
  .bodywebsite .section-lg-bottom-35 {
	padding-bottom: 35px;
  }
  .bodywebsite .section-lg-bottom-40 {
	padding-bottom: 40px;
  }
  .bodywebsite .section-lg-bottom-45 {
	padding-bottom: 45px;
  }
  .bodywebsite .section-lg-bottom-50 {
	padding-bottom: 50px;
  }
  .bodywebsite .section-lg-bottom-60 {
	padding-bottom: 60px;
  }
  .bodywebsite .section-lg-bottom-66 {
	padding-bottom: 66px;
  }
  .bodywebsite .section-lg-bottom-75 {
	padding-bottom: 75px;
  }
  .bodywebsite .section-lg-bottom-90 {
	padding-bottom: 90px;
  }
  .bodywebsite .section-lg-bottom-100 {
	padding-bottom: 100px;
  }
  .bodywebsite .section-lg-bottom-120 {
	padding-bottom: 120px;
  }
  .bodywebsite .section-lg-bottom-130 {
	padding-bottom: 130px;
  }
  .bodywebsite .section-lg-bottom-145 {
	padding-bottom: 145px;
  }
  .bodywebsite .section-lg-bottom-165 {
	padding-bottom: 165px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .section-xl-bottom-15 {
	padding-bottom: 15px;
  }
  .bodywebsite .section-xl-bottom-30 {
	padding-bottom: 30px;
  }
  .bodywebsite .section-xl-bottom-35 {
	padding-bottom: 35px;
  }
  .bodywebsite .section-xl-bottom-40 {
	padding-bottom: 40px;
  }
  .bodywebsite .section-xl-bottom-45 {
	padding-bottom: 45px;
  }
  .bodywebsite .section-xl-bottom-50 {
	padding-bottom: 50px;
  }
  .bodywebsite .section-xl-bottom-60 {
	padding-bottom: 60px;
  }
  .bodywebsite .section-xl-bottom-66 {
	padding-bottom: 66px;
  }
  .bodywebsite .section-xl-bottom-75 {
	padding-bottom: 75px;
  }
  .bodywebsite .section-xl-bottom-90 {
	padding-bottom: 90px;
  }
  .bodywebsite .section-xl-bottom-100 {
	padding-bottom: 100px;
  }
  .bodywebsite .section-xl-bottom-120 {
	padding-bottom: 120px;
  }
  .bodywebsite .section-xl-bottom-130 {
	padding-bottom: 130px;
  }
  .bodywebsite .section-xl-bottom-145 {
	padding-bottom: 145px;
  }
  .bodywebsite .section-xl-bottom-165 {
	padding-bottom: 165px;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .section-xxl-bottom-15 {
	padding-bottom: 15px;
  }
  .bodywebsite .section-xxl-bottom-30 {
	padding-bottom: 30px;
  }
  .bodywebsite .section-xxl-bottom-35 {
	padding-bottom: 35px;
  }
  .bodywebsite .section-xxl-bottom-40 {
	padding-bottom: 40px;
  }
  .bodywebsite .section-xxl-bottom-45 {
	padding-bottom: 45px;
  }
  .bodywebsite .section-xxl-bottom-50 {
	padding-bottom: 50px;
  }
  .bodywebsite .section-xxl-bottom-60 {
	padding-bottom: 60px;
  }
  .bodywebsite .section-xxl-bottom-66 {
	padding-bottom: 66px;
  }
  .bodywebsite .section-xxl-bottom-75 {
	padding-bottom: 75px;
  }
  .bodywebsite .section-xxl-bottom-90 {
	padding-bottom: 90px;
  }
  .bodywebsite .section-xxl-bottom-100 {
	padding-bottom: 100px;
  }
  .bodywebsite .section-xxl-bottom-120 {
	padding-bottom: 120px;
  }
  .bodywebsite .section-xxl-bottom-130 {
	padding-bottom: 130px;
  }
  .bodywebsite .section-xxl-bottom-145 {
	padding-bottom: 145px;
  }
  .bodywebsite .section-xxl-bottom-165 {
	padding-bottom: 165px;
  }
}
html .bodywebsite .group {
  -webkit-transform: translateY(-15px);
  transform: translateY(-15px);
  margin-bottom: -15px;
  margin-left: -15px;
}
html .bodywebsite .group > *,
html .bodywebsite .group > *:first-child {
  display: inline-block;
  margin-top: 15px;
  margin-left: 15px;
}
html .bodywebsite .group-xs {
  -webkit-transform: translateY(-5px);
  transform: translateY(-5px);
  margin-bottom: -5px;
  margin-left: -5px;
}
html .bodywebsite .group-xs > *,
html .bodywebsite .group-xs > *:first-child {
  display: inline-block;
  margin-top: 5px;
  margin-left: 5px;
}
html .bodywebsite .group-sm {
  -webkit-transform: translateY(-10px);
  transform: translateY(-10px);
  margin-bottom: -10px;
  margin-left: -10px;
}
html .bodywebsite .group-sm > *,
html .bodywebsite .group-sm > *:first-child {
  display: inline-block;
  margin-top: 10px;
  margin-left: 10px;
}
html .bodywebsite .group-md {
  -webkit-transform: translateY(-15px);
  transform: translateY(-15px);
  margin-bottom: -15px;
  margin-left: -15px;
}
html .bodywebsite .group-md > *,
html .bodywebsite .group-md > *:first-child {
  display: inline-block;
  margin-top: 15px;
  margin-left: 15px;
}
html .bodywebsite .group-lg {
  -webkit-transform: translateY(-20px);
  transform: translateY(-20px);
  margin-bottom: -20px;
  margin-left: -20px;
}
html .bodywebsite .group-lg > *,
html .bodywebsite .group-lg > *:first-child {
  display: inline-block;
  margin-top: 20px;
  margin-left: 20px;
}
html .bodywebsite .group-xl {
  -webkit-transform: translateY(-30px);
  transform: translateY(-30px);
  margin-bottom: -30px;
  margin-left: -30px;
}
html .bodywebsite .group-xl > *,
html .bodywebsite .group-xl > *:first-child {
  display: inline-block;
  margin-top: 30px;
  margin-left: 30px;
}
html .bodywebsite .group-top > *,
html .bodywebsite .group-top > *:first-child {
  vertical-align: top;
}
html .bodywebsite .group-middle > *,
html .bodywebsite .group-middle > *:first-child {
  vertical-align: middle;
}
html .bodywebsite .group-bottom > *,
html .bodywebsite .group-bottom > *:first-child {
  vertical-align: bottom;
}
html .bodywebsite .group-inline > * {
  display: inline;
}
html .bodywebsite .group-inline > *:not(:last-child) {
  margin-right: .25em;
}
html .bodywebsite .group-xl-responsive {
  -webkit-transform: translateY(-18px);
  transform: translateY(-18px);
  margin-bottom: -18px;
  margin-left: -18px;
}
html .bodywebsite .group-xl-responsive > *,
html .bodywebsite .group-xl-responsive > *:first-child {
  display: inline-block;
  margin-top: 18px;
  margin-left: 18px;
}
@media (min-width: 768px) {
  html .bodywebsite .group-xl-responsive {
	-webkit-transform: translateY(-30px);
	transform: translateY(-30px);
	margin-bottom: -30px;
	margin-left: -30px;
  }
  html .bodywebsite .group-xl-responsive > *,
  html .bodywebsite .group-xl-responsive > *:first-child {
	display: inline-block;
	margin-top: 30px;
	margin-left: 30px;
  }
}
.bodywebsite .group-flex-center {
  display: -webkit-inline-box;
  display: -webkit-inline-flex;
  display: -ms-inline-flexbox;
  display: inline-flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-flex-wrap: wrap;
  -ms-flex-wrap: wrap;
  flex-wrap: wrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: center;
  -ms-flex-pack: center;
  justify-content: center;
}
.bodywebsite .relative {
  position: relative;
}
.bodywebsite .static {
  position: static;
}
.bodywebsite .block-top-level {
  position: relative;
  z-index: 3;
}
.bodywebsite .height-fill {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: column;
  -ms-flex-direction: column;
  flex-direction: column;
  -webkit-align-items: stretch;
  -ms-flex-align: stretch;
  align-items: stretch;
}
.bodywebsite .height-fill > * {
  -webkit-flex-grow: 1;
  -ms-flex-positive: 1;
  flex-grow: 1;
}
.bodywebsite .centered {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-justify-content: center;
  -ms-flex-pack: center;
  justify-content: center;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
}
.bodywebsite .align-bottom {
  -webkit-align-self: flex-end;
  -ms-flex-item-align: end;
  align-self: flex-end;
}
.bodywebsite .block-centered {
  margin-left: auto;
  margin-right: auto;
}
@media (max-width: 767px) {
  .bodywebsite .responsive-centered {
	margin-left: auto;
	margin-right: auto;
  }
}
.bodywebsite .overflow-hidden {
  overflow: hidden;
}
.bodywebsite .page .white-space-normal {
  white-space: normal;
}
.bodywebsite * + h1,
.bodywebsite * + .h1 {
  margin-top: 20px;
}
@media (min-width: 768px) {
  .bodywebsite * + h1,
  .bodywebsite * + .h1 {
	margin-top: 27px;
  }
}
@media (min-width: 992px) {
  .bodywebsite * + h1,
  .bodywebsite * + .h1 {
	margin-top: 34px;
  }
}
.bodywebsite * + h2,
.bodywebsite * + .h2 {
  margin-top: 25px;
}
.bodywebsite * + h3,
.bodywebsite * + .h3 {
  margin-top: 17px;
}
.bodywebsite * + h4,
.bodywebsite * + .h4 {
  margin-top: 15px;
}
.bodywebsite h1 + *,
.bodywebsite .h1 + * {
  margin-top: 25px;
}
.bodywebsite h2 + *,
.bodywebsite .h2 + * {
  margin-top: 18px;
}
.bodywebsite h3 + *,
.bodywebsite .h3 + * {
  margin-top: 19px;
}
.bodywebsite h4 + *,
.bodywebsite .h4 + * {
  margin-top: 18px;
}
.bodywebsite * + p,
.bodywebsite * + p {
  margin-top: 14px;
}
.bodywebsite * + .text-big {
  margin-top: 20px;
}
.bodywebsite hr + * {
  margin-top: 18px;
}
@media (min-width: 1200px) {
  .bodywebsite hr + * {
	margin-top: 26px;
  }
}
.bodywebsite * + .big {
  margin-top: 6px;
}
.bodywebsite * + .text-large {
  margin-top: 10px;
}
.bodywebsite * + .text-bigger {
  margin-top: 28px;
}
.bodywebsite * + .btn {
  margin-top: 30px;
}
@media (min-width: 1200px) {
  .bodywebsite * + .btn {
	margin-top: 44px;
  }
}
.bodywebsite * + .link {
  margin-top: 18px;
}
.bodywebsite * + .contact-info {
  margin-top: 16px;
}
.bodywebsite * + .list-inline {
  margin-top: 32px;
}
.bodywebsite * + .list-terms {
  margin-top: 42px;
}
@media (min-width: 1200px) {
  .bodywebsite * + .list-terms {
	margin-top: 62px;
  }
}
.bodywebsite * + .list-marked,
.bodywebsite * + .list-ordered {
  margin-top: 22px;
}
.bodywebsite * + .link-wrap {
  margin-top: 8px;
}
.bodywebsite * + .link-iconed {
  margin-top: 2px;
}
.bodywebsite .contact-info {
  vertical-align: baseline;
}
.bodywebsite .contact-info a {
  display: inline-block;
}
.bodywebsite .contact-info dl dt,
.bodywebsite .contact-info dl dd {
  display: inline-block;
}
.bodywebsite .contact-info dl dt:after {
  content: ':';
  display: inline-block;
  text-align: center;
}
.bodywebsite .contact-info .dl-inline dt {
  padding-right: 0;
}
.bodywebsite .grid-system p {
  color: #00030a;
}
@media (max-width: 1199px) {
  .bodywebsite .grid-system p {
	width: 100%;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
  }
}
.bodywebsite .object-inline,
.bodywebsite .object-inline-baseline {
  white-space: nowrap;
}
.bodywebsite .object-inline > * + *,
.bodywebsite .object-inline-baseline > * + * {
  margin-top: 0;
  margin-left: 5px;
}
.bodywebsite .object-inline {
  vertical-align: middle;
}
.bodywebsite .object-inline > * {
  display: inline-block;
  vertical-align: middle;
}
.bodywebsite .object-inline-baseline {
  vertical-align: baseline;
}
.bodywebsite .object-inline-baseline > * {
  display: inline-block;
  vertical-align: baseline;
}
.bodywebsite .row-no-gutter {
  margin-left: 0;
  margin-right: 0;
}
.bodywebsite .row-no-gutter [class*='col'] {
  padding: 0;
}
.bodywebsite .text-width-1 {
  max-width: 400px;
}
@media (min-width: 992px) {
  .bodywebsite .text-width-1 {
	max-width: 310px;
  }
}
.bodywebsite .min-width-1 {
  min-width: 100%;
}
@media (min-width: 576px) {
  .bodywebsite .min-width-1 {
	min-width: 270px;
  }
}
.bodywebsite .img-shadow {
  box-shadow: -3px 2px 4px 0px rgba(0, 0, 0, 0.58);
}
@media (min-width: 768px) {
  .bodywebsite .img-shadow {
	box-shadow: -5px 4px 8px 0px rgba(0, 0, 0, 0.58);
  }
}
.bodywebsite .box {
  box-shadow: 0 5px 23px 0 rgba(0, 0, 0, 0.3);
  padding: 50px 30px;
  margin-top: 10px;
  margin-bottom: 10px;
}
@media (min-width: 992px) {
  .bodywebsite .box {
	padding: 55px 30px 65px 44px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .box {
	padding: 54px 40px 85px 54px;
  }
}
.bodywebsite .box-xs {
  padding: 38px 20px;
}
.bodywebsite .page .box-list-xs {
  box-shadow: 0 5px 13px 0 rgba(0, 0, 0, 0.2);
}
.bodywebsite .page .box-list-xs .box-xs + .box-xs {
  border-top: 1px solid #1c2e3f;
}
@media (min-width: 768px) {
  .bodywebsite .page .box-list-xs {
	max-width: 170px;
  }
}
.bodywebsite .group-item {
  width: 100%;
  max-width: 220px;
  margin-left: auto;
  margin-right: auto;
}
@media (min-width: 576px) {
  .bodywebsite .group-item {
	max-width: 300px;
  }
}
@media (min-width: 768px) {
  .bodywebsite .group-item {
	min-width: 40%;
	max-width: 0;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .group-item {
	min-width: 272px;
  }
  .bodywebsite .group-item-sm {
	min-width: 195px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .border-modern {
	position: relative;
  }
  .bodywebsite .border-modern .border-modern-item-1,
  .bodywebsite .border-modern .border-modern-item-2,
  .bodywebsite .border-modern .border-modern-item-3,
  .bodywebsite .border-modern .border-modern-item-4 {
	position: absolute;
	width: 45px;
	height: 45px;
	border-left: 3px solid <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
	border-top: 3px solid <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  }
  .bodywebsite .border-modern .border-modern-item-1,
  .bodywebsite .border-modern .border-modern-item-2 {
	top: -27px;
  }
  .bodywebsite .border-modern .border-modern-item-3,
  .bodywebsite .border-modern .border-modern-item-4 {
	bottom: -68px;
  }
  .bodywebsite .border-modern .border-modern-item-1,
  .bodywebsite .border-modern .border-modern-item-3 {
	left: 0px;
  }
  .bodywebsite .border-modern .border-modern-item-2,
  .bodywebsite .border-modern .border-modern-item-4 {
	right: 0px;
  }
  .bodywebsite .border-modern .border-modern-item-2 {
	transform: rotate(90deg);
  }
  .bodywebsite .border-modern .border-modern-item-3 {
	transform: rotate(-90deg);
  }
  .bodywebsite .border-modern .border-modern-item-4 {
	transform: rotate(180deg);
  }
}
.bodywebsite .pagination-custom {
  display: inline-block;
  position: relative;
  transform: translateY(-4px);
  margin-bottom: -4px;
}
.bodywebsite .pagination-custom > * {
  margin-top: 4px;
}
.bodywebsite .pagination-custom > *:not(:last-child) {
  margin-right: 4px;
}
.bodywebsite .pagination-custom .page-item {
  display: inline-block;
  line-height: 1;
}
.bodywebsite .pagination-custom .page-item:first-child .page-link,
.bodywebsite .pagination-custom .page-item:last-child .page-link {
  padding-left: 25px;
  padding-right: 25px;
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  border-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .pagination-custom .page-item .page-link {
  display: block;
  width: auto;
  min-width: 52px;
  height: 52px;
  padding: 10px 20px;
  border: 2px solid;
  border-radius: 0;
  font: 700 14px/14px "Roboto", Helvetica, Arial, sans-serif;
  text-transform: uppercase;
  vertical-align: middle;
}
.bodywebsite .pagination-custom .page-item .page-link:after {
  content: '';
  height: 108%;
  width: 0;
  display: inline-block;
  vertical-align: middle;
}
.bodywebsite .pagination-custom .page-item .page-link,
.bodywebsite .pagination-custom .page-item .page-link:active,
.bodywebsite .pagination-custom .page-item .page-link:focus {
  color: #000;
  background: transparent;
  border-color: #cdcdcd;
}
.bodywebsite .pagination-custom .page-item .page-link:hover {
  color: #fff;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  border-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .pagination-custom .page-item.disabled,
.bodywebsite .pagination-custom .page-item.active {
  pointer-events: none;
}
.bodywebsite .pagination-custom .page-item.active .page-link {
  color: #fff;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  border-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .pagination-custom .page-item.disabled .page-link {
  color: #f9f9f9;
  background: #cdcdcd;
  border-color: #cdcdcd;
}
.bodywebsite .label-custom {
  padding: .35em .3em .25em;
  font-weight: 400;
  font-size: 70%;
  text-transform: uppercase;
}
.bodywebsite .mac .label-custom {
  padding-top: .4em;
}
.bodywebsite .label-custom.label-danger {
  color: #fff;
  background: #fe4a21;
  box-shadow: inset 0 8px 12px rgba(0, 0, 0, 0.25);
}
.bodywebsite .label-custom.label-info {
  color: #fff;
  background: #3e9cf6;
  box-shadow: inset 0 8px 12px rgba(0, 0, 0, 0.25);
}
.bodywebsite blockquote {
  font: inherit;
  padding: 0;
  margin: 0;
  border: 0;
}
.bodywebsite blockquote q:before,
.bodywebsite blockquote q:after {
  content: none;
}
.bodywebsite blockquote cite {
  font-style: normal;
}
.bodywebsite .quote-default {
  position: relative;
  padding: 43px 0 43px 6px;
  color: #00030a;
}
.bodywebsite .quote-default svg {
  fill: #ddd;
}
.bodywebsite .quote-default .quote-open,
.bodywebsite .quote-default .quote-close {
  position: absolute;
  left: 30px;
}
.bodywebsite .quote-default .quote-open {
  top: 0;
}
.bodywebsite .quote-default .quote-close {
  bottom: 0;
}
.bodywebsite .quote-bordered {
  padding-top: 14px;
}
.bodywebsite .quote-bordered .quote-body {
  position: relative;
  padding-bottom: 10px;
}
.bodywebsite .quote-bordered h6 {
  font-size: 18px;
}
.bodywebsite .quote-bordered .quote-body-inner {
  position: relative;
  padding: 37px 22px 29px 34px;
  border-style: solid;
  border-width: 1px 1px 0 1px;
  border-color: #e5e7e9;
}
.bodywebsite .quote-bordered .quote-body-inner:before,
.bodywebsite .quote-bordered .quote-body-inner:after {
  content: '';
  position: absolute;
  bottom: -10px;
  height: 10px;
  border-style: solid;
  border-color: #e5e7e9;
  background-color: transparent;
}
.bodywebsite .quote-bordered .quote-body-inner:before {
  left: 10px;
  width: 46px;
  border-width: 1px 1px 0 0;
  transform: skew(45deg);
  transform-origin: 100% 100%;
}
.bodywebsite .quote-bordered .quote-body-inner:after {
  right: 10px;
  width: calc(34%);
  border-width: 1px 0 0 1px;
  transform: skew(-45deg);
  transform-origin: 0 100%;
}
.bodywebsite .quote-bordered .quote-open {
  position: absolute;
  top: -10px;
  left: 34px;
  z-index: 2;
}
.bodywebsite .quote-bordered .quote-open > svg {
  fill: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .quote-bordered .quote-footer {
  padding-left: 25px;
}
.bodywebsite .quote-bordered cite {
  font-size: 17px;
  font-weight: 900;
  line-height: 21px;
  color: #fff;
}
.bodywebsite .quote-bordered * + .quote-footer {
  margin-top: 9px;
}
.bodywebsite .quote-bordered cite + p {
  margin-top: 0;
}
.bodywebsite .quote-bordered-inverse cite,
.bodywebsite .quote-bordered-inverse q {
  color: #000;
}
.bodywebsite .quote-minimal-bordered {
  position: relative;
  padding: 16px 0 26px;
  text-align: center;
}
.bodywebsite .quote-minimal-bordered q {
  font: 400 20px "Roboto", Helvetica, Arial, sans-serif;
  font-style: italic;
  color: #464a4d;
}
.bodywebsite .quote-minimal-bordered q:before,
.bodywebsite .quote-minimal-bordered q:after {
  content: '"';
}
.bodywebsite .quote-minimal-bordered:before,
.bodywebsite .quote-minimal-bordered:after {
  content: '';
  position: absolute;
  left: 50%;
  width: 270px;
  height: 1px;
  transform: translateX(-50%);
  background: -moz-linear-gradient(left, rgba(255, 255, 255, 0) 0%, #dedede 50%, rgba(0, 0, 0, 0) 100%);
  background: -webkit-linear-gradient(left, rgba(255, 255, 255, 0) 0%, #dedede 50%, rgba(0, 0, 0, 0) 100%);
  background: linear-gradient(to right, rgba(255, 255, 255, 0) 0%, #dedede 50%, rgba(0, 0, 0, 0) 100%);
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#00ffffff', endColorstr='#00000000', GradientType=1);
}
.bodywebsite .quote-minimal-bordered:before {
  top: 0;
}
.bodywebsite .quote-minimal-bordered:after {
  bottom: 0;
}
@media (min-width: 768px) {
  .bodywebsite .quote-minimal-bordered q {
	font-size: 24px;
	line-height: 1.55;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .quote-minimal-bordered q {
	font-size: 30px;
  }
}
.bodywebsite .quote-minimal q {
  font-size: 18px;
  font-weight: 300;
  font-style: italic;
  line-height: 1.2;
  color: #000;
}
.bodywebsite .quote-minimal cite {
  font: 700 15px "Roboto", Helvetica, Arial, sans-serif;
  line-height: 1.1;
  color: #000;
}
.bodywebsite .quote-minimal .caption {
  color: #9f9f9f;
}
.bodywebsite .quote-minimal.quote-minimal-inverse q {
  color: #fff;
}
.bodywebsite .quote-minimal.quote-minimal-inverse cite {
  color: #fff;
}
.bodywebsite .quote-minimal.quote-minimal-inverse .caption {
  color: rgba(255, 255, 255, 0.5);
}
.bodywebsite .quote-minimal * + .caption {
  margin-top: 0;
}
.bodywebsite .quote-minimal * + .quote-meta {
  margin-top: 20px;
}
.bodywebsite .quote-strict q,
.bodywebsite .quote-strict cite {
  color: #000;
}
.bodywebsite .quote-strict q {
  font-size: 19px;
  font-weight: 300;
  font-style: italic;
  line-height: 28px;
}
.bodywebsite .quote-strict cite {
  display: block;
  font: 700 16px/21px "Roboto", Helvetica, Arial, sans-serif;
  text-transform: uppercase;
}
.bodywebsite .quote-strict * + cite {
  margin-top: 20px;
}
.bodywebsite .quote-strict.quote-strict-inverse q,
.bodywebsite .quote-strict.quote-strict-inverse cite {
  color: #fff;
}
.bodywebsite .quote-vertical {
  max-width: 360px;
  margin-left: auto;
  margin-right: auto;
  text-align: center;
}
.bodywebsite .quote-vertical q {
  font-size: 16px;
  line-height: 1.57895;
  font-weight: 100;
  color: rgba(0, 0, 0, 0.5);
}
.bodywebsite .quote-vertical cite {
  display: block;
  color: #000;
  font: 700 14px/18px "Roboto", Helvetica, Arial, sans-serif;
}
.bodywebsite .quote-vertical .quote-open > svg {
  fill: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .quote-vertical .quote-image,
.bodywebsite .quote-vertical .quote-image > img {
  border-radius: 600px;
}
.bodywebsite .quote-vertical .quote-image > img {
  width: auto;
}
.bodywebsite .quote-vertical * + cite {
  margin-top: 16px;
}
.bodywebsite .quote-vertical * + .caption {
  margin-top: 0;
}
.bodywebsite .quote-vertical * + .quote-text {
  margin-top: 25px;
}
.bodywebsite .quote-vertical * + .quote-meta {
  margin-top: 25px;
}
.bodywebsite .quote-vertical.quote-vertical-inverse q,
.bodywebsite .quote-vertical.quote-vertical-inverse cite {
  color: #fff;
}
.bodywebsite .quote-vertical.quote-vertical-inverse .cite {
  color: rgba(255, 255, 255, 0.5);
}
.bodywebsite .quote-vertical.quote-vertical-inverse .quote-open > svg {
  fill: #fff;
}
.bodywebsite .quote-review cite {
  font: 700 14px/18px "Roboto", Helvetica, Arial, sans-serif;
  text-transform: uppercase;
  letter-spacing: -0.025em;
  color: #000;
}
.bodywebsite .quote-review .quote-header {
  position: relative;
  transform: translateY(-2px);
  margin-bottom: -2px;
}
.bodywebsite .quote-review .quote-header > * {
  margin-top: 2px;
}
.bodywebsite .quote-review .quote-header > *:not(:last-child) {
  margin-right: 10px;
}
.bodywebsite .quote-review .quote-header > * {
  display: inline-block;
  vertical-align: middle;
}
.bodywebsite .quote-review * + .quote-body {
  margin-top: 10px;
}
.bodywebsite * + .quote-review {
  margin-top: 35px;
}
@media (min-width: 768px) {
  .bodywebsite .quote-minimal q {
	font-size: 22px;
  }
  .bodywebsite .quote-minimal cite {
	font-size: 19px;
  }
  .bodywebsite .quote-minimal * + .quote-meta {
	margin-top: 37px;
  }
  .bodywebsite * + .quote-review {
	margin-top: 45px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .quote-minimal q {
	font-size: 24px;
  }
  .bodywebsite .quote-vertical q {
	font-size: 19px;
  }
}
.bodywebsite .quote-left .divider-fullwidth {
  margin-top: 20px;
  background: #bcd;
}
.bodywebsite .quote-left .quote-name {
  font-size: 18px;
  font-weight: 500;
  color: #fff;
}
@media (min-width: 992px) {
  .bodywebsite .quote-left .quote-name {
	font-size: 24px;
  }
}
.bodywebsite .quote-left .quote-desc-text {
  font-size: 26px;
  line-height: 1;
  font-style: italic;
  font-weight: 700;
}
@media (min-width: 992px) {
  .bodywebsite .quote-left .quote-desc-text {
	font-size: 36px;
  }
}
.bodywebsite .quote-left .quote-body {
  margin-top: 27px;
  padding-left: 75px;
  position: relative;
  text-align: left;
}
.bodywebsite .quote-left .quote-body:before {
  content: '';
  position: absolute;
  top: 6px;
  left: 0;
  width: 50px;
  height: 36px;
  background: url("medias/image/<?php echo $website->ref; ?>/icon-quote.png") no-repeat top left;
  opacity: .5;
}
.bodywebsite .quote-left .quote-body q {
  color: #fff;
}
.bodywebsite .quote-left .h4 + *,
.bodywebsite .quote-left h5 + * {
  margin-top: 0;
}
.bodywebsite .page .box-text > * {
  display: inline;
  margin: 0 .25em 0 0;
}
.bodywebsite .icon-box-horizontal .unit-left {
  min-width: 48px;
}
.bodywebsite .icon-box-horizontal [class*='icon-md'] {
  margin-top: -2px;
}
.bodywebsite .icon-box-horizontal [class*='icon-lg'] {
  margin-top: -5px;
}
.bodywebsite .icon-box-horizontal * + p {
  margin-top: 9px;
}
.bodywebsite .icon-box-vertical * + p {
  margin-top: 9px;
}
.bodywebsite .icon-box-vertical-sm {
  max-width: 370px;
}
@media (max-width: 767px) {
  .bodywebsite .icon-box-vertical-sm {
	margin-left: auto;
	margin-right: auto;
  }
}
.bodywebsite .icon-box {
  position: relative;
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: column;
  -ms-flex-direction: column;
  flex-direction: column;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: center;
  -ms-flex-pack: center;
  justify-content: center;
  width: 100%;
  padding: 35px 30px;
  text-align: center;
  cursor: default;
}
@media (min-width: 768px) {
  .bodywebsite .icon-box:before,
  .bodywebsite .icon-box:after {
	content: '';
	position: absolute;
	width: 100%;
	height: 100%;
	border: 1px solid #e5e7e9;
	pointer-events: none;
	transition: .33s all ease;
  }
  .bodywebsite .icon-box.icon-box-top-line:before {
	border-width: 1px 0 0 0;
  }
  .bodywebsite .icon-box:before {
	top: 0;
	left: 0;
	border-width: 0 0 0 0;
  }
  .bodywebsite .icon-box:after {
	bottom: 0;
	right: 0;
	border-width: 0 1px 1px 0;
  }
}
.bodywebsite .icon-box .icon:after {
  opacity: 0;
}
.bodywebsite .icon-box .btn:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .icon-box .box-top,
.bodywebsite .icon-box .box-body {
  position: relative;
  will-change: transform;
  transition: .33s all ease;
  -webkit-filter: blur(0);
}
.bodywebsite .icon-box .box-top {
  top: 0;
}
.bodywebsite .icon-box .box-body {
  max-width: 100%;
}
.bodywebsite .icon-box .box-header {
  bottom: 0;
}
.bodywebsite .icon-box .box-icon {
  min-height: 46px;
  display: -webkit-inline-box;
  display: -webkit-inline-flex;
  display: -ms-inline-flexbox;
  display: inline-flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: center;
  -ms-flex-pack: center;
  justify-content: center;
}
.bodywebsite .icon-box * + .box-header {
  margin-top: 10px;
}
.bodywebsite .icon-box * + .box-body {
  margin-top: 22px;
}
.bodywebsite .icon-box .box-body + .btn {
  margin-top: 18px;
}
.bodywebsite .icon-box.hover .box-top,
.bodywebsite .icon-box:hover .box-top {
  -webkit-transform: translateY(-7px);
  transform: translateY(-7px);
}
.bodywebsite .icon-box.hover .btn,
.bodywebsite .icon-box.hover .box-body,
.bodywebsite .icon-box:hover .btn,
.bodywebsite .icon-box:hover .box-body {
  -webkit-transform: translateY(7px);
  transform: translateY(7px);
}
@media (min-width: 992px) {
  .bodywebsite .desktop .icon-box .icon-box-overlay {
	position: absolute;
	top: 0;
	bottom: 0;
	right: 0;
	left: 0;
	opacity: 0;
	background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
	transition: 0.2s ease-in-out;
  }
  .bodywebsite .desktop .icon-box:hover .icon-box-overlay {
	opacity: 1;
	z-index: 5;
	-webkit-transform: scale(1.05);
	transform: scale(1.05);
  }
  .bodywebsite .desktop .icon-box:hover .btn {
	z-index: 6;
  }
  .bodywebsite .desktop .icon-box:hover .box-body,
  .bodywebsite .desktop .icon-box:hover .box-top {
	z-index: 6;
  }
}
@media (min-width: 768px) {
  .bodywebsite .icon-box {
	padding: 67px 37px 61px;
	margin-left: auto;
	margin-right: auto;
  }
}
@media (min-width: 1400px) {
  .bodywebsite .icon-box {
	padding: 67px 110px 61px;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .icon-box {
	padding: 90px 165px 82px;
  }
}
.bodywebsite .list-blocks {
  counter-reset: li;
}
.bodywebsite .list-blocks > li {
  display: block;
}
.bodywebsite .list-blocks .block-list-counter:before {
  position: relative;
  content: counter(li, decimal-leading-zero);
  counter-increment: li;
  font: 700 30px/30px "Roboto", Helvetica, Arial, sans-serif;
  letter-spacing: -0.025em;
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .list-blocks > li + li {
  margin-top: 50px;
}
.bodywebsite .block-image-plate {
  display: block;
  width: 100%;
}
.bodywebsite .block-image-plate .block-header {
  max-width: 400px;
}
.bodywebsite .block-image-plate .block-inner {
  position: relative;
  padding: 45px 30px;
}
.bodywebsite .block-image-plate .block-inner:after {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  z-index: 0;
  background: rgba(0, 0, 0, 0.7);
}
.bodywebsite .block-image-plate .block-inner > * {
  position: relative;
  z-index: 2;
}
.bodywebsite .block-image-plate * + .block-text {
  margin-top: 18px;
}
.bodywebsite .block-image-plate * + .block-body {
  margin-top: 22px;
}
@media (max-width: 767px) {
  .bodywebsite .block-image-plate {
	margin-left: -16px;
	margin-right: -15px;
	width: calc(132%);
  }
}
@media (min-width: 768px) {
  .bodywebsite .block-image-plate .block-header {
	max-width: 340px;
  }
  .bodywebsite .block-image-plate .block-header h3 {
	line-height: 1.2;
  }
  .bodywebsite .block-image-plate .block-inner {
	padding: 60px 12.5% 60px 8.33333%;
  }
  .bodywebsite .block-image-plate .block-body {
	display: -ms-flexbox;
	display: -webkit-flex;
	display: flex;
	-webkit-flex-direction: row;
	-ms-flex-direction: row;
	flex-direction: row;
	-webkit-flex-wrap: nowrap;
	-ms-flex-wrap: nowrap;
	flex-wrap: nowrap;
	-webkit-align-items: flex-start;
	-ms-flex-align: start;
	align-items: flex-start;
	-webkit-justify-content: center;
	-ms-flex-pack: center;
	justify-content: center;
  }
  .bodywebsite .block-image-plate .block-left {
	-webkit-flex-shrink: 0;
	-ms-flex-negative: 0;
	flex-shrink: 0;
	-webkit-flex-basis: 11.11111%;
	-ms-flex-preferred-size: 11.11111%;
	flex-basis: 11.11111%;
	max-width: 11.11111%;
	max-width: 100px;
  }
  .bodywebsite .block-image-plate .block-body {
	-webkit-flex-grow: 1;
	-ms-flex-positive: 1;
	flex-grow: 1;
  }
  .bodywebsite .block-image-plate * + .block-text {
	margin-top: 0;
  }
}
@media (min-width: 992px) {
  .bodywebsite .block-image-plate .block-header {
	max-width: 410px;
  }
  .bodywebsite .block-image-plate .block-inner {
	padding-right: 16%;
	padding-top: 90px;
	padding-bottom: 95px;
  }
}
.bodywebsite .block-vacation {
  position: relative;
  width: 100%;
  padding: 39px 9% 45px;
  border-radius: 4px;
  background: #fff;
  box-shadow: -1px 0px 10px 0px rgba(65, 65, 65, 0.12);
}
.bodywebsite .block-vacation,
.bodywebsite .block-vacation:active,
.bodywebsite .block-vacation:focus,
.bodywebsite .block-vacation:hover {
  color: #9f9f9f;
}
.bodywebsite .block-vacation:hover {
  box-shadow: -3px 5px 12px 0px rgba(65, 65, 65, 0.16);
}
.bodywebsite .block-vacation * + .block-meta {
  margin-top: 14px;
}
@media (min-width: 1200px) {
  .bodywebsite .block-vacation * + .block-meta {
	margin-top: 5px;
  }
}
.bodywebsite .block-shadow {
  position: relative;
  width: 100%;
  padding-top: 30px;
  overflow: hidden;
  border-radius: 0;
  background: #fff;
  box-shadow: 0px 1px 10px 0px rgba(65, 65, 65, 0.12);
  text-align: center;
}
.bodywebsite .block-shadow .block-inner {
  padding: 0 40px;
}
.bodywebsite .block-shadow * + .block-footer {
  margin-top: 32px;
}
.bodywebsite .block-shadow * + .icon-block {
  margin-top: 40px;
}
@media (min-width: 768px) {
  .bodywebsite .block-shadow {
	padding-top: 38px;
  }
  .bodywebsite .block-shadow .block-inner {
	padding: 0 70px;
  }
  .bodywebsite .block-shadow * + .icon-block {
	margin-top: 60px;
  }
}
.bodywebsite .box-counter {
  text-align: center;
}
.bodywebsite .box-counter .box-header {
  text-transform: uppercase;
}
.bodywebsite .box-counter * + .box-header {
  margin-top: 10px;
}
@media (min-width: 768px) and (max-width: 1199px) {
  .bodywebsite .box-counter .box-header {
	font-size: 15px;
  }
}
.bodywebsite .box-counter-inverse .box-header {
  color: rgba(255, 255, 255, 0.2);
}
.bodywebsite .box-counter-inverse .counter {
  color: #dedede;
}
.bodywebsite .box-counter-inverse-lighter .box-header {
  color: rgba(255, 255, 255, 0.35);
}
.bodywebsite .box-counter-inverse-lighter .counter {
  color: #dedede;
}
.bodywebsite .box-container-small {
  display: inline-block;
  width: 100%;
  max-width: 280px;
}
.bodywebsite .post-single .post-footer {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-flex-wrap: wrap;
  -ms-flex-wrap: wrap;
  flex-wrap: wrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: space-between;
  -ms-flex-pack: justify;
  justify-content: space-between;
  position: relative;
  transform: translateY(-10px);
  margin-bottom: -10px;
}
.bodywebsite .post-single .post-footer > * {
  margin-top: 10px;
}
.bodywebsite .post-single .post-footer > *:not(:last-child) {
  margin-right: 20px;
}
.bodywebsite .post-single * + .post-header {
  margin-top: 15px;
}
.bodywebsite .post-single * + .post-meta {
  margin-top: 20px;
}
.bodywebsite .post-single * + .post-body {
  margin-top: 20px;
}
.bodywebsite .post-single * + .post-footer {
  margin-top: 42px;
}
.bodywebsite .post-single + * {
  margin-top: 40px;
}
@media (min-width: 768px) {
  .bodywebsite .post-single * + .post-header {
	margin-top: 22px;
  }
  .bodywebsite .post-single * + .post-meta {
	margin-top: 10px;
  }
}
.bodywebsite .post-info * + .post-main {
  margin-top: 30px;
}
.bodywebsite .post-info * + .post-body {
  margin-top: 20px;
}
@media (min-width: 768px) {
  .bodywebsite .post-info .post-main {
	display: -ms-flexbox;
	display: -webkit-flex;
	display: flex;
	-webkit-flex-direction: row;
	-ms-flex-direction: row;
	flex-direction: row;
	-webkit-flex-wrap: nowrap;
	-ms-flex-wrap: nowrap;
	flex-wrap: nowrap;
  }
  .bodywebsite .post-info .post-left {
	-webkit-flex-basis: 33.33333%;
	-ms-flex-preferred-size: 33.33333%;
	flex-basis: 33.33333%;
	max-width: 33.33333%;
	padding-right: 25px;
  }
  .bodywebsite .post-info .post-body {
	-webkit-flex-basis: 66.66667%;
	-ms-flex-preferred-size: 66.66667%;
	flex-basis: 66.66667%;
	max-width: 66.66667%;
  }
  .bodywebsite .post-info * + .post-body {
	margin-top: 0;
  }
}
.bodywebsite .post-minimal {
  position: relative;
  border-radius: 4px;
  overflow: hidden;
  background: #fff;
  box-shadow: -1px 0px 10px 0px rgba(65, 65, 65, 0.12);
}
.bodywebsite .post-minimal .post-body {
  padding: 20px;
}
.bodywebsite .post-minimal * + p {
  margin-top: 8px;
}
.bodywebsite .post-minimal * + .post-meta {
  margin-top: 5px;
}
@media (max-width: 575px) {
  .bodywebsite .post-minimal {
	display: inline-block;
	width: 100%;
	max-width: 300px;
  }
}
@media (min-width: 576px) {
  .bodywebsite .post-minimal {
	display: -ms-flexbox;
	display: -webkit-flex;
	display: flex;
	-webkit-flex-direction: row;
	-ms-flex-direction: row;
	flex-direction: row;
	-webkit-flex-wrap: nowrap;
	-ms-flex-wrap: nowrap;
	flex-wrap: nowrap;
	-webkit-align-items: center;
	-ms-flex-align: center;
	align-items: center;
	-webkit-justify-content: center;
	-ms-flex-pack: center;
	justify-content: center;
  }
  .bodywebsite .post-minimal .post-left {
	display: -ms-flexbox;
	display: -webkit-flex;
	display: flex;
	-webkit-flex-direction: column;
	-ms-flex-direction: column;
	flex-direction: column;
	-webkit-align-items: stretch;
	-ms-flex-align: stretch;
	align-items: stretch;
	-webkit-flex-shrink: 0;
	-ms-flex-negative: 0;
	flex-shrink: 0;
	-webkit-align-self: stretch;
	-ms-flex-item-align: stretch;
	align-self: stretch;
	width: 220px;
  }
  .bodywebsite .post-minimal .post-image {
	position: relative;
	-webkit-flex-shrink: 0;
	-ms-flex-negative: 0;
	flex-shrink: 0;
	-webkit-align-self: stretch;
	-ms-flex-item-align: stretch;
	align-self: stretch;
	-webkit-flex-grow: 1;
	-ms-flex-positive: 1;
	flex-grow: 1;
	overflow: hidden;
  }
  .bodywebsite .post-minimal .post-image img {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	height: auto;
	width: auto;
	min-height: 100%;
	min-width: 100%;
	z-index: 1;
  }
  .bodywebsite .post-minimal .post-body {
	padding: 30px 24px 30px 27px;
	-webkit-flex-grow: 1;
	-ms-flex-positive: 1;
	flex-grow: 1;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .post-minimal .post-body {
	padding: 40px 26px 40px 50px;
  }
}
.bodywebsite .post-preview {
  max-width: 320px;
}
.bodywebsite .post-preview a {
  display: block;
}
.bodywebsite .post-preview .post-image,
.bodywebsite .post-preview .post-image img {
  border-radius: 5px;
}
.bodywebsite .post-preview .post-image img {
  width: auto;
}
.bodywebsite .post-preview .post-header {
  line-height: 1.5;
  color: #000;
  transition: .33s all ease;
}
.bodywebsite .post-preview .list-meta > li {
  display: inline-block;
  font-size: 12px;
  font-style: italic;
  color: #9b9b9b;
}
.bodywebsite .post-preview .list-meta > li:not(:last-child):after {
  content: '/';
}
.bodywebsite .post-preview:hover .post-header {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .post-preview * + .post-meta {
  margin-top: 5px;
}
.bodywebsite .post-preview.post-preview-inverse > li {
  color: rgba(255, 255, 255, 0.5);
}
.bodywebsite .post-preview.post-preview-inverse .post-header {
  color: #fff;
}
.bodywebsite .post-preview.post-preview-inverse:hover .post-header {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .blog-timeline > dt {
  font: 700 25px "Roboto", Helvetica, Arial, sans-serif;
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .blog-timeline > dd {
  margin-top: 20px;
}
@media (min-width: 768px) {
  .bodywebsite .blog-timeline {
	word-spacing: 0;
	white-space: nowrap;
  }
  .bodywebsite .blog-timeline > * {
	display: inline-block;
  }
  .bodywebsite .blog-timeline > dt {
	min-width: 65px;
	padding-right: 20px;
	margin-top: -0.15em;
	vertical-align: top;
  }
  .bodywebsite .blog-timeline > dd {
	margin-top: 0;
	width: calc(0%);
  }
}
@media (min-width: 992px) {
  .bodywebsite .blog-timeline > dt {
	min-width: 100px;
	padding-right: 30px;
  }
}
.bodywebsite .post-boxed {
  max-width: 330px;
  margin-right: auto;
  margin-left: auto;
  padding-top: 10px;
  text-align: center;
  box-shadow: -1px 0px 10px 0px rgba(65, 65, 65, 0.12);
  transition: .3s all ease;
}
.bodywebsite .post-boxed-img-wrap a {
  display: block;
}
.bodywebsite .post-boxed-title {
  font: 500 18px/28px "Roboto", Helvetica, Arial, sans-serif;
}
.bodywebsite .post-boxed-title {
  color: #000;
}
.bodywebsite .post-boxed-title a {
  display: inline;
  color: #000;
}
.bodywebsite .post-boxed-title a:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .post-boxed img {
  max-width: 75%;
}
.bodywebsite .post-boxed .list-inline {
  font-size: 12px;
  letter-spacing: .05em;
}
.bodywebsite .post-boxed-caption {
  padding: 20px;
}
.bodywebsite #sectionteam .post-boxed-caption {
	height: 140px;
}
.bodywebsite .post-boxed .post-boxed-title + * {
  margin-top: 5px;
}
@media (min-width: 768px) {
  .bodywebsite .post-boxed .post-boxed-caption {
	padding: 28px 42px 36px 28px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .post-boxed:hover {
	box-shadow: -3px 5px 12px 0px rgba(65, 65, 65, 0.16);
  }
}
.bodywebsite .post-minimal .post-image {
  height: 100%;
}
.bodywebsite .post-minimal .post-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.bodywebsite * + .post-blockquote {
  margin-top: 30px;
}
.bodywebsite .post-blockquote + * {
  margin-top: 30px;
}
@media (min-width: 768px) {
  .bodywebsite * + .post-blockquote {
	margin-top: 50px;
  }
  .bodywebsite .post-blockquote + * {
	margin-top: 50px;
  }
}
.bodywebsite * + .post-comment-block,
.bodywebsite * + .post-comment-form {
  margin-top: 40px;
}
.bodywebsite .post-comment-form h4 + * {
  margin-top: 15px;
}
.bodywebsite .comment figure,
.bodywebsite .comment figure img {
  border-radius: 50%;
  max-width: 71px;
}
.bodywebsite .comment time {
  font-size: 12px;
  line-height: 1;
  color: #9b9b9b;
}
.bodywebsite .comment .user {
  font-size: 16px;
  line-height: 1.33333;
  font-weight: 700;
  text-transform: uppercase;
  color: #000;
}
.bodywebsite .comment .list-icon-meta {
  position: relative;
  transform: translateY(0);
  margin-bottom: 0;
}
.bodywebsite .comment .list-icon-meta > * {
  margin-top: 0;
}
.bodywebsite .comment .list-icon-meta > *:not(:last-child) {
  margin-right: 8px;
}
.bodywebsite .comment .list-icon-meta > li {
  display: inline-block;
}
.bodywebsite .comment .list-icon-meta li {
  font-size: 12px;
  line-height: 1;
  font-weight: 400;
}
.bodywebsite .comment .comment-body {
  padding: 17px 22px;
  border: 1px solid #dedede;
  border-radius: 7px;
}
.bodywebsite .comment .comment-body-header {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-wrap: wrap;
  -ms-flex-wrap: wrap;
  flex-wrap: wrap;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-align-items: flex-start;
  -ms-flex-align: start;
  align-items: flex-start;
  -webkit-justify-content: space-between;
  -ms-flex-pack: justify;
  justify-content: space-between;
  position: relative;
  transform: translateY(-5px);
  margin-bottom: -5px;
}
.bodywebsite .comment .comment-body-header > * {
  margin-top: 5px;
}
.bodywebsite .comment .comment-body-header > *:not(:last-child) {
  margin-right: 5px;
}
@media (min-width: 768px) {
  .bodywebsite .comment .comment-body-header {
	-webkit-align-items: center;
	-ms-flex-align: center;
	align-items: center;
  }
}
.bodywebsite .comment .comment-body-header > * {
  -webkit-flex-shrink: 0;
  -ms-flex-negative: 0;
  flex-shrink: 0;
}
.bodywebsite .comment .comment-meta {
  -webkit-flex-grow: 1;
  -ms-flex-positive: 1;
  flex-grow: 1;
  position: relative;
  transform: translateY(-5px);
  margin-bottom: -5px;
}
.bodywebsite .comment .comment-meta > * {
  margin-top: 5px;
}
.bodywebsite .comment .comment-meta > *:not(:last-child) {
  margin-right: 10px;
}
@media (min-width: 768px) {
  .bodywebsite .comment .comment-meta > * {
	display: inline-block;
	vertical-align: baseline;
  }
}
.bodywebsite .comment .comment-body-text {
  margin-top: 10px;
}
.bodywebsite .comment-minimal .author {
  font: 700 14px/18px "Roboto", Helvetica, Arial, sans-serif;
  text-transform: uppercase;
  letter-spacing: -0.025em;
  color: #000;
}
.bodywebsite .comment-minimal * + .comment-body {
  margin-top: 10px;
}
.bodywebsite * + .comment-minimal {
  margin-top: 35px;
}
.bodywebsite .comment-group-reply {
  padding-left: 12%;
}
.bodywebsite .comment + * {
  margin-top: 21px;
}
.bodywebsite * + .comment-group {
  margin-top: 30px;
}
@media (min-width: 768px) {
  .bodywebsite .comment > .unit > .unit-left {
	margin-top: 16px;
  }
  .bodywebsite * + .comment-minimal {
	margin-top: 45px;
  }
}
@media (min-width: 992px) {
  .bodywebsite * + .post-comment {
	margin-top: 80px;
  }
}
.bodywebsite .page-title {
  text-align: center;
}
.bodywebsite .page-title > * {
  letter-spacing: 0;
  text-transform: uppercase;
}
.bodywebsite .page-title .page-title-inner {
  position: relative;
  display: inline-block;
}
.bodywebsite .page-title .page-title-left,
.bodywebsite .page-title .page-title-right {
  position: absolute;
  top: 50%;
  width: auto;
  overflow: hidden;
  white-space: nowrap;
  vertical-align: middle;
}
.bodywebsite .page-title .page-title-left *,
.bodywebsite .page-title .page-title-right * {
  display: inline;
  white-space: nowrap;
}
.bodywebsite .page-title .page-title-left {
  left: 0;
  text-align: right;
  transform: translate(-100%, -50%);
}
.bodywebsite .page-title .page-title-left * {
  padding-right: .85em;
}
.bodywebsite .page-title .page-title-left *:nth-last-child(odd) {
  color: rgba(255, 255, 255, 0.1);
}
.bodywebsite .page-title .page-title-left *:nth-last-child(even) {
  color: rgba(255, 255, 255, 0.2);
}
.bodywebsite .page-title .page-title-right {
  right: 0;
  text-align: left;
  transform: translate(100%, -50%);
}
.bodywebsite .page-title .page-title-right * {
  padding-left: .85em;
}
.bodywebsite .page-title .page-title-right *:nth-child(odd) {
  color: rgba(255, 255, 255, 0.1);
}
.bodywebsite .page-title .page-title-right *:nth-child(even) {
  color: rgba(255, 255, 255, 0.2);
}
.bodywebsite .page-title-wrap {
  background: #000;
  background-attachment: fixed;
  -webkit-background-size: cover;
  background-size: cover;
  background-position: center 80%;
}
@media (min-width: 768px) {
  .bodywebsite .page-title {
	text-align: left;
  }
}
.bodywebsite .preloader {
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  right: 0;
  z-index: 10000;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
  background: #fff;
  transition: .3s all ease;
}
.bodywebsite .preloader.loaded {
  opacity: 0;
  visibility: hidden;
}
.bodywebsite .preloader-body {
  text-align: center;
}
.bodywebsite .preloader-body p {
  position: relative;
  right: -8px;
}
.bodywebsite .cssload-container {
  width: 100%;
  height: 36px;
  text-align: center;
}
.bodywebsite .cssload-speeding-wheel {
  width: 36px;
  height: 36px;
  margin: 0 auto;
  border: 3px solid <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  border-radius: 50%;
  border-left-color: transparent;
  border-bottom-color: transparent;
  animation: cssload-spin 0.88s infinite linear;
}
@-webkit-keyframes cssload-spin {
  100% {
	transform: rotate(360deg);
  }
}
@keyframes cssload-spin {
  100% {
	transform: rotate(360deg);
  }
}
.bodywebsite .pricing-table {
  overflow: hidden;
  background: #fff;
  text-align: center;
  box-shadow: -1px 2px 5px 0 rgba(65, 65, 65, 0.12);
}
.bodywebsite .pricing-table-header {
  font-size: 18px;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: #464a4d;
}
.bodywebsite .pricing-table-body {
  padding: 35px 30px;
}
.bodywebsite .pricing-table-label {
  padding: 17px 15px;
  text-align: center;
  background: #3a3c3e;
}
.bodywebsite .pricing-table-label p {
  font: 700 14px "Roboto", Helvetica, Arial, sans-serif;
  letter-spacing: .05em;
  text-transform: uppercase;
  color: #fff;
}
.bodywebsite .pricing-table .pricing-list {
  font-size: 16px;
  font-weight: 300;
  color: #00030a;
}
.bodywebsite .pricing-table .pricing-list span {
  display: inline-block;
  margin-right: .25em;
}
.bodywebsite .pricing-table .pricing-list > li + li {
  margin-top: 12px;
}
.bodywebsite .pricing-table * + .price-object {
  margin-top: 22px;
}
.bodywebsite .pricing-table * + .pricing-list {
  margin-top: 22px;
}
.bodywebsite .pricing-object {
  font-family: "Roboto", Helvetica, Arial, sans-serif;
  font-weight: 900;
  font-size: 0;
  line-height: 0;
  color: #000;
}
.bodywebsite .pricing-object > * {
  margin-top: 0;
}
.bodywebsite .pricing-object .price {
  font-family: Helvetica, Arial, sans-serif;
  font-weight: 900;
}
.bodywebsite .pricing-object .small {
  position: relative;
  font: 700 10px "Roboto", Helvetica, Arial, sans-serif;
  color: inherit;
  text-transform: uppercase;
}
.bodywebsite .pricing-object .small-middle {
  vertical-align: middle;
}
.bodywebsite .pricing-object .small-bottom {
  vertical-align: bottom;
}
.bodywebsite .pricing-object-sm {
  font-size: 32px;
  line-height: .8;
}
.bodywebsite .pricing-object-sm .small {
  font-size: 12px;
}
.bodywebsite .pricing-object-sm .small-middle {
  margin-right: 3px;
}
.bodywebsite .pricing-object-sm .small-bottom {
  margin-left: 1px;
  vertical-align: bottom;
}
.bodywebsite .price-irrelevant {
  color: #9f9f9f;
  text-decoration: line-through;
}
.bodywebsite .pricing-object-md {
  font-size: 53px;
  line-height: 1;
}
.bodywebsite .pricing-object-md .price {
  line-height: .5;
}
.bodywebsite .pricing-object-md .small {
  font-size: 17px;
  font-weight: 400;
}
.bodywebsite .pricing-object-md .small-middle {
  font-size: 23px;
}
.bodywebsite .pricing-object-md .small-bottom {
  bottom: -0.25em;
}
.bodywebsite .pricing-object-lg,
.bodywebsite .pricing-object-xl {
  font-size: 64px;
  line-height: .7;
}
.bodywebsite .pricing-object-lg .small,
.bodywebsite .pricing-object-xl .small {
  font-size: 9px;
}
.bodywebsite .pricing-object-lg .small-top,
.bodywebsite .pricing-object-xl .small-top {
  top: 11px;
  margin-right: 5px;
  font-size: 14px;
  vertical-align: top;
  font-weight: 700;
}
.bodywebsite .pricing-object-lg .small-bottom,
.bodywebsite .pricing-object-xl .small-bottom {
  bottom: -10px;
  margin-left: -2px;
  font-weight: 700;
  vertical-align: bottom;
}
.bodywebsite .price-current .small {
  position: relative;
  font-family: "Roboto", Helvetica, Arial, sans-serif;
  font-weight: 400;
}
.bodywebsite .price-current .small-middle {
  vertical-align: middle;
  top: -0.3em;
}
.bodywebsite .price-current .small-bottom {
  top: .3em;
}
@media (min-width: 768px) {
  .bodywebsite .pricing-object-lg {
	font-size: 72px;
  }
  .bodywebsite .pricing-object-xl {
	font-size: 54px;
  }
  .bodywebsite .pricing-object-xl .small-middle {
	font-size: 30px;
  }
  .bodywebsite .pricing-object-xl .small-bottom {
	font-size: 25px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .pricing-object-xl {
	font-size: 76px;
  }
}
.bodywebsite .panel.bg-accent.bg-default-outline-btn.text-center {
  background: transparent;
}
.bodywebsite .product .product-label {
  padding: 7px 11px;
  min-width: 90px;
  font: 700 12px/16px "Roboto", Helvetica, Arial, sans-serif;
  letter-spacing: .05em;
  text-align: center;
  border-radius: 0 0 7px 7px;
}
.bodywebsite .product .product-rating {
  position: relative;
  transform: translateY(-2px);
  margin-bottom: -2px;
}
.bodywebsite .product .product-rating > * {
  margin-top: 2px;
}
.bodywebsite .product .product-rating > *:not(:last-child) {
  margin-right: 5px;
}
.bodywebsite .product .product-rating > * {
  display: inline-block;
  vertical-align: middle;
}
.bodywebsite .product .product-color {
  display: inline-block;
  width: 24px;
  height: 24px;
  font-size: 0;
  line-height: 0;
  border-radius: 50%;
  background: #000;
  vertical-align: middle;
}
.bodywebsite .product .product-size {
  font: 700 14px/18px "Roboto", Helvetica, Arial, sans-serif;
  color: #000;
}
.bodywebsite .product * + .product-brand,
.bodywebsite .product .product-brand + * {
  margin-top: 0;
}
.bodywebsite .one-screen-page .page {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
}
.bodywebsite .one-screen-page .page-inner {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: column;
  -ms-flex-direction: column;
  flex-direction: column;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: space-between;
  -ms-flex-pack: justify;
  justify-content: space-between;
  min-height: 100vh;
  text-align: center;
}
.bodywebsite .one-screen-page .page-inner,
.bodywebsite .one-screen-page .page-inner > * {
  width: 100%;
}
.bodywebsite .one-screen-page .page-head,
.bodywebsite .one-screen-page .page-foot {
  padding: 0;
  background: transparent;
}
.bodywebsite .one-screen-page .page-head-inner {
  padding: calc(5em) 0 calc(3em);
}
.bodywebsite .one-screen-page .page-content {
  padding: calc(5em) 0;
}
.bodywebsite .one-screen-page .page-foot-inner {
  padding: calc(3em) 0 calc(5em);
}
.bodywebsite .one-screen-page .rights {
  color: #fff;
}
.bodywebsite .one-screen-page .rights a,
.bodywebsite .one-screen-page .rights a:active,
.bodywebsite .one-screen-page .rights a:focus {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .one-screen-page .rights a:hover {
  color: #ababab;
}
@media (min-width: 576px) {
  .bodywebsite .one-screen-page .page-inner {
	text-align: left;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .one-screen-page .page-head-inner {
	padding: 50px 0 10px;
  }
  .bodywebsite .one-screen-page .page-content {
	padding: 30px 0;
  }
  .bodywebsite .one-screen-page .page-foot-inner {
	padding: 50px 0 23px;
  }
}
.bodywebsite .ie-10 .one-screen-page,
.bodywebsite .ie-11 .one-screen-page {
  overflow-x: hidden;
  overflow-y: auto;
}
.bodywebsite .inset-left-0 {
  padding-left: 0;
}
.bodywebsite .inset-left-10 {
  padding-left: 10px;
}
.bodywebsite .inset-left-15 {
  padding-left: 15px;
}
.bodywebsite .inset-left-20 {
  padding-left: 20px;
}
.bodywebsite .inset-left-30 {
  padding-left: 30px;
}
.bodywebsite .inset-left-40 {
  padding-left: 40px;
}
.bodywebsite .inset-left-50 {
  padding-left: 50px;
}
.bodywebsite .inset-left-60 {
  padding-left: 60px;
}
.bodywebsite .inset-left-70 {
  padding-left: 70px;
}
.bodywebsite .inset-left-85 {
  padding-left: 85px;
}
.bodywebsite .inset-left-100 {
  padding-left: 100px;
}
@media (min-width: 576px) {
  .bodywebsite .inset-sm-left-0 {
	padding-left: 0;
  }
  .bodywebsite .inset-sm-left-10 {
	padding-left: 10px;
  }
  .bodywebsite .inset-sm-left-15 {
	padding-left: 15px;
  }
  .bodywebsite .inset-sm-left-20 {
	padding-left: 20px;
  }
  .bodywebsite .inset-sm-left-30 {
	padding-left: 30px;
  }
  .bodywebsite .inset-sm-left-40 {
	padding-left: 40px;
  }
  .bodywebsite .inset-sm-left-50 {
	padding-left: 50px;
  }
  .bodywebsite .inset-sm-left-60 {
	padding-left: 60px;
  }
  .bodywebsite .inset-sm-left-70 {
	padding-left: 70px;
  }
  .bodywebsite .inset-sm-left-85 {
	padding-left: 85px;
  }
  .bodywebsite .inset-sm-left-100 {
	padding-left: 100px;
  }
}
@media (min-width: 768px) {
  .bodywebsite .inset-md-left-0 {
	padding-left: 0;
  }
  .bodywebsite .inset-md-left-10 {
	padding-left: 10px;
  }
  .bodywebsite .inset-md-left-15 {
	padding-left: 15px;
  }
  .bodywebsite .inset-md-left-20 {
	padding-left: 20px;
  }
  .bodywebsite .inset-md-left-30 {
	padding-left: 30px;
  }
  .bodywebsite .inset-md-left-40 {
	padding-left: 40px;
  }
  .bodywebsite .inset-md-left-50 {
	padding-left: 50px;
  }
  .bodywebsite .inset-md-left-60 {
	padding-left: 60px;
  }
  .bodywebsite .inset-md-left-70 {
	padding-left: 70px;
  }
  .bodywebsite .inset-md-left-85 {
	padding-left: 85px;
  }
  .bodywebsite .inset-md-left-100 {
	padding-left: 100px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .inset-lg-left-0 {
	padding-left: 0;
  }
  .bodywebsite .inset-lg-left-10 {
	padding-left: 10px;
  }
  .bodywebsite .inset-lg-left-15 {
	padding-left: 15px;
  }
  .bodywebsite .inset-lg-left-20 {
	padding-left: 20px;
  }
  .bodywebsite .inset-lg-left-30 {
	padding-left: 30px;
  }
  .bodywebsite .inset-lg-left-40 {
	padding-left: 40px;
  }
  .bodywebsite .inset-lg-left-50 {
	padding-left: 50px;
  }
  .bodywebsite .inset-lg-left-60 {
	padding-left: 60px;
  }
  .bodywebsite .inset-lg-left-70 {
	padding-left: 70px;
  }
  .bodywebsite .inset-lg-left-85 {
	padding-left: 85px;
  }
  .bodywebsite .inset-lg-left-100 {
	padding-left: 100px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .inset-xl-left-0 {
	padding-left: 0;
  }
  .bodywebsite .inset-xl-left-10 {
	padding-left: 10px;
  }
  .bodywebsite .inset-xl-left-15 {
	padding-left: 15px;
  }
  .bodywebsite .inset-xl-left-20 {
	padding-left: 20px;
  }
  .bodywebsite .inset-xl-left-30 {
	padding-left: 30px;
  }
  .bodywebsite .inset-xl-left-40 {
	padding-left: 40px;
  }
  .bodywebsite .inset-xl-left-50 {
	padding-left: 50px;
  }
  .bodywebsite .inset-xl-left-60 {
	padding-left: 60px;
  }
  .bodywebsite .inset-xl-left-70 {
	padding-left: 70px;
  }
  .bodywebsite .inset-xl-left-85 {
	padding-left: 85px;
  }
  .bodywebsite .inset-xl-left-100 {
	padding-left: 100px;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .inset-xxl-left-0 {
	padding-left: 0;
  }
  .bodywebsite .inset-xxl-left-10 {
	padding-left: 10px;
  }
  .bodywebsite .inset-xxl-left-15 {
	padding-left: 15px;
  }
  .bodywebsite .inset-xxl-left-20 {
	padding-left: 20px;
  }
  .bodywebsite .inset-xxl-left-30 {
	padding-left: 30px;
  }
  .bodywebsite .inset-xxl-left-40 {
	padding-left: 40px;
  }
  .bodywebsite .inset-xxl-left-50 {
	padding-left: 50px;
  }
  .bodywebsite .inset-xxl-left-60 {
	padding-left: 60px;
  }
  .bodywebsite .inset-xxl-left-70 {
	padding-left: 70px;
  }
  .bodywebsite .inset-xxl-left-85 {
	padding-left: 85px;
  }
  .bodywebsite .inset-xxl-left-100 {
	padding-left: 100px;
  }
}
.bodywebsite .inset-right-0 {
  padding-right: 0;
}
.bodywebsite .inset-right-10 {
  padding-right: 10px;
}
.bodywebsite .inset-right-15 {
  padding-right: 15px;
}
.bodywebsite .inset-right-20 {
  padding-right: 20px;
}
.bodywebsite .inset-right-30 {
  padding-right: 30px;
}
.bodywebsite .inset-right-40 {
  padding-right: 40px;
}
.bodywebsite .inset-right-50 {
  padding-right: 50px;
}
.bodywebsite .inset-right-60 {
  padding-right: 60px;
}
.bodywebsite .inset-right-70 {
  padding-right: 70px;
}
.bodywebsite .inset-right-85 {
  padding-right: 85px;
}
.bodywebsite .inset-right-100 {
  padding-right: 100px;
}
@media (min-width: 576px) {
  .bodywebsite .inset-sm-right-0 {
	padding-right: 0;
  }
  .bodywebsite .inset-sm-right-10 {
	padding-right: 10px;
  }
  .bodywebsite .inset-sm-right-15 {
	padding-right: 15px;
  }
  .bodywebsite .inset-sm-right-20 {
	padding-right: 20px;
  }
  .bodywebsite .inset-sm-right-30 {
	padding-right: 30px;
  }
  .bodywebsite .inset-sm-right-40 {
	padding-right: 40px;
  }
  .bodywebsite .inset-sm-right-50 {
	padding-right: 50px;
  }
  .bodywebsite .inset-sm-right-60 {
	padding-right: 60px;
  }
  .bodywebsite .inset-sm-right-70 {
	padding-right: 70px;
  }
  .bodywebsite .inset-sm-right-85 {
	padding-right: 85px;
  }
  .bodywebsite .inset-sm-right-100 {
	padding-right: 100px;
  }
}
@media (min-width: 768px) {
  .bodywebsite .inset-md-right-0 {
	padding-right: 0;
  }
  .bodywebsite .inset-md-right-10 {
	padding-right: 10px;
  }
  .bodywebsite .inset-md-right-15 {
	padding-right: 15px;
  }
  .bodywebsite .inset-md-right-20 {
	padding-right: 20px;
  }
  .bodywebsite .inset-md-right-30 {
	padding-right: 30px;
  }
  .bodywebsite .inset-md-right-40 {
	padding-right: 40px;
  }
  .bodywebsite .inset-md-right-50 {
	padding-right: 50px;
  }
  .bodywebsite .inset-md-right-60 {
	padding-right: 60px;
  }
  .bodywebsite .inset-md-right-70 {
	padding-right: 70px;
  }
  .bodywebsite .inset-md-right-85 {
	padding-right: 85px;
  }
  .bodywebsite .inset-md-right-100 {
	padding-right: 100px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .inset-lg-right-0 {
	padding-right: 0;
  }
  .bodywebsite .inset-lg-right-10 {
	padding-right: 10px;
  }
  .bodywebsite .inset-lg-right-15 {
	padding-right: 15px;
  }
  .bodywebsite .inset-lg-right-20 {
	padding-right: 20px;
  }
  .bodywebsite .inset-lg-right-30 {
	padding-right: 30px;
  }
  .bodywebsite .inset-lg-right-40 {
	padding-right: 40px;
  }
  .bodywebsite .inset-lg-right-50 {
	padding-right: 50px;
  }
  .bodywebsite .inset-lg-right-60 {
	padding-right: 60px;
  }
  .bodywebsite .inset-lg-right-70 {
	padding-right: 70px;
  }
  .bodywebsite .inset-lg-right-85 {
	padding-right: 85px;
  }
  .bodywebsite .inset-lg-right-100 {
	padding-right: 100px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .inset-xl-right-0 {
	padding-right: 0;
  }
  .bodywebsite .inset-xl-right-10 {
	padding-right: 10px;
  }
  .bodywebsite .inset-xl-right-15 {
	padding-right: 15px;
  }
  .bodywebsite .inset-xl-right-20 {
	padding-right: 20px;
  }
  .bodywebsite .inset-xl-right-30 {
	padding-right: 30px;
  }
  .bodywebsite .inset-xl-right-40 {
	padding-right: 40px;
  }
  .bodywebsite .inset-xl-right-50 {
	padding-right: 50px;
  }
  .bodywebsite .inset-xl-right-60 {
	padding-right: 60px;
  }
  .bodywebsite .inset-xl-right-70 {
	padding-right: 70px;
  }
  .bodywebsite .inset-xl-right-85 {
	padding-right: 85px;
  }
  .bodywebsite .inset-xl-right-100 {
	padding-right: 100px;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .inset-xxl-right-0 {
	padding-right: 0;
  }
  .bodywebsite .inset-xxl-right-10 {
	padding-right: 10px;
  }
  .bodywebsite .inset-xxl-right-15 {
	padding-right: 15px;
  }
  .bodywebsite .inset-xxl-right-20 {
	padding-right: 20px;
  }
  .bodywebsite .inset-xxl-right-30 {
	padding-right: 30px;
  }
  .bodywebsite .inset-xxl-right-40 {
	padding-right: 40px;
  }
  .bodywebsite .inset-xxl-right-50 {
	padding-right: 50px;
  }
  .bodywebsite .inset-xxl-right-60 {
	padding-right: 60px;
  }
  .bodywebsite .inset-xxl-right-70 {
	padding-right: 70px;
  }
  .bodywebsite .inset-xxl-right-85 {
	padding-right: 85px;
  }
  .bodywebsite .inset-xxl-right-100 {
	padding-right: 100px;
  }
}
.bodywebsite .container + .container {
  margin-top: 60px;
}
.bodywebsite h3.section-title {
  color: #000;
}
.bodywebsite h4 + .section-title {
  margin-top: 2px;
}
.bodywebsite h4 + .comment-list {
  margin-top: 30px;
}
.bodywebsite h3 + p {
  margin-top: 15px;
}
.bodywebsite h3 + p.h4 {
  margin-top: 2px;
}
.bodywebsite h3 + .row {
  margin-top: 40px;
}
.bodywebsite h3 + * {
  margin-top: 40px;
}
.bodywebsite .row + .row {
  margin-top: 60px;
}
.bodywebsite * + .row.list-md-dashed {
  margin-top: 60px;
}
.bodywebsite .row + .button-block {
  margin-top: 60px;
}
.bodywebsite .slick-slider + .slick-slider.carousel-parent {
  margin-top: 35px;
}
.bodywebsite .quote-left + .button-block {
  margin-top: 22px;
}
.bodywebsite .aside-title + * {
  margin-top: 22px;
}
.bodywebsite * + .button-group {
  margin-top: 25px;
}
html .bodywebsite .page .offset-top-0 {
  margin-top: 0;
}
html .bodywebsite .page .offset-top-2 {
  margin-top: 2px;
}
html .bodywebsite .page .offset-top-5 {
  margin-top: 5px;
}
html .bodywebsite .page .offset-top-10 {
  margin-top: 10px;
}
html .bodywebsite .page .offset-top-15 {
  margin-top: 15px;
}
html .bodywebsite .page .offset-top-22 {
  margin-top: 22px;
}
html .bodywebsite .page .offset-top-27 {
  margin-top: 27px;
}
html .bodywebsite .page .offset-top-30 {
  margin-top: 30px;
}
html .bodywebsite .page .offset-top-35 {
  margin-top: 35px;
}
html .bodywebsite .page .offset-top-40 {
  margin-top: 40px;
}
html .bodywebsite .page .offset-top-45 {
  margin-top: 45px;
}
html .bodywebsite .page .offset-top-50 {
  margin-top: 50px;
}
html .bodywebsite .page .offset-top-60 {
  margin-top: 60px;
}
html .bodywebsite .page .offset-top-75 {
  margin-top: 75px;
}
html .bodywebsite .page .offset-top-90 {
  margin-top: 90px;
}
html .bodywebsite .page .offset-top-100 {
  margin-top: 100px;
}
html .bodywebsite .page .offset-top-120 {
  margin-top: 120px;
}
@media (min-width: 576px) {
  html .bodywebsite .page .offset-sm-top-0 {
	margin-top: 0;
  }
  html .bodywebsite .page .offset-sm-top-2 {
	margin-top: 2px;
  }
  html .bodywebsite .page .offset-sm-top-5 {
	margin-top: 5px;
  }
  html .bodywebsite .page .offset-sm-top-10 {
	margin-top: 10px;
  }
  html .bodywebsite .page .offset-sm-top-15 {
	margin-top: 15px;
  }
  html .bodywebsite .page .offset-sm-top-22 {
	margin-top: 22px;
  }
  html .bodywebsite .page .offset-sm-top-27 {
	margin-top: 27px;
  }
  html .bodywebsite .page .offset-sm-top-30 {
	margin-top: 30px;
  }
  html .bodywebsite .page .offset-sm-top-35 {
	margin-top: 35px;
  }
  html .bodywebsite .page .offset-sm-top-40 {
	margin-top: 40px;
  }
  html .bodywebsite .page .offset-sm-top-45 {
	margin-top: 45px;
  }
  html .bodywebsite .page .offset-sm-top-50 {
	margin-top: 50px;
  }
  html .bodywebsite .page .offset-sm-top-60 {
	margin-top: 60px;
  }
  html .bodywebsite .page .offset-sm-top-75 {
	margin-top: 75px;
  }
  html .bodywebsite .page .offset-sm-top-90 {
	margin-top: 90px;
  }
  html .bodywebsite .page .offset-sm-top-100 {
	margin-top: 100px;
  }
  html .bodywebsite .page .offset-sm-top-120 {
	margin-top: 120px;
  }
}
@media (min-width: 768px) {
  html .bodywebsite .page .offset-md-top-0 {
	margin-top: 0;
  }
  html .bodywebsite .page .offset-md-top-2 {
	margin-top: 2px;
  }
  html .bodywebsite .page .offset-md-top-5 {
	margin-top: 5px;
  }
  html .bodywebsite .page .offset-md-top-10 {
	margin-top: 10px;
  }
  html .bodywebsite .page .offset-md-top-15 {
	margin-top: 15px;
  }
  html .bodywebsite .page .offset-md-top-22 {
	margin-top: 22px;
  }
  html .bodywebsite .page .offset-md-top-27 {
	margin-top: 27px;
  }
  html .bodywebsite .page .offset-md-top-30 {
	margin-top: 30px;
  }
  html .bodywebsite .page .offset-md-top-35 {
	margin-top: 35px;
  }
  html .bodywebsite .page .offset-md-top-40 {
	margin-top: 40px;
  }
  html .bodywebsite .page .offset-md-top-45 {
	margin-top: 45px;
  }
  html .bodywebsite .page .offset-md-top-50 {
	margin-top: 50px;
  }
  html .bodywebsite .page .offset-md-top-60 {
	margin-top: 60px;
  }
  html .bodywebsite .page .offset-md-top-75 {
	margin-top: 75px;
  }
  html .bodywebsite .page .offset-md-top-90 {
	margin-top: 90px;
  }
  html .bodywebsite .page .offset-md-top-100 {
	margin-top: 100px;
  }
  html .bodywebsite .page .offset-md-top-120 {
	margin-top: 120px;
  }
}
@media (min-width: 992px) {
  html .bodywebsite .page .offset-lg-top-0 {
	margin-top: 0;
  }
  html .bodywebsite .page .offset-lg-top-2 {
	margin-top: 2px;
  }
  html .bodywebsite .page .offset-lg-top-5 {
	margin-top: 5px;
  }
  html .bodywebsite .page .offset-lg-top-10 {
	margin-top: 10px;
  }
  html .bodywebsite .page .offset-lg-top-15 {
	margin-top: 15px;
  }
  html .bodywebsite .page .offset-lg-top-22 {
	margin-top: 22px;
  }
  html .bodywebsite .page .offset-lg-top-27 {
	margin-top: 27px;
  }
  html .bodywebsite .page .offset-lg-top-30 {
	margin-top: 30px;
  }
  html .bodywebsite .page .offset-lg-top-35 {
	margin-top: 35px;
  }
  html .bodywebsite .page .offset-lg-top-40 {
	margin-top: 40px;
  }
  html .bodywebsite .page .offset-lg-top-45 {
	margin-top: 45px;
  }
  html .bodywebsite .page .offset-lg-top-50 {
	margin-top: 50px;
  }
  html .bodywebsite .page .offset-lg-top-60 {
	margin-top: 60px;
  }
  html .bodywebsite .page .offset-lg-top-75 {
	margin-top: 75px;
  }
  html .bodywebsite .page .offset-lg-top-90 {
	margin-top: 90px;
  }
  html .bodywebsite .page .offset-lg-top-100 {
	margin-top: 100px;
  }
  html .bodywebsite .page .offset-lg-top-120 {
	margin-top: 120px;
  }
}
@media (min-width: 1200px) {
  html .bodywebsite .page .offset-xl-top-0 {
	margin-top: 0;
  }
  html .bodywebsite .page .offset-xl-top-2 {
	margin-top: 2px;
  }
  html .bodywebsite .page .offset-xl-top-5 {
	margin-top: 5px;
  }
  html .bodywebsite .page .offset-xl-top-10 {
	margin-top: 10px;
  }
  html .bodywebsite .page .offset-xl-top-15 {
	margin-top: 15px;
  }
  html .bodywebsite .page .offset-xl-top-22 {
	margin-top: 22px;
  }
  html .bodywebsite .page .offset-xl-top-27 {
	margin-top: 27px;
  }
  html .bodywebsite .page .offset-xl-top-30 {
	margin-top: 30px;
  }
  html .bodywebsite .page .offset-xl-top-35 {
	margin-top: 35px;
  }
  html .bodywebsite .page .offset-xl-top-40 {
	margin-top: 40px;
  }
  html .bodywebsite .page .offset-xl-top-45 {
	margin-top: 45px;
  }
  html .bodywebsite .page .offset-xl-top-50 {
	margin-top: 50px;
  }
  html .bodywebsite .page .offset-xl-top-60 {
	margin-top: 60px;
  }
  html .bodywebsite .page .offset-xl-top-75 {
	margin-top: 75px;
  }
  html .bodywebsite .page .offset-xl-top-90 {
	margin-top: 90px;
  }
  html .bodywebsite .page .offset-xl-top-100 {
	margin-top: 100px;
  }
  html .bodywebsite .page .offset-xl-top-120 {
	margin-top: 120px;
  }
}
@media (min-width: 1800px) {
  html .bodywebsite .page .offset-xxl-top-0 {
	margin-top: 0;
  }
  html .bodywebsite .page .offset-xxl-top-2 {
	margin-top: 2px;
  }
  html .bodywebsite .page .offset-xxl-top-5 {
	margin-top: 5px;
  }
  html .bodywebsite .page .offset-xxl-top-10 {
	margin-top: 10px;
  }
  html .bodywebsite .page .offset-xxl-top-15 {
	margin-top: 15px;
  }
  html .bodywebsite .page .offset-xxl-top-22 {
	margin-top: 22px;
  }
  html .bodywebsite .page .offset-xxl-top-27 {
	margin-top: 27px;
  }
  html .bodywebsite .page .offset-xxl-top-30 {
	margin-top: 30px;
  }
  html .bodywebsite .page .offset-xxl-top-35 {
	margin-top: 35px;
  }
  html .bodywebsite .page .offset-xxl-top-40 {
	margin-top: 40px;
  }
  html .bodywebsite .page .offset-xxl-top-45 {
	margin-top: 45px;
  }
  html .bodywebsite .page .offset-xxl-top-50 {
	margin-top: 50px;
  }
  html .bodywebsite .page .offset-xxl-top-60 {
	margin-top: 60px;
  }
  html .bodywebsite .page .offset-xxl-top-75 {
	margin-top: 75px;
  }
  html .bodywebsite .page .offset-xxl-top-90 {
	margin-top: 90px;
  }
  html .bodywebsite .page .offset-xxl-top-100 {
	margin-top: 100px;
  }
  html .bodywebsite .page .offset-xxl-top-120 {
	margin-top: 120px;
  }
}
@media (min-width: 768px) {
  .bodywebsite .shift-sm-top-1 {
	margin-top: -18px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .shift-sm-top-1 {
	margin-top: -23px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .shift-md-top-1 {
	margin-top: -33px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .shift-md-top-1 {
	margin-top: -43px;
  }
}
.bodywebsite .row-0 {
  margin-bottom: 0px;
}
.bodywebsite .row-0:empty {
  margin-bottom: 0;
}
.bodywebsite .row-0 > * {
  margin-bottom: 0px;
}
.bodywebsite .row-15 {
  margin-bottom: -15px;
}
.bodywebsite .row-15:empty {
  margin-bottom: 0;
}
.bodywebsite .row-15 > * {
  margin-bottom: 15px;
}
.bodywebsite .row-20 {
  margin-bottom: -20px;
}
.bodywebsite .row-20:empty {
  margin-bottom: 0;
}
.bodywebsite .row-20 > * {
  margin-bottom: 20px;
}
.bodywebsite .row-30 {
  margin-bottom: -30px;
}
.bodywebsite .row-30:empty {
  margin-bottom: 0;
}
.bodywebsite .row-30 > * {
  margin-bottom: 30px;
}
.bodywebsite .row-40 {
  margin-bottom: -40px;
}
.bodywebsite .row-40:empty {
  margin-bottom: 0;
}
.bodywebsite .row-40 > * {
  margin-bottom: 40px;
}
.bodywebsite .row-50 {
  margin-bottom: -50px;
}
.bodywebsite .row-50:empty {
  margin-bottom: 0;
}
.bodywebsite .row-50 > * {
  margin-bottom: 50px;
}
.bodywebsite .row-60 {
  margin-bottom: -60px;
}
.bodywebsite .row-60:empty {
  margin-bottom: 0;
}
.bodywebsite .row-60 > * {
  margin-bottom: 60px;
}
@media (min-width: 576px) {
  .bodywebsite .row-sm-50 {
	margin-bottom: -50px;
  }
  .bodywebsite .row-sm-50:empty {
	margin-bottom: 0;
  }
  .bodywebsite .row-sm-50 > * {
	margin-bottom: 50px;
  }
  .bodywebsite .row-sm-0 {
	margin-bottom: 0px;
  }
  .bodywebsite .row-sm-0:empty {
	margin-bottom: 0;
  }
  .bodywebsite .row-sm-0 > * {
	margin-bottom: 0px;
  }
}
@media (min-width: 768px) {
  .bodywebsite .row-md-60 {
	margin-bottom: -60px;
  }
  .bodywebsite .row-md-60:empty {
	margin-bottom: 0;
  }
  .bodywebsite .row-md-60 > * {
	margin-bottom: 60px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .row-md-30 {
	margin-bottom: -30px;
  }
  .bodywebsite .row-md-30:empty {
	margin-bottom: 0;
  }
  .bodywebsite .row-md-30 > * {
	margin-bottom: 30px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .row-xl-100 {
	margin-bottom: -100px;
  }
  .bodywebsite .row-xl-100:empty {
	margin-bottom: 0;
  }
  .bodywebsite .row-xl-100 > * {
	margin-bottom: 100px;
  }
  .bodywebsite .row-xl-90 {
	margin-bottom: -90px;
  }
  .bodywebsite .row-xl-90:empty {
	margin-bottom: 0;
  }
  .bodywebsite .row-xl-90 > * {
	margin-bottom: 90px;
  }
}
.bodywebsite .link {
  display: inline-block;
}
.bodywebsite .link-inline {
  font: inherit;
  line-height: inherit;
  text-decoration: underline;
}
.bodywebsite .link-underline,
.bodywebsite .link-underline:active,
.bodywebsite .link-underline:focus {
  text-decoration: underline;
}
.bodywebsite .link-underline:hover {
  text-decoration: none;
}
.bodywebsite .link-circle {
  border-radius: 50%;
}
.bodywebsite .link-circle .icon,
.bodywebsite .link-circle .icon:before {
  position: static;
}
.bodywebsite .link-bold {
  font: 700 18px/22px "Roboto", Helvetica, Arial, sans-serif;
}
.bodywebsite .link-group {
  white-space: nowrap;
}
.bodywebsite .link-group * {
  vertical-align: middle;
}
.bodywebsite .link-group span {
  display: inline-block;
}
.bodywebsite .link-group span + *,
.bodywebsite .link-group * + span {
  margin-left: 5px;
}
.bodywebsite .link-group.link-group-animated .icon {
  position: relative;
  right: 0;
  transition: .22s;
}
.bodywebsite .link-group.link-group-animated:hover .icon {
  right: -5px;
}
.bodywebsite .link-group-baseline * {
  vertical-align: baseline;
}
.bodywebsite .link-icon,
.bodywebsite .link-icon * {
  vertical-align: middle;
}
.bodywebsite .link-icon .icon {
  margin-right: 5px;
}
.bodywebsite .link-icon-mod .icon {
  position: relative;
  top: -3px;
}
.bodywebsite .link-image img {
  width: auto;
  transition: .44s all ease;
  opacity: .5;
}
.bodywebsite .link-image:hover img {
  opacity: 1;
}
.bodywebsite .link-image-wrap {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: center;
  -ms-flex-pack: center;
  justify-content: center;
  min-height: 126px;
}
.bodywebsite * + .link-image-wrap {
  margin-top: 13px;
}
.bodywebsite .page .link-primary-inline {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page .link-primary-inline.active,
.bodywebsite .page .link-primary-inline:active,
.bodywebsite .page .link-primary-inline:focus {
  color: #9b9b9b;
}
.bodywebsite .page .link-primary-inline.hover,
.bodywebsite .page .link-primary-inline:hover {
  color: #42b294;
}
.bodywebsite .page .link-default,
.bodywebsite .page .link-default:active,
.bodywebsite .page .link-default:focus {
  color: #9f9f9f;
}
.bodywebsite .page .link-default:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page .link-primary,
.bodywebsite .page .link-primary:active,
.bodywebsite .page .link-primary:focus {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page .link-primary:hover {
  color: #00030a;
}
.bodywebsite .page .link-primary-inverse,
.bodywebsite .page .link-primary-inverse:active,
.bodywebsite .page .link-primary-inverse:focus {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page .link-primary-inverse:hover {
  color: #ddd;
}
.bodywebsite .page .link-primary-inverse-v2,
.bodywebsite .page .link-primary-inverse-v2:active,
.bodywebsite .page .link-primary-inverse-v2:focus {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page .link-primary-inverse-v2:hover {
  color: #fff;
}
.bodywebsite .page .link-secondary,
.bodywebsite .page .link-secondary:active,
.bodywebsite .page .link-secondary:focus {
  color: #00030a;
}
.bodywebsite .page .link-secondary:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page .link-tundora,
.bodywebsite .page .link-tundora:active,
.bodywebsite .page .link-tundora:focus {
  color: #414141;
}
.bodywebsite .page .link-tundora:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page .link-tundora-inverse,
.bodywebsite .page .link-tundora-inverse:active,
.bodywebsite .page .link-tundora-inverse:focus {
  color: #414141;
}
.bodywebsite .page .link-tundora-inverse:hover {
  color: #fff;
}
.bodywebsite .page .link-secondary,
.bodywebsite .page .link-secondary:active,
.bodywebsite .page .link-secondary:focus {
  color: #000;
}
.bodywebsite .page .link-secondary:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page .link-gray-light,
.bodywebsite .page .link-gray-light:active,
.bodywebsite .page .link-gray-light:focus {
  color: #dedede;
}
.bodywebsite .page .link-gray-light:hover {
  color: #000;
}
.bodywebsite .page .link-white,
.bodywebsite .page .link-white:active,
.bodywebsite .page .link-white:focus {
  color: #fff;
}
.bodywebsite .page .link-white:hover {
  color: #fff;
}
.bodywebsite .page .link-black,
.bodywebsite .page .link-black:active,
.bodywebsite .page .link-black:focus {
  color: #000;
}
.bodywebsite .page .link-black:hover {
  color: <?php echo empty($website->maincolorbis) ? 'rgb(50, 120, 180)' : '#'.$website->maincolorbis; ?>;
}
.bodywebsite .page .link-black:hover {
  text-decoration: underline;
}
.bodywebsite .page .link-gray-dark-filled,
.bodywebsite .page .link-gray-dark-filled:active,
.bodywebsite .page .link-gray-dark-filled:focus {
  color: #fff;
  background: #2a2b2b;
}
.bodywebsite .page .link-gray-dark-filled:hover {
  color: #fff;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .page .link-shop {
  width: 25px;
  height: 25px;
  font-size: 25px;
  line-height: 25px;
}
.bodywebsite .page .link-shop,
.bodywebsite .page .link-shop:active,
.bodywebsite .page .link-shop:focus {
  color: #00030a;
}
.bodywebsite .page .link-shop:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite ul,
.bodywebsite ol {
  list-style: none;
  padding: 0;
  margin: 0;
}
.bodywebsite dl {
  margin: 0;
}
.bodywebsite dt {
  font-weight: inherit;
}
.bodywebsite .list > li + li {
  margin-top: 5px;
}
.bodywebsite .list-xl > li + li {
  margin-top: 44px;
}
.bodywebsite .list-inline {
  margin-left: -5px;
  margin-right: -5px;
  vertical-align: baseline;
}
.bodywebsite .list-inline > li {
  display: inline-block;
  padding-left: 8px;
  padding-right: 8px;
}
.bodywebsite .list-inline-xs {
  margin-left: -6px;
  margin-right: -6px;
}
.bodywebsite .list-inline-xs > li {
  display: inline-block;
  padding-left: 6px;
  padding-right: 6px;
}
.bodywebsite .list-inline-sm {
  margin-left: -10px;
  margin-right: -10px;
}
.bodywebsite .list-inline-sm > li {
  display: inline-block;
  padding-left: 10px;
  padding-right: 10px;
}
.bodywebsite .list-inline-md {
  margin-left: -15px;
  margin-right: -15px;
}
.bodywebsite .list-inline-md > li {
  display: inline-block;
  padding-left: 15px;
  padding-right: 15px;
}
.bodywebsite .list-objects-inline {
  margin-bottom: -4px;
  margin-left: -22px;
  transform: translateY(-4px);
}
.bodywebsite .list-objects-inline > *,
.bodywebsite .list-objects-inline > *:first-child {
  display: inline-block;
  vertical-align: middle;
  margin-top: 4px;
  margin-left: 22px;
}
.bodywebsite .list-objects-inline > li > * {
  display: inline-block;
  vertical-align: middle;
}
.bodywebsite .list-objects-inline > li > * + * {
  margin-left: 5px;
}
.bodywebsite .list-terms dt + dd {
  margin-top: 10px;
}
.bodywebsite .list-terms dd + dt {
  margin-top: 31px;
}
.bodywebsite .list-terms-variant-1 dt {
  font: 700 16px/22px "Roboto", Helvetica, Arial, sans-serif;
  letter-spacing: -0.025em;
  color: #000;
}
.bodywebsite .list-terms-variant-1 dt + dd {
  margin-top: 18px;
}
.bodywebsite .list-terms-variant-1 dd + dt {
  margin-top: 40px;
}
@media (min-width: 1200px) {
  .bodywebsite .list-terms-variant-1 dt {
	font-size: 24px;
	line-height: 1.2;
  }
  .bodywebsite .list-terms-variant-1 dd + dt {
	margin-top: 50px;
  }
}
.bodywebsite .list-inline-dashed {
  margin-left: -15px;
}
.bodywebsite .list-inline-dashed li {
  padding-left: 15px;
  padding-right: 10px;
}
.bodywebsite .list-inline-dashed li:after {
  content: '|';
  position: relative;
  right: -12.5px;
  color: #e5e7e9;
}
.bodywebsite .list-inline-dashed li:last-child {
  padding-right: 0;
}
.bodywebsite .list-inline-dashed li:last-child:after {
  display: none;
}
@media (min-width: 992px) {
  .bodywebsite .list-md-dashed > * {
	position: relative;
  }
  .bodywebsite .list-md-dashed > *:after {
	content: '';
	position: absolute;
	font-weight: 100;
	top: 0;
	right: -6%;
	height: 73px;
	-webkit-transform: translateX(-50%) skew(-21deg);
	transform: translateX(-50%) skew(-21deg);
	width: 1px;
	background: #48494a;
  }
}
@media (min-width: 992px) and (min-width: 1200px) {
  .bodywebsite .list-md-dashed > *:after {
	right: -3%;
	height: 120px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .list-md-dashed > *:last-child::after {
	display: none;
  }
}
.bodywebsite .dl-inline {
  vertical-align: middle;
}
.bodywebsite .dl-inline dt,
.bodywebsite .dl-inline dd {
  display: inline-block;
  vertical-align: middle;
}
.bodywebsite .dl-inline dt {
  padding-right: 5px;
}
.bodywebsite .dl-inline dt:after {
  content: ':';
}
.bodywebsite .dl-inline .pricing-object-sm {
  position: relative;
  top: -5px;
}
.bodywebsite .list-terms-inline dt,
.bodywebsite .list-terms-inline dd {
  display: inline-block;
}
.bodywebsite .list-terms-inline dt {
  color: #000;
}
.bodywebsite .list-terms-inline dd {
  color: #9f9f9f;
}
.bodywebsite .list-terms-inline dt:after {
  content: ':';
}
.bodywebsite .list-index {
  counter-reset: li;
}
.bodywebsite .list-index > li .list-index-counter:before {
  content: counter(li, decimal-leading-zero);
  counter-increment: li;
}
.bodywebsite .list-marked li {
  color: #000;
  position: relative;
  padding-left: 32px;
}
.bodywebsite .list-marked li:before {
  position: absolute;
  top: 1px;
  left: 0;
  content: '\e005';
  font-family: "fl-flat-icons-set-2";
  display: inline-block;
  margin-right: 11px;
  font-size: 13px;
  line-height: inherit;
  vertical-align: middle;
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .list-marked li:not(:last-child):after {
  content: ';';
}
.bodywebsite .list-marked li:last-child:after {
  content: '.';
}
.bodywebsite .list-marked li + li {
  margin-top: 11px;
}
.bodywebsite .list-marked-spacing-lg li {
  padding-left: 26px;
}
@media (min-width: 992px) and (max-width: 1799px) {
  .bodywebsite .list-marked li {
	padding-left: 24px;
	font-size: 13px;
  }
  .bodywebsite .list-marked li:before {
	font-size: 11px;
  }
}
.bodywebsite .list-marked-variant-2 > li > a {
  position: relative;
  display: inline-block;
}
.bodywebsite .list-marked-variant-2 > li > a:hover:before {
  left: 4px;
}
.bodywebsite .list-marked-variant-2 > li + li {
  margin-top: 14px;
}
.bodywebsite .list-ordered {
  counter-reset: li;
}
.bodywebsite .list-ordered li {
  color: #000;
}
.bodywebsite .list-ordered li:before {
  display: inline-block;
  margin-right: 13px;
  width: 15px;
  content: counter(li, decimal) ".";
  counter-increment: li;
}
.bodywebsite .list-ordered li:not(:last-child):after {
  content: ';';
}
.bodywebsite .list-ordered li:last-child:after {
  content: '.';
}
.bodywebsite .list-ordered li + li {
  margin-top: 11px;
}
.bodywebsite .list-tags > li {
  display: inline-block;
  font-style: italic;
}
.bodywebsite .list-tags > li a,
.bodywebsite .list-tags > li a:active,
.bodywebsite .list-tags > li a:focus {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .list-tags > li a:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .list-tags > li:after {
  content: ',';
  display: inline-block;
  color: #f9f9f9;
}
.bodywebsite .list-tags > li:last-child:after {
  display: none;
}
.bodywebsite .list-numbered {
  counter-reset: li;
}
.bodywebsite .list-numbered > li {
  position: relative;
  padding-left: 30px;
}
.bodywebsite .list-numbered > li:before {
  position: absolute;
  top: 0;
  left: 0;
  content: counter(li, decimal) ".";
  counter-increment: li;
}
.bodywebsite .list-numbered > li + li {
  margin-top: 10px;
}
.bodywebsite .list-icon-pack {
  margin-top: 6px;
}
.bodywebsite .list-icon-pack > li {
  margin-top: 25px;
}
.bodywebsite .list-icon-pack > li span {
  display: block;
}
.bodywebsite .list-icon-pack > li span + span {
  margin-left: .25em;
}
.bodywebsite .list-icon-pack h6 + *,
.bodywebsite .list-icon-pack .h6 + * {
  margin-top: 2px;
}
.bodywebsite .list-links > li {
  display: inline-block;
}
.bodywebsite .list-links > li:after {
  content: ';';
}
.bodywebsite .list-links > li:last-child:after {
  display: none;
}
.bodywebsite .list-hashtags > li {
  display: inline-block;
}
.bodywebsite .list-hashtags > li a {
  color: inherit;
}
.bodywebsite .list-hashtags > li a:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .list-hashtags > li > a:before {
  content: '#';
}
.bodywebsite .list-marked-bordered {
  color: #000;
}
.bodywebsite .list-marked-bordered li a {
  display: block;
  padding: 10px 7px;
  border-bottom: 1px solid #f9f9f9;
}
.bodywebsite .list-marked-bordered li a:before {
  position: relative;
  display: inline-block;
  padding-right: 10px;
  font-family: "Font Awesome 5 Free";
  font-weight: 600;
  font-size: initial;
  line-height: inherit;
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  content: '\f105';
}
.bodywebsite .list-marked-bordered li a span {
  color: inherit;
  transition: color .33s;
}
.bodywebsite .list-marked-bordered li a span:first-child {
  color: #000;
}
.bodywebsite .list-marked-bordered li a:hover,
.bodywebsite .list-marked-bordered li a:hover span:nth-child(n) {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .list-marked-bordered li span:not(:last-child) {
  margin-right: .25em;
}
.bodywebsite .list-bordered-horizontal {
  position: relative;
  transform: translateY(-7px);
  margin-bottom: -7px;
}
.bodywebsite .list-bordered-horizontal > * {
  margin-top: 7px;
}
.bodywebsite .list-bordered-horizontal > *:not(:last-child) {
  margin-right: 35px;
}
@media (min-width: 768px) {
  .bodywebsite .list-bordered-horizontal > li {
	display: inline-block;
  }
  .bodywebsite .list-bordered-horizontal > li:not(:last-child) {
	position: relative;
  }
  .bodywebsite .list-bordered-horizontal > li:not(:last-child):after {
	content: '';
	position: absolute;
	top: 50%;
	transform: translateY(-50%);
	right: -20px;
	width: 1px;
	height: 22px;
	background: #dedede;
  }
}
.bodywebsite .list-tag-blocks {
  position: relative;
  transform: translateY(-6px);
  margin-bottom: -6px;
}
.bodywebsite .list-tag-blocks > * {
  margin-top: 6px;
}
.bodywebsite .list-tag-blocks > *:not(:last-child) {
  margin-right: 6px;
}
.bodywebsite .list-tag-blocks li {
  display: inline-block;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}
.bodywebsite .list-tag-blocks li a {
  display: inline-block;
  padding: 6px 19px;
  border-radius: 0;
  border: 1px solid transparent;
}
.bodywebsite .list-tag-blocks li a,
.bodywebsite .list-tag-blocks li a:active,
.bodywebsite .list-tag-blocks li a:focus {
  color: #000;
  background: #fff;
}
.bodywebsite .list-tag-blocks li a:hover {
  background: transparent;
  border-color: #cdcdcd;
}
.bodywebsite .list-progress {
  font-family: "Roboto", Helvetica, Arial, sans-serif;
  color: #00030a;
}
.bodywebsite .list-progress li + li {
  margin-top: 23px;
}
.bodywebsite .list-progress p {
  padding-right: 40px;
}
.bodywebsite .list-tags-inline > li {
  display: inline;
}
.bodywebsite .list-tags-inline > li a {
  color: inherit;
}
.bodywebsite .list-tags-inline > li a:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .list-tags-inline > li:not(:last-child):after {
  content: ',';
}
.bodywebsite .list-rating {
  font-size: 0;
  line-height: 0;
}
.bodywebsite .list-rating > li {
  display: inline-block;
}
.bodywebsite .list-rating .icon {
  color: #ffd400;
}
.bodywebsite .list-wide-bordered {
  color: #00030a;
  font: 400 14px/22px "Roboto", Helvetica, Arial, sans-serif;
  border-top: 1px solid #dedede;
}
.bodywebsite .list-wide-bordered dl {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: space-between;
  -ms-flex-pack: justify;
  justify-content: space-between;
  width: 100%;
  font-weight: 700;
}
.bodywebsite .list-wide-bordered dl dt {
  padding-right: 15px;
}
.bodywebsite .list-wide-bordered dl dd {
  font-weight: 700;
  font-size: 14px;
}
.bodywebsite .list-wide-bordered li {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  min-height: 54px;
  padding: 10px 20px;
  border-bottom: 1px solid #dedede;
}
.bodywebsite .list-wide-bordered + .list-wide-bordered {
  border-top: 0;
}
@media (min-width: 768px) {
  .bodywebsite .list-wide-bordered {
	font-size: 16px;
  }
  .bodywebsite .list-wide-bordered li {
	min-height: 73px;
	padding: 20px 30px;
  }
}
.bodywebsite .object-wrap {
  position: relative;
  overflow: hidden;
}
.bodywebsite .object-wrap-right > .object-wrap-body {
  right: 0;
}
.bodywebsite .object-wrap-left > .object-wrap-body {
  left: 0;
}
@media (min-width: 768px) {
  .bodywebsite .object-wrap-sm-right > .object-wrap-body {
	right: 0;
  }
  .bodywebsite .object-wrap-sm-left > .object-wrap-body {
	left: 0;
  }
}

@media (max-width: 767px) {
  .bodywebsite .object-wrap-body {
	position: relative;
	overflow: hidden;
	min-height: 300px;
	width: 100%;
  }
  .bodywebsite .object-wrap-body > img {
	position: absolute;
	min-width: 100%;
	max-width: none;
	height: auto;
	max-height: none;
	top: 20%;
	left: 50%;
	transform: translate(-50%, -20%);
  }
  .bodywebsite .page .text-primary {
	word-break: break-all;
  }
  .bodywebsite figure img {
	  margin: unset;
  }
}

@media (min-width: 768px) {
  .bodywebsite .object-wrap-body {
	overflow: hidden;
	position: absolute;
	top: 0;
	bottom: 0;
	width: 100vw;
	min-width: 1px;
	max-width: none;
	height: 100%;
	min-height: 100%;
	max-height: none;
	margin: 0;
	background: inherit;
	z-index: 0;
  }
  .bodywebsite .object-wrap-body > img {
	position: relative;
	height: auto;
	min-height: 100.5%;
	width: auto;
	min-width: 102%;
	max-width: none;
	left: 50%;
	transform: translateX(-50%);
  }
  .bodywebsite .object-wrap-body + * {
	margin-top: 0;
  }
}
@media (min-width: 768px) {
  .bodywebsite .sm-width-c6 {
	width: calc(50vw);
  }
}
@media (min-width: 992px) {
  .bodywebsite .md-width-c7d20 {
	width: calc(150vw);
  }
}
@media (min-width: 1200px) {
  .bodywebsite .md-width-c7d20 {
	width: calc(167.5vw);
  }
}
@media (min-width: 992px) {
  .bodywebsite .md-width-c5dm20 {
	width: calc(-50vw);
  }
}
@media (min-width: 1200px) {
  .bodywebsite .md-width-c5dm20 {
	width: calc(-67.5vw);
  }
}
.bodywebsite .bg-wrap {
  position: relative;
}
.bodywebsite .bg-wrap:before {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  width: 120vw;
  left: 50%;
  transform: translateX(-50%);
  background: inherit;
  z-index: -1;
}
.bodywebsite .bg-wrap-sm-left {
  z-index: 1;
}
@media (min-width: 992px) {
  .bodywebsite .bg-wrap-sm-left:before {
	width: 100vw;
	right: 0;
	transform: none;
  }
}
.bodywebsite .bg-wrap-sm-right {
  z-index: 1;
}
@media (min-width: 992px) {
  .bodywebsite .bg-wrap-sm-right:before {
	width: 100vw;
	left: 0;
	transform: none;
  }
}
@media (min-width: 576px) {
  .bodywebsite .wrap-justify {
	display: -ms-flexbox;
	display: -webkit-flex;
	display: flex;
	-webkit-flex-direction: row;
	-ms-flex-direction: row;
	flex-direction: row;
	-webkit-flex-wrap: nowrap;
	-ms-flex-wrap: nowrap;
	flex-wrap: nowrap;
	-webkit-align-items: center;
	-ms-flex-align: center;
	align-items: center;
	-webkit-justify-content: space-around;
	-ms-flex-pack: distribute;
	justify-content: space-around;
  }
  .bodywebsite .wrap-justify > * + * {
	margin-top: 0;
  }
}
@media (min-width: 768px) {
  .bodywebsite .wrap-justify {
	-webkit-justify-content: space-between;
	-ms-flex-pack: justify;
	justify-content: space-between;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .wrap-justify {
	padding-right: 30px;
  }
}
.bodywebsite .link-wrap {
  line-height: 1;
}
.bodywebsite .link-wrap a {
  display: inline;
  line-height: 1;
}
.bodywebsite [class*='bg-decoration-wrap'] {
  position: relative;
  overflow: hidden;
}
.bodywebsite [class*='bg-decoration-wrap'] .bg-decoration-content {
  position: relative;
  z-index: 2;
}
.bodywebsite [class*='bg-decoration-wrap'] .bg-decoration-object {
  top: 0;
  bottom: auto;
}
.bodywebsite .bg-decoration-bottom .bg-decoration-object {
  top: auto;
  bottom: 0;
}
@media (min-width: 768px) {
  .bodywebsite .bg-decoration-wrap-sm .bg-decoration-object {
	height: 50%;
	position: absolute;
	right: 0;
	left: 0;
  }
  .bodywebsite .bg-decoration-bottom-sm .bg-decoration-object {
	height: 34%;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .bg-decoration-object {
	height: 50%;
	position: absolute;
	right: 0;
	left: 0;
  }
  .bodywebsite .bg-decoration-bottom-mod .bg-decoration-object {
	height: 45%;
  }
}
.bodywebsite .divider-fullwidth {
  height: 1px;
  width: 100%;
}
.bodywebsite .hr {
  border: none;
  height: 1px;
  width: 100%;
}
.bodywebsite .tabs-custom .nav-tabs {
  display: block;
  word-spacing: 0;
  border: 0;
}
.bodywebsite .tabs-custom .nav-tabs:before,
.bodywebsite .tabs-custom .nav-tabs:after {
  display: none;
}
.bodywebsite .tabs-custom .nav-tabs .nav-item {
  float: none;
  border: 0;
  cursor: pointer;
  transition: .33s all ease;
}
.bodywebsite .tabs-custom .nav-tabs .nav-item .nav-link.active {
  cursor: default;
  border: 0;
}
.bodywebsite .tabs-custom .nav-tabs .nav-link {
  margin: 0;
  border: 0;
}
.bodywebsite * + .tabs-custom {
  margin-top: 35px;
}
@media (min-width: 992px) {
  .bodywebsite * + .tabs-custom {
	margin-top: 50px;
  }
}
.bodywebsite .tabs-custom.tabs-corporate .nav-tabs,
.bodywebsite .tabs-custom.tabs-line .nav-tabs,
.bodywebsite .tabs-custom.tabs-minimal .nav-tabs {
  font-size: 0;
  line-height: 0;
}
.bodywebsite .tabs-custom.tabs-corporate .nav-tabs {
  border: 1px solid #dedede;
}
.bodywebsite .tabs-custom.tabs-corporate .nav-tabs li {
  margin: -1px 0;
}
.bodywebsite .tabs-custom.tabs-corporate .nav-tabs .nav-item .nav-link {
  padding: 8px 10px;
  font: 700 11px/18px "Roboto", Helvetica, Arial, sans-serif;
  color: #000;
  background: transparent;
  border-bottom: 1px solid #dedede;
  text-align: center;
  vertical-align: middle;
}
.bodywebsite .tabs-custom.tabs-corporate .nav-tabs .nav-item .nav-link:after {
  font: 400 17px 'Material Design Icons';
  color: transparent;
  position: relative;
  top: -12px;
  display: inline-block;
  margin-left: 5px;
  content: '\f236';
  vertical-align: middle;
  transition: .33s all ease;
}
.bodywebsite .tabs-custom.tabs-corporate .nav-tabs .nav-item .nav-link:first-child {
  border-top: 1px solid #dedede;
}
.bodywebsite .tabs-custom.tabs-corporate .nav-tabs .nav-item .nav-link:hover,
.bodywebsite .tabs-custom.tabs-corporate .nav-tabs .nav-item .active.nav-link {
  color: #fff;
  background: #3a3c3e;
  border-color: #3a3c3e;
}
.bodywebsite .tabs-custom.tabs-corporate .nav-tabs .nav-item .nav-link:hover:after,
.bodywebsite .tabs-custom.tabs-corporate .nav-tabs .nav-item .active.nav-link:after {
  top: -1px;
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .tabs-custom.tabs-corporate .tab-content {
  padding: 22px 0 0;
}
.bodywebsite .tabs-custom.tabs-line .nav-tabs .nav-item,
.bodywebsite .tabs-custom.tabs-minimal .nav-tabs .nav-item {
  margin: 0;
}
.bodywebsite .tabs-custom.tabs-line .nav-tabs .nav-item + .nav-item,
.bodywebsite .tabs-custom.tabs-minimal .nav-tabs .nav-item + .nav-item {
  margin-top: -1px;
}
.bodywebsite .tabs-custom.tabs-line .tab-content,
.bodywebsite .tabs-custom.tabs-minimal .tab-content {
  padding: 22px 0 0;
}
.bodywebsite .tabs-custom.tabs-line .nav-tabs .nav-item .nav-link {
  font: 400 11px/16px "Roboto", Helvetica, Arial, sans-serif;
  letter-spacing: -0.05em;
  text-transform: uppercase;
  color: #9b9b9b;
}
.bodywebsite .tabs-custom.tabs-line .nav-tabs .nav-item .nav-link:hover,
.bodywebsite .tabs-custom.tabs-line .nav-tabs .nav-item .active.nav-link {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .tabs-custom.tabs-minimal .nav-tabs .nav-item .nav-link {
  font: 700 11px/24px "Roboto", Helvetica, Arial, sans-serif;
  color: #000;
}
.bodywebsite .tabs-custom.tabs-minimal .nav-tabs .nav-item .nav-link:hover,
.bodywebsite .tabs-custom.tabs-minimal .nav-tabs .nav-item .active.nav-link {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .tabs-custom.tabs-line .nav-tabs {
  text-align: center;
}
.bodywebsite .tabs-custom.tabs-line .nav-tabs .nav-item {
  display: block;
  margin: 0 -1px;
}
.bodywebsite .tabs-custom.tabs-line .nav-tabs .nav-item .nav-link {
  padding: 8px 15px;
  border: 1px solid #e5e7e9;
}
.bodywebsite .tabs-custom.tabs-line .nav-tabs .nav-item .nav-link:last-child {
  margin-right: 0;
  border-bottom-width: 1px;
}
.bodywebsite .tabs-custom.tabs-line .nav-tabs .nav-item .nav-link:hover,
.bodywebsite .tabs-custom.tabs-line .nav-tabs .nav-item .active.nav-link {
  color: #fff;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  border-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .tabs-custom.tabs-minimal .nav-tabs {
  text-align: center;
}
.bodywebsite .tabs-custom.tabs-minimal .nav-tabs .nav-item {
  display: block;
}
.bodywebsite .tabs-custom.tabs-minimal .nav-tabs .nav-item .nav-link {
  padding: 7px 15px;
  border: 1px solid #e5e7e9;
}
.bodywebsite .tabs-custom.tabs-minimal .nav-tabs .nav-item .nav-link:last-child {
  border-bottom-width: 1px;
}
.bodywebsite .tabs-custom.tabs-minimal .nav-tabs .nav-item .nav-link:hover,
.bodywebsite .tabs-custom.tabs-minimal .nav-tabs .nav-item .active.nav-link {
  color: #fff;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  border-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
@media (min-width: 768px) {
  .bodywebsite .tabs-custom.tabs-line .nav-item,
  .bodywebsite .tabs-custom.tabs-minimal .nav-item {
	margin: 0;
  }
  .bodywebsite .tabs-custom.tabs-line .nav-tabs .nav-item .nav-link {
	font-size: 14px;
	line-height: 24px;
  }
  .bodywebsite .tabs-custom.tabs-minimal .nav-tabs .nav-item .nav-link {
	font-size: 14px;
	line-height: 24px;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-corporate .nav-tabs,
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-line .nav-tabs {
	display: -ms-flexbox;
	display: -webkit-flex;
	display: flex;
	-webkit-flex-direction: row;
	-ms-flex-direction: row;
	flex-direction: row;
	-webkit-align-items: center;
	-ms-flex-align: center;
	align-items: center;
	width: 100%;
	text-align: left;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-line .nav-tabs .nav-item .nav-link,
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-minimal .nav-tabs .nav-item .nav-link {
	position: relative;
	z-index: 10;
	display: inline-block;
	border: 0;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-line .nav-tabs .nav-item + .nav-item,
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-minimal .nav-tabs .nav-item + .nav-item {
	margin-top: 0;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-line .tab-content,
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-minimal .tab-content {
	padding: 40px 0 0;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-corporate .nav-tabs .nav-item,
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-corporate .nav-tabs .nav-link {
	display: block;
	border: 0;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-line .nav-tabs {
	-webkit-justify-content: space-between;
	-ms-flex-pack: justify;
	justify-content: space-between;
	border-bottom: 2px solid #e5e7e9;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-line .nav-tabs .nav-item .nav-link {
	padding: 8px 0 8px 0;
	margin: 0 30px 0 0;
	font-weight: 700;
	background: transparent;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-line .nav-tabs .nav-item .nav-link:after {
	content: '';
	position: absolute;
	left: 0;
	right: 100%;
	bottom: -1px;
	border-bottom: 2px solid <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
	opacity: 0;
	visibility: hidden;
	transition: .33s all ease;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-line .nav-tabs .nav-item .nav-link:hover,
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-line .nav-tabs .nav-item .active.nav-link {
	color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
	background: transparent;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-line .nav-tabs .nav-item .active.nav-link:after {
	right: 0;
	opacity: 1;
	visibility: visible;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-minimal .nav-tabs {
	margin-top: -10px;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-minimal .nav-tabs .nav-item {
	display: inline-block;
	margin: 10px 15px 0 0;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-minimal .nav-tabs .nav-item .nav-link {
	position: relative;
	bottom: -1px;
	z-index: 10;
	display: inline-block;
	padding: 0 0 5px 0;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-minimal .nav-tabs .nav-item .nav-link:after {
	content: '';
	position: absolute;
	left: 0;
	right: 100%;
	bottom: 0;
	border-bottom: 2px solid <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
	opacity: 0;
	visibility: hidden;
	transition: .33s all ease;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-minimal .nav-tabs .nav-item .nav-link:last-child {
	margin-right: 0;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-minimal .nav-tabs .nav-item .nav-link:hover,
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-minimal .nav-tabs .nav-item .active.nav-link {
	color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
	background: transparent;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-minimal .nav-tabs .nav-item .active.nav-link:after {
	right: 0;
	opacity: 1;
	visibility: visible;
  }
}
.bodywebsite .tabs-vertical .nav-tabs {
  position: relative;
}
.bodywebsite .tabs-vertical .nav-tabs > .nav-item {
  z-index: 10;
  display: block;
  -webkit-flex-grow: 1;
  -ms-flex-positive: 1;
  flex-grow: 1;
}
.bodywebsite .tabs-vertical.tabs-corporate .nav-tabs {
  width: 100%;
}
.bodywebsite .tabs-vertical.tabs-corporate .nav-tabs .nav-item {
  display: block;
}
.bodywebsite .tabs-vertical.tabs-corporate .nav-tabs .nav-item .nav-link {
  position: relative;
  padding: 8px 10px;
}
.bodywebsite .tabs-vertical.tabs-corporate .nav-tabs .nav-item .nav-link:hover,
.bodywebsite .tabs-vertical.tabs-corporate .nav-tabs .nav-item .active.nav-link {
  border-color: #3a3c3e;
}
.bodywebsite .tabs-vertical.tabs-corporate .tab-content {
  padding: 30px 0 0;
}
.bodywebsite .tabs-vertical.tabs-minimal .nav-tabs {
  border-right: 1px solid #ddd;
}
.bodywebsite .tabs-vertical.tabs-minimal .nav-tabs .nav-item .nav-link {
  position: relative;
  right: -1px;
  padding: 0 16px 0 0;
  text-align: right;
  border-right: 1px solid transparent;
  background-color: transparent;
}
.bodywebsite .tabs-vertical.tabs-minimal .nav-tabs .nav-item .nav-link:hover,
.bodywebsite .tabs-vertical.tabs-minimal .nav-tabs .nav-item .nav-link.resp-tab-active {
  border-right-color: #00030a;
}
.bodywebsite .tabs-vertical.tabs-minimal .nav-tabs .nav-item + .nav-item {
  margin-top: 16px;
}
@media (min-width: 768px) {
  .bodywebsite .tabs-custom.tabs-corporate .nav-tabs .nav-item .nav-link {
	font-size: 16px;
	line-height: 26px;
  }
  .bodywebsite .tabs-custom.tabs-corporate .nav-tabs .nav-item .nav-link:after {
	font-size: 25px;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-corporate .nav-tabs {
	position: relative;
	-webkit-justify-content: center;
	-ms-flex-pack: center;
	justify-content: center;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-corporate .nav-tabs .nav-item {
	-webkit-flex-grow: 1;
	-ms-flex-positive: 1;
	flex-grow: 1;
	-webkit-flex-shrink: 0;
	-ms-flex-negative: 0;
	flex-shrink: 0;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-corporate .nav-tabs .nav-item .nav-link {
	display: block;
	padding: 21px 10px 19px;
  }
  .bodywebsite .tabs-custom.tabs-horizontal.tabs-corporate .tab-content {
	padding: 30px 0 0;
  }
  .bodywebsite .tabs-custom.tabs-vertical {
	display: -ms-flexbox;
	display: -webkit-flex;
	display: flex;
	-webkit-flex-direction: row;
	-ms-flex-direction: row;
	flex-direction: row;
	-webkit-flex-wrap: nowrap;
	-ms-flex-wrap: nowrap;
	flex-wrap: nowrap;
	-webkit-align-items: flex-start;
	-ms-flex-align: start;
	align-items: flex-start;
  }
  .bodywebsite .tabs-custom.tabs-vertical .nav-tabs {
	display: -ms-flexbox;
	display: -webkit-flex;
	display: flex;
	-webkit-flex-direction: column;
	-ms-flex-direction: column;
	flex-direction: column;
	-webkit-flex-wrap: nowrap;
	-ms-flex-wrap: nowrap;
	flex-wrap: nowrap;
	-webkit-align-items: stretch;
	-ms-flex-align: stretch;
	align-items: stretch;
	-webkit-flex-shrink: 0;
	-ms-flex-negative: 0;
	flex-shrink: 0;
	max-width: 50%;
  }
  .bodywebsite .tabs-custom.tabs-vertical .nav-tabs .nav-item {
	width: 100%;
  }
  .bodywebsite .tabs-custom.tabs-vertical .nav-tabs .nav-item .nav-link {
	text-align: left;
  }
  .bodywebsite .tabs-custom.tabs-vertical .nav-tabs .nav-item .nav-link:hover,
  .bodywebsite .tabs-custom.tabs-vertical .nav-tabs .nav-item .active.nav-link {
	border-color: #3a3c3e;
  }
  .bodywebsite .tabs-custom.tabs-vertical .nav-tabs .nav-item .nav-link:hover:after,
  .bodywebsite .tabs-custom.tabs-vertical .nav-tabs .nav-item .active.nav-link:after {
	right: 15px;
  }
  .bodywebsite .tabs-custom.tabs-vertical .tab-content {
	-webkit-flex-grow: 1;
	-ms-flex-positive: 1;
	flex-grow: 1;
  }
  .bodywebsite .tabs-custom.tabs-vertical.tabs-corporate .nav-tabs {
	width: auto;
	min-width: 240px;
	border-width: 0 0 1px 0;
  }
  .bodywebsite .tabs-custom.tabs-vertical.tabs-corporate .nav-tabs .nav-item {
	margin: 0;
  }
  .bodywebsite .tabs-custom.tabs-vertical.tabs-corporate .nav-tabs .nav-item .nav-link {
	padding: 23px 44px 22px 30px;
	border-width: 1px 0 0 0;
	text-align: left;
  }
  .bodywebsite .tabs-custom.tabs-vertical.tabs-corporate .nav-tabs .nav-item .nav-link:after {
	position: absolute;
	top: 50%;
	transform: translateY(-50%);
	right: 26px;
	content: '\f238';
	transition: .33s all ease;
  }
  .bodywebsite .tabs-custom.tabs-vertical.tabs-corporate .nav-tabs .nav-item .nav-link:hover:after,
  .bodywebsite .tabs-custom.tabs-vertical.tabs-corporate .nav-tabs .nav-item .active.nav-link:after {
	right: 15px;
	top: 50%;
  }
  .bodywebsite .tabs-custom.tabs-vertical.tabs-corporate .tab-content {
	padding: 0 0 0 30px;
	margin-top: -5px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .tabs-custom.tabs-vertical.tabs-corporate .nav-tabs {
	min-width: 300px;
  }
  .bodywebsite .tabs-custom.tabs-vertical.tabs-corporate .tab-content {
	padding: 0 0 0 44px;
  }
}
.bodywebsite .card-group.card-group-custom {
  margin-bottom: 0;
}
.bodywebsite .card-group.card-group-custom .card-heading + .card-collapse > .card-body,
.bodywebsite .card-group.card-group-custom .card-heading + .card-collapse > .list-group {
  border-top: 0;
}
.bodywebsite .card-group.card-group-custom .card + .card {
  margin-top: 0;
}
.bodywebsite .card-group.card-group-corporate .card + .card {
  margin-top: 30px;
}
.bodywebsite .card-custom {
  margin: 0;
  background: inherit;
  border: 0;
  border-radius: 0;
  box-shadow: none;
}
.bodywebsite .card-custom a {
  display: block;
}
.bodywebsite .card-custom .card-heading {
  padding: 0;
  border-bottom: 0;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
}
.bodywebsite .card-custom .card-body {
  padding: 0;
  border: 0;
}
.bodywebsite * + .card-group-custom {
  margin-top: 35px;
}
@media (min-width: 768px) {
  .bodywebsite * + .card-group-custom {
	margin-top: 50px;
  }
}
.bodywebsite .card-light:first-child .card-title {
  border-top: 1px solid #dedede;
}
.bodywebsite .card-light .card-title {
  border-bottom: 1px solid #dedede;
}
.bodywebsite .card-light .card-title:nth-child(n + 2) {
  margin-top: -1px;
}
.bodywebsite .card-light .card-title a {
  position: relative;
  padding: 24px 55px 22px 32px;
  font: 500 18px/24px "Roboto", Helvetica, Arial, sans-serif;
  color: #000;
  transition: 1.5s all ease;
}
.bodywebsite .card-light .card-title a .card-arrow:after {
  opacity: 0;
  visibility: hidden;
}
.bodywebsite .card-light .card-title a.collapsed .card-arrow:after {
  opacity: 1;
  visibility: visible;
}
.bodywebsite .card-light .card-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  right: 26px;
  transition: .33s;
  will-change: transform;
}
.bodywebsite .card-light .card-arrow:before,
.bodywebsite .card-light .card-arrow:after {
  content: '';
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .card-light .card-arrow:before {
  width: 14px;
  height: 2px;
  right: 0;
}
.bodywebsite .card-light .card-arrow:after {
  width: 2px;
  height: 14px;
  right: 6px;
  transition: .2s all ease;
}
.bodywebsite .card-light .card-collapse {
  position: relative;
  margin-top: -1px;
  border-bottom: 1px solid #dedede;
  color: #9f9f9f;
  will-change: transform;
}
.bodywebsite .card-light .card-body {
  padding: 25px 44px 25px 32px;
}
@media (max-width: 767px) {
  .bodywebsite .card-light .card-title a,
  .bodywebsite .card-light .card-body {
	padding-left: 15px;
  }
}
.bodywebsite .card-corporate .card-title a,
.bodywebsite .card-corporate .card-collapse {
  background: #fff;
  box-shadow: -1px 0px 10px 0px rgba(65, 65, 65, 0.12);
}
.bodywebsite .card-corporate .card-collapse.in {
  box-shadow: -1px 0 5px 0 rgba(65, 65, 65, 0.12);
}
.bodywebsite .card-corporate .card-collapse.in:before {
  content: '';
  position: absolute;
  top: -1px;
  height: 1px;
  background: #ededed;
  left: 0;
  width: 100%;
}
.bodywebsite .card-corporate .card-title a {
  position: relative;
  z-index: 1;
  padding: 24px 82px 22px 32px;
  font: 500 18px/24px "Roboto", Helvetica, Arial, sans-serif;
  color: #000;
  transition: 1.3s all ease;
  letter-spacing: -0.025em;
  border-radius: 6px 6px 0 0;
}
.bodywebsite .card-corporate .card-title a .card-arrow:after {
  opacity: 0;
  visibility: hidden;
}
.bodywebsite .card-corporate .card-title a.collapsed {
  border-radius: 6px;
}
.bodywebsite .card-corporate .card-title a.collapsed .card-arrow {
  border-radius: 0 6px 6px 0;
}
.bodywebsite .card-corporate .card-title a.collapsed .card-arrow:after {
  opacity: 1;
  visibility: visible;
}
.bodywebsite .card-corporate .card-arrow {
  position: absolute;
  top: 0;
  bottom: 0;
  right: 0;
  z-index: 2;
  width: 70px;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  border-radius: 0 6px 0 0;
  transition: 1.3s all ease;
}
.bodywebsite .card-corporate .card-arrow:before,
.bodywebsite .card-corporate .card-arrow:after {
  content: '';
  position: absolute;
  top: 50%;
  z-index: 4;
  transform: translateY(-50%);
  background: #fff;
}
.bodywebsite .card-corporate .card-arrow:before {
  width: 14px;
  height: 2px;
  right: 28px;
}
.bodywebsite .card-corporate .card-arrow:after {
  width: 2px;
  height: 14px;
  right: 34px;
}
.bodywebsite .card-corporate .card-collapse {
  position: relative;
  z-index: 2;
  color: #9f9f9f;
  border-radius: 0 0 6px 6px;
}
.bodywebsite .card-corporate .card-body {
  padding: 25px 44px 25px 32px;
}
@media (max-width: 767px) {
  .bodywebsite .card-corporate .card-title a,
  .bodywebsite .card-corporate .card-body {
	padding-left: 25px;
  }
}
.bodywebsite .card-lg {
  position: relative;
  padding: 50px 15px;
}
.bodywebsite .card-lg:before {
  content: '';
  position: absolute;
  top: -45px;
  left: 50%;
  width: 55px;
  height: 55px;
  margin-left: -10px;
  background: #fff;
  transform: translateX(-50%) rotate(-45deg);
}
@media (min-width: 768px) {
  .bodywebsite .card-lg {
	padding: 90px 35px 100px;
  }
}
.bodywebsite table {
  background-color: transparent;
}
.bodywebsite caption {
  padding-top: 17px 25px 18px;
  padding-bottom: 17px 25px 18px;
  color: #dedede;
  text-align: left;
}
.bodywebsite th {
  text-align: left;
}
.bodywebsite .table {
  width: 100%;
  max-width: 100%;
  margin-bottom: 0;
  color: #00030a;
}
.bodywebsite .table > thead > tr > th,
.bodywebsite .table > thead > tr > td,
.bodywebsite .table > tbody > tr > th,
.bodywebsite .table > tbody > tr > td,
.bodywebsite .table > tfoot > tr > th,
.bodywebsite .table > tfoot > tr > td {
  line-height: 1.71429;
  vertical-align: top;
  border-top: 0;
}
.bodywebsite .table > tbody > tr > th,
.bodywebsite .table > tbody > tr > td,
.bodywebsite .table > tfoot > tr > th,
.bodywebsite .table > tfoot > tr > td {
  padding: 17px 25px 18px;
  line-height: 1.71429;
  vertical-align: top;
  border-bottom: 1px solid #d9d9d9;
}
.bodywebsite .table > thead > tr > th {
  font-family: "Playfair Display", Helvetica, Arial, sans-serif;
  font-size: 16px;
  font-weight: 700;
  padding: 26px 25px;
  vertical-align: bottom;
  background: #f6f7fa;
  border-bottom: 0;
}
@media (min-width: 576px) {
  .bodywebsite .table > thead > tr > th {
	padding: 34px 25px 29px;
  }
}
.bodywebsite .table > tfoot > tr > td {
  font-weight: 700;
}
.bodywebsite .table > caption + thead > tr:first-child > th,
.bodywebsite .table > caption + thead > tr:first-child > td,
.bodywebsite .table > colgroup + thead > tr:first-child > th,
.bodywebsite .table > colgroup + thead > tr:first-child > td,
.bodywebsite .table > thead:first-child > tr:first-child > th,
.bodywebsite .table > thead:first-child > tr:first-child > td {
  border-top: 0;
}
.bodywebsite .table > tbody + tbody {
  border-top: 0;
}
.bodywebsite .table .table {
  background-color: #fff;
}
.bodywebsite .table-condensed > thead > tr > th,
.bodywebsite .table-condensed > thead > tr > td,
.bodywebsite .table-condensed > tbody > tr > th,
.bodywebsite .table-condensed > tbody > tr > td,
.bodywebsite .table-condensed > tfoot > tr > th,
.bodywebsite .table-condensed > tfoot > tr > td {
  padding: 5px;
}
.bodywebsite .table-bordered {
  border: 1px solid #d9d9d9;
}
.bodywebsite .table-bordered > thead > tr > th,
.bodywebsite .table-bordered > thead > tr > td,
.bodywebsite .table-bordered > tbody > tr > th,
.bodywebsite .table-bordered > tbody > tr > td,
.bodywebsite .table-bordered > tfoot > tr > th,
.bodywebsite .table-bordered > tfoot > tr > td {
  border: 1px solid #d9d9d9;
}
.bodywebsite .table-bordered > thead > tr > th,
.bodywebsite .table-bordered > thead > tr > td {
  border-bottom-width: 2px;
}
.bodywebsite .table-primary {
  background: #fff;
}
.bodywebsite .table-primary thead > tr > th {
  color: #fff;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .table-striped > tbody > tr:nth-of-type(even) {
  background-color: #f6f7fa;
}
.bodywebsite .table-striped > tbody > tr:nth-of-type(odd) {
  background-color: #fff;
}
.bodywebsite .table-striped > tbody > tr > td {
  border-bottom: 0;
}
.bodywebsite .table-hover > tbody > tr:hover {
  background-color: #f6f7fa;
}
.bodywebsite table col[class*="col-"] {
  position: static;
  float: none;
  display: table-column;
}
.bodywebsite table td[class*="col-"],
.bodywebsite table th[class*="col-"] {
  position: static;
  float: none;
  display: table-cell;
}
.bodywebsite .table-active,
.bodywebsite .table-active > th,
.bodywebsite .table-active > td {
  background-color: #f6f7fa;
}
.bodywebsite .table-hover .table-active:hover {
  background-color: #e6e8f1;
}
.bodywebsite .table-hover .table-active:hover > td,
.bodywebsite .table-hover .table-active:hover > th {
  background-color: #e6e8f1;
}
.bodywebsite .table-success,
.bodywebsite .table-success > th,
.bodywebsite .table-success > td {
  background-color: #dff0d8;
}
.bodywebsite .table-hover .table-success:hover {
  background-color: #d0e9c6;
}
.bodywebsite .table-hover .table-success:hover > td,
.bodywebsite .table-hover .table-success:hover > th {
  background-color: #d0e9c6;
}
.bodywebsite .table-info,
.bodywebsite .table-info > th,
.bodywebsite .table-info > td {
  background-color: #d9edf7;
}
.bodywebsite .table-hover .table-info:hover {
  background-color: #c4e3f3;
}
.bodywebsite .table-hover .table-info:hover > td,
.bodywebsite .table-hover .table-info:hover > th {
  background-color: #c4e3f3;
}
.bodywebsite .table-warning,
.bodywebsite .table-warning > th,
.bodywebsite .table-warning > td {
  background-color: #fcf8e3;
}
.bodywebsite .table-hover .table-warning:hover {
  background-color: #faf2cc;
}
.bodywebsite .table-hover .table-warning:hover > td,
.bodywebsite .table-hover .table-warning:hover > th {
  background-color: #faf2cc;
}
.bodywebsite .table-danger,
.bodywebsite .table-danger > th,
.bodywebsite .table-danger > td {
  background-color: #fe4a21;
}
.bodywebsite .table-hover .table-danger:hover {
  background-color: #fe3508;
}
.bodywebsite .table-hover .table-danger:hover > td,
.bodywebsite .table-hover .table-danger:hover > th {
  background-color: #fe3508;
}
.bodywebsite .table-responsive {
  overflow-x: auto;
  min-height: 0.01%;
}
@media (max-width: 575px) {
  .bodywebsite .table-responsive {
	width: 100%;
	margin-bottom: 1.28571;
	overflow-y: hidden;
	-ms-overflow-style: -ms-autohiding-scrollbar;
	border: 1px solid #d9d9d9;
  }
  .bodywebsite .table-responsive > .table {
	margin-bottom: 0;
  }
  .bodywebsite .table-responsive > .table > thead > tr > th,
  .bodywebsite .table-responsive > .table > thead > tr > td,
  .bodywebsite .table-responsive > .table > tbody > tr > th,
  .bodywebsite .table-responsive > .table > tbody > tr > td,
  .bodywebsite .table-responsive > .table > tfoot > tr > th,
  .bodywebsite .table-responsive > .table > tfoot > tr > td {
	white-space: nowrap;
  }
  .bodywebsite .table-responsive > .table-bordered {
	border: 0;
  }
  .bodywebsite .table-responsive > .table-bordered > thead > tr > th:first-child,
  .bodywebsite .table-responsive > .table-bordered > thead > tr > td:first-child,
  .bodywebsite .table-responsive > .table-bordered > tbody > tr > th:first-child,
  .bodywebsite .table-responsive > .table-bordered > tbody > tr > td:first-child,
  .bodywebsite .table-responsive > .table-bordered > tfoot > tr > th:first-child,
  .bodywebsite .table-responsive > .table-bordered > tfoot > tr > td:first-child {
	border-left: 0;
  }
  .bodywebsite .table-responsive > .table-bordered > thead > tr > th:last-child,
  .bodywebsite .table-responsive > .table-bordered > thead > tr > td:last-child,
  .bodywebsite .table-responsive > .table-bordered > tbody > tr > th:last-child,
  .bodywebsite .table-responsive > .table-bordered > tbody > tr > td:last-child,
  .bodywebsite .table-responsive > .table-bordered > tfoot > tr > th:last-child,
  .bodywebsite .table-responsive > .table-bordered > tfoot > tr > td:last-child {
	border-right: 0;
  }
  .bodywebsite .table-responsive > .table-bordered > tbody > tr:last-child > th,
  .bodywebsite .table-responsive > .table-bordered > tbody > tr:last-child > td,
  .bodywebsite .table-responsive > .table-bordered > tfoot > tr:last-child > th,
  .bodywebsite .table-responsive > .table-bordered > tfoot > tr:last-child > td {
	border-bottom: 0;
  }
}
.bodywebsite .jumbotron-custom {
  font-weight: 900;
  font-size: 35px;
  line-height: 1.2;
  letter-spacing: .01em;
}
.bodywebsite .jumbotron-custom > span {
  font-size: 31px;
  line-height: 1.2;
}
@media (min-width: 768px) {
  .bodywebsite .jumbotron-custom {
	font-size: 45px;
  }
  .bodywebsite .jumbotron-custom > span {
	font-size: 41px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .jumbotron-custom {
	font-size: 55px;
  }
  .bodywebsite .jumbotron-custom > span {
	font-size: 51px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .jumbotron-custom {
	font-size: 65px;
  }
  .bodywebsite .jumbotron-custom > span {
	font-size: 61px;
  }
}
.bodywebsite [class^="thin-icon-"]:before,
.bodywebsite [class*=" thin-icon-"]:before,
.bodywebsite .thin-ico {
  font-family: "Thin Regular";
  font-weight: 400;
  font-style: normal;
  font-size: inherit;
  text-transform: none;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
.bodywebsite .thin-icon-volume-on:before {
  content: '\e800';
}
.bodywebsite .thin-icon-gift:before {
  content: '\e801';
}
.bodywebsite .thin-icon-cup:before {
  content: '\e802';
}
.bodywebsite .thin-icon-folder:before {
  content: '\e803';
}
.bodywebsite .thin-icon-dublicate:before {
  content: '\e804';
}
.bodywebsite .thin-icon-tag:before {
  content: '\e805';
}
.bodywebsite .thin-icon-chat:before {
  content: '\e806';
}
.bodywebsite .thin-icon-clock:before {
  content: '\e807';
}
.bodywebsite .thin-icon-microphone:before {
  content: '\e808';
}
.bodywebsite .thin-icon-map-marker:before {
  content: '\e809';
}
.bodywebsite .thin-icon-mobile:before {
  content: '\e80a';
}
.bodywebsite .thin-icon-cloud-charge:before {
  content: '\e80b';
}
.bodywebsite .thin-icon-resize:before {
  content: '\e80c';
}
.bodywebsite .thin-icon-cake:before {
  content: '\e80d';
}
.bodywebsite .thin-icon-case:before {
  content: '\e80e';
}
.bodywebsite .thin-icon-address:before {
  content: '\e80f';
}
.bodywebsite .thin-icon-phone-support:before {
  content: '\e810';
}
.bodywebsite .thin-icon-fullscreen:before {
  content: '\e811';
}
.bodywebsite .thin-icon-db:before {
  content: '\e812';
}
.bodywebsite .thin-icon-music:before {
  content: '\e813';
}
.bodywebsite .thin-icon-network:before {
  content: '\e814';
}
.bodywebsite .thin-icon-db-network:before {
  content: '\e815';
}
.bodywebsite .thin-icon-dropbox-upload:before {
  content: '\e816';
}
.bodywebsite .thin-icon-phone-call:before {
  content: '\e817';
}
.bodywebsite .thin-icon-briefcase-2:before {
  content: '\e818';
}
.bodywebsite .thin-icon-card:before {
  content: '\e819';
}
.bodywebsite .thin-icon-support:before {
  content: '\e81a';
}
.bodywebsite .thin-icon-pull:before {
  content: '\e81b';
}
.bodywebsite .thin-icon-desktop:before {
  content: '\e81c';
}
.bodywebsite .thin-icon-pass:before {
  content: '\e81d';
}
.bodywebsite .thin-icon-picture:before {
  content: '\e81e';
}
.bodywebsite .thin-icon-email:before {
  content: '\e81f';
}
.bodywebsite .thin-icon-push:before {
  content: '\e820';
}
.bodywebsite .thin-icon-house:before {
  content: '\e821';
}
.bodywebsite .thin-icon-download:before {
  content: '\e822';
}
.bodywebsite .thin-icon-storage:before {
  content: '\e823';
}
.bodywebsite .thin-icon-milk:before {
  content: '\e824';
}
.bodywebsite .thin-icon-external-right:before {
  content: '\e825';
}
.bodywebsite .thin-icon-email-open:before {
  content: '\e826';
}
.bodywebsite .thin-icon-planet:before {
  content: '\e827';
}
.bodywebsite .thin-icon-pointer:before {
  content: '\e828';
}
.bodywebsite .thin-icon-email-search:before {
  content: '\e829';
}
.bodywebsite .thin-icon-external-left:before {
  content: '\e82a';
}
.bodywebsite .thin-icon-shirt:before {
  content: '\e82b';
}
.bodywebsite .thin-icon-document-edit:before {
  content: '\e82c';
}
.bodywebsite .thin-icon-document-delete:before {
  content: '\e82d';
}
.bodywebsite .thin-icon-money:before {
  content: '\e82e';
}
.bodywebsite .thin-icon-eye:before {
  content: '\e82f';
}
.bodywebsite .thin-icon-settings:before {
  content: '\e830';
}
.bodywebsite .thin-icon-arrow-bottom-right:before {
  content: '\e831';
}
.bodywebsite .thin-icon-arrow-right:before {
  content: '\e832';
}
.bodywebsite .thin-icon-flag:before {
  content: '\e833';
}
.bodywebsite .thin-icon-star:before {
  content: '\e834';
}
.bodywebsite .thin-icon-calculator:before {
  content: '\e835';
}
.bodywebsite .thin-icon-safe:before {
  content: '\e836';
}
.bodywebsite .thin-icon-cart:before {
  content: '\e837';
}
.bodywebsite .thin-icon-bullhorn:before {
  content: '\e838';
}
.bodywebsite .thin-icon-anchor:before {
  content: '\e839';
}
.bodywebsite .thin-icon-globe:before {
  content: '\e83a';
}
.bodywebsite .thin-icon-statistics:before {
  content: '\e83b';
}
.bodywebsite .thin-icon-thumb-up:before {
  content: '\e83c';
}
.bodywebsite .thin-icon-headphones:before {
  content: '\e83d';
}
.bodywebsite .thin-icon-bell:before {
  content: '\e83e';
}
.bodywebsite .thin-icon-study:before {
  content: '\e83f';
}
.bodywebsite .thin-icon-cart-add:before {
  content: '\e840';
}
.bodywebsite .thin-icon-cart-delete:before {
  content: '\e841';
}
.bodywebsite .thin-icon-satelite:before {
  content: '\e842';
}
.bodywebsite .thin-icon-home:before {
  content: '\e843';
}
.bodywebsite .thin-icon-time:before {
  content: '\e844';
}
.bodywebsite .thin-icon-book:before {
  content: '\e845';
}
.bodywebsite .thin-icon-bookmark:before {
  content: '\e846';
}
.bodywebsite .thin-icon-key:before {
  content: '\e847';
}
.bodywebsite .thin-icon-timer:before {
  content: '\e848';
}
.bodywebsite .thin-icon-saturn:before {
  content: '\e849';
}
.bodywebsite .thin-icon-notes:before {
  content: '\e84a';
}
.bodywebsite .thin-icon-ambulance:before {
  content: '\e84b';
}
.bodywebsite .thin-icon-briefcase:before {
  content: '\e84c';
}
.bodywebsite .thin-icon-layers:before {
  content: '\e84d';
}
.bodywebsite .thin-icon-delivery:before {
  content: '\e84e';
}
.bodywebsite .thin-icon-tint:before {
  content: '\e84f';
}
.bodywebsite .thin-icon-trash:before {
  content: '\e850';
}
.bodywebsite .thin-icon-lightbulb:before {
  content: '\e851';
}
.bodywebsite .thin-icon-calendar:before {
  content: '\e852';
}
.bodywebsite .thin-icon-chart:before {
  content: '\e853';
}
.bodywebsite .thin-icon-documents:before {
  content: '\e854';
}
.bodywebsite .thin-icon-checklist:before {
  content: '\e855';
}
.bodywebsite .thin-icon-camera-web:before {
  content: '\e856';
}
.bodywebsite .thin-icon-camera:before {
  content: '\e857';
}
.bodywebsite .thin-icon-lock:before {
  content: '\e858';
}
.bodywebsite .thin-icon-umbrella:before {
  content: '\e859';
}
.bodywebsite .thin-icon-user:before {
  content: '\e85a';
}
.bodywebsite .thin-icon-love:before {
  content: '\e85b';
}
.bodywebsite .thin-icon-hanger:before {
  content: '\e85c';
}
.bodywebsite .thin-icon-car:before {
  content: '\e85d';
}
.bodywebsite .thin-icon-cloth:before {
  content: '\e85e';
}
.bodywebsite .thin-icon-box:before {
  content: '\e85f';
}
.bodywebsite .thin-icon-attachment:before {
  content: '\e860';
}
.bodywebsite .thin-icon-cd:before {
  content: '\e861';
}
.bodywebsite .thin-icon-love-broken:before {
  content: '\e862';
}
.bodywebsite .thin-icon-volume-off:before {
  content: '\e863';
}
.bodywebsite .slideOutUp {
  -webkit-animation-name: slideOutUp;
  animation-name: slideOutUp;
}
.bodywebsite .counter {
  font: 900 45px/45px "Roboto", Helvetica, Arial, sans-serif;
  margin-bottom: 0;
  color: #fff;
}
.bodywebsite .counter-bold {
  font-weight: 700;
}
.bodywebsite .counter-k:after {
  content: 'k';
}
.bodywebsite * + .counter-title {
  margin-top: 0;
}
.bodywebsite .countdown-default {
  color: #000;
}
.bodywebsite .countdown-default .countdown-section {
  position: relative;
  display: inline-block;
  min-width: 90px;
  padding: 0 10px;
  text-align: center;
}
.bodywebsite .countdown-default .countdown-section > * {
  display: block;
}
.bodywebsite .countdown-default .countdown-section:after {
  position: absolute;
  top: 35%;
  transform: translateY(-35%);
  border-radius: 20px;
  background: #000;
}
.bodywebsite .countdown-default .countdown-section:nth-last-child(n + 3):after {
  content: '';
  right: -2px;
  width: 5px;
  height: 5px;
}
@media (max-width: 767px) {
  .bodywebsite .countdown-default .countdown-section:last-child {
	display: none;
  }
}
.bodywebsite .countdown-default .countdown-amount {
  font-family: Helvetica, Arial, sans-serif;
  font-size: 30px;
  font-weight: 900;
  line-height: 1;
}
.bodywebsite .countdown-default .countdown-period {
  margin-top: 10px;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: -0.025em;
  color: rgba(0, 0, 0, 0.4);
}
.bodywebsite .countdown-default.countdown-inverse .countdown-section:after {
  background: #fff;
}
.bodywebsite .countdown-default.countdown-inverse .countdown-period {
  color: rgba(255, 255, 255, 0.4);
}
@media (min-width: 768px) {
  .bodywebsite .countdown-default .countdown-section {
	min-width: 150px;
  }
  .bodywebsite .countdown-default .countdown-section:not(:last-child):after {
	content: '';
	top: 50%;
	right: -5px;
	transform: translateY(-50%);
	width: 10px;
	height: 10px;
  }
  .bodywebsite .countdown-default .countdown-amount {
	font-size: 50px;
  }
  .bodywebsite .countdown-default .countdown-period {
	font-size: 14px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .countdown-default .countdown-section {
	min-width: 200px;
  }
  .bodywebsite .countdown-default .countdown-amount {
	font-size: 72px;
  }
}
.bodywebsite .countdown-inverse {
  color: #fff;
}
.bodywebsite .owl-carousel .animated {
  -webkit-animation-duration: 1000ms;
  animation-duration: 1000ms;
  -webkit-animation-fill-mode: both;
  animation-fill-mode: both;
}
.bodywebsite .owl-carousel .owl-animated-in {
  z-index: 0;
}
.bodywebsite .owl-carousel .owl-animated-out {
  z-index: 1;
}
.bodywebsite .owl-carousel .fadeOut {
  -webkit-animation-name: fadeOut;
  animation-name: fadeOut;
}
@-webkit-keyframes fadeOut {
  0% {
	opacity: 1;
  }
  100% {
	opacity: 0;
  }
}
@keyframes fadeOut {
  0% {
	opacity: 1;
  }
  100% {
	opacity: 0;
  }
}
.bodywebsite .owl-height {
  -webkit-transition: height 500ms ease-in-out;
  -moz-transition: height 500ms ease-in-out;
  -ms-transition: height 500ms ease-in-out;
  -o-transition: height 500ms ease-in-out;
  transition: height 500ms ease-in-out;
}
.bodywebsite .owl-carousel {
  display: none;
  width: 100%;
  -webkit-tap-highlight-color: transparent;
  /* position relative and z-index fix webkit rendering fonts issue */
  position: relative;
  z-index: 1;
}
.bodywebsite .owl-carousel .owl-stage {
  position: relative;
  -ms-touch-action: pan-Y;
}
.bodywebsite .owl-carousel .owl-stage:after {
  content: ".";
  display: block;
  clear: both;
  visibility: hidden;
  line-height: 0;
  height: 0;
}
.bodywebsite .owl-carousel .owl-stage-outer {
  position: relative;
  overflow: hidden;
  /* fix for flashing background */
  -webkit-transform: translate3d(0px, 0px, 0px);
}
.bodywebsite .owl-carousel .owl-controls .owl-nav .owl-prev,
.bodywebsite .owl-carousel .owl-controls .owl-nav .owl-next,
.bodywebsite .owl-carousel .owl-controls .owl-dot {
  cursor: pointer;
  cursor: hand;
  -webkit-user-select: none;
  -khtml-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}
.bodywebsite .owl-carousel.owl-loaded {
  display: block;
}
.bodywebsite .owl-carousel.owl-loading {
  opacity: 0;
  display: block;
}
.bodywebsite .owl-carousel.owl-hidden {
  opacity: 0;
}
.bodywebsite .owl-carousel .owl-refresh .owl-item {
  display: none;
}
.bodywebsite .owl-carousel .owl-item {
  position: relative;
  min-height: 1px;
  float: left;
  -webkit-tap-highlight-color: transparent;
  -webkit-touch-callout: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}
.bodywebsite .owl-carousel.owl-text-select-on .owl-item {
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}
.bodywebsite .owl-carousel .owl-grab {
  cursor: move;
  cursor: -webkit-grab;
  cursor: grab;
}
.bodywebsite .owl-carousel.owl-rtl {
  direction: rtl;
}
.bodywebsite .owl-carousel.owl-rtl .owl-item {
  float: right;
}
.bodywebsite .no-js .owl-carousel {
  display: block;
}
.bodywebsite .owl-carousel .owl-item .owl-lazy {
  opacity: 0;
  -webkit-transition: opacity 400ms ease;
  -moz-transition: opacity 400ms ease;
  -ms-transition: opacity 400ms ease;
  -o-transition: opacity 400ms ease;
  transition: opacity 400ms ease;
}
.bodywebsite .owl-carousel .owl-video-wrapper {
  position: relative;
  height: 100%;
  background: #000;
}
.bodywebsite .owl-carousel .owl-video-play-icon {
  position: absolute;
  height: 80px;
  width: 80px;
  left: 50%;
  top: 50%;
  margin-left: -40px;
  margin-top: -40px;
  font-family: "Font Awesome 5 Free";
  font-weight: 600;
  font-size: initial;
  cursor: pointer;
  z-index: 1;
  -webkit-transition: scale 100ms ease;
  -moz-transition: scale 100ms ease;
  -ms-transition: scale 100ms ease;
  -o-transition: scale 100ms ease;
  transition: scale 100ms ease;
}
.bodywebsite .owl-carousel .owl-video-play-icon:before {
  content: '\f144';
}
.bodywebsite .owl-carousel .owl-video-play-icon:hover {
  -webkit-transform: scale(1.3);
  transform: scale(1.3);
}
.bodywebsite .owl-carousel .owl-video-playing .owl-video-tn,
.bodywebsite .owl-carousel .owl-video-playing .owl-video-play-icon {
  display: none;
}
.bodywebsite .owl-carousel .owl-video-tn {
  opacity: 0;
  height: 100%;
  background-position: center center;
  background-repeat: no-repeat;
  -webkit-background-size: contain;
  -moz-background-size: contain;
  -o-background-size: contain;
  background-size: contain;
  -webkit-transition: opacity 400ms ease;
  -moz-transition: opacity 400ms ease;
  -ms-transition: opacity 400ms ease;
  -o-transition: opacity 400ms ease;
  transition: opacity 400ms ease;
}
.bodywebsite .owl-carousel .owl-video-frame {
  position: relative;
  z-index: 1;
}
.bodywebsite .owl-carousel .owl-stage {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: stretch;
  -ms-flex-align: stretch;
  align-items: stretch;
}
.bodywebsite .owl-carousel .owl-item {
  float: none;
  display: -webkit-inline-box;
  display: -webkit-inline-flex;
  display: -ms-inline-flexbox;
  display: inline-flex;
  -webkit-flex-grow: 1;
  -ms-flex-positive: 1;
  flex-grow: 1;
  -webkit-flex-shrink: 0;
  -ms-flex-negative: 0;
  flex-shrink: 0;
  -webkit-align-items: stretch;
  -ms-flex-align: stretch;
  align-items: stretch;
}
.bodywebsite .owl-carousel .item {
  width: 100%;
}
.bodywebsite .owl-carousel-center .owl-item {
  -webkit-justify-content: center;
  -ms-flex-pack: center;
  justify-content: center;
}
.bodywebsite .owl-carousel-center .owl-stage {
  -webkit-justify-content: space-around;
  -ms-flex-pack: distribute;
  justify-content: space-around;
}
.bodywebsite .owl-prev,
.bodywebsite .owl-next {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  color: #000;
  transition: .22s;
}
.bodywebsite .owl-prev.disabled,
.bodywebsite .owl-next.disabled {
  opacity: 0;
}
.bodywebsite .owl-prev:hover,
.bodywebsite .owl-next:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .owl-prev {
  left: 0;
}
.bodywebsite .owl-prev:before {
  content: '\e5c4';
}
.bodywebsite .owl-next {
  right: 0;
}
.bodywebsite .owl-next:before {
  content: '\e5c8';
}
.bodywebsite .owl-dots {
  text-align: center;
}
.bodywebsite .owl-dot {
  display: inline-block;
}
.bodywebsite .owl-numbering-default {
  padding-bottom: 15px;
}
.bodywebsite .owl-numbering-default > * {
  display: inline-block;
}
.bodywebsite .owl-numbering-default .numbering-current {
  min-width: 16px;
  font: 700 25px "Roboto", Helvetica, Arial, sans-serif;
  color: #000;
  transition: .33s all ease;
}
.bodywebsite .owl-numbering-default .numbering-separator {
  position: relative;
  display: inline-block;
  margin: 0 10px;
}
.bodywebsite .owl-numbering-default .numbering-separator:after {
  position: absolute;
  top: -23px;
  left: -12px;
  content: '';
  width: 2px;
  height: 51px;
  transform-origin: 50% 75%;
  transform: rotate(30deg);
  background: rgba(0, 0, 0, 0.3);
}
.bodywebsite .owl-numbering-default .numbering-count {
  position: relative;
  top: 19px;
  left: -7px;
  font: 400 18px "Roboto", Helvetica, Arial, sans-serif;
  color: rgba(0, 0, 0, 0.3);
}
.bodywebsite .owl-carousel-inverse .owl-next,
.bodywebsite .owl-carousel-inverse .owl-prev {
  color: #fff;
}
.bodywebsite .owl-carousel-inverse .owl-next:hover,
.bodywebsite .owl-carousel-inverse .owl-prev:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .owl-carousel-inverse .owl-numbering-default .numbering-current {
  color: #fff;
}
.bodywebsite .owl-carousel-inverse .owl-numbering-default .numbering-separator:after {
  background: rgba(255, 255, 255, 0.3);
}
.bodywebsite .owl-carousel-inverse .owl-numbering-default .numbering-count {
  color: rgba(255, 255, 255, 0.3);
}
.bodywebsite .owl-carousel-dark .owl-next,
.bodywebsite .owl-carousel-dark .owl-prev {
  color: #000;
}
.bodywebsite .owl-carousel-dark .owl-next:hover,
.bodywebsite .owl-carousel-dark .owl-prev:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .owl-nav-position-numbering .owl-next,
.bodywebsite .owl-nav-position-numbering .owl-prev {
  top: auto;
  bottom: -53px;
  transform: none;
}
.bodywebsite .owl-nav-position-numbering .owl-prev {
  left: auto;
  right: calc(92%);
}
.bodywebsite .owl-nav-position-numbering .owl-next {
  right: auto;
  left: calc(92%);
}
.bodywebsite .owl-nav-position-numbering + .owl-numbering {
  margin-top: 15px;
}
.bodywebsite .owl-nav-bottom-left .owl-nav {
  margin-top: 15px;
}
.bodywebsite .owl-nav-bottom-left .owl-next,
.bodywebsite .owl-nav-bottom-left .owl-prev {
  display: inline-block;
  position: static;
  top: auto;
  transform: none;
}
.bodywebsite .owl-nav-bottom-left .owl-prev {
  left: auto;
}
.bodywebsite .owl-nav-bottom-left .owl-next {
  right: auto;
  margin-left: 10px;
}
.bodywebsite .owl-style-minimal .item {
  width: 100%;
}
.bodywebsite .owl-style-minimal .item img {
  width: 100%;
}
.bodywebsite .owl-style-minimal .owl-dots {
  margin-top: 10px;
  text-align: center;
}
.bodywebsite .owl-style-minimal .owl-dot {
  width: 8px;
  height: 8px;
  border-radius: 10px;
  background: #dedede;
  transition: .33s all ease;
}
.bodywebsite .owl-style-minimal .owl-dot.active,
.bodywebsite .owl-style-minimal .owl-dot:hover {
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .owl-style-minimal .owl-dot + .owl-dot {
  margin-left: 8px;
}
.bodywebsite .owl-style-minimal-inverse .owl-dot {
  background: #74787C;
}
@media (min-width: 992px) {
  .bodywebsite .owl-spacing-1 {
	padding-right: 60px;
	padding-left: 60px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .owl-spacing-1 {
	padding: 0;
  }
  .bodywebsite .owl-spacing-1 .owl-item {
	padding-right: 41px;
	padding-left: 41px;
  }
  .bodywebsite .owl-spacing-1 .owl-prev {
	left: -6%;
  }
  .bodywebsite .owl-spacing-1 .owl-next {
	right: -6%;
  }
}
.bodywebsite .owl-nav-classic .owl-nav {
  display: none;
}
@media (min-width: 992px) {
  .bodywebsite .owl-nav-classic .owl-dots {
	display: none !important;
  }
  .bodywebsite .owl-nav-classic .owl-nav {
	display: block;
  }
  .bodywebsite .owl-nav-classic .owl-nav .owl-prev,
  .bodywebsite .owl-nav-classic .owl-nav .owl-next {
	top: 39%;
	transform: translateY(-45%);
	width: 45px;
	height: 45px;
	line-height: 45px;
	color: #fff;
	background: rgba(255, 255, 255, 0.2);
	text-align: center;
	font: 400 20px/45px 'fl-flat-icons-set-2';
  }
  .bodywebsite .owl-nav-classic .owl-nav .owl-prev:hover,
  .bodywebsite .owl-nav-classic .owl-nav .owl-next:hover {
	color: #fff;
	background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  }
  .bodywebsite .owl-nav-classic .owl-nav .owl-prev {
	padding-right: 3px;
  }
  .bodywebsite .owl-nav-classic .owl-nav .owl-prev:before {
	position: relative;
	display: inline-block;
	content: '\e015';
	transform: scale(-1, 1);
  }
  .bodywebsite .owl-nav-classic .owl-nav .owl-next {
	padding-left: 3px;
  }
  .bodywebsite .owl-nav-classic .owl-nav .owl-next:before {
	content: '\e015';
  }
}
.bodywebsite .owl-nav-modern .owl-nav {
  display: none;
}
@media (min-width: 1400px) {
  .bodywebsite .owl-nav-modern .owl-dots {
	display: none !important;
  }
  .bodywebsite .owl-nav-modern .owl-nav {
	display: block;
  }
  .bodywebsite .owl-nav-modern .owl-nav .owl-prev:before,
  .bodywebsite .owl-nav-modern .owl-nav .owl-next:before {
	content: '';
  }
  .bodywebsite .owl-nav-modern .owl-nav .owl-prev:hover,
  .bodywebsite .owl-nav-modern .owl-nav .owl-next:hover {
	opacity: 0.5;
  }
  .bodywebsite .owl-nav-modern .owl-nav .owl-prev {
	left: -58px;
  }
  .bodywebsite .owl-nav-modern .owl-nav .owl-next {
	right: -50px;
  }
  .bodywebsite .owl-nav-modern .owl-nav .owl-next {
	-webkit-transform: rotate(180deg);
	transform: rotate(180deg);
  }
}
@-webkit-keyframes rd-navbar-slide-down {
  0% {
	transform: translateY(-100%);
  }
  100% {
	transform: translateY(0);
  }
}

.rd-navbar-group.rd-navbar-search-wrap.toggle-original-elements.active {
	overflow: hidden;
}

.bodywebsite .rd-navbar-wrap,
.bodywebsite .rd-navbar-static .rd-navbar-megamenu,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-megamenu,
.bodywebsite .rd-navbar-static .rd-navbar-inner,
.bodywebsite .rd-navbar-fixed .rd-navbar-nav-wrap,
.bodywebsite .rd-navbar-fixed .rd-navbar-submenu,
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-group,
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle,
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle:before,
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle:after,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search .rd-search,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-group,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search {
  transition: 0.3s all cubic-bezier(0.785, 0.135, 0.15, 0.86);
}
.bodywebsite .rd-navbar,
.bodywebsite .rd-navbar.rd-navbar--is-clone {
  display: none;
}
.bodywebsite .rd-navbar-fixed,
.bodywebsite .rd-navbar-static,
.bodywebsite .rd-navbar-fullwidth,
.bodywebsite .rd-navbar-sidebar {
  display: block;
}
.bodywebsite .rd-navbar--no-transition,
.bodywebsite .rd-navbar--no-transition * {
  transition: none !important;
}
.bodywebsite .rd-navbar-wrap {
  position: relative;
  z-index: 10;
}
.bodywebsite .rd-navbar-wrap,
.bodywebsite .rd-navbar,
.bodywebsite .rd-navbar-brand,
.bodywebsite .rd-navbar-slogan,
.bodywebsite .rd-navbar-dropdown,
.bodywebsite .rd-navbar-megamenu,
.bodywebsite .rd-navbar-collapse-items,
.bodywebsite .brand-name,
.bodywebsite .rd-navbar-nav,
.bodywebsite .rd-navbar-panel,
.bodywebsite .rd-navbar-search-form-input,
.bodywebsite .rd-navbar-search-form-submit,
.bodywebsite .rd-navbar-search-toggle,
.bodywebsite .rd-navbar-live-search-results,
.bodywebsite .rd-navbar-search-form {
  transition: 0.33s all ease-out;
}
.bodywebsite .rd-navbar-collapse-toggle {
  display: inline-block;
  position: relative;
  width: 48px;
  height: 48px;
  line-height: 48px;
  cursor: pointer;
  color: #00030a;
  display: none;
}
.bodywebsite .rd-navbar-collapse-toggle span {
  top: 50%;
  margin-top: -3px;
}
.bodywebsite .rd-navbar-collapse-toggle span,
.bodywebsite .rd-navbar-collapse-toggle span:before,
.bodywebsite .rd-navbar-collapse-toggle span:after {
  position: absolute;
  width: 6px;
  height: 6px;
  line-height: 6px;
  text-align: center;
  background: #00030a;
  left: 50%;
  margin-left: -3px;
  border-radius: 50%;
  transition: .3s all ease;
}
.bodywebsite .rd-navbar-collapse-toggle span:before,
.bodywebsite .rd-navbar-collapse-toggle span:after {
  content: '';
}
.bodywebsite .rd-navbar-collapse-toggle span:before {
  bottom: 100%;
  margin-bottom: 3px;
}
.bodywebsite .rd-navbar-collapse-toggle span:after {
  top: 100%;
  margin-top: 3px;
}
.bodywebsite .rd-navbar-collapse-toggle.active span {
  transform: scale(0.7);
}
.bodywebsite .rd-navbar-collapse-toggle.active span:before {
  transform: translateY(18px);
}
.bodywebsite .rd-navbar-collapse-toggle.active span:after {
  transform: translateY(-18px);
}
.bodywebsite .rd-navbar--has-sidebar body {
  padding-left: 270px;
}
.bodywebsite .rd-navbar--is-stuck {
  border-bottom: 1px solid #e5e7e9;
}
.bodywebsite .rd-navbar.rd-navbar-fixed + .rd-navbar.rd-navbar--is-clone,
.bodywebsite .rd-navbar.rd-navbar-sidebar + .rd-navbar.rd-navbar--is-clone {
  display: none;
}
.bodywebsite .rd-navbar {
  display: none;
  background: #fff;
  box-shadow: none;
}
.bodywebsite .rd-navbar-toggle {
  display: inline-block;
  position: relative;
  width: 48px;
  height: 48px;
  line-height: 48px;
  cursor: pointer;
  color: #000;
  background-color: transparent;
  border: none;
  display: none;
}
.bodywebsite .rd-navbar-toggle span {
  position: relative;
  display: block;
  margin: auto;
  transition: .3s all ease;
}
.bodywebsite .rd-navbar-toggle span:after,
.bodywebsite .rd-navbar-toggle span:before {
  content: "";
  position: absolute;
  left: 0;
  top: -8px;
  transition: .3s all ease;
}
.bodywebsite .rd-navbar-toggle span:after {
  top: 8px;
}
.bodywebsite .rd-navbar-toggle span:after,
.bodywebsite .rd-navbar-toggle span:before,
.bodywebsite .rd-navbar-toggle span {
  width: 24px;
  height: 4px;
  background-color: #000;
  backface-visibility: hidden;
  border-radius: 2px;
}
.bodywebsite .rd-navbar-toggle span {
  transform: rotate(180deg);
}
.bodywebsite .rd-navbar-toggle span:before,
.bodywebsite .rd-navbar-toggle span:after {
  transform-origin: 1.71429px center;
}
.bodywebsite .rd-navbar-toggle.active span {
  transform: rotate(360deg);
}
.bodywebsite .rd-navbar-toggle.active span:before,
.bodywebsite .rd-navbar-toggle.active span:after {
  top: 0;
  width: 15px;
}
.bodywebsite .rd-navbar-toggle.active span:before {
  -webkit-transform: rotate3d(0, 0, 1, -40deg);
  transform: rotate3d(0, 0, 1, -40deg);
}
.bodywebsite .rd-navbar-toggle.active span:after {
  -webkit-transform: rotate3d(0, 0, 1, 40deg);
  transform: rotate3d(0, 0, 1, 40deg);
}
.bodywebsite .rd-navbar-toggle:focus {
  outline: none;
}
.bodywebsite .rd-navbar-brand {
  transition: none !important;
}
.bodywebsite .rd-navbar-brand svg {
  fill: #000;
}
.bodywebsite .rd-navbar-search .rd-navbar-search-toggle,
.bodywebsite .rd-navbar-search .rd-search-submit,
.bodywebsite .buttonwithnoborder {
  background: none;
  border: none;
  display: inline-block;
  padding: 0;
  outline: none;
  outline-offset: 0;
  cursor: pointer;
  -webkit-appearance: none;
}
.bodywebsite .rd-navbar-search .rd-navbar-search-toggle::-moz-focus-inner,
.bodywebsite .rd-navbar-search .rd-search-submit::-moz-focus-inner {
  border: none;
  padding: 0;
}
.bodywebsite .rd-navbar-search .form-input::-ms-clear {
  display: none;
}
.bodywebsite .rd-navbar-search-toggle {
  display: inline-block;
  width: 36px;
  height: 36px;
  text-align: center;
  font-family: "Font Awesome 5 Free";
  font-weight: 600;
  font-size: initial;
}
.bodywebsite .rd-navbar-search-toggle:before {
  content: '\f002';
  position: absolute;
  left: 0;
  top: 0;
}
.bodywebsite .rd-navbar-search-toggle:after {
  display: none;
}
.bodywebsite .rd-navbar-aside {
  pointer-events: none;
}
.bodywebsite .rd-navbar-aside > * {
  pointer-events: auto;
}
.bodywebsite .rd-navbar-aside-toggle {
  display: none;
  pointer-events: auto;
}
.bodywebsite .rd-navbar-static .rd-navbar-search-form-input input,
.bodywebsite .rd-navbar-sidebar .rd-navbar-search-form-input input,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-search-form-input input {
  width: 100%;
  padding: 0 10px;
  font-size: 16px;
}
.bodywebsite .rd-navbar-static:after,
.bodywebsite .rd-navbar-fullwidth:after {
  content: '';
  background: #fff;
}
.bodywebsite .rd-navbar-static .rd-navbar-brand,
.bodywebsite .rd-navbar-static .rd-navbar-nav > li > a,
.bodywebsite .rd-navbar-static .rd-navbar-search-toggle,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-brand,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li > a,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-search-toggle {
  position: relative;
  z-index: 2;
}
.bodywebsite .rd-navbar-static .rd-navbar-inner,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-inner {
  position: relative;
  max-width: 1200px;
  padding-left: 15px;
  padding-right: 15px;
  margin-left: auto;
  margin-right: auto;
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > li > a,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li > a {
  position: relative;
  padding: 5px 0;
  font-size: 13px;
  line-height: 1.2;
  color: #00030a;
  background: transparent;
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > li > a .label,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li > a .label {
  position: absolute;
  left: 0;
  margin: -18px 0 0 0;
}
@media (min-width: 1200px) {
  .bodywebsite .rd-navbar-static .rd-navbar-nav > li > a,
  .bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li > a {
	font-size: 14px;
  }
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > li.active > a,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li.active > a {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  background: transparent;
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > li.focus > a,
.bodywebsite .rd-navbar-static .rd-navbar-nav > li > a:hover,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li.focus > a,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li > a:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  background: transparent;
}
.bodywebsite .rd-navbar-static .rd-navbar-nav .rd-navbar-submenu > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-static .rd-navbar-nav .rd-navbar-submenu > .rd-navbar-megamenu,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav .rd-navbar-submenu > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav .rd-navbar-submenu > .rd-navbar-megamenu {
  opacity: 0;
  visibility: hidden;
  font-size: 14px;
}
.bodywebsite .rd-navbar-static .rd-navbar-nav .rd-navbar-submenu.focus,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav .rd-navbar-submenu.focus {
  opacity: 1;
  visibility: visible;
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > .rd-navbar-submenu > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-static .rd-navbar-nav > .rd-navbar-submenu > .rd-navbar-megamenu,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > .rd-navbar-submenu > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > .rd-navbar-submenu > .rd-navbar-megamenu {
  transform: translateY(30px);
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > .rd-navbar-submenu.opened > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-static .rd-navbar-nav > .rd-navbar-submenu.opened > .rd-navbar-megamenu,
.bodywebsite .rd-navbar-static .rd-navbar-nav > .rd-navbar-submenu.focus > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-static .rd-navbar-nav > .rd-navbar-submenu.focus > .rd-navbar-megamenu,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > .rd-navbar-submenu.opened > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > .rd-navbar-submenu.opened > .rd-navbar-megamenu,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > .rd-navbar-submenu.focus > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > .rd-navbar-submenu.focus > .rd-navbar-megamenu {
  transform: translateY(0);
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > .rd-navbar-submenu .rd-navbar-submenu > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > .rd-navbar-submenu .rd-navbar-submenu > .rd-navbar-dropdown {
  transform: translateX(-20px);
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > .rd-navbar-submenu .rd-navbar-submenu.focus > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-static .rd-navbar-nav > .rd-navbar-submenu .rd-navbar-submenu.opened > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > .rd-navbar-submenu .rd-navbar-submenu.focus > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > .rd-navbar-submenu .rd-navbar-submenu.opened > .rd-navbar-dropdown {
  transform: translateX(0);
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > .rd-navbar-submenu .rd-navbar-submenu.focus > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-static .rd-navbar-nav > .rd-navbar-submenu .rd-navbar-submenu.opened > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > .rd-navbar-submenu .rd-navbar-submenu.focus > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > .rd-navbar-submenu .rd-navbar-submenu.opened > .rd-navbar-dropdown {
  display: block;
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > li,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li {
  display: inline-block;
}
.bodywebsite .rd-navbar-static .rd-navbar-nav li.rd-navbar--has-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav li.rd-navbar--has-dropdown {
  position: relative;
}
.bodywebsite .rd-navbar-static .rd-navbar-nav li.focus > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-static .rd-navbar-nav li.focus > .rd-navbar-megamenu,
.bodywebsite .rd-navbar-static .rd-navbar-nav li.opened > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-static .rd-navbar-nav li.opened > .rd-navbar-megamenu,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav li.focus > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav li.focus > .rd-navbar-megamenu,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav li.opened > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav li.opened > .rd-navbar-megamenu {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > li > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li > .rd-navbar-dropdown {
  position: absolute;
  left: 0;
  z-index: 5;
  display: block;
  margin-top: 27px;
  text-align: left;
  background: #fff;
}
.bodywebsite .rd-navbar-static .rd-navbar-list li,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-list li {
  padding-left: 5px;
  padding-right: 5px;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a,
.bodywebsite .rd-navbar-static .rd-navbar-list > li > a,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-list > li > a {
  position: relative;
  display: block;
  width: 100%;
  padding-left: 0;
  padding-right: 14px;
  font-size: 14px;
  line-height: 1.3;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a,
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a:before,
.bodywebsite .rd-navbar-static .rd-navbar-list > li > a,
.bodywebsite .rd-navbar-static .rd-navbar-list > li > a:before,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a:before,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-list > li > a,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-list > li > a:before {
  transition: .33s all ease;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a:before,
.bodywebsite .rd-navbar-static .rd-navbar-list > li > a:before,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a:before,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-list > li > a:before {
  position: absolute;
  top: 0;
  left: -6px;
  content: '\f105';
  font-family: "Font Awesome 5 Free";
  font-weight: 600;
  font-size: initial;
  line-height: inherit;
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  opacity: 0;
  visibility: hidden;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a:hover,
.bodywebsite .rd-navbar-static .rd-navbar-list > li > a:hover,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a:hover,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-list > li > a:hover {
  padding-left: 14px;
  padding-right: 0;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a:hover:before,
.bodywebsite .rd-navbar-static .rd-navbar-list > li > a:hover:before,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a:hover:before,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-list > li > a:hover:before {
  left: 0;
  opacity: 1;
  visibility: visible;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a,
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a:focus,
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a:active,
.bodywebsite .rd-navbar-static .rd-navbar-list > li > a,
.bodywebsite .rd-navbar-static .rd-navbar-list > li > a:focus,
.bodywebsite .rd-navbar-static .rd-navbar-list > li > a:active,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a:focus,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a:active,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-list > li > a,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-list > li > a:focus,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-list > li > a:active {
  color: #9f9f9f;
  background: transparent;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a:hover,
.bodywebsite .rd-navbar-static .rd-navbar-list > li > a:hover,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a:hover,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-list > li > a:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  background: transparent;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li + li,
.bodywebsite .rd-navbar-static .rd-navbar-list > li + li,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li + li,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-list > li + li {
  margin-top: 14px;
}
@media (min-width: 1200px) {
  .bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a,
  .bodywebsite .rd-navbar-static .rd-navbar-list > li > a,
  .bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a,
  .bodywebsite .rd-navbar-fullwidth .rd-navbar-list > li > a {
	font-size: 16px;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .rd-navbar-static .rd-navbar-dropdown > li + li,
  .bodywebsite .rd-navbar-static .rd-navbar-list > li + li,
  .bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li + li,
  .bodywebsite .rd-navbar-fullwidth .rd-navbar-list > li + li {
	margin-top: 17px;
  }
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown,
.bodywebsite .rd-navbar-static .rd-navbar-megamenu,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-megamenu {
  box-shadow: 0 0 13px 0 rgba(0, 0, 0, 0.13);
  border-top: 2px solid <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown {
  width: 188px;
  padding: 25px 25px 30px;
  margin-left: -32px;
  background: #fff;
}
@media (min-width: 1200px) {
  .bodywebsite .rd-navbar-static .rd-navbar-dropdown,
  .bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown {
	width: 235px;
  }
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown .rd-navbar-dropdown {
  position: absolute;
  left: 100%;
  margin-left: 91px;
  top: -20px;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a {
  display: block;
  width: 100%;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a,
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a:focus,
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a:active,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a:focus,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a:active {
  color: #9f9f9f;
  background: transparent;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a:hover,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  background: transparent;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li.focus > a,
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li.opened > a,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li.focus > a,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li.opened > a {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  background: transparent;
}
.bodywebsite .rd-navbar-static .rd-navbar-megamenu,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-megamenu {
  position: absolute;
  z-index: 4;
  display: table;
  table-layout: fixed;
  width: calc(70%);
  left: 15px;
  max-width: 1200px;
  margin-top: 27px;
  text-align: left;
  background: #fff;
}
.bodywebsite .rd-navbar-static .rd-navbar-megamenu > li,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-megamenu > li {
  position: relative;
  display: table-cell;
  padding: 34px 20px 30px 35px;
}
.bodywebsite .rd-navbar-static .rd-navbar-megamenu > li + li,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-megamenu > li + li {
  border-left: 1px solid #ededed;
}
.bodywebsite .rd-navbar-static .rd-navbar-megamenu * + .rd-megamenu-header,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-megamenu * + .rd-megamenu-header {
  margin-top: 40px;
}
.bodywebsite .rd-navbar-static .rd-navbar-megamenu * + .rd-navbar-list,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-megamenu * + .rd-navbar-list {
  margin-top: 20px;
}
@media (min-width: 1200px) {
  .bodywebsite .rd-navbar-static .rd-navbar-megamenu,
  .bodywebsite .rd-navbar-fullwidth .rd-navbar-megamenu {
	width: 1140px;
  }
  .bodywebsite .rd-navbar-static .rd-navbar-megamenu > li,
  .bodywebsite .rd-navbar-fullwidth .rd-navbar-megamenu > li {
	padding: 44px 25px 50px 45px;
  }
}
.bodywebsite .rd-navbar-static .rd-navbar-submenu-toggle,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-submenu-toggle {
  display: none;
  cursor: pointer;
  z-index: 100;
}
.bodywebsite .rd-navbar-static .rd-navbar-submenu-toggle:hover,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-submenu-toggle:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > li > .rd-navbar-submenu-toggle,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li > .rd-navbar-submenu-toggle {
  display: none;
  -webkit-align-self: center;
  -ms-flex-item-align: center;
  align-self: center;
  width: 24px;
  text-align: center;
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > li > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li > .rd-navbar-submenu-toggle::after {
  content: '\f107';
  position: relative;
  display: inline-block;
  font-family: "Font Awesome 5 Free";
  font-weight: 600;
  font-size: initial;
  text-align: center;
  transition: 0.4s all ease;
  z-index: 2;
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  will-change: transform;
  -webkit-filter: blur(0);
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > li li.focus > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-static .rd-navbar-nav > li li.opened > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-static .rd-navbar-nav > li li > a:hover + .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li li.focus > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li li.opened > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li li > a:hover + .rd-navbar-submenu-toggle::after {
  -webkit-transform: rotate(-90deg);
  transform: rotate(-90deg);
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > li.focus > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-static .rd-navbar-nav > li.opened > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-static .rd-navbar-nav > li > a:hover + .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li.focus > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li.opened > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li > a:hover + .rd-navbar-submenu-toggle::after {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown .rd-navbar-submenu-toggle,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown .rd-navbar-submenu-toggle {
  display: none;
  vertical-align: middle;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown .rd-navbar-submenu-toggle::after {
  top: 1px;
}
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li.focus > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li.opened > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-static .rd-navbar-dropdown > li > a:hover + .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li.focus > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li.opened > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-dropdown > li > a:hover + .rd-navbar-submenu-toggle::after {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .rd-navbar-static.rd-navbar--is-clone,
.bodywebsite .rd-navbar-fullwidth.rd-navbar--is-clone {
  display: none;
}
.bodywebsite .rd-navbar-static.rd-navbar--is-clone.rd-navbar--is-stuck,
.bodywebsite .rd-navbar-fullwidth.rd-navbar--is-clone.rd-navbar--is-stuck {
  display: block;
}
.bodywebsite .rd-navbar-static.rd-navbar--is-stuck,
.bodywebsite .rd-navbar-static.rd-navbar--is-clone,
.bodywebsite .rd-navbar-fullwidth.rd-navbar--is-stuck,
.bodywebsite .rd-navbar-fullwidth.rd-navbar--is-clone {
  position: fixed;
  left: 0;
  top: 0;
  right: 0;
  z-index: 999;
  background: #fff;
}
.bodywebsite .rd-navbar-static.rd-navbar--is-stuck .rd-navbar-megamenu,
.bodywebsite .rd-navbar-static.rd-navbar--is-clone .rd-navbar-megamenu,
.bodywebsite .rd-navbar-fullwidth.rd-navbar--is-stuck .rd-navbar-megamenu,
.bodywebsite .rd-navbar-fullwidth.rd-navbar--is-clone .rd-navbar-megamenu {
  margin-top: 18px;
}
.bodywebsite .rd-navbar-static .rd-navbar-megamenu,
.bodywebsite .rd-navbar-fullwidth .rd-navbar-megamenu {
  position: absolute;
  transform: translateY(30px);
  text-align: left;
  visibility: hidden;
  opacity: 0;
}
.bodywebsite .rd-navbar-static .rd-navbar--has-dropdown,
.bodywebsite .rd-navbar-fullwidth .rd-navbar--has-dropdown {
  position: relative;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-collapse-toggle,
.bodywebsite .rd-navbar-sidebar .rd-navbar-collapse-toggle {
  display: inline-block;
  z-index: 9999;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-dropdown,
.bodywebsite .rd-navbar-sidebar .rd-navbar-dropdown {
  display: block;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-collapse-items,
.bodywebsite .rd-navbar-sidebar .rd-navbar-collapse-items {
  position: absolute;
  width: 260px;
  padding: 25px 15px;
  box-shadow: none;
  color: #00030a;
  background: #fff;
  font-size: 16px;
  line-height: 34px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-collapse-items li > *,
.bodywebsite .rd-navbar-sidebar .rd-navbar-collapse-items li > * {
  vertical-align: middle;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-collapse-items li + li,
.bodywebsite .rd-navbar-sidebar .rd-navbar-collapse-items li + li {
  margin-top: 10px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-collapse-items .icon,
.bodywebsite .rd-navbar-fixed .rd-navbar-collapse-items a,
.bodywebsite .rd-navbar-sidebar .rd-navbar-collapse-items .icon,
.bodywebsite .rd-navbar-sidebar .rd-navbar-collapse-items a {
  display: inline-block;
  font-size: 16px;
  line-height: 30px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-collapse-items .icon,
.bodywebsite .rd-navbar-fixed .rd-navbar-collapse-items a[class*="fa"]:before,
.bodywebsite .rd-navbar-sidebar .rd-navbar-collapse-items .icon,
.bodywebsite .rd-navbar-sidebar .rd-navbar-collapse-items a[class*="fa"]:before {
  display: inline-block;
  width: 30px;
  height: 30px;
  padding-right: 5px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav,
.bodywebsite .rd-navbar-sidebar {
  width: 270px;
  left: 0;
  top: 0;
  font-size: 16px;
  line-height: 34px;
  color: #00030a;
  background: #fff;
  z-index: 998;
}
.bodywebsite .rd-navbar-static-smooth .rd-navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 9999;
}
.bodywebsite .rd-navbar-static {
  display: block;
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > li {
  display: inline-block;
}
.bodywebsite .rd-navbar-static .rd-navbar-nav > li + li {
  margin-left: 10px;
}
.bodywebsite .rd-navbar-static.rd-navbar-default .rd-navbar-search,
.bodywebsite .rd-navbar-static.rd-navbar-corporate-dark .rd-navbar-search {
  position: static;
  z-index: 2;
}
.bodywebsite .rd-navbar-static.rd-navbar-default .rd-navbar-search .rd-search,
.bodywebsite .rd-navbar-static.rd-navbar-corporate-dark .rd-navbar-search .rd-search {
  position: absolute;
  top: -1px;
  right: 4px;
  bottom: 0;
  left: 0;
  z-index: 5;
  opacity: 0;
  visibility: hidden;
}
.bodywebsite .rd-navbar-static.rd-navbar-default .rd-navbar-search .rd-search-submit,
.bodywebsite .rd-navbar-static.rd-navbar-corporate-dark .rd-navbar-search .rd-search-submit {
  width: 39px;
  height: 39px;
  line-height: 38px;
}
.bodywebsite .rd-navbar-static.rd-navbar-default .rd-navbar-search .rd-search-results-live,
.bodywebsite .rd-navbar-static.rd-navbar-corporate-dark .rd-navbar-search .rd-search-results-live {
  padding: 0;
  border: 0;
  background: #fff;
}
.bodywebsite .rd-navbar-static.rd-navbar-default .rd-navbar-search .rd-search-results-live > *,
.bodywebsite .rd-navbar-static.rd-navbar-corporate-dark .rd-navbar-search .rd-search-results-live > * {
  display: none;
  padding: 16px;
  border: 1px solid #e5e7e9;
  border-top: 0;
  border-radius: 0 0 3px 3px;
}
.bodywebsite .rd-navbar-static.rd-navbar-default .rd-navbar-search .form-label,
.bodywebsite .rd-navbar-static.rd-navbar-corporate-dark .rd-navbar-search .form-label {
  border: 0;
}
.bodywebsite .rd-navbar-static.rd-navbar-default .rd-navbar-search.active .rd-search,
.bodywebsite .rd-navbar-static.rd-navbar-corporate-dark .rd-navbar-search.active .rd-search {
  opacity: 1;
  visibility: visible;
  transition: .22s;
}
.bodywebsite .rd-navbar-static.rd-navbar-default .rd-navbar-search.active .rd-search-results-live > *,
.bodywebsite .rd-navbar-static.rd-navbar-corporate-dark .rd-navbar-search.active .rd-search-results-live > * {
  display: block;
}
.bodywebsite .rd-navbar-static.rd-navbar-default .rd-navbar-search-wrap.active .rd-navbar-nav-inner,
.bodywebsite .rd-navbar-static.rd-navbar-corporate-dark .rd-navbar-search-wrap.active .rd-navbar-nav-inner {
  position: relative;
}
.bodywebsite .rd-navbar-static.rd-navbar-default .rd-navbar-search.active + .rd-navbar-nav,
.bodywebsite .rd-navbar-static.rd-navbar-corporate-dark .rd-navbar-search.active + .rd-navbar-nav {
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
}
.bodywebsite .rd-navbar-static.rd-navbar--is-clone {
  display: block;
  transform: translateY(-105%);
  transition: .33s all ease;
}
.bodywebsite .rd-navbar-static.rd-navbar--is-clone.rd-navbar--is-stuck {
  transform: translateY(0);
}
.bodywebsite .rd-navbar-static.rd-navbar--is-clone .rd-navbar-inner,
.bodywebsite .rd-navbar-static.rd-navbar--is-stuck .rd-navbar-inner {
  padding: 13px 30px;
}
.bodywebsite .rd-navbar-static.rd-navbar--is-clone .rd-navbar-nav-wrap,
.bodywebsite .rd-navbar-static.rd-navbar--is-stuck .rd-navbar-nav-wrap {
  margin-top: 0;
}
.bodywebsite .rd-navbar-fullwidth {
  display: block;
  text-align: center;
}
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav {
  width: 100%;
}
.bodywebsite .rd-navbar-fullwidth .rd-navbar-nav > li + li {
  margin-left: 20px;
}
.bodywebsite .rd-navbar-fullwidth.rd-navbar--is-stuck .rd-navbar-panel {
  display: none;
}
.bodywebsite .rd-navbar-fixed {
  display: block;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-brand {
  /*position: fixed; */
  top: 10px;
  left: 64px;
  z-index: 17;
  display: block;
  overflow: hidden;
  text-align: left;
  white-space: nowrap;
  text-overflow: ellipsis;
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: flex-start;
  -ms-flex-pack: start;
  justify-content: flex-start;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-brand .brand-slogan {
  display: none;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-panel {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  /* position: fixed; */
  left: 0;
  top: 0;
  right: 0;
  padding: 4px;
  height: 56px;
  color: #9f9f9f;
  z-index: 999;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-panel:before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  right: 0;
  box-shadow: none;
  border-bottom: 1px solid #e5e7e9;
  background: #fff;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-toggle {
  display: inline-block;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav-wrap {
  position: fixed;
  top: 56px;
  left: 0;
  bottom: -56px;
  z-index: 998;
  width: 270px;
  padding: 0 0 56px;
  color: #fff;
  background: #fff;
  border-right: 1px solid #e5e7e9;
  transform: translateX(-105%);
  pointer-events: none;
  overflow: hidden;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav-wrap.active {
  transform: translateX(0);
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav-inner {
  position: relative;
  z-index: 100000;
  height: 100%;
  padding: 10px 0 20px;
  pointer-events: auto;
  -webkit-overflow-scrolling: touch;
  overflow-x: hidden;
  overflow-y: auto;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav-inner::-webkit-scrollbar {
  width: 4px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav-inner::-webkit-scrollbar-thumb {
  background: white;
  border: none;
  border-radius: 0;
  opacity: .2;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav-inner::-webkit-scrollbar-track {
  background: #fff;
  border: none;
  border-radius: 0;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav {
  display: block;
  font-size: 16px;
  line-height: 26px;
  text-align: left;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li > a {
  display: block;
  font-size: 16px;
  padding: 14px 56px 14px 16px;
  color: #464a4d;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li:hover > a,
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li:hover > a:hover,
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li.focus > a,
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li.focus > a:hover,
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li.active > a,
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li.active > a:hover,
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li.opened > a,
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li.opened > a:hover {
  color: #fff;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li:hover > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li.focus > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li.active > .rd-navbar-submenu-toggle::after,
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li.opened > .rd-navbar-submenu-toggle::after {
  color: #fff;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav > li + li {
  margin-top: 4px;
}
.bodywebsite .rd-navbar-fixed .label-custom {
  position: relative;
  top: -1px;
  display: inline-block;
  margin: 0 0 0 8px;
  font-size: 60%;
  line-height: 1;
  padding: 6px .5em 5px;
  vertical-align: middle;
}
.bodywebsite .iphone .rd-navbar-fixed .label-custom,
.bodywebsite .ipad .rd-navbar-fixed .label-custom,
.bodywebsite .mac .rd-navbar-fixed .label-custom {
  padding: 6px .5em 4px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-dropdown > li > a,
.bodywebsite .rd-navbar-fixed .rd-navbar-list > li > a {
  font-family: "Roboto", Helvetica, Arial, sans-serif;
  font-size: 12px;
  line-height: 1.2;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-megamenu .rd-megamenu-header {
  padding: 0 15px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-megamenu > li {
  padding-top: 15px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-megamenu * + .rd-megamenu-header {
  margin-top: 15px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-megamenu * + .rd-navbar-list {
  margin-top: 10px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fixed .rd-navbar-megamenu {
  display: none;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-submenu {
  position: relative;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-submenu li > a {
  font-size: 14px;
  padding-left: 30px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-submenu .rd-navbar-dropdown li li > a,
.bodywebsite .rd-navbar-fixed .rd-navbar-submenu .rd-navbar-megamenu ul li li > a {
  padding-left: 48px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-submenu.opened > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fixed .rd-navbar-submenu.opened > .rd-navbar-megamenu {
  display: block;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-search,
.bodywebsite .rd-navbar-fixed .rd-navbar-btn-wrap {
  display: block;
  padding: 16px 5px;
}
.bodywebsite .rd-navbar-fixed .rd-search .rd-search-results-live {
  display: none;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-btn-wrap {
  padding: 16px 10px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-btn-wrap .btn {
  width: 100%;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li .rd-navbar-megamenu {
  transition: opacity 0.3s, height 0.4s ease;
  opacity: 0;
  height: 0;
  overflow: hidden;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li.opened > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li.opened > .rd-navbar-megamenu {
  padding: 3px 0;
  opacity: 1;
  height: auto;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li.opened > .rd-navbar-submenu-toggle {
  color: #fff;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-nav li.opened > .rd-navbar-submenu-toggle::after {
  transform: rotate(180deg);
  margin-top: -24px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-submenu-toggle::after {
  content: '\f107';
  position: absolute;
  top: 24px;
  right: 0;
  margin-top: -18px;
  width: 65px;
  height: 44px;
  font-family: "Font Awesome 5 Free";
  font-weight: 600;
  font-size: initial;
  line-height: 42px;
  text-align: center;
  transition: 0.4s all ease;
  z-index: 2;
  cursor: pointer;
  color: #000;
  will-change: transform;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-collapse,
.bodywebsite .rd-navbar-fixed .rd-navbar-search-toggle {
  position: fixed;
  top: 4px;
  height: 48px;
  z-index: 1000;
  background-color: transparent;
  border: none;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-collapse:focus,
.bodywebsite .rd-navbar-fixed .rd-navbar-search-toggle:focus {
  outline: none;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-aside {
  top: 0;
  right: 0;
  width: 100%;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-aside,
.bodywebsite .rd-navbar-fixed .rd-navbar-aside .rd-navbar-aside-toggle {
  position: fixed;
  z-index: 1000;
  display: block;
  height: 48px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-aside.active .rd-navbar-aside-content {
  visibility: visible;
  opacity: 1;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-aside-toggle {
  top: 4px;
  right: 4px;
  display: inline-block;
  position: relative;
  width: 48px;
  height: 48px;
  line-height: 48px;
  cursor: pointer;
  color: #000;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-aside-toggle span {
  top: 50%;
  margin-top: -3px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-aside-toggle span,
.bodywebsite .rd-navbar-fixed .rd-navbar-aside-toggle span:before,
.bodywebsite .rd-navbar-fixed .rd-navbar-aside-toggle span:after {
  position: absolute;
  width: 6px;
  height: 6px;
  line-height: 6px;
  text-align: center;
  background: #000;
  left: 50%;
  margin-left: -3px;
  border-radius: 50%;
  transition: .3s all ease;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-aside-toggle span:before,
.bodywebsite .rd-navbar-fixed .rd-navbar-aside-toggle span:after {
  content: '';
}
.bodywebsite .rd-navbar-fixed .rd-navbar-aside-toggle span:before {
  bottom: 100%;
  margin-bottom: 3px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-aside-toggle span:after {
  top: 100%;
  margin-top: 3px;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-aside-toggle.active span {
  transform: scale(0.7);
}
.bodywebsite .rd-navbar-fixed .rd-navbar-aside-toggle.active span:before {
  transform: translateY(18px);
}
.bodywebsite .rd-navbar-fixed .rd-navbar-aside-toggle.active span:after {
  transform: translateY(-18px);
}
.bodywebsite .rd-navbar-fixed .rd-navbar-aside-content {
  position: absolute;
  top: calc(107%);
  right: 0;
  width: calc(102%);
  padding: 20px 35px;
  margin: 0 -1px;
  pointer-events: auto;
  opacity: 0;
  visibility: hidden;
  transition: 0.23s all ease-out;
}
@media (min-width: 768px) {
  .bodywebsite .rd-navbar-fixed .rd-navbar-aside-content {
	width: auto;
  }
}
.bodywebsite .rd-navbar-fixed.rd-navbar--is-clone {
  display: none;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-fixed--visible {
  display: block;
}
.bodywebsite .rd-navbar-fixed .rd-navbar-fixed--hidden {
  display: none;
}
.bodywebsite html.rd-navbar-fixed-linked .page {
  padding-top: 56px;
}
.bodywebsite html.rd-navbar-sidebar-linked body {
  padding-left: 270px;
}
.bodywebsite .rd-navbar-sidebar {
  position: fixed;
  display: block;
}
.bodywebsite .rd-navbar-sidebar .rd-navbar-nav li:hover > a,
.bodywebsite .rd-navbar-sidebar .rd-navbar-nav li:hover > a:hover,
.bodywebsite .rd-navbar-sidebar .rd-navbar-nav li.focus > a,
.bodywebsite .rd-navbar-sidebar .rd-navbar-nav li.focus > a:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  background: transparent;
}
.bodywebsite .rd-navbar-sidebar .rd-navbar-nav li:hover > .rd-navbar-submenu-toggle,
.bodywebsite .rd-navbar-sidebar .rd-navbar-nav li.focus > .rd-navbar-submenu-toggle {
  color: #fff;
}
.bodywebsite .rd-navbar-sidebar .rd-navbar-nav li:hover > .rd-navbar-submenu-toggle:hover,
.bodywebsite .rd-navbar-sidebar .rd-navbar-nav li.focus > .rd-navbar-submenu-toggle:hover {
  cursor: pointer;
  color: #fff;
}
.bodywebsite .rd-navbar-sidebar .rd-navbar-nav li .rd-navbar-dropdown,
.bodywebsite .rd-navbar-sidebar .rd-navbar-nav li .rd-navbar-megamenu {
  transition: opacity 0.3s, height 0.4s ease;
  opacity: 0;
  height: 0;
  overflow: hidden;
}
.bodywebsite .rd-navbar-sidebar .rd-navbar-nav li.opened > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-sidebar .rd-navbar-nav li.opened > .rd-navbar-megamenu {
  opacity: 1;
  height: auto;
}
.bodywebsite .rd-navbar-sidebar .rd-navbar-nav li.opened > a {
  background: transparent;
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .rd-navbar-sidebar .rd-navbar-nav li.opened > .rd-navbar-submenu-toggle {
  color: #fff;
}
.bodywebsite .rd-navbar-sidebar .rd-navbar-nav li.opened > .rd-navbar-submenu-toggle::after {
  -webkit-transform: rotate(180deg);
  transform: rotate(180deg);
}
.bodywebsite .rd-navbar-sidebar .rd-navbar-submenu-toggle::after {
  content: '\f078';
  position: absolute;
  top: 22px;
  right: 0;
  margin-top: -22px;
  width: 65px;
  height: 44px;
  font-family: "Font Awesome 5 Free";
  font-weight: 600;
  font-size: initial;
  line-height: 42px;
  text-align: center;
  transition: 0.4s all ease;
  z-index: 2;
}
.bodywebsite .rd-navbar-sidebar .rd-navbar-brand {
  text-align: center;
}
.bodywebsite .rd-navbar-sidebar .rd-navbar-collapse-items {
  top: 0;
  left: 0;
  padding-top: 45px;
  transform: scale(0.7);
  transform-origin: 0% 0%;
  opacity: 0;
  visibility: hidden;
}
.bodywebsite .rd-navbar-sidebar .rd-navbar-collapse {
  position: absolute;
  top: 4px;
  left: 4px;
  display: inline-block;
  z-index: 1;
}
.bodywebsite .rd-navbar-sidebar .rd-navbar-collapse.active .rd-navbar-collapse-items {
  opacity: 1;
  visibility: visible;
  transform: scale(1);
}
.bodywebsite .rd-navbar-default .rd-navbar-nav > li > a {
  font-family: "Roboto", Helvetica, Arial, sans-serif;
  font-weight: 700;
  line-height: 1.2;
  text-transform: uppercase;
  color: #00030a;
}
.bodywebsite .rd-navbar-default .rd-navbar-search .form-input,
.bodywebsite .rd-navbar-default .rd-navbar-search .form-label {
  font-size: 16px;
  line-height: 1.3;
  color: #9b9b9b;
}
.bodywebsite .rd-navbar-default .rd-navbar-search .form-label {
  top: 18px;
  left: 22px;
}
.bodywebsite .rd-navbar-default .rd-navbar-search .form-input {
  padding: 7px 45px 10px 22px;
  height: auto;
  min-height: 20px;
  border: 1px solid #e5e7e9;
  border-radius: 3px;
}
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-navbar-search-toggle,
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-search-submit {
  font-size: 25px;
}
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-navbar-search-toggle,
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-navbar-search-toggle:active,
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-navbar-search-toggle:focus,
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-search-submit,
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-search-submit:active,
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-search-submit:focus {
  color: #00030a;
}
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-navbar-search-toggle:hover,
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-search-submit:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-navbar-search-toggle,
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-navbar-search-toggle:before,
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-search-submit,
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-search-submit:before {
  font-family: 'Material Icons';
}
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-navbar-search-toggle {
  position: relative;
}
.bodywebsite .rd-navbar-default .rd-navbar-search .rd-navbar-search-toggle:after {
  content: '\e5cd';
}
.bodywebsite .rd-navbar-default.rd-navbar-fixed .rd-navbar-shop {
  position: fixed;
  top: 15px;
  right: 15px;
  z-index: 1001;
}
.bodywebsite .rd-navbar-default.rd-navbar-fixed .rd-navbar-search .rd-navbar-search-toggle {
  display: none;
}
.bodywebsite .rd-navbar-default.rd-navbar-static .rd-navbar-inner,
.bodywebsite .rd-navbar-default.rd-navbar-static .rd-navbar-group {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
}
.bodywebsite .rd-navbar-default.rd-navbar-static .rd-navbar-inner {
  -webkit-justify-content: space-between;
  -ms-flex-pack: justify;
  justify-content: space-between;
  padding: 44px 15px 42px;
  font-size: 0;
  line-height: 0;
}
.bodywebsite .rd-navbar-default.rd-navbar-static .rd-navbar-panel {
  min-width: 100px;
}
.bodywebsite .rd-navbar-default.rd-navbar-static .rd-navbar-group {
  -webkit-justify-content: flex-end;
  -ms-flex-pack: end;
  justify-content: flex-end;
}
.bodywebsite .rd-navbar-default.rd-navbar-static .rd-navbar-nav-inner {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row-reverse;
  -ms-flex-direction: row-reverse;
  flex-direction: row-reverse;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: flex-start;
  -ms-flex-pack: start;
  justify-content: flex-start;
  margin-right: 12px;
}
.bodywebsite .rd-navbar-default.rd-navbar-static .rd-navbar-nav {
  z-index: 0;
  margin-right: 40px;
  transition: .25s;
}
.bodywebsite .rd-navbar-default.rd-navbar-static .rd-navbar-nav > li + li {
  margin-left: 32px;
}
@media (min-width: 1200px) {
  .bodywebsite .rd-navbar-default.rd-navbar-static .rd-navbar-nav {
	margin-right: 77px;
  }
  .bodywebsite .rd-navbar-default.rd-navbar-static .rd-navbar-nav > li + li {
	margin-left: 48px;
  }
}
.bodywebsite .rd-navbar-default.rd-navbar-static .rd-navbar-toggle .rd-navbar-nav > li .rd-navbar-toggle {
  display: none;
}
.bodywebsite .rd-navbar-default.rd-navbar-static .rd-navbar-nav > li > .rd-navbar-dropdown {
  margin-top: 54px;
}
.bodywebsite .rd-navbar-default.rd-navbar-static.rd-navbar--is-clone .rd-navbar-inner,
.bodywebsite .rd-navbar-default.rd-navbar-static.rd-navbar--is-stuck .rd-navbar-inner {
  padding: 18px 15px;
}
.bodywebsite .rd-navbar-default.rd-navbar-static.rd-navbar--is-clone .rd-navbar-nav > li > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-default.rd-navbar-static.rd-navbar--is-stuck .rd-navbar-nav > li > .rd-navbar-dropdown {
  margin-top: 49px;
}
.bodywebsite .rd-navbar-corporate-dark .rd-navbar-nav > li > a {
  font-weight: 700;
  font-size: 14px;
  letter-spacing: .05em;
  text-transform: uppercase;
}
.bodywebsite .rd-navbar-corporate-dark .rd-navbar-search .form-input,
.bodywebsite .rd-navbar-corporate-dark .rd-navbar-search .form-label {
  font-size: 16px;
  line-height: 1.3;
  color: #9b9b9b;
}
.bodywebsite .rd-navbar-corporate-dark .rd-navbar-search .form-label {
  top: 18px;
  left: 22px;
}
.bodywebsite .rd-navbar-corporate-dark .rd-navbar-search .form-input {
  padding: 7px 45px 7px 22px;
  height: auto;
  min-height: 20px;
  border: 1px solid #e5e7e9;
  border-radius: 3px;
}
.bodywebsite .rd-navbar-corporate-dark .rd-navbar-search .rd-navbar-search-toggle,
.bodywebsite .rd-navbar-corporate-dark .rd-navbar-search .rd-search-submit {
  text-align: center;
  color: #000;
}
.bodywebsite .rd-navbar-corporate-dark .rd-navbar-search .rd-navbar-search-toggle:before,
.bodywebsite .rd-navbar-corporate-dark .rd-navbar-search .rd-search-submit:before {
  position: static;
  display: inline-block;
  font-family: 'fl-bigmug-line';
  font-size: 20px;
}
.bodywebsite .rd-navbar-corporate-dark .rd-navbar-search .rd-navbar-search-toggle:hover,
.bodywebsite .rd-navbar-corporate-dark .rd-navbar-search .rd-search-submit:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .rd-navbar-corporate-dark .rd-navbar-search .rd-navbar-search-toggle:after {
  display: none;
}
.bodywebsite .rd-navbar-corporate-dark .rd-navbar-aside {
  width: 100%;
  font-size: 14px;
  line-height: 1.71429;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-fixed .rd-navbar-aside-content {
  border-bottom: 1px solid #000;
  border-left: 1px solid #000;
  background: #111;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-fixed .rd-navbar-aside .list-units > li + li {
  margin-top: 10px;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-fixed .rd-navbar-aside * + .rd-navbar-aside-group {
  margin-top: 14px;
}
@media (min-width: 576px) {
  .bodywebsite .rd-navbar-corporate-dark.rd-navbar-fixed .rd-navbar-aside-content {
	width: auto;
  }
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-group {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-inner {
  padding: 0;
  font-size: 0;
  line-height: 0;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-panel {
  min-width: 100px;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-aside {
  position: relative;
  z-index: 2;
  background: #3a3c3e;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-aside:after {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  background: inherit;
  width: 102vw;
  z-index: -1;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-aside .rd-navbar-aside-content,
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-aside .rd-navbar-aside-group {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-aside .rd-navbar-aside-content {
  padding: 12px 15px;
  margin-bottom: -5px;
  transform: translateY(-5px);
  -webkit-justify-content: space-between;
  -ms-flex-pack: justify;
  justify-content: space-between;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-aside .rd-navbar-aside-group {
  -webkit-flex-shrink: 0;
  -ms-flex-negative: 0;
  flex-shrink: 0;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-aside .rd-navbar-aside-group:first-child {
  margin-top: 7px;
  -webkit-flex-grow: 8;
  -ms-flex-positive: 8;
  flex-grow: 8;
  -webkit-justify-content: flex-start;
  -ms-flex-pack: start;
  justify-content: flex-start;
  margin-right: 20px;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-aside .rd-navbar-aside-group:last-child {
  margin-top: 5px;
  -webkit-justify-content: flex-end;
  -ms-flex-pack: end;
  justify-content: flex-end;
  -webkit-flex-grow: 1;
  -ms-flex-positive: 1;
  flex-grow: 1;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-aside .list-units li {
  display: inline-block;
  margin-top: 0;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-aside .list-units li:not(:last-child) {
  margin-right: 25px;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-group {
  /* padding: 35px 15px; */
  padding-top: 18px;
  padding-bottom: 18px;
  -webkit-justify-content: space-between;
  -ms-flex-pack: justify;
  justify-content: space-between;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-nav-inner {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row-reverse;
  -ms-flex-direction: row-reverse;
  flex-direction: row-reverse;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: flex-start;
  -ms-flex-pack: start;
  justify-content: flex-start;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-nav {
  margin-right: 23px;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-nav > li {
  padding-left: 5px;
  padding-right: 5px;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-nav > li > a {
  font-size: 13px;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-nav > li.rd-navbar-submenu {
  margin-right: -18px;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-nav > li > .rd-navbar-submenu-toggle {
  display: inline-block;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-nav > li + li {
  margin-left: 32px;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-nav .rd-navbar-dropdown > li {
  padding-left: 5px;
  padding-right: 5px;
}
@media (min-width: 1200px) {
  .bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-nav > li > a {
	font-size: 14px;
  }
  .bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-nav > li + li {
	margin-left: 29px;
  }
  .bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-aside .list-units li:not(:last-child) {
	margin-right: 50px;
  }
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-search {
  position: static;
  z-index: 2;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-search .rd-search {
  position: absolute;
  top: -2px;
  /* right: -2px; */
  bottom: 0;
  left: 0;
  z-index: 5;
  opacity: 0;
  visibility: hidden;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-search .rd-search-submit {
  width: 39px;
  height: 39px;
  line-height: 38px;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-search-wrap.active .rd-navbar-nav-wrap {
  position: relative;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-search.active + .rd-navbar-nav {
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static .rd-navbar-toggle .rd-navbar-nav > li .rd-navbar-toggle {
  display: none;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static.rd-navbar--is-clone .rd-navbar-aside,
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static.rd-navbar--is-stuck .rd-navbar-aside {
  display: none;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static.rd-navbar--is-clone .rd-navbar-group,
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static.rd-navbar--is-stuck .rd-navbar-group {
  padding-top: 18px;
  padding-bottom: 18px;
}
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static.rd-navbar--is-clone .rd-navbar-nav > li > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-corporate-dark.rd-navbar-static.rd-navbar--is-stuck .rd-navbar-nav > li > .rd-navbar-dropdown {
  margin-top: 18px;
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-nav > li > a {
  font: 400 16px "Roboto", Helvetica, Arial, sans-serif;
  letter-spacing: .025em;
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .form-input,
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .form-label {
  font-size: 16px;
  line-height: 1.3;
  color: #9b9b9b;
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .form-label {
  top: 18px;
  left: 22px;
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .form-input {
  padding: 7px 22px 10px;
  height: auto;
  min-height: 20px;
  border: 1px solid #e5e7e9;
  border-radius: 3px;
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle,
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-search-submit {
  text-align: center;
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle:before,
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-search-submit:before {
  font-family: 'fl-bigmug-line';
  position: static;
  display: inline-block;
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle:before,
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle:after {
  font-size: 20px;
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle:before {
  font-family: 'fl-bigmug-line';
  color: #000;
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle:after {
  font-family: 'Material Icons';
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle:hover:before {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle,
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle:before,
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle:after {
  width: 36px;
  height: 36px;
  text-align: center;
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle:before,
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle:after {
  display: block;
  position: absolute;
  left: 0;
  top: 0;
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle:before {
  content: "";
  transform: scale(1) rotate(0deg);
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle:after {
  content: "";
  opacity: 0;
  transform: scale(0) rotate(-90deg);
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle.active:before {
  opacity: 0;
  transform: scale(0) rotate(90deg);
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-search .rd-navbar-search-toggle.active:after {
  opacity: 1;
  transform: scale(1) rotate(0deg);
}
.bodywebsite .rd-navbar-corporate-light .rd-navbar-aside {
  width: 100%;
  font-size: 14px;
  line-height: 1.71429;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search {
  padding: 0;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search .rd-search {
  opacity: 0;
  visibility: hidden;
  position: fixed;
  width: 100%;
  padding: 8px 10px;
  transform: translateY(-80%);
  background: #fff;
  border: 1px solid #e5e7e9;
  border-top: 0;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search.active .rd-search {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search .form-input {
  padding: 7px 46px 10px 22px;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search .rd-navbar-search-toggle,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search .rd-search-submit {
  font-size: 20px;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search .rd-navbar-search-toggle,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search .rd-navbar-search-toggle:active,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search .rd-navbar-search-toggle:focus,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search .rd-search-submit,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search .rd-search-submit:active,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search .rd-search-submit:focus {
  color: #000;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search .rd-navbar-search-toggle:hover,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search .rd-search-submit:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search .rd-navbar-search-toggle {
  position: fixed;
  right: 56px;
  top: 10px;
  z-index: 1000;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-search .rd-search-submit {
  right: 10px;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-aside-content {
  border: 1px solid #e5e7e9;
  background: #fff;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-aside .list-units > li + li {
  margin-top: 10px;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-aside * + .rd-navbar-aside-group {
  margin-top: 14px;
}
@media (min-width: 576px) {
  .bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-aside-content {
	width: auto;
  }
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-fixed .rd-navbar-btn-wrap {
  padding: 16px 5px;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-group {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-inner {
  padding: 0;
  font-size: 0;
  line-height: 0;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-panel {
  min-width: 100px;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-aside {
  position: relative;
  z-index: 100;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-aside-wrap,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-aside .rd-navbar-aside-content,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-aside .rd-navbar-aside-group {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-aside-wrap {
  position: relative;
  z-index: 1001;
  padding: 6px 20px 6px 10px;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-aside-wrap:after {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  border-bottom: 1px solid #e5e7e9;
  width: 101vw;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-aside-wrap > * + * {
  margin-left: 10px;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-aside .rd-navbar-aside-content {
  margin-bottom: -5px;
  transform: translateY(-5px);
  -webkit-justify-content: space-between;
  -ms-flex-pack: justify;
  justify-content: space-between;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-aside .rd-navbar-aside-group {
  -webkit-flex-shrink: 0;
  -ms-flex-negative: 0;
  flex-shrink: 0;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-aside .rd-navbar-aside-group:first-child {
  margin-top: 7px;
  -webkit-flex-grow: 8;
  -ms-flex-positive: 8;
  flex-grow: 8;
  -webkit-justify-content: flex-start;
  -ms-flex-pack: start;
  justify-content: flex-start;
  margin-right: 20px;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-aside .rd-navbar-aside-group:last-child {
  margin-top: 5px;
  -webkit-justify-content: flex-end;
  -ms-flex-pack: end;
  justify-content: flex-end;
  -webkit-flex-grow: 1;
  -ms-flex-positive: 1;
  flex-grow: 1;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-aside .list-units li {
  display: inline-block;
  margin-top: 0;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-aside .list-units li:not(:last-child) {
  margin-right: 30px;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-group {
  padding: 35px 15px;
  -webkit-justify-content: space-between;
  -ms-flex-pack: justify;
  justify-content: space-between;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-nav-inner {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row-reverse;
  -ms-flex-direction: row-reverse;
  flex-direction: row-reverse;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: flex-start;
  -ms-flex-pack: start;
  justify-content: flex-start;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-nav {
  margin-right: 40px;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-nav > li > a {
  font-size: 15px;
  padding: 7px 0;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-nav > li.rd-navbar-submenu {
  margin-right: -24px;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-nav > li > .rd-navbar-submenu-toggle {
  position: relative;
  top: 2px;
  display: inline-block;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-nav > li + li {
  margin-left: 37px;
}
@media (min-width: 1200px) {
  .bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-nav > li > a {
	font-size: 16px;
  }
  .bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-nav > li + li {
	margin-left: 48px;
  }
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-search {
  position: relative;
  z-index: 1500;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-search .form-label {
  -webkit-flex-grow: 1;
  -ms-flex-positive: 1;
  flex-grow: 1;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-search .form-input,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-search .form-label {
  color: #fff;
  font-size: 30px;
  font-weight: 700;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-search .form-label {
  top: 24px;
  left: 0;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-search .form-input {
  padding: 10px 50px 9px 0;
  background-color: transparent;
  border: 0;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-search .btn {
  -webkit-flex-shrink: 0;
  -ms-flex-negative: 0;
  flex-shrink: 0;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  z-index: -1000;
  opacity: 0;
  visibility: hidden;
  background: rgba(0, 0, 0, 0.96);
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-inner {
  width: 540px;
  margin-top: 75px;
  margin-left: auto;
  margin-right: auto;
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  border-bottom: 1px solid #fff;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-submit {
  position: relative;
  left: 0;
  top: 0;
  width: 39px;
  height: 39px;
  font-size: 25px;
  line-height: 39px;
  transform: none;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-submit,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-submit:active,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-submit:focus {
  color: #fff;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-submit:hover {
  color: #ababab;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live {
  position: relative;
  display: block;
  top: auto;
  right: auto;
  bottom: auto;
  left: auto;
  margin-top: 60px;
  margin-left: auto;
  margin-right: auto;
  width: 800px;
  font-size: 20px;
  background-color: transparent;
  opacity: 1;
  visibility: visible;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live > * {
  display: block;
  padding: 0;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .active .search_list li {
  top: 0;
  opacity: 1;
  visibility: visible;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search-quick-result {
  display: none;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list {
  margin: 0;
  background-color: transparent;
  text-align: left;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li {
  position: relative;
  top: 30px;
  display: inline-block;
  width: 48%;
  padding: 0 15px;
  text-align: left;
  transition: 0.5s all ease-in-out;
  opacity: 0;
  visibility: hidden;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list .search_all {
  top: 0;
  margin-top: 40px;
  display: inline-block;
  width: 100%;
  text-align: right;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li:nth-child(0) {
  transition-delay: 0s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li:nth-child(1) {
  transition-delay: 0.15s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li:nth-child(2) {
  transition-delay: 0.3s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li:nth-child(3) {
  transition-delay: 0.45s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li:nth-child(4) {
  transition-delay: 0.6s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li:nth-child(5) {
  transition-delay: 0.75s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li:nth-child(6) {
  transition-delay: 0.9s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li:nth-child(7) {
  transition-delay: 1.05s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li:nth-child(8) {
  transition-delay: 1.2s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li:nth-child(9) {
  transition-delay: 1.35s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li:nth-child(10) {
  transition-delay: 1.5s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li.search_all:nth-child(0) {
  transition-delay: 0s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li.search_all:nth-child(1) {
  transition-delay: 0.2s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li.search_all:nth-child(2) {
  transition-delay: 0.4s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li.search_all:nth-child(3) {
  transition-delay: 0.6s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li.search_all:nth-child(4) {
  transition-delay: 0.8s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li.search_all:nth-child(5) {
  transition-delay: 1s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li.search_all:nth-child(6) {
  transition-delay: 1.2s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li.search_all:nth-child(7) {
  transition-delay: 1.4s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li.search_all:nth-child(8) {
  transition-delay: 1.6s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li.search_all:nth-child(9) {
  transition-delay: 1.8s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_list li.search_all:nth-child(10) {
  transition-delay: 2s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .result-item:only-child {
  top: 0;
  width: 100%;
  text-align: center;
  transition-delay: 0s;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .result-item:nth-child(n + 3) {
  margin-top: 50px;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_title {
  font: 700 30px/26px Helvetica, Arial, sans-serif;
  font-style: italic;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_title a,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_title a:active,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_title a:focus {
  color: #fff;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_title a:hover {
  color: #ababab;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_title + p {
  margin-top: 16px;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_error {
  line-height: 1.35;
  text-align: center;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_submit {
  display: inline-block;
  padding: 10px 35px;
  border: 2px solid;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_submit,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_submit:active,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_submit.active,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_submit:active:focus,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_submit.active:focus,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_submit:focus:active,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_submit:focus {
  color: #fff;
  background-color: transparent;
  border-color: #fff;
}
.bodywebsite .open > .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_submit.dropdown-toggle,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_submit:hover {
  color: #414141;
  background-color: #fff;
  border-color: #fff;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_submit.disabled,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_submit[disabled],
.bodywebsite fieldset[disabled] .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_submit {
  pointer-events: none;
  opacity: .5;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search-results-live .search_submit .badge {
  color: transparent;
  background-color: #fff;
}
@media (min-width: 1600px) and (min-height: 767px) {
  .bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search .rd-search-inner {
	margin-top: 10%;
  }
}
@media (max-height: 767px) {
  .bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search .rd-search-results-live .result-item:nth-child(5),
  .bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search .rd-search-results-live .result-item:nth-child(6) {
	display: none;
  }
  .bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-search .rd-search-results-live .search_list > li.search_all {
	transition-delay: 0.8s;
  }
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-search.active .rd-search {
  display: block;
  z-index: 10000;
  margin: 0;
  opacity: 1;
  visibility: visible;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-search.active .rd-navbar-search-toggle {
  z-index: 10002;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-search.active .rd-navbar-search-toggle:after {
  color: #fff;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-search.active .rd-navbar-search-toggle:hover:after {
  color: #ababab;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static .rd-navbar-toggle .rd-navbar-nav > li .rd-navbar-toggle {
  display: none;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static.rd-navbar--is-clone .rd-navbar-aside-wrap,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static.rd-navbar--is-stuck .rd-navbar-aside-wrap {
  position: absolute;
  top: -60px;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static.rd-navbar--is-clone .rd-navbar-group,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static.rd-navbar--is-stuck .rd-navbar-group {
  padding-top: 17px;
  padding-bottom: 17px;
}
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static.rd-navbar--is-clone .rd-navbar-nav > li > .rd-navbar-dropdown,
.bodywebsite .rd-navbar-corporate-light.rd-navbar-static.rd-navbar--is-stuck .rd-navbar-nav > li > .rd-navbar-dropdown {
  margin-top: 50px;
}
html .bodywebsite.boxed.rd-navbar--has-sidebar body {
  padding-left: 300px;
  padding-right: 30px;
}
html .bodywebsite.boxed .rd-navbar--is-clone {
  max-width: 1920px;
  margin-left: auto;
  margin-right: auto;
}


.bodywebsite .rd-parallax-inner {
  position: relative;
  overflow: hidden;
  -webkit-transform: translate3d(0px, 0px, 0px);
  transform: translate3d(0px, 0px, 0px);
  z-index: 1;
}
.bodywebsite .rd-parallax-layer[data-type="media"] {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  height: 100%;
  pointer-events: none;
}
.bodywebsite .rd-parallax-layer[data-type="media"] iframe {
  width: 100%;
  height: 100%;
}
.bodywebsite .rd-parallax-layer[data-url] {
  -webkit-background-size: cover;
  background-size: cover;
  background-position: center center;
}
.bodywebsite .rd-parallax[class*="rd-parallax-overlay"] {
  background-color: #000;
  color: #fff;
}
.bodywebsite .rd-parallax[class*="rd-parallax-overlay"] .rd-parallax-layer[data-type="media"] {
  opacity: 0.2;
}
.bodywebsite .rd-parallax[class*="rd-parallax-overlay"] .rd-parallax-layer[data-type="media"] + * {
  position: relative;
}
.bodywebsite .rd-parallax.rd-parallax-overlay-2 .rd-parallax-layer[data-type="media"] {
  opacity: 0.8;
}
.bodywebsite .google-map-markers {
  display: none;
}
.bodywebsite .google-map-container {
  width: 100%;
}
.bodywebsite .google-map {
  height: 250px;
  color: #333;
}
.bodywebsite .google-map img {
  max-width: none !important;
}
@media (min-width: 576px) {
  .bodywebsite .google-map {
	height: 250px;
  }
}
@media (min-width: 768px) {
  .bodywebsite .google-map {
	height: 400px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .google-map {
	height: 450px;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .google-map {
	height: 532px;
  }
}
.bodywebsite .rd-search {
  position: relative;
}
.bodywebsite .rd-search .form-wrap {
  display: block;
  margin: 0;
  font-size: 0;
}
.bodywebsite .rd-search label * {
  margin-top: 0;
}
.bodywebsite .rd-search .rd-search-submit {
  top: 19px;
  right: -4px;
  background-color: transparent;
  border: none;
  transform: translateY(-50%);
}
.bodywebsite .rd-search .text-mobile {
  display: block;
}
.bodywebsite .rd-search .text-default {
  display: none;
}
@media (min-width: 768px) {
  .bodywebsite .rd-search .text-mobile {
	display: none;
  }
  .bodywebsite .rd-search .text-default {
	display: block;
  }
}
.bodywebsite .rd-search-submit {
  background: none;
  border: none;
  padding: 0;
  outline: none;
  outline-offset: 0;
  -webkit-appearance: none;
  display: inline-block;
  position: relative;
  width: 48px;
  height: 48px;
  line-height: 48px;
  cursor: pointer;
  color: #00030a;
  text-align: center;
  font-size: 22px;
  position: absolute;
  right: 0;
  transition: color .33s;
}
.bodywebsite .rd-search-submit::-moz-focus-inner {
  border: none;
  padding: 0;
}
.bodywebsite .rd-search-submit:before {
  font-family: "Font Awesome 5 Free";
  font-weight: 600;
  font-size: initial;
}
.bodywebsite .rd-search-submit.active {
  transform: scale(0.7);
}
.bodywebsite .rd-search-submit:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .rd-search-minimal {
  position: relative;
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: row;
  -ms-flex-direction: row;
  flex-direction: row;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: stretch;
  -ms-flex-align: stretch;
  align-items: stretch;
  -webkit-justify-content: center;
  -ms-flex-pack: center;
  justify-content: center;
  padding-left: 40px;
  border-bottom: 1px solid #dedede;
}
.bodywebsite .rd-search-minimal:before {
  content: '\e8b6';
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  left: 10px;
  font: 400 21px 'Material Icons';
  color: #000;
}
.bodywebsite .rd-search-minimal .form-wrap {
  -webkit-flex-grow: 1;
  -ms-flex-positive: 1;
  flex-grow: 1;
}
.bodywebsite .rd-search-minimal .form-label,
.bodywebsite .rd-search-minimal .form-input {
  font: 400 16px/24px "Roboto", Helvetica, Arial, sans-serif;
  color: rgba(0, 0, 0, 0.2);
  letter-spacing: -0.025em;
}
.bodywebsite .rd-search-minimal .form-label {
  top: 29px;
}
.bodywebsite .rd-search-minimal .form-input {
  padding: 17px 20px;
}
.bodywebsite .rd-search-minimal button[type='submit'] {
  padding: 0 20px;
  -webkit-flex-shrink: 0;
  -ms-flex-negative: 0;
  flex-shrink: 0;
}
@media (min-width: 768px) {
  .bodywebsite .rd-search-minimal .form-label,
  .bodywebsite .rd-search-minimal .form-input {
	font-size: 19px;
  }
}
.bodywebsite .rd-search-classic {
  overflow: hidden;
  border: 1px solid #dedede;
  border-radius: 0;
}
.bodywebsite .rd-search-classic .form-input {
  min-height: 50px;
  padding: 13px 50px 15px 19px;
  border: 0;
}
.bodywebsite .rd-search-classic .rd-search-submit {
  position: absolute;
  right: 0;
  top: 0;
  bottom: 0;
  width: 40px;
  line-height: 0;
  height: auto;
  transform: none;
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: center;
  -ms-flex-pack: center;
  justify-content: center;
  text-align: left;
}
.bodywebsite .rd-search-classic .rd-search-submit:before {
  font: 400 25px 'Material Icons';
}
.bodywebsite .search_error {
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
}
.bodywebsite .rd-search-results-live {
  position: absolute;
  left: 0;
  right: 0;
  top: 100%;
  z-index: 998;
  margin: -3px 0 0;
  font-size: 14px;
  line-height: 34px;
  text-align: left;
  color: #9f9f9f;
  opacity: 0;
  visibility: hidden;
}
.bodywebsite .rd-search-results-live > * {
  padding: 16px;
  border: 0px solid #dedede;
  border-top: 0;
}
.bodywebsite .rd-search-results-live .search-quick-result {
  font: 700 14px/24px "Roboto", Helvetica, Arial, sans-serif;
  color: #000;
  letter-spacing: .06em;
  text-transform: uppercase;
}
.bodywebsite .rd-search-results-live .search_list {
  margin-top: 10px;
  font-size: 16px;
  line-height: 30px;
}
.bodywebsite .rd-search-results-live .search_list li + li {
  margin-top: 20px;
}
.bodywebsite .rd-search-results-live .search_list .search_error {
  padding-bottom: 10px;
  font-size: 14px;
  line-height: 1.1;
}
.bodywebsite .rd-search-results-live .search_link,
.bodywebsite .rd-search-results-live .search_link:active,
.bodywebsite .rd-search-results-live .search_link:focus {
  color: #464a4d;
}
.bodywebsite .rd-search-results-live .search_link:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .rd-search-results-live p {
  margin-top: 0;
  font-size: 14px;
  line-height: 1.6;
}
.bodywebsite .rd-search-results-live .search_title {
  margin-bottom: 0;
  font-family: "Roboto", Helvetica, Arial, sans-serif;
  font-size: 14px;
  font-weight: 700;
  color: #000;
}
.bodywebsite .rd-search-results-live .search_submit {
  display: block;
  padding: 6px 20px;
  font-size: 14px;
  font-weight: 700;
  text-align: center;
  color: #fff;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  border-radius: 5px;
  border: 0;
  text-transform: uppercase;
  transition: 0.3s ease-out;
}
.bodywebsite .rd-search-results-live .search_submit:hover {
  color: #fff;
  background: #3a3c3e;
}
.bodywebsite .rd-search-results-live .match {
  display: none;
}
@media (min-width: 1200px) {
  .bodywebsite .rd-search-results-live .search_link p {
	display: block;
  }
}
.bodywebsite .rd-navbar-fixed-linked .rd-search-results-live {
  display: none;
}
.bodywebsite .rd-search-results-live.active.cleared {
  opacity: 0;
  visibility: hidden;
  transition-delay: .08s;
}
.bodywebsite .active .rd-search-results-live {
  display: block;
  opacity: 1;
  visibility: visible;
}
.bodywebsite .rd-search-results .search_list {
  text-align: left;
  padding-left: 0;
  font-size: 14px;
  list-style-type: none;
  counter-reset: result;
}
.bodywebsite .rd-search-results .result-item {
  position: relative;
  padding-left: 40px;
  color: #9f9f9f;
}
.bodywebsite .rd-search-results .result-item:before {
  position: absolute;
  top: -1px;
  left: 0;
  content: counter(result, decimal-leading-zero) ".";
  counter-increment: result;
  font: 500 19px "Roboto", Helvetica, Arial, sans-serif;
  line-height: 1;
  color: #cdcdcd;
}
.bodywebsite .rd-search-results .result-item:only-child:before {
  display: none;
}
.bodywebsite .rd-search-results .search {
  color: #fff;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .rd-search-results .search_error .search {
  color: #9f9f9f;
  background-color: transparent;
}
.bodywebsite .rd-search-results .match em {
  font: 700 12px/16px "Roboto", Helvetica, Arial, sans-serif;
  font-style: normal;
  text-transform: uppercase;
  color: #000;
}
.bodywebsite .rd-search-results * + p {
  margin-top: 10px;
}
.bodywebsite .rd-search-results * + .match {
  margin-top: 5px;
}
.bodywebsite .rd-search-results * + .result-item {
  margin-top: 35px;
}
@media (min-width: 576px) {
  .bodywebsite .rd-search-results .result-item {
	padding-left: 60px;
  }
  .bodywebsite .rd-search-results .result-item:before {
	left: 15px;
	font-size: 19px;
  }
}
@media (min-width: 768px) {
  .bodywebsite .rd-search-results .result-item:before {
	top: 0;
  }
}
@media (min-width: 992px) {
  .bodywebsite .rd-search-results .result-item {
	padding-left: 85px;
  }
  .bodywebsite .rd-search-results .result-item:before {
	top: 0;
	left: 40px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .rd-search-results .result-item {
	padding-left: 100px;
  }
  .bodywebsite .rd-search-results .result-item:before {
	left: 44px;
  }
}
.bodywebsite .twitter-item-minimal .tweet-user {
  font-size: 16px;
  font-weight: 700;
}
.bodywebsite .twitter-item-minimal .tweet-user a,
.bodywebsite .twitter-item-minimal .tweet-user a:active,
.bodywebsite .twitter-item-minimal .tweet-user a:focus {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .twitter-item-minimal .tweet-user a:hover {
  color: #000;
}
.bodywebsite .twitter-item-minimal .tweet-user a:before {
  content: '-';
}
.bodywebsite .twitter-item-minimal .tweet-text a,
.bodywebsite .twitter-item-minimal .tweet-text a:active,
.bodywebsite .twitter-item-minimal .tweet-text a:focus {
  color: #000;
}
.bodywebsite .twitter-item-minimal .tweet-text a:hover {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .twitter-item-minimal * + .tweet-text {
  margin-top: 0;
}
.bodywebsite .twitter-item-minimal * + .tweet-user {
  margin-top: 10px;
}
.bodywebsite .twitter-item-minimal + .twitter-item-minimal {
  margin-top: 25px;
}
.bodywebsite .twitter-widget {
  overflow: hidden;
  background: #fff;
  border-radius: 6px;
  box-shadow: -1px 0px 10px 0px rgba(65, 65, 65, 0.12);
}
.bodywebsite .twitter-widget > a {
  display: block;
  color: #9f9f9f;
}
.bodywebsite .twitter-widget .tweet-text a,
.bodywebsite .twitter-widget .tweet-text a:active,
.bodywebsite .twitter-widget .tweet-text a:focus {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .twitter-widget .tweet-text a:hover {
  color: #000;
}
.bodywebsite .twitter-widget .twitter-widget-time {
  color: #9f9f9f;
}
.bodywebsite .twitter-widget .twitter-widget-meta > * {
  line-height: 1.1;
}
.bodywebsite .twitter-widget .twitter-widget-meta > * + * {
  margin-top: 5px;
}
.bodywebsite .twitter-widget .twitter-widget-media {
  position: relative;
  z-index: 1;
  overflow: hidden;
}
.bodywebsite .twitter-widget .twitter-widget-media > img {
  position: absolute;
  top: 0;
  right: 0;
  left: 0;
  min-height: 101%;
}
.bodywebsite .twitter-widget .twitter-widget-media:empty {
  display: none;
}
.bodywebsite .twitter-widget .twitter-widget-media:not(:empty) {
  padding-bottom: 42.5170068%;
}
.bodywebsite .twitter-widget .tweet-avatar {
  display: block;
  width: 48px;
  height: 48px;
  background: #dedede;
}
.bodywebsite .twitter-widget .twitter-widget-header {
  padding: 30px 30px 0 30px;
}
.bodywebsite .twitter-widget .twitter-widget-inset {
  padding: 25px 30px 15px;
}
.bodywebsite .twitter-widget .twitter-widget-footer {
  padding: 15px 30px;
}
.bodywebsite * + .twitter-widget {
  margin-top: 30px;
}
.bodywebsite .swiper-container {
  margin: 0 auto;
  position: relative;
  overflow: hidden;
  /* Fix of Webkit flickering */
  z-index: 1;
  height: auto;
}
.bodywebsite .swiper-container .swiper-wrapper {
  height: auto;
  min-height: 36.25vw;
}
@media (min-width: 1800px) {
  .bodywebsite .swiper-container .swiper-wrapper {
	height: auto;
	min-height: 680px;
  }
}
.bodywebsite .swiper-container-no-flexbox .swiper-slide {
  float: left;
}
.bodywebsite .swiper-container-vertical > .swiper-wrapper {
  -webkit-box-orient: vertical;
  -moz-box-orient: vertical;
  -ms-flex-direction: column;
  -webkit-flex-direction: column;
  flex-direction: column;
}
.bodywebsite .swiper-wrapper {
  position: relative;
  width: 100%;
  z-index: 1;
  display: -webkit-box;
  display: -moz-box;
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-transition-property: -webkit-transform;
  -moz-transition-property: -moz-transform;
  -o-transition-property: -o-transform;
  -ms-transition-property: -ms-transform;
  transition-property: transform;
  -webkit-box-sizing: content-box;
  -moz-box-sizing: content-box;
  box-sizing: content-box;
  -webkit-align-self: stretch;
  -ms-flex-item-align: stretch;
  align-self: stretch;
  -webkit-align-items: stretch;
  -ms-flex-align: stretch;
  align-items: stretch;
}
.bodywebsite .swiper-container-android .swiper-slide,
.bodywebsite .swiper-wrapper {
  -webkit-transform: translate3d(0px, 0, 0);
  -moz-transform: translate3d(0px, 0, 0);
  -o-transform: translate(0px, 0px);
  -ms-transform: translate3d(0px, 0, 0);
  transform: translate3d(0px, 0, 0);
}
.bodywebsite .swiper-container-multirow > .swiper-wrapper {
  -webkit-box-lines: multiple;
  -moz-box-lines: multiple;
  -ms-flex-wrap: wrap;
  -webkit-flex-wrap: wrap;
  flex-wrap: wrap;
}
.bodywebsite .swiper-container-free-mode > .swiper-wrapper {
  -webkit-transition-timing-function: ease-out;
  -moz-transition-timing-function: ease-out;
  -ms-transition-timing-function: ease-out;
  -o-transition-timing-function: ease-out;
  transition-timing-function: ease-out;
  margin: 0 auto;
}
.bodywebsite .swiper-slide {
  position: relative;
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-flex-shrink: 0;
  -ms-flex: 0 0 auto;
  flex-shrink: 0;
  width: 100%;
  min-height: inherit;
}
.bodywebsite .swiper-container .swiper-notification {
  position: absolute;
  left: 0;
  top: 0;
  pointer-events: none;
  opacity: 0;
  z-index: -1000;
}
.bodywebsite .swiper-wp8-horizontal {
  -ms-touch-action: pan-y;
  touch-action: pan-y;
}
.bodywebsite .swiper-wp8-vertical {
  -ms-touch-action: pan-x;
  touch-action: pan-x;
}
.bodywebsite .swiper-nav {
  position: absolute;
  top: 50%;
  right: 0;
  left: 0;
  z-index: 10;
  pointer-events: none;
  transform: translateY(-50%);
}
.bodywebsite .swiper-button-prev,
.bodywebsite .swiper-button-next {
  z-index: 10;
  width: 48px;
  height: 48px;
  background-size: 48px 48px;
  color: #fff;
  transition: 180ms ease-in-out;
  text-align: center;
  cursor: pointer;
  pointer-events: auto;
}
.bodywebsite .swiper-button-prev:hover,
.bodywebsite .swiper-button-next:hover {
  opacity: .7;
}
.bodywebsite .swiper-button-prev.swiper-button-disabled,
.bodywebsite .swiper-button-next.swiper-button-disabled {
  opacity: 0;
  cursor: auto;
  pointer-events: none;
}
.bodywebsite .swiper-button-next {
  transform: rotate(180deg);
}
.bodywebsite .swiper-pagination-wrap {
  position: absolute;
  bottom: 20px;
  left: 50%;
  width: 100%;
  transform: translate3d(-50%, 0, 0);
  z-index: 10;
}
@media (min-width: 992px) {
  .bodywebsite .swiper-pagination-wrap {
	bottom: 35px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .swiper-pagination-wrap {
	bottom: 55px;
  }
}
.bodywebsite .swiper-pagination {
  display: block;
  width: 100%;
  text-align: center;
  transition: 300ms;
  -webkit-transform: translate3d(0, 0, 0);
  transform: translate3d(0, 0, 0);
  z-index: 10;
}
@media (min-width: 768px) {
  .bodywebsite .swiper-pagination {
	text-align: left;
  }
}
.bodywebsite .swiper-pagination.swiper-pagination-hidden {
  opacity: 0;
}
.bodywebsite .swiper-pagination-bullet {
  display: inline-block;
  width: 6px;
  height: 6px;
  border-radius: 20px;
  background: #cdcdcd;
  pointer-events: none;
  transition: all 0.2s ease-out;
}
.bodywebsite .swiper-pagination-bullet + * {
  margin-left: 10px;
}
@media (min-width: 768px) {
  .bodywebsite .swiper-pagination-bullet {
	width: 12px;
	height: 12px;
	pointer-events: auto;
  }
  .bodywebsite .swiper-pagination-bullet + * {
	margin-left: 20px;
  }
}
.bodywebsite .swiper-pagination-clickable .swiper-pagination-bullet {
  cursor: pointer;
}
.bodywebsite .swiper-pagination-white .swiper-pagination-bullet {
  background: #fff;
}
.bodywebsite .swiper-pagination-bullet:hover,
.bodywebsite .swiper-pagination-bullet-active {
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .swiper-pagination-white .swiper-pagination-bullet-active {
  background: #fff;
}
.bodywebsite .swiper-pagination-black .swiper-pagination-bullet-active {
  background: #000;
}
.bodywebsite .swiper-container-vertical > .swiper-pagination {
  right: 10px;
  top: 50%;
  -webkit-transform: translate3d(0px, -50%, 0);
  -moz-transform: translate3d(0px, -50%, 0);
  -o-transform: translate(0px, -50%);
  -ms-transform: translate3d(0px, -50%, 0);
  transform: translate3d(0px, -50%, 0);
}
.bodywebsite .swiper-container-vertical > .swiper-pagination .swiper-pagination-bullet {
  margin: 5px 0;
  display: block;
}
.bodywebsite .swiper-container-horizontal > .swiper-pagination {
  bottom: 20px;
  left: 0;
  width: 100%;
}
.bodywebsite .swiper-container-horizontal > .swiper-pagination .swiper-pagination-bullet {
  margin: 0 5px;
}

.bodywebsite .swiper-container-coverflow .swiper-wrapper {
  /* Windows 8 IE 10 fix */
  -ms-perspective: 1200px;
}
.bodywebsite .swiper-container-fade.swiper-container-free-mode .swiper-slide {
  -webkit-transition-timing-function: ease-out;
  transition-timing-function: ease-out;
}
.bodywebsite .swiper-container-fade .swiper-slide {
  pointer-events: none;
}
.bodywebsite .swiper-container-fade .swiper-slide .swiper-slide {
  pointer-events: none;
}
.bodywebsite .swiper-container-fade .swiper-slide-active,
.bodywebsite .swiper-container-fade .swiper-slide-active .swiper-slide-active {
  pointer-events: auto;
}
.bodywebsite .swiper-container-cube {
  overflow: visible;
}
.bodywebsite .swiper-container-cube .swiper-slide {
  pointer-events: none;
  visibility: hidden;
  -webkit-transform-origin: 0 0;
  -moz-transform-origin: 0 0;
  -ms-transform-origin: 0 0;
  transform-origin: 0 0;
  -webkit-backface-visibility: hidden;
  -moz-backface-visibility: hidden;
  -ms-backface-visibility: hidden;
  backface-visibility: hidden;
  width: 100%;
  height: 100%;
  z-index: 1;
}
.bodywebsite .swiper-container-cube.swiper-container-rtl .swiper-slide {
  -webkit-transform-origin: 100% 0;
  -moz-transform-origin: 100% 0;
  -ms-transform-origin: 100% 0;
  transform-origin: 100% 0;
}
.bodywebsite .swiper-container-cube .swiper-slide-active,
.bodywebsite .swiper-container-cube .swiper-slide-next,
.bodywebsite .swiper-container-cube .swiper-slide-prev,
.bodywebsite .swiper-container-cube .swiper-slide-next + .swiper-slide {
  pointer-events: auto;
  visibility: visible;
}
.bodywebsite .swiper-container-cube .swiper-slide-shadow-top,
.bodywebsite .swiper-container-cube .swiper-slide-shadow-bottom,
.bodywebsite .swiper-container-cube .swiper-slide-shadow-left,
.bodywebsite .swiper-container-cube .swiper-slide-shadow-right {
  z-index: 0;
  -webkit-backface-visibility: hidden;
  -moz-backface-visibility: hidden;
  -ms-backface-visibility: hidden;
  backface-visibility: hidden;
}
.bodywebsite .swiper-container-cube .swiper-cube-shadow {
  position: absolute;
  left: 0;
  bottom: 0px;
  width: 100%;
  height: 100%;
  background: #000;
  opacity: 0.6;
  -webkit-filter: blur(50px);
  filter: blur(50px);
  z-index: 0;
}
.bodywebsite .swiper-scrollbar {
  position: relative;
  -ms-touch-action: none;
}
.bodywebsite .swiper-container-horizontal > .swiper-scrollbar {
  position: absolute;
  top: 0;
  left: 0;
  z-index: 50;
  height: 5px;
  width: 100%;
}
.bodywebsite .swiper-container-vertical > .swiper-scrollbar {
  position: absolute;
  right: 3px;
  top: 1%;
  z-index: 50;
  width: 5px;
  height: 98%;
}
.bodywebsite .swiper-scrollbar-drag {
  height: 100%;
  width: 100%;
  position: relative;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
  left: 0;
  top: 0;
}
.bodywebsite .swiper-scrollbar-cursor-drag {
  cursor: move;
}
.bodywebsite .swiper-lazy-preloader {
  width: 42px;
  height: 42px;
  position: absolute;
  left: 50%;
  top: 50%;
  margin-left: -21px;
  margin-top: -21px;
  z-index: 10;
  -webkit-transform-origin: 50%;
  -moz-transform-origin: 50%;
  transform-origin: 50%;
  -webkit-animation: swiper-preloader-spin 1s steps(12, end) infinite;
  -moz-animation: swiper-preloader-spin 1s steps(12, end) infinite;
  animation: swiper-preloader-spin 1s steps(12, end) infinite;
}
.bodywebsite .swiper-lazy-preloader:after {
  display: block;
  content: "";
  width: 100%;
  height: 100%;
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg%20viewBox%3D'0%200%20120%20120'%20xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg'%20xmlns%3Axlink%3D'http%3A%2F%2Fwww.w3.org%2F1999%2Fxlink'%3E%3Cdefs%3E%3Cline%20id%3D'l'%20x1%3D'60'%20x2%3D'60'%20y1%3D'7'%20y2%3D'27'%20stroke%3D'%236c6c6c'%20stroke-width%3D'11'%20stroke-linecap%3D'round'%2F%3E%3C%2Fdefs%3E%3Cg%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(30%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(60%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(90%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(120%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(150%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.37'%20transform%3D'rotate(180%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.46'%20transform%3D'rotate(210%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.56'%20transform%3D'rotate(240%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.66'%20transform%3D'rotate(270%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.75'%20transform%3D'rotate(300%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.85'%20transform%3D'rotate(330%2060%2C60)'%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E");
  background-position: 50%;
  -webkit-background-size: 100%;
  background-size: 100%;
  background-repeat: no-repeat;
}
.bodywebsite .swiper-lazy-preloader-white:after {
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg%20viewBox%3D'0%200%20120%20120'%20xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg'%20xmlns%3Axlink%3D'http%3A%2F%2Fwww.w3.org%2F1999%2Fxlink'%3E%3Cdefs%3E%3Cline%20id%3D'l'%20x1%3D'60'%20x2%3D'60'%20y1%3D'7'%20y2%3D'27'%20stroke%3D'%23fff'%20stroke-width%3D'11'%20stroke-linecap%3D'round'%2F%3E%3C%2Fdefs%3E%3Cg%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(30%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(60%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(90%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(120%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(150%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.37'%20transform%3D'rotate(180%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.46'%20transform%3D'rotate(210%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.56'%20transform%3D'rotate(240%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.66'%20transform%3D'rotate(270%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.75'%20transform%3D'rotate(300%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.85'%20transform%3D'rotate(330%2060%2C60)'%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E");
}
@-webkit-keyframes swiper-preloader-spin {
  100% {
	-webkit-transform: rotate(360deg);
  }
}
@keyframes swiper-preloader-spin {
  100% {
	transform: rotate(360deg);
  }
}
.bodywebsite .swiper-slide > .vide__body,
.bodywebsite .swiper-slide > .parallax_cnt {
  height: 100%;
}
.bodywebsite .swiper-slide {
  position: relative;
  text-align: center;
  white-space: nowrap;
  background-position: center center;
  overflow: hidden;
}
.bodywebsite .swiper-slide:not(.vide):not(.rd-parallax):before,
.bodywebsite .swiper-slide .parallax_cnt:before,
.bodywebsite .swiper-slide .vide__body:before {
  content: '';
  display: inline-block;
  height: 50%;
}
.bodywebsite .swiper-slide-caption {
  display: inline-block;
  width: 100%;
  max-height: 100%;
  margin-left: -0.25em;
  vertical-align: middle;
  white-space: normal;
  z-index: 1;
}
.bodywebsite .swiper-variant-1,
.bodywebsite .swiper-variant-1 .swiper-wrapper {
  height: auto;
  min-height: calc(44vh);
}
.bodywebsite .swiper-variant-1 .swiper-slide-caption {
  padding: 40px 0 40px;
}
.bodywebsite .swiper-variant-1 .swiper-button-prev,
.bodywebsite .swiper-variant-1 .swiper-button-next {
  display: none;
}
.bodywebsite .swiper-variant-1 .slider-text {
  display: none;
}
.bodywebsite .swiper-variant-1 .jumbotron-custom + * {
  margin-top: 5px;
}
.bodywebsite .swiper-variant-1 * + .button-block {
  margin-top: 27px;
}
@media (min-width: 768px) {
  .bodywebsite .swiper-variant-1,
  .bodywebsite .swiper-variant-1 .swiper-wrapper {
	height: auto;
	min-height: 36.25vw;
  }
  .bodywebsite .swiper-variant-1 .swiper-slide-caption {
	padding: 60px 0 115px;
  }
  .bodywebsite .swiper-variant-1 .slider-header {
	font-size: 50px;
  }
  .bodywebsite .swiper-variant-1 .slider-text {
	display: block;
  }
}
@media (min-width: 992px) {
  .bodywebsite .swiper-variant-1 .swiper-slide-caption {
	padding: 100px 0 155px;
  }
  .bodywebsite .swiper-variant-1 .swiper-button-prev,
  .bodywebsite .swiper-variant-1 .swiper-button-next {
	position: absolute;
	top: 50%;
	transform: translateY(-59%);
	z-index: 10;
	display: block;
	transition: .3s all ease;
  }
  .bodywebsite .swiper-variant-1 .swiper-button-prev {
	left: 5.1%;
	transform: scale(-1, 1);
  }
  .bodywebsite .swiper-variant-1 .swiper-button-next {
	right: 5.1%;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .swiper-variant-1 .slider-header {
	font-size: 59px;
  }
  .bodywebsite .swiper-variant-1 .swiper-button-prev {
	left: 20px;
  }
  .bodywebsite .swiper-variant-1 .swiper-button-next {
	right: 20px;
  }
}
@media (min-width: 1599px) {
  .bodywebsite .swiper-variant-1 .swiper-button-prev {
	left: calc(50vw - 1170px / 2 - 170px + (1170px / 12) * 0);
  }
  .bodywebsite .swiper-variant-1 .swiper-button-next {
	right: calc(50vw - 1170px / 2 - 170px + (1170px / 12) * 0);
  }
}
@media (min-width: 1800px) {
  .bodywebsite .swiper-variant-1,
  .bodywebsite .swiper-variant-1 .swiper-wrapper {
	height: auto;
	min-height: 680px;
  }
}
.bodywebsite .ui-to-top {
  width: 40px;
  height: 40px;
  font-size: 18px;
  line-height: 38px;
  border-radius: 50%;
  position: fixed;
  right: 15px;
  bottom: 15px;
  overflow: hidden;
  text-align: center;
  text-decoration: none;
  z-index: 20;
  transition: .3s all ease;
  box-shadow: 0 0 1px 0px rgba(55, 195, 134, 0.3);
  transform: translateY(100px);
}
.bodywebsite .ui-to-top,
.bodywebsite .ui-to-top:active,
.bodywebsite .ui-to-top:focus {
  color: #fff;
  background: <?php echo empty($website->maincolor) ? 'rgb(150, 150, 150)' : '#'.$website->maincolor; ?>;
  opacity: 0.6;
}
.bodywebsite .ui-to-top:hover {
  color: #fff;
  background: <?php echo empty($website->maincolorbis) ? '#6ca' : '#'.$website->maincolorbis; ?>;
  box-shadow: 0 0 1px 0px rgba(0, 0, 0, 0.4);
}
.bodywebsite .ui-to-top:focus {
  outline: 0;
}
.bodywebsite .ui-to-top.active {
  transform: translateY(0);
}
.bodywebsite .mobile .ui-to-top,
.bodywebsite .tablet .ui-to-top {
  display: none !important;
}
@media (min-width: 576px) {
  .bodywebsite .ui-to-top {
	right: 40px;
	bottom: 40px;
  }
}
.bodywebsite .progress-bar-wrap {
  max-width: 100%;
  width: 210px;
}
@media (min-width: 576px) and (max-width: 767px) {
  .bodywebsite .progress-bar-wrap {
	max-width: 120px;
  }
}
@media (min-width: 768px) {
  .bodywebsite .progress-bar-wrap {
	max-width: 150px;
  }
}
.bodywebsite .progress-bar {
  position: relative;
  width: 100%;
  margin: 0;
  font-family: "Roboto", Helvetica, Arial, sans-serif;
}
.bodywebsite .progress-bar .progress-bar__body {
  position: absolute;
  right: 50%;
  width: 100%;
  top: 50%;
  padding: 0;
  margin: 0;
  white-space: nowrap;
  font-size: 34px;
  font-weight: 400;
  line-height: 26px;
  color: #00030a;
  text-align: right;
}
.bodywebsite .progress-bar .progress-bar__body:after {
  content: '%';
}
.bodywebsite .progress-bar .progress-bar__stroke,
.bodywebsite .progress-bar .progress-bar__trail {
  stroke-linejoin: round;
}
.bodywebsite .progress-bar-horizontal {
  position: relative;
  font-family: "Roboto", Helvetica, Arial, sans-serif;
  text-align: right;
}
.bodywebsite .progress-bar-horizontal > svg {
  margin-top: 3px;
  border-radius: 3px;
}
.bodywebsite .progress-bar-horizontal .progress-bar__body {
  position: absolute;
  top: -27px;
  margin-top: 0;
  padding-right: 0;
}
.bodywebsite .progress-bar-horizontal .progress-bar__body:after {
  content: '%';
}
.bodywebsite .progress-bar-radial {
  position: relative;
  padding-bottom: 100%;
}
.bodywebsite .progress-bar-radial > svg {
  position: absolute;
  width: 100%;
  height: 100%;
  left: 0;
  top: 0;
  border-radius: 5px;
  overflow: hidden;
}
.bodywebsite .progress-bar-radial .progress-bar__stroke,
.bodywebsite .progress-bar-radial .progress-bar__trail {
  stroke-location: outside;
}
.bodywebsite .progress-bar-radial .progress-bar__body {
  transform: translate(50%, -50%);
}
.bodywebsite .progress-bar-default .progress-bar__stroke {
  stroke: #9f9f9f;
}
.bodywebsite .progress-bar-default .progress-bar__trail {
  stroke: rgba(159, 159, 159, 0.05);
}
.bodywebsite .progress-bar-primary .progress-bar__stroke {
  stroke: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .progress-bar-primary .progress-bar__trail {
  stroke: #F8F9FB;
}
.bodywebsite .progress-bar-bermuda-gray .progress-bar__stroke {
  stroke: #6f8fad;
}
.bodywebsite .progress-bar-bermuda-gray .progress-bar__trail {
  stroke: #F8F9FB;
}
.bodywebsite .progress-bar-contessa .progress-bar__stroke {
  stroke: #e76752;
}
.bodywebsite .progress-bar-contessa .progress-bar__trail {
  stroke: #F8F9FB;
}
.bodywebsite .progress-bar-red-orange-1 .progress-bar__stroke {
  stroke: #f8333c;
}
.bodywebsite .progress-bar-red-orange-1 .progress-bar__trail {
  stroke: #ededed;
}
.bodywebsite .progress-bar-dodger-blue .progress-bar__stroke {
  stroke: #45a4ff;
}
.bodywebsite .progress-bar-dodger-blue .progress-bar__trail {
  stroke: #ededed;
}
.bodywebsite .progress-bar-gorse .progress-bar__stroke {
  stroke: #fde74c;
}
.bodywebsite .progress-bar-gorse .progress-bar__trail {
  stroke: #ededed;
}
.bodywebsite .progress-bar-old-gold .progress-bar__stroke {
  stroke: #ecd746;
}
.bodywebsite .progress-bar-old-gold .progress-bar__trail {
  stroke: #F8F9FB;
}
.bodywebsite .progress-bar-secondary-2 .progress-bar__stroke {
  stroke: #dedede;
}
.bodywebsite .progress-bar-secondary-2 .progress-bar__trail {
  stroke: gray;
}
.bodywebsite .progress-bar-secondary-1 .progress-bar__stroke {
  stroke: #dedede;
}
.bodywebsite .progress-bar-secondary-1 .progress-bar__trail {
  stroke: rgba(159, 159, 159, 0.05);
}
.bodywebsite .progress-bar-secondary-3 .progress-bar__stroke {
  stroke: #c49558;
}
.bodywebsite .progress-bar-secondary-3 .progress-bar__trail {
  stroke: rgba(159, 159, 159, 0.05);
}
.bodywebsite .progress-bar-secondary-4 .progress-bar__stroke {
  stroke: #fe4a21;
}
.bodywebsite .progress-bar-secondary-4 .progress-bar__trail {
  stroke: rgba(159, 159, 159, 0.05);
}
.bodywebsite .countdown-wrap {
  max-width: 720px;
  max-height: 134px;
}
.bodywebsite .countdown-wrap .time_circles > div {
  display: -ms-flexbox;
  display: -webkit-flex;
  display: flex;
  -webkit-flex-direction: column-reverse;
  -ms-flex-direction: column-reverse;
  flex-direction: column-reverse;
  -webkit-flex-wrap: nowrap;
  -ms-flex-wrap: nowrap;
  flex-wrap: nowrap;
  -webkit-align-items: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-justify-content: center;
  -ms-flex-pack: center;
  justify-content: center;
  font-size: 0;
  line-height: 0;
}
.bodywebsite .countdown-wrap div > h4 {
  position: relative;
  margin-top: -2px;
  font: 500 12px "Roboto", Helvetica, Arial, sans-serif !important;
  color: rgba(0, 0, 0, 0.2);
  letter-spacing: -0.025em;
  bottom: auto !important;
  text-transform: uppercase;
}
@media (min-width: 576px) {
  .bodywebsite .countdown-wrap div > h4 {
	font-size: 14px !important;
  }
}
.bodywebsite .countdown-wrap span {
  font: 900 18px "Roboto", Helvetica, Arial, sans-serif !important;
  font-style: normal;
  color: #000;
}
@media (min-width: 576px) {
  .bodywebsite .countdown-wrap span {
	font-size: 40px !important;
  }
}
@media (min-width: 768px) {
  .bodywebsite .countdown-wrap span {
	font-size: 45px !important;
	line-height: 1;
  }
}
.bodywebsite .slick-slider {
  position: relative;
  display: block;
  -moz-box-sizing: border-box;
  box-sizing: border-box;
  -webkit-touch-callout: none;
  -webkit-user-select: none;
  -khtml-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
  -ms-touch-action: pan-y;
  touch-action: pan-y;
  -webkit-tap-highlight-color: transparent;
}
.bodywebsite .slick-list {
  position: relative;
  overflow: hidden;
  display: block;
  margin: 0;
  padding: 0;
}
.bodywebsite .slick-list:focus {
  outline: none;
}
.bodywebsite .slick-list.dragging {
  cursor: pointer;
  cursor: hand;
}
.bodywebsite .slick-slider .slick-track,
.bodywebsite .slick-slider .slick-list {
  -webkit-transform: translate3d(0, 0, 0);
  -moz-transform: translate3d(0, 0, 0);
  -ms-transform: translate3d(0, 0, 0);
  -o-transform: translate3d(0, 0, 0);
  transform: translate3d(0, 0, 0);
}
.bodywebsite .slick-track {
  position: relative;
  left: 0;
  top: 0;
  display: block;
}
.bodywebsite .slick-track:before,
.bodywebsite .slick-track:after {
  content: "";
  display: table;
}
.bodywebsite .slick-track:after {
  clear: both;
}
.bodywebsite .slick-loading .slick-track {
  visibility: hidden;
}
.bodywebsite .slick-slide {
  float: left;
  min-height: 1px;
  display: none;
}
.bodywebsite [dir="rtl"] .slick-slide {
  float: right;
}
.bodywebsite .slick-slide img {
  display: block;
}
.bodywebsite .slick-slide.slick-loading img {
  display: none;
}
.bodywebsite .slick-slide.dragging img {
  pointer-events: none;
}
.bodywebsite .slick-initialized .slick-slide {
  display: block;
}
.bodywebsite .slick-loading .slick-slide {
  visibility: hidden;
}
.bodywebsite .slick-vertical .slick-slide {
  display: block;
  height: auto;
  border: 1px solid transparent;
}
.bodywebsite .slick-arrow.slick-hidden {
  display: none;
}
.bodywebsite .slick-loading .slick-list {
  background: #fff url("medias/image/<?php echo $website->ref; ?>/ajax-loading.gif") center center no-repeat;
}
.bodywebsite .slick-prev,
.bodywebsite .slick-next {
  position: absolute;
  display: block;
  height: 20px;
  width: 20px;
  line-height: 0;
  font-size: 0;
  cursor: pointer;
  background: rgba(0, 0, 0, 0.6);
  color: transparent;
  top: 50%;
  -webkit-transform: translate(0, -50%);
  -ms-transform: translate(0, -50%);
  transform: translate(0, -50%);
  padding: 0;
  border: none;
  outline: none;
  z-index: 999;
}
.bodywebsite .slick-prev:hover,
.bodywebsite .slick-prev:focus,
.bodywebsite .slick-next:hover,
.bodywebsite .slick-next:focus {
  outline: none;
  background: transparent;
  color: transparent;
}
.bodywebsite .slick-prev:hover:before,
.bodywebsite .slick-prev:focus:before,
.bodywebsite .slick-next:hover:before,
.bodywebsite .slick-next:focus:before {
  opacity: 1;
}
.bodywebsite .slick-prev.slick-disabled:before,
.bodywebsite .slick-next.slick-disabled:before {
  opacity: 0.25;
}
.bodywebsite .slick-prev:before,
.bodywebsite .slick-next:before {
  font-family: "Roboto", Helvetica, Arial, sans-serif;
  font-size: 20px;
  line-height: 1;
  color: white;
  opacity: 0.75;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
.bodywebsite .slick-prev {
  left: 0;
}
.bodywebsite [dir="rtl"] .slick-prev {
  left: auto;
  right: 0;
}
.bodywebsite .slick-prev:before {
  content: "←";
}
.bodywebsite [dir="rtl"] .slick-prev:before {
  content: "→";
}
.bodywebsite .slick-next {
  right: 0;
}
.bodywebsite [dir="rtl"] .slick-next {
  left: 0;
  right: auto;
}
.bodywebsite .slick-next:before {
  content: "→";
}
.bodywebsite [dir="rtl"] .slick-next:before {
  content: "←";
}
.bodywebsite .slick-slider {
  margin-top: 30px;
}
.bodywebsite .slick-slider *:focus {
  outline: 0;
}
.bodywebsite .slick-dots {
  display: block;
  margin-top: 20px;
  list-style: none;
  width: 100%;
  padding: 0;
  text-align: center;
  font-size: 0;
  line-height: 0;
  word-spacing: 0;
}
.bodywebsite .slick-dots li {
  position: relative;
  display: inline-block;
  height: 20px;
  width: 20px;
  margin: 0 5px;
  padding: 0;
  cursor: pointer;
}
.bodywebsite .slick-dots li button {
  background: none;
  border: none;
  display: inline-block;
  padding: 0;
  outline: none;
  outline-offset: 0;
  cursor: pointer;
  -webkit-appearance: none;
  width: 8px;
  height: 8px;
  border-radius: 100px;
  background: #ababab;
}
.bodywebsite .slick-dots li button::-moz-focus-inner {
  border: none;
  padding: 0;
}
.bodywebsite .slick-dots li.slick-active button,
.bodywebsite .slick-dots li:hover button {
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .slick-dots-variant-1 .slick-dots li button {
  height: 12px;
  width: 12px;
  background: rgba(58, 60, 62, 0.5);
  transition: .33s all ease;
  position: relative;
}
.bodywebsite .slick-dots-variant-1 .slick-dots li button:after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 19px;
  height: 19px;
  border: 4px solid #fff;
  opacity: 0;
  border-radius: 50%;
  -webkit-transform: translate(-50%, -50%) scale(0);
  transform: translate(-50%, -50%) scale(0);
  transition: 180ms ease-in-out;
}
.bodywebsite .slick-dots-variant-1 .slick-dots li.slick-active button,
.bodywebsite .slick-dots-variant-1 .slick-dots li:hover button {
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .slick-dots-variant-1 .slick-dots li.slick-active button:after,
.bodywebsite .slick-dots-variant-1 .slick-dots li:hover button:after {
  opacity: 1;
  -webkit-transform: translate(-50%, -50%) scale(1);
  transform: translate(-50%, -50%) scale(1);
}
.bodywebsite .slick-carousel-complex-variant-1 {
  position: relative;
  padding-bottom: 60px;
}
.bodywebsite .slick-carousel-complex-variant-1:after {
  content: '';
  position: absolute;
  top: 80px;
  left: 50%;
  bottom: 0;
  transform: translateX(-50%);
  width: 101vw;
  background: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .slick-carousel-complex-variant-1 > * {
  position: relative;
  z-index: 2;
}
.bodywebsite .slick-carousel-complex-variant-1 .slick-slider {
  margin-bottom: 0;
}
.bodywebsite .slick-carousel-complex-variant-1 .slick-dots {
  margin-top: 30px;
}
@media (min-width: 768px) {
  .bodywebsite .slick-carousel-complex-variant-1 {
	padding-bottom: 90px;
  }
}
.bodywebsite .slick-slider-images .item {
  padding: 0 15px;
  text-align: right;
}
.bodywebsite .slick-slider-images .item img {
  display: inline-block;
  transform: scale(0.75);
  transform-origin: 100% 50%;
  will-change: transform;
  cursor: pointer;
  transition: .5s all ease;
}
.bodywebsite .slick-slider-images .item.slick-center {
  text-align: center;
}
.bodywebsite .slick-slider-images .item.slick-center img {
  transform-origin: 50% 50%;
  transform: scale(1);
}
.bodywebsite .slick-slider-images .item.slick-center + * {
  text-align: left;
}
.bodywebsite .slick-slider-images .item.slick-center + * img {
  transform-origin: 0 50%;
}
.bodywebsite .slick-carousel-round-image .item img {
  border-radius: 50%;
}
.bodywebsite .carousel-testimonials-home .slick-slide {
  text-align: center;
}
.bodywebsite #sectiontestimonies.maincolorbackground {
  background-image: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
@media (min-width: 576px) {
  .bodywebsite .carousel-testimonials-home .slick-slide {
	text-align: left;
  }
}
.bodywebsite .carousel-testimonials-home .slick-dots li button {
  background: rgba(246, 247, 250, 0.5);
}
.bodywebsite .carousel-testimonials-home .item {
  padding-left: 10px;
  padding-right: 10px;
}
@media (min-width: 1200px) {
  .bodywebsite .carousel-testimonials-home .item {
	padding-left: 0;
	padding-right: 0;
  }
}
@media (min-width: 576px) {
  .bodywebsite .carousel-testimonials-home .slick-dots {
	display: none !important;
  }
}
.bodywebsite .carousel-testimonials-home .slick-images .item {
  padding-left: 0;
  padding-right: 0;
}
.bodywebsite .carousel-testimonials-home .slick-images .item .imp-wrap {
  text-align: center;
  position: relative;
  padding: 10px;
}
.bodywebsite .carousel-testimonials-home .slick-images .item .imp-wrap:after {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(0);
  content: '';
  display: inline-block;
  margin-left: 0px;
  border: 1px solid #fccb56;
  width: 116px;
  height: 116px;
  border-radius: 50%;
  transition: 200ms ease-in-out;
  opacity: 0;
  pointer-events: none;
}
.bodywebsite .carousel-testimonials-home .slick-images .item .imp-wrap img {
  display: inline-block;
  border-radius: 50%;
  cursor: pointer;
}
.bodywebsite .carousel-testimonials-home .slick-images .item:hover .imp-wrap:after,
.bodywebsite .carousel-testimonials-home .slick-images .item.slick-current .imp-wrap:after {
  transform: translate(-50%, -50%) scale(1);
  opacity: 1;
}
.bodywebsite .carousel-testimonials-home .quote-desc {
  -webkit-justify-content: center;
  -ms-flex-pack: center;
  justify-content: center;
}
.bodywebsite .websitemaincolor {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .maincolor {
  color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .maincolorbis {
  color: <?php echo empty($website->maincolorbis) ? '#6ca' : '#'.$website->maincolorbis; ?> !important;
}
.bodywebsite .maincolorbackground {
  background-color: <?php echo empty($website->maincolor) ? 'rgb(50, 120, 180)' : '#'.$website->maincolor; ?>;
}
.bodywebsite .maincolorbisbackground {
  background-color: <?php echo empty($website->maincolorbis) ? '#6ca' : '#'.$website->maincolorbis; ?> !important;
  border-color: <?php echo empty($website->maincolorbis) ? '#6ca' : '#'.$website->maincolorbis; ?> !important;
}
.bodywebsite .maincolorbisbackground:hover {
  box-shadow: 1px 1px 8px #aaa;
}
.bodywebsite textarea:focus,
.bodywebsite button:focus {
  border: unset !important;
}
.bodywebsite .marginrightonly {
  margin-right: 10px !important;
}
.bodywebsite .inline-block {
  display: inline-block;
}
.bodywebsite .valignmiddle {
  vertical-align: middle;
}
.bodywebsite .center {
  text-align: center;
}
.bodywebsite button.buttonwithnoborder.toggle-original.active {
  display: none;
}
.bodywebsite .rd-navbar-nav-wrap.active .toggle-original span.icon.icon-xs.icon-dusty-gray.fa.fa-search {
  display: none;
}
.bodywebsite ul.rd-navbar-list {
  padding-top: 20px;
}
.bodywebsite .photouser:hover,
.bodywebsite .photouser:active {
  border: 2px solid #eee;
}
.bodywebsite .imp-wrap {
  display: inline-block;
}
.bodywebsite .imp-wrap img {
  border-radius: 50px;
}
.bodywebsite .text-green {
  color: #6ca;
}
.bodywebsite .plan-tile .plan-title {
  padding: 20px 0 0;
  font-size: 20px;
  font-weight: bold;
  text-align: center;
}
.bodywebsite .plan-tile .plan-tag {
  color: #687484;
  text-align: center;
  font-size: 16px;
  padding: 0 5px 10px;
  font-weight: 300;
  min-height: 70px;
}
.bodywebsite .plan-tile .plan-pricer .plan-price-title {
  display: block;
  text-align: center;
  color: #8492A6;
  font-style: italic;
  position: absolute;
  top: 30px;
  transform: translateX(-50%);
  -webkit-transform: translateX(-50%);
  left: 50%;
  font-size: 16px;
  width: 100%;
  font-weight: 300;
}
.bodywebsite .plan-tile .plan-feat {
  display: block;
  font-size: 14px;
  color: #3C4858;
  text-align: center;
  padding: 22px 10px;
  min-height: 90px;
}
.bodywebsite .plan-tile .plan-pricer .plan-price {
  border-bottom: 1px solid #d5dadf;
  border-top: 1px solid #d5dadf;
  padding: 20px 0 20px;
  display: block;
}
.bodywebsite .plan-tile .plan-pricer .plan-price > span {
  color: #3C4858;
  font-size: 32px;
}
.bodywebsite .plan-tile .plan-pricer .plan-price > span > sup {
  font-size: 13px;
  top: -0.9em;
}
.bodywebsite .plan-tile .plan-pricer {
  padding: 5px 0;
  text-align: center;
  max-width: 90%;
  position: relative;
  margin: auto;
}
.bodywebsite .pricing-plan-slider .plan-tile .plan-btn {
  position: absolute;
  bottom: 0px;
  left: 0;
  width: 100%;
}
.bodywebsite .plan-tile .plan-btn {
  text-align: center;
  padding: 0 15px 15px 15px;
}
.bodywebsite .plan-features {
  padding-top: 20px;
  padding-bottom: 20px;
  padding-left: 20px;
  padding-right: 20px;
}
.bodywebsite .formcontact div {
  margin: 2px;
}
.bodywebsite section#sectionfooterdolibarr {
  padding-left: 3px;
  padding-right: 3px;
  padding-bottom: 10px;
}
.bodywebsite button.buttonwithnoborder.toggle-original {
  font-family: "Font Awesome 5 Free";
  font-weight: 600;
  font-size: initial;
  /* If removed, the search icon is not visible */
}
.bodywebsite .rd-navbar-fixed .buttonsearchwhenstatic {
  display: none;
}
.bodywebsite input[type="text"] {
  display: block;
  width: 100%;
  padding: 11px 35px;
  font-size: 14px;
  line-height: 1.25;
  background-image: none;
  background-clip: padding-box;
  border: 1px solid #ced4da;
  border-radius: 0.25rem;
  box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
  transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
}
@media print {
  .bodywebsite *,
  .bodywebsite *::before,
  .bodywebsite *::after {
	text-shadow: none !important;
	box-shadow: none !important;
  }
  .bodywebsite a,
  .bodywebsite a:visited {
	text-decoration: underline;
  }
  .bodywebsite abbr[title]::after {
	content: " (" attr(title) ")";
  }
  .bodywebsite pre {
	white-space: pre-wrap !important;
  }
  .bodywebsite pre,
  .bodywebsite blockquote {
	border: 1px solid #999;
	page-break-inside: avoid;
  }
  .bodywebsite thead {
	display: table-header-group;
  }
  .bodywebsite tr,
  .bodywebsite img {
	page-break-inside: avoid;
  }
  .bodywebsite p,
  .bodywebsite h2,
  .bodywebsite h3 {
	orphans: 3;
	widows: 3;
  }
  .bodywebsite h2,
  .bodywebsite h3 {
	page-break-after: avoid;
  }
  .bodywebsite .navbar {
	display: none;
  }
  .bodywebsite .badge {
	border: 1px solid #000;
  }
  .bodywebsite .table {
	border-collapse: collapse !important;
  }
  .bodywebsite .table td,
  .bodywebsite .table th {
	background-color: #fff !important;
  }
  .bodywebsite .table-bordered th,
  .bodywebsite .table-bordered td {
	border: 1px solid #ddd !important;
  }
}
.bodywebsite *,
.bodywebsite *::before,
.bodywebsite *::after {
  box-sizing: border-box;
}
html .bodywebsite {
  font-family: sans-serif;
  -webkit-text-size-adjust: 100%;
  -ms-text-size-adjust: 100%;
  -ms-overflow-style: scrollbar;
  -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
}
@-ms-viewport {
  width: device-width;
}
.bodywebsite article,
.bodywebsite aside,
.bodywebsite dialog,
.bodywebsite figcaption,
.bodywebsite figure,
.bodywebsite footer,
.bodywebsite header,
.bodywebsite hgroup,
.bodywebsite main,
.bodywebsite nav,
.bodywebsite section {
  display: block;
}
.bodywebsite {
  margin: 0;
  font-family: "Roboto", Helvetica, Arial, sans-serif;
  font-size: 14px;
  font-weight: 400;
  line-height: 1.71429;
  text-align: left;
  background-color: #fff;
}
.bodywebsite [tabindex="-1"]:focus {
  outline: none !important;
}
.bodywebsite hr {
  box-sizing: content-box;
  height: 0;
  overflow: visible;
}
.bodywebsite h1,
.bodywebsite h2,
.bodywebsite h3,
.bodywebsite h4,
.bodywebsite h5,
.bodywebsite h6 {
  margin-top: 0;
  margin-bottom: 0.5rem;
}
.bodywebsite abbr[title],
.bodywebsite abbr[data-original-title] {
  text-decoration: underline;
  text-decoration: underline dotted;
  cursor: help;
  border-bottom: 0;
}
.bodywebsite address {
  margin-bottom: 1rem;
  font-style: normal;
  line-height: inherit;
}
.bodywebsite ol,
.bodywebsite ul,
.bodywebsite dl {
  margin-top: 0;
}
.bodywebsite ol ol,
.bodywebsite ul ul,
.bodywebsite ol ul,
.bodywebsite ul ol {
  margin-bottom: 0;
}
.bodywebsite dt {
  font-weight: inherit;
}
.bodywebsite dd {
  margin-bottom: .5rem;
  margin-left: 0;
}
.bodywebsite blockquote {
  margin: 0 0 1rem;
}
.bodywebsite dfn {
  font-style: italic;
}
.bodywebsite b,
.bodywebsite strong {
  font-weight: bolder;
}
.bodywebsite small {
  font-size: 80%;
}
.bodywebsite sub,
.bodywebsite sup {
  position: relative;
  font-size: 75%;
  line-height: 0;
  vertical-align: baseline;
}
.bodywebsite sub {
  bottom: -0.25em;
}
.bodywebsite sup {
  top: -0.5em;
}
.bodywebsite a {
  text-decoration: none;
  -webkit-text-decoration-skip: objects;
}
.bodywebsite a:hover {
  text-decoration: none;
}
.bodywebsite a:not([href]):not([tabindex]) {
  color: inherit;
  text-decoration: none;
}
.bodywebsite a:not([href]):not([tabindex]):focus,
.bodywebsite a:not([href]):not([tabindex]):hover {
  color: inherit;
  text-decoration: none;
}
.bodywebsite a:not([href]):not([tabindex]):focus {
  outline: 0;
}
.bodywebsite pre,
.bodywebsite code,
.bodywebsite kbd,
.bodywebsite samp {
  font-family: monospace, monospace;
  font-size: 1em;
}
.bodywebsite pre {
  margin-top: 0;
  margin-bottom: 1rem;
  overflow: auto;
  -ms-overflow-style: scrollbar;
}
.bodywebsite figure {
  margin: 0 0 1rem;
}
.bodywebsite img {
  vertical-align: middle;
  border-style: none;
}
.bodywebsite svg:not(:root) {
  overflow: hidden;
}
.bodywebsite a,
.bodywebsite area,
.bodywebsite button,
.bodywebsite [role="button"],
.bodywebsite input:not([type="range"]),
.bodywebsite label,
.bodywebsite select,
.bodywebsite summary,
.bodywebsite textarea {
  touch-action: manipulation;
}
.bodywebsite table {
  border-collapse: collapse;
}
.bodywebsite caption {
  padding-top: 17px 25px 18px;
  padding-bottom: 17px 25px 18px;
  color: #dedede;
  text-align: left;
  caption-side: bottom;
}
.bodywebsite th {
  text-align: inherit;
}
.bodywebsite label {
  display: inline-block;
  margin-bottom: .5rem;
}
.bodywebsite button {
  border-radius: 0;
}
.bodywebsite button:focus {
  outline: 1px dotted;
  outline: 5px auto -webkit-focus-ring-color;
}
.bodywebsite input,
.bodywebsite button,
.bodywebsite select,
.bodywebsite optgroup,
.bodywebsite textarea {
  margin: 0;
  font-family: inherit;
  font-size: inherit;
  line-height: inherit;
}
.bodywebsite button,
.bodywebsite input {
  overflow: visible;
}
.bodywebsite button,
.bodywebsite select {
  text-transform: none;
}
.bodywebsite button,
html .bodywebsite [type="button"],
.bodywebsite [type="reset"],
.bodywebsite [type="submit"] {
  -webkit-appearance: button;
}
.bodywebsite button::-moz-focus-inner,
.bodywebsite [type="button"]::-moz-focus-inner,
.bodywebsite [type="reset"]::-moz-focus-inner,
.bodywebsite [type="submit"]::-moz-focus-inner {
  padding: 0;
  border-style: none;
}
.bodywebsite input[type="radio"],
.bodywebsite input[type="checkbox"] {
  box-sizing: border-box;
  padding: 0;
}
.bodywebsite input[type="date"],
.bodywebsite input[type="time"],
.bodywebsite input[type="datetime-local"],
.bodywebsite input[type="month"] {
  -webkit-appearance: listbox;
}
.bodywebsite textarea {
  overflow: auto;
  resize: vertical;
}
.bodywebsite fieldset {
  min-width: 0;
  padding: 0;
  margin: 0;
  border: 0;
}
.bodywebsite legend {
  display: block;
  width: 100%;
  max-width: 100%;
  padding: 0;
  margin-bottom: .5rem;
  font-size: 1.5rem;
  line-height: inherit;
  color: inherit;
  white-space: normal;
}
.bodywebsite progress {
  vertical-align: baseline;
}
.bodywebsite [type="number"]::-webkit-inner-spin-button,
.bodywebsite [type="number"]::-webkit-outer-spin-button {
  height: auto;
}
.bodywebsite [type="search"] {
  outline-offset: -2px;
  -webkit-appearance: none;
}
.bodywebsite [type="search"]::-webkit-search-cancel-button,
.bodywebsite [type="search"]::-webkit-search-decoration {
  -webkit-appearance: none;
}
.bodywebsite ::-webkit-file-upload-button {
  font: inherit;
  -webkit-appearance: button;
}
.bodywebsite output {
  display: inline-block;
}
.bodywebsite summary {
  display: list-item;
}
.bodywebsite template {
  display: none;
}
.bodywebsite [hidden] {
  display: none !important;
}
.bodywebsite h1,
.bodywebsite h2,
.bodywebsite h3,
.bodywebsite h4,
.bodywebsite h5,
.bodywebsite h6,
.bodywebsite .h1,
.bodywebsite .h2,
.bodywebsite .h3,
.bodywebsite .h4,
.bodywebsite .h5,
.bodywebsite .h6 {
  margin-bottom: 0.5rem;
  font-family: "Roboto", Helvetica, Arial, sans-serif;
  font-weight: 700;
  line-height: 1.1;
  color: #000;
}
.bodywebsite h1,
.bodywebsite .h1 {
  font-size: 45px;
}
.bodywebsite h2,
.bodywebsite .h2 {
  font-size: 24px;
}
.bodywebsite h3,
.bodywebsite .h3 {
  font-size: 33px;
}
.bodywebsite h4,
.bodywebsite .h4 {
  font-size: 18px;
}
.bodywebsite .lead {
  font-size: 24px;
  font-weight: 300;
}
.bodywebsite hr {
  margin-top: 1rem;
  margin-bottom: 1rem;
  border: 0;
  border-top: 1px solid #2a2b2b;
}
.bodywebsite small,
.bodywebsite .small {
  font-size: 80%;
  font-weight: 400;
}
.bodywebsite mark,
.bodywebsite .mark {
  padding: 5px 10px;
  background-color: #37c386;
}
.bodywebsite .list-unstyled {
  padding-left: 0;
  list-style: none;
}
.bodywebsite .list-inline {
  padding-left: 0;
  list-style: none;
}
.bodywebsite .list-inline-item {
  display: inline-block;
}
.bodywebsite .list-inline-item:not(:last-child) {
  margin-right: 5px;
}
.bodywebsite .initialism {
  font-size: 90%;
  text-transform: uppercase;
}
.bodywebsite .blockquote {
  margin-bottom: 1rem;
  font-size: 17.5px;
}
.bodywebsite .blockquote-footer {
  display: block;
  font-size: 80%;
  color: #dedede;
}
.bodywebsite .blockquote-footer::before {
  content: "\2014 \00A0";
}
.bodywebsite .img-fluid {
  max-width: 100%;
  height: auto;
}
.bodywebsite code,
.bodywebsite kbd,
.bodywebsite pre,
.bodywebsite samp {
  font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
}
.bodywebsite code {
  padding: 10px 5px;
  font-size: 90%;
  color: #00030a;
  background-color: #edeff4;
  border-radius: 0;
}
.bodywebsite a > code {
  padding: 0;
  color: inherit;
  background-color: inherit;
}
.bodywebsite kbd {
  padding: 10px 5px;
  font-size: 90%;
  color: #fff;
  background-color: #212529;
  border-radius: 3px;
  box-shadow: inset 0 -0.1rem 0 rgba(0, 0, 0, 0.25);
}
.bodywebsite kbd kbd {
  padding: 0;
  font-size: 100%;
  font-weight: 700;
  box-shadow: none;
}
.bodywebsite pre {
  display: block;
  margin-top: 0;
  margin-bottom: 1rem;
  font-size: 90%;
  color: #212529;
}
.bodywebsite pre code {
  padding: 0;
  font-size: inherit;
  color: inherit;
  background-color: transparent;
  border-radius: 0;
}
.bodywebsite .pre-scrollable {
  max-height: 340px;
  overflow-y: scroll;
}
.bodywebsite .container {
  width: 100%;
  padding-right: 15px;
  padding-left: 15px;
  margin-right: auto;
  margin-left: auto;
}
@media (min-width: 576px) {
  .bodywebsite .container {
	max-width: 540px;
  }
}
@media (min-width: 768px) {
  .bodywebsite .container {
	max-width: 720px;
  }
}
@media (min-width: 992px) {
  .bodywebsite .container {
	max-width: 960px;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .container {
	max-width: 1170px;
  }
}
.bodywebsite .container-fluid {
  width: 100%;
  padding-right: 15px;
  padding-left: 15px;
  margin-right: auto;
  margin-left: auto;
}
.bodywebsite .row {
  display: flex;
  flex-wrap: wrap;
  /*margin-right: -15px;
  margin-left: -15px; */
}
.bodywebsite .no-gutters {
  margin-right: 0;
  margin-left: 0;
}
.bodywebsite .no-gutters > .col,
.bodywebsite .no-gutters > [class*="col-"] {
  padding-right: 0;
  padding-left: 0;
}
.bodywebsite .col-1,
.bodywebsite .col-2,
.bodywebsite .col-3,
.bodywebsite .col-4,
.bodywebsite .col-5,
.bodywebsite .col-6,
.bodywebsite .col-7,
.bodywebsite .col-8,
.bodywebsite .col-9,
.bodywebsite .col-10,
.bodywebsite .col-11,
.bodywebsite .col-12,
.bodywebsite .col,
.bodywebsite .col-auto,
.bodywebsite .col-sm-1,
.bodywebsite .col-sm-2,
.bodywebsite .col-sm-3,
.bodywebsite .col-sm-4,
.bodywebsite .col-sm-5,
.bodywebsite .col-sm-6,
.bodywebsite .col-sm-7,
.bodywebsite .col-sm-8,
.bodywebsite .col-sm-9,
.bodywebsite .col-sm-10,
.bodywebsite .col-sm-11,
.bodywebsite .col-sm-12,
.bodywebsite .col-sm,
.bodywebsite .col-sm-auto,
.bodywebsite .col-md-1,
.bodywebsite .col-md-2,
.bodywebsite .col-md-3,
.bodywebsite .col-md-4,
.bodywebsite .col-md-5,
.bodywebsite .col-md-6,
.bodywebsite .col-md-7,
.bodywebsite .col-md-8,
.bodywebsite .col-md-9,
.bodywebsite .col-md-10,
.bodywebsite .col-md-11,
.bodywebsite .col-md-12,
.bodywebsite .col-md,
.bodywebsite .col-md-auto,
.bodywebsite .col-lg-1,
.bodywebsite .col-lg-2,
.bodywebsite .col-lg-3,
.bodywebsite .col-lg-4,
.bodywebsite .col-lg-5,
.bodywebsite .col-lg-6,
.bodywebsite .col-lg-7,
.bodywebsite .col-lg-8,
.bodywebsite .col-lg-9,
.bodywebsite .col-lg-10,
.bodywebsite .col-lg-11,
.bodywebsite .col-lg-12,
.bodywebsite .col-lg,
.bodywebsite .col-lg-auto,
.bodywebsite .col-xl-1,
.bodywebsite .col-xl-2,
.bodywebsite .col-xl-3,
.bodywebsite .col-xl-4,
.bodywebsite .col-xl-5,
.bodywebsite .col-xl-6,
.bodywebsite .col-xl-7,
.bodywebsite .col-xl-8,
.bodywebsite .col-xl-9,
.bodywebsite .col-xl-10,
.bodywebsite .col-xl-11,
.bodywebsite .col-xl-12,
.bodywebsite .col-xl,
.bodywebsite .col-xl-auto,
.bodywebsite .col-xxl-1,
.bodywebsite .col-xxl-2,
.bodywebsite .col-xxl-3,
.bodywebsite .col-xxl-4,
.bodywebsite .col-xxl-5,
.bodywebsite .col-xxl-6,
.bodywebsite .col-xxl-7,
.bodywebsite .col-xxl-8,
.bodywebsite .col-xxl-9,
.bodywebsite .col-xxl-10,
.bodywebsite .col-xxl-11,
.bodywebsite .col-xxl-12,
.bodywebsite .col-xxl,
.bodywebsite .col-xxl-auto {
  position: relative;
  width: 100%;
  min-height: 1px;
  padding-right: 15px;
  padding-left: 15px
}
.bodywebsite .col {
  flex-basis: 0;
  flex-grow: 1;
  max-width: 100%;
}
.bodywebsite .col-auto {
  flex: 0 0 auto;
  width: auto;
  max-width: none;
}
.bodywebsite .col-1 {
  flex: 0 0 8.33333%;
  max-width: 8.33333%;
}
.bodywebsite .col-2 {
  flex: 0 0 16.66667%;
  max-width: 16.66667%;
}
.bodywebsite .col-3 {
  flex: 0 0 25%;
  max-width: 25%;
}
.bodywebsite .col-4 {
  flex: 0 0 33.33333%;
  max-width: 33.33333%;
}
.bodywebsite .col-5 {
  flex: 0 0 41.66667%;
  max-width: 41.66667%;
}
.bodywebsite .col-6 {
  flex: 0 0 50%;
  max-width: 50%;
}
.bodywebsite .col-7 {
  flex: 0 0 58.33333%;
  max-width: 58.33333%;
}
.bodywebsite .col-8 {
  flex: 0 0 66.66667%;
  max-width: 66.66667%;
}
.bodywebsite .col-9 {
  flex: 0 0 75%;
  max-width: 75%;
}
.bodywebsite .col-10 {
  flex: 0 0 83.33333%;
  max-width: 83.33333%;
}
.bodywebsite .col-11 {
  flex: 0 0 91.66667%;
  max-width: 91.66667%;
}
.bodywebsite .col-12 {
  flex: 0 0 100%;
  max-width: 100%;
}
.bodywebsite .order-first {
  order: -1;
}
.bodywebsite .order-1 {
  order: 1;
}
.bodywebsite .order-2 {
  order: 2;
}
.bodywebsite .order-3 {
  order: 3;
}
.bodywebsite .order-4 {
  order: 4;
}
.bodywebsite .order-5 {
  order: 5;
}
.bodywebsite .order-6 {
  order: 6;
}
.bodywebsite .order-7 {
  order: 7;
}
.bodywebsite .order-8 {
  order: 8;
}
.bodywebsite .order-9 {
  order: 9;
}
.bodywebsite .order-10 {
  order: 10;
}
.bodywebsite .order-11 {
  order: 11;
}
.bodywebsite .order-12 {
  order: 12;
}
.bodywebsite .offset-1 {
  margin-left: 8.33333%;
}
.bodywebsite .offset-2 {
  margin-left: 16.66667%;
}
.bodywebsite .offset-3 {
  margin-left: 25%;
}
.bodywebsite .offset-4 {
  margin-left: 33.33333%;
}
.bodywebsite .offset-5 {
  margin-left: 41.66667%;
}
.bodywebsite .offset-6 {
  margin-left: 50%;
}
.bodywebsite .offset-7 {
  margin-left: 58.33333%;
}
.bodywebsite .offset-8 {
  margin-left: 66.66667%;
}
.bodywebsite .offset-9 {
  margin-left: 75%;
}
.bodywebsite .offset-10 {
  margin-left: 83.33333%;
}
.bodywebsite .offset-11 {
  margin-left: 91.66667%;
}
@media (min-width: 576px) {
  .bodywebsite .col-sm {
	flex-basis: 0;
	flex-grow: 1;
	max-width: 100%;
  }
  .bodywebsite .col-sm-auto {
	flex: 0 0 auto;
	width: auto;
	max-width: none;
  }
  .bodywebsite .col-sm-1 {
	flex: 0 0 8.33333%;
	max-width: 8.33333%;
  }
  .bodywebsite .col-sm-2 {
	flex: 0 0 16.66667%;
	max-width: 16.66667%;
  }
  .bodywebsite .col-sm-3 {
	flex: 0 0 25%;
	max-width: 25%;
  }
  .bodywebsite .col-sm-4 {
	flex: 0 0 33.33333%;
	max-width: 33.33333%;
  }
  .bodywebsite .col-sm-5 {
	flex: 0 0 41.66667%;
	max-width: 41.66667%;
  }
  .bodywebsite .col-sm-6 {
	flex: 0 0 50%;
	max-width: 50%;
  }
  .bodywebsite .col-sm-7 {
	flex: 0 0 58.33333%;
	max-width: 58.33333%;
  }
  .bodywebsite .col-sm-8 {
	flex: 0 0 66.66667%;
	max-width: 66.66667%;
  }
  .bodywebsite .col-sm-9 {
	flex: 0 0 75%;
	max-width: 75%;
  }
  .bodywebsite .col-sm-10 {
	flex: 0 0 83.33333%;
	max-width: 83.33333%;
  }
  .bodywebsite .col-sm-11 {
	flex: 0 0 91.66667%;
	max-width: 91.66667%;
  }
  .bodywebsite .col-sm-12 {
	flex: 0 0 100%;
	max-width: 100%;
  }
  .bodywebsite .order-sm-first {
	order: -1;
  }
  .bodywebsite .order-sm-1 {
	order: 1;
  }
  .bodywebsite .order-sm-2 {
	order: 2;
  }
  .bodywebsite .order-sm-3 {
	order: 3;
  }
  .bodywebsite .order-sm-4 {
	order: 4;
  }
  .bodywebsite .order-sm-5 {
	order: 5;
  }
  .bodywebsite .order-sm-6 {
	order: 6;
  }
  .bodywebsite .order-sm-7 {
	order: 7;
  }
  .bodywebsite .order-sm-8 {
	order: 8;
  }
  .bodywebsite .order-sm-9 {
	order: 9;
  }
  .bodywebsite .order-sm-10 {
	order: 10;
  }
  .bodywebsite .order-sm-11 {
	order: 11;
  }
  .bodywebsite .order-sm-12 {
	order: 12;
  }
  .bodywebsite .offset-sm-0 {
	margin-left: 0;
  }
  .bodywebsite .offset-sm-1 {
	margin-left: 8.33333%;
  }
  .bodywebsite .offset-sm-2 {
	margin-left: 16.66667%;
  }
  .bodywebsite .offset-sm-3 {
	margin-left: 25%;
  }
  .bodywebsite .offset-sm-4 {
	margin-left: 33.33333%;
  }
  .bodywebsite .offset-sm-5 {
	margin-left: 41.66667%;
  }
  .bodywebsite .offset-sm-6 {
	margin-left: 50%;
  }
  .bodywebsite .offset-sm-7 {
	margin-left: 58.33333%;
  }
  .bodywebsite .offset-sm-8 {
	margin-left: 66.66667%;
  }
  .bodywebsite .offset-sm-9 {
	margin-left: 75%;
  }
  .bodywebsite .offset-sm-10 {
	margin-left: 83.33333%;
  }
  .bodywebsite .offset-sm-11 {
	margin-left: 91.66667%;
  }
}
@media (min-width: 768px) {
  .bodywebsite .col-md {
	flex-basis: 0;
	flex-grow: 1;
	max-width: 100%;
  }
  .bodywebsite .col-md-auto {
	flex: 0 0 auto;
	width: auto;
	max-width: none;
  }
  .bodywebsite .col-md-1 {
	flex: 0 0 8.33333%;
	max-width: 8.33333%;
  }
  .bodywebsite .col-md-2 {
	flex: 0 0 16.66667%;
	max-width: 16.66667%;
  }
  .bodywebsite .col-md-3 {
	flex: 0 0 25%;
	max-width: 25%;
  }
  .bodywebsite .col-md-4 {
	flex: 0 0 33.33333%;
	max-width: 33.33333%;
  }
  .bodywebsite .col-md-5 {
	flex: 0 0 41.66667%;
	max-width: 41.66667%;
  }
  .bodywebsite .col-md-6 {
	flex: 0 0 50%;
	max-width: 50%;
  }
  .bodywebsite .col-md-7 {
	flex: 0 0 58.33333%;
	max-width: 58.33333%;
  }
  .bodywebsite .col-md-8 {
	flex: 0 0 66.66667%;
	max-width: 66.66667%;
  }
  .bodywebsite .col-md-9 {
	flex: 0 0 75%;
	max-width: 75%;
  }
  .bodywebsite .col-md-10 {
	flex: 0 0 83.33333%;
	max-width: 83.33333%;
  }
  .bodywebsite .col-md-11 {
	flex: 0 0 91.66667%;
	max-width: 91.66667%;
  }
  .bodywebsite .col-md-12 {
	flex: 0 0 100%;
	max-width: 100%;
  }
  .bodywebsite .order-md-first {
	order: -1;
  }
  .bodywebsite .order-md-1 {
	order: 1;
  }
  .bodywebsite .order-md-2 {
	order: 2;
  }
  .bodywebsite .order-md-3 {
	order: 3;
  }
  .bodywebsite .order-md-4 {
	order: 4;
  }
  .bodywebsite .order-md-5 {
	order: 5;
  }
  .bodywebsite .order-md-6 {
	order: 6;
  }
  .bodywebsite .order-md-7 {
	order: 7;
  }
  .bodywebsite .order-md-8 {
	order: 8;
  }
  .bodywebsite .order-md-9 {
	order: 9;
  }
  .bodywebsite .order-md-10 {
	order: 10;
  }
  .bodywebsite .order-md-11 {
	order: 11;
  }
  .bodywebsite .order-md-12 {
	order: 12;
  }
  .bodywebsite .offset-md-0 {
	margin-left: 0;
  }
  .bodywebsite .offset-md-1 {
	margin-left: 8.33333%;
  }
  .bodywebsite .offset-md-2 {
	margin-left: 16.66667%;
  }
  .bodywebsite .offset-md-3 {
	margin-left: 25%;
  }
  .bodywebsite .offset-md-4 {
	margin-left: 33.33333%;
  }
  .bodywebsite .offset-md-5 {
	margin-left: 41.66667%;
  }
  .bodywebsite .offset-md-6 {
	margin-left: 50%;
  }
  .bodywebsite .offset-md-7 {
	margin-left: 58.33333%;
  }
  .bodywebsite .offset-md-8 {
	margin-left: 66.66667%;
  }
  .bodywebsite .offset-md-9 {
	margin-left: 75%;
  }
  .bodywebsite .offset-md-10 {
	margin-left: 83.33333%;
  }
  .bodywebsite .offset-md-11 {
	margin-left: 91.66667%;
  }
}
@media (min-width: 992px) {
  .bodywebsite .col-lg {
	flex-basis: 0;
	flex-grow: 1;
	max-width: 100%;
  }
  .bodywebsite .col-lg-auto {
	flex: 0 0 auto;
	width: auto;
	max-width: none;
  }
  .bodywebsite .col-lg-1 {
	flex: 0 0 8.33333%;
	max-width: 8.33333%;
  }
  .bodywebsite .col-lg-2 {
	flex: 0 0 16.66667%;
	max-width: 16.66667%;
  }
  .bodywebsite .col-lg-3 {
	flex: 0 0 25%;
	max-width: 25%;
  }
  .bodywebsite .col-lg-4 {
	flex: 0 0 33.33333%;
	max-width: 33.33333%;
  }
  .bodywebsite .col-lg-5 {
	flex: 0 0 41.66667%;
	max-width: 41.66667%;
  }
  .bodywebsite .col-lg-6 {
	flex: 0 0 50%;
	max-width: 50%;
  }
  .bodywebsite .col-lg-7 {
	flex: 0 0 58.33333%;
	max-width: 58.33333%;
  }
  .bodywebsite .col-lg-8 {
	flex: 0 0 66.66667%;
	max-width: 66.66667%;
  }
  .bodywebsite .col-lg-9 {
	flex: 0 0 75%;
	max-width: 75%;
  }
  .bodywebsite .col-lg-10 {
	flex: 0 0 83.33333%;
	max-width: 83.33333%;
  }
  .bodywebsite .col-lg-11 {
	flex: 0 0 91.66667%;
	max-width: 91.66667%;
  }
  .bodywebsite .col-lg-12 {
	flex: 0 0 100%;
	max-width: 100%;
  }
  .bodywebsite .order-lg-first {
	order: -1;
  }
  .bodywebsite .order-lg-1 {
	order: 1;
  }
  .bodywebsite .order-lg-2 {
	order: 2;
  }
  .bodywebsite .order-lg-3 {
	order: 3;
  }
  .bodywebsite .order-lg-4 {
	order: 4;
  }
  .bodywebsite .order-lg-5 {
	order: 5;
  }
  .bodywebsite .order-lg-6 {
	order: 6;
  }
  .bodywebsite .order-lg-7 {
	order: 7;
  }
  .bodywebsite .order-lg-8 {
	order: 8;
  }
  .bodywebsite .order-lg-9 {
	order: 9;
  }
  .bodywebsite .order-lg-10 {
	order: 10;
  }
  .bodywebsite .order-lg-11 {
	order: 11;
  }
  .bodywebsite .order-lg-12 {
	order: 12;
  }
  .bodywebsite .offset-lg-0 {
	margin-left: 0;
  }
  .bodywebsite .offset-lg-1 {
	margin-left: 8.33333%;
  }
  .bodywebsite .offset-lg-2 {
	margin-left: 16.66667%;
  }
  .bodywebsite .offset-lg-3 {
	margin-left: 25%;
  }
  .bodywebsite .offset-lg-4 {
	margin-left: 33.33333%;
  }
  .bodywebsite .offset-lg-5 {
	margin-left: 41.66667%;
  }
  .bodywebsite .offset-lg-6 {
	margin-left: 50%;
  }
  .bodywebsite .offset-lg-7 {
	margin-left: 58.33333%;
  }
  .bodywebsite .offset-lg-8 {
	margin-left: 66.66667%;
  }
  .bodywebsite .offset-lg-9 {
	margin-left: 75%;
  }
  .bodywebsite .offset-lg-10 {
	margin-left: 83.33333%;
  }
  .bodywebsite .offset-lg-11 {
	margin-left: 91.66667%;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .col-xl {
	flex-basis: 0;
	flex-grow: 1;
	max-width: 100%;
  }
  .bodywebsite .col-xl-auto {
	flex: 0 0 auto;
	width: auto;
	max-width: none;
  }
  .bodywebsite .col-xl-1 {
	flex: 0 0 8.33333%;
	max-width: 8.33333%;
  }
  .bodywebsite .col-xl-2 {
	flex: 0 0 16.66667%;
	max-width: 16.66667%;
  }
  .bodywebsite .col-xl-3 {
	flex: 0 0 25%;
	max-width: 25%;
  }
  .bodywebsite .col-xl-4 {
	flex: 0 0 33.33333%;
	max-width: 33.33333%;
  }
  .bodywebsite .col-xl-5 {
	flex: 0 0 41.66667%;
	max-width: 41.66667%;
  }
  .bodywebsite .col-xl-6 {
	flex: 0 0 50%;
	max-width: 50%;
  }
  .bodywebsite .col-xl-7 {
	flex: 0 0 58.33333%;
	max-width: 58.33333%;
  }
  .bodywebsite .col-xl-8 {
	flex: 0 0 66.66667%;
	max-width: 66.66667%;
  }
  .bodywebsite .col-xl-9 {
	flex: 0 0 75%;
	max-width: 75%;
  }
  .bodywebsite .col-xl-10 {
	flex: 0 0 83.33333%;
	max-width: 83.33333%;
  }
  .bodywebsite .col-xl-11 {
	flex: 0 0 91.66667%;
	max-width: 91.66667%;
  }
  .bodywebsite .col-xl-12 {
	flex: 0 0 100%;
	max-width: 100%;
  }
  .bodywebsite .order-xl-first {
	order: -1;
  }
  .bodywebsite .order-xl-1 {
	order: 1;
  }
  .bodywebsite .order-xl-2 {
	order: 2;
  }
  .bodywebsite .order-xl-3 {
	order: 3;
  }
  .bodywebsite .order-xl-4 {
	order: 4;
  }
  .bodywebsite .order-xl-5 {
	order: 5;
  }
  .bodywebsite .order-xl-6 {
	order: 6;
  }
  .bodywebsite .order-xl-7 {
	order: 7;
  }
  .bodywebsite .order-xl-8 {
	order: 8;
  }
  .bodywebsite .order-xl-9 {
	order: 9;
  }
  .bodywebsite .order-xl-10 {
	order: 10;
  }
  .bodywebsite .order-xl-11 {
	order: 11;
  }
  .bodywebsite .order-xl-12 {
	order: 12;
  }
  .bodywebsite .offset-xl-0 {
	margin-left: 0;
  }
  .bodywebsite .offset-xl-1 {
	margin-left: 8.33333%;
  }
  .bodywebsite .offset-xl-2 {
	margin-left: 16.66667%;
  }
  .bodywebsite .offset-xl-3 {
	margin-left: 25%;
  }
  .bodywebsite .offset-xl-4 {
	margin-left: 33.33333%;
  }
  .bodywebsite .offset-xl-5 {
	margin-left: 41.66667%;
  }
  .bodywebsite .offset-xl-6 {
	margin-left: 50%;
  }
  .bodywebsite .offset-xl-7 {
	margin-left: 58.33333%;
  }
  .bodywebsite .offset-xl-8 {
	margin-left: 66.66667%;
  }
  .bodywebsite .offset-xl-9 {
	margin-left: 75%;
  }
  .bodywebsite .offset-xl-10 {
	margin-left: 83.33333%;
  }
  .bodywebsite .offset-xl-11 {
	margin-left: 91.66667%;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .col-xxl {
	flex-basis: 0;
	flex-grow: 1;
	max-width: 100%;
  }
  .bodywebsite .col-xxl-auto {
	flex: 0 0 auto;
	width: auto;
	max-width: none;
  }
  .bodywebsite .col-xxl-1 {
	flex: 0 0 8.33333%;
	max-width: 8.33333%;
  }
  .bodywebsite .col-xxl-2 {
	flex: 0 0 16.66667%;
	max-width: 16.66667%;
  }
  .bodywebsite .col-xxl-3 {
	flex: 0 0 25%;
	max-width: 25%;
  }
  .bodywebsite .col-xxl-4 {
	flex: 0 0 33.33333%;
	max-width: 33.33333%;
  }
  .bodywebsite .col-xxl-5 {
	flex: 0 0 41.66667%;
	max-width: 41.66667%;
  }
  .bodywebsite .col-xxl-6 {
	flex: 0 0 50%;
	max-width: 50%;
  }
  .bodywebsite .col-xxl-7 {
	flex: 0 0 58.33333%;
	max-width: 58.33333%;
  }
  .bodywebsite .col-xxl-8 {
	flex: 0 0 66.66667%;
	max-width: 66.66667%;
  }
  .bodywebsite .col-xxl-9 {
	flex: 0 0 75%;
	max-width: 75%;
  }
  .bodywebsite .col-xxl-10 {
	flex: 0 0 83.33333%;
	max-width: 83.33333%;
  }
  .bodywebsite .col-xxl-11 {
	flex: 0 0 91.66667%;
	max-width: 91.66667%;
  }
  .bodywebsite .col-xxl-12 {
	flex: 0 0 100%;
	max-width: 100%;
  }
  .bodywebsite .order-xxl-first {
	order: -1;
  }
  .bodywebsite .order-xxl-1 {
	order: 1;
  }
  .bodywebsite .order-xxl-2 {
	order: 2;
  }
  .bodywebsite .order-xxl-3 {
	order: 3;
  }
  .bodywebsite .order-xxl-4 {
	order: 4;
  }
  .bodywebsite .order-xxl-5 {
	order: 5;
  }
  .bodywebsite .order-xxl-6 {
	order: 6;
  }
  .bodywebsite .order-xxl-7 {
	order: 7;
  }
  .bodywebsite .order-xxl-8 {
	order: 8;
  }
  .bodywebsite .order-xxl-9 {
	order: 9;
  }
  .bodywebsite .order-xxl-10 {
	order: 10;
  }
  .bodywebsite .order-xxl-11 {
	order: 11;
  }
  .bodywebsite .order-xxl-12 {
	order: 12;
  }
  .bodywebsite .offset-xxl-0 {
	margin-left: 0;
  }
  .bodywebsite .offset-xxl-1 {
	margin-left: 8.33333%;
  }
  .bodywebsite .offset-xxl-2 {
	margin-left: 16.66667%;
  }
  .bodywebsite .offset-xxl-3 {
	margin-left: 25%;
  }
  .bodywebsite .offset-xxl-4 {
	margin-left: 33.33333%;
  }
  .bodywebsite .offset-xxl-5 {
	margin-left: 41.66667%;
  }
  .bodywebsite .offset-xxl-6 {
	margin-left: 50%;
  }
  .bodywebsite .offset-xxl-7 {
	margin-left: 58.33333%;
  }
  .bodywebsite .offset-xxl-8 {
	margin-left: 66.66667%;
  }
  .bodywebsite .offset-xxl-9 {
	margin-left: 75%;
  }
  .bodywebsite .offset-xxl-10 {
	margin-left: 83.33333%;
  }
  .bodywebsite .offset-xxl-11 {
	margin-left: 91.66667%;
  }
}
.bodywebsite .table {
  width: 100%;
  max-width: 100%;
  margin-bottom: 1rem;
  background-color: transparent;
}
.bodywebsite .table th,
.bodywebsite .table td {
  padding: 17px 25px 18px;
  vertical-align: top;
  border-top: 1px solid #d9d9d9;
}
.bodywebsite .table thead th {
  vertical-align: bottom;
  border-bottom: 2px solid #d9d9d9;
}
.bodywebsite .table tbody + tbody {
  border-top: 2px solid #d9d9d9;
}
.bodywebsite .table .table {
  background-color: #fff;
}
.bodywebsite .table-sm th,
.bodywebsite .table-sm td {
  padding: 0.3rem;
}
.bodywebsite .table-bordered {
  border: 1px solid #d9d9d9;
}
.bodywebsite .table-bordered th,
.bodywebsite .table-bordered td {
  border: 1px solid #d9d9d9;
}
.bodywebsite .table-bordered thead th,
.bodywebsite .table-bordered thead td {
  border-bottom-width: 2px;
}
.bodywebsite .table-striped tbody tr:nth-of-type(odd) {
  background-color: rgba(0, 0, 0, 0.05);
}
.bodywebsite .table-hover tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.075);
}
.bodywebsite .table-primary,
.bodywebsite .table-primary > th,
.bodywebsite .table-primary > td {
  background-color: #b8daff;
}
.bodywebsite .table-hover .table-primary:hover {
  background-color: #9fcdff;
}
.bodywebsite .table-hover .table-primary:hover > td,
.bodywebsite .table-hover .table-primary:hover > th {
  background-color: #9fcdff;
}
.bodywebsite .table-secondary,
.bodywebsite .table-secondary > th,
.bodywebsite .table-secondary > td {
  background-color: #dddfe2;
}
.bodywebsite .table-hover .table-secondary:hover {
  background-color: #cfd2d6;
}
.bodywebsite .table-hover .table-secondary:hover > td,
.bodywebsite .table-hover .table-secondary:hover > th {
  background-color: #cfd2d6;
}
.bodywebsite .table-success,
.bodywebsite .table-success > th,
.bodywebsite .table-success > td {
  background-color: #c3e6cb;
}
.bodywebsite .table-hover .table-success:hover {
  background-color: #b1dfbb;
}
.bodywebsite .table-hover .table-success:hover > td,
.bodywebsite .table-hover .table-success:hover > th {
  background-color: #b1dfbb;
}
.bodywebsite .table-info,
.bodywebsite .table-info > th,
.bodywebsite .table-info > td {
  background-color: #bee5eb;
}
.bodywebsite .table-hover .table-info:hover {
  background-color: #abdde5;
}
.bodywebsite .table-hover .table-info:hover > td,
.bodywebsite .table-hover .table-info:hover > th {
  background-color: #abdde5;
}
.bodywebsite .table-warning,
.bodywebsite .table-warning > th,
.bodywebsite .table-warning > td {
  background-color: #ffeeba;
}
.bodywebsite .table-hover .table-warning:hover {
  background-color: #ffe8a1;
}
.bodywebsite .table-hover .table-warning:hover > td,
.bodywebsite .table-hover .table-warning:hover > th {
  background-color: #ffe8a1;
}
.bodywebsite .table-danger,
.bodywebsite .table-danger > th,
.bodywebsite .table-danger > td {
  background-color: #f5c6cb;
}
.bodywebsite .table-hover .table-danger:hover {
  background-color: #f1b0b7;
}
.bodywebsite .table-hover .table-danger:hover > td,
.bodywebsite .table-hover .table-danger:hover > th {
  background-color: #f1b0b7;
}
.bodywebsite .table-light,
.bodywebsite .table-light > th,
.bodywebsite .table-light > td {
  background-color: #fdfdfe;
}
.bodywebsite .table-hover .table-light:hover {
  background-color: #ececf6;
}
.bodywebsite .table-hover .table-light:hover > td,
.bodywebsite .table-hover .table-light:hover > th {
  background-color: #ececf6;
}
.bodywebsite .table-dark,
.bodywebsite .table-dark > th,
.bodywebsite .table-dark > td {
  background-color: #c6c8ca;
}
.bodywebsite .table-hover .table-dark:hover {
  background-color: #b9bbbe;
}
.bodywebsite .table-hover .table-dark:hover > td,
.bodywebsite .table-hover .table-dark:hover > th {
  background-color: #b9bbbe;
}
.bodywebsite .table-active,
.bodywebsite .table-active > th,
.bodywebsite .table-active > td {
  background-color: rgba(0, 0, 0, 0.075);
}
.bodywebsite .table-hover .table-active:hover {
  background-color: rgba(0, 0, 0, 0.075);
}
.bodywebsite .table-hover .table-active:hover > td,
.bodywebsite .table-hover .table-active:hover > th {
  background-color: rgba(0, 0, 0, 0.075);
}
.bodywebsite .table .thead-dark th {
  color: #fff;
  background-color: #212529;
  border-color: #32383e;
}
.bodywebsite .table .thead-light th {
  color: #495057;
  background-color: #e9ecef;
  border-color: #d9d9d9;
}
.bodywebsite .table-dark {
  color: #fff;
  background-color: #212529;
}
.bodywebsite .table-dark th,
.bodywebsite .table-dark td,
.bodywebsite .table-dark thead th {
  border-color: #32383e;
}
.bodywebsite .table-dark.table-bordered {
  border: 0;
}
.bodywebsite .table-dark.table-striped tbody tr:nth-of-type(odd) {
  background-color: rgba(255, 255, 255, 0.05);
}
.bodywebsite .table-dark.table-hover tbody tr:hover {
  background-color: rgba(255, 255, 255, 0.075);
}
@media (max-width: 575px) {
  .bodywebsite .table-responsive-sm {
	display: block;
	width: 100%;
	overflow-x: auto;
	-webkit-overflow-scrolling: touch;
	-ms-overflow-style: -ms-autohiding-scrollbar;
  }
  .bodywebsite .table-responsive-sm.table-bordered {
	border: 0;
  }
}
@media (max-width: 767px) {
  .bodywebsite .table-responsive-md {
	display: block;
	width: 100%;
	overflow-x: auto;
	-webkit-overflow-scrolling: touch;
	-ms-overflow-style: -ms-autohiding-scrollbar;
  }
  .bodywebsite .table-responsive-md.table-bordered {
	border: 0;
  }
}
@media (max-width: 991px) {
  .bodywebsite .table-responsive-lg {
	display: block;
	width: 100%;
	overflow-x: auto;
	-webkit-overflow-scrolling: touch;
	-ms-overflow-style: -ms-autohiding-scrollbar;
  }
  .bodywebsite .table-responsive-lg.table-bordered {
	border: 0;
  }
}
@media (max-width: 1199px) {
  .bodywebsite .table-responsive-xl {
	display: block;
	width: 100%;
	overflow-x: auto;
	-webkit-overflow-scrolling: touch;
	-ms-overflow-style: -ms-autohiding-scrollbar;
  }
  .bodywebsite .table-responsive-xl.table-bordered {
	border: 0;
  }
}
@media (max-width: 1799px) {
  .bodywebsite .table-responsive-xxl {
	display: block;
	width: 100%;
	overflow-x: auto;
	-webkit-overflow-scrolling: touch;
	-ms-overflow-style: -ms-autohiding-scrollbar;
  }
  .bodywebsite .table-responsive-xxl.table-bordered {
	border: 0;
  }
}
.bodywebsite .table-responsive {
  display: block;
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  -ms-overflow-style: -ms-autohiding-scrollbar;
}
.bodywebsite .table-responsive.table-bordered {
  border: 0;
}
.bodywebsite .form-input {
  display: block;
  width: 100%;
  padding: 11px 35px;
  font-size: 14px;
  line-height: 1.25;
  background-image: none;
  background-clip: padding-box;
  border: 1px solid #ced4da;
  border-radius: 0.25rem;
  box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
  transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
}
.bodywebsite .form-input::-ms-expand {
  background-color: transparent;
  border: 0;
}
.bodywebsite .form-input:focus {
  color: #495057;
  background-color: #fff;
  border-color: #80bdff;
  outline: none;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
.bodywebsite .form-input::placeholder {
  color: #868e96;
  opacity: 1;
}
.bodywebsite .form-input:disabled,
.bodywebsite .form-input[readonly] {
  background-color: #e9ecef;
  opacity: 1;
}
.bodywebsite select.form-input:not([size]):not([multiple]) {
  height: calc(4.25rem);
}
.bodywebsite .form-input-file,
.bodywebsite .form-input-range {
  display: block;
}
.bodywebsite .col-form-label {
  padding-top: calc(12px);
  padding-bottom: calc(12px);
  margin-bottom: 0;
  line-height: 1.25;
}
.bodywebsite .col-form-label-lg {
  padding-top: calc(13px);
  padding-bottom: calc(13px);
  font-size: 18px;
  line-height: 1.5;
}
.bodywebsite .col-form-label-sm {
  padding-top: calc(6px);
  padding-bottom: calc(6px);
  font-size: 12px;
  line-height: 1.5;
}
.bodywebsite .col-form-legend {
  padding-top: 11px;
  padding-bottom: 11px;
  margin-bottom: 0;
  font-size: 14px;
}
.bodywebsite .form-input-plaintext {
  padding-top: 11px;
  padding-bottom: 11px;
  margin-bottom: 0;
  line-height: 1.25;
  background-color: transparent;
  border: solid transparent;
  border-width: 1px 0;
}
.bodywebsite .form-input-plaintext.form-input-sm,
.bodywebsite .input-group-sm > .form-input-plaintext.form-input,
.bodywebsite .input-group-sm > .form-input-plaintext.input-group-addon,
.bodywebsite .input-group-sm > .input-group-btn > .form-input-plaintext.btn,
.bodywebsite .form-input-plaintext.form-input-lg,
.bodywebsite .input-group-lg > .form-input-plaintext.form-input,
.bodywebsite .input-group-lg > .form-input-plaintext.input-group-addon,
.bodywebsite .input-group-lg > .input-group-btn > .form-input-plaintext.btn {
  padding-right: 0;
  padding-left: 0;
}
.bodywebsite .form-input-sm,
.bodywebsite .input-group-sm > .form-input,
.bodywebsite .input-group-sm > .input-group-addon,
.bodywebsite .input-group-sm > .input-group-btn > .btn {
  padding: 5px 25px;
  font-size: 12px;
  line-height: 1.5;
  border-radius: 0.2rem;
}
.bodywebsite select.form-input-sm:not([size]):not([multiple]),
.bodywebsite .input-group-sm > select.form-input:not([size]):not([multiple]),
.bodywebsite .input-group-sm > select.input-group-addon:not([size]):not([multiple]),
.bodywebsite .input-group-sm > .input-group-btn > select.btn:not([size]):not([multiple]) {
  height: calc(3.8125rem);
}
.bodywebsite .form-input-lg,
.bodywebsite .input-group-lg > .form-input,
.bodywebsite .input-group-lg > .input-group-addon,
.bodywebsite .input-group-lg > .input-group-btn > .btn {
  padding: 12px 50px;
  font-size: 18px;
  line-height: 1.5;
  border-radius: 0.3rem;
}
.bodywebsite select.form-input-lg:not([size]):not([multiple]),
.bodywebsite .input-group-lg > select.form-input:not([size]):not([multiple]),
.bodywebsite .input-group-lg > select.input-group-addon:not([size]):not([multiple]),
.bodywebsite .input-group-lg > .input-group-btn > select.btn:not([size]):not([multiple]) {
  height: calc(4.875rem);
}
.bodywebsite .form-label {
  margin-bottom: 1rem;
}
.bodywebsite .form-text {
  display: block;
  margin-top: 0.25rem;
}
.bodywebsite .form-row {
  display: flex;
  flex-wrap: wrap;
  margin-right: -5px;
  margin-left: -5px;
}
.bodywebsite .form-row > .col,
.bodywebsite .form-row > [class*="col-"] {
  padding-right: 5px;
  padding-left: 5px;
}
.bodywebsite .form-check {
  position: relative;
  display: block;
  margin-bottom: 0.5rem;
}
.bodywebsite .form-check.disabled .form-check-label {
  color: #dedede;
}
.bodywebsite .form-check-label {
  padding-left: 1.25rem;
  margin-bottom: 0;
}
.bodywebsite .form-check-input {
  position: absolute;
  margin-top: 0.25rem;
  margin-left: -1.25rem;
}
.bodywebsite .form-check-inline {
  display: inline-block;
  margin-right: 0.75rem;
}
.bodywebsite .form-check-inline .form-check-label {
  vertical-align: middle;
}
.bodywebsite .was-validated .form-input:valid,
.bodywebsite .form-input.is-valid,
.bodywebsite .was-validated .custom-select:valid,
.bodywebsite .custom-select.is-valid {
  border-color: #98bf44;
}
.bodywebsite .was-validated .form-input:valid:focus,
.bodywebsite .form-input.is-valid:focus,
.bodywebsite .was-validated .custom-select:valid:focus,
.bodywebsite .custom-select.is-valid:focus {
  box-shadow: 0 0 0 0.2rem rgba(152, 191, 68, 0.25);
}
.bodywebsite .was-validated .form-check-input:valid + .form-check-label,
.bodywebsite .form-check-input.is-valid + .form-check-label {
  color: #98bf44;
}
.bodywebsite .was-validated .custom-control-input:valid ~ .custom-control-indicator,
.bodywebsite .custom-control-input.is-valid ~ .custom-control-indicator {
  background-color: rgba(152, 191, 68, 0.25);
}
.bodywebsite .was-validated .custom-control-input:valid ~ .custom-control-description,
.bodywebsite .custom-control-input.is-valid ~ .custom-control-description {
  color: #98bf44;
}
.bodywebsite .was-validated .custom-file-input:valid ~ .custom-file-control,
.bodywebsite .custom-file-input.is-valid ~ .custom-file-control {
  border-color: #98bf44;
}
.bodywebsite .was-validated .custom-file-input:valid ~ .custom-file-control::before,
.bodywebsite .custom-file-input.is-valid ~ .custom-file-control::before {
  border-color: inherit;
}
.bodywebsite .was-validated .custom-file-input:valid:focus,
.bodywebsite .custom-file-input.is-valid:focus {
  box-shadow: 0 0 0 0.2rem rgba(152, 191, 68, 0.25);
}
.bodywebsite .was-validated .form-input:invalid,
.bodywebsite .form-input.is-invalid,
.bodywebsite .was-validated .custom-select:invalid,
.bodywebsite .custom-select.is-invalid {
  border-color: #f5543f;
}
.bodywebsite .was-validated .form-input:invalid:focus,
.bodywebsite .form-input.is-invalid:focus,
.bodywebsite .was-validated .custom-select:invalid:focus,
.bodywebsite .custom-select.is-invalid:focus {
  box-shadow: 0 0 0 0.2rem rgba(245, 84, 63, 0.25);
}
.bodywebsite .was-validated .form-check-input:invalid + .form-check-label,
.bodywebsite .form-check-input.is-invalid + .form-check-label {
  color: #f5543f;
}
.bodywebsite .was-validated .custom-control-input:invalid ~ .custom-control-indicator,
.bodywebsite .custom-control-input.is-invalid ~ .custom-control-indicator {
  background-color: rgba(245, 84, 63, 0.25);
}
.bodywebsite .was-validated .custom-control-input:invalid ~ .custom-control-description,
.bodywebsite .custom-control-input.is-invalid ~ .custom-control-description {
  color: #f5543f;
}
.bodywebsite .was-validated .custom-file-input:invalid ~ .custom-file-control,
.bodywebsite .custom-file-input.is-invalid ~ .custom-file-control {
  border-color: #f5543f;
}
.bodywebsite .was-validated .custom-file-input:invalid ~ .custom-file-control::before,
.bodywebsite .custom-file-input.is-invalid ~ .custom-file-control::before {
  border-color: inherit;
}
.bodywebsite .was-validated .custom-file-input:invalid:focus,
.bodywebsite .custom-file-input.is-invalid:focus {
  box-shadow: 0 0 0 0.2rem rgba(245, 84, 63, 0.25);
}
.bodywebsite .form-inline {
  display: flex;
  flex-flow: row wrap;
  align-items: center;
}
.bodywebsite .form-inline .form-check {
  width: 100%;
}
@media (min-width: 576px) {
  .bodywebsite .form-inline label {
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 0;
  }
  .bodywebsite .form-inline .form-label {
	display: flex;
	flex: 0 0 auto;
	flex-flow: row wrap;
	align-items: center;
	margin-bottom: 0;
  }
  .bodywebsite .form-inline .form-input {
	display: inline-block;
	width: auto;
	vertical-align: middle;
  }
  .bodywebsite .form-inline .form-input-plaintext {
	display: inline-block;
  }
  .bodywebsite .form-inline .input-group {
	width: auto;
  }
  .bodywebsite .form-inline .form-check {
	display: flex;
	align-items: center;
	justify-content: center;
	width: auto;
	margin-top: 0;
	margin-bottom: 0;
  }
  .bodywebsite .form-inline .form-check-label {
	padding-left: 0;
  }
  .bodywebsite .form-inline .form-check-input {
	position: relative;
	margin-top: 0;
	margin-right: 0.25rem;
	margin-left: 0;
  }
  .bodywebsite .form-inline .custom-control {
	display: flex;
	align-items: center;
	justify-content: center;
	padding-left: 0;
  }
  .bodywebsite .form-inline .custom-control-indicator {
	position: static;
	display: inline-block;
	margin-right: 0.25rem;
	vertical-align: text-bottom;
  }
  .bodywebsite .form-inline .has-feedback .form-input-feedback {
	top: 0;
  }
}
.bodywebsite .btn {
  display: inline-block;
  font-weight: 700;
  text-align: center;
  white-space: nowrap;
  vertical-align: middle;
  user-select: none;
  border: 1px solid transparent;
  padding: 11px 35px;
  font-size: 14px;
  line-height: 1.25;
  border-radius: 5px;
  transition: all 0.15s ease-in-out;
}
.bodywebsite .btn:focus,
.bodywebsite .btn:hover {
  text-decoration: none;
}
.bodywebsite .btn:focus,
.bodywebsite .btn.focus {
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
.bodywebsite .btn.disabled,
.bodywebsite .btn:disabled {
  opacity: .65;
  box-shadow: none;
}
.bodywebsite .btn:not([disabled]):not(.disabled):active,
.bodywebsite .btn:not([disabled]):not(.disabled).active {
  background-image: none;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25), 0;
}
.bodywebsite a.btn.disabled,
.bodywebsite fieldset[disabled] a.btn {
  pointer-events: none;
}
.bodywebsite .btn-primary {
  color: #fff;
  background-color: #007bff;
  border-color: #007bff;
  box-shadow: 0;
}
.bodywebsite .btn-primary:focus,
.bodywebsite .btn-primary.focus {
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.5);
}
.bodywebsite .btn-primary.maincolorbisbackground:focus,
.bodywebsite .btn-primary.maincolorbisbackground.focus {
  box-shadow: 0 0 0 0.2rem rgba(0, 255, 123, 0.5);
}
.bodywebsite .btn-primary.maincolorbisbackground:hover {
  box-shadow: 1px 1px 8px #aaa;
}
.bodywebsite .btn-primary.disabled,
.bodywebsite .btn-primary:disabled {
  background-color: #007bff;
  border-color: #007bff;
}
.bodywebsite .btn-primary:not([disabled]):not(.disabled):active,
.bodywebsite .btn-primary:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-primary.dropdown-toggle {
  color: #fff;
  background-color: #0062cc;
  border-color: #005cbf;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.5);
}
.bodywebsite .btn-secondary {
  color: #fff;
  background-color: #868e96;
  border-color: #868e96;
  box-shadow: 0;
}
.bodywebsite .btn-secondary:hover {
  color: #fff;
  background-color: #727b84;
  border-color: #6c757d;
}
.bodywebsite .btn-secondary:focus,
.bodywebsite .btn-secondary.focus {
  box-shadow: 0 0 0 0.2rem rgba(134, 142, 150, 0.5);
}
.bodywebsite .btn-secondary.disabled,
.bodywebsite .btn-secondary:disabled {
  background-color: #868e96;
  border-color: #868e96;
}
.bodywebsite .btn-secondary:not([disabled]):not(.disabled):active,
.bodywebsite .btn-secondary:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-secondary.dropdown-toggle {
  color: #fff;
  background-color: #6c757d;
  border-color: #666e76;
  box-shadow: 0 0 0 0.2rem rgba(134, 142, 150, 0.5);
}
.bodywebsite .btn-success {
  color: #fff;
  background-color: #28a745;
  border-color: #28a745;
  box-shadow: 0;
}
.bodywebsite .btn-success:hover {
  color: #fff;
  background-color: #218838;
  border-color: #1e7e34;
}
.bodywebsite .btn-success:focus,
.bodywebsite .btn-success.focus {
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.5);
}
.bodywebsite .btn-success.disabled,
.bodywebsite .btn-success:disabled {
  background-color: #28a745;
  border-color: #28a745;
}
.bodywebsite .btn-success:not([disabled]):not(.disabled):active,
.bodywebsite .btn-success:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-success.dropdown-toggle {
  color: #fff;
  background-color: #1e7e34;
  border-color: #1c7430;
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.5);
}
.bodywebsite .btn-info {
  color: #fff;
  background-color: #17a2b8;
  border-color: #17a2b8;
  box-shadow: 0;
}
.bodywebsite .btn-info:hover {
  color: #fff;
  background-color: #138496;
  border-color: #117a8b;
}
.bodywebsite .btn-info:focus,
.bodywebsite .btn-info.focus {
  box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.5);
}
.bodywebsite .btn-info.disabled,
.bodywebsite .btn-info:disabled {
  background-color: #17a2b8;
  border-color: #17a2b8;
}
.bodywebsite .btn-info:not([disabled]):not(.disabled):active,
.bodywebsite .btn-info:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-info.dropdown-toggle {
  color: #fff;
  background-color: #117a8b;
  border-color: #10707f;
  box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.5);
}
.bodywebsite .btn-warning {
  color: #111;
  background-color: #ffc107;
  border-color: #ffc107;
  box-shadow: 0;
}
.bodywebsite .btn-warning:hover {
  color: #111;
  background-color: #e0a800;
  border-color: #d39e00;
}
.bodywebsite .btn-warning:focus,
.bodywebsite .btn-warning.focus {
  box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.5);
}
.bodywebsite .btn-warning.disabled,
.bodywebsite .btn-warning:disabled {
  background-color: #ffc107;
  border-color: #ffc107;
}
.bodywebsite .btn-warning:not([disabled]):not(.disabled):active,
.bodywebsite .btn-warning:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-warning.dropdown-toggle {
  color: #111;
  background-color: #d39e00;
  border-color: #c69500;
  box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.5);
}
.bodywebsite .btn-danger {
  color: #fff;
  background-color: #dc3545;
  border-color: #dc3545;
  box-shadow: 0;
}
.bodywebsite .btn-danger:hover {
  color: #fff;
  background-color: #c82333;
  border-color: #bd2130;
}
.bodywebsite .btn-danger:focus,
.bodywebsite .btn-danger.focus {
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.5);
}
.bodywebsite .btn-danger.disabled,
.bodywebsite .btn-danger:disabled {
  background-color: #dc3545;
  border-color: #dc3545;
}
.bodywebsite .btn-danger:not([disabled]):not(.disabled):active,
.bodywebsite .btn-danger:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-danger.dropdown-toggle {
  color: #fff;
  background-color: #bd2130;
  border-color: #b21f2d;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.5);
}
.bodywebsite .btn-light {
  color: #111;
  background-color: #f8f9fa;
  border-color: #f8f9fa;
  box-shadow: 0;
}
.bodywebsite .btn-light:hover {
  color: #111;
  background-color: #e2e6ea;
  border-color: #dae0e5;
}
.bodywebsite .btn-light:focus,
.bodywebsite .btn-light.focus {
  box-shadow: 0 0 0 0.2rem rgba(248, 249, 250, 0.5);
}
.bodywebsite .btn-light.disabled,
.bodywebsite .btn-light:disabled {
  background-color: #f8f9fa;
  border-color: #f8f9fa;
}
.bodywebsite .btn-light:not([disabled]):not(.disabled):active,
.bodywebsite .btn-light:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-light.dropdown-toggle {
  color: #111;
  background-color: #dae0e5;
  border-color: #d3d9df;
  box-shadow: 0 0 0 0.2rem rgba(248, 249, 250, 0.5);
}
.bodywebsite .btn-dark {
  color: #fff;
  background-color: #343a40;
  border-color: #343a40;
  box-shadow: 0;
}
.bodywebsite .btn-dark:hover {
  color: #fff;
  background-color: #23272b;
  border-color: #1d2124;
}
.bodywebsite .btn-dark:focus,
.bodywebsite .btn-dark.focus {
  box-shadow: 0 0 0 0.2rem rgba(52, 58, 64, 0.5);
}
.bodywebsite .btn-dark.disabled,
.bodywebsite .btn-dark:disabled {
  background-color: #343a40;
  border-color: #343a40;
}
.bodywebsite .btn-dark:not([disabled]):not(.disabled):active,
.bodywebsite .btn-dark:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-dark.dropdown-toggle {
  color: #fff;
  background-color: #1d2124;
  border-color: #171a1d;
  box-shadow: 0 0 0 0.2rem rgba(52, 58, 64, 0.5);
}
.bodywebsite .btn-outline-primary {
  color: #007bff;
  background-color: transparent;
  background-image: none;
  border-color: #007bff;
}
.bodywebsite .btn-outline-primary:hover {
  color: #fff;
  background-color: #007bff;
  border-color: #007bff;
}
.bodywebsite .btn-outline-primary:focus,
.bodywebsite .btn-outline-primary.focus {
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.5);
}
.bodywebsite .btn-outline-primary.disabled,
.bodywebsite .btn-outline-primary:disabled {
  color: #007bff;
  background-color: transparent;
}
.bodywebsite .btn-outline-primary:not([disabled]):not(.disabled):active,
.bodywebsite .btn-outline-primary:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-outline-primary.dropdown-toggle {
  color: #fff;
  background-color: #007bff;
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.5);
}
.bodywebsite .btn-outline-secondary {
  color: #868e96;
  background-color: transparent;
  background-image: none;
  border-color: #868e96;
}
.bodywebsite .btn-outline-secondary:hover {
  color: #fff;
  background-color: #868e96;
  border-color: #868e96;
}
.bodywebsite .btn-outline-secondary:focus,
.bodywebsite .btn-outline-secondary.focus {
  box-shadow: 0 0 0 0.2rem rgba(134, 142, 150, 0.5);
}
.bodywebsite .btn-outline-secondary.disabled,
.bodywebsite .btn-outline-secondary:disabled {
  color: #868e96;
  background-color: transparent;
}
.bodywebsite .btn-outline-secondary:not([disabled]):not(.disabled):active,
.bodywebsite .btn-outline-secondary:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-outline-secondary.dropdown-toggle {
  color: #fff;
  background-color: #868e96;
  border-color: #868e96;
  box-shadow: 0 0 0 0.2rem rgba(134, 142, 150, 0.5);
}
.bodywebsite .btn-outline-success {
  color: #28a745;
  background-color: transparent;
  background-image: none;
  border-color: #28a745;
}
.bodywebsite .btn-outline-success:hover {
  color: #fff;
  background-color: #28a745;
  border-color: #28a745;
}
.bodywebsite .btn-outline-success:focus,
.bodywebsite .btn-outline-success.focus {
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.5);
}
.bodywebsite .btn-outline-success.disabled,
.bodywebsite .btn-outline-success:disabled {
  color: #28a745;
  background-color: transparent;
}
.bodywebsite .btn-outline-success:not([disabled]):not(.disabled):active,
.bodywebsite .btn-outline-success:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-outline-success.dropdown-toggle {
  color: #fff;
  background-color: #28a745;
  border-color: #28a745;
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.5);
}
.bodywebsite .btn-outline-info {
  color: #17a2b8;
  background-color: transparent;
  background-image: none;
  border-color: #17a2b8;
}
.bodywebsite .btn-outline-info:hover {
  color: #fff;
  background-color: #17a2b8;
  border-color: #17a2b8;
}
.bodywebsite .btn-outline-info:focus,
.bodywebsite .btn-outline-info.focus {
  box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.5);
}
.bodywebsite .btn-outline-info.disabled,
.bodywebsite .btn-outline-info:disabled {
  color: #17a2b8;
  background-color: transparent;
}
.bodywebsite .btn-outline-info:not([disabled]):not(.disabled):active,
.bodywebsite .btn-outline-info:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-outline-info.dropdown-toggle {
  color: #fff;
  background-color: #17a2b8;
  border-color: #17a2b8;
  box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.5);
}
.bodywebsite .btn-outline-warning {
  color: #ffc107;
  background-color: transparent;
  background-image: none;
  border-color: #ffc107;
}
.bodywebsite .btn-outline-warning:hover {
  color: #fff;
  background-color: #ffc107;
  border-color: #ffc107;
}
.bodywebsite .btn-outline-warning:focus,
.bodywebsite .btn-outline-warning.focus {
  box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.5);
}
.bodywebsite .btn-outline-warning.disabled,
.bodywebsite .btn-outline-warning:disabled {
  color: #ffc107;
  background-color: transparent;
}
.bodywebsite .btn-outline-warning:not([disabled]):not(.disabled):active,
.bodywebsite .btn-outline-warning:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-outline-warning.dropdown-toggle {
  color: #fff;
  background-color: #ffc107;
  border-color: #ffc107;
  box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.5);
}
.bodywebsite .btn-outline-danger {
  color: #dc3545;
  background-color: transparent;
  background-image: none;
  border-color: #dc3545;
}
.bodywebsite .btn-outline-danger:hover {
  color: #fff;
  background-color: #dc3545;
  border-color: #dc3545;
}
.bodywebsite .btn-outline-danger:focus,
.bodywebsite .btn-outline-danger.focus {
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.5);
}
.bodywebsite .btn-outline-danger.disabled,
.bodywebsite .btn-outline-danger:disabled {
  color: #dc3545;
  background-color: transparent;
}
.bodywebsite .btn-outline-danger:not([disabled]):not(.disabled):active,
.bodywebsite .btn-outline-danger:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-outline-danger.dropdown-toggle {
  color: #fff;
  background-color: #dc3545;
  border-color: #dc3545;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.5);
}
.bodywebsite .btn-outline-light {
  color: #f8f9fa;
  background-color: transparent;
  background-image: none;
  border-color: #f8f9fa;
}
.bodywebsite .btn-outline-light:hover {
  color: #00030a;
  background-color: #f8f9fa;
  border-color: #f8f9fa;
}
.bodywebsite .btn-outline-light:focus,
.bodywebsite .btn-outline-light.focus {
  box-shadow: 0 0 0 0.2rem rgba(248, 249, 250, 0.5);
}
.bodywebsite .btn-outline-light.disabled,
.bodywebsite .btn-outline-light:disabled {
  color: #f8f9fa;
  background-color: transparent;
}
.bodywebsite .btn-outline-light:not([disabled]):not(.disabled):active,
.bodywebsite .btn-outline-light:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-outline-light.dropdown-toggle {
  color: #00030a;
  background-color: #f8f9fa;
  border-color: #f8f9fa;
  box-shadow: 0 0 0 0.2rem rgba(248, 249, 250, 0.5);
}
.bodywebsite .btn-outline-dark {
  color: #343a40;
  background-color: transparent;
  background-image: none;
  border-color: #343a40;
}
.bodywebsite .btn-outline-dark:hover {
  color: #fff;
  background-color: #343a40;
  border-color: #343a40;
}
.bodywebsite .btn-outline-dark:focus,
.bodywebsite .btn-outline-dark.focus {
  box-shadow: 0 0 0 0.2rem rgba(52, 58, 64, 0.5);
}
.bodywebsite .btn-outline-dark.disabled,
.bodywebsite .btn-outline-dark:disabled {
  color: #343a40;
  background-color: transparent;
}
.bodywebsite .btn-outline-dark:not([disabled]):not(.disabled):active,
.bodywebsite .btn-outline-dark:not([disabled]):not(.disabled).active,
.bodywebsite .show > .btn-outline-dark.dropdown-toggle {
  color: #fff;
  background-color: #343a40;
  border-color: #343a40;
  box-shadow: 0 0 0 0.2rem rgba(52, 58, 64, 0.5);
}
.bodywebsite .btn-link {
  font-weight: 400;
  color: #37c386;
  background-color: transparent;
}
.bodywebsite .btn-link:hover {
  color: #26875d;
  text-decoration: none;
  background-color: transparent;
  border-color: transparent;
}
.bodywebsite .btn-link:focus,
.bodywebsite .btn-link.focus {
  border-color: transparent;
  box-shadow: none;
}
.bodywebsite .btn-link:disabled,
.bodywebsite .btn-link.disabled {
  color: #dedede;
}
.bodywebsite .btn-lg,
.bodywebsite .btn-group-lg > .btn {
  padding: 12px 50px;
  font-size: 18px;
  line-height: 1.5;
  border-radius: 6px;
}
.bodywebsite .btn-sm,
.bodywebsite .btn-group-sm > .btn {
  padding: 5px 25px;
  font-size: 12px;
  line-height: 1.5;
  border-radius: 3px;
}
.bodywebsite .btn-block {
  display: block;
  width: 100%;
}
.bodywebsite .btn-block + .btn-block {
  margin-top: 0.5rem;
}
.bodywebsite input[type="submit"].btn-block,
.bodywebsite input[type="reset"].btn-block,
.bodywebsite input[type="button"].btn-block {
  width: 100%;
}
.bodywebsite .fade {
  opacity: 0;
  transition: opacity 0.15s linear;
}
.bodywebsite .fade.show {
  opacity: 1;
}
.bodywebsite .collapse {
  display: none;
}
.bodywebsite .collapse.show {
  display: block;
}
.bodywebsite tr.collapse.show {
  display: table-row;
}
.bodywebsite tbody.collapse.show {
  display: table-row-group;
}
.bodywebsite .collapsing {
  position: relative;
  height: 0;
  overflow: hidden;
  transition: height 0.35s ease;
}
.bodywebsite .dropup,
.bodywebsite .dropdown {
  position: relative;
}
.bodywebsite .dropdown-toggle::after {
  display: inline-block;
  width: 0;
  height: 0;
  margin-left: 0.255em;
  vertical-align: 0.255em;
  content: "";
  border-top: 0.3em solid;
  border-right: 0.3em solid transparent;
  border-bottom: 0;
  border-left: 0.3em solid transparent;
}
.bodywebsite .dropdown-toggle:empty::after {
  margin-left: 0;
}
.bodywebsite .dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  z-index: 1000;
  display: none;
  float: left;
  min-width: 10rem;
  padding: 0.5rem 0;
  margin: 0.125rem 0 0;
  font-size: 14px;
  text-align: left;
  list-style: none;
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid rgba(0, 0, 0, 0.15);
  border-radius: 0;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
}
.bodywebsite .dropup .dropdown-menu {
  margin-top: 0;
  margin-bottom: 0.125rem;
}
.bodywebsite .dropup .dropdown-toggle::after {
  display: inline-block;
  width: 0;
  height: 0;
  margin-left: 0.255em;
  vertical-align: 0.255em;
  content: "";
  border-top: 0;
  border-right: 0.3em solid transparent;
  border-bottom: 0.3em solid;
  border-left: 0.3em solid transparent;
}
.bodywebsite .dropup .dropdown-toggle:empty::after {
  margin-left: 0;
}
.bodywebsite .dropdown-divider {
  height: 0;
  margin: 0.5rem 0;
  overflow: hidden;
  border-top: 1px solid #e5e5e5;
}
.bodywebsite .dropdown-item {
  display: block;
  width: 100%;
  padding: 0.25rem 1.5rem;
  clear: both;
  font-weight: 400;
  color: #2a2b2b;
  text-align: inherit;
  white-space: nowrap;
  background: none;
  border: 0;
}
.bodywebsite .dropdown-item:focus,
.bodywebsite .dropdown-item:hover {
  color: #1d1e1e;
  text-decoration: none;
  background-color: #f5f5f5;
}
.bodywebsite .dropdown-item.active,
.bodywebsite .dropdown-item:active {
  color: #2a2b2b;
  text-decoration: none;
  background-color: #37c386;
}
.bodywebsite .dropdown-item.disabled,
.bodywebsite .dropdown-item:disabled {
  color: #dedede;
  background-color: transparent;
}
.bodywebsite .dropdown-menu.show {
  display: block;
}
.bodywebsite .dropdown-header {
  display: block;
  padding: 0.5rem 1.5rem;
  margin-bottom: 0;
  font-size: 12px;
  color: #dedede;
  white-space: nowrap;
}
.bodywebsite .btn-group,
.bodywebsite .btn-group-vertical {
  position: relative;
  display: inline-flex;
  vertical-align: middle;
}
.bodywebsite .btn-group > .btn,
.bodywebsite .btn-group-vertical > .btn {
  position: relative;
  flex: 0 1 auto;
}
.bodywebsite .btn-group > .btn:hover,
.bodywebsite .btn-group-vertical > .btn:hover {
  z-index: 2;
}
.bodywebsite .btn-group > .btn:focus,
.bodywebsite .btn-group > .btn:active,
.bodywebsite .btn-group > .btn.active,
.bodywebsite .btn-group-vertical > .btn:focus,
.bodywebsite .btn-group-vertical > .btn:active,
.bodywebsite .btn-group-vertical > .btn.active {
  z-index: 2;
}
.bodywebsite .btn-group .btn + .btn,
.bodywebsite .btn-group .btn + .btn-group,
.bodywebsite .btn-group .btn-group + .btn,
.bodywebsite .btn-group .btn-group + .btn-group,
.bodywebsite .btn-group-vertical .btn + .btn,
.bodywebsite .btn-group-vertical .btn + .btn-group,
.bodywebsite .btn-group-vertical .btn-group + .btn,
.bodywebsite .btn-group-vertical .btn-group + .btn-group {
  margin-left: -1px;
}
.bodywebsite .btn-toolbar {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-start;
}
.bodywebsite .btn-toolbar .input-group {
  width: auto;
}
.bodywebsite .btn-group > .btn:not(:first-child):not(:last-child):not(.dropdown-toggle) {
  border-radius: 0;
}
.bodywebsite .btn-group > .btn:first-child {
  margin-left: 0;
}
.bodywebsite .btn-group > .btn:first-child:not(:last-child):not(.dropdown-toggle) {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}
.bodywebsite .btn-group > .btn:last-child:not(:first-child),
.bodywebsite .btn-group > .dropdown-toggle:not(:first-child) {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}
.bodywebsite .btn-group > .btn-group {
  float: left;
}
.bodywebsite .btn-group > .btn-group:not(:first-child):not(:last-child) > .btn {
  border-radius: 0;
}
.bodywebsite .btn-group > .btn-group:first-child:not(:last-child) > .btn:last-child,
.bodywebsite .btn-group > .btn-group:first-child:not(:last-child) > .dropdown-toggle {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}
.bodywebsite .btn-group > .btn-group:last-child:not(:first-child) > .btn:first-child {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}
.bodywebsite .btn + .dropdown-toggle-split {
  padding-right: 26.25px;
  padding-left: 26.25px;
}
.bodywebsite .btn + .dropdown-toggle-split::after {
  margin-left: 0;
}
.bodywebsite .btn-sm + .dropdown-toggle-split,
.bodywebsite .btn-group-sm > .btn + .dropdown-toggle-split {
  padding-right: 18.75px;
  padding-left: 18.75px;
}
.bodywebsite .btn-lg + .dropdown-toggle-split,
.bodywebsite .btn-group-lg > .btn + .dropdown-toggle-split {
  padding-right: 37.5px;
  padding-left: 37.5px;
}
.bodywebsite .btn-group.show .dropdown-toggle {
  box-shadow: 0;
}
.bodywebsite .btn-group.show .dropdown-toggle.btn-link {
  box-shadow: none;
}
.bodywebsite .btn-group-vertical {
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
}
.bodywebsite .btn-group-vertical .btn,
.bodywebsite .btn-group-vertical .btn-group {
  width: 100%;
}
.bodywebsite .btn-group-vertical > .btn + .btn,
.bodywebsite .btn-group-vertical > .btn + .btn-group,
.bodywebsite .btn-group-vertical > .btn-group + .btn,
.bodywebsite .btn-group-vertical > .btn-group + .btn-group {
  margin-top: -1px;
  margin-left: 0;
}
.bodywebsite .btn-group-vertical > .btn:not(:first-child):not(:last-child) {
  border-radius: 0;
}
.bodywebsite .btn-group-vertical > .btn:first-child:not(:last-child) {
  border-bottom-right-radius: 0;
  border-bottom-left-radius: 0;
}
.bodywebsite .btn-group-vertical > .btn:last-child:not(:first-child) {
  border-top-left-radius: 0;
  border-top-right-radius: 0;
}
.bodywebsite .btn-group-vertical > .btn-group:not(:first-child):not(:last-child) > .btn {
  border-radius: 0;
}
.bodywebsite .btn-group-vertical > .btn-group:first-child:not(:last-child) > .btn:last-child,
.bodywebsite .btn-group-vertical > .btn-group:first-child:not(:last-child) > .dropdown-toggle {
  border-bottom-right-radius: 0;
  border-bottom-left-radius: 0;
}
.bodywebsite .btn-group-vertical > .btn-group:last-child:not(:first-child) > .btn:first-child {
  border-top-left-radius: 0;
  border-top-right-radius: 0;
}
.bodywebsite [data-toggle="buttons"] > .btn input[type="radio"],
.bodywebsite [data-toggle="buttons"] > .btn input[type="checkbox"],
.bodywebsite [data-toggle="buttons"] > .btn-group > .btn input[type="radio"],
.bodywebsite [data-toggle="buttons"] > .btn-group > .btn input[type="checkbox"] {
  position: absolute;
  clip: rect(0, 0, 0, 0);
  pointer-events: none;
}
.bodywebsite .input-group {
  position: relative;
  display: flex;
  align-items: stretch;
  width: 100%;
}
.bodywebsite .input-group .form-input {
  position: relative;
  z-index: 2;
  flex: 1 1 auto;
  width: 1%;
  margin-bottom: 0;
}
.bodywebsite .input-group .form-input:focus,
.bodywebsite .input-group .form-input:active,
.bodywebsite .input-group .form-input:hover {
  z-index: 3;
}
.bodywebsite .input-group-addon,
.bodywebsite .input-group-btn,
.bodywebsite .input-group .form-input {
  display: flex;
  align-items: center;
}
.bodywebsite .input-group-addon:not(:first-child):not(:last-child),
.bodywebsite .input-group-btn:not(:first-child):not(:last-child),
.bodywebsite .input-group .form-input:not(:first-child):not(:last-child) {
  border-radius: 0;
}
.bodywebsite .input-group-addon,
.bodywebsite .input-group-btn {
  white-space: nowrap;
}
.bodywebsite .input-group-addon {
  padding: 11px 35px;
  margin-bottom: 0;
  font-size: 14px;
  font-weight: 400;
  line-height: 1.25;
  color: #495057;
  text-align: center;
  background-color: #e9ecef;
  border: 1px solid #ced4da;
  border-radius: 0.25rem;
}
.bodywebsite .input-group-addon.form-input-sm,
.bodywebsite .input-group-sm > .input-group-addon,
.bodywebsite .input-group-sm > .input-group-btn > .input-group-addon.btn {
  padding: 5px 25px;
  font-size: 12px;
  border-radius: 0.2rem;
}
.bodywebsite .input-group-addon.form-input-lg,
.bodywebsite .input-group-lg > .input-group-addon,
.bodywebsite .input-group-lg > .input-group-btn > .input-group-addon.btn {
  padding: 12px 50px;
  font-size: 18px;
  border-radius: 0.3rem;
}
.bodywebsite .input-group-addon input[type="radio"],
.bodywebsite .input-group-addon input[type="checkbox"] {
  margin-top: 0;
}
.bodywebsite .input-group .form-input:not(:last-child),
.bodywebsite .input-group-addon:not(:last-child),
.bodywebsite .input-group-btn:not(:last-child) > .btn,
.bodywebsite .input-group-btn:not(:last-child) > .btn-group > .btn,
.bodywebsite .input-group-btn:not(:last-child) > .dropdown-toggle,
.bodywebsite .input-group-btn:not(:first-child) > .btn:not(:last-child):not(.dropdown-toggle),
.bodywebsite .input-group-btn:not(:first-child) > .btn-group:not(:last-child) > .btn {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}
.bodywebsite .input-group-addon:not(:last-child) {
  border-right: 0;
}
.bodywebsite .input-group .form-input:not(:first-child),
.bodywebsite .input-group-addon:not(:first-child),
.bodywebsite .input-group-btn:not(:first-child) > .btn,
.bodywebsite .input-group-btn:not(:first-child) > .btn-group > .btn,
.bodywebsite .input-group-btn:not(:first-child) > .dropdown-toggle,
.bodywebsite .input-group-btn:not(:last-child) > .btn:not(:first-child),
.bodywebsite .input-group-btn:not(:last-child) > .btn-group:not(:first-child) > .btn {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}
.bodywebsite .form-input + .input-group-addon:not(:first-child) {
  border-left: 0;
}
.bodywebsite .input-group-btn {
  position: relative;
  align-items: stretch;
  font-size: 0;
  white-space: nowrap;
}
.bodywebsite .input-group-btn > .btn {
  position: relative;
}
.bodywebsite .input-group-btn > .btn + .btn {
  margin-left: -1px;
}
.bodywebsite .input-group-btn > .btn:focus,
.bodywebsite .input-group-btn > .btn:active,
.bodywebsite .input-group-btn > .btn:hover {
  z-index: 3;
}
.bodywebsite .input-group-btn:first-child > .btn + .btn {
  margin-left: 0;
}
.bodywebsite .input-group-btn:not(:last-child) > .btn,
.bodywebsite .input-group-btn:not(:last-child) > .btn-group {
  margin-right: -1px;
}
.bodywebsite .input-group-btn:not(:first-child) > .btn,
.bodywebsite .input-group-btn:not(:first-child) > .btn-group {
  z-index: 2;
  margin-left: 0;
}
.bodywebsite .input-group-btn:not(:first-child) > .btn:first-child,
.bodywebsite .input-group-btn:not(:first-child) > .btn-group:first-child {
  margin-left: -1px;
}
.bodywebsite .input-group-btn:not(:first-child) > .btn:focus,
.bodywebsite .input-group-btn:not(:first-child) > .btn:active,
.bodywebsite .input-group-btn:not(:first-child) > .btn:hover,
.bodywebsite .input-group-btn:not(:first-child) > .btn-group:focus,
.bodywebsite .input-group-btn:not(:first-child) > .btn-group:active,
.bodywebsite .input-group-btn:not(:first-child) > .btn-group:hover {
  z-index: 3;
}
.bodywebsite .custom-control {
  position: relative;
  display: inline-flex;
  min-height: 1.71429rem;
  padding-left: 1.5rem;
  margin-right: 1rem;
}
.bodywebsite .custom-control-input {
  position: absolute;
  z-index: -1;
  opacity: 0;
}
.bodywebsite .custom-control-input:checked ~ .custom-control-indicator {
  color: #fff;
  background-color: #007bff;
  box-shadow: none;
}
.bodywebsite .custom-control-input:focus ~ .custom-control-indicator {
  box-shadow: 0 0 0 1px #fff, 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
.bodywebsite .custom-control-input:active ~ .custom-control-indicator {
  color: #fff;
  background-color: #b3d7ff;
  box-shadow: none;
}
.bodywebsite .custom-control-input:disabled ~ .custom-control-indicator {
  background-color: #e9ecef;
}
.bodywebsite .custom-control-input:disabled ~ .custom-control-description {
  color: #868e96;
}
.bodywebsite .custom-control-indicator {
  position: absolute;
  top: 0.35714rem;
  left: 0;
  display: block;
  width: 1rem;
  height: 1rem;
  pointer-events: none;
  user-select: none;
  background-color: #ddd;
  background-repeat: no-repeat;
  background-position: center center;
  background-size: 50% 50%;
  box-shadow: inset 0 0.25rem 0.25rem rgba(0, 0, 0, 0.1);
}
.bodywebsite .custom-checkbox .custom-control-indicator {
  border-radius: 0.25rem;
}
.bodywebsite .custom-checkbox .custom-control-input:checked ~ .custom-control-indicator {
  background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3E%3Cpath fill='%23fff' d='M6.564.75l-3.59 3.612-1.538-1.55L0 4.26 2.974 7.25 8 2.193z'/%3E%3C/svg%3E");
}
.bodywebsite .custom-checkbox .custom-control-input:indeterminate ~ .custom-control-indicator {
  background-color: #007bff;
  background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 4'%3E%3Cpath stroke='%23fff' d='M0 2h4'/%3E%3C/svg%3E");
  box-shadow: none;
}
.bodywebsite .custom-radio .custom-control-indicator {
  border-radius: 50%;
}
.bodywebsite .custom-radio .custom-control-input:checked ~ .custom-control-indicator {
  background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3E%3Ccircle r='3' fill='%23fff'/%3E%3C/svg%3E");
}
.bodywebsite .custom-controls-stacked {
  display: flex;
  flex-direction: column;
}
.bodywebsite .custom-controls-stacked .custom-control {
  margin-bottom: 0.25rem;
}
.bodywebsite .custom-controls-stacked .custom-control + .custom-control {
  margin-left: 0;
}
.bodywebsite .custom-select {
  display: inline-block;
  max-width: 100%;
  height: calc(4.25rem);
  padding: 0.375rem 1.75rem 0.375rem 0.75rem;
  line-height: 1.5;
  color: #495057;
  vertical-align: middle;
  background: #fff url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'%3E%3Cpath fill='%23333' d='M2 0L0 2h4zm0 5L0 3h4z'/%3E%3C/svg%3E") no-repeat right 0.75rem center;
  background-size: 8px 10px;
  border: 1px solid #ced4da;
  border-radius: 0.25rem;
  appearance: none;
}
.bodywebsite .custom-select:focus {
  border-color: #80bdff;
  outline: none;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.075), 0 0 5px rgba(128, 189, 255, 0.5);
}
.bodywebsite .custom-select[multiple] {
  height: auto;
  background-image: none;
}
.bodywebsite .custom-select:disabled {
  color: #868e96;
  background-color: #e9ecef;
}
.bodywebsite .custom-select::-ms-expand {
  opacity: 0;
}
.bodywebsite .custom-select-sm {
  height: calc(3.8125rem);
  padding-top: 0.375rem;
  padding-bottom: 0.375rem;
  font-size: 75%;
}
.bodywebsite .custom-file {
  position: relative;
  display: inline-block;
  max-width: 100%;
  height: calc(4.25rem);
  margin-bottom: 0;
}
.bodywebsite .custom-file-input {
  min-width: 14rem;
  max-width: 100%;
  height: calc(4.25rem);
  margin: 0;
  opacity: 0;
}
.bodywebsite .custom-file-input:focus ~ .custom-file-control {
  box-shadow: 0 0 0 0.075rem #fff, 0 0 0 0.2rem #007bff;
}
.bodywebsite .custom-file-control {
  position: absolute;
  top: 0;
  right: 0;
  left: 0;
  z-index: 5;
  height: calc(4.25rem);
  padding: 0.375rem 0.75rem;
  line-height: 1.5;
  color: #495057;
  pointer-events: none;
  user-select: none;
  background-color: #fff;
  border: 1px solid #ced4da;
  border-radius: 0.25rem;
  box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
}
.bodywebsite .custom-file-control:lang(en):empty::after {
  content: "Choose file...";
}
.bodywebsite .custom-file-control::before {
  position: absolute;
  top: -1px;
  right: -1px;
  bottom: -1px;
  z-index: 6;
  display: block;
  height: calc(4.25rem);
  padding: 0.375rem 0.75rem;
  line-height: 1.5;
  color: #495057;
  background-color: #e9ecef;
  border: 1px solid #ced4da;
  border-radius: 0 0.25rem 0.25rem 0;
}
.bodywebsite .custom-file-control:lang(en)::before {
  content: "Browse";
}
.bodywebsite .nav {
  display: flex;
  flex-wrap: wrap;
  padding-left: 0;
  margin-bottom: 0;
  list-style: none;
}
.bodywebsite .nav-link {
  display: block;
  padding: 0.5rem 1rem;
}
.bodywebsite .nav-link:focus,
.bodywebsite .nav-link:hover {
  text-decoration: none;
}
.bodywebsite .nav-link.disabled {
  color: #868e96;
}
.bodywebsite .nav-tabs {
  border-bottom: 1px solid #ddd;
}
.bodywebsite .nav-tabs .nav-item {
  margin-bottom: -1px;
}
.bodywebsite .nav-tabs .nav-link {
  border: 1px solid transparent;
  border-top-left-radius: 0.25rem;
  border-top-right-radius: 0.25rem;
}
.bodywebsite .nav-tabs .nav-link:focus,
.bodywebsite .nav-tabs .nav-link:hover {
  border-color: #f9f9f9 #f9f9f9 #ddd;
}
.bodywebsite .nav-tabs .nav-link.disabled {
  color: #868e96;
  background-color: transparent;
  border-color: transparent;
}
.bodywebsite .nav-tabs .nav-link.active,
.bodywebsite .nav-tabs .nav-item.show .nav-link {
  color: #495057;
  background-color: #fff;
  border-color: #ddd #ddd #fff;
}
.bodywebsite .nav-tabs .dropdown-menu {
  margin-top: -1px;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
}
.bodywebsite .nav-pills .nav-link {
  border-radius: 0;
}
.bodywebsite .nav-pills .nav-link.active,
.bodywebsite .nav-pills .show > .nav-link {
  color: #fff;
  background-color: #007bff;
}
.bodywebsite .nav-fill .nav-item {
  flex: 1 1 auto;
  text-align: center;
}
.bodywebsite .nav-justified .nav-item {
  flex-basis: 0;
  flex-grow: 1;
  text-align: center;
}
.bodywebsite .tab-content > .tab-pane {
  display: none;
}
.bodywebsite .tab-content > .active {
  display: block;
}
.bodywebsite .navbar {
  position: relative;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 1rem;
}
.bodywebsite .navbar > .container,
.bodywebsite .navbar > .container-fluid {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
}
.bodywebsite .navbar-brand {
  display: inline-block;
  padding-top: 0.3125rem;
  padding-bottom: 0.3125rem;
  margin-right: 1rem;
  font-size: 1.25rem;
  line-height: inherit;
  white-space: nowrap;
}
.bodywebsite .navbar-brand:focus,
.bodywebsite .navbar-brand:hover {
  text-decoration: none;
}
.bodywebsite .navbar-nav {
  display: flex;
  flex-direction: column;
  padding-left: 0;
  margin-bottom: 0;
  list-style: none;
}
.bodywebsite .navbar-nav .nav-link {
  padding-right: 0;
  padding-left: 0;
}
.bodywebsite .navbar-nav .dropdown-menu {
  position: static;
  float: none;
}
.bodywebsite .navbar-text {
  display: inline-block;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}
.bodywebsite .navbar-collapse {
  flex-basis: 100%;
  flex-grow: 1;
  align-items: center;
}
.bodywebsite .navbar-toggler {
  padding: 0.25rem 0.75rem;
  font-size: 1.25rem;
  line-height: 1;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 0.25rem;
}
.bodywebsite .navbar-toggler:focus,
.bodywebsite .navbar-toggler:hover {
  text-decoration: none;
}
.bodywebsite .navbar-toggler-icon {
  display: inline-block;
  width: 1.5em;
  height: 1.5em;
  vertical-align: middle;
  content: "";
  background: no-repeat center center;
  background-size: 100% 100%;
}
@media (max-width: 575px) {
  .bodywebsite .navbar-expand-sm > .container,
  .bodywebsite .navbar-expand-sm > .container-fluid {
	padding-right: 0;
	padding-left: 0;
  }
}
@media (min-width: 576px) {
  .bodywebsite .navbar-expand-sm {
	flex-flow: row nowrap;
	justify-content: flex-start;
  }
  .bodywebsite .navbar-expand-sm .navbar-nav {
	flex-direction: row;
  }
  .bodywebsite .navbar-expand-sm .navbar-nav .dropdown-menu {
	position: absolute;
  }
  .bodywebsite .navbar-expand-sm .navbar-nav .dropdown-menu-right {
	right: 0;
	left: auto;
  }
  .bodywebsite .navbar-expand-sm .navbar-nav .nav-link {
	padding-right: .5rem;
	padding-left: .5rem;
  }
  .bodywebsite .navbar-expand-sm > .container,
  .bodywebsite .navbar-expand-sm > .container-fluid {
	flex-wrap: nowrap;
  }
  .bodywebsite .navbar-expand-sm .navbar-collapse {
	display: flex !important;
	flex-basis: auto;
  }
  .bodywebsite .navbar-expand-sm .navbar-toggler {
	display: none;
  }
  .bodywebsite .navbar-expand-sm .dropup .dropdown-menu {
	top: auto;
	bottom: 100%;
  }
}
@media (max-width: 767px) {
  .bodywebsite .navbar-expand-md > .container,
  .bodywebsite .navbar-expand-md > .container-fluid {
	padding-right: 0;
	padding-left: 0;
  }
}
@media (min-width: 768px) {
  .bodywebsite .navbar-expand-md {
	flex-flow: row nowrap;
	justify-content: flex-start;
  }
  .bodywebsite .navbar-expand-md .navbar-nav {
	flex-direction: row;
  }
  .bodywebsite .navbar-expand-md .navbar-nav .dropdown-menu {
	position: absolute;
  }
  .bodywebsite .navbar-expand-md .navbar-nav .dropdown-menu-right {
	right: 0;
	left: auto;
  }
  .bodywebsite .navbar-expand-md .navbar-nav .nav-link {
	padding-right: .5rem;
	padding-left: .5rem;
  }
  .bodywebsite .navbar-expand-md > .container,
  .bodywebsite .navbar-expand-md > .container-fluid {
	flex-wrap: nowrap;
  }
  .bodywebsite .navbar-expand-md .navbar-collapse {
	display: flex !important;
	flex-basis: auto;
  }
  .bodywebsite .navbar-expand-md .navbar-toggler {
	display: none;
  }
  .bodywebsite .navbar-expand-md .dropup .dropdown-menu {
	top: auto;
	bottom: 100%;
  }
}
@media (max-width: 991px) {
  .bodywebsite .navbar-expand-lg > .container,
  .bodywebsite .navbar-expand-lg > .container-fluid {
	padding-right: 0;
	padding-left: 0;
  }
}
@media (min-width: 992px) {
  .bodywebsite .navbar-expand-lg {
	flex-flow: row nowrap;
	justify-content: flex-start;
  }
  .bodywebsite .navbar-expand-lg .navbar-nav {
	flex-direction: row;
  }
  .bodywebsite .navbar-expand-lg .navbar-nav .dropdown-menu {
	position: absolute;
  }
  .bodywebsite .navbar-expand-lg .navbar-nav .dropdown-menu-right {
	right: 0;
	left: auto;
  }
  .bodywebsite .navbar-expand-lg .navbar-nav .nav-link {
	padding-right: .5rem;
	padding-left: .5rem;
  }
  .bodywebsite .navbar-expand-lg > .container,
  .bodywebsite .navbar-expand-lg > .container-fluid {
	flex-wrap: nowrap;
  }
  .bodywebsite .navbar-expand-lg .navbar-collapse {
	display: flex !important;
	flex-basis: auto;
  }
  .bodywebsite .navbar-expand-lg .navbar-toggler {
	display: none;
  }
  .bodywebsite .navbar-expand-lg .dropup .dropdown-menu {
	top: auto;
	bottom: 100%;
  }
}
@media (max-width: 1199px) {
  .bodywebsite .navbar-expand-xl > .container,
  .bodywebsite .navbar-expand-xl > .container-fluid {
	padding-right: 0;
	padding-left: 0;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .navbar-expand-xl {
	flex-flow: row nowrap;
	justify-content: flex-start;
  }
  .bodywebsite .navbar-expand-xl .navbar-nav {
	flex-direction: row;
  }
  .bodywebsite .navbar-expand-xl .navbar-nav .dropdown-menu {
	position: absolute;
  }
  .bodywebsite .navbar-expand-xl .navbar-nav .dropdown-menu-right {
	right: 0;
	left: auto;
  }
  .bodywebsite .navbar-expand-xl .navbar-nav .nav-link {
	padding-right: .5rem;
	padding-left: .5rem;
  }
  .bodywebsite .navbar-expand-xl > .container,
  .bodywebsite .navbar-expand-xl > .container-fluid {
	flex-wrap: nowrap;
  }
  .bodywebsite .navbar-expand-xl .navbar-collapse {
	display: flex !important;
	flex-basis: auto;
  }
  .bodywebsite .navbar-expand-xl .navbar-toggler {
	display: none;
  }
  .bodywebsite .navbar-expand-xl .dropup .dropdown-menu {
	top: auto;
	bottom: 100%;
  }
}
@media (max-width: 1799px) {
  .bodywebsite .navbar-expand-xxl > .container,
  .bodywebsite .navbar-expand-xxl > .container-fluid {
	padding-right: 0;
	padding-left: 0;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .navbar-expand-xxl {
	flex-flow: row nowrap;
	justify-content: flex-start;
  }
  .bodywebsite .navbar-expand-xxl .navbar-nav {
	flex-direction: row;
  }
  .bodywebsite .navbar-expand-xxl .navbar-nav .dropdown-menu {
	position: absolute;
  }
  .bodywebsite .navbar-expand-xxl .navbar-nav .dropdown-menu-right {
	right: 0;
	left: auto;
  }
  .bodywebsite .navbar-expand-xxl .navbar-nav .nav-link {
	padding-right: .5rem;
	padding-left: .5rem;
  }
  .bodywebsite .navbar-expand-xxl > .container,
  .bodywebsite .navbar-expand-xxl > .container-fluid {
	flex-wrap: nowrap;
  }
  .bodywebsite .navbar-expand-xxl .navbar-collapse {
	display: flex !important;
	flex-basis: auto;
  }
  .bodywebsite .navbar-expand-xxl .navbar-toggler {
	display: none;
  }
  .bodywebsite .navbar-expand-xxl .dropup .dropdown-menu {
	top: auto;
	bottom: 100%;
  }
}
.bodywebsite .navbar-expand {
  flex-flow: row nowrap;
  justify-content: flex-start;
}
.bodywebsite .navbar-expand > .container,
.bodywebsite .navbar-expand > .container-fluid {
  padding-right: 0;
  padding-left: 0;
}
.bodywebsite .navbar-expand .navbar-nav {
  flex-direction: row;
}
.bodywebsite .navbar-expand .navbar-nav .dropdown-menu {
  position: absolute;
}
.bodywebsite .navbar-expand .navbar-nav .dropdown-menu-right {
  right: 0;
  left: auto;
}
.bodywebsite .navbar-expand .navbar-nav .nav-link {
  padding-right: .5rem;
  padding-left: .5rem;
}
.bodywebsite .navbar-expand > .container,
.bodywebsite .navbar-expand > .container-fluid {
  flex-wrap: nowrap;
}
.bodywebsite .navbar-expand .navbar-collapse {
  display: flex !important;
  flex-basis: auto;
}
.bodywebsite .navbar-expand .navbar-toggler {
  display: none;
}
.bodywebsite .navbar-expand .dropup .dropdown-menu {
  top: auto;
  bottom: 100%;
}
.bodywebsite .navbar-light .navbar-brand {
  color: rgba(0, 0, 0, 0.9);
}
.bodywebsite .navbar-light .navbar-brand:focus,
.bodywebsite .navbar-light .navbar-brand:hover {
  color: rgba(0, 0, 0, 0.9);
}
.bodywebsite .navbar-light .navbar-nav .nav-link {
  color: rgba(0, 0, 0, 0.5);
}
.bodywebsite .navbar-light .navbar-nav .nav-link:focus,
.bodywebsite .navbar-light .navbar-nav .nav-link:hover {
  color: rgba(0, 0, 0, 0.7);
}
.bodywebsite .navbar-light .navbar-nav .nav-link.disabled {
  color: rgba(0, 0, 0, 0.3);
}
.bodywebsite .navbar-light .navbar-nav .show > .nav-link,
.bodywebsite .navbar-light .navbar-nav .active > .nav-link,
.bodywebsite .navbar-light .navbar-nav .nav-link.show,
.bodywebsite .navbar-light .navbar-nav .nav-link.active {
  color: rgba(0, 0, 0, 0.9);
}
.bodywebsite .navbar-light .navbar-toggler {
  color: rgba(0, 0, 0, 0.5);
  border-color: rgba(0, 0, 0, 0.1);
}
.bodywebsite .navbar-light .navbar-toggler-icon {
  background-image: url("data:image/svg+xml;charset=utf8,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='rgba(0, 0, 0, 0.5)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
}
.bodywebsite .navbar-light .navbar-text {
  color: rgba(0, 0, 0, 0.5);
}
.bodywebsite .navbar-light .navbar-text a {
  color: rgba(0, 0, 0, 0.9);
}
.bodywebsite .navbar-light .navbar-text a:focus,
.bodywebsite .navbar-light .navbar-text a:hover {
  color: rgba(0, 0, 0, 0.9);
}
.bodywebsite .navbar-dark .navbar-brand {
  color: #fff;
}
.bodywebsite .navbar-dark .navbar-brand:focus,
.bodywebsite .navbar-dark .navbar-brand:hover {
  color: #fff;
}
.bodywebsite .navbar-dark .navbar-nav .nav-link {
  color: rgba(255, 255, 255, 0.5);
}
.bodywebsite .navbar-dark .navbar-nav .nav-link:focus,
.bodywebsite .navbar-dark .navbar-nav .nav-link:hover {
  color: rgba(255, 255, 255, 0.75);
}
.bodywebsite .navbar-dark .navbar-nav .nav-link.disabled {
  color: rgba(255, 255, 255, 0.25);
}
.bodywebsite .navbar-dark .navbar-nav .show > .nav-link,
.bodywebsite .navbar-dark .navbar-nav .active > .nav-link,
.bodywebsite .navbar-dark .navbar-nav .nav-link.show,
.bodywebsite .navbar-dark .navbar-nav .nav-link.active {
  color: #fff;
}
.bodywebsite .navbar-dark .navbar-toggler {
  color: rgba(255, 255, 255, 0.5);
  border-color: rgba(255, 255, 255, 0.1);
}
.bodywebsite .navbar-dark .navbar-toggler-icon {
  background-image: url("data:image/svg+xml;charset=utf8,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='rgba(255, 255, 255, 0.5)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
}
.bodywebsite .navbar-dark .navbar-text {
  color: rgba(255, 255, 255, 0.5);
}
.bodywebsite .navbar-dark .navbar-text a {
  color: #fff;
}
.bodywebsite .navbar-dark .navbar-text a:focus,
.bodywebsite .navbar-dark .navbar-text a:hover {
  color: #fff;
}
.bodywebsite .jumbotron {
  padding: 2rem 1rem;
  margin-bottom: 2rem;
  background-color: #e9ecef;
  border-radius: 6px;
}
@media (min-width: 576px) {
  .bodywebsite .jumbotron {
	padding: 4rem 2rem;
  }
}
.bodywebsite .jumbotron-fluid {
  padding-right: 0;
  padding-left: 0;
  border-radius: 0;
}
.bodywebsite .alert {
  position: relative;
  padding: 0.75rem 1.25rem;
  margin-bottom: 1rem;
  border: 1px solid transparent;
  border-radius: 0.25rem;
}
.bodywebsite .alert-heading {
  color: inherit;
}
.bodywebsite .alert-link {
  font-weight: 700;
}
.bodywebsite .alert-dismissible .close {
  position: absolute;
  top: 0;
  right: 0;
  padding: 0.75rem 1.25rem;
  color: inherit;
}
.bodywebsite .alert-primary {
  color: #004085;
  background-color: #cce5ff;
  border-color: #b8daff;
}
.bodywebsite .alert-primary hr {
  border-top-color: #9fcdff;
}
.bodywebsite .alert-primary .alert-link {
  color: #002752;
}
.bodywebsite .alert-secondary {
  color: #464a4e;
  background-color: #e7e8ea;
  border-color: #dddfe2;
}
.bodywebsite .alert-secondary hr {
  border-top-color: #cfd2d6;
}
.bodywebsite .alert-secondary .alert-link {
  color: #2e3133;
}
.bodywebsite .alert-success {
  color: #155724;
  background-color: #d4edda;
  border-color: #c3e6cb;
}
.bodywebsite .alert-success hr {
  border-top-color: #b1dfbb;
}
.bodywebsite .alert-success .alert-link {
  color: #0b2e13;
}
.bodywebsite .alert-info {
  color: #0c5460;
  background-color: #d1ecf1;
  border-color: #bee5eb;
}
.bodywebsite .alert-info hr {
  border-top-color: #abdde5;
}
.bodywebsite .alert-info .alert-link {
  color: #062c33;
}
.bodywebsite .alert-warning {
  color: #856404;
  background-color: #fff3cd;
  border-color: #ffeeba;
}
.bodywebsite .alert-warning hr {
  border-top-color: #ffe8a1;
}
.bodywebsite .alert-warning .alert-link {
  color: #533f03;
}
.bodywebsite .alert-danger {
  color: #721c24;
  background-color: #f8d7da;
  border-color: #f5c6cb;
}
.bodywebsite .alert-danger hr {
  border-top-color: #f1b0b7;
}
.bodywebsite .alert-danger .alert-link {
  color: #491217;
}
.bodywebsite .alert-light {
  color: #818182;
  background-color: #fefefe;
  border-color: #fdfdfe;
}
.bodywebsite .alert-light hr {
  border-top-color: #ececf6;
}
.bodywebsite .alert-light .alert-link {
  color: #686868;
}
.bodywebsite .alert-dark {
  color: #1b1e21;
  background-color: #d6d8d9;
  border-color: #c6c8ca;
}
.bodywebsite .alert-dark hr {
  border-top-color: #b9bbbe;
}
.bodywebsite .alert-dark .alert-link {
  color: #040505;
}
@keyframes progress-bar-stripes {
  from {
	background-position: 1rem 0;
  }
  to {
	background-position: 0 0;
  }
}
.bodywebsite .progress {
  display: flex;
  height: 1rem;
  overflow: hidden;
  font-size: 0.75rem;
  background-color: #e9ecef;
  border-radius: 0.25rem;
}
.bodywebsite .progress-bar {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  background-color: #007bff;
}
.bodywebsite .progress-bar-striped {
  background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
  background-size: 1rem 1rem;
}
.bodywebsite .progress-bar-animated {
  animation: progress-bar-stripes 1s linear infinite;
}
.bodywebsite .media {
  display: flex;
  align-items: flex-start;
}
.bodywebsite .media-body {
  flex: 1;
}
.bodywebsite .list-group {
  display: flex;
  flex-direction: column;
  padding-left: 0;
  margin-bottom: 0;
}
.bodywebsite .list-group-item-action {
  width: 100%;
  color: #495057;
  text-align: inherit;
}
.bodywebsite .list-group-item-action:focus,
.bodywebsite .list-group-item-action:hover {
  color: #495057;
  text-decoration: none;
  background-color: #f8f9fa;
}
.bodywebsite .list-group-item-action:active {
  color: #212529;
  background-color: #e9ecef;
}
.bodywebsite .list-group-item {
  position: relative;
  display: block;
  padding: 0.75rem 1.25rem;
  margin-bottom: -1px;
  background-color: #fff;
  border: 1px solid rgba(0, 0, 0, 0.125);
}
.bodywebsite .list-group-item:first-child {
  border-top-left-radius: 0.25rem;
  border-top-right-radius: 0.25rem;
}
.bodywebsite .list-group-item:last-child {
  margin-bottom: 0;
  border-bottom-right-radius: 0.25rem;
  border-bottom-left-radius: 0.25rem;
}
.bodywebsite .list-group-item:focus,
.bodywebsite .list-group-item:hover {
  text-decoration: none;
}
.bodywebsite .list-group-item.disabled,
.bodywebsite .list-group-item:disabled {
  color: #868e96;
  background-color: #fff;
}
.bodywebsite .list-group-item.active {
  z-index: 2;
  color: #fff;
  background-color: #007bff;
  border-color: #007bff;
}
.bodywebsite .list-group-flush .list-group-item {
  border-right: 0;
  border-left: 0;
  border-radius: 0;
}
.bodywebsite .list-group-flush:first-child .list-group-item:first-child {
  border-top: 0;
}
.bodywebsite .list-group-flush:last-child .list-group-item:last-child {
  border-bottom: 0;
}
.bodywebsite .list-group-item-primary {
  color: #004085;
  background-color: #b8daff;
}
.bodywebsite a.list-group-item-primary,
.bodywebsite button.list-group-item-primary {
  color: #004085;
}
.bodywebsite a.list-group-item-primary:focus,
.bodywebsite a.list-group-item-primary:hover,
.bodywebsite button.list-group-item-primary:focus,
.bodywebsite button.list-group-item-primary:hover {
  color: #004085;
  background-color: #9fcdff;
}
.bodywebsite a.list-group-item-primary.active,
.bodywebsite button.list-group-item-primary.active {
  color: #fff;
  background-color: #004085;
  border-color: #004085;
}
.bodywebsite .list-group-item-secondary {
  color: #464a4e;
  background-color: #dddfe2;
}
.bodywebsite a.list-group-item-secondary,
.bodywebsite button.list-group-item-secondary {
  color: #464a4e;
}
.bodywebsite a.list-group-item-secondary:focus,
.bodywebsite a.list-group-item-secondary:hover,
.bodywebsite button.list-group-item-secondary:focus,
.bodywebsite button.list-group-item-secondary:hover {
  color: #464a4e;
  background-color: #cfd2d6;
}
.bodywebsite a.list-group-item-secondary.active,
.bodywebsite button.list-group-item-secondary.active {
  color: #fff;
  background-color: #464a4e;
  border-color: #464a4e;
}
.bodywebsite .list-group-item-success {
  color: #155724;
  background-color: #c3e6cb;
}
.bodywebsite a.list-group-item-success,
.bodywebsite button.list-group-item-success {
  color: #155724;
}
.bodywebsite a.list-group-item-success:focus,
.bodywebsite a.list-group-item-success:hover,
.bodywebsite button.list-group-item-success:focus,
.bodywebsite button.list-group-item-success:hover {
  color: #155724;
  background-color: #b1dfbb;
}
.bodywebsite a.list-group-item-success.active,
.bodywebsite button.list-group-item-success.active {
  color: #fff;
  background-color: #155724;
  border-color: #155724;
}
.bodywebsite .list-group-item-info {
  color: #0c5460;
  background-color: #bee5eb;
}
.bodywebsite a.list-group-item-info,
.bodywebsite button.list-group-item-info {
  color: #0c5460;
}
.bodywebsite a.list-group-item-info:focus,
.bodywebsite a.list-group-item-info:hover,
.bodywebsite button.list-group-item-info:focus,
.bodywebsite button.list-group-item-info:hover {
  color: #0c5460;
  background-color: #abdde5;
}
.bodywebsite a.list-group-item-info.active,
.bodywebsite button.list-group-item-info.active {
  color: #fff;
  background-color: #0c5460;
  border-color: #0c5460;
}
.bodywebsite .list-group-item-warning {
  color: #856404;
  background-color: #ffeeba;
}
.bodywebsite a.list-group-item-warning,
.bodywebsite button.list-group-item-warning {
  color: #856404;
}
.bodywebsite a.list-group-item-warning:focus,
.bodywebsite a.list-group-item-warning:hover,
.bodywebsite button.list-group-item-warning:focus,
.bodywebsite button.list-group-item-warning:hover {
  color: #856404;
  background-color: #ffe8a1;
}
.bodywebsite a.list-group-item-warning.active,
.bodywebsite button.list-group-item-warning.active {
  color: #fff;
  background-color: #856404;
  border-color: #856404;
}
.bodywebsite .list-group-item-danger {
  color: #721c24;
  background-color: #f5c6cb;
}
.bodywebsite a.list-group-item-danger,
.bodywebsite button.list-group-item-danger {
  color: #721c24;
}
.bodywebsite a.list-group-item-danger:focus,
.bodywebsite a.list-group-item-danger:hover,
.bodywebsite button.list-group-item-danger:focus,
.bodywebsite button.list-group-item-danger:hover {
  color: #721c24;
  background-color: #f1b0b7;
}
.bodywebsite a.list-group-item-danger.active,
.bodywebsite button.list-group-item-danger.active {
  color: #fff;
  background-color: #721c24;
  border-color: #721c24;
}
.bodywebsite .list-group-item-light {
  color: #818182;
  background-color: #fdfdfe;
}
.bodywebsite a.list-group-item-light,
.bodywebsite button.list-group-item-light {
  color: #818182;
}
.bodywebsite a.list-group-item-light:focus,
.bodywebsite a.list-group-item-light:hover,
.bodywebsite button.list-group-item-light:focus,
.bodywebsite button.list-group-item-light:hover {
  color: #818182;
  background-color: #ececf6;
}
.bodywebsite a.list-group-item-light.active,
.bodywebsite button.list-group-item-light.active {
  color: #fff;
  background-color: #818182;
  border-color: #818182;
}
.bodywebsite .list-group-item-dark {
  color: #1b1e21;
  background-color: #c6c8ca;
}
.bodywebsite a.list-group-item-dark,
.bodywebsite button.list-group-item-dark {
  color: #1b1e21;
}
.bodywebsite a.list-group-item-dark:focus,
.bodywebsite a.list-group-item-dark:hover,
.bodywebsite button.list-group-item-dark:focus,
.bodywebsite button.list-group-item-dark:hover {
  color: #1b1e21;
  background-color: #b9bbbe;
}
.bodywebsite a.list-group-item-dark.active,
.bodywebsite button.list-group-item-dark.active {
  color: #fff;
  background-color: #1b1e21;
  border-color: #1b1e21;
}
.bodywebsite .close {
  float: right;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1;
  color: #000;
  text-shadow: 0 1px 0 #fff;
  opacity: .5;
}
.bodywebsite .close:focus,
.bodywebsite .close:hover {
  color: #000;
  text-decoration: none;
  opacity: .75;
}
.bodywebsite button.close {
  padding: 0;
  background: transparent;
  border: 0;
  -webkit-appearance: none;
}
.bodywebsite .tooltip {
  position: absolute;
  z-index: 1070;
  display: block;
  margin: 0;
  font-family: "Roboto", Helvetica, Arial, sans-serif;
  font-style: normal;
  font-weight: 400;
  line-height: 1.71429;
  text-align: left;
  text-align: start;
  text-decoration: none;
  text-shadow: none;
  text-transform: none;
  letter-spacing: normal;
  word-break: normal;
  word-spacing: normal;
  white-space: normal;
  line-break: auto;
  font-size: 12px;
  word-wrap: break-word;
  opacity: 0;
}
.bodywebsite .tooltip.show {
  opacity: 1;
}
.bodywebsite .tooltip .arrow {
  position: absolute;
  display: block;
  width: 6px;
  height: 6px;
}
.bodywebsite .tooltip .arrow::before {
  position: absolute;
  border-color: transparent;
  border-style: solid;
}
.bodywebsite .tooltip.bs-tooltip-top,
.bodywebsite .tooltip.bs-tooltip-auto[x-placement^="top"] {
  padding: 6px 0;
}
.bodywebsite .tooltip.bs-tooltip-top .arrow,
.bodywebsite .tooltip.bs-tooltip-auto[x-placement^="top"] .arrow {
  bottom: 0;
}
.bodywebsite .tooltip.bs-tooltip-top .arrow::before,
.bodywebsite .tooltip.bs-tooltip-auto[x-placement^="top"] .arrow::before {
  margin-left: -4px;
  content: "";
  border-width: 6px 6px 0;
  border-top-color: #37c386;
}
.bodywebsite .tooltip.bs-tooltip-right,
.bodywebsite .tooltip.bs-tooltip-auto[x-placement^="right"] {
  padding: 0 6px;
}
.bodywebsite .tooltip.bs-tooltip-right .arrow,
.bodywebsite .tooltip.bs-tooltip-auto[x-placement^="right"] .arrow {
  left: 0;
}
.bodywebsite .tooltip.bs-tooltip-right .arrow::before,
.bodywebsite .tooltip.bs-tooltip-auto[x-placement^="right"] .arrow::before {
  margin-top: -4px;
  content: "";
  border-width: 6px 6px 6px 0;
  border-right-color: #37c386;
}
.bodywebsite .tooltip.bs-tooltip-bottom,
.bodywebsite .tooltip.bs-tooltip-auto[x-placement^="bottom"] {
  padding: 6px 0;
}
.bodywebsite .tooltip.bs-tooltip-bottom .arrow,
.bodywebsite .tooltip.bs-tooltip-auto[x-placement^="bottom"] .arrow {
  top: 0;
}
.bodywebsite .tooltip.bs-tooltip-bottom .arrow::before,
.bodywebsite .tooltip.bs-tooltip-auto[x-placement^="bottom"] .arrow::before {
  margin-left: -4px;
  content: "";
  border-width: 0 6px 6px;
  border-bottom-color: #37c386;
}
.bodywebsite .tooltip.bs-tooltip-left,
.bodywebsite .tooltip.bs-tooltip-auto[x-placement^="left"] {
  padding: 0 6px;
}
.bodywebsite .tooltip.bs-tooltip-left .arrow,
.bodywebsite .tooltip.bs-tooltip-auto[x-placement^="left"] .arrow {
  right: 0;
}
.bodywebsite .tooltip.bs-tooltip-left .arrow::before,
.bodywebsite .tooltip.bs-tooltip-auto[x-placement^="left"] .arrow::before {
  right: 0;
  margin-top: -4px;
  content: "";
  border-width: 6px 0 6px 6px;
  border-left-color: #37c386;
}
.bodywebsite .tooltip-inner {
  max-width: 200px;
  padding: 6px 10px;
  color: #fff;
  text-align: center;
  background-color: #37c386;
  border-radius: 0;
}
.bodywebsite .popover {
  position: absolute;
  top: 0;
  left: 0;
  z-index: 1060;
  display: block;
  max-width: 276px;
  font-family: "Roboto", Helvetica, Arial, sans-serif;
  font-style: normal;
  font-weight: 400;
  line-height: 1.71429;
  text-align: left;
  text-align: start;
  text-decoration: none;
  text-shadow: none;
  text-transform: none;
  letter-spacing: normal;
  word-break: normal;
  word-spacing: normal;
  white-space: normal;
  line-break: auto;
  font-size: 12px;
  word-wrap: break-word;
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid rgba(0, 0, 0, 0.2);
  border-radius: 6px;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2);
}
.bodywebsite .popover .arrow {
  position: absolute;
  display: block;
  width: 0.8rem;
  height: 0.4rem;
}
.bodywebsite .popover .arrow::before,
.bodywebsite .popover .arrow::after {
  position: absolute;
  display: block;
  border-color: transparent;
  border-style: solid;
}
.bodywebsite .popover .arrow::before {
  content: "";
  border-width: 0.8rem;
}
.bodywebsite .popover .arrow::after {
  content: "";
  border-width: 0.8rem;
}
.bodywebsite .popover.bs-popover-top,
.bodywebsite .popover.bs-popover-auto[x-placement^="top"] {
  margin-bottom: 0.8rem;
}
.bodywebsite .popover.bs-popover-top .arrow,
.bodywebsite .popover.bs-popover-auto[x-placement^="top"] .arrow {
  bottom: 0;
}
.bodywebsite .popover.bs-popover-top .arrow::before,
.bodywebsite .popover.bs-popover-auto[x-placement^="top"] .arrow::before,
.bodywebsite .popover.bs-popover-top .arrow::after,
.bodywebsite .popover.bs-popover-auto[x-placement^="top"] .arrow::after {
  border-bottom-width: 0;
}
.bodywebsite .popover.bs-popover-top .arrow::before,
.bodywebsite .popover.bs-popover-auto[x-placement^="top"] .arrow::before {
  bottom: -0.8rem;
  margin-left: -0.8rem;
  border-top-color: rgba(0, 0, 0, 0.25);
}
.bodywebsite .popover.bs-popover-top .arrow::after,
.bodywebsite .popover.bs-popover-auto[x-placement^="top"] .arrow::after {
  bottom: calc(0.2rem);
  margin-left: -0.8rem;
  border-top-color: #fff;
}
.bodywebsite .popover.bs-popover-right,
.bodywebsite .popover.bs-popover-auto[x-placement^="right"] {
  margin-left: 0.8rem;
}
.bodywebsite .popover.bs-popover-right .arrow,
.bodywebsite .popover.bs-popover-auto[x-placement^="right"] .arrow {
  left: 0;
}
.bodywebsite .popover.bs-popover-right .arrow::before,
.bodywebsite .popover.bs-popover-auto[x-placement^="right"] .arrow::before,
.bodywebsite .popover.bs-popover-right .arrow::after,
.bodywebsite .popover.bs-popover-auto[x-placement^="right"] .arrow::after {
  margin-top: -0.8rem;
  border-left-width: 0;
}
.bodywebsite .popover.bs-popover-right .arrow::before,
.bodywebsite .popover.bs-popover-auto[x-placement^="right"] .arrow::before {
  left: -0.8rem;
  border-right-color: rgba(0, 0, 0, 0.25);
}
.bodywebsite .popover.bs-popover-right .arrow::after,
.bodywebsite .popover.bs-popover-auto[x-placement^="right"] .arrow::after {
  left: calc(0.2rem);
  border-right-color: #fff;
}
.bodywebsite .popover.bs-popover-bottom,
.bodywebsite .popover.bs-popover-auto[x-placement^="bottom"] {
  margin-top: 0.8rem;
}
.bodywebsite .popover.bs-popover-bottom .arrow,
.bodywebsite .popover.bs-popover-auto[x-placement^="bottom"] .arrow {
  top: 0;
}
.bodywebsite .popover.bs-popover-bottom .arrow::before,
.bodywebsite .popover.bs-popover-auto[x-placement^="bottom"] .arrow::before,
.bodywebsite .popover.bs-popover-bottom .arrow::after,
.bodywebsite .popover.bs-popover-auto[x-placement^="bottom"] .arrow::after {
  margin-left: -0.8rem;
  border-top-width: 0;
}
.bodywebsite .popover.bs-popover-bottom .arrow::before,
.bodywebsite .popover.bs-popover-auto[x-placement^="bottom"] .arrow::before {
  top: -0.8rem;
  border-bottom-color: rgba(0, 0, 0, 0.25);
}
.bodywebsite .popover.bs-popover-bottom .arrow::after,
.bodywebsite .popover.bs-popover-auto[x-placement^="bottom"] .arrow::after {
  top: calc(0.2rem);
  border-bottom-color: #fff;
}
.bodywebsite .popover.bs-popover-bottom .popover-header::before,
.bodywebsite .popover.bs-popover-auto[x-placement^="bottom"] .popover-header::before {
  position: absolute;
  top: 0;
  left: 50%;
  display: block;
  width: 20px;
  margin-left: -10px;
  content: "";
  border-bottom: 1px solid #f7f7f7;
}
.bodywebsite .popover.bs-popover-left,
.bodywebsite .popover.bs-popover-auto[x-placement^="left"] {
  margin-right: 0.8rem;
}
.bodywebsite .popover.bs-popover-left .arrow,
.bodywebsite .popover.bs-popover-auto[x-placement^="left"] .arrow {
  right: 0;
}
.bodywebsite .popover.bs-popover-left .arrow::before,
.bodywebsite .popover.bs-popover-auto[x-placement^="left"] .arrow::before,
.bodywebsite .popover.bs-popover-left .arrow::after,
.bodywebsite .popover.bs-popover-auto[x-placement^="left"] .arrow::after {
  margin-top: -0.8rem;
  border-right-width: 0;
}
.bodywebsite .popover.bs-popover-left .arrow::before,
.bodywebsite .popover.bs-popover-auto[x-placement^="left"] .arrow::before {
  right: -0.8rem;
  border-left-color: rgba(0, 0, 0, 0.25);
}
.bodywebsite .popover.bs-popover-left .arrow::after,
.bodywebsite .popover.bs-popover-auto[x-placement^="left"] .arrow::after {
  right: calc(0.2rem);
  border-left-color: #fff;
}
.bodywebsite .popover-header {
  padding: 0.5rem 0.75rem;
  margin-bottom: 0;
  font-size: 14px;
  color: inherit;
  background-color: #f7f7f7;
  border-bottom: 1px solid #ebebeb;
  border-top-left-radius: calc(5px);
  border-top-right-radius: calc(5px);
}
.bodywebsite .popover-header:empty {
  display: none;
}
.bodywebsite .popover-body {
  padding: 0.5rem 0.75rem;
  color: #212529;
}
.bodywebsite .carousel {
  position: relative;
}
.bodywebsite .carousel-inner {
  position: relative;
  width: 100%;
  overflow: hidden;
}
.bodywebsite .carousel-item {
  position: relative;
  display: none;
  align-items: center;
  width: 100%;
  transition: transform 0.6s ease;
  backface-visibility: hidden;
  perspective: 1000px;
}
.bodywebsite .carousel-item.active,
.bodywebsite .carousel-item-next,
.bodywebsite .carousel-item-prev {
  display: block;
}
.bodywebsite .carousel-item-next,
.bodywebsite .carousel-item-prev {
  position: absolute;
  top: 0;
}
.bodywebsite .carousel-item-next.carousel-item-left,
.bodywebsite .carousel-item-prev.carousel-item-right {
  transform: translateX(0);
}
@supports (transform-style: preserve-3d) {
  .bodywebsite .carousel-item-next.carousel-item-left,
  .bodywebsite .carousel-item-prev.carousel-item-right {
	transform: translate3d(0, 0, 0);
  }
}
.bodywebsite .carousel-item-next,
.bodywebsite .active.carousel-item-right {
  transform: translateX(100%);
}
@supports (transform-style: preserve-3d) {
  .bodywebsite .carousel-item-next,
  .bodywebsite .active.carousel-item-right {
	transform: translate3d(100%, 0, 0);
  }
}
.bodywebsite .carousel-item-prev,
.bodywebsite .active.carousel-item-left {
  transform: translateX(-100%);
}
@supports (transform-style: preserve-3d) {
  .bodywebsite .carousel-item-prev,
  .bodywebsite .active.carousel-item-left {
	transform: translate3d(-100%, 0, 0);
  }
}
.bodywebsite .carousel-control-prev,
.bodywebsite .carousel-control-next {
  position: absolute;
  top: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 15%;
  color: #fff;
  text-align: center;
  opacity: 0.5;
}
.bodywebsite .carousel-control-prev:focus,
.bodywebsite .carousel-control-prev:hover,
.bodywebsite .carousel-control-next:focus,
.bodywebsite .carousel-control-next:hover {
  color: #fff;
  text-decoration: none;
  outline: 0;
  opacity: .9;
}
.bodywebsite .carousel-control-prev {
  left: 0;
}
.bodywebsite .carousel-control-next {
  right: 0;
}
.bodywebsite .carousel-control-prev-icon,
.bodywebsite .carousel-control-next-icon {
  display: inline-block;
  width: 20px;
  height: 20px;
  background: transparent no-repeat center center;
  background-size: 100% 100%;
}
.bodywebsite .carousel-control-prev-icon {
  background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23fff' viewBox='0 0 8 8'%3E%3Cpath d='M5.25 0l-4 4 4 4 1.5-1.5-2.5-2.5 2.5-2.5-1.5-1.5z'/%3E%3C/svg%3E");
}
.bodywebsite .carousel-control-next-icon {
  background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23fff' viewBox='0 0 8 8'%3E%3Cpath d='M2.75 0l-1.5 1.5 2.5 2.5-2.5 2.5 1.5 1.5 4-4-4-4z'/%3E%3C/svg%3E");
}
.bodywebsite .carousel-indicators {
  position: absolute;
  right: 0;
  bottom: 10px;
  left: 0;
  z-index: 15;
  display: flex;
  justify-content: center;
  padding-left: 0;
  margin-right: 15%;
  margin-left: 15%;
  list-style: none;
}
.bodywebsite .carousel-indicators li {
  position: relative;
  flex: 0 1 auto;
  width: 30px;
  height: 3px;
  margin-right: 3px;
  margin-left: 3px;
  text-indent: -999px;
  background-color: rgba(255, 255, 255, 0.5);
}
.bodywebsite .carousel-indicators li::before {
  position: absolute;
  top: -10px;
  left: 0;
  display: inline-block;
  width: 100%;
  height: 10px;
  content: "";
}
.bodywebsite .carousel-indicators li::after {
  position: absolute;
  bottom: -10px;
  left: 0;
  display: inline-block;
  width: 100%;
  height: 10px;
  content: "";
}
.bodywebsite .carousel-indicators .active {
  background-color: #fff;
}
.bodywebsite .carousel-caption {
  position: absolute;
  right: 15%;
  bottom: 20px;
  left: 15%;
  z-index: 10;
  padding-top: 20px;
  padding-bottom: 20px;
  color: #fff;
  text-align: center;
}
.bodywebsite .align-baseline {
  vertical-align: baseline !important;
}
.bodywebsite .align-top {
  vertical-align: top !important;
}
.bodywebsite .align-middle {
  vertical-align: middle !important;
}
.bodywebsite .align-bottom {
  vertical-align: bottom !important;
}
.bodywebsite .align-text-bottom {
  vertical-align: text-bottom !important;
}
.bodywebsite .align-text-top {
  vertical-align: text-top !important;
}
.bodywebsite .bg-primary {
  background-color: #007bff !important;
}
.bodywebsite a.bg-primary:focus,
.bodywebsite a.bg-primary:hover {
  background-color: #0062cc !important;
}
.bodywebsite .bg-secondary {
  background-color: #868e96 !important;
}
.bodywebsite a.bg-secondary:focus,
.bodywebsite a.bg-secondary:hover {
  background-color: #6c757d !important;
}
.bodywebsite .bg-success {
  background-color: #28a745 !important;
}
.bodywebsite a.bg-success:focus,
.bodywebsite a.bg-success:hover {
  background-color: #1e7e34 !important;
}
.bodywebsite .bg-info {
  background-color: #17a2b8 !important;
}
.bodywebsite a.bg-info:focus,
.bodywebsite a.bg-info:hover {
  background-color: #117a8b !important;
}
.bodywebsite .bg-warning {
  background-color: #ffc107 !important;
}
.bodywebsite a.bg-warning:focus,
.bodywebsite a.bg-warning:hover {
  background-color: #d39e00 !important;
}
.bodywebsite .bg-danger {
  background-color: #dc3545 !important;
}
.bodywebsite a.bg-danger:focus,
.bodywebsite a.bg-danger:hover {
  background-color: #bd2130 !important;
}
.bodywebsite .bg-light {
  background-color: #f8f9fa !important;
}
.bodywebsite a.bg-light:focus,
.bodywebsite a.bg-light:hover {
  background-color: #dae0e5 !important;
}
.bodywebsite .bg-dark {
  background-color: #343a40 !important;
}
.bodywebsite a.bg-dark:focus,
.bodywebsite a.bg-dark:hover {
  background-color: #1d2124 !important;
}
.bodywebsite .bg-default {
  background-color: #fff !important;
}
.bodywebsite .bg-transparent {
  background-color: transparent !important;
}
.bodywebsite .border {
  border: 1px solid #e9ecef !important;
}
.bodywebsite .border-0 {
  border: 0 !important;
}
.bodywebsite .border-top-0 {
  border-top: 0 !important;
}
.bodywebsite .border-right-0 {
  border-right: 0 !important;
}
.bodywebsite .border-bottom-0 {
  border-bottom: 0 !important;
}
.bodywebsite .border-left-0 {
  border-left: 0 !important;
}
.bodywebsite .border-primary {
  border-color: #007bff !important;
}
.bodywebsite .border-secondary {
  border-color: #868e96 !important;
}
.bodywebsite .border-success {
  border-color: #28a745 !important;
}
.bodywebsite .border-info {
  border-color: #17a2b8 !important;
}
.bodywebsite .border-warning {
  border-color: #ffc107 !important;
}
.bodywebsite .border-danger {
  border-color: #dc3545 !important;
}
.bodywebsite .border-light {
  border-color: #f8f9fa !important;
}
.bodywebsite .border-dark {
  border-color: #343a40 !important;
}
.bodywebsite .border-white {
  border-color: #fff !important;
}
.bodywebsite .rounded {
  border-radius: 0 !important;
}
.bodywebsite .rounded-top {
  border-top-left-radius: 0 !important;
  border-top-right-radius: 0 !important;
}
.bodywebsite .rounded-right {
  border-top-right-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
}
.bodywebsite .rounded-bottom {
  border-bottom-right-radius: 0 !important;
  border-bottom-left-radius: 0 !important;
}
.bodywebsite .rounded-left {
  border-top-left-radius: 0 !important;
  border-bottom-left-radius: 0 !important;
}
.bodywebsite .rounded-circle {
  border-radius: 50% !important;
}
.bodywebsite .rounded-0 {
  border-radius: 0 !important;
}
.bodywebsite .clearfix::after {
  display: block;
  clear: both;
  content: "";
}
.bodywebsite .d-none {
  display: none !important;
}
.bodywebsite .d-inline {
  display: inline !important;
}
.bodywebsite .d-inline-block {
  display: inline-block !important;
}
.bodywebsite .d-block {
  display: block !important;
}
.bodywebsite .d-table {
  display: table !important;
}
.bodywebsite .d-table-row {
  display: table-row !important;
}
.bodywebsite .d-table-cell {
  display: table-cell !important;
}
.bodywebsite .d-flex {
  display: flex !important;
}
.bodywebsite .d-inline-flex {
  display: inline-flex !important;
}
@media (min-width: 576px) {
  .bodywebsite .d-sm-none {
	display: none !important;
  }
  .bodywebsite .d-sm-inline {
	display: inline !important;
  }
  .bodywebsite .d-sm-inline-block {
	display: inline-block !important;
  }
  .bodywebsite .d-sm-block {
	display: block !important;
  }
  .bodywebsite .d-sm-table {
	display: table !important;
  }
  .bodywebsite .d-sm-table-row {
	display: table-row !important;
  }
  .bodywebsite .d-sm-table-cell {
	display: table-cell !important;
  }
  .bodywebsite .d-sm-flex {
	display: flex !important;
  }
  .bodywebsite .d-sm-inline-flex {
	display: inline-flex !important;
  }
}
@media (min-width: 768px) {
  .bodywebsite .d-md-none {
	display: none !important;
  }
  .bodywebsite .d-md-inline {
	display: inline !important;
  }
  .bodywebsite .d-md-inline-block {
	display: inline-block !important;
  }
  .bodywebsite .d-md-block {
	display: block !important;
  }
  .bodywebsite .d-md-table {
	display: table !important;
  }
  .bodywebsite .d-md-table-row {
	display: table-row !important;
  }
  .bodywebsite .d-md-table-cell {
	display: table-cell !important;
  }
  .bodywebsite .d-md-flex {
	display: flex !important;
  }
  .bodywebsite .d-md-inline-flex {
	display: inline-flex !important;
  }
}
@media (min-width: 992px) {
  .bodywebsite .d-lg-none {
	display: none !important;
  }
  .bodywebsite .d-lg-inline {
	display: inline !important;
  }
  .bodywebsite .d-lg-inline-block {
	display: inline-block !important;
  }
  .bodywebsite .d-lg-block {
	display: block !important;
  }
  .bodywebsite .d-lg-table {
	display: table !important;
  }
  .bodywebsite .d-lg-table-row {
	display: table-row !important;
  }
  .bodywebsite .d-lg-table-cell {
	display: table-cell !important;
  }
  .bodywebsite .d-lg-flex {
	display: flex !important;
  }
  .bodywebsite .d-lg-inline-flex {
	display: inline-flex !important;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .d-xl-none {
	display: none !important;
  }
  .bodywebsite .d-xl-inline {
	display: inline !important;
  }
  .bodywebsite .d-xl-inline-block {
	display: inline-block !important;
  }
  .bodywebsite .d-xl-block {
	display: block !important;
  }
  .bodywebsite .d-xl-table {
	display: table !important;
  }
  .bodywebsite .d-xl-table-row {
	display: table-row !important;
  }
  .bodywebsite .d-xl-table-cell {
	display: table-cell !important;
  }
  .bodywebsite .d-xl-flex {
	display: flex !important;
  }
  .bodywebsite .d-xl-inline-flex {
	display: inline-flex !important;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .d-xxl-none {
	display: none !important;
  }
  .bodywebsite .d-xxl-inline {
	display: inline !important;
  }
  .bodywebsite .d-xxl-inline-block {
	display: inline-block !important;
  }
  .bodywebsite .d-xxl-block {
	display: block !important;
  }
  .bodywebsite .d-xxl-table {
	display: table !important;
  }
  .bodywebsite .d-xxl-table-row {
	display: table-row !important;
  }
  .bodywebsite .d-xxl-table-cell {
	display: table-cell !important;
  }
  .bodywebsite .d-xxl-flex {
	display: flex !important;
  }
  .bodywebsite .d-xxl-inline-flex {
	display: inline-flex !important;
  }
}
.bodywebsite .d-print-block {
  display: none !important;
}
@media print {
  .bodywebsite .d-print-block {
	display: block !important;
  }
}
.bodywebsite .d-print-inline {
  display: none !important;
}
@media print {
  .bodywebsite .d-print-inline {
	display: inline !important;
  }
}
.bodywebsite .d-print-inline-block {
  display: none !important;
}
@media print {
  .bodywebsite .d-print-inline-block {
	display: inline-block !important;
  }
}
@media print {
  .bodywebsite .d-print-none {
	display: none !important;
  }
}
.bodywebsite .embed-responsive {
  position: relative;
  display: block;
  width: 100%;
  padding: 0;
  overflow: hidden;
}
.bodywebsite .embed-responsive::before {
  display: block;
  content: "";
}
.bodywebsite .embed-responsive .embed-responsive-item,
.bodywebsite .embed-responsive iframe,
.bodywebsite .embed-responsive embed,
.bodywebsite .embed-responsive object,
.bodywebsite .embed-responsive video {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border: 0;
}
.bodywebsite .embed-responsive-21by9::before {
  padding-top: 42.85714%;
}
.bodywebsite .embed-responsive-16by9::before {
  padding-top: 56.25%;
}
.bodywebsite .embed-responsive-4by3::before {
  padding-top: 75%;
}
.bodywebsite .embed-responsive-1by1::before {
  padding-top: 100%;
}
.bodywebsite .flex-row {
  flex-direction: row !important;
}
.bodywebsite .flex-column {
  flex-direction: column !important;
}
.bodywebsite .flex-row-reverse {
  flex-direction: row-reverse !important;
}
.bodywebsite .flex-column-reverse {
  flex-direction: column-reverse !important;
}
.bodywebsite .flex-wrap {
  flex-wrap: wrap !important;
}
.bodywebsite .flex-nowrap {
  flex-wrap: nowrap !important;
}
.bodywebsite .flex-wrap-reverse {
  flex-wrap: wrap-reverse !important;
}
.bodywebsite .justify-content-start {
  justify-content: flex-start !important;
}
.bodywebsite .justify-content-end {
  justify-content: flex-end !important;
}
.bodywebsite .justify-content-center {
  justify-content: center !important;
}
.bodywebsite .justify-content-between {
  justify-content: space-between !important;
}
.bodywebsite .justify-content-around {
  justify-content: space-around !important;
}
.bodywebsite .align-items-start {
  align-items: flex-start !important;
}
.bodywebsite .align-items-end {
  align-items: flex-end !important;
}
.bodywebsite .align-items-center {
  align-items: center !important;
}
.bodywebsite .align-items-baseline {
  align-items: baseline !important;
}
.bodywebsite .align-items-stretch {
  align-items: stretch !important;
}
.bodywebsite .align-content-start {
  align-content: flex-start !important;
}
.bodywebsite .align-content-end {
  align-content: flex-end !important;
}
.bodywebsite .align-content-center {
  align-content: center !important;
}
.bodywebsite .align-content-between {
  align-content: space-between !important;
}
.bodywebsite .align-content-around {
  align-content: space-around !important;
}
.bodywebsite .align-content-stretch {
  align-content: stretch !important;
}
.bodywebsite .align-self-auto {
  align-self: auto !important;
}
.bodywebsite .align-self-start {
  align-self: flex-start !important;
}
.bodywebsite .align-self-end {
  align-self: flex-end !important;
}
.bodywebsite .align-self-center {
  align-self: center !important;
}
.bodywebsite .align-self-baseline {
  align-self: baseline !important;
}
.bodywebsite .align-self-stretch {
  align-self: stretch !important;
}
@media (min-width: 576px) {
  .bodywebsite .flex-sm-row {
	flex-direction: row !important;
  }
  .bodywebsite .flex-sm-column {
	flex-direction: column !important;
  }
  .bodywebsite .flex-sm-row-reverse {
	flex-direction: row-reverse !important;
  }
  .bodywebsite .flex-sm-column-reverse {
	flex-direction: column-reverse !important;
  }
  .bodywebsite .flex-sm-wrap {
	flex-wrap: wrap !important;
  }
  .bodywebsite .flex-sm-nowrap {
	flex-wrap: nowrap !important;
  }
  .bodywebsite .flex-sm-wrap-reverse {
	flex-wrap: wrap-reverse !important;
  }
  .bodywebsite .justify-content-sm-start {
	justify-content: flex-start !important;
  }
  .bodywebsite .justify-content-sm-end {
	justify-content: flex-end !important;
  }
  .bodywebsite .justify-content-sm-center {
	justify-content: center !important;
  }
  .bodywebsite .justify-content-sm-between {
	justify-content: space-between !important;
  }
  .bodywebsite .justify-content-sm-around {
	justify-content: space-around !important;
  }
  .bodywebsite .align-items-sm-start {
	align-items: flex-start !important;
  }
  .bodywebsite .align-items-sm-end {
	align-items: flex-end !important;
  }
  .bodywebsite .align-items-sm-center {
	align-items: center !important;
  }
  .bodywebsite .align-items-sm-baseline {
	align-items: baseline !important;
  }
  .bodywebsite .align-items-sm-stretch {
	align-items: stretch !important;
  }
  .bodywebsite .align-content-sm-start {
	align-content: flex-start !important;
  }
  .bodywebsite .align-content-sm-end {
	align-content: flex-end !important;
  }
  .bodywebsite .align-content-sm-center {
	align-content: center !important;
  }
  .bodywebsite .align-content-sm-between {
	align-content: space-between !important;
  }
  .bodywebsite .align-content-sm-around {
	align-content: space-around !important;
  }
  .bodywebsite .align-content-sm-stretch {
	align-content: stretch !important;
  }
  .bodywebsite .align-self-sm-auto {
	align-self: auto !important;
  }
  .bodywebsite .align-self-sm-start {
	align-self: flex-start !important;
  }
  .bodywebsite .align-self-sm-end {
	align-self: flex-end !important;
  }
  .bodywebsite .align-self-sm-center {
	align-self: center !important;
  }
  .bodywebsite .align-self-sm-baseline {
	align-self: baseline !important;
  }
  .bodywebsite .align-self-sm-stretch {
	align-self: stretch !important;
  }
}
@media (min-width: 768px) {
  .bodywebsite .flex-md-row {
	flex-direction: row !important;
  }
  .bodywebsite .flex-md-column {
	flex-direction: column !important;
  }
  .bodywebsite .flex-md-row-reverse {
	flex-direction: row-reverse !important;
  }
  .bodywebsite .flex-md-column-reverse {
	flex-direction: column-reverse !important;
  }
  .bodywebsite .flex-md-wrap {
	flex-wrap: wrap !important;
  }
  .bodywebsite .flex-md-nowrap {
	flex-wrap: nowrap !important;
  }
  .bodywebsite .flex-md-wrap-reverse {
	flex-wrap: wrap-reverse !important;
  }
  .bodywebsite .justify-content-md-start {
	justify-content: flex-start !important;
  }
  .bodywebsite .justify-content-md-end {
	justify-content: flex-end !important;
  }
  .bodywebsite .justify-content-md-center {
	justify-content: center !important;
  }
  .bodywebsite .justify-content-md-between {
	justify-content: space-between !important;
  }
  .bodywebsite .justify-content-md-around {
	justify-content: space-around !important;
  }
  .bodywebsite .align-items-md-start {
	align-items: flex-start !important;
  }
  .bodywebsite .align-items-md-end {
	align-items: flex-end !important;
  }
  .bodywebsite .align-items-md-center {
	align-items: center !important;
  }
  .bodywebsite .align-items-md-baseline {
	align-items: baseline !important;
  }
  .bodywebsite .align-items-md-stretch {
	align-items: stretch !important;
  }
  .bodywebsite .align-content-md-start {
	align-content: flex-start !important;
  }
  .bodywebsite .align-content-md-end {
	align-content: flex-end !important;
  }
  .bodywebsite .align-content-md-center {
	align-content: center !important;
  }
  .bodywebsite .align-content-md-between {
	align-content: space-between !important;
  }
  .bodywebsite .align-content-md-around {
	align-content: space-around !important;
  }
  .bodywebsite .align-content-md-stretch {
	align-content: stretch !important;
  }
  .bodywebsite .align-self-md-auto {
	align-self: auto !important;
  }
  .bodywebsite .align-self-md-start {
	align-self: flex-start !important;
  }
  .bodywebsite .align-self-md-end {
	align-self: flex-end !important;
  }
  .bodywebsite .align-self-md-center {
	align-self: center !important;
  }
  .bodywebsite .align-self-md-baseline {
	align-self: baseline !important;
  }
  .bodywebsite .align-self-md-stretch {
	align-self: stretch !important;
  }
}
@media (min-width: 992px) {
  .bodywebsite .flex-lg-row {
	flex-direction: row !important;
  }
  .bodywebsite .flex-lg-column {
	flex-direction: column !important;
  }
  .bodywebsite .flex-lg-row-reverse {
	flex-direction: row-reverse !important;
  }
  .bodywebsite .flex-lg-column-reverse {
	flex-direction: column-reverse !important;
  }
  .bodywebsite .flex-lg-wrap {
	flex-wrap: wrap !important;
  }
  .bodywebsite .flex-lg-nowrap {
	flex-wrap: nowrap !important;
  }
  .bodywebsite .flex-lg-wrap-reverse {
	flex-wrap: wrap-reverse !important;
  }
  .bodywebsite .justify-content-lg-start {
	justify-content: flex-start !important;
  }
  .bodywebsite .justify-content-lg-end {
	justify-content: flex-end !important;
  }
  .bodywebsite .justify-content-lg-center {
	justify-content: center !important;
  }
  .bodywebsite .justify-content-lg-between {
	justify-content: space-between !important;
  }
  .bodywebsite .justify-content-lg-around {
	justify-content: space-around !important;
  }
  .bodywebsite .align-items-lg-start {
	align-items: flex-start !important;
  }
  .bodywebsite .align-items-lg-end {
	align-items: flex-end !important;
  }
  .bodywebsite .align-items-lg-center {
	align-items: center !important;
  }
  .bodywebsite .align-items-lg-baseline {
	align-items: baseline !important;
  }
  .bodywebsite .align-items-lg-stretch {
	align-items: stretch !important;
  }
  .bodywebsite .align-content-lg-start {
	align-content: flex-start !important;
  }
  .bodywebsite .align-content-lg-end {
	align-content: flex-end !important;
  }
  .bodywebsite .align-content-lg-center {
	align-content: center !important;
  }
  .bodywebsite .align-content-lg-between {
	align-content: space-between !important;
  }
  .bodywebsite .align-content-lg-around {
	align-content: space-around !important;
  }
  .bodywebsite .align-content-lg-stretch {
	align-content: stretch !important;
  }
  .bodywebsite .align-self-lg-auto {
	align-self: auto !important;
  }
  .bodywebsite .align-self-lg-start {
	align-self: flex-start !important;
  }
  .bodywebsite .align-self-lg-end {
	align-self: flex-end !important;
  }
  .bodywebsite .align-self-lg-center {
	align-self: center !important;
  }
  .bodywebsite .align-self-lg-baseline {
	align-self: baseline !important;
  }
  .bodywebsite .align-self-lg-stretch {
	align-self: stretch !important;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .flex-xl-row {
	flex-direction: row !important;
  }
  .bodywebsite .flex-xl-column {
	flex-direction: column !important;
  }
  .bodywebsite .flex-xl-row-reverse {
	flex-direction: row-reverse !important;
  }
  .bodywebsite .flex-xl-column-reverse {
	flex-direction: column-reverse !important;
  }
  .bodywebsite .flex-xl-wrap {
	flex-wrap: wrap !important;
  }
  .bodywebsite .flex-xl-nowrap {
	flex-wrap: nowrap !important;
  }
  .bodywebsite .flex-xl-wrap-reverse {
	flex-wrap: wrap-reverse !important;
  }
  .bodywebsite .justify-content-xl-start {
	justify-content: flex-start !important;
  }
  .bodywebsite .justify-content-xl-end {
	justify-content: flex-end !important;
  }
  .bodywebsite .justify-content-xl-center {
	justify-content: center !important;
  }
  .bodywebsite .justify-content-xl-between {
	justify-content: space-between !important;
  }
  .bodywebsite .justify-content-xl-around {
	justify-content: space-around !important;
  }
  .bodywebsite .align-items-xl-start {
	align-items: flex-start !important;
  }
  .bodywebsite .align-items-xl-end {
	align-items: flex-end !important;
  }
  .bodywebsite .align-items-xl-center {
	align-items: center !important;
  }
  .bodywebsite .align-items-xl-baseline {
	align-items: baseline !important;
  }
  .bodywebsite .align-items-xl-stretch {
	align-items: stretch !important;
  }
  .bodywebsite .align-content-xl-start {
	align-content: flex-start !important;
  }
  .bodywebsite .align-content-xl-end {
	align-content: flex-end !important;
  }
  .bodywebsite .align-content-xl-center {
	align-content: center !important;
  }
  .bodywebsite .align-content-xl-between {
	align-content: space-between !important;
  }
  .bodywebsite .align-content-xl-around {
	align-content: space-around !important;
  }
  .bodywebsite .align-content-xl-stretch {
	align-content: stretch !important;
  }
  .bodywebsite .align-self-xl-auto {
	align-self: auto !important;
  }
  .bodywebsite .align-self-xl-start {
	align-self: flex-start !important;
  }
  .bodywebsite .align-self-xl-end {
	align-self: flex-end !important;
  }
  .bodywebsite .align-self-xl-center {
	align-self: center !important;
  }
  .bodywebsite .align-self-xl-baseline {
	align-self: baseline !important;
  }
  .bodywebsite .align-self-xl-stretch {
	align-self: stretch !important;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .flex-xxl-row {
	flex-direction: row !important;
  }
  .bodywebsite .flex-xxl-column {
	flex-direction: column !important;
  }
  .bodywebsite .flex-xxl-row-reverse {
	flex-direction: row-reverse !important;
  }
  .bodywebsite .flex-xxl-column-reverse {
	flex-direction: column-reverse !important;
  }
  .bodywebsite .flex-xxl-wrap {
	flex-wrap: wrap !important;
  }
  .bodywebsite .flex-xxl-nowrap {
	flex-wrap: nowrap !important;
  }
  .bodywebsite .flex-xxl-wrap-reverse {
	flex-wrap: wrap-reverse !important;
  }
  .bodywebsite .justify-content-xxl-start {
	justify-content: flex-start !important;
  }
  .bodywebsite .justify-content-xxl-end {
	justify-content: flex-end !important;
  }
  .bodywebsite .justify-content-xxl-center {
	justify-content: center !important;
  }
  .bodywebsite .justify-content-xxl-between {
	justify-content: space-between !important;
  }
  .bodywebsite .justify-content-xxl-around {
	justify-content: space-around !important;
  }
  .bodywebsite .align-items-xxl-start {
	align-items: flex-start !important;
  }
  .bodywebsite .align-items-xxl-end {
	align-items: flex-end !important;
  }
  .bodywebsite .align-items-xxl-center {
	align-items: center !important;
  }
  .bodywebsite .align-items-xxl-baseline {
	align-items: baseline !important;
  }
  .bodywebsite .align-items-xxl-stretch {
	align-items: stretch !important;
  }
  .bodywebsite .align-content-xxl-start {
	align-content: flex-start !important;
  }
  .bodywebsite .align-content-xxl-end {
	align-content: flex-end !important;
  }
  .bodywebsite .align-content-xxl-center {
	align-content: center !important;
  }
  .bodywebsite .align-content-xxl-between {
	align-content: space-between !important;
  }
  .bodywebsite .align-content-xxl-around {
	align-content: space-around !important;
  }
  .bodywebsite .align-content-xxl-stretch {
	align-content: stretch !important;
  }
  .bodywebsite .align-self-xxl-auto {
	align-self: auto !important;
  }
  .bodywebsite .align-self-xxl-start {
	align-self: flex-start !important;
  }
  .bodywebsite .align-self-xxl-end {
	align-self: flex-end !important;
  }
  .bodywebsite .align-self-xxl-center {
	align-self: center !important;
  }
  .bodywebsite .align-self-xxl-baseline {
	align-self: baseline !important;
  }
  .bodywebsite .align-self-xxl-stretch {
	align-self: stretch !important;
  }
}
.bodywebsite .float-left {
  float: left !important;
}
.bodywebsite .float-right {
  float: right !important;
}
.bodywebsite .float-none {
  float: none !important;
}
@media (min-width: 576px) {
  .bodywebsite .float-sm-left {
	float: left !important;
  }
  .bodywebsite .float-sm-right {
	float: right !important;
  }
  .bodywebsite .float-sm-none {
	float: none !important;
  }
}
@media (min-width: 768px) {
  .bodywebsite .float-md-left {
	float: left !important;
  }
  .bodywebsite .float-md-right {
	float: right !important;
  }
  .bodywebsite .float-md-none {
	float: none !important;
  }
}
@media (min-width: 992px) {
  .bodywebsite .float-lg-left {
	float: left !important;
  }
  .bodywebsite .float-lg-right {
	float: right !important;
  }
  .bodywebsite .float-lg-none {
	float: none !important;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .float-xl-left {
	float: left !important;
  }
  .bodywebsite .float-xl-right {
	float: right !important;
  }
  .bodywebsite .float-xl-none {
	float: none !important;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .float-xxl-left {
	float: left !important;
  }
  .bodywebsite .float-xxl-right {
	float: right !important;
  }
  .bodywebsite .float-xxl-none {
	float: none !important;
  }
}
.bodywebsite .position-static {
  position: static !important;
}
.bodywebsite .position-relative {
  position: relative !important;
}
.bodywebsite .position-absolute {
  position: absolute !important;
}
.bodywebsite .position-fixed {
  position: fixed !important;
}
.bodywebsite .position-sticky {
  position: sticky !important;
}
.bodywebsite .dolsticky {
	position: sticky;
	top: 0;
	z-index: 100;
}
.bodywebsite .fixed-top {
  position: fixed;
  top: 0;
  right: 0;
  left: 0;
  z-index: 1030;
}
.bodywebsite .fixed-bottom {
  position: fixed;
  right: 0;
  bottom: 0;
  left: 0;
  z-index: 1030;
}
@supports (position: sticky) {
  .bodywebsite .sticky-top {
	position: sticky;
	top: 0;
	z-index: 1020;
  }
}
.bodywebsite .sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  clip-path: inset(50%);
  border: 0;
}
.bodywebsite .sr-only-focusable:active,
.bodywebsite .sr-only-focusable:focus {
  position: static;
  width: auto;
  height: auto;
  overflow: visible;
  clip: auto;
  white-space: normal;
  clip-path: none;
}
.bodywebsite .w-25 {
  width: 25% !important;
}
.bodywebsite .w-50 {
  width: 50% !important;
}
.bodywebsite .w-75 {
  width: 75% !important;
}
.bodywebsite .w-100 {
  width: 100% !important;
}
.bodywebsite .h-25 {
  height: 25% !important;
}
.bodywebsite .h-50 {
  height: 50% !important;
}
.bodywebsite .h-75 {
  height: 75% !important;
}
.bodywebsite .h-100 {
  height: 100% !important;
}
.bodywebsite .mw-100 {
  max-width: 100% !important;
}
.bodywebsite .mh-100 {
  max-height: 100% !important;
}
.bodywebsite .m-0 {
  margin: 0 !important;
}
.bodywebsite .mt-0,
.bodywebsite .my-0 {
  margin-top: 0 !important;
}
.bodywebsite .mr-0,
.bodywebsite .mx-0 {
  margin-right: 0 !important;
}
.bodywebsite .mb-0,
.bodywebsite .my-0 {
  margin-bottom: 0 !important;
}
.bodywebsite .ml-0,
.bodywebsite .mx-0 {
  margin-left: 0 !important;
}
.bodywebsite .m-1 {
  margin: 0.25rem !important;
}
.bodywebsite .mt-1,
.bodywebsite .my-1 {
  margin-top: 0.25rem !important;
}
.bodywebsite .mr-1,
.bodywebsite .mx-1 {
  margin-right: 0.25rem !important;
}
.bodywebsite .mb-1,
.bodywebsite .my-1 {
  margin-bottom: 0.25rem !important;
}
.bodywebsite .ml-1,
.bodywebsite .mx-1 {
  margin-left: 0.25rem !important;
}
.bodywebsite .m-2 {
  margin: 0.5rem !important;
}
.bodywebsite .mt-2,
.bodywebsite .my-2 {
  margin-top: 0.5rem !important;
}
.bodywebsite .mr-2,
.bodywebsite .mx-2 {
  margin-right: 0.5rem !important;
}
.bodywebsite .mb-2,
.bodywebsite .my-2 {
  margin-bottom: 0.5rem !important;
}
.bodywebsite .ml-2,
.bodywebsite .mx-2 {
  margin-left: 0.5rem !important;
}
.bodywebsite .m-3 {
  margin: 1rem !important;
}
.bodywebsite .mt-3,
.bodywebsite .my-3 {
  margin-top: 1rem !important;
}
.bodywebsite .mr-3,
.bodywebsite .mx-3 {
  margin-right: 1rem !important;
}
.bodywebsite .mb-3,
.bodywebsite .my-3 {
  margin-bottom: 1rem !important;
}
.bodywebsite .ml-3,
.bodywebsite .mx-3 {
  margin-left: 1rem !important;
}
.bodywebsite .m-4 {
  margin: 1.5rem !important;
}
.bodywebsite .mt-4,
.bodywebsite .my-4 {
  margin-top: 1.5rem !important;
}
.bodywebsite .mr-4,
.bodywebsite .mx-4 {
  margin-right: 1.5rem !important;
}
.bodywebsite .mb-4,
.bodywebsite .my-4 {
  margin-bottom: 1.5rem !important;
}
.bodywebsite .ml-4,
.bodywebsite .mx-4 {
  margin-left: 1.5rem !important;
}
.bodywebsite .m-5 {
  margin: 3rem !important;
}
.bodywebsite .mt-5,
.bodywebsite .my-5 {
  margin-top: 3rem !important;
}
.bodywebsite .mr-5,
.bodywebsite .mx-5 {
  margin-right: 3rem !important;
}
.bodywebsite .mb-5,
.bodywebsite .my-5 {
  margin-bottom: 3rem !important;
}
.bodywebsite .ml-5,
.bodywebsite .mx-5 {
  margin-left: 3rem !important;
}
.bodywebsite .p-0 {
  padding: 0 !important;
}
.bodywebsite .pt-0,
.bodywebsite .py-0 {
  padding-top: 0 !important;
}
.bodywebsite .pr-0,
.bodywebsite .px-0 {
  padding-right: 0 !important;
}
.bodywebsite .pb-0,
.bodywebsite .py-0 {
  padding-bottom: 0 !important;
}
.bodywebsite .pl-0,
.bodywebsite .px-0 {
  padding-left: 0 !important;
}
.bodywebsite .p-1 {
  padding: 0.25rem !important;
}
.bodywebsite .pt-1,
.bodywebsite .py-1 {
  padding-top: 0.25rem !important;
}
.bodywebsite .pr-1,
.bodywebsite .px-1 {
  padding-right: 0.25rem !important;
}
.bodywebsite .pb-1,
.bodywebsite .py-1 {
  padding-bottom: 0.25rem !important;
}
.bodywebsite .pl-1,
.bodywebsite .px-1 {
  padding-left: 0.25rem !important;
}
.bodywebsite .p-2 {
  padding: 0.5rem !important;
}
.bodywebsite .pt-2,
.bodywebsite .py-2 {
  padding-top: 0.5rem !important;
}
.bodywebsite .pr-2,
.bodywebsite .px-2 {
  padding-right: 0.5rem !important;
}
.bodywebsite .pb-2,
.bodywebsite .py-2 {
  padding-bottom: 0.5rem !important;
}
.bodywebsite .pl-2,
.bodywebsite .px-2 {
  padding-left: 0.5rem !important;
}
.bodywebsite .p-3 {
  padding: 1rem !important;
}
.bodywebsite .pt-3,
.bodywebsite .py-3 {
  padding-top: 1rem !important;
}
.bodywebsite .pr-3,
.bodywebsite .px-3 {
  padding-right: 1rem !important;
}
.bodywebsite .pb-3,
.bodywebsite .py-3 {
  padding-bottom: 1rem !important;
}
.bodywebsite .pl-3,
.bodywebsite .px-3 {
  padding-left: 1rem !important;
}
.bodywebsite .p-4 {
  padding: 1.5rem !important;
}
.bodywebsite .pt-4,
.bodywebsite .py-4 {
  padding-top: 1.5rem !important;
}
.bodywebsite .pr-4,
.bodywebsite .px-4 {
  padding-right: 1.5rem !important;
}
.bodywebsite .pb-4,
.bodywebsite .py-4 {
  padding-bottom: 1.5rem !important;
}
.bodywebsite .pl-4,
.bodywebsite .px-4 {
  padding-left: 1.5rem !important;
}
.bodywebsite .p-5 {
  padding: 3rem !important;
}
.bodywebsite .pt-5,
.bodywebsite .py-5 {
  padding-top: 3rem !important;
}
.bodywebsite .pr-5,
.bodywebsite .px-5 {
  padding-right: 3rem !important;
}
.bodywebsite .pb-5,
.bodywebsite .py-5 {
  padding-bottom: 3rem !important;
}
.bodywebsite .pl-5,
.bodywebsite .px-5 {
  padding-left: 3rem !important;
}
.bodywebsite .m-auto {
  margin: auto !important;
}
.bodywebsite .mt-auto,
.bodywebsite .my-auto {
  margin-top: auto !important;
}
.bodywebsite .mr-auto,
.bodywebsite .mx-auto {
  margin-right: auto !important;
}
.bodywebsite .mb-auto,
.bodywebsite .my-auto {
  margin-bottom: auto !important;
}
.bodywebsite .ml-auto,
.bodywebsite .mx-auto {
  margin-left: auto !important;
}
@media (min-width: 576px) {
  .bodywebsite .m-sm-0 {
	margin: 0 !important;
  }
  .bodywebsite .mt-sm-0,
  .bodywebsite .my-sm-0 {
	margin-top: 0 !important;
  }
  .bodywebsite .mr-sm-0,
  .bodywebsite .mx-sm-0 {
	margin-right: 0 !important;
  }
  .bodywebsite .mb-sm-0,
  .bodywebsite .my-sm-0 {
	margin-bottom: 0 !important;
  }
  .bodywebsite .ml-sm-0,
  .bodywebsite .mx-sm-0 {
	margin-left: 0 !important;
  }
  .bodywebsite .m-sm-1 {
	margin: 0.25rem !important;
  }
  .bodywebsite .mt-sm-1,
  .bodywebsite .my-sm-1 {
	margin-top: 0.25rem !important;
  }
  .bodywebsite .mr-sm-1,
  .bodywebsite .mx-sm-1 {
	margin-right: 0.25rem !important;
  }
  .bodywebsite .mb-sm-1,
  .bodywebsite .my-sm-1 {
	margin-bottom: 0.25rem !important;
  }
  .bodywebsite .ml-sm-1,
  .bodywebsite .mx-sm-1 {
	margin-left: 0.25rem !important;
  }
  .bodywebsite .m-sm-2 {
	margin: 0.5rem !important;
  }
  .bodywebsite .mt-sm-2,
  .bodywebsite .my-sm-2 {
	margin-top: 0.5rem !important;
  }
  .bodywebsite .mr-sm-2,
  .bodywebsite .mx-sm-2 {
	margin-right: 0.5rem !important;
  }
  .bodywebsite .mb-sm-2,
  .bodywebsite .my-sm-2 {
	margin-bottom: 0.5rem !important;
  }
  .bodywebsite .ml-sm-2,
  .bodywebsite .mx-sm-2 {
	margin-left: 0.5rem !important;
  }
  .bodywebsite .m-sm-3 {
	margin: 1rem !important;
  }
  .bodywebsite .mt-sm-3,
  .bodywebsite .my-sm-3 {
	margin-top: 1rem !important;
  }
  .bodywebsite .mr-sm-3,
  .bodywebsite .mx-sm-3 {
	margin-right: 1rem !important;
  }
  .bodywebsite .mb-sm-3,
  .bodywebsite .my-sm-3 {
	margin-bottom: 1rem !important;
  }
  .bodywebsite .ml-sm-3,
  .bodywebsite .mx-sm-3 {
	margin-left: 1rem !important;
  }
  .bodywebsite .m-sm-4 {
	margin: 1.5rem !important;
  }
  .bodywebsite .mt-sm-4,
  .bodywebsite .my-sm-4 {
	margin-top: 1.5rem !important;
  }
  .bodywebsite .mr-sm-4,
  .bodywebsite .mx-sm-4 {
	margin-right: 1.5rem !important;
  }
  .bodywebsite .mb-sm-4,
  .bodywebsite .my-sm-4 {
	margin-bottom: 1.5rem !important;
  }
  .bodywebsite .ml-sm-4,
  .bodywebsite .mx-sm-4 {
	margin-left: 1.5rem !important;
  }
  .bodywebsite .m-sm-5 {
	margin: 3rem !important;
  }
  .bodywebsite .mt-sm-5,
  .bodywebsite .my-sm-5 {
	margin-top: 3rem !important;
  }
  .bodywebsite .mr-sm-5,
  .bodywebsite .mx-sm-5 {
	margin-right: 3rem !important;
  }
  .bodywebsite .mb-sm-5,
  .bodywebsite .my-sm-5 {
	margin-bottom: 3rem !important;
  }
  .bodywebsite .ml-sm-5,
  .bodywebsite .mx-sm-5 {
	margin-left: 3rem !important;
  }
  .bodywebsite .p-sm-0 {
	padding: 0 !important;
  }
  .bodywebsite .pt-sm-0,
  .bodywebsite .py-sm-0 {
	padding-top: 0 !important;
  }
  .bodywebsite .pr-sm-0,
  .bodywebsite .px-sm-0 {
	padding-right: 0 !important;
  }
  .bodywebsite .pb-sm-0,
  .bodywebsite .py-sm-0 {
	padding-bottom: 0 !important;
  }
  .bodywebsite .pl-sm-0,
  .bodywebsite .px-sm-0 {
	padding-left: 0 !important;
  }
  .bodywebsite .p-sm-1 {
	padding: 0.25rem !important;
  }
  .bodywebsite .pt-sm-1,
  .bodywebsite .py-sm-1 {
	padding-top: 0.25rem !important;
  }
  .bodywebsite .pr-sm-1,
  .bodywebsite .px-sm-1 {
	padding-right: 0.25rem !important;
  }
  .bodywebsite .pb-sm-1,
  .bodywebsite .py-sm-1 {
	padding-bottom: 0.25rem !important;
  }
  .bodywebsite .pl-sm-1,
  .bodywebsite .px-sm-1 {
	padding-left: 0.25rem !important;
  }
  .bodywebsite .p-sm-2 {
	padding: 0.5rem !important;
  }
  .bodywebsite .pt-sm-2,
  .bodywebsite .py-sm-2 {
	padding-top: 0.5rem !important;
  }
  .bodywebsite .pr-sm-2,
  .bodywebsite .px-sm-2 {
	padding-right: 0.5rem !important;
  }
  .bodywebsite .pb-sm-2,
  .bodywebsite .py-sm-2 {
	padding-bottom: 0.5rem !important;
  }
  .bodywebsite .pl-sm-2,
  .bodywebsite .px-sm-2 {
	padding-left: 0.5rem !important;
  }
  .bodywebsite .p-sm-3 {
	padding: 1rem !important;
  }
  .bodywebsite .pt-sm-3,
  .bodywebsite .py-sm-3 {
	padding-top: 1rem !important;
  }
  .bodywebsite .pr-sm-3,
  .bodywebsite .px-sm-3 {
	padding-right: 1rem !important;
  }
  .bodywebsite .pb-sm-3,
  .bodywebsite .py-sm-3 {
	padding-bottom: 1rem !important;
  }
  .bodywebsite .pl-sm-3,
  .bodywebsite .px-sm-3 {
	padding-left: 1rem !important;
  }
  .bodywebsite .p-sm-4 {
	padding: 1.5rem !important;
  }
  .bodywebsite .pt-sm-4,
  .bodywebsite .py-sm-4 {
	padding-top: 1.5rem !important;
  }
  .bodywebsite .pr-sm-4,
  .bodywebsite .px-sm-4 {
	padding-right: 1.5rem !important;
  }
  .bodywebsite .pb-sm-4,
  .bodywebsite .py-sm-4 {
	padding-bottom: 1.5rem !important;
  }
  .bodywebsite .pl-sm-4,
  .bodywebsite .px-sm-4 {
	padding-left: 1.5rem !important;
  }
  .bodywebsite .p-sm-5 {
	padding: 3rem !important;
  }
  .bodywebsite .pt-sm-5,
  .bodywebsite .py-sm-5 {
	padding-top: 3rem !important;
  }
  .bodywebsite .pr-sm-5,
  .bodywebsite .px-sm-5 {
	padding-right: 3rem !important;
  }
  .bodywebsite .pb-sm-5,
  .bodywebsite .py-sm-5 {
	padding-bottom: 3rem !important;
  }
  .bodywebsite .pl-sm-5,
  .bodywebsite .px-sm-5 {
	padding-left: 3rem !important;
  }
  .bodywebsite .m-sm-auto {
	margin: auto !important;
  }
  .bodywebsite .mt-sm-auto,
  .bodywebsite .my-sm-auto {
	margin-top: auto !important;
  }
  .bodywebsite .mr-sm-auto,
  .bodywebsite .mx-sm-auto {
	margin-right: auto !important;
  }
  .bodywebsite .mb-sm-auto,
  .bodywebsite .my-sm-auto {
	margin-bottom: auto !important;
  }
  .bodywebsite .ml-sm-auto,
  .bodywebsite .mx-sm-auto {
	margin-left: auto !important;
  }
}
@media (min-width: 768px) {
  .bodywebsite .m-md-0 {
	margin: 0 !important;
  }
  .bodywebsite .mt-md-0,
  .bodywebsite .my-md-0 {
	margin-top: 0 !important;
  }
  .bodywebsite .mr-md-0,
  .bodywebsite .mx-md-0 {
	margin-right: 0 !important;
  }
  .bodywebsite .mb-md-0,
  .bodywebsite .my-md-0 {
	margin-bottom: 0 !important;
  }
  .bodywebsite .ml-md-0,
  .bodywebsite .mx-md-0 {
	margin-left: 0 !important;
  }
  .bodywebsite .m-md-1 {
	margin: 0.25rem !important;
  }
  .bodywebsite .mt-md-1,
  .bodywebsite .my-md-1 {
	margin-top: 0.25rem !important;
  }
  .bodywebsite .mr-md-1,
  .bodywebsite .mx-md-1 {
	margin-right: 0.25rem !important;
  }
  .bodywebsite .mb-md-1,
  .bodywebsite .my-md-1 {
	margin-bottom: 0.25rem !important;
  }
  .bodywebsite .ml-md-1,
  .bodywebsite .mx-md-1 {
	margin-left: 0.25rem !important;
  }
  .bodywebsite .m-md-2 {
	margin: 0.5rem !important;
  }
  .bodywebsite .mt-md-2,
  .bodywebsite .my-md-2 {
	margin-top: 0.5rem !important;
  }
  .bodywebsite .mr-md-2,
  .bodywebsite .mx-md-2 {
	margin-right: 0.5rem !important;
  }
  .bodywebsite .mb-md-2,
  .bodywebsite .my-md-2 {
	margin-bottom: 0.5rem !important;
  }
  .bodywebsite .ml-md-2,
  .bodywebsite .mx-md-2 {
	margin-left: 0.5rem !important;
  }
  .bodywebsite .m-md-3 {
	margin: 1rem !important;
  }
  .bodywebsite .mt-md-3,
  .bodywebsite .my-md-3 {
	margin-top: 1rem !important;
  }
  .bodywebsite .mr-md-3,
  .bodywebsite .mx-md-3 {
	margin-right: 1rem !important;
  }
  .bodywebsite .mb-md-3,
  .bodywebsite .my-md-3 {
	margin-bottom: 1rem !important;
  }
  .bodywebsite .ml-md-3,
  .bodywebsite .mx-md-3 {
	margin-left: 1rem !important;
  }
  .bodywebsite .m-md-4 {
	margin: 1.5rem !important;
  }
  .bodywebsite .mt-md-4,
  .bodywebsite .my-md-4 {
	margin-top: 1.5rem !important;
  }
  .bodywebsite .mr-md-4,
  .bodywebsite .mx-md-4 {
	margin-right: 1.5rem !important;
  }
  .bodywebsite .mb-md-4,
  .bodywebsite .my-md-4 {
	margin-bottom: 1.5rem !important;
  }
  .bodywebsite .ml-md-4,
  .bodywebsite .mx-md-4 {
	margin-left: 1.5rem !important;
  }
  .bodywebsite .m-md-5 {
	margin: 3rem !important;
  }
  .bodywebsite .mt-md-5,
  .bodywebsite .my-md-5 {
	margin-top: 3rem !important;
  }
  .bodywebsite .mr-md-5,
  .bodywebsite .mx-md-5 {
	margin-right: 3rem !important;
  }
  .bodywebsite .mb-md-5,
  .bodywebsite .my-md-5 {
	margin-bottom: 3rem !important;
  }
  .bodywebsite .ml-md-5,
  .bodywebsite .mx-md-5 {
	margin-left: 3rem !important;
  }
  .bodywebsite .p-md-0 {
	padding: 0 !important;
  }
  .bodywebsite .pt-md-0,
  .bodywebsite .py-md-0 {
	padding-top: 0 !important;
  }
  .bodywebsite .pr-md-0,
  .bodywebsite .px-md-0 {
	padding-right: 0 !important;
  }
  .bodywebsite .pb-md-0,
  .bodywebsite .py-md-0 {
	padding-bottom: 0 !important;
  }
  .bodywebsite .pl-md-0,
  .bodywebsite .px-md-0 {
	padding-left: 0 !important;
  }
  .bodywebsite .p-md-1 {
	padding: 0.25rem !important;
  }
  .bodywebsite .pt-md-1,
  .bodywebsite .py-md-1 {
	padding-top: 0.25rem !important;
  }
  .bodywebsite .pr-md-1,
  .bodywebsite .px-md-1 {
	padding-right: 0.25rem !important;
  }
  .bodywebsite .pb-md-1,
  .bodywebsite .py-md-1 {
	padding-bottom: 0.25rem !important;
  }
  .bodywebsite .pl-md-1,
  .bodywebsite .px-md-1 {
	padding-left: 0.25rem !important;
  }
  .bodywebsite .p-md-2 {
	padding: 0.5rem !important;
  }
  .bodywebsite .pt-md-2,
  .bodywebsite .py-md-2 {
	padding-top: 0.5rem !important;
  }
  .bodywebsite .pr-md-2,
  .bodywebsite .px-md-2 {
	padding-right: 0.5rem !important;
  }
  .bodywebsite .pb-md-2,
  .bodywebsite .py-md-2 {
	padding-bottom: 0.5rem !important;
  }
  .bodywebsite .pl-md-2,
  .bodywebsite .px-md-2 {
	padding-left: 0.5rem !important;
  }
  .bodywebsite .p-md-3 {
	padding: 1rem !important;
  }
  .bodywebsite .pt-md-3,
  .bodywebsite .py-md-3 {
	padding-top: 1rem !important;
  }
  .bodywebsite .pr-md-3,
  .bodywebsite .px-md-3 {
	padding-right: 1rem !important;
  }
  .bodywebsite .pb-md-3,
  .bodywebsite .py-md-3 {
	padding-bottom: 1rem !important;
  }
  .bodywebsite .pl-md-3,
  .bodywebsite .px-md-3 {
	padding-left: 1rem !important;
  }
  .bodywebsite .p-md-4 {
	padding: 1.5rem !important;
  }
  .bodywebsite .pt-md-4,
  .bodywebsite .py-md-4 {
	padding-top: 1.5rem !important;
  }
  .bodywebsite .pr-md-4,
  .bodywebsite .px-md-4 {
	padding-right: 1.5rem !important;
  }
  .bodywebsite .pb-md-4,
  .bodywebsite .py-md-4 {
	padding-bottom: 1.5rem !important;
  }
  .bodywebsite .pl-md-4,
  .bodywebsite .px-md-4 {
	padding-left: 1.5rem !important;
  }
  .bodywebsite .p-md-5 {
	padding: 3rem !important;
  }
  .bodywebsite .pt-md-5,
  .bodywebsite .py-md-5 {
	padding-top: 3rem !important;
  }
  .bodywebsite .pr-md-5,
  .bodywebsite .px-md-5 {
	padding-right: 3rem !important;
  }
  .bodywebsite .pb-md-5,
  .bodywebsite .py-md-5 {
	padding-bottom: 3rem !important;
  }
  .bodywebsite .pl-md-5,
  .bodywebsite .px-md-5 {
	padding-left: 3rem !important;
  }
  .bodywebsite .m-md-auto {
	margin: auto !important;
  }
  .bodywebsite .mt-md-auto,
  .bodywebsite .my-md-auto {
	margin-top: auto !important;
  }
  .bodywebsite .mr-md-auto,
  .bodywebsite .mx-md-auto {
	margin-right: auto !important;
  }
  .bodywebsite .mb-md-auto,
  .bodywebsite .my-md-auto {
	margin-bottom: auto !important;
  }
  .bodywebsite .ml-md-auto,
  .bodywebsite .mx-md-auto {
	margin-left: auto !important;
  }
}
@media (min-width: 992px) {
  .bodywebsite .m-lg-0 {
	margin: 0 !important;
  }
  .bodywebsite .mt-lg-0,
  .bodywebsite .my-lg-0 {
	margin-top: 0 !important;
  }
  .bodywebsite .mr-lg-0,
  .bodywebsite .mx-lg-0 {
	margin-right: 0 !important;
  }
  .bodywebsite .mb-lg-0,
  .bodywebsite .my-lg-0 {
	margin-bottom: 0 !important;
  }
  .bodywebsite .ml-lg-0,
  .bodywebsite .mx-lg-0 {
	margin-left: 0 !important;
  }
  .bodywebsite .m-lg-1 {
	margin: 0.25rem !important;
  }
  .bodywebsite .mt-lg-1,
  .bodywebsite .my-lg-1 {
	margin-top: 0.25rem !important;
  }
  .bodywebsite .mr-lg-1,
  .bodywebsite .mx-lg-1 {
	margin-right: 0.25rem !important;
  }
  .bodywebsite .mb-lg-1,
  .bodywebsite .my-lg-1 {
	margin-bottom: 0.25rem !important;
  }
  .bodywebsite .ml-lg-1,
  .bodywebsite .mx-lg-1 {
	margin-left: 0.25rem !important;
  }
  .bodywebsite .m-lg-2 {
	margin: 0.5rem !important;
  }
  .bodywebsite .mt-lg-2,
  .bodywebsite .my-lg-2 {
	margin-top: 0.5rem !important;
  }
  .bodywebsite .mr-lg-2,
  .bodywebsite .mx-lg-2 {
	margin-right: 0.5rem !important;
  }
  .bodywebsite .mb-lg-2,
  .bodywebsite .my-lg-2 {
	margin-bottom: 0.5rem !important;
  }
  .bodywebsite .ml-lg-2,
  .bodywebsite .mx-lg-2 {
	margin-left: 0.5rem !important;
  }
  .bodywebsite .m-lg-3 {
	margin: 1rem !important;
  }
  .bodywebsite .mt-lg-3,
  .bodywebsite .my-lg-3 {
	margin-top: 1rem !important;
  }
  .bodywebsite .mr-lg-3,
  .bodywebsite .mx-lg-3 {
	margin-right: 1rem !important;
  }
  .bodywebsite .mb-lg-3,
  .bodywebsite .my-lg-3 {
	margin-bottom: 1rem !important;
  }
  .bodywebsite .ml-lg-3,
  .bodywebsite .mx-lg-3 {
	margin-left: 1rem !important;
  }
  .bodywebsite .m-lg-4 {
	margin: 1.5rem !important;
  }
  .bodywebsite .mt-lg-4,
  .bodywebsite .my-lg-4 {
	margin-top: 1.5rem !important;
  }
  .bodywebsite .mr-lg-4,
  .bodywebsite .mx-lg-4 {
	margin-right: 1.5rem !important;
  }
  .bodywebsite .mb-lg-4,
  .bodywebsite .my-lg-4 {
	margin-bottom: 1.5rem !important;
  }
  .bodywebsite .ml-lg-4,
  .bodywebsite .mx-lg-4 {
	margin-left: 1.5rem !important;
  }
  .bodywebsite .m-lg-5 {
	margin: 3rem !important;
  }
  .bodywebsite .mt-lg-5,
  .bodywebsite .my-lg-5 {
	margin-top: 3rem !important;
  }
  .bodywebsite .mr-lg-5,
  .bodywebsite .mx-lg-5 {
	margin-right: 3rem !important;
  }
  .bodywebsite .mb-lg-5,
  .bodywebsite .my-lg-5 {
	margin-bottom: 3rem !important;
  }
  .bodywebsite .ml-lg-5,
  .bodywebsite .mx-lg-5 {
	margin-left: 3rem !important;
  }
  .bodywebsite .p-lg-0 {
	padding: 0 !important;
  }
  .bodywebsite .pt-lg-0,
  .bodywebsite .py-lg-0 {
	padding-top: 0 !important;
  }
  .bodywebsite .pr-lg-0,
  .bodywebsite .px-lg-0 {
	padding-right: 0 !important;
  }
  .bodywebsite .pb-lg-0,
  .bodywebsite .py-lg-0 {
	padding-bottom: 0 !important;
  }
  .bodywebsite .pl-lg-0,
  .bodywebsite .px-lg-0 {
	padding-left: 0 !important;
  }
  .bodywebsite .p-lg-1 {
	padding: 0.25rem !important;
  }
  .bodywebsite .pt-lg-1,
  .bodywebsite .py-lg-1 {
	padding-top: 0.25rem !important;
  }
  .bodywebsite .pr-lg-1,
  .bodywebsite .px-lg-1 {
	padding-right: 0.25rem !important;
  }
  .bodywebsite .pb-lg-1,
  .bodywebsite .py-lg-1 {
	padding-bottom: 0.25rem !important;
  }
  .bodywebsite .pl-lg-1,
  .bodywebsite .px-lg-1 {
	padding-left: 0.25rem !important;
  }
  .bodywebsite .p-lg-2 {
	padding: 0.5rem !important;
  }
  .bodywebsite .pt-lg-2,
  .bodywebsite .py-lg-2 {
	padding-top: 0.5rem !important;
  }
  .bodywebsite .pr-lg-2,
  .bodywebsite .px-lg-2 {
	padding-right: 0.5rem !important;
  }
  .bodywebsite .pb-lg-2,
  .bodywebsite .py-lg-2 {
	padding-bottom: 0.5rem !important;
  }
  .bodywebsite .pl-lg-2,
  .bodywebsite .px-lg-2 {
	padding-left: 0.5rem !important;
  }
  .bodywebsite .p-lg-3 {
	padding: 1rem !important;
  }
  .bodywebsite .pt-lg-3,
  .bodywebsite .py-lg-3 {
	padding-top: 1rem !important;
  }
  .bodywebsite .pr-lg-3,
  .bodywebsite .px-lg-3 {
	padding-right: 1rem !important;
  }
  .bodywebsite .pb-lg-3,
  .bodywebsite .py-lg-3 {
	padding-bottom: 1rem !important;
  }
  .bodywebsite .pl-lg-3,
  .bodywebsite .px-lg-3 {
	padding-left: 1rem !important;
  }
  .bodywebsite .p-lg-4 {
	padding: 1.5rem !important;
  }
  .bodywebsite .pt-lg-4,
  .bodywebsite .py-lg-4 {
	padding-top: 1.5rem !important;
  }
  .bodywebsite .pr-lg-4,
  .bodywebsite .px-lg-4 {
	padding-right: 1.5rem !important;
  }
  .bodywebsite .pb-lg-4,
  .bodywebsite .py-lg-4 {
	padding-bottom: 1.5rem !important;
  }
  .bodywebsite .pl-lg-4,
  .bodywebsite .px-lg-4 {
	padding-left: 1.5rem !important;
  }
  .bodywebsite .p-lg-5 {
	padding: 3rem !important;
  }
  .bodywebsite .pt-lg-5,
  .bodywebsite .py-lg-5 {
	padding-top: 3rem !important;
  }
  .bodywebsite .pr-lg-5,
  .bodywebsite .px-lg-5 {
	padding-right: 3rem !important;
  }
  .bodywebsite .pb-lg-5,
  .bodywebsite .py-lg-5 {
	padding-bottom: 3rem !important;
  }
  .bodywebsite .pl-lg-5,
  .bodywebsite .px-lg-5 {
	padding-left: 3rem !important;
  }
  .bodywebsite .m-lg-auto {
	margin: auto !important;
  }
  .bodywebsite .mt-lg-auto,
  .bodywebsite .my-lg-auto {
	margin-top: auto !important;
  }
  .bodywebsite .mr-lg-auto,
  .bodywebsite .mx-lg-auto {
	margin-right: auto !important;
  }
  .bodywebsite .mb-lg-auto,
  .bodywebsite .my-lg-auto {
	margin-bottom: auto !important;
  }
  .bodywebsite .ml-lg-auto,
  .bodywebsite .mx-lg-auto {
	margin-left: auto !important;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .m-xl-0 {
	margin: 0 !important;
  }
  .bodywebsite .mt-xl-0,
  .bodywebsite .my-xl-0 {
	margin-top: 0 !important;
  }
  .bodywebsite .mr-xl-0,
  .bodywebsite .mx-xl-0 {
	margin-right: 0 !important;
  }
  .bodywebsite .mb-xl-0,
  .bodywebsite .my-xl-0 {
	margin-bottom: 0 !important;
  }
  .bodywebsite .ml-xl-0,
  .bodywebsite .mx-xl-0 {
	margin-left: 0 !important;
  }
  .bodywebsite .m-xl-1 {
	margin: 0.25rem !important;
  }
  .bodywebsite .mt-xl-1,
  .bodywebsite .my-xl-1 {
	margin-top: 0.25rem !important;
  }
  .bodywebsite .mr-xl-1,
  .bodywebsite .mx-xl-1 {
	margin-right: 0.25rem !important;
  }
  .bodywebsite .mb-xl-1,
  .bodywebsite .my-xl-1 {
	margin-bottom: 0.25rem !important;
  }
  .bodywebsite .ml-xl-1,
  .bodywebsite .mx-xl-1 {
	margin-left: 0.25rem !important;
  }
  .bodywebsite .m-xl-2 {
	margin: 0.5rem !important;
  }
  .bodywebsite .mt-xl-2,
  .bodywebsite .my-xl-2 {
	margin-top: 0.5rem !important;
  }
  .bodywebsite .mr-xl-2,
  .bodywebsite .mx-xl-2 {
	margin-right: 0.5rem !important;
  }
  .bodywebsite .mb-xl-2,
  .bodywebsite .my-xl-2 {
	margin-bottom: 0.5rem !important;
  }
  .bodywebsite .ml-xl-2,
  .bodywebsite .mx-xl-2 {
	margin-left: 0.5rem !important;
  }
  .bodywebsite .m-xl-3 {
	margin: 1rem !important;
  }
  .bodywebsite .mt-xl-3,
  .bodywebsite .my-xl-3 {
	margin-top: 1rem !important;
  }
  .bodywebsite .mr-xl-3,
  .bodywebsite .mx-xl-3 {
	margin-right: 1rem !important;
  }
  .bodywebsite .mb-xl-3,
  .bodywebsite .my-xl-3 {
	margin-bottom: 1rem !important;
  }
  .bodywebsite .ml-xl-3,
  .bodywebsite .mx-xl-3 {
	margin-left: 1rem !important;
  }
  .bodywebsite .m-xl-4 {
	margin: 1.5rem !important;
  }
  .bodywebsite .mt-xl-4,
  .bodywebsite .my-xl-4 {
	margin-top: 1.5rem !important;
  }
  .bodywebsite .mr-xl-4,
  .bodywebsite .mx-xl-4 {
	margin-right: 1.5rem !important;
  }
  .bodywebsite .mb-xl-4,
  .bodywebsite .my-xl-4 {
	margin-bottom: 1.5rem !important;
  }
  .bodywebsite .ml-xl-4,
  .bodywebsite .mx-xl-4 {
	margin-left: 1.5rem !important;
  }
  .bodywebsite .m-xl-5 {
	margin: 3rem !important;
  }
  .bodywebsite .mt-xl-5,
  .bodywebsite .my-xl-5 {
	margin-top: 3rem !important;
  }
  .bodywebsite .mr-xl-5,
  .bodywebsite .mx-xl-5 {
	margin-right: 3rem !important;
  }
  .bodywebsite .mb-xl-5,
  .bodywebsite .my-xl-5 {
	margin-bottom: 3rem !important;
  }
  .bodywebsite .ml-xl-5,
  .bodywebsite .mx-xl-5 {
	margin-left: 3rem !important;
  }
  .bodywebsite .p-xl-0 {
	padding: 0 !important;
  }
  .bodywebsite .pt-xl-0,
  .bodywebsite .py-xl-0 {
	padding-top: 0 !important;
  }
  .bodywebsite .pr-xl-0,
  .bodywebsite .px-xl-0 {
	padding-right: 0 !important;
  }
  .bodywebsite .pb-xl-0,
  .bodywebsite .py-xl-0 {
	padding-bottom: 0 !important;
  }
  .bodywebsite .pl-xl-0,
  .bodywebsite .px-xl-0 {
	padding-left: 0 !important;
  }
  .bodywebsite .p-xl-1 {
	padding: 0.25rem !important;
  }
  .bodywebsite .pt-xl-1,
  .bodywebsite .py-xl-1 {
	padding-top: 0.25rem !important;
  }
  .bodywebsite .pr-xl-1,
  .bodywebsite .px-xl-1 {
	padding-right: 0.25rem !important;
  }
  .bodywebsite .pb-xl-1,
  .bodywebsite .py-xl-1 {
	padding-bottom: 0.25rem !important;
  }
  .bodywebsite .pl-xl-1,
  .bodywebsite .px-xl-1 {
	padding-left: 0.25rem !important;
  }
  .bodywebsite .p-xl-2 {
	padding: 0.5rem !important;
  }
  .bodywebsite .pt-xl-2,
  .bodywebsite .py-xl-2 {
	padding-top: 0.5rem !important;
  }
  .bodywebsite .pr-xl-2,
  .bodywebsite .px-xl-2 {
	padding-right: 0.5rem !important;
  }
  .bodywebsite .pb-xl-2,
  .bodywebsite .py-xl-2 {
	padding-bottom: 0.5rem !important;
  }
  .bodywebsite .pl-xl-2,
  .bodywebsite .px-xl-2 {
	padding-left: 0.5rem !important;
  }
  .bodywebsite .p-xl-3 {
	padding: 1rem !important;
  }
  .bodywebsite .pt-xl-3,
  .bodywebsite .py-xl-3 {
	padding-top: 1rem !important;
  }
  .bodywebsite .pr-xl-3,
  .bodywebsite .px-xl-3 {
	padding-right: 1rem !important;
  }
  .bodywebsite .pb-xl-3,
  .bodywebsite .py-xl-3 {
	padding-bottom: 1rem !important;
  }
  .bodywebsite .pl-xl-3,
  .bodywebsite .px-xl-3 {
	padding-left: 1rem !important;
  }
  .bodywebsite .p-xl-4 {
	padding: 1.5rem !important;
  }
  .bodywebsite .pt-xl-4,
  .bodywebsite .py-xl-4 {
	padding-top: 1.5rem !important;
  }
  .bodywebsite .pr-xl-4,
  .bodywebsite .px-xl-4 {
	padding-right: 1.5rem !important;
  }
  .bodywebsite .pb-xl-4,
  .bodywebsite .py-xl-4 {
	padding-bottom: 1.5rem !important;
  }
  .bodywebsite .pl-xl-4,
  .bodywebsite .px-xl-4 {
	padding-left: 1.5rem !important;
  }
  .bodywebsite .p-xl-5 {
	padding: 3rem !important;
  }
  .bodywebsite .pt-xl-5,
  .bodywebsite .py-xl-5 {
	padding-top: 3rem !important;
  }
  .bodywebsite .pr-xl-5,
  .bodywebsite .px-xl-5 {
	padding-right: 3rem !important;
  }
  .bodywebsite .pb-xl-5,
  .bodywebsite .py-xl-5 {
	padding-bottom: 3rem !important;
  }
  .bodywebsite .pl-xl-5,
  .bodywebsite .px-xl-5 {
	padding-left: 3rem !important;
  }
  .bodywebsite .m-xl-auto {
	margin: auto !important;
  }
  .bodywebsite .mt-xl-auto,
  .bodywebsite .my-xl-auto {
	margin-top: auto !important;
  }
  .bodywebsite .mr-xl-auto,
  .bodywebsite .mx-xl-auto {
	margin-right: auto !important;
  }
  .bodywebsite .mb-xl-auto,
  .bodywebsite .my-xl-auto {
	margin-bottom: auto !important;
  }
  .bodywebsite .ml-xl-auto,
  .bodywebsite .mx-xl-auto {
	margin-left: auto !important;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .m-xxl-0 {
	margin: 0 !important;
  }
  .bodywebsite .mt-xxl-0,
  .bodywebsite .my-xxl-0 {
	margin-top: 0 !important;
  }
  .bodywebsite .mr-xxl-0,
  .bodywebsite .mx-xxl-0 {
	margin-right: 0 !important;
  }
  .bodywebsite .mb-xxl-0,
  .bodywebsite .my-xxl-0 {
	margin-bottom: 0 !important;
  }
  .bodywebsite .ml-xxl-0,
  .bodywebsite .mx-xxl-0 {
	margin-left: 0 !important;
  }
  .bodywebsite .m-xxl-1 {
	margin: 0.25rem !important;
  }
  .bodywebsite .mt-xxl-1,
  .bodywebsite .my-xxl-1 {
	margin-top: 0.25rem !important;
  }
  .bodywebsite .mr-xxl-1,
  .bodywebsite .mx-xxl-1 {
	margin-right: 0.25rem !important;
  }
  .bodywebsite .mb-xxl-1,
  .bodywebsite .my-xxl-1 {
	margin-bottom: 0.25rem !important;
  }
  .bodywebsite .ml-xxl-1,
  .bodywebsite .mx-xxl-1 {
	margin-left: 0.25rem !important;
  }
  .bodywebsite .m-xxl-2 {
	margin: 0.5rem !important;
  }
  .bodywebsite .mt-xxl-2,
  .bodywebsite .my-xxl-2 {
	margin-top: 0.5rem !important;
  }
  .bodywebsite .mr-xxl-2,
  .bodywebsite .mx-xxl-2 {
	margin-right: 0.5rem !important;
  }
  .bodywebsite .mb-xxl-2,
  .bodywebsite .my-xxl-2 {
	margin-bottom: 0.5rem !important;
  }
  .bodywebsite .ml-xxl-2,
  .bodywebsite .mx-xxl-2 {
	margin-left: 0.5rem !important;
  }
  .bodywebsite .m-xxl-3 {
	margin: 1rem !important;
  }
  .bodywebsite .mt-xxl-3,
  .bodywebsite .my-xxl-3 {
	margin-top: 1rem !important;
  }
  .bodywebsite .mr-xxl-3,
  .bodywebsite .mx-xxl-3 {
	margin-right: 1rem !important;
  }
  .bodywebsite .mb-xxl-3,
  .bodywebsite .my-xxl-3 {
	margin-bottom: 1rem !important;
  }
  .bodywebsite .ml-xxl-3,
  .bodywebsite .mx-xxl-3 {
	margin-left: 1rem !important;
  }
  .bodywebsite .m-xxl-4 {
	margin: 1.5rem !important;
  }
  .bodywebsite .mt-xxl-4,
  .bodywebsite .my-xxl-4 {
	margin-top: 1.5rem !important;
  }
  .bodywebsite .mr-xxl-4,
  .bodywebsite .mx-xxl-4 {
	margin-right: 1.5rem !important;
  }
  .bodywebsite .mb-xxl-4,
  .bodywebsite .my-xxl-4 {
	margin-bottom: 1.5rem !important;
  }
  .bodywebsite .ml-xxl-4,
  .bodywebsite .mx-xxl-4 {
	margin-left: 1.5rem !important;
  }
  .bodywebsite .m-xxl-5 {
	margin: 3rem !important;
  }
  .bodywebsite .mt-xxl-5,
  .bodywebsite .my-xxl-5 {
	margin-top: 3rem !important;
  }
  .bodywebsite .mr-xxl-5,
  .bodywebsite .mx-xxl-5 {
	margin-right: 3rem !important;
  }
  .bodywebsite .mb-xxl-5,
  .bodywebsite .my-xxl-5 {
	margin-bottom: 3rem !important;
  }
  .bodywebsite .ml-xxl-5,
  .bodywebsite .mx-xxl-5 {
	margin-left: 3rem !important;
  }
  .bodywebsite .p-xxl-0 {
	padding: 0 !important;
  }
  .bodywebsite .pt-xxl-0,
  .bodywebsite .py-xxl-0 {
	padding-top: 0 !important;
  }
  .bodywebsite .pr-xxl-0,
  .bodywebsite .px-xxl-0 {
	padding-right: 0 !important;
  }
  .bodywebsite .pb-xxl-0,
  .bodywebsite .py-xxl-0 {
	padding-bottom: 0 !important;
  }
  .bodywebsite .pl-xxl-0,
  .bodywebsite .px-xxl-0 {
	padding-left: 0 !important;
  }
  .bodywebsite .p-xxl-1 {
	padding: 0.25rem !important;
  }
  .bodywebsite .pt-xxl-1,
  .bodywebsite .py-xxl-1 {
	padding-top: 0.25rem !important;
  }
  .bodywebsite .pr-xxl-1,
  .bodywebsite .px-xxl-1 {
	padding-right: 0.25rem !important;
  }
  .bodywebsite .pb-xxl-1,
  .bodywebsite .py-xxl-1 {
	padding-bottom: 0.25rem !important;
  }
  .bodywebsite .pl-xxl-1,
  .bodywebsite .px-xxl-1 {
	padding-left: 0.25rem !important;
  }
  .bodywebsite .p-xxl-2 {
	padding: 0.5rem !important;
  }
  .bodywebsite .pt-xxl-2,
  .bodywebsite .py-xxl-2 {
	padding-top: 0.5rem !important;
  }
  .bodywebsite .pr-xxl-2,
  .bodywebsite .px-xxl-2 {
	padding-right: 0.5rem !important;
  }
  .bodywebsite .pb-xxl-2,
  .bodywebsite .py-xxl-2 {
	padding-bottom: 0.5rem !important;
  }
  .bodywebsite .pl-xxl-2,
  .bodywebsite .px-xxl-2 {
	padding-left: 0.5rem !important;
  }
  .bodywebsite .p-xxl-3 {
	padding: 1rem !important;
  }
  .bodywebsite .pt-xxl-3,
  .bodywebsite .py-xxl-3 {
	padding-top: 1rem !important;
  }
  .bodywebsite .pr-xxl-3,
  .bodywebsite .px-xxl-3 {
	padding-right: 1rem !important;
  }
  .bodywebsite .pb-xxl-3,
  .bodywebsite .py-xxl-3 {
	padding-bottom: 1rem !important;
  }
  .bodywebsite .pl-xxl-3,
  .bodywebsite .px-xxl-3 {
	padding-left: 1rem !important;
  }
  .bodywebsite .p-xxl-4 {
	padding: 1.5rem !important;
  }
  .bodywebsite .pt-xxl-4,
  .bodywebsite .py-xxl-4 {
	padding-top: 1.5rem !important;
  }
  .bodywebsite .pr-xxl-4,
  .bodywebsite .px-xxl-4 {
	padding-right: 1.5rem !important;
  }
  .bodywebsite .pb-xxl-4,
  .bodywebsite .py-xxl-4 {
	padding-bottom: 1.5rem !important;
  }
  .bodywebsite .pl-xxl-4,
  .bodywebsite .px-xxl-4 {
	padding-left: 1.5rem !important;
  }
  .bodywebsite .p-xxl-5 {
	padding: 3rem !important;
  }
  .bodywebsite .pt-xxl-5,
  .bodywebsite .py-xxl-5 {
	padding-top: 3rem !important;
  }
  .bodywebsite .pr-xxl-5,
  .bodywebsite .px-xxl-5 {
	padding-right: 3rem !important;
  }
  .bodywebsite .pb-xxl-5,
  .bodywebsite .py-xxl-5 {
	padding-bottom: 3rem !important;
  }
  .bodywebsite .pl-xxl-5,
  .bodywebsite .px-xxl-5 {
	padding-left: 3rem !important;
  }
  .bodywebsite .m-xxl-auto {
	margin: auto !important;
  }
  .bodywebsite .mt-xxl-auto,
  .bodywebsite .my-xxl-auto {
	margin-top: auto !important;
  }
  .bodywebsite .mr-xxl-auto,
  .bodywebsite .mx-xxl-auto {
	margin-right: auto !important;
  }
  .bodywebsite .mb-xxl-auto,
  .bodywebsite .my-xxl-auto {
	margin-bottom: auto !important;
  }
  .bodywebsite .ml-xxl-auto,
  .bodywebsite .mx-xxl-auto {
	margin-left: auto !important;
  }
}
.bodywebsite .text-justify {
  text-align: justify !important;
}
.bodywebsite .text-nowrap {
  white-space: nowrap !important;
}
.bodywebsite .text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.bodywebsite .text-left {
  text-align: left !important;
}
.bodywebsite .text-right {
  text-align: right !important;
}
.bodywebsite .text-center {
  text-align: center !important;
}
@media (min-width: 576px) {
  .bodywebsite .text-sm-left {
	text-align: left !important;
  }
  .bodywebsite .text-sm-right {
	text-align: right !important;
  }
  .bodywebsite .text-sm-center {
	text-align: center !important;
  }
}
@media (min-width: 768px) {
  .bodywebsite .text-md-left {
	text-align: left !important;
  }
  .bodywebsite .text-md-right {
	text-align: right !important;
  }
  .bodywebsite .text-md-center {
	text-align: center !important;
  }
}
@media (min-width: 992px) {
  .bodywebsite .text-lg-left {
	text-align: left !important;
  }
  .bodywebsite .text-lg-right {
	text-align: right !important;
  }
  .bodywebsite .text-lg-center {
	text-align: center !important;
  }
}
@media (min-width: 1200px) {
  .bodywebsite .text-xl-left {
	text-align: left !important;
  }
  .bodywebsite .text-xl-right {
	text-align: right !important;
  }
  .bodywebsite .text-xl-center {
	text-align: center !important;
  }
}
@media (min-width: 1800px) {
  .bodywebsite .text-xxl-left {
	text-align: left !important;
  }
  .bodywebsite .text-xxl-right {
	text-align: right !important;
  }
  .bodywebsite .text-xxl-center {
	text-align: center !important;
  }
}
.bodywebsite .text-lowercase {
  text-transform: lowercase !important;
}
.bodywebsite .text-uppercase {
  text-transform: uppercase !important;
}
.bodywebsite .text-capitalize {
  text-transform: capitalize !important;
}
.bodywebsite .font-weight-light {
  font-weight: 300 !important;
}
.bodywebsite .font-weight-normal {
  font-weight: 400 !important;
}
.bodywebsite .font-weight-bold {
  font-weight: 700 !important;
}
.bodywebsite .font-italic {
  font-style: italic !important;
}
.bodywebsite .text-white {
  color: #fff !important;
}
.bodywebsite .text-primary {
  color: #007bff !important;
}
.bodywebsite a.text-primary:focus,
.bodywebsite a.text-primary:hover {
  color: #0062cc !important;
}
.bodywebsite .text-secondary {
  color: #868e96 !important;
}
.bodywebsite a.text-secondary:focus,
.bodywebsite a.text-secondary:hover {
  color: #6c757d !important;
}
.bodywebsite .text-success {
  color: #28a745 !important;
}
.bodywebsite a.text-success:focus,
.bodywebsite a.text-success:hover {
  color: #1e7e34 !important;
}
.bodywebsite .text-info {
  color: #17a2b8 !important;
}
.bodywebsite a.text-info:focus,
.bodywebsite a.text-info:hover {
  color: #117a8b !important;
}
.bodywebsite .text-warning {
  color: #ffc107 !important;
}
.bodywebsite a.text-warning:focus,
.bodywebsite a.text-warning:hover {
  color: #d39e00 !important;
}
.bodywebsite .text-danger {
  color: #dc3545 !important;
}
.bodywebsite a.text-danger:focus,
.bodywebsite a.text-danger:hover {
  color: #bd2130 !important;
}
.bodywebsite .text-light {
  color: #f8f9fa !important;
}
.bodywebsite a.text-light:focus,
.bodywebsite a.text-light:hover {
  color: #dae0e5 !important;
}
.bodywebsite .text-dark {
  color: #343a40 !important;
}
.bodywebsite a.text-dark:focus,
.bodywebsite a.text-dark:hover {
  color: #1d2124 !important;
}
.bodywebsite .text-muted {
  color: #dedede !important;
}
.bodywebsite .text-hide {
  font: 0/0 a;
  color: transparent;
  text-shadow: none;
  background-color: transparent;
  border: 0;
}
.bodywebsite .visible {
  visibility: visible !important;
}
.bodywebsite .invisible {
  visibility: hidden !important;
}
.bodywebsite .animated {
  -webkit-animation-duration: 1s;
  animation-duration: 1s;
  -webkit-animation-fill-mode: both;
  animation-fill-mode: both;
  opacity: 1;
}
.bodywebsite .animated:not(.page) {
  will-change: transform;
}
.bodywebsite .animated.infinite {
  -webkit-animation-iteration-count: infinite;
  animation-iteration-count: infinite;
}
.bodywebsite .animated.hinge {
  -webkit-animation-duration: 2s;
  animation-duration: 2s;
}
html .bodywebsite:not(.lt-ie10) .not-animated {
  opacity: 0;
}
@-webkit-keyframes fadeInUp {
  0% {
	opacity: 0;
	-webkit-transform: translate3d(0, 100%, 0);
	transform: translate3d(0, 100%, 0);
  }
  100% {
	opacity: 1;
	-webkit-transform: none;
	transform: none;
  }
}
@keyframes fadeInUp {
  0% {
	opacity: 0;
	-webkit-transform: translate3d(0, 100%, 0);
	transform: translate3d(0, 100%, 0);
  }
  100% {
	opacity: 1;
	-webkit-transform: none;
	transform: none;
  }
}
.bodywebsite .fadeInUp {
  -webkit-animation-name: fadeInUp;
  animation-name: fadeInUp;
}
@-webkit-keyframes fadeInUpBig {
  0% {
	opacity: 0;
	-webkit-transform: translate3d(0, 2000px, 0);
	transform: translate3d(0, 2000px, 0);
  }
  100% {
	opacity: 1;
	-webkit-transform: none;
	transform: none;
  }
}
@keyframes fadeInUpBig {
  0% {
	opacity: 0;
	-webkit-transform: translate3d(0, 2000px, 0);
	transform: translate3d(0, 2000px, 0);
  }
  100% {
	opacity: 1;
	-webkit-transform: none;
	transform: none;
  }
}
.bodywebsite .fadeInUpBig {
  -webkit-animation-name: fadeInUpBig;
  animation-name: fadeInUpBig;
}
<?php // BEGIN PHP
$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "css");
// END PHP
