
var $mainNav = $('#mainNav');
var stickyoffset = $mainNav.offset().top;

$(window).scroll(function() {
    if ($(this).scrollTop() >= stickyoffset) {
        if(!$mainNav.hasClass('stickytop')) {
            $mainNav.addClass('stickytop');

            $('.nav-item').each(function() {
                $(this).addClass('stickytop')
            });

            $('#menuLogo').fadeIn();
            $('#logo').addClass('hiddenLogo');
            stickyoffset = $mainNav.offset().top;
        }
    }
    else {
        $('#menuLogo').fadeOut();

        if($mainNav.hasClass('stickytop')) {
            $mainNav.removeClass('stickytop');

            $('#logo').removeClass('hiddenLogo');

            $('.nav-item').each(function () {
                $(this).removeClass('stickytop')
            });
        }
    }
});