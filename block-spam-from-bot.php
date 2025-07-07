/**
* Jet form validate with akismet & jetpack
*@author Puji Ermanto<pujiermanto@gmail.com>
**/
if ( function_exists( 'akismet_http_post' ) ) {
    error_log('Akismet aktif dan tersedia');
} else {
    error_log('Akismet tidak tersedia atau tidak aktif');
}

// function check_akismet_spam_in_jetform( $response, $handler ) {
//     $api_key = '1e25bbf77376'; // Ganti dengan API Key Akismet kamu
//     $site_url = get_option('https://aionindonesia.com/test-drive/'); // Ambil URL website
    
//     // Ambil data dari form submission
//     $form_data = $handler->request;

//     $comment_data = array(
//         'blog'                 => $site_url,
//         'user_ip'              => $_SERVER['REMOTE_ADDR'],
//         'user_agent'           => $_SERVER['HTTP_USER_AGENT'],
//         'referrer'             => $_SERVER['HTTP_REFERER'],
//         'comment_type'         => 'contact-form',
//         'comment_author'       => trim(($form_data['honorific'] ?? '') . ' ' . ($form_data['first_name'] ?? '') . ' ' . ($form_data['last_name'] ?? '')), 
//         'comment_author_email' => $form_data['email'] ?? '',
//         'comment_content'      => implode(' | ', [
//             'Car Model: ' . ($form_data['car_model'] ?? ''),
//             'Dealership: ' . ($form_data['dealership'] ?? ''),
//             'Address: ' . ($form_data['dealership_address'] ?? ''),
//             'Date: ' . ($form_data['date'] ?? ''),
//             'Time: ' . ($form_data['time'] ?? ''),
//             'Phone: ' . ($form_data['country_code'] ?? '') . ' ' . ($form_data['phone_number'] ?? ''),
//             'Message: ' . ($form_data['message'] ?? ''),
//             'Subscribe: ' . ($form_data['subscribe_value'] ?? 'No'),
//             'Policy: ' . ($form_data['policy_value'] ?? 'No'),
//         ]),
//     );

//     // Kirim data ke Akismet
//     $akismet_url = "https://$api_key.rest.akismet.com/1.1/comment-check";
//     $response = wp_remote_post($akismet_url, array(
//         'body'    => $comment_data,
//         'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
//     ));

//     if (!is_wp_error($response)) {
//         $body = wp_remote_retrieve_body($response);
//         if ($body == 'true') {
//             wp_die(__('Pesan terdeteksi sebagai spam!'));
//         }
//     }
    
//     return $response;
// }
// add_action('jet-form-builder/form-handler/after-check', 'check_akismet_spam_in_jetform', 10, 2);

// add_action('wp_loaded', function() {
//     if (!class_exists('Akismet')) {
//         error_log('Akismet class tidak ditemukan!');
//     } else {
//         error_log('Akismet tersedia.');
//         if (!method_exists('Akismet', 'comment_check')) {
//             error_log('Metode comment_check tidak ditemukan di Akismet.');
//         } else {
//             error_log('Metode comment_check tersedia.');
//         }
//     }
// });
// add_action('wp_loaded', function() {
//     if (!class_exists('Akismet')) {
//         error_log('Akismet class tidak ditemukan!');
//     } else {
//         error_log('Akismet tersedia.');
//         if (!method_exists('Akismet', 'check')) { // Ganti comment_check jadi check_comment
//             error_log('Metode check tidak ditemukan di Akismet.');
//         } else {
//             error_log('Metode check tersedia.');
//         }
//     }
// });

// add_action('jet-form-builder/custom-action/jetform_test_drive_hook', function ($request) {
//     try {
//         error_log('Hook jetform_test_drive_hook dipanggil.');

//         // Pastikan Akismet tersedia sebelum lanjut
//         // if (!class_exists('Akismet') || !method_exists('Akismet', 'check')) {
//         //     error_log('Akismet tidak ditemukan atau method check tidak tersedia.');
//         //     wp_send_json_error(['message' => 'Layanan spam filter tidak tersedia.'], 400);
//         //     wp_die();
//         // }

//         // Pastikan request adalah array
//         $data = is_array($request) ? $request : [];
//         error_log('Data Form: ' . print_r($data, true));

//         // Periksa apakah form ini dianggap spam oleh Akismet
//         $is_spam = akismet_check_request($data);

//         error_log('Response dari Akismet: ' . print_r($is_spam, true));

//         if ($is_spam) {
//             error_log('Spam terdeteksi di Test Drive Form!');
//         }

//         error_log('Form valid, lanjut submit.');

//     } catch (Exception $e) {
//         error_log('Error di custom hook: ' . $e->getMessage());
//     }
// }, 10, 1);

// function akismet_check_request($data) {
//     if (!class_exists('Akismet') || !method_exists('Akismet', 'check')) {
//         error_log('Akismet tidak tersedia.');
//         return false;
//     }

//     $akismet_key = Akismet::get_api_key();
//     if (!$akismet_key) {
//         error_log('Akismet API key tidak ditemukan.');
//         return false;
//     }

//     $comment_data = [
//         'blog'           => get_option('home'),
//         'user_ip'        => $_SERVER['REMOTE_ADDR'],
//         'user_agent'     => $_SERVER['HTTP_USER_AGENT'],
//         'referrer'       => $_SERVER['HTTP_REFERER'],
//         'comment_type'   => 'contact-form',
//         'comment_author' => $data['first_name'] . ' ' . $data['last_name'],
//         'comment_content' => 'Email: ' . $data['email']
//     ];

//     $is_spam = Akismet::check($comment_data);
//     error_log('Akismet response: ' . ($is_spam ? 'SPAM' : 'Bukan SPAM'));

//     return (bool) $is_spam;
// }



// add_action( 'jet-form-builder/form-handler/register', function ( $form_handler ) {
//     $form_handler->add_validator( 'custom_spam_check', function ( $request, $handler ) {

//         $form_id = $request->get_param( 'form_id' ); // Ambil Form ID otomatis
//         $allowed_form_ids = [4109]; // Ganti dengan Form ID formulir kamu

//         if (!in_array($form_id, $allowed_form_ids)) {
//             return true; // Lewati validasi jika bukan form yang ditargetkan
//         }

//         $blocked_keywords = ['graph.org', 'bitcoin', 'transfer', 'notification', 'message'];
//         $blocked_email_domains = [''];
//         $blocked_name_patterns = '/^[a-z0-9]{5,7}$/';

//         $fields = $request->get_body_params();

//         if (!empty($fields['time']) && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $fields['time'])) {
//             return new WP_Error( 'spam_detected', __( 'Invalid time format detected! Submission blocked.' ) );
//         }

//          if (!isset($fields['_wpnonce']) || !wp_verify_nonce($fields['_wpnonce'], 'jet-form-nonce')) {
//             return new WP_Error( 'spam_detected', __( 'Invalid form submission detected! Submission blocked.' ) );
//         }


//         foreach (['time', 'message'] as $field) {
//             foreach ($blocked_keywords as $keyword) {
//                 if (!empty($fields[$field]) && stripos($fields[$field], $keyword) !== false) {
//                     return new WP_Error( 'spam_detected', __( 'Spam detected! Submission blocked.' ) );
//                 }
//             }
//         }

//         if (preg_match($blocked_name_patterns, $fields['first_name']) || preg_match($blocked_name_patterns, $fields['last_name'])) {
//             return new WP_Error( 'spam_detected', __( 'Invalid name format detected! Submission blocked.' ) );
//         }

//         if (!empty($fields['email'])) {
//             $email_domain = substr(strrchr($fields['email'], "@"), 1);
//             if (in_array($email_domain, $blocked_email_domains)) {
//                 return new WP_Error( 'spam_detected', __( 'Suspicious email detected! Submission blocked.' ) );
//             }
//         }

//         if (!empty($fields['phone_number']) && !preg_match('/^\d{10,15}$/', $fields['phone_number'])) {
//             return new WP_Error( 'spam_detected', __( 'Invalid phone number detected! Submission blocked.' ) );
//         }

//         foreach (['dealership', 'dealership_address'] as $required_field) {
//             if (empty($fields[$required_field])) {
//                 return new WP_Error( 'spam_detected', __( 'Required fields are empty! Submission blocked.' ) );
//             }
//         }

//         return true;
//     });
// });

function akismet_check_request($data) {
    if (!class_exists('Akismet') || !method_exists('Akismet', 'comment_check')) {
        error_log('Akismet tidak tersedia.');
        return false;
    }

    $akismet_key = Akismet::get_api_key();
    if (!$akismet_key) {
        error_log('Akismet API key tidak ditemukan.');
        return false;
    }

    $akismet_data = [
        'blog'                 => get_option('home'),
        'user_ip'              => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent'           => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'comment_type'         => 'contact-form',
        'comment_author'       => sanitize_text_field(($data['honorific'] ?? '') . ' ' . $data['first_name'] . ' ' . $data['last_name']),
        'comment_author_email' => sanitize_email($data['email']),
        'comment_content'      => sanitize_textarea_field($data['message'] ?? ''),
        'referrer'             => sanitize_text_field($data['__refer'] ?? ''),
    ];

    $is_spam = Akismet::comment_check($akismet_data);
    return (bool) $is_spam;
}

add_action( 'jet-form-builder/form-handler/register', function ( $form_handler ) {
    $form_handler->add_validator( 'custom_spam_check', function ( $request, $handler ) {

        $form_id = $request->get_param( 'form_id' );
        $allowed_form_ids = [4109]; // Sesuaikan dengan Form ID
        
        if (!in_array($form_id, $allowed_form_ids)) {
            return true; // Lewati validasi jika bukan form target
        }

        $fields = $request->get_body_params();

        // **Cek Nonce Lebih Awal**
        if (!isset($fields['_wpnonce']) || !wp_verify_nonce($fields['_wpnonce'], 'jet-form-nonce')) {
            wp_die( 'Invalid form submission detected! Submission blocked.', 'Spam Detected', ['response' => 403] );
        }

        // **Cek Format Time Harus HH:MM atau HH:MM:SS**
        if (!empty($fields['time']) && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $fields['time'])) {
            wp_die( 'Invalid time format detected! Submission blocked.', 'Spam Detected', ['response' => 403] );
        }

        // **Filter Kata-Kata Spam**
        $blocked_keywords = ['graph.org', 'bitcoin', 'transfer', 'notification', 'message'];
        foreach (['time', 'message'] as $field) {
            foreach ($blocked_keywords as $keyword) {
                if (!empty($fields[$field]) && stripos($fields[$field], $keyword) !== false) {
                    wp_die( 'Spam detected! Submission blocked.', 'Spam Detected', ['response' => 403] );
                }
            }
        }

        // **Filter Format Nama yang Mencurigakan**
        $blocked_name_patterns = '/^[a-z0-9]{5,7}$/';
        if (!empty($fields['first_name']) && preg_match($blocked_name_patterns, $fields['first_name'])) {
            wp_die( 'Invalid name format detected! Submission blocked.', 'Spam Detected', ['response' => 403] );
        }
        if (!empty($fields['last_name']) && preg_match($blocked_name_patterns, $fields['last_name'])) {
            wp_die( 'Invalid name format detected! Submission blocked.', 'Spam Detected', ['response' => 403] );
        }

        // **Filter Email Domain Mencurigakan**
        $blocked_email_domains = ['mailinator.com', 'tempmail.com', 'example.com']; // Tambahkan domain spam
        if (!empty($fields['email'])) {
            $email_domain = substr(strrchr($fields['email'], "@"), 1);
            if (in_array($email_domain, $blocked_email_domains)) {
                wp_die( 'Suspicious email detected! Submission blocked.', 'Spam Detected', ['response' => 403] );
            }
        }

        // **Filter Nomor Telepon yang Tidak Valid**
        if (!empty($fields['phone_number']) && !preg_match('/^\d{10,15}$/', $fields['phone_number'])) {
            wp_die( 'Invalid phone number detected! Submission blocked.', 'Spam Detected', ['response' => 403] );
        }

        // **Cek Field yang Harus Diisi**
        $required_fields = ['dealership', 'dealership_address'];
        foreach ($required_fields as $required_field) {
            if (empty($fields[$required_field])) {
                wp_die( 'Required fields are empty! Submission blocked.', 'Spam Detected', ['response' => 403] );
            }
        }

        return true;
    });
});

add_action('jet-form-builder/custom-action/jetform_test_drive_hook', function ($request) {
    error_log('Hook jetform_test_drive_hook dipanggil.');

    // Ambil data form
    $data = is_array($request) ? $request : [];
    error_log('Data Form: ' . print_r($data, true));

    // Cek apakah request berasal dari website
    if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], get_home_url()) === false) {
        error_log('Spam bot terdeteksi: Tidak ada referer atau berasal dari luar situs.');
        wp_send_json_error(['message' => 'Spam terdeteksi!'], 403);
        wp_die();
    }

    // Cek honeypot field
    if (!empty($data['honey_field'])) {
        error_log('Honeypot terisi, bot terdeteksi!');
        wp_send_json_error(['message' => 'Spam terdeteksi!'], 403);
        wp_die();
    }

    // Cek apakah pengisian terlalu cepat (< 5 detik)
    if (!empty($data['submission_time']) && (time() - intval($data['submission_time']) < 5)) {
        error_log('Form dikirim terlalu cepat, kemungkinan bot!');
        wp_send_json_error(['message' => 'Spam terdeteksi!'], 403);
        wp_die();
    }

    // Cek apakah data lengkap
    if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
        error_log('Data tidak lengkap.');
        wp_send_json_error(['message' => 'Harap lengkapi semua kolom yang diperlukan.'], 400);
        wp_die();
    }

    // Gunakan Akismet untuk validasi spam
    if (akismet_check_request($data)) {
        error_log('Spam terdeteksi di Test Drive Form!');
        wp_send_json_error(['message' => 'Pesan terdeteksi sebagai spam!'], 403);
        wp_die();
    }

    // Jika lolos semua validasi, arahkan pengguna ke halaman sukses
    error_log('Form valid, lanjut submit.');
}, 10, 1);
