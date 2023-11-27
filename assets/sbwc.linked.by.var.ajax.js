(function ($) {

    // initial product id
    let init_product_id = $('#sbwc-linked-init-id').val();

    // get script from dom which contains variable name 'dataLayer_content' and extract value of variable
    let gtm_data_layer = JSON.parse($('script').filter(function () {
        return ($(this).text().indexOf('dataLayer_content') !== -1);
    }).eq(0).html().split('dataLayer_content = ').pop().split(';')[0]);

    // DEBUG
    // console.log(gtm_data_layer);

    // holds linked product html
    let linked_product_html = [];

    // loop through .aclass-clr and fetch product single content for each linked product, pushing href and html to array
    $('.navplugify').find('.imgclasssmall').each(function (i, e) {

        // get first a element
        let a = $(this).find('a').eq(0);

        // get href
        let href = a.attr('href');

        // push href to array
        linked_product_html.push(href);

    });

    // get current permalink
    let current_permalink = window.location.href;

    // get each .aclass-clr href attribute and append to array
    let linked_product_urls = [];

    // add current permalink to array
    linked_product_urls.push(current_permalink);

    $('.aclass-clr').each(function () {

        // if not in array, add to array
        if (linked_product_urls.indexOf($(this).attr('href')) === -1) {
            linked_product_urls.push($(this).attr('href'));
        }

    });

    // holds deferred objects for each AJAX request
    let deferreds = [];

    // href => replacement html data store
    let data_store = {};

    // loop through linked product urls and send ajax request to fetch product single content
    $.each(linked_product_urls, function (i, v) {

        // define deferred
        let deferred = $.Deferred();

        // send ajax request to fetch product single content
        $.post($('#sbwc-linked-aj-url').val(), {
            action: 'sbwc_ajax_fetch_linked_prods_html',
            _ajax_nonce: $('#sbwc-linked-aj-nonce').val(),
            linked_product_url: v
        }, function (data) {

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
    //     console.log('data_store');
    //     console.log(data_store);
    //     return;
    // });

    // ~~~~~~~~~~~~~~~~~~~~~~~~
    // linked swatch on click
    // ~~~~~~~~~~~~~~~~~~~~~~~~
    $('.navplugify').on('click', '.imgclasssmall, .imgclasssmallactive', function (e) {

        e.preventDefault();

        // get first a element
        let a = $(this).find('a').eq(0);

        // get href
        let href = a.attr('href');

        // DEBUG
        // console.log(data_store);

        if (!data_store.hasOwnProperty(href)) {
            console.error('SBWC Linked by Variation AJAX plugin: data_store does not yet contain href ' + href);
            return;
        }

        // check if data_store contains href before proceeding
        let linked_gtm_data_layer = JSON.parse(data_store[href]['gtm_data_layer']);

        // compare linked gtm data layer to current gtm data layer and replace empty values in linked gtm data layer with current gtm data layer values
        $.each(linked_gtm_data_layer, function (i, v) {

            // empty customerBillingEmailHash, country, customerTotalOrders, customerTotalOrderValue, event_id
            if (i == 'customerBillingEmailHash' || i == 'country' || i == 'customerTotalOrders' ||
                i == 'customerTotalOrderValue' || i == 'event_id') {
                linked_gtm_data_layer[i] = gtm_data_layer[i];
            }

            // if value is empty, replace with current gtm data layer value
            if (v == '') {
                linked_gtm_data_layer[i] = gtm_data_layer[i];
            }

        });

        // DEBUG
        // console.log('new dl:');
        // console.log(linked_gtm_data_layer);

        // DEBUG
        // console.log('current dl: ');
        // console.log(window.dataLayer);

        // empty current data layer (accessible via global variable dataLayer)
        window.dataLayer[0] = [];

        // set updated data layer
        window.dataLayer[0] = linked_gtm_data_layer;

        // DEBUG
        // console.log('updated data_store');
        // console.log(window.dataLayer);

        // remove old gtm.js script(s)
        let oldScripts = document.querySelectorAll("script[src^='//www.googletagmanager.com/gtm.js']");

        oldScripts.forEach(function (script) {
            script.remove();
        });

        // add new gtm.js script
        (function (w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(),
                event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s),
                dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src = '//www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', 'GTM-K58QDD');


        // remove disabled class from #size-select-prompt
        $('#size-select-prompt').removeClass('disabled');

        // set CSS
        $('.imgclasssmall, .imgclasssmallactive').css({
            'outline': 'none',
            'outline-offset': 'none',
            'cursor': 'pointer'
        });

        // if imgclasssmallactive does not have a.aclass-clr, append a.aclass-clr to .imgclasssmallactive with current href as href attribute
        if (!$('.imgclasssmallactive').find('a.aclass-clr').length) {
            $('.imgclasssmallactive').find('.child_class_plugify').append(
                '<a style="opacity:0;" class="aclass-clr" href="' + window.location.href + '"></a>');
            $('.imgclasssmallactive').prepend('<a style="opacity:0;" class="aclass-clr" href="' + window
                .location.href + '"></a>');
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
        let linked_products_html_encoded = data_store[href]['product_html'];

        // temp div
        let temp_div = $('<div></div>');

        // append decoded to temp div
        temp_div.append(linked_products_html_encoded);

        // DEBUG
        // console.log(temp_div.html());

        // get first .elementor-column elementor-col-50
        let elementor_column = temp_div.find('.elementor-column.elementor-col-50').eq(0);

        // find .product-single-carousel in elementor_column
        let product_single_carousel = elementor_column.find('.product-single-carousel').eq(0);

        // find .product-thumbs-wrap in elementor_column
        let product_thumbs_wrap = elementor_column.find('.product-thumbs-wrap').eq(0);

        // get second .elementor-column elementor-col-50
        let elementor_column_2 = temp_div.find('.elementor-column.elementor-col-50').eq(1);

        // find title in elementor_column_2
        let title = elementor_column_2.find('.elementor-widget-riode_sproduct_title').eq(0);

        // find rating in elementor_column_2
        let rating = elementor_column_2.find('.elementor-widget-riode_sproduct_rating').eq(0);

        // find price in elementor_column_2
        let price = elementor_column_2.find('.elementor-widget-riode_sproduct_price').eq(0);

        // get .cart 
        let cart_form = elementor_column_2.find('.cart').eq(0);

        // add to cart button
        let add_to_cart_button = cart_form.find('.single_add_to_cart_button').eq(0);

        // add disabled class to button
        add_to_cart_button.addClass('disabled wc-variation-selection-needed');

        // buy now button
        let buy_now_button = cart_form.find('.product-buy-now').eq(0);

        // add disabled class to button
        buy_now_button.addClass('disabled wc-variation-selection-needed');

        // get action attribute
        let action = cart_form.attr('action');

        // get product id
        let product_id = cart_form.attr('data-product_id');

        // get variation data
        let variation_data = cart_form.attr('data-product_variations');

        // retrieve gtm4wp hidden input data
        let gtm_hidden_inputs = cart_form.find('input[name^="gtm4wp_"]');

        // find add-to-cart hidden input value
        let add_to_cart = cart_form.find('input[name="add-to-cart"]').eq(0).val();

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
        let containers = $('*[class*="imgclasssmall"]');

        // Get the parent of the first .imgclasssmall element
        let parent = $('.navplugify > div');

        // Sort the .imgclasssmall elements based on the value of their child <a> element's href attribute
        containers.sort(function (a, b) {
            let aText = $(a).text();
            let bText = $(b).text();
            return aText.localeCompare(bText);
        });

        // Remove second (unused) product gallery html
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
        $('.product-thumbs').on('click', '.owl-item', function (e) {

            // get index
            let index = $(this).index();

            // set thumbnail active class
            $('.product-thumb').removeClass('active');
            $('.product-thumb').eq(index).addClass('active');

            // slide to index
            $('.product-single-carousel').trigger('to.owl.carousel', [index]);
        });

        // set product thumb active class on .product-single-carousel slide change
        $('.product-single-carousel').on('changed.owl.carousel', function (e) {

            // get index
            let index = e.item.index;

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
            onZoomIn: function () {
                $(this).parent().css('cursor', 'pointer');
            },
        });

        // ##################
        // REINIT PHOTOSWIPE
        // ##################
        // product image full button html
        let product_image_full_button_html = '<button class="product-image-full d-icon-zoom"></button>';

        // insert after .owl-stage-outer inside .product-single-carousel
        $('.product-single-carousel').find('.owl-stage-outer').after(product_image_full_button_html);

        // setup pswp html
        let pswp_html = data_store[href]['.pswp'];

        // delete current .pswp element
        $('.pswp').remove();

        // destroy photoswipe
        $('.product-image-full').off('click');

        // append pswp html to body
        $('body').append(pswp_html);

        // pswp element
        let pswpElement = document.querySelectorAll('.pswp')[0];

        // build items array
        let items = [];

        // loop through .woocommerce-product-gallery__image elements and push to items array
        product_single_carousel.find('.woocommerce-product-gallery__image').each(function (i, e) {

            // get first a element
            let first_a = $(this).find('a').eq(0);

            // get a > first img srcset
            let srcset = first_a.find('img').eq(0).attr('srcset') ? first_a.find('img').eq(0).attr('srcset') : first_a.find('img').eq(0).attr('data-srcset');

            // debug
            // console.log(srcset);

            // get last item in srcset
            let src = srcset.split(',').pop().trim().split(' ')[0];

            // get width and height from child .zoomImg css
            let width = 1400;
            let height = 1400;

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

        let options = {
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
        let gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);

        // init gallery on .product-image-full click
        $('.product-image-full').on('click', function (e) {

            // disable any previously attached click events
            e.stopPropagation();

            // gallery init
            gallery.init();
        });

        // remove class and/or any empty attributes named 'class' from .pa_size button elements
        $('.pa_size').find('button').each(function (e) {
            $(this).removeClass().removeAttr('class');
        });

        // size on click
        $('.pa_size').on('click mousedown', 'button', function (event) {

            // stop propagation
            event.stopPropagation();

            // prevent default
            event.preventDefault();

            // sort classes
            $(this).parent().find('button').removeClass().removeAttr('class');
            $(this).addClass('active');

            // get variation data from form and find matching variation id
            let variation_data = JSON.parse($('.cart').attr('data-product_variations'));

            $.each(variation_data, function (i, v) {

                let attributes = v.attributes;
                let attr_val = attributes.attribute_pa_size;

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