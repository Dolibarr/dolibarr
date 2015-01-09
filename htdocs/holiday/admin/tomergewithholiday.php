<?php
/* Copyright (C) 2012-2103 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011	   Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2012	   Regis Houssin		<regis.houssin@capnetworks.com>
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
 * 	Page module configuration paid holiday.
 *
 *  \file       holiday.php
 *	\ingroup    holiday
 *	\brief      Page module configuration paid holiday.
 */

require '../../main.inc.php';
require DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT. '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT. '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT. '/user/class/usergroup.class.php';

// Si pas administrateur
if (! $user->admin) accessforbidden();


/*
 * View
 */

// Vérification si module activé
if (empty($conf->holiday->enabled)) print $langs->trans('NotActiveModCP');

llxheader('',$langs->trans('TitleAdminCP'));

print_fiche_titre($langs->trans('MenuCPTypes'));

echo '<div id="types">';

echo '<table class="noborder" style="width: 100%;">';
echo '<tr class="liste_titre">';
echo '<td style="width: 150px">' . $langs->trans('TypeLabel') . '</td>';
echo '<td>' . $langs->trans('TypeDescription') . '</td>';
echo '<td style="text-align: center; width: 100px">' . $langs->trans('TypeAffect') . '</td>';
echo '<td style="text-align: center; width: 50px">' . $langs->trans('TypeDelay') . '</td>';
echo '<td style="text-align: center;">' . $langs->trans('nbCongesDeductedCPMini') . '</td>';
echo '<td style="text-align: center;">' . $langs->trans('nbCongesEveryMonthCPMini') . '</td>';
echo '<td style="width: 40px"></td>';
echo '</tr>';

$types = $db->query(
	"SELECT *
   FROM llx_congespayes_types
   WHERE deleteAt IS NULL
   ORDER BY label"
);

while($type = $db->fetch_array($types))
{
   echo'
      <tr id="row_'.$type['rowid'].'" '.$bc[$var=!$var].'>
         <td>'.$type['label'].'</td>
         <td>'.($type['description']?$type['description']:'N/A').'</td>
         <td style="font-weight: bold; text-align: center">
         '.($type['affect']?$langs->trans('TypeAffectYes'):$langs->trans('TypeAffectNo')).'
         </td>
         <td style="text-align: center">'.$type['delay'].'</td>
         <td style="text-align: center">'.$type['nbCongesDeducted'].'</td>
         <td style="text-align: center">'.$type['nbCongesEveryMonth'].'</td>
         <td>
            <img class="btn edit" data-rowid="'.$type['rowid'].'" src="../img/edit.png" title="'.$langs->trans('TypeBtnEdit').'">
            <img class="btn delete" data-rowid="'.$type['rowid'].'" src="../img/delete.png" title="'.$langs->trans('TypeBtnDelete').'">
         </td>
      </tr>
      <tr id="form_'.$type['rowid'].'" '.$bc[$var].' style="display: none">
         <td colspan="7">
            <form>
               <input type="hidden" name="rowid" value="'.$type['rowid'].'">
               <div>
                  <label>' . $langs->trans('TypeLabel') . '</label>
                  <input class="text" maxlength="45" name="label" type="text" value="'.$type['label'].'">
               </div>
               <div>
                  <label>' . $langs->trans('TypeDescription') . '</label>
                  <textarea name="description">'.$type['description'].'</textarea>
               </div>
               <div>
                  <label>' . $langs->trans('TypeDelay') . '</label>
                  <input class="text" maxlength="2" name="delay" type="text" value="'.$type['delay'].'" style="width: 50px">
               </div>
               <div>
                  <label>' . $langs->trans('TypeAffect') . '</label>
                  <select name="affect">
                     <option value="1"'.($type['affect']?' selected="selected"':'').'>
                        '.$langs->trans('TypeAffectYes').'
                     </option>
                     <option value="0"'.($type['affect']?'':' selected="selected"').'>
                        '.$langs->trans('TypeAffectNo').'
                     </option>
                  </select>
               </div>
               <div>
                  <label>' . $langs->trans('nbCongesDeductedCPMini') . '</label>
                  <input class="text" maxlength="5" name="nbCongesDeducted" type="text"
                        value="'.$type['nbCongesDeducted'].'" style="width: 50px">
               </div>
               <div>
                  <label>' . $langs->trans('nbCongesEveryMonthCPMini') . '</label>
                  <input class="text" maxlength="5" name="nbCongesEveryMonth" type="text"
                        value="'.$type['nbCongesEveryMonth'].'" style="width: 50px">
               </div>
               <div style="text-align: right">
                  <input class="btn btn-primary button " type="submit" value="'.$langs->trans('TypeBtnApply').'">
                  <input data-rowid="'.$type['rowid'].'" class="btn cancel button" type="button" value="'.$langs->trans('TypeBtnCancel').'">
               </div>
            </form>
         </td>
      </tr>
   ';
}

echo '
   <tr id="row_new" '.$bc[!$var].'>
      <td colspan="7" style="text-align: right;">
         <img class="btn" id="new" src="../img/add.png" title="'.$langs->trans('TypeBtnAdd').'">
      </td>
   </tr>
   <tr id="form_new" '.$bc[!$var].' style="display:none">
      <td colspan="7">
         <form>
            <div>
               <label>' . $langs->trans('TypeLabel') . '</label>
               <input class="text" maxlength="45" name="label" type="text">
            </div>
            <div>
               <label>' . $langs->trans('TypeDescription') . '</label>
               <textarea name="description"></textarea>
            </div>
            <div>
               <label>' . $langs->trans('TypeDelay') . '</label>
               <input class="text" maxlength="2" name="delay" type="text" value="0" style="width: 50px">
            </div>
            <div>
               <label>' . $langs->trans('TypeAffect') . '</label>
               <select name="affect">
                  <option value="1">'.$langs->trans('TypeAffectYes').'</option>
                  <option value="0">'.$langs->trans('TypeAffectNo').'</option>
               </select>
            </div>
            <div>
               <label>' . $langs->trans('nbCongesDeductedCPMini') . '</label>
               <input class="text" maxlength="5" name="nbCongesDeducted" type="text"
                     value="0" style="width: 50px">
            </div>
            <div>
               <label>' . $langs->trans('nbCongesEveryMonthCPMini') . '</label>
               <input class="text" maxlength="5" name="nbCongesEveryMonth" type="text"
                     value="0" style="width: 50px">
            </div>
            <div style="text-align: right">
               <input class="btn btn-primary" type="submit" value="'.$langs->trans('TypeBtnAdd').'">
               <input class="btn cancel" type="button" value="'.$langs->trans('TypeBtnCancel').'">
            </div>
         </form>
      </td>
   </tr>
</table>
<script>
   $(function(){
      $(".cancel", "#types").click(function(){
         var rowid = false;
         if(rowid = $(this).data("rowid")) {
             $("#row_"+rowid, "#types").show();
             $("#form_"+rowid, "#types").hide();
         } else {
             $("#row_new", "#types").show();
             $("#form_new", "#types").hide();
         }
      });
      $(".edit", "#types").click(function(){
         var rowid = $(this).data("rowid");
         $("#row_"+rowid, "#types").hide();
         $("#form_"+rowid, "#types").show();
      });
      $("#new", "#types").click(function(){
         $("#row_new", "#types").hide();
         $("#form_new", "#types").show();
      });
      $("form", "#types").submit(function(){
         $.post("ajax/types_editor.php", $(this).serialize(), function(response) {
            if(response.status) window.location.reload();
            else alert(response.content);
         }, "json");
         return false;
      });
      $(".delete", "#types").click(function(){
         if(confirm("'.$langs->trans('TypeBtnDeleteConfirmation').'")) {
            $.post("ajax/types_delete.php", {
               rowid: $(this).data("rowid")
            }, function(response) {
               if(response.status) window.location.reload();
               else alert(response.content);
            }, "json");
         }
      });
   });
</script>
';

echo '</div>';

llxFooter();

$db->close();
