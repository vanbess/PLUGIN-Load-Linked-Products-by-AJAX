<?php
/*
Plugin Name: SBWC AJAX Load Linked Products
Plugin URI: 
Description: Loads linked products (as set up via plugin Products Linked by Variations for WooCommerce) via AJAX on the single product page. Requires the Products Linked by Variations for WooCommerce plugin to be installed and activated.
Version: 1.0.7
Author: WC Bessinger
Author URI: 
License: GPL2
*/

// Prevent direct access
if (!defined('ABSPATH')) :
    exit;
endif;

add_action('init', function () {

    // Check if products linked by variations plugin is active; bail if not
    if (!class_exists('PlugfyMAO_Main_Class_Alpha')) :
        return;
    endif;

    // Define plugin path and URI
    define('sbwc_ajax_fetch_linked_prods_html_PATH', plugin_dir_path(__FILE__));
    define('sbwc_ajax_fetch_linked_prods_html_URI', plugin_dir_url(__FILE__));

    // Hook to wp_footer on product_single to do the magic
    add_action('wp_footer', function () {

        // bail if not on product page
        if (!is_product()) :
            return;
        endif;

        // Initial setup
        require_once sbwc_ajax_fetch_linked_prods_html_PATH . 'functions/initial_setup.php';

        // CSS loading spinner 
?>

        <style>
            #main {
                z-index: 0;
            }

            .sbwc_linked_prods_loading_spinner {
                position: absolute;
                top: 35vh;
                left: 49vw;
                transform: translate(-50%, -50%);
                border: 3px solid #f3f3f3;
                border-radius: 50%;
                border-top: 3px solid #3498db;
                width: 40px;
                height: 40px;
                -webkit-animation: spin 0.5s linear infinite;
                animation: spin 0.5s linear infinite;
                z-index: 9999;
            }
        </style>

<?php    });

    // AJAX to fetch linked products HTML
    require_once sbwc_ajax_fetch_linked_prods_html_PATH . 'functions/product_html_ajax.php';
});

// Enqueue scripts
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('sbwc-ajax-fetch-linked-prods-html', sbwc_ajax_fetch_linked_prods_html_URI . 'assets/sbwc.linked.by.var.ajax.js', array('jquery'), '1.0.7', true);
});
