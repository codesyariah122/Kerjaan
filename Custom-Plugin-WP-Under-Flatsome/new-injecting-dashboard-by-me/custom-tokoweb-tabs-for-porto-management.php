<?php
function tokoweb_tabs_shortcode()
{
    ob_start(); ?>

    <style>
        .tokoweb-tabs {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 40px 0;
            flex-wrap: wrap;
        }

        .tokoweb-tab {
            position: relative;
            width: 150px;
            height: 100px;
            border-radius: 20px;
            overflow: hidden;
            cursor: pointer;
            text-align: center;
            transition: transform 0.3s ease;
            text-decoration: none;
        }

        .tokoweb-tab:hover,
        .tokoweb-tab.active {
            transform: scale(1.05);
            outline: 3px solid #fff;
        }

        .tokoweb-tab img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.4);
            position: absolute;
            top: 0;
            left: 0;
        }

        .tokoweb-tab span {
            position: relative;
            color: white;
            font-size: 24px;
            font-weight: bold;
            z-index: 2;
            line-height: 100px;
            display: block;
        }

        .tokoweb-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            /* Menampilkan 4 kolom */
            gap: 20px;
            /* Jarak antar card */
            margin: 40px auto;
            padding: 0 20px;
            min-height: 100%;
        }

        .tokoweb-post-item {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            background-color: #fff;
            align-items: flex-start;
        }

        .tokoweb-post-item img {
            width: 120px;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
        }

        .tokoweb-post-content {
            flex: 1;
        }

        .tokoweb-post-content h3 {
            margin: 0 0 10px;
        }

        .tokoweb-post-content p {
            margin: 0;
            color: #555;
        }

        .tokoweb-card {
            background-color: #1c1c1c;
            color: #fff;
            height: 580px;
            min-height: 650px;
            /* Menambahkan min-height untuk memastikan tinggi card konsisten */
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.6);
            font-family: sans-serif;
        }

        .tokoweb-card-body {
            padding: 10px;
            background-color: #1c1c1c;
            flex-grow: 1;
            /* Membuat body card fleksibel agar selalu mengisi ruang kosong */
        }

        .tokoweb-card-img {
            width: 100%;
            height: 430px !important;
            object-fit: cover;
            display: block;
            /* Memastikan gambar berperilaku seperti elemen block */
            background-color: #000;
            /* Menambahkan latar belakang untuk gambar kosong */
        }

        .tokoweb-rating {
            font-size: 14px;
            color: #ffd700;
            margin-bottom: 5px;
        }

        .tokoweb-rating .star {
            font-size: 16px;
        }

        .tokoweb-title {
            font-size: 15px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .tokoweb-title a {
            color: #ffffff;
            text-decoration: none;
        }

        .tokoweb-buttons {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .watchlist-btn,
        .trailer-btn {
            display: inline-block;
            padding: 6px;
            border-radius: 8px;
            font-size: 13px;
            text-align: center;
            text-decoration: none;
        }

        .watchlist-btn {
            background-color: #1f1f1f;
            color: #0f9fff;
            border: 1px solid #0f9fff;
        }

        .trailer-btn {
            background-color: #333;
            color: #fff;
            border: 1px solid #555;
        }

        .tokoweb-genre-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            justify-content: center;
        }

        .genre-tab {
            padding: 6px 12px;
            background-color: #eee;
            border-radius: 20px;
            cursor: pointer;
            transition: 0.3s;
            font-size: 14px;
        }

        .genre-tab.active,
        .genre-tab:hover {
            background-color: #0f9fff;
            color: #fff;
        }

        .tokoweb-artist-name,
        .tokoweb-artists {
            font-size: 13px;
            margin-bottom: 5px;
            color: #ccc;
        }

        #genre-tabs {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 20px;
        }

        #tokoweb-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            /* 4 items per row */
            gap: 20px;
            margin-top: 20px;
        }

        #genre-posts {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .load-more-btn {
            background-color: #0f9fff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            margin-top: 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .load-more-btn:hover {
            background-color: #0c7bb8;
        }

        .load-more-wrapper {
            grid-column: 1 / -1;
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .tokoweb-tab {
                width: 100px;
                height: 80px;
            }

            .tokoweb-tab span {
                font-size: 18px;
                line-height: 80px;
            }

            .tokoweb-card {
                width: 100%;
                max-width: 100%;
            }

            .tokoweb-tabs {
                justify-content: center;
                gap: 10px;
            }

            .tokoweb-content {
                grid-template-columns: repeat(2, 1fr);
                /* 2 card per baris untuk layar kecil */
            }

            #tokoweb-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .tokoweb-tab {
                width: 90px;
                height: 70px;
            }

            .tokoweb-tab span {
                font-size: 14px;
                line-height: 70px;
            }

            .tokoweb-card {
                width: 100%;
                height: 500px;
                min-height: 650px;
            }

            .tokoweb-tabs {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }

            .tokoweb-content {
                grid-template-columns: 1fr;
                /* 1 card per baris untuk layar ekstra kecil */
            }

            #tokoweb-content {
                display: grid;
                grid-template-columns: repeat(1, 1fr);
                gap: 20px;
                margin-top: 20px;
            }
        }
    </style>

    <div class="tokoweb-tabs-wrapper">
        <div class="tokoweb-tabs">
            <div class="tokoweb-tab active" data-type="film_review">
                <img src="https://portomanagement.demo-tokoweb.my.id/wp-content/uploads/2025/04/filmandclapboard-58dc894e7697732409578100.jpg" alt="Film">
                <span>FILM</span>
            </div>
            <div class="tokoweb-tab" data-type="music_review">
                <img src="https://portomanagement.demo-tokoweb.my.id/wp-content/uploads/2025/04/63182cdbe6326.jpg" alt="Musik">
                <span>MUSIK</span>
            </div>
            <div class="tokoweb-tab" data-type="seni_review">
                <img src="https://portomanagement.demo-tokoweb.my.id/wp-content/uploads/2025/04/warisan-budaya-indonesia-yang-diakui-oleh-unesco.jpg" alt="Seni">
                <span>SENI</span>
            </div>
        </div>


    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tokoweb-tab');
            const content = document.getElementById('tokoweb-content');
            let tabActiveName = 'film_review',
                currentPage = 1;

            function loadPosts(postType, page = 1) {
                content.innerHTML = '<p>Loading konten...</p>';
                fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=load_tokoweb_tab_content&post_type=' + postType + '&page=' + page)
                    .then(response => response.text())
                    .then(data => {
                        if (page === 1) {
                            content.innerHTML = data;
                        } else {
                            content.innerHTML += data;
                        }
                        currentPage = page;
                        addLoadMoreButton(postType);
                    });
            }

            function addLoadMoreButton(postType) {
                let wrapper = document.createElement('div');
                wrapper.classList.add('load-more-wrapper');

                let loadMoreBtn = document.createElement('button');
                loadMoreBtn.textContent = 'Load More';
                loadMoreBtn.classList.add('load-more-btn');
                loadMoreBtn.addEventListener('click', () => {
                    loadPosts(postType, currentPage + 1);
                });

                wrapper.appendChild(loadMoreBtn);
                content.appendChild(wrapper);
            }

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    console.log("Click tab")
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    const type = tab.getAttribute('data-type');
                    tabActiveName = type
                    loadPosts(type);
                    loadGenres(type)
                });
            });


            loadPosts(tabActiveName); // load default content for film review

            function loadGenres(postType) {
                fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=load_genre_tabs&post_type=${postType}`)
                    .then(res => res.text())
                    .then(data => {
                        const genreTabs = document.getElementById('genre-tabs');
                        if (genreTabs) {
                            genreTabs.innerHTML = data;

                            document.querySelectorAll('.genre-tab').forEach(genreTab => {
                                genreTab.addEventListener('click', () => {
                                    document.querySelectorAll('.genre-tab').forEach(g => g.classList.remove('active'));
                                    genreTab.classList.add('active');
                                    const genre = genreTab.getAttribute('data-genre');
                                    loadGenrePosts(postType, genre);
                                });
                            });
                        } else {
                            console.error('Element #genre-tabs tidak ditemukan');
                        }
                    });
            }


            function loadGenrePosts(postType, genre) {
                const genrePosts = document.getElementById('tokoweb-content');
                if (genrePosts) {
                    genrePosts.innerHTML = '<p>Loading konten...</p>';
                    fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=load_tokoweb_tab_content&post_type=${postType}&genre=${genre}`)
                        .then(res => res.text())
                        .then(data => {
                            genrePosts.innerHTML = data;
                        });
                } else {
                    console.error('Element #genre-posts tidak ditemukan');
                }
            }

            // load default film_review + genre tabs
            loadGenres(tabActiveName);
            loadGenrePosts(tabActiveName, '');

        });
    </script>

<?php
    return ob_get_clean();
}
add_shortcode('tokoweb_tabs', 'tokoweb_tabs_shortcode');


function load_tokoweb_tab_content()
{
    $post_type = sanitize_text_field($_GET['post_type']);
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $posts_per_page = 8; // Batasi jumlah posting per halaman
    $offset = ($page - 1) * $posts_per_page;

    $genre = isset($_GET['genre']) ? sanitize_text_field($_GET['genre']) : '';

    $meta_query = [];
    if ($genre) {
        $meta_query[] = [
            'key' => 'genre',
            'value' => $genre,
            'compare' => 'LIKE'
        ];
    }

    $query = new WP_Query([
        'post_type' => $post_type,
        'posts_per_page' => $posts_per_page,
        'offset' => $offset,
        'meta_query' => $meta_query
    ]);


    if ($query->have_posts()) {

        $i = 1;
        while ($query->have_posts()) {
            $query->the_post();
            $thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium');

            if (!$thumb) {
                $thumb = 'https://via.placeholder.com/300x450?text=No+Image';
            }

            $rating = get_post_meta(get_the_ID(), 'rating', true);
            if (!$rating) {
                $rating = '0.0';
            }

            $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'film_review';
            $title = get_the_title();
            $album = get_post_meta(get_the_ID(), 'album_title', true);
            $artists = get_post_meta(get_the_ID(), 'artists', true);
            $artist_name = get_post_meta(get_the_ID(), 'artist_name', true);

            // 			echo "<pre>";
            // 			var_dump($album);
            // 			echo "</pre>";
            // 			die;

            $youtube_link = 'https://www.youtube.com/results?search_query=' . urlencode($title . ' trailer');

            echo '<div class="tokoweb-card">';
            // 				if($post_type === "film_review") {
            // 					echo '<img class="tokoweb-card-img" src="' . esc_url($thumb) . '" alt="' . esc_attr($title) . '">';
            // 				}

            // 				if($post_type === "music_review") {
            // 					if($thumb !== "https://via.placeholder.com/300x450?text=No+Image") {
            // 						echo '<img class="tokoweb-card-img" src="' . $thumb ? esc_url($thumb) : '' . '" alt="' . esc_attr($artist_name) . '">';
            // 					} else {
            // 						$fallback_image = 'https://dummyimage.com/300x450/ccc/000&text=' . $album;
            // 						echo '<img class="tokoweb-card-img" src="' . $thumb ? esc_url($thumb) : esc_url($fallback_image) . '" alt="' . esc_attr($album) . '">';
            // 					}
            // 				} 
            echo '<img class="tokoweb-card-img" src="' . esc_url($thumb) . '" alt="' . esc_attr($title) . '">';
            echo '<div class="tokoweb-card-body">';
            echo '<div class="tokoweb-rating"><span class="star">&#9733;</span> ' . esc_html($rating) . '</div>';
            if ($post_type === "film_review") {
                echo '<div class="tokoweb-title">' . $i . '. <a href="' . get_permalink() . '">' . esc_html($title) . '</a></div>';
            }
            if ($post_type === "music_review") {
                echo '<div class="tokoweb-title">' . $i . '. <a href="' . get_permalink() . '">' . esc_html($album) . '</a></div>';
            }

            if ($artist_name) {
                echo '<div class="tokoweb-artist-name"><strong>Artist:</strong> ' . esc_html($artist_name) . '</div>';
            }
            if ($artists) {
                echo '<div class="tokoweb-artists"><strong>Artists:</strong> ' . esc_html($artists) . '</div>';
            }
            echo '<div class="tokoweb-buttons">';
            echo '<a href="#" class="watchlist-btn">+ Watchlist</a>';
            echo '<a href="' . esc_url($youtube_link) . '" class="trailer-btn" target="_blank" rel="noopener">▶ Trailer</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            $i++;
        }
        wp_reset_postdata();
    } else {
        echo '<p>Tidak ada konten ditemukan.</p>';
    }

    wp_die();
}


function load_genre_tabs()
{
    $post_type = sanitize_text_field($_GET['post_type']);

    // Ambil genre dari ACF (assumsi genre disimpan sebagai string biasa, bukan taxonomy)
    $args = [
        'post_type' => $post_type,
        'posts_per_page' => -1,
        'meta_key' => 'genre',
        'fields' => 'ids'
    ];
    $query = new WP_Query($args);

    $genres = [];

    if ($query->have_posts()) {
        foreach ($query->posts as $post_id) {
            $genre = get_field('genre', $post_id);
            if (!empty($genre)) {
                $genres[] = $genre;
            }
        }
    }

    $genres = array_unique($genres);
    sort($genres);

    echo '<div class="genre-tab active" data-genre="">Semua</div>';
    foreach ($genres as $genre) {
        echo '<div class="genre-tab" data-genre="' . esc_attr($genre) . '">' . esc_html($genre) . '</div>';
    }

    wp_die();
}
add_action('wp_ajax_load_genre_tabs', 'load_genre_tabs');
add_action('wp_ajax_nopriv_load_genre_tabs', 'load_genre_tabs');

add_action('wp_ajax_load_tokoweb_tab_content', 'load_tokoweb_tab_content');
add_action('wp_ajax_nopriv_load_tokoweb_tab_content', 'load_tokoweb_tab_content');
