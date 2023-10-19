<?php
/*
Plugin Name: SBWC AJAX Load Linked Products
Plugin URI: 
Description: Loads linked products (as set up via plugin Products Linked by Variations for WooCommerce) via AJAX on the single product page. Requires the Products Linked by Variations for WooCommerce plugin to be installed and activated.
Version: 1.0.0
Author: WC Bessinger
Author URI: 
License: GPL2
*/

// Prevent direct access
if (!defined('ABSPATH')) :
    exit;
endif;

// Check if products linked by variations plugin is active; bail if not
if (!class_exists('PlugfyMAO_Main_Class_Alpha')) :
    return;
endif;

// Define plugin path and URI
define('SBWC_AJAX_LOAD_LINKED_PRODUCTS_PATH', plugin_dir_path(__FILE__));
define('SBWC_AJAX_LOAD_LINKED_PRODUCTS_URI', plugin_dir_url(__FILE__));

// Add JS to product single page footer to fetch complete HTML for all linked products based on product ID 
// (woocommerce product single template and all content per product) and return it in base64 encoded format, 
// adding to hidden input in product single page
add_action('wp_footer', function () {

    // bail if not on product page
    if (!is_product()) :
        return;
    endif;

    // get product id
    $product_id = get_the_ID();

    // DEBUG
    $cache_key = get_post_meta($product_id, 'sbwc_ajax_linked_products_cache_key', true);
    delete_post_meta($product_id, 'sbwc_ajax_linked_products_cache_key');
    delete_transient($cache_key);
    wp_cache_delete($cache_key, 'sbwc_ajax_linked_products_cache');

    // get linked products settings
    $linked_products_settings = get_option('plgfymao_all_rulesplgfyplv');

    // debug: output linked products settings to file
    // file_put_contents(SBWC_AJAX_LOAD_LINKED_PRODUCTS_PATH . 'linked-products-settings.txt', print_r($linked_products_settings, true));

    // get apllied_on_ids column from linked products settings
    $linked_products_settings_applied_on_ids = array_column($linked_products_settings, 'apllied_on_ids');

    // debug: output linked products settings applied on ids to file
    // file_put_contents(SBWC_AJAX_LOAD_LINKED_PRODUCTS_PATH . 'linked-products-settings-applied-on-ids.txt', print_r($linked_products_settings_applied_on_ids, true));

    // array of product ids linked to current product id
    $linked_product_ids = array();

    // loop to search for product id in applied_on_ids column
    foreach ($linked_products_settings_applied_on_ids as $linked_products_settings_applied_on_id) :

        // if product id is found in applied_on_ids column, set linked product ids value and break loop
        if (in_array($product_id, $linked_products_settings_applied_on_id)) :
            $linked_product_ids = $linked_products_settings_applied_on_id;
            break;
        endif;

    endforeach;

    // if current product id not in applied_on_ids column for some reason, append current product id to linked product ids array
    if (!in_array($product_id, $linked_product_ids)) :
        $linked_product_ids[] = $product_id;
    endif;

    // get cache key
    $cache_key = get_post_meta($product_id, 'sbwc_ajax_linked_products_cache_key', true);

    $linked_product_html = '';

    // if cache key exists, get linked product html from transient cache
    if ($cache_key) :

        // get linked product html from transient cache
        $linked_product_html = get_transient($cache_key);

        // if linked product html is empty, get linked product html from wp cache
        if (empty($linked_product_html)) :
            $linked_product_html = wp_cache_get($cache_key, 'sbwc_ajax_linked_products_cache');
        endif;

        // if linked product html is empty, delete cache key and return
        if (empty($linked_product_html)) :
            delete_post_meta($product_id, 'sbwc_ajax_linked_products_cache_key');
        endif; ?>

        <input type="hidden" id="sbwc-linked-prods-cached-already" data-cached="<?php echo empty($linked_product_html) ? null : $linked_product_html; ?>" value="<?php echo empty($linked_product_html) ? 'no' : 'yes'; ?>" />

    <?php else : ?>

        <input type="hidden" id="sbwc-linked-prods-cached-already" data-cached="null" value="no" />

    <?php endif; ?>

    <!-- send ajax request to fetch product single content -->
    <script id="sbwc-linked-prods-ajax" async="false">
        $ = jQuery;

        // holds value of hidden input
        var linked_product_html = $('#sbwc-linked-prods-cached-already').attr('cached');
        var is_cached = $('#sbwc-linked-prods-cached-already').val();

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        // loop through .aclass-clr and fetch product single content for each linked product, pushing href and html to array
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        if (is_cached == 'no' || typeof(linked_product_html) == 'undefined') {

            // holds linked product html
            var linked_product_html = [];

            // holds deferred objects for each AJAX request
            var deferreds = [];

            $('.imgclasssmall').each(function(i, e) {

                // get first a element
                var a = $(this).find('a').eq(0);

                // get href
                var href = a.attr('href');

                // create deferred object for AJAX request
                var deferred = $.Deferred();

                // Use jQuery's $.get method to fetch the content
                $.get(href, function(data) {

                    // push href and html to array
                    linked_product_html.push({
                        href: href,
                        html: data
                    });

                    // resolve deferred object
                    deferred.resolve();

                });

                // add deferred object to array
                deferreds.push(deferred);

            });

            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            // add to hidden input for caching and/or later retrieval
            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            $.when.apply($, deferreds).done(function() {

                // set hidden input data-cached to linked product html
                $('#sbwc-linked-prods-cached-already').attr('data-cached', btoa(linked_product_html));

                // send ajax request to save to transient cache/wp cache
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php') ?>',
                    type: 'POST',
                    data: {
                        linked_product_html: btoa(linked_product_html),
                        linked_product_ids: '<?php echo json_encode($linked_product_ids) ?>',
                        action: 'sbwc_ajax_cache_linked_products',
                        nonce: '<?php echo wp_create_nonce('sbwc_ajax_cache_linked_products_nonce') ?>',
                    },
                    success: function(data) {
                        console.log(data);
                    }
                });

            });

        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        // .imgclasssmall on click of first <a> child element
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        $('.imgclasssmall').on('click', 'a', function(e) {

            // prevent default
            e.preventDefault();

            console.log('clicked linked product');

            // var linked_product_html = atob(linked_product_html);

            console.log(linked_product_html);

            return;

            // get href
            var href = $(this).attr('href');

            // get index of href in linked_product_html array
            var index = linked_product_html.findIndex(x => x.href === href);

            // get html
            var html = linked_product_html[index].html;

            // DEBUG
            // console.log(href);
            // console.log(index);
            // console.log(html);

            // replace current document with new document
            document.open();

            document.write(html);

            document.close();

            // set current location
            window.history.pushState("", "", href);

        });
    </script>

<?php });

/**
 * AJAX callback to save linked product html to transient cache/wp cache
 */
add_action('wp_ajax_sbwc_ajax_cache_linked_products', 'sbwc_ajax_cache_linked_products');
add_action('wp_ajax_nopriv_sbwc_ajax_cache_linked_products', 'sbwc_ajax_cache_linked_products');

function sbwc_ajax_cache_linked_products()
{

    // bail if nonce fails
    if (!wp_verify_nonce($_POST['nonce'], 'sbwc_ajax_cache_linked_products_nonce')) :
        wp_send_json_error('Nonce verification failed', 401);
    endif;

    print_r(json_decode(base64_decode($_POST['linked_product_html'])));

    wp_die();

    // get linked product ids
    $linked_product_ids = $_POST['linked_product_ids'];

    // get linked product html
    $linked_product_html = $_POST['linked_product_html'];

    // encode
    // $linked_product_html = base64_encode(wp_json_encode($linked_product_html));

    // wp_send_json($linked_product_html);

    // define unique cache key
    $cache_key = 'sbwc_ajax_linked_products_cache_' . time();

    // loop through linked product ids and save cache key to each
    foreach ($linked_product_ids as $linked_product_id) :

        // check for current cache key
        $current_cache_key = get_post_meta($linked_product_id, 'sbwc_ajax_linked_products_cache_key', true);

        // if cache key == current cache key, bail
        if ($cache_key == $current_cache_key) :
            continue;
        endif;

        // update cache key
        update_post_meta($linked_product_id, 'sbwc_ajax_linked_products_cache_key', $cache_key);

    endforeach;

    // add to transient cache
    $transient_set = set_transient($cache_key, $linked_product_html, 60 * 60 * 24);

    // add to wp cache
    $cache_set = wp_cache_set($cache_key, $linked_product_html, 'sbwc_ajax_linked_products_cache', 60 * 60 * 24);

    // if cache and transient set failed, return error
    if (!$transient_set && !$cache_set) :
        wp_send_json_error('Linked products cache failed', 500);
    else :
        // return success with unique cache key
        wp_send_json_success($cache_key, 200);
    endif;
}
