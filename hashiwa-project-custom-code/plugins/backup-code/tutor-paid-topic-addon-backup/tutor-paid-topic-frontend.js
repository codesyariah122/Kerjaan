document.addEventListener("DOMContentLoaded", () => {
    const updatePriceBadge = (card) => {
        const titleEl = card.querySelector('.tutor-course-title');
        if (!titleEl || titleEl.querySelector('.tpt-price-badge')) return;

        // 🔹 Ambil topic_id dari class atau dataset
        let topicId = null;
        const topicClass = card.className.match(/tutor-course-topic-(\d+)/);
        if (topicClass) {
            topicId = topicClass[1];
        } else if (card.dataset.topicId) {
            topicId = card.dataset.topicId;
        }

        const title = titleEl.textContent.trim();
        const endpoint = topicId
            ? `${TPT_Ajax.resturl}get-price?topic_id=${topicId}`
            : `${TPT_Ajax.resturl}get-price?title=${encodeURIComponent(title)}`;

        fetch(endpoint, { headers: { 'X-WP-Nonce': TPT_Ajax.nonce } })
            .then(r => r.json())
            .then(data => {
                if (data?.price && data.price > 0) {
                    const badge = document.createElement('span');
                    badge.className = 'tpt-price-badge';
                    badge.textContent = `Rp ${Number(data.price).toLocaleString()}`;
                    badge.style.cssText = `
                        margin-left:10px;
                        font-size:13px;
                        background:#ED2D56;
                        color:#fff;
                        padding:2px 8px;
                        border-radius:8px;
                        font-weight:600;
                        display:inline-block;
                        vertical-align:middle;
                    `;
                    titleEl.appendChild(badge);
                }
            })
            .catch(err => console.warn("Fetch error (get-price):", err));
    };

    // 🔹 Initial load
    document.querySelectorAll('.tutor-course-card').forEach(updatePriceBadge);

    // 🔹 Observe perubahan dynamic (Ajax load)
    const observer = new MutationObserver(() => {
        document.querySelectorAll('.tutor-course-card').forEach(updatePriceBadge);
    });
    observer.observe(document.body, { childList: true, subtree: true });
});
