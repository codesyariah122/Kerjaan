document.addEventListener('DOMContentLoaded', function () {
    const icon = document.querySelector('.fwc-icon');
    const bubble = document.querySelector('.fwc-bubble');
    const close = document.querySelector('.fwc-close');

    if (icon && bubble && close) {
        icon.addEventListener('mouseenter', () => {
            bubble.style.display = 'flex';
        });
        close.addEventListener('click', () => {
            bubble.style.display = 'none';
        });
    }
});
