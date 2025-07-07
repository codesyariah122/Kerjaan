<?php
function fetch_omdb_data_and_set_fields_only($post_id)
{
    if (get_post_type($post_id) !== 'film_review') return;

    remove_action('save_post', 'fetch_omdb_data_and_set_fields_only');

    $api_key = '346043a7';
    $title = get_the_title($post_id);
    $response = wp_remote_get("http://www.omdbapi.com/?t=" . urlencode($title) . "&apikey=$api_key");

    if (!is_wp_error($response)) {
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($body['Response'] === 'True') {
            // Update custom fields
            update_field('sinopsis', $body['Plot'], $post_id);
            update_field('rating', $body['imdbRating'], $post_id);
            update_field('genre', $body['Genre'], $post_id);
            update_field('release_date', $body['Released'], $post_id);
            update_field('director', $body['Director'], $post_id);
            update_field('artists', esc_html($body['Actors']), $post_id);

            // Generate content with additional fields
            ob_start();
?>
            <h2>Film Detail</h2>
            <table style="width:100%; border: 1px solid #ddd; border-collapse: collapse;">
                <tr>
                    <th style="padding: 8px; text-align: left; background-color: #f2f2f2;">Sinopsis</th>
                    <td style="padding: 8px;"><?= esc_html($body['Plot']); ?></td>
                </tr>
                <tr>
                    <th style="padding: 8px; text-align: left; background-color: #f2f2f2;">Rating IMDb</th>
                    <td style="padding: 8px;"><?= esc_html($body['imdbRating']); ?></td>
                </tr>
                <tr>
                    <th style="padding: 8px; text-align: left; background-color: #f2f2f2;">Genre</th>
                    <td style="padding: 8px;"><?= esc_html($body['Genre']); ?></td>
                </tr>
                <tr>
                    <th style="padding: 8px; text-align: left; background-color: #f2f2f2;">Dirilis</th>
                    <td style="padding: 8px;"><?= esc_html($body['Released']); ?></td>
                </tr>
                <tr>
                    <th style="padding: 8px; text-align: left; background-color: #f2f2f2;">Aktor</th>
                    <td style="padding: 8px;"><?= esc_html($body['Actors']); ?></td>
                </tr>
                <tr>
                    <th style="padding: 8px; text-align: left; background-color: #f2f2f2;">Director</th>
                    <td style="padding: 8px;"><?= esc_html($body['Director']); ?></td>
                </tr>
                <?php if (isset($body['Ratings'])): ?>
                    <tr>
                        <th style="padding: 8px; text-align: left; background-color: #f2f2f2;">Rotten Tomatoes Rating</th>
                        <td style="padding: 8px;">
                            <?php
                            // Find Rotten Tomatoes rating
                            $rotten_rating = null;
                            foreach ($body['Ratings'] as $rating) {
                                if ($rating['Source'] === 'Rotten Tomatoes') {
                                    $rotten_rating = $rating['Value'];
                                    break;
                                }
                            }
                            echo esc_html($rotten_rating ? $rotten_rating : 'N/A');
                            ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>

<?php
            $content = ob_get_clean();

            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $content,
            ));

            // Handle Poster Image
            $poster_url = $body['Poster'];
            if ($poster_url && $poster_url !== 'N/A') {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $tmp = download_url($poster_url);
                if (!is_wp_error($tmp)) {
                    $file_array = array(
                        'name'     => basename($poster_url),
                        'tmp_name' => $tmp
                    );

                    $id = media_handle_sideload($file_array, $post_id);
                    if (!is_wp_error($id)) {
                        update_field('poster_url', $id, $post_id);
                        set_post_thumbnail($post_id, $id);
                    } else {
                        error_log("Gagal handle_sideload: " . $id->get_error_message());
                    }
                } else {
                    error_log("Gagal download poster: " . $tmp->get_error_message());
                }
            } else {
                error_log("Poster URL kosong atau N/A dari OMDB.");
            }
        }
    }

    add_action('save_post', 'fetch_omdb_data_and_set_fields_only');
}
add_action('save_post', 'fetch_omdb_data_and_set_fields_only');
