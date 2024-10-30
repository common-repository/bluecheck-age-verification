<?php


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// This can be changed to use the dev server, etc.
define('BLUECHECK_ROOT_URL', 'https://verify.bluecheck.me');

if (! class_exists('BlueCheck_Age_Verification_Integration')) :

class BlueCheck_Age_Verification_Integration extends WC_Integration
{
    private $bluecheckDomainToken;

    /**
     * Init and hook in the integration.
     */
    public function __construct() 
    {
        $this->id                 = 'integration-bluecheck';
        $this->method_title       = __('BlueCheck Integration', 'bluecheck-age-verification-plugin');
        $this->method_description = __('BlueCheck integration to WooCommerce. Please note: This plugin requires a BlueCheck merchant account.  See https://www.bluecheck.me.', 'bluecheck-age-verification-plugin');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->bluecheckDomainToken = $this->get_option('bluecheck_api_key');

        // Actions.
        
        /**
         * woocommerce_after_checkout_validation
         * 
         * woocommerce_before_checkout_process
         * (checks for expired session)
         * woocommerce_checkout_process
         * woocommerce_checkout_order_processed
         * 
         */

        add_action('woocommerce_update_options_integration_' . $this->id, [$this, 'process_admin_options']);

        add_action('wp_enqueue_scripts', [$this, 'custom_enqueue_checkout_script']);
        add_action('wp_head', [$this, 'custom_add_noscript_tag']);

        add_action('woocommerce_thankyou', [$this, 'secondary_check']);
        add_filter('woocommerce_integration_settings', [$this, 'sanitize_settings']);
    }

    /**
     * Initialize integration settings form fields.
     */
    public function init_form_fields() 
    {
        $this->form_fields = [
            'bluecheck_api_key' => [
                'title'             => __('BlueCheck Domain Token', 'bluecheck-age-verification-plugin'),
                'type'              => 'text',
                'description'       => __('Enter with your BlueCheck Domain Token. You can find it in the BlueCheck merchant portal.', 'bluecheck-age-verification-plugin'),
                'desc_tip'          => true,
                'default'           => '',
                'validation'        => []
            ]
        ];
    }

    public function sanitize_settings($fields) 
    {
        // The only thing we do right now to sanitize it is trim spaces.
        if (isset($settings['bluecheck_api_key'])) {
            $settings['bluecheck_api_key'] = trim($settings['bluecheck_api_key']);
        }

        return $settings;
    }

    public function secondary_check($orderID)
    {
        if (! $orderID) return;

        $url = BLUECHECK_ROOT_URL . '/platforms/woocommerce/webhooks/secondary-check';

        $order = wc_get_order($orderID);

        $info = ['first_name', 'last_name', 'address_1', 'address_2', 'city', 'state', 'country', 'postcode'];

        // shipping doesn't have an email, so let's use the billing email
        $addressData = [
            "billing_email" => $order->get_billing_email(),
            "shipping_email" => $order->get_billing_email(),
        ];
        foreach ($info as $item) {
            $addressData["billing_$item"] = $order->{"get_billing_$item"}();
            $addressData["shipping_$item"] = $order->{"get_shipping_$item"}();
        }

        $domainToken = $this->bluecheckDomainToken;

        $productIDs =[];
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$item = $cart_item['data'];
			if(!empty($item)) $productIDs[] = $item->id;
		}

        $response = wp_remote_post($url, [
            'method' => 'POST',
            'timeout' => 120,
            'httpversion' => '1.1',
            'body' => ['domainToken' => $domainToken, 'theirOrderID' => $orderID, 'addressData' => $addressData]
        ]);

        if (is_wp_error($response) || ! isset($response['body'])) {
            return false;
        }

        $result = json_decode($response['body']);
        if ($result && $result->result == 'valid') return '';

        // if we don't get a result, we can't assume the order was verified
        // if it returns anything other than valid, order is not verified
        $order->update_status('wc-failed', 'BlueCheck was unable to verify this order.', false);
        return '';
    }

    /**
     * This adds Bluecheck's special fields to the checkout form.
     */
    public function custom_enqueue_checkout_script($checkout)
    {
        if (is_checkout()) {
            wp_enqueue_script(
                'bluecheck-age-verification-script',
                BLUECHECK_ROOT_URL."/platforms/woocommerce/js/AgeVerification.js?domain_token=".$this->bluecheckDomainToken,
                array(),
                '1.0.0',
                array(
                    'strategy'  => 'defer',
                )
            );
        }
    }

    /**
     * @return void
     */
    public function custom_add_noscript_tag() {
        if (is_checkout()) {
            echo '<noscript><meta http-equiv="refresh" content="0;url='. esc_url(BLUECHECK_ROOT_URL) .'/noscript"></noscript>';
        }
    }
}

endif;
