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

    // get linked products settings
    $linked_products_settings = get_option('plgfymao_all_rulesplgfyplv');

    // DEBUG
    // delete_post_meta($product_id, 'sbwc_linked_product_html_cache_key');

    // get cache key from product meta
    $cache_key = get_post_meta($product_id, 'sbwc_linked_product_html_cache_key', true);

    // if cache key exists, get cached linked product html
    if ($cache_key) :

        // get cached linked product html
        $linked_product_html = wp_cache_get($cache_key) ? wp_cache_get($cache_key) : 'cache key exists but no cached linked product html found!';

?>

        <!-- hidden input for holding encoded href => html data -->
        <input type="hidden" id="sbwc-linked-prods-html" value="<?php echo $linked_product_html ?>">

        <!-- send ajax request to fetch product single content -->
        <script id="sbwc-linked-prods-ajax" data-flag="has cache key" data-cache-key="<?php echo $cache_key; ?>" async="false">
            $ = jQuery;

            // holds linked product html
            var linked_product_html = [];

            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            // .imgclasssmall on click of first <a> child element
            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            $('.imgclasssmall').on('click', 'a', function(e) {

                // prevent default
                e.preventDefault();

                console.log('replacing...');

                // decode
                var linked_product_html = $('#sbwc-linked-prods-html').val();

                // parse
                linked_product_html = JSON.parse(linked_product_html);

                // console.log(linked_product_html);
                // return;

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


    <?php else :

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
        endif; ?>


        <!-- hidden input for holding encoded href => html data -->
        <input type="hidden" id="sbwc-linked-prods-html" value="">

        <!-- send ajax request to fetch product single content -->
        <script id="sbwc-linked-prods-ajax" data-flag="does not have cache key" async="false">
            $ = jQuery;

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

                // debug
                console.log(linked_product_html);

                // stringify linked_product_html
                linked_product_html = JSON.stringify(linked_product_html);

                // set hidden input value to
                $('#sbwc-linked-prods-html').val(linked_product_html);

                // send ajax request to save linked_product_html to wp cache
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'sbwc_ajax_save_linked_prods_html',
                        nonce: '<?php echo wp_create_nonce('sbwc_ajax_save_linked_prods_html_nonce'); ?>',
                        linked_product_html: linked_product_html,
                        linked_product_ids: '<?php echo base64_encode(json_encode($linked_product_ids)); ?>'
                    },
                    success: function(response) {
                        console.log(response);
                    }
                });

            });


            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            // .imgclasssmall on click of first <a> child element
            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            $('.imgclasssmall').on('click', 'a', function(e) {

                // prevent default
                e.preventDefault();

                console.log('replacing...');

                // decode
                var linked_product_html = $('#sbwc-linked-prods-html').val();

                // parse
                linked_product_html = JSON.parse(linked_product_html);

                // console.log(linked_product_html);
                // return;

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

<?php endif;
});

/**
 * Save linked products html to wp cache
 */
add_action('wp_ajax_sbwc_ajax_save_linked_prods_html', 'sbwc_ajax_save_linked_prods_html');
add_action('wp_ajax_nopriv_sbwc_ajax_save_linked_prods_html', 'sbwc_ajax_save_linked_prods_html');

function sbwc_ajax_save_linked_prods_html()
{

    // bail if nonce fails
    if (!wp_verify_nonce($_POST['nonce'], 'sbwc_ajax_save_linked_prods_html_nonce')) :
        wp_send_json_error('Nonce failed!');
    endif;

    // wp_send_json($_POST);

    // get linked product html
    $linked_product_html = $_POST['linked_product_html'];

    // get linked product ids
    $linked_product_ids = $_POST['linked_product_ids'];

    // decode linked product ids
    $linked_product_ids = json_decode(base64_decode($linked_product_ids));

    // set unique cache key
    $cache_key = 'sbwc_linked_product_html_' . time();

    // loop through linked product ids and save cache key to product meta
    foreach ($linked_product_ids as $lid) :
        $cache_keys_saved[] =  update_post_meta($lid, 'sbwc_linked_product_html_cache_key', $cache_key);
    endforeach;

    // set cache expiration to 1 day
    $cache_expiration = 60 * 60 * 24;

    // add cache
    $cached = wp_cache_add($cache_key, $linked_product_html, '', $cache_expiration);

    // set transient as backup
    $transient_set = set_transient($cache_key, $linked_product_html, $cache_expiration);

    // end error or success
    if (!$cached) :
        wp_send_json_error('Failed to save linked product html to cache!');
    else :
        wp_send_json_success(['cache_key' => $cache_key, 'cache_keys_saved' => $cache_keys_saved, 'cached' => $cached, 'transient_set' => $transient_set, 'msg' => $cached ? 'Linked product html saved to cache!' : 'Linked product html not saved to cache!']);
    endif;
}
