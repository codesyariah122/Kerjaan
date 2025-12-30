document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const courseId = urlParams.get('course_id');

    // Jangan lanjut kalau bukan halaman course builder
    if (!courseId) return;

    let renderTimeout;
    let lastRender = 0;

    // 🧩 Render badge harga di semua topic (throttled)
    const renderAllTopicPrices = () => {
        const now = Date.now();
        if (now - lastRender < 3000) return; // maksimal 1x per 3 detik
        lastRender = now;

        clearTimeout(renderTimeout);
        renderTimeout = setTimeout(() => {
            // Ubah selector: Langsung cari elemen title yang ada di semua topic
            const titleEls = document.querySelectorAll('.css-1jlm4v3');
            if (!titleEls.length) return;

            titleEls.forEach(titleEl => {
                const topicTitle = titleEl.textContent.trim().replace(/Rp \d+(?:,\d{3})*$/, '').trim(); // Hapus badge lama dari text jika ada
                if (!topicTitle) return;

                // Hapus badge lama biar update
                titleEl.querySelectorAll('.tpt-price-badge').forEach(b => b.remove());

                fetch(`${TPT_Ajax.resturl}get-price?title=${encodeURIComponent(topicTitle)}&course_id=${courseId}`, {
                    headers: { 'X-WP-Nonce': TPT_Ajax.nonce }
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data?.price && data.price > 0) {
                            const badge = document.createElement('span');
                            badge.className = 'tpt-price-badge';
                            badge.textContent = `Rp ${Number(data.price).toLocaleString()}`;
                            badge.style.cssText = `
                            margin-left:10px;font-size:13px;background:#ED2D56;color:#fff;
                            padding:2px 8px;border-radius:8px;font-weight:600;
                            display:inline-block;vertical-align:middle;
                        `;
                            titleEl.appendChild(badge);
                        }
                    })
                    .catch(err => console.warn("Fetch error (get-price):", err));
            });
        }, 800);
    };

    // 🧱 Tambahkan input harga ke modal editor
    const attachTopicEditor = () => {
        document.querySelectorAll('.css-oks3g7').forEach(topicEl => {
            if (topicEl.querySelector('.tutor-topic-price')) return;

            const input = document.createElement('input');
            input.type = 'number';
            input.placeholder = 'Masukkan harga topik (Rp)';
            input.className = 'tutor-input-field tutor-topic-price';
            input.style.cssText = 'margin-top:10px;width:100%;border:1px solid #ddd;padding:6px;border-radius:8px;';
            const wrapper = topicEl.querySelector('.css-15gb5bw');
            if (wrapper) wrapper.after(input);

            const titleInput = topicEl.querySelector('input[name="title"]');
            if (titleInput?.value) {
                fetch(`${TPT_Ajax.resturl}get-price?title=${encodeURIComponent(titleInput.value)}&course_id=${courseId}`, {
                    headers: { 'X-WP-Nonce': TPT_Ajax.nonce }
                })
                    .then(r => r.json())
                    .then(data => { if (data?.price) input.value = data.price; });
            }

            const saveBtn = topicEl.querySelector('button[data-cy="save-topic"]');
            if (saveBtn && !saveBtn.dataset.tptBound) {
                saveBtn.dataset.tptBound = true;
                saveBtn.addEventListener('click', () => {
                    const title = titleInput?.value?.trim();
                    const price = input.value;
                    if (!title || !courseId) return;

                    fetch(`${TPT_Ajax.resturl}save-price`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': TPT_Ajax.nonce
                        },
                        body: JSON.stringify({ title, price, course_id: courseId })
                    })
                        .then(r => r.json())
                        .then(resp => {
                            console.log('💾 Saved:', resp);
                            if (resp.synced) {
                                // 🔄 Coba update Regular Price dengan retry sampai elemen muncul
                                let retry = 0;
                                const tryUpdateRegularPrice = () => {
                                    const regularPriceInput = document.querySelector('input[name="course_price"]');
                                    if (regularPriceInput) {
                                        const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
                                            window.HTMLInputElement.prototype,
                                            "value"
                                        ).set;
                                        nativeInputValueSetter.call(regularPriceInput, price);

                                        const inputEvent = new Event("input", { bubbles: true });
                                        const changeEvent = new Event("change", { bubbles: true });
                                        regularPriceInput.dispatchEvent(inputEvent);
                                        regularPriceInput.dispatchEvent(changeEvent);

                                        console.log("✅ Regular Price field found and updated:", price);

                                        // Refresh tab agar React sinkron
                                        const basicsTab = document.querySelector('a[href="#/basics"]');
                                        if (basicsTab) {
                                            basicsTab.click();
                                            console.log("🌀 Refreshing Basics tab...");
                                            setTimeout(() => {
                                                const curriculumTab = document.querySelector('a[href="#/curriculum"]');
                                                if (curriculumTab) curriculumTab.click();
                                            }, 1500);
                                        }
                                    } else if (retry < 10) {
                                        retry++;
                                        console.log("⏳ Regular Price field not found yet... retry", retry);
                                        setTimeout(tryUpdateRegularPrice, 500);
                                    } else {
                                        console.warn("❌ Regular Price input never appeared in DOM");
                                    }
                                };
                                tryUpdateRegularPrice();
                            }
                        })

                        .catch(err => console.error('REST Error:', err));
                });
            }
        });
    };

    // 🧭 Observer: pantau perubahan tapi batasi trigger
    const observer = new MutationObserver(() => {
        attachTopicEditor();
        renderAllTopicPrices();
    });

    observer.observe(document.body, { childList: true, subtree: true });

    // Jalankan awal (1.5 detik delay)
    setTimeout(() => {
        attachTopicEditor();
        renderAllTopicPrices();
    }, 1500);
});
