<?php
/* Copyright (C) 2009-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/core/lib/memory.lib.php
 *  \brief		Set of function for memory/cache management
 */


/**
 * 	Save data into a memory area shared by all users, all sessions on server
 *
 *  @param	string      $destdir		Directory
 * 	@param	string		$module			Module name
 *  @param	string      $objectname		Name of object
 * 	@param	string		$newmask		New mask
 * 	@return	int							<0 if KO, >0 if OK
 */
function rebuildobjectsql($destdir, $module, $objectname, $newmask)
{
    global $db;

    if (empty($objectname)) return -1;

    dol_include_once(strtolower($module).'/class/'.strtolower($objectname).'.class.php');
    $object=new $objectname($db);

    // Edit sql files
    $pathoffiletoedit=dol_osencode($destdir.'/sql/llx_'.strtolower($objectname).'.sql');

    $contentsql = file_get_contents($pathoffiletoedit, 'r');

    $i=0;
    $texttoinsert = '-- BEGIN MODULEBUILDER FIELDS'."\n";
    foreach($object->fields as $key => $val)
    {
        $i++;
        $texttoinsert.= "\t".$key." ".$val['type'];
        if ($key == 'rowid')  $texttoinsert.= ' AUTO_INCREMENT PRIMARY KEY';
        if ($key == 'entity') $texttoinsert.= ' DEFAULT 1';
        $texttoinsert.= ($val['notnull']?' NOT NULL':'');
        if ($i < count($object->fields)) $texttoinsert.=", ";
        $texttoinsert.= "\n";
    }
    $texttoinsert.= "\t".'-- END MODULEBUILDER FIELDS';

    $contentsql = preg_replace('/-- BEGIN MODULEBUILDER FIELDS.*END MODULEBUILDER FIELDS/ims', $texttoinsert, $contentsql);

    file_put_contents($pathoffiletoedit, $contentsql);
    @chmod($pathoffiletoedit, octdec($newmask));



    // Edit sql files
    $pathoffiletoedit=dol_osencode($destdir.'/sql/llx_'.strtolower($objectname).'.key.sql');

    $contentsql = file_get_contents($pathoffiletoedit, 'r');

    $i=0;
    $texttoinsert = '-- BEGIN MODULEBUILDER INDEXES'."\n";
    foreach($object->fields as $key => $val)
    {
        $i++;
        if ($val['index'])
        {
            $texttoinsert.= "ALTER TABLE llx_".strtolower($objectname)." ADD INDEX idx_".strtolower($objectname)."_".$key." (".$key.");";
            $texttoinsert.= "\n";
        }
    }
    $texttoinsert.= '-- END MODULEBUILDER INDEXES';

    $contentsql = preg_replace('/-- BEGIN MODULEBUILDER INDEXES.*END MODULEBUILDER INDEXES/ims', $texttoinsert, $contentsql);

    file_put_contents($pathoffiletoedit, $contentsql);
    @chmod($pathoffiletoedit, octdec($newmask));

    return 1;
}
