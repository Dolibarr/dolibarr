<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Webcal {

  Function Webcal() {

  }


  Function add($user, $texte, $desc) {

    $db = new Db();

    $id = get_next_id($db);

    $cal_id = $id;
    $cal_create_by = $user;
    $cal_date = strftime('%Y%m%d');
    $cal_time  = -1;
    $cal_mod_date = strftime('%Y%m%d', time());
    $cal_mod_time = strftime('%H%M', time());
    $cal_duration = 0;
    $cal_priority = 2;
    $cal_type = "E";
    $cal_access = "P";
    $cal_name = $texte;
    $cal_description = $desc;


    $sql = "INSERT INTO webcal_entry (cal_id, cal_create_by,cal_date,cal_time,cal_mod_date, cal_mod_time,cal_duration,cal_priority,cal_type, cal_access, cal_name,cal_description)";

    $sql .= " VALUES ($cal_id, '$cal_create_by',$cal_date,$cal_time,$cal_mod_date, $cal_mod_time,cal_duration,$cal_priority,'$cal_type', '$cal_access', '$cal_name','$cal_description');";

    $db->query($sql);

    $db->close();
 
  }


  Function get_next_id($db) {

    $sql = "SELECT max(cal_id) FROM webcal_entry";

    if ($db->query($sql)) {
      $id = $db->result(0, 0) + 1;
      return $id;
    }

  }
?>
