/*
 * http://deepliquid.com/content/Jcrop.html
 */

//
// \file       htdocs/lib/lib_photoresize.js
// \brief      File that include javascript functions for croping feature
// \version    $Id$
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
