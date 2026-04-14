(function ($) {
  "use strict";

  $(document).ready(function () {
    // Mobile Menu
    const sidebarToggle = $(".sidebar-toggler");
    if (sidebarToggle) {
      sidebarToggle.on("click", function () {
        $("body").addClass("sidebar-open");
      });
    }

    $('.doc__sidebar_close').on('click', function () {
      $("body").removeClass('sidebar-open')

    });

    function addActiveClassToSidenav() {
      $('.section--sm').each((index, element) => {
        var offsetTop = $(element).offset().top; 
        var elementHeight = $(element).outerHeight(); 
        var distance = offsetTop - $(window).scrollTop(); 

        if (distance <= 88 && distance + elementHeight > 0) {
          let menu = $(`a[href="#${element.id}"]`);
          $('.doc-nav__link.nav-link').not(menu).removeClass('active');
          menu.addClass('active');
        }
      });
    }

    $(window).on("scroll", function () {
      addActiveClassToSidenav();
    });

    addActiveClassToSidenav();

  });

})(jQuery);
