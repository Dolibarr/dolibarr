<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Éric Seigne <erics@rycks.com>
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

require_once("includes/magpierss/rss_fetch.inc");

//rq erics:
// A changer si on a plus d'un site syndiqué ? je n'ai pas encore tout
// compris aux boxes, sorry !
for($site = 0; $site < 1; $site++) {
  $info_box_head = array();
  $info_box_head[] = array('text' => "Les 5 dernières infos du site " . @constant("EXTERNAL_RSS_TITLE_". $site));
  $info_box_contents = array();
  $rss = fetch_rss( @constant("EXTERNAL_RSS_URLRSS_" . $site) );
  for($i = 0; $i < 5 ; $i++){
    $item = $rss->items[$i];
    $href = $item['link'];
    $title = $item['title'];
    $info_box_contents["$href"]="$title";
    $info_box_contents[$i][0] = array('align' => 'left',
				      'text' => $title,
				      'url' => $href);
  } 
  new infoBox($info_box_head, $info_box_contents);
}
?>
