$(document).ready(function() {

  // Scrollspy changes navbar active links
    $("body").scrollspy({target:"#my-navbar-nav", offset:50});

  // Smooth scroll
    $("a").click(function(event) {
      if (this.hash !== "") {
       event.preventDefault();
       var linkOffset = 0;
       if ($.inArray(this.hash,["#options","#xmlExport","#optionsLanguage","#setDayMajorDateDisplayFormat"]) != -1) {
         linkOffset = -25;
       }
       $("html, body").animate({
         scrollTop: $(this.hash).offset().top - $(".navbar").height() + linkOffset
       }, 600);
      }
    });

  // Demo buttons
    $("#embedded-Gantt").hide(0);
    $("#external-Gantt").hide(0);

    $(".btn-demo").click(function() {
      if ($(this).html().indexOf("Embedded Code") != -1) {
        if ($("#external-Gantt").is(":visible")) {
          $("#external-Gantt").animate({
            height: "toggle",
            opacity: "toggle"}, 300, function () {
              $("#embedded-Gantt").animate({
                height: "toggle",
                opacity: "toggle"}, 600
              );
            }
          );
          $(".btn-demo:nth-child(2)").removeClass("active");
        } else {
          $("#embedded-Gantt").animate({
            height: "toggle",
            opacity: "toggle"}, 600
          );
        }
      } else {
        if ($("#embedded-Gantt").is(":visible")) {
          $("#embedded-Gantt").animate({
            height: "toggle",
            opacity: "toggle"}, 300, function() {
              $("#external-Gantt").animate({
                height: "toggle",
                opacity: "toggle"}, 600
              );
            }
          );
          $(".btn-demo:nth-child(1)").removeClass("active");
        } else {
          $("#external-Gantt").animate({
            height: "toggle",
            opacity: "toggle"}, 600
          );
        }
      }
    });

  // Slideshow
    var slideIndex = 0;
    carousel();

    function carousel() {
      var i;
      var x = document.getElementsByClassName("slide");
      var d = document.getElementsByClassName("dot");
      for (i = 0; i < x.length; i++) {
        x[i].style.display = "none";
      }
      slideIndex++;
      if (slideIndex > x.length) {slideIndex = 1}
      x[slideIndex-1].style.display = "inline-block";
      $(".slide:nth-child(" + (slideIndex).toString() + ")").animate({
        opacity: 1
      }, 500);
      $(".dot").removeClass("active");
      $(".dot:nth-child(" + (slideIndex).toString() + ")").addClass("active");
      setTimeout(carousel, 2000); // Change image every 2 seconds
    }
  });
