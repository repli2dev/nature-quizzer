$(window).scroll(function() {
    if($(window).scrollTop() + $(window).height() > $(document).height() - 100) {
        $('body').addClass('scrolled-bottom');
    } else {
        $('body').removeClass('scrolled-bottom');
    }
});
