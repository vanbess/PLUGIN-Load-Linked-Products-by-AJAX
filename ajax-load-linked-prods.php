<?php
/*
Plugin Name: SBWC AJAX Load Linked Products
Plugin URI: 
Description: Loads linked products (as set up via plugin Products Linked by Variations for WooCommerce) via AJAX on the single product page. Requires the Products Linked by Variations for WooCommerce plugin to be installed and activated.
Version: 1.0.5
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
define('sbwc_ajax_fetch_linked_prods_html_PATH', plugin_dir_path(__FILE__));
define('sbwc_ajax_fetch_linked_prods_html_URI', plugin_dir_url(__FILE__));

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

    // echo do_shortcode('[product_page id="' . $product_id . '"]');

    // get linked products settings
    $linked_products_settings = get_option('plgfymao_all_rulesplgfyplv');

    // debug: output linked products settings to file
    // file_put_contents(sbwc_ajax_fetch_linked_prods_html_PATH . 'linked-products-settings.txt', print_r($linked_products_settings, true));

    // get apllied_on_ids column from linked products settings
    $linked_products_settings_applied_on_ids = array_column($linked_products_settings, 'apllied_on_ids');

    // debug: output linked products settings applied on ids to file
    // file_put_contents(sbwc_ajax_fetch_linked_prods_html_PATH . 'linked-products-settings-applied-on-ids.txt', print_r($linked_products_settings_applied_on_ids, true));

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

    // available variations array which holds all available variations for all linked products
    $available_variations = array();

    // loop through linked product ids and get available variations for each
    foreach ($linked_product_ids as $linked_product_id) :

        // get product object
        $product = wc_get_product($linked_product_id);

        // get available variations
        $product_available_variations = $product->get_available_variations();

        // loop through available variations and add to available variations array
        foreach ($product_available_variations as $product_available_variation) :

            // add to available variations array
            $available_variations[$product->get_permalink()][] = $product_available_variation;

        endforeach;

    endforeach;

    // debug: output available variations to file
    // file_put_contents(sbwc_ajax_fetch_linked_prods_html_PATH . 'available-variations-all-linked.txt', print_r($available_variations, true));

?>

    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <!-- send ajax request to fetch product single content -->
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <script id="sbwc-linked-ajax-load-js" async="false" data-av-variations="<?php echo wc_esc_json(wp_json_encode($available_variations)); ?>">

        (function($) {

            // available variations
            var available_variations = $('#sbwc-linked-ajax-load-js').attr('data-av-variations');

            // DEBUG
            // console.log(available_variations);

            // target container for replacing content
            var to_replace = $('#product-' + <?php echo $product_id; ?>);

            // holds linked product html
            var linked_product_html = [];

            // loop through .aclass-clr and fetch product single content for each linked product, pushing href and html to array
            $('.navplugify').find('.imgclasssmall').each(function(i, e) {

                // get first a element
                var a = $(this).find('a').eq(0);

                // get href
                var href = a.attr('href');

                // push href to array
                linked_product_html.push(href);

            });

            // get current permalink
            var current_permalink = window.location.href;

            // get each .aclass-clr href attribute and append to array
            var linked_product_urls = [];

            // add current permalink to array
            linked_product_urls.push(current_permalink);

            $('.aclass-clr').each(function() {

                // if not in array, add to array
                if (linked_product_urls.indexOf($(this).attr('href')) === -1) {
                    linked_product_urls.push($(this).attr('href'));
                }

            });

            // replacement html array (link => html)
            var replacement_html = {};

            // holds deferred objects for each AJAX request
            var deferreds = [];

            // href => replacement html data store
            var data_store = {};

            // loop through linked product urls and send ajax request to fetch product single content
            $.each(linked_product_urls, function(i, v) {

                var deferred = $.Deferred();

                // send ajax request to fetch product single content
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'sbwc_ajax_fetch_linked_prods_html',
                    _ajax_nonce: '<?php echo wp_create_nonce('sbwc_ajax_fetch_linked_prods_html_nonce'); ?>',
                    linked_product_url: v
                }, function(data) {

                    // resolve deferred
                    deferred.resolve();

                    // DEBUG
                    // console.log(data);
                    // return;

                    data_store[v] = data;
                });

                // push to deferreds array
                deferreds.push(deferred);

            });

            // DEBUG
            // $.when.apply($, deferreds).done(function() {
            //     console.log(data_store);
            //     return;
            // });

            // ~~~~~~~~~~~~~~~~~~~~~~~~
            // linked swatch on click
            // ~~~~~~~~~~~~~~~~~~~~~~~~
            $('.navplugify').on('click', '.imgclasssmall, .imgclasssmallactive', function(e) {

                e.preventDefault();

                // remove disabled class from #size-select-prompt
                $('#size-select-prompt').removeClass('disabled');

                // get first a element
                var a = $(this).find('a').eq(0);

                // get href
                var href = a.attr('href');

                // get product id from href
                var product_id = href.split('/').pop();

                $('.imgclasssmall, .imgclasssmallactive').css({
                    'outline': 'none',
                    'outline-offset': 'none',
                    'cursor': 'pointer'
                });

                // if imgclasssmallactive does not have a.aclass-clr, append a.aclass-clr to .imgclasssmallactive with current href as href attribute
                if (!$('.imgclasssmallactive').find('a.aclass-clr').length) {
                    $('.imgclasssmallactive').find('.child_class_plugify').append('<a style="opacity:0;" class="aclass-clr" href="' + window.location.href + '"></a>');
                    $('.imgclasssmallactive').prepend('<a style="opacity:0;" class="aclass-clr" href="' + window.location.href + '"></a>');
                }

                // set active style
                $(this).css({
                    'outline': '1px solid #000',
                    'outline-offset': '3px'
                });

                // ####################################
                // BEGIN REPLACEMENT OF HTML ELEMENTS
                // ####################################

                // get data-html-encoded attribute
                var linked_products_html_encoded = data_store[href]['product_html'];

                // temp div
                var temp_div = $('<div></div>');

                // append decoded to temp div
                temp_div.append(linked_products_html_encoded);

                // DEBUG
                // console.log(temp_div.html());

                // get first .elementor-column elementor-col-50
                var elementor_column = temp_div.find('.elementor-column.elementor-col-50').eq(0);

                // find .product-single-carousel in elementor_column
                var product_single_carousel = elementor_column.find('.product-single-carousel').eq(0);

                // find .product-thumbs-wrap in elementor_column
                var product_thumbs_wrap = elementor_column.find('.product-thumbs-wrap').eq(0);

                // get second .elementor-column elementor-col-50
                var elementor_column_2 = temp_div.find('.elementor-column.elementor-col-50').eq(1);

                // find title in elementor_column_2
                var title = elementor_column_2.find('.elementor-widget-riode_sproduct_title').eq(0);

                // find rating in elementor_column_2
                var rating = elementor_column_2.find('.elementor-widget-riode_sproduct_rating').eq(0);

                // find price in elementor_column_2
                var price = elementor_column_2.find('.elementor-widget-riode_sproduct_price').eq(0);

                // get .cart 
                var cart_form = elementor_column_2.find('.cart').eq(0);

                // add to cart button
                var add_to_cart_button = cart_form.find('.single_add_to_cart_button').eq(0);

                // add disabled class to button
                add_to_cart_button.addClass('disabled wc-variation-selection-needed');

                // buy now button
                var buy_now_button = cart_form.find('.product-buy-now').eq(0);

                // add disabled class to button
                buy_now_button.addClass('disabled wc-variation-selection-needed');

                // get action attribute
                var action = cart_form.attr('action');

                // get product id
                var product_id = cart_form.attr('data-product_id');

                // get variation data
                var variation_data = cart_form.attr('data-product_variations');

                // retrieve gtm4wp hidden input data
                var gtm_hidden_inputs = cart_form.find('input[name^="gtm4wp_"]');

                // find add-to-cart hidden input value
                var add_to_cart = cart_form.find('input[name="add-to-cart"]').eq(0).val();

                // find product_id hidden input value
                var product_id = cart_form.find('input[name="product_id"]').eq(0).val();

                // replace all relevant html and cart form attributes along with hidden input values
                $('.elementor-widget-riode_sproduct_title').replaceWith(title);
                $('.elementor-widget-riode_sproduct_rating').replaceWith(rating);
                $('.elementor-widget-riode_sproduct_price').replaceWith(price);
                $('.cart').attr('action', action);
                $('.cart').attr('data-product_id', product_id);
                $('.cart').attr('data-product_variations', variation_data);
                $('.cart').find('input[name^="gtm4wp_"]').remove();
                $('.cart').find('.woocommerce-variation-add-to-cart').append(gtm_hidden_inputs);
                $('.cart').find('input[name="add-to-cart"]').eq(0).val(add_to_cart);
                $('.cart').find('input[name="product_id"]').eq(0).val(product_id);

                // replace add to cart button
                $('.single_add_to_cart_button').replaceWith(add_to_cart_button);

                // Get all the .imgclasssmall elements
                var containers = $('*[class*="imgclasssmall"]');

                var parent = $('.navplugify > div');

                // Sort the .imgclasssmall elements based on the value of their child <a> element's href attribute
                containers.sort(function(a, b) {
                    var aText = $(a).text();
                    var bText = $(b).text();
                    return aText.localeCompare(bText);
                });

                $('.elementor-widget-riode_sproduct_image').eq(1).remove();

                // Remove the sorted elements from their current location in the HTML
                containers.detach();

                // Append the sorted elements to their new location based on the sorted order
                containers.appendTo(parent);

                // remove second instance of .navplugify
                $('.navplugify').find('.navplugify').remove();

                // replace product single carousel
                $('.product-single-carousel').replaceWith(product_single_carousel);

                // replace product thumbs wrap
                $('.product-thumbs-wrap').replaceWith(product_thumbs_wrap);

                // remove .woocommerce-variation.single_variation
                $('.woocommerce-variation.single_variation').remove();

                // ####################
                // REINIT OWL CAROUSEL
                // ####################

                // destroy .product-single-carousel owl carousel and .product-thumbs owl carousel
                $('.product-single-carousel').owlCarousel('destroy');
                $('.product-thumbs').owlCarousel('destroy');

                // reinit .product-thumbs owl carousel
                $('.product-thumbs').owlCarousel({
                    items: 4,
                    nav: true,
                    navText: [],
                    dots: false,
                });

                // reinit .product-single-carousel
                $('.product-single-carousel').owlCarousel({
                    items: 1,
                    nav: true,
                    navText: [],
                    dots: false,
                });

                // slide corresponding main image to first position in .product-single-carousel on .product-thumbs click
                $('.product-thumbs').on('click', '.owl-item', function(e) {

                    // get index
                    var index = $(this).index();

                    // set thumbnail active class
                    $('.product-thumb').removeClass('active');
                    $('.product-thumb').eq(index).addClass('active');

                    // slide to index
                    $('.product-single-carousel').trigger('to.owl.carousel', [index]);
                });

                // set product thumb active class on .product-single-carousel slide change
                $('.product-single-carousel').on('changed.owl.carousel', function(e) {

                    // get index
                    var index = e.item.index;

                    // set thumbnail active class
                    $('.product-thumb').removeClass('active');
                    $('.product-thumb').eq(index).addClass('active');

                    // slide into view if not in view
                    $('.product-thumb').trigger('to.owl.carousel', [index]);

                });

                // replace current window location with href
                window.history.pushState(null, null, href);

                // #########################################
                // reinit gallery main image zoom on hover
                // #########################################
                $('.woocommerce-product-gallery__image').zoom({
                    url: $(this).parent().attr('href'),
                    touch: false,
                    magnify: 1,
                    on: 'mouseover',
                    onZoomIn: function() {
                        $(this).parent().css('cursor', 'pointer');
                    },
                });

                // ##################
                // REINIT PHOTOSWIPE
                // ##################
                // product image full button html
                var product_image_full_button_html = '<button class="product-image-full d-icon-zoom"></button>';

                // insert after .owl-stage-outer inside .product-single-carousel
                $('.product-single-carousel').find('.owl-stage-outer').after(product_image_full_button_html);

                // setup pswp html
                var pswp_html = data_store[href]['.pswp'];

                // delete current .pswp element
                $('.pswp').remove();

                // destroy photoswipe
                $('.product-image-full').off('click');

                // append pswp html to body
                $('body').append(pswp_html);

                // pswp element
                var pswpElement = document.querySelectorAll('.pswp')[0];

                // build items array
                var items = [];

                // loop through .woocommerce-product-gallery__image elements and push to items array
                product_single_carousel.find('.woocommerce-product-gallery__image').each(function(i, e) {

                    // get first a element
                    var first_a = $(this).find('a').eq(0);

                    // get a > first img srcset
                    var srcset = first_a.find('img').eq(0).attr('srcset');

                    // get last item in srcset
                    var src = srcset.split(',').pop().trim().split(' ')[0];

                    // get width and height from child .zoomImg css
                    var width = 1400;
                    var height = 1400;

                    // push to items array
                    items.push({
                        src: src,
                        w: width,
                        h: height
                    });

                });

                // DEBUG
                // $.each(items, function(i, v) {
                //     window.open(v.src, '_blank');
                // });

                var options = {
                    index: 0,
                    bgOpacity: 0.8,
                    history: false,
                    showHideOpacity: true,
                    closeElClasses: ['item', 'caption', 'zoom-wrap', 'ui', 'top-bar'],
                    closeOnOutsideClick: true,
                    closeEl: true,
                    escKey: true,
                    arrowKeys: true,
                };

                // init photoswipe
                var gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);

                // init gallery on .product-image-full click
                $('.product-image-full').on('click', function(e) {

                    // disable any previously attached click events
                    e.stopPropagation();

                    // gallery init
                    gallery.init();
                });

                // remove class and/or any empty attributes named 'class' from .pa_size button elements
                $('.pa_size').find('button').each(function(e) {

                    $(this).removeClass().removeAttr('class');

                });


                // size on click
                $('.pa_size').on('click mousedown', 'button', function(event) {

                    // stop propagation
                    event.stopPropagation();

                    // prevent default
                    event.preventDefault();

                    // sort classes
                    $(this).parent().find('button').removeClass().removeAttr('class');
                    $(this).addClass('active');

                    // get variation data from form and find matching variation id
                    var variation_data = JSON.parse($('.cart').attr('data-product_variations'));

                    $.each(variation_data, function (i, v) { 

                        var attributes = v.attributes;
                        var attr_val = attributes.attribute_pa_size;

                        if (attr_val == $(event.target).attr('name')) {
                            
                            // set attribute id of hidden field
                            $('.cart').find('input[name="variation_id"]').eq(0).val(v.variation_id);

                        }

                    });

                    // enable add to cart and buy now buttons
                    $('.single_add_to_cart_button').removeClass('disabled wc-variation-selection-needed');
                    $('.product-buy-now').removeClass('disabled wc-variation-selection-needed');

                    // add disabled class to #size-select-prompt
                    $('#size-select-prompt').addClass('disabled');

                });

            });

        })(jQuery);
    </script>

<?php

});

/**
 * AJAX callback to fetch product single content for linked products
 */
add_action('wp_ajax_sbwc_ajax_fetch_linked_prods_html', 'sbwc_ajax_fetch_linked_prods_html');
add_action('wp_ajax_nopriv_sbwc_ajax_fetch_linked_prods_html', 'sbwc_ajax_fetch_linked_prods_html');

function sbwc_ajax_fetch_linked_prods_html()
{

    // check nonce
    check_ajax_referer('sbwc_ajax_fetch_linked_prods_html_nonce', '_ajax_nonce');

    // get product link
    $linked_product_url = $_POST['linked_product_url'];

    // get id from url
    $linked_product_id = url_to_postid($linked_product_url);

    // fetch product page via curl
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $linked_product_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    // $output contains the output string
    $linked_product_html = curl_exec($ch);

    // close curl resource to free up system resources
    curl_close($ch);

    // target id
    $target_id = 'product-' . $linked_product_id;

    // create dom document
    $dom = new DOMDocument();

    // load html
    @$dom->loadHTML($linked_product_html);

    // get product container
    $product_container = $dom->getElementById($target_id);

    // product html
    $product_html = $product_container->ownerDocument->saveHTML($product_container);

    // get element with CSS class .pswp
    $pswp = new DOMXPath(@$dom);

    // get pswp container
    $pswp_container = $pswp->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' pswp ')]");

    $return['.pswp'] = $pswp_container->item(0)->ownerDocument->saveHTML($pswp_container->item(0));
    $return['product_html'] = $product_html;

    wp_send_json($return);

}
