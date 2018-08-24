// Copyright (C) 2009-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// or see http://www.gnu.org/

//
// \file       htdocs/lib/lib_photoresize.js
// \brief      File that include javascript functions for croping feature
//

/* Enable jcrop plugin onto id cropbox */
jQuery(function() {
    jQuery('#cropbox').Jcrop({
        onSelect: updateCoords,
        onChange: updateCoords
    });
});

/* Update fields that store new size */
function updateCoords(c)
{
	//alert(parseInt(jQuery("#ratioforcrop").val()));
	ratio=1;
	if (parseInt(jQuery("#ratioforcrop").val()) > 0) ratio = parseInt(jQuery("#ratioforcrop").val());
	//console.log(ratio);
	jQuery('#x').val(Math.ceil(c.x * ratio));
	jQuery('#y').val(Math.ceil(c.y * ratio));
	jQuery('#x2').val(Math.ceil(c.x2 * ratio));
	jQuery('#y2').val(Math.ceil(c.y2 * ratio));
	jQuery('#w').val(Math.ceil(c.w * ratio));
	jQuery('#h').val(Math.ceil(c.h * ratio));
};
