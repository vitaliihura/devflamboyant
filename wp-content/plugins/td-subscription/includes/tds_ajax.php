<?php

add_action( 'rest_api_init', function () {

	register_rest_route( 'tds_preview', '/do_job/', array(
		'methods'  => 'POST',
		'callback' => array ( 'tds_ajax', 'on_ajax_render_preview_shortcode' ),
		'permission_callback' => function() {
			return current_user_can('edit_posts' );
		}
	));

	register_rest_route( 'tds-api', '/tds-proxy/', array(
		'methods'  => 'POST',
		'callback' => array ( 'tds_ajax', 'on_ajax_tds_proxy' ),
		'permission_callback' => function() {
			return current_user_can('edit_posts' );
		}
	));

    /**
     * Local subscriptions endpoints
     */
	register_rest_route( 'tds_subscription', '/create_subscription/', array(
		'methods'  => 'POST',
		'callback' => array ( 'tds_ajax', 'on_ajax_create_subscription' ),
		'permission_callback' => function() {
			return is_user_logged_in();
		},
	));

	register_rest_route( 'tds_subscription', '/cancel_subscription/', array(
		'methods'  => 'POST',
		'callback' => array ( 'tds_ajax', 'on_ajax_cancel_subscription' ),
		'permission_callback' => function() {
			return is_user_logged_in();
		}
	));

    register_rest_route( 'tds_subscription', '/apply_coupon/', array(
        'methods'  => 'POST',
        'callback' => array ( 'tds_ajax', 'on_ajax_apply_coupon' ),
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));

	register_rest_route( 'tds_subscription', '/update_paypal_subscription/', array(
		'methods'  => 'POST',
		'callback' => array ( 'tds_ajax', 'on_ajax_update_paypal_subscription' ),
		'permission_callback' => function() {
			return is_user_logged_in();
		}
	));

    /**
     * Stripe endpoints
     */
	register_rest_route( 'tds_stripe', '/webhook/', array(
		'methods'  => 'POST',
		'callback' => function ($data) {

			require_once TDS_PATH . '/includes/vendor/stripe/init.php';

			global $wpdb;
            $tds_payment_stripe_results = $wpdb->get_results("SELECT * FROM tds_payment_stripe LIMIT 1", ARRAY_A);

			$is_testing = '';
			if ( !empty( $tds_payment_stripe_results[0]['is_sandbox'] ) ) {
				$is_testing = 'sandbox_';
			}

			\Stripe\Stripe::setApiKey( $tds_payment_stripe_results[0][$is_testing . 'secret_key'] );

			// endpoint's unique secret
			$endpoint_secret = $tds_payment_stripe_results[0]['webhook_endpoint_secret'];

			$payload = @file_get_contents('php://input');
			$event = null;

			try {
				$event = \Stripe\Event::constructFrom(
					json_decode( $payload, true )
				);
			} catch( \UnexpectedValueException $e ) {
				// Invalid payload
				echo '⚠️  Webhook error while parsing basic request.';
				http_response_code(400);
				exit();
			}

			if ( $endpoint_secret ) {

				// Only verify the event if there is an endpoint secret defined
				// Otherwise use the basic decoded event
				$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
				try {
					$event = \Stripe\Webhook::constructEvent(
						$payload, $sig_header, $endpoint_secret
					);
				} catch( \Stripe\Exception\SignatureVerificationException $e ) {
					// Invalid signature
					echo '⚠️  Webhook error while validating signature.';
					http_response_code(400);
					exit();
				}

			}

			//print_r($event);
			//exit();

            // stripe api init
            $stripe_client = new \Stripe\StripeClient( $tds_payment_stripe_results[0][$is_testing . 'secret_key'] );

			// Handle the event
			switch ( $event->type ) {
				case 'customer.subscription.created':
				case 'customer.subscription.updated':

					$stripe_subscription_obj = $event->data->object;
					$stripe_subscription_id = $stripe_subscription_obj->id;
					//error_log($event->type . ': ' . $stripe_subscription_id );

					// subscription update
					if ( $event->type === 'customer.subscription.updated' ) {

						// subscription canceled
						$stripe_subscription_canceled_at = $stripe_subscription_obj->canceled_at;

						// subscription set to cancel at period end
						$stripe_subscription_cancel_at_period_end = $stripe_subscription_obj->cancel_at_period_end;

						// if subscription was canceled
						if ( !empty($stripe_subscription_canceled_at) ) {

							// subscription update where
							$where = array(
								'stripe_subscription_id' => $stripe_subscription_id,
							);

							// subscription update where format
							$where_format = array( '%s' );

							// && was not set to cancel at period end also cancel the local subscription
							if ( !$stripe_subscription_cancel_at_period_end ) {

								// subscription update data
								$data = array(
									'canceled' => '1',
									'status' => 'closed',
									'stripe_payment_status' => 'subscription_canceled' // update stripe status
								);

								// subscription update data format
								$data_format = array( '%s', '%s', '%s' );

							} else {

								// subscription update data
								$data = array(
									'stripe_payment_status' => 'subscription_cancel_at_period_end' // update stripe status
								);

								// subscription update data format
								$data_format = array( '%s' );

							}

							// subscription update
							$update_result = $wpdb->update( 'tds_subscriptions', $data, $where, $data_format, $where_format );

							//if ( false !== $update_result ) {
								//error_log('Local subscription updated! > subscription canceled due to an canceled_at subscription att. stripe subs ID: ' . $stripe_subscription_id );
							//}

						}

						$stripe_subscription_status = $stripe_subscription_obj->status;

						// if an incomplete_expired status is received cancel the local subscription
						if ( $stripe_subscription_status === 'incomplete_expired' ) {

							// subscription update
							$update_result = $wpdb->update('tds_subscriptions',
								array(
									'canceled' => '1',
									'status' => 'closed',
									'stripe_payment_status' => 'incomplete_expired' // update stripe status
								),
								array( 'stripe_subscription_id' => $stripe_subscription_id ),
								array( '%s', '%s', '%s' ),
								array( '%s' )
							);

							//if ( false !== $update_result ) {
								//error_log('Local subscription updated! > subscription canceled due to an incomplete_expired status. stripe subs ID: ' . $stripe_subscription_id );
							//}

						}

						// if an active or past_due status is received check if it's a trial end
						if ( $stripe_subscription_status === 'active' || $stripe_subscription_status === 'past_due' ) {

							// previous attributes data previous_attributes
							$stripe_subscription_pa = !empty( $event->data->previous_attributes ) ? $event->data->previous_attributes : null;
							$stripe_subscription_pa_status = !empty($stripe_subscription_pa->status) ? $stripe_subscription_pa->status : '';

                            // default_payment_method
                            $stripe_subscription_default_payment_method = $event->data->object->default_payment_method;

							if ( $stripe_subscription_pa_status === 'trialing' ) {

								// subscription update
								$update_result = $wpdb->update('tds_subscriptions',
									array(
                                        'stripe_payment_method' => $stripe_subscription_default_payment_method,
                                        'stripe_payment_status' => 'subscription_update - trial_end'
                                    ),
									array( 'stripe_subscription_id' => $stripe_subscription_id ),
									array( '%s', '%s' ),
									array( '%s' )
								);

								//if ( false !== $update_result ) {
									//error_log('Local subscription updated! > stripe_payment_status set to subscription_update - trial_end. stripe subs ID: ' . $stripe_subscription_id );
								//}
							}

						}

					}

					// subscription created
					if ( $event->type === 'customer.subscription.created' ) {

						// subscription status
						$status = $stripe_subscription_obj->status;

						// if subscription was created with trial period
						if ( !empty( $status ) && $status === 'trialing' ) {

							// subscription update
							$update_result = $wpdb->update( 'tds_subscriptions',
								array(
									'stripe_payment_status' => 'subscription_create - trialing',
								),
								array( 'stripe_subscription_id' => $stripe_subscription_id ),
								array( '%s' ),
								array( '%s' )
							);

							//if ( false !== $update_result ) {
								//error_log('Local subscription updated! > subscription stripe status set to trailing.' );
							//}

						}

					}

					break;
				case 'customer.subscription.deleted':
					$stripe_subscription_obj = $event->data->object;
					$stripe_subscription_id = $stripe_subscription_obj->id;

					// subscription update data
					$data = array(
						'stripe_payment_status' => 'subscription_deleted',
						'status' => 'closed'
					);

					// subscription update data format
					$data_format = array(
						'%s',
						'%s'
					);

					// subscription update where
					$where = array( 'stripe_subscription_id' => $stripe_subscription_id );

					// subscription update where format
					$where_format = array( '%s' );

					// subscription update
					$update_result = $wpdb->update(
						'tds_subscriptions',
						$data,
						$where,
						$data_format,
						$where_format
					);

					//if ( false !== $update_result ) {
						//error_log('Local subscription updated! > customer.subscription.deleted: ' . $stripe_subscription_id );
					//}

					break;
				case 'invoice.upcoming':
				case 'invoice.created':
				case 'invoice.updated':
				case 'invoice.paid':
				case 'invoice.payment_succeeded':
				case 'invoice.payment_failed':

					// invoice event types
					/*
					invoice.created
					invoice.finalization_failed
					invoice.finalized
					invoice.paid
					invoice.payment_failed
					invoice.payment_succeeded
					invoice.upcoming
					invoice.updated
					*/

					$stripe_invoice_data = $event->data->object;

					// previous attributes data previous_attributes
					$invoice_pa = !empty($event->data->previous_attributes) ? $event->data->previous_attributes : null;

					$stripe_subscription_id = $stripe_invoice_data->subscription;
					$stripe_customer_id = $stripe_invoice_data->customer;

					$billing_reason = $stripe_invoice_data->billing_reason; // subscription_create or subscription_cycle
					$invoice_status = $stripe_invoice_data->status; // the status of the invoice, one of draft, open, paid, uncollectible, or void
					$invoice_status_transitions = $stripe_invoice_data->status; // the timestamps at which the invoice status was updated

					$stripe_payment_status = $billing_reason . ' - ' . $event->type;

                    if ( in_array( $billing_reason, array( 'subscription_create', 'subscription_update', 'subscription_cycle' ) ) ) {
                        $subscription = tds_util::get_subscription_stripe($stripe_subscription_id);
                    } elseif ( $billing_reason === 'manual' ) {
                        $subscription = tds_util::get_subscription_stripe($stripe_invoice_data->id);
                    }

                    if ( empty($subscription) ) {
                        break;
                    }

                    $plan = tds_util::get_plan($subscription['plan_id']);
                    $subscription_price = tds_util::get_basic_currency($subscription['price']);
                    $subscription_end_date = empty($subscription['is_free']) && empty($subscription['is_unlimited']) ? tds_util::get_subscription_end_date( date('Y-m-d'), $subscription['cycle_interval'], $subscription['cycle_interval_count'] )->format('Y-m-d') : __td('unlimited', TD_THEME_NAME );

                    $add_email_tags = array('%subscription_name%', '%subscription_price%');
                    $add_email_tags_replacements = array($plan['name'], $subscription_price);

                    // set subscription user id
                    $subscription_user_id = $subscription['user_id'];

					// get stripe subscription
					$stripe_subscription = null;
					if ( !empty($stripe_subscription_id) ) {

                        try {
                            $stripe_subscription = $stripe_client->subscriptions->retrieve( $stripe_subscription_id );
                            //error_log('stripe subscriptions->retrieve OK.' );
                        } catch ( Exception $ex ) {
                            //error_log('stripe subscriptions->retrieve ERROR. error_code: ' . $ex->getCode() . ' error_msg: ' . $ex->getMessage() );
                        }

					}

					if ( $stripe_subscription ) {
						$stripe_subscription_status = $stripe_subscription['status'];

						// if trialing, don't update stripe_payment_status
						if ( $stripe_subscription_status === 'trialing' ) {
							$stripe_payment_status = '';
						}

					}

					// subscription update data
					$data = array();

					// subscription update data format
					$data_format = array();

					// subscription update where
					$where = array(
						'stripe_customer_id' => $stripe_customer_id,
						'stripe_subscription_id' => $billing_reason === 'manual' ? $stripe_invoice_data->id : $stripe_subscription_id,
					);

					// subscription update where format
					$where_format = array(
						'%s',
						'%s',
					);

					// process event types
					if ( $event->type === 'invoice.payment_succeeded' ) {

						// if payment was successful for a recurring subscription or a manual invoice set subscription status to active
						if ( in_array( $billing_reason, array( 'subscription_cycle', 'subscription_update', 'manual' ) ) ) {
							$data['status'] = 'active';
							$data_format[] = '%s';

                            // update payment intent
                            $stripe_payment_intent = $stripe_invoice_data->payment_intent;
                            $data['stripe_payment_intent'] = $stripe_payment_intent;
                            $data_format[] = '%s';

                            // get & update used payment method
                            if ( !empty($stripe_payment_intent) ) {

                                $stripe_payment_intent_data = $stripe_payment_intent_err = '';

                                try {

                                    // get stripe subscription payment intent data
                                    $stripe_payment_intent_data = $stripe_client->paymentIntents->retrieve($stripe_payment_intent);

                                } catch ( Exception $ex ) {
                                    $stripe_payment_intent_err = $ex->getMessage();
                                }

                                if ( !empty($stripe_payment_intent_data) ) {

                                    // get payment method id
                                    $payment_method = $stripe_payment_intent_data->payment_method;

                                    if ( !empty($payment_method) ) {

                                        $payment_method_data = $payment_method_err = '';

                                        try {

                                            // get stripe subscription payment method data
                                            $payment_method_data = $stripe_client->paymentMethods->retrieve($payment_method);

                                        } catch ( Exception $ex ) {
                                            $payment_method_err = $ex->getMessage();
                                        }

                                        if ( !empty($payment_method_data) ) {

                                            $data['stripe_payment_method'] = json_encode($payment_method_data);
                                            $data_format[] = '%s';

                                        } else {
                                            error_log( $payment_method_err );
                                        }

                                    }


                                } else {
                                    error_log( $stripe_payment_intent_err );
                                }

                            }

							$where['status'] = 'waiting_payment';
							$where_format[] = '%s';

							// send an email notification to the user and admins
							$add_email_tags[] = '%subscription_expiry%';
							$add_email_tags_replacements[] = $subscription_end_date;

							tds_email_notifications::send_user_email_notification('renewal', $subscription_user_id, $add_email_tags, $add_email_tags_replacements);
							tds_email_notifications::send_admin_email_notification('renewal', $subscription_user_id, $add_email_tags, $add_email_tags_replacements);

						} elseif( $billing_reason === 'subscription_create' ) {
							// do nothing on subscription create event
						}

					} else if ( $event->type === 'invoice.payment_failed' ) {

                        // update payment intent
                        $data['stripe_payment_intent'] = $stripe_invoice_data->payment_intent;
                        $data_format[] = '%s';

                        // send an email notification to the user and admins
                        tds_email_notifications::send_user_email_notification('failed', $subscription_user_id, $add_email_tags, $add_email_tags_replacements);
                        tds_email_notifications::send_admin_email_notification('failed', $subscription_user_id, $add_email_tags, $add_email_tags_replacements);

					} else if ( $event->type === 'invoice.updated' ) {

						// if invoice status changed
                        $invoice_pa_status = !empty($invoice_pa->status) ? $invoice_pa->status : '';
                        if ( !empty($invoice_pa_status) ) {
                            // update stripe status && add current invoice status
                            if ( !empty( $stripe_payment_status ) ) {
                                $stripe_payment_status .= ' - ' . $invoice_status;
                            }
                        }

					}

					// update stripe status
					if ( !empty( $stripe_payment_status ) ) {
						$data['stripe_payment_status'] = $stripe_payment_status;
						$data_format[] = '%s';
					}

					// update invoice details
					$invoice_url = $stripe_invoice_data->hosted_invoice_url ?? '';
					$invoice_pdf = $stripe_invoice_data->invoice_pdf ?? '';
					if ( !empty($invoice_url) || !empty($invoice_pdf) ) {
						$invoice_details = array(
							'invoice_url' => $stripe_invoice_data->hosted_invoice_url ?? '',
							'invoice_pdf' => $stripe_invoice_data->invoice_pdf ?? '',
							'next_payment_attempt' => $stripe_invoice_data->next_payment_attempt ?? '',
						);

                        // for failed payments, add the next payment attempt ts to invoice details
                        //if ( $event->type === 'invoice.payment_failed' ) {
                        //    $invoice_details['next_payment_attempt'] = $stripe_invoice_data->next_payment_attempt ?? '';
                        //}

						$data['stripe_invoice_details'] = json_encode($invoice_details);
						$data_format[] = '%s';
					}

					// if we have no data to update stop here
					if ( empty($data) ) {
						//error_log($event->type .  ' - no data to update: ' . $stripe_subscription_id );
						break;
					}

					// subscription update
					$update_result = $wpdb->update('tds_subscriptions', $data, $where, $data_format, $where_format );

					//if ( false !== $update_result ) {
						//error_log('Local subscription updated OK! - ' . $event->type .  ': ' . $stripe_subscription_id );
					//} else {
						//error_log( 'Local subscription updated failed! - ' . $event->type );
						//var_dump( $wpdb->last_error );
						//var_dump( $wpdb->last_query );
					//}

					break;

                //case 'setup_intent.requires_action':
				case 'setup_intent.succeeded':

                    $setup_intent_data = $event->data->object;

                    // set customer, payment_method from setup intent data
                    $stripe_customer_id = $setup_intent_data->customer;
                    $payment_method_id = $setup_intent_data->payment_method;

                    // set customer, subscription from setup intent metadata
                    //$stripe_customer_id = $setup_intent_data->metadata->stripe_customer_id;
                    //$subscription_local_id = $setup_intent_data->metadata->subscription_local_id;
                    $stripe_subscription_id = $setup_intent_data->metadata->stripe_subscription_id ?? '';

                    try {

                        // set new payment method as default pm for subscription
                        if ( !empty($payment_method_id) ) {

                            if ( $stripe_subscription_id ) {

                                // set setup payment method to a subscription
                                $stripe_client->subscriptions->update(
                                    $stripe_subscription_id,
                                    [ 'default_payment_method' => $payment_method_id ]
                                );

                            } else {

                                // set setup payment method to customer
                                $stripe_client->customers->update(
                                    $stripe_customer_id,
                                    [ 'invoice_settings' => [ 'default_payment_method' => $payment_method_id ] ]
                                );

                            }

                        }

                    } catch ( Exception $ex ) {
                        error_log( 'stripe:setup_intent.succeeded ERROR. error_msg: ' . $ex->getMessage() );
                    }

                    break;

                case 'checkout.session.completed':

                    $session_data = $event->data->object;
                    $session_metadata = $session_data->metadata;

                    // session data context
                    $session_context = $session_metadata->context ?? '';

                    // session context check, go further only if in subscription context
                    if ( empty($session_context) || $session_context !== 'subscription' ) {
                        error_log('empty session data context or context is not subscription');
                        break;
                    }

                    // session data
                    $session_mode = $session_data->mode;
                    $session_id = $session_data->id;
                    $stripe_subscription_id = $session_data->subscription;
                    $stripe_customer_id = $session_data->customer;
                    $stripe_invoice_id = $session_data->invoice;
                    $stripe_payment_status = $session_data->payment_status;
                    $stripe_payment_method_types = $session_data->payment_method_types;

                    // subscription data
                    $subscription_user_id = $session_metadata->user_id;
                    $subscription_plan_id = $session_metadata->plan_id;
                    $subscription_coupon_id = !empty($session_metadata->coupon_id) ? $session_metadata->coupon_id : '';
                    $stripe_price_id = !empty($session_metadata->stripe_price_id) ? $session_metadata->stripe_price_id : '';

                    // billing data
                    $subscription_billing_data = [
                        'billing_first_name' => $session_metadata->billing_first_name,
                        'billing_last_name' => $session_metadata->billing_last_name,
                        'billing_company_name' => $session_metadata->billing_company_name,
                        'billing_vat_number' => $session_metadata->billing_vat_number,
                        'billing_country' => $session_metadata->billing_country,
                        'billing_address' => $session_metadata->billing_address,
                        'billing_city' => $session_metadata->billing_city,
                        'billing_county' => $session_metadata->billing_county,
                        'billing_post_code' => $session_metadata->billing_postcode,
                        'billing_phone' => $session_metadata->billing_phone,
                        'billing_email' => $session_metadata->billing_email,
                    ];

                    $subscription_data = [

                        // session data
                        'session_mode' => $session_mode,
                        'session_id' => $session_id,
                        'stripe_subscription_id' => $stripe_subscription_id,
                        'stripe_customer_id' => $stripe_customer_id,
                        'stripe_invoice_id' => $stripe_invoice_id,
                        'payment_status' => $stripe_payment_status,
                        'payment_method_types' => $stripe_payment_method_types,

                        // subscription data
                        'user_id' => $subscription_user_id,
                        'plan_id' => $subscription_plan_id,
                        'coupon_id' => $subscription_coupon_id,
                        'price_id' => $stripe_price_id,

                        // billing data
                        'billing_data' => $subscription_billing_data

                    ];

                    // create stripe subscription
                    $create_subscription_results = tds_ajax::create_stripe_subscription($subscription_data);

                    // td log results
                    td_log::log(
                        __FILE__,
                        $event->type,
                        'create stripe subscription results',
                        [
                            'create_subscription_results' => $create_subscription_results,
                            'subscription_data' => $subscription_data
                        ]
                    );

                    break;

				default:
					// unexpected event type
					error_log('Received unknown event type: ' . $event->type );
			}

			http_response_code(200);
			exit();

		},
		'permission_callback' => '__return_true'
	));

    register_rest_route( 'tds_subscription', '/create_stripe_customer/', array(
        'methods'  => 'POST',
        'callback' => array ( 'tds_ajax', 'on_ajax_create_stripe_customer' ),
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));

    register_rest_route( 'tds_subscription', '/stripe_pm_update/', array(
        'methods'  => 'POST',
        'callback' => array ( 'tds_ajax', 'on_ajax_stripe_pm_update' ),
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));

    register_rest_route( 'tds_subscription', '/stripe_checkout_session_create/', array(
        'methods'  => 'POST',
        'callback' => array ( 'tds_ajax', 'on_ajax_stripe_checkout_session_create' ),
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));

    register_rest_route( 'tds_subscription', '/stripe_checkout_session_retrieve/', array(
        'methods'  => 'POST',
        'callback' => array ( 'tds_ajax', 'on_ajax_stripe_checkout_session_retrieve' ),
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));

});

class tds_ajax {

	static function on_ajax_render_preview_shortcode( WP_REST_Request $request ) {

		// get the $_POST parameters only
		$parameters = $request->get_body_params();

		$shortcode = $request->get_param('shortcode');
		$parameters['shortcode'] = $shortcode;

		$reply_html = do_shortcode( $shortcode );

		$parameters['replyHtml'] = $reply_html;

		die( json_encode( $parameters ) );

	}

	static function update_post_settings_meta( $post_id, $meta_key, $meta_value ) {
		$td_post_theme_settings = td_util::get_post_meta_array($post_id, 'td_post_theme_settings');
		$td_post_theme_settings[$meta_key] = $meta_value;
		return update_post_meta($post_id, 'td_post_theme_settings', $td_post_theme_settings);
	}

	static function on_ajax_create_subscription( WP_REST_Request $request ) {

		global $wpdb;

		$result = [];

		$subscription_user_id = $request->get_param('subscriptionUserId');
		$subscription_plan_id = $request->get_param('subscriptionPlanId');
		$subscription_coupon_id = $request->get_param('subscriptionCouponId');

		// verify if the user trying to create the subscription has permissions
		$current_user = wp_get_current_user();
		$is_current_user_admin = in_array('administrator', $current_user->roles );

		if( !$is_current_user_admin && $subscription_user_id != $current_user->ID ) {
			$result['error'] = __td( 'You do not hold the required privileges to execute this request.' );
		}

		if ( empty($subscription_user_id) ) {
			$result['error'] = 'Invalid user id';
		}

		if ( empty($subscription_plan_id) ) {
			$result['error'] = 'Invalid plan id';
		}

		// check plan
		$valid_plan = false;

		if ( empty($result) ) {

            $plan_name = '';
			$cycle_interval = '';
			$cycle_interval_count = 0;
			$trial_days = 0;
			$is_free = 0;
            $is_unlimited = 0;
			$price = 0;
            $list_id = '';
            $plan_publishing_limits = array();

			$curr_name = $curr_pos = $curr_th_sep = $curr_dec_sep = $curr_dec_no = '';

			$results = self::get_all_plans($request);
			if ( !empty( $results['plans'] ) ) {
				foreach ( $results['plans'] as $plan ) {
					if ( $plan['id'] == $subscription_plan_id ) {
						$valid_plan = true;
                        $plan_name = $plan['name'];

                        // do not set subscription cycle_interval & cycle_interval_count for free or unlimited plans
                        if ( empty($plan['is_free']) && empty($plan['is_unlimited']) ) {
                            $cycle_interval = $plan['interval'];
                            $cycle_interval_count = $plan['interval_count'];
                        }

						$trial_days = intval( $plan['trial_days'] ) > 0 ? intval( $plan['trial_days'] ) : 0;
						$is_free = $plan['is_free'];
						$is_unlimited = $plan['is_unlimited'];
						$price = $plan['price'];
                        $list_id = $plan['list'];

                        if ( $plan['publishing_limits'] ) {
                            $plan_publishing_limits = unserialize($plan['publishing_limits']);
                        }

						// check previous subscriptions to allow a user to benefit from trial just one time
						$user_subscriptions = tds_util::get_user_subscriptions_to_plan( $subscription_user_id, $subscription_plan_id );

						// if the user had other subscriptions to this plan reset trial days ( no trial )
						if ( null !== $user_subscriptions && count($user_subscriptions) ) {
							$trial_days = 0; // set trial to 0 days
						}

						tds_util::get_currency_options($curr_name, $curr_pos, $curr_th_sep, $curr_dec_sep, $curr_dec_no);
						break;
					}
				}
			}

			$billing_first_name = $request->get_param('billingFirstName');
			$billing_last_name = $request->get_param('billingLastName');
			$billing_company_name = $request->get_param('billingCompanyName');
			$billing_vat_number = $request->get_param('billingVatNumber');
			$billing_country = $request->get_param('billingCountry');
			$billing_address = $request->get_param('billingAddress');
			$billing_city = $request->get_param('billingCity');
			$billing_county = $request->get_param('billingCounty');
			$billing_postcode = $request->get_param('billingPostcode');
			$billing_phone = $request->get_param('billingPhone');
			$billing_email = $request->get_param('billingEmail');
			
			if ( empty($is_free) ) {
				$billing_payment_method = $request->get_param( 'billingPaymentMethod' );

				if ( empty( $billing_payment_method ) ) {
					$result['error']['billingPaymentMethod'] = 'Invalid payment method';
				}
			}

		}

		if ( empty($result) ) {

			if ( !$valid_plan ) {
				$result['error'] = 'Invalid plan';
			} else {

				$data_values = array(
					'user_id' => $subscription_user_id,
					'plan_id' => $subscription_plan_id,
					'price' => $price,
					'curr_name' => $curr_name,
					'curr_pos' => $curr_pos,
					'curr_th_sep' => $curr_th_sep,
					'curr_dec_sep' => $curr_dec_sep,
					'curr_dec_no' => $curr_dec_no,
					'billing_first_name' => $billing_first_name,
					'billing_last_name' => $billing_last_name,
					'billing_company_name' => $billing_company_name,
					'billing_vat_number' => $billing_vat_number,
					'billing_country' => $billing_country,
					'billing_address' => $billing_address,
					'billing_city' => $billing_city,
					'billing_county' => $billing_county,
					'billing_post_code' => $billing_postcode,
					'billing_phone' => $billing_phone,
					'billing_email' => $billing_email,
					'payment_type' => $billing_payment_method,
					'status' => ( empty($is_free) ? ( empty($trial_days) ? 'waiting_payment' : 'trial' ) : 'free' ),
					'cycle_interval' => $cycle_interval,
					'cycle_interval_count' => $cycle_interval_count,
					'trial_days' => $trial_days,
					'is_free' => $is_free,
					'is_unlimited' => $is_unlimited,
					'start_date' => date('Y-m-d')
				);
                $data_format = array(
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
                );

                // prepare billing data
                $user_id = get_current_user_id();
                $data_billing = array(
                    'user_id' => $user_id,
                    'billing_first_name' => $billing_first_name,
                    'billing_last_name' => $billing_last_name,
                    'billing_company_name' => $billing_company_name,
                    'billing_vat_number' => $billing_vat_number,
                    'billing_country' => $billing_country,
                    'billing_address' => $billing_address,
                    'billing_city' => $billing_city,
                    'billing_county' => $billing_county,
                    'billing_post_code' => $billing_postcode,
                    'billing_phone' => $billing_phone,
                    'billing_email' => $billing_email,
                );

                // create & set confirm key
                $confirm_key = $subscription_user_id . uniqid() . $subscription_plan_id;
                $data_values['confirm_key'] = $confirm_key;
                $data_format[] = '%s';

				if ( 'paypal' === $billing_payment_method ) {

                    $paypal_order_data = $request->get_param( 'paypalOrderData' );

                    // order status
                    $paypal_order_status = $paypal_order_data['order_status'];

                    // set subscription active status if order status is 'COMPLETED' or 'NO_PAYMENT_REQUIRED'
                    if ( in_array( $paypal_order_status, [ 'COMPLETED', 'NO_PAYMENT_REQUIRED' ], true ) ) {
                        $data_values['status'] = 'active';
                    }

				} else if ( 'stripe' === $billing_payment_method ) {

                    // build stripe subscription data from $request
                    $subscription_data = [

                        // session data
                        'session_mode' => $request->get_param( 'stripeCheckoutSessionMode' ),
                        'session_id' => '',
                        'stripe_subscription_id' => $request->get_param( 'stripeSubscriptionId' ),
                        'stripe_customer_id' => $request->get_param( 'stripeCustomerId' ),
                        'stripe_invoice_id' => $request->get_param( 'stripePaymentInvoice' ),
                        'payment_status' => 'no_payment_required',
                        'payment_method_types' => '',

                        // subscription data
                        'user_id' => $subscription_user_id,
                        'plan_id' => $subscription_plan_id,
                        'coupon_id' => $subscription_coupon_id,
                        'price_id' => '',

                        // billing data
                        'billing_data' => [
                            'billing_first_name' => $request->get_param('billingFirstName'),
                            'billing_last_name' => $request->get_param('billingLastName'),
                            'billing_company_name' => $request->get_param('billingCompanyName'),
                            'billing_vat_number' => $request->get_param('billingVatNumber'),
                            'billing_country' => $request->get_param('billingCountry'),
                            'billing_address' => $request->get_param('billingAddress'),
                            'billing_city' => $request->get_param('billingCity'),
                            'billing_county' => $request->get_param('billingCounty'),
                            'billing_post_code' => $request->get_param('billingPostcode'),
                            'billing_phone' => $request->get_param('billingPhone'),
                            'billing_email' => $request->get_param('billingEmail'),
                        ]

                    ];

                    // create stripe subscription
                    $create_stripe_subscription_result = tds_ajax::create_stripe_subscription($subscription_data);

                    // return result
                    die( json_encode($create_stripe_subscription_result) );

				}

				if ( !empty($subscription_coupon_id) ) {
					$data_values['coupon_id'] = $subscription_coupon_id;
					$data_format[] = '%s';
				}
				
				if ( !empty($plan_publishing_limits) ) {
					$posts_remaining = array();

					foreach( $plan_publishing_limits as $plan_publishing_limit ) {
						$posts_remaining[$plan_publishing_limit->post_type] = $plan_publishing_limit->limit;
					}

					$data_values['plan_posts_remaining'] = serialize($posts_remaining);
					$data_format[] = '%s';
				}

				$data_values['created_at'] = date('Y-m-d H:i:s');

				$wpdb->suppress_errors = true;

                $insert_result = $wpdb->insert( 'tds_subscriptions',
                    $data_values,
                    $data_format
                );

                if ( false === $insert_result ) {
                    $result['error'] = $wpdb->last_error;
                }

                // save billing details on checkout
                $billing_details_row_exists = $wpdb->get_results(
                    $wpdb->prepare("SELECT id FROM tds_billing WHERE user_id = %s", $user_id )
                );
                $billing_data_format = array(
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
                );

                if ( empty($billing_details_row_exists) ) {

                    $insert_billing_details = $wpdb->insert('tds_billing',
                        $data_billing,
                        $billing_data_format
                    );

                    if ( false === $insert_billing_details ) {
                        $result['error'] = $wpdb->last_error;
                    }

                }

				if ( empty($result) ) {

                     if ( empty($is_free) && ( empty($is_unlimited) || $trial_days > 0 ) ) {
                         $end_date = tds_util::get_subscription_end_date( date('Y-m-d'), $cycle_interval, $cycle_interval_count, max($trial_days, 0) )->format('Y-m-d');
                     } else {
                         $end_date = __td('unlimited', TD_THEME_NAME );
                     }

					$result['response'] = [
						'local_subscription_id' => $wpdb->insert_id,
						'local_plan_id' => $subscription_plan_id,
                        'local_plan_name' => $plan_name,
                        'start_date' => date('Y-m-d'),
                        'end_date' => $end_date,
						'price' => $price,
                        'is_free' => $is_free,
						'curr_name' => $curr_name
                    ];

                    if( empty($is_free) && empty($is_unlimited) ) {
                        $result['response']['cycle_interval'] = $cycle_interval;
                        $result['response']['cycle_interval_count'] = $cycle_interval_count;
                        $result['response']['cycle_interval_format'] = $cycle_interval_count . ' ' . tds_util::ci_format( $cycle_interval, $cycle_interval_count );
                    }

					$payment_bank = null;

					if ( !empty($subscription_coupon_id) ) {
						$result['response']['price_full'] = $price;
						$result['response']['price'] = tds_util::get_coupon_discount( $subscription_coupon_id, $price );
					}

					if ( 'direct' === $billing_payment_method ) {
	                    $result['response']['payment_type'] = __td('Direct Bank Transfer', TD_THEME_NAME );
	                    $result['response']['curr_symbol'] = tds_util::get_currency( $curr_name, true );

	                    $payment_bank = $wpdb->get_results("SELECT * FROM tds_payment_bank LIMIT 1", ARRAY_A );
                        if ( null !== $payment_bank && count($payment_bank) && 1 == $payment_bank[0]['is_active'] ) {
                            $result['response']['payment_bank'] = $payment_bank[0]['bank_name'];
                            $result['response']['payment_account_name'] = $payment_bank[0]['account_name'];
                            $result['response']['payment_account_number'] = $payment_bank[0]['account_number'];
                            $result['response']['payment_routing_number'] = $payment_bank[0]['routing_number'];
                            $result['response']['payment_iban'] = $payment_bank[0]['iban'];
                            $result['response']['payment_bic_swift'] = $payment_bank[0]['bic_swift'];
                            $result['response']['payment_instruction'] = $payment_bank[0]['instruction'];
                        }
                    } else if ( 'paypal' === $billing_payment_method ) {
                        $result['response']['payment_type'] = 'PayPal';

                        // save paypal payment details
                        if ( !empty($paypal_order_data) ) {

                            // add subscription id to paypal order data
                            $paypal_order_data['subscription_id'] = $wpdb->insert_id;

                            // capture paypal_order_data
                            $result['response']['paypal_order_data'] = $paypal_order_data;

                            // tds_paypal_payments insert
                            $tds_paypal_payments_insert = $wpdb->insert('tds_paypal_payments',
                                $paypal_order_data,
                                [
                                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
                                ]
                            );

                            if ( false === $tds_paypal_payments_insert ) {
                                $result['response']['paypal_order_data_err'] = $wpdb->last_error;
                            }
                        }

                    }

					$payment_page_id = tds_util::get_tds_option('payment_page_id');
		            if ( !is_null($payment_page_id) ) {
		                $payment_page_permalink = get_permalink($payment_page_id);
		                if ( false !== $payment_page_permalink ) {
		                    $confirm_url = add_query_arg( array(
		                    	'subscription' => $result['response']['local_subscription_id'],
			                    'key' => $confirm_key
		                    ), $payment_page_permalink );
		                }
		            }

					if ( !empty($confirm_url) ) {
			            $result['response']['confirm_url'] = $confirm_url;
		            }

                    // add billing email to the plan list
                    if ( !empty($list_id) && $data_values['billing_email'] != '' ) {

                        $found_post = tds_util::get_post_by_title( $data_values['billing_email'],'tds_email' );
                        if ( $found_post === null ) {
                            $args = [
                                'post_title' => $data_values['billing_email'],
                                'post_type'=> 'tds_email',
                                'post_status' => 'publish',
                            ];

                            $new_post_id = wp_insert_post( $args );

                            if ( !is_wp_error( $new_post_id ) && 0 < $new_post_id ) {
                                wp_set_object_terms( $new_post_id, array( intval($list_id) ), 'tds_list' );
                            }

                        }

                    }

					// send confirmation email to both member and admins
					$coupon_code = 'No discount code used';
					$subscription_full_price = '';
					if( !empty($subscription_coupon_id) ) {
						$coupon = tds_util::get_coupon($subscription_coupon_id);

						if( $coupon !== null ) {
							$coupon_code = $coupon['name'];
                            $subscription_full_price = tds_util::get_basic_currency($price);
                            $price = tds_util::get_coupon_discount( $subscription_coupon_id, $price );
						}
					}

					$direct_bank_info = '';
					if ( 'direct' === $billing_payment_method ) {
						if ( null !== $payment_bank && count($payment_bank) && 1 == $payment_bank[0]['is_active'] ) {
							$direct_bank_info = '<div style="Margin: 0 0 20px 0; border-radius: 3px; background-color: #f9f9f9; mso-line-height-rule: exactly; line-height: 160%;">';
								$direct_bank_info .= '<div style="padding: 8px 18px 10px; background-color: #efefef; font-size: 12px; color: #4c565c; border-radius: 3px 3px 0 0;">Direct bank transfer details</div>';
								$direct_bank_info .= '<div style="padding: 11px 18px 13px 20px; font-size: 14px;">';
									$direct_bank_info .= 'Account name: <strong>' . $payment_bank[0]['account_name'] . '</strong><br>';
									$direct_bank_info .= 'Account number: <strong>' . $payment_bank[0]['account_number'] . '</strong><br>';
									$direct_bank_info .= 'Bank name: <strong>' . $payment_bank[0]['bank_name'] . '</strong><br>';
									$direct_bank_info .= 'Routing number: <strong>' . $payment_bank[0]['routing_number'] . '</strong><br>';
									$direct_bank_info .= 'IBAN: <strong>' . $payment_bank[0]['iban'] . '</strong><br>';
									$direct_bank_info .= 'Bic/Swift: <strong>' . $payment_bank[0]['bic_swift'] . '</strong>';
								$direct_bank_info .= '</div>';
							$direct_bank_info .= '</div>';
						}
					}

					$add_tags = array( '%subscription_name%', '%subscription_price%', '%coupon_code%', '%subscription_expiry%', '%direct_bank_info%', '%subscription_full_price%' );
					$add_tags_replacements = array( $plan_name, tds_util::get_basic_currency($price), $coupon_code, $end_date, $direct_bank_info, $subscription_full_price );

					tds_email_notifications::send_user_email_notification('subscription', $subscription_user_id, $add_tags, $add_tags_replacements);
					tds_email_notifications::send_admin_email_notification('subscription', $subscription_user_id, $add_tags, $add_tags_replacements);

				} else {
					$result['error'] = $wpdb->last_error;
				}

			}

		}

		die( json_encode( $result ) );

	}

	static function on_ajax_cancel_subscription( WP_REST_Request $request ) {

		$result = [];

		$subscription_id = $request->get_param('subscriptionId');
		$subscription_user_id = $request->get_param('subscriptionUserId');
		$subscription_status = $request->get_param('subscriptionStatus');
		$stripe_subscription_id = $request->get_param('stripeSubscriptionId');

		// verify if the user trying to create the subscription has permissions
		$current_user = wp_get_current_user();
		$is_current_user_admin = in_array('administrator', $current_user->roles );

		if( !$is_current_user_admin && $subscription_user_id != $current_user->ID ) {
			$result['error'] = __td( 'You do not hold the required privileges to execute this request.' );
		}

		if ( empty($subscription_id) ) {
			$result['error'][] = 'Invalid subscription id!';
		}

		if ( empty($subscription_status) ) {
			$result['error'][] = 'Invalid subscription status!';
		}

		if ( !empty($result) ) {
			// return error
		} else {
			global $wpdb;

			// cancel subscription on stripe
			if ( !empty($stripe_subscription_id) ) {

				// get stripe api keys
				$results = $wpdb->get_results("SELECT * FROM tds_payment_stripe LIMIT 1", ARRAY_A );
				if ( null !== $results ) {

					require_once TDS_PATH . '/includes/vendor/stripe/init.php';

					$is_testing = '';
					if ( !empty($results[0]['is_sandbox']) ) {
						$is_testing = 'sandbox_';
					}

					\Stripe\Stripe::setApiKey( $results[0][$is_testing . 'secret_key'] );

					try {

						// get subscription
						$subscription = \Stripe\Subscription::retrieve( $stripe_subscription_id );

						// for subscriptions waiting for payment cancel immediately
						if ( $subscription_status === 'waiting_payment' ) {

							// cancel immediately
							$result['canceled_stripe_subscription'] = $subscription->cancel();

						// for active subscriptions cancel at period end
						} else if ( $subscription_status === 'active' ) {
							// cancelling subscription at period end
							$subscription->cancel_at_period_end = true;
							$result['canceled_stripe_subscription'] = $subscription->save();
						}

					} catch ( Exception $ex ) {
						$result['error'] = $ex->getMessage();
					}

				}
			}

			if ( !empty($result['error']) ) {
				// if we have errors after trying to cancel subscription on stripe return the error
			} else {
				// proceed with canceling the local subscription

				// subscription update data
				$data = array( 'canceled' => '1' );

				// subscription update data format
				$data_format = array( '%s' );

				// close subscriptions waiting for payment
				if ( $subscription_status === 'waiting_payment' ) {
					$data['status'] = 'closed';
					$data_format[] = '%s';
				}

				// subscription update where
				$where = array( 'id' => $subscription_id );

				// subscription update where format
				$where_format = array( '%d' );

				// subscription update
				$update_result = $wpdb->update( 'tds_subscriptions', $data, $where, $data_format, $where_format );

				if ( false !== $update_result ) {
					$result['success'] = true;

					$my_account_page_id = tds_util::get_tds_option('my_account_page_id');

					if ( class_exists('SitePress') ) {
						$translated_my_account_page_id = apply_filters( 'wpml_object_id', $my_account_page_id, 'page' );
						if ( !is_null($translated_my_account_page_id) ) {
							$my_account_page_id = $translated_my_account_page_id;
						}
					}

					if ( !is_null( $my_account_page_id ) ) {
						$my_account_permalink = get_permalink( $my_account_page_id );
						if ( false !== $my_account_permalink ) {
							$result['redirect_url'] = add_query_arg( 'subscriptions', '', $my_account_permalink );
						}
					}

					// send subscription canceled email notifications
					$subscription = tds_util::get_subscription($subscription_id);
					$plan = tds_util::get_plan($subscription['plan_id']);
					$subscription_price = tds_util::get_basic_currency($subscription['price']);

                    $subscription_end_date = __td('unlimited', TD_THEME_NAME );
                    if ( empty($subscription['is_free']) && empty($subscription['is_unlimited']) ) {

                        $subscription_end_date = tds_util::get_subscription_end_date(
                            date('Y-m-d'),
                            $subscription['cycle_interval'],
                            $subscription['cycle_interval_count']
                        )->format('Y-m-d');

                    }

					$add_tags = array('%subscription_name%', '%subscription_price%', '%subscription_expiry%');
					$add_tags_replacements = array($plan['name'], $subscription_price, $subscription_end_date);

					tds_email_notifications::send_user_email_notification('cancel', $subscription_user_id, $add_tags, $add_tags_replacements);
					tds_email_notifications::send_admin_email_notification('cancel', $subscription_user_id, $add_tags, $add_tags_replacements);

				} else {
					$result['error'] = $wpdb->last_error;
				}

			}

		}

		die( json_encode( $result ) );
	}

	static function on_ajax_update_paypal_subscription( WP_REST_Request $request ) {

		$result = [];

		$subscription_id = $request->get_param('subscriptionId');
		if ( empty($subscription_id) ) {
			$result['error'] = 'Invalid subscription id';
		}

		// get subscription data
		$subscription_data = tds_util::get_subscription($subscription_id);
		if ( empty($subscription_data) ) {
			$result['error'] = 'Invalid subscription data';
		} else {

			// set subscription user id
			$subscription_user_id = $subscription_data['user_id'];

			// verify if the user trying to create the subscription has permissions
			$current_user = wp_get_current_user();
			$is_current_user_admin = in_array('administrator', $current_user->roles );

			if( !$is_current_user_admin && $subscription_user_id != $current_user->ID ) {
				$result['error'] = __td( 'You do not hold the required privileges to execute this request.' );
			}

		}

		if ( empty($result) ) {
            $paypal_order_data = $request->get_param( 'paypalOrderData' );
			if ( empty($paypal_order_data) ) {
				$result['error'] = 'Invalid PayPal order data';
			}
		}

		if ( empty($result) && !empty($paypal_order_data) ) {

            // order status
            $paypal_order_status = $paypal_order_data['order_status'];

            // set subscription active status if order status is 'COMPLETED'
            if ( $paypal_order_status === 'COMPLETED' ) {
                $subscription_status = 'active';
            } else {
                $subscription_status = 'paid_incomplete';
            }

			global $wpdb;
			$wpdb->suppress_errors = true;

            // update subscription status
			$update_result = $wpdb->update( 'tds_subscriptions',
                [ 'status' => $subscription_status ],
                [ 'id' => $subscription_id ],
                [ '%s' ],
                [ '%d' ],
            );

			if ( false !== $update_result ) {
				$result['success'] = true;
			} else {
				$result['error'] = $wpdb->last_error;
			}

            // save paypal payment details
            $paypal_order_data['subscription_id'] = $subscription_id;
            $result['paypal_order_data'] = $paypal_order_data;

            // tds_paypal_payments insert
            $tds_paypal_payments_insert = $wpdb->insert('tds_paypal_payments',
                $paypal_order_data,
                [
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
                ]
            );

            if ( false === $tds_paypal_payments_insert ) {
                $result['paypal_order_data_err'] = $wpdb->last_error;
            }

		}

		die( json_encode($result) );

	}

	static function on_ajax_tds_proxy( WP_REST_Request $request ) {
		$reply = [];

        $end_point = $request->get_param('endPoint');
        if (empty($end_point)) {
            $reply['error'] = array(
                array(
		            'type' => 'API ERROR',
		            'message' => 'No endPoint received. Please use tdsApi.run for proxy requests.',
		            'debug_data' => $request
	            )
            );
            die( json_encode( $reply ) );
        }

        $end_point = $request->get_param('endPoint');

        switch ($end_point) {

        	case 'set_option':
	        	$intern_result = self::set_option($request);
	        	break;
            case 'get_option':
	        	$intern_result = self::get_option($request);
	        	break;
            case 'get_all_options':
	        	$intern_result = self::get_all_options();
	        	break;
            case 'set_options':
	        	$intern_result = self::set_options($request);
	        	break;
            case 'get_all_currencies':
	        	$intern_result = self::get_all_currencies();
	        	break;
            case 'get_all_stripe_currencies':
	        	$intern_result = self::get_all_stripe_currencies();
	        	break;

        	case 'create_plan':
            	$intern_result = self::create_plan($request);
	        	break;
            case 'delete_plan':
            	$intern_result = self::delete_plan($request);
	        	break;
	        case 'get_all_plans':
	        	$intern_result = self::get_all_plans($request);
	        	break;

	        case 'create_company':
	        	$intern_result = self::create_company($request);
	        	break;
            case 'get_company':
	        	$intern_result = self::get_company($request);
	        	break;

            case 'create_payment_bank':
	        	$intern_result = self::create_payment_bank($request);
	        	break;
            case 'get_payment_bank':
	        	$intern_result = self::get_payment_bank($request);
	        	break;

            case 'create_payment_paypal':
	        	$intern_result = self::create_payment_paypal($request);
	        	break;
            case 'get_payment_paypal':
	        	$intern_result = self::get_payment_paypal($request);
	        	break;
	        case 'get_token_paypal':
	        	self::get_token_paypal($request);
	        	break;

            case 'create_payment_stripe':
	        	$intern_result = self::create_payment_stripe($request);
	        	break;
            case 'get_payment_stripe':
	        	$intern_result = self::get_payment_stripe($request);
	        	break;

            case 'modify_subscription':
            	$intern_result = self::modify_subscription($request);
	        	break;
	        case 'get_all_subscriptions':
	        	$intern_result = self::get_all_subscriptions($request);
	        	break;
            case 'get_latest_subscriptions':
	        	$intern_result = self::get_latest_subscriptions($request);
	        	break;
            case 'get_info_subscriptions':
	        	$intern_result = self::get_info_subscriptions($request);
	        	break;

            case 'create_wizard_locker':
            	$intern_result = self::create_wizard_locker($request);
	        	break;

            case 'get_page_info':
            	$intern_result = self::get_page_info($request);
	        	break;
            case 'get_email_lists':
                $intern_result = self::get_email_lists();
                break;
			case 'get_post_types':
				$intern_result = self::get_post_types();
				break;
            case 'create_wizard_pages':
	        	$intern_result = self::create_wizard_pages($request);
	        	break;

            case 'get_list_pages':
            	$intern_result = self::get_list_pages($request);
	        	break;

            case 'get_dashboard_permalinks':
            	$intern_result = self::get_dashboard_permalinks($request);
	        	break;

            case 'coupons_get_all':
            	$intern_result = self::coupons_get_all();
	        	break;
            case 'coupon_add_edit':
            	$intern_result = self::coupon_add_edit($request);
	        	break;
            case 'coupon_delete':
            	$intern_result = self::coupon_delete($request);
	        	break;

			case 'modify_email_notification':
				$intern_result = self::modify_email_notification($request);
				break;

			case 'tds_update':

                $current_version = $request->get_param('version');
                $current_version = '1.4';
                if ( !empty($current_version) ) {

                    if ( version_compare( $current_version, '1.4', '>=' ) ) {
                        require_once('tds_update.php');
                        $intern_result['tds_update_results'] = tds_update::update_settings($current_version);
                        $intern_result['success'] = true;
                    } else {
                        $intern_result['error'][] = 'version not supported!';
                    }

                } else {
                    $intern_result['error'][] = 'version required!';
                }

				break;

	        default:
	        	$intern_result['error'] = 'Invalid endPoint';

        }

        if (empty($intern_result['error'])) {
        	$reply = $intern_result;
        } else {
	        $reply['error'] = array(
	            array(
			        'type' => 'API ERROR',
			        'message' => $intern_result['error'],
		            'debug_data' => $request
		        )
	        );
        }

		die( json_encode($reply) );
	}

	private static function set_option(WP_REST_Request $request) {
		$result = [];
        $option_name = $request->get_param('optionName');
        $option_value = $request->get_param('optionValue');

        if (empty($option_name)) {
			$result[ 'error' ][] = 'Invalid option name';
		}
		if (empty($option_value)) {
			$result[ 'error' ][] = 'Invalid option value';
		}
		if (!empty($result)) {
			// return error
		} else {
			$response = tds_util::set_tds_option($option_name, $option_value);
			if (!is_null($response)) {
				$result = $response;
			}
		}

		return $result;
	}

	private static function get_option(WP_REST_Request $request) {
		$result = [];
        $option_name = $request->get_param('optionName');

        if (empty($option_name)) {
			$result[ 'error' ][] = 'Invalid option name';
		}
		if (!empty($result)) {
			// return error
		} else {
			global $wpdb;

			$get_result = $wpdb->get_var( $wpdb->prepare( "SELECT value FROM tds_options WHERE name = %s", $option_name) );
			if ( false !== $get_result ) {
				$result[ 'value' ] = $get_result;
			} else {
				$result[ 'error' ][] = 'Invalid value';
			}
		}

		return $result;
	}

	static function get_all_options() {
		global $wpdb;

		$result = [];
		$results = $wpdb->get_results("SELECT * FROM tds_options", ARRAY_A);

		if ( null !== $results) {
			$result['options'] = $results;
		}

        $result['td_subscription_version'] = TD_SUBSCRIPTION_VERSION;

		return $result;

	}

	static function get_all_currencies() {
		$result = [];
		$currencies = tds_util::get_currency();

		foreach ($currencies as $key_currency => $val_currency) {
			$is_paypal = false;
			$is_digit = false;
			tds_util::check_paypal_currency($key_currency, $is_paypal, $is_digit);
			$results[$key_currency] = [
				'description' => $val_currency,
				'is_paypal' => $is_paypal,
				'is_digit' => $is_digit
			];
		}

		if ( null !== $results) {
			$result['currency'] = $results;
		}
		return $result;
	}

	static function get_all_stripe_currencies() {
		$result = [];
		$currencies = tds_util::get_currency();

		foreach ($currencies as $key_currency => $val_currency) {
			$is_stripe = false;
			$is_digit = false;
			tds_util::check_stripe_currency($key_currency, $is_stripe, $is_digit);
			$results[$key_currency] = [
				'description' => $val_currency,
				'is_stripe' => $is_stripe,
				'is_digit' => $is_digit
			];
		}

		if ( null !== $results) {
			$result['currency'] = $results;
		}
		return $result;
	}

	private static function get_all_plans(WP_REST_Request $request) {
		global $wpdb;

		$lockers = get_posts( array(
			'post_type' => 'tds_locker',
			'numberposts' => -1
		));

		$result = [];
		$results = $wpdb->get_results("SELECT 
				tds_plans.*,
				test.count_subscriptions
			FROM 
				tds_plans LEFT JOIN (
					SELECT 
						tds_plans.id as 'plan_id',
			            COUNT( DISTINCT tds_subscriptions.id ) as 'count_subscriptions'
					FROM 
						tds_plans INNER join tds_subscriptions ON tds_plans.id = tds_subscriptions.plan_id
			            AND tds_subscriptions.status IN ('waiting_payment', 'active', 'free', 'trial')
					GROUP BY
						tds_plans.id
			    ) as test on test.plan_id = tds_plans.id", ARRAY_A);

		if ( null !== $results) {
			foreach ( $results as &$item ) {
				$item['formatted_price'] = tds_util::get_basic_currency($item['price']);

				$item['count_lockers'] = 0;
				foreach ( $lockers as $locker ) {
					$tds_locker_types = get_post_meta( $locker->ID, 'tds_locker_types', true );
					if ( ! empty( $tds_locker_types[ 'tds_paid_subs_plan_ids' ] ) && is_array( $tds_locker_types[ 'tds_paid_subs_plan_ids' ] ) && in_array( $item[ 'id' ], $tds_locker_types[ 'tds_paid_subs_plan_ids' ] ) ) {
						$item['count_lockers']++;
						continue;
					}
				}

				$item['publishing_limits_unserialized'] = $item['publishing_limits'] ? unserialize($item['publishing_limits']) : array();
                $item['automatic_delistings_unserialized'] = $item['automatic_delistings'] ? unserialize($item['automatic_delistings']) : array();
			}
			$result['plans'] = $results;
		}
		return $result;
	}

	private static function create_plan(WP_REST_Request $request) {
		$result = [];
        $plan_id = $request->get_param('planId');
        $plan_name = $request->get_param('planName');
        $plan_price = $request->get_param('planPrice');
        $plan_interval = $request->get_param('planInterval');
        $plan_interval_count = $request->get_param('planIntervalCount');
        $plan_trial = $request->get_param('planTrial');
        $plan_free = $request->get_param('planFree');
        $plan_unlimited = $request->get_param('planUnlimited');
        $plan_list = $request->get_param('planList');
        $plan_publishing_limits = json_decode($request->get_param('planPublishingLimits'));
        $plan_automatic_delistings = json_decode($request->get_param('planAutomaticDelistings'));

        if ( empty($plan_name) ) {
			$result['error'][] = 'Invalid plan name';
		}

		if ( isset($plan_free) && '0' === $plan_free ) {

			if ( empty($plan_price) ) {
				$result['error'][] = 'Invalid plan price';
			}

            if ( isset($plan_unlimited) && '0' === $plan_unlimited ) {

                if ( empty($plan_interval) ) {
                    $result['error'][] = 'Invalid plan interval';
                }

                if ( empty($plan_interval_count) ) {
                    $result['error'][] = 'Invalid plan interval count';
                }

            }

            if ( empty($result) ) {

                // check interval length
                switch ($plan_interval) {

                    case 'day':
                        // check number of days
                        if ( intval($plan_interval_count) > 365 ) {
                            $result['error'][] = 'The interval count for daily plans can\'t be greater than 365';
                        }
                        break;

                    case 'week':
                        // check number of weeks
                        if ( intval($plan_interval_count) > 52 ) {
                            $result['error'][] = 'The interval count for weekly plans can\'t be greater than 52';
                        }
                        break;

                    case 'month':
                        // check number of months
                        if ( intval($plan_interval_count) > 12 ) {
                            $result['error'][] = 'The interval count for monthly plans can\'t be greater than 12';
                        }
                        break;

                    case 'year':
                        // check number of years
                        if ( intval($plan_interval_count) > 1 ) {
                            $result['error'][] = 'The interval count for yearly plans can\'t be greater than 1';
                        }
                        break;

                }

            }

		}

		if ( !empty($result) ) {
			// return error
		} else {
			global $wpdb;

			if ( empty($plan_id) ) {
				$insert_result = $wpdb->insert( 'tds_plans',
					array(
						'name'            => $plan_name,
						'price'           => $plan_price,
						'interval'        => $plan_interval,
						'interval_count'  => $plan_interval_count,
						'trial_days' => intval($plan_trial) > 0 ? intval($plan_trial) : 0,
						'is_free' => $plan_free,
						'is_unlimited' => $plan_unlimited,
                        'list' => $plan_list,
                        'publishing_limits' => serialize($plan_publishing_limits),
                        'automatic_delistings' => serialize($plan_automatic_delistings),
					),
					array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
                );

				if ( false !== $insert_result ) {
					$result[ 'inserted_id' ] = $wpdb->insert_id;
				}
			} else {
				$update_result = $wpdb->update( 'tds_plans',
					array(
						'name'            => $plan_name,
						'price'           => $plan_price,
						'interval'        => $plan_interval,
						'interval_count'  => $plan_interval_count,
						'trial_days' => intval($plan_trial) > 0 ? intval($plan_trial) : 0,
						'is_free' => $plan_free,
                        'is_unlimited' => $plan_unlimited,
                        'list' => $plan_list,
                        'publishing_limits' => serialize($plan_publishing_limits),
                        'automatic_delistings' => serialize($plan_automatic_delistings),
					),
					array( 'id' => $plan_id ),
					array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
					array( '%d' )
				);

				if ( false !== $update_result ) {
					$result['success'] = true;
				}
			}

			if ( !empty($result) ) {
				$result['interval'] = $plan_interval;
				$result['interval_count'] = $plan_interval_count;
				$result['formatted_price'] = tds_util::get_basic_currency($plan_price);
				$result['publishing_limits_unserialized'] = $plan_publishing_limits;
				$result['automatic_delistings_unserialized'] = $plan_automatic_delistings;
			}

		}

		return $result;
	}

	private static function delete_plan(WP_REST_Request $request) {
		$result = [];
        $plan_id = $request->get_param('planId');

        if (empty($plan_id)) {
			$result[ 'error' ][] = 'Invalid plan id';
		}
		if (!empty($result)) {
			// return error
		} else {
			global $wpdb;

			$delete_result = $wpdb->delete( 'tds_plans',
				array(
					'id' => $plan_id
				),
				array( '%d' ) );

			if ( false !== $delete_result ) {
				$result[ 'success' ] = true;
			}
		}

		return $result;
	}

	private static function get_company(WP_REST_Request $request) {
		global $wpdb;

		$result = [];
		$results = $wpdb->get_results("SELECT * FROM tds_companies LIMIT 1", ARRAY_A);

		if ( null !== $results) {
			$result['company'] = $results;
		}
		return $result;
	}

	private static function create_company(WP_REST_Request $request) {
		$result = [];
        $company_id = $request->get_param('companyId');
        $company_name = $request->get_param('companyName');
        $billing_cui = $request->get_param('billingCUI');
        $billing_j = $request->get_param('billingJ');
        $billing_address = $request->get_param('billingAddress');
        $billing_city = $request->get_param('billingCity');
        $billing_country = $request->get_param('billingCountry');
        $billing_email = $request->get_param('billingEmail');
        $billing_bank_account = $request->get_param('billingBankAccount');
        $billing_post_code = $request->get_param('billingPostCode');
        $billing_vat_number = $request->get_param('billingVatNumber');

        if (empty($company_name)) {
			$result[ 'error' ]['companyName'] = 'Invalid company name';
		}
//		if (empty($billing_cui)) {
//			$result[ 'error' ][] = 'Invalid billing CUI';
//		}
//		if (empty($billing_j)) {
//			$result[ 'error' ][] = 'Invalid billing J';
//		}
//		if (empty($billing_address)) {
//			$result[ 'error' ][] = 'Invalid billing address';
//		}
//		if (empty($billing_city)) {
//			$result[ 'error' ][] = 'Invalid billing city';
//		}
//		if (empty($billing_country)) {
//			$result[ 'error' ][] = 'Invalid billing country';
//		}
		if (empty($billing_email)) {
			$result[ 'error' ]['billingEmail'] = 'Empty billing email';
		} else if (!is_email($billing_email)) {
			$result[ 'error' ]['billingEmail'] = 'Invalid billing email';
		}
//		if (empty($billing_bank_account)) {
//			$result[ 'error' ][] = 'Invalid billing bank account';
//		}
//		if (empty($billing_post_code)) {
//			$result[ 'error' ][] = 'Invalid billing post code';
//		}
//		if (empty($billing_vat_number)) {
//			$result[ 'error' ][] = 'Invalid billing vat number';
//		}
		if (!empty($result)) {
			// return error
		} else {
			global $wpdb;

			$data_values = array(
				'company_name' => $company_name,
				'billing_cui' => $billing_cui,
				'billing_j' => $billing_j,
				'billing_address' => $billing_address,
				'billing_city' => $billing_city,
				'billing_country' => $billing_country,
				'billing_email' => $billing_email,
				'billing_bank_account' => $billing_bank_account,
				'billing_post_code' => $billing_post_code,
				'billing_vat_number' => $billing_vat_number,
			);
			$data_format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');

			$wpdb->suppress_errors = true;

			if (empty($company_id)) {

				$insert_result = $wpdb->insert( 'tds_companies',
					$data_values,
					$data_format);

				if ( false !== $insert_result ) {
					$result[ 'inserted_id' ] = $wpdb->insert_id;
				} else {
					$result[ 'error' ] = $wpdb->last_error;
				}

			} else {

				$update_result = $wpdb->update( 'tds_companies',
					$data_values,
					array( 'id' => $company_id ),
					$data_format,
					array( '%d' )
				);

				if ( false !== $update_result ) {
					$result[ 'success' ] = true;
				} else {
					$result[ 'error' ] = $wpdb->last_error;
				}
			}
		}

		return $result;
	}

	private static function create_payment_bank(WP_REST_Request $request) {
		$result = [];
        $payment_id = $request->get_param('paymentId');
        $account_name = $request->get_param('accountName');
        $account_number = $request->get_param('accountNumber');
        $bank_name = $request->get_param('bankName');
        $routing_number = $request->get_param('routingNumber');
        $iban = $request->get_param('iban');
        $bic_swift = $request->get_param('bicSwift');
        $description = $request->get_param('description');
        $instruction = $request->get_param('instruction');
        $is_active = $request->get_param('isActive');

        if (empty($account_name)) {
			$result[ 'error' ]['accountName'] = 'Empty account name';
		}
		if (empty($account_number)) {
			$result[ 'error' ]['accountNumber'] = 'Empty account number';
		}
		if (empty($bank_name)) {
			$result[ 'error' ]['bankName'] = 'Empty bank name';
		}
//		if (empty($routing_number)) {
//			$result[ 'error' ][] = 'Invalid routing number';
//		}
//		if (empty($iban)) {
//			$result[ 'error' ][] = 'Invalid IBAN';
//		}
//		if (empty($bic_swift)) {
//			$result[ 'error' ][] = 'Invalid BIC/SWIFT';
//		}
//		if (empty($description)) {
//			$result[ 'error' ][] = 'Invalid description';
//		}
//		if (empty($instruction)) {
//			$result[ 'error' ][] = 'Invalid instruction';
//		}
		if (!empty($result)) {
			// return error
		} else {
			global $wpdb;

			$data_values = array(
				'account_name' => $account_name,
				'account_number' => $account_number,
				'bank_name' => $bank_name,
				'routing_number' => $routing_number,
				'iban' => $iban,
				'bic_swift' => $bic_swift,
				'description' => $description,
				'instruction' => $instruction,
				'is_active' => empty($is_active) ? 0 : 1,
			);
			$data_format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');

			$wpdb->suppress_errors = true;

			if (empty($payment_id)) {

				$insert_result = $wpdb->insert( 'tds_payment_bank',
					$data_values,
					$data_format);

				if ( false !== $insert_result ) {
					$result[ 'inserted_id' ] = $wpdb->insert_id;
				} else {
					$result[ 'error' ] = $wpdb->last_error;
				}

			} else {

				$update_result = $wpdb->update( 'tds_payment_bank',
					$data_values,
					array( 'id' => $payment_id ),
					$data_format,
					array( '%d' )
				);

				if ( false !== $update_result ) {
					$result[ 'success' ] = true;
				} else {
					$result[ 'error' ] = $wpdb->last_error;
				}
			}
		}

		return $result;
	}

	private static function get_payment_bank(WP_REST_Request $request) {
		global $wpdb;

		$result = [];
		$results = $wpdb->get_results("SELECT * FROM tds_payment_bank LIMIT 1", ARRAY_A);

		if ( null !== $results) {
			$result['payment'] = $results;
		}
		return $result;
	}

	private static function create_payment_paypal(WP_REST_Request $request) {
		$result = [];
        $payment_id = $request->get_param('paymentId');
        $client_id = $request->get_param('clientId');
        $client_id_sandbox = $request->get_param('clientIdSandbox');
        $is_active = $request->get_param('isActive');
        $is_sandbox = $request->get_param('isSandbox');

        if (!empty($is_active) ) {
        	if ( empty($is_sandbox) ) {
        		if (empty($client_id) ) {
        		    $result[ 'error' ][ 'clientId' ] = 'Empty client id';
		        }
	        } else {
	            if (empty($client_id_sandbox)) {
			        $result[ 'error' ][ 'clientIdSandbox' ] = 'Empty Sandbox Merchant Id';
		        }
	        }
		}

		if (!empty($result)) {
			// return error
		} else {
			global $wpdb;

			$data_values = array(
				'client_id' => empty($client_id) ? '' : $client_id,
				'client_id_sandbox' => empty($client_id_sandbox) ? '' : $client_id_sandbox,
				'is_active' => empty($is_active) ? 0 : 1,
				'is_sandbox' => empty($is_sandbox) ? 0 : 1
			);
			$data_format = array('%s', '%s', '%d', '%d');

			$wpdb->suppress_errors = true;

			if (empty($payment_id)) {

				$insert_result = $wpdb->insert( 'tds_payment_paypal',
					$data_values,
					$data_format);

				if ( false !== $insert_result ) {
					$result[ 'inserted_id' ] = $wpdb->insert_id;
				} else {
					$result[ 'error' ] = $wpdb->last_error;
				}

			} else {

				$update_result = $wpdb->update( 'tds_payment_paypal',
					$data_values,
					array( 'id' => $payment_id ),
					$data_format,
					array( '%d' )
				);

				if ( false !== $update_result ) {
					$result[ 'success' ] = true;
				} else {
					$result[ 'error' ] = $wpdb->last_error;
				}
			}
		}

		return $result;
	}

	private static function get_payment_paypal(WP_REST_Request $request) {
		global $wpdb;

		$result = [];
		$results = $wpdb->get_results("SELECT * FROM tds_payment_paypal LIMIT 1", ARRAY_A);

		if ( null !== $results) {
			$result['payment'] = $results;
		}
		return $result;
	}

	private static function get_token_paypal(WP_REST_Request $request) {
		$result = td_subscription::get_payment_method_credentials('paypal', $info);

		die( json_encode([
			'token' =>$result['token'],
			'info' => $info
		]) );
	}

	private static function get_all_subscriptions(WP_REST_Request $request) {
		global $wpdb;

		$result  = [];

		$check = $request->get_param('check');
		if ( !empty($check) && '1' === $check ) {
			$result = tds_util::get_subscriptions();
		} else {

			$results = $wpdb->get_results( "SELECT 
					tds_subscriptions.*, 
					tds_plans.name AS 'plan_name' 
				FROM 
					tds_subscriptions 
					LEFT JOIN tds_plans
					ON tds_subscriptions.plan_id = tds_plans.id ORDER BY tds_subscriptions.ID desc", ARRAY_A );

			if ( null !== $results ) {

				foreach ( $results as &$item ) {
					$item['formatted_plan_name'] = ( $item['plan_name'] ? $item['plan_name'] : 'Missing plan' ) . ' (#' . $item['plan_id'] . ')';

					if ( !empty( $item['user_id'] ) ) {
						$item['user_name'] = get_user_meta( $item['user_id'], 'nickname', true );
						$item['formatted_user_name'] = $item['user_name'] . ' (#' . $item['user_id'] . ')';

                        $item['end_date'] = '';
                        if ( !$item['is_free'] && ( !$item['is_unlimited'] || ( $item['status'] === 'trial' && intval($item['trial_days']) > 0 ) ) ) {
                            $item['end_date'] = tds_util::get_subscription_end_date(
                                $item['start_date'],
                                $item['cycle_interval'],
                                $item['cycle_interval_count'],
                                'trial' === $item['status'] ? $item['trial_days'] : 0
                            )->format('Y-m-d' );
                        }

						$item['next_price'] = empty($item['next_price']) ? $item['price'] : $item['next_price'];

						// apply coupon
						if ( !empty( $item['coupon_id'] ) ) {

							if ( empty( $item['curr_name'] ) ) {
								$item['formatted_full_price'] = tds_util::get_basic_currency( $item['price'] );
							} else {
								$item['formatted_full_price'] = tds_util::get_formatted_currency( $item['price'], $item['curr_name'], $item['curr_pos'], $item['curr_th_sep'], $item['curr_dec_sep'], $item['curr_dec_no'] );
							}

							$item['price'] = tds_util::get_coupon_discount( $item['coupon_id'], $item['price'] );
						}

						if ( empty( $item['curr_name'] ) ) {
							$item['formatted_price'] = tds_util::get_basic_currency( $item['price'] );
						} else {
							$item['formatted_price'] = tds_util::get_formatted_currency( $item['price'], $item['curr_name'], $item['curr_pos'], $item['curr_th_sep'], $item['curr_dec_sep'], $item['curr_dec_no'] );
						}

						// stripe subscriptions
						if ( $item['payment_type'] === 'stripe' ) {

							if ( !empty( $item['stripe_invoice_details'] ) ) {
								$item['stripe_invoice_details'] = json_decode( stripslashes( $item['stripe_invoice_details'] ), true );
							}

						}

						// paypal subscriptions
						if ( $item['payment_type'] === 'paypal' ) {

                            // get paypal payment details
                            $tds_paypal_payments_results = $wpdb->get_results(
                                $wpdb->prepare("SELECT * FROM tds_paypal_payments WHERE subscription_id = %s", $item['id'] )
                            );
                            if ( count($tds_paypal_payments_results) ) {
                                $tds_paypal_payment_details = $tds_paypal_payments_results[0];

                                foreach ( $tds_paypal_payment_details as $key => $data ) {
                                    $item['paypal_' . $key] = $data;
                                }

                            }

						}

					}

					$item['formatted_payment_type'] = '-';
					if( !$item['is_free'] ) {
						switch( $item['payment_type'] ) {
							case 'direct':
								$item['formatted_payment_type'] = 'Bank transfer';
								break;
							case 'paypal':
								$item['formatted_payment_type'] = 'PayPal';
								break;
							case 'stripe':
								$item['formatted_payment_type'] = 'Stripe';
								break;
							default:
								$item['formatted_payment_type'] = $item['payment_type'];
								break;
						}
					}

					$item['formatted_status'] = '';
					switch( $item['status'] ) {
						case 'free':
						case 'active':
						case 'blocked':
						case 'closed':
							$item['formatted_status'] = ucfirst($item['status']);
							break;
						case 'trial':
							$item['formatted_status'] = 'Trial ' . $item['trial_days'] . ' ' . (intval($item['trial_days']) > 1 ? 'days' : 'day' );
							break;
						case 'closed_not_paid':
							$item['formatted_status'] = 'Not paid';
							break;
						case 'waiting_payment':
							$item['formatted_status'] = 'Awaiting payment';
							break;
						default:
							$item['formatted_status'] = $item['status'];
							break;
					}

                    $item['formatted_cycle_interval'] = '-';
                    if( !$item['is_free'] && !$item['is_unlimited'] ) {
                        $subscription_ci_format = tds_util::ci_format( $item['cycle_interval'], $item['cycle_interval_count'] );
                        $item['formatted_cycle_interval'] = $item['cycle_interval_count'] . ' ' . $subscription_ci_format;
					}
				}

				$result['subscriptions'] = $results;

			}
		}

		return $result;

	}

	private static function get_latest_subscriptions( WP_REST_Request $request ) {
		global $wpdb;

		$result = [];
		$results = $wpdb->get_results("SELECT 
					tds_subscriptions.*, 
					tds_plans.name AS 'plan_name' 
				FROM 
					tds_subscriptions 
					LEFT JOIN tds_plans
					ON tds_subscriptions.plan_id = tds_plans.id 
				WHERE tds_subscriptions.status IN ('waiting_payment', 'active', 'trial', 'free')
				ORDER BY tds_subscriptions.id DESC  
				LIMIT 10", ARRAY_A);

		if ( null !== $results) {
			foreach ( $results as &$item ) {
				$item['formatted_plan_name'] = ( $item['plan_name'] ? $item['plan_name'] : 'Missing plan' ) . ' (#' . $item['plan_id'] . ')';

				if ( !empty($item['user_id']) ) {
					$item['user_name'] = get_user_meta( $item['user_id'], 'nickname', true );
					$item['formatted_user_name'] = $item['user_name'] . ' (#' . $item['user_id'] . ')';

                    $item['end_date'] = '';
                    if ( !$item['is_free'] && ( !$item['is_unlimited'] || ( $item['status'] === 'trial' && intval($item['trial_days']) > 0 ) ) ) {
                        $item['end_date'] = tds_util::get_subscription_end_date(
                            $item['start_date'],
                            $item['cycle_interval'],
                            $item['cycle_interval_count'],
                            'trial' === $item['status'] ? $item['trial_days'] : 0
                        )->format('Y-m-d' );
                    }

					$item['next_price'] = empty( $item['next_price'] ) ? $item['price'] : $item['next_price'];

					// apply coupon
					if ( !empty( $item['coupon_id'] ) ) {

						if ( empty( $item['curr_name'] ) ) {
							$item['formatted_full_price'] = tds_util::get_basic_currency( $item['price'] );
						} else {
							$item['formatted_full_price'] = tds_util::get_formatted_currency( $item['price'], $item['curr_name'], $item['curr_pos'], $item['curr_th_sep'], $item['curr_dec_sep'], $item['curr_dec_no'] );
						}

						$item['price'] = tds_util::get_coupon_discount( $item['coupon_id'], $item['price'] );
					}

					if ( empty($item['curr_name']) ) {
						$item['formatted_price'] = tds_util::get_basic_currency( $item['price'] );
					} else {
						$item['formatted_price'] = tds_util::get_formatted_currency( $item['price'], $item['curr_name'], $item['curr_pos'], $item['curr_th_sep'], $item['curr_dec_sep'], $item['curr_dec_no'] );
					}

				}

				$item['formatted_payment_type'] = '-';
				if( !$item['is_free'] ) {
					switch( $item['payment_type'] ) {
						case 'direct':
							$item['formatted_payment_type'] = 'Bank transfer';
							break;
						case 'paypal':
							$item['formatted_payment_type'] = 'PayPal';
							break;
						case 'stripe':
							$item['formatted_payment_type'] = 'Stripe';
							break;
						default:
							$item['formatted_payment_type'] = $item['payment_type'];
							break;
					}
				}

				$item['formatted_status'] = '';
				switch( $item['status'] ) {
					case 'free':
					case 'active':
					case 'blocked':
					case 'closed':
						$item['formatted_status'] = ucfirst($item['status']);
						break;
					case 'trial':
						$item['formatted_status'] = 'Trial ' . $item['trial_days'] . ' ' . (intval($item['trial_days']) > 1 ? 'days' : 'day' );
						break;
					case 'closed_not_paid':
						$item['formatted_status'] = 'Not paid';
						break;
					case 'waiting_payment':
						$item['formatted_status'] = 'Awaiting payment';
						break;
					default:
						$item['formatted_status'] = $item['status'];
						break;
				}

                $item['formatted_cycle_interval'] = '-';
                if( !$item['is_free'] && !$item['is_unlimited'] ) {
                    $subscription_ci_format = tds_util::ci_format( $item['cycle_interval'], $item['cycle_interval_count'] );
                    $item['formatted_cycle_interval'] = $item['cycle_interval_count'] . ' ' . $subscription_ci_format;
                }

			}

			$result['subscriptions'] = $results;

		}

		return $result;
	}

	private static function get_info_subscriptions(WP_REST_Request $request) {
		global $wpdb;

		$result = [
			'waiting_payment' => 0,
			'active' => 0,
		];

		foreach ($result as $key => $val ) {
			$status_counter = $wpdb->get_var( "SELECT count(*) FROM tds_subscriptions WHERE status = '" . $key . "'");
			if ( false !== $status_counter) {
				$result[$key] = $status_counter;
			}
		}

		return $result;
	}

	private static function modify_subscription( WP_REST_Request $request ) {
		$result = [];
        $subs_id = $request->get_param('subsId');
        $subs_user_id = $request->get_param('subsUserId');
        $subs_status = $request->get_param('subsStatus');
        $subs_start_date = $request->get_param('subsStartDate');

        $subs_bill_first_name = $request->get_param('subsBillFirstName');
        $subs_bill_last_name = $request->get_param('subsBillLastName');
        $subs_bill_company_name = $request->get_param('subsBillCompanyName');
        $subs_bill_email = $request->get_param('subsBillEmail');
        $subs_bill_cui = $request->get_param('subsBillCUI');
        $subs_bill_j = $request->get_param('subsBillJ');
        $subs_bill_vat_number = $request->get_param('subsBillVATNumber');
        $subs_bill_address = $request->get_param('subsBillAddress');
        $subs_bill_city = $request->get_param('subsBillCity');
        $subs_bill_country = $request->get_param('subsBillCountry');
        $subs_bill_bank_account = $request->get_param('subsBillBankAccount');
        $subs_bill_post_code = $request->get_param('subsBillPostCode');

        $subs_price = $request->get_param('subsPrice');
        $subs_plan_id = $request->get_param('subsPlanId');

        if (empty($subs_id)) {
			$result[ 'error' ][] = 'Invalid subscription id';
		}
		if (empty($subs_status)) {
			$result[ 'error' ][] = 'Invalid subscription status';
		}
		if (empty($subs_start_date)) {
			$result[ 'error' ][] = 'Invalid subscription start date';
		}
		if (!empty($result)) {
			// return error
		} else {
			global $wpdb;

			$update_result = $wpdb->update( 'tds_subscriptions',
				array(
					'status'     => $subs_status,
					'start_date' => $subs_start_date,
					'billing_first_name' => $subs_bill_first_name,
					'billing_last_name' => $subs_bill_last_name,
					'billing_company_name' => $subs_bill_company_name,
					'billing_email' => $subs_bill_email,
					'billing_cui' => $subs_bill_cui,
					'billing_j' => $subs_bill_j,
					'billing_vat_number' => $subs_bill_vat_number,
					'billing_address' => $subs_bill_address,
					'billing_city' => $subs_bill_city,
					'billing_country' => $subs_bill_country,
					'billing_bank_account' => $subs_bill_bank_account,
					'billing_post_code' => $subs_bill_post_code,
					'next_price' => $subs_price,
					'plan_id' => $subs_plan_id
				),
				array( 'id' => $subs_id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
				array( '%d' )
			);

			if ( false !== $update_result ) {
                $plan_data = tds_util::get_plan($subs_plan_id);

                $plan_automatic_delistings = !empty($plan_data['automatic_delistings']) ? unserialize($plan_data['automatic_delistings']) : [];
                $old_status = ( $subs_status == 'active' || $subs_status == 'free' )  ? 'draft' : 'publish';
                $new_status = ( $subs_status == 'active' || $subs_status == 'free' ) ? 'publish' : 'draft';

                tds_util::update_subscriber_articles_status( $subs_user_id, $plan_automatic_delistings, $old_status, $new_status );

				$result[ 'success' ] = true;
			}
		}

		return $result;
	}

	private static function create_wizard_locker(WP_REST_Request $request) {
		$result = [];
        $plan_id_free = $request->get_param('planIdFree');
        $plan_id_month = $request->get_param('planIdMonth');
        $plan_id_year = $request->get_param('planIdYear');

        if (empty($plan_id_free)) {
			$result[ 'error' ][] = 'Invalid plan id free';
		}
		if (empty($plan_id_month)) {
			$result[ 'error' ][] = 'Invalid plan id month';
		}
		if (empty($plan_id_year)) {
			$result[ 'error' ][] = 'Invalid plan id year';
		}
		if (!empty($result)) {
			// return error
		} else {

			$insert_result = wp_insert_post([
				'post_type' => 'tds_locker',
				'post_status' => 'publish',
				'post_title' => 'Wizard Locker (default)',
				'post_name' => 'tds_default_wizard_locker'
			]);

			if ( empty( $insert_result ) ) {
				$result[ 'error' ][] = 'Locker could not be created';
			} else if ( is_wp_error( $insert_result ) ) {
				$result[ 'error' ][] = $insert_result->get_error_message();
			} else {

                if ( TD_THEME_NAME === 'Newsmag' ) {
                    $post_content = '[tdc_zone type="tdc_content"][vc_row tdc_css="eyJhbGwiOnsibWFyZ2luLWJvdHRvbSI6IjQwIiwicGFkZGluZy10b3AiOiI0MCIsImRpc3BsYXkiOiIifSwicGhvbmUiOnsibWFyZ2luLWJvdHRvbSI6IjQwIiwiZGlzcGxheSI6IiJ9LCJwaG9uZV9tYXhfd2lkdGgiOjc2N30="][vc_column width="1/3" tdc_css="eyJwb3J0cmFpdCI6eyJkaXNwbGF5IjoiIn0sInBvcnRyYWl0X21heF93aWR0aCI6MTAyMywicG9ydHJhaXRfbWluX3dpZHRoIjo3NjgsInBob25lIjp7InBhZGRpbmctcmlnaHQiOiIxNCIsInBhZGRpbmctYm90dG9tIjoiMzAiLCJwYWRkaW5nLWxlZnQiOiIxNCIsImRpc3BsYXkiOiIifSwicGhvbmVfbWF4X3dpZHRoIjo3Njd9"][vc_column_text f_post_font_size="18" tdc_css="eyJhbGwiOnsicGFkZGluZy1ib3R0b20iOiIwIiwiYm9yZGVyLWNvbG9yIjoiI2U2ZTZlNiIsImRpc3BsYXkiOiIifSwicG9ydHJhaXQiOnsibWFyZ2luLXJpZ2h0IjoiMTAiLCJtYXJnaW4tbGVmdCI6IjEwIiwiZGlzcGxheSI6IiJ9LCJwb3J0cmFpdF9tYXhfd2lkdGgiOjEwMjMsInBvcnRyYWl0X21pbl93aWR0aCI6NzY4fQ=="]<span style="color: #888;">Unlock</span>[/vc_column_text][vc_column_text tdc_css="eyJhbGwiOnsibWFyZ2luLXRvcCI6IjAiLCJib3JkZXItdG9wLXdpZHRoIjoiMCIsInBhZGRpbmctdG9wIjoiMCIsImRpc3BsYXkiOiIifSwicG9ydHJhaXQiOnsibWFyZ2luLXJpZ2h0IjoiMTAiLCJtYXJnaW4tbGVmdCI6IjEwIiwiZGlzcGxheSI6IiJ9LCJwb3J0cmFpdF9tYXhfd2lkdGgiOjEwMjMsInBvcnRyYWl0X21pbl93aWR0aCI6NzY4fQ=="]<h3><strong>The Best Experience</strong></h3>[/vc_column_text][vc_column_text tdc_css="eyJhbGwiOnsiYm9yZGVyLXRvcC13aWR0aCI6IjAiLCJkaXNwbGF5IjoiIn0sInBvcnRyYWl0Ijp7Im1hcmdpbi1yaWdodCI6IjEwIiwibWFyZ2luLWxlZnQiOiIxMCIsImRpc3BsYXkiOiIifSwicG9ydHJhaXRfbWF4X3dpZHRoIjoxMDIzLCJwb3J0cmFpdF9taW5fd2lkdGgiOjc2OH0="]<p><span style="color: #888888;">Ut tempor suscipit justo a viverra. Etiam turpis erat, hendrerit quis molestie ut, vestibulum non diam.</span></p><p><span style="color: #888888;">Fusce at tortor tempor, porta elit ut, fringilla risus. Mauris ante ante, vulputate tincidunt eros at, scelerisque dictum justo.</span></p>[/vc_column_text][vc_raw_html tdc_css="eyJhbGwiOnsibWFyZ2luLWxlZnQiOiIyMCIsInBhZGRpbmctdG9wIjoiMjAiLCJwYWRkaW5nLXJpZ2h0IjoiMTYwIiwiZGlzcGxheSI6IiJ9LCJwaG9uZSI6eyJwYWRkaW5nLXJpZ2h0IjoiMjAwIiwiZGlzcGxheSI6IiJ9LCJwaG9uZV9tYXhfd2lkdGgiOjc2NywicG9ydHJhaXQiOnsibWFyZ2luLXJpZ2h0IjoiMTAiLCJtYXJnaW4tbGVmdCI6IjEwIiwicGFkZGluZy1yaWdodCI6IjgwIiwiZGlzcGxheSI6IiJ9LCJwb3J0cmFpdF9tYXhfd2lkdGgiOjEwMjMsInBvcnRyYWl0X21pbl93aWR0aCI6NzY4fQ=="]JTNDJTNGeG1sJTIwdmVyc2lvbiUzRCUyMjEuMCUyMiUyMCUzRiUzRSUzQ3N2ZyUyMHZlcnNpb24lM0QlMjIxLjElMjIlMjB2aWV3Qm94JTNEJTIyMCUyMDAlMjA2MCUyMDYwJTIyJTIweG1sbnMlM0QlMjJodHRwJTNBJTJGJTJGd3d3LnczLm9yZyUyRjIwMDAlMkZzdmclMjIlMjB4bWxucyUzQXhsaW5rJTNEJTIyaHR0cCUzQSUyRiUyRnd3dy53My5vcmclMkYxOTk5JTJGeGxpbmslMjIlM0UlM0N0aXRsZSUyRiUzRSUzQ2Rlc2MlMkYlM0UlM0NkZWZzJTJGJTNFJTNDZyUyMGZpbGwlM0QlMjJub25lJTIyJTIwZmlsbC1ydWxlJTNEJTIyZXZlbm9kZCUyMiUyMGlkJTNEJTIyUGVvcGxlJTIyJTIwc3Ryb2tlJTNEJTIybm9uZSUyMiUyMHN0cm9rZS13aWR0aCUzRCUyMjElMjIlM0UlM0NnJTIwZmlsbCUzRCUyMiUyMzAwMDAwMCUyMiUyMGlkJTNEJTIySWNvbi0xJTIyJTNFJTNDcGF0aCUyMGQlM0QlMjJNNSUyQzE0JTIwQzQuNDQ4JTJDMTQlMjA0JTJDMTQuNDQ4JTIwNCUyQzE1JTIwTDQlMkM0OSUyMEM0JTJDNDkuNTUyJTIwNC40NDglMkM1MCUyMDUlMkM1MCUyMEM1LjU1MiUyQzUwJTIwNiUyQzQ5LjU1MiUyMDYlMkM0OSUyMEw2JTJDMTUlMjBDNiUyQzE0LjQ0OCUyMDUuNTUyJTJDMTQlMjA1JTJDMTQlMjBMNSUyQzE0JTIwWiUyME0yMiUyQzIyJTIwQzIyJTJDMjMuMTAzJTIwMjIuODk3JTJDMjQlMjAyNCUyQzI0JTIwQzI1LjEwMyUyQzI0JTIwMjYlMkMyMy4xMDMlMjAyNiUyQzIyJTIwQzI2JTJDMjAuODk3JTIwMjUuMTAzJTJDMjAlMjAyNCUyQzIwJTIwQzIyLjg5NyUyQzIwJTIwMjIlMkMyMC44OTclMjAyMiUyQzIyJTIwTDIyJTJDMjIlMjBaJTIwTTE4JTJDMzElMjBDMTglMkMzMS41NTIlMjAxOC40NDglMkMzMiUyMDE5JTJDMzIlMjBMMjUlMkMzMiUyMEMyNS4yNjUlMkMzMiUyMDI1LjUyJTJDMzEuODk1JTIwMjUuNzA3JTJDMzEuNzA3JTIwTDMyJTJDMjUuNDE0JTIwTDM0LjI5MyUyQzI3LjcwNyUyMEMzNC42ODQlMkMyOC4wOTglMjAzNS4zMTYlMkMyOC4wOTglMjAzNS43MDclMkMyNy43MDclMjBMNDAlMkMyMy40MTQlMjBMNDQuMjkzJTJDMjcuNzA3JTIwQzQ0LjY4NCUyQzI4LjA5OCUyMDQ1LjMxNiUyQzI4LjA5OCUyMDQ1LjcwNyUyQzI3LjcwNyUyMEM0Ni4wOTglMkMyNy4zMTYlMjA0Ni4wOTglMkMyNi42ODQlMjA0NS43MDclMkMyNi4yOTMlMjBMNDAuNzA3JTJDMjEuMjkzJTIwQzQwLjMxNiUyQzIwLjkwMiUyMDM5LjY4NCUyQzIwLjkwMiUyMDM5LjI5MyUyQzIxLjI5MyUyMEwzNSUyQzI1LjU4NiUyMEwzMi43MDclMkMyMy4yOTMlMjBDMzIuMzE2JTJDMjIuOTAyJTIwMzEuNjg0JTJDMjIuOTAyJTIwMzEuMjkzJTJDMjMuMjkzJTIwTDI0LjU4NiUyQzMwJTIwTDIwJTJDMzAlMjBMMjAlMkMxOCUyMEw0OCUyQzE4JTIwTDQ4JTJDMzAlMjBMMzElMkMzMCUyMEMzMC40NDglMkMzMCUyMDMwJTJDMzAuNDQ4JTIwMzAlMkMzMSUyMEMzMCUyQzMxLjU1MiUyMDMwLjQ0OCUyQzMyJTIwMzElMkMzMiUyMEw0OSUyQzMyJTIwQzQ5LjU1MiUyQzMyJTIwNTAlMkMzMS41NTIlMjA1MCUyQzMxJTIwTDUwJTJDMTclMjBDNTAlMkMxNi40NDglMjA0OS41NTIlMkMxNiUyMDQ5JTJDMTYlMjBMMTklMkMxNiUyMEMxOC40NDglMkMxNiUyMDE4JTJDMTYuNDQ4JTIwMTglMkMxNyUyMEwxOCUyQzMxJTIwWiUyME00NyUyQzQ4JTIwTDM3JTJDNDglMjBDMzYuNDQ4JTJDNDglMjAzNiUyQzQ4LjQ0OCUyMDM2JTJDNDklMjBDMzYlMkM0OS41NTIlMjAzNi40NDglMkM1MCUyMDM3JTJDNTAlMjBMNDclMkM1MCUyMEM0Ny41NTIlMkM1MCUyMDQ4JTJDNDkuNTUyJTIwNDglMkM0OSUyMEM0OCUyQzQ4LjQ0OCUyMDQ3LjU1MiUyQzQ4JTIwNDclMkM0OCUyMEw0NyUyQzQ4JTIwWiUyME0xOSUyQzUwJTIwTDI5JTJDNTAlMjBDMjkuNTUyJTJDNTAlMjAzMCUyQzQ5LjU1MiUyMDMwJTJDNDklMjBDMzAlMkM0OC40NDglMjAyOS41NTIlMkM0OCUyMDI5JTJDNDglMjBMMTklMkM0OCUyMEMxOC40NDglMkM0OCUyMDE4JTJDNDguNDQ4JTIwMTglMkM0OSUyMEMxOCUyQzQ5LjU1MiUyMDE4LjQ0OCUyQzUwJTIwMTklMkM1MCUyMEwxOSUyQzUwJTIwWiUyME00NyUyQzQyJTIwTDM3JTJDNDIlMjBDMzYuNDQ4JTJDNDIlMjAzNiUyQzQyLjQ0OCUyMDM2JTJDNDMlMjBDMzYlMkM0My41NTIlMjAzNi40NDglMkM0NCUyMDM3JTJDNDQlMjBMNDclMkM0NCUyMEM0Ny41NTIlMkM0NCUyMDQ4JTJDNDMuNTUyJTIwNDglMkM0MyUyMEM0OCUyQzQyLjQ0OCUyMDQ3LjU1MiUyQzQyJTIwNDclMkM0MiUyMEw0NyUyQzQyJTIwWiUyME0xOSUyQzQ0JTIwTDMxJTJDNDQlMjBDMzEuNTUyJTJDNDQlMjAzMiUyQzQzLjU1MiUyMDMyJTJDNDMlMjBDMzIlMkM0Mi40NDglMjAzMS41NTIlMkM0MiUyMDMxJTJDNDIlMjBMMTklMkM0MiUyMEMxOC40NDglMkM0MiUyMDE4JTJDNDIuNDQ4JTIwMTglMkM0MyUyMEMxOCUyQzQzLjU1MiUyMDE4LjQ0OCUyQzQ0JTIwMTklMkM0NCUyMEwxOSUyQzQ0JTIwWiUyME01MCUyQzM3JTIwQzUwJTJDMzYuNDQ4JTIwNDkuNTUyJTJDMzYlMjA0OSUyQzM2JTIwTDM3JTJDMzYlMjBDMzYuNDQ4JTJDMzYlMjAzNiUyQzM2LjQ0OCUyMDM2JTJDMzclMjBDMzYlMkMzNy41NTIlMjAzNi40NDglMkMzOCUyMDM3JTJDMzglMjBMNDklMkMzOCUyMEM0OS41NTIlMkMzOCUyMDUwJTJDMzcuNTUyJTIwNTAlMkMzNyUyMEw1MCUyQzM3JTIwWiUyME00OSUyQzEyJTIwQzQ5LjU1MiUyQzEyJTIwNTAlMkMxMS41NTIlMjA1MCUyQzExJTIwQzUwJTJDMTAuNDQ4JTIwNDkuNTUyJTJDMTAlMjA0OSUyQzEwJTIwTDQ1JTJDMTAlMjBDNDQuNDQ4JTJDMTAlMjA0NCUyQzEwLjQ0OCUyMDQ0JTJDMTElMjBDNDQlMkMxMS41NTIlMjA0NC40NDglMkMxMiUyMDQ1JTJDMTIlMjBMNDklMkMxMiUyMFolMjBNMTklMkMxMiUyMEwzNSUyQzEyJTIwQzM1LjU1MiUyQzEyJTIwMzYlMkMxMS41NTIlMjAzNiUyQzExJTIwQzM2JTJDMTAuNDQ4JTIwMzUuNTUyJTJDMTAlMjAzNSUyQzEwJTIwTDE5JTJDMTAlMjBDMTguNDQ4JTJDMTAlMjAxOCUyQzEwLjQ0OCUyMDE4JTJDMTElMjBDMTglMkMxMS41NTIlMjAxOC40NDglMkMxMiUyMDE5JTJDMTIlMjBMMTklMkMxMiUyMFolMjBNMTklMkMzOCUyMEwyOSUyQzM4JTIwQzI5LjU1MiUyQzM4JTIwMzAlMkMzNy41NTIlMjAzMCUyQzM3JTIwQzMwJTJDMzYuNDQ4JTIwMjkuNTUyJTJDMzYlMjAyOSUyQzM2JTIwTDE5JTJDMzYlMjBDMTguNDQ4JTJDMzYlMjAxOCUyQzM2LjQ0OCUyMDE4JTJDMzclMjBDMTglMkMzNy41NTIlMjAxOC40NDglMkMzOCUyMDE5JTJDMzglMjBMMTklMkMzOCUyMFolMjBNNjAlMkM1JTIwTDYwJTJDNTElMjBDNjAlMkM1Ni41NTElMjA1Ni41NTElMkM2MCUyMDUxJTJDNjAlMjBMOSUyQzYwJTIwQzMuNDQ5JTJDNjAlMjAwJTJDNTYuNTUxJTIwMCUyQzUxJTIwTDAlMkMxMyUyMEMwJTJDMTAuMjQzJTIwMi4yNDMlMkM4JTIwNSUyQzglMjBDNS41NTIlMkM4JTIwNiUyQzguNDQ4JTIwNiUyQzklMjBDNiUyQzkuNTUyJTIwNS41NTIlMkMxMCUyMDUlMkMxMCUyMEMzLjM0NiUyQzEwJTIwMiUyQzExLjM0NiUyMDIlMkMxMyUyMEwyJTJDNTElMjBDMiUyQzU1LjQ0OSUyMDQuNTUxJTJDNTglMjA5JTJDNTglMjBMNTElMkM1OCUyMEM1NS40NDklMkM1OCUyMDU4JTJDNTUuNDQ5JTIwNTglMkM1MSUyMEw1OCUyQzUlMjBDNTglMkMzLjM0NiUyMDU2LjY1NCUyQzIlMjA1NSUyQzIlMjBMMTMlMkMyJTIwQzExLjQwMiUyQzIlMjAxMCUyQzMuNDAyJTIwMTAlMkM1JTIwTDEwJTJDNTMlMjBDMTAlMkM1My41NTIlMjA5LjU1MiUyQzU0JTIwOSUyQzU0JTIwQzguNDQ4JTJDNTQlMjA4JTJDNTMuNTUyJTIwOCUyQzUzJTIwTDglMkM1JTIwQzglMkMyLjI5JTIwMTAuMjklMkMwJTIwMTMlMkMwJTIwTDU1JTJDMCUyMEM1Ny43NTclMkMwJTIwNjAlMkMyLjI0MyUyMDYwJTJDNSUyMEw2MCUyQzUlMjBaJTIyJTIwaWQlM0QlMjJuZXdzcGFwZXIlMjIlMkYlM0UlM0MlMkZnJTNFJTNDJTJGZyUzRSUzQyUyRnN2ZyUzRQ==[/vc_raw_html][/vc_column][vc_column width="1/3" tdc_css="eyJhbGwiOnsiYmFja2dyb3VuZC1jb2xvciI6IiNmOGY4ZjgiLCJkaXNwbGF5IjoiIn0sInBob25lIjp7Im1hcmdpbi1ib3R0b20iOiIwIiwicGFkZGluZy1ib3R0b20iOiIxMCIsImRpc3BsYXkiOiIifSwicGhvbmVfbWF4X3dpZHRoIjo3NjcsInBvcnRyYWl0Ijp7ImRpc3BsYXkiOiIifSwicG9ydHJhaXRfbWF4X3dpZHRoIjoxMDIzLCJwb3J0cmFpdF9taW5fd2lkdGgiOjc2OH0=" column_height="eyJwb3J0cmFpdCI6IjYwMyIsImFsbCI6IjU1NnB4IiwicGhvbmUiOiIwIn0=" vertical_align=""][vc_column_text tdc_css="eyJhbGwiOnsiYm9yZGVyLWJvdHRvbS13aWR0aCI6IjEiLCJib3JkZXItY29sb3IiOiIjZTZlNmU2IiwiZGlzcGxheSI6ImJsb2NrIn19" f_h3_font_weight="700"]<h3>Free</h3>[/vc_column_text][tds_plans_price curr_txt="$" free_plan="' . $plan_id_free . '" inline="yes" f_price_font_size="36" vert_align="baseline" tdc_css="eyJhbGwiOnsiYm9yZGVyLXRvcC13aWR0aCI6IjAiLCJwYWRkaW5nLXJpZ2h0IjoiMTAiLCJkaXNwbGF5IjoiIn19"][tds_plans_description year_plan_desc="JTJGeWVhcg==" month_plan_desc="JTJGJTIwbW9udGg=" inline="yes" free_plan_desc="JTJGJTIwZm9yZXZlcg==" tdc_css="eyJhbGwiOnsicGFkZGluZy1sZWZ0IjoiMCIsImRpc3BsYXkiOiJpbmxpbmUtYmxvY2sifX0=" vert_align="baseline" f_descr_font_size="14" f_descr_font_line_height="1"][tds_plans_button display="full" all_border="2" text_color="#4db2ec" bg_color="#ffffff" all_border_color="#4db2ec" horiz_align="content-horiz-center" border_radius="5" padd="16px 24px 18px" free_plan="' . $plan_id_free . '" f_txt_font_weight="700" bg_color_h="#ffffff"][vc_column_text tdc_css="eyJhbGwiOnsiYm9yZGVyLXRvcC13aWR0aCI6IjAiLCJkaXNwbGF5IjoiIn0sInBvcnRyYWl0Ijp7Im1hcmdpbi1yaWdodCI6IjEwIiwibWFyZ2luLWxlZnQiOiIxMCIsImRpc3BsYXkiOiIifSwicG9ydHJhaXRfbWF4X3dpZHRoIjoxMDIzLCJwb3J0cmFpdF9taW5fd2lkdGgiOjc2OH0=" f_list_font_line_height="eyJwb3J0cmFpdCI6IjEuNyIsImFsbCI6IjIifQ=="]<span style="color: #888888;"><strong>What you\'ll get</strong></span>[/vc_column_text][vc_column_text f_list_font_line_height="eyJhbGwiOiIyIiwicG9ydHJhaXQiOiIxLjcifQ==" border_top="no_border_top" tdc_css="eyJhbGwiOnsibWFyZ2luLXRvcCI6Ii0xNSIsIm1hcmdpbi1sZWZ0IjoiMCIsInBhZGRpbmctdG9wIjoiMCIsImRpc3BsYXkiOiIifSwicG9ydHJhaXQiOnsiZGlzcGxheSI6IiJ9LCJwb3J0cmFpdF9tYXhfd2lkdGgiOjEwMjMsInBvcnRyYWl0X21pbl93aWR0aCI6NzY4fQ=="]<ul><li><strong><span style="color: #444444;">Etiam est nibh, lobortis sit</span></strong></li><li><strong><span style="color: #444444;">Praesent euismod ac</span></strong></li><li><strong><span style="color: #444444;">Ut mollis pellentesque tortor</span></strong></li><li><strong><span style="color: #444444;">Nullam eu erat condimentum</span></strong></li></ul>[/vc_column_text][/vc_column][vc_column width="1/3" tdc_css="eyJhbGwiOnsiYmFja2dyb3VuZC1jb2xvciI6IiNlNWYzZmYiLCJkaXNwbGF5IjoiIn0sInBob25lIjp7InBhZGRpbmctYm90dG9tIjoiMjAiLCJkaXNwbGF5IjoiIn0sInBob25lX21heF93aWR0aCI6NzY3fQ=="][vc_column_text f_h3_font_weight="700" tdc_css="eyJhbGwiOnsiYm9yZGVyLWJvdHRvbS13aWR0aCI6IjEiLCJib3JkZXItY29sb3IiOiIjZTZlNmU2IiwiZGlzcGxheSI6IiJ9fQ=="]<h3>Pro</h3>[/vc_column_text][tds_plans_price curr_txt="$" free_plan="" year_plan="' . $plan_id_year . '" month_plan="' . $plan_id_month . '" inline="yes" f_price_font_size="36" vert_align="baseline" tdc_css="eyJhbGwiOnsiYm9yZGVyLXRvcC13aWR0aCI6IjAiLCJwYWRkaW5nLXJpZ2h0IjoiMTAiLCJkaXNwbGF5IjoiIn19"][tds_plans_description inline="yes" tdc_css="eyJhbGwiOnsicGFkZGluZy1sZWZ0IjoiMCIsImRpc3BsYXkiOiJpbmxpbmUtYmxvY2sifX0=" vert_align="baseline" f_descr_font_size="14" f_descr_font_line_height="1" year_plan_desc="JTJGeWVhcg==" month_plan_desc="JTJGJTIwbW9udGg="][tds_plans_button display="full" horiz_align="content-horiz-center" border_radius="5" padd="16px 24px 18px" year_plan="' . $plan_id_year . '" month_plan="' . $plan_id_month . '" bg_color="#4db2ec" f_txt_font_weight="700"][vc_column_text tdc_css="eyJhbGwiOnsiYm9yZGVyLXRvcC13aWR0aCI6IjAiLCJkaXNwbGF5IjoiIn0sInBvcnRyYWl0Ijp7Im1hcmdpbi1yaWdodCI6IjEwIiwibWFyZ2luLWxlZnQiOiIxMCIsImRpc3BsYXkiOiIifSwicG9ydHJhaXRfbWF4X3dpZHRoIjoxMDIzLCJwb3J0cmFpdF9taW5fd2lkdGgiOjc2OH0=" f_list_font_line_height="eyJwb3J0cmFpdCI6IjEuNyIsImFsbCI6IjIifQ=="]<span style="color: #888888;"><strong>What you\'ll get</strong></span>[/vc_column_text][vc_column_text f_list_font_line_height="eyJhbGwiOiIyIiwicG9ydHJhaXQiOiIxLjcifQ==" border_top="no_border_top" tdc_css="eyJhbGwiOnsibWFyZ2luLXRvcCI6Ii0xNSIsIm1hcmdpbi1sZWZ0IjoiMCIsInBhZGRpbmctdG9wIjoiMCIsImRpc3BsYXkiOiIifX0="]<ul><li><strong><span style="color: #444444;">Etiam est nibh, lobortis sit</span></strong></li><li><strong><span style="color: #444444;">Praesent euismod ac</span></strong></li><li><strong><span style="color: #444444;">Ut mollis pellentesque tortor</span></strong></li><li><strong><span style="color: #444444;">Nullam eu erat condimentum</span></strong></li><li><strong><span style="color: #444444;">Donec quis est ac felis</span></strong></li><li><strong><span style="color: #444444;">Orci varius natoque dolor</span></strong></li></ul>[/vc_column_text][tds_plans_switcher tds_plans_switcher1-annual_txt="Yearly pricing" tds_plans_switcher1-monthly_txt="Monthly pricing" tdc_css="eyJwaG9uZSI6eyJwYWRkaW5nLXRvcCI6IjEwIiwicGFkZGluZy1ib3R0b20iOiIyMCIsImRpc3BsYXkiOiIifSwicGhvbmVfbWF4X3dpZHRoIjo3NjcsInBvcnRyYWl0Ijp7InBhZGRpbmctdG9wIjoiMTAiLCJkaXNwbGF5IjoiIn0sInBvcnRyYWl0X21heF93aWR0aCI6MTAyMywicG9ydHJhaXRfbWluX3dpZHRoIjo3Njh9" tds_plans_switcher="tds_plans_switcher1" tds_plans_switcher1-bg_color="#4db2ec"][/vc_column][/vc_row][/tdc_zone]';
                } else {
                    $post_content = '[tdc_zone type="tdc_content"][vc_row flex_layout="row" flex_vert_align="stretch" flex_wrap="yes" tdc_css="eyJwaG9uZSI6eyJtYXJnaW4tdG9wIjoiMzIiLCJtYXJnaW4tcmlnaHQiOiItMjAiLCJtYXJnaW4tYm90dG9tIjoiMzIiLCJtYXJnaW4tbGVmdCI6Ii0yMCIsIndpZHRoIjoiYXV0byIsImRpc3BsYXkiOiIifSwicGhvbmVfbWF4X3dpZHRoIjo3NjcsImFsbCI6eyJtYXJnaW4tdG9wIjoiNDgiLCJtYXJnaW4tYm90dG9tIjoiNDgiLCJkaXNwbGF5IjoiIn19"][vc_column width="1/3" flex_width="eyJwb3J0cmFpdCI6IjEwMCUifQ==" tdc_css="eyJhbGwiOnsicGFkZGluZy10b3AiOiIyOCIsInBhZGRpbmctcmlnaHQiOiI2MCIsInBhZGRpbmctYm90dG9tIjoiMzgiLCJwYWRkaW5nLWxlZnQiOiIzMCIsImRpc3BsYXkiOiIifSwicGhvbmUiOnsicGFkZGluZy10b3AiOiIyMiIsInBhZGRpbmctcmlnaHQiOiIyMCIsInBhZGRpbmctYm90dG9tIjoiNDIiLCJwYWRkaW5nLWxlZnQiOiIyMCIsImRpc3BsYXkiOiIifSwicGhvbmVfbWF4X3dpZHRoIjo3NjcsInBvcnRyYWl0Ijp7InBhZGRpbmctdG9wIjoiMjMiLCJwYWRkaW5nLXJpZ2h0IjoiMjUiLCJwYWRkaW5nLWJvdHRvbSI6IjQzIiwicGFkZGluZy1sZWZ0IjoiMjUiLCJkaXNwbGF5IjoiIn0sInBvcnRyYWl0X21heF93aWR0aCI6MTAxOCwicG9ydHJhaXRfbWluX3dpZHRoIjo3NjgsImxhbmRzY2FwZSI6eyJwYWRkaW5nLXRvcCI6IjIzIiwicGFkZGluZy1yaWdodCI6IjI1IiwicGFkZGluZy1ib3R0b20iOiIzMyIsInBhZGRpbmctbGVmdCI6IjI1IiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGVfbWF4X3dpZHRoIjoxMTQwLCJsYW5kc2NhcGVfbWluX3dpZHRoIjoxMDE5fQ=="][vc_row_inner flex_layout="eyJhbGwiOiJjb2x1bW4iLCJwb3J0cmFpdCI6InJvdyJ9" flex_vert_align="eyJhbGwiOiJmbGV4LXN0YXJ0IiwicG9ydHJhaXQiOiJjZW50ZXIifQ=="][vc_column_inner width="1/2" flex_width="100%" flex_grow="eyJhbGwiOiJkZWZhdWx0IiwicG9ydHJhaXQiOiJvbiJ9"][tdm_block_column_title title_text="VW5sb2Nr" title_tag="h3" title_size="tdm-title-sm" tds_title1-f_title_font_weight="400" tds_title1-f_title_font_line_height="1.2" tds_title1-f_title_font_size="eyJsYW5kc2NhcGUiOiIxNyIsInBvcnRyYWl0IjoiMTUiLCJwaG9uZSI6IjE3IiwiYWxsIjoiMTkifQ==" tds_title1-title_color="rgba(85,93,102,0.7)"][tdm_block_column_title title_text="VGhlJTIwQmVzdCUyMEV4cGVyaWVuY2U=" title_tag="h3" title_size="tdm-title-sm" tds_title1-f_title_font_weight="700" tds_title1-f_title_font_line_height="1.2" tds_title1-f_title_font_size="eyJsYW5kc2NhcGUiOiIyMSIsInBvcnRyYWl0IjoiMTkiLCJwaG9uZSI6IjIxIn0=" tdc_css="eyJhbGwiOnsibWFyZ2luLXRvcCI6Ii0xMiIsIm1hcmdpbi1ib3R0b20iOiIyNSIsImRpc3BsYXkiOiIifSwibGFuZHNjYXBlIjp7Im1hcmdpbi10b3AiOiItMTQiLCJtYXJnaW4tYm90dG9tIjoiMjMiLCJkaXNwbGF5IjoiIn0sImxhbmRzY2FwZV9tYXhfd2lkdGgiOjExNDAsImxhbmRzY2FwZV9taW5fd2lkdGgiOjEwMTksInBvcnRyYWl0Ijp7Im1hcmdpbi10b3AiOiItMTUiLCJtYXJnaW4tYm90dG9tIjoiMTgiLCJkaXNwbGF5IjoiIn0sInBvcnRyYWl0X21heF93aWR0aCI6MTAxOCwicG9ydHJhaXRfbWluX3dpZHRoIjo3NjgsInBob25lIjp7Im1hcmdpbi10b3AiOiItMTQiLCJtYXJnaW4tYm90dG9tIjoiMjAiLCJkaXNwbGF5IjoiIn0sInBob25lX21heF93aWR0aCI6NzY3fQ=="][tdm_block_inline_text description="VXQlMjB0ZW1wb3IlMjBzdXNjaXBpdCUyMGp1c3RvJTIwYSUyMHZpdmVycmEuJTIwRXRpYW0lMjB0dXJwaXMlMjBlcmF0JTJDJTIwaGVuZHJlcml0JTIwcXVpcyUyMG1vbGVzdGllJTIwdXQlMkMlMjB2ZXN0aWJ1bHVtJTIwbm9uJTIwZGlhbS4=" f_descr_font_size="eyJhbGwiOiIxNCIsImxhbmRzY2FwZSI6IjEzIiwicG9ydHJhaXQiOiIxMyIsInBob25lIjoiMTMifQ==" description_color="rgba(85,93,102,0.9)" tdc_css="eyJhbGwiOnsibWFyZ2luLWJvdHRvbSI6IjE0IiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGUiOnsibWFyZ2luLWJvdHRvbSI6IjEyIiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGVfbWF4X3dpZHRoIjoxMTQwLCJsYW5kc2NhcGVfbWluX3dpZHRoIjoxMDE5LCJwb3J0cmFpdCI6eyJtYXJnaW4tYm90dG9tIjoiMTAiLCJkaXNwbGF5IjoiIn0sInBvcnRyYWl0X21heF93aWR0aCI6MTAxOCwicG9ydHJhaXRfbWluX3dpZHRoIjo3NjgsInBob25lIjp7Im1hcmdpbi1ib3R0b20iOiIxMSIsImRpc3BsYXkiOiIifSwicGhvbmVfbWF4X3dpZHRoIjo3Njd9" f_descr_font_line_height="1.5"][tdm_block_inline_text description="RnVzY2UlMjBhdCUyMHRvcnRvciUyMHRlbXBvciUyQyUyMHBvcnRhJTIwZWxpdCUyMHV0JTJDJTIwZnJpbmdpbGxhJTIwcmlzdXMuJTIwTWF1cmlzJTIwYW50ZSUyMGFudGUlMkMlMjB2dWxwdXRhdGUlMjB0aW5jaWR1bnQlMjBlcm9zJTIwYXQlMkMlMjBzY2VsZXJpc3F1ZSUyMGRpY3R1bSUyMGp1c3RvLg==" f_descr_font_size="eyJhbGwiOiIxNCIsImxhbmRzY2FwZSI6IjEzIiwicG9ydHJhaXQiOiIxMyIsInBob25lIjoiMTMifQ==" description_color="rgba(85,93,102,0.9)" tdc_css="eyJhbGwiOnsiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGUiOnsiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGVfbWF4X3dpZHRoIjoxMTQwLCJsYW5kc2NhcGVfbWluX3dpZHRoIjoxMDE5LCJwb3J0cmFpdCI6eyJkaXNwbGF5IjoiIn0sInBvcnRyYWl0X21heF93aWR0aCI6MTAxOCwicG9ydHJhaXRfbWluX3dpZHRoIjo3NjgsInBob25lIjp7Im1hcmdpbi1ib3R0b20iOiI5IiwiZGlzcGxheSI6IiJ9LCJwaG9uZV9tYXhfd2lkdGgiOjc2N30=" f_descr_font_line_height="1.5"][/vc_column_inner][vc_column_inner width="1/2" flex_width="eyJhbGwiOiIxMDAlIiwicG9ydHJhaXQiOiJhdXRvIn0=" tdc_css="eyJhbGwiOnsicGFkZGluZy10b3AiOiI1MCIsImRpc3BsYXkiOiIifSwibGFuZHNjYXBlIjp7InBhZGRpbmctdG9wIjoiNDUiLCJkaXNwbGF5IjoiIn0sImxhbmRzY2FwZV9tYXhfd2lkdGgiOjExNDAsImxhbmRzY2FwZV9taW5fd2lkdGgiOjEwMTksInBvcnRyYWl0Ijp7InBhZGRpbmctdG9wIjoiMCIsImRpc3BsYXkiOiIifSwicG9ydHJhaXRfbWF4X3dpZHRoIjoxMDE4LCJwb3J0cmFpdF9taW5fd2lkdGgiOjc2OCwicGhvbmUiOnsicGFkZGluZy10b3AiOiIzMiIsImRpc3BsYXkiOiIifSwicGhvbmVfbWF4X3dpZHRoIjo3Njd9"][tdm_block_icon tdicon_id="tdc-font-fa tdc-font-fa-star-o" icon_size="eyJhbGwiOjE1MCwibGFuZHNjYXBlIjoiMTIwIiwicG9ydHJhaXQiOiIxMDAiLCJwaG9uZSI6IjEwMCJ9" icon_spacing="1" svg_code="JTNDJTNGeG1sJTIwdmVyc2lvbiUzRCUyMjEuMCUyMiUyMCUzRiUzRSUzQ3N2ZyUyMHZlcnNpb24lM0QlMjIxLjElMjIlMjB2aWV3Qm94JTNEJTIyMCUyMDAlMjA2MCUyMDYwJTIyJTIweG1sbnMlM0QlMjJodHRwJTNBJTJGJTJGd3d3LnczLm9yZyUyRjIwMDAlMkZzdmclMjIlMjB4bWxucyUzQXhsaW5rJTNEJTIyaHR0cCUzQSUyRiUyRnd3dy53My5vcmclMkYxOTk5JTJGeGxpbmslMjIlM0UlM0N0aXRsZSUyRiUzRSUzQ2Rlc2MlMkYlM0UlM0NkZWZzJTJGJTNFJTNDZyUyMGZpbGwlM0QlMjJub25lJTIyJTIwZmlsbC1ydWxlJTNEJTIyZXZlbm9kZCUyMiUyMGlkJTNEJTIyUGVvcGxlJTIyJTIwc3Ryb2tlJTNEJTIybm9uZSUyMiUyMHN0cm9rZS13aWR0aCUzRCUyMjElMjIlM0UlM0NnJTIwZmlsbCUzRCUyMiUyMzAwMDAwMCUyMiUyMGlkJTNEJTIySWNvbi0xJTIyJTNFJTNDcGF0aCUyMGQlM0QlMjJNNSUyQzE0JTIwQzQuNDQ4JTJDMTQlMjA0JTJDMTQuNDQ4JTIwNCUyQzE1JTIwTDQlMkM0OSUyMEM0JTJDNDkuNTUyJTIwNC40NDglMkM1MCUyMDUlMkM1MCUyMEM1LjU1MiUyQzUwJTIwNiUyQzQ5LjU1MiUyMDYlMkM0OSUyMEw2JTJDMTUlMjBDNiUyQzE0LjQ0OCUyMDUuNTUyJTJDMTQlMjA1JTJDMTQlMjBMNSUyQzE0JTIwWiUyME0yMiUyQzIyJTIwQzIyJTJDMjMuMTAzJTIwMjIuODk3JTJDMjQlMjAyNCUyQzI0JTIwQzI1LjEwMyUyQzI0JTIwMjYlMkMyMy4xMDMlMjAyNiUyQzIyJTIwQzI2JTJDMjAuODk3JTIwMjUuMTAzJTJDMjAlMjAyNCUyQzIwJTIwQzIyLjg5NyUyQzIwJTIwMjIlMkMyMC44OTclMjAyMiUyQzIyJTIwTDIyJTJDMjIlMjBaJTIwTTE4JTJDMzElMjBDMTglMkMzMS41NTIlMjAxOC40NDglMkMzMiUyMDE5JTJDMzIlMjBMMjUlMkMzMiUyMEMyNS4yNjUlMkMzMiUyMDI1LjUyJTJDMzEuODk1JTIwMjUuNzA3JTJDMzEuNzA3JTIwTDMyJTJDMjUuNDE0JTIwTDM0LjI5MyUyQzI3LjcwNyUyMEMzNC42ODQlMkMyOC4wOTglMjAzNS4zMTYlMkMyOC4wOTglMjAzNS43MDclMkMyNy43MDclMjBMNDAlMkMyMy40MTQlMjBMNDQuMjkzJTJDMjcuNzA3JTIwQzQ0LjY4NCUyQzI4LjA5OCUyMDQ1LjMxNiUyQzI4LjA5OCUyMDQ1LjcwNyUyQzI3LjcwNyUyMEM0Ni4wOTglMkMyNy4zMTYlMjA0Ni4wOTglMkMyNi42ODQlMjA0NS43MDclMkMyNi4yOTMlMjBMNDAuNzA3JTJDMjEuMjkzJTIwQzQwLjMxNiUyQzIwLjkwMiUyMDM5LjY4NCUyQzIwLjkwMiUyMDM5LjI5MyUyQzIxLjI5MyUyMEwzNSUyQzI1LjU4NiUyMEwzMi43MDclMkMyMy4yOTMlMjBDMzIuMzE2JTJDMjIuOTAyJTIwMzEuNjg0JTJDMjIuOTAyJTIwMzEuMjkzJTJDMjMuMjkzJTIwTDI0LjU4NiUyQzMwJTIwTDIwJTJDMzAlMjBMMjAlMkMxOCUyMEw0OCUyQzE4JTIwTDQ4JTJDMzAlMjBMMzElMkMzMCUyMEMzMC40NDglMkMzMCUyMDMwJTJDMzAuNDQ4JTIwMzAlMkMzMSUyMEMzMCUyQzMxLjU1MiUyMDMwLjQ0OCUyQzMyJTIwMzElMkMzMiUyMEw0OSUyQzMyJTIwQzQ5LjU1MiUyQzMyJTIwNTAlMkMzMS41NTIlMjA1MCUyQzMxJTIwTDUwJTJDMTclMjBDNTAlMkMxNi40NDglMjA0OS41NTIlMkMxNiUyMDQ5JTJDMTYlMjBMMTklMkMxNiUyMEMxOC40NDglMkMxNiUyMDE4JTJDMTYuNDQ4JTIwMTglMkMxNyUyMEwxOCUyQzMxJTIwWiUyME00NyUyQzQ4JTIwTDM3JTJDNDglMjBDMzYuNDQ4JTJDNDglMjAzNiUyQzQ4LjQ0OCUyMDM2JTJDNDklMjBDMzYlMkM0OS41NTIlMjAzNi40NDglMkM1MCUyMDM3JTJDNTAlMjBMNDclMkM1MCUyMEM0Ny41NTIlMkM1MCUyMDQ4JTJDNDkuNTUyJTIwNDglMkM0OSUyMEM0OCUyQzQ4LjQ0OCUyMDQ3LjU1MiUyQzQ4JTIwNDclMkM0OCUyMEw0NyUyQzQ4JTIwWiUyME0xOSUyQzUwJTIwTDI5JTJDNTAlMjBDMjkuNTUyJTJDNTAlMjAzMCUyQzQ5LjU1MiUyMDMwJTJDNDklMjBDMzAlMkM0OC40NDglMjAyOS41NTIlMkM0OCUyMDI5JTJDNDglMjBMMTklMkM0OCUyMEMxOC40NDglMkM0OCUyMDE4JTJDNDguNDQ4JTIwMTglMkM0OSUyMEMxOCUyQzQ5LjU1MiUyMDE4LjQ0OCUyQzUwJTIwMTklMkM1MCUyMEwxOSUyQzUwJTIwWiUyME00NyUyQzQyJTIwTDM3JTJDNDIlMjBDMzYuNDQ4JTJDNDIlMjAzNiUyQzQyLjQ0OCUyMDM2JTJDNDMlMjBDMzYlMkM0My41NTIlMjAzNi40NDglMkM0NCUyMDM3JTJDNDQlMjBMNDclMkM0NCUyMEM0Ny41NTIlMkM0NCUyMDQ4JTJDNDMuNTUyJTIwNDglMkM0MyUyMEM0OCUyQzQyLjQ0OCUyMDQ3LjU1MiUyQzQyJTIwNDclMkM0MiUyMEw0NyUyQzQyJTIwWiUyME0xOSUyQzQ0JTIwTDMxJTJDNDQlMjBDMzEuNTUyJTJDNDQlMjAzMiUyQzQzLjU1MiUyMDMyJTJDNDMlMjBDMzIlMkM0Mi40NDglMjAzMS41NTIlMkM0MiUyMDMxJTJDNDIlMjBMMTklMkM0MiUyMEMxOC40NDglMkM0MiUyMDE4JTJDNDIuNDQ4JTIwMTglMkM0MyUyMEMxOCUyQzQzLjU1MiUyMDE4LjQ0OCUyQzQ0JTIwMTklMkM0NCUyMEwxOSUyQzQ0JTIwWiUyME01MCUyQzM3JTIwQzUwJTJDMzYuNDQ4JTIwNDkuNTUyJTJDMzYlMjA0OSUyQzM2JTIwTDM3JTJDMzYlMjBDMzYuNDQ4JTJDMzYlMjAzNiUyQzM2LjQ0OCUyMDM2JTJDMzclMjBDMzYlMkMzNy41NTIlMjAzNi40NDglMkMzOCUyMDM3JTJDMzglMjBMNDklMkMzOCUyMEM0OS41NTIlMkMzOCUyMDUwJTJDMzcuNTUyJTIwNTAlMkMzNyUyMEw1MCUyQzM3JTIwWiUyME00OSUyQzEyJTIwQzQ5LjU1MiUyQzEyJTIwNTAlMkMxMS41NTIlMjA1MCUyQzExJTIwQzUwJTJDMTAuNDQ4JTIwNDkuNTUyJTJDMTAlMjA0OSUyQzEwJTIwTDQ1JTJDMTAlMjBDNDQuNDQ4JTJDMTAlMjA0NCUyQzEwLjQ0OCUyMDQ0JTJDMTElMjBDNDQlMkMxMS41NTIlMjA0NC40NDglMkMxMiUyMDQ1JTJDMTIlMjBMNDklMkMxMiUyMFolMjBNMTklMkMxMiUyMEwzNSUyQzEyJTIwQzM1LjU1MiUyQzEyJTIwMzYlMkMxMS41NTIlMjAzNiUyQzExJTIwQzM2JTJDMTAuNDQ4JTIwMzUuNTUyJTJDMTAlMjAzNSUyQzEwJTIwTDE5JTJDMTAlMjBDMTguNDQ4JTJDMTAlMjAxOCUyQzEwLjQ0OCUyMDE4JTJDMTElMjBDMTglMkMxMS41NTIlMjAxOC40NDglMkMxMiUyMDE5JTJDMTIlMjBMMTklMkMxMiUyMFolMjBNMTklMkMzOCUyMEwyOSUyQzM4JTIwQzI5LjU1MiUyQzM4JTIwMzAlMkMzNy41NTIlMjAzMCUyQzM3JTIwQzMwJTJDMzYuNDQ4JTIwMjkuNTUyJTJDMzYlMjAyOSUyQzM2JTIwTDE5JTJDMzYlMjBDMTguNDQ4JTJDMzYlMjAxOCUyQzM2LjQ0OCUyMDE4JTJDMzclMjBDMTglMkMzNy41NTIlMjAxOC40NDglMkMzOCUyMDE5JTJDMzglMjBMMTklMkMzOCUyMFolMjBNNjAlMkM1JTIwTDYwJTJDNTElMjBDNjAlMkM1Ni41NTElMjA1Ni41NTElMkM2MCUyMDUxJTJDNjAlMjBMOSUyQzYwJTIwQzMuNDQ5JTJDNjAlMjAwJTJDNTYuNTUxJTIwMCUyQzUxJTIwTDAlMkMxMyUyMEMwJTJDMTAuMjQzJTIwMi4yNDMlMkM4JTIwNSUyQzglMjBDNS41NTIlMkM4JTIwNiUyQzguNDQ4JTIwNiUyQzklMjBDNiUyQzkuNTUyJTIwNS41NTIlMkMxMCUyMDUlMkMxMCUyMEMzLjM0NiUyQzEwJTIwMiUyQzExLjM0NiUyMDIlMkMxMyUyMEwyJTJDNTElMjBDMiUyQzU1LjQ0OSUyMDQuNTUxJTJDNTglMjA5JTJDNTglMjBMNTElMkM1OCUyMEM1NS40NDklMkM1OCUyMDU4JTJDNTUuNDQ5JTIwNTglMkM1MSUyMEw1OCUyQzUlMjBDNTglMkMzLjM0NiUyMDU2LjY1NCUyQzIlMjA1NSUyQzIlMjBMMTMlMkMyJTIwQzExLjQwMiUyQzIlMjAxMCUyQzMuNDAyJTIwMTAlMkM1JTIwTDEwJTJDNTMlMjBDMTAlMkM1My41NTIlMjA5LjU1MiUyQzU0JTIwOSUyQzU0JTIwQzguNDQ4JTJDNTQlMjA4JTJDNTMuNTUyJTIwOCUyQzUzJTIwTDglMkM1JTIwQzglMkMyLjI5JTIwMTAuMjklMkMwJTIwMTMlMkMwJTIwTDU1JTJDMCUyMEM1Ny43NTclMkMwJTIwNjAlMkMyLjI0MyUyMDYwJTJDNSUyMEw2MCUyQzUlMjBaJTIyJTIwaWQlM0QlMjJuZXdzcGFwZXIlMjIlMkYlM0UlM0MlMkZnJTNFJTNDJTJGZyUzRSUzQyUyRnN2ZyUzRQ==" tds_icon1-color="rgba(144,156,175,0.2)" tdc_css="eyJhbGwiOnsiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGUiOnsiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGVfbWF4X3dpZHRoIjoxMTQwLCJsYW5kc2NhcGVfbWluX3dpZHRoIjoxMDE5LCJwb3J0cmFpdCI6eyJkaXNwbGF5IjoiIn0sInBvcnRyYWl0X21heF93aWR0aCI6MTAxOCwicG9ydHJhaXRfbWluX3dpZHRoIjo3Njh9"][/vc_column_inner][/vc_row_inner][/vc_column][vc_column tdc_css="eyJhbGwiOnsicGFkZGluZy10b3AiOiIyOCIsInBhZGRpbmctcmlnaHQiOiIzMCIsInBhZGRpbmctYm90dG9tIjoiMzgiLCJwYWRkaW5nLWxlZnQiOiIzMCIsImJhY2tncm91bmQtY29sb3IiOiIjZjhmOGY4IiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGUiOnsicGFkZGluZy10b3AiOiIyMyIsInBhZGRpbmctcmlnaHQiOiIyNSIsInBhZGRpbmctYm90dG9tIjoiMzMiLCJwYWRkaW5nLWxlZnQiOiIyNSIsImRpc3BsYXkiOiIifSwibGFuZHNjYXBlX21heF93aWR0aCI6MTE0MCwibGFuZHNjYXBlX21pbl93aWR0aCI6MTAxOSwicG9ydHJhaXQiOnsicGFkZGluZy10b3AiOiIyMyIsInBhZGRpbmctcmlnaHQiOiIyNSIsInBhZGRpbmctYm90dG9tIjoiMzMiLCJwYWRkaW5nLWxlZnQiOiIyNSIsImRpc3BsYXkiOiIifSwicG9ydHJhaXRfbWF4X3dpZHRoIjoxMDE4LCJwb3J0cmFpdF9taW5fd2lkdGgiOjc2OCwicGhvbmUiOnsicGFkZGluZy10b3AiOiIyMiIsInBhZGRpbmctcmlnaHQiOiIyMCIsInBhZGRpbmctYm90dG9tIjoiMzIiLCJwYWRkaW5nLWxlZnQiOiIyMCIsImRpc3BsYXkiOiIifSwicGhvbmVfbWF4X3dpZHRoIjo3Njd9" flex_width="eyJwb3J0cmFpdCI6IjUwJSJ9" width="1/3"][tdm_block_column_title title_size="tdm-title-sm" tds_title="tds_title2" tds_title2-f_title_font_line_height="1.2" tds_title2-f_title_font_weight="700" tds_title2-line_width="15" tds_title2-line_height="3" tds_title2-line_alignment="-100" tds_title2-line_space="15" tds_title2-line_color="eyJ0eXBlIjoiZ3JhZGllbnQiLCJjb2xvcjEiOiIjMTUyYmY3IiwiY29sb3IyIjoiIzE1MmJmNyIsIm1peGVkQ29sb3JzIjpbXSwiZGVncmVlIjoiLTkwIiwiY3NzIjoiYmFja2dyb3VuZC1jb2xvcjogIzE1MmJmNzsiLCJjc3NQYXJhbXMiOiIwZGVnLCMxNTJiZjcsIzE1MmJmNyJ9" tdc_css="eyJhbGwiOnsibWFyZ2luLWJvdHRvbSI6IjI0IiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGUiOnsibWFyZ2luLWJvdHRvbSI6IjIyIiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGVfbWF4X3dpZHRoIjoxMTQwLCJsYW5kc2NhcGVfbWluX3dpZHRoIjoxMDE5LCJwb3J0cmFpdCI6eyJtYXJnaW4tYm90dG9tIjoiMTkiLCJkaXNwbGF5IjoiIn0sInBvcnRyYWl0X21heF93aWR0aCI6MTAxOCwicG9ydHJhaXRfbWluX3dpZHRoIjo3NjgsInBob25lIjp7Im1hcmdpbi1ib3R0b20iOiIyMCIsImRpc3BsYXkiOiIifSwicGhvbmVfbWF4X3dpZHRoIjo3Njd9" tds_title2-title_color="#1d2327" tds_title2-f_title_font_size="eyJsYW5kc2NhcGUiOiIyMSIsInBvcnRyYWl0IjoiMTkiLCJwaG9uZSI6IjIxIn0=" title_text="RnJlZQ==" title_tag="h3"][tds_plans_price tdc_css="eyJhbGwiOnsibWFyZ2luLWJvdHRvbSI6IjAiLCJkaXNwbGF5IjoiIn19" f_price_font_size="eyJhbGwiOiIzNiIsImxhbmRzY2FwZSI6IjM0IiwicG9ydHJhaXQiOiIzMiIsInBob25lIjoiMzQifQ==" price_color="#1d2327" vert_align="baseline" inline="yes" free_plan="' . $plan_id_free . '" year_plan="' . $plan_id_year . '" month_plan="' . $plan_id_month . '"][tds_plans_description year_plan_desc="JTJGJTIweWVhcg==" month_plan_desc="JTJGJTIwbW9udGg=" inline="yes" tdc_css="eyJhbGwiOnsibWFyZ2luLWxlZnQiOiIxMCIsImRpc3BsYXkiOiIifSwibGFuZHNjYXBlIjp7Im1hcmdpbi1sZWZ0IjoiOSIsImRpc3BsYXkiOiIifSwibGFuZHNjYXBlX21heF93aWR0aCI6MTE0MCwibGFuZHNjYXBlX21pbl93aWR0aCI6MTAxOSwicG9ydHJhaXQiOnsibWFyZ2luLWxlZnQiOiI4IiwiZGlzcGxheSI6IiJ9LCJwb3J0cmFpdF9tYXhfd2lkdGgiOjEwMTgsInBvcnRyYWl0X21pbl93aWR0aCI6NzY4LCJwaG9uZSI6eyJtYXJnaW4tbGVmdCI6IjkiLCJkaXNwbGF5IjoiIn0sInBob25lX21heF93aWR0aCI6NzY3fQ==" color="#565f6d" f_descr_font_size="eyJhbGwiOiIxNCIsImxhbmRzY2FwZSI6IjEzIiwicG9ydHJhaXQiOiIxMiIsInBob25lIjoiMTMifQ==" f_descr_font_line_height="1" vert_align="baseline" free_plan_desc="JTJGJTIwZm9yZXZlcg=="][tds_plans_button month_plan="' . $plan_id_month . '" year_plan="' . $plan_id_year . '" tdc_css="eyJhbGwiOnsibWFyZ2luLXRvcCI6IjI4IiwibWFyZ2luLWJvdHRvbSI6IjMwIiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGUiOnsibWFyZ2luLXRvcCI6IjI2IiwibWFyZ2luLWJvdHRvbSI6IjI4IiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGVfbWF4X3dpZHRoIjoxMTQwLCJsYW5kc2NhcGVfbWluX3dpZHRoIjoxMDE5LCJwb3J0cmFpdCI6eyJtYXJnaW4tdG9wIjoiMjMiLCJtYXJnaW4tYm90dG9tIjoiMjUiLCJkaXNwbGF5IjoiIn0sInBvcnRyYWl0X21heF93aWR0aCI6MTAxOCwicG9ydHJhaXRfbWluX3dpZHRoIjo3NjgsInBob25lIjp7Im1hcmdpbi10b3AiOiIyNCIsIm1hcmdpbi1ib3R0b20iOiIyNiIsImRpc3BsYXkiOiIifSwicGhvbmVfbWF4X3dpZHRoIjo3Njd9" horiz_align="content-horiz-center" display="full" border_radius="5" f_txt_font_weight="600" padd="eyJhbGwiOiIxNHB4IDI0cHggMTZweCIsImxhbmRzY2FwZSI6IjEzcHggMjJweCAxNXB4IiwicG9ydHJhaXQiOiIxM3B4IDIycHggMTRweCIsInBob25lIjoiMTNweCAyMnB4IDE1cHgifQ==" all_border_color="#0489fc" text_color="#0489fc" bg_color="rgba(21,43,247,0)" all_border="2" bg_color_h="rgba(21,43,247,0)" free_plan="' . $plan_id_free . '" f_txt_font_size="eyJwb3J0cmFpdCI6IjEyIn0="][tdm_block_inline_text f_descr_font_size="eyJhbGwiOiIxNSIsImxhbmRzY2FwZSI6IjE0IiwicG9ydHJhaXQiOiIxMyIsInBob25lIjoiMTQifQ==" description_color="rgba(85,93,102,0.7)" tdc_css="eyJhbGwiOnsibWFyZ2luLWJvdHRvbSI6IjEyIiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGUiOnsibWFyZ2luLWJvdHRvbSI6IjEwIiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGVfbWF4X3dpZHRoIjoxMTQwLCJsYW5kc2NhcGVfbWluX3dpZHRoIjoxMDE5LCJwb3J0cmFpdCI6eyJtYXJnaW4tYm90dG9tIjoiNyIsImRpc3BsYXkiOiIifSwicG9ydHJhaXRfbWF4X3dpZHRoIjoxMDE4LCJwb3J0cmFpdF9taW5fd2lkdGgiOjc2OCwicGhvbmUiOnsibWFyZ2luLWJvdHRvbSI6IjkiLCJkaXNwbGF5IjoiIn0sInBob25lX21heF93aWR0aCI6NzY3fQ==" description="V2hhdCUyMHlvdSdsbCUyMGdldA=="][tdm_block_list content_align_horizontal="content-horiz-left" icon_color="#152bf7" text_color="#444444" f_list_font_weight="600" f_list_font_size="eyJhbGwiOiIxNSIsImxhbmRzY2FwZSI6IjE0IiwicG9ydHJhaXQiOiIxMyIsInBob25lIjoiMTQifQ==" f_list_font_line_height="eyJhbGwiOiIxLjUiLCJwb3J0cmFpdCI6IjEuMzUiLCJwaG9uZSI6IjEuNDUiLCJsYW5kc2NhcGUiOiIxLjQ1In0=" tdc_css="eyJhbGwiOnsibWFyZ2luLWJvdHRvbSI6IjAiLCJkaXNwbGF5IjoiIn19" icon_size="eyJsYW5kc2NhcGUiOiIxNCJ9" items="RXRpYW0lMjBlc3QlMjBuaWJoJTJDJTIwbG9ib3J0aXMlMjBzaXQlMEFQcmFlc2VudCUyMGV1aXNtb2QlMjBhYyUwQVV0JTIwbW9sbGlzJTIwcGVsbGVudGVzcXVlJTIwdG9ydG9yJTBBTnVsbGFtJTIwZXUlMjBlcmF0JTIwY29uZGltZW50dW0=" tdicon="tdc-font-fa tdc-font-fa-check-circle"][tdm_block_list items="RG9uZWMlMjBxdWlzJTIwZXN0JTIwYWMlMjBmZWxpcyUwQU9yY2klMjB2YXJpdXMlMjBuYXRvcXVlJTIwZG9sb3I=" tdicon="tdc-font-fa tdc-font-fa-minus-circle" content_align_horizontal="content-horiz-left" icon_color="#909caf" text_color="#909caf" f_list_font_weight="600" f_list_font_size="eyJhbGwiOiIxNSIsImxhbmRzY2FwZSI6IjE0IiwicG9ydHJhaXQiOiIxMyIsInBob25lIjoiMTQifQ==" f_list_font_line_height="eyJhbGwiOiIxLjUiLCJsYW5kc2NhcGUiOiIxLjQ1IiwicG9ydHJhaXQiOiIxLjM1IiwicGhvbmUiOiIxLjQ1In0=" tdc_css="eyJhbGwiOnsibWFyZ2luLWJvdHRvbSI6IjAiLCJkaXNwbGF5IjoiIn19"][/vc_column][vc_column width="1/3" tdc_css="eyJhbGwiOnsicGFkZGluZy10b3AiOiIyOCIsInBhZGRpbmctcmlnaHQiOiIzMCIsInBhZGRpbmctYm90dG9tIjoiMzgiLCJwYWRkaW5nLWxlZnQiOiIzMCIsImJhY2tncm91bmQtY29sb3IiOiIjZTVmM2ZmIiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGUiOnsicGFkZGluZy10b3AiOiIyMyIsInBhZGRpbmctcmlnaHQiOiIyNSIsInBhZGRpbmctYm90dG9tIjoiMzMiLCJwYWRkaW5nLWxlZnQiOiIyNSIsImRpc3BsYXkiOiIifSwibGFuZHNjYXBlX21heF93aWR0aCI6MTE0MCwibGFuZHNjYXBlX21pbl93aWR0aCI6MTAxOSwicG9ydHJhaXQiOnsicGFkZGluZy10b3AiOiIyMyIsInBhZGRpbmctcmlnaHQiOiIyNSIsInBhZGRpbmctYm90dG9tIjoiMzMiLCJwYWRkaW5nLWxlZnQiOiIyNSIsImRpc3BsYXkiOiIifSwicG9ydHJhaXRfbWF4X3dpZHRoIjoxMDE4LCJwb3J0cmFpdF9taW5fd2lkdGgiOjc2OCwicGhvbmUiOnsicGFkZGluZy10b3AiOiIyMiIsInBhZGRpbmctcmlnaHQiOiIyMCIsInBhZGRpbmctYm90dG9tIjoiMzIiLCJwYWRkaW5nLWxlZnQiOiIyMCIsImRpc3BsYXkiOiIifSwicGhvbmVfbWF4X3dpZHRoIjo3Njd9" flex_width="eyJwb3J0cmFpdCI6IjUwJSJ9"][tdm_block_column_title title_text="UHJv" title_tag="h3" title_size="tdm-title-sm" tds_title1-f_title_font_weight="700" tds_title1-f_title_font_line_height="1.2" tds_title="tds_title2" tds_title2-f_title_font_line_height="1.2" tds_title2-f_title_font_weight="700" tds_title2-line_width="15" tds_title2-line_height="3" tds_title2-line_alignment="-100" tds_title2-line_space="15" tds_title2-line_color="eyJ0eXBlIjoiZ3JhZGllbnQiLCJjb2xvcjEiOiIjMTUyYmY3IiwiY29sb3IyIjoiIzE1MmJmNyIsIm1peGVkQ29sb3JzIjpbXSwiZGVncmVlIjoiLTkwIiwiY3NzIjoiYmFja2dyb3VuZC1jb2xvcjogIzE1MmJmNzsiLCJjc3NQYXJhbXMiOiIwZGVnLCMxNTJiZjcsIzE1MmJmNyJ9" tdc_css="eyJhbGwiOnsibWFyZ2luLWJvdHRvbSI6IjI0IiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGUiOnsibWFyZ2luLWJvdHRvbSI6IjIyIiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGVfbWF4X3dpZHRoIjoxMTQwLCJsYW5kc2NhcGVfbWluX3dpZHRoIjoxMDE5LCJwb3J0cmFpdCI6eyJtYXJnaW4tYm90dG9tIjoiMTkiLCJkaXNwbGF5IjoiIn0sInBvcnRyYWl0X21heF93aWR0aCI6MTAxOCwicG9ydHJhaXRfbWluX3dpZHRoIjo3NjgsInBob25lIjp7Im1hcmdpbi1ib3R0b20iOiIyMCIsImRpc3BsYXkiOiIifSwicGhvbmVfbWF4X3dpZHRoIjo3Njd9" tds_title2-title_color="#1d2327" tds_title2-f_title_font_size="eyJsYW5kc2NhcGUiOiIyMSIsInBvcnRyYWl0IjoiMTkiLCJwaG9uZSI6IjIxIn0="][tds_plans_price month_plan="' . $plan_id_month . '" year_plan="' . $plan_id_year . '" tdc_css="eyJhbGwiOnsibWFyZ2luLWJvdHRvbSI6IjAiLCJkaXNwbGF5IjoiIn19" f_price_font_size="eyJhbGwiOiIzNiIsImxhbmRzY2FwZSI6IjM0IiwicG9ydHJhaXQiOiIzMiIsInBob25lIjoiMzQifQ==" curr_txt="$" price_color="#1d2327" vert_align="baseline" inline="yes" def_plan="" free_plan=""][tds_plans_description year_plan_desc="JTJGJTIweWVhcg==" month_plan_desc="JTJGJTIwbW9udGg=" inline="yes" tdc_css="eyJhbGwiOnsibWFyZ2luLWxlZnQiOiIxMCIsImRpc3BsYXkiOiIifSwibGFuZHNjYXBlIjp7Im1hcmdpbi1sZWZ0IjoiOSIsImRpc3BsYXkiOiIifSwibGFuZHNjYXBlX21heF93aWR0aCI6MTE0MCwibGFuZHNjYXBlX21pbl93aWR0aCI6MTAxOSwicG9ydHJhaXQiOnsibWFyZ2luLWxlZnQiOiI4IiwiZGlzcGxheSI6IiJ9LCJwb3J0cmFpdF9tYXhfd2lkdGgiOjEwMTgsInBvcnRyYWl0X21pbl93aWR0aCI6NzY4LCJwaG9uZSI6eyJtYXJnaW4tbGVmdCI6IjkiLCJkaXNwbGF5IjoiIn0sInBob25lX21heF93aWR0aCI6NzY3fQ==" color="#565f6d" f_descr_font_size="eyJhbGwiOiIxNCIsImxhbmRzY2FwZSI6IjEzIiwicG9ydHJhaXQiOiIxMiIsInBob25lIjoiMTMifQ==" f_descr_font_line_height="1" vert_align="baseline"][tds_plans_button month_plan="' . $plan_id_month . '" year_plan="' . $plan_id_year . '" tdc_css="eyJhbGwiOnsibWFyZ2luLXRvcCI6IjI4IiwibWFyZ2luLWJvdHRvbSI6IjMwIiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGUiOnsibWFyZ2luLXRvcCI6IjI2IiwibWFyZ2luLWJvdHRvbSI6IjI4IiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGVfbWF4X3dpZHRoIjoxMTQwLCJsYW5kc2NhcGVfbWluX3dpZHRoIjoxMDE5LCJwb3J0cmFpdCI6eyJtYXJnaW4tdG9wIjoiMjMiLCJtYXJnaW4tYm90dG9tIjoiMjUiLCJkaXNwbGF5IjoiIn0sInBvcnRyYWl0X21heF93aWR0aCI6MTAxOCwicG9ydHJhaXRfbWluX3dpZHRoIjo3NjgsInBob25lIjp7Im1hcmdpbi10b3AiOiIyNCIsIm1hcmdpbi1ib3R0b20iOiIyNiIsImRpc3BsYXkiOiIifSwicGhvbmVfbWF4X3dpZHRoIjo3Njd9" horiz_align="content-horiz-center" display="eyJsYW5kc2NhcGUiOiJmdWxsIiwiYWxsIjoiZnVsbCJ9" bg_color="#0489fc" border_radius="5" f_txt_font_weight="600" padd="eyJhbGwiOiIxNnB4IDI0cHggMThweCIsImxhbmRzY2FwZSI6IjE1cHggMjJweCAxN3B4IiwicG9ydHJhaXQiOiIxNXB4IDIycHggMTZweCIsInBob25lIjoiMTVweCAyMnB4IDE3cHgifQ==" bg_color_h="eyJ0eXBlIjoiZ3JhZGllbnQiLCJjb2xvcjEiOiIjMTUyYmY3IiwiY29sb3IyIjoiIzE1MmJmNyIsIm1peGVkQ29sb3JzIjpbXSwiZGVncmVlIjoiLTkwIiwiY3NzIjoiYmFja2dyb3VuZC1jb2xvcjogIzE1MmJmNzsiLCJjc3NQYXJhbXMiOiIwZGVnLCMxNTJiZjcsIzE1MmJmNyJ9" free_plan="" f_txt_font_size="eyJwb3J0cmFpdCI6IjEyIn0="][tdm_block_inline_text description="V2hhdCUyMHlvdSdsbCUyMGdldA==" f_descr_font_size="eyJhbGwiOiIxNSIsImxhbmRzY2FwZSI6IjE0IiwicG9ydHJhaXQiOiIxMyIsInBob25lIjoiMTQifQ==" description_color="rgba(85,93,102,0.7)" tdc_css="eyJhbGwiOnsibWFyZ2luLWJvdHRvbSI6IjEyIiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGUiOnsibWFyZ2luLWJvdHRvbSI6IjEwIiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGVfbWF4X3dpZHRoIjoxMTQwLCJsYW5kc2NhcGVfbWluX3dpZHRoIjoxMDE5LCJwb3J0cmFpdCI6eyJtYXJnaW4tYm90dG9tIjoiNyIsImRpc3BsYXkiOiIifSwicG9ydHJhaXRfbWF4X3dpZHRoIjoxMDE4LCJwb3J0cmFpdF9taW5fd2lkdGgiOjc2OCwicGhvbmUiOnsibWFyZ2luLWJvdHRvbSI6IjkiLCJkaXNwbGF5IjoiIn0sInBob25lX21heF93aWR0aCI6NzY3fQ=="][tdm_block_list items="RXRpYW0lMjBlc3QlMjBuaWJoJTJDJTIwbG9ib3J0aXMlMjBzaXQlMEFQcmFlc2VudCUyMGV1aXNtb2QlMjBhYyUwQVV0JTIwbW9sbGlzJTIwcGVsbGVudGVzcXVlJTIwdG9ydG9yJTBBTnVsbGFtJTIwZXUlMjBlcmF0JTIwY29uZGltZW50dW0lMEFEb25lYyUyMHF1aXMlMjBlc3QlMjBhYyUyMGZlbGlzJTBBT3JjaSUyMHZhcml1cyUyMG5hdG9xdWUlMjBkb2xvcg==" tdicon="tdc-font-fa tdc-font-fa-check-circle" content_align_horizontal="content-horiz-left" icon_color="#152bf7" text_color="#444444" f_list_font_weight="600" f_list_font_size="eyJhbGwiOiIxNSIsImxhbmRzY2FwZSI6IjE0IiwicG9ydHJhaXQiOiIxMyIsInBob25lIjoiMTQifQ==" f_list_font_line_height="eyJhbGwiOiIxLjUiLCJwb3J0cmFpdCI6IjEuMzUiLCJwaG9uZSI6IjEuNDUiLCJsYW5kc2NhcGUiOiIxLjQ1In0=" tdc_css="eyJhbGwiOnsibWFyZ2luLWJvdHRvbSI6IjAiLCJkaXNwbGF5IjoiIn19" icon_size="eyJsYW5kc2NhcGUiOiIxNCJ9"][tds_plans_switcher tds_plans_switcher1-annual_txt="Yearly pricing" tds_plans_switcher1-monthly_txt="Monthly pricing" def_plan="" tdc_css="eyJhbGwiOnsibWFyZ2luLXRvcCI6IjM1IiwibWFyZ2luLWJvdHRvbSI6IjAiLCJib3JkZXItdG9wLXdpZHRoIjoiMiIsInBhZGRpbmctdG9wIjoiMzAiLCJib3JkZXItc3R5bGUiOiJkYXNoZWQiLCJib3JkZXItY29sb3IiOiJyZ2JhKDIxLDQzLDI0NywwLjEpIiwiZGlzcGxheSI6IiJ9LCJsYW5kc2NhcGUiOnsibWFyZ2luLXRvcCI6IjMzIiwicGFkZGluZy10b3AiOiIyOCIsImRpc3BsYXkiOiIifSwibGFuZHNjYXBlX21heF93aWR0aCI6MTE0MCwibGFuZHNjYXBlX21pbl93aWR0aCI6MTAxOSwicG9ydHJhaXQiOnsibWFyZ2luLXRvcCI6IjMwIiwicGFkZGluZy10b3AiOiIyNSIsImRpc3BsYXkiOiIifSwicG9ydHJhaXRfbWF4X3dpZHRoIjoxMDE4LCJwb3J0cmFpdF9taW5fd2lkdGgiOjc2OCwicGhvbmUiOnsibWFyZ2luLXRvcCI6IjMxIiwicGFkZGluZy10b3AiOiIyNiIsImRpc3BsYXkiOiIifSwicGhvbmVfbWF4X3dpZHRoIjo3Njd9" tds_plans_switcher1-horiz_align="content-horiz-left" tds_plans_switcher="tds_plans_switcher1" tds_plans_switcher1-all_border="1" tds_plans_switcher1-bg_color="rgba(255,255,255,0)" tds_plans_switcher1-all_border_color="#152bf7" tds_plans_switcher1-dot_bg_color="eyJ0eXBlIjoiZ3JhZGllbnQiLCJjb2xvcjEiOiIjMTUyYmY3IiwiY29sb3IyIjoiIzE1MmJmNyIsIm1peGVkQ29sb3JzIjpbXSwiZGVncmVlIjoiLTkwIiwiY3NzIjoiYmFja2dyb3VuZC1jb2xvcjogIzE1MmJmNzsiLCJjc3NQYXJhbXMiOiIwZGVnLCMxNTJiZjcsIzE1MmJmNyJ9" tds_plans_switcher1-f_label_font_size="eyJhbGwiOiIxMyIsInBvcnRyYWl0IjoiMTIifQ==" tds_plans_switcher1-switch_size="1" tds_plans_switcher1-label_color="#909caf" tds_plans_switcher1-label_color_a="#565f6d" tds_plans_switcher1-label_space="10"][/vc_column][/vc_row][/tdc_zone]';
                }

				$inserted_page_id = wp_insert_post([
					'post_type' => 'page',
					'post_title' => 'Switching plans wizard',
					'post_name' => 'tds-switching-plans-wizard',
					'post_status' => 'publish',
					'post_content' => $post_content
                ]);

				if (!empty($inserted_page_id) && !is_wp_error($inserted_page_id)) {

                    update_post_meta( $insert_result, 'tds_locker_settings', [
                        'tds_title' => 'This Content Is Only For Subscribers',
                        'tds_message' => 'Please subscribe to unlock this content.',
                        'tds_input_placeholder' => '',
                        'tds_submit_btn_text' => 'Subscribe to unlock',
                        'tds_after_btn_text' => '',
                        'tds_pp_msg' => 'I consent to processing of my data according to <a href="#">Terms of Use</a> & <a href="#">Privacy Policy</a>'
                    ] );

					update_post_meta( $insert_result, 'tds_locker_types', [
						'tds_payable'            => 'paid_subscription',
						'tds_paid_subs_plan_ids' => [ $plan_id_free, $plan_id_month, $plan_id_year ],
						'tds_paid_subs_page_id' => $inserted_page_id
					] );

					$result[ 'inserted_id' ] = $insert_result;
					$last_post_permalink     = '';
					$last_post               = get_posts( [ 'numberposts' => 1, 'order' => 'ASC' ] );
					if ( ! empty( $last_post ) && is_array( $last_post ) ) {
						$last_post_permalink = get_permalink( $last_post[ 0 ] );

						$td_post_settings = td_util::get_post_meta_array( $last_post[ 0 ]->ID, 'td_post_theme_settings' );

						$td_post_settings[ 'tds_lock_content' ] = 1;
						$td_post_settings[ 'tds_locker' ]       = $insert_result;

						update_post_meta( $last_post[ 0 ]->ID, 'td_post_theme_settings', $td_post_settings );
					}
					$result[ 'last_post_permalink' ] = $last_post_permalink;
				}
			}
		}

		return $result;
	}

	static function create_wizard_pages() {
		$result = [];

		$items = tds_util::get_wizard_pages();

		$options = [];
		$result['pages'] = [];

		$db_options = tds_ajax::get_all_options();

		foreach ( $items as $name => $item ) {

			$create_page = true;
			if (!empty($db_options['options'])) {
				foreach ($db_options['options'] as $option) {
					if ( !empty($option['name']) && $item['db_id'] === $option['name'] && false !== get_permalink($option['value'])) {
						$create_page = false;
						break;
					}
				}
			}
			if (!$create_page) {
				continue;
			}

			$inserted_page_id = wp_insert_post($item['data']);

			if ( empty( $inserted_page_id ) ) {
				$result[ 'error' ][] = 'Page could not be created';
				break;
			} else if ( is_wp_error( $inserted_page_id ) ) {
				$result[ 'error' ][] = $inserted_page_id->get_error_message();
				break;
			} else {

				$permalink = esc_url(get_permalink($inserted_page_id));
				$result['pages'][] = ['name' => $name, 'id' => $inserted_page_id, 'permalink' => $permalink, 'title' => $item['data']['post_title']];
				$options[] = ['name' => $item['db_id'], 'value' => $inserted_page_id];
			}
		}

		if (!empty($options)) {
			self::set_db_options($options);
		}

		return $result;
	}

	static function create_general_settings() {
		self::set_db_options([
			['name' => 'curr_name', 'value' => 'USD'],
			['name' => 'curr_pos', 'value' => 'left_space'],
			['name' => 'curr_th_sep', 'value' => ','],
			['name' => 'curr_dec_sep', 'value' => '.'],
			['name' => 'curr_dec_no', 'value' => '0'],
		], true);
	}

	static function create_emails_settings() {
		self::set_db_options([
			['name' => 'from_name', 'value' => get_bloginfo('name')],
			['name' => 'from_email', 'value' => get_bloginfo('admin_email')],
			['name' => 'admin_notice_emails', 'value' => get_bloginfo('admin_email')],
			['name' => 'email_logo', 'value' => ''],
			['name' => 'email_footer_text', 'value' => '&copy; ' . get_bloginfo('name')],


			// Email notifications
			['name' => 'register_email_enabled', 'value' => '1'],
			['name' => 'register_email_enabled_admin', 'value' => '1'],
			['name' => 'register_email_subject', 'value' => '[%blogname%] Activate account'],
			['name' => 'register_email_subject_admin', 'value' => '[%blogname%] New user registration'],
			['name' => 'register_email_body', 'value' => 
				'<h3>Welcome onboard!</h3>
				<p>Hello %name%,</p>
				<p>Thank you for registering on %blogname%! To activate your account, please visit the following link:</p>
				<p><a href="%verification_link%">%verification_link%</a></p>'
			],
			['name' => 'register_email_body_admin', 'value' => 
				'<h3>New user!</h3>
				<p>A new user has registered on your website!</p>
				<p>Username: %username%<br>
				Email: %useremail%</p>'
			],
			
			['name' => 'optin_email_enabled', 'value' => '1'],
			['name' => 'optin_email_enabled_admin', 'value' => '0'],
			['name' => 'optin_email_subject', 'value' => '[%blogname%] Confirm subscription'],
			['name' => 'optin_email_subject_admin', 'value' => ''],
			['name' => 'optin_email_body', 'value' => 
				'<h3>Welcome onboard!</h3>
				<p>Hello,</p>
				<p>Thank you for subscribing to %blogname%! To confirm your subscription, please visit the following link:</p>
				<p><a href="%optin_confirm_link%">%optin_confirm_link%</a></p>'
			],
			['name' => 'optin_email_body_admin', 'value' => ''],

			['name' => 'password_email_enabled', 'value' => '1'],
			['name' => 'password_email_enabled_admin', 'value' => '0'],
			['name' => 'password_email_subject', 'value' => '[%blogname%] Password reset'],
			['name' => 'password_email_subject_admin', 'value' => ''],
			['name' => 'password_email_body', 'value' => 
				'<h3>Password reset</h3>
				<p>Hello %name%,</p>
				<p>Someone has requested a password reset for your account.</p>
				<p>To reset your password, visit the following address: <a href="%pass_reset_link%">%pass_reset_link%</a>.</p>
				<p>If this was a mistake, just ignore this email and nothing will happen.</p>'
			],
			['name' => 'password_email_body_admin', 'value' => ''],
			
			['name' => 'subscription_email_enabled', 'value' => '1'],
			['name' => 'subscription_email_enabled_admin', 'value' => '1'],
			['name' => 'subscription_email_subject', 'value' => '[%blogname%] Subscription confirmation'],
			['name' => 'subscription_email_subject_admin', 'value' => '[%blogname%] New subscriber'],
			['name' => 'subscription_email_body', 'value' => 
				'<h3>Subscription confirmation</h3>
				<p>Hello %name%,</p>
				<p>Thank you for subscribing to %blogname%!</p>
				<p>Subscription plan: %subscription_name%<br>
				Subscription price: %subscription_price%</p>
				%direct_bank_info%'
			],
			['name' => 'subscription_email_body_admin', 'value' => 
				'<h3>New subscription</h3>
				<p>A new user has subscribed to your website.</p>
				<p>Username: %username%<br>
				Subscription plan: %subscription_name%<br>
				Subscription price: %subscription_price%</p>'
			],
			
			['name' => 'renewal_email_enabled', 'value' => '1'],
			['name' => 'renewal_email_enabled_admin', 'value' => '0'],
			['name' => 'renewal_email_subject', 'value' => '[%blogname%] Subscription renewal'],
			['name' => 'renewal_email_subject_admin', 'value' => '[%blogname%] Subscription renewal'],
			['name' => 'renewal_email_body', 'value' => 
				'<h3>Subscription renewal</h3>
				<p>Hello %name%,</p>
				<p>Your subscription on %blogname% has been sucessfully renewed.
				Subscription plan: %subscription_name%<br>
				Subscription price: %subscription_price%</p>'
			],
			['name' => 'renewal_email_body_admin', 'value' => 
				'<h3>Subscription renewal</h3>
				<p>An user has successfully renewed their subscription.</p>
				<p>Username: %username%<br>
				Subscription plan: %subscription_name%<br>
				Subscription price: %subscription_price%</p>'
			],
			
			['name' => 'cancel_email_enabled', 'value' => '1'],
			['name' => 'cancel_email_enabled_admin', 'value' => '1'],
			['name' => 'cancel_email_subject', 'value' => '[%blogname%] Subscription canceled'],
			['name' => 'cancel_email_subject_admin', 'value' => '[%blogname%] Subscription canceled'],
			['name' => 'cancel_email_body', 'value' => 
				'<h3>Subscription cancelation</h3>
				<p>Hello %name%,</p>
				<p>We are sorry to see you go! Your subscription on %blogname% has been canceled and is only valid until %subscription_expiry%. You will not be charged in the future.</p>'
			],
			['name' => 'cancel_email_body_admin', 'value' => 
				'<h3>Subscription cancelation</h3>
				<p>An user on your website has canceled their subscription.</p>
				<p>Username: %username%<br>
				Subscription plan: %subscription_name%<br>
				Subscription expiry: %subscription_expiry%</p>'
			],
			
			['name' => 'failed_email_enabled', 'value' => '1'],
			['name' => 'failed_email_enabled_admin', 'value' => '0'],
			['name' => 'failed_email_subject', 'value' => '[%blogname%] Your latest payment has failed'],
			['name' => 'failed_email_subject_admin', 'value' => '[%blogname%] A subscription payment has failed'],
			['name' => 'failed_email_body', 'value' => 
				'<h3>Payment failure</h3>
				<p>Hello %name%,</p>
				<p>Your latest payment for "%subscription_name%" has failed.</p>
				<p>You can go to the <a href="%subscriptions_page_link%">account page</a> in order to try again.</p>'
			],
			['name' => 'failed_email_body_admin', 'value' => 
				'<h3>Payment failure</h3>
				<p>An user on your website has failed to pay for their subscription.</p>
				<p>Username: %username%<br>
				Subscription plan: %subscription_name%<br>
				Subscription price: %subscription_price%</p>'
			],
		], true);
	}

	private static function set_options( WP_REST_Request $request ) {
		$result = [];
        $options = $request->get_param('options');

        if (empty($options)) {
			$result[ 'error' ][] = 'Invalid options';
		}
		if (!empty($result)) {
			// return error
		} else {
			$result = self::set_db_options($options);
		}

		return $result;
	}

	private static function set_db_options( $options, $soft_update = false ) {
		$result = [];
		global $wpdb;

		foreach ($options as $option) {

			if (empty($option['name']) || !isset($option['value'])) {
				continue;
			}

			$get_result = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM tds_options WHERE name = %s", $option['name'] ) );
			if ( false !== $get_result ) {

				if (empty($option['value'])) {
					switch ($option['name']) {
						case 'curr_name': $option['value'] = 'USD'; break;
						case 'curr_pos': $option['value'] = 'left_space'; break;
						case 'curr_th_sep': $option['value'] = ','; break;
						case 'curr_dec_sep': $option['value'] = '.'; break;
						case 'curr_dec_no': $option['value'] = '0'; break;
					}
				}

				if ( '0' === $get_result || 0 === $get_result ) {

					$insert_result = $wpdb->insert( 'tds_options',
						array(
							'name'  => $option['name'],
							'value' => $option['value'],
						),
						array( '%s', '%s' ) );

					if ( false !== $insert_result ) {
						$result[ 'inserted_id' ] = $wpdb->insert_id;
					}
				} else if (! $soft_update ) {
					$update_result = $wpdb->update( 'tds_options',
						array(
							'name'  => $option['name'],
							'value' => $option['value'],
						),
						array( 'name' => $option['name'] ),
						array( '%s', '%s' ),
						array( '%s' )
					);

					if ( false !== $update_result ) {
						$result[ 'success' ] = true;
					}
				}
			}
		}
		return $result;
	}

	private static function get_page_info( WP_REST_Request $request ) {
		$result = [];
        $page_ids = $request->get_param('page_ids');

        if (empty($page_ids)) {
			$result[ 'error' ][] = 'Invalid page ids';
		}
		if (!empty($result)) {
			// return error
		} else {
			$temp_pages = [];

			foreach ($page_ids as $page_id) {

				$page = get_post($page_id);

				if (!is_null($page)) {

					$temp_pages[] = [ 'id'        => $page_id,
					                  'title'     => $page->post_title,
					                  'permalink' => esc_url( get_permalink( $page_id ) )
					];
				}
			}

			$result['pages'] = $temp_pages;
		}

		return $result;
	}

    private static function get_email_lists() {
        $result = [];
        $tds_lists = get_terms(
            array(
                'taxonomy' => 'tds_list',
                'hide_empty' => false,
            )
        );

        if (empty($tds_lists)) {
            $result[ 'error' ][] = 'No lists';
        }
        if (!empty($result)) {
            // return error
        } else {
            $temp_lists = [];

            foreach ( $tds_lists as $list ) {

                $temp_lists[] = ['id'      => $list->term_id,
                                'name'     => $list->name,
                ];
            }

            $result['lists'] = $temp_lists;

        }

        return $result;
    }

	private static function get_post_types() {

		$result = array();

		$post_types = get_post_types( array(
            'public' => true,
        ), 'objects' );

		foreach( $post_types as $post_type ) {
			switch ($post_type->name) {
                case 'page':
                case 'attachment':
                case 'product':
                case 'tds_locker':
                case 'tds_email':
                case 'tdb_templates':
                case 'tdc-review':
                case 'tdc-review-email':
                    break;
                default:
                	$result[] = $post_type;
            }
		}

		return $result;

	}

	private static function generate_default_pages( WP_REST_Request $request ) {
		global $wpdb;

		$result  = [];

		$check = $request->get_param('check');
		if ( !empty($check) && '1' === $check ) {
			$result = tds_util::get_subscriptions();
		} else {

			$results = $wpdb->get_results( "SELECT 
					tds_subscriptions.*, 
					tds_plans.name AS 'plan_name' 
				FROM 
					tds_subscriptions 
					INNER JOIN tds_plans
					ON tds_subscriptions.plan_id = tds_plans.id ORDER BY tds_subscriptions.ID desc", ARRAY_A );

			if ( null !== $results ) {
				foreach ( $results as &$item ) {
					if ( !empty($item['user_id']) ) {
						$item['user_name'] = get_user_meta( $item['user_id'], 'nickname', true );
						$item['end_date'] = tds_util::get_subscription_end_date( $item['start_date'], $item['cycle_interval'], $item['cycle_interval_count'] )->format( 'Y-m-d' );
					}
				}
				$result['subscriptions'] = $results;
			}
		}
		return $result;
	}

	private static function get_list_pages( WP_REST_Request $request ) {
		$result = [];
        $title = $request->get_param('title');

        $args = [
        	'post_type' => 'page',
	        'numberposts' => 30, // should be enough
	        'suppress_filters' => false,
        ];

        if (!empty($title)) {

        	global $keyword;
        	$keyword = $title;

        	add_filter( 'posts_where', 'tds_filter_list_pages');
        	function tds_filter_list_pages( $where ) {
				global $keyword;

			    global $wpdb;
			    $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( like_escape( $keyword ) ) . '%\'';

			    return $where;
			}
        }

        $result['pages'] = [];
        $pages = get_posts($args);
        remove_filter( 'posts_where', 'tds_filter_list_pages' );
        unset($keyword);

        foreach ($pages as $page) {
        	$result['pages'][] = ['id' => $page->ID, 'title' => $page->post_title, 'permalink' => esc_url(get_permalink($page->ID))];
        }

		return $result;
	}

	private static function get_dashboard_permalinks( WP_REST_Request $request ) {
		$result = [];
        $dashboard_id = $request->get_param('dashboard_page_id');
        $args = $request->get_param('args');

        if (empty($dashboard_id)) {
			$result[ 'error' ][] = 'Invalid dashboard page id';
		}
		if (!empty($result)) {
			// return error
		} else {

			$dashboard = get_post($dashboard_id);
			if ($dashboard instanceof WP_Post) {
				$dashboard_permalink = get_permalink($dashboard_id);
				$result['dashboard'] = ['id' => $dashboard_id, 'title' => $dashboard->post_title, 'permalink' => esc_url($dashboard_permalink)];

				if (!empty($args)) {
					foreach ($args as $arg) {
						$result[$arg] = esc_url(add_query_arg($arg, '', $dashboard_permalink));
					}
				}
			}
		}

		return $result;
	}

	/* tds coupons endpoints */
	private static function coupons_get_all() {
		global $wpdb;

		$result = [];

		$results = $wpdb->get_results( "SELECT * FROM tds_coupons", ARRAY_A );

		if ( null !== $results ) {

			// parse found coupons and add usage count
			foreach ( $results as $key => $coupon ) {
				$usage_count = $wpdb->get_var( "SELECT count(*) FROM tds_subscriptions WHERE coupon_id = '" . $coupon['id'] . "'" );
				$results[$key]['usage_count'] = $usage_count;

				// get coupon subscriptions
				$results[$key]['subscriptions'] = array();
				$subscriptions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM tds_subscriptions WHERE coupon_id = %s", $coupon['id'] ), ARRAY_A );
				if ( null !== $subscriptions && count( $subscriptions ) ) {

					foreach ( $subscriptions as $index => $subscription ) {

						// format create date
						$subscriptions[$index]['formatted_date'] = date("Y-m-d", strtotime( $subscription['created_at'] ) );

						// apply coupon
						if ( !empty( $subscription['coupon_id'] ) ) {
							$subscription['price'] = tds_util::get_coupon_discount( $subscription['coupon_id'], $subscription['price'] );
						}

						// format price
						if ( empty( $subscription['curr_name'] ) ) {
							$subscriptions[$index]['formatted_price'] = tds_util::get_basic_currency( $subscription['price'] );
						} else {
							$subscriptions[$index]['formatted_price'] = tds_util::get_formatted_currency(
								$subscription['price'],
								$subscription['curr_name'],
								$subscription['curr_pos'],
								$subscription['curr_th_sep'],
								$subscription['curr_dec_sep'],
								$subscription['curr_dec_no']
							);
						}

						// get subscription user data
						$user = get_user_by( 'id', $subscription['user_id'] );
						$subscriptions[$index]['user'] = $user;

						// get subscription plan data
						$plan = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM tds_plans WHERE id = %s LIMIT 1", $subscription['plan_id'] ), ARRAY_A );
						$subscriptions[$index]['plan'] = ( $plan !== null && count( $plan ) ) ? $plan[0] : array();

						if( !empty( $subscriptions[$index]['plan'] ) ) {
							$subscriptions[$index]['plan']['formatted_price'] = tds_util::get_formatted_currency(
								$subscriptions[$index]['plan']['price'],
								$subscription['curr_name'],
								$subscription['curr_pos'],
								$subscription['curr_th_sep'],
								$subscription['curr_dec_sep'],
								$subscription['curr_dec_no']
							);
						}

					}

					// update subscriptions
					$results[$key]['subscriptions'] = $subscriptions;

				}

			}

			$result['tds_coupons'] = $results;
		} else {
			$result['error'][] = 'Failed to retrieve coupons !';
		}

		return $result;
	}

	private static function coupon_add_edit( WP_REST_Request $request ) {
		global $wpdb;

		$result = [];

		$coupon_id = $request->get_param('couponId');
		$coupon_name = $request->get_param('couponCode');
		$coupon_amount = $request->get_param('couponAmount');
		$coupon_type = $request->get_param('couponType');
		$coupon_usage_limit = $request->get_param('couponUsageLimit');
		$coupon_desc_code = $request->get_param('couponDescCode');
		$coupon_start_dt = $request->get_param('couponStartDate');
		$coupon_end_dt = $request->get_param('couponEndDate');

		// validate coupon code
		if ( empty( $coupon_name ) ) {
			$result['error'][] = 'Empty coupon code, please add coupon code.';
		} elseif ( empty( $coupon_id ) ) {
			// name must be unique so check for previous added coupons with the same name
			$name_check_result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM tds_coupons WHERE name LIKE %s LIMIT 1", $coupon_name ) );

			if ( $name_check_result ) {
				$result['error'][] = 'Coupon code must be unique, please use another coupon code.';
			}
		}

		// validate usage limit
		if ( !empty( $coupon_usage_limit ) && !is_numeric( $coupon_usage_limit ) ) {
			$result['error'][] = 'Invalid coupon usage limit number.';
		}

		// validate coupon amount
		if ( empty( $coupon_amount ) ) {
			$result['error'][] = 'Coupon amount is empty.';
		} elseif ( !is_numeric( $coupon_amount ) ) {
			$result['error'][] = 'Invalid coupon amount.';
		}

		// validate coupon type
		if ( empty( $coupon_type ) ) {
			$result['error'][] = 'Coupon type is empty.';
		} elseif ( !in_array( $coupon_type, array( 'fixed', 'percent' ) ) ) {
			$result['error'][] = 'Invalid coupon type.'; // should be either `fixed` or `percent`
		}

		// at this point return errors if we have any ...
		if ( !empty( $result ) ) {
			return $result;
		}

		// coupon amount && type check
		if ( $coupon_type === 'percent' ) {

			if ( !( $coupon_amount >= 0.01 && $coupon_amount <= 100 ) ) {
				$result['error'][] = 'Invalid coupon amount value. For `percent` type please select a value in between 0.01 and 100.';
			}

		}

		// validate start date
		if ( empty( $coupon_start_dt ) ) {
			$result['error'][] = 'Start date is required.';
		} else {

			// check format
			$start_date_format = DateTime::createFromFormat( 'Y-m-d', $coupon_start_dt );
			if ( !$start_date_format || $start_date_format->format('Y-m-d' ) !== $coupon_start_dt ) {
				$result['error'][] = 'Invalid start date format.';
			}

		}

		// validate end date
		if ( empty( $coupon_end_dt ) ) {
			$result['error'][] = 'End date is required.';
		} else {

			// check format
			$end_date_format = DateTime::createFromFormat( 'Y-m-d', $coupon_end_dt );
			if ( !$end_date_format || $end_date_format->format('Y-m-d' ) !== $coupon_end_dt ) {
				$result['error'][] = 'Invalid end date format.';
			}

		}

		// end date is later than start date check
		if ( !empty( $coupon_start_dt ) && !empty( $coupon_end_dt ) && $coupon_start_dt > $coupon_end_dt ) {
			$result['error'][] = 'Invalid start/end dates interval, the end date is set before start date.';
		}

		if ( !empty( $result ) ) {
			// return error
		} else {
			global $wpdb;

			if ( empty( $coupon_id ) ) {

				$insert_result = $wpdb->insert( 'tds_coupons',
					array(
						'name'        => $coupon_name,
						'value'       => $coupon_amount,
						'type'        => $coupon_type,
						'usage_limit' => $coupon_usage_limit,
						'desc'        => $coupon_desc_code,
						'start_date'  => $coupon_start_dt,
						'end_date'    => $coupon_end_dt,
					),
					array( '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
				);

				if ( false !== $insert_result ) {
					$result['inserted_id'] = $wpdb->insert_id;
				} else {
					$result['error'] = $wpdb->last_error;
				}

			} else {

				$update_result = $wpdb->update( 'tds_coupons',
					array(
						'name'        => $coupon_name,
						'value'       => $coupon_amount,
						'type'        => $coupon_type,
						'usage_limit' => $coupon_usage_limit,
						'desc'        => $coupon_desc_code,
						'start_date'  => $coupon_start_dt,
						'end_date'    => $coupon_end_dt,
					),
					array( 'id' => $coupon_id ),
					array( '%s', '%s', '%s', '%d', '%s', '%s', '%s' ),
					array( '%d' )
				);

				if ( false !== $update_result ) {
					$result['success'] = true;
				} else {
					$result['error'] = $wpdb->last_error;
				}
			}

		}

		return $result;
	}

	private static function coupon_delete( WP_REST_Request $request ) {
		global $wpdb;

		$result = [];
		$coupon_id = $request->get_param('couponId');

		if ( empty( $coupon_id ) ) {
			$result['error'][] = 'Coupon id is required!';
		}

		$delete_result = $wpdb->delete(
			'tds_coupons',
			array( 'id' => $coupon_id ),
			array( '%d' )
		);

		if ( false !== $delete_result ) {
			$result['success'] = true;
		} else {
			$result['error'][] = $wpdb->last_error;
		}

		return $result;

	}

	static function on_ajax_apply_coupon( WP_REST_Request $request ) {

		global $wpdb;

		$result = [];
		$coupon_name = $request->get_param('couponName');
		$price = $request->get_param('price');

		if ( empty( $coupon_name ) ) {
			$result['error'][] = __td( 'Coupon name is required !', TD_THEME_NAME );
		}

		// get coupon data
		$coupon = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM tds_coupons WHERE name LIKE %s LIMIT 1", trim( $coupon_name ) ), ARRAY_A );

		if ( $coupon ) {
			$result['coupon'] = $coupon;
			$coupon = $coupon[0];

			// check usage limit
			$usage_limit = $coupon['usage_limit'];
			if ( (int) $usage_limit > 0 ) {
				$usage_count = $wpdb->get_var( "SELECT count(*) FROM tds_subscriptions WHERE coupon_id = '" . $coupon['id'] . "'" );

				// if limit has been reached
				if ( $usage_count >= $usage_limit ) {
					//$result['error'][] = 'Invalid - $usage_count: ' . $usage_count . ' $usage_limit: ' . $usage_limit;
					$result['error'][] = __td( 'The coupon code you entered has reached its usage limit', TD_THEME_NAME );
					return $result;
				}

			}

			// check start/end date
			$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
			$coupon_start_date = date( 'Y-m-d', strtotime( $coupon['start_date'] ) );
			$coupon_end_date = date( 'Y-m-d', strtotime( $coupon['end_date'] ) );
			if ( ( $current_date < $coupon_start_date ) || ( $current_date > $coupon_end_date ) ) {
				$result['error'][] = __td( 'The coupon code you entered has expired', TD_THEME_NAME );
				//$result['error'][] = 'Invalid - end_date: ' . $coupon_end_date . ' start_date: ' . $coupon_start_date . ' curr date: ' . $current_date;
				return $result;
			}

			// apply coupon
			if ( !empty( $price ) ) {
				$price = floatval( $price );
				$coupon_value = floatval( $coupon['value'] );
				$coupon_type = $coupon['type'];

				$discount = 0;
				if ( $coupon_type === 'fixed' ) {
					$discount = $coupon_value;
				} elseif ( $coupon_type === 'percent'  ) {
					$discount = $price * ( $coupon_value / 100 );
				}

				$discounted_price = $price - $discount;
				if ( $discounted_price < 0 ) {
					$discounted_price = 0;
					$discount = $price;
				}

				$result['coupon_id'] = $coupon['id'];
				$result['price'] = $price;
				$result['discount_type'] = $coupon_type;
				$result['discount'] = $discount;
				$result['discount_with_currency'] = tds_util::get_basic_currency( $discount, false );
				$result['discounted_price'] = $discounted_price;
				$result['discounted_price_with_currency'] = tds_util::get_basic_currency( $discounted_price, false );

			} else {
				$result['error'][] = __td( 'Price not set', TD_THEME_NAME );
				return $result;
			}

		} elseif ( $coupon !== null ) {
			$result['error'][] = __td( 'The coupon code you entered is invalid', TD_THEME_NAME );
		} else {
			$result['error'][] = $wpdb->last_error;
		}

		if ( empty( $result['error'] ) ) {
			$reply = $result;
		} else {
			$reply['error'] = array(
				array(
					'type' => 'API ERROR',
					'message' => $result['error'],
					'debug_data' => $request
				)
			);
		}

		die( json_encode( $reply ) );

	}

	private static function modify_email_notification( WP_REST_Request $request ) {

		$result = [];

		$notification_type = $request->get_param('notification_type');
		$enabled = $request->get_param('enabled');
		$enabled_admin = $request->get_param('enabled_admin');
		$subject = $request->get_param('subject');
		$subject_admin = $request->get_param('subject_admin');
		$body = $request->get_param('body');
		$body_admin = $request->get_param('body_admin');

		$update_options = array();
		if( !is_null($enabled) ) {
			$update_options[] = array(
				'name' => $notification_type . '_email_enabled',
				'value' => $enabled
			);
		}
		if( !is_null($enabled_admin) ) {
			$update_options[] = array(
				'name' => $notification_type . '_email_enabled_admin',
				'value' => $enabled_admin
			);
		}
		if( !is_null($subject) ) {
			$update_options[] = array(
				'name' => $notification_type . '_email_subject',
				'value' => $subject
			);
		}
		if( !is_null($subject_admin) ) {
			$update_options[] = array(
				'name' => $notification_type . '_email_subject_admin',
				'value' => $subject_admin
			);
		}
		if( !is_null($body) ) {
			$update_options[] = array(
				'name' => $notification_type . '_email_body',
				'value' => base64_decode($body)
			);
		}
		if( !is_null($body_admin) ) {
			$update_options[] = array(
				'name' => $notification_type . '_email_body_admin',
				'value' => base64_decode($body_admin)
			);
		}

		self::set_db_options($update_options);

		$result[ 'success' ] = true;

		return $result;

	}

    /**
     * Stripe endpoints
     */
    private static function create_payment_stripe( WP_REST_Request $request ) {

        $result = [];

        $payment_id = $request->get_param('paymentId');
        $secret_key = $request->get_param('secretKey');
        $public_key = $request->get_param('publicKey');
        $secret_key_sandbox = $request->get_param('secretKeySandbox');
        $public_key_sandbox = $request->get_param('publicKeySandbox');
        $is_active = $request->get_param('isActive');
        $is_sandbox = $request->get_param('isSandbox');
        $webhook_endpoint = $request->get_param('webhookEndpoint');
        $webhook_endpoint_secret = $request->get_param('webhookEndpointSecret');
        $payment_methods = $request->get_param('paymentMethodsStripe');
        $description = $request->get_param('description');
        $instructions = $request->get_param('instructions');

        if ( !empty($is_active) ) {
            if ( empty($is_sandbox) ) {
                if ( empty($secret_key ) ) {
                    $result[ 'error' ][ 'secretKey' ] = 'Empty Secret Key';
                }
                if ( empty($public_key ) ) {
                    $result[ 'error' ][ 'publicKey' ] = 'Empty Public Key';
                }
            } else {
                if ( empty($secret_key_sandbox ) ) {
                    $result['error']['secretKeySandbox'] = 'Empty Sandbox Secret Key';
                }
                if ( empty($public_key_sandbox ) ) {
                    $result['error']['publicKeySandbox'] = 'Empty Sandbox Public Key';
                }
            }
        }

        if ( !empty($result) ) {
            // return error
        } else {
            global $wpdb;

            $data_values = array(
                'secret_key' => empty($secret_key) ? '' : $secret_key,
                'public_key' => empty($public_key) ? '' : $public_key,
                'sandbox_secret_key' => empty($secret_key_sandbox) ? '' : $secret_key_sandbox,
                'sandbox_public_key' => empty($public_key_sandbox) ? '' : $public_key_sandbox,
                'webhook_endpoint' => empty($webhook_endpoint) ? '' : $webhook_endpoint,
                'webhook_endpoint_secret' => empty($webhook_endpoint_secret) ? '' : $webhook_endpoint_secret,
                'payment_methods' => empty($payment_methods) ? '' : serialize($payment_methods),
                'description' => empty($description) ? '' : $description,
                'instructions' => empty($instructions) ? '' : $instructions,
                'is_active' => empty($is_active) ? 0 : 1,
                'is_sandbox' => empty($is_sandbox) ? 0 : 1
            );
            $data_format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' );

            $wpdb->suppress_errors = true;

            if ( empty($payment_id) ) {

                $insert_result = $wpdb->insert(
                    'tds_payment_stripe',
                    $data_values,
                    $data_format
                );

                if ( false !== $insert_result ) {
                    $result['inserted_id'] = $wpdb->insert_id;
                } else {
                    $result['error'] = $wpdb->last_error;
                }

            } else {

                $update_result = $wpdb->update( 'tds_payment_stripe',
                    $data_values,
                    array( 'id' => $payment_id ),
                    $data_format,
                    array( '%d' )
                );

                if ( false !== $update_result ) {
                    $result['success'] = true;
                } else {
                    $result['error'] = $wpdb->last_error;
                }
            }

            if ( empty($result['error']) ) {

                $is_testing = '';
                if ( !empty($is_sandbox) ) {
                    $is_testing = 'sandbox_';
                }

                require_once TDS_PATH . '/includes/vendor/stripe/init.php';

                try {
                    // try to get the balance
                    $stripeClient = new \Stripe\StripeClient( $data_values[$is_testing . 'secret_key'] );
                    $stripeClient->balance->retrieve();
                    $valid_secret_key = true;
                } catch ( Exception $ex ) {
                    // not valid
                    $valid_secret_key = false;
                }

                $result['valid_secret_key'] = $valid_secret_key;

                // all good ... maybe add webhook endpoint
                if ( $valid_secret_key ) {
                    $result['stripe_webhook_endpoint'] = self::maybe_add_stripe_webhook_endpoint( $data_values, $payment_id );
                } else {
                    $result['stripe_webhook_endpoint']['error'] = 'ERROR GENERATING WEBHOOK URL/SIGNING SECRET' . ': cannot generate the stripe webhook endpoint without a valid secret key !';
                }

                $result['valid_public_key'] = false;
                $result['debug_info'] = self::validate_stripe_public_key(
                    $data_values[$is_testing . 'secret_key'],
                    $data_values[$is_testing . 'public_key'],
                    $result['valid_public_key']
                );

            }

        }

        return $result;
    }

    private static function get_payment_stripe( WP_REST_Request $request ) {
        global $wpdb;

        $result = [];
        $results = $wpdb->get_results("SELECT * FROM tds_payment_stripe LIMIT 1", ARRAY_A);

        if ( null !== $results && count($results) ) {

            // set payment methods defaults
            if ( empty($results[0]['payment_methods']) ) {
                $results[0]['payment_methods'] = array(
                    'card' => [
                        'status' => 1,
                        'label' => 'Cards'
                    ],
                    'ideal' => [
                        'status' => '',
                        'label' => 'Ideal'
                    ]
                );
            } else {
                $results[0]['payment_methods'] = unserialize($results[0]['payment_methods']);
            }

            $result['payment'] = $results;

            $is_testing = '';
            if ( !empty($results[0]['is_sandbox']) ) {
                $is_testing = 'sandbox_';
            }

            require_once TDS_PATH . '/includes/vendor/stripe/init.php';

            // check stripe secret key
            try {

                // try to get the balance
                $stripeClient = new \Stripe\StripeClient($results[0][$is_testing . 'secret_key']);
                $stripeClient->balance->retrieve();
                $result['valid_secret_key'] = true;

            } catch (Exception $ex) {
                $result['valid_secret_key'] = false;
            }

            $result['valid_public_key'] = false;
            $result['debug_info'] = self::validate_stripe_public_key(
                $results[0][ $is_testing . 'secret_key'],
                $results[0][ $is_testing . 'public_key'],
                $result['valid_public_key']
            );

        }

        return $result;

    }

    // maybe add stripe webhook endpoint
    private static function maybe_add_stripe_webhook_endpoint( $payment_data, $payment_id ) {

        // the tds stripe webhook rest endpoint
        $stripe_webhook_endpoint_rest_url = rest_url("tds_stripe/webhook/" );

        // set webhook_endpoint to look for and add
        $stripe_webhook_endpoint = !empty( $payment_data['webhook_endpoint'] ) ? $payment_data['webhook_endpoint'] : $stripe_webhook_endpoint_rest_url;
        $stripe_webhook_endpoint_secret = !empty( $payment_data['webhook_endpoint_secret'] ) ? $payment_data['webhook_endpoint_secret'] : '';

        require_once TDS_PATH . '/includes/vendor/stripe/init.php';

        $is_testing = '';
        if ( !empty( $payment_data['is_sandbox'] ) ) {
            $is_testing = 'sandbox_';
        }

        // set the secret api key
        $api_key = $payment_data[$is_testing . 'secret_key'];

        \Stripe\Stripe::setApiKey($api_key);

        $result = array();
        $tds_stripe_webhook_endpoint = null;

        try {

            // get/check endpoints
            $all_endpoints = \Stripe\WebhookEndpoint::all();
            $endpoints = $all_endpoints->data;

            if ( !empty($endpoints) ) {
                foreach ( $endpoints as $endpoint ) {
                    if ( $endpoint->url === $stripe_webhook_endpoint ) {
                        $tds_stripe_webhook_endpoint = $endpoint;
                        break;
                    }
                }
            }

            // if no endpoints or the set webhook endpoint is not found
            if ( empty($endpoints) || empty($tds_stripe_webhook_endpoint) ) {

                // create the tds rest webhook endpoint
                $tds_stripe_webhook_endpoint = \Stripe\WebhookEndpoint::create([
                    'url' => $stripe_webhook_endpoint,
                    'enabled_events' => [
                        'customer.subscription.created',
                        'customer.subscription.deleted',
                        'customer.subscription.updated',
                        'invoice.upcoming',
                        'invoice.created',
                        'invoice.updated',
                        'invoice.paid',
                        'invoice.payment_succeeded',
                        'invoice.payment_failed',
                        'setup_intent.succeeded',
                        'checkout.session.completed',
                    ],
                ]);

            }

        } catch ( Exception $ex ) {
            $result['error'] = 'ERROR GENERATING WEBHOOK URL/SIGNING SECRET' . ': ' . $ex->getMessage();
        }

        if ( !empty($result) ) {
            // return error
        } else {

            //die(
            //	json_encode(
            //		array(
            //			'webhook_endpoint' => $tds_stripe_webhook_endpoint->url,
            //			'webhook_endpoint_secret' => !empty( $tds_stripe_webhook_endpoint->secret ) ? $tds_stripe_webhook_endpoint->secret : $stripe_webhook_endpoint_secret
            //		)
            //	)
            //);

            // add it to db
            global $wpdb;
            $wpdb->suppress_errors = true;

            $update_result = $wpdb->update( 'tds_payment_stripe',
                array(
                    'webhook_endpoint' => $tds_stripe_webhook_endpoint->url,
                    'webhook_endpoint_secret' => !empty( $tds_stripe_webhook_endpoint->secret ) ? $tds_stripe_webhook_endpoint->secret : $stripe_webhook_endpoint_secret
                ),
                array( 'id' => $payment_id ),
                array( '%s', '%s' ),
                array( '%d' )
            );

            if ( false !== $update_result ) {
                $result['webhook_endpoint'] = $tds_stripe_webhook_endpoint->url;
                $result['webhook_endpoint_secret'] = !empty( $tds_stripe_webhook_endpoint->secret ) ? $tds_stripe_webhook_endpoint->secret : $stripe_webhook_endpoint_secret;
            } else {
                $result['error'] = $wpdb->last_error;
            }

        }

        return $result;

    }

    private static function validate_stripe_public_key( $stripe_secret_key, $stripe_public_key, &$is_valid = false ) {

        // check stripe public key
        $curl = curl_init();

        $post_params = [
            'client_secret' => $stripe_secret_key,
            'key' => $stripe_public_key
        ];

        curl_setopt( $curl, CURLOPT_URL, "https://api.stripe.com/v1/sources/src_" );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_POST, true );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, \Stripe\Util\Util::encodeParameters($post_params) );

        $response = json_decode( curl_exec($curl),true );

        curl_close($curl);

        if ( substr($response["error"]["message"],0, 24 ) != "Invalid API Key provided" ) {
            $is_valid = true;
        }

        return $response;

    }

    static function on_ajax_create_stripe_customer( WP_REST_Request $request ) {

        global $wpdb;

        $result = [];
        $errors = [];

        $subscription_user_id = $request->get_param('subscriptionUserId' );
        if ( empty($subscription_user_id) ) {
            $errors[] = 'Invalid user id';
        }

        if ($errors) {
            $result['error'] = 'Invalid data supplied';
        }

        // verify if the user trying to create the subscription has permissions
        $current_user = wp_get_current_user();
        $is_current_user_admin = in_array('administrator', $current_user->roles );

        if( !$is_current_user_admin && $subscription_user_id != $current_user->ID ) {
            $result['error'] = __td( 'You do not hold the required privileges to execute this request.' );
            die( json_encode( $result ) );
        }

        $billing_first_name = $request->get_param('billingFirstName');
        $billing_last_name = $request->get_param('billingLastName');
        $billing_company_name = $request->get_param('billingCompanyName');
        $billing_vat_number = $request->get_param('billingVatNumber');
        $billing_country = $request->get_param('billingCountry');
        $billing_address = $request->get_param('billingAddress');
        $billing_city = $request->get_param('billingCity');
        $billing_county = $request->get_param('billingCounty');
        $billing_postcode = $request->get_param('billingPostcode');
        $billing_phone = $request->get_param('billingPhone');
        $billing_email = $request->get_param('billingEmail');

        if ( !empty($billing_email) && !is_email($billing_email) ) {
            $result['error'] = 'Invalid email address';
        }

        if ( empty($result) ) {

            $results = $wpdb->get_results("SELECT * FROM tds_payment_stripe LIMIT 1", ARRAY_A );
            if ( null !== $results ) {

                // get user data
                $user = get_user_by('id', $subscription_user_id );
                if ($user) {

                    require_once TDS_PATH . '/includes/vendor/stripe/init.php';

                    $is_testing = '';
                    if ( !empty( $results[0]['is_sandbox'] ) ) {
                        $is_testing = 'sandbox_';
                    }

                    $stripe_client = new \Stripe\StripeClient( $results[0][$is_testing . 'secret_key'] );

                    /* customer data */
                    // name
                    if ( !empty($billing_first_name) && !empty($billing_last_name) ) {
                        $name = $billing_first_name . ' ' . $billing_last_name;
                    } else {
                        $name = $user->first_name . ' ' . $user->last_name;
                    }

                    if ( empty( trim($name) ) ) {
                        $name = !empty($user->nickname) ? $user->nickname : $user->user_login;
                    }

                    // email
                    $email = !empty($billing_email) ? $billing_email : $user->user_email;

                    // phone
                    $phone = !empty($billing_phone) ? $billing_phone : '';

                    // address
                    $address = array(
                        'city' => !empty( $billing_city ) ? $billing_city : '',
                        'country' => !empty( $billing_country ) ? $billing_country : '',
                        'state' => !empty( $billing_county ) ? $billing_county : '',
                        'line1' => !empty( $billing_company_name ) ? $billing_company_name : '',
                        'line2' => !empty( $billing_address ) ? $billing_address : '',
                        'postal_code' => !empty( $billing_postcode ) ? $billing_postcode : '',
                    );

                    try {

                        // check customer
                        $customer_search = $stripe_client->customers->search([
                            'query' => 'metadata[\'local_id\']:\'' . $subscription_user_id . '\'',
                        ]);
                        if ( !empty($customer_search->data) ) {

                            // get the first customer from search results
                            $customer = $customer_search->data[0];

                        } else {

                            // if test mode is enabled, add customer test clock
                            if ( !empty($is_testing) ) {

                                try {

                                    $test_clock = $stripe_client->testHelpers->testClocks->create([
                                        'frozen_time' => time(),
                                        'name' => $name . ' test clock',
                                    ]);

                                    $result['test_clock'] = $test_clock;

                                } catch ( Exception $ex ) {
                                    $result['error'] = 'stripe api > testClocks->create error: ' . $ex->getMessage();
                                }

                            }

                            // set new customer data
                            $customer_data = [
                                'email' => $email,
                                'name' => $name,
                                'phone' => $phone,
                                'description' => "( created by tagDiv Opt-In Builder )",
                                'metadata' => array(
                                    'local_id' => $subscription_user_id
                                ),
                                'address' => $address
                            ];

                            // add customer test clock, this will be set if test mode is enabled
                            if ( !empty($result['test_clock']) ) {
                                $customer_data['test_clock'] = $result['test_clock']->id;
                            }

                            // create customer
                            $customer = $stripe_client->customers->create($customer_data);

                        }

                        $result['customer'] = $customer;
                        $result['customer_id'] = $customer->id;

                    } catch ( Exception $ex ) {
                        $result['error'] = 'stripe api > customers search/create error: ' . $ex->getMessage();
                    }

                } else {
                    $result['error'] = 'user not found';
                }

            }

        }

        die( json_encode( $result ) );

    }

    static function on_ajax_stripe_pm_update( WP_REST_Request $request ) {

        global $wpdb;

        $result = [];
        $errors = [];

        $stripe_subscription_id = $request->get_param('stripeSubscriptionId' );
        $stripe_customer_id = $request->get_param('stripeCustomerId' );

        $stripe_pm_id = $request->get_param('stripePaymentMethodId' );
        if ( empty($stripe_pm_id) ) {
            $errors[] = 'Stripe payment method id missing and it\'s required';
        }

        $subscription_user_id = $request->get_param('subscriptionUserId' );
        if ( empty($subscription_user_id) ) {
            $errors[] = 'Invalid user id';
        }

        if ( $errors ) {
            $result['errors'] = $errors;
            $result['error'] = implode( ' | ', $errors  );
        }

        if ( empty($result) ) {

            // verify if the user trying to create the subscription has permissions
            $current_user = wp_get_current_user();
            $is_current_user_admin = in_array('administrator', $current_user->roles );

            if( !$is_current_user_admin && $subscription_user_id != $current_user->ID ) {
                $result['error'] = __td( 'You do not hold the required privileges to execute this request.' );
            }

            $tds_payment_stripe_results = $wpdb->get_results("SELECT * FROM tds_payment_stripe LIMIT 1", ARRAY_A );
            if ( null !== $tds_payment_stripe_results ) {

                require_once TDS_PATH . '/includes/vendor/stripe/init.php';

                $is_testing = '';
                if ( !empty( $tds_payment_stripe_results[0]['is_sandbox'] ) ) {
                    $is_testing = 'sandbox_';
                }

                $stripe_client = new \Stripe\StripeClient( $tds_payment_stripe_results[0][$is_testing . 'secret_key'] );

                try {

                    if ( !empty($stripe_subscription_id) ) {

                        $stripe_subscription = $stripe_client->subscriptions->update(
                            $stripe_subscription_id,
                            [ 'default_payment_method' => $stripe_pm_id ]
                        );

                        $result['stripe_subscription'] = $stripe_subscription;

                    } elseif ( !empty($stripe_customer_id) ) {

                        // set setup payment method to customer
                        $stripe_customer = $stripe_client->customers->update(
                            $stripe_customer_id,
                            [ 'invoice_settings' => [ 'default_payment_method' => $stripe_pm_id ] ]
                        );

                        $result['stripe_customer'] = $stripe_customer;

                    }

                    $result['success'] = true;

                } catch ( Exception $ex ) {
                    $result['error'] = $ex->getMessage();
                }

            }

        }

        die( json_encode($result) );

    }

    static function on_ajax_stripe_checkout_session_create( WP_REST_Request $request ) {

        global $wpdb;

        $result = [];
        $errors = [];

        // checkout session mode, can be one of subscription, setup or payment
        $checkout_session_mode = $request->get_param('checkoutSessionMode' );
        if ( empty($checkout_session_mode) || !in_array( $checkout_session_mode, array( 'subscription', 'setup', 'payment' ) ) ) {
            $errors[] = 'Invalid checkout session mode';
        }

        // checkout session context can be one of subscription(subscription creation on checkout page) or pm_setup(payment method setup - used on my account to set up new payment methods for subscriptions or customers)
        // @note this will be added to stripe's checkout session metadata and used on 'checkout.session.completed' event to know when to create a local subscription after a checkout session has been completed
        $checkout_session_context = $request->get_param('checkoutSessionContext' );

        $subscription_local_id = $request->get_param('subscriptionLocalId' );
        $stripe_subscription_id = $request->get_param('stripeSubscriptionId' );

        $subscription_user_id = $request->get_param('subscriptionUserId' );
        if ( empty($subscription_user_id) ) {
            $errors[] = 'Invalid user id';
        }

        $subscription_plan_id = $request->get_param('subscriptionPlanId' );
        if ( empty($subscription_plan_id) ) {
            $errors[] = 'Invalid plan id';
        }

        $subscription_customer_id = $request->get_param('stripeCustomerId' );
        if ( empty($subscription_customer_id) ) {
            $errors[] = 'Invalid customer id';
        }

        $current_url = $request->get_param('currentUrl' );
        if ( empty($current_url) ) {
            $errors[] = 'Invalid current url';
        }

        $subscription_coupon_id = $request->get_param('subscriptionCouponId' );

        if ($errors) {
            $result['error'] = 'Invalid data supplied';
            $result['errors'] = $errors;
        }

        // billing data
        $billing_first_name = $request->get_param('billingFirstName');
        $billing_last_name = $request->get_param('billingLastName');
        $billing_company_name = $request->get_param('billingCompanyName');
        $billing_vat_number = $request->get_param('billingVatNumber');
        $billing_country = $request->get_param('billingCountry');
        $billing_address = $request->get_param('billingAddress');
        $billing_city = $request->get_param('billingCity');
        $billing_county = $request->get_param('billingCounty');
        $billing_postcode = $request->get_param('billingPostcode');
        $billing_phone = $request->get_param('billingPhone');
        $billing_email = $request->get_param('billingEmail');

        if ( !empty($billing_email) && !is_email($billing_email) ) {
            $result['error'] = 'Invalid email address';
        }

        if ( empty($result) ) {

            // verify if the user trying to create the subscription has permissions
            $current_user = wp_get_current_user();
            $is_current_user_admin = in_array('administrator', $current_user->roles );

            if( !$is_current_user_admin && $subscription_user_id != $current_user->ID ) {
                $result['error'] = __td( 'You do not hold the required privileges to execute this request.' );
            }

        }

        if ( empty($result) ) {

            // plan data
            $plan_price = '';
            $plan_name = '';
            $plan_interval = '';
            $plan_interval_count = '';
            $trial_days = '';
            $is_unlimited = 0;

            // check plan
            $valid_plan = false;
            $results = self::get_all_plans($request);
            if ( !empty($results['plans']) ) {

                foreach ( $results['plans'] as $plan ) {

                    if ( $plan['id'] == $subscription_plan_id ) {

                        $valid_plan = true;
                        $plan_price = $plan['price'];
                        $plan_name = $plan['name'];
                        $plan_interval = $plan['interval'];
                        $plan_interval_count = $plan['interval_count'];
                        $trial_days = max( intval( $plan['trial_days'] ), 0 );
                        $is_unlimited = !empty($plan['is_unlimited']);

                        // check previous subscriptions to allow a user to benefit from trial just one time
                        $user_subscriptions = tds_util::get_user_subscriptions_to_plan( $subscription_user_id, $plan['id'] );

                        // if the user had other subscriptions to this plan reset trial days ( no trial )
                        if ( null !== $user_subscriptions && count($user_subscriptions) ) {
                            $trial_days = 0; // set trial to 0 days
                        }

                        // invalidate plan if plan interval or interval_count are empty & it's not an unlimited plan
                        if ( ( empty($plan_interval) || empty($plan_interval_count) ) && !$is_unlimited ) {
                            $result['plan_interval_error'] = 'Invalid plan interval or interval_count data.';
                            $valid_plan = false;
                        }

                        // invalidate plan if plan unlimited & mode mismatch
                        if ( $is_unlimited && !in_array( $checkout_session_mode, [ 'payment', 'setup' ] ) ) {
                            $result['plan_data_error'] = 'For unlimited plans checkout session mode must be either payment or setup.';
                            $valid_plan = false;
                        }

                        break;
                    }

                }

            }

            if ($valid_plan) {

                $tds_payment_stripe_results = $wpdb->get_results("SELECT * FROM tds_payment_stripe LIMIT 1", ARRAY_A );
                if ( null !== $tds_payment_stripe_results ) {

                    tds_util::get_currency_options( $curr_name,$curr_pos,$curr_th_sep,$curr_dec_sep,$curr_dec_no );
                    if ( !empty($curr_name) ) {

                        require_once TDS_PATH . '/includes/vendor/stripe/init.php';

                        $is_testing = '';
                        if ( !empty( $tds_payment_stripe_results[0]['is_sandbox'] ) ) {
                            $is_testing = 'sandbox_';
                        }

                        $stripe_client = new \Stripe\StripeClient( $tds_payment_stripe_results[0][$is_testing . 'secret_key'] );

                        tds_util::check_stripe_currency( $curr_name, $is_stripe, $is_digit );

                        if ( $is_stripe ) {

                            // process stripe payment methods
                            $stripe_payment_methods = !empty($tds_payment_stripe_results[0]['payment_methods']) ? unserialize($tds_payment_stripe_results[0]['payment_methods']) : array();

                            $payment_method_types = [];

                            // process payment methods
                            if ( !empty($stripe_payment_methods) && is_array($stripe_payment_methods) ) {

                                foreach ( $stripe_payment_methods as $pm_key => $pm_data ) {

                                    if ( !empty($pm_data['status']) ) {
                                        $payment_method_types[] = $pm_key;
                                    }

                                }

                            }

                            // process coupon
                            $coupon_err = '';
                            if ( !empty($subscription_coupon_id) ) {

                                $coupons_results = $wpdb->get_results( "SELECT * FROM tds_coupons WHERE id = '$subscription_coupon_id'", ARRAY_A );
                                if ( null !== $coupons_results && count($coupons_results) ) {

                                    $local_coupon_data = $coupons_results[0];
                                    $coupon_id = $local_coupon_data['id'];
                                    $coupon_name = $local_coupon_data['name'];
                                    $coupon_type = $local_coupon_data['type'];
                                    $coupon_value = $local_coupon_data['value'];

                                    // check for existing coupon
                                    $coupons = $stripe_client->coupons->all( [ 'limit' => 100 ] );
                                    if ( !empty($coupons->data) && is_array( $coupons->data ) ) {

                                        foreach ( $coupons->data as $coupon_data ) {
                                            $coupon_metadata = $coupon_data->metadata;
                                            $coupon_metadata_local_id = $coupon_metadata->local_coupon_id;

                                            if ( $coupon_metadata_local_id === $coupon_id ) {

                                                // check coupon type and value
                                                if ( $coupon_type === 'fixed' ) {

                                                    // if coupon type is fixed, check that the stripe coupon value matches the value of the local coupon
                                                    if ( !empty( $coupon_data->amount_off ) && $coupon_data->amount_off === intval($coupon_value * ( $is_digit ? 100 : 1 ) ) ) {
                                                        // set coupon
                                                        $coupon = $coupon_data;
                                                    }

                                                } elseif ( $coupon_type === 'percent' ) {

                                                    // if coupon type is percent, check that the stripe coupon value matches the value of the local coupon
                                                    if ( !empty( $coupon_data->percent_off ) && $coupon_data->percent_off === floatval($coupon_value) ) {
                                                        // set coupon
                                                        $coupon = $coupon_data;
                                                    }

                                                }

                                                break;
                                            }

                                        }

                                    }

                                    if ( empty($coupon) ) {

                                        $coupon_params = array(
                                            'name' => $coupon_name,
                                            'metadata' => [
                                                'local_coupon_id' => $coupon_id
                                            ]
                                        );

                                        $valid_coupon_value = false;
                                        if ( $coupon_type === 'fixed' ) {

                                            $coupon_params['amount_off'] = intval( $coupon_value * ( $is_digit ? 100 : 1 ) );
                                            $coupon_params['currency'] = $curr_name;
                                            $valid_coupon_value = true;

                                        } elseif ( $coupon_type === 'percent' ) {

                                            if ( is_numeric($coupon_value) && $coupon_value > 0 && $coupon_value <= 100 ) {
                                                $coupon_params['percent_off'] = floatval($coupon_value);
                                                $valid_coupon_value = true;
                                            } else {
                                                //throw new Exception( 'Invalid coupon value!' );
                                                $coupon_err = 'invalid coupon value';
                                            }

                                        } else {
                                            $coupon_err = 'invalid coupon type';
                                        }

                                        // create coupon
                                        if ( $valid_coupon_value ) {
                                            $coupon = $stripe_client->coupons->create($coupon_params);
                                        }

                                    }

                                    $result['coupon'] = !empty($coupon) ? $coupon : $coupon_err;

                                }

                            }

                            // subscription mode
                            if ( $checkout_session_mode === 'subscription' ) {

                                // check for existing plan price
                                $unit_amount = intval( $plan_price * ( $is_digit ? 100 : 1 ) );
                                $interval = !empty($plan_interval) ? $plan_interval : '';
                                $interval_count = !empty($plan_interval_count) ? intval($plan_interval_count) : '';
                                $price_search = $stripe_client->prices->search([
                                    'query' => 'active:\'true\' AND currency:\'' . $curr_name . '\' AND type:\'recurring\' AND metadata[\'interval\']:\'' . $interval . '\' AND metadata[\'interval_count\']:\'' . $interval_count . '\' AND metadata[\'unit_amount\']:\'' . $unit_amount . '\'',
                                ]);
                                if ( !empty( $price_search->data ) ) {

                                    // get the first price from search results
                                    $price = $price_search->data[0];

                                } else {

                                    try {

                                        $price_data = [
                                            'unit_amount' => $unit_amount,
                                            'currency' => $curr_name,
                                            'recurring' => [
                                                'interval' => $interval,
                                                'interval_count' => $interval_count
                                            ],
                                            'product_data' => [
                                                'name' => $plan_name
                                            ],
                                            'metadata' => array(
                                                'unit_amount' => $unit_amount,
                                                'interval' => $interval,
                                                'interval_count' => $interval_count
                                            )
                                        ];

                                        // add price
                                        $price = $stripe_client->prices->create($price_data);

                                    } catch ( Exception $ex ) {
                                        $result['stripe_cs_price_data'] = $price_data;
                                        $result['error'] = $ex->getMessage();
                                    }

                                }

                                if ( empty($result['error']) ) {

                                    $result['plan_price'] = $price;

                                    // stripe checkout session data params
                                    $stripe_cs_params = [
                                        'customer' => $subscription_customer_id,
                                        'line_items' => [
                                            [
                                                'price' => $price->id,
                                                'quantity' => 1,
                                            ],
                                        ],
                                        'payment_method_types' => !empty($payment_method_types) ? $payment_method_types : ['card'],
                                        'success_url' => add_query_arg( [ 'stripe_session_id' => '{CHECKOUT_SESSION_ID}' ], $current_url ),
                                        'cancel_url' => $current_url,
                                        'mode' => 'subscription',
                                        'subscription_data' => []
                                    ];

                                    // add subscription trial
                                    if ( !empty($trial_days) ) {
                                        $stripe_cs_params['subscription_data']['trial_period_days'] = $trial_days;
                                    }

                                    // add billing data
                                    $stripe_cs_params['metadata'] = array(
                                        'billing_first_name' => $billing_first_name,
                                        'billing_last_name' => $billing_last_name,
                                        'billing_company_name' => $billing_company_name,
                                        'billing_vat_number' => $billing_vat_number,
                                        'billing_country' => $billing_country,
                                        'billing_address' => $billing_address,
                                        'billing_city' => $billing_city,
                                        'billing_county' => $billing_county,
                                        'billing_postcode' => $billing_postcode,
                                        'billing_phone' => $billing_phone,
                                        'billing_email' => $billing_email,
                                        'user_id' => $subscription_user_id, // add local subscription user id
                                        'plan_id' => $subscription_plan_id, // add local subscription plan id
                                        'coupon_id' => !empty($subscription_coupon_id) ? $subscription_coupon_id : '', // add local subscription coupon id
                                    );

                                } else {
                                    die( json_encode($result) );
                                }

                            } elseif ( $checkout_session_mode === 'setup' ) {

                                // check for existing plan price
                                $unit_amount = intval( $plan_price * ( $is_digit ? 100 : 1 ) );
                                $price_search = $stripe_client->prices->search([
                                    'query' => 'active:\'true\' AND currency:\'' . $curr_name . '\' AND type:\'one_time\' AND metadata[\'unit_amount\']:\'' . $unit_amount . '\'',
                                ]);
                                if ( !empty( $price_search->data ) ) {

                                    // get the first price from search results
                                    $price = $price_search->data[0];

                                } else {

                                    try {

                                        $price_data = [
                                            'unit_amount' => $unit_amount,
                                            'currency' => $curr_name,
                                            'product_data' => [
                                                'name' => $plan_name
                                            ],
                                            'metadata' => array(
                                                'unit_amount' => $unit_amount
                                            )
                                        ];

                                        // add price
                                        $price = $stripe_client->prices->create($price_data);

                                    } catch ( Exception $ex ) {
                                        $result['stripe_cs_price_data'] = $price_data;
                                        $result['error'] = $ex->getMessage();
                                    }

                                }

                                if ( empty($result['error']) ) {

                                    // stripe checkout session data params
                                    $stripe_cs_params = [
                                        'payment_method_types' => !empty($payment_method_types) ? $payment_method_types : ['card'],
                                        'mode' => 'setup',
                                        'customer' => $subscription_customer_id,
                                        'setup_intent_data' => [
                                            'metadata' => []
                                        ],
                                        'cancel_url' => add_query_arg( [ 'expand' => $subscription_local_id ], $current_url ),
                                    ];

                                    // set success_url
                                    if ( !empty($subscription_local_id) ) {
                                        $stripe_cs_params['success_url'] = add_query_arg( [ 'expand' => $subscription_local_id ], $current_url );
                                        $stripe_cs_params['setup_intent_data']['metadata']['description'] = 'subscription setup';
                                    } else {
                                        $stripe_cs_params['success_url'] = add_query_arg( [ 'stripe_session_id' => '{CHECKOUT_SESSION_ID}' ], $current_url );
                                        $stripe_cs_params['setup_intent_data']['metadata']['description'] = 'customer setup';
                                    }

                                    // add subscription_local_id metadata
                                    if ( !empty($subscription_local_id) ) {
                                        $stripe_cs_params['setup_intent_data']['metadata']['subscription_local_id'] = $subscription_local_id;
                                    }

                                    // add stripe_subscription_id metadata
                                    if ( !empty($stripe_subscription_id) ) {
                                        $stripe_cs_params['setup_intent_data']['metadata']['stripe_subscription_id'] = $stripe_subscription_id;
                                    }

                                    // add stripe_customer_id metadata
                                    if ( !empty($subscription_customer_id) ) {
                                        $stripe_cs_params['setup_intent_data']['metadata']['stripe_customer_id'] = $subscription_customer_id;
                                    }

                                    // add billing data
                                    $stripe_cs_params['metadata'] = array(
                                        'billing_first_name' => $billing_first_name,
                                        'billing_last_name' => $billing_last_name,
                                        'billing_company_name' => $billing_company_name,
                                        'billing_vat_number' => $billing_vat_number,
                                        'billing_country' => $billing_country,
                                        'billing_address' => $billing_address,
                                        'billing_city' => $billing_city,
                                        'billing_county' => $billing_county,
                                        'billing_postcode' => $billing_postcode,
                                        'billing_phone' => $billing_phone,
                                        'billing_email' => $billing_email,
                                    );

                                    // add user id metadata
                                    if ( !empty($subscription_user_id) ) {
                                        $stripe_cs_params['metadata']['user_id'] = $subscription_user_id;
                                    }

                                    // add plan id metadata
                                    if ( !empty($subscription_plan_id) ) {
                                        $stripe_cs_params['metadata']['plan_id'] = $subscription_plan_id;
                                    }

                                    // add stripe price id metadata
                                    if ( !empty($price) ) {
                                        $stripe_cs_params['metadata']['stripe_price_id'] = $price;
                                    }

                                } else {
                                    die( json_encode($result) );
                                }

                            } elseif ( $checkout_session_mode === 'payment' ) {

                                // check for existing plan price
                                $unit_amount = intval( $plan_price * ( $is_digit ? 100 : 1 ) );
                                $price_search = $stripe_client->prices->search([
                                    'query' => 'active:\'true\' AND currency:\'' . $curr_name . '\' AND type:\'one_time\' AND metadata[\'unit_amount\']:\'' . $unit_amount . '\'',
                                ]);
                                if ( !empty( $price_search->data ) ) {

                                    // get the first price from search results
                                    $price = $price_search->data[0];

                                } else {

                                    try {

                                        $price_data = [
                                            'unit_amount' => $unit_amount,
                                            'currency' => $curr_name,
                                            'product_data' => [
                                                'name' => $plan_name
                                            ],
                                            'metadata' => array(
                                                'unit_amount' => $unit_amount
                                            )
                                        ];

                                        // add price
                                        $price = $stripe_client->prices->create($price_data);

                                    } catch ( Exception $ex ) {
                                        $result['stripe_cs_price_data'] = $price_data;
                                        $result['error'] = $ex->getMessage();
                                    }

                                }

                                if ( empty($result['error']) ) {

                                    $result['plan_price'] = $price;

                                    // stripe checkout session data params
                                    $stripe_cs_params = [
                                        'customer' => $subscription_customer_id,
                                        'line_items' => [
                                            [
                                                'price' => $price->id,
                                                'quantity' => 1,
                                            ]
                                        ],
                                        'invoice_creation' => [
                                            'enabled' => true
                                        ],
                                        'payment_method_types' => !empty($payment_method_types) ? $payment_method_types : ['card'],
                                        'success_url' => add_query_arg( [ 'stripe_session_id' => '{CHECKOUT_SESSION_ID}' ], $current_url ),
                                        'cancel_url' => $current_url,
                                        'mode' => 'payment'
                                    ];

                                    // add billing data
                                    $stripe_cs_params['metadata'] = array(
                                        'billing_first_name' => $billing_first_name,
                                        'billing_last_name' => $billing_last_name,
                                        'billing_company_name' => $billing_company_name,
                                        'billing_vat_number' => $billing_vat_number,
                                        'billing_country' => $billing_country,
                                        'billing_address' => $billing_address,
                                        'billing_city' => $billing_city,
                                        'billing_county' => $billing_county,
                                        'billing_postcode' => $billing_postcode,
                                        'billing_phone' => $billing_phone,
                                        'billing_email' => $billing_email,
                                        'user_id' => $subscription_user_id, // add user id
                                        'plan_id' => $subscription_plan_id, // add plan id
                                        'coupon_id' => !empty($subscription_coupon_id) ? $subscription_coupon_id : '', // add coupon id
                                    );

                                } else {
                                    die( json_encode($result) );
                                }

                            }

                            if ( !empty($stripe_cs_params) ) {

                                // add coupon discount
                                if ( !empty($coupon) ) {
                                    $stripe_cs_params['discounts'] = [
                                        [ 'coupon' => $coupon->id ]
                                    ];
                                }

                                // add testing text in testing mode
                                if ( $is_testing ) {
                                    $stripe_cs_params['custom_text'] = [
                                        'submit' => [
                                            'message' => 'Test mode is enabled. You can use the following card details for Stripe test transactions: Number: 4242424242424242 / CVC: 123 / Expiration: any future date.'
                                        ]
                                    ];
                                }

                                // add checkout session context
                                if ( $checkout_session_context ) {
                                    $stripe_cs_params['metadata']['context'] = $checkout_session_context;
                                }

                                try {
                                    $stripe_checkout_session = $stripe_client->checkout->sessions->create($stripe_cs_params);
                                    $result['stripe_checkout_session_data'] = $stripe_checkout_session;
                                    $result['stripe_cs_params'] = $stripe_cs_params;
                                } catch ( Exception $ex ) {
                                    $result['stripe_cs_params'] = $stripe_cs_params;
                                    $result['error'] = $ex->getMessage();
                                }

                            } else {
                                $result['error'] = 'Empty params array for stripe checkout session.';
                            }

                        } else {
                            $result['error'] = 'Stripe does not support the current currency.';
                        }

                    } else {
                        $result['error'] = 'Invalid currency.';
                    }

                } else {
                    $result['error'] = 'Could not retrieve stripe payment data from database.';
                }

            } else {
                $result['error'] = 'Invalid plan id/data.';
            }

        }

        die( json_encode($result) );

    }

    static function on_ajax_stripe_checkout_session_retrieve( WP_REST_Request $request ) {

        global $wpdb;

        $result = [];
        $errors = [];

        $checkout_session_id = $request->get_param('checkoutSessionId' );
        if ( empty($checkout_session_id) ) {
            $errors[] = 'Invalid session id';
        }

        if ( $errors ) {
            $result['error'] = 'Invalid data supplied';
            $result['errors'] = $errors;
        }

        if ( empty($result) ) {

            $tds_payment_stripe_results = $wpdb->get_results("SELECT * FROM tds_payment_stripe LIMIT 1", ARRAY_A );
            if ( null !== $tds_payment_stripe_results ) {

                require_once TDS_PATH . '/includes/vendor/stripe/init.php';

                $is_testing = !empty($tds_payment_stripe_results[0]['is_sandbox']) ? 'sandbox_' : '';

                $stripe_client = new \Stripe\StripeClient( $tds_payment_stripe_results[0][$is_testing . 'secret_key'] );

                try {
                    $stripe_checkout_session = $stripe_client->checkout->sessions->retrieve(
                        $checkout_session_id,
                        [ 'expand' => [ 'setup_intent.payment_method', 'line_items' ] ]
                    );
                    $result['stripe_checkout_session_data'] = $stripe_checkout_session;
                } catch ( Exception $ex ) {
                    $result['error'] = $ex->getMessage();
                }

            }

            // get local subscription using stripe's checkout session id
            $tds_subscriptions_results = $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM tds_subscriptions WHERE stripe_payment_info = %s", $checkout_session_id ),
                ARRAY_A
            );

            if ( !empty($tds_subscriptions_results) && count($tds_subscriptions_results) ) {
                $tds_subscription = $tds_subscriptions_results[0];

                $plan = tds_util::get_plan($tds_subscription['plan_id']);

                $plan_id = $plan ? $plan['id'] : 'N/A';
                $plan_name = $plan ? $plan['name'] : 'N/A';

                $is_free = $tds_subscription['is_free'];
                $is_unlimited = $tds_subscription['is_unlimited'];
                $price = $tds_subscription['price'];
                $curr_name = $tds_subscription['curr_name'];
                $coupon_id = $tds_subscription['coupon_id'];
                $trial_days = intval($tds_subscription['trial_days']);

                $start_date = tds_util::get_formatted_date($tds_subscription['start_date']);

                $cycle_interval = $tds_subscription['cycle_interval'];
                $cycle_interval_count = $tds_subscription['cycle_interval_count'];

                if ( empty($is_free) && ( empty($is_unlimited) || $trial_days > 0 ) ) {
                    $end_date = tds_util::get_subscription_end_date( $start_date, $cycle_interval, $cycle_interval_count, max($trial_days, 0) )->format('Y-m-d');
                } else {
                    $end_date = __td('unlimited', TD_THEME_NAME );
                }

                $result['response'] = [
                    'local_subscription_id' => $tds_subscription['id'],
                    'local_plan_id' => $plan_id,
                    'local_plan_name' => $plan_name,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'price' => $price,
                    'is_free' => $is_free,
                    'curr_name' => $curr_name
                ];

                if( empty($is_free) && empty($is_unlimited) ) {
                    $result['response']['cycle_interval'] = $cycle_interval;
                    $result['response']['cycle_interval_count'] = $cycle_interval_count;
                    $result['response']['cycle_interval_format'] = $cycle_interval_count . ' ' . tds_util::ci_format( $cycle_interval, $cycle_interval_count );
                }

                if ( !empty($coupon_id) ) {
                    $result['response']['price_full'] = $price;
                    $result['response']['price'] = tds_util::get_coupon_discount( $coupon_id, $price );
                }

                $payment_page_id = tds_util::get_tds_option('payment_page_id');
                if ( !is_null($payment_page_id) ) {
                    $payment_page_permalink = get_permalink($payment_page_id);
                    if ( false !== $payment_page_permalink ) {
                        $confirm_url = add_query_arg( array(
                            'subscription' => $tds_subscription['id'],
                            'key' => $tds_subscription['confirm_key']
                        ), $payment_page_permalink );
                    }
                }

                if ( !empty($confirm_url) ) {
                    $result['response']['confirm_url'] = $confirm_url;
                }

                $my_account_page_id = tds_util::get_tds_option('my_account_page_id');
                if ( class_exists('SitePress') ) {
                    $translated_my_account_page_id = apply_filters( 'wpml_object_id', $my_account_page_id, 'page' );
                    if ( !is_null($translated_my_account_page_id) ) {
                        $my_account_page_id = $translated_my_account_page_id;
                    }
                }

                if ( !is_null($my_account_page_id) ) {
                    $my_account_permalink = get_permalink($my_account_page_id);
                    if ( false !== $my_account_permalink ) {
                        $view_subscription_url = add_query_arg( [ 'subscriptions' => '', 'expand' => $tds_subscription['id'] ], $my_account_permalink );
                    }
                }

                if ( !empty($view_subscription_url) ) {
                    $result['response']['view_subscription_url'] = $view_subscription_url;
                }

            } else {
                $result['error'] = 'subscription for stripe session id `' . $checkout_session_id . '` was not found !';
                $result['response'] = [];
            }

        }

        die( json_encode($result) );

    }

    static function create_stripe_subscription($subscription_data) {

        global $wpdb;

        $result = [];

        // local subscription data
        $subscription_user_id = $subscription_data['user_id'];
        $subscription_plan_id = $subscription_data['plan_id'];
        $subscription_coupon_id = $subscription_data['coupon_id'];

        // stripe checkout session data
        $stripe_cs_mode = $subscription_data['session_mode'];

        $stripe_customer_id = $subscription_data['stripe_customer_id'];
        $stripe_subscription_id = $subscription_data['stripe_subscription_id'];

        $stripe_payment_types = $subscription_data['payment_method_types'];
        $stripe_payment_status = $subscription_data['payment_status']; // one of paid, unpaid, or no_payment_required
        $stripe_payment_invoice = $subscription_data['stripe_invoice_id']; // the stripe payment invoice id
        $stripe_payment_info = $subscription_data['session_id']; // the checkout session id

        if ( empty($subscription_user_id) ) {
            $result['errors'][] = 'Invalid user id';
        }

        if ( empty($subscription_plan_id) ) {
            $result['errors'][] = 'Invalid plan id';
        }

        // if errors, process them and return $result
        if ( !empty($result['errors']) ) {
            error_log( implode( ' | ', $result['errors'] ) );
            $result['error'] = implode( '<br>', $result['errors'] );
            return $result;
        }

        // stripe api init
        require_once TDS_PATH . '/includes/vendor/stripe/init.php';

        // get stripe api payments data (api keys etc)
        $tds_payment_stripe_results = $wpdb->get_results(
            "SELECT * FROM tds_payment_stripe LIMIT 1",
            ARRAY_A
        );

        // stripe api key
        $is_testing = !empty($tds_payment_stripe_results[0]['is_sandbox']) ? 'sandbox_' : '';
        $secret_key = $tds_payment_stripe_results[0][$is_testing . 'secret_key'];

        // stripe client init
        $stripe_client = new \Stripe\StripeClient($secret_key);

        // plan check
        $valid_plan = false;

        $plan_name = '';
        $cycle_interval = '';
        $cycle_interval_count = 0;
        $trial_days = 0;
        $is_free = 0;
        $is_unlimited = 0;
        $price = 0;
        $list_id = '';
        $plan_publishing_limits = array();

        $curr_name = $curr_pos = $curr_th_sep = $curr_dec_sep = $curr_dec_no = '';

        $plan = tds_util::get_plan($subscription_plan_id);
        if ($plan) {

            $valid_plan = true;
            $plan_name = $plan['name'];

            // do not set subscription cycle_interval & cycle_interval_count for free or unlimited plans
            if ( empty($plan['is_free']) && empty($plan['is_unlimited']) ) {
                $cycle_interval = $plan['interval'];
                $cycle_interval_count = $plan['interval_count'];
            }

            $trial_days = max( intval($plan['trial_days']), 0 );
            $is_free = $plan['is_free'];
            $is_unlimited = $plan['is_unlimited'];
            $price = $plan['price'];
            $list_id = $plan['list'];

            if ( $plan['publishing_limits'] ) {
                $plan_publishing_limits = unserialize($plan['publishing_limits']);
            }

            // check previous subscriptions to allow a user to benefit from trial just one time
            $user_subscriptions = tds_util::get_user_subscriptions_to_plan( $subscription_user_id, $subscription_plan_id );

            // if the user had other subscriptions to this plan reset trial days ( no trial )
            if ( null !== $user_subscriptions && count($user_subscriptions) ) {
                $trial_days = 0; // set trial to 0 days
            }

            // invalidate plan if plan is free
            if ( !empty($is_free) ) {
                $result['errors'][] = 'Invalid plan (plan is free).';
                $valid_plan = false;
            }

            tds_util::get_currency_options($curr_name, $curr_pos, $curr_th_sep, $curr_dec_sep, $curr_dec_no);

        } else {
            $result['errors'][] = 'Invalid plan';
        }

        // if errors, process them and return $result
        if ( !empty($result['errors']) ) {
            error_log( implode( ' | ', $result['errors'] ) );
            $result['error'] = implode( '<br>', $result['errors'] );
            return $result;
        }

        if ($valid_plan) {

            // initialize subscription data values array
            $data_values = array(
                'user_id' => $subscription_user_id,
                'plan_id' => $subscription_plan_id,
                'price' => $price,
                'curr_name' => $curr_name,
                'curr_pos' => $curr_pos,
                'curr_th_sep' => $curr_th_sep,
                'curr_dec_sep' => $curr_dec_sep,
                'curr_dec_no' => $curr_dec_no,
                'payment_type' => 'stripe',
                'status' => empty($trial_days) ? 'waiting_payment' : 'trial',
                'cycle_interval' => $cycle_interval,
                'cycle_interval_count' => $cycle_interval_count,
                'trial_days' => $trial_days,
                'is_free' => $is_free,
                'is_unlimited' => $is_unlimited,
                'start_date' => date('Y-m-d'),
                'stripe_payment_info' => $stripe_payment_info,
                'stripe_customer_id' => $stripe_customer_id,
                'stripe_subscription_id' => $stripe_subscription_id,
            );
            $data_format = array(
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            );

            // add billing data
            $subscription_billing_data = $subscription_data['billing_data'];
            foreach ( $subscription_billing_data as $billing_data_key => $billing_data_val ) {
                $data_values[$billing_data_key] = $billing_data_val;
                $data_format[] = '%s';
            }

            // create & set confirm key
            $confirm_key = $subscription_user_id . uniqid() . $subscription_plan_id;
            $data_values['confirm_key'] = $confirm_key;
            $data_format[] = '%s';

            // stripe checkout session mode process
            if ( $stripe_cs_mode === 'subscription' ) {

                // stripe subscription details
                if ( $stripe_subscription_id ) {

                    $stripe_subscription_data = $subscription_retrieve_error = '';

                    try {
                        $stripe_subscription_data = $stripe_client->subscriptions->retrieve(
                            $stripe_subscription_id,
                            [ 'expand' => [ 'latest_invoice.payment_intent.payment_method' ] ]
                        );
                    } catch ( Exception $ex ) {
                        $subscription_retrieve_error = $ex->getMessage();
                    }

                    if ($stripe_subscription_data) {

                        // stripe subscription status
                        $stripe_subscription_status = $stripe_subscription_data->status;

                        // if subscription was created with trial period
                        if ( $stripe_subscription_status === 'trialing' ) {

                            // set trialling status
                            $data_values['stripe_payment_status'] = 'subscription_create - trialing';
                            $data_format[] = '%s';

                        } elseif ( $stripe_payment_status === 'paid' ) {

                            // set active status to local subscription
                            $data_values['status'] = 'active';
                            $data_format[] = '%s';

                            // set paid status
                            $data_values['stripe_payment_status'] = 'subscription_create - invoice.paid';
                            $data_format[] = '%s';

                        }

                        // invoice details
                        $stripe_invoice_data = $subscription_invoice_retrieve_error = '';
                        if ( !empty($stripe_subscription_data->latest_invoice) ) {
                            $stripe_invoice_data = $stripe_subscription_data->latest_invoice;
                        }

                        if ($stripe_invoice_data) {

                            // set invoice details db values
                            $invoice_url = $stripe_invoice_data->hosted_invoice_url ?? '';
                            $invoice_pdf = $stripe_invoice_data->invoice_pdf ?? '';
                            if ( !empty($invoice_url) || !empty($invoice_pdf) ) {
                                $invoice_details = array(
                                    'invoice_url' => $stripe_invoice_data->hosted_invoice_url ?? '',
                                    'invoice_pdf' => $stripe_invoice_data->invoice_pdf ?? '',
                                    'next_payment_attempt' => '',
                                );
                                $data_values['stripe_invoice_details'] = json_encode($invoice_details);
                                $data_format[] = '%s';
                            }

                            // invoice payment intent
                            $invoice_payment_intent = $stripe_invoice_data->payment_intent ?? '';
                            if ( !empty($invoice_payment_intent) ) {
                                $data_values['stripe_payment_intent'] = $invoice_payment_intent->id;
                                $data_format[] = '%s';

                                // invoice payment method
                                $invoice_payment_method = $invoice_payment_intent->payment_method ?? '';
                                if ( $invoice_payment_method ) {
                                    $data_values['stripe_payment_method'] = json_encode($invoice_payment_method);
                                    $data_format[] = '%s';
                                }

                            }

                        } else {
                            $subscription_invoice_retrieve_error = 'Latest invoice data is missing from stripe subscription data.';
                        }

                    }

                    // add the stripe_subscription result
                    if ( !empty($stripe_subscription_data) ) {
                        $result['response']['stripe_subscription'] = $stripe_subscription_data;
                    } else if ( !empty( $subscription_retrieve_error ) ) {
                        $result['response']['subscription_retrieve_error'] = $subscription_retrieve_error;
                    }

                    // add the stripe_subscription_invoice result
                    if ( !empty($stripe_invoice_data) ) {
                        $result['response']['stripe_subscription_invoice'] = $stripe_invoice_data;
                    } else if ( !empty($subscription_invoice_retrieve_error) ) {
                        $result['response']['stripe_subscription_invoice_retrieve_error'] = $subscription_invoice_retrieve_error;
                    }

                }

            }  elseif ( $stripe_cs_mode === 'payment' ) {

                // stripe payment invoice details
                if ( $stripe_payment_invoice ) {

                    $payment_invoice_data = $payment_invoice_retrieve_error = '';

                    try {
                        $payment_invoice_data = $stripe_client->invoices->retrieve(
                            $stripe_payment_invoice,
                            [ 'expand' => [ 'payment_intent.payment_method' ] ]
                        );
                    } catch ( Exception $ex ) {
                        $payment_invoice_retrieve_error = $ex->getMessage();
                    }

                    if ($payment_invoice_data) {

                        // if check session status is set to paid set local subscription status to paid
                        if ( $stripe_payment_status === 'paid' ) {

                            // set active status to local subscription
                            $data_values['status'] = 'active';
                            $data_format[] = '%s';

                            // set paid status
                            $data_values['stripe_payment_status'] = 'manual - paid';
                            $data_format[] = '%s';

                        } else if ( $stripe_payment_status === 'unpaid' ) {

                            // set status
                            $data_values['stripe_payment_status'] = 'manual - unpaid';
                            $data_format[] = '%s';

                        }

                        // invoice details, set invoice details db values
                        $invoice_url = $payment_invoice_data->hosted_invoice_url ?? '';
                        $invoice_pdf = $payment_invoice_data->invoice_pdf ?? '';
                        if ( !empty($invoice_url) || !empty($invoice_pdf) ) {
                            $invoice_details = array(
                                'invoice_url' => $invoice_url,
                                'invoice_pdf' => $invoice_pdf,
                                'next_payment_attempt' => '',
                            );
                            $data_values['stripe_invoice_details'] = json_encode($invoice_details);
                            $data_format[] = '%s';
                        }

                        // invoice payment intent
                        $invoice_payment_intent = $payment_invoice_data->payment_intent ?? '';
                        if ( !empty($invoice_payment_intent) ) {
                            $data_values['stripe_payment_intent'] = $invoice_payment_intent->id;
                            $data_format[] = '%s';

                            // invoice payment method
                            $invoice_payment_method = $invoice_payment_intent->payment_method ?? '';
                            if ( $invoice_payment_method ) {
                                $data_values['stripe_payment_method'] = json_encode($invoice_payment_method);
                                $data_format[] = '%s';
                            }

                        }

                    }

                    // add the stripe payment invoice result
                    if ( !empty($payment_invoice_data) ) {
                        $result['response']['payment_invoice_data'] = $payment_invoice_data;
                    } else if ( !empty($payment_invoice_retrieve_error) ) {
                        $result['response']['payment_invoice_retrieve_error'] = $payment_invoice_retrieve_error;
                    }

                } else {

                    // if check session status is set to no_payment_required set local subscription status to active && stripe payment status as paid
                    if ( $stripe_payment_status === 'no_payment_required' ) {

                        // set active status to local subscription
                        $data_values['status'] = 'active';
                        $data_format[] = '%s';

                        // set paid status
                        $data_values['stripe_payment_status'] = 'manual - no_payment_required';
                        $data_format[] = '%s';

                    }

                }

            } elseif ( $stripe_cs_mode === 'setup' ) {

                // get stripe price id
                $stripe_price_id = $subscription_data['price_id'];

                // setup invoice
                $finalized_invoice = $setup_invoice_error = '';

                try {

                    // create customer invoice
                    $setup_invoice = $stripe_client->invoices->create([
                        'customer' => $stripe_customer_id,
                        'payment_settings' => [
                            'payment_method_types' => $stripe_payment_types
                        ]
                    ]);

                    // create invoice item
                    $stripe_client->invoiceItems->create([
                        'customer' => $stripe_customer_id,
                        'price' => $stripe_price_id,
                        'invoice' => $setup_invoice->id,
                    ]);

                    // finalize invoice
                    $finalized_invoice = $stripe_client->invoices->finalizeInvoice( $setup_invoice->id );

                } catch ( Exception $ex ) {
                    $setup_invoice_error = $ex->getMessage();
                }

                if ( !empty($finalized_invoice) ) {

                    // invoice details, set invoice details db values
                    $invoice_url = $finalized_invoice->hosted_invoice_url ?? '';
                    $invoice_pdf = $finalized_invoice->invoice_pdf ?? '';
                    if ( !empty($invoice_url) || !empty($invoice_pdf) ) {
                        $invoice_details = array(
                            'invoice_id' => $finalized_invoice->id,
                            'invoice_url' => $invoice_url,
                            'invoice_pdf' => $invoice_pdf,
                            'next_payment_attempt' => '',
                        );
                        $data_values['stripe_invoice_details'] = json_encode($invoice_details);
                        $data_format[] = '%s';
                    }

                    // set status
                    $data_values['stripe_payment_status'] = 'setup - invoice finalized';
                    $data_format[] = '%s';

                    // set invoice id as stripe subscription id
                    $data_values['stripe_subscription_id'] = $finalized_invoice->id;
                    $data_format[] = '%s';

                } else {
                    $result['errors'][] = "setup_invoice_error: " . $setup_invoice_error;
                }

                // add the setup invoice result
                if ( !empty($finalized_invoice) ) {
                    $result['response']['stripe_setup_invoice'] = $finalized_invoice;
                } elseif ( !empty($setup_invoice_error) ) {
                    $result['response']['stripe_setup_invoice_error'] = $setup_invoice_error;
                }

            }

            // add coupon
            if ( !empty($subscription_coupon_id) ) {
                $data_values['coupon_id'] = $subscription_coupon_id;
                $data_format[] = '%s';
            }

            // add plan posts remaining
            if ( !empty($plan_publishing_limits) ) {
                $posts_remaining = array();

                foreach( $plan_publishing_limits as $plan_publishing_limit ) {
                    $posts_remaining[$plan_publishing_limit->post_type] = $plan_publishing_limit->limit;
                }

                $data_values['plan_posts_remaining'] = serialize($posts_remaining);
                $data_format[] = '%s';
            }

            // set created at value
            $data_values['created_at'] = date('Y-m-d H:i:s');
            $data_format[] = '%s';

            //$wpdb->suppress_errors = true;
            $insert_result = $wpdb->insert( 'tds_subscriptions',
                $data_values,
                $data_format
            );

            if ( false === $insert_result ) {
                $result['errors'][] = "wpdb > tds_subscriptions insert error: " . $wpdb->last_error;
            } else {

                if ( empty($is_free) && ( empty($is_unlimited) || $trial_days > 0 ) ) {
                    $end_date = tds_util::get_subscription_end_date( date('Y-m-d'), $cycle_interval, $cycle_interval_count, max($trial_days, 0) )->format('Y-m-d');
                } else {
                    $end_date = __td('unlimited', TD_THEME_NAME );
                }

                $result['response'] = [
                    'local_subscription_id' => $wpdb->insert_id,
                    'local_plan_id' => $subscription_plan_id,
                    'local_plan_name' => $plan_name,
                    'start_date' => date('Y-m-d'),
                    'end_date' => $end_date,
                    'price' => $price,
                    'is_unlimited' => $is_unlimited,
                    'curr_name' => $curr_name
                ];

                if( empty($is_unlimited) ) {
                    $result['response']['cycle_interval'] = $cycle_interval;
                    $result['response']['cycle_interval_count'] = $cycle_interval_count;
                    $result['response']['cycle_interval_format'] = $cycle_interval_count . ' ' . tds_util::ci_format( $cycle_interval, $cycle_interval_count );
                }

                if ( !empty($subscription_coupon_id) ) {
                    $result['response']['price_full'] = $price;
                    $result['response']['price'] = tds_util::get_coupon_discount( $subscription_coupon_id, $price );
                }

                $payment_page_id = tds_util::get_tds_option('payment_page_id');
                if ( !is_null($payment_page_id) ) {
                    $payment_page_permalink = get_permalink($payment_page_id);
                    if ( false !== $payment_page_permalink ) {
                        $confirm_url = add_query_arg( array(
                            'subscription' => $wpdb->insert_id,
                            'key' => $confirm_key
                        ), $payment_page_permalink );
                    }
                }

                if ( !empty($confirm_url) ) {
                    $result['response']['confirm_url'] = $confirm_url;
                }

                $my_account_page_id = tds_util::get_tds_option('my_account_page_id');
                if ( class_exists('SitePress') ) {
                    $translated_my_account_page_id = apply_filters( 'wpml_object_id', $my_account_page_id, 'page' );
                    if ( !is_null($translated_my_account_page_id) ) {
                        $my_account_page_id = $translated_my_account_page_id;
                    }
                }

                if ( !is_null($my_account_page_id) ) {
                    $my_account_permalink = get_permalink($my_account_page_id);
                    if ( false !== $my_account_permalink ) {
                        $view_subscription_url = add_query_arg( [ 'subscriptions' => '', 'expand' => $wpdb->insert_id ], $my_account_permalink );
                    }
                }

                if ( !empty($view_subscription_url) ) {
                    $result['response']['view_subscription_url'] = $view_subscription_url;
                }

                /**
                 * confirmation emails
                 */

                // set confirmation emails data
                $coupon_code = 'No discount code used';
                $subscription_full_price = '';
                if( !empty($subscription_coupon_id) ) {
                    $coupon = tds_util::get_coupon($subscription_coupon_id);

                    if( $coupon !== null ) {
                        $coupon_code = $coupon['name'];
                        $subscription_full_price = tds_util::get_basic_currency($price);
                        $price = tds_util::get_coupon_discount( $subscription_coupon_id, $price );
                    }
                }

                $add_tags = array( '%subscription_name%', '%subscription_price%', '%coupon_code%', '%subscription_expiry%', '%subscription_full_price%' );
                $add_tags_replacements = array( $plan_name, tds_util::get_basic_currency($price), $coupon_code, $end_date, $subscription_full_price );

                // send confirmation email to both member and admins
                tds_email_notifications::send_user_email_notification('subscription', $subscription_user_id, $add_tags, $add_tags_replacements);
                tds_email_notifications::send_admin_email_notification('subscription', $subscription_user_id, $add_tags, $add_tags_replacements);

                // add billing email to the plan list
                if ( !empty($list_id) && $data_values['billing_email'] !== '' ) {

                    $found_post = tds_util::get_post_by_title( $data_values['billing_email'],'tds_email' );
                    if ( $found_post === null ) {
                        $new_post_id = wp_insert_post([
                            'post_title' => $data_values['billing_email'],
                            'post_type'=> 'tds_email',
                            'post_status' => 'publish',
                        ]);

                        if ( !is_wp_error($new_post_id) && 0 < $new_post_id ) {
                            wp_set_object_terms( $new_post_id, array( intval($list_id) ), 'tds_list' );
                        }

                    }

                }

            }

            // save billing details
            $billing_details_row_exists = $wpdb->get_results(
                $wpdb->prepare("SELECT id FROM tds_billing WHERE user_id = %s", $subscription_user_id )
            );

            if ( empty($billing_details_row_exists) ) {

                // set billing data to save
                $billing_data = $subscription_billing_data;

                // add user id
                $billing_data['user_id'] = $subscription_user_id;

                // insert billing data
                $insert_billing_details = $wpdb->insert('tds_billing',
                    $billing_data,
                    [ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
                );

                if ( false === $insert_billing_details ) {
                    $result['errors'][] = "wpdb > tds_billing insert error: " . $wpdb->last_error;
                }

            }

            // if errors, process them and return $result
            if ( !empty($result['errors']) ) {
                error_log( implode( ' | ', $result['errors'] ) );
                $result['error'] = implode( '<br>', $result['errors'] );
                return $result;
            }

        }

        return $result;

    }

}
