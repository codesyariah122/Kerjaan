document.addEventListener("DOMContentLoaded", () => {
    window.TPT_COURSE_ID = new URLSearchParams(window.location.search).get("course_id") || null;
    let renderTimeout;

    // 🔹 Buat input harga per topic
    const createPriceInput = (block, price = 0) => {
        if (block.querySelector(".tpt-topic-price")) return; // jangan double inject

        const titleInput = block.querySelector('input[name="title"]');
        if (!titleInput) return;

        const input = document.createElement("input");
        input.type = "number";
        input.placeholder = "Harga per topic (Rp)";
        input.className = "tpt-topic-price";
        input.value = price; // isi dengan harga existing

        // simpan topic_id supaya bisa inject ke form
        const topicId = block.dataset.topicId || null;
        if (topicId) input.dataset.topicId = topicId;

        Object.assign(input.style, {
            marginTop: "8px",
            width: "200px",
            fontSize: "13px",
            padding: "6px 12px",
            border: "1px solid #ccc",
            borderRadius: "8px",
            display: "block",
            transition: "all 0.2s ease",
        });
        titleInput.parentElement.appendChild(input);

        // Simpan saat user input
        input.addEventListener("change", () => handlePriceSave(block, input));
    };
    // 🔹 Handle simpan harga via AJAX
    // const handlePriceSave = (block, input) => {
    //     const price = parseInt(input.value || 0);
    //     if (isNaN(price)) return;

    //     const titleWrapper = block.querySelector(".css-11s3wkf");
    //     if (!titleWrapper) return;

    //     let saveLoader = titleWrapper.querySelector(".tpt-badge-loader");
    //     if (!saveLoader) {
    //         saveLoader = document.createElement("span");
    //         saveLoader.className = "tpt-badge-loader";
    //         saveLoader.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i>`;
    //         Object.assign(saveLoader.style, {
    //             marginLeft: "10px",
    //             color: "#999",
    //             fontSize: "13px",
    //             opacity: "0.7",
    //         });
    //         titleWrapper.appendChild(saveLoader);
    //     }

    //     const courseForm = document.querySelector('form[data-course-id]');
    //     const courseId = courseForm?.dataset.courseId;
    //     const editableTitle = block.querySelector('input[name="title"]').value.trim();

    //     if (!courseId) {
    //         console.warn("⚠️ course_id tidak ditemukan");
    //         saveLoader.remove();
    //         return;
    //     }

    //     // Kirim AJAX simpan harga
    //     jQuery.post(TPT_Ajax.ajax_url, {
    //         action: "tpt_save_price",
    //         title: editableTitle,
    //         course_id: courseId,
    //         price: price,
    //         nonce: TPT_Ajax.nonce
    //     }).done(resp => {
    //         saveLoader.remove();
    //         if (resp.success) {
    //             titleWrapper.querySelectorAll(".tpt-price-badge").forEach(el => el.remove());
    //             const badge = document.createElement("span");
    //             badge.className = "tpt-price-badge";
    //             badge.innerHTML = `<i class="fa-solid fa-coins"></i> Rp ${price.toLocaleString()}`;
    //             Object.assign(badge.style, {
    //                 marginLeft: "12px",
    //                 background: "#ED2D56",
    //                 color: "#fff",
    //                 fontSize: "14px",
    //                 padding: "6px 18px",
    //                 borderRadius: "20px",
    //                 fontWeight: "600",
    //                 boxShadow: "0 2px 8px rgba(237,45,86,0.4)",
    //                 display: "inline-flex",
    //                 alignItems: "center",
    //                 justifyContent: "center",
    //                 gap: "6px",
    //                 minWidth: "150px",
    //             });
    //             titleWrapper.appendChild(badge);
    //         } else {
    //             input.style.borderColor = "#f44336";
    //             input.style.boxShadow = "0 0 6px rgba(244,67,54,0.5)";
    //         }
    //     }).fail(err => {
    //         saveLoader.remove();
    //         console.error("⚠️ AJAX gagal:", err);
    //         input.style.borderColor = "#f44336";
    //         input.style.boxShadow = "0 0 6px rgba(244,67,54,0.5)";
    //     });
    // };
    // 🔹 Handle simpan harga via AJAX (versi fix)
    const handlePriceSave = (block, input) => {
        const price = parseInt(input.value || 0);
        if (isNaN(price)) return;

        const titleWrapper = block.querySelector(".css-11s3wkf");
        if (!titleWrapper) return;

        let saveLoader = titleWrapper.querySelector(".tpt-badge-loader");
        if (!saveLoader) {
            saveLoader = document.createElement("span");
            saveLoader.className = "tpt-badge-loader";
            saveLoader.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i>`;
            Object.assign(saveLoader.style, {
                marginLeft: "10px",
                color: "#999",
                fontSize: "13px",
                opacity: "0.7",
            });
            titleWrapper.appendChild(saveLoader);
        }

        const courseForm = document.querySelector('form[data-course-id]');
        const courseId = courseForm?.dataset.courseId;
        const editableTitle = block.querySelector('input[name="title"]').value.trim();
        const topicId = input.dataset.topicId || null;

        if (!courseId) {
            console.warn("⚠️ course_id tidak ditemukan");
            saveLoader.remove();
            return;
        }

        // 🚀 Kirim AJAX dengan fallback topic_id
        jQuery.post(TPT_Ajax.ajax_url, {
            action: "tpt_save_price",
            title: editableTitle,
            course_id: courseId,
            topic_id: topicId, // 🔥 kirim topic_id langsung
            price: price,
            nonce: TPT_Ajax.nonce
        }).done(resp => {
            saveLoader.remove();
            if (resp.success) {
                console.log("✅ Harga topic tersimpan:", resp.data);
                if (typeof renderTopicPrices === "function") {
                    console.log("♻️ [TPT] Refreshing topic price badges...");
                    setTimeout(() => renderTopicPrices(), 800);
                }

                titleWrapper.querySelectorAll(".tpt-price-badge").forEach(el => el.remove());
                const badge = document.createElement("span");
                badge.className = "tpt-price-badge";
                badge.innerHTML = `<i class="fa-solid fa-coins"></i> Rp ${price.toLocaleString()}`;
                Object.assign(badge.style, {
                    marginLeft: "12px",
                    background: "#ED2D56",
                    color: "#fff",
                    fontSize: "14px",
                    padding: "6px 18px",
                    borderRadius: "20px",
                    fontWeight: "600",
                    boxShadow: "0 2px 8px rgba(237,45,86,0.4)",
                    display: "inline-flex",
                    alignItems: "center",
                    justifyContent: "center",
                    gap: "6px",
                    minWidth: "150px",
                });
                titleWrapper.appendChild(badge);
            } else {
                console.warn("❌ Gagal simpan harga:", resp.data || resp);
                input.style.borderColor = "#f44336";
                input.style.boxShadow = "0 0 6px rgba(244,67,54,0.5)";
            }
        }).fail(err => {
            saveLoader.remove();
            console.error("⚠️ AJAX gagal:", err);
            input.style.borderColor = "#f44336";
            input.style.boxShadow = "0 0 6px rgba(244,67,54,0.5)";
        });
    };

    // 🔹 Render harga dan badge topic
    const renderTopicPrices = () => {
        clearTimeout(renderTimeout);
        renderTimeout = setTimeout(() => {
            const topicWrappers = document.querySelectorAll(".css-1g2ghqr, .css-rxenkd");
            if (!topicWrappers.length) return;

            topicWrappers.forEach(block => {
                if (block.dataset.tptRendered === "1") return;
                block.dataset.tptRendered = "1";

                const titleInput = block.querySelector('input[name="title"]');
                const titleTextEl = block.querySelector(".css-1jlm4v3");
                const topicTitle = titleTextEl ? titleTextEl.textContent.trim() : null;
                const editableTitle = titleInput ? titleInput.value.trim() : topicTitle;
                const titleWrapper = block.querySelector(".css-11s3wkf");
                if (!titleWrapper) return;

                // const urlParams = new URLSearchParams(window.location.search);
                // const courseId = urlParams.get("course_id");
                const course_id = window.TPT_COURSE_ID;

                if (!course_id) return;

                // Loader & ambil harga existing
                if (!titleWrapper.querySelector(".tpt-badge-loader")) {
                    const loader = document.createElement("span");
                    loader.className = "tpt-badge-loader";
                    loader.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i>`;
                    Object.assign(loader.style, {
                        marginLeft: "10px",
                        color: "#999",
                        fontSize: "13px",
                        opacity: "0.7",
                    });
                    titleWrapper.appendChild(loader);
                }

                jQuery.post(TPT_Ajax.ajax_url, {
                    action: "tpt_get_price",
                    title: editableTitle,
                    course_id: course_id,
                    nonce: TPT_Ajax.nonce,
                }).done((resp) => {
                    titleWrapper.querySelectorAll(".tpt-badge-loader").forEach(el => el.remove());
                    titleWrapper.querySelectorAll(".tpt-price-badge").forEach(el => el.remove());

                    const price = parseInt(resp.data?.price || 0);

                    // Buat input dengan nilai existing
                    // createPriceInput(block, price);
                    const topicIdAttr = block.getAttribute("data-id") || block.dataset.topicId || null;
                    createPriceInput(block, price);
                    const input = block.querySelector(".tpt-topic-price");
                    if (input && topicIdAttr) input.dataset.topicId = topicIdAttr;

                    if (price > 0) {
                        const badge = document.createElement("span");
                        badge.className = "tpt-price-badge";
                        badge.innerHTML = `<i class="fa-solid fa-coins"></i> Rp ${price.toLocaleString()}`;
                        Object.assign(badge.style, {
                            marginLeft: "12px",
                            background: "#ED2D56",
                            color: "#fff",
                            fontSize: "14px",
                            padding: "6px 18px",
                            borderRadius: "20px",
                            fontWeight: "600",
                            boxShadow: "0 2px 8px rgba(237,45,86,0.4)",
                            display: "inline-flex",
                            alignItems: "center",
                            justifyContent: "center",
                            gap: "6px",
                            minWidth: "150px",
                        });
                        titleWrapper.appendChild(badge);
                    }
                });
            });
        }, 200);
    };

    // Observer untuk detect DOM update
    const observer = new MutationObserver(() => renderTopicPrices());
    observer.observe(document.querySelector("#wpbody-content") || document.body, {
        childList: true,
        subtree: true,
    });

    renderTopicPrices();

    const injectPricingBeforeReactUpdate = () => {
        const updateBtn = document.querySelector('button[data-cy="course-builder-submit-button"]');
        if (!updateBtn) return;

        updateBtn.addEventListener('click', () => {
            const courseId = window.TPT_COURSE_ID || document.querySelector('form[data-course-id]')?.dataset.courseId || null;
            if (!courseForm) return;

            // Hapus semua input tpt_pricing lama
            courseForm.querySelectorAll('input[name^="tpt_pricing["]').forEach(el => el.remove());

            // Inject harga tiap topic
            const topicInputs = Array.from(document.querySelectorAll('.tpt-topic-price'));
            topicInputs.forEach(input => {
                const topicId = input.dataset.topicId;
                if (!topicId) return;

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = `tpt_pricing[${topicId}]`;
                hidden.value = input.value || 0;
                courseForm.appendChild(hidden);
            });

            // Set harga topic pertama sebagai regular_price
            const firstPrice = parseInt(topicInputs[0]?.value || 0) || 0;
            let mainPriceInput = courseForm.querySelector('input[name="pricing[regular_price]"]');
            if (!mainPriceInput) {
                mainPriceInput = document.createElement('input');
                mainPriceInput.type = 'hidden';
                mainPriceInput.name = 'pricing[regular_price]';
                courseForm.appendChild(mainPriceInput);
            }
            mainPriceInput.value = firstPrice;

            // Pastikan field wajib Tutor LMS ada
            const ensureInput = (name, value) => {
                let input = courseForm.querySelector(`input[name="${name}"]`);
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    courseForm.appendChild(input);
                }
                input.value = value;
            };
            ensureInput('title', document.querySelector('input[name="course_title"]')?.value || "Untitled Course");
            ensureInput('status', 'publish');
            ensureInput('type', 'course');

            console.log('✅ Injected pricing fields before submit');
        }, true); // gunakan capture phase supaya dijalankan sebelum React
    };

    // Jalankan
    injectPricingBeforeReactUpdate();
});

(function interceptTutorAxios() {
    const waitAxios = () => {
        if (typeof window.axios === "undefined" || !window.axios.interceptors) {
            return setTimeout(waitAxios, 500);
        }
        if (window.__tptAxiosIntercepted) return;
        window.__tptAxiosIntercepted = true;

        window.axios.interceptors.request.use((config) => {
            try {
                if (config.url && config.url.includes("/wp-json/tutor/v1/courses/")) {
                    config.data = config.data || {};

                    const topicInputs = Array.from(document.querySelectorAll(".tpt-topic-price"));
                    const pricingObj = {};
                    topicInputs.forEach(input => {
                        const topicId = input.dataset.topicId;
                        if (topicId) pricingObj[topicId] = parseInt(input.value) || 0;
                    });

                    const firstPrice = Object.values(pricingObj)[0] || 0;

                    config.data.pricing = {
                        regular_price: firstPrice,
                        ...pricingObj
                    };

                    console.log("💡 [Tutor Patch] Injected pricing:", config.data.pricing);
                }
            } catch (err) {
                console.warn("⚠️ Tutor Axios intercept error:", err);
            }
            return config;
        });

        console.log("✅ Tutor LMS Axios pricing patch active");
    };

    waitAxios();
})();


console.log("🔥 [TPT] admin.js loaded");

// Tunggu tombol OK di modal edit topic
(function interceptTutorSaveTopic() {
    const observe = () => {
        const btns = document.querySelectorAll('button[data-cy="save-topic"], .css-u5asr1');
        if (!btns.length) return setTimeout(observe, 1000);

        btns.forEach(btn => {
            if (btn.dataset.tptBound === "1") return;
            btn.dataset.tptBound = "1";

            btn.addEventListener('click', () => {
                console.log("🟡 [TPT] Tombol OK diklik...");
                let modal = btn.closest('.css-oks3g7');
                if (!modal) {
                    modal = document.querySelector('.css-oks3g7,[role="dialog"],.tutor-modal-container');
                }
                if (!modal) {
                    console.warn("⚠️ [TPT] Modal tidak ditemukan (fallback gagal).");
                    return;
                }

                // tunggu title input muncul
                const waitForTitle = (attempt = 0) => {
                    const titleInput = modal.querySelector('input[name="title"]');
                    const title = titleInput ? titleInput.value.trim() : null;

                    if (!title) {
                        if (attempt < 10) {
                            return setTimeout(() => waitForTitle(attempt + 1), 100);
                        }
                        console.warn("⚠️ [TPT] Gagal: title tidak ditemukan di modal (setelah 1s).");
                        return;
                    }

                    // cari input harga di DOM utama yang cocok dengan judul topic
                    const priceInput = [...document.querySelectorAll('.tpt-topic-price')].find(el => {
                        const topicBlock = el.closest('.css-1g2ghqr, .css-rxenkd');
                        const inputTitle = topicBlock?.querySelector('input[name="title"]')?.value.trim();
                        const labelTitle = topicBlock?.querySelector('.css-1jlm4v3')?.textContent.trim();
                        return inputTitle === title || labelTitle === title;
                    });

                    if (!priceInput) {
                        console.warn("⚠️ [TPT] Tidak menemukan input harga untuk:", title);
                        return;
                    }

                    const price = parseInt(priceInput.value || 0);
                    // const urlParams = new URLSearchParams(window.location.search);
                    // const course_id = urlParams.get("course_id");
                    const course_id = window.TPT_COURSE_ID;


                    if (!course_id) {
                        console.warn("⚠️ [TPT] course_id tidak ditemukan.");
                        return;
                    }

                    console.log("📦 [TPT] Mengirim AJAX ke server...", { title, price, course_id });

                    jQuery.post(TPT_Ajax.ajax_url, {
                        action: "tpt_save_price",
                        title,
                        course_id,
                        price,
                        nonce: TPT_Ajax.nonce
                    }).done(resp => {
                        if (resp.success) {
                            console.log("✅ Topic price saved & WC product created:", resp.data);

                            // 🧩 Tambahan Baru: Auto-select product ke tab Basics
                            // 🧩 Auto-select product hanya untuk TOPIC PERTAMA
                            const { wc_id, topic_id } = resp.data;
                            if (!wc_id || !topic_id) return;

                            // Ambil semua topic dari REST API
                            jQuery.getJSON(`${window.location.origin}/wp-json/tpt/v1/get-topic-prices?course_id=${course_id}`)
                                .done(resp2 => {
                                    if (resp2.status_code !== 200 || !resp2.data?.topics?.length) return;

                                    const firstTopic = resp2.data.topics[0];
                                    const isFirstTopic = parseInt(firstTopic.id) === parseInt(topic_id);

                                    // ⛔ Lock keras: hanya jalan kalau topic pertama
                                    if (!isFirstTopic) {
                                        console.log("ℹ️ [TPT] Topic bukan yang pertama — skip auto-select.");
                                        return;
                                    }

                                    console.log("✅ [TPT] Auto-select aktif untuk topic pertama:", firstTopic.title);

                                    const waitForSelect = (attempt = 0) => {
                                        const input = document.querySelector('input[name="course_product_id"]');
                                        if (!input) {
                                            if (attempt < 20) return setTimeout(() => waitForSelect(attempt + 1), 300);
                                            return;
                                        }

                                        // Set nilai (gunakan setter React-safe)
                                        const nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, "value").set;
                                        nativeInputValueSetter.call(input, `${firstTopic.title}`);

                                        // Trigger React re-render
                                        input.dispatchEvent(new Event("input", { bubbles: true }));
                                        input.dispatchEvent(new Event("change", { bubbles: true }));

                                        // Efek visual
                                        input.style.borderColor = "#4CAF50";
                                        input.style.boxShadow = "0 0 6px rgba(76,175,80,0.5)";
                                        setTimeout(() => {
                                            input.style.borderColor = "";
                                            input.style.boxShadow = "";
                                        }, 1200);
                                    };

                                    waitForSelect();
                                });

                        } else {
                            console.warn("❌ Gagal simpan harga:", resp);
                        }
                    }).fail(err => {
                        console.error("⚠️ AJAX gagal:", err);
                    });
                };

                waitForTitle();
            });
        });
    };

    observe();
})();
