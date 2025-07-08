# dynamic-gold-pricing
# Dynamic Gold Jewelry Pricing for WooCommerce

This file provides automatic dynamic pricing for gold jewelry products in WooCommerce based on gold karat, labor cost, origin adjustment, and product/variation weight.

## Features

- Calculates and updates prices for all jewelry products and variations.
- Uses highest labor cost found among all product categories.
- Supports per-product or per-variation karat and origin adjustments.
- Bulk recalculation tool in WooCommerce admin.
- Excludes specific categories (diamond, luxury-watches) from calculation.

## How To Use

1. **Set gold prices** per karat in `WooCommerce > Gold Karat Prices`.
2. **Add/verify labor cost** in each product category and subcategory.
3. **Set weight** for each product or variation.
4. (Optional) **Set origin** in the custom field (`_custom_product_origin`).
5. The system will auto-calculate and update prices:
    - On product save
    - Via the bulk admin tool (**WooCommerce > Status > Tools > Recalculate Gold Product Prices**)

## Installation

- Copy `dynamic-gold-pricing.php` into your theme or plugin directory.
- Include it in your theme’s `functions.php` or your plugin:
    ```php
    require_once get_stylesheet_directory() . '/dynamic-gold-pricing.php';
    ```
- Or add it as a snippet using the Code Snippets plugin (**without the opening `<?php` tag**).

## Troubleshooting

- Ensure all variations have weights set.
- Ensure karat prices are up-to-date in admin.
- Labor cost must be set for each relevant category.
- For excluded categories, edit the `$excluded_categories` array in the code.

---

**Questions?**  
Create an issue or pull request for updates.
