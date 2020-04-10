<?php
/* Copyright (C) 2008-2013	Laurent Destailleur			<eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file			htdocs/core/lib/parsemd.lib.php
 *	\brief			This file contains functions dedicated to MD parsind.
 */

/**
 * Function to parse MD content into HTML
 *
 * @param	string	  $content			    MD content
 * @param   string    $parser               'parsedown' or 'nl2br'
 * @param   string    $replaceimagepath     Replace path to image with another path. Exemple: ('doc/'=>'xxx/aaa/')
 * @return	string                          Parsed content
 */
function dolMd2Html($content, $parser = 'parsedown', $replaceimagepath = null)
{
    if (is_array($replaceimagepath))
    {
        foreach ($replaceimagepath as $key => $val)
        {
            $keytoreplace = ']('.$key;
            $valafter = ']('.$val;
            $content = preg_replace('/'.preg_quote($keytoreplace, '/').'/m', $valafter, $content);
        }
    }
    if ($parser == 'parsedown')
    {
        include_once DOL_DOCUMENT_ROOT.'/includes/parsedown/Parsedown.php';
        $Parsedown = new Parsedown();
        $content = $Parsedown->text($content);
    }
    else
    {
        $content = nl2br($content);
    }

    return $content;
}


/**
 * Function to parse MD content into ASCIIDOC
 *
 * @param	string	  $content			    MD content
 * @param   string    $parser               'dolibarr'
 * @param   string    $replaceimagepath     Replace path to image with another path. Exemple: ('doc/'=>'xxx/aaa/')
 * @return	string                          Parsed content
 */
function dolMd2Asciidoc($content, $parser = 'dolibarr', $replaceimagepath = null)
{
    if (is_array($replaceimagepath))
    {
        foreach ($replaceimagepath as $key => $val)
        {
            $keytoreplace = ']('.$key;
            $valafter = ']('.$val;
            $content = preg_replace('/'.preg_quote($keytoreplace, '/').'/m', $valafter, $content);
        }
    }
    //if ($parser == 'dolibarr')
    //{
        $content = preg_replace('/<!--.*-->/msU', '', $content);
    //}
    //else
    //{
    //    $content = $content;
    //}

    return $content;
}
