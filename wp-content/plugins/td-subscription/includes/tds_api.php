<?php

require_once('api/tds_leads_api.php');
require_once('api/tds_payment_api.php');

class tdsStripeClient {

    public static function getStripe() {

        global $wpdb;

        $tds_payment_stripe_results = $wpdb->get_results("SELECT * FROM tds_payment_stripe LIMIT 1", ARRAY_A );
        if ( count($tds_payment_stripe_results) ) {

            $tds_payment_stripe = $tds_payment_stripe_results[0];
            $is_active = boolval($tds_payment_stripe['is_active']);

            if ($is_active) {

                $in_testing = $tds_payment_stripe['is_sandbox'] ? 'sandbox_' : '';
                //$public_key = $tds_payment_stripe[$in_testing . 'public_key'];
                $secret_key = $tds_payment_stripe[$in_testing . 'secret_key'];

                // load stripe api
                require_once TDS_PATH . '/includes/vendor/stripe/init.php';

                return new \Stripe\StripeClient($secret_key);

            }

        }

        return false;

    }

}
