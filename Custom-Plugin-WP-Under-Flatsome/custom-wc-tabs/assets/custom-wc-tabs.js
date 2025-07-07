document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.custom-wc-tabs .tab-button');
    const cards = document.querySelectorAll('.custom-wc-tabs .product-card');
    const loader = document.querySelector('.product-loader');

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            // Aktifkan tab
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            const location = this.getAttribute('data-location');

            // Tampilkan loader
            loader.style.display = 'flex';

            // Delay kecil supaya loader terlihat
            setTimeout(() => {
                cards.forEach(card => {
                    const cardLocation = card.getAttribute('data-location');
                    if (location === 'all' || cardLocation === location) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Sembunyikan loader setelah filter selesai
                loader.style.display = 'none';
            }, 300);
        });
    });
});
