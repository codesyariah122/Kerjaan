/**
 * Tutor Paid Topic Runtime Script
 * Auto-defined global: TPT_Ajax (REST URL & nonce)
 */
document.addEventListener("DOMContentLoaded", () => {
    console.log("✅ tutor-paid-topic-runtime.js aktif");
    console.log("REST Endpoint:", TPT_Ajax?.resturl || "(tidak terdeteksi)");

    // kamu bisa pakai TPT_Ajax di sini
    // contoh cek API respons
    /*
    fetch(TPT_Ajax.resturl + 'check-order?topic_id=123', {
        headers: { 'X-WP-Nonce': TPT_Ajax.nonce }
    })
    .then(r => r.json())
    .then(d => console.log("Sample check:", d));
    */
});
