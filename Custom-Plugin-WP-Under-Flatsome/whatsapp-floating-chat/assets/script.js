document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById('wa-floating-button');
    const box = document.getElementById('wa-chatbox');
    const closeBtn = document.getElementById('close-chatbox');
    const adminList = document.querySelectorAll('.admin');
    const chatForm = document.getElementById('chat-form');
    const textarea = document.getElementById('user-message');
    let selectedPhone = '';

    feather.replace();

    btn.addEventListener('click', () => {
        box.style.display = 'block';
    });

    closeBtn.addEventListener('click', () => {
        box.style.display = 'none';
        chatForm.style.display = 'none';
        document.getElementById('admin-list').style.display = 'block';
        textarea.value = '';
        selectedPhone = '';
    });

    adminList.forEach(admin => {
        admin.addEventListener('click', () => {
            selectedPhone = admin.getAttribute('data-phone');
            const adminName = admin.getAttribute('data-name');
            const adminImg = admin.querySelector('img').getAttribute('src');

            const adminInfo = document.getElementById('selected-admin-info');
            adminInfo.innerHTML = `
                <img src="${adminImg}" alt="${adminName}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                <span>${adminName}</span>
            `;

            chatForm.style.display = 'block';
            document.getElementById('admin-list').style.display = 'none';
        });
    });

    document.getElementById('send-whatsapp').addEventListener('click', () => {
        if (!selectedPhone) {
            alert('Pilih admin dulu ya!');
            return;
        }
        const message = encodeURIComponent(textarea.value.trim() || 'Halo, saya ingin konsultasi.');
        window.open(`https://wa.me/${selectedPhone}?text=${message}`, '_blank');
    });
});
