<?php
add_action('wp_footer', function () {
?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const menuItems = document.querySelectorAll(".menu li");

            menuItems.forEach(item => {
                const link = item.querySelector(".ct-menu-link");
                if (!link) return;

                const text = link.textContent.trim().toLowerCase();

                let iconClass = "";
                if (text.includes("about")) iconClass = "fa-users";
                else if (text.includes("news")) iconClass = "fa-newspaper";
                else if (text.includes("companies")) iconClass = "fa-people-roof";
                else if (text.includes("communities")) iconClass = "fa-image";
                else if (text.includes("services")) iconClass = "fa-people-line";
                else if (text.includes("insight")) iconClass = "fa-image";

                if (iconClass) {
                    link.innerHTML = `<i class="fas ${iconClass}" style="margin-right: 8px;"></i>` + link.innerHTML;
                }
            });

            var header = document.querySelector(".menu");
            window.addEventListener("scroll", function() {
                if (window.scrollY > 50) {
                    header.classList.add("menu-scrolled");
                } else {
                    header.classList.remove("menu-scrolled");
                }
            });
        });
    </script>
<?php
});
