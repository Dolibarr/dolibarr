<?php
/* Copyright (C) 2011-2012 Regis Houssin <regis.houssin@inodbox.com>
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

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

?>

<!-- START TEMPLATE FILE UPLOAD -->

<!-- The file upload form used as target for the file upload widget -->
<form id="fileupload" action="<?php echo DOL_URL_ROOT; ?>/core/ajax/fileupload.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="fk_element" value="<?php echo $object->id; ?>">
<input type="hidden" name="element" value="<?php echo $object->element; ?>">
<input type="hidden" name="redirect" value="<?php echo $_SERVER['PHP_SELF'].'?id='.$object->id; ?>">
<!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
<div class="row fileupload-buttonbar">
	<div class="span7">
		<!-- The fileinput-button span is used to style the file input field as button -->
		<span class="btn btn-success fileinput-button">
			<i class="icon-plus icon-white"></i>
			<span><?php echo $langs->trans('AddFiles'); ?></span>
			<input type="file" name="files[]" multiple>
		</span>
		<button type="submit" class="btn btn-primary start">
			<i class="icon-upload icon-white"></i>
			<span><?php echo $langs->trans('StartUpload'); ?></span>
		</button>
		<button type="reset" class="btn btn-warning cancel">
			<i class="icon-ban-circle icon-white"></i>
			<span><?php echo $langs->trans('CancelUpload'); ?></span>
		</button>
		<!--
		<button type="button" class="btn btn-danger delete">
			<i class="icon-trash icon-white"></i>
			<span><?php echo $langs->trans('Delete'); ?></span>
		</button>
		<input type="checkbox" class="toggle">
		-->
	</div>
	<!-- The global progress information -->
	<div class="span5 fileupload-progress fade">
		<!-- The global progress bar -->
		<!--
		<div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
			<div class="bar" style="width:0%;"></div>
		</div>
		-->
		<!-- The extended global progress information -->
		<div class="progress-extended">&nbsp;</div>
	</div>
</div>
<!-- The loading indicator is shown during file processing -->
<div class="fileupload-loading"></div>
<br>
<!-- The table listing the files available for upload/download -->
<table role="presentation" class="table table-striped"><tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody></table>
</form>

<!-- The template to display files available for upload -->
<!-- Warning id on script is not W3C compliant and is reported as error by phpcs but it is required by fileupload plugin -->
<script id="template-upload" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-upload fade">
        <td class="preview"><span class="fade"></span></td>
        <td class="name"><span>{%=file.name%}</span></td>
        <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
        {% if (file.error) { %}
            <td class="error" colspan="2"><span class="label label-important">{%=locale.fileupload.error%}</span> {%=locale.fileupload.errors[file.error] || file.error%}</td>
        {% } else if (o.files.valid && !i) { %}
            <td>
                <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="bar" style="width:0%;"></div></div>
            </td>
            <td class="start">{% if (!o.options.autoUpload) { %}
                <button class="btn btn-primary">
                    <i class="icon-upload icon-white"></i>
                    <span>{%=locale.fileupload.start%}</span>
                </button>
            {% } %}</td>
        {% } else { %}
            <td colspan="2"></td>
        {% } %}
        <td class="cancel">{% if (!i) { %}
            <button class="btn btn-warning">
                <i class="icon-ban-circle icon-white"></i>
                <span>{%=locale.fileupload.cancel%}</span>
            </button>
        {% } %}</td>
    </tr>
{% } %}
</script>
<!-- The template to display files available for download -->
<!-- Warning id on script is not W3C compliant and is reported as error by phpcs but it is required by jfilepload plugin -->
<script id="template-download" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-download fade">
        {% if (file.error) { %}
            <td></td>
            <td class="name"><span>{%=file.name%}</span></td>
            <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
            <td class="error" colspan="2"><span class="label label-important">{%=locale.fileupload.error%}</span> {%=locale.fileupload.errors[file.error] || file.error%}</td>
        {% } else { %}
            <td class="preview">{% if (file.thumbnail_url) { %}
                <a href="{%=file.url%}" title="{%=file.name%}" rel="gallery" download="{%=file.name%}"><img src="{%=file.thumbnail_url%}"></a>
            {% } %}</td>
            <td class="name">
                <a href="{%=file.url%}" title="{%=file.name%}" rel="{%=file.thumbnail_url&&'gallery'%}" download="{%=file.name%}">{%=file.name%}</a>
            </td>
            <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
            <td colspan="2"></td>
        {% } %}
        <td class="delete">
            <button class="btn btn-danger" data-type="{%=file.delete_type%}" data-url="{%=file.delete_url%}">
                <i class="icon-trash icon-white"></i>
                <span>{%=locale.fileupload.destroy%}</span>
            </button>
            <input type="checkbox" name="delete" value="1">
        </td>
    </tr>
{% } %}
</script>
<br>
<!-- END PHP TEMPLATE -->
