/* Create an inline datepicker which leverages the
   jQuery UI datepicker 
*/
$.editable.addInputType('datepicker', {
  element: function(settings, original) {
      var input = $('<input />');

      input.datepicker({
        onSelect: function(dateText, inst) {
          $(this).parents("form").submit();
        }
      });
      
      $(this).append(input);
      return (input);
  }
});