<?php

/**
 * Plugin Name: BlueCheck - Age Verification
 * Description: Verify customer age at checkout. Cut fraud with photo ID verification. Check purchaser age info.
 * Version:     1.0
 * Author:      BlueCheck
 * Author URI:  https://www.bluecheck.me
 * Requires at least: 6.6.2
 * Tested up to: 6.6.2
 * License:     GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// This can be changed to use the dev server, etc.
define('BLUECHECK_ROOT_URL', 'https://verify.bluecheck.me');

if (! class_exists('BlueCheck_WooCommerce_Plugin')) :

class BlueCheck_WooCommerce_Plugin
{
	/**
	* Construct the plugin.  This gets executed on every page load of the WordPress site.
	*/
	public function __construct() 
	{
		// 'plugins_loaded' is triggered on each page view after plugins are loaded.
		add_action('plugins_loaded', [$this, 'plugins_loaded']);
	}

	/**
	* Initialize the plugin.
	*/
	public function plugins_loaded()
	{
		// Checks if WooCommerce is installed.
		if (! class_exists('WC_Integration')) {
			// (We could report some kind of error here, but an exception )
			// throw new \Exception("WooCommerce must be installed to use this plugin.");
			return;
		}

		// Include our integration class.
		require_once 'woocommerce-bluecheck-integration.php';

		// Register the integration with WooCommerce.
		add_filter('woocommerce_integrations', [$this, 'woocommerce_integrations']);
	}

	/**
	 * Add a new integration to WooCommerce.
	 */
	public function woocommerce_integrations($integrations) 
	{
		$integrations[] = 'BlueCheck_Age_Verification_Integration';
		return $integrations;
	}
}

// Add product IDs to the Checkout page
add_action('woocommerce_before_checkout_form', 'bluecheck_product_ids_to_checkout');
function bluecheck_product_ids_to_checkout() {
    $array =[];
    $tags = [];
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $item = $cart_item['data'];
        if(!empty($item)) $array[] = $item->id;

        $terms = wp_get_post_terms($item->id, 'product_tag' );
        foreach ($terms as $term) {
            $tags[] = $term->name;
        }
    }
    echo '<span id="productIDs" style="display:none">'.esc_html(implode(",", $array)).'</span>';
    echo '<span id="BluecheckProductTags" style="display:none">'.esc_html(implode(",", $tags)).'</span>';
}

// This creates an instance of this class, which executes the constructor and initializes the plugin.
new BlueCheck_WooCommerce_Plugin();

endif;
