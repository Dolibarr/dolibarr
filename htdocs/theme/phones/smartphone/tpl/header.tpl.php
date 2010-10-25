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

<!DOCTYPE html>

<!-- BEGIN HEADER SMARTPHONE TEMPLATE -->

<html>

<head>
<title><?php echo $this->title; ?></title>
<meta name="robots" content="noindex,nofollow" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<link rel="apple-touch-icon" href="<?php echo DOL_URL_ROOT.'/theme/phones/smartphone/theme/'.$this->theme.'/thumbs/homescreen.png'; ?>" />
<link rel="stylesheet" href="<?php echo DOL_URL_ROOT.'/includes/jquery/mobile/jquery.mobile-1.0a1.min.css'; ?>" />
<link rel="stylesheet" href="<?php echo DOL_URL_ROOT.'/theme/phones/smartphone/theme/default/default.css.php'; ?>" />
<script src="<?php echo DOL_URL_ROOT.'/includes/jquery/js/jquery-1.4.3.min.js'; ?>"></script>
<script src="<?php echo DOL_URL_ROOT.'/includes/jquery/mobile/jquery.mobile-1.0a1.js'; ?>"></script>
<!--
<script src="<?php echo DOL_URL_ROOT.'/includes/jquery/mobile/jquery.mobile-1.0a1.min.js'; ?>"></script>
-->
</head>

<body>

<!-- END HEADER SMARTPHONE TEMPLATE -->