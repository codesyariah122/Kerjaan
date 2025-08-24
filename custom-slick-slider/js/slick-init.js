jQuery(window).on('elementor/frontend/init', function () {
    console.log('slick-init loaded ✅');

    elementorFrontend.hooks.addAction('frontend/element_ready/custom_slick_slider.default', function ($scope, $) {
        var $slider = $scope.find('.animated-slider');

        console.log("Slider found:", $slider.length);

        if ($slider.length) {
            $slider.not('.slick-initialized').on('init', function (event, slick) {
                console.log("Slick init event fired 🚀");

                // // Paksa full width setelah slick selesai init
                // $slider.find('.slick-list, .slick-track').css({
                //     'width': '100%',
                //     'max-width': '100%',
                //     'margin': '0',
                //     'padding': '0'
                // });
                $slider.find('.slick-list, .slick-track').css({
                    'margin': '0',
                    'padding': '0'
                });
            }).slick({
                dots: true,
                arrows: false,
                autoplay: true,
                autoplaySpeed: 5000,
                infinite: true,
                speed: 800,
                fade: true,
                adaptiveHeight: false,
                cssEase: 'ease',
                centerMode: false,
                variableWidth: false
            });

            // Recalculate saat resize/orientationchange
            jQuery(window).on('resize orientationchange', function () {
                $slider.slick('setPosition');
            });
        }

        console.log("Slick initialized 🛞");
    });
});
