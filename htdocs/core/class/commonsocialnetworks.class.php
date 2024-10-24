<?php
/* Copyright (C) 2012 Regis Houssin  <regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 */

/**
 *       \file       htdocs/core/class/commonsocialnetworks.class.php
 *       \ingroup    core
 *       \brief      File of the superclass of object classes that support socialnetworks
 */


/**
 *      Superclass for social networks
 */
trait CommonSocialNetworks
{
	/**
	 * @var array<string,string>
	 */
	public $socialnetworks;


	/**
	 * Show social network part if the module is enabled with hiding functionality
	 *
	 * @param	array<string,array{active:int<0,1>,icon:string,label:string}>	$socialnetworks		Array of social networks
	 * @param	int		$colspan			Colspan
	 * @return 	void
	 */
	public function showSocialNetwork($socialnetworks, $colspan = 4)
	{
		global $object, $form, $langs;

		$nbofnetworks = count($socialnetworks);
		$nbactive = 0;
		foreach ($socialnetworks as $key => $value) {
			if (!empty($object->socialnetworks[$key])) {
				$nbactive++;
			}
		}

		if ($nbofnetworks > 1) {
			print '<tr><td colspan="'.$colspan.'"><br><a class="paddingtop paddingbottom socialnetworklnk onreposition" id="socialnetworklnk" href="#"></a>';
			//print '</td>';
			//print '<td'.($colspan ? ' colspan="'.($colspan-1).'"' : '').'>';
			//print '<br>';
			print ' <a class="paddingtop paddingbottom socialnetworklnk onreposition" href="#"><span class="badge badge-secondary socialnetworklnk">'.$nbactive.'</span></a>';
			print '</td>';
			print '</tr>';
		}
		foreach ($socialnetworks as $key => $value) {
			if ($value['active'] || $nbofnetworks == 1) {
				print '<tr class="soc_network">';
				print '<td><label for="'.$value['label'].'">'.$form->editfieldkey($value['label'], $key, '', $object, 0).'</label></td>';
				print '<td colspan="3">';
				if (!empty($value['icon'])) {
					print '<span class="fab '.$value['icon'].' pictofixedwidth"></span>';
				}
				print '<input type="text" name="'.$key.'" id="'.$key.'" class="minwidth100 maxwidth300 widthcentpercentminusx" maxlength="80" value="'.dol_escape_htmltag(GETPOSTISSET($key) ? GETPOST($key, 'alphanohtml') : (empty($object->socialnetworks[$key]) ? '' : $object->socialnetworks[$key])).'">';
				print '</td>';
				print '</tr>';
			} elseif (!empty($object->socialnetworks[$key])) {
				print '<input type="hidden" name="'.$key.'" value="'.$object->socialnetworks[$key].'">';
			}
		}

		if ($nbofnetworks > 1) {
			print '<script nonce="'.getNonce().'" type="text/javascript">
		$("document").ready(function() { toogleSocialNetwork(false); });

		jQuery(".socialnetworklnk").click(function() {
			console.log("Click on link");
			toogleSocialNetwork(true);
			return false;
		});

		function toogleSocialNetwork(chgCookieState) {
			const lnk = $("#socialnetworklnk");
			const items = $(".soc_network");
			var cookieState = document.cookie.split(";").some((item) => item.trim().startsWith("DOLUSER_SOCIALNETWORKS_SHOW=true")) == true;

			if (!chgCookieState) cookieState = !cookieState ;

			if (cookieState) {
				items.hide();
				lnk.text("'.dol_escape_js($langs->transnoentitiesnoconv("ShowSocialNetworks")).'...");
				if (chgCookieState) { document.cookie = "DOLUSER_SOCIALNETWORKS_SHOW=false; SameSite=Strict"};
			} else {
				items.show();
				lnk.text("'.dol_escape_js($langs->transnoentitiesnoconv("HideSocialNetworks")).'...");
				if (chgCookieState) { document.cookie = "DOLUSER_SOCIALNETWORKS_SHOW=true; SameSite=Strict";}
			}
		}
		</script>';
		}
	}
}
