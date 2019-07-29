/* TODO PRG */

$(document).ready(function () {
    // toggle promo code im cart preview
    $('.js-promo-code-toggle-checkbox').on("change", function (event) {
        $(this).toggleClass('active');
        $('.js-promo-code-toggle-content').toggleClass('active');
    });
});
