<?php
function fetch_lastfm_data_and_set_music_review_fields($post_id)
{
    if (get_post_type($post_id) !== 'music_review') return;

    remove_action('save_post', 'fetch_lastfm_data_and_set_music_review_fields');

    $title = get_the_title($post_id);
    $expected_artist = get_field('artist_name', $post_id); // ACF field

    if (empty($title) || empty($expected_artist)) return;

    $api_key = 'f2c3fb1beac6804c2e5a834edcd188a7';
    $artist = urlencode($expected_artist);
    $track = urlencode($title);

    $url = "http://ws.audioscrobbler.com/2.0/?method=track.getInfo&api_key={$api_key}&artist={$artist}&track={$track}&format=json";

    error_log("Last.fm API URL: " . $url);

    $response = wp_remote_get($url);

    if (!is_wp_error($response)) {
        $body = json_decode(wp_remote_retrieve_body($response), true);

        error_log("Last.fm API Response: " . print_r($body, true));

        if (isset($body['track'])) {
            $track_data = $body['track'];

            $song_title = $track_data['name'] ?? 'Unknown';
            $artist = $track_data['artist']['name'] ?? $expected_artist;
            $album_title = $track_data['album']['title'] ?? '';
            $cover_url = $track_data['album']['image'][2]['#text'] ?? ''; // Medium image

            // Genre (tags)
            $genre = '';
            if (!empty($track_data['toptags']['tag'])) {
                $genre = $track_data['toptags']['tag'][0]['name'];
            }

            // Last.fm doesn't provide exact release date in track.getInfo
            $release = '';

            // Update ACF fields
            update_field('artist_name', $artist, $post_id);
            update_field('album_title', $album_title, $post_id);
            update_field('genre', $genre, $post_id);
            update_field('release_date', $release, $post_id);
            update_field('rating', '', $post_id);

            if ($cover_url) {
                $image_id = media_sideload_image($cover_url, $post_id, '', 'id');
                if (!is_wp_error($image_id)) {
                    set_post_thumbnail($post_id, $image_id);
                    update_field('cover_image', $post_id, $image_id);
                }
            }

            // Generate YouTube embed link
            $search_query = urlencode("{$artist} {$song_title}");
            $youtube_embed = "https://www.youtube.com/embed?listType=search&list={$search_query}";
            update_field('spotifyyoutube_embed', $youtube_embed, $post_id);

            ob_start();
?>
            <h2>Review Otomatis</h2>
            <p><strong>Judul Lagu:</strong> <?= esc_html($song_title); ?></p>
            <p><strong>Artis:</strong> <?= esc_html($artist); ?></p>
            <p><strong>Album:</strong> <?= esc_html($album_title); ?></p>
            <p><strong>Tanggal Rilis:</strong> <?= esc_html($release ?: 'Tidak tersedia'); ?></p>
            <?php if ($genre): ?>
                <p><strong>Genre:</strong> <?= esc_html($genre); ?></p>
            <?php endif; ?>
            <p>Informasi ini diambil otomatis dari Last.fm berdasarkan judul lagu dan artis. Silakan lengkapi review-nya secara manual jika dibutuhkan.</p>
<?php
            $review_content = ob_get_clean();

            update_field('review_content', $review_content, $post_id);

            wp_update_post([
                'ID' => $post_id,
                'post_content' => $review_content,
            ]);
        } else {
            error_log("Track data tidak ditemukan di Last.fm untuk artist={$artist}, track={$track}");
        }
    } else {
        error_log("Request ke Last.fm gagal: " . $response->get_error_message());
    }

    add_action('save_post', 'fetch_lastfm_data_and_set_music_review_fields');
}
add_action('save_post', 'fetch_lastfm_data_and_set_music_review_fields');
