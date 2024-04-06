<?php

add_action('init', function () {
    register_block_type(__DIR__ . '/build');
});

add_action('wp_enqueue_scripts', function () {
    $assets = require(__DIR__ . '/build/index.asset.php');
});
