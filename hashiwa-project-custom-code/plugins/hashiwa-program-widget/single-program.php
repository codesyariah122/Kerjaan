<?php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post(); ?>

        <div class="program-single" style="max-width:900px;margin:80px auto;">
            <h1><?php the_title(); ?></h1>
            <?php if (has_post_thumbnail()) : ?>
                <div class="program-thumb" style="margin:20px 0;">
                    <?php the_post_thumbnail('large', ['style' => 'width:100%;border-radius:12px;']); ?>
                </div>
            <?php endif; ?>

            <div class="program-content">
                <?php the_content(); ?>
            </div>
        </div>

<?php endwhile;
endif;

get_footer();
