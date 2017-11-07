
var $mainNav = $('#mainNav');
var $main = $('main');
var stickyoffset = $main.offset().top - $mainNav.outerHeight();
$('header').css('height', $('header').outerHeight());


$(window).resize(function() {
    $('header').css('height', 'auto');
    $('header').css('height', $('header').outerHeight());
});


$(window).scroll(function() {
    if ($(this).scrollTop() >= stickyoffset) {
        if(!$mainNav.hasClass('stickytop')) {
            $mainNav.addClass('stickytop');

            $('header .nav-item').each(function() {
                $(this).addClass('stickytop')
            });

            $('#menuLogo').fadeIn();
            $('#logo').addClass('hiddenLogo');
            stickyoffset = $main.offset().top - $mainNav.outerHeight();

            if(!$('#toc-nav').hasClass('fixed-toc'))
                $('#toc-nav').addClass('fixed-toc');

        }
    }
    else {
        $('#menuLogo').fadeOut();

        if($mainNav.hasClass('stickytop')) {
            $mainNav.removeClass('stickytop');

            $('header').css('height', 'auto');
            $('header').css('height', $('header').outerHeight());

            $('#logo').removeClass('hiddenLogo');

            $('header .nav-item').each(function () {
                $(this).removeClass('stickytop')
            });

            if($('#toc-nav').hasClass('fixed-toc'))
                $('#toc-nav').removeClass('fixed-toc');
        }
    }
});