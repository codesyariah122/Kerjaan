<?php
// Dummy array
$tags = ['php', 'laravel', 'mysql'];

// Convert array ke string
$tags_str = implode(',', $tags); // h


echo "<pre>";

$tags_str_explode = explode(',', $tags_str);

foreach ($tags_str_explode as $string) {
    echo $string . "<br>";
}

// var_dump($tags_str_explode);

echo "</pre>";
