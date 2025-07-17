<?php

/**
 * Plugin Name: Custom Footer Manti
 * Description: Menghapus footer bawaan theme Electro dan mengganti dengan versi custom mirip desain Manti.
 * Version: 1.0
 * Author: Tokoweb Creative
 */

// Hapus semua elemen footer bawaan Electro
add_action('init', function () {
    remove_all_actions('electro_footer_v2');
});

// Sisipkan footer baru di wp_footer
add_action('wp_footer', 'custom_footer_manti', 100);
function custom_footer_manti()
{
?>
    <style>
        #custom-footer-manti {
            background-color: #ffffff;
            color: #333;
            padding: 60px 0;
            font-family: Arial, sans-serif;
            position: relative;
        }

        #custom-footer-manti .footer-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        #custom-footer-manti h4 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #FF4C60;
        }

        #custom-footer-manti p {
            font-size: 14px;
            margin-bottom: 20px;
        }

        #custom-footer-manti ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        #custom-footer-manti ul li {
            margin-bottom: 10px;
        }

        #custom-footer-manti ul li a {
            color: #333;
            text-decoration: none;
        }

        #custom-footer-manti ul li a:hover {
            color: #FF4C60;
        }

        #custom-footer-manti .newsletter {
            grid-column: span 3;
            text-align: center;
            margin-top: 40px;
        }

        #custom-footer-manti .newsletter input[type="email"] {
            padding: 10px;
            width: 70%;
            max-width: 300px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-right: 10px;
        }

        #custom-footer-manti .newsletter button {
            padding: 10px 15px;
            border: none;
            background-color: #FF4C60;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }

        #custom-footer-manti .socials {
            margin-top: 20px;
        }

        #custom-footer-manti .socials a {
            margin: 0 10px;
            color: #FF4C60;
            font-size: 24px;
        }

        @media(max-width: 768px) {
            #custom-footer-manti .footer-wrapper {
                grid-template-columns: 1fr;
            }

            #custom-footer-manti .newsletter {
                grid-column: span 1;
            }
        }
    </style>
    <div id="custom-footer-manti">
        <div class="footer-wrapper">
            <div>
                <h4>At Manti</h4>
                <p>We offer the latest electronics at unbeatable prices.</p>
            </div>
            <div>
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">FAQs</a></li>
                </ul>
            </div>
            <div>
                <h4>Contact Us</h4>
                <p>Phone: 943783924</p>
                <p>Email: support@manti.com</p>
                <p>Address: 450 Young Road, New York, USA</p>
            </div>
        </div>
        <div class="newsletter">
            <h4>Subscribe to our Newsletter and Enjoy 50% Discount Offer</h4>
            <form>
                <input type="email" placeholder="Enter your email" />
                <button type="submit">Subscribe</button>
            </form>
            <div class="socials">
                <a href="#">🌐</a>
                <a href="#">🐦</a>
                <a href="#">📘</a>
            </div>
        </div>
    </div>
<?php
}
