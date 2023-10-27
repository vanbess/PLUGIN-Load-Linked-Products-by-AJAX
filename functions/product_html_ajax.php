<?php

use Elementor\Core\Logger\Items\PHP;

defined('ABSPATH') or die();

/**
 * AJAX callback to fetch product single content for linked products
 */
add_action('wp_ajax_sbwc_ajax_fetch_linked_prods_html', 'sbwc_ajax_fetch_linked_prods_html',  PHP_INT_MAX);
add_action('wp_ajax_nopriv_sbwc_ajax_fetch_linked_prods_html', 'sbwc_ajax_fetch_linked_prods_html', PHP_INT_MAX);

function sbwc_ajax_fetch_linked_prods_html()
{

    // check nonce
    check_ajax_referer('sbwc_ajax_fetch_linked_prods_html_nonce', '_ajax_nonce');

    // DEBUG
    // wp_send_json($_POST);

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
    $dom->loadHTML($linked_product_html);

    // get all scripts
    $scripts = $dom->getElementsByTagName('script');

    // init vars
    $gtm_main       = '';
    $gtm_data_layer = '';

    // loop through scripts and fetch gtm scripts
    foreach ($scripts as $script) :

        // if script id is sbwc-linked-ajax-load-js, skip
        if ($script->getAttribute('id') == 'sbwc-linked-ajax-load-js') :
            continue;
        endif;

        // gtm main
        if (strpos($script->nodeValue, 'gtm4wp_datalayer_name') !== false) :
            $gtm_main = $script->nodeValue;
        endif;

        // gtm data layer
        if (strpos($script->nodeValue, 'dataLayer_content') !== false) :
            $gtm_data_layer = $script->nodeValue;
        endif;

    endforeach;

    // strip any text before and after curly braces in gtm data layer
    $gtm_data_layer = preg_replace('/^.*?({.*}).*$/s', '$1', $gtm_data_layer);

    // get product container
    $product_container = $dom->getElementById($target_id);

    // product html
    $product_html = $product_container->ownerDocument->saveHTML($product_container);

    // get element with CSS class .pswp
    $pswp = new DOMXPath($dom);

    // get pswp container
    $pswp_container = $pswp->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' pswp ')]");

    // return array
    $return['.pswp']          = $pswp_container->item(0)->ownerDocument->saveHTML($pswp_container->item(0));
    $return['product_html']   = $product_html;
    $return['gtm_main']       = $gtm_main;
    $return['gtm_data_layer'] = $gtm_data_layer;

    wp_send_json($return);
}
