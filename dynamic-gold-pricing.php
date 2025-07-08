<?php
/*
 * Plugin Name: Dynamic Gold Jewelry Pricing Functions
 * Description: Calculates gold jewelry prices dynamically for WooCommerce products and variations based on karat, labor, origin, and weight.
 * Author: Your Name or Company
 * Version: 1.0.0
 * License: GPL2
 */

// ================================
// DYNAMIC GOLD JEWELRY PRICING CODE
// ================================

// 1. MAIN DYNAMIC PRICING LOGIC
function custom_gold_price_calculation($product_id) {
    global $wpdb;
    $product = wc_get_product($product_id);
    if (!$product) return 0;

    // Get gold karat (purity) and base price from admin options
    $product_purity = strtoupper(trim(get_post_meta($product_id, '_custom_product_karats', true)));
    $base_prices = array(
        '10K' => get_option('gold_price_10k', 0),
        '14K' => get_option('gold_price_14k', 0),
        '18K' => get_option('gold_price_18k', 0),
        '22K' => get_option('gold_price_22k', 0),
        '24K' => get_option('gold_price_24k', 0),
    );
    $base_price = isset($base_prices[$product_purity]) ? $base_prices[$product_purity] : 0;

    // Use the HIGHEST labor cost among ALL categories
    $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
    $labor_price = 0;
    foreach ($product_categories as $category_id) {
        $category_labor_cost = get_term_meta($category_id, 'labor_cost', true);
        if (!empty($category_labor_cost) && (float)$category_labor_cost > $labor_price) {
            $labor_price = (float)$category_labor_cost;
        }
    }

    // Product weight
    $product_weight = (float) $product->get_weight();

    // Origin adjustments (customizable)
    $origin_adjustments = array(
        'korean' => 2.0,
        'italian' => 5.0,
    );
    $product_origin = strtolower(trim(get_post_meta($product_id, '_custom_product_origin', true)));
    $origin_adjustment = isset($origin_adjustments[$product_origin]) ? $origin_adjustments[$product_origin] : 0;

    if ($product_weight <= 0) return 0;

    // Final price calculation
    $final_price = ($base_price + $labor_price + $origin_adjustment) * $product_weight;
    $final_price = round($final_price, 2);

    // Save price to database for parent/simple product
    $wpdb->delete(
        $wpdb->postmeta,
        array(
            'post_id' => $product_id,
            'meta_key' => '_sale_price'
        )
    );
    $wpdb->update(
        $wpdb->postmeta,
        array('meta_value' => $final_price),
        array(
            'post_id' => $product_id,
            'meta_key' => '_regular_price'
        )
    );
    $wpdb->update(
        $wpdb->postmeta,
        array('meta_value' => $final_price),
        array(
            'post_id' => $product_id,
            'meta_key' => '_price'
        )
    );

    // Handle all variations if variable product
    if ($product->is_type('variable')) {
        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation) continue;

            $variation_weight = (float) $variation->get_weight();
            if ($variation_weight <= 0) continue;

            // Karat/origin can be per-variation or fall back to parent
            $variation_purity = strtoupper(trim(get_post_meta($variation_id, '_custom_product_karats', true)));
            $v_purity = $variation_purity ? $variation_purity : $product_purity;
            $variation_base_price = isset($base_prices[$v_purity]) ? $base_prices[$v_purity] : $base_price;

            $variation_origin = strtolower(trim(get_post_meta($variation_id, '_custom_product_origin', true)));
            $v_origin = $variation_origin ? $variation_origin : $product_origin;
            $variation_origin_adjustment = isset($origin_adjustments[$v_origin]) ? $origin_adjustments[$v_origin] : $origin_adjustment;

            // Use parent labor price
            $variation_price = ($variation_base_price + $labor_price + $variation_origin_adjustment) * $variation_weight;
            $variation_price = round($variation_price, 2);

            // Save variation price to database
            $wpdb->delete(
                $wpdb->postmeta,
                array(
                    'post_id' => $variation_id,
                    'meta_key' => '_sale_price'
                )
            );
            $wpdb->update(
                $wpdb->postmeta,
                array('meta_value' => $variation_price),
                array(
                    'post_id' => $variation_id,
                    'meta_key' => '_regular_price'
                )
            );
            $wpdb->update(
                $wpdb->postmeta,
                array('meta_value' => $variation_price),
                array(
                    'post_id' => $variation_id,
                    'meta_key' => '_price'
                )
            );
        }
    }

    wc_delete_product_transients($product_id);

    return $final_price;
}

// 2. FRONTEND & ADMIN PRICE FILTERS (ENSURE CORRECT DISPLAY)
function apply_custom_gold_price_with_exclusion($price, $product) {
    if (!$product) return $price;
    $excluded_categories = array('diamond', 'luxury-watches');
    $product_id = $product->get_id();

    // Exclude specific categories (by slug)
    $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'slugs'));
    if (is_array($product_categories)) {
        foreach ($product_categories as $category) {
            if (in_array($category, $excluded_categories)) {
                return $price;
            }
        }
    }

    // Only calculate if basic data present
    $product_purity = strtoupper(trim(get_post_meta($product_id, '_custom_product_karats', true)));
    $product_weight = (float) $product->get_weight();
    if (empty($product_purity) || $product_weight <= 0) return $price;

    // For variations, calculate for that specific variation
    if ($product->is_type('variation')) {
        return custom_gold_price_calculation($product_id);
    }

    // For all other products
    $custom_price = custom_gold_price_calculation($product_id);
    return $custom_price > 0 ? $custom_price : $price;
}
add_filter('woocommerce_product_get_price', 'apply_custom_gold_price_with_exclusion', 999, 2);
add_filter('woocommerce_product_get_regular_price', 'apply_custom_gold_price_with_exclusion', 999, 2);
add_filter('woocommerce_product_variation_get_price', 'apply_custom_gold_price_with_exclusion', 999, 2);
add_filter('woocommerce_product_variation_get_regular_price', 'apply_custom_gold_price_with_exclusion', 999, 2);
add_filter('woocommerce_variation_prices_price', 'apply_custom_gold_price_with_exclusion', 999, 2);
add_filter('woocommerce_variation_prices_regular_price', 'apply_custom_gold_price_with_exclusion', 999, 2);

// 3. FORCE RECALCULATION ON PRODUCT SAVE
add_action('woocommerce_process_product_meta', function($post_id) {
    $product = wc_get_product($post_id);
    if ($product) custom_gold_price_calculation($post_id);
}, 20, 1);

// 4. BULK RECALCULATION TOOL FOR ADMIN
function recalculate_all_gold_product_prices() {
    global $wpdb;
    $products = $wpdb->get_col("
        SELECT post_id FROM $wpdb->postmeta 
        WHERE meta_key = '_custom_product_karats' 
        AND meta_value != ''
    ");
    $updated = 0;
    foreach ($products as $product_id) {
        custom_gold_price_calculation($product_id);
        $updated++;
    }
    WC_Cache_Helper::get_transient_version('product', true);
    return 'Updated ' . $updated . ' gold products with new calculated prices.';
}
function add_gold_price_recalculation_tool($tools) {
    $tools['recalculate_gold_prices'] = array(
        'name' => __('Recalculate Gold Product Prices', 'woocommerce'),
        'button' => __('Run Update', 'woocommerce'),
        'desc' => __('Recalculate all gold product prices based on current karat values, weights, and labor costs.', 'woocommerce'),
        'callback' => 'gold_price_recalculation_callback',
    );
    return $tools;
}
add_filter('woocommerce_debug_tools', 'add_gold_price_recalculation_tool');
function gold_price_recalculation_callback() {
    return recalculate_all_gold_product_prices();
}

// ================================
// END OF DYNAMIC GOLD JEWELRY PRICING CODE
// ================================
