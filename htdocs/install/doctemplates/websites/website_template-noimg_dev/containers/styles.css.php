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
@charset "UTF-8";

.bodywebsite {
    margin: 0;
}

.bodywebsite :root {
	--bs-blue: #0d6efd;
	--bs-indigo: #6610f2;
	--bs-purple: #6f42c1;
	--bs-pink: #d63384;
	--bs-red: #dc3545;
	--bs-orange: #fd7e14;
	--bs-yellow: #ffc107;
	--bs-green: #198754;
	--bs-teal: #20c997;
	--bs-cyan: #0dcaf0;
	--bs-black: #000;
	--bs-white: #fff;
	--bs-gray: #6c757d;
	--bs-gray-dark: #343a40;
	--bs-gray-100: #f8f9fa;
	--bs-gray-200: #e9ecef;
	--bs-gray-300: #dee2e6;
	--bs-gray-400: #ced4da;
	--bs-gray-500: #adb5bd;
	--bs-gray-600: #6c757d;
	--bs-gray-700: #495057;
	--bs-gray-800: #343a40;
	--bs-gray-900: #212529;
	--bs-primary: #0d6efd;
	--bs-secondary: #6c757d;
	--bs-success: #198754;
	--bs-info: #0dcaf0;
	--bs-warning: #ffc107;
	--bs-danger: #dc3545;
	--bs-light: #f8f9fa;
	--bs-dark: #212529;
	--bs-primary-rgb: 13, 110, 253;
	--bs-secondary-rgb: 108, 117, 125;
	--bs-success-rgb: 25, 135, 84;
	--bs-info-rgb: 13, 202, 240;
	--bs-warning-rgb: 255, 193, 7;
	--bs-danger-rgb: 220, 53, 69;
	--bs-light-rgb: 248, 249, 250;
	--bs-dark-rgb: 33, 37, 41;
	--bs-white-rgb: 255, 255, 255;
	--bs-black-rgb: 0, 0, 0;
	--bs-body-color-rgb: 33, 37, 41;
	--bs-body-bg-rgb: 255, 255, 255;
	--bs-font-sans-serif: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", "Noto Sans", "Liberation Sans", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
	--bs-font-monospace: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
	--bs-gradient: linear-gradient(180deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0));
	--bs-body-font-family: var(--bs-font-sans-serif);
	--bs-body-font-size: 1rem;
	--bs-body-font-weight: 400;
	--bs-body-line-height: 1.5;
	--bs-body-color: #212529;
	--bs-body-bg: #fff;
	--bs-border-width: 1px;
	--bs-border-style: solid;
	--bs-border-color: #dee2e6;
	--bs-border-color-translucent: rgba(0, 0, 0, 0.175);
	--bs-border-radius: 0.375rem;
	--bs-border-radius-sm: 0.25rem;
	--bs-border-radius-lg: 0.5rem;
	--bs-border-radius-xl: 1rem;
	--bs-border-radius-2xl: 2rem;
	--bs-border-radius-pill: 50rem;
	--bs-link-color: #0d6efd;
	--bs-link-hover-color: #0a58ca;
	--bs-code-color: #d63384;
	--bs-highlight-bg: #fff3cd;
}

.bodywebsite *,
  .bodywebsite *::before,
  .bodywebsite *::after {
	box-sizing: border-box;
}

@media (prefers-reduced-motion: no-preference) {
	.bodywebsite :root {
		scroll-behavior: smooth;
	}
}

.bodywebsite body {
	margin: 0;
	font-family: var(--bs-body-font-family);
	font-size: var(--bs-body-font-size);
	font-weight: var(--bs-body-font-weight);
	line-height: var(--bs-body-line-height);
	color: var(--bs-body-color);
	text-align: var(--bs-body-text-align);
	background-color: var(--bs-body-bg);
	-webkit-text-size-adjust: 100%;
	-webkit-tap-highlight-color: transparent;
}

.bodywebsite hr {
	margin: 1rem 0;
	color: inherit;
	border: 0;
	border-top: 1px solid;
	opacity: 0.25;
}

.bodywebsite h6, .bodywebsite .h6, .bodywebsite h5, .bodywebsite .h5, .bodywebsite h4, .bodywebsite .h4, .bodywebsite h3, .bodywebsite .h3, .bodywebsite h2, .bodywebsite .h2, .bodywebsite h1, .bodywebsite .h1 {
	margin-top: 0;
	margin-bottom: 0.5rem;
	font-weight: 500;
	line-height: 1.2;
}

.bodywebsite .text-dark{
	color: black;
}

.bodywebsite h1, .bodywebsite .h1 {
	font-size: calc(1.375rem + 1.5vw);
}

@media (min-width: 1200px) {
	.bodywebsite h1, .bodywebsite .h1 {
		font-size: 2.5rem;
	}
}

.bodywebsite h2, .bodywebsite .h2 {
	font-size: calc(1.325rem + 0.9vw);
}

@media (min-width: 1200px) {
	.bodywebsite h2, .bodywebsite .h2 {
		font-size: 2rem;
	}
}

.bodywebsite h3, .bodywebsite .h3 {
	font-size: calc(1.3rem + 0.6vw);
}

@media (min-width: 1200px) {
	.bodywebsite h3, .bodywebsite .h3 {
		font-size: 1.75rem;
	}
}

.bodywebsite h4, .bodywebsite .h4 {
	font-size: calc(1.275rem + 0.3vw);
}

@media (min-width: 1200px) {
	.bodywebsite h4, .bodywebsite .h4 {
		font-size: 1.5rem;
	}
}

.bodywebsite h5, .bodywebsite .h5 {
	font-size: 1.25rem;
}

.bodywebsite h6, .bodywebsite .h6 {
	font-size: 1rem;
}

.bodywebsite p {
	margin-top: 0;
	margin-bottom: 1rem;
}

.bodywebsite abbr[title] {
	-webkit-text-decoration: underline dotted;
	text-decoration: underline dotted;
	cursor: help;
	-webkit-text-decoration-skip-ink: none;
	text-decoration-skip-ink: none;
}

.bodywebsite address {
	margin-bottom: 1rem;
	font-style: normal;
	line-height: inherit;
}

.bodywebsite ol,
  .bodywebsite ul {
	padding-left: 2rem;
}

.bodywebsite ol,
  .bodywebsite ul,
  .bodywebsite dl {
	margin-top: 0;
	margin-bottom: 1rem;
}

.bodywebsite ol ol,
  .bodywebsite ul ul,
  .bodywebsite ol ul,
  .bodywebsite ul ol {
	margin-bottom: 0;
}

.bodywebsite dt {
	font-weight: 700;
}

.bodywebsite dd {
	margin-bottom: 0.5rem;
	margin-left: 0;
}

.bodywebsite blockquote {
	margin: 0 0 1rem;
}

.bodywebsite b,
  .bodywebsite strong {
	font-weight: bolder;
}

.bodywebsite small, .bodywebsite .small {
	font-size: 0.875em;
}

.bodywebsite mark, .bodywebsite .mark {
	padding: 0.1875em;
	background-color: var(--bs-highlight-bg);
}

.bodywebsite sub,
  .bodywebsite sup {
	position: relative;
	font-size: 0.75em;
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
	color: var(--bs-link-color);
	text-decoration: underline;
}

.bodywebsite a:hover {
	color: var(--bs-link-hover-color);
}

.bodywebsite a:not([href]):not([class]), .bodywebsite a:not([href]):not([class]):hover {
	color: inherit;
	text-decoration: none;
}

.bodywebsite pre,
  .bodywebsite code,
  .bodywebsite kbd,
  .bodywebsite samp {
	font-family: var(--bs-font-monospace);
	font-size: 1em;
}

.bodywebsite pre {
	display: block;
	margin-top: 0;
	margin-bottom: 1rem;
	overflow: auto;
	font-size: 0.875em;
}

.bodywebsite pre code {
	font-size: inherit;
	color: inherit;
	word-break: normal;
}

.bodywebsite code {
	font-size: 0.875em;
	color: var(--bs-code-color);
	word-wrap: break-word;
}

.bodywebsite a > code {
	color: inherit;
}

.bodywebsite kbd {
	padding: 0.1875rem 0.375rem;
	font-size: 0.875em;
	color: var(--bs-body-bg);
	background-color: var(--bs-body-color);
	border-radius: 0.25rem;
}

.bodywebsite kbd kbd {
	padding: 0;
	font-size: 1em;
}

.bodywebsite figure {
	margin: 0 0 1rem;
}

.bodywebsite img,
  .bodywebsite svg {
	vertical-align: middle;
}

.bodywebsite table {
	caption-side: bottom;
	border-collapse: collapse;
}

.bodywebsite caption {
	padding-top: 0.5rem;
	padding-bottom: 0.5rem;
	color: #6c757d;
	text-align: left;
}

.bodywebsite th {
	text-align: inherit;
	text-align: -webkit-match-parent;
}

.bodywebsite thead,
  .bodywebsite tbody,
  .bodywebsite tfoot,
  .bodywebsite tr,
  .bodywebsite td,
  .bodywebsite th {
	border-color: inherit;
	border-style: solid;
	border-width: 0;
}

.bodywebsite label {
	display: inline-block;
}

.bodywebsite button {
	border-radius: 0;
}

.bodywebsite button:focus:not(:focus-visible) {
	outline: 0;
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
  .bodywebsite select {
	text-transform: none;
}

.bodywebsite [role=button] {
	cursor: pointer;
}

.bodywebsite select {
	word-wrap: normal;
}

.bodywebsite select:disabled {
	opacity: 1;
}

.bodywebsite [list]:not([type=date]):not([type=datetime-local]):not([type=month]):not([type=week]):not([type=time])::-webkit-calendar-picker-indicator {
	display: none !important;
}

.bodywebsite button,
  .bodywebsite [type=button],
  .bodywebsite [type=reset],
  .bodywebsite [type=submit] {
	-webkit-appearance: button;
}

.bodywebsite button:not(:disabled),
  .bodywebsite [type=button]:not(:disabled),
  .bodywebsite [type=reset]:not(:disabled),
  .bodywebsite [type=submit]:not(:disabled) {
	cursor: pointer;
}

.bodywebsite ::-moz-focus-inner {
	padding: 0;
	border-style: none;
}

.bodywebsite textarea {
	resize: vertical;
}

.bodywebsite fieldset {
	min-width: 0;
	padding: 0;
	margin: 0;
	border: 0;
}

.bodywebsite legend {
	float: left;
	width: 100%;
	padding: 0;
	margin-bottom: 0.5rem;
	font-size: calc(1.275rem + 0.3vw);
	line-height: inherit;
}

@media (min-width: 1200px) {
	.bodywebsite legend {
		font-size: 1.5rem;
	}
}

.bodywebsite legend + * {
	clear: left;
}

.bodywebsite ::-webkit-datetime-edit-fields-wrapper,
  .bodywebsite ::-webkit-datetime-edit-text,
  .bodywebsite ::-webkit-datetime-edit-minute,
  .bodywebsite ::-webkit-datetime-edit-hour-field,
  .bodywebsite ::-webkit-datetime-edit-day-field,
  .bodywebsite ::-webkit-datetime-edit-month-field,
  .bodywebsite ::-webkit-datetime-edit-year-field {
	padding: 0;
}

.bodywebsite ::-webkit-inner-spin-button {
	height: auto;
}

.bodywebsite [type=search] {
	outline-offset: -2px;
	-webkit-appearance: textfield;
}

.bodywebsite ::-webkit-search-decoration {
	-webkit-appearance: none;
}

.bodywebsite ::-webkit-color-swatch-wrapper {
	padding: 0;
}

.bodywebsite ::-webkit-file-upload-button {
	font: inherit;
	-webkit-appearance: button;
}

.bodywebsite ::file-selector-button {
	font: inherit;
	-webkit-appearance: button;
}

.bodywebsite output {
	display: inline-block;
}

.bodywebsite iframe {
	border: 0;
}

.bodywebsite summary {
	display: list-item;
	cursor: pointer;
}

.bodywebsite progress {
	vertical-align: baseline;
}

.bodywebsite [hidden] {
	display: none !important;
}

.bodywebsite .lead {
	font-size: 1.25rem;
	font-weight: 300;
}

.bodywebsite .display-1 {
	font-size: calc(1.625rem + 4.5vw);
	font-weight: 300;
	line-height: 1.2;
}

@media (min-width: 1200px) {
	.bodywebsite .display-1 {
		font-size: 5rem;
	}
}

.bodywebsite .display-2 {
	font-size: calc(1.575rem + 3.9vw);
	font-weight: 300;
	line-height: 1.2;
}

@media (min-width: 1200px) {
	.bodywebsite .display-2 {
		font-size: 4.5rem;
	}
}

.bodywebsite .display-3 {
	font-size: calc(1.525rem + 3.3vw);
	font-weight: 300;
	line-height: 1.2;
}

@media (min-width: 1200px) {
	.bodywebsite .display-3 {
		font-size: 4rem;
	}
}

.bodywebsite .display-4 {
	font-size: calc(1.475rem + 2.7vw);
	font-weight: 300;
	line-height: 1.2;
}

@media (min-width: 1200px) {
	.bodywebsite .display-4 {
		font-size: 3.5rem;
	}
}

.bodywebsite .display-5 {
	font-size: calc(1.425rem + 2.1vw);
	font-weight: 300;
	line-height: 1.2;
}

@media (min-width: 1200px) {
	.bodywebsite .display-5 {
		font-size: 3rem;
	}
}

.bodywebsite .display-6 {
	font-size: calc(1.375rem + 1.5vw);
	font-weight: 300;
	line-height: 1.2;
}

@media (min-width: 1200px) {
	.bodywebsite .display-6 {
		font-size: 2.5rem;
	}
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
	margin-right: 0.5rem;
}

.bodywebsite .initialism {
	font-size: 0.875em;
	text-transform: uppercase;
}

.bodywebsite .blockquote {
	margin-bottom: 1rem;
	font-size: 1.25rem;
}

.bodywebsite .blockquote > :last-child {
	margin-bottom: 0;
}

.bodywebsite .blockquote-footer {
	margin-top: -1rem;
	margin-bottom: 1rem;
	font-size: 0.875em;
	color: #6c757d;
}

.bodywebsite .blockquote-footer::before {
	content: "— ";
}

.bodywebsite .img-fluid {
	max-width: 100%;
	height: auto;
}

.bodywebsite .img-thumbnail {
	padding: 0.25rem;
	background-color: #fff;
	border: 1px solid var(--bs-border-color);
	border-radius: 0.375rem;
	max-width: 100%;
	height: auto;
}

.bodywebsite .figure {
	display: inline-block;
}

.bodywebsite .figure-img {
	margin-bottom: 0.5rem;
	line-height: 1;
}

.bodywebsite .figure-caption {
	font-size: 0.875em;
	color: #6c757d;
}

.bodywebsite .container,
  .bodywebsite .container-fluid,
  .bodywebsite .container-xxl,
  .bodywebsite .container-xl,
  .bodywebsite .container-lg,
  .bodywebsite .container-md,
  .bodywebsite .container-sm {
	--bs-gutter-x: 1.5rem;
	--bs-gutter-y: 0;
	width: 100%;
	padding-right: calc(var(--bs-gutter-x) * 0.5);
	padding-left: calc(var(--bs-gutter-x) * 0.5);
	margin-right: auto;
	margin-left: auto;
}

@media (min-width: 576px) {
	.bodywebsite .container-sm, .bodywebsite .container {
		max-width: 540px;
	}
}

@media (min-width: 768px) {
	.bodywebsite .container-md, .bodywebsite .container-sm, .bodywebsite .container {
		max-width: 720px;
	}
}

@media (min-width: 992px) {
	.bodywebsite .container-lg, .bodywebsite .container-md, .bodywebsite .container-sm, .bodywebsite .container {
		max-width: 960px;
	}
}

@media (min-width: 1200px) {
	.bodywebsite .container-xl, .bodywebsite .container-lg, .bodywebsite .container-md, .bodywebsite .container-sm, .bodywebsite .container {
		max-width: 1140px;
	}
}

@media (min-width: 1400px) {
	.bodywebsite .container-xxl, .bodywebsite .container-xl, .bodywebsite .container-lg, .bodywebsite .container-md, .bodywebsite .container-sm, .bodywebsite .container {
		max-width: 1320px;
	}
}

.bodywebsite .row {
	--bs-gutter-x: 1.5rem;
	--bs-gutter-y: 0;
	display: flex;
	flex-wrap: wrap;
	margin-top: calc(-1 * var(--bs-gutter-y));
	margin-right: calc(-0.5 * var(--bs-gutter-x));
	margin-left: calc(-0.5 * var(--bs-gutter-x));
}

.bodywebsite .row > * {
	flex-shrink: 0;
	width: 100%;
	max-width: 100%;
	padding-right: calc(var(--bs-gutter-x) * 0.5);
	padding-left: calc(var(--bs-gutter-x) * 0.5);
	margin-top: var(--bs-gutter-y);
}

.bodywebsite .col {
	flex: 1 0 0%;
}

.bodywebsite .row-cols-auto > * {
	flex: 0 0 auto;
	width: auto;
}

.bodywebsite .row-cols-1 > * {
	flex: 0 0 auto;
	width: 100%;
}

.bodywebsite .row-cols-2 > * {
	flex: 0 0 auto;
	width: 50%;
}

.bodywebsite .row-cols-3 > * {
	flex: 0 0 auto;
	width: 33.3333333333%;
}

.bodywebsite .row-cols-4 > * {
	flex: 0 0 auto;
	width: 25%;
}

.bodywebsite .row-cols-5 > * {
	flex: 0 0 auto;
	width: 20%;
}

.bodywebsite .row-cols-6 > * {
	flex: 0 0 auto;
	width: 16.6666666667%;
}

.bodywebsite .col-auto {
	flex: 0 0 auto;
	width: auto;
}

.bodywebsite .col-1 {
	flex: 0 0 auto;
	width: 8.33333333%;
}

.bodywebsite .col-2 {
	flex: 0 0 auto;
	width: 16.66666667%;
}

.bodywebsite .col-3 {
	flex: 0 0 auto;
	width: 25%;
}

.bodywebsite .col-4 {
	flex: 0 0 auto;
	width: 33.33333333%;
}

.bodywebsite .col-5 {
	flex: 0 0 auto;
	width: 41.66666667%;
}

.bodywebsite .col-6 {
	flex: 0 0 auto;
	width: 50%;
}

.bodywebsite .col-7 {
	flex: 0 0 auto;
	width: 58.33333333%;
}

.bodywebsite .col-8 {
	flex: 0 0 auto;
	width: 66.66666667%;
}

.bodywebsite .col-9 {
	flex: 0 0 auto;
	width: 75%;
}

.bodywebsite .col-10 {
	flex: 0 0 auto;
	width: 83.33333333%;
}

.bodywebsite .col-11 {
	flex: 0 0 auto;
	width: 91.66666667%;
}

.bodywebsite .col-12 {
	flex: 0 0 auto;
	width: 100%;
}

.bodywebsite .offset-1 {
	margin-left: 8.33333333%;
}

.bodywebsite .offset-2 {
	margin-left: 16.66666667%;
}

.bodywebsite .offset-3 {
	margin-left: 25%;
}

.bodywebsite .offset-4 {
	margin-left: 33.33333333%;
}

.bodywebsite .offset-5 {
	margin-left: 41.66666667%;
}

.bodywebsite .offset-6 {
	margin-left: 50%;
}

.bodywebsite .offset-7 {
	margin-left: 58.33333333%;
}

.bodywebsite .offset-8 {
	margin-left: 66.66666667%;
}

.bodywebsite .offset-9 {
	margin-left: 75%;
}

.bodywebsite .offset-10 {
	margin-left: 83.33333333%;
}

.bodywebsite .offset-11 {
	margin-left: 91.66666667%;
}

.bodywebsite .g-0,
  .bodywebsite .gx-0 {
	--bs-gutter-x: 0;
}

.bodywebsite .g-0,
  .bodywebsite .gy-0 {
	--bs-gutter-y: 0;
}

.bodywebsite .g-1,
  .bodywebsite .gx-1 {
	--bs-gutter-x: 0.25rem;
}

.bodywebsite .g-1,
  .bodywebsite .gy-1 {
	--bs-gutter-y: 0.25rem;
}

.bodywebsite .g-2,
  .bodywebsite .gx-2 {
	--bs-gutter-x: 0.5rem;
}

.bodywebsite .g-2,
  .bodywebsite .gy-2 {
	--bs-gutter-y: 0.5rem;
}

.bodywebsite .g-3,
  .bodywebsite .gx-3 {
	--bs-gutter-x: 1rem;
}

.bodywebsite .g-3,
  .bodywebsite .gy-3 {
	--bs-gutter-y: 1rem;
}

.bodywebsite .g-4,
  .bodywebsite .gx-4 {
	--bs-gutter-x: 1.5rem;
}

.bodywebsite .g-4,
  .bodywebsite .gy-4 {
	--bs-gutter-y: 1.5rem;
}

.bodywebsite .g-5,
  .bodywebsite .gx-5 {
	--bs-gutter-x: 3rem;
}

.bodywebsite .g-5,
  .bodywebsite .gy-5 {
	--bs-gutter-y: 3rem;
}

@media (min-width: 576px) {
	.bodywebsite .col-sm {
		flex: 1 0 0%;
	}

	.bodywebsite .row-cols-sm-auto > * {
		flex: 0 0 auto;
		width: auto;
	}

	.bodywebsite .row-cols-sm-1 > * {
		flex: 0 0 auto;
		width: 100%;
	}

	.bodywebsite .row-cols-sm-2 > * {
		flex: 0 0 auto;
		width: 50%;
	}

	.bodywebsite .row-cols-sm-3 > * {
		flex: 0 0 auto;
		width: 33.3333333333%;
	}

	.bodywebsite .row-cols-sm-4 > * {
		flex: 0 0 auto;
		width: 25%;
	}

	.bodywebsite .row-cols-sm-5 > * {
		flex: 0 0 auto;
		width: 20%;
	}

	.bodywebsite .row-cols-sm-6 > * {
		flex: 0 0 auto;
		width: 16.6666666667%;
	}

	.bodywebsite .col-sm-auto {
		flex: 0 0 auto;
		width: auto;
	}

	.bodywebsite .col-sm-1 {
		flex: 0 0 auto;
		width: 8.33333333%;
	}

	.bodywebsite .col-sm-2 {
		flex: 0 0 auto;
		width: 16.66666667%;
	}

	.bodywebsite .col-sm-3 {
		flex: 0 0 auto;
		width: 25%;
	}

	.bodywebsite .col-sm-4 {
		flex: 0 0 auto;
		width: 33.33333333%;
	}

	.bodywebsite .col-sm-5 {
		flex: 0 0 auto;
		width: 41.66666667%;
	}

	.bodywebsite .col-sm-6 {
		flex: 0 0 auto;
		width: 50%;
	}

	.bodywebsite .col-sm-7 {
		flex: 0 0 auto;
		width: 58.33333333%;
	}

	.bodywebsite .col-sm-8 {
		flex: 0 0 auto;
		width: 66.66666667%;
	}

	.bodywebsite .col-sm-9 {
		flex: 0 0 auto;
		width: 75%;
	}

	.bodywebsite .col-sm-10 {
		flex: 0 0 auto;
		width: 83.33333333%;
	}

	.bodywebsite .col-sm-11 {
		flex: 0 0 auto;
		width: 91.66666667%;
	}

	.bodywebsite .col-sm-12 {
		flex: 0 0 auto;
		width: 100%;
	}

	.bodywebsite .offset-sm-0 {
		margin-left: 0;
	}

	.bodywebsite .offset-sm-1 {
		margin-left: 8.33333333%;
	}

	.bodywebsite .offset-sm-2 {
		margin-left: 16.66666667%;
	}

	.bodywebsite .offset-sm-3 {
		margin-left: 25%;
	}

	.bodywebsite .offset-sm-4 {
		margin-left: 33.33333333%;
	}

	.bodywebsite .offset-sm-5 {
		margin-left: 41.66666667%;
	}

	.bodywebsite .offset-sm-6 {
		margin-left: 50%;
	}

	.bodywebsite .offset-sm-7 {
		margin-left: 58.33333333%;
	}

	.bodywebsite .offset-sm-8 {
		margin-left: 66.66666667%;
	}

	.bodywebsite .offset-sm-9 {
		margin-left: 75%;
	}

	.bodywebsite .offset-sm-10 {
		margin-left: 83.33333333%;
	}

	.bodywebsite .offset-sm-11 {
		margin-left: 91.66666667%;
	}

	.bodywebsite .g-sm-0,
	.bodywebsite .gx-sm-0 {
		--bs-gutter-x: 0;
	}

	.bodywebsite .g-sm-0,
	.bodywebsite .gy-sm-0 {
		--bs-gutter-y: 0;
	}

	.bodywebsite .g-sm-1,
	.bodywebsite .gx-sm-1 {
		--bs-gutter-x: 0.25rem;
	}

	.bodywebsite .g-sm-1,
	.bodywebsite .gy-sm-1 {
		--bs-gutter-y: 0.25rem;
	}

	.bodywebsite .g-sm-2,
	.bodywebsite .gx-sm-2 {
		--bs-gutter-x: 0.5rem;
	}

	.bodywebsite .g-sm-2,
	.bodywebsite .gy-sm-2 {
		--bs-gutter-y: 0.5rem;
	}

	.bodywebsite .g-sm-3,
	.bodywebsite .gx-sm-3 {
		--bs-gutter-x: 1rem;
	}

	.bodywebsite .g-sm-3,
	.bodywebsite .gy-sm-3 {
		--bs-gutter-y: 1rem;
	}

	.bodywebsite .g-sm-4,
	.bodywebsite .gx-sm-4 {
		--bs-gutter-x: 1.5rem;
	}

	.bodywebsite .g-sm-4,
	.bodywebsite .gy-sm-4 {
		--bs-gutter-y: 1.5rem;
	}

	.bodywebsite .g-sm-5,
	.bodywebsite .gx-sm-5 {
		--bs-gutter-x: 3rem;
	}

	.bodywebsite .g-sm-5,
	.bodywebsite .gy-sm-5 {
		--bs-gutter-y: 3rem;
	}
}

@media (min-width: 768px) {
	.bodywebsite .col-md {
		flex: 1 0 0%;
	}

	.bodywebsite .row-cols-md-auto > * {
		flex: 0 0 auto;
		width: auto;
	}

	.bodywebsite .row-cols-md-1 > * {
		flex: 0 0 auto;
		width: 100%;
	}

	.bodywebsite .row-cols-md-2 > * {
		flex: 0 0 auto;
		width: 50%;
	}

	.bodywebsite .row-cols-md-3 > * {
		flex: 0 0 auto;
		width: 33.3333333333%;
	}

	.bodywebsite .row-cols-md-4 > * {
		flex: 0 0 auto;
		width: 25%;
	}

	.bodywebsite .row-cols-md-5 > * {
		flex: 0 0 auto;
		width: 20%;
	}

	.bodywebsite .row-cols-md-6 > * {
		flex: 0 0 auto;
		width: 16.6666666667%;
	}

	.bodywebsite .col-md-auto {
		flex: 0 0 auto;
		width: auto;
	}

	.bodywebsite .col-md-1 {
		flex: 0 0 auto;
		width: 8.33333333%;
	}

	.bodywebsite .col-md-2 {
		flex: 0 0 auto;
		width: 16.66666667%;
	}

	.bodywebsite .col-md-3 {
		flex: 0 0 auto;
		width: 25%;
	}

	.bodywebsite .col-md-4 {
		flex: 0 0 auto;
		width: 33.33333333%;
	}

	.bodywebsite .col-md-5 {
		flex: 0 0 auto;
		width: 41.66666667%;
	}

	.bodywebsite .col-md-6 {
		flex: 0 0 auto;
		width: 50%;
	}

	.bodywebsite .col-md-7 {
		flex: 0 0 auto;
		width: 58.33333333%;
	}

	.bodywebsite .col-md-8 {
		flex: 0 0 auto;
		width: 66.66666667%;
	}

	.bodywebsite .col-md-9 {
		flex: 0 0 auto;
		width: 75%;
	}

	.bodywebsite .col-md-10 {
		flex: 0 0 auto;
		width: 83.33333333%;
	}

	.bodywebsite .col-md-11 {
		flex: 0 0 auto;
		width: 91.66666667%;
	}

	.bodywebsite .col-md-12 {
		flex: 0 0 auto;
		width: 100%;
	}

	.bodywebsite .offset-md-0 {
		margin-left: 0;
	}

	.bodywebsite .offset-md-1 {
		margin-left: 8.33333333%;
	}

	.bodywebsite .offset-md-2 {
		margin-left: 16.66666667%;
	}

	.bodywebsite .offset-md-3 {
		margin-left: 25%;
	}

	.bodywebsite .offset-md-4 {
		margin-left: 33.33333333%;
	}

	.bodywebsite .offset-md-5 {
		margin-left: 41.66666667%;
	}

	.bodywebsite .offset-md-6 {
		margin-left: 50%;
	}

	.bodywebsite .offset-md-7 {
		margin-left: 58.33333333%;
	}

	.bodywebsite .offset-md-8 {
		margin-left: 66.66666667%;
	}

	.bodywebsite .offset-md-9 {
		margin-left: 75%;
	}

	.bodywebsite .offset-md-10 {
		margin-left: 83.33333333%;
	}

	.bodywebsite .offset-md-11 {
		margin-left: 91.66666667%;
	}

	.bodywebsite .g-md-0,
	.bodywebsite .gx-md-0 {
		--bs-gutter-x: 0;
	}

	.bodywebsite .g-md-0,
	.bodywebsite .gy-md-0 {
		--bs-gutter-y: 0;
	}

	.bodywebsite .g-md-1,
	.bodywebsite .gx-md-1 {
		--bs-gutter-x: 0.25rem;
	}

	.bodywebsite .g-md-1,
	.bodywebsite .gy-md-1 {
		--bs-gutter-y: 0.25rem;
	}

	.bodywebsite .g-md-2,
	.bodywebsite .gx-md-2 {
		--bs-gutter-x: 0.5rem;
	}

	.bodywebsite .g-md-2,
	.bodywebsite .gy-md-2 {
		--bs-gutter-y: 0.5rem;
	}

	.bodywebsite .g-md-3,
	.bodywebsite .gx-md-3 {
		--bs-gutter-x: 1rem;
	}

	.bodywebsite .g-md-3,
	.bodywebsite .gy-md-3 {
		--bs-gutter-y: 1rem;
	}

	.bodywebsite .g-md-4,
	.bodywebsite .gx-md-4 {
		--bs-gutter-x: 1.5rem;
	}

	.bodywebsite .g-md-4,
	.bodywebsite .gy-md-4 {
		--bs-gutter-y: 1.5rem;
	}

	.bodywebsite .g-md-5,
	.bodywebsite .gx-md-5 {
		--bs-gutter-x: 3rem;
	}

	.bodywebsite .g-md-5,
	.bodywebsite .gy-md-5 {
		--bs-gutter-y: 3rem;
	}
}

@media (min-width: 992px) {
	.bodywebsite .col-lg {
		flex: 1 0 0%;
	}

	.bodywebsite .row-cols-lg-auto > * {
		flex: 0 0 auto;
		width: auto;
	}

	.bodywebsite .row-cols-lg-1 > * {
		flex: 0 0 auto;
		width: 100%;
	}

	.bodywebsite .row-cols-lg-2 > * {
		flex: 0 0 auto;
		width: 50%;
	}

	.bodywebsite .row-cols-lg-3 > * {
		flex: 0 0 auto;
		width: 33.3333333333%;
	}

	.bodywebsite .row-cols-lg-4 > * {
		flex: 0 0 auto;
		width: 25%;
	}

	.bodywebsite .row-cols-lg-5 > * {
		flex: 0 0 auto;
		width: 20%;
	}

	.bodywebsite .row-cols-lg-6 > * {
		flex: 0 0 auto;
		width: 16.6666666667%;
	}

	.bodywebsite .col-lg-auto {
		flex: 0 0 auto;
		width: auto;
	}

	.bodywebsite .col-lg-1 {
		flex: 0 0 auto;
		width: 8.33333333%;
	}

	.bodywebsite .col-lg-2 {
		flex: 0 0 auto;
		width: 16.66666667%;
	}

	.bodywebsite .col-lg-3 {
		flex: 0 0 auto;
		width: 25%;
	}

	.bodywebsite .col-lg-4 {
		flex: 0 0 auto;
		width: 33.33333333%;
	}

	.bodywebsite .col-lg-5 {
		flex: 0 0 auto;
		width: 41.66666667%;
	}

	.bodywebsite .col-lg-6 {
		flex: 0 0 auto;
		width: 50%;
	}

	.bodywebsite .col-lg-7 {
		flex: 0 0 auto;
		width: 58.33333333%;
	}

	.bodywebsite .col-lg-8 {
		flex: 0 0 auto;
		width: 66.66666667%;
	}

	.bodywebsite .col-lg-9 {
		flex: 0 0 auto;
		width: 75%;
	}

	.bodywebsite .col-lg-10 {
		flex: 0 0 auto;
		width: 83.33333333%;
	}

	.bodywebsite .col-lg-11 {
		flex: 0 0 auto;
		width: 91.66666667%;
	}

	.bodywebsite .col-lg-12 {
		flex: 0 0 auto;
		width: 100%;
	}

	.bodywebsite .offset-lg-0 {
		margin-left: 0;
	}

	.bodywebsite .offset-lg-1 {
		margin-left: 8.33333333%;
	}

	.bodywebsite .offset-lg-2 {
		margin-left: 16.66666667%;
	}

	.bodywebsite .offset-lg-3 {
		margin-left: 25%;
	}

	.bodywebsite .offset-lg-4 {
		margin-left: 33.33333333%;
	}

	.bodywebsite .offset-lg-5 {
		margin-left: 41.66666667%;
	}

	.bodywebsite .offset-lg-6 {
		margin-left: 50%;
	}

	.bodywebsite .offset-lg-7 {
		margin-left: 58.33333333%;
	}

	.bodywebsite .offset-lg-8 {
		margin-left: 66.66666667%;
	}

	.bodywebsite .offset-lg-9 {
		margin-left: 75%;
	}

	.bodywebsite .offset-lg-10 {
		margin-left: 83.33333333%;
	}

	.bodywebsite .offset-lg-11 {
		margin-left: 91.66666667%;
	}

	.bodywebsite .g-lg-0,
	.bodywebsite .gx-lg-0 {
		--bs-gutter-x: 0;
	}

	.bodywebsite .g-lg-0,
	.bodywebsite .gy-lg-0 {
		--bs-gutter-y: 0;
	}

	.bodywebsite .g-lg-1,
	.bodywebsite .gx-lg-1 {
		--bs-gutter-x: 0.25rem;
	}

	.bodywebsite .g-lg-1,
	.bodywebsite .gy-lg-1 {
		--bs-gutter-y: 0.25rem;
	}

	.bodywebsite .g-lg-2,
	.bodywebsite .gx-lg-2 {
		--bs-gutter-x: 0.5rem;
	}

	.bodywebsite .g-lg-2,
	.bodywebsite .gy-lg-2 {
		--bs-gutter-y: 0.5rem;
	}

	.bodywebsite .g-lg-3,
	.bodywebsite .gx-lg-3 {
		--bs-gutter-x: 1rem;
	}

	.bodywebsite .g-lg-3,
	.bodywebsite .gy-lg-3 {
		--bs-gutter-y: 1rem;
	}

	.bodywebsite .g-lg-4,
	.bodywebsite .gx-lg-4 {
		--bs-gutter-x: 1.5rem;
	}

	.bodywebsite .g-lg-4,
	.bodywebsite .gy-lg-4 {
		--bs-gutter-y: 1.5rem;
	}

	.bodywebsite .g-lg-5,
	.bodywebsite .gx-lg-5 {
		--bs-gutter-x: 3rem;
	}

	.bodywebsite .g-lg-5,
	.bodywebsite .gy-lg-5 {
		--bs-gutter-y: 3rem;
	}
}

@media (min-width: 1200px) {
	.bodywebsite .col-xl {
		flex: 1 0 0%;
	}

	.bodywebsite .row-cols-xl-auto > * {
		flex: 0 0 auto;
		width: auto;
	}

	.bodywebsite .row-cols-xl-1 > * {
		flex: 0 0 auto;
		width: 100%;
	}

	.bodywebsite .row-cols-xl-2 > * {
		flex: 0 0 auto;
		width: 50%;
	}

	.bodywebsite .row-cols-xl-3 > * {
		flex: 0 0 auto;
		width: 33.3333333333%;
	}

	.bodywebsite .row-cols-xl-4 > * {
		flex: 0 0 auto;
		width: 25%;
	}

	.bodywebsite .row-cols-xl-5 > * {
		flex: 0 0 auto;
		width: 20%;
	}

	.bodywebsite .row-cols-xl-6 > * {
		flex: 0 0 auto;
		width: 16.6666666667%;
	}

	.bodywebsite .col-xl-auto {
		flex: 0 0 auto;
		width: auto;
	}

	.bodywebsite .col-xl-1 {
		flex: 0 0 auto;
		width: 8.33333333%;
	}

	.bodywebsite .col-xl-2 {
		flex: 0 0 auto;
		width: 16.66666667%;
	}

	.bodywebsite .col-xl-3 {
		flex: 0 0 auto;
		width: 25%;
	}

	.bodywebsite .col-xl-4 {
		flex: 0 0 auto;
		width: 33.33333333%;
	}

	.bodywebsite .col-xl-5 {
		flex: 0 0 auto;
		width: 41.66666667%;
	}

	.bodywebsite .col-xl-6 {
		flex: 0 0 auto;
		width: 50%;
	}

	.bodywebsite .col-xl-7 {
		flex: 0 0 auto;
		width: 58.33333333%;
	}

	.bodywebsite .col-xl-8 {
		flex: 0 0 auto;
		width: 66.66666667%;
	}

	.bodywebsite .col-xl-9 {
		flex: 0 0 auto;
		width: 75%;
	}

	.bodywebsite .col-xl-10 {
		flex: 0 0 auto;
		width: 83.33333333%;
	}

	.bodywebsite .col-xl-11 {
		flex: 0 0 auto;
		width: 91.66666667%;
	}

	.bodywebsite .col-xl-12 {
		flex: 0 0 auto;
		width: 100%;
	}

	.bodywebsite .offset-xl-0 {
		margin-left: 0;
	}

	.bodywebsite .offset-xl-1 {
		margin-left: 8.33333333%;
	}

	.bodywebsite .offset-xl-2 {
		margin-left: 16.66666667%;
	}

	.bodywebsite .offset-xl-3 {
		margin-left: 25%;
	}

	.bodywebsite .offset-xl-4 {
		margin-left: 33.33333333%;
	}

	.bodywebsite .offset-xl-5 {
		margin-left: 41.66666667%;
	}

	.bodywebsite .offset-xl-6 {
		margin-left: 50%;
	}

	.bodywebsite .offset-xl-7 {
		margin-left: 58.33333333%;
	}

	.bodywebsite .offset-xl-8 {
		margin-left: 66.66666667%;
	}

	.bodywebsite .offset-xl-9 {
		margin-left: 75%;
	}

	.bodywebsite .offset-xl-10 {
		margin-left: 83.33333333%;
	}

	.bodywebsite .offset-xl-11 {
		margin-left: 91.66666667%;
	}

	.bodywebsite .g-xl-0,
	.bodywebsite .gx-xl-0 {
		--bs-gutter-x: 0;
	}

	.bodywebsite .g-xl-0,
	.bodywebsite .gy-xl-0 {
		--bs-gutter-y: 0;
	}

	.bodywebsite .g-xl-1,
	.bodywebsite .gx-xl-1 {
		--bs-gutter-x: 0.25rem;
	}

	.bodywebsite .g-xl-1,
	.bodywebsite .gy-xl-1 {
		--bs-gutter-y: 0.25rem;
	}

	.bodywebsite .g-xl-2,
	.bodywebsite .gx-xl-2 {
		--bs-gutter-x: 0.5rem;
	}

	.bodywebsite .g-xl-2,
	.bodywebsite .gy-xl-2 {
		--bs-gutter-y: 0.5rem;
	}

	.bodywebsite .g-xl-3,
	.bodywebsite .gx-xl-3 {
		--bs-gutter-x: 1rem;
	}

	.bodywebsite .g-xl-3,
	.bodywebsite .gy-xl-3 {
		--bs-gutter-y: 1rem;
	}

	.bodywebsite .g-xl-4,
	.bodywebsite .gx-xl-4 {
		--bs-gutter-x: 1.5rem;
	}

	.bodywebsite .g-xl-4,
	.bodywebsite .gy-xl-4 {
		--bs-gutter-y: 1.5rem;
	}

	.bodywebsite .g-xl-5,
	.bodywebsite .gx-xl-5 {
		--bs-gutter-x: 3rem;
	}

	.bodywebsite .g-xl-5,
	.bodywebsite .gy-xl-5 {
		--bs-gutter-y: 3rem;
	}
}

@media (min-width: 1400px) {
	.bodywebsite .col-xxl {
		flex: 1 0 0%;
	}

	.bodywebsite .row-cols-xxl-auto > * {
		flex: 0 0 auto;
		width: auto;
	}

	.bodywebsite .row-cols-xxl-1 > * {
		flex: 0 0 auto;
		width: 100%;
	}

	.bodywebsite .row-cols-xxl-2 > * {
		flex: 0 0 auto;
		width: 50%;
	}

	.bodywebsite .row-cols-xxl-3 > * {
		flex: 0 0 auto;
		width: 33.3333333333%;
	}

	.bodywebsite .row-cols-xxl-4 > * {
		flex: 0 0 auto;
		width: 25%;
	}

	.bodywebsite .row-cols-xxl-5 > * {
		flex: 0 0 auto;
		width: 20%;
	}

	.bodywebsite .row-cols-xxl-6 > * {
		flex: 0 0 auto;
		width: 16.6666666667%;
	}

	.bodywebsite .col-xxl-auto {
		flex: 0 0 auto;
		width: auto;
	}

	.bodywebsite .col-xxl-1 {
		flex: 0 0 auto;
		width: 8.33333333%;
	}

	.bodywebsite .col-xxl-2 {
		flex: 0 0 auto;
		width: 16.66666667%;
	}

	.bodywebsite .col-xxl-3 {
		flex: 0 0 auto;
		width: 25%;
	}

	.bodywebsite .col-xxl-4 {
		flex: 0 0 auto;
		width: 33.33333333%;
	}

	.bodywebsite .col-xxl-5 {
		flex: 0 0 auto;
		width: 41.66666667%;
	}

	.bodywebsite .col-xxl-6 {
		flex: 0 0 auto;
		width: 50%;
	}

	.bodywebsite .col-xxl-7 {
		flex: 0 0 auto;
		width: 58.33333333%;
	}

	.bodywebsite .col-xxl-8 {
		flex: 0 0 auto;
		width: 66.66666667%;
	}

	.bodywebsite .col-xxl-9 {
		flex: 0 0 auto;
		width: 75%;
	}

	.bodywebsite .col-xxl-10 {
		flex: 0 0 auto;
		width: 83.33333333%;
	}

	.bodywebsite .col-xxl-11 {
		flex: 0 0 auto;
		width: 91.66666667%;
	}

	.bodywebsite .col-xxl-12 {
		flex: 0 0 auto;
		width: 100%;
	}

	.bodywebsite .offset-xxl-0 {
		margin-left: 0;
	}

	.bodywebsite .offset-xxl-1 {
		margin-left: 8.33333333%;
	}

	.bodywebsite .offset-xxl-2 {
		margin-left: 16.66666667%;
	}

	.bodywebsite .offset-xxl-3 {
		margin-left: 25%;
	}

	.bodywebsite .offset-xxl-4 {
		margin-left: 33.33333333%;
	}

	.bodywebsite .offset-xxl-5 {
		margin-left: 41.66666667%;
	}

	.bodywebsite .offset-xxl-6 {
		margin-left: 50%;
	}

	.bodywebsite .offset-xxl-7 {
		margin-left: 58.33333333%;
	}

	.bodywebsite .offset-xxl-8 {
		margin-left: 66.66666667%;
	}

	.bodywebsite .offset-xxl-9 {
		margin-left: 75%;
	}

	.bodywebsite .offset-xxl-10 {
		margin-left: 83.33333333%;
	}

	.bodywebsite .offset-xxl-11 {
		margin-left: 91.66666667%;
	}

	.bodywebsite .g-xxl-0,
	.bodywebsite .gx-xxl-0 {
		--bs-gutter-x: 0;
	}

	.bodywebsite .g-xxl-0,
	.bodywebsite .gy-xxl-0 {
		--bs-gutter-y: 0;
	}

	.bodywebsite .g-xxl-1,
	.bodywebsite .gx-xxl-1 {
		--bs-gutter-x: 0.25rem;
	}

	.bodywebsite .g-xxl-1,
	.bodywebsite .gy-xxl-1 {
		--bs-gutter-y: 0.25rem;
	}

	.bodywebsite .g-xxl-2,
	.bodywebsite .gx-xxl-2 {
		--bs-gutter-x: 0.5rem;
	}

	.bodywebsite .g-xxl-2,
	.bodywebsite .gy-xxl-2 {
		--bs-gutter-y: 0.5rem;
	}

	.bodywebsite .g-xxl-3,
	.bodywebsite .gx-xxl-3 {
		--bs-gutter-x: 1rem;
	}

	.bodywebsite .g-xxl-3,
	.bodywebsite .gy-xxl-3 {
		--bs-gutter-y: 1rem;
	}

	.bodywebsite .g-xxl-4,
	.bodywebsite .gx-xxl-4 {
		--bs-gutter-x: 1.5rem;
	}

	.bodywebsite .g-xxl-4,
	.bodywebsite .gy-xxl-4 {
		--bs-gutter-y: 1.5rem;
	}

	.bodywebsite .g-xxl-5,
	.bodywebsite .gx-xxl-5 {
		--bs-gutter-x: 3rem;
	}

	.bodywebsite .g-xxl-5,
	.bodywebsite .gy-xxl-5 {
		--bs-gutter-y: 3rem;
	}
}

.bodywebsite .table {
	--bs-table-color: var(--bs-body-color);
	--bs-table-bg: transparent;
	--bs-table-border-color: var(--bs-border-color);
	--bs-table-accent-bg: transparent;
	--bs-table-striped-color: var(--bs-body-color);
	--bs-table-striped-bg: rgba(0, 0, 0, 0.05);
	--bs-table-active-color: var(--bs-body-color);
	--bs-table-active-bg: rgba(0, 0, 0, 0.1);
	--bs-table-hover-color: var(--bs-body-color);
	--bs-table-hover-bg: rgba(0, 0, 0, 0.075);
	width: 100%;
	margin-bottom: 1rem;
	color: var(--bs-table-color);
	vertical-align: top;
	border-color: var(--bs-table-border-color);
}

.bodywebsite .table > :not(caption) > * > * {
	padding: 0.5rem 0.5rem;
	background-color: var(--bs-table-bg);
	border-bottom-width: 1px;
	box-shadow: inset 0 0 0 9999px var(--bs-table-accent-bg);
}

.bodywebsite .table > tbody {
	vertical-align: inherit;
}

.bodywebsite .table > thead {
	vertical-align: bottom;
}

.bodywebsite .table-group-divider {
	border-top: 2px solid currentcolor;
}

.bodywebsite .caption-top {
	caption-side: top;
}

.bodywebsite .table-sm > :not(caption) > * > * {
	padding: 0.25rem 0.25rem;
}

.bodywebsite .table-bordered > :not(caption) > * {
	border-width: 1px 0;
}

.bodywebsite .table-bordered > :not(caption) > * > * {
	border-width: 0 1px;
}

.bodywebsite .table-borderless > :not(caption) > * > * {
	border-bottom-width: 0;
}

.bodywebsite .table-borderless > :not(:first-child) {
	border-top-width: 0;
}

.bodywebsite .table-striped > tbody > tr:nth-of-type(odd) > * {
	--bs-table-accent-bg: var(--bs-table-striped-bg);
	color: var(--bs-table-striped-color);
}

.bodywebsite .table-striped-columns > :not(caption) > tr > :nth-child(even) {
	--bs-table-accent-bg: var(--bs-table-striped-bg);
	color: var(--bs-table-striped-color);
}

.bodywebsite .table-active {
	--bs-table-accent-bg: var(--bs-table-active-bg);
	color: var(--bs-table-active-color);
}

.bodywebsite .table-hover > tbody > tr:hover > * {
	--bs-table-accent-bg: var(--bs-table-hover-bg);
	color: var(--bs-table-hover-color);
}

.bodywebsite .table-primary {
	--bs-table-color: #000;
	--bs-table-bg: #cfe2ff;
	--bs-table-border-color: #bacbe6;
	--bs-table-striped-bg: #c5d7f2;
	--bs-table-striped-color: #000;
	--bs-table-active-bg: #bacbe6;
	--bs-table-active-color: #000;
	--bs-table-hover-bg: #bfd1ec;
	--bs-table-hover-color: #000;
	color: var(--bs-table-color);
	border-color: var(--bs-table-border-color);
}

.bodywebsite .table-secondary {
	--bs-table-color: #000;
	--bs-table-bg: #e2e3e5;
	--bs-table-border-color: #cbccce;
	--bs-table-striped-bg: #d7d8da;
	--bs-table-striped-color: #000;
	--bs-table-active-bg: #cbccce;
	--bs-table-active-color: #000;
	--bs-table-hover-bg: #d1d2d4;
	--bs-table-hover-color: #000;
	color: var(--bs-table-color);
	border-color: var(--bs-table-border-color);
}

.bodywebsite .table-success {
	--bs-table-color: #000;
	--bs-table-bg: #d1e7dd;
	--bs-table-border-color: #bcd0c7;
	--bs-table-striped-bg: #c7dbd2;
	--bs-table-striped-color: #000;
	--bs-table-active-bg: #bcd0c7;
	--bs-table-active-color: #000;
	--bs-table-hover-bg: #c1d6cc;
	--bs-table-hover-color: #000;
	color: var(--bs-table-color);
	border-color: var(--bs-table-border-color);
}

.bodywebsite .table-info {
	--bs-table-color: #000;
	--bs-table-bg: #cff4fc;
	--bs-table-border-color: #badce3;
	--bs-table-striped-bg: #c5e8ef;
	--bs-table-striped-color: #000;
	--bs-table-active-bg: #badce3;
	--bs-table-active-color: #000;
	--bs-table-hover-bg: #bfe2e9;
	--bs-table-hover-color: #000;
	color: var(--bs-table-color);
	border-color: var(--bs-table-border-color);
}

.bodywebsite .table-warning {
	--bs-table-color: #000;
	--bs-table-bg: #fff3cd;
	--bs-table-border-color: #e6dbb9;
	--bs-table-striped-bg: #f2e7c3;
	--bs-table-striped-color: #000;
	--bs-table-active-bg: #e6dbb9;
	--bs-table-active-color: #000;
	--bs-table-hover-bg: #ece1be;
	--bs-table-hover-color: #000;
	color: var(--bs-table-color);
	border-color: var(--bs-table-border-color);
}

.bodywebsite .table-danger {
	--bs-table-color: #000;
	--bs-table-bg: #f8d7da;
	--bs-table-border-color: #dfc2c4;
	--bs-table-striped-bg: #eccccf;
	--bs-table-striped-color: #000;
	--bs-table-active-bg: #dfc2c4;
	--bs-table-active-color: #000;
	--bs-table-hover-bg: #e5c7ca;
	--bs-table-hover-color: #000;
	color: var(--bs-table-color);
	border-color: var(--bs-table-border-color);
}

.bodywebsite .table-light {
	--bs-table-color: #000;
	--bs-table-bg: #f8f9fa;
	--bs-table-border-color: #dfe0e1;
	--bs-table-striped-bg: #ecedee;
	--bs-table-striped-color: #000;
	--bs-table-active-bg: #dfe0e1;
	--bs-table-active-color: #000;
	--bs-table-hover-bg: #e5e6e7;
	--bs-table-hover-color: #000;
	color: var(--bs-table-color);
	border-color: var(--bs-table-border-color);
}

.bodywebsite .table-dark {
	--bs-table-color: #fff;
	--bs-table-bg: #212529;
	--bs-table-border-color: #373b3e;
	--bs-table-striped-bg: #2c3034;
	--bs-table-striped-color: #fff;
	--bs-table-active-bg: #373b3e;
	--bs-table-active-color: #fff;
	--bs-table-hover-bg: #323539;
	--bs-table-hover-color: #fff;
	color: var(--bs-table-color);
	border-color: var(--bs-table-border-color);
}

.bodywebsite .table-responsive {
	overflow-x: auto;
	-webkit-overflow-scrolling: touch;
}

@media (max-width: 575.98px) {
	.bodywebsite .table-responsive-sm {
		overflow-x: auto;
		-webkit-overflow-scrolling: touch;
	}
}

@media (max-width: 767.98px) {
	.bodywebsite .table-responsive-md {
		overflow-x: auto;
		-webkit-overflow-scrolling: touch;
	}
}

@media (max-width: 991.98px) {
	.bodywebsite .table-responsive-lg {
		overflow-x: auto;
		-webkit-overflow-scrolling: touch;
	}
}

@media (max-width: 1199.98px) {
	.bodywebsite .table-responsive-xl {
		overflow-x: auto;
		-webkit-overflow-scrolling: touch;
	}
}

@media (max-width: 1399.98px) {
	.bodywebsite .table-responsive-xxl {
		overflow-x: auto;
		-webkit-overflow-scrolling: touch;
	}
}

.bodywebsite .form-label {
	margin-bottom: 0.5rem;
}

.bodywebsite .col-form-label {
	padding-top: calc(0.375rem + 1px);
	padding-bottom: calc(0.375rem + 1px);
	margin-bottom: 0;
	font-size: inherit;
	line-height: 1.5;
}

.bodywebsite .col-form-label-lg {
	padding-top: calc(0.5rem + 1px);
	padding-bottom: calc(0.5rem + 1px);
	font-size: 1.25rem;
}

.bodywebsite .col-form-label-sm {
	padding-top: calc(0.25rem + 1px);
	padding-bottom: calc(0.25rem + 1px);
	font-size: 0.875rem;
}

.bodywebsite .form-text {
	margin-top: 0.25rem;
	font-size: 0.875em;
	color: #6c757d;
}

.bodywebsite .form-control {
	display: block;
	width: 100%;
	padding: 0.375rem 0.75rem;
	font-size: 1rem;
	font-weight: 400;
	line-height: 1.5;
	color: #212529;
	background-color: #fff;
	background-clip: padding-box;
	border: 1px solid #ced4da;
	-webkit-appearance: none;
	-moz-appearance: none;
	appearance: none;
	border-radius: 0.375rem;
	transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .form-control {
		transition: none;
	}
}

.bodywebsite .form-control[type=file] {
	overflow: hidden;
}

.bodywebsite .form-control[type=file]:not(:disabled):not([readonly]) {
	cursor: pointer;
}

.bodywebsite .form-control:focus {
	color: #212529;
	background-color: #fff;
	border-color: #86b7fe;
	outline: 0;
	box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.bodywebsite .form-control::-webkit-date-and-time-value {
	height: 1.5em;
}

.bodywebsite .form-control::-moz-placeholder {
	color: #6c757d;
	opacity: 1;
}

.bodywebsite .form-control::placeholder {
	color: #6c757d;
	opacity: 1;
}

.bodywebsite .form-control:disabled {
	background-color: #e9ecef;
	opacity: 1;
}

.bodywebsite .form-control::-webkit-file-upload-button {
	padding: 0.375rem 0.75rem;
	margin: -0.375rem -0.75rem;
	-webkit-margin-end: 0.75rem;
	margin-inline-end: 0.75rem;
	color: #212529;
	background-color: #e9ecef;
	pointer-events: none;
	border-color: inherit;
	border-style: solid;
	border-width: 0;
	border-inline-end-width: 1px;
	border-radius: 0;
	-webkit-transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
	transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.bodywebsite .form-control::file-selector-button {
	padding: 0.375rem 0.75rem;
	margin: -0.375rem -0.75rem;
	-webkit-margin-end: 0.75rem;
	margin-inline-end: 0.75rem;
	color: #212529;
	background-color: #e9ecef;
	pointer-events: none;
	border-color: inherit;
	border-style: solid;
	border-width: 0;
	border-inline-end-width: 1px;
	border-radius: 0;
	transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .form-control::-webkit-file-upload-button {
		-webkit-transition: none;
		transition: none;
	}

	.bodywebsite .form-control::file-selector-button {
		transition: none;
	}
}

.bodywebsite .form-control:hover:not(:disabled):not([readonly])::-webkit-file-upload-button {
	background-color: #dde0e3;
}

.bodywebsite .form-control:hover:not(:disabled):not([readonly])::file-selector-button {
	background-color: #dde0e3;
}

.bodywebsite .form-control-plaintext {
	display: block;
	width: 100%;
	padding: 0.375rem 0;
	margin-bottom: 0;
	line-height: 1.5;
	color: #212529;
	background-color: transparent;
	border: solid transparent;
	border-width: 1px 0;
}

.bodywebsite .form-control-plaintext:focus {
	outline: 0;
}

.bodywebsite .form-control-plaintext.form-control-sm, .bodywebsite .form-control-plaintext.form-control-lg {
	padding-right: 0;
	padding-left: 0;
}

.bodywebsite .form-control-sm {
	min-height: calc(1.5em + 0.5rem + 2px);
	padding: 0.25rem 0.5rem;
	font-size: 0.875rem;
	border-radius: 0.25rem;
}

.bodywebsite .form-control-sm::-webkit-file-upload-button {
	padding: 0.25rem 0.5rem;
	margin: -0.25rem -0.5rem;
	-webkit-margin-end: 0.5rem;
	margin-inline-end: 0.5rem;
}

.bodywebsite .form-control-sm::file-selector-button {
	padding: 0.25rem 0.5rem;
	margin: -0.25rem -0.5rem;
	-webkit-margin-end: 0.5rem;
	margin-inline-end: 0.5rem;
}

.bodywebsite .form-control-lg {
	min-height: calc(1.5em + 1rem + 2px);
	padding: 0.5rem 1rem;
	font-size: 1.25rem;
	border-radius: 0.5rem;
}

.bodywebsite .form-control-lg::-webkit-file-upload-button {
	padding: 0.5rem 1rem;
	margin: -0.5rem -1rem;
	-webkit-margin-end: 1rem;
	margin-inline-end: 1rem;
}

.bodywebsite .form-control-lg::file-selector-button {
	padding: 0.5rem 1rem;
	margin: -0.5rem -1rem;
	-webkit-margin-end: 1rem;
	margin-inline-end: 1rem;
}

.bodywebsite textarea.form-control {
	min-height: calc(1.5em + 0.75rem + 2px);
}

.bodywebsite textarea.form-control-sm {
	min-height: calc(1.5em + 0.5rem + 2px);
}

.bodywebsite textarea.form-control-lg {
	min-height: calc(1.5em + 1rem + 2px);
}

.bodywebsite .form-control-color {
	width: 3rem;
	height: calc(1.5em + 0.75rem + 2px);
	padding: 0.375rem;
}

.bodywebsite .form-control-color:not(:disabled):not([readonly]) {
	cursor: pointer;
}

.bodywebsite .form-control-color::-moz-color-swatch {
	border: 0 !important;
	border-radius: 0.375rem;
}

.bodywebsite .form-control-color::-webkit-color-swatch {
	border-radius: 0.375rem;
}

.bodywebsite .form-control-color.form-control-sm {
	height: calc(1.5em + 0.5rem + 2px);
}

.bodywebsite .form-control-color.form-control-lg {
	height: calc(1.5em + 1rem + 2px);
}

.bodywebsite .form-select {
	display: block;
	width: 100%;
	padding: 0.375rem 2.25rem 0.375rem 0.75rem;
	-moz-padding-start: calc(0.75rem - 3px);
	font-size: 1rem;
	font-weight: 400;
	line-height: 1.5;
	color: #212529;
	background-color: #fff;
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
	background-repeat: no-repeat;
	background-position: right 0.75rem center;
	background-size: 16px 12px;
	border: 1px solid #ced4da;
	border-radius: 0.375rem;
	transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
	-webkit-appearance: none;
	-moz-appearance: none;
	appearance: none;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .form-select {
		transition: none;
	}
}

.bodywebsite .form-select:focus {
	border-color: #86b7fe;
	outline: 0;
	box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.bodywebsite .form-select[multiple], .bodywebsite .form-select[size]:not([size="1"]) {
	padding-right: 0.75rem;
	background-image: none;
}

.bodywebsite .form-select:disabled {
	background-color: #e9ecef;
}

.bodywebsite .form-select:-moz-focusring {
	color: transparent;
	text-shadow: 0 0 0 #212529;
}

.bodywebsite .form-select-sm {
	padding-top: 0.25rem;
	padding-bottom: 0.25rem;
	padding-left: 0.5rem;
	font-size: 0.875rem;
	border-radius: 0.25rem;
}

.bodywebsite .form-select-lg {
	padding-top: 0.5rem;
	padding-bottom: 0.5rem;
	padding-left: 1rem;
	font-size: 1.25rem;
	border-radius: 0.5rem;
}

.bodywebsite .form-check {
	display: block;
	min-height: 1.5rem;
	padding-left: 1.5em;
	margin-bottom: 0.125rem;
}

.bodywebsite .form-check .form-check-input {
	float: left;
	margin-left: -1.5em;
}

.bodywebsite .form-check-reverse {
	padding-right: 1.5em;
	padding-left: 0;
	text-align: right;
}

.bodywebsite .form-check-reverse .form-check-input {
	float: right;
	margin-right: -1.5em;
	margin-left: 0;
}

.bodywebsite .form-check-input {
	width: 1em;
	height: 1em;
	margin-top: 0.25em;
	vertical-align: top;
	background-color: #fff;
	background-repeat: no-repeat;
	background-position: center;
	background-size: contain;
	border: 1px solid rgba(0, 0, 0, 0.25);
	-webkit-appearance: none;
	-moz-appearance: none;
	appearance: none;
	-webkit-print-color-adjust: exact;
	color-adjust: exact;
	print-color-adjust: exact;
}

.bodywebsite .form-check-input[type=checkbox] {
	border-radius: 0.25em;
}

.bodywebsite .form-check-input[type=radio] {
	border-radius: 50%;
}

.bodywebsite .form-check-input:active {
	filter: brightness(90%);
}

.bodywebsite .form-check-input:focus {
	border-color: #86b7fe;
	outline: 0;
	box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.bodywebsite .form-check-input:checked {
	background-color: #0d6efd;
	border-color: #0d6efd;
}

.bodywebsite .form-check-input:checked[type=checkbox] {
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='m6 10 3 3 6-6'/%3e%3c/svg%3e");
}

.bodywebsite .form-check-input:checked[type=radio] {
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='2' fill='%23fff'/%3e%3c/svg%3e");
}

.bodywebsite .form-check-input[type=checkbox]:indeterminate {
	background-color: #0d6efd;
	border-color: #0d6efd;
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10h8'/%3e%3c/svg%3e");
}

.bodywebsite .form-check-input:disabled {
	pointer-events: none;
	filter: none;
	opacity: 0.5;
}

.bodywebsite .form-check-input[disabled] ~ .form-check-label, .bodywebsite .form-check-input:disabled ~ .form-check-label {
	cursor: default;
	opacity: 0.5;
}

.bodywebsite .form-switch {
	padding-left: 2.5em;
}

.bodywebsite .form-switch .form-check-input {
	width: 2em;
	margin-left: -2.5em;
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba%280, 0, 0, 0.25%29'/%3e%3c/svg%3e");
	background-position: left center;
	border-radius: 2em;
	transition: background-position 0.15s ease-in-out;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .form-switch .form-check-input {
		transition: none;
	}
}

.bodywebsite .form-switch .form-check-input:focus {
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%2386b7fe'/%3e%3c/svg%3e");
}

.bodywebsite .form-switch .form-check-input:checked {
	background-position: right center;
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
}

.bodywebsite .form-switch.form-check-reverse {
	padding-right: 2.5em;
	padding-left: 0;
}

.bodywebsite .form-switch.form-check-reverse .form-check-input {
	margin-right: -2.5em;
	margin-left: 0;
}

.bodywebsite .form-check-inline {
	display: inline-block;
	margin-right: 1rem;
}

.bodywebsite .btn-check {
	position: absolute;
	clip: rect(0, 0, 0, 0);
	pointer-events: none;
}

.bodywebsite .btn-check[disabled] + .btn, .bodywebsite .btn-check:disabled + .btn {
	pointer-events: none;
	filter: none;
	opacity: 0.65;
}

.bodywebsite .form-range {
	width: 100%;
	height: 1.5rem;
	padding: 0;
	background-color: transparent;
	-webkit-appearance: none;
	-moz-appearance: none;
	appearance: none;
}

.bodywebsite .form-range:focus {
	outline: 0;
}

.bodywebsite .form-range:focus::-webkit-slider-thumb {
	box-shadow: 0 0 0 1px #fff, 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.bodywebsite .form-range:focus::-moz-range-thumb {
	box-shadow: 0 0 0 1px #fff, 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.bodywebsite .form-range::-moz-focus-outer {
	border: 0;
}

.bodywebsite .form-range::-webkit-slider-thumb {
	width: 1rem;
	height: 1rem;
	margin-top: -0.25rem;
	background-color: #0d6efd;
	border: 0;
	border-radius: 1rem;
	-webkit-transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
	transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
	-webkit-appearance: none;
	appearance: none;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .form-range::-webkit-slider-thumb {
		-webkit-transition: none;
		transition: none;
	}
}

.bodywebsite .form-range::-webkit-slider-thumb:active {
	background-color: #b6d4fe;
}

.bodywebsite .form-range::-webkit-slider-runnable-track {
	width: 100%;
	height: 0.5rem;
	color: transparent;
	cursor: pointer;
	background-color: #dee2e6;
	border-color: transparent;
	border-radius: 1rem;
}

.bodywebsite .form-range::-moz-range-thumb {
	width: 1rem;
	height: 1rem;
	background-color: #0d6efd;
	border: 0;
	border-radius: 1rem;
	-moz-transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
	transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
	-moz-appearance: none;
	appearance: none;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .form-range::-moz-range-thumb {
		-moz-transition: none;
		transition: none;
	}
}

.bodywebsite .form-range::-moz-range-thumb:active {
	background-color: #b6d4fe;
}

.bodywebsite .form-range::-moz-range-track {
	width: 100%;
	height: 0.5rem;
	color: transparent;
	cursor: pointer;
	background-color: #dee2e6;
	border-color: transparent;
	border-radius: 1rem;
}

.bodywebsite .form-range:disabled {
	pointer-events: none;
}

.bodywebsite .form-range:disabled::-webkit-slider-thumb {
	background-color: #adb5bd;
}

.bodywebsite .form-range:disabled::-moz-range-thumb {
	background-color: #adb5bd;
}

.bodywebsite .form-floating {
	position: relative;
}

.bodywebsite .form-floating > .form-control,
  .bodywebsite .form-floating > .form-control-plaintext,
  .bodywebsite .form-floating > .form-select {
	height: calc(3.5rem + 2px);
	line-height: 1.25;
}

.bodywebsite .form-floating > label {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	padding: 1rem 0.75rem;
	overflow: hidden;
	text-align: start;
	text-overflow: ellipsis;
	white-space: nowrap;
	pointer-events: none;
	border: 1px solid transparent;
	transform-origin: 0 0;
	transition: opacity 0.1s ease-in-out, transform 0.1s ease-in-out;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .form-floating > label {
		transition: none;
	}
}

.bodywebsite .form-floating > .form-control,
  .bodywebsite .form-floating > .form-control-plaintext {
	padding: 1rem 0.75rem;
}

.bodywebsite .form-floating > .form-control::-moz-placeholder, .bodywebsite .form-floating > .form-control-plaintext::-moz-placeholder {
	color: transparent;
}

.bodywebsite .form-floating > .form-control::placeholder,
  .bodywebsite .form-floating > .form-control-plaintext::placeholder {
	color: transparent;
}

.bodywebsite .form-floating > .form-control:not(:-moz-placeholder-shown), .bodywebsite .form-floating > .form-control-plaintext:not(:-moz-placeholder-shown) {
	padding-top: 1.625rem;
	padding-bottom: 0.625rem;
}

.bodywebsite .form-floating > .form-control:focus, .bodywebsite .form-floating > .form-control:not(:placeholder-shown),
  .bodywebsite .form-floating > .form-control-plaintext:focus,
  .bodywebsite .form-floating > .form-control-plaintext:not(:placeholder-shown) {
	padding-top: 1.625rem;
	padding-bottom: 0.625rem;
}

.bodywebsite .form-floating > .form-control:-webkit-autofill,
  .bodywebsite .form-floating > .form-control-plaintext:-webkit-autofill {
	padding-top: 1.625rem;
	padding-bottom: 0.625rem;
}

.bodywebsite .form-floating > .form-select {
	padding-top: 1.625rem;
	padding-bottom: 0.625rem;
}

.bodywebsite .form-floating > .form-control:not(:-moz-placeholder-shown) ~ label {
	opacity: 0.65;
	transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
}

.bodywebsite .form-floating > .form-control:focus ~ label,
  .bodywebsite .form-floating > .form-control:not(:placeholder-shown) ~ label,
  .bodywebsite .form-floating > .form-control-plaintext ~ label,
  .bodywebsite .form-floating > .form-select ~ label {
	opacity: 0.65;
	transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
}

.bodywebsite .form-floating > .form-control:-webkit-autofill ~ label {
	opacity: 0.65;
	transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
}

.bodywebsite .form-floating > .form-control-plaintext ~ label {
	border-width: 1px 0;
}

.bodywebsite .input-group {
	position: relative;
	display: flex;
	flex-wrap: wrap;
	align-items: stretch;
	width: 100%;
}

.bodywebsite .input-group > .form-control,
  .bodywebsite .input-group > .form-select,
  .bodywebsite .input-group > .form-floating {
	position: relative;
	flex: 1 1 auto;
	width: 1%;
	min-width: 0;
}

.bodywebsite .input-group > .form-control:focus,
  .bodywebsite .input-group > .form-select:focus,
  .bodywebsite .input-group > .form-floating:focus-within {
	z-index: 5;
}

.bodywebsite .input-group .btn {
	position: relative;
	z-index: 2;
}

.bodywebsite .input-group .btn:focus {
	z-index: 5;
}

.bodywebsite .input-group-text {
	display: flex;
	align-items: center;
	padding: 0.375rem 0.75rem;
	font-size: 1rem;
	font-weight: 400;
	line-height: 1.5;
	color: #212529;
	text-align: center;
	white-space: nowrap;
	background-color: #e9ecef;
	border: 1px solid #ced4da;
	border-radius: 0.375rem;
}

.bodywebsite .input-group-lg > .form-control,
  .bodywebsite .input-group-lg > .form-select,
  .bodywebsite .input-group-lg > .input-group-text,
  .bodywebsite .input-group-lg > .btn {
	padding: 0.5rem 1rem;
	font-size: 1.25rem;
	border-radius: 0.5rem;
}

.bodywebsite .input-group-sm > .form-control,
  .bodywebsite .input-group-sm > .form-select,
  .bodywebsite .input-group-sm > .input-group-text,
  .bodywebsite .input-group-sm > .btn {
	padding: 0.25rem 0.5rem;
	font-size: 0.875rem;
	border-radius: 0.25rem;
}

.bodywebsite .input-group-lg > .form-select,
  .bodywebsite .input-group-sm > .form-select {
	padding-right: 3rem;
}

.bodywebsite .input-group:not(.has-validation) > :not(:last-child):not(.dropdown-toggle):not(.dropdown-menu):not(.form-floating),
  .bodywebsite .input-group:not(.has-validation) > .dropdown-toggle:nth-last-child(n+3),
  .bodywebsite .input-group:not(.has-validation) > .form-floating:not(:last-child) > .form-control,
  .bodywebsite .input-group:not(.has-validation) > .form-floating:not(:last-child) > .form-select {
	border-top-right-radius: 0;
	border-bottom-right-radius: 0;
}

.bodywebsite .input-group.has-validation > :nth-last-child(n+3):not(.dropdown-toggle):not(.dropdown-menu):not(.form-floating),
  .bodywebsite .input-group.has-validation > .dropdown-toggle:nth-last-child(n+4),
  .bodywebsite .input-group.has-validation > .form-floating:nth-last-child(n+3) > .form-control,
  .bodywebsite .input-group.has-validation > .form-floating:nth-last-child(n+3) > .form-select {
	border-top-right-radius: 0;
	border-bottom-right-radius: 0;
}

.bodywebsite .input-group > :not(:first-child):not(.dropdown-menu):not(.valid-tooltip):not(.valid-feedback):not(.invalid-tooltip):not(.invalid-feedback) {
	margin-left: -1px;
	border-top-left-radius: 0;
	border-bottom-left-radius: 0;
}

.bodywebsite .input-group > .form-floating:not(:first-child) > .form-control,
  .bodywebsite .input-group > .form-floating:not(:first-child) > .form-select {
	border-top-left-radius: 0;
	border-bottom-left-radius: 0;
}

.bodywebsite .valid-feedback {
	display: none;
	width: 100%;
	margin-top: 0.25rem;
	font-size: 0.875em;
	color: #198754;
}

.bodywebsite .valid-tooltip {
	position: absolute;
	top: 100%;
	z-index: 5;
	display: none;
	max-width: 100%;
	padding: 0.25rem 0.5rem;
	margin-top: 0.1rem;
	font-size: 0.875rem;
	color: #fff;
	background-color: rgba(25, 135, 84, 0.9);
	border-radius: 0.375rem;
}

.bodywebsite .was-validated :valid ~ .valid-feedback,
  .bodywebsite .was-validated :valid ~ .valid-tooltip,
  .bodywebsite .is-valid ~ .valid-feedback,
  .bodywebsite .is-valid ~ .valid-tooltip {
	display: block;
}

.bodywebsite .was-validated .form-control:valid, .bodywebsite .form-control.is-valid {
	border-color: #198754;
	padding-right: calc(1.5em + 0.75rem);
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
	background-repeat: no-repeat;
	background-position: right calc(0.375em + 0.1875rem) center;
	background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.bodywebsite .was-validated .form-control:valid:focus, .bodywebsite .form-control.is-valid:focus {
	border-color: #198754;
	box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
}

.bodywebsite .was-validated textarea.form-control:valid, .bodywebsite textarea.form-control.is-valid {
	padding-right: calc(1.5em + 0.75rem);
	background-position: top calc(0.375em + 0.1875rem) right calc(0.375em + 0.1875rem);
}

.bodywebsite .was-validated .form-select:valid, .bodywebsite .form-select.is-valid {
	border-color: #198754;
}

.bodywebsite .was-validated .form-select:valid:not([multiple]):not([size]), .bodywebsite .was-validated .form-select:valid:not([multiple])[size="1"], .bodywebsite .form-select.is-valid:not([multiple]):not([size]), .bodywebsite .form-select.is-valid:not([multiple])[size="1"] {
	padding-right: 4.125rem;
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e"), url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
	background-position: right 0.75rem center, center right 2.25rem;
	background-size: 16px 12px, calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.bodywebsite .was-validated .form-select:valid:focus, .bodywebsite .form-select.is-valid:focus {
	border-color: #198754;
	box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
}

.bodywebsite .was-validated .form-control-color:valid, .bodywebsite .form-control-color.is-valid {
	width: calc(3rem + calc(1.5em + 0.75rem));
}

.bodywebsite .was-validated .form-check-input:valid, .bodywebsite .form-check-input.is-valid {
	border-color: #198754;
}

.bodywebsite .was-validated .form-check-input:valid:checked, .bodywebsite .form-check-input.is-valid:checked {
	background-color: #198754;
}

.bodywebsite .was-validated .form-check-input:valid:focus, .bodywebsite .form-check-input.is-valid:focus {
	box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
}

.bodywebsite .was-validated .form-check-input:valid ~ .form-check-label, .bodywebsite .form-check-input.is-valid ~ .form-check-label {
	color: #198754;
}

.bodywebsite .form-check-inline .form-check-input ~ .valid-feedback {
	margin-left: 0.5em;
}

.bodywebsite .was-validated .input-group > .form-control:not(:focus):valid, .bodywebsite .input-group > .form-control:not(:focus).is-valid,
  .bodywebsite .was-validated .input-group > .form-select:not(:focus):valid,
  .bodywebsite .input-group > .form-select:not(:focus).is-valid,
  .bodywebsite .was-validated .input-group > .form-floating:not(:focus-within):valid,
  .bodywebsite .input-group > .form-floating:not(:focus-within).is-valid {
	z-index: 3;
}

.bodywebsite .invalid-feedback {
	display: none;
	width: 100%;
	margin-top: 0.25rem;
	font-size: 0.875em;
	color: #dc3545;
}

.bodywebsite .invalid-tooltip {
	position: absolute;
	top: 100%;
	z-index: 5;
	display: none;
	max-width: 100%;
	padding: 0.25rem 0.5rem;
	margin-top: 0.1rem;
	font-size: 0.875rem;
	color: #fff;
	background-color: rgba(220, 53, 69, 0.9);
	border-radius: 0.375rem;
}

.bodywebsite .was-validated :invalid ~ .invalid-feedback,
  .bodywebsite .was-validated :invalid ~ .invalid-tooltip,
  .bodywebsite .is-invalid ~ .invalid-feedback,
  .bodywebsite .is-invalid ~ .invalid-tooltip {
	display: block;
}

.bodywebsite .was-validated .form-control:invalid, .bodywebsite .form-control.is-invalid {
	border-color: #dc3545;
	padding-right: calc(1.5em + 0.75rem);
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
	background-repeat: no-repeat;
	background-position: right calc(0.375em + 0.1875rem) center;
	background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.bodywebsite .was-validated .form-control:invalid:focus, .bodywebsite .form-control.is-invalid:focus {
	border-color: #dc3545;
	box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
}

.bodywebsite .was-validated textarea.form-control:invalid, .bodywebsite textarea.form-control.is-invalid {
	padding-right: calc(1.5em + 0.75rem);
	background-position: top calc(0.375em + 0.1875rem) right calc(0.375em + 0.1875rem);
}

.bodywebsite .was-validated .form-select:invalid, .bodywebsite .form-select.is-invalid {
	border-color: #dc3545;
}

.bodywebsite .was-validated .form-select:invalid:not([multiple]):not([size]), .bodywebsite .was-validated .form-select:invalid:not([multiple])[size="1"], .bodywebsite .form-select.is-invalid:not([multiple]):not([size]), .bodywebsite .form-select.is-invalid:not([multiple])[size="1"] {
	padding-right: 4.125rem;
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e"), url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
	background-position: right 0.75rem center, center right 2.25rem;
	background-size: 16px 12px, calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.bodywebsite .was-validated .form-select:invalid:focus, .bodywebsite .form-select.is-invalid:focus {
	border-color: #dc3545;
	box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
}

.bodywebsite .was-validated .form-control-color:invalid, .bodywebsite .form-control-color.is-invalid {
	width: calc(3rem + calc(1.5em + 0.75rem));
}

.bodywebsite .was-validated .form-check-input:invalid, .bodywebsite .form-check-input.is-invalid {
	border-color: #dc3545;
}

.bodywebsite .was-validated .form-check-input:invalid:checked, .bodywebsite .form-check-input.is-invalid:checked {
	background-color: #dc3545;
}

.bodywebsite .was-validated .form-check-input:invalid:focus, .bodywebsite .form-check-input.is-invalid:focus {
	box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
}

.bodywebsite .was-validated .form-check-input:invalid ~ .form-check-label, .bodywebsite .form-check-input.is-invalid ~ .form-check-label {
	color: #dc3545;
}

.bodywebsite .form-check-inline .form-check-input ~ .invalid-feedback {
	margin-left: 0.5em;
}

.bodywebsite .was-validated .input-group > .form-control:not(:focus):invalid, .bodywebsite .input-group > .form-control:not(:focus).is-invalid,
  .bodywebsite .was-validated .input-group > .form-select:not(:focus):invalid,
  .bodywebsite .input-group > .form-select:not(:focus).is-invalid,
  .bodywebsite .was-validated .input-group > .form-floating:not(:focus-within):invalid,
  .bodywebsite .input-group > .form-floating:not(:focus-within).is-invalid {
	z-index: 4;
}

.bodywebsite .btn {
	--bs-btn-padding-x: 0.75rem;
	--bs-btn-padding-y: 0.375rem;
	--bs-btn-font-size: 1rem;
	--bs-btn-font-weight: 400;
	--bs-btn-line-height: 1.5;
	--bs-btn-color: #212529;
	--bs-btn-bg: transparent;
	--bs-btn-border-width: 1px;
	--bs-btn-border-color: transparent;
	--bs-btn-border-radius: 0.375rem;
	--bs-btn-hover-border-color: transparent;
	--bs-btn-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.15), 0 1px 1px rgba(0, 0, 0, 0.075);
	--bs-btn-disabled-opacity: 0.65;
	display: inline-block;
	padding: var(--bs-btn-padding-y) var(--bs-btn-padding-x);
	font-family: var(--bs-btn-font-family);
	font-size: var(--bs-btn-font-size);
	font-weight: var(--bs-btn-font-weight);
	line-height: var(--bs-btn-line-height);
	color: var(--bs-btn-color);
	text-align: center;
	text-decoration: none;
	vertical-align: middle;
	cursor: pointer;
	-webkit-user-select: none;
	-moz-user-select: none;
	user-select: none;
	border: var(--bs-btn-border-width) solid var(--bs-btn-border-color);
	border-radius: var(--bs-btn-border-radius);
	background-color: var(--bs-btn-bg);
	transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .btn {
		transition: none;
	}
}

.bodywebsite :not(.btn-check) + .btn:hover, .bodywebsite .btn:first-child:hover {
	color: var(--bs-btn-hover-color);
	background-color: var(--bs-btn-hover-bg);
	border-color: var(--bs-btn-hover-border-color);
}

.bodywebsite .btn:focus-visible {
	color: var(--bs-btn-hover-color);
	background-color: var(--bs-btn-hover-bg);
	border-color: var(--bs-btn-hover-border-color);
	outline: 0;
	box-shadow: var(--bs-btn-focus-box-shadow);
}

.bodywebsite .btn-check:focus-visible + .btn {
	border-color: var(--bs-btn-hover-border-color);
	outline: 0;
	box-shadow: var(--bs-btn-focus-box-shadow);
}

.bodywebsite .btn-check:checked + .btn, .bodywebsite :not(.btn-check) + .btn:active, .bodywebsite .btn:first-child:active, .bodywebsite .btn.active, .bodywebsite .btn.show {
	color: var(--bs-btn-active-color);
	background-color: var(--bs-btn-active-bg);
	border-color: var(--bs-btn-active-border-color);
}

.bodywebsite .btn-check:checked + .btn:focus-visible, .bodywebsite :not(.btn-check) + .btn:active:focus-visible, .bodywebsite .btn:first-child:active:focus-visible, .bodywebsite .btn.active:focus-visible, .bodywebsite .btn.show:focus-visible {
	box-shadow: var(--bs-btn-focus-box-shadow);
}

.bodywebsite .btn:disabled, .bodywebsite .btn.disabled, .bodywebsite fieldset:disabled .btn {
	color: var(--bs-btn-disabled-color);
	pointer-events: none;
	background-color: var(--bs-btn-disabled-bg);
	border-color: var(--bs-btn-disabled-border-color);
	opacity: var(--bs-btn-disabled-opacity);
}

.bodywebsite .btn-primary {
	--bs-btn-color: #fff;
	--bs-btn-bg: #0d6efd;
	--bs-btn-border-color: #0d6efd;
	--bs-btn-hover-color: #fff;
	--bs-btn-hover-bg: #0b5ed7;
	--bs-btn-hover-border-color: #0a58ca;
	--bs-btn-focus-shadow-rgb: 49, 132, 253;
	--bs-btn-active-color: #fff;
	--bs-btn-active-bg: #0a58ca;
	--bs-btn-active-border-color: #0a53be;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #fff;
	--bs-btn-disabled-bg: #0d6efd;
	--bs-btn-disabled-border-color: #0d6efd;
}

.bodywebsite .btn-secondary {
	--bs-btn-color: #fff;
	--bs-btn-bg: #6c757d;
	--bs-btn-border-color: #6c757d;
	--bs-btn-hover-color: #fff;
	--bs-btn-hover-bg: #5c636a;
	--bs-btn-hover-border-color: #565e64;
	--bs-btn-focus-shadow-rgb: 130, 138, 145;
	--bs-btn-active-color: #fff;
	--bs-btn-active-bg: #565e64;
	--bs-btn-active-border-color: #51585e;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #fff;
	--bs-btn-disabled-bg: #6c757d;
	--bs-btn-disabled-border-color: #6c757d;
}

.bodywebsite .btn-success {
	--bs-btn-color: #fff;
	--bs-btn-bg: #198754;
	--bs-btn-border-color: #198754;
	--bs-btn-hover-color: #fff;
	--bs-btn-hover-bg: #157347;
	--bs-btn-hover-border-color: #146c43;
	--bs-btn-focus-shadow-rgb: 60, 153, 110;
	--bs-btn-active-color: #fff;
	--bs-btn-active-bg: #146c43;
	--bs-btn-active-border-color: #13653f;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #fff;
	--bs-btn-disabled-bg: #198754;
	--bs-btn-disabled-border-color: #198754;
}

.bodywebsite .btn-info {
	--bs-btn-color: #000;
	--bs-btn-bg: #0dcaf0;
	--bs-btn-border-color: #0dcaf0;
	--bs-btn-hover-color: #000;
	--bs-btn-hover-bg: #31d2f2;
	--bs-btn-hover-border-color: #25cff2;
	--bs-btn-focus-shadow-rgb: 11, 172, 204;
	--bs-btn-active-color: #000;
	--bs-btn-active-bg: #3dd5f3;
	--bs-btn-active-border-color: #25cff2;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #000;
	--bs-btn-disabled-bg: #0dcaf0;
	--bs-btn-disabled-border-color: #0dcaf0;
}

.bodywebsite .btn-warning {
	--bs-btn-color: #000;
	--bs-btn-bg: #ffc107;
	--bs-btn-border-color: #ffc107;
	--bs-btn-hover-color: #000;
	--bs-btn-hover-bg: #ffca2c;
	--bs-btn-hover-border-color: #ffc720;
	--bs-btn-focus-shadow-rgb: 217, 164, 6;
	--bs-btn-active-color: #000;
	--bs-btn-active-bg: #ffcd39;
	--bs-btn-active-border-color: #ffc720;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #000;
	--bs-btn-disabled-bg: #ffc107;
	--bs-btn-disabled-border-color: #ffc107;
}

.bodywebsite .btn-danger {
	--bs-btn-color: #fff;
	--bs-btn-bg: #dc3545;
	--bs-btn-border-color: #dc3545;
	--bs-btn-hover-color: #fff;
	--bs-btn-hover-bg: #bb2d3b;
	--bs-btn-hover-border-color: #b02a37;
	--bs-btn-focus-shadow-rgb: 225, 83, 97;
	--bs-btn-active-color: #fff;
	--bs-btn-active-bg: #b02a37;
	--bs-btn-active-border-color: #a52834;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #fff;
	--bs-btn-disabled-bg: #dc3545;
	--bs-btn-disabled-border-color: #dc3545;
}

.bodywebsite .btn-light {
	--bs-btn-color: #000;
	--bs-btn-bg: #f8f9fa;
	--bs-btn-border-color: #f8f9fa;
	--bs-btn-hover-color: #000;
	--bs-btn-hover-bg: #d3d4d5;
	--bs-btn-hover-border-color: #c6c7c8;
	--bs-btn-focus-shadow-rgb: 211, 212, 213;
	--bs-btn-active-color: #000;
	--bs-btn-active-bg: #c6c7c8;
	--bs-btn-active-border-color: #babbbc;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #000;
	--bs-btn-disabled-bg: #f8f9fa;
	--bs-btn-disabled-border-color: #f8f9fa;
}

.bodywebsite .btn-dark {
	--bs-btn-color: #fff;
	--bs-btn-bg: #212529;
	--bs-btn-border-color: #212529;
	--bs-btn-hover-color: #fff;
	--bs-btn-hover-bg: #424649;
	--bs-btn-hover-border-color: #373b3e;
	--bs-btn-focus-shadow-rgb: 66, 70, 73;
	--bs-btn-active-color: #fff;
	--bs-btn-active-bg: #4d5154;
	--bs-btn-active-border-color: #373b3e;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #fff;
	--bs-btn-disabled-bg: #212529;
	--bs-btn-disabled-border-color: #212529;
}

.bodywebsite .btn-outline-primary {
	--bs-btn-color: #0d6efd;
	--bs-btn-border-color: #0d6efd;
	--bs-btn-hover-color: #fff;
	--bs-btn-hover-bg: #0d6efd;
	--bs-btn-hover-border-color: #0d6efd;
	--bs-btn-focus-shadow-rgb: 13, 110, 253;
	--bs-btn-active-color: #fff;
	--bs-btn-active-bg: #0d6efd;
	--bs-btn-active-border-color: #0d6efd;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #0d6efd;
	--bs-btn-disabled-bg: transparent;
	--bs-btn-disabled-border-color: #0d6efd;
	--bs-gradient: none;
}

.bodywebsite .btn-outline-secondary {
	--bs-btn-color: #6c757d;
	--bs-btn-border-color: #6c757d;
	--bs-btn-hover-color: #fff;
	--bs-btn-hover-bg: #6c757d;
	--bs-btn-hover-border-color: #6c757d;
	--bs-btn-focus-shadow-rgb: 108, 117, 125;
	--bs-btn-active-color: #fff;
	--bs-btn-active-bg: #6c757d;
	--bs-btn-active-border-color: #6c757d;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #6c757d;
	--bs-btn-disabled-bg: transparent;
	--bs-btn-disabled-border-color: #6c757d;
	--bs-gradient: none;
}

.bodywebsite .btn-outline-success {
	--bs-btn-color: #198754;
	--bs-btn-border-color: #198754;
	--bs-btn-hover-color: #fff;
	--bs-btn-hover-bg: #198754;
	--bs-btn-hover-border-color: #198754;
	--bs-btn-focus-shadow-rgb: 25, 135, 84;
	--bs-btn-active-color: #fff;
	--bs-btn-active-bg: #198754;
	--bs-btn-active-border-color: #198754;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #198754;
	--bs-btn-disabled-bg: transparent;
	--bs-btn-disabled-border-color: #198754;
	--bs-gradient: none;
}

.bodywebsite .btn-outline-info {
	--bs-btn-color: #0dcaf0;
	--bs-btn-border-color: #0dcaf0;
	--bs-btn-hover-color: #000;
	--bs-btn-hover-bg: #0dcaf0;
	--bs-btn-hover-border-color: #0dcaf0;
	--bs-btn-focus-shadow-rgb: 13, 202, 240;
	--bs-btn-active-color: #000;
	--bs-btn-active-bg: #0dcaf0;
	--bs-btn-active-border-color: #0dcaf0;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #0dcaf0;
	--bs-btn-disabled-bg: transparent;
	--bs-btn-disabled-border-color: #0dcaf0;
	--bs-gradient: none;
}

.bodywebsite .btn-outline-warning {
	--bs-btn-color: #ffc107;
	--bs-btn-border-color: #ffc107;
	--bs-btn-hover-color: #000;
	--bs-btn-hover-bg: #ffc107;
	--bs-btn-hover-border-color: #ffc107;
	--bs-btn-focus-shadow-rgb: 255, 193, 7;
	--bs-btn-active-color: #000;
	--bs-btn-active-bg: #ffc107;
	--bs-btn-active-border-color: #ffc107;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #ffc107;
	--bs-btn-disabled-bg: transparent;
	--bs-btn-disabled-border-color: #ffc107;
	--bs-gradient: none;
}

.bodywebsite .btn-outline-danger {
	--bs-btn-color: #dc3545;
	--bs-btn-border-color: #dc3545;
	--bs-btn-hover-color: #fff;
	--bs-btn-hover-bg: #dc3545;
	--bs-btn-hover-border-color: #dc3545;
	--bs-btn-focus-shadow-rgb: 220, 53, 69;
	--bs-btn-active-color: #fff;
	--bs-btn-active-bg: #dc3545;
	--bs-btn-active-border-color: #dc3545;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #dc3545;
	--bs-btn-disabled-bg: transparent;
	--bs-btn-disabled-border-color: #dc3545;
	--bs-gradient: none;
}

.bodywebsite .btn-outline-light {
	--bs-btn-color: #f8f9fa;
	--bs-btn-border-color: #f8f9fa;
	--bs-btn-hover-color: #000;
	--bs-btn-hover-bg: #f8f9fa;
	--bs-btn-hover-border-color: #f8f9fa;
	--bs-btn-focus-shadow-rgb: 248, 249, 250;
	--bs-btn-active-color: #000;
	--bs-btn-active-bg: #f8f9fa;
	--bs-btn-active-border-color: #f8f9fa;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #f8f9fa;
	--bs-btn-disabled-bg: transparent;
	--bs-btn-disabled-border-color: #f8f9fa;
	--bs-gradient: none;
}

.bodywebsite .btn-outline-dark {
	--bs-btn-color: #212529;
	--bs-btn-border-color: #212529;
	--bs-btn-hover-color: #fff;
	--bs-btn-hover-bg: #212529;
	--bs-btn-hover-border-color: #212529;
	--bs-btn-focus-shadow-rgb: 33, 37, 41;
	--bs-btn-active-color: #fff;
	--bs-btn-active-bg: #212529;
	--bs-btn-active-border-color: #212529;
	--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
	--bs-btn-disabled-color: #212529;
	--bs-btn-disabled-bg: transparent;
	--bs-btn-disabled-border-color: #212529;
	--bs-gradient: none;
}

.bodywebsite .btn-link {
	--bs-btn-font-weight: 400;
	--bs-btn-color: var(--bs-link-color);
	--bs-btn-bg: transparent;
	--bs-btn-border-color: transparent;
	--bs-btn-hover-color: var(--bs-link-hover-color);
	--bs-btn-hover-border-color: transparent;
	--bs-btn-active-color: var(--bs-link-hover-color);
	--bs-btn-active-border-color: transparent;
	--bs-btn-disabled-color: #6c757d;
	--bs-btn-disabled-border-color: transparent;
	--bs-btn-box-shadow: none;
	--bs-btn-focus-shadow-rgb: 49, 132, 253;
	text-decoration: underline;
}

.bodywebsite .btn-link:focus-visible {
	color: var(--bs-btn-color);
}

.bodywebsite .btn-link:hover {
	color: var(--bs-btn-hover-color);
}

.bodywebsite .btn-lg, .bodywebsite .btn-group-lg > .btn {
	--bs-btn-padding-y: 0.5rem;
	--bs-btn-padding-x: 1rem;
	--bs-btn-font-size: 1.25rem;
	--bs-btn-border-radius: 0.5rem;
}

.bodywebsite .btn-sm, .bodywebsite .btn-group-sm > .btn {
	--bs-btn-padding-y: 0.25rem;
	--bs-btn-padding-x: 0.5rem;
	--bs-btn-font-size: 0.875rem;
	--bs-btn-border-radius: 0.25rem;
}

.bodywebsite .fade {
	transition: opacity 0.15s linear;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .fade {
		transition: none;
	}
}

.bodywebsite .fade:not(.show) {
	opacity: 0;
}

.bodywebsite .collapse:not(.show) {
	display: none;
}

.bodywebsite .collapsing {
	height: 0;
	overflow: hidden;
	transition: height 0.35s ease;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .collapsing {
		transition: none;
	}
}

.bodywebsite .collapsing.collapse-horizontal {
	width: 0;
	height: auto;
	transition: width 0.35s ease;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .collapsing.collapse-horizontal {
		transition: none;
	}
}

.bodywebsite .dropup,
  .bodywebsite .dropend,
  .bodywebsite .dropdown,
  .bodywebsite .dropstart,
  .bodywebsite .dropup-center,
  .bodywebsite .dropdown-center {
	position: relative;
}

.bodywebsite .dropdown-toggle {
	white-space: nowrap;
}

.bodywebsite .dropdown-toggle::after {
	display: inline-block;
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
	--bs-dropdown-zindex: 1000;
	--bs-dropdown-min-width: 10rem;
	--bs-dropdown-padding-x: 0;
	--bs-dropdown-padding-y: 0.5rem;
	--bs-dropdown-spacer: 0.125rem;
	--bs-dropdown-font-size: 1rem;
	--bs-dropdown-color: #212529;
	--bs-dropdown-bg: #fff;
	--bs-dropdown-border-color: var(--bs-border-color-translucent);
	--bs-dropdown-border-radius: 0.375rem;
	--bs-dropdown-border-width: 1px;
	--bs-dropdown-inner-border-radius: calc(0.375rem - 1px);
	--bs-dropdown-divider-bg: var(--bs-border-color-translucent);
	--bs-dropdown-divider-margin-y: 0.5rem;
	--bs-dropdown-box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
	--bs-dropdown-link-color: #212529;
	--bs-dropdown-link-hover-color: #1e2125;
	--bs-dropdown-link-hover-bg: #e9ecef;
	--bs-dropdown-link-active-color: #fff;
	--bs-dropdown-link-active-bg: #0d6efd;
	--bs-dropdown-link-disabled-color: #adb5bd;
	--bs-dropdown-item-padding-x: 1rem;
	--bs-dropdown-item-padding-y: 0.25rem;
	--bs-dropdown-header-color: #6c757d;
	--bs-dropdown-header-padding-x: 1rem;
	--bs-dropdown-header-padding-y: 0.5rem;
	position: absolute;
	z-index: var(--bs-dropdown-zindex);
	display: none;
	min-width: var(--bs-dropdown-min-width);
	padding: var(--bs-dropdown-padding-y) var(--bs-dropdown-padding-x);
	margin: 0;
	font-size: var(--bs-dropdown-font-size);
	color: var(--bs-dropdown-color);
	text-align: left;
	list-style: none;
	background-color: var(--bs-dropdown-bg);
	background-clip: padding-box;
	border: var(--bs-dropdown-border-width) solid var(--bs-dropdown-border-color);
	border-radius: var(--bs-dropdown-border-radius);
}

.bodywebsite .dropdown-menu[data-bs-popper] {
	top: 100%;
	left: 0;
	margin-top: var(--bs-dropdown-spacer);
}

.bodywebsite .dropdown-menu-start {
	--bs-position: start;
}

.bodywebsite .dropdown-menu-start[data-bs-popper] {
	right: auto;
	left: 0;
}

.bodywebsite .dropdown-menu-end {
	--bs-position: end;
}

.bodywebsite .dropdown-menu-end[data-bs-popper] {
	right: 0;
	left: auto;
}

@media (min-width: 576px) {
	.bodywebsite .dropdown-menu-sm-start {
		--bs-position: start;
	}

	.bodywebsite .dropdown-menu-sm-start[data-bs-popper] {
		right: auto;
		left: 0;
	}

	.bodywebsite .dropdown-menu-sm-end {
		--bs-position: end;
	}

	.bodywebsite .dropdown-menu-sm-end[data-bs-popper] {
		right: 0;
		left: auto;
	}
}

@media (min-width: 768px) {
	.bodywebsite .dropdown-menu-md-start {
		--bs-position: start;
	}

	.bodywebsite .dropdown-menu-md-start[data-bs-popper] {
		right: auto;
		left: 0;
	}

	.bodywebsite .dropdown-menu-md-end {
		--bs-position: end;
	}

	.bodywebsite .dropdown-menu-md-end[data-bs-popper] {
		right: 0;
		left: auto;
	}
}

@media (min-width: 992px) {
	.bodywebsite .dropdown-menu-lg-start {
		--bs-position: start;
	}

	.bodywebsite .dropdown-menu-lg-start[data-bs-popper] {
		right: auto;
		left: 0;
	}

	.bodywebsite .dropdown-menu-lg-end {
		--bs-position: end;
	}

	.bodywebsite .dropdown-menu-lg-end[data-bs-popper] {
		right: 0;
		left: auto;
	}
}

@media (min-width: 1200px) {
	.bodywebsite .dropdown-menu-xl-start {
		--bs-position: start;
	}

	.bodywebsite .dropdown-menu-xl-start[data-bs-popper] {
		right: auto;
		left: 0;
	}

	.bodywebsite .dropdown-menu-xl-end {
		--bs-position: end;
	}

	.bodywebsite .dropdown-menu-xl-end[data-bs-popper] {
		right: 0;
		left: auto;
	}
}

@media (min-width: 1400px) {
	.bodywebsite .dropdown-menu-xxl-start {
		--bs-position: start;
	}

	.bodywebsite .dropdown-menu-xxl-start[data-bs-popper] {
		right: auto;
		left: 0;
	}

	.bodywebsite .dropdown-menu-xxl-end {
		--bs-position: end;
	}

	.bodywebsite .dropdown-menu-xxl-end[data-bs-popper] {
		right: 0;
		left: auto;
	}
}

.bodywebsite .dropup .dropdown-menu[data-bs-popper] {
	top: auto;
	bottom: 100%;
	margin-top: 0;
	margin-bottom: var(--bs-dropdown-spacer);
}

.bodywebsite .dropup .dropdown-toggle::after {
	display: inline-block;
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

.bodywebsite .dropend .dropdown-menu[data-bs-popper] {
	top: 0;
	right: auto;
	left: 100%;
	margin-top: 0;
	margin-left: var(--bs-dropdown-spacer);
}

.bodywebsite .dropend .dropdown-toggle::after {
	display: inline-block;
	margin-left: 0.255em;
	vertical-align: 0.255em;
	content: "";
	border-top: 0.3em solid transparent;
	border-right: 0;
	border-bottom: 0.3em solid transparent;
	border-left: 0.3em solid;
}

.bodywebsite .dropend .dropdown-toggle:empty::after {
	margin-left: 0;
}

.bodywebsite .dropend .dropdown-toggle::after {
	vertical-align: 0;
}

.bodywebsite .dropstart .dropdown-menu[data-bs-popper] {
	top: 0;
	right: 100%;
	left: auto;
	margin-top: 0;
	margin-right: var(--bs-dropdown-spacer);
}

.bodywebsite .dropstart .dropdown-toggle::after {
	display: inline-block;
	margin-left: 0.255em;
	vertical-align: 0.255em;
	content: "";
}

.bodywebsite .dropstart .dropdown-toggle::after {
	display: none;
}

.bodywebsite .dropstart .dropdown-toggle::before {
	display: inline-block;
	margin-right: 0.255em;
	vertical-align: 0.255em;
	content: "";
	border-top: 0.3em solid transparent;
	border-right: 0.3em solid;
	border-bottom: 0.3em solid transparent;
}

.bodywebsite .dropstart .dropdown-toggle:empty::after {
	margin-left: 0;
}

.bodywebsite .dropstart .dropdown-toggle::before {
	vertical-align: 0;
}

.bodywebsite .dropdown-divider {
	height: 0;
	margin: var(--bs-dropdown-divider-margin-y) 0;
	overflow: hidden;
	border-top: 1px solid var(--bs-dropdown-divider-bg);
	opacity: 1;
}

.bodywebsite .dropdown-item {
	display: block;
	width: 100%;
	padding: var(--bs-dropdown-item-padding-y) var(--bs-dropdown-item-padding-x);
	clear: both;
	font-weight: 400;
	color: var(--bs-dropdown-link-color);
	text-align: inherit;
	text-decoration: none;
	white-space: nowrap;
	background-color: transparent;
	border: 0;
}

.bodywebsite .dropdown-item:hover, .bodywebsite .dropdown-item:focus {
	color: var(--bs-dropdown-link-hover-color);
	background-color: var(--bs-dropdown-link-hover-bg);
}

.bodywebsite .dropdown-item.active, .bodywebsite .dropdown-item:active {
	color: var(--bs-dropdown-link-active-color);
	text-decoration: none;
	background-color: var(--bs-dropdown-link-active-bg);
}

.bodywebsite .dropdown-item.disabled, .bodywebsite .dropdown-item:disabled {
	color: var(--bs-dropdown-link-disabled-color);
	pointer-events: none;
	background-color: transparent;
}

.bodywebsite .dropdown-menu.show {
	display: block;
}

.bodywebsite .dropdown-header {
	display: block;
	padding: var(--bs-dropdown-header-padding-y) var(--bs-dropdown-header-padding-x);
	margin-bottom: 0;
	font-size: 0.875rem;
	color: var(--bs-dropdown-header-color);
	white-space: nowrap;
}

.bodywebsite .dropdown-item-text {
	display: block;
	padding: var(--bs-dropdown-item-padding-y) var(--bs-dropdown-item-padding-x);
	color: var(--bs-dropdown-link-color);
}

.bodywebsite .dropdown-menu-dark {
	--bs-dropdown-color: #dee2e6;
	--bs-dropdown-bg: #343a40;
	--bs-dropdown-border-color: var(--bs-border-color-translucent);
	--bs-dropdown-link-color: #dee2e6;
	--bs-dropdown-link-hover-color: #fff;
	--bs-dropdown-divider-bg: var(--bs-border-color-translucent);
	--bs-dropdown-link-hover-bg: rgba(255, 255, 255, 0.15);
	--bs-dropdown-link-active-color: #fff;
	--bs-dropdown-link-active-bg: #0d6efd;
	--bs-dropdown-link-disabled-color: #adb5bd;
	--bs-dropdown-header-color: #adb5bd;
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
	flex: 1 1 auto;
}

.bodywebsite .btn-group > .btn-check:checked + .btn,
  .bodywebsite .btn-group > .btn-check:focus + .btn,
  .bodywebsite .btn-group > .btn:hover,
  .bodywebsite .btn-group > .btn:focus,
  .bodywebsite .btn-group > .btn:active,
  .bodywebsite .btn-group > .btn.active,
  .bodywebsite .btn-group-vertical > .btn-check:checked + .btn,
  .bodywebsite .btn-group-vertical > .btn-check:focus + .btn,
  .bodywebsite .btn-group-vertical > .btn:hover,
  .bodywebsite .btn-group-vertical > .btn:focus,
  .bodywebsite .btn-group-vertical > .btn:active,
  .bodywebsite .btn-group-vertical > .btn.active {
	z-index: 1;
}

.bodywebsite .btn-toolbar {
	display: flex;
	flex-wrap: wrap;
	justify-content: flex-start;
}

.bodywebsite .btn-toolbar .input-group {
	width: auto;
}

.bodywebsite .btn-group {
	border-radius: 0.375rem;
}

.bodywebsite .btn-group > :not(.btn-check:first-child) + .btn,
  .bodywebsite .btn-group > .btn-group:not(:first-child) {
	margin-left: -1px;
}

.bodywebsite .btn-group > .btn:not(:last-child):not(.dropdown-toggle),
  .bodywebsite .btn-group > .btn.dropdown-toggle-split:first-child,
  .bodywebsite .btn-group > .btn-group:not(:last-child) > .btn {
	border-top-right-radius: 0;
	border-bottom-right-radius: 0;
}

.bodywebsite .btn-group > .btn:nth-child(n+3),
  .bodywebsite .btn-group > :not(.btn-check) + .btn,
  .bodywebsite .btn-group > .btn-group:not(:first-child) > .btn {
	border-top-left-radius: 0;
	border-bottom-left-radius: 0;
}

.bodywebsite .dropdown-toggle-split {
	padding-right: 0.5625rem;
	padding-left: 0.5625rem;
}

.bodywebsite .dropdown-toggle-split::after, .bodywebsite .dropup .dropdown-toggle-split::after, .bodywebsite .dropend .dropdown-toggle-split::after {
	margin-left: 0;
}

.bodywebsite .dropstart .dropdown-toggle-split::before {
	margin-right: 0;
}

.bodywebsite .btn-sm + .dropdown-toggle-split, .bodywebsite .btn-group-sm > .btn + .dropdown-toggle-split {
	padding-right: 0.375rem;
	padding-left: 0.375rem;
}

.bodywebsite .btn-lg + .dropdown-toggle-split, .bodywebsite .btn-group-lg > .btn + .dropdown-toggle-split {
	padding-right: 0.75rem;
	padding-left: 0.75rem;
}

.bodywebsite .btn-group-vertical {
	flex-direction: column;
	align-items: flex-start;
	justify-content: center;
}

.bodywebsite .btn-group-vertical > .btn,
  .bodywebsite .btn-group-vertical > .btn-group {
	width: 100%;
}

.bodywebsite .btn-group-vertical > .btn:not(:first-child),
  .bodywebsite .btn-group-vertical > .btn-group:not(:first-child) {
	margin-top: -1px;
}

.bodywebsite .btn-group-vertical > .btn:not(:last-child):not(.dropdown-toggle),
  .bodywebsite .btn-group-vertical > .btn-group:not(:last-child) > .btn {
	border-bottom-right-radius: 0;
	border-bottom-left-radius: 0;
}

.bodywebsite .btn-group-vertical > .btn ~ .btn,
  .bodywebsite .btn-group-vertical > .btn-group:not(:first-child) > .btn {
	border-top-left-radius: 0;
	border-top-right-radius: 0;
}

.bodywebsite .nav {
	--bs-nav-link-padding-x: 1rem;
	--bs-nav-link-padding-y: 0.5rem;
	--bs-nav-link-color: var(--bs-link-color);
	--bs-nav-link-hover-color: var(--bs-link-hover-color);
	--bs-nav-link-disabled-color: #6c757d;
	display: flex;
	flex-wrap: wrap;
	padding-left: 0;
	margin-bottom: 0;
	list-style: none;
}

.bodywebsite .nav-link {
	display: block;
	padding: var(--bs-nav-link-padding-y) var(--bs-nav-link-padding-x);
	font-size: var(--bs-nav-link-font-size);
	font-weight: var(--bs-nav-link-font-weight);
	color: var(--bs-nav-link-color);
	text-decoration: none;
	transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .nav-link {
		transition: none;
	}
}

.bodywebsite .nav-link:hover, .bodywebsite .nav-link:focus {
	color: var(--bs-nav-link-hover-color);
}

.bodywebsite .nav-link.disabled {
	color: var(--bs-nav-link-disabled-color);
	pointer-events: none;
	cursor: default;
}

.bodywebsite .nav-tabs {
	--bs-nav-tabs-border-width: 1px;
	--bs-nav-tabs-border-color: #dee2e6;
	--bs-nav-tabs-border-radius: 0.375rem;
	--bs-nav-tabs-link-hover-border-color: #e9ecef #e9ecef #dee2e6;
	--bs-nav-tabs-link-active-color: #495057;
	--bs-nav-tabs-link-active-bg: #fff;
	--bs-nav-tabs-link-active-border-color: #dee2e6 #dee2e6 #fff;
	border-bottom: var(--bs-nav-tabs-border-width) solid var(--bs-nav-tabs-border-color);
}

.bodywebsite .nav-tabs .nav-link {
	margin-bottom: calc(-1 * var(--bs-nav-tabs-border-width));
	background: none;
	border: var(--bs-nav-tabs-border-width) solid transparent;
	border-top-left-radius: var(--bs-nav-tabs-border-radius);
	border-top-right-radius: var(--bs-nav-tabs-border-radius);
}

.bodywebsite .nav-tabs .nav-link:hover, .bodywebsite .nav-tabs .nav-link:focus {
	isolation: isolate;
	border-color: var(--bs-nav-tabs-link-hover-border-color);
}

.bodywebsite .nav-tabs .nav-link.disabled, .bodywebsite .nav-tabs .nav-link:disabled {
	color: var(--bs-nav-link-disabled-color);
	background-color: transparent;
	border-color: transparent;
}

.bodywebsite .nav-tabs .nav-link.active,
  .bodywebsite .nav-tabs .nav-item.show .nav-link {
	color: var(--bs-nav-tabs-link-active-color);
	background-color: var(--bs-nav-tabs-link-active-bg);
	border-color: var(--bs-nav-tabs-link-active-border-color);
}

.bodywebsite .nav-tabs .dropdown-menu {
	margin-top: calc(-1 * var(--bs-nav-tabs-border-width));
	border-top-left-radius: 0;
	border-top-right-radius: 0;
}

.bodywebsite .nav-pills {
	--bs-nav-pills-border-radius: 0.375rem;
	--bs-nav-pills-link-active-color: #fff;
	--bs-nav-pills-link-active-bg: #0d6efd;
}

.bodywebsite .nav-pills .nav-link {
	background: none;
	border: 0;
	border-radius: var(--bs-nav-pills-border-radius);
}

.bodywebsite .nav-pills .nav-link:disabled {
	color: var(--bs-nav-link-disabled-color);
	background-color: transparent;
	border-color: transparent;
}

.bodywebsite .nav-pills .nav-link.active,
  .bodywebsite .nav-pills .show > .nav-link {
	color: var(--bs-nav-pills-link-active-color);
	background-color: var(--bs-nav-pills-link-active-bg);
}

.bodywebsite .nav-fill > .nav-link,
  .bodywebsite .nav-fill .nav-item {
	flex: 1 1 auto;
	text-align: center;
}

.bodywebsite .nav-justified > .nav-link,
  .bodywebsite .nav-justified .nav-item {
	flex-basis: 0;
	flex-grow: 1;
	text-align: center;
}

.bodywebsite .nav-fill .nav-item .nav-link,
  .bodywebsite .nav-justified .nav-item .nav-link {
	width: 100%;
}

.bodywebsite .tab-content > .tab-pane {
	display: none;
}

.bodywebsite .tab-content > .active {
	display: block;
}

.bodywebsite .navbar {
	--bs-navbar-padding-x: 0;
	--bs-navbar-padding-y: 0.5rem;
	--bs-navbar-color: rgba(0, 0, 0, 0.55);
	--bs-navbar-hover-color: rgba(0, 0, 0, 0.7);
	--bs-navbar-disabled-color: rgba(0, 0, 0, 0.3);
	--bs-navbar-active-color: rgba(0, 0, 0, 0.9);
	--bs-navbar-brand-padding-y: 0.3125rem;
	--bs-navbar-brand-margin-end: 1rem;
	--bs-navbar-brand-font-size: 1.25rem;
	--bs-navbar-brand-color: rgba(0, 0, 0, 0.9);
	--bs-navbar-brand-hover-color: rgba(0, 0, 0, 0.9);
	--bs-navbar-nav-link-padding-x: 0.5rem;
	--bs-navbar-toggler-padding-y: 0.25rem;
	--bs-navbar-toggler-padding-x: 0.75rem;
	--bs-navbar-toggler-font-size: 1.25rem;
	--bs-navbar-toggler-icon-bg: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%280, 0, 0, 0.55%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
	--bs-navbar-toggler-border-color: rgba(0, 0, 0, 0.1);
	--bs-navbar-toggler-border-radius: 0.375rem;
	--bs-navbar-toggler-focus-width: 0.25rem;
	--bs-navbar-toggler-transition: box-shadow 0.15s ease-in-out;
	position: relative;
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: space-between;
	padding: var(--bs-navbar-padding-y) var(--bs-navbar-padding-x);
}

.bodywebsite .navbar > .container,
  .bodywebsite .navbar > .container-fluid,
  .bodywebsite .navbar > .container-sm,
  .bodywebsite .navbar > .container-md,
  .bodywebsite .navbar > .container-lg,
  .bodywebsite .navbar > .container-xl,
  .bodywebsite .navbar > .container-xxl {
	display: flex;
	flex-wrap: inherit;
	align-items: center;
	justify-content: space-between;
}

.bodywebsite .navbar-brand {
	padding-top: var(--bs-navbar-brand-padding-y);
	padding-bottom: var(--bs-navbar-brand-padding-y);
	margin-right: var(--bs-navbar-brand-margin-end);
	font-size: var(--bs-navbar-brand-font-size);
	color: var(--bs-navbar-brand-color);
	text-decoration: none;
	white-space: nowrap;
}

.bodywebsite .navbar-brand:hover, .bodywebsite .navbar-brand:focus {
	color: var(--bs-navbar-brand-hover-color);
}

.bodywebsite .navbar-nav {
	--bs-nav-link-padding-x: 0;
	--bs-nav-link-padding-y: 0.5rem;
	--bs-nav-link-color: var(--bs-navbar-color);
	--bs-nav-link-hover-color: var(--bs-navbar-hover-color);
	--bs-nav-link-disabled-color: var(--bs-navbar-disabled-color);
	display: flex;
	flex-direction: column;
	padding-left: 0;
	margin-bottom: 0;
	list-style: none;
}

.bodywebsite .navbar-nav .show > .nav-link,
  .bodywebsite .navbar-nav .nav-link.active {
	color: var(--bs-navbar-active-color);
}

.bodywebsite .navbar-nav .dropdown-menu {
	position: static;
}

.bodywebsite .navbar-text {
	padding-top: 0.5rem;
	padding-bottom: 0.5rem;
	color: var(--bs-navbar-color);
}

.bodywebsite .navbar-text a,
  .bodywebsite .navbar-text a:hover,
  .bodywebsite .navbar-text a:focus {
	color: var(--bs-navbar-active-color);
}

.bodywebsite .navbar-collapse {
	flex-basis: 100%;
	flex-grow: 1;
	align-items: center;
}

.bodywebsite .navbar-toggler {
	padding: var(--bs-navbar-toggler-padding-y) var(--bs-navbar-toggler-padding-x);
	font-size: var(--bs-navbar-toggler-font-size);
	line-height: 1;
	color: var(--bs-navbar-color);
	background-color: transparent;
	border: var(--bs-border-width) solid var(--bs-navbar-toggler-border-color);
	border-radius: var(--bs-navbar-toggler-border-radius);
	transition: var(--bs-navbar-toggler-transition);
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .navbar-toggler {
		transition: none;
	}
}

.bodywebsite .navbar-toggler:hover {
	text-decoration: none;
}

.bodywebsite .navbar-toggler:focus {
	text-decoration: none;
	outline: 0;
	box-shadow: 0 0 0 var(--bs-navbar-toggler-focus-width);
}

.bodywebsite .navbar-toggler-icon {
	display: inline-block;
	width: 1.5em;
	height: 1.5em;
	vertical-align: middle;
	background-image: var(--bs-navbar-toggler-icon-bg);
	background-repeat: no-repeat;
	background-position: center;
	background-size: 100%;
}

.bodywebsite .navbar-nav-scroll {
	max-height: var(--bs-scroll-height, 75vh);
	overflow-y: auto;
}

@media (min-width: 576px) {
	.bodywebsite .navbar-expand-sm {
		flex-wrap: nowrap;
		justify-content: flex-start;
	}

	.bodywebsite .navbar-expand-sm .navbar-nav {
		flex-direction: row;
	}

	.bodywebsite .navbar-expand-sm .navbar-nav .dropdown-menu {
		position: absolute;
	}

	.bodywebsite .navbar-expand-sm .navbar-nav .nav-link {
		padding-right: var(--bs-navbar-nav-link-padding-x);
		padding-left: var(--bs-navbar-nav-link-padding-x);
	}

	.bodywebsite .navbar-expand-sm .navbar-nav-scroll {
		overflow: visible;
	}

	.bodywebsite .navbar-expand-sm .navbar-collapse {
		display: flex !important;
		flex-basis: auto;
	}

	.bodywebsite .navbar-expand-sm .navbar-toggler {
		display: none;
	}

	.bodywebsite .navbar-expand-sm .offcanvas {
		position: static;
		z-index: auto;
		flex-grow: 1;
		width: auto !important;
		height: auto !important;
		visibility: visible !important;
		background-color: transparent !important;
		border: 0 !important;
		transform: none !important;
		transition: none;
	}

	.bodywebsite .navbar-expand-sm .offcanvas .offcanvas-header {
		display: none;
	}

	.bodywebsite .navbar-expand-sm .offcanvas .offcanvas-body {
		display: flex;
		flex-grow: 0;
		padding: 0;
		overflow-y: visible;
	}
}

@media (min-width: 768px) {
	.bodywebsite .navbar-expand-md {
		flex-wrap: nowrap;
		justify-content: flex-start;
	}

	.bodywebsite .navbar-expand-md .navbar-nav {
		flex-direction: row;
	}

	.bodywebsite .navbar-expand-md .navbar-nav .dropdown-menu {
		position: absolute;
	}

	.bodywebsite .navbar-expand-md .navbar-nav .nav-link {
		padding-right: var(--bs-navbar-nav-link-padding-x);
		padding-left: var(--bs-navbar-nav-link-padding-x);
	}

	.bodywebsite .navbar-expand-md .navbar-nav-scroll {
		overflow: visible;
	}

	.bodywebsite .navbar-expand-md .navbar-collapse {
		display: flex !important;
		flex-basis: auto;
	}

	.bodywebsite .navbar-expand-md .navbar-toggler {
		display: none;
	}

	.bodywebsite .navbar-expand-md .offcanvas {
		position: static;
		z-index: auto;
		flex-grow: 1;
		width: auto !important;
		height: auto !important;
		visibility: visible !important;
		background-color: transparent !important;
		border: 0 !important;
		transform: none !important;
		transition: none;
	}

	.bodywebsite .navbar-expand-md .offcanvas .offcanvas-header {
		display: none;
	}

	.bodywebsite .navbar-expand-md .offcanvas .offcanvas-body {
		display: flex;
		flex-grow: 0;
		padding: 0;
		overflow-y: visible;
	}
}

@media (min-width: 992px) {
	.bodywebsite .navbar-expand-lg {
		flex-wrap: nowrap;
		justify-content: flex-start;
	}

	.bodywebsite .navbar-expand-lg .navbar-nav {
		flex-direction: row;
	}

	.bodywebsite .navbar-expand-lg .navbar-nav .dropdown-menu {
		position: absolute;
	}

	.bodywebsite .navbar-expand-lg .navbar-nav .nav-link {
		padding-right: var(--bs-navbar-nav-link-padding-x);
		padding-left: var(--bs-navbar-nav-link-padding-x);
	}

	.bodywebsite .navbar-expand-lg .navbar-nav-scroll {
		overflow: visible;
	}

	.bodywebsite .navbar-expand-lg .navbar-collapse {
		display: flex !important;
		flex-basis: auto;
	}

	.bodywebsite .navbar-expand-lg .navbar-toggler {
		display: none;
	}

	.bodywebsite .navbar-expand-lg .offcanvas {
		position: static;
		z-index: auto;
		flex-grow: 1;
		width: auto !important;
		height: auto !important;
		visibility: visible !important;
		background-color: transparent !important;
		border: 0 !important;
		transform: none !important;
		transition: none;
	}

	.bodywebsite .navbar-expand-lg .offcanvas .offcanvas-header {
		display: none;
	}

	.bodywebsite .navbar-expand-lg .offcanvas .offcanvas-body {
		display: flex;
		flex-grow: 0;
		padding: 0;
		overflow-y: visible;
	}
}

@media (min-width: 1200px) {
	.bodywebsite .navbar-expand-xl {
		flex-wrap: nowrap;
		justify-content: flex-start;
	}

	.bodywebsite .navbar-expand-xl .navbar-nav {
		flex-direction: row;
	}

	.bodywebsite .navbar-expand-xl .navbar-nav .dropdown-menu {
		position: absolute;
	}

	.bodywebsite .navbar-expand-xl .navbar-nav .nav-link {
		padding-right: var(--bs-navbar-nav-link-padding-x);
		padding-left: var(--bs-navbar-nav-link-padding-x);
	}

	.bodywebsite .navbar-expand-xl .navbar-nav-scroll {
		overflow: visible;
	}

	.bodywebsite .navbar-expand-xl .navbar-collapse {
		display: flex !important;
		flex-basis: auto;
	}

	.bodywebsite .navbar-expand-xl .navbar-toggler {
		display: none;
	}

	.bodywebsite .navbar-expand-xl .offcanvas {
		position: static;
		z-index: auto;
		flex-grow: 1;
		width: auto !important;
		height: auto !important;
		visibility: visible !important;
		background-color: transparent !important;
		border: 0 !important;
		transform: none !important;
		transition: none;
	}

	.bodywebsite .navbar-expand-xl .offcanvas .offcanvas-header {
		display: none;
	}

	.bodywebsite .navbar-expand-xl .offcanvas .offcanvas-body {
		display: flex;
		flex-grow: 0;
		padding: 0;
		overflow-y: visible;
	}
}

@media (min-width: 1400px) {
	.bodywebsite .navbar-expand-xxl {
		flex-wrap: nowrap;
		justify-content: flex-start;
	}

	.bodywebsite .navbar-expand-xxl .navbar-nav {
		flex-direction: row;
	}

	.bodywebsite .navbar-expand-xxl .navbar-nav .dropdown-menu {
		position: absolute;
	}

	.bodywebsite .navbar-expand-xxl .navbar-nav .nav-link {
		padding-right: var(--bs-navbar-nav-link-padding-x);
		padding-left: var(--bs-navbar-nav-link-padding-x);
	}

	.bodywebsite .navbar-expand-xxl .navbar-nav-scroll {
		overflow: visible;
	}

	.bodywebsite .navbar-expand-xxl .navbar-collapse {
		display: flex !important;
		flex-basis: auto;
	}

	.bodywebsite .navbar-expand-xxl .navbar-toggler {
		display: none;
	}

	.bodywebsite .navbar-expand-xxl .offcanvas {
		position: static;
		z-index: auto;
		flex-grow: 1;
		width: auto !important;
		height: auto !important;
		visibility: visible !important;
		background-color: transparent !important;
		border: 0 !important;
		transform: none !important;
		transition: none;
	}

	.bodywebsite .navbar-expand-xxl .offcanvas .offcanvas-header {
		display: none;
	}

	.bodywebsite .navbar-expand-xxl .offcanvas .offcanvas-body {
		display: flex;
		flex-grow: 0;
		padding: 0;
		overflow-y: visible;
	}
}

.bodywebsite .navbar-expand {
	flex-wrap: nowrap;
	justify-content: flex-start;
}

.bodywebsite .navbar-expand .navbar-nav {
	flex-direction: row;
}

.bodywebsite .navbar-expand .navbar-nav .dropdown-menu {
	position: absolute;
}

.bodywebsite .navbar-expand .navbar-nav .nav-link {
	padding-right: var(--bs-navbar-nav-link-padding-x);
	padding-left: var(--bs-navbar-nav-link-padding-x);
}

.bodywebsite .navbar-expand .navbar-nav-scroll {
	overflow: visible;
}

.bodywebsite .navbar-expand .navbar-collapse {
	display: flex !important;
	flex-basis: auto;
}

.bodywebsite .navbar-expand .navbar-toggler {
	display: none;
}

.bodywebsite .navbar-expand .offcanvas {
	position: static;
	z-index: auto;
	flex-grow: 1;
	width: auto !important;
	height: auto !important;
	visibility: visible !important;
	background-color: transparent !important;
	border: 0 !important;
	transform: none !important;
	transition: none;
}

.bodywebsite .navbar-expand .offcanvas .offcanvas-header {
	display: none;
}

.bodywebsite .navbar-expand .offcanvas .offcanvas-body {
	display: flex;
	flex-grow: 0;
	padding: 0;
	overflow-y: visible;
}

.bodywebsite .navbar-dark {
	--bs-navbar-color: rgba(255, 255, 255, 0.55);
	--bs-navbar-hover-color: rgba(255, 255, 255, 0.75);
	--bs-navbar-disabled-color: rgba(255, 255, 255, 0.25);
	--bs-navbar-active-color: #fff;
	--bs-navbar-brand-color: #fff;
	--bs-navbar-brand-hover-color: #fff;
	--bs-navbar-toggler-border-color: rgba(255, 255, 255, 0.1);
	--bs-navbar-toggler-icon-bg: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.55%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

.bodywebsite .card {
	--bs-card-spacer-y: 1rem;
	--bs-card-spacer-x: 1rem;
	--bs-card-title-spacer-y: 0.5rem;
	--bs-card-border-width: 1px;
	--bs-card-border-color: var(--bs-border-color-translucent);
	--bs-card-border-radius: 0.375rem;
	--bs-card-inner-border-radius: calc(0.375rem - 1px);
	--bs-card-cap-padding-y: 0.5rem;
	--bs-card-cap-padding-x: 1rem;
	--bs-card-cap-bg: rgba(0, 0, 0, 0.03);
	--bs-card-bg: #fff;
	--bs-card-img-overlay-padding: 1rem;
	--bs-card-group-margin: 0.75rem;
	position: relative;
	display: flex;
	flex-direction: column;
	min-width: 0;
	height: var(--bs-card-height);
	word-wrap: break-word;
	background-color: var(--bs-card-bg);
	background-clip: border-box;
	border: var(--bs-card-border-width) solid var(--bs-card-border-color);
	border-radius: var(--bs-card-border-radius);
}

.bodywebsite .card > hr {
	margin-right: 0;
	margin-left: 0;
}

.bodywebsite .card > .list-group {
	border-top: inherit;
	border-bottom: inherit;
}

.bodywebsite .card > .list-group:first-child {
	border-top-width: 0;
	border-top-left-radius: var(--bs-card-inner-border-radius);
	border-top-right-radius: var(--bs-card-inner-border-radius);
}

.bodywebsite .card > .list-group:last-child {
	border-bottom-width: 0;
	border-bottom-right-radius: var(--bs-card-inner-border-radius);
	border-bottom-left-radius: var(--bs-card-inner-border-radius);
}

.bodywebsite .card > .card-header + .list-group,
  .bodywebsite .card > .list-group + .card-footer {
	border-top: 0;
}

.bodywebsite .card-body {
	flex: 1 1 auto;
	padding: var(--bs-card-spacer-y) var(--bs-card-spacer-x);
	color: var(--bs-card-color);
}

.bodywebsite .card-title {
	margin-bottom: var(--bs-card-title-spacer-y);
}

.bodywebsite .card-subtitle {
	margin-top: calc(-0.5 * var(--bs-card-title-spacer-y));
	margin-bottom: 0;
}

.bodywebsite .card-text:last-child {
	margin-bottom: 0;
}

.bodywebsite .card-link + .card-link {
	margin-left: var(--bs-card-spacer-x);
}

.bodywebsite .card-header {
	padding: var(--bs-card-cap-padding-y) var(--bs-card-cap-padding-x);
	margin-bottom: 0;
	color: var(--bs-card-cap-color);
	background-color: var(--bs-card-cap-bg);
	border-bottom: var(--bs-card-border-width) solid var(--bs-card-border-color);
}

.bodywebsite .card-header:first-child {
	border-radius: var(--bs-card-inner-border-radius) var(--bs-card-inner-border-radius) 0 0;
}

.bodywebsite .card-footer {
	padding: var(--bs-card-cap-padding-y) var(--bs-card-cap-padding-x);
	color: var(--bs-card-cap-color);
	background-color: var(--bs-card-cap-bg);
	border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);
}

.bodywebsite .card-footer:last-child {
	border-radius: 0 0 var(--bs-card-inner-border-radius) var(--bs-card-inner-border-radius);
}

.bodywebsite .card-header-tabs {
	margin-right: calc(-0.5 * var(--bs-card-cap-padding-x));
	margin-bottom: calc(-1 * var(--bs-card-cap-padding-y));
	margin-left: calc(-0.5 * var(--bs-card-cap-padding-x));
	border-bottom: 0;
}

.bodywebsite .card-header-tabs .nav-link.active {
	background-color: var(--bs-card-bg);
	border-bottom-color: var(--bs-card-bg);
}

.bodywebsite .card-header-pills {
	margin-right: calc(-0.5 * var(--bs-card-cap-padding-x));
	margin-left: calc(-0.5 * var(--bs-card-cap-padding-x));
}

.bodywebsite .card-img-overlay {
	position: absolute;
	top: 0;
	right: 0;
	bottom: 0;
	left: 0;
	padding: var(--bs-card-img-overlay-padding);
	border-radius: var(--bs-card-inner-border-radius);
}

.bodywebsite .card-img,
  .bodywebsite .card-img-top,
  .bodywebsite .card-img-bottom {
	width: 100%;
}

.bodywebsite .card-img,
  .bodywebsite .card-img-top {
	border-top-left-radius: var(--bs-card-inner-border-radius);
	border-top-right-radius: var(--bs-card-inner-border-radius);
}

.bodywebsite .card-img,
  .bodywebsite .card-img-bottom {
	border-bottom-right-radius: var(--bs-card-inner-border-radius);
	border-bottom-left-radius: var(--bs-card-inner-border-radius);
}

.bodywebsite .card-group > .card {
	margin-bottom: var(--bs-card-group-margin);
}

@media (min-width: 576px) {
	.bodywebsite .card-group {
		display: flex;
		flex-flow: row wrap;
	}

	.bodywebsite .card-group > .card {
		flex: 1 0 0%;
		margin-bottom: 0;
	}

	.bodywebsite .card-group > .card + .card {
		margin-left: 0;
		border-left: 0;
	}

	.bodywebsite .card-group > .card:not(:last-child) {
		border-top-right-radius: 0;
		border-bottom-right-radius: 0;
	}

	.bodywebsite .card-group > .card:not(:last-child) .card-img-top,
	.bodywebsite .card-group > .card:not(:last-child) .card-header {
		border-top-right-radius: 0;
	}

	.bodywebsite .card-group > .card:not(:last-child) .card-img-bottom,
	.bodywebsite .card-group > .card:not(:last-child) .card-footer {
		border-bottom-right-radius: 0;
	}

	.bodywebsite .card-group > .card:not(:first-child) {
		border-top-left-radius: 0;
		border-bottom-left-radius: 0;
	}

	.bodywebsite .card-group > .card:not(:first-child) .card-img-top,
	.bodywebsite .card-group > .card:not(:first-child) .card-header {
		border-top-left-radius: 0;
	}

	.bodywebsite .card-group > .card:not(:first-child) .card-img-bottom,
	.bodywebsite .card-group > .card:not(:first-child) .card-footer {
		border-bottom-left-radius: 0;
	}
}

.bodywebsite .accordion {
	--bs-accordion-color: var(--bs-body-color);
	--bs-accordion-bg: #fff;
	--bs-accordion-transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out, border-radius 0.15s ease;
	--bs-accordion-border-color: var(--bs-border-color);
	--bs-accordion-border-width: 1px;
	--bs-accordion-border-radius: 0.375rem;
	--bs-accordion-inner-border-radius: calc(0.375rem - 1px);
	--bs-accordion-btn-padding-x: 1.25rem;
	--bs-accordion-btn-padding-y: 1rem;
	--bs-accordion-btn-color: var(--bs-body-color);
	--bs-accordion-btn-bg: var(--bs-accordion-bg);
	--bs-accordion-btn-icon: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='var%28--bs-body-color%29'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
	--bs-accordion-btn-icon-width: 1.25rem;
	--bs-accordion-btn-icon-transform: rotate(-180deg);
	--bs-accordion-btn-icon-transition: transform 0.2s ease-in-out;
	--bs-accordion-btn-active-icon: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%230c63e4'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
	--bs-accordion-btn-focus-border-color: #86b7fe;
	--bs-accordion-btn-focus-box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
	--bs-accordion-body-padding-x: 1.25rem;
	--bs-accordion-body-padding-y: 1rem;
	--bs-accordion-active-color: #0c63e4;
	--bs-accordion-active-bg: #e7f1ff;
}

.bodywebsite .accordion-button {
	position: relative;
	display: flex;
	align-items: center;
	width: 100%;
	padding: var(--bs-accordion-btn-padding-y) var(--bs-accordion-btn-padding-x);
	font-size: 1rem;
	color: var(--bs-accordion-btn-color);
	text-align: left;
	background-color: var(--bs-accordion-btn-bg);
	border: 0;
	border-radius: 0;
	overflow-anchor: none;
	transition: var(--bs-accordion-transition);
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .accordion-button {
		transition: none;
	}
}

.bodywebsite .accordion-button:not(.collapsed) {
	color: var(--bs-accordion-active-color);
	background-color: var(--bs-accordion-active-bg);
	box-shadow: inset 0 calc(-1 * var(--bs-accordion-border-width)) 0 var(--bs-accordion-border-color);
}

.bodywebsite .accordion-button:not(.collapsed)::after {
	background-image: var(--bs-accordion-btn-active-icon);
	transform: var(--bs-accordion-btn-icon-transform);
}

.bodywebsite .accordion-button::after {
	flex-shrink: 0;
	width: var(--bs-accordion-btn-icon-width);
	height: var(--bs-accordion-btn-icon-width);
	margin-left: auto;
	content: "";
	background-image: var(--bs-accordion-btn-icon);
	background-repeat: no-repeat;
	background-size: var(--bs-accordion-btn-icon-width);
	transition: var(--bs-accordion-btn-icon-transition);
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .accordion-button::after {
		transition: none;
	}
}

.bodywebsite .accordion-button:hover {
	z-index: 2;
}

.bodywebsite .accordion-button:focus {
	z-index: 3;
	border-color: var(--bs-accordion-btn-focus-border-color);
	outline: 0;
	box-shadow: var(--bs-accordion-btn-focus-box-shadow);
}

.bodywebsite .accordion-header {
	margin-bottom: 0;
}

.bodywebsite .accordion-item {
	color: var(--bs-accordion-color);
	background-color: var(--bs-accordion-bg);
	border: var(--bs-accordion-border-width) solid var(--bs-accordion-border-color);
}

.bodywebsite .accordion-item:first-of-type {
	border-top-left-radius: var(--bs-accordion-border-radius);
	border-top-right-radius: var(--bs-accordion-border-radius);
}

.bodywebsite .accordion-item:first-of-type .accordion-button {
	border-top-left-radius: var(--bs-accordion-inner-border-radius);
	border-top-right-radius: var(--bs-accordion-inner-border-radius);
}

.bodywebsite .accordion-item:not(:first-of-type) {
	border-top: 0;
}

.bodywebsite .accordion-item:last-of-type {
	border-bottom-right-radius: var(--bs-accordion-border-radius);
	border-bottom-left-radius: var(--bs-accordion-border-radius);
}

.bodywebsite .accordion-item:last-of-type .accordion-button.collapsed {
	border-bottom-right-radius: var(--bs-accordion-inner-border-radius);
	border-bottom-left-radius: var(--bs-accordion-inner-border-radius);
}

.bodywebsite .accordion-item:last-of-type .accordion-collapse {
	border-bottom-right-radius: var(--bs-accordion-border-radius);
	border-bottom-left-radius: var(--bs-accordion-border-radius);
}

.bodywebsite .accordion-body {
	padding: var(--bs-accordion-body-padding-y) var(--bs-accordion-body-padding-x);
}

.bodywebsite .accordion-flush .accordion-collapse {
	border-width: 0;
}

.bodywebsite .accordion-flush .accordion-item {
	border-right: 0;
	border-left: 0;
	border-radius: 0;
}

.bodywebsite .accordion-flush .accordion-item:first-child {
	border-top: 0;
}

.bodywebsite .accordion-flush .accordion-item:last-child {
	border-bottom: 0;
}

.bodywebsite .accordion-flush .accordion-item .accordion-button, .bodywebsite .accordion-flush .accordion-item .accordion-button.collapsed {
	border-radius: 0;
}

.bodywebsite .breadcrumb {
	--bs-breadcrumb-padding-x: 0;
	--bs-breadcrumb-padding-y: 0;
	--bs-breadcrumb-margin-bottom: 1rem;
	--bs-breadcrumb-divider-color: #6c757d;
	--bs-breadcrumb-item-padding-x: 0.5rem;
	--bs-breadcrumb-item-active-color: #6c757d;
	display: flex;
	flex-wrap: wrap;
	padding: var(--bs-breadcrumb-padding-y) var(--bs-breadcrumb-padding-x);
	margin-bottom: var(--bs-breadcrumb-margin-bottom);
	font-size: var(--bs-breadcrumb-font-size);
	list-style: none;
	background-color: var(--bs-breadcrumb-bg);
	border-radius: var(--bs-breadcrumb-border-radius);
}

.bodywebsite .breadcrumb-item + .breadcrumb-item {
	padding-left: var(--bs-breadcrumb-item-padding-x);
}

.bodywebsite .breadcrumb-item + .breadcrumb-item::before {
	float: left;
	padding-right: var(--bs-breadcrumb-item-padding-x);
	color: var(--bs-breadcrumb-divider-color);
	content: var(--bs-breadcrumb-divider, "/");
}

.bodywebsite .breadcrumb-item.active {
	color: var(--bs-breadcrumb-item-active-color);
}

.bodywebsite .pagination {
	--bs-pagination-padding-x: 0.75rem;
	--bs-pagination-padding-y: 0.375rem;
	--bs-pagination-font-size: 1rem;
	--bs-pagination-color: var(--bs-link-color);
	--bs-pagination-bg: #fff;
	--bs-pagination-border-width: 1px;
	--bs-pagination-border-color: #dee2e6;
	--bs-pagination-border-radius: 0.375rem;
	--bs-pagination-hover-color: var(--bs-link-hover-color);
	--bs-pagination-hover-bg: #e9ecef;
	--bs-pagination-hover-border-color: #dee2e6;
	--bs-pagination-focus-color: var(--bs-link-hover-color);
	--bs-pagination-focus-bg: #e9ecef;
	--bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
	--bs-pagination-active-color: #fff;
	--bs-pagination-active-bg: #0d6efd;
	--bs-pagination-active-border-color: #0d6efd;
	--bs-pagination-disabled-color: #6c757d;
	--bs-pagination-disabled-bg: #fff;
	--bs-pagination-disabled-border-color: #dee2e6;
	display: flex;
	padding-left: 0;
	list-style: none;
}

.bodywebsite .page-link {
	position: relative;
	display: block;
	padding: var(--bs-pagination-padding-y) var(--bs-pagination-padding-x);
	font-size: var(--bs-pagination-font-size);
	color: var(--bs-pagination-color);
	text-decoration: none;
	background-color: var(--bs-pagination-bg);
	border: var(--bs-pagination-border-width) solid var(--bs-pagination-border-color);
	transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .page-link {
		transition: none;
	}
}

.bodywebsite .page-link:hover {
	z-index: 2;
	color: var(--bs-pagination-hover-color);
	background-color: var(--bs-pagination-hover-bg);
	border-color: var(--bs-pagination-hover-border-color);
}

.bodywebsite .page-link:focus {
	z-index: 3;
	color: var(--bs-pagination-focus-color);
	background-color: var(--bs-pagination-focus-bg);
	outline: 0;
	box-shadow: var(--bs-pagination-focus-box-shadow);
}

.bodywebsite .page-link.active, .bodywebsite .active > .page-link {
	z-index: 3;
	color: var(--bs-pagination-active-color);
	background-color: var(--bs-pagination-active-bg);
	border-color: var(--bs-pagination-active-border-color);
}

.bodywebsite .page-link.disabled, .bodywebsite .disabled > .page-link {
	color: var(--bs-pagination-disabled-color);
	pointer-events: none;
	background-color: var(--bs-pagination-disabled-bg);
	border-color: var(--bs-pagination-disabled-border-color);
}

.bodywebsite .page-item:not(:first-child) .page-link {
	margin-left: -1px;
}

.bodywebsite .page-item:first-child .page-link {
	border-top-left-radius: var(--bs-pagination-border-radius);
	border-bottom-left-radius: var(--bs-pagination-border-radius);
}

.bodywebsite .page-item:last-child .page-link {
	border-top-right-radius: var(--bs-pagination-border-radius);
	border-bottom-right-radius: var(--bs-pagination-border-radius);
}

.bodywebsite .pagination-lg {
	--bs-pagination-padding-x: 1.5rem;
	--bs-pagination-padding-y: 0.75rem;
	--bs-pagination-font-size: 1.25rem;
	--bs-pagination-border-radius: 0.5rem;
}

.bodywebsite .pagination-sm {
	--bs-pagination-padding-x: 0.5rem;
	--bs-pagination-padding-y: 0.25rem;
	--bs-pagination-font-size: 0.875rem;
	--bs-pagination-border-radius: 0.25rem;
}

.bodywebsite .badge {
	--bs-badge-padding-x: 0.65em;
	--bs-badge-padding-y: 0.35em;
	--bs-badge-font-size: 0.75em;
	--bs-badge-font-weight: 700;
	--bs-badge-color: #fff;
	--bs-badge-border-radius: 0.375rem;
	display: inline-block;
	padding: var(--bs-badge-padding-y) var(--bs-badge-padding-x);
	font-size: var(--bs-badge-font-size);
	font-weight: var(--bs-badge-font-weight);
	line-height: 1;
	color: var(--bs-badge-color);
	text-align: center;
	white-space: nowrap;
	vertical-align: baseline;
	border-radius: var(--bs-badge-border-radius);
}

.bodywebsite .badge:empty {
	display: none;
}

.bodywebsite .btn .badge {
	position: relative;
	top: -1px;
}

.bodywebsite .alert {
	--bs-alert-bg: transparent;
	--bs-alert-padding-x: 1rem;
	--bs-alert-padding-y: 1rem;
	--bs-alert-margin-bottom: 1rem;
	--bs-alert-color: inherit;
	--bs-alert-border-color: transparent;
	--bs-alert-border: 1px solid var(--bs-alert-border-color);
	--bs-alert-border-radius: 0.375rem;
	position: relative;
	padding: var(--bs-alert-padding-y) var(--bs-alert-padding-x);
	margin-bottom: var(--bs-alert-margin-bottom);
	color: var(--bs-alert-color);
	background-color: var(--bs-alert-bg);
	border: var(--bs-alert-border);
	border-radius: var(--bs-alert-border-radius);
}

.bodywebsite .alert-heading {
	color: inherit;
}

.bodywebsite .alert-link {
	font-weight: 700;
}

.bodywebsite .alert-dismissible {
	padding-right: 3rem;
}

.bodywebsite .alert-dismissible .btn-close {
	position: absolute;
	top: 0;
	right: 0;
	z-index: 2;
	padding: 1.25rem 1rem;
}

.bodywebsite .alert-primary {
	--bs-alert-color: #084298;
	--bs-alert-bg: #cfe2ff;
	--bs-alert-border-color: #b6d4fe;
}

.bodywebsite .alert-primary .alert-link {
	color: #06357a;
}

.bodywebsite .alert-secondary {
	--bs-alert-color: #41464b;
	--bs-alert-bg: #e2e3e5;
	--bs-alert-border-color: #d3d6d8;
}

.bodywebsite .alert-secondary .alert-link {
	color: #34383c;
}

.bodywebsite .alert-success {
	--bs-alert-color: #0f5132;
	--bs-alert-bg: #d1e7dd;
	--bs-alert-border-color: #badbcc;
}

.bodywebsite .alert-success .alert-link {
	color: #0c4128;
}

.bodywebsite .alert-info {
	--bs-alert-color: #055160;
	--bs-alert-bg: #cff4fc;
	--bs-alert-border-color: #b6effb;
}

.bodywebsite .alert-info .alert-link {
	color: #04414d;
}

.bodywebsite .alert-warning {
	--bs-alert-color: #664d03;
	--bs-alert-bg: #fff3cd;
	--bs-alert-border-color: #ffecb5;
}

.bodywebsite .alert-warning .alert-link {
	color: #523e02;
}

.bodywebsite .alert-danger {
	--bs-alert-color: #842029;
	--bs-alert-bg: #f8d7da;
	--bs-alert-border-color: #f5c2c7;
}

.bodywebsite .alert-danger .alert-link {
	color: #6a1a21;
}

.bodywebsite .alert-light {
	--bs-alert-color: #636464;
	--bs-alert-bg: #fefefe;
	--bs-alert-border-color: #fdfdfe;
}

.bodywebsite .alert-light .alert-link {
	color: #4f5050;
}

.bodywebsite .alert-dark {
	--bs-alert-color: #141619;
	--bs-alert-bg: #d3d3d4;
	--bs-alert-border-color: #bcbebf;
}

.bodywebsite .alert-dark .alert-link {
	color: #101214;
}

@-webkit-keyframes progress-bar-stripes {
	0% {
		background-position-x: 1rem;
	}
}

@keyframes progress-bar-stripes {
	0% {
		background-position-x: 1rem;
	}
}

.bodywebsite .progress {
	--bs-progress-height: 1rem;
	--bs-progress-font-size: 0.75rem;
	--bs-progress-bg: #e9ecef;
	--bs-progress-border-radius: 0.375rem;
	--bs-progress-box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.075);
	--bs-progress-bar-color: #fff;
	--bs-progress-bar-bg: #0d6efd;
	--bs-progress-bar-transition: width 0.6s ease;
	display: flex;
	height: var(--bs-progress-height);
	overflow: hidden;
	font-size: var(--bs-progress-font-size);
	background-color: var(--bs-progress-bg);
	border-radius: var(--bs-progress-border-radius);
}

.bodywebsite .progress-bar {
	display: flex;
	flex-direction: column;
	justify-content: center;
	overflow: hidden;
	color: var(--bs-progress-bar-color);
	text-align: center;
	white-space: nowrap;
	background-color: var(--bs-progress-bar-bg);
	transition: var(--bs-progress-bar-transition);
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .progress-bar {
		transition: none;
	}
}

.bodywebsite .progress-bar-striped {
	background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
	background-size: var(--bs-progress-height) var(--bs-progress-height);
}

.bodywebsite .progress-bar-animated {
	-webkit-animation: 1s linear infinite progress-bar-stripes;
	animation: 1s linear infinite progress-bar-stripes;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .progress-bar-animated {
		-webkit-animation: none;
		animation: none;
	}
}

.bodywebsite .list-group {
	--bs-list-group-color: #212529;
	--bs-list-group-bg: #fff;
	--bs-list-group-border-color: rgba(0, 0, 0, 0.125);
	--bs-list-group-border-width: 1px;
	--bs-list-group-border-radius: 0.375rem;
	--bs-list-group-item-padding-x: 1rem;
	--bs-list-group-item-padding-y: 0.5rem;
	--bs-list-group-action-color: #495057;
	--bs-list-group-action-hover-color: #495057;
	--bs-list-group-action-hover-bg: #f8f9fa;
	--bs-list-group-action-active-color: #212529;
	--bs-list-group-action-active-bg: #e9ecef;
	--bs-list-group-disabled-color: #6c757d;
	--bs-list-group-disabled-bg: #fff;
	--bs-list-group-active-color: #fff;
	--bs-list-group-active-bg: #0d6efd;
	--bs-list-group-active-border-color: #0d6efd;
	display: flex;
	flex-direction: column;
	padding-left: 0;
	margin-bottom: 0;
	border-radius: var(--bs-list-group-border-radius);
}

.bodywebsite .list-group-numbered {
	list-style-type: none;
	counter-reset: section;
}

.bodywebsite .list-group-numbered > .list-group-item::before {
	content: counters(section, ".") ". ";
	counter-increment: section;
}

.bodywebsite .list-group-item-action {
	width: 100%;
	color: var(--bs-list-group-action-color);
	text-align: inherit;
}

.bodywebsite .list-group-item-action:hover, .bodywebsite .list-group-item-action:focus {
	z-index: 1;
	color: var(--bs-list-group-action-hover-color);
	text-decoration: none;
	background-color: var(--bs-list-group-action-hover-bg);
}

.bodywebsite .list-group-item-action:active {
	color: var(--bs-list-group-action-active-color);
	background-color: var(--bs-list-group-action-active-bg);
}

.bodywebsite .list-group-item {
	position: relative;
	display: block;
	padding: var(--bs-list-group-item-padding-y) var(--bs-list-group-item-padding-x);
	color: var(--bs-list-group-color);
	text-decoration: none;
	background-color: var(--bs-list-group-bg);
	border: var(--bs-list-group-border-width) solid var(--bs-list-group-border-color);
}

.bodywebsite .list-group-item:first-child {
	border-top-left-radius: inherit;
	border-top-right-radius: inherit;
}

.bodywebsite .list-group-item:last-child {
	border-bottom-right-radius: inherit;
	border-bottom-left-radius: inherit;
}

.bodywebsite .list-group-item.disabled, .bodywebsite .list-group-item:disabled {
	color: var(--bs-list-group-disabled-color);
	pointer-events: none;
	background-color: var(--bs-list-group-disabled-bg);
}

.bodywebsite .list-group-item.active {
	z-index: 2;
	color: var(--bs-list-group-active-color);
	background-color: var(--bs-list-group-active-bg);
	border-color: var(--bs-list-group-active-border-color);
}

.bodywebsite .list-group-item + .list-group-item {
	border-top-width: 0;
}

.bodywebsite .list-group-item + .list-group-item.active {
	margin-top: calc(-1 * var(--bs-list-group-border-width));
	border-top-width: var(--bs-list-group-border-width);
}

.bodywebsite .list-group-horizontal {
	flex-direction: row;
}

.bodywebsite .list-group-horizontal > .list-group-item:first-child:not(:last-child) {
	border-bottom-left-radius: var(--bs-list-group-border-radius);
	border-top-right-radius: 0;
}

.bodywebsite .list-group-horizontal > .list-group-item:last-child:not(:first-child) {
	border-top-right-radius: var(--bs-list-group-border-radius);
	border-bottom-left-radius: 0;
}

.bodywebsite .list-group-horizontal > .list-group-item.active {
	margin-top: 0;
}

.bodywebsite .list-group-horizontal > .list-group-item + .list-group-item {
	border-top-width: var(--bs-list-group-border-width);
	border-left-width: 0;
}

.bodywebsite .list-group-horizontal > .list-group-item + .list-group-item.active {
	margin-left: calc(-1 * var(--bs-list-group-border-width));
	border-left-width: var(--bs-list-group-border-width);
}

@media (min-width: 576px) {
	.bodywebsite .list-group-horizontal-sm {
		flex-direction: row;
	}

	.bodywebsite .list-group-horizontal-sm > .list-group-item:first-child:not(:last-child) {
		border-bottom-left-radius: var(--bs-list-group-border-radius);
		border-top-right-radius: 0;
	}

	.bodywebsite .list-group-horizontal-sm > .list-group-item:last-child:not(:first-child) {
		border-top-right-radius: var(--bs-list-group-border-radius);
		border-bottom-left-radius: 0;
	}

	.bodywebsite .list-group-horizontal-sm > .list-group-item.active {
		margin-top: 0;
	}

	.bodywebsite .list-group-horizontal-sm > .list-group-item + .list-group-item {
		border-top-width: var(--bs-list-group-border-width);
		border-left-width: 0;
	}

	.bodywebsite .list-group-horizontal-sm > .list-group-item + .list-group-item.active {
		margin-left: calc(-1 * var(--bs-list-group-border-width));
		border-left-width: var(--bs-list-group-border-width);
	}
}

@media (min-width: 768px) {
	.bodywebsite .list-group-horizontal-md {
		flex-direction: row;
	}

	.bodywebsite .list-group-horizontal-md > .list-group-item:first-child:not(:last-child) {
		border-bottom-left-radius: var(--bs-list-group-border-radius);
		border-top-right-radius: 0;
	}

	.bodywebsite .list-group-horizontal-md > .list-group-item:last-child:not(:first-child) {
		border-top-right-radius: var(--bs-list-group-border-radius);
		border-bottom-left-radius: 0;
	}

	.bodywebsite .list-group-horizontal-md > .list-group-item.active {
		margin-top: 0;
	}

	.bodywebsite .list-group-horizontal-md > .list-group-item + .list-group-item {
		border-top-width: var(--bs-list-group-border-width);
		border-left-width: 0;
	}

	.bodywebsite .list-group-horizontal-md > .list-group-item + .list-group-item.active {
		margin-left: calc(-1 * var(--bs-list-group-border-width));
		border-left-width: var(--bs-list-group-border-width);
	}
}

@media (min-width: 992px) {
	.bodywebsite .list-group-horizontal-lg {
		flex-direction: row;
	}

	.bodywebsite .list-group-horizontal-lg > .list-group-item:first-child:not(:last-child) {
		border-bottom-left-radius: var(--bs-list-group-border-radius);
		border-top-right-radius: 0;
	}

	.bodywebsite .list-group-horizontal-lg > .list-group-item:last-child:not(:first-child) {
		border-top-right-radius: var(--bs-list-group-border-radius);
		border-bottom-left-radius: 0;
	}

	.bodywebsite .list-group-horizontal-lg > .list-group-item.active {
		margin-top: 0;
	}

	.bodywebsite .list-group-horizontal-lg > .list-group-item + .list-group-item {
		border-top-width: var(--bs-list-group-border-width);
		border-left-width: 0;
	}

	.bodywebsite .list-group-horizontal-lg > .list-group-item + .list-group-item.active {
		margin-left: calc(-1 * var(--bs-list-group-border-width));
		border-left-width: var(--bs-list-group-border-width);
	}
}

@media (min-width: 1200px) {
	.bodywebsite .list-group-horizontal-xl {
		flex-direction: row;
	}

	.bodywebsite .list-group-horizontal-xl > .list-group-item:first-child:not(:last-child) {
		border-bottom-left-radius: var(--bs-list-group-border-radius);
		border-top-right-radius: 0;
	}

	.bodywebsite .list-group-horizontal-xl > .list-group-item:last-child:not(:first-child) {
		border-top-right-radius: var(--bs-list-group-border-radius);
		border-bottom-left-radius: 0;
	}

	.bodywebsite .list-group-horizontal-xl > .list-group-item.active {
		margin-top: 0;
	}

	.bodywebsite .list-group-horizontal-xl > .list-group-item + .list-group-item {
		border-top-width: var(--bs-list-group-border-width);
		border-left-width: 0;
	}

	.bodywebsite .list-group-horizontal-xl > .list-group-item + .list-group-item.active {
		margin-left: calc(-1 * var(--bs-list-group-border-width));
		border-left-width: var(--bs-list-group-border-width);
	}
}

@media (min-width: 1400px) {
	.bodywebsite .list-group-horizontal-xxl {
		flex-direction: row;
	}

	.bodywebsite .list-group-horizontal-xxl > .list-group-item:first-child:not(:last-child) {
		border-bottom-left-radius: var(--bs-list-group-border-radius);
		border-top-right-radius: 0;
	}

	.bodywebsite .list-group-horizontal-xxl > .list-group-item:last-child:not(:first-child) {
		border-top-right-radius: var(--bs-list-group-border-radius);
		border-bottom-left-radius: 0;
	}

	.bodywebsite .list-group-horizontal-xxl > .list-group-item.active {
		margin-top: 0;
	}

	.bodywebsite .list-group-horizontal-xxl > .list-group-item + .list-group-item {
		border-top-width: var(--bs-list-group-border-width);
		border-left-width: 0;
	}

	.bodywebsite .list-group-horizontal-xxl > .list-group-item + .list-group-item.active {
		margin-left: calc(-1 * var(--bs-list-group-border-width));
		border-left-width: var(--bs-list-group-border-width);
	}
}

.bodywebsite .list-group-flush {
	border-radius: 0;
}

.bodywebsite .list-group-flush > .list-group-item {
	border-width: 0 0 var(--bs-list-group-border-width);
}

.bodywebsite .list-group-flush > .list-group-item:last-child {
	border-bottom-width: 0;
}

.bodywebsite .list-group-item-primary {
	color: #084298;
	background-color: #cfe2ff;
}

.bodywebsite .list-group-item-primary.list-group-item-action:hover, .bodywebsite .list-group-item-primary.list-group-item-action:focus {
	color: #084298;
	background-color: #bacbe6;
}

.bodywebsite .list-group-item-primary.list-group-item-action.active {
	color: #fff;
	background-color: #084298;
	border-color: #084298;
}

.bodywebsite .list-group-item-secondary {
	color: #41464b;
	background-color: #e2e3e5;
}

.bodywebsite .list-group-item-secondary.list-group-item-action:hover, .bodywebsite .list-group-item-secondary.list-group-item-action:focus {
	color: #41464b;
	background-color: #cbccce;
}

.bodywebsite .list-group-item-secondary.list-group-item-action.active {
	color: #fff;
	background-color: #41464b;
	border-color: #41464b;
}

.bodywebsite .list-group-item-success {
	color: #0f5132;
	background-color: #d1e7dd;
}

.bodywebsite .list-group-item-success.list-group-item-action:hover, .bodywebsite .list-group-item-success.list-group-item-action:focus {
	color: #0f5132;
	background-color: #bcd0c7;
}

.bodywebsite .list-group-item-success.list-group-item-action.active {
	color: #fff;
	background-color: #0f5132;
	border-color: #0f5132;
}

.bodywebsite .list-group-item-info {
	color: #055160;
	background-color: #cff4fc;
}

.bodywebsite .list-group-item-info.list-group-item-action:hover, .bodywebsite .list-group-item-info.list-group-item-action:focus {
	color: #055160;
	background-color: #badce3;
}

.bodywebsite .list-group-item-info.list-group-item-action.active {
	color: #fff;
	background-color: #055160;
	border-color: #055160;
}

.bodywebsite .list-group-item-warning {
	color: #664d03;
	background-color: #fff3cd;
}

.bodywebsite .list-group-item-warning.list-group-item-action:hover, .bodywebsite .list-group-item-warning.list-group-item-action:focus {
	color: #664d03;
	background-color: #e6dbb9;
}

.bodywebsite .list-group-item-warning.list-group-item-action.active {
	color: #fff;
	background-color: #664d03;
	border-color: #664d03;
}

.bodywebsite .list-group-item-danger {
	color: #842029;
	background-color: #f8d7da;
}

.bodywebsite .list-group-item-danger.list-group-item-action:hover, .bodywebsite .list-group-item-danger.list-group-item-action:focus {
	color: #842029;
	background-color: #dfc2c4;
}

.bodywebsite .list-group-item-danger.list-group-item-action.active {
	color: #fff;
	background-color: #842029;
	border-color: #842029;
}

.bodywebsite .list-group-item-light {
	color: #636464;
	background-color: #fefefe;
}

.bodywebsite .list-group-item-light.list-group-item-action:hover, .bodywebsite .list-group-item-light.list-group-item-action:focus {
	color: #636464;
	background-color: #e5e5e5;
}

.bodywebsite .list-group-item-light.list-group-item-action.active {
	color: #fff;
	background-color: #636464;
	border-color: #636464;
}

.bodywebsite .list-group-item-dark {
	color: #141619;
	background-color: #d3d3d4;
}

.bodywebsite .list-group-item-dark.list-group-item-action:hover, .bodywebsite .list-group-item-dark.list-group-item-action:focus {
	color: #141619;
	background-color: #bebebf;
}

.bodywebsite .list-group-item-dark.list-group-item-action.active {
	color: #fff;
	background-color: #141619;
	border-color: #141619;
}

.bodywebsite .btn-close {
	box-sizing: content-box;
	width: 1em;
	height: 1em;
	padding: 0.25em 0.25em;
	color: #000;
	background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
	border: 0;
	border-radius: 0.375rem;
	opacity: 0.5;
}

.bodywebsite .btn-close:hover {
	color: #000;
	text-decoration: none;
	opacity: 0.75;
}

.bodywebsite .btn-close:focus {
	outline: 0;
	box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
	opacity: 1;
}

.bodywebsite .btn-close:disabled, .bodywebsite .btn-close.disabled {
	pointer-events: none;
	-webkit-user-select: none;
	-moz-user-select: none;
	user-select: none;
	opacity: 0.25;
}

.bodywebsite .btn-close-white {
	filter: invert(1) grayscale(100%) brightness(200%);
}

.bodywebsite .toast {
	--bs-toast-zindex: 1090;
	--bs-toast-padding-x: 0.75rem;
	--bs-toast-padding-y: 0.5rem;
	--bs-toast-spacing: 1.5rem;
	--bs-toast-max-width: 350px;
	--bs-toast-font-size: 0.875rem;
	--bs-toast-bg: rgba(255, 255, 255, 0.85);
	--bs-toast-border-width: 1px;
	--bs-toast-border-color: var(--bs-border-color-translucent);
	--bs-toast-border-radius: 0.375rem;
	--bs-toast-box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
	--bs-toast-header-color: #6c757d;
	--bs-toast-header-bg: rgba(255, 255, 255, 0.85);
	--bs-toast-header-border-color: rgba(0, 0, 0, 0.05);
	width: var(--bs-toast-max-width);
	max-width: 100%;
	font-size: var(--bs-toast-font-size);
	color: var(--bs-toast-color);
	pointer-events: auto;
	background-color: var(--bs-toast-bg);
	background-clip: padding-box;
	border: var(--bs-toast-border-width) solid var(--bs-toast-border-color);
	box-shadow: var(--bs-toast-box-shadow);
	border-radius: var(--bs-toast-border-radius);
}

.bodywebsite .toast.showing {
	opacity: 0;
}

.bodywebsite .toast:not(.show) {
	display: none;
}

.bodywebsite .toast-container {
	position: absolute;
	z-index: var(--bs-toast-zindex);
	width: -webkit-max-content;
	width: -moz-max-content;
	width: max-content;
	max-width: 100%;
	pointer-events: none;
}

.bodywebsite .toast-container > :not(:last-child) {
	margin-bottom: var(--bs-toast-spacing);
}

.bodywebsite .toast-header {
	display: flex;
	align-items: center;
	padding: var(--bs-toast-padding-y) var(--bs-toast-padding-x);
	color: var(--bs-toast-header-color);
	background-color: var(--bs-toast-header-bg);
	background-clip: padding-box;
	border-bottom: var(--bs-toast-border-width) solid var(--bs-toast-header-border-color);
	border-top-left-radius: calc(var(--bs-toast-border-radius) - var(--bs-toast-border-width));
	border-top-right-radius: calc(var(--bs-toast-border-radius) - var(--bs-toast-border-width));
}

.bodywebsite .toast-header .btn-close {
	margin-right: calc(-0.5 * var(--bs-toast-padding-x));
	margin-left: var(--bs-toast-padding-x);
}

.bodywebsite .toast-body {
	padding: var(--bs-toast-padding-x);
	word-wrap: break-word;
}

.bodywebsite .modal {
	--bs-modal-zindex: 1055;
	--bs-modal-width: 500px;
	--bs-modal-padding: 1rem;
	--bs-modal-margin: 0.5rem;
	--bs-modal-bg: #fff;
	--bs-modal-border-color: var(--bs-border-color-translucent);
	--bs-modal-border-width: 1px;
	--bs-modal-border-radius: 0.5rem;
	--bs-modal-box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
	--bs-modal-inner-border-radius: calc(0.5rem - 1px);
	--bs-modal-header-padding-x: 1rem;
	--bs-modal-header-padding-y: 1rem;
	--bs-modal-header-padding: 1rem 1rem;
	--bs-modal-header-border-color: var(--bs-border-color);
	--bs-modal-header-border-width: 1px;
	--bs-modal-title-line-height: 1.5;
	--bs-modal-footer-gap: 0.5rem;
	--bs-modal-footer-border-color: var(--bs-border-color);
	--bs-modal-footer-border-width: 1px;
	position: fixed;
	top: 0;
	left: 0;
	z-index: var(--bs-modal-zindex);
	display: none;
	width: 100%;
	height: 100%;
	overflow-x: hidden;
	overflow-y: auto;
	outline: 0;
}

.bodywebsite .modal-dialog {
	position: relative;
	width: auto;
	margin: var(--bs-modal-margin);
	pointer-events: none;
}

.bodywebsite .modal.fade .modal-dialog {
	transition: transform 0.3s ease-out;
	transform: translate(0, -50px);
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .modal.fade .modal-dialog {
		transition: none;
	}
}

.bodywebsite .modal.show .modal-dialog {
	transform: none;
}

.bodywebsite .modal.modal-static .modal-dialog {
	transform: scale(1.02);
}

.bodywebsite .modal-dialog-scrollable {
	height: calc(100% - var(--bs-modal-margin) * 2);
}

.bodywebsite .modal-dialog-scrollable .modal-content {
	max-height: 100%;
	overflow: hidden;
}

.bodywebsite .modal-dialog-scrollable .modal-body {
	overflow-y: auto;
}

.bodywebsite .modal-dialog-centered {
	display: flex;
	align-items: center;
	min-height: calc(100% - var(--bs-modal-margin) * 2);
}

.bodywebsite .modal-content {
	position: relative;
	display: flex;
	flex-direction: column;
	width: 100%;
	color: var(--bs-modal-color);
	pointer-events: auto;
	background-color: var(--bs-modal-bg);
	background-clip: padding-box;
	border: var(--bs-modal-border-width) solid var(--bs-modal-border-color);
	border-radius: var(--bs-modal-border-radius);
	outline: 0;
}

.bodywebsite .modal-backdrop {
	--bs-backdrop-zindex: 1050;
	--bs-backdrop-bg: #000;
	--bs-backdrop-opacity: 0.5;
	position: fixed;
	top: 0;
	left: 0;
	z-index: var(--bs-backdrop-zindex);
	width: 100vw;
	height: 100vh;
	background-color: var(--bs-backdrop-bg);
}

.bodywebsite .modal-backdrop.fade {
	opacity: 0;
}

.bodywebsite .modal-backdrop.show {
	opacity: var(--bs-backdrop-opacity);
}

.bodywebsite .modal-header {
	display: flex;
	flex-shrink: 0;
	align-items: center;
	justify-content: space-between;
	padding: var(--bs-modal-header-padding);
	border-bottom: var(--bs-modal-header-border-width) solid var(--bs-modal-header-border-color);
	border-top-left-radius: var(--bs-modal-inner-border-radius);
	border-top-right-radius: var(--bs-modal-inner-border-radius);
}

.bodywebsite .modal-header .btn-close {
	padding: calc(var(--bs-modal-header-padding-y) * 0.5) calc(var(--bs-modal-header-padding-x) * 0.5);
	margin: calc(-0.5 * var(--bs-modal-header-padding-y)) calc(-0.5 * var(--bs-modal-header-padding-x)) calc(-0.5 * var(--bs-modal-header-padding-y)) auto;
}

.bodywebsite .modal-title {
	margin-bottom: 0;
	line-height: var(--bs-modal-title-line-height);
}

.bodywebsite .modal-body {
	position: relative;
	flex: 1 1 auto;
	padding: var(--bs-modal-padding);
}

.bodywebsite .modal-footer {
	display: flex;
	flex-shrink: 0;
	flex-wrap: wrap;
	align-items: center;
	justify-content: flex-end;
	padding: calc(var(--bs-modal-padding) - var(--bs-modal-footer-gap) * 0.5);
	background-color: var(--bs-modal-footer-bg);
	border-top: var(--bs-modal-footer-border-width) solid var(--bs-modal-footer-border-color);
	border-bottom-right-radius: var(--bs-modal-inner-border-radius);
	border-bottom-left-radius: var(--bs-modal-inner-border-radius);
}

.bodywebsite .modal-footer > * {
	margin: calc(var(--bs-modal-footer-gap) * 0.5);
}

@media (min-width: 576px) {
	.bodywebsite .modal {
		--bs-modal-margin: 1.75rem;
		--bs-modal-box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
	}

	.bodywebsite .modal-dialog {
		max-width: var(--bs-modal-width);
		margin-right: auto;
		margin-left: auto;
	}

	.bodywebsite .modal-sm {
		--bs-modal-width: 300px;
	}
}

@media (min-width: 992px) {
	.bodywebsite .modal-lg,
	.bodywebsite .modal-xl {
		--bs-modal-width: 800px;
	}
}

@media (min-width: 1200px) {
	.bodywebsite .modal-xl {
		--bs-modal-width: 1140px;
	}
}

.bodywebsite .modal-fullscreen {
	width: 100vw;
	max-width: none;
	height: 100%;
	margin: 0;
}

.bodywebsite .modal-fullscreen .modal-content {
	height: 100%;
	border: 0;
	border-radius: 0;
}

.bodywebsite .modal-fullscreen .modal-header,
  .bodywebsite .modal-fullscreen .modal-footer {
	border-radius: 0;
}

.bodywebsite .modal-fullscreen .modal-body {
	overflow-y: auto;
}

@media (max-width: 575.98px) {
	.bodywebsite .modal-fullscreen-sm-down {
		width: 100vw;
		max-width: none;
		height: 100%;
		margin: 0;
	}

	.bodywebsite .modal-fullscreen-sm-down .modal-content {
		height: 100%;
		border: 0;
		border-radius: 0;
	}

	.bodywebsite .modal-fullscreen-sm-down .modal-header,
	.bodywebsite .modal-fullscreen-sm-down .modal-footer {
		border-radius: 0;
	}

	.bodywebsite .modal-fullscreen-sm-down .modal-body {
		overflow-y: auto;
	}
}

@media (max-width: 767.98px) {
	.bodywebsite .modal-fullscreen-md-down {
		width: 100vw;
		max-width: none;
		height: 100%;
		margin: 0;
	}

	.bodywebsite .modal-fullscreen-md-down .modal-content {
		height: 100%;
		border: 0;
		border-radius: 0;
	}

	.bodywebsite .modal-fullscreen-md-down .modal-header,
	.bodywebsite .modal-fullscreen-md-down .modal-footer {
		border-radius: 0;
	}

	.bodywebsite .modal-fullscreen-md-down .modal-body {
		overflow-y: auto;
	}
}

@media (max-width: 991.98px) {
	.bodywebsite .modal-fullscreen-lg-down {
		width: 100vw;
		max-width: none;
		height: 100%;
		margin: 0;
	}

	.bodywebsite .modal-fullscreen-lg-down .modal-content {
		height: 100%;
		border: 0;
		border-radius: 0;
	}

	.bodywebsite .modal-fullscreen-lg-down .modal-header,
	.bodywebsite .modal-fullscreen-lg-down .modal-footer {
		border-radius: 0;
	}

	.bodywebsite .modal-fullscreen-lg-down .modal-body {
		overflow-y: auto;
	}
}

@media (max-width: 1199.98px) {
	.bodywebsite .modal-fullscreen-xl-down {
		width: 100vw;
		max-width: none;
		height: 100%;
		margin: 0;
	}

	.bodywebsite .modal-fullscreen-xl-down .modal-content {
		height: 100%;
		border: 0;
		border-radius: 0;
	}

	.bodywebsite .modal-fullscreen-xl-down .modal-header,
	.bodywebsite .modal-fullscreen-xl-down .modal-footer {
		border-radius: 0;
	}

	.bodywebsite .modal-fullscreen-xl-down .modal-body {
		overflow-y: auto;
	}
}

@media (max-width: 1399.98px) {
	.bodywebsite .modal-fullscreen-xxl-down {
		width: 100vw;
		max-width: none;
		height: 100%;
		margin: 0;
	}

	.bodywebsite .modal-fullscreen-xxl-down .modal-content {
		height: 100%;
		border: 0;
		border-radius: 0;
	}

	.bodywebsite .modal-fullscreen-xxl-down .modal-header,
	.bodywebsite .modal-fullscreen-xxl-down .modal-footer {
		border-radius: 0;
	}

	.bodywebsite .modal-fullscreen-xxl-down .modal-body {
		overflow-y: auto;
	}
}

.bodywebsite .tooltip {
	--bs-tooltip-zindex: 1080;
	--bs-tooltip-max-width: 200px;
	--bs-tooltip-padding-x: 0.5rem;
	--bs-tooltip-padding-y: 0.25rem;
	--bs-tooltip-font-size: 0.875rem;
	--bs-tooltip-color: #fff;
	--bs-tooltip-bg: #000;
	--bs-tooltip-border-radius: 0.375rem;
	--bs-tooltip-opacity: 0.9;
	--bs-tooltip-arrow-width: 0.8rem;
	--bs-tooltip-arrow-height: 0.4rem;
	z-index: var(--bs-tooltip-zindex);
	display: block;
	padding: var(--bs-tooltip-arrow-height);
	margin: var(--bs-tooltip-margin);
	font-family: var(--bs-font-sans-serif);
	font-style: normal;
	font-weight: 400;
	line-height: 1.5;
	text-align: left;
	text-align: start;
	text-decoration: none;
	text-shadow: none;
	text-transform: none;
	letter-spacing: normal;
	word-break: normal;
	white-space: normal;
	word-spacing: normal;
	line-break: auto;
	font-size: var(--bs-tooltip-font-size);
	word-wrap: break-word;
	opacity: 0;
}

.bodywebsite .tooltip.show {
	opacity: var(--bs-tooltip-opacity);
}

.bodywebsite .tooltip .tooltip-arrow {
	display: block;
	width: var(--bs-tooltip-arrow-width);
	height: var(--bs-tooltip-arrow-height);
}

.bodywebsite .tooltip .tooltip-arrow::before {
	position: absolute;
	content: "";
	border-color: transparent;
	border-style: solid;
}

.bodywebsite .bs-tooltip-top .tooltip-arrow, .bodywebsite .bs-tooltip-auto[data-popper-placement^=top] .tooltip-arrow {
	bottom: 0;
}

.bodywebsite .bs-tooltip-top .tooltip-arrow::before, .bodywebsite .bs-tooltip-auto[data-popper-placement^=top] .tooltip-arrow::before {
	top: -1px;
	border-width: var(--bs-tooltip-arrow-height) calc(var(--bs-tooltip-arrow-width) * 0.5) 0;
	border-top-color: var(--bs-tooltip-bg);
}

.bodywebsite .bs-tooltip-end .tooltip-arrow, .bodywebsite .bs-tooltip-auto[data-popper-placement^=right] .tooltip-arrow {
	left: 0;
	width: var(--bs-tooltip-arrow-height);
	height: var(--bs-tooltip-arrow-width);
}

.bodywebsite .bs-tooltip-end .tooltip-arrow::before, .bodywebsite .bs-tooltip-auto[data-popper-placement^=right] .tooltip-arrow::before {
	right: -1px;
	border-width: calc(var(--bs-tooltip-arrow-width) * 0.5) var(--bs-tooltip-arrow-height) calc(var(--bs-tooltip-arrow-width) * 0.5) 0;
	border-right-color: var(--bs-tooltip-bg);
}

.bodywebsite .bs-tooltip-bottom .tooltip-arrow, .bodywebsite .bs-tooltip-auto[data-popper-placement^=bottom] .tooltip-arrow {
	top: 0;
}

.bodywebsite .bs-tooltip-bottom .tooltip-arrow::before, .bodywebsite .bs-tooltip-auto[data-popper-placement^=bottom] .tooltip-arrow::before {
	bottom: -1px;
	border-width: 0 calc(var(--bs-tooltip-arrow-width) * 0.5) var(--bs-tooltip-arrow-height);
	border-bottom-color: var(--bs-tooltip-bg);
}

.bodywebsite .bs-tooltip-start .tooltip-arrow, .bodywebsite .bs-tooltip-auto[data-popper-placement^=left] .tooltip-arrow {
	right: 0;
	width: var(--bs-tooltip-arrow-height);
	height: var(--bs-tooltip-arrow-width);
}

.bodywebsite .bs-tooltip-start .tooltip-arrow::before, .bodywebsite .bs-tooltip-auto[data-popper-placement^=left] .tooltip-arrow::before {
	left: -1px;
	border-width: calc(var(--bs-tooltip-arrow-width) * 0.5) 0 calc(var(--bs-tooltip-arrow-width) * 0.5) var(--bs-tooltip-arrow-height);
	border-left-color: var(--bs-tooltip-bg);
}

.bodywebsite .tooltip-inner {
	max-width: var(--bs-tooltip-max-width);
	padding: var(--bs-tooltip-padding-y) var(--bs-tooltip-padding-x);
	color: var(--bs-tooltip-color);
	text-align: center;
	background-color: var(--bs-tooltip-bg);
	border-radius: var(--bs-tooltip-border-radius);
}

.bodywebsite .popover {
	--bs-popover-zindex: 1070;
	--bs-popover-max-width: 276px;
	--bs-popover-font-size: 0.875rem;
	--bs-popover-bg: #fff;
	--bs-popover-border-width: 1px;
	--bs-popover-border-color: var(--bs-border-color-translucent);
	--bs-popover-border-radius: 0.5rem;
	--bs-popover-inner-border-radius: calc(0.5rem - 1px);
	--bs-popover-box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
	--bs-popover-header-padding-x: 1rem;
	--bs-popover-header-padding-y: 0.5rem;
	--bs-popover-header-font-size: 1rem;
	--bs-popover-header-bg: #f0f0f0;
	--bs-popover-body-padding-x: 1rem;
	--bs-popover-body-padding-y: 1rem;
	--bs-popover-body-color: #212529;
	--bs-popover-arrow-width: 1rem;
	--bs-popover-arrow-height: 0.5rem;
	--bs-popover-arrow-border: var(--bs-popover-border-color);
	z-index: var(--bs-popover-zindex);
	display: block;
	max-width: var(--bs-popover-max-width);
	font-family: var(--bs-font-sans-serif);
	font-style: normal;
	font-weight: 400;
	line-height: 1.5;
	text-align: left;
	text-align: start;
	text-decoration: none;
	text-shadow: none;
	text-transform: none;
	letter-spacing: normal;
	word-break: normal;
	white-space: normal;
	word-spacing: normal;
	line-break: auto;
	font-size: var(--bs-popover-font-size);
	word-wrap: break-word;
	background-color: var(--bs-popover-bg);
	background-clip: padding-box;
	border: var(--bs-popover-border-width) solid var(--bs-popover-border-color);
	border-radius: var(--bs-popover-border-radius);
}

.bodywebsite .popover .popover-arrow {
	display: block;
	width: var(--bs-popover-arrow-width);
	height: var(--bs-popover-arrow-height);
}

.bodywebsite .popover .popover-arrow::before, .bodywebsite .popover .popover-arrow::after {
	position: absolute;
	display: block;
	content: "";
	border-color: transparent;
	border-style: solid;
	border-width: 0;
}

.bodywebsite .bs-popover-top > .popover-arrow, .bodywebsite .bs-popover-auto[data-popper-placement^=top] > .popover-arrow {
	bottom: calc(-1 * (var(--bs-popover-arrow-height)) - var(--bs-popover-border-width));
}

.bodywebsite .bs-popover-top > .popover-arrow::before, .bodywebsite .bs-popover-auto[data-popper-placement^=top] > .popover-arrow::before, .bodywebsite .bs-popover-top > .popover-arrow::after, .bodywebsite .bs-popover-auto[data-popper-placement^=top] > .popover-arrow::after {
	border-width: var(--bs-popover-arrow-height) calc(var(--bs-popover-arrow-width) * 0.5) 0;
}

.bodywebsite .bs-popover-top > .popover-arrow::before, .bodywebsite .bs-popover-auto[data-popper-placement^=top] > .popover-arrow::before {
	bottom: 0;
	border-top-color: var(--bs-popover-arrow-border);
}

.bodywebsite .bs-popover-top > .popover-arrow::after, .bodywebsite .bs-popover-auto[data-popper-placement^=top] > .popover-arrow::after {
	bottom: var(--bs-popover-border-width);
	border-top-color: var(--bs-popover-bg);
}

.bodywebsite .bs-popover-end > .popover-arrow, .bodywebsite .bs-popover-auto[data-popper-placement^=right] > .popover-arrow {
	left: calc(-1 * (var(--bs-popover-arrow-height)) - var(--bs-popover-border-width));
	width: var(--bs-popover-arrow-height);
	height: var(--bs-popover-arrow-width);
}

.bodywebsite .bs-popover-end > .popover-arrow::before, .bodywebsite .bs-popover-auto[data-popper-placement^=right] > .popover-arrow::before, .bodywebsite .bs-popover-end > .popover-arrow::after, .bodywebsite .bs-popover-auto[data-popper-placement^=right] > .popover-arrow::after {
	border-width: calc(var(--bs-popover-arrow-width) * 0.5) var(--bs-popover-arrow-height) calc(var(--bs-popover-arrow-width) * 0.5) 0;
}

.bodywebsite .bs-popover-end > .popover-arrow::before, .bodywebsite .bs-popover-auto[data-popper-placement^=right] > .popover-arrow::before {
	left: 0;
	border-right-color: var(--bs-popover-arrow-border);
}

.bodywebsite .bs-popover-end > .popover-arrow::after, .bodywebsite .bs-popover-auto[data-popper-placement^=right] > .popover-arrow::after {
	left: var(--bs-popover-border-width);
	border-right-color: var(--bs-popover-bg);
}

.bodywebsite .bs-popover-bottom > .popover-arrow, .bodywebsite .bs-popover-auto[data-popper-placement^=bottom] > .popover-arrow {
	top: calc(-1 * (var(--bs-popover-arrow-height)) - var(--bs-popover-border-width));
}

.bodywebsite .bs-popover-bottom > .popover-arrow::before, .bodywebsite .bs-popover-auto[data-popper-placement^=bottom] > .popover-arrow::before, .bodywebsite .bs-popover-bottom > .popover-arrow::after, .bodywebsite .bs-popover-auto[data-popper-placement^=bottom] > .popover-arrow::after {
	border-width: 0 calc(var(--bs-popover-arrow-width) * 0.5) var(--bs-popover-arrow-height);
}

.bodywebsite .bs-popover-bottom > .popover-arrow::before, .bodywebsite .bs-popover-auto[data-popper-placement^=bottom] > .popover-arrow::before {
	top: 0;
	border-bottom-color: var(--bs-popover-arrow-border);
}

.bodywebsite .bs-popover-bottom > .popover-arrow::after, .bodywebsite .bs-popover-auto[data-popper-placement^=bottom] > .popover-arrow::after {
	top: var(--bs-popover-border-width);
	border-bottom-color: var(--bs-popover-bg);
}

.bodywebsite .bs-popover-bottom .popover-header::before, .bodywebsite .bs-popover-auto[data-popper-placement^=bottom] .popover-header::before {
	position: absolute;
	top: 0;
	left: 50%;
	display: block;
	width: var(--bs-popover-arrow-width);
	margin-left: calc(-0.5 * var(--bs-popover-arrow-width));
	content: "";
	border-bottom: var(--bs-popover-border-width) solid var(--bs-popover-header-bg);
}

.bodywebsite .bs-popover-start > .popover-arrow, .bodywebsite .bs-popover-auto[data-popper-placement^=left] > .popover-arrow {
	right: calc(-1 * (var(--bs-popover-arrow-height)) - var(--bs-popover-border-width));
	width: var(--bs-popover-arrow-height);
	height: var(--bs-popover-arrow-width);
}

.bodywebsite .bs-popover-start > .popover-arrow::before, .bodywebsite .bs-popover-auto[data-popper-placement^=left] > .popover-arrow::before, .bodywebsite .bs-popover-start > .popover-arrow::after, .bodywebsite .bs-popover-auto[data-popper-placement^=left] > .popover-arrow::after {
	border-width: calc(var(--bs-popover-arrow-width) * 0.5) 0 calc(var(--bs-popover-arrow-width) * 0.5) var(--bs-popover-arrow-height);
}

.bodywebsite .bs-popover-start > .popover-arrow::before, .bodywebsite .bs-popover-auto[data-popper-placement^=left] > .popover-arrow::before {
	right: 0;
	border-left-color: var(--bs-popover-arrow-border);
}

.bodywebsite .bs-popover-start > .popover-arrow::after, .bodywebsite .bs-popover-auto[data-popper-placement^=left] > .popover-arrow::after {
	right: var(--bs-popover-border-width);
	border-left-color: var(--bs-popover-bg);
}

.bodywebsite .popover-header {
	padding: var(--bs-popover-header-padding-y) var(--bs-popover-header-padding-x);
	margin-bottom: 0;
	font-size: var(--bs-popover-header-font-size);
	color: var(--bs-popover-header-color);
	background-color: var(--bs-popover-header-bg);
	border-bottom: var(--bs-popover-border-width) solid var(--bs-popover-border-color);
	border-top-left-radius: var(--bs-popover-inner-border-radius);
	border-top-right-radius: var(--bs-popover-inner-border-radius);
}

.bodywebsite .popover-header:empty {
	display: none;
}

.bodywebsite .popover-body {
	padding: var(--bs-popover-body-padding-y) var(--bs-popover-body-padding-x);
	color: var(--bs-popover-body-color);
}

.bodywebsite .carousel {
	position: relative;
}

.bodywebsite .carousel.pointer-event {
	touch-action: pan-y;
}

.bodywebsite .carousel-inner {
	position: relative;
	width: 100%;
	overflow: hidden;
}

.bodywebsite .carousel-inner::after {
	display: block;
	clear: both;
	content: "";
}

.bodywebsite .carousel-item {
	position: relative;
	display: none;
	float: left;
	width: 100%;
	margin-right: -100%;
	-webkit-backface-visibility: hidden;
	backface-visibility: hidden;
	transition: transform 0.6s ease-in-out;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .carousel-item {
		transition: none;
	}
}

.bodywebsite .carousel-item.active,
  .bodywebsite .carousel-item-next,
  .bodywebsite .carousel-item-prev {
	display: block;
}

.bodywebsite .carousel-item-next:not(.carousel-item-start),
  .bodywebsite .active.carousel-item-end {
	transform: translateX(100%);
}

.bodywebsite .carousel-item-prev:not(.carousel-item-end),
  .bodywebsite .active.carousel-item-start {
	transform: translateX(-100%);
}

.bodywebsite .carousel-fade .carousel-item {
	opacity: 0;
	transition-property: opacity;
	transform: none;
}

.bodywebsite .carousel-fade .carousel-item.active,
  .bodywebsite .carousel-fade .carousel-item-next.carousel-item-start,
  .bodywebsite .carousel-fade .carousel-item-prev.carousel-item-end {
	z-index: 1;
	opacity: 1;
}

.bodywebsite .carousel-fade .active.carousel-item-start,
  .bodywebsite .carousel-fade .active.carousel-item-end {
	z-index: 0;
	opacity: 0;
	transition: opacity 0s 0.6s;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .carousel-fade .active.carousel-item-start,
	.bodywebsite .carousel-fade .active.carousel-item-end {
		transition: none;
	}
}

.bodywebsite .carousel-control-prev,
  .bodywebsite .carousel-control-next {
	position: absolute;
	top: 0;
	bottom: 0;
	z-index: 1;
	display: flex;
	align-items: center;
	justify-content: center;
	width: 15%;
	padding: 0;
	color: #fff;
	text-align: center;
	background: none;
	border: 0;
	opacity: 0.5;
	transition: opacity 0.15s ease;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .carousel-control-prev,
	.bodywebsite .carousel-control-next {
		transition: none;
	}
}

.bodywebsite .carousel-control-prev:hover, .bodywebsite .carousel-control-prev:focus,
  .bodywebsite .carousel-control-next:hover,
  .bodywebsite .carousel-control-next:focus {
	color: #fff;
	text-decoration: none;
	outline: 0;
	opacity: 0.9;
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
	width: 2rem;
	height: 2rem;
	background-repeat: no-repeat;
	background-position: 50%;
	background-size: 100% 100%;
}

.bodywebsite .carousel-control-prev-icon {
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath d='M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z'/%3e%3c/svg%3e");
}

.bodywebsite .carousel-control-next-icon {
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath d='M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
}

.bodywebsite .carousel-indicators {
	position: absolute;
	right: 0;
	bottom: 0;
	left: 0;
	z-index: 2;
	display: flex;
	justify-content: center;
	padding: 0;
	margin-right: 15%;
	margin-bottom: 1rem;
	margin-left: 15%;
	list-style: none;
}

.bodywebsite .carousel-indicators [data-bs-target] {
	box-sizing: content-box;
	flex: 0 1 auto;
	width: 30px;
	height: 3px;
	padding: 0;
	margin-right: 3px;
	margin-left: 3px;
	text-indent: -999px;
	cursor: pointer;
	background-color: #fff;
	background-clip: padding-box;
	border: 0;
	border-top: 10px solid transparent;
	border-bottom: 10px solid transparent;
	opacity: 0.5;
	transition: opacity 0.6s ease;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .carousel-indicators [data-bs-target] {
		transition: none;
	}
}

.bodywebsite .carousel-indicators .active {
	opacity: 1;
}

.bodywebsite .carousel-caption {
	position: absolute;
	right: 15%;
	bottom: 1.25rem;
	left: 15%;
	padding-top: 1.25rem;
	padding-bottom: 1.25rem;
	color: #fff;
	text-align: center;
}

.bodywebsite .carousel-dark .carousel-control-prev-icon,
  .bodywebsite .carousel-dark .carousel-control-next-icon {
	filter: invert(1) grayscale(100);
}

.bodywebsite .carousel-dark .carousel-indicators [data-bs-target] {
	background-color: #000;
}

.bodywebsite .carousel-dark .carousel-caption {
	color: #000;
}

.bodywebsite .spinner-grow,
  .bodywebsite .spinner-border {
	display: inline-block;
	width: var(--bs-spinner-width);
	height: var(--bs-spinner-height);
	vertical-align: var(--bs-spinner-vertical-align);
	border-radius: 50%;
	-webkit-animation: var(--bs-spinner-animation-speed) linear infinite var(--bs-spinner-animation-name);
	animation: var(--bs-spinner-animation-speed) linear infinite var(--bs-spinner-animation-name);
}

@-webkit-keyframes spinner-border {
	to {
		transform: rotate(360deg);
	}
}

@keyframes spinner-border {
	to {
		transform: rotate(360deg);
	}
}

.bodywebsite .spinner-border {
	--bs-spinner-width: 2rem;
	--bs-spinner-height: 2rem;
	--bs-spinner-vertical-align: -0.125em;
	--bs-spinner-border-width: 0.25em;
	--bs-spinner-animation-speed: 0.75s;
	--bs-spinner-animation-name: spinner-border;
	border: var(--bs-spinner-border-width) solid currentcolor;
	border-right-color: transparent;
}

.bodywebsite .spinner-border-sm {
	--bs-spinner-width: 1rem;
	--bs-spinner-height: 1rem;
	--bs-spinner-border-width: 0.2em;
}

@-webkit-keyframes spinner-grow {
	0% {
		transform: scale(0);
	}

	50% {
		opacity: 1;
		transform: none;
	}
}

@keyframes spinner-grow {
	0% {
		transform: scale(0);
	}

	50% {
		opacity: 1;
		transform: none;
	}
}

.bodywebsite .spinner-grow {
	--bs-spinner-width: 2rem;
	--bs-spinner-height: 2rem;
	--bs-spinner-vertical-align: -0.125em;
	--bs-spinner-animation-speed: 0.75s;
	--bs-spinner-animation-name: spinner-grow;
	background-color: currentcolor;
	opacity: 0;
}

.bodywebsite .spinner-grow-sm {
	--bs-spinner-width: 1rem;
	--bs-spinner-height: 1rem;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .spinner-border,
	.bodywebsite .spinner-grow {
		--bs-spinner-animation-speed: 1.5s;
	}
}

.bodywebsite .offcanvas, .bodywebsite .offcanvas-xxl, .bodywebsite .offcanvas-xl, .bodywebsite .offcanvas-lg, .bodywebsite .offcanvas-md, .bodywebsite .offcanvas-sm {
	--bs-offcanvas-zindex: 1045;
	--bs-offcanvas-width: 400px;
	--bs-offcanvas-height: 30vh;
	--bs-offcanvas-padding-x: 1rem;
	--bs-offcanvas-padding-y: 1rem;
	--bs-offcanvas-bg: #fff;
	--bs-offcanvas-border-width: 1px;
	--bs-offcanvas-border-color: var(--bs-border-color-translucent);
	--bs-offcanvas-box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

@media (max-width: 575.98px) {
	.bodywebsite .offcanvas-sm {
		position: fixed;
		bottom: 0;
		z-index: var(--bs-offcanvas-zindex);
		display: flex;
		flex-direction: column;
		max-width: 100%;
		color: var(--bs-offcanvas-color);
		visibility: hidden;
		background-color: var(--bs-offcanvas-bg);
		background-clip: padding-box;
		outline: 0;
		transition: transform 0.3s ease-in-out;
	}
}

@media (max-width: 575.98px) and (prefers-reduced-motion: reduce) {
	.bodywebsite .offcanvas-sm {
		transition: none;
	}
}

@media (max-width: 575.98px) {
	.bodywebsite .offcanvas-sm.offcanvas-start {
		top: 0;
		left: 0;
		width: var(--bs-offcanvas-width);
		border-right: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateX(-100%);
	}
}

@media (max-width: 575.98px) {
	.bodywebsite .offcanvas-sm.offcanvas-end {
		top: 0;
		right: 0;
		width: var(--bs-offcanvas-width);
		border-left: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateX(100%);
	}
}

@media (max-width: 575.98px) {
	.bodywebsite .offcanvas-sm.offcanvas-top {
		top: 0;
		right: 0;
		left: 0;
		height: var(--bs-offcanvas-height);
		max-height: 100%;
		border-bottom: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateY(-100%);
	}
}

@media (max-width: 575.98px) {
	.bodywebsite .offcanvas-sm.offcanvas-bottom {
		right: 0;
		left: 0;
		height: var(--bs-offcanvas-height);
		max-height: 100%;
		border-top: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateY(100%);
	}
}

@media (max-width: 575.98px) {
	.bodywebsite .offcanvas-sm.showing, .bodywebsite .offcanvas-sm.show:not(.hiding) {
		transform: none;
	}
}

@media (max-width: 575.98px) {
	.bodywebsite .offcanvas-sm.showing, .bodywebsite .offcanvas-sm.hiding, .bodywebsite .offcanvas-sm.show {
		visibility: visible;
	}
}

@media (min-width: 576px) {
	.bodywebsite .offcanvas-sm {
		--bs-offcanvas-height: auto;
		--bs-offcanvas-border-width: 0;
		background-color: transparent !important;
	}

	.bodywebsite .offcanvas-sm .offcanvas-header {
		display: none;
	}

	.bodywebsite .offcanvas-sm .offcanvas-body {
		display: flex;
		flex-grow: 0;
		padding: 0;
		overflow-y: visible;
		background-color: transparent !important;
	}
}

@media (max-width: 767.98px) {
	.bodywebsite .offcanvas-md {
		position: fixed;
		bottom: 0;
		z-index: var(--bs-offcanvas-zindex);
		display: flex;
		flex-direction: column;
		max-width: 100%;
		color: var(--bs-offcanvas-color);
		visibility: hidden;
		background-color: var(--bs-offcanvas-bg);
		background-clip: padding-box;
		outline: 0;
		transition: transform 0.3s ease-in-out;
	}
}

@media (max-width: 767.98px) and (prefers-reduced-motion: reduce) {
	.bodywebsite .offcanvas-md {
		transition: none;
	}
}

@media (max-width: 767.98px) {
	.bodywebsite .offcanvas-md.offcanvas-start {
		top: 0;
		left: 0;
		width: var(--bs-offcanvas-width);
		border-right: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateX(-100%);
	}
}

@media (max-width: 767.98px) {
	.bodywebsite .offcanvas-md.offcanvas-end {
		top: 0;
		right: 0;
		width: var(--bs-offcanvas-width);
		border-left: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateX(100%);
	}
}

@media (max-width: 767.98px) {
	.bodywebsite .offcanvas-md.offcanvas-top {
		top: 0;
		right: 0;
		left: 0;
		height: var(--bs-offcanvas-height);
		max-height: 100%;
		border-bottom: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateY(-100%);
	}
}

@media (max-width: 767.98px) {
	.bodywebsite .offcanvas-md.offcanvas-bottom {
		right: 0;
		left: 0;
		height: var(--bs-offcanvas-height);
		max-height: 100%;
		border-top: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateY(100%);
	}
}

@media (max-width: 767.98px) {
	.bodywebsite .offcanvas-md.showing, .bodywebsite .offcanvas-md.show:not(.hiding) {
		transform: none;
	}
}

@media (max-width: 767.98px) {
	.bodywebsite .offcanvas-md.showing, .bodywebsite .offcanvas-md.hiding, .bodywebsite .offcanvas-md.show {
		visibility: visible;
	}
}

@media (min-width: 768px) {
	.bodywebsite .offcanvas-md {
		--bs-offcanvas-height: auto;
		--bs-offcanvas-border-width: 0;
		background-color: transparent !important;
	}

	.bodywebsite .offcanvas-md .offcanvas-header {
		display: none;
	}

	.bodywebsite .offcanvas-md .offcanvas-body {
		display: flex;
		flex-grow: 0;
		padding: 0;
		overflow-y: visible;
		background-color: transparent !important;
	}
}

@media (max-width: 991.98px) {
	.bodywebsite .offcanvas-lg {
		position: fixed;
		bottom: 0;
		z-index: var(--bs-offcanvas-zindex);
		display: flex;
		flex-direction: column;
		max-width: 100%;
		color: var(--bs-offcanvas-color);
		visibility: hidden;
		background-color: var(--bs-offcanvas-bg);
		background-clip: padding-box;
		outline: 0;
		transition: transform 0.3s ease-in-out;
	}
}

@media (max-width: 991.98px) and (prefers-reduced-motion: reduce) {
	.bodywebsite .offcanvas-lg {
		transition: none;
	}
}

@media (max-width: 991.98px) {
	.bodywebsite .offcanvas-lg.offcanvas-start {
		top: 0;
		left: 0;
		width: var(--bs-offcanvas-width);
		border-right: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateX(-100%);
	}
}

@media (max-width: 991.98px) {
	.bodywebsite .offcanvas-lg.offcanvas-end {
		top: 0;
		right: 0;
		width: var(--bs-offcanvas-width);
		border-left: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateX(100%);
	}
}

@media (max-width: 991.98px) {
	.bodywebsite .offcanvas-lg.offcanvas-top {
		top: 0;
		right: 0;
		left: 0;
		height: var(--bs-offcanvas-height);
		max-height: 100%;
		border-bottom: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateY(-100%);
	}
}

@media (max-width: 991.98px) {
	.bodywebsite .offcanvas-lg.offcanvas-bottom {
		right: 0;
		left: 0;
		height: var(--bs-offcanvas-height);
		max-height: 100%;
		border-top: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateY(100%);
	}
}

@media (max-width: 991.98px) {
	.bodywebsite .offcanvas-lg.showing, .bodywebsite .offcanvas-lg.show:not(.hiding) {
		transform: none;
	}
}

@media (max-width: 991.98px) {
	.bodywebsite .offcanvas-lg.showing, .bodywebsite .offcanvas-lg.hiding, .bodywebsite .offcanvas-lg.show {
		visibility: visible;
	}
}

@media (min-width: 992px) {
	.bodywebsite .offcanvas-lg {
		--bs-offcanvas-height: auto;
		--bs-offcanvas-border-width: 0;
		background-color: transparent !important;
	}

	.bodywebsite .offcanvas-lg .offcanvas-header {
		display: none;
	}

	.bodywebsite .offcanvas-lg .offcanvas-body {
		display: flex;
		flex-grow: 0;
		padding: 0;
		overflow-y: visible;
		background-color: transparent !important;
	}
}

@media (max-width: 1199.98px) {
	.bodywebsite .offcanvas-xl {
		position: fixed;
		bottom: 0;
		z-index: var(--bs-offcanvas-zindex);
		display: flex;
		flex-direction: column;
		max-width: 100%;
		color: var(--bs-offcanvas-color);
		visibility: hidden;
		background-color: var(--bs-offcanvas-bg);
		background-clip: padding-box;
		outline: 0;
		transition: transform 0.3s ease-in-out;
	}
}

@media (max-width: 1199.98px) and (prefers-reduced-motion: reduce) {
	.bodywebsite .offcanvas-xl {
		transition: none;
	}
}

@media (max-width: 1199.98px) {
	.bodywebsite .offcanvas-xl.offcanvas-start {
		top: 0;
		left: 0;
		width: var(--bs-offcanvas-width);
		border-right: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateX(-100%);
	}
}

@media (max-width: 1199.98px) {
	.bodywebsite .offcanvas-xl.offcanvas-end {
		top: 0;
		right: 0;
		width: var(--bs-offcanvas-width);
		border-left: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateX(100%);
	}
}

@media (max-width: 1199.98px) {
	.bodywebsite .offcanvas-xl.offcanvas-top {
		top: 0;
		right: 0;
		left: 0;
		height: var(--bs-offcanvas-height);
		max-height: 100%;
		border-bottom: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateY(-100%);
	}
}

@media (max-width: 1199.98px) {
	.bodywebsite .offcanvas-xl.offcanvas-bottom {
		right: 0;
		left: 0;
		height: var(--bs-offcanvas-height);
		max-height: 100%;
		border-top: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateY(100%);
	}
}

@media (max-width: 1199.98px) {
	.bodywebsite .offcanvas-xl.showing, .bodywebsite .offcanvas-xl.show:not(.hiding) {
		transform: none;
	}
}

@media (max-width: 1199.98px) {
	.bodywebsite .offcanvas-xl.showing, .bodywebsite .offcanvas-xl.hiding, .bodywebsite .offcanvas-xl.show {
		visibility: visible;
	}
}

@media (min-width: 1200px) {
	.bodywebsite .offcanvas-xl {
		--bs-offcanvas-height: auto;
		--bs-offcanvas-border-width: 0;
		background-color: transparent !important;
	}

	.bodywebsite .offcanvas-xl .offcanvas-header {
		display: none;
	}

	.bodywebsite .offcanvas-xl .offcanvas-body {
		display: flex;
		flex-grow: 0;
		padding: 0;
		overflow-y: visible;
		background-color: transparent !important;
	}
}

@media (max-width: 1399.98px) {
	.bodywebsite .offcanvas-xxl {
		position: fixed;
		bottom: 0;
		z-index: var(--bs-offcanvas-zindex);
		display: flex;
		flex-direction: column;
		max-width: 100%;
		color: var(--bs-offcanvas-color);
		visibility: hidden;
		background-color: var(--bs-offcanvas-bg);
		background-clip: padding-box;
		outline: 0;
		transition: transform 0.3s ease-in-out;
	}
}

@media (max-width: 1399.98px) and (prefers-reduced-motion: reduce) {
	.bodywebsite .offcanvas-xxl {
		transition: none;
	}
}

@media (max-width: 1399.98px) {
	.bodywebsite .offcanvas-xxl.offcanvas-start {
		top: 0;
		left: 0;
		width: var(--bs-offcanvas-width);
		border-right: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateX(-100%);
	}
}

@media (max-width: 1399.98px) {
	.bodywebsite .offcanvas-xxl.offcanvas-end {
		top: 0;
		right: 0;
		width: var(--bs-offcanvas-width);
		border-left: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateX(100%);
	}
}

@media (max-width: 1399.98px) {
	.bodywebsite .offcanvas-xxl.offcanvas-top {
		top: 0;
		right: 0;
		left: 0;
		height: var(--bs-offcanvas-height);
		max-height: 100%;
		border-bottom: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateY(-100%);
	}
}

@media (max-width: 1399.98px) {
	.bodywebsite .offcanvas-xxl.offcanvas-bottom {
		right: 0;
		left: 0;
		height: var(--bs-offcanvas-height);
		max-height: 100%;
		border-top: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
		transform: translateY(100%);
	}
}

@media (max-width: 1399.98px) {
	.bodywebsite .offcanvas-xxl.showing, .bodywebsite .offcanvas-xxl.show:not(.hiding) {
		transform: none;
	}
}

@media (max-width: 1399.98px) {
	.bodywebsite .offcanvas-xxl.showing, .bodywebsite .offcanvas-xxl.hiding, .bodywebsite .offcanvas-xxl.show {
		visibility: visible;
	}
}

@media (min-width: 1400px) {
	.bodywebsite .offcanvas-xxl {
		--bs-offcanvas-height: auto;
		--bs-offcanvas-border-width: 0;
		background-color: transparent !important;
	}

	.bodywebsite .offcanvas-xxl .offcanvas-header {
		display: none;
	}

	.bodywebsite .offcanvas-xxl .offcanvas-body {
		display: flex;
		flex-grow: 0;
		padding: 0;
		overflow-y: visible;
		background-color: transparent !important;
	}
}

.bodywebsite .offcanvas {
	position: fixed;
	bottom: 0;
	z-index: var(--bs-offcanvas-zindex);
	display: flex;
	flex-direction: column;
	max-width: 100%;
	color: var(--bs-offcanvas-color);
	visibility: hidden;
	background-color: var(--bs-offcanvas-bg);
	background-clip: padding-box;
	outline: 0;
	transition: transform 0.3s ease-in-out;
}

@media (prefers-reduced-motion: reduce) {
	.bodywebsite .offcanvas {
		transition: none;
	}
}

.bodywebsite .offcanvas.offcanvas-start {
	top: 0;
	left: 0;
	width: var(--bs-offcanvas-width);
	border-right: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
	transform: translateX(-100%);
}

.bodywebsite .offcanvas.offcanvas-end {
	top: 0;
	right: 0;
	width: var(--bs-offcanvas-width);
	border-left: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
	transform: translateX(100%);
}

.bodywebsite .offcanvas.offcanvas-top {
	top: 0;
	right: 0;
	left: 0;
	height: var(--bs-offcanvas-height);
	max-height: 100%;
	border-bottom: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
	transform: translateY(-100%);
}

.bodywebsite .offcanvas.offcanvas-bottom {
	right: 0;
	left: 0;
	height: var(--bs-offcanvas-height);
	max-height: 100%;
	border-top: var(--bs-offcanvas-border-width) solid var(--bs-offcanvas-border-color);
	transform: translateY(100%);
}

.bodywebsite .offcanvas.showing, .bodywebsite .offcanvas.show:not(.hiding) {
	transform: none;
}

.bodywebsite .offcanvas.showing, .bodywebsite .offcanvas.hiding, .bodywebsite .offcanvas.show {
	visibility: visible;
}

.bodywebsite .offcanvas-backdrop {
	position: fixed;
	top: 0;
	left: 0;
	z-index: 1040;
	width: 100vw;
	height: 100vh;
	background-color: #000;
}

.bodywebsite .offcanvas-backdrop.fade {
	opacity: 0;
}

.bodywebsite .offcanvas-backdrop.show {
	opacity: 0.5;
}

.bodywebsite .offcanvas-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: var(--bs-offcanvas-padding-y) var(--bs-offcanvas-padding-x);
}

.bodywebsite .offcanvas-header .btn-close {
	padding: calc(var(--bs-offcanvas-padding-y) * 0.5) calc(var(--bs-offcanvas-padding-x) * 0.5);
	margin-top: calc(-0.5 * var(--bs-offcanvas-padding-y));
	margin-right: calc(-0.5 * var(--bs-offcanvas-padding-x));
	margin-bottom: calc(-0.5 * var(--bs-offcanvas-padding-y));
}

.bodywebsite .offcanvas-title {
	margin-bottom: 0;
	line-height: 1.5;
}

.bodywebsite .offcanvas-body {
	flex-grow: 1;
	padding: var(--bs-offcanvas-padding-y) var(--bs-offcanvas-padding-x);
	overflow-y: auto;
}

.bodywebsite .placeholder {
	display: inline-block;
	min-height: 1em;
	vertical-align: middle;
	cursor: wait;
	background-color: currentcolor;
	opacity: 0.5;
}

.bodywebsite .placeholder.btn::before {
	display: inline-block;
	content: "";
}

.bodywebsite .placeholder-xs {
	min-height: 0.6em;
}

.bodywebsite .placeholder-sm {
	min-height: 0.8em;
}

.bodywebsite .placeholder-lg {
	min-height: 1.2em;
}

.bodywebsite .placeholder-glow .placeholder {
	-webkit-animation: placeholder-glow 2s ease-in-out infinite;
	animation: placeholder-glow 2s ease-in-out infinite;
}

@-webkit-keyframes placeholder-glow {
	50% {
		opacity: 0.2;
	}
}

@keyframes placeholder-glow {
	50% {
		opacity: 0.2;
	}
}

.bodywebsite .placeholder-wave {
	-webkit-mask-image: linear-gradient(130deg, #000 55%, rgba(0, 0, 0, 0.8) 75%, #000 95%);
	mask-image: linear-gradient(130deg, #000 55%, rgba(0, 0, 0, 0.8) 75%, #000 95%);
	-webkit-mask-size: 200% 100%;
	mask-size: 200% 100%;
	-webkit-animation: placeholder-wave 2s linear infinite;
	animation: placeholder-wave 2s linear infinite;
}

@-webkit-keyframes placeholder-wave {
	100% {
		-webkit-mask-position: -200% 0%;
		mask-position: -200% 0%;
	}
}

@keyframes placeholder-wave {
	100% {
		-webkit-mask-position: -200% 0%;
		mask-position: -200% 0%;
	}
}

.bodywebsite .clearfix::after {
	display: block;
	clear: both;
	content: "";
}

.bodywebsite .text-bg-primary {
	color: #fff !important;
	background-color: RGBA(13, 110, 253, var(--bs-bg-opacity, 1)) !important;
}

.bodywebsite .text-bg-secondary {
	color: #fff !important;
	background-color: RGBA(108, 117, 125, var(--bs-bg-opacity, 1)) !important;
}

.bodywebsite .text-bg-success {
	color: #fff !important;
	background-color: RGBA(25, 135, 84, var(--bs-bg-opacity, 1)) !important;
}

.bodywebsite .text-bg-info {
	color: #000 !important;
	background-color: RGBA(13, 202, 240, var(--bs-bg-opacity, 1)) !important;
}

.bodywebsite .text-bg-warning {
	color: #000 !important;
	background-color: RGBA(255, 193, 7, var(--bs-bg-opacity, 1)) !important;
}

.bodywebsite .text-bg-danger {
	color: #fff !important;
	background-color: RGBA(220, 53, 69, var(--bs-bg-opacity, 1)) !important;
}

.bodywebsite .text-bg-light {
	color: #000 !important;
	background-color: RGBA(248, 249, 250, var(--bs-bg-opacity, 1)) !important;
}

.bodywebsite .text-bg-dark {
	color: #fff !important;
	background-color: RGBA(33, 37, 41, var(--bs-bg-opacity, 1)) !important;
}

.bodywebsite .link-primary {
	color: #0d6efd !important;
}

.bodywebsite .link-primary:hover, .bodywebsite .link-primary:focus {
	color: #0a58ca !important;
}

.bodywebsite .link-secondary {
	color: #6c757d !important;
}

.bodywebsite .link-secondary:hover, .bodywebsite .link-secondary:focus {
	color: #565e64 !important;
}

.bodywebsite .link-success {
	color: #198754 !important;
}

.bodywebsite .link-success:hover, .bodywebsite .link-success:focus {
	color: #146c43 !important;
}

.bodywebsite .link-info {
	color: #0dcaf0 !important;
}

.bodywebsite .link-info:hover, .bodywebsite .link-info:focus {
	color: #3dd5f3 !important;
}

.bodywebsite .link-warning {
	color: #ffc107 !important;
}

.bodywebsite .link-warning:hover, .bodywebsite .link-warning:focus {
	color: #ffcd39 !important;
}

.bodywebsite .link-danger {
	color: #dc3545 !important;
}

.bodywebsite .link-danger:hover, .bodywebsite .link-danger:focus {
	color: #b02a37 !important;
}

.bodywebsite .link-light {
	color: #f8f9fa !important;
}

.bodywebsite .link-light:hover, .bodywebsite .link-light:focus {
	color: #f9fafb !important;
}

.bodywebsite .link-dark {
	color: #212529 !important;
}

.bodywebsite .link-dark:hover, .bodywebsite .link-dark:focus {
	color: #1a1e21 !important;
}

.bodywebsite .ratio {
	position: relative;
	width: 100%;
}

.bodywebsite .ratio::before {
	display: block;
	padding-top: var(--bs-aspect-ratio);
	content: "";
}

.bodywebsite .ratio > * {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
}

.bodywebsite .ratio-1x1 {
	--bs-aspect-ratio: 100%;
}

.bodywebsite .ratio-4x3 {
	--bs-aspect-ratio: 75%;
}

.bodywebsite .ratio-16x9 {
	--bs-aspect-ratio: 56.25%;
}

.bodywebsite .ratio-21x9 {
	--bs-aspect-ratio: 42.8571428571%;
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

.bodywebsite .sticky-top {
	position: -webkit-sticky;
	position: sticky;
	top: 0;
	z-index: 1020;
}

.bodywebsite .sticky-bottom {
	position: -webkit-sticky;
	position: sticky;
	bottom: 0;
	z-index: 1020;
}

@media (min-width: 576px) {
	.bodywebsite .sticky-sm-top {
		position: -webkit-sticky;
		position: sticky;
		top: 0;
		z-index: 1020;
	}

	.bodywebsite .sticky-sm-bottom {
		position: -webkit-sticky;
		position: sticky;
		bottom: 0;
		z-index: 1020;
	}
}

@media (min-width: 768px) {
	.bodywebsite .sticky-md-top {
		position: -webkit-sticky;
		position: sticky;
		top: 0;
		z-index: 1020;
	}

	.bodywebsite .sticky-md-bottom {
		position: -webkit-sticky;
		position: sticky;
		bottom: 0;
		z-index: 1020;
	}
}

@media (min-width: 992px) {
	.bodywebsite .sticky-lg-top {
		position: -webkit-sticky;
		position: sticky;
		top: 0;
		z-index: 1020;
	}

	.bodywebsite .sticky-lg-bottom {
		position: -webkit-sticky;
		position: sticky;
		bottom: 0;
		z-index: 1020;
	}
}

@media (min-width: 1200px) {
	.bodywebsite .sticky-xl-top {
		position: -webkit-sticky;
		position: sticky;
		top: 0;
		z-index: 1020;
	}

	.bodywebsite .sticky-xl-bottom {
		position: -webkit-sticky;
		position: sticky;
		bottom: 0;
		z-index: 1020;
	}
}

@media (min-width: 1400px) {
	.bodywebsite .sticky-xxl-top {
		position: -webkit-sticky;
		position: sticky;
		top: 0;
		z-index: 1020;
	}

	.bodywebsite .sticky-xxl-bottom {
		position: -webkit-sticky;
		position: sticky;
		bottom: 0;
		z-index: 1020;
	}
}

.bodywebsite .hstack {
	display: flex;
	flex-direction: row;
	align-items: center;
	align-self: stretch;
}

.bodywebsite .vstack {
	display: flex;
	flex: 1 1 auto;
	flex-direction: column;
	align-self: stretch;
}

.bodywebsite .visually-hidden,
  .bodywebsite .visually-hidden-focusable:not(:focus):not(:focus-within) {
	position: absolute !important;
	width: 1px !important;
	height: 1px !important;
	padding: 0 !important;
	margin: -1px !important;
	overflow: hidden !important;
	clip: rect(0, 0, 0, 0) !important;
	white-space: nowrap !important;
	border: 0 !important;
}

.bodywebsite .stretched-link::after {
	position: absolute;
	top: 0;
	right: 0;
	bottom: 0;
	left: 0;
	z-index: 1;
	content: "";
}

.bodywebsite .text-truncate {
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.bodywebsite .vr {
	display: inline-block;
	align-self: stretch;
	width: 1px;
	min-height: 1em;
	background-color: currentcolor;
	opacity: 0.25;
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

.bodywebsite .float-start {
	float: left !important;
}

.bodywebsite .float-end {
	float: right !important;
}

.bodywebsite .float-none {
	float: none !important;
}

.bodywebsite .opacity-0 {
	opacity: 0 !important;
}

.bodywebsite .opacity-25 {
	opacity: 0.25 !important;
}

.bodywebsite .opacity-50 {
	opacity: 0.5 !important;
}

.bodywebsite .opacity-75 {
	opacity: 0.75 !important;
}

.bodywebsite .opacity-100 {
	opacity: 1 !important;
}

.bodywebsite .overflow-auto {
	overflow: auto !important;
}

.bodywebsite .overflow-hidden {
	overflow: hidden !important;
}

.bodywebsite .overflow-visible {
	overflow: visible !important;
}

.bodywebsite .overflow-scroll {
	overflow: scroll !important;
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

.bodywebsite .d-grid {
	display: grid !important;
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

.bodywebsite .d-none {
	display: none !important;
}

.bodywebsite .shadow {
	box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.bodywebsite .shadow-sm {
	box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}

.bodywebsite .shadow-lg {
	box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175) !important;
}

.bodywebsite .shadow-none {
	box-shadow: none !important;
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
	position: -webkit-sticky !important;
	position: sticky !important;
}

.bodywebsite .top-0 {
	top: 0 !important;
}

.bodywebsite .top-50 {
	top: 50% !important;
}

.bodywebsite .top-100 {
	top: 100% !important;
}

.bodywebsite .bottom-0 {
	bottom: 0 !important;
}

.bodywebsite .bottom-50 {
	bottom: 50% !important;
}

.bodywebsite .bottom-100 {
	bottom: 100% !important;
}

.bodywebsite .start-0 {
	left: 0 !important;
}

.bodywebsite .start-50 {
	left: 50% !important;
}

.bodywebsite .start-100 {
	left: 100% !important;
}

.bodywebsite .end-0 {
	right: 0 !important;
}

.bodywebsite .end-50 {
	right: 50% !important;
}

.bodywebsite .end-100 {
	right: 100% !important;
}

.bodywebsite .translate-middle {
	transform: translate(-50%, -50%) !important;
}

.bodywebsite .translate-middle-x {
	transform: translateX(-50%) !important;
}

.bodywebsite .translate-middle-y {
	transform: translateY(-50%) !important;
}

.bodywebsite .border {
	border: var(--bs-border-width) var(--bs-border-style) var(--bs-border-color) !important;
}

.bodywebsite .border-0 {
	border: 0 !important;
}

.bodywebsite .border-top {
	border-top: var(--bs-border-width) var(--bs-border-style) var(--bs-border-color) !important;
}

.bodywebsite .border-top-0 {
	border-top: 0 !important;
}

.bodywebsite .border-end {
	border-right: var(--bs-border-width) var(--bs-border-style) var(--bs-border-color) !important;
}

.bodywebsite .border-end-0 {
	border-right: 0 !important;
}

.bodywebsite .border-bottom {
	border-bottom: var(--bs-border-width) var(--bs-border-style) var(--bs-border-color) !important;
}

.bodywebsite .border-bottom-0 {
	border-bottom: 0 !important;
}

.bodywebsite .border-start {
	border-left: var(--bs-border-width) var(--bs-border-style) var(--bs-border-color) !important;
}

.bodywebsite .border-start-0 {
	border-left: 0 !important;
}

.bodywebsite .border-primary {
	--bs-border-opacity: 1;
}

.bodywebsite .border-secondary {
	--bs-border-opacity: 1;
}

.bodywebsite .border-success {
	--bs-border-opacity: 1;
}

.bodywebsite .border-info {
	--bs-border-opacity: 1;
}

.bodywebsite .border-warning {
	--bs-border-opacity: 1;
}

.bodywebsite .border-danger {
	--bs-border-opacity: 1;
}

.bodywebsite .border-light {
	--bs-border-opacity: 1;
}

.bodywebsite .border-dark {
	--bs-border-opacity: 1;
}

.bodywebsite .border-white {
	--bs-border-opacity: 1;
}

.bodywebsite .border-1 {
	--bs-border-width: 1px;
}

.bodywebsite .border-2 {
	--bs-border-width: 2px;
}

.bodywebsite .border-3 {
	--bs-border-width: 3px;
}

.bodywebsite .border-4 {
	--bs-border-width: 4px;
}

.bodywebsite .border-5 {
	--bs-border-width: 5px;
}

.bodywebsite .border-opacity-10 {
	--bs-border-opacity: 0.1;
}

.bodywebsite .border-opacity-25 {
	--bs-border-opacity: 0.25;
}

.bodywebsite .border-opacity-50 {
	--bs-border-opacity: 0.5;
}

.bodywebsite .border-opacity-75 {
	--bs-border-opacity: 0.75;
}

.bodywebsite .border-opacity-100 {
	--bs-border-opacity: 1;
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

.bodywebsite .w-auto {
	width: auto !important;
}

.bodywebsite .mw-100 {
	max-width: 100% !important;
}

.bodywebsite .vw-100 {
	width: 100vw !important;
}

.bodywebsite .min-vw-100 {
	min-width: 100vw !important;
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

.bodywebsite .h-auto {
	height: auto !important;
}

.bodywebsite .mh-100 {
	max-height: 100% !important;
}

.bodywebsite .vh-100 {
	height: 100vh !important;
}

.bodywebsite .min-vh-100 {
	min-height: 100vh !important;
}

.bodywebsite .flex-fill {
	flex: 1 1 auto !important;
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

.bodywebsite .flex-grow-0 {
	flex-grow: 0 !important;
}

.bodywebsite .flex-grow-1 {
	flex-grow: 1 !important;
}

.bodywebsite .flex-shrink-0 {
	flex-shrink: 0 !important;
}

.bodywebsite .flex-shrink-1 {
	flex-shrink: 1 !important;
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

.bodywebsite .justify-content-evenly {
	justify-content: space-evenly !important;
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

.bodywebsite .order-first {
	order: -1 !important;
}

.bodywebsite .order-0 {
	order: 0 !important;
}

.bodywebsite .order-1 {
	order: 1 !important;
}

.bodywebsite .order-2 {
	order: 2 !important;
}

.bodywebsite .order-3 {
	order: 3 !important;
}

.bodywebsite .order-4 {
	order: 4 !important;
}

.bodywebsite .order-5 {
	order: 5 !important;
}

.bodywebsite .order-last {
	order: 6 !important;
}

.bodywebsite .m-0 {
	margin: 0 !important;
}

.bodywebsite .m-1 {
	margin: 0.25rem !important;
}

.bodywebsite .m-2 {
	margin: 0.5rem !important;
}

.bodywebsite .m-3 {
	margin: 1rem !important;
}

.bodywebsite .m-4 {
	margin: 1.5rem !important;
}

.bodywebsite .m-5 {
	margin: 3rem !important;
}

.bodywebsite .m-auto {
	margin: auto !important;
}

.bodywebsite .mx-0 {
	margin-right: 0 !important;
	margin-left: 0 !important;
}

.bodywebsite .mx-1 {
	margin-right: 0.25rem !important;
	margin-left: 0.25rem !important;
}

.bodywebsite .mx-2 {
	margin-right: 0.5rem !important;
	margin-left: 0.5rem !important;
}

.bodywebsite .mx-3 {
	margin-right: 1rem !important;
	margin-left: 1rem !important;
}

.bodywebsite .mx-4 {
	margin-right: 1.5rem !important;
	margin-left: 1.5rem !important;
}

.bodywebsite .mx-5 {
	margin-right: 3rem !important;
	margin-left: 3rem !important;
}

.bodywebsite .mx-auto {
	margin-right: auto !important;
	margin-left: auto !important;
}

.bodywebsite .my-0 {
	margin-top: 0 !important;
	margin-bottom: 0 !important;
}

.bodywebsite .my-1 {
	margin-top: 0.25rem !important;
	margin-bottom: 0.25rem !important;
}

.bodywebsite .my-2 {
	margin-top: 0.5rem !important;
	margin-bottom: 0.5rem !important;
}

.bodywebsite .my-3 {
	margin-top: 1rem !important;
	margin-bottom: 1rem !important;
}

.bodywebsite .my-4 {
	margin-top: 1.5rem !important;
	margin-bottom: 1.5rem !important;
}

.bodywebsite .my-5 {
	margin-top: 3rem !important;
	margin-bottom: 3rem !important;
}

.bodywebsite .my-auto {
	margin-top: auto !important;
	margin-bottom: auto !important;
}

.bodywebsite .mt-0 {
	margin-top: 0 !important;
}

.bodywebsite .mt-1 {
	margin-top: 0.25rem !important;
}

.bodywebsite .mt-2 {
	margin-top: 0.5rem !important;
}

.bodywebsite .mt-3 {
	margin-top: 1rem !important;
}

.bodywebsite .mt-4 {
	margin-top: 1.5rem !important;
}

.bodywebsite .mt-5 {
	margin-top: 3rem !important;
}

.bodywebsite .mt-auto {
	margin-top: auto !important;
}

.bodywebsite .me-0 {
	margin-right: 0 !important;
}

.bodywebsite .me-1 {
	margin-right: 0.25rem !important;
}

.bodywebsite .me-2 {
	margin-right: 0.5rem !important;
}

.bodywebsite .me-3 {
	margin-right: 1rem !important;
}

.bodywebsite .me-4 {
	margin-right: 1.5rem !important;
}

.bodywebsite .me-5 {
	margin-right: 3rem !important;
}

.bodywebsite .me-auto {
	margin-right: auto !important;
}

.bodywebsite .mb-0 {
	margin-bottom: 0 !important;
}

.bodywebsite .mb-1 {
	margin-bottom: 0.25rem !important;
}

.bodywebsite .mb-2 {
	margin-bottom: 0.5rem !important;
}

.bodywebsite .mb-3 {
	margin-bottom: 1rem !important;
}

.bodywebsite .mb-4 {
	margin-bottom: 1.5rem !important;
}

.bodywebsite .mb-5 {
	margin-bottom: 3rem !important;
}

.bodywebsite .mb-auto {
	margin-bottom: auto !important;
}

.bodywebsite .ms-0 {
	margin-left: 0 !important;
}

.bodywebsite .ms-1 {
	margin-left: 0.25rem !important;
}

.bodywebsite .ms-2 {
	margin-left: 0.5rem !important;
}

.bodywebsite .ms-3 {
	margin-left: 1rem !important;
}

.bodywebsite .ms-4 {
	margin-left: 1.5rem !important;
}

.bodywebsite .ms-5 {
	margin-left: 3rem !important;
}

.bodywebsite .ms-auto {
	margin-left: auto !important;
}

.bodywebsite .p-0 {
	padding: 0 !important;
}

.bodywebsite .p-1 {
	padding: 0.25rem !important;
}

.bodywebsite .p-2 {
	padding: 0.5rem !important;
}

.bodywebsite .p-3 {
	padding: 1rem !important;
}

.bodywebsite .p-4 {
	padding: 1.5rem !important;
}

.bodywebsite .p-5 {
	padding: 3rem !important;
}

.bodywebsite .px-0 {
	padding-right: 0 !important;
	padding-left: 0 !important;
}

.bodywebsite .px-1 {
	padding-right: 0.25rem !important;
	padding-left: 0.25rem !important;
}

.bodywebsite .px-2 {
	padding-right: 0.5rem !important;
	padding-left: 0.5rem !important;
}

.bodywebsite .px-3 {
	padding-right: 1rem !important;
	padding-left: 1rem !important;
}

.bodywebsite .px-4 {
	padding-right: 1.5rem !important;
	padding-left: 1.5rem !important;
}

.bodywebsite .px-5 {
	padding-right: 3rem !important;
	padding-left: 3rem !important;
}

.bodywebsite .py-0 {
	padding-top: 0 !important;
	padding-bottom: 0 !important;
}

.bodywebsite .py-1 {
	padding-top: 0.25rem !important;
	padding-bottom: 0.25rem !important;
}

.bodywebsite .py-2 {
	padding-top: 0.5rem !important;
	padding-bottom: 0.5rem !important;
}

.bodywebsite .py-3 {
	padding-top: 1rem !important;
	padding-bottom: 1rem !important;
}

.bodywebsite .py-4 {
	padding-top: 1.5rem !important;
	padding-bottom: 1.5rem !important;
}

.bodywebsite .py-5 {
	padding-top: 3rem !important;
	padding-bottom: 3rem !important;
}

.bodywebsite .pt-0 {
	padding-top: 0 !important;
}

.bodywebsite .pt-1 {
	padding-top: 0.25rem !important;
}

.bodywebsite .pt-2 {
	padding-top: 0.5rem !important;
}

.bodywebsite .pt-3 {
	padding-top: 1rem !important;
}

.bodywebsite .pt-4 {
	padding-top: 1.5rem !important;
}

.bodywebsite .pt-5 {
	padding-top: 3rem !important;
}

.bodywebsite .pe-0 {
	padding-right: 0 !important;
}

.bodywebsite .pe-1 {
	padding-right: 0.25rem !important;
}

.bodywebsite .pe-2 {
	padding-right: 0.5rem !important;
}

.bodywebsite .pe-3 {
	padding-right: 1rem !important;
}

.bodywebsite .pe-4 {
	padding-right: 1.5rem !important;
}

.bodywebsite .pe-5 {
	padding-right: 3rem !important;
}

.bodywebsite .pb-0 {
	padding-bottom: 0 !important;
}

.bodywebsite .pb-1 {
	padding-bottom: 0.25rem !important;
}

.bodywebsite .pb-2 {
	padding-bottom: 0.5rem !important;
}

.bodywebsite .pb-3 {
	padding-bottom: 1rem !important;
}

.bodywebsite .pb-4 {
	padding-bottom: 1.5rem !important;
}

.bodywebsite .pb-5 {
	padding-bottom: 3rem !important;
}

.bodywebsite .ps-0 {
	padding-left: 0 !important;
}

.bodywebsite .ps-1 {
	padding-left: 0.25rem !important;
}

.bodywebsite .ps-2 {
	padding-left: 0.5rem !important;
}

.bodywebsite .ps-3 {
	padding-left: 1rem !important;
}

.bodywebsite .ps-4 {
	padding-left: 1.5rem !important;
}

.bodywebsite .ps-5 {
	padding-left: 3rem !important;
}

.bodywebsite .gap-0 {
	gap: 0 !important;
}

.bodywebsite .gap-1 {
	gap: 0.25rem !important;
}

.bodywebsite .gap-2 {
	gap: 0.5rem !important;
}

.bodywebsite .gap-3 {
	gap: 1rem !important;
}

.bodywebsite .gap-4 {
	gap: 1.5rem !important;
}

.bodywebsite .gap-5 {
	gap: 3rem !important;
}

.bodywebsite .font-monospace {
	font-family: var(--bs-font-monospace) !important;
}

.bodywebsite .fs-1 {
	font-size: calc(1.375rem + 1.5vw) !important;
}

.bodywebsite .fs-2 {
	font-size: calc(1.325rem + 0.9vw) !important;
}

.bodywebsite .fs-3 {
	font-size: calc(1.3rem + 0.6vw) !important;
}

.bodywebsite .fs-4 {
	font-size: calc(1.275rem + 0.3vw) !important;
}

.bodywebsite .fs-5 {
	font-size: 1.25rem !important;
}

.bodywebsite .fs-6 {
	font-size: 1rem !important;
}

.bodywebsite .fst-italic {
	font-style: italic !important;
}

.bodywebsite .fst-normal {
	font-style: normal !important;
}

.bodywebsite .fw-light {
	font-weight: 300 !important;
}

.bodywebsite .fw-lighter {
	font-weight: lighter !important;
}

.bodywebsite .fw-normal {
	font-weight: 400 !important;
}

.bodywebsite .fw-bold {
	font-weight: 700 !important;
}

.bodywebsite .fw-semibold {
	font-weight: 600 !important;
}

.bodywebsite .fw-bolder {
	font-weight: bolder !important;
}

.bodywebsite .lh-1 {
	line-height: 1 !important;
}

.bodywebsite .lh-sm {
	line-height: 1.25 !important;
}

.bodywebsite .lh-base {
	line-height: 1.5 !important;
}

.bodywebsite .lh-lg {
	line-height: 2 !important;
}

.bodywebsite .text-start {
	text-align: left !important;
}

.bodywebsite .text-end {
	text-align: right !important;
}

.bodywebsite .text-center {
	text-align: center !important;
}

.bodywebsite .text-decoration-none {
	text-decoration: none !important;
}

.bodywebsite .text-decoration-underline {
	text-decoration: underline !important;
}

.bodywebsite .text-decoration-line-through {
	text-decoration: line-through !important;
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

.bodywebsite .text-wrap {
	white-space: normal !important;
}

.bodywebsite .text-nowrap {
	white-space: nowrap !important;
}

.bodywebsite .text-break {
	word-wrap: break-word !important;
	word-break: break-word !important;
}

.bodywebsite .text-primary {
	--bs-text-opacity: 1;
}

.bodywebsite .text-secondary {
	--bs-text-opacity: 1;
}

.bodywebsite .text-success {
	--bs-text-opacity: 1;
}

.bodywebsite .text-info {
	--bs-text-opacity: 1;
}

.bodywebsite .text-warning {
	--bs-text-opacity: 1;
}

.bodywebsite .text-danger {
	--bs-text-opacity: 1;
}

.bodywebsite .text-light {
	--bs-text-opacity: 1;
}

.bodywebsite .text-dark {
	--bs-text-opacity: 1;
}

.bodywebsite .text-black {
	--bs-text-opacity: 1;
}

.bodywebsite .text-white {
	--bs-text-opacity: 1;
}

.bodywebsite .text-body {
	--bs-text-opacity: 1;
}

.bodywebsite .text-muted {
	--bs-text-opacity: 1;
	color: #6c757d !important;
}

.bodywebsite .text-black-50 {
	--bs-text-opacity: 1;
	color: rgba(0, 0, 0, 0.5) !important;
}

.bodywebsite .text-white-50 {
	--bs-text-opacity: 1;
	color: rgba(255, 255, 255, 0.5) !important;
}

.bodywebsite .text-reset {
	--bs-text-opacity: 1;
	color: inherit !important;
}

.bodywebsite .text-opacity-25 {
	--bs-text-opacity: 0.25;
}

.bodywebsite .text-opacity-50 {
	--bs-text-opacity: 0.5;
}

.bodywebsite .text-opacity-75 {
	--bs-text-opacity: 0.75;
}

.bodywebsite .text-opacity-100 {
	--bs-text-opacity: 1;
}

.bodywebsite .bg-primary {
	--bs-bg-opacity: 1;
}

.bodywebsite .bg-secondary {
	--bs-bg-opacity: 1;
}

.bodywebsite .bg-success {
	--bs-bg-opacity: 1;
}

.bodywebsite .bg-info {
	--bs-bg-opacity: 1;
}

.bodywebsite .bg-warning {
	--bs-bg-opacity: 1;
}

.bodywebsite .bg-danger {
	--bs-bg-opacity: 1;
}

.bodywebsite .bg-light {
	--bs-bg-opacity: 1;
}

.bodywebsite .bg-dark {
	--bs-bg-opacity: 1;
}

.bodywebsite .bg-black {
	--bs-bg-opacity: 1;
}

.bodywebsite .bg-white {
	--bs-bg-opacity: 1;
}

.bodywebsite .bg-body {
	--bs-bg-opacity: 1;
}

.bodywebsite .bg-transparent {
	--bs-bg-opacity: 1;
}

.bodywebsite .bg-opacity-10 {
	--bs-bg-opacity: 0.1;
}

.bodywebsite .bg-opacity-25 {
	--bs-bg-opacity: 0.25;
}

.bodywebsite .bg-opacity-50 {
	--bs-bg-opacity: 0.5;
}

.bodywebsite .bg-opacity-75 {
	--bs-bg-opacity: 0.75;
}

.bodywebsite .bg-opacity-100 {
	--bs-bg-opacity: 1;
}

.bodywebsite .bg-gradient {
	background-image: var(--bs-gradient) !important;
}

.bodywebsite .user-select-all {
	-webkit-user-select: all !important;
	-moz-user-select: all !important;
	user-select: all !important;
}

.bodywebsite .user-select-auto {
	-webkit-user-select: auto !important;
	-moz-user-select: auto !important;
	user-select: auto !important;
}

.bodywebsite .user-select-none {
	-webkit-user-select: none !important;
	-moz-user-select: none !important;
	user-select: none !important;
}

.bodywebsite .pe-none {
	pointer-events: none !important;
}

.bodywebsite .pe-auto {
	pointer-events: auto !important;
}

.bodywebsite .rounded {
	border-radius: var(--bs-border-radius) !important;
}

.bodywebsite .rounded-0 {
	border-radius: 0 !important;
}

.bodywebsite .rounded-1 {
	border-radius: var(--bs-border-radius-sm) !important;
}

.bodywebsite .rounded-2 {
	border-radius: var(--bs-border-radius) !important;
}

.bodywebsite .rounded-3 {
	border-radius: var(--bs-border-radius-lg) !important;
}

.bodywebsite .rounded-4 {
	border-radius: var(--bs-border-radius-xl) !important;
}

.bodywebsite .rounded-5 {
	border-radius: var(--bs-border-radius-2xl) !important;
}

.bodywebsite .rounded-circle {
	border-radius: 50% !important;
}

.bodywebsite .rounded-pill {
	border-radius: var(--bs-border-radius-pill) !important;
}

.bodywebsite .rounded-top {
	border-top-left-radius: var(--bs-border-radius) !important;
	border-top-right-radius: var(--bs-border-radius) !important;
}

.bodywebsite .rounded-end {
	border-top-right-radius: var(--bs-border-radius) !important;
	border-bottom-right-radius: var(--bs-border-radius) !important;
}

.bodywebsite .rounded-bottom {
	border-bottom-right-radius: var(--bs-border-radius) !important;
	border-bottom-left-radius: var(--bs-border-radius) !important;
}

.bodywebsite .rounded-start {
	border-bottom-left-radius: var(--bs-border-radius) !important;
	border-top-left-radius: var(--bs-border-radius) !important;
}

.bodywebsite .visible {
	visibility: visible !important;
}

.bodywebsite .invisible {
	visibility: hidden !important;
}

@media (min-width: 576px) {
	.bodywebsite .float-sm-start {
		float: left !important;
	}

	.bodywebsite .float-sm-end {
		float: right !important;
	}

	.bodywebsite .float-sm-none {
		float: none !important;
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

	.bodywebsite .d-sm-grid {
		display: grid !important;
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

	.bodywebsite .d-sm-none {
		display: none !important;
	}

	.bodywebsite .flex-sm-fill {
		flex: 1 1 auto !important;
	}

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

	.bodywebsite .flex-sm-grow-0 {
		flex-grow: 0 !important;
	}

	.bodywebsite .flex-sm-grow-1 {
		flex-grow: 1 !important;
	}

	.bodywebsite .flex-sm-shrink-0 {
		flex-shrink: 0 !important;
	}

	.bodywebsite .flex-sm-shrink-1 {
		flex-shrink: 1 !important;
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

	.bodywebsite .justify-content-sm-evenly {
		justify-content: space-evenly !important;
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

	.bodywebsite .order-sm-first {
		order: -1 !important;
	}

	.bodywebsite .order-sm-0 {
		order: 0 !important;
	}

	.bodywebsite .order-sm-1 {
		order: 1 !important;
	}

	.bodywebsite .order-sm-2 {
		order: 2 !important;
	}

	.bodywebsite .order-sm-3 {
		order: 3 !important;
	}

	.bodywebsite .order-sm-4 {
		order: 4 !important;
	}

	.bodywebsite .order-sm-5 {
		order: 5 !important;
	}

	.bodywebsite .order-sm-last {
		order: 6 !important;
	}

	.bodywebsite .m-sm-0 {
		margin: 0 !important;
	}

	.bodywebsite .m-sm-1 {
		margin: 0.25rem !important;
	}

	.bodywebsite .m-sm-2 {
		margin: 0.5rem !important;
	}

	.bodywebsite .m-sm-3 {
		margin: 1rem !important;
	}

	.bodywebsite .m-sm-4 {
		margin: 1.5rem !important;
	}

	.bodywebsite .m-sm-5 {
		margin: 3rem !important;
	}

	.bodywebsite .m-sm-auto {
		margin: auto !important;
	}

	.bodywebsite .mx-sm-0 {
		margin-right: 0 !important;
		margin-left: 0 !important;
	}

	.bodywebsite .mx-sm-1 {
		margin-right: 0.25rem !important;
		margin-left: 0.25rem !important;
	}

	.bodywebsite .mx-sm-2 {
		margin-right: 0.5rem !important;
		margin-left: 0.5rem !important;
	}

	.bodywebsite .mx-sm-3 {
		margin-right: 1rem !important;
		margin-left: 1rem !important;
	}

	.bodywebsite .mx-sm-4 {
		margin-right: 1.5rem !important;
		margin-left: 1.5rem !important;
	}

	.bodywebsite .mx-sm-5 {
		margin-right: 3rem !important;
		margin-left: 3rem !important;
	}

	.bodywebsite .mx-sm-auto {
		margin-right: auto !important;
		margin-left: auto !important;
	}

	.bodywebsite .my-sm-0 {
		margin-top: 0 !important;
		margin-bottom: 0 !important;
	}

	.bodywebsite .my-sm-1 {
		margin-top: 0.25rem !important;
		margin-bottom: 0.25rem !important;
	}

	.bodywebsite .my-sm-2 {
		margin-top: 0.5rem !important;
		margin-bottom: 0.5rem !important;
	}

	.bodywebsite .my-sm-3 {
		margin-top: 1rem !important;
		margin-bottom: 1rem !important;
	}

	.bodywebsite .my-sm-4 {
		margin-top: 1.5rem !important;
		margin-bottom: 1.5rem !important;
	}

	.bodywebsite .my-sm-5 {
		margin-top: 3rem !important;
		margin-bottom: 3rem !important;
	}

	.bodywebsite .my-sm-auto {
		margin-top: auto !important;
		margin-bottom: auto !important;
	}

	.bodywebsite .mt-sm-0 {
		margin-top: 0 !important;
	}

	.bodywebsite .mt-sm-1 {
		margin-top: 0.25rem !important;
	}

	.bodywebsite .mt-sm-2 {
		margin-top: 0.5rem !important;
	}

	.bodywebsite .mt-sm-3 {
		margin-top: 1rem !important;
	}

	.bodywebsite .mt-sm-4 {
		margin-top: 1.5rem !important;
	}

	.bodywebsite .mt-sm-5 {
		margin-top: 3rem !important;
	}

	.bodywebsite .mt-sm-auto {
		margin-top: auto !important;
	}

	.bodywebsite .me-sm-0 {
		margin-right: 0 !important;
	}

	.bodywebsite .me-sm-1 {
		margin-right: 0.25rem !important;
	}

	.bodywebsite .me-sm-2 {
		margin-right: 0.5rem !important;
	}

	.bodywebsite .me-sm-3 {
		margin-right: 1rem !important;
	}

	.bodywebsite .me-sm-4 {
		margin-right: 1.5rem !important;
	}

	.bodywebsite .me-sm-5 {
		margin-right: 3rem !important;
	}

	.bodywebsite .me-sm-auto {
		margin-right: auto !important;
	}

	.bodywebsite .mb-sm-0 {
		margin-bottom: 0 !important;
	}

	.bodywebsite .mb-sm-1 {
		margin-bottom: 0.25rem !important;
	}

	.bodywebsite .mb-sm-2 {
		margin-bottom: 0.5rem !important;
	}

	.bodywebsite .mb-sm-3 {
		margin-bottom: 1rem !important;
	}

	.bodywebsite .mb-sm-4 {
		margin-bottom: 1.5rem !important;
	}

	.bodywebsite .mb-sm-5 {
		margin-bottom: 3rem !important;
	}

	.bodywebsite .mb-sm-auto {
		margin-bottom: auto !important;
	}

	.bodywebsite .ms-sm-0 {
		margin-left: 0 !important;
	}

	.bodywebsite .ms-sm-1 {
		margin-left: 0.25rem !important;
	}

	.bodywebsite .ms-sm-2 {
		margin-left: 0.5rem !important;
	}

	.bodywebsite .ms-sm-3 {
		margin-left: 1rem !important;
	}

	.bodywebsite .ms-sm-4 {
		margin-left: 1.5rem !important;
	}

	.bodywebsite .ms-sm-5 {
		margin-left: 3rem !important;
	}

	.bodywebsite .ms-sm-auto {
		margin-left: auto !important;
	}

	.bodywebsite .p-sm-0 {
		padding: 0 !important;
	}

	.bodywebsite .p-sm-1 {
		padding: 0.25rem !important;
	}

	.bodywebsite .p-sm-2 {
		padding: 0.5rem !important;
	}

	.bodywebsite .p-sm-3 {
		padding: 1rem !important;
	}

	.bodywebsite .p-sm-4 {
		padding: 1.5rem !important;
	}

	.bodywebsite .p-sm-5 {
		padding: 3rem !important;
	}

	.bodywebsite .px-sm-0 {
		padding-right: 0 !important;
		padding-left: 0 !important;
	}

	.bodywebsite .px-sm-1 {
		padding-right: 0.25rem !important;
		padding-left: 0.25rem !important;
	}

	.bodywebsite .px-sm-2 {
		padding-right: 0.5rem !important;
		padding-left: 0.5rem !important;
	}

	.bodywebsite .px-sm-3 {
		padding-right: 1rem !important;
		padding-left: 1rem !important;
	}

	.bodywebsite .px-sm-4 {
		padding-right: 1.5rem !important;
		padding-left: 1.5rem !important;
	}

	.bodywebsite .px-sm-5 {
		padding-right: 3rem !important;
		padding-left: 3rem !important;
	}

	.bodywebsite .py-sm-0 {
		padding-top: 0 !important;
		padding-bottom: 0 !important;
	}

	.bodywebsite .py-sm-1 {
		padding-top: 0.25rem !important;
		padding-bottom: 0.25rem !important;
	}

	.bodywebsite .py-sm-2 {
		padding-top: 0.5rem !important;
		padding-bottom: 0.5rem !important;
	}

	.bodywebsite .py-sm-3 {
		padding-top: 1rem !important;
		padding-bottom: 1rem !important;
	}

	.bodywebsite .py-sm-4 {
		padding-top: 1.5rem !important;
		padding-bottom: 1.5rem !important;
	}

	.bodywebsite .py-sm-5 {
		padding-top: 3rem !important;
		padding-bottom: 3rem !important;
	}

	.bodywebsite .pt-sm-0 {
		padding-top: 0 !important;
	}

	.bodywebsite .pt-sm-1 {
		padding-top: 0.25rem !important;
	}

	.bodywebsite .pt-sm-2 {
		padding-top: 0.5rem !important;
	}

	.bodywebsite .pt-sm-3 {
		padding-top: 1rem !important;
	}

	.bodywebsite .pt-sm-4 {
		padding-top: 1.5rem !important;
	}

	.bodywebsite .pt-sm-5 {
		padding-top: 3rem !important;
	}

	.bodywebsite .pe-sm-0 {
		padding-right: 0 !important;
	}

	.bodywebsite .pe-sm-1 {
		padding-right: 0.25rem !important;
	}

	.bodywebsite .pe-sm-2 {
		padding-right: 0.5rem !important;
	}

	.bodywebsite .pe-sm-3 {
		padding-right: 1rem !important;
	}

	.bodywebsite .pe-sm-4 {
		padding-right: 1.5rem !important;
	}

	.bodywebsite .pe-sm-5 {
		padding-right: 3rem !important;
	}

	.bodywebsite .pb-sm-0 {
		padding-bottom: 0 !important;
	}

	.bodywebsite .pb-sm-1 {
		padding-bottom: 0.25rem !important;
	}

	.bodywebsite .pb-sm-2 {
		padding-bottom: 0.5rem !important;
	}

	.bodywebsite .pb-sm-3 {
		padding-bottom: 1rem !important;
	}

	.bodywebsite .pb-sm-4 {
		padding-bottom: 1.5rem !important;
	}

	.bodywebsite .pb-sm-5 {
		padding-bottom: 3rem !important;
	}

	.bodywebsite .ps-sm-0 {
		padding-left: 0 !important;
	}

	.bodywebsite .ps-sm-1 {
		padding-left: 0.25rem !important;
	}

	.bodywebsite .ps-sm-2 {
		padding-left: 0.5rem !important;
	}

	.bodywebsite .ps-sm-3 {
		padding-left: 1rem !important;
	}

	.bodywebsite .ps-sm-4 {
		padding-left: 1.5rem !important;
	}

	.bodywebsite .ps-sm-5 {
		padding-left: 3rem !important;
	}

	.bodywebsite .gap-sm-0 {
		gap: 0 !important;
	}

	.bodywebsite .gap-sm-1 {
		gap: 0.25rem !important;
	}

	.bodywebsite .gap-sm-2 {
		gap: 0.5rem !important;
	}

	.bodywebsite .gap-sm-3 {
		gap: 1rem !important;
	}

	.bodywebsite .gap-sm-4 {
		gap: 1.5rem !important;
	}

	.bodywebsite .gap-sm-5 {
		gap: 3rem !important;
	}

	.bodywebsite .text-sm-start {
		text-align: left !important;
	}

	.bodywebsite .text-sm-end {
		text-align: right !important;
	}

	.bodywebsite .text-sm-center {
		text-align: center !important;
	}
}

@media (min-width: 768px) {
	.bodywebsite .float-md-start {
		float: left !important;
	}

	.bodywebsite .float-md-end {
		float: right !important;
	}

	.bodywebsite .float-md-none {
		float: none !important;
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

	.bodywebsite .d-md-grid {
		display: grid !important;
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

	.bodywebsite .d-md-none {
		display: none !important;
	}

	.bodywebsite .flex-md-fill {
		flex: 1 1 auto !important;
	}

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

	.bodywebsite .flex-md-grow-0 {
		flex-grow: 0 !important;
	}

	.bodywebsite .flex-md-grow-1 {
		flex-grow: 1 !important;
	}

	.bodywebsite .flex-md-shrink-0 {
		flex-shrink: 0 !important;
	}

	.bodywebsite .flex-md-shrink-1 {
		flex-shrink: 1 !important;
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

	.bodywebsite .justify-content-md-evenly {
		justify-content: space-evenly !important;
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

	.bodywebsite .order-md-first {
		order: -1 !important;
	}

	.bodywebsite .order-md-0 {
		order: 0 !important;
	}

	.bodywebsite .order-md-1 {
		order: 1 !important;
	}

	.bodywebsite .order-md-2 {
		order: 2 !important;
	}

	.bodywebsite .order-md-3 {
		order: 3 !important;
	}

	.bodywebsite .order-md-4 {
		order: 4 !important;
	}

	.bodywebsite .order-md-5 {
		order: 5 !important;
	}

	.bodywebsite .order-md-last {
		order: 6 !important;
	}

	.bodywebsite .m-md-0 {
		margin: 0 !important;
	}

	.bodywebsite .m-md-1 {
		margin: 0.25rem !important;
	}

	.bodywebsite .m-md-2 {
		margin: 0.5rem !important;
	}

	.bodywebsite .m-md-3 {
		margin: 1rem !important;
	}

	.bodywebsite .m-md-4 {
		margin: 1.5rem !important;
	}

	.bodywebsite .m-md-5 {
		margin: 3rem !important;
	}

	.bodywebsite .m-md-auto {
		margin: auto !important;
	}

	.bodywebsite .mx-md-0 {
		margin-right: 0 !important;
		margin-left: 0 !important;
	}

	.bodywebsite .mx-md-1 {
		margin-right: 0.25rem !important;
		margin-left: 0.25rem !important;
	}

	.bodywebsite .mx-md-2 {
		margin-right: 0.5rem !important;
		margin-left: 0.5rem !important;
	}

	.bodywebsite .mx-md-3 {
		margin-right: 1rem !important;
		margin-left: 1rem !important;
	}

	.bodywebsite .mx-md-4 {
		margin-right: 1.5rem !important;
		margin-left: 1.5rem !important;
	}

	.bodywebsite .mx-md-5 {
		margin-right: 3rem !important;
		margin-left: 3rem !important;
	}

	.bodywebsite .mx-md-auto {
		margin-right: auto !important;
		margin-left: auto !important;
	}

	.bodywebsite .my-md-0 {
		margin-top: 0 !important;
		margin-bottom: 0 !important;
	}

	.bodywebsite .my-md-1 {
		margin-top: 0.25rem !important;
		margin-bottom: 0.25rem !important;
	}

	.bodywebsite .my-md-2 {
		margin-top: 0.5rem !important;
		margin-bottom: 0.5rem !important;
	}

	.bodywebsite .my-md-3 {
		margin-top: 1rem !important;
		margin-bottom: 1rem !important;
	}

	.bodywebsite .my-md-4 {
		margin-top: 1.5rem !important;
		margin-bottom: 1.5rem !important;
	}

	.bodywebsite .my-md-5 {
		margin-top: 3rem !important;
		margin-bottom: 3rem !important;
	}

	.bodywebsite .my-md-auto {
		margin-top: auto !important;
		margin-bottom: auto !important;
	}

	.bodywebsite .mt-md-0 {
		margin-top: 0 !important;
	}

	.bodywebsite .mt-md-1 {
		margin-top: 0.25rem !important;
	}

	.bodywebsite .mt-md-2 {
		margin-top: 0.5rem !important;
	}

	.bodywebsite .mt-md-3 {
		margin-top: 1rem !important;
	}

	.bodywebsite .mt-md-4 {
		margin-top: 1.5rem !important;
	}

	.bodywebsite .mt-md-5 {
		margin-top: 3rem !important;
	}

	.bodywebsite .mt-md-auto {
		margin-top: auto !important;
	}

	.bodywebsite .me-md-0 {
		margin-right: 0 !important;
	}

	.bodywebsite .me-md-1 {
		margin-right: 0.25rem !important;
	}

	.bodywebsite .me-md-2 {
		margin-right: 0.5rem !important;
	}

	.bodywebsite .me-md-3 {
		margin-right: 1rem !important;
	}

	.bodywebsite .me-md-4 {
		margin-right: 1.5rem !important;
	}

	.bodywebsite .me-md-5 {
		margin-right: 3rem !important;
	}

	.bodywebsite .me-md-auto {
		margin-right: auto !important;
	}

	.bodywebsite .mb-md-0 {
		margin-bottom: 0 !important;
	}

	.bodywebsite .mb-md-1 {
		margin-bottom: 0.25rem !important;
	}

	.bodywebsite .mb-md-2 {
		margin-bottom: 0.5rem !important;
	}

	.bodywebsite .mb-md-3 {
		margin-bottom: 1rem !important;
	}

	.bodywebsite .mb-md-4 {
		margin-bottom: 1.5rem !important;
	}

	.bodywebsite .mb-md-5 {
		margin-bottom: 3rem !important;
	}

	.bodywebsite .mb-md-auto {
		margin-bottom: auto !important;
	}

	.bodywebsite .ms-md-0 {
		margin-left: 0 !important;
	}

	.bodywebsite .ms-md-1 {
		margin-left: 0.25rem !important;
	}

	.bodywebsite .ms-md-2 {
		margin-left: 0.5rem !important;
	}

	.bodywebsite .ms-md-3 {
		margin-left: 1rem !important;
	}

	.bodywebsite .ms-md-4 {
		margin-left: 1.5rem !important;
	}

	.bodywebsite .ms-md-5 {
		margin-left: 3rem !important;
	}

	.bodywebsite .ms-md-auto {
		margin-left: auto !important;
	}

	.bodywebsite .p-md-0 {
		padding: 0 !important;
	}

	.bodywebsite .p-md-1 {
		padding: 0.25rem !important;
	}

	.bodywebsite .p-md-2 {
		padding: 0.5rem !important;
	}

	.bodywebsite .p-md-3 {
		padding: 1rem !important;
	}

	.bodywebsite .p-md-4 {
		padding: 1.5rem !important;
	}

	.bodywebsite .p-md-5 {
		padding: 3rem !important;
	}

	.bodywebsite .px-md-0 {
		padding-right: 0 !important;
		padding-left: 0 !important;
	}

	.bodywebsite .px-md-1 {
		padding-right: 0.25rem !important;
		padding-left: 0.25rem !important;
	}

	.bodywebsite .px-md-2 {
		padding-right: 0.5rem !important;
		padding-left: 0.5rem !important;
	}

	.bodywebsite .px-md-3 {
		padding-right: 1rem !important;
		padding-left: 1rem !important;
	}

	.bodywebsite .px-md-4 {
		padding-right: 1.5rem !important;
		padding-left: 1.5rem !important;
	}

	.bodywebsite .px-md-5 {
		padding-right: 3rem !important;
		padding-left: 3rem !important;
	}

	.bodywebsite .py-md-0 {
		padding-top: 0 !important;
		padding-bottom: 0 !important;
	}

	.bodywebsite .py-md-1 {
		padding-top: 0.25rem !important;
		padding-bottom: 0.25rem !important;
	}

	.bodywebsite .py-md-2 {
		padding-top: 0.5rem !important;
		padding-bottom: 0.5rem !important;
	}

	.bodywebsite .py-md-3 {
		padding-top: 1rem !important;
		padding-bottom: 1rem !important;
	}

	.bodywebsite .py-md-4 {
		padding-top: 1.5rem !important;
		padding-bottom: 1.5rem !important;
	}

	.bodywebsite .py-md-5 {
		padding-top: 3rem !important;
		padding-bottom: 3rem !important;
	}

	.bodywebsite .pt-md-0 {
		padding-top: 0 !important;
	}

	.bodywebsite .pt-md-1 {
		padding-top: 0.25rem !important;
	}

	.bodywebsite .pt-md-2 {
		padding-top: 0.5rem !important;
	}

	.bodywebsite .pt-md-3 {
		padding-top: 1rem !important;
	}

	.bodywebsite .pt-md-4 {
		padding-top: 1.5rem !important;
	}

	.bodywebsite .pt-md-5 {
		padding-top: 3rem !important;
	}

	.bodywebsite .pe-md-0 {
		padding-right: 0 !important;
	}

	.bodywebsite .pe-md-1 {
		padding-right: 0.25rem !important;
	}

	.bodywebsite .pe-md-2 {
		padding-right: 0.5rem !important;
	}

	.bodywebsite .pe-md-3 {
		padding-right: 1rem !important;
	}

	.bodywebsite .pe-md-4 {
		padding-right: 1.5rem !important;
	}

	.bodywebsite .pe-md-5 {
		padding-right: 3rem !important;
	}

	.bodywebsite .pb-md-0 {
		padding-bottom: 0 !important;
	}

	.bodywebsite .pb-md-1 {
		padding-bottom: 0.25rem !important;
	}

	.bodywebsite .pb-md-2 {
		padding-bottom: 0.5rem !important;
	}

	.bodywebsite .pb-md-3 {
		padding-bottom: 1rem !important;
	}

	.bodywebsite .pb-md-4 {
		padding-bottom: 1.5rem !important;
	}

	.bodywebsite .pb-md-5 {
		padding-bottom: 3rem !important;
	}

	.bodywebsite .ps-md-0 {
		padding-left: 0 !important;
	}

	.bodywebsite .ps-md-1 {
		padding-left: 0.25rem !important;
	}

	.bodywebsite .ps-md-2 {
		padding-left: 0.5rem !important;
	}

	.bodywebsite .ps-md-3 {
		padding-left: 1rem !important;
	}

	.bodywebsite .ps-md-4 {
		padding-left: 1.5rem !important;
	}

	.bodywebsite .ps-md-5 {
		padding-left: 3rem !important;
	}

	.bodywebsite .gap-md-0 {
		gap: 0 !important;
	}

	.bodywebsite .gap-md-1 {
		gap: 0.25rem !important;
	}

	.bodywebsite .gap-md-2 {
		gap: 0.5rem !important;
	}

	.bodywebsite .gap-md-3 {
		gap: 1rem !important;
	}

	.bodywebsite .gap-md-4 {
		gap: 1.5rem !important;
	}

	.bodywebsite .gap-md-5 {
		gap: 3rem !important;
	}

	.bodywebsite .text-md-start {
		text-align: left !important;
	}

	.bodywebsite .text-md-end {
		text-align: right !important;
	}

	.bodywebsite .text-md-center {
		text-align: center !important;
	}
}

@media (min-width: 992px) {
	.bodywebsite .float-lg-start {
		float: left !important;
	}

	.bodywebsite .float-lg-end {
		float: right !important;
	}

	.bodywebsite .float-lg-none {
		float: none !important;
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

	.bodywebsite .d-lg-grid {
		display: grid !important;
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

	.bodywebsite .d-lg-none {
		display: none !important;
	}

	.bodywebsite .flex-lg-fill {
		flex: 1 1 auto !important;
	}

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

	.bodywebsite .flex-lg-grow-0 {
		flex-grow: 0 !important;
	}

	.bodywebsite .flex-lg-grow-1 {
		flex-grow: 1 !important;
	}

	.bodywebsite .flex-lg-shrink-0 {
		flex-shrink: 0 !important;
	}

	.bodywebsite .flex-lg-shrink-1 {
		flex-shrink: 1 !important;
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

	.bodywebsite .justify-content-lg-evenly {
		justify-content: space-evenly !important;
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

	.bodywebsite .order-lg-first {
		order: -1 !important;
	}

	.bodywebsite .order-lg-0 {
		order: 0 !important;
	}

	.bodywebsite .order-lg-1 {
		order: 1 !important;
	}

	.bodywebsite .order-lg-2 {
		order: 2 !important;
	}

	.bodywebsite .order-lg-3 {
		order: 3 !important;
	}

	.bodywebsite .order-lg-4 {
		order: 4 !important;
	}

	.bodywebsite .order-lg-5 {
		order: 5 !important;
	}

	.bodywebsite .order-lg-last {
		order: 6 !important;
	}

	.bodywebsite .m-lg-0 {
		margin: 0 !important;
	}

	.bodywebsite .m-lg-1 {
		margin: 0.25rem !important;
	}

	.bodywebsite .m-lg-2 {
		margin: 0.5rem !important;
	}

	.bodywebsite .m-lg-3 {
		margin: 1rem !important;
	}

	.bodywebsite .m-lg-4 {
		margin: 1.5rem !important;
	}

	.bodywebsite .m-lg-5 {
		margin: 3rem !important;
	}

	.bodywebsite .m-lg-auto {
		margin: auto !important;
	}

	.bodywebsite .mx-lg-0 {
		margin-right: 0 !important;
		margin-left: 0 !important;
	}

	.bodywebsite .mx-lg-1 {
		margin-right: 0.25rem !important;
		margin-left: 0.25rem !important;
	}

	.bodywebsite .mx-lg-2 {
		margin-right: 0.5rem !important;
		margin-left: 0.5rem !important;
	}

	.bodywebsite .mx-lg-3 {
		margin-right: 1rem !important;
		margin-left: 1rem !important;
	}

	.bodywebsite .mx-lg-4 {
		margin-right: 1.5rem !important;
		margin-left: 1.5rem !important;
	}

	.bodywebsite .mx-lg-5 {
		margin-right: 3rem !important;
		margin-left: 3rem !important;
	}

	.bodywebsite .mx-lg-auto {
		margin-right: auto !important;
		margin-left: auto !important;
	}

	.bodywebsite .my-lg-0 {
		margin-top: 0 !important;
		margin-bottom: 0 !important;
	}

	.bodywebsite .my-lg-1 {
		margin-top: 0.25rem !important;
		margin-bottom: 0.25rem !important;
	}

	.bodywebsite .my-lg-2 {
		margin-top: 0.5rem !important;
		margin-bottom: 0.5rem !important;
	}

	.bodywebsite .my-lg-3 {
		margin-top: 1rem !important;
		margin-bottom: 1rem !important;
	}

	.bodywebsite .my-lg-4 {
		margin-top: 1.5rem !important;
		margin-bottom: 1.5rem !important;
	}

	.bodywebsite .my-lg-5 {
		margin-top: 3rem !important;
		margin-bottom: 3rem !important;
	}

	.bodywebsite .my-lg-auto {
		margin-top: auto !important;
		margin-bottom: auto !important;
	}

	.bodywebsite .mt-lg-0 {
		margin-top: 0 !important;
	}

	.bodywebsite .mt-lg-1 {
		margin-top: 0.25rem !important;
	}

	.bodywebsite .mt-lg-2 {
		margin-top: 0.5rem !important;
	}

	.bodywebsite .mt-lg-3 {
		margin-top: 1rem !important;
	}

	.bodywebsite .mt-lg-4 {
		margin-top: 1.5rem !important;
	}

	.bodywebsite .mt-lg-5 {
		margin-top: 3rem !important;
	}

	.bodywebsite .mt-lg-auto {
		margin-top: auto !important;
	}

	.bodywebsite .me-lg-0 {
		margin-right: 0 !important;
	}

	.bodywebsite .me-lg-1 {
		margin-right: 0.25rem !important;
	}

	.bodywebsite .me-lg-2 {
		margin-right: 0.5rem !important;
	}

	.bodywebsite .me-lg-3 {
		margin-right: 1rem !important;
	}

	.bodywebsite .me-lg-4 {
		margin-right: 1.5rem !important;
	}

	.bodywebsite .me-lg-5 {
		margin-right: 3rem !important;
	}

	.bodywebsite .me-lg-auto {
		margin-right: auto !important;
	}

	.bodywebsite .mb-lg-0 {
		margin-bottom: 0 !important;
	}

	.bodywebsite .mb-lg-1 {
		margin-bottom: 0.25rem !important;
	}

	.bodywebsite .mb-lg-2 {
		margin-bottom: 0.5rem !important;
	}

	.bodywebsite .mb-lg-3 {
		margin-bottom: 1rem !important;
	}

	.bodywebsite .mb-lg-4 {
		margin-bottom: 1.5rem !important;
	}

	.bodywebsite .mb-lg-5 {
		margin-bottom: 3rem !important;
	}

	.bodywebsite .mb-lg-auto {
		margin-bottom: auto !important;
	}

	.bodywebsite .ms-lg-0 {
		margin-left: 0 !important;
	}

	.bodywebsite .ms-lg-1 {
		margin-left: 0.25rem !important;
	}

	.bodywebsite .ms-lg-2 {
		margin-left: 0.5rem !important;
	}

	.bodywebsite .ms-lg-3 {
		margin-left: 1rem !important;
	}

	.bodywebsite .ms-lg-4 {
		margin-left: 1.5rem !important;
	}

	.bodywebsite .ms-lg-5 {
		margin-left: 3rem !important;
	}

	.bodywebsite .ms-lg-auto {
		margin-left: auto !important;
	}

	.bodywebsite .p-lg-0 {
		padding: 0 !important;
	}

	.bodywebsite .p-lg-1 {
		padding: 0.25rem !important;
	}

	.bodywebsite .p-lg-2 {
		padding: 0.5rem !important;
	}

	.bodywebsite .p-lg-3 {
		padding: 1rem !important;
	}

	.bodywebsite .p-lg-4 {
		padding: 1.5rem !important;
	}

	.bodywebsite .p-lg-5 {
		padding: 3rem !important;
	}

	.bodywebsite .px-lg-0 {
		padding-right: 0 !important;
		padding-left: 0 !important;
	}

	.bodywebsite .px-lg-1 {
		padding-right: 0.25rem !important;
		padding-left: 0.25rem !important;
	}

	.bodywebsite .px-lg-2 {
		padding-right: 0.5rem !important;
		padding-left: 0.5rem !important;
	}

	.bodywebsite .px-lg-3 {
		padding-right: 1rem !important;
		padding-left: 1rem !important;
	}

	.bodywebsite .px-lg-4 {
		padding-right: 1.5rem !important;
		padding-left: 1.5rem !important;
	}

	.bodywebsite .px-lg-5 {
		padding-right: 3rem !important;
		padding-left: 3rem !important;
	}

	.bodywebsite .py-lg-0 {
		padding-top: 0 !important;
		padding-bottom: 0 !important;
	}

	.bodywebsite .py-lg-1 {
		padding-top: 0.25rem !important;
		padding-bottom: 0.25rem !important;
	}

	.bodywebsite .py-lg-2 {
		padding-top: 0.5rem !important;
		padding-bottom: 0.5rem !important;
	}

	.bodywebsite .py-lg-3 {
		padding-top: 1rem !important;
		padding-bottom: 1rem !important;
	}

	.bodywebsite .py-lg-4 {
		padding-top: 1.5rem !important;
		padding-bottom: 1.5rem !important;
	}

	.bodywebsite .py-lg-5 {
		padding-top: 3rem !important;
		padding-bottom: 3rem !important;
	}

	.bodywebsite .pt-lg-0 {
		padding-top: 0 !important;
	}

	.bodywebsite .pt-lg-1 {
		padding-top: 0.25rem !important;
	}

	.bodywebsite .pt-lg-2 {
		padding-top: 0.5rem !important;
	}

	.bodywebsite .pt-lg-3 {
		padding-top: 1rem !important;
	}

	.bodywebsite .pt-lg-4 {
		padding-top: 1.5rem !important;
	}

	.bodywebsite .pt-lg-5 {
		padding-top: 3rem !important;
	}

	.bodywebsite .pe-lg-0 {
		padding-right: 0 !important;
	}

	.bodywebsite .pe-lg-1 {
		padding-right: 0.25rem !important;
	}

	.bodywebsite .pe-lg-2 {
		padding-right: 0.5rem !important;
	}

	.bodywebsite .pe-lg-3 {
		padding-right: 1rem !important;
	}

	.bodywebsite .pe-lg-4 {
		padding-right: 1.5rem !important;
	}

	.bodywebsite .pe-lg-5 {
		padding-right: 3rem !important;
	}

	.bodywebsite .pb-lg-0 {
		padding-bottom: 0 !important;
	}

	.bodywebsite .pb-lg-1 {
		padding-bottom: 0.25rem !important;
	}

	.bodywebsite .pb-lg-2 {
		padding-bottom: 0.5rem !important;
	}

	.bodywebsite .pb-lg-3 {
		padding-bottom: 1rem !important;
	}

	.bodywebsite .pb-lg-4 {
		padding-bottom: 1.5rem !important;
	}

	.bodywebsite .pb-lg-5 {
		padding-bottom: 3rem !important;
	}

	.bodywebsite .ps-lg-0 {
		padding-left: 0 !important;
	}

	.bodywebsite .ps-lg-1 {
		padding-left: 0.25rem !important;
	}

	.bodywebsite .ps-lg-2 {
		padding-left: 0.5rem !important;
	}

	.bodywebsite .ps-lg-3 {
		padding-left: 1rem !important;
	}

	.bodywebsite .ps-lg-4 {
		padding-left: 1.5rem !important;
	}

	.bodywebsite .ps-lg-5 {
		padding-left: 3rem !important;
	}

	.bodywebsite .gap-lg-0 {
		gap: 0 !important;
	}

	.bodywebsite .gap-lg-1 {
		gap: 0.25rem !important;
	}

	.bodywebsite .gap-lg-2 {
		gap: 0.5rem !important;
	}

	.bodywebsite .gap-lg-3 {
		gap: 1rem !important;
	}

	.bodywebsite .gap-lg-4 {
		gap: 1.5rem !important;
	}

	.bodywebsite .gap-lg-5 {
		gap: 3rem !important;
	}

	.bodywebsite .text-lg-start {
		text-align: left !important;
	}

	.bodywebsite .text-lg-end {
		text-align: right !important;
	}

	.bodywebsite .text-lg-center {
		text-align: center !important;
	}
}

@media (min-width: 1200px) {
	.bodywebsite .float-xl-start {
		float: left !important;
	}

	.bodywebsite .float-xl-end {
		float: right !important;
	}

	.bodywebsite .float-xl-none {
		float: none !important;
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

	.bodywebsite .d-xl-grid {
		display: grid !important;
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

	.bodywebsite .d-xl-none {
		display: none !important;
	}

	.bodywebsite .flex-xl-fill {
		flex: 1 1 auto !important;
	}

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

	.bodywebsite .flex-xl-grow-0 {
		flex-grow: 0 !important;
	}

	.bodywebsite .flex-xl-grow-1 {
		flex-grow: 1 !important;
	}

	.bodywebsite .flex-xl-shrink-0 {
		flex-shrink: 0 !important;
	}

	.bodywebsite .flex-xl-shrink-1 {
		flex-shrink: 1 !important;
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

	.bodywebsite .justify-content-xl-evenly {
		justify-content: space-evenly !important;
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

	.bodywebsite .order-xl-first {
		order: -1 !important;
	}

	.bodywebsite .order-xl-0 {
		order: 0 !important;
	}

	.bodywebsite .order-xl-1 {
		order: 1 !important;
	}

	.bodywebsite .order-xl-2 {
		order: 2 !important;
	}

	.bodywebsite .order-xl-3 {
		order: 3 !important;
	}

	.bodywebsite .order-xl-4 {
		order: 4 !important;
	}

	.bodywebsite .order-xl-5 {
		order: 5 !important;
	}

	.bodywebsite .order-xl-last {
		order: 6 !important;
	}

	.bodywebsite .m-xl-0 {
		margin: 0 !important;
	}

	.bodywebsite .m-xl-1 {
		margin: 0.25rem !important;
	}

	.bodywebsite .m-xl-2 {
		margin: 0.5rem !important;
	}

	.bodywebsite .m-xl-3 {
		margin: 1rem !important;
	}

	.bodywebsite .m-xl-4 {
		margin: 1.5rem !important;
	}

	.bodywebsite .m-xl-5 {
		margin: 3rem !important;
	}

	.bodywebsite .m-xl-auto {
		margin: auto !important;
	}

	.bodywebsite .mx-xl-0 {
		margin-right: 0 !important;
		margin-left: 0 !important;
	}

	.bodywebsite .mx-xl-1 {
		margin-right: 0.25rem !important;
		margin-left: 0.25rem !important;
	}

	.bodywebsite .mx-xl-2 {
		margin-right: 0.5rem !important;
		margin-left: 0.5rem !important;
	}

	.bodywebsite .mx-xl-3 {
		margin-right: 1rem !important;
		margin-left: 1rem !important;
	}

	.bodywebsite .mx-xl-4 {
		margin-right: 1.5rem !important;
		margin-left: 1.5rem !important;
	}

	.bodywebsite .mx-xl-5 {
		margin-right: 3rem !important;
		margin-left: 3rem !important;
	}

	.bodywebsite .mx-xl-auto {
		margin-right: auto !important;
		margin-left: auto !important;
	}

	.bodywebsite .my-xl-0 {
		margin-top: 0 !important;
		margin-bottom: 0 !important;
	}

	.bodywebsite .my-xl-1 {
		margin-top: 0.25rem !important;
		margin-bottom: 0.25rem !important;
	}

	.bodywebsite .my-xl-2 {
		margin-top: 0.5rem !important;
		margin-bottom: 0.5rem !important;
	}

	.bodywebsite .my-xl-3 {
		margin-top: 1rem !important;
		margin-bottom: 1rem !important;
	}

	.bodywebsite .my-xl-4 {
		margin-top: 1.5rem !important;
		margin-bottom: 1.5rem !important;
	}

	.bodywebsite .my-xl-5 {
		margin-top: 3rem !important;
		margin-bottom: 3rem !important;
	}

	.bodywebsite .my-xl-auto {
		margin-top: auto !important;
		margin-bottom: auto !important;
	}

	.bodywebsite .mt-xl-0 {
		margin-top: 0 !important;
	}

	.bodywebsite .mt-xl-1 {
		margin-top: 0.25rem !important;
	}

	.bodywebsite .mt-xl-2 {
		margin-top: 0.5rem !important;
	}

	.bodywebsite .mt-xl-3 {
		margin-top: 1rem !important;
	}

	.bodywebsite .mt-xl-4 {
		margin-top: 1.5rem !important;
	}

	.bodywebsite .mt-xl-5 {
		margin-top: 3rem !important;
	}

	.bodywebsite .mt-xl-auto {
		margin-top: auto !important;
	}

	.bodywebsite .me-xl-0 {
		margin-right: 0 !important;
	}

	.bodywebsite .me-xl-1 {
		margin-right: 0.25rem !important;
	}

	.bodywebsite .me-xl-2 {
		margin-right: 0.5rem !important;
	}

	.bodywebsite .me-xl-3 {
		margin-right: 1rem !important;
	}

	.bodywebsite .me-xl-4 {
		margin-right: 1.5rem !important;
	}

	.bodywebsite .me-xl-5 {
		margin-right: 3rem !important;
	}

	.bodywebsite .me-xl-auto {
		margin-right: auto !important;
	}

	.bodywebsite .mb-xl-0 {
		margin-bottom: 0 !important;
	}

	.bodywebsite .mb-xl-1 {
		margin-bottom: 0.25rem !important;
	}

	.bodywebsite .mb-xl-2 {
		margin-bottom: 0.5rem !important;
	}

	.bodywebsite .mb-xl-3 {
		margin-bottom: 1rem !important;
	}

	.bodywebsite .mb-xl-4 {
		margin-bottom: 1.5rem !important;
	}

	.bodywebsite .mb-xl-5 {
		margin-bottom: 3rem !important;
	}

	.bodywebsite .mb-xl-auto {
		margin-bottom: auto !important;
	}

	.bodywebsite .ms-xl-0 {
		margin-left: 0 !important;
	}

	.bodywebsite .ms-xl-1 {
		margin-left: 0.25rem !important;
	}

	.bodywebsite .ms-xl-2 {
		margin-left: 0.5rem !important;
	}

	.bodywebsite .ms-xl-3 {
		margin-left: 1rem !important;
	}

	.bodywebsite .ms-xl-4 {
		margin-left: 1.5rem !important;
	}

	.bodywebsite .ms-xl-5 {
		margin-left: 3rem !important;
	}

	.bodywebsite .ms-xl-auto {
		margin-left: auto !important;
	}

	.bodywebsite .p-xl-0 {
		padding: 0 !important;
	}

	.bodywebsite .p-xl-1 {
		padding: 0.25rem !important;
	}

	.bodywebsite .p-xl-2 {
		padding: 0.5rem !important;
	}

	.bodywebsite .p-xl-3 {
		padding: 1rem !important;
	}

	.bodywebsite .p-xl-4 {
		padding: 1.5rem !important;
	}

	.bodywebsite .p-xl-5 {
		padding: 3rem !important;
	}

	.bodywebsite .px-xl-0 {
		padding-right: 0 !important;
		padding-left: 0 !important;
	}

	.bodywebsite .px-xl-1 {
		padding-right: 0.25rem !important;
		padding-left: 0.25rem !important;
	}

	.bodywebsite .px-xl-2 {
		padding-right: 0.5rem !important;
		padding-left: 0.5rem !important;
	}

	.bodywebsite .px-xl-3 {
		padding-right: 1rem !important;
		padding-left: 1rem !important;
	}

	.bodywebsite .px-xl-4 {
		padding-right: 1.5rem !important;
		padding-left: 1.5rem !important;
	}

	.bodywebsite .px-xl-5 {
		padding-right: 3rem !important;
		padding-left: 3rem !important;
	}

	.bodywebsite .py-xl-0 {
		padding-top: 0 !important;
		padding-bottom: 0 !important;
	}

	.bodywebsite .py-xl-1 {
		padding-top: 0.25rem !important;
		padding-bottom: 0.25rem !important;
	}

	.bodywebsite .py-xl-2 {
		padding-top: 0.5rem !important;
		padding-bottom: 0.5rem !important;
	}

	.bodywebsite .py-xl-3 {
		padding-top: 1rem !important;
		padding-bottom: 1rem !important;
	}

	.bodywebsite .py-xl-4 {
		padding-top: 1.5rem !important;
		padding-bottom: 1.5rem !important;
	}

	.bodywebsite .py-xl-5 {
		padding-top: 3rem !important;
		padding-bottom: 3rem !important;
	}

	.bodywebsite .pt-xl-0 {
		padding-top: 0 !important;
	}

	.bodywebsite .pt-xl-1 {
		padding-top: 0.25rem !important;
	}

	.bodywebsite .pt-xl-2 {
		padding-top: 0.5rem !important;
	}

	.bodywebsite .pt-xl-3 {
		padding-top: 1rem !important;
	}

	.bodywebsite .pt-xl-4 {
		padding-top: 1.5rem !important;
	}

	.bodywebsite .pt-xl-5 {
		padding-top: 3rem !important;
	}

	.bodywebsite .pe-xl-0 {
		padding-right: 0 !important;
	}

	.bodywebsite .pe-xl-1 {
		padding-right: 0.25rem !important;
	}

	.bodywebsite .pe-xl-2 {
		padding-right: 0.5rem !important;
	}

	.bodywebsite .pe-xl-3 {
		padding-right: 1rem !important;
	}

	.bodywebsite .pe-xl-4 {
		padding-right: 1.5rem !important;
	}

	.bodywebsite .pe-xl-5 {
		padding-right: 3rem !important;
	}

	.bodywebsite .pb-xl-0 {
		padding-bottom: 0 !important;
	}

	.bodywebsite .pb-xl-1 {
		padding-bottom: 0.25rem !important;
	}

	.bodywebsite .pb-xl-2 {
		padding-bottom: 0.5rem !important;
	}

	.bodywebsite .pb-xl-3 {
		padding-bottom: 1rem !important;
	}

	.bodywebsite .pb-xl-4 {
		padding-bottom: 1.5rem !important;
	}

	.bodywebsite .pb-xl-5 {
		padding-bottom: 3rem !important;
	}

	.bodywebsite .ps-xl-0 {
		padding-left: 0 !important;
	}

	.bodywebsite .ps-xl-1 {
		padding-left: 0.25rem !important;
	}

	.bodywebsite .ps-xl-2 {
		padding-left: 0.5rem !important;
	}

	.bodywebsite .ps-xl-3 {
		padding-left: 1rem !important;
	}

	.bodywebsite .ps-xl-4 {
		padding-left: 1.5rem !important;
	}

	.bodywebsite .ps-xl-5 {
		padding-left: 3rem !important;
	}

	.bodywebsite .gap-xl-0 {
		gap: 0 !important;
	}

	.bodywebsite .gap-xl-1 {
		gap: 0.25rem !important;
	}

	.bodywebsite .gap-xl-2 {
		gap: 0.5rem !important;
	}

	.bodywebsite .gap-xl-3 {
		gap: 1rem !important;
	}

	.bodywebsite .gap-xl-4 {
		gap: 1.5rem !important;
	}

	.bodywebsite .gap-xl-5 {
		gap: 3rem !important;
	}

	.bodywebsite .text-xl-start {
		text-align: left !important;
	}

	.bodywebsite .text-xl-end {
		text-align: right !important;
	}

	.bodywebsite .text-xl-center {
		text-align: center !important;
	}
}

@media (min-width: 1400px) {
	.bodywebsite .float-xxl-start {
		float: left !important;
	}

	.bodywebsite .float-xxl-end {
		float: right !important;
	}

	.bodywebsite .float-xxl-none {
		float: none !important;
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

	.bodywebsite .d-xxl-grid {
		display: grid !important;
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

	.bodywebsite .d-xxl-none {
		display: none !important;
	}

	.bodywebsite .flex-xxl-fill {
		flex: 1 1 auto !important;
	}

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

	.bodywebsite .flex-xxl-grow-0 {
		flex-grow: 0 !important;
	}

	.bodywebsite .flex-xxl-grow-1 {
		flex-grow: 1 !important;
	}

	.bodywebsite .flex-xxl-shrink-0 {
		flex-shrink: 0 !important;
	}

	.bodywebsite .flex-xxl-shrink-1 {
		flex-shrink: 1 !important;
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

	.bodywebsite .justify-content-xxl-evenly {
		justify-content: space-evenly !important;
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

	.bodywebsite .order-xxl-first {
		order: -1 !important;
	}

	.bodywebsite .order-xxl-0 {
		order: 0 !important;
	}

	.bodywebsite .order-xxl-1 {
		order: 1 !important;
	}

	.bodywebsite .order-xxl-2 {
		order: 2 !important;
	}

	.bodywebsite .order-xxl-3 {
		order: 3 !important;
	}

	.bodywebsite .order-xxl-4 {
		order: 4 !important;
	}

	.bodywebsite .order-xxl-5 {
		order: 5 !important;
	}

	.bodywebsite .order-xxl-last {
		order: 6 !important;
	}

	.bodywebsite .m-xxl-0 {
		margin: 0 !important;
	}

	.bodywebsite .m-xxl-1 {
		margin: 0.25rem !important;
	}

	.bodywebsite .m-xxl-2 {
		margin: 0.5rem !important;
	}

	.bodywebsite .m-xxl-3 {
		margin: 1rem !important;
	}

	.bodywebsite .m-xxl-4 {
		margin: 1.5rem !important;
	}

	.bodywebsite .m-xxl-5 {
		margin: 3rem !important;
	}

	.bodywebsite .m-xxl-auto {
		margin: auto !important;
	}

	.bodywebsite .mx-xxl-0 {
		margin-right: 0 !important;
		margin-left: 0 !important;
	}

	.bodywebsite .mx-xxl-1 {
		margin-right: 0.25rem !important;
		margin-left: 0.25rem !important;
	}

	.bodywebsite .mx-xxl-2 {
		margin-right: 0.5rem !important;
		margin-left: 0.5rem !important;
	}

	.bodywebsite .mx-xxl-3 {
		margin-right: 1rem !important;
		margin-left: 1rem !important;
	}

	.bodywebsite .mx-xxl-4 {
		margin-right: 1.5rem !important;
		margin-left: 1.5rem !important;
	}

	.bodywebsite .mx-xxl-5 {
		margin-right: 3rem !important;
		margin-left: 3rem !important;
	}

	.bodywebsite .mx-xxl-auto {
		margin-right: auto !important;
		margin-left: auto !important;
	}

	.bodywebsite .my-xxl-0 {
		margin-top: 0 !important;
		margin-bottom: 0 !important;
	}

	.bodywebsite .my-xxl-1 {
		margin-top: 0.25rem !important;
		margin-bottom: 0.25rem !important;
	}

	.bodywebsite .my-xxl-2 {
		margin-top: 0.5rem !important;
		margin-bottom: 0.5rem !important;
	}

	.bodywebsite .my-xxl-3 {
		margin-top: 1rem !important;
		margin-bottom: 1rem !important;
	}

	.bodywebsite .my-xxl-4 {
		margin-top: 1.5rem !important;
		margin-bottom: 1.5rem !important;
	}

	.bodywebsite .my-xxl-5 {
		margin-top: 3rem !important;
		margin-bottom: 3rem !important;
	}

	.bodywebsite .my-xxl-auto {
		margin-top: auto !important;
		margin-bottom: auto !important;
	}

	.bodywebsite .mt-xxl-0 {
		margin-top: 0 !important;
	}

	.bodywebsite .mt-xxl-1 {
		margin-top: 0.25rem !important;
	}

	.bodywebsite .mt-xxl-2 {
		margin-top: 0.5rem !important;
	}

	.bodywebsite .mt-xxl-3 {
		margin-top: 1rem !important;
	}

	.bodywebsite .mt-xxl-4 {
		margin-top: 1.5rem !important;
	}

	.bodywebsite .mt-xxl-5 {
		margin-top: 3rem !important;
	}

	.bodywebsite .mt-xxl-auto {
		margin-top: auto !important;
	}

	.bodywebsite .me-xxl-0 {
		margin-right: 0 !important;
	}

	.bodywebsite .me-xxl-1 {
		margin-right: 0.25rem !important;
	}

	.bodywebsite .me-xxl-2 {
		margin-right: 0.5rem !important;
	}

	.bodywebsite .me-xxl-3 {
		margin-right: 1rem !important;
	}

	.bodywebsite .me-xxl-4 {
		margin-right: 1.5rem !important;
	}

	.bodywebsite .me-xxl-5 {
		margin-right: 3rem !important;
	}

	.bodywebsite .me-xxl-auto {
		margin-right: auto !important;
	}

	.bodywebsite .mb-xxl-0 {
		margin-bottom: 0 !important;
	}

	.bodywebsite .mb-xxl-1 {
		margin-bottom: 0.25rem !important;
	}

	.bodywebsite .mb-xxl-2 {
		margin-bottom: 0.5rem !important;
	}

	.bodywebsite .mb-xxl-3 {
		margin-bottom: 1rem !important;
	}

	.bodywebsite .mb-xxl-4 {
		margin-bottom: 1.5rem !important;
	}

	.bodywebsite .mb-xxl-5 {
		margin-bottom: 3rem !important;
	}

	.bodywebsite .mb-xxl-auto {
		margin-bottom: auto !important;
	}

	.bodywebsite .ms-xxl-0 {
		margin-left: 0 !important;
	}

	.bodywebsite .ms-xxl-1 {
		margin-left: 0.25rem !important;
	}

	.bodywebsite .ms-xxl-2 {
		margin-left: 0.5rem !important;
	}

	.bodywebsite .ms-xxl-3 {
		margin-left: 1rem !important;
	}

	.bodywebsite .ms-xxl-4 {
		margin-left: 1.5rem !important;
	}

	.bodywebsite .ms-xxl-5 {
		margin-left: 3rem !important;
	}

	.bodywebsite .ms-xxl-auto {
		margin-left: auto !important;
	}

	.bodywebsite .p-xxl-0 {
		padding: 0 !important;
	}

	.bodywebsite .p-xxl-1 {
		padding: 0.25rem !important;
	}

	.bodywebsite .p-xxl-2 {
		padding: 0.5rem !important;
	}

	.bodywebsite .p-xxl-3 {
		padding: 1rem !important;
	}

	.bodywebsite .p-xxl-4 {
		padding: 1.5rem !important;
	}

	.bodywebsite .p-xxl-5 {
		padding: 3rem !important;
	}

	.bodywebsite .px-xxl-0 {
		padding-right: 0 !important;
		padding-left: 0 !important;
	}

	.bodywebsite .px-xxl-1 {
		padding-right: 0.25rem !important;
		padding-left: 0.25rem !important;
	}

	.bodywebsite .px-xxl-2 {
		padding-right: 0.5rem !important;
		padding-left: 0.5rem !important;
	}

	.bodywebsite .px-xxl-3 {
		padding-right: 1rem !important;
		padding-left: 1rem !important;
	}

	.bodywebsite .px-xxl-4 {
		padding-right: 1.5rem !important;
		padding-left: 1.5rem !important;
	}

	.bodywebsite .px-xxl-5 {
		padding-right: 3rem !important;
		padding-left: 3rem !important;
	}

	.bodywebsite .py-xxl-0 {
		padding-top: 0 !important;
		padding-bottom: 0 !important;
	}

	.bodywebsite .py-xxl-1 {
		padding-top: 0.25rem !important;
		padding-bottom: 0.25rem !important;
	}

	.bodywebsite .py-xxl-2 {
		padding-top: 0.5rem !important;
		padding-bottom: 0.5rem !important;
	}

	.bodywebsite .py-xxl-3 {
		padding-top: 1rem !important;
		padding-bottom: 1rem !important;
	}

	.bodywebsite .py-xxl-4 {
		padding-top: 1.5rem !important;
		padding-bottom: 1.5rem !important;
	}

	.bodywebsite .py-xxl-5 {
		padding-top: 3rem !important;
		padding-bottom: 3rem !important;
	}

	.bodywebsite .pt-xxl-0 {
		padding-top: 0 !important;
	}

	.bodywebsite .pt-xxl-1 {
		padding-top: 0.25rem !important;
	}

	.bodywebsite .pt-xxl-2 {
		padding-top: 0.5rem !important;
	}

	.bodywebsite .pt-xxl-3 {
		padding-top: 1rem !important;
	}

	.bodywebsite .pt-xxl-4 {
		padding-top: 1.5rem !important;
	}

	.bodywebsite .pt-xxl-5 {
		padding-top: 3rem !important;
	}

	.bodywebsite .pe-xxl-0 {
		padding-right: 0 !important;
	}

	.bodywebsite .pe-xxl-1 {
		padding-right: 0.25rem !important;
	}

	.bodywebsite .pe-xxl-2 {
		padding-right: 0.5rem !important;
	}

	.bodywebsite .pe-xxl-3 {
		padding-right: 1rem !important;
	}

	.bodywebsite .pe-xxl-4 {
		padding-right: 1.5rem !important;
	}

	.bodywebsite .pe-xxl-5 {
		padding-right: 3rem !important;
	}

	.bodywebsite .pb-xxl-0 {
		padding-bottom: 0 !important;
	}

	.bodywebsite .pb-xxl-1 {
		padding-bottom: 0.25rem !important;
	}

	.bodywebsite .pb-xxl-2 {
		padding-bottom: 0.5rem !important;
	}

	.bodywebsite .pb-xxl-3 {
		padding-bottom: 1rem !important;
	}

	.bodywebsite .pb-xxl-4 {
		padding-bottom: 1.5rem !important;
	}

	.bodywebsite .pb-xxl-5 {
		padding-bottom: 3rem !important;
	}

	.bodywebsite .ps-xxl-0 {
		padding-left: 0 !important;
	}

	.bodywebsite .ps-xxl-1 {
		padding-left: 0.25rem !important;
	}

	.bodywebsite .ps-xxl-2 {
		padding-left: 0.5rem !important;
	}

	.bodywebsite .ps-xxl-3 {
		padding-left: 1rem !important;
	}

	.bodywebsite .ps-xxl-4 {
		padding-left: 1.5rem !important;
	}

	.bodywebsite .ps-xxl-5 {
		padding-left: 3rem !important;
	}

	.bodywebsite .gap-xxl-0 {
		gap: 0 !important;
	}

	.bodywebsite .gap-xxl-1 {
		gap: 0.25rem !important;
	}

	.bodywebsite .gap-xxl-2 {
		gap: 0.5rem !important;
	}

	.bodywebsite .gap-xxl-3 {
		gap: 1rem !important;
	}

	.bodywebsite .gap-xxl-4 {
		gap: 1.5rem !important;
	}

	.bodywebsite .gap-xxl-5 {
		gap: 3rem !important;
	}

	.bodywebsite .text-xxl-start {
		text-align: left !important;
	}

	.bodywebsite .text-xxl-end {
		text-align: right !important;
	}

	.bodywebsite .text-xxl-center {
		text-align: center !important;
	}
}

@media (min-width: 1200px) {
	.bodywebsite .fs-1 {
		font-size: 2.5rem !important;
	}

	.bodywebsite .fs-2 {
		font-size: 2rem !important;
	}

	.bodywebsite .fs-3 {
		font-size: 1.75rem !important;
	}

	.bodywebsite .fs-4 {
		font-size: 1.5rem !important;
	}
}

@media print {
	.bodywebsite .d-print-inline {
		display: inline !important;
	}

	.bodywebsite .d-print-inline-block {
		display: inline-block !important;
	}

	.bodywebsite .d-print-block {
		display: block !important;
	}

	.bodywebsite .d-print-grid {
		display: grid !important;
	}

	.bodywebsite .d-print-table {
		display: table !important;
	}

	.bodywebsite .d-print-table-row {
		display: table-row !important;
	}

	.bodywebsite .d-print-table-cell {
		display: table-cell !important;
	}

	.bodywebsite .d-print-flex {
		display: flex !important;
	}

	.bodywebsite .d-print-inline-flex {
		display: inline-flex !important;
	}

	.bodywebsite .d-print-none {
		display: none !important;
	}
}

/* CSS content (all pages) */
.bodywebsite h1,
.bodywebsite h2,
.bodywebsite h3,
.bodywebsite h4,
.bodywebsite h5,
.bodywebsite h6 {
	font-family: 'Hurricane', cursive;
}

.bodywebsite #title {
	font-size: 100px;
}
.bodywebsite #mysection1{
	font-family: 'Inconsolata', monospace;
	color: white;
	height: 80%;
}

.bodywebsite .full-height {
	height: 100vh;
}
.bodywebsite .color {
	color: #aefeff;
}

.bodywebsite .btn-color {
	font-weight: bold;
	color: #35858b;
	border-color: #35858b;
}

.bodywebsite .btn-color:hover {
	background-color: #35858b;
	color: #fff;
}

.bodywebsite .btn-color-filled {
	background-color: #35858b;
	color: #072227;
}
.bodywebsite #products {
	background-color: whitesmoke;
}

.bodywebsite #home, .bodywebsite  #contact{
	background-color: #072227;
}

.bodywebsite footer {
	position: fixed;
	bottom: 0;
	left: 50%;
	transform: translateX(-50%);
}

/*# sourceMappingURL=bootstrap.css.map */
<?php // BEGIN PHP
$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "css");
// END PHP
