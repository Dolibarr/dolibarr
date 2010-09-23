<?php
/* Copyright (C) 2010 Regis Houssin <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */
header('Cache-Control: Public, must-revalidate');
header("Content-type: text/html; charset=".$conf->file->character_set_client);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
         "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<!-- BEGIN HEADER SMARTPHONE TEMPLATE -->

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title><?php echo $this->title; ?></title>
<meta name="robots" content="noindex,nofollow" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/>
<link rel="apple-touch-icon" href="<?php echo DOL_URL_ROOT.'/theme/phones/smartphone/theme/'.$this->theme.'/thumbs/homescreen.png'; ?>" />
<meta name="apple-touch-fullscreen" content="YES" />
<style type="text/css" media="screen">@import "<?php echo DOL_URL_ROOT.'/theme/phones/smartphone/theme/'.$this->theme.'/menu/iui.css'; ?>";</style>
<script type="application/x-javascript" src="<?php echo DOL_URL_ROOT.'/includes/iui/js/iui.js'; ?>"></script>
</head>

<body>

<!-- END HEADER SMARTPHONE TEMPLATE -->