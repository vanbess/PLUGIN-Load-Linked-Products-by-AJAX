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

    <!-- hidden input -->
    <input type="hidden" id="sbwc-ajax-load-linked-products" value="" />

    <!-- send ajax request to fetch product single content -->
    <script async="false">
        $ = jQuery;

        // holds linked product html
        var linked_product_html = [];

        // loop through .aclass-clr and fetch product single content for each linked product, pushing href and html to array
        $('.imgclasssmall').each(function(i,e) {

            console.log('looping through .imgclasssmall '+i);

            // get first a element
            var a = $(this).find('a').eq(0);

            // get href
            var href = a.attr('href');



            console.log(href);

            // // get href
            // var href = $(this).attr('href');

            // // Use jQuery's $.get method to fetch the content
            // $.get(href, function(data) {
            //     console.log(data);
            // });

        });

        // console.log(linked_product_html);


        // load product content via AJAX when variation link is clicked
        // $('.aclass-clr').on('click', function(event) {

        //     event.preventDefault();

        // //    fetch page content using value of href

        // // Get the href attribute of the clicked link
        // var href = $(this).attr('href');

        // // Use jQuery's $.get method to fetch the content
        // $.get(href, function (data) {
        //     // 'data' contains the fetched content

        //     // console.log(data);

        //     // replace current document with new document
        //     document.open();

        //     document.write(data);

        //     document.close();

        //     // set current location
        //     window.history.pushState("", "", href);



        //     // create temp cont
        //     // var temp_cont = $('<div></div>');

        //     // // append data to temp cont
        //     // temp_cont.append(data);

        //     // // find body
        //     // var main = temp_cont.find('body').html();

        //     // // console.log(main);

        //     // // replace #main with main
        //     // $('body').replaceWith(main);

        //     // You can now manipulate or display the content
        //     // $('#content-container').html(data);
        // });



        });

        // // get current permalink
        // var current_permalink = window.location.href;

        // // fix default variation form
        // let link = '<a style="opacity: 0" class="aclass-clr" href="' + current_permalink + '"></a>';
        // $('.imgclasssmallactive').removeClass().addClass('imgclasssmall').css('cursor', 'pointer').prepend(link).find('.child_class_plugify').append(link);

        // // append container to body for testing
        // $('body').append('<div id="sbwc-ajax-load-linked-products-container"></div>');

        // // get each .aclass-clr href attribute and append to array
        // var linked_product_urls = [];

        // // add current permalink to array
        // linked_product_urls.push(current_permalink);

        // $('.aclass-clr').each(function() {

        //     // if not in array, add to array
        //     if (linked_product_urls.indexOf($(this).attr('href')) === -1) {
        //         linked_product_urls.push($(this).attr('href'));
        //     }

        // });

        // // loop through linked product urls and send ajax request to fetch product single content
        // $.each(linked_product_urls, function(i, v) {

        //     // send ajax request
        //     $.ajax({
        //         url: '<?php echo admin_url('admin-ajax.php') ?>',
        //         type: 'POST',
        //         data: {
        //             linked_product_url: v,
        //             action: 'sbwc_ajax_load_linked_products',
        //             _ajax_nonce: '<?php echo wp_create_nonce('sbwc_ajax_load_linked_products_nonce'); ?>'
        //         },
        //         success: function(response) {

        //             console.log(response);



        //             // $('.aclass-clr[href="' + v + '"]').attr('data-linked_data', JSON.stringify(response));

        //         },
        //         error: function(error) {
        //             console.log(error);
        //         }
        //     });

        // });

        // replace product single content with linked product content on click
        // $(document).on('click', '.imgclasssmall', function(e) {

        //     // prevent default
        //     e.preventDefault();

        //     // empty .product-thumb
        //     $('.product-thumb').empty();

        //     // get linked data
        //     var linked_data = JSON.parse($(this).find('a').attr('data-linked_data'));

        //     // remove class from all parents
        //     $('.child_class_plugify').removeClass('linked-clicked');

        //     // add class to child
        //     $(this).find('.child_class_plugify').addClass('linked-clicked');

        //     // loop through linked data and replace content
        //     $.each(linked_data, function(i, v) {

        //         $.each(v, function(i2, v2) {

        //             // replace gallery
        //             if (i2 == 'gallery') {
        //                 var gallery = v2;

        //                 // console.log(JSON.parse(gallery));

        //                 // replace .woocommerce-product-gallery with gallery
        //                 $('.woocommerce-product-gallery').replaceWith(JSON.parse(gallery));

        //                 // re-init owl carousel
        //                 var carousel_main = $('.product-single-carousel');

        //                 // destroy owl carousel
        //                 carousel_main.trigger('destroy.owl.carousel');

        //                 // re-initialize owl carousel
        //                 carousel_main.owlCarousel({
        //                     items: 1,
        //                     nav: true,
        //                     navText: [''],
        //                     dots: false,
        //                     loop: true,
        //                     cursor: 'pointer',
        //                     onInitialized: function() {
        //                         // carousel_main.find('.owl-item.active .product-thumb').eq(0).trigger('click');
        //                     }
        //                 });

        //                 // re-init thumbnail owl carousel
        //                 var carousel_thumb = $('.product-thumbs');

        //                 // destroy owl carousel
        //                 carousel_thumb.trigger('destroy.owl.carousel');

        //                 // re-initialize owl carousel
        //                 carousel_thumb.owlCarousel({
        //                     items: 4,
        //                     nav: true,
        //                     navText: [''],
        //                     dots: false,
        //                     loop: true,
        //                     cursor: 'pointer',
        //                     onInitialized: function() {
        //                         // carousel_thumb.find('.owl-item.active .product-thumb').eq(0).trigger('click');
        //                     },
        //                     onclick: function() {
        //                         console.log('clicked');
        //                     }
        //                 });

        //                 // re-init zoom
        //                 $('.woocommerce-product-gallery__image').trigger('zoom.destroy');

        //                 // re-init zoom
        //                 $('.woocommerce-product-gallery__image').zoom({
        //                     url: $('.woocommerce-product-gallery__image').find('img').attr('data-zoom-image'),
        //                 });


        //             }

        //             // photoswipe html
        //             if (i2 == 'photoswipe_html') {
        //                 var photoswipe_html = v2;

        //                 // console.log(JSON.parse(photoswipe_html));

        //                 // replace photoswipe html
        //                 $('.pswp').replaceWith(JSON.parse(photoswipe_html));

        //             }

        //             // product title
        //             if (i2 == '.product_title.entry-title.title') {
        //                 $('.product_title.entry-title.title').html(v2);
        //             }

        //             // price
        //             if (i2 == '.elementor-widget.elementor-widget-riode_sproduct_price > div > p') {
        //                 $('.elementor-widget.elementor-widget-riode_sproduct_price > div > p').html(v2);
        //             }

        //             // action
        //             if (i2 == 'action') {
        //                 $('form.cart').attr('action', v2);
        //             }

        //             // data-product_id
        //             if (i2 == 'data-product_id') {
        //                 $('form.cart').attr('data-product_id', v2);
        //             }

        //             // data-product_variations
        //             if (i2 == 'data-product_variations') {
        //                 $('form.cart').attr('data-product_variations', v2);
        //             }

        //             // gtm4wp_id
        //             if (i2 == 'input[name="gtm4wp_id"]') {
        //                 $('input[name="gtm4wp_id"]').val(v2);
        //             }

        //             // gtm4wp_internal_id
        //             if (i2 == 'input[name="gtm4wp_internal_id"]') {
        //                 $('input[name="gtm4wp_internal_id"]').val(v2);
        //             }

        //             // gtm4wp_name
        //             if (i2 == 'input[name="gtm4wp_name"]') {
        //                 $('input[name="gtm4wp_name"]').val(v2);
        //             }

        //             // gtm4wp_category
        //             if (i2 == 'input[name="gtm4wp_category"]') {
        //                 $('input[name="gtm4wp_category"]').val(v2);
        //             }

        //             // gtm4wp_price
        //             if (i2 == 'input[name="gtm4wp_price"]') {
        //                 $('input[name="gtm4wp_price"]').val(v2);
        //             }

        //             // add-to-cart
        //             if (i2 == 'input[name="add-to-cart"]') {
        //                 $('input[name="add-to-cart"]').val(v2);
        //             }

        //             // product_id
        //             if (i2 == 'input[name="product_id"]') {
        //                 $('input[name="product_id"]').val(v2);
        //             }


        //         });

        //     });

        // });


        // // show/pan/zoom .zoomImg on hover
        // $(document).on('mousemove', '.woocommerce-product-gallery__image', function(e) {

        //     // get .zoomImg
        //     var zoomImg = $(this).find('.zoomImg');

        //     // show .zoomImg
        //     zoomImg.css('opacity', '1');

        //     // get image dimensions
        //     var imgWidth = zoomImg.width();
        //     var imgHeight = zoomImg.height();

        //     // get container dimensions
        //     var containerWidth = $(this).width();
        //     var containerHeight = $(this).height();

        //     // calculate position based on mouse position
        //     var xPos = (e.pageX - $(this).offset().left) / containerWidth * (imgWidth - containerWidth);
        //     var yPos = (e.pageY - $(this).offset().top) / containerHeight * (imgHeight - containerHeight);

        //     // set position of .zoomImg
        //     zoomImg.css({
        //         'left': -xPos + 'px',
        //         'top': -yPos + 'px'
        //     });


        // });

        // // hide .zoomImg on mouseleave
        // $(document).on('mouseleave', '.woocommerce-product-gallery__image', function() {

        //     // get .zoomImg
        //     var zoomImg = $(this).find('.zoomImg');

        //     // hide .zoomImg
        //     zoomImg.css('opacity', '0');

        // });

        // $(document).click(function(e) {

        //     // if target has class of product-thumb active, get image srcset
        //     if ($(e.target).hasClass('product-thumb active')) {

        //         // get image srcset
        //         var img_srcset = $(e.target).find('.attachment-thumbnail').attr('srcset');

        //         if (typeof img_srcset === 'undefined') {
        //             return;
        //         }

        //         // split srcset into array
        //         var img_srcset_array = img_srcset.split(',');

        //         // get last item in array
        //         var img_srcset_last_item = img_srcset_array[img_srcset_array.length - 1];

        //         // extract url from last item in array
        //         var img_srcset_last_item_url = img_srcset_last_item.split(' ')[1];

        //         // get .zoomImg
        //         var zoomImg = $('.woocommerce-product-gallery__image').find('.zoomImg');

        //         // console.log(zoomImg);

        //         // set .zoomImg src
        //         zoomImg.attr('src', img_srcset_last_item_url);

        //         // replace .attachment-full .size-full src
        //         $('img.attachment-full.size-full').attr('src', '');

        //         // remove data-thumb from .woocommerce-product-gallery__image
        //         $('.woocommerce-product-gallery__image').removeAttr('data-thumb');

        //         var carousel_main = $('.product-single-carousel');

        //         // destroy owl carousel
        //         carousel_main.trigger('destroy.owl.carousel');

        //         // re-initialize owl carousel
        //         carousel_main.owlCarousel({
        //             items: 1,
        //             nav: true,
        //             dots: false,
        //             loop: true,
        //             navText: [''],
        //             thumbs: false,
        //             thumbImage: true,
        //             thumbContainerClass: 'owl-thumbs',
        //             thumbItemClass: 'owl-thumb-item',
        //             onInitialized: function() {
        //                 carousel_main.find('.owl-item.active .product-thumb').eq(0).trigger('click');
        //             }
        //         });


        //     }

        // });
    </script>

    <?php

});

/**
 * AJAX callback to fetch product single content for linked products
 */
add_action('wp_ajax_sbwc_ajax_load_linked_products', 'sbwc_ajax_load_linked_products');
add_action('wp_ajax_nopriv_sbwc_ajax_load_linked_products', 'sbwc_ajax_load_linked_products');

function sbwc_ajax_load_linked_products()
{

    // check nonce
    check_ajax_referer('sbwc_ajax_load_linked_products_nonce', '_ajax_nonce');

    // debug
    // wp_send_json($_POST);

    // retrieve product owl carousel gallery html
    try {

        if (!function_exists('wc_get_gallery_image_html')) {
            return;
        }

        // get linked product urls
        $linked_product_url = $_POST['linked_product_url'];

        // get product id from url
        $linked_product_id = url_to_postid($linked_product_url);

        // get product object
        $linked_product = wc_get_product($linked_product_id);

        // global $product
        global $product;

        // save original product
        $original_product = $product;

        // set product to linked product
        $product = $linked_product;

        // get product image gallery
        ob_start();

        if ($product) {
            wc_get_template_part('single-product');
        }

        do_action('riode_after_template');

        $layout = ob_get_clean();



        $test1 = wc_get_template_html('single-product');

        // encode
        // $layout = wp_json_encode($layout);

        // wp_send_json($layout);

        print $test1;

        wp_die();

        // ***************************************************************

        do_action('riode_single_product_before_image');

        $columns           = apply_filters('woocommerce_product_thumbnails_columns', 4);
        $post_thumbnail_id = $product->get_image_id();
        $wrapper_classes   = apply_filters(
            'woocommerce_single_product_image_gallery_classes',
            array(
                'woocommerce-product-gallery',
                'woocommerce-product-gallery--' . ($product->get_image_id() ? 'with-images' : 'without-images'),
                'woocommerce-product-gallery--columns-' . absint($columns),
                'images',
            )
        );
    ?>
        <div class="<?php echo esc_attr(implode(' ', array_map('sanitize_html_class', $wrapper_classes))); ?>" data-columns="<?php echo esc_attr($columns); ?>">

            <?php do_action('riode_before_wc_gallery_figure'); ?>

            <figure class="<?php echo esc_attr(implode(' ', apply_filters('riode_single_product_gallery_main_classes', array('woocommerce-product-gallery__wrapper')))); ?>">
                <?php
                do_action('riode_before_product_gallery');
                do_action('riode_woocommerce_product_images');
                do_action('woocommerce_product_thumbnails');
                do_action('riode_after_product_gallery');
                ?>
            </figure>

            <?php do_action('riode_after_wc_gallery_figure'); ?>

        </div>
        <?php

        // get gallery
        $new_owl =  ob_get_clean();

        // encode
        $new_owl = wp_json_encode($new_owl);

        // photoswipe html
        ob_start(); ?>

        <!-- photoswipe -->
        <div id="pbs_photoswipe_cont_<?php echo $linked_product_id; ?>" class="pswp pswp--supports-fs pswp--css_animation pswp--svg pswp--animated-in pswp--zoom-allowed pswp--has_mouse pbs_photoswipe_cont" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="pswp__bg"></div>
            <div class="pswp__scroll-wrap">
                <div class="pswp__container">
                    <div class="pswp__item"></div>
                    <div class="pswp__item"></div>
                    <div class="pswp__item"></div>
                </div>
                <div class="pswp__ui pswp__ui--hidden">
                    <div class="pswp__top-bar">
                        <div class="pswp__counter"></div>
                        <button class="pswp__button pswp__button--close" aria-label="Close (Esc)"></button>
                        <button class="pswp__button pswp__button--share" aria-label="Share"></button>
                        <button class="pswp__button pswp__button--fs" aria-label="Toggle fullscreen"></button>
                        <button class="pswp__button pswp__button--zoom" aria-label="Zoom in/out"></button>
                        <div class="pswp__preloader">
                            <div class="pswp__preloader__icn">
                                <div class="pswp__preloader__cut">
                                    <div class="pswp__preloader__donut"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
                        <div class="pswp__share-tooltip"></div>
                    </div>
                    <button class="pswp__button pswp__button--arrow--left" aria-label="Previous (arrow left)"></button>
                    <button class="pswp__button pswp__button--arrow--right" aria-label="Next (arrow right)"></button>
                    <div class="pswp__caption">
                        <div class="pswp__caption__center"></div>
                    </div>
                </div>
            </div>
        </div>

<?php

        // get photoswipe html
        $photoswipe_html = ob_get_clean();

        // encode
        $photoswipe_html = wp_json_encode($photoswipe_html);

        // reset global product object
        $product = $original_product;
    } catch (\Throwable $th) {
        wp_send_json_error('The following error occurred when trying to retrieve the product\'s image gallery (owl carousel): ' . $th->getMessage());
    }

    try {
        // get linked product urls
        $linked_product_url = $_POST['linked_product_url'];

        // get product id from url
        $linked_product_id = url_to_postid($linked_product_url);

        // if linked product id == 0, continue
        if ($linked_product_id == 0) :
            wp_send_json_error('Linked product id is 0. Please check that the product you are looking for actually exists.');
        endif;

        // get product object
        $linked_product = wc_get_product($linked_product_id);

        // if null or false, return error
        if (is_null($linked_product) || $linked_product == false) :
            wp_send_json_error('No product object found for linked product id: ' . $linked_product_id);
        endif;

        // get available variations and encode
        $variations_json = wp_json_encode($linked_product->get_available_variations());

        // variations attributes
        $variations_attr = function_exists('wc_esc_json') ? wc_esc_json($variations_json) : _wp_specialchars($variations_json, ENT_QUOTES, 'UTF-8', true);

        // get product main category
        $linked_product_main_category = get_term_by('id', $linked_product->get_category_ids()[0], 'product_cat');

        // replacement data array
        $replacement_data = array(
            $linked_product_url => array(
                'gallery'                                                           => $new_owl,
                'photoswipe_html'                                                   => $photoswipe_html,
                '.product_title.entry-title.title'                                  => $linked_product->get_title(),
                '.elementor-widget.elementor-widget-riode_sproduct_price > div > p' => $linked_product->get_price_html(),
                'action'                                                            => $linked_product_url,
                'data-product_id'                                                   => $linked_product_id,
                'data-product_variations'                                           => $variations_attr,
                'input[name="gtm4wp_id"]'                                           => $linked_product->get_sku(),
                'input[name="gtm4wp_internal_id"]'                                  => $linked_product->get_id(),
                'input[name="gtm4wp_name"]'                                         => $linked_product->get_name(),
                'input[name="gtm4wp_category"]'                                     => $linked_product_main_category->name,
                'input[name="gtm4wp_price"]'                                        => $linked_product->get_price(),
                'input[name="add-to-cart"]'                                         => $linked_product->get_id(),
                'input[name="product_id"]'                                          => $linked_product->get_id(),
            )
        );

        wp_send_json($replacement_data);
    } catch (\Throwable $th) {
        wp_send_json_error('The following error occurred when trying to retrieve the product\'s HTML: ' . $th->getMessage());
    }
}
