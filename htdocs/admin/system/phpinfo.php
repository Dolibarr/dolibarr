<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * $Id$
 * $Source$
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
 */
require("./pre.inc.php");

if (!$user->admin)
  accessforbidden();


llxHeader();

ob_start(); 

if ($what == 'conf')
{
  phpinfo(INFO_CONFIGURATION);
}
elseif ($what == 'env')
{
  phpinfo(INFO_ENVIRONMENT);
}
elseif ($what == 'modules')
{
  phpinfo(INFO_MODULES);
}
else
{
  phpinfo();
}

$chaine = ob_get_contents(); 
ob_end_clean(); 

# Nettoie la sortie php pour inclusion dans une page deja existante
$chaine = eregi_replace('.*<style','<style',$chaine);
$chaine = eregi_replace('<title>.*<body>','',$chaine);
$chaine = eregi_replace('<title>.*<body>','',$chaine);
$chaine = eregi_replace('a:link.*underline','',$chaine);
$chaine = eregi_replace('table.*important; }','',$chaine);
$chaine = eregi_replace('<hr />','',$chaine);
$chaine = eregi_replace('</body></html>','',$chaine);
$chaine = eregi_replace('body, td, th, h1, h2 {font-family: sans-serif;}','',$chaine);

print "$chaine\n";	// Ne pas centrer la réponse php car certains tableau du bas très large rendent ceux du haut complètement à droite
print "<br>\n";

llxfooter();
?>
