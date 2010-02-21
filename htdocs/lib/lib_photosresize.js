/*
* http://deepliquid.com/content/Jcrop.html
*/


jQuery(function() {
   jQuery('#cropbox').Jcrop({
      onSelect: updateCoords, 
      onChange: updateCoords
   });
});
     
function updateCoords(c)
{
   $('#x').val(c.x);
   $('#y').val(c.y);
   $('#x2').val(c.x2);
   $('#y2').val(c.y2);
   $('#w').val(c.w);
   $('#h').val(c.h);
};

function checkCoords()
{
   if (parseInt($('#w').val())) return true;
   alert('Please select a crop region then press submit.');
   return false;
};
   