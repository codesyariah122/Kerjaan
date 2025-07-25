function enqueue_fontawesome_for_dropdown() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css');
}
add_action('wp_enqueue_scripts', 'enqueue_fontawesome_for_dropdown');

add_action('wp_footer', function () {
    ?>
<script>
	document.querySelectorAll('.dropdown-toggle').forEach(el => {
  el.setAttribute('data-bs-display', 'static');
});
</script>
    <script>
		jQuery(function($) {
			const dropdownContainer = document.querySelector('.departments-menu-v2 .dropdown');
			const menuToggle = document.querySelector('.departments-menu-v2-title');
			const stickyNavElement = document.querySelector('.electro-navigation');
			const mastHead = document.querySelector('#masthead');
			const iconElement = document.querySelector('.departments-menu-v2-title .departments-menu-v2-icon');
			const logoImg = document.querySelector(".header-logo img");
			const logoWrapper = document.querySelector(".header-logo");
// 			mastHead?.style.setProperty("background", "#ffffff", "important");
// 			mastHead?.style.setProperty("background-color", "#ffffff", "important");
			
			

// 			if (logoImg) {
// 				logoImg.src = "https://ptdutapersadiinstrumentasi.tokoweb.live/wp-content/uploads/2025/07/logo-white1.png";
// 				logoImg.srcset = ""; // Kosongkan agar tidak override
// 				logoImg.alt = "DUTA PERSADA INSTRUMENT (White)";
// 			}
			
// 			if (logoWrapper) {
// 			  logoWrapper.style.height = "40px";
// 			  logoWrapper.style.padding = "10px 0";
// 			  logoWrapper.style.display = "flex";
// 			  logoWrapper.style.alignItems = "center";
// 			  logoWrapper.style.justifyContent = "center";
// 			}
			
			if (iconElement) {
				const svgIcon = 
					`<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="grid-2" role="img" xmlns="http://www.w3.org/2000/svg"
						viewBox="0 0 512 512" class="svg-inline--fa fa-grid-2 fa-lg departments-menu-v2-icon" width="20" height="20">
						<path fill="currentColor"
							d="M224 80c0-26.5-21.5-48-48-48L80 32C53.5 32 32 53.5 32 80l0 96c0 26.5 21.5 48 48 48l96 0c26.5 0 48-21.5 48-48l0-96zm0 256c0-26.5-21.5-48-48-48l-96 0c-26.5 0-48 21.5-48 48l0 96c0 26.5 21.5 48 48 48l96 0c26.5 0 48-21.5 48-48l0-96zM288 80l0 96c0 26.5 21.5 48 48 48l96 0c26.5 0 48-21.5 48-48l0-96c0-26.5-21.5-48-48-48l-96 0c-26.5 0-48 21.5-48 48zM480 336c0-26.5-21.5-48-48-48l-96 0c-26.5 0-48 21.5-48 48l0 96c0 26.5 21.5 48 48 48l96 0c26.5 0 48-21.5 48-48l0-96z">
						</path>
					</svg>`;
				iconElement.outerHTML = svgIcon;
			}

			if (dropdownContainer && dropdownContainer.classList.contains('show-dropdown')) {
				dropdownContainer.classList.remove('show-dropdown');
			}

			let caretIcon = menuToggle?.querySelector('.caret-icon');
			if (!caretIcon) {
				caretIcon = document.createElement('i');
				caretIcon.className = 'fa-solid fa-chevron-down caret-icon';
				menuToggle?.appendChild(caretIcon);
			}

			if (dropdownContainer && menuToggle) {
				dropdownContainer.addEventListener('mouseenter', () => {
					dropdownContainer.classList.add('show-dropdown');
					caretIcon.classList.replace('fa-chevron-down', 'fa-chevron-up');
				});

				dropdownContainer.addEventListener('mouseleave', () => {
					dropdownContainer.classList.remove('show-dropdown');
					caretIcon.classList.replace('fa-chevron-up', 'fa-chevron-down');
				});
			}

// 			window.addEventListener('scroll', () => {
// 				const scrolled = window.scrollY > 10;

// 				if (scrolled) {
// 					mastHead?.classList.remove('site-header', 'stick-this', 'header-v1');
// 					stickyNavElement?.classList.add(
// 						'stick-this', 'stuck', 'animated', 'fadeInDown', 'faster', 'mobile-nav-adjust'
// 					);

// 					$(".secondary-nav", ".yamm").css("margin-left", "-5rem");
// 				} else {
// 					mastHead?.classList.add('site-header', 'stick-this', 'header-v1');
// 					stickyNavElement?.classList.remove(
// 						'stick-this', 'stuck', 'animated', 'fadeInDown', 'faster', 'mobile-nav-adjust'
// 					);
// 					$(".secondary-nav", ".yamm").css("margin-left", "0");
// 				}
// 			});
// 			
 			window.addEventListener('scroll', () => {
				const scrolled = window.scrollY > 10;
				
				if (scrolled) {
					mastHead?.classList.add('is-sticky-mobile');
					stickyNavElement?.classList.add(
						'stick-this', 'stuck', 'animated', 'fadeInDown', 'faster', 'mobile-nav-adjust'
					);
					$(".secondary-nav", ".yamm").css("margin-left", "-5rem");

					// Hapus inline style jika ada, dan set warna putih
					mastHead?.removeAttribute("style");
					$("header#masthead").css({
					  backgroundColor: '#ffffff',
					  background: '#ffffff'
					});
				} else {
					mastHead?.classList.remove('is-sticky-mobile');
					stickyNavElement?.classList.remove(
						'stick-this', 'stuck', 'animated', 'fadeInDown', 'faster', 'mobile-nav-adjust'
					);
					$(".secondary-nav", ".yamm").css("margin-left", "0");
				}

			});
		});
		</script>


		<style>
			/* === DROPDOWN MENU FIX === */
			.departments-menu-v2 .dropdown-menu li,
			.departments-menu-v2 .dropdown-menu li a {
				border-radius: 0 !important;
				overflow: hidden !important;
				background: transparent !important;
			}

			.departments-menu-v2 .dropdown-menu > li:first-child a {
				border-top-left-radius: 12px;
				border-top-right-radius: 12px;
				border-bottom-left-radius: 12px;
				border-bottom-right-radius: 12px;
			}

			.departments-menu-v2 .dropdown-menu > li:last-child a {
				border-bottom-left-radius: 10px;
				border-bottom-right-radius: 10px;
				border-bottom-left-radius: 12px;
				border-bottom-right-radius: 12px;
			}
			.departments-menu-v2 .dropdown {
				position: relative;
				overflow: visible !important;
				border-radius: 12px!important;
			}
			
			.departments-menu-v2 .departments-menu-v2-title {
				border-radius: 12px;
				background-color: #fe3406;
				color: #fff;
				padding: 10px 12px;
				display: inline-flex;
				align-items: center;
				gap: 8px;
				transition: all 0.3s ease;
				border: 1px solid #fd4217;
				font-size: 16px;
			    font-weight: 700;
			  	text-transform: capitalize;
			  	letter-spacing: 0.05em;
				width: 280px;
				height: 50px;
/* 				line-height: 1.2; */
			}
			.departments-menu-v2 .departments-menu-v2-title:hover {
				background-color: #fb6846;
			}

			.departments-menu-v2 .dropdown-menu {
				border-radius: 10px !important;
				overflow: hidden !important;
				box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
				min-width: 500px;
				z-index: 9999;
			}

			.departments-menu-v2 .dropdown:not(.show-dropdown) .dropdown-menu {
				display: none !important;
			}

			.departments-menu-v2-title {
				display: flex;
				align-items: center;
				justify-content: space-between;
				cursor: pointer;
			}

			.departments-menu-v2-title .caret-icon {
				margin-left: 8px;
				font-size: 14px;
				transition: transform 0.2s ease;
			}

			/* === STICKY ADJUSTMENT === */
			.stick-this .departments-menu-v2 .dropdown-menu {
				position: absolute !important;
				top: 100%;
				left: 0;
				display: block !important;
				visibility: visible !important;
				opacity: 1 !important;
			}

			.stick-this .departments-menu-v2 .dropdown {
				position: relative;
				overflow: visible !important;
				z-index: 999;
			}
			

			.mobile-nav-adjust .departments-menu-v2,
			.mobile-nav-adjust .secondary-nav-menu {
				max-width: auto;
				margin-left: 3rem;
				margin-right: 3rem;
				padding: 0.5rem;
				width: 100%;
				box-sizing: border-box;
			}

			.mobile-nav-adjust {
				position: fixed;
				top: 0;
				left: 0;
				right: 0;
				margin-left: -1rem;
				width: 100vw;
				z-index: 999;
				background: rgba(255, 255, 255, 0.9);
				box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
				backdrop-filter: blur(5px);
			}
			.departments-menu-v2 .dropdown .dropdown-menu {
				display: none !important;
			}

			.departments-menu-v2 .dropdown.show-dropdown .dropdown-menu {
				display: block !important;
			}
			.brand img[src*="woocommerce-placeholder"] {
				display: none;
			}
			.secondary-nav.yamm {
/* 				margin-left: 7rem; */
				font-size: 1rem;
				padding: 1px 145px;
			    font-weight: 600;
				transition: padding 0.3s ease;
			}
/* 			@media (max-width: 768px) {
				  .header-logo img {
					max-height: 100px;
					width: 450px!important;
				 }
				.mobile-nav-adjust .departments-menu-v2,
				.mobile-nav-adjust .secondary-nav-menu {
					padding-left: 1rem;
					padding-right: 1rem;
				}

				.mobile-nav-adjust {
					margin-left: 0;
					width: 100%;
					padding-left: 1rem;
					padding-right: 1rem;
				}
			} */
			
/* 			@media (max-width: 768px) {
			  html body header#masthead.is-sticky-mobile {
				background-color: #ffffff !important;
				background: #ffffff !important;
				box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3) !important;
				color: #000 !important;
				position: fixed !important;
				top: 0 !important;
				width: 100% !important;
				z-index: 9999 !important;
			  }
			}
			
			@media (max-width: 768px) {
			  html body header#masthead.is-sticky-mobile .electro-navigation {
				background: #ffffff !important;
				z-index: 9999 !important;
			  }
			} */
		</style>
<?php
});

add_action('woocommerce_single_product_summary', 'tampilkan_brand_image_from_attribute', 5);
function tampilkan_brand_image_from_attribute() {
    $brands = wp_get_post_terms(get_the_ID(), 'pa_brands');

    if (!empty($brands) && !is_wp_error($brands)) {
        foreach ($brands as $brand) {
            $image_id = get_term_meta($brand->term_id, 'product_attribute_cover_image', true);

            if ($image_id) {
                $image_url = wp_get_attachment_image_url($image_id, 'full');

                echo '<div class="brand" style="margin-bottom: 20px;">';
                echo '<a href="' . esc_url(get_term_link($brand)) . '">';
                echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($brand->name) . '" style="width: 100%; max-width: 400px; height: auto;">';
                echo '</a>';
                echo '</div>';
            } else {
                echo '<!-- Gagal ambil image_id dari key product_attribute_cover_image -->';
            }
        }
    }
}

function custom_scroll_to_top_button() {
    ?>
    <!-- HTML + SVG button -->
    <div class="scroll-to-top" aria-label="Scroll to Top">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="white" viewBox="0 0 24 24">
            <path d="M12 8.5l4 4-1.4 1.4L12 11.3l-2.6 2.6L8 12.5l4-4zm0-5l4 4-1.4 1.4L12 6.3 9.4 8.9 8 7.5l4-4z"/>
        </svg>
    </div>

    <style>
	.back-to-top-wrapper {
		display: none !important;
	}
    .scroll-to-top {
        position: fixed;
        bottom: 80px;
        right: -15px;
        background-color: #ff3606;
        border-radius: 10px;
        width: 70px;
        height: 50px;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        z-index: 9999;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        transition: opacity 0.3s ease;
        opacity: 0;
        visibility: hidden;
    }
    .scroll-to-top.show {
        opacity: 1;
        visibility: visible;
    }
    .scroll-to-top svg {
        width: 45px;
        height: 45px;
		margin-left: -3px;
		margin-top: 5px;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const button = document.querySelector('.scroll-to-top');
        window.addEventListener('scroll', function () {
            if (window.scrollY > 300) {
                button.classList.add('show');
            } else {
                button.classList.remove('show');
            }
        });

        button.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'custom_scroll_to_top_button');
