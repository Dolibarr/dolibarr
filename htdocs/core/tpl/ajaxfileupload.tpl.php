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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * $Id: ajaxfileupload.tpl.php,v 1.11 2011/07/31 23:45:11 eldy Exp $
 */
?>

<!-- START TEMPLATE FILE UPLOAD -->
<!-- Warning id on script is not W3C compliant and is reported as error by phpcs but it is required by jfilepload plugin -->
<script id="template-upload" type="text/x-jquery-tmpl">
	<tr class="template-upload{{if error}} ui-state-error{{/if}}">
		<td class="name">${name}</td>
		<td class="preview"></td>
		<td class="size">${sizef}</td>
		{{if error}}
			<td class="error" colspan="2"><?php echo $langs->trans('Error'); ?>:
				{{if error === 'maxFileSize'}}<?php echo $langs->trans('FileIsTooBig'); ?>
				{{else error === 'minFileSize'}}File is too small
				{{else error === 'acceptFileTypes'}}Filetype not allowed
				{{else error === 'maxNumberOfFiles'}}Max number of files exceeded
				{{else}}${error}
				{{/if}}
			</td>
		{{else}}
			<td class="progress"><div></div></td>
			<td align="right" class="start"><button><?php echo $langs->trans('Start'); ?></button></td>
		{{/if}}
		<td align="right" class="cancel"><button><?php echo $langs->trans('Cancel'); ?></button></td>
	</tr>
</script>

<script id="template-download" type="text/x-jquery-tmpl">
    <tr class="template-download{{if error}} ui-state-error{{/if}}">
        {{if error}}
            <td></td>
            <td class="name">${name}</td>
            <td class="size">${sizef}</td>
            <td class="error" colspan="2"><?php echo $langs->trans('Error'); ?>:
                {{if error === 1}}File exceeds upload_max_filesize (php.ini directive)
                {{else error === 2}}File exceeds MAX_FILE_SIZE (HTML form directive)
                {{else error === 3}}File was only partially uploaded
                {{else error === 4}}No File was uploaded
                {{else error === 5}}Missing a temporary folder
                {{else error === 6}}Failed to write file to disk
                {{else error === 7}}File upload stopped by extension
                {{else error === 'maxFileSize'}}<?php echo $langs->trans('FileIsTooBig'); ?>
                {{else error === 'minFileSize'}}File is too small
                {{else error === 'acceptFileTypes'}}Filetype not allowed
                {{else error === 'maxNumberOfFiles'}}Max number of files exceeded
                {{else error === 'uploadedBytes'}}Uploaded bytes exceed file size
                {{else error === 'emptyResult'}}Empty file upload result
                {{else}}${error}
                {{/if}}
            </td>
            <td align="right" class="delete">
                <button data-type="${delete_type}" data-url="${delete_url}"><?php echo $langs->trans('Delete'); ?></button>
            </td>
        {{/if}}
    </tr>
</script>

<br>
<!-- END PHP TEMPLATE -->