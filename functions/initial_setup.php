<?php

defined('ABSPATH') or die();

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

?>

<input type="hidden" id="sbwc-linked-init-id" value="<?php echo $linked_product_id_initial; ?>">

<input type="hidden" id="sbwc-linked-aj-url" value="<?php echo admin_url('admin-ajax.php'); ?>">

<input type="hidden" id="sbwc-linked-aj-nonce" value="<?php echo wp_create_nonce('sbwc_ajax_fetch_linked_prods_html_nonce'); ?>">
