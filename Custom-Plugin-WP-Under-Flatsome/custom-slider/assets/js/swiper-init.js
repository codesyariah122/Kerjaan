document.addEventListener('DOMContentLoaded', function () {
    new Swiper('.cs-swiper-container', {
        loop: true,
        slidesPerView: 3,
        centeredSlides: true,
        spaceBetween: 20,
        breakpoints: {
            768: {
                slidesPerView: 1
            },
            1024: {
                slidesPerView: 3
            }
        }
    });
});
