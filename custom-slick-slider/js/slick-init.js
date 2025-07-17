jQuery(window).on('elementor/frontend/init', function () {
    console.log('slick-init loaded ✅');

    elementorFrontend.hooks.addAction('frontend/element_ready/custom_slick_slider.default', function ($scope, $) {
        var $slider = $scope.find('.animated-slider');

        // Cek slider-nya ditemukan atau tidak
        console.log("Slider found:", $slider.length);

        $slider.not('.slick-initialized').slick({
            dots: true,
            arrows: false,
            autoplay: true,
            autoplaySpeed: 5000,
            infinite: true,
            speed: 800,
            fade: true,
            adaptiveHeight: false,
            cssEase: 'ease'
        });

        console.log("Slick initialized 🛞")
    });
});


