<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004 Sebastien DiCintio   <sdicintio@ressource-toi.org>
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
 * $Source$
 *
 */

$etapes=5;
$docurl = '<a href="doc/dolibarr-install.html">documentation</a>';

function pHeader($soutitre,$next)
{

print '
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=iso8859-1">
<link rel="stylesheet" type="text/css" href="./default.css">
<title>Dolibarr Installation</title>
</head>
<body>
<div class="titre">
<span class="titre"><a class="titre" href="index.php">Dolibarr Installation</a></span>
</div>
';
 print '<form action="'.$next.'.php" method="POST">
<input type="hidden" name="action" value="set">';
print '<div class="main"><div class="soustitre">'.$soutitre.'</div>
<div class="main-inside">
';
}


function pFooter($nonext=0)
{
print '</div></div>';
if ($nonext == 0)
  {
    print '<div class="barrebottom"><input type="submit" value="Etape suivante ->"></div>';
  }
print '
</form>
</body>
</html>';
    }


function dolibarr_syslog($message)
{
  define_syslog_variables();

  openlog("dolibarr", LOG_PID | LOG_PERROR, LOG_USER);	# LOG_USER au lieu de LOG_LOCAL0 car non accepté par tous les PHP
  
  syslog(LOG_WARNING, $message);

  closelog();
}

?>
