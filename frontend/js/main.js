$(document).ready(function() {
    
    $('.hamburger').on('click', function() {
        $('.nav-menu').toggleClass('active');
        $(this).toggleClass('active');
    });

    
    $('.nav-link').on('click', function() {
        $('.nav-menu').removeClass('active');
        $('.hamburger').removeClass('active');
    });

    
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this.getAttribute('href'));
        if(target.length) {
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 70
            }, 1000);
        }
    });

    
    $('.dropdown').on('click', function(e) {
        e.stopPropagation();
        $(this).toggleClass('open');
    });

    $(document).on('click', function() {
        $('.dropdown').removeClass('open');
    });

    
    updateActiveLink();
    $(window).on('scroll', updateActiveLink);

    function updateActiveLink() {
        var scrollPos = $(document).scrollTop();
        
        $('a[href^="#"]').each(function() {
            var currLink = $(this);
            var refElement = $(currLink.attr("href"));
            if (refElement.length && 
                refElement.offset().top - 100 <= scrollPos && 
                refElement.offset().top + refElement.height() > scrollPos) {
                $('.nav-link').removeClass('active');
                currLink.addClass('active');
            }
        });
    }
});
