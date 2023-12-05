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
// along with this program. If not, see <https://www.gnu.org/licenses/>.
// or see https://www.gnu.org/

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
	imagewidth=0;
	imageheight=0;
	
	console.log(c);

	if (parseInt(jQuery("#ratioforcrop").val()) > 1) {
		ratio = parseInt(jQuery("#ratioforcrop").val());
		if (parseInt(jQuery("#imagewidth").val()) > 0) imagewidth = parseInt(jQuery("#imagewidth").val());
		if (parseInt(jQuery("#imageheight").val()) > 0) imageheight = parseInt(jQuery("#imageheight").val());
	}
	
	x = Math.floor(c.x * ratio);
	y = Math.floor(c.y * ratio);
	x2 = Math.ceil(c.x2 * ratio);
	y2 = Math.ceil(c.y2 * ratio);
	console.log("x="+x+" y="+y+" x2="+x2+" y2="+y2+" imageheight="+imageheight+" ratio="+ratio);
	if (imagewidth > 0 && x > imagewidth) {
		x = imagewidth;
	}
	if (imageheight > 0 && y > imageheight) {
		y = imageheight;
	}
	if (imagewidth > 0 && x2 > imagewidth) {
		x2 = imagewidth;
	}
	if (imageheight > 0 && y2 > imageheight) {
		y2 = imageheight;
	}
	
	//console.log(ratio);
	jQuery('#x').val(x);
	jQuery('#y').val(y);
	jQuery('#x2').val(x2);
	jQuery('#y2').val(y2);
	jQuery('#w').val(x2-x);
	jQuery('#h').val(y2-y);
};
