/**
 * 🎬 Tutor LMS Cinematic Player 2.0
 * Author: Puji Ermanto
 * Features:
 *  - Auto convert YouTube watch?v= to embed
 *  - Fade-in transition
 *  - Dark cinematic mode (lights off)
 *  - Auto scroll lock during fullscreen
 */

document.addEventListener('DOMContentLoaded', function () {

    const body = document.body;

    // 🔸 Create overlay element (for dark mode)
    const overlay = document.createElement('div');
    overlay.className = 'tpt-cinematic-overlay';
    body.appendChild(overlay);

    // 🔹 Convert <video> to YouTube embed
    document.querySelectorAll('.tutor-video-player .tutorPlayer').forEach(video => {
        const source = video.querySelector('source');
        if (!source) return;

        let src = source.src;
        if (!src) return;

        // YouTube long format
        const ytMatch = src.match(/youtube\.com\/watch\?v=([^\&\?]+)/);
        if (ytMatch && ytMatch[1]) {
            src = `https://www.youtube.com/embed/${ytMatch[1]}?rel=0&showinfo=0`;
        }

        // YouTube short format
        const shortMatch = src.match(/youtu\.be\/([a-zA-Z0-9_-]+)/);
        if (shortMatch && shortMatch[1]) {
            src = `https://www.youtube.com/embed/${shortMatch[1]}?rel=0&showinfo=0`;
        }

        if (!src.includes('youtube.com/embed')) return;

        const iframe = document.createElement('iframe');
        iframe.src = src;
        iframe.allow = "autoplay; fullscreen; picture-in-picture";
        iframe.allowFullscreen = true;
        iframe.frameBorder = "0";
        iframe.className = 'tpt-youtube-embed tpt-fade-in';
        iframe.style.width = '100%';
        iframe.style.aspectRatio = '16 / 9';

        video.parentNode.replaceChild(iframe, video);

        // 🎧 Event: Cinematic mode when playing
        iframe.addEventListener('load', () => {
            const playerWrapper = iframe.closest('.tutor-video-player');
            if (playerWrapper) {
                // Fade in
                setTimeout(() => playerWrapper.classList.add('tpt-visible'), 150);
            }
        });
    });

    // 🎥 Listen to fullscreen changes
    document.addEventListener('fullscreenchange', () => {
        const isFull = !!document.fullscreenElement;
        body.classList.toggle('tpt-fullscreen-mode', isFull);
        overlay.classList.toggle('active', isFull);
        if (isFull) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    });

    // 🔘 Toggle cinematic mode manually (lights on/off)
    const btn = document.createElement('button');
    btn.className = 'tpt-toggle-cinema';
    btn.innerHTML = '<i class="fas fa-film"></i> Cinema Mode';
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isActive = overlay.classList.toggle('active');
        btn.classList.toggle('active', isActive);
        body.classList.toggle('tpt-cinema-active', isActive); // ✅ posisi video ke center
    });
    document.querySelector('.tutor-course-topic-single-header')?.appendChild(btn);

    // 🖱️ Tutup cinema mode saat klik di area overlay
    overlay.addEventListener('click', () => {
        overlay.classList.remove('active');
        btn.classList.remove('active');
    });

    // Opsional: tekan ESC untuk keluar juga
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            overlay.classList.remove('active');
            btn.classList.remove('active');
        }
    });

    overlay.addEventListener('click', () => {
        overlay.classList.remove('active');
        btn.classList.remove('active');
        body.classList.remove('tpt-cinema-active'); // ✅ keluar dari center mode
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            overlay.classList.remove('active');
            btn.classList.remove('active');
            body.classList.remove('tpt-cinema-active'); // ✅ keluar dari center mode
        }
    });
});
