jQuery(window).on('elementor/frontend/init', function () {
    elementorFrontend.hooks.addAction('frontend/element_ready/custom_slick_slider.default', function ($scope, $) {
        $scope.find('.animated-slider').not('.slick-initialized').slick({
            dots: true,
            arrows: false,
            autoplay: true,
            autoplaySpeed: 5000,
            infinite: true,
            speed: 800,
            fade: false,
            cssEase: 'ease'
        });
    });
});