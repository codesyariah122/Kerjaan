document.addEventListener("DOMContentLoaded", function () {
    const buttons = document.querySelectorAll(".tab-button");
    const cards = document.querySelectorAll(".product-card");

    buttons.forEach((btn) => {
        btn.addEventListener("click", () => {
            buttons.forEach((b) => b.classList.remove("active"));
            btn.classList.add("active");

            const selected = btn.getAttribute("data-location");

            cards.forEach((card) => {
                const loc = card.getAttribute("data-location");
                if (selected === "all" || loc === selected) {
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }
            });
        });
    });
});
