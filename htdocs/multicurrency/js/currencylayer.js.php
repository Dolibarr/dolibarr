<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2016 Pierre-Henry Favre <phf@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

include '../../main.inc.php';
$langs->load('errors');

?>

function getXMLHttpRequest() 
{
    var xhr = null;
    if (window.XMLHttpRequest || window.ActiveXObject) 
    {
        if (window.ActiveXObject) 
        {
            try 
            {
                xhr = new ActiveXObject("Msxml2.XMLHTTP");
            } 
            catch(e) 
            {
                xhr = new ActiveXObject("Microsoft.XMLHTTP");
            }
        } 
        else 
        {
            xhr = new XMLHttpRequest(); 
        }
    } 
    else 
    {
    	if (typeof $ !== "undefined") $.jnotify("<?php echo $langs->transnoentitiesnoconv('multicurrency_error_browser_incompatible'); ?>", "error");
		else alert("<?php echo $langs->transnoentitiesnoconv('multicurrency_error_browser_incompatible'); ?>");
       
        return null;
    }
    
    return xhr;
}

function request(url, callback) 
{
    var xhr = getXMLHttpRequest();
    xhr.onreadystatechange = function() 
    {
        if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0)) 
        {
            callback(xhr.responseText);
        }

    };

    xhr.open("GET", url, true);
    xhr.send(null);
}

function syncronize_rates()
{
	document.getElementById("bt_sync").disabled = true;
	var url_sync = "http://apilayer.net/api/live?access_key=<?php echo $conf->global->MULTICURRENCY_APP_ID; ?>&format=1<?php if (!empty($conf->global->MULTICURRENCY_APP_SOURCE)) echo '&source='.$conf->global->MULTICURRENCY_APP_SOURCE; ?>";
	request(url_sync, update_rates);
}

function update_rates(responseText)
{
	var response = JSON.parse(responseText);
	if (response.success)
	{
		var url = "<?php echo DOL_URL_ROOT; ?>/multicurrency/ajax/updaterates.php?sync_response="+JSON.stringify(response);
		request(url, reloadpage);
	}
	else
	{
		if (typeof $ !== "undefined") $.jnotify("<?php echo $langs->transnoentitiesnoconv('multicurrency_syncronize_error'); ?>: "+response.error.info, "error");
		else alert("<?php echo $langs->transnoentitiesnoconv('multicurrency_syncronize_error'); ?>: "+response.error.info);
	}
}

function reloadpage(responseText)
{
	document.getElementById("bt_sync").disabled = false;
	window.location.href = window.location.pathname;
}
