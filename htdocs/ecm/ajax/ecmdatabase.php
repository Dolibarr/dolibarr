<?php
/* Copyright (C) 2012	Regis Houssin	<regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/ecm/ajax/ecmdatabase.php
 *       \brief      File to build ecm database
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

$action	= GETPOST('action', 'alpha');
$element = GETPOST('element', 'alpha');

/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Load original field value
if (isset($action) && ! empty($action))
{
	$error=0;

	if ($action == 'build' && ! empty($element))
	{
		require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmdirectory.class.php';

		$ecmdirstatic = new EcmDirectory($db);
		$ecmdirtmp = new EcmDirectory($db);

		// This part of code is same than into file index.php for action refreshmanual TODO Remove duplicate
		clearstatcache();

		$diroutputslash=str_replace('\\', '/', $conf->$element->dir_output);
		$diroutputslash.='/';

		// Scan directory tree on disk
		$disktree=dol_dir_list($conf->$element->dir_output, 'directories', 1, '', array('^temp$'), '', '', 0);

		// Scan directory tree in database
		$sqltree=$ecmdirstatic->get_full_arbo(0);

		$adirwascreated=0;

		// Now we compare both trees to complete missing trees into database
		//var_dump($disktree);
		//var_dump($sqltree);
		foreach($disktree as $dirdesc)    // Loop on tree onto disk
		{
			set_time_limit(0); // To force restarts the timeout counter from zero

			$dirisindatabase=0;
			foreach($sqltree as $dirsqldesc)
			{
				if ($conf->$element->dir_output.'/'.$dirsqldesc['fullrelativename'] == $dirdesc['fullname'])
				{
					$dirisindatabase=1;
					break;
				}
			}

			if (! $dirisindatabase)
			{
				$txt="Directory found on disk ".$dirdesc['fullname'].", not found into database so we add it";
				dol_syslog($txt);

				// We must first find the fk_parent of directory to create $dirdesc['fullname']
				$fk_parent=-1;
				$relativepathmissing=str_replace($diroutputslash, '', $dirdesc['fullname']);
				$relativepathtosearchparent=$relativepathmissing;
				//dol_syslog("Try to find parent id for directory ".$relativepathtosearchparent);
				if (preg_match('/\//', $relativepathtosearchparent))
					//while (preg_match('/\//',$relativepathtosearchparent))
				{
					$relativepathtosearchparent=preg_replace('/\/[^\/]*$/', '', $relativepathtosearchparent);
					$txt="Is relative parent path ".$relativepathtosearchparent." for ".$relativepathmissing." found in sql tree ?";
					dol_syslog($txt);
					//print $txt." -> ";
					$parentdirisindatabase=0;
					foreach($sqltree as $dirsqldesc)
					{
						if ($dirsqldesc['fullrelativename'] == $relativepathtosearchparent)
						{
							$parentdirisindatabase=$dirsqldesc['id'];
							break;
						}
					}
					if ($parentdirisindatabase > 0)
					{
						dol_syslog("Yes with id ".$parentdirisindatabase);
						//print "Yes with id ".$parentdirisindatabase."<br>\n";
						$fk_parent=$parentdirisindatabase;
						//break;  // We found parent, we can stop the while loop
					}
					else
					{
						dol_syslog("No");
						//print "No<br>\n";
					}
				}
				else
				{
					dol_syslog("Parent is root");
					$fk_parent=0;   // Parent is root
				}

				if ($fk_parent >= 0)
				{
					$ecmdirtmp->ref                = 'NOTUSEDYET';
					$ecmdirtmp->label              = dol_basename($dirdesc['fullname']);
					$ecmdirtmp->description        = '';
					$ecmdirtmp->fk_parent          = $fk_parent;

					$txt="We create directory ".$ecmdirtmp->label." with parent ".$fk_parent;
					dol_syslog($txt);
					//print $txt."<br>\n";
					$id = $ecmdirtmp->create($user);
					if ($id > 0)
					{
						$newdirsql=array('id'=>$id,
								'id_mere'=>$ecmdirtmp->fk_parent,
								'label'=>$ecmdirtmp->label,
								'description'=>$ecmdirtmp->description,
								'fullrelativename'=>$relativepathmissing);
						$sqltree[]=$newdirsql; // We complete fulltree for following loops
						//var_dump($sqltree);
						$adirwascreated=1;
					}
					else
					{
						dol_syslog("Failed to create directory ".$ecmdirtmp->label, LOG_ERR);
					}
				}
				else {
					$txt="Parent of ".$dirdesc['fullname']." not found";
					dol_syslog($txt);
					//print $txt."<br>\n";
				}
			}
		}

	    // Loop now on each sql tree to check if dir exists
	    foreach($sqltree as $dirdesc)    // Loop on each sqltree to check dir is on disk
	    {
	    	$dirtotest=$conf->$element->dir_output.'/'.$dirdesc['fullrelativename'];
			if (! dol_is_dir($dirtotest))
			{
				$mesg.=$dirtotest." not found onto disk. We delete from database dir with id=".$dirdesc['id']."<br>\n";
				$ecmdirtmp->id=$dirdesc['id'];
				$ecmdirtmp->delete($user, 'databaseonly');
				//exit;
			}
	    }

		$sql="UPDATE ".MAIN_DB_PREFIX."ecm_directories set cachenbofdoc = -1 WHERE cachenbofdoc < 0"; // If pb into cahce counting, we set to value -1 = "unknown"
		dol_syslog("sql = ".$sql);
		$db->query($sql);
	}
}
