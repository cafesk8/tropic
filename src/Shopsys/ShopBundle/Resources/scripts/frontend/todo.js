/* TODO PRG */

$(document).ready(function () {
    $('.js-product-filter-opener').click(function (e) {
        $('.js-product-list-panel').toggleClass('active');
        $('.js-product-filter').toggleClass('active-mobile');
        e.preventDefault();
        setFilterPosition();
    });

    var setFilterPosition = function () {
        if ($(window).width() < 1024) {
            var position = $('.js-product-list').position();
            var newPosition = position.top;
            $('.js-product-list-panel').css({ 'top': newPosition });
        } else {
            $('.js-product-list-panel').css({ 'top': 0 });
        }
    };

    $(window).resize(function () {
        setFilterPosition();
    });

    $('.js-product-filter-opener').click(function(e){
        e.preventDefault();
        setFilterPosition();
    });

    var setFilterPosition = function () {
        if($(window).width() < 1024){
            var position = $('.js-product-list').position();
            var newPosition = position.top;
            $('.js-product-list-panel').toggleClass('active');
            $('.js-product-filter').toggleClass('active-mobile');
            $('.js-product-list-panel').css({ 'top': newPosition });
        } else {
            $('.js-product-list-panel').removeClass('active');
            $('.js-product-filter').removeClass('active-mobile');
            $('.js-product-list-panel').css({ 'top': 0 });
        }
    }

    $( window ).resize(function() {
        setFilterPosition();
    });
});
