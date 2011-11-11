// Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
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

jQuery(function() {
   jQuery('#cropbox').Jcrop({
      onSelect: updateCoords, 
      onChange: updateCoords
   });
});
     
function updateCoords(c)
{
	jQuery('#x').val(c.x);
	jQuery('#y').val(c.y);
	jQuery('#x2').val(c.x2);
	jQuery('#y2').val(c.y2);
	jQuery('#w').val(c.w);
	jQuery('#h').val(c.h);
};

function checkCoords()
{
   if (parseInt(jQuery('#w').val())) return true;
   alert('Please select a crop region then press submit.');
   return false;
};
