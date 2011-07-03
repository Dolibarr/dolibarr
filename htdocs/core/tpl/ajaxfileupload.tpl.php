<?php
/* Copyright (C) 2011 Regis Houssin <regis@dolibarr.fr>
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
 * $Id: ajaxfileupload.tpl.php,v 1.3 2011/07/03 13:45:32 hregis Exp $
 */
?>

<!-- START TEMPLATE FILE UPLOAD -->
<script id="template-upload" type="text/x-jquery-tmpl">
	<tr class="template-upload{{if error}} ui-state-error{{/if}}">
		<td class="name">${name}</td>
		<td class="preview"></td>
		<td class="size">${sizef}</td>
		{{if error}}
			<td class="error" colspan="2">Error:
				{{if error === 'maxFileSize'}}File is too big
				{{else error === 'minFileSize'}}File is too small
				{{else error === 'acceptFileTypes'}}Filetype not allowed
				{{else error === 'maxNumberOfFiles'}}Max number of files exceeded
				{{else}}${error}
				{{/if}}
			</td>
		{{else}}
			<td class="progress"><div></div></td>
			<td class="start"><button><?php echo $langs->trans('Start'); ?></button></td>
		{{/if}}
		<td class="cancel"><button><?php echo $langs->trans('Cancel'); ?></button></td>
	</tr>
</script>

<script id="template-download" type="text/x-jquery-tmpl">
    <tr class="template-download{{if error}} ui-state-error{{/if}}">
        {{if error}}
            <td></td>
            <td class="name">${name}</td>
            <td class="size">${sizef}</td>
            <td class="error" colspan="2">Error:
                {{if error === 1}}File exceeds upload_max_filesize (php.ini directive)
                {{else error === 2}}File exceeds MAX_FILE_SIZE (HTML form directive)
                {{else error === 3}}File was only partially uploaded
                {{else error === 4}}No File was uploaded
                {{else error === 5}}Missing a temporary folder
                {{else error === 6}}Failed to write file to disk
                {{else error === 7}}File upload stopped by extension
                {{else error === 'maxFileSize'}}File is too big
                {{else error === 'minFileSize'}}File is too small
                {{else error === 'acceptFileTypes'}}Filetype not allowed
                {{else error === 'maxNumberOfFiles'}}Max number of files exceeded
                {{else error === 'uploadedBytes'}}Uploaded bytes exceed file size
                {{else error === 'emptyResult'}}Empty file upload result
                {{else}}${error}
                {{/if}}
            </td>
        {{else}}
            <td class="name">
				<img src="<?php echo DOL_URL_ROOT; ?>/theme/common/mime/${mime}" border="0">
                <a href="${url}"{{if thumbnail_url}} target="_blank"{{/if}}>${name}</a>
            </td>
			<td class="preview">
                {{if thumbnail_url}}
                    <a href="${url}" target="_blank"><img src="${thumbnail_url}"></a>
                {{/if}}
            </td>
            <td class="size">${sizef}</td>
            <td colspan="2"></td>
        {{/if}}
        <td align="right" class="delete">
            <button data-type="${delete_type}" data-url="${delete_url}"><?php echo $langs->trans('Delete'); ?></button>
        </td>
    </tr>
</script>
<!-- END PHP TEMPLATE -->