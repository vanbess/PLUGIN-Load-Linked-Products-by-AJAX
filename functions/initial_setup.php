<?php

defined('ABSPATH') or die();

// global $linked_products_ajax_variations, $linked_product_id;
global $linked_products_ajax_variations, $linked_product_id_initial;

// get product id
$linked_product_id_initial = get_the_ID();

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
    if (in_array($linked_product_id_initial, $linked_products_settings_applied_on_id)) :
        $linked_product_ids = $linked_products_settings_applied_on_id;
        break;
    endif;

endforeach;

// if current product id not in applied_on_ids column for some reason, append current product id to linked product ids array
if (!in_array($linked_product_id_initial, $linked_product_ids)) :
    $linked_product_ids[] = $linked_product_id_initial;
endif;

// available variations array which holds all available variations for all linked products
$linked_products_ajax_variations = array();

// loop through linked product ids and get available variations for each
foreach ($linked_product_ids as $linked_product_id) :

    // get product object
    $product = wc_get_product($linked_product_id);

    // get available variations
    $product_available_variations = $product->get_available_variations();

    // loop through available variations and add to available variations array
    foreach ($product_available_variations as $product_available_variation) :

        // add to available variations array
        $linked_products_ajax_variations[$product->get_permalink()][] = $product_available_variation;

    endforeach;

endforeach;

// debug: output available variations to file
// file_put_contents(sbwc_ajax_fetch_linked_prods_html_PATH . 'available-variations-all-linked.txt', print_r($available_variations, true));
