<?php
add_action('restrict_manage_posts', 'add_export_button_for_test_drive');

function add_export_button_for_test_drive() {
    global $typenow;

    if ($typenow == 'test-drive') {
        echo '<input type="submit" name="export_test_drive" class="button button-primary" value="Export to Excel">';
    }
}

add_action('init', 'handle_export_test_drive');

function handle_export_test_drive() {
    if (isset($_GET['export_test_drive']) && $_GET['export_test_drive'] == 'Export to Excel') {
        if (isset($_GET['post_type']) && $_GET['post_type'] == 'test-drive') {
            export_test_drive_to_excel();
        }
    }
}

function export_test_drive_to_excel() {
    require_once ABSPATH . 'excel-library/export-excel/vendor/autoload.php';

    $writer = \Box\Spout\Writer\Common\Creator\WriterEntityFactory::createXLSXWriter();
    $writer->openToBrowser('test-drive-export-' . date('Y-m-d') . '.xlsx'); // Output to browser

    $headerRow = \Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray(['Post ID', 'Title', 'Date', 'Time', 'Name', 'Phone', 'Email', 'Car Model', 'Dealership', 'Message']);
    $writer->addRow($headerRow);

    $args = array(
        'post_type'   => 'test-drive',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    );
    $posts = get_posts($args);

    foreach ($posts as $post) {
        $row = \Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray([
            $post->ID,
            $post->post_title,
            $post->post_date,
			$post->time,
			$post->full_name,
			$post->phone_number,
			$post->email,
			$post->car_model,
			$post->dealership,
			$post->message
        ]);
        $writer->addRow($row);
    }

    $writer->close();
    exit;
}
