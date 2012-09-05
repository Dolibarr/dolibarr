<?php
/* Copyright (C) 2011	Anthony Hebert		<ahebert@teclib.com>
 * Copyright (C) 2012	Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2012	Laurent Destailleur	<eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       holidayagenda.class.php
 *	\ingroup    holiday
 *	\brief      Fichier d'agenda pour le module Congés Payés
 */


/**
 *	\class 		CommonAgenda
 *	\brief 		Classe mere pour heritage des classes Agenda
 */

// FIXME this class not exist
//require_once DOL_DOCUMENT_ROOT.'/core/class/commonagenda.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';


class HolidayAgenda// extends CommonAgenda
{
    var $db;

    // Nombre de seconde à ajouter ou soustraire pour être GMT?
    var $offset 	= 3600;

    // Pays ou calendrier assujetti à l'heure d'été?
    var $summerTime = 0;




    /**
     *	Constructor
     *
     *	@param	DoliDB	$db		Database access handler
     */
    function construct($db)
    {
        $this->db = $db;
    }

    /**
     *    Fonction permettant d'altérer les paramètres javascript
     *    du calendrier
     *    @what string clé de paramètre recherché
     */
    function getParams($what = ''){

        $params =  array(  'timeslotsPerHour' => 2,
                         'businessHours'    => '{start: 0, end: 24, limitDisplay: true }');

        if(empty($what)){
            return $params;
        }elseif(array_key_exists($what,$params)){
            return $params[$what];
        }else{
            return false;
        }
    }

    /**
     *    Accesseur des évènements à mettre dans l'agenda
     *    @param	$start		Timestamp de début
     *    @param	$end		Timestamp de fin
     *    @param	$options 	Array stockage de paramètre à utiliser au besoin.
     * 	  @return   $events		Events encodés en Json
     */
    function getEvents($start,$end,$options)
    {
        global $langs,$conf,$user,$db;

        $langs->load('holiday');
        $data = array();

        if(!isset($options['user']) or empty($options['user'])):
        $users_id = $user->id;
        else:
        $users_id = $options['user'];
        endif;

        $sql  = "SELECT lcp.rowid as id,
                  UNIX_TIMESTAMP(date_debut) as start,
                  UNIX_TIMESTAMP(CONCAT(date_fin, ' 23:59:59')) as end,
                  lu.firstname as prenom,
                  lu.name as nom,
                  lcp.description as title,
                  lcp.statut as statut
                  FROM llx_holiday lcp
                  INNER JOIN llx_user lu
                  ON lu.rowid = lcp.fk_user
                  WHERE lcp.fk_user = {$users_id}
                  AND lcp.statut in (1,3)
                  AND (UNIX_TIMESTAMP(date_debut) BETWEEN LEFT('$start',10) AND LEFT('$end',10)
                  OR UNIX_TIMESTAMP(CONCAT(date_fin, ' 23:59:59')) BETWEEN LEFT('$start',10) AND LEFT('$end',10))
               ";

        $qry  = $db->query($sql);
        $nbr  = $db->num_rows($qry);

        if($nbr)
        {
            $i = 0;
            while($res = $db->fetch_object($qry))
            {
                $data[$i]['eventid']		   = $res->id;
                $data[$i]['readonly']	   = 0;
                $data[$i]['draggable']	   = 0;
                $data[$i]['resizable']	   = 0;
                $data[$i]['eventstart']		= (string)$res->start;
                $data[$i]['eventend']		= (string)$res->end;
                $data[$i]['eventmessage']  = $res->message;
                $data[$i]['extraInfo']        = $res->message;
                $data[$i]['color']			= "#7AAC22";
                $data[$i]['type']			= $langs->trans('CPTitreMenu');
                $data[$i]['icon']			= "<img src='".DOL_URL_ROOT."/holiday/img/holiday.png' height=13 width=13 />";


                $data[$i]['eventtitle'] = "<strong>{$res->prenom} {$res->nom}<br />{$res->title}</strong>";
                $data[$i]['eventtitle'] .= ($res->statut == 1) ? "<br />{$langs->trans('ToValidateCP')}" : "<br />{$langs->trans('ValidateCP')}";
                $i++;
            }
        }




        return $data;
    }


    function getFormItems()
    {
        global $conf;

        $fields = array(
							'selectedTab'		=> 'input',
							'user'		=> 'select'

        );

        return $fields;

    }


    /**
     *    Accesseur d'un formulaire à mettre en en-tête de l'agenda
     * 	  @return   $form		code html
     */
    function getForm($get,$post)
    {
        global $user,$langs,$conf,$db;

        $post = $get + $post;

        if($user->admin){
            $sql = "SELECT * from llx_user where statut = 1";
            $options = "";
            $qry = $db->query($sql);
            while($res = $db->fetch_object($qry)){
                $options .= ((isset($_POST['user']) && $res->rowid == $_POST['user']) or ($res->rowid == $user->id and !isset($_POST['user'])))
                ? "<option value={$res->rowid} selected='selected'>{$res->firstname} {$res->name}</option>"
                : "<option value={$res->rowid}>{$res->firstname} {$res->name}</option>";
            }



        }
        else{
            $options = "<option value={$user->id} selected='selected'>{$user->prenom} {$user->nom}</option>";
        }

        $HTML = <<<HTML
<form method='post' action="{$_SERVER["PHP_SELF"]}">
<table class='border' width='100%'>
   <tr>
      <td>
        {$langs->trans('Collab')}
      </td>
      <td>
         <select name='user' id='user'>
      {$options}
         </select>
         <input type='hidden' name='selectedTab' value='holidayagenda' />
         <input type='submit' value='Envoyer' class='button' />
      </td>
   </tr>
</table>
</form>
HTML;
      $form = $HTML;
      return $form;
    }

    /**
     *    Exporteur au format iCal
     *    @see		http://tools.ietf.org/html/rfc5545
     *	  @todo		Export en iCal
     * 	  @return   $iCal		calendrier au format iCal
     */
    static function exportIniCal()
    {
    }

    /**
     *    Générateur du lien public d'accès au calendrier iCal
     *    @see		http://tools.ietf.org/html/rfc5545
     *	  @todo		Générer le lien
     * 	  @return   $iCal		calendrier au format iCal
     */
    function getiCalLink()
    {
        global $conf,$user,$langs;
    }



    /**
     *    Accesseur du label à afficher par print_fiche_titre()
     * 	  @return   $label		Label de l'agenda
     */
    function getLabel()
    {
        global $user,$langs,$conf;
        $langs->load('holiday');
        ob_start();
        print_fiche_titre($langs->trans('ListeCP'));
        $label = ob_get_contents();
        ob_end_clean();

        return $label;
    }




    function getTitle()
    {
        global $langs;
        $langs->load('holiday');
        return $langs->trans('CPTitreMenu');

    }


    function getLink($action = "")
    {
        global $conf,$user;

        $action = ($action == 'create') ? 'request' : '';
        $url = DOL_URL_ROOT."/holiday/fiche.php";

        return (empty($action)) ? $url : $url."?action={$action}";
    }



}

?>
