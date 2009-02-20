<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class CommandeMethode
{

  function LogSql()
  {
    $error = 0;

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_commande";
    $sql .= " (datec, fk_user_creat, fk_fournisseur, filename)";
    $sql .= " VALUES (now(),".$this->user->id.",".$this->fourn->id.",'".$this->filename."')";

    $resql = $this->db->query($sql);

    if ($resql)
      {
	$this->commande_id = $this->db->last_insert_id(MAIN_DB_PREFIX."telephonie_commande");

	/*
	 * Modifie le statut des lignes commandées
	 *
	 */
	foreach ($this->ligneids as $lid)
	  {
	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_commande_ligne";
	    $sql .= " (fk_commande, fk_ligne)";
	    $sql .= " VALUES (".$this->commande_id.",".$lid.")";

	    if (! $this->db->query($sql) )
	      {
		$error++;
		dol_syslog($sql);
	      }
	  }
      }
    else
      {
	$error++;
	dol_syslog($sql);
      }

    return $error;

  }
}
