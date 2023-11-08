<?php

class tds_update {

	private static $versions = ['1.2', '1.3', '1.3.2', '1.4', '1.4.1', '1.4.4', '1.5'];

	static function update_settings( $to_version ) {

		if ( empty( $to_version ) ) {
			return '';
		}

		$versions = self::get_upper_versions( $to_version );
        $results = [];

		foreach ( $versions as $version ) {
		    $method_name = '_to_' . str_replace('.', 'p', $version );
		    if ( method_exists(__CLASS__, $method_name ) ) {
                $method_return = call_user_func( array( __CLASS__, $method_name ) );

                if ( !empty($method_return) ) {
                    $results[$method_name] = $method_return;
                }

            }
		}

        return $results;

	}

	static function get_upper_versions( $current_version ) {
		$upper_versions = [];
		foreach ( self::$versions as $version ) {
			if ( 1 === version_compare( $version, $current_version ) ) {
				$upper_versions[] = $version;
			}
		}

		return $upper_versions;
	}

	static function _to_1p2 () {
		global $wpdb;

		try {

			$wpdb->query( "ALTER TABLE `tds_subscriptions` MODIFY paypal_order_info TEXT;" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception($wpdb->print_error());
			}

			$wpdb->query( "ALTER TABLE `tds_subscriptions` ADD `curr_dec_no` VARCHAR(30) DEFAULT NULL AFTER `price`" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception($wpdb->print_error());
			}

			$wpdb->query( "ALTER TABLE `tds_subscriptions` ADD `curr_dec_sep` VARCHAR(30) NULL DEFAULT NULL AFTER `price`" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception($wpdb->print_error());
			}

			$wpdb->query( "ALTER TABLE `tds_subscriptions` ADD `curr_th_sep` VARCHAR(30) NULL DEFAULT NULL AFTER `price`" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception($wpdb->print_error());
			}

			$wpdb->query( "ALTER TABLE `tds_subscriptions` ADD `curr_pos` VARCHAR(30) NULL DEFAULT NULL AFTER `price`" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception($wpdb->print_error());
			}

			$wpdb->query( "ALTER TABLE `tds_subscriptions` ADD `curr_name` VARCHAR(50) NULL DEFAULT NULL AFTER `price`" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception($wpdb->print_error());
			}

			$wpdb->query( "ALTER TABLE `tds_subscriptions` ADD `next_price` VARCHAR(50) DEFAULT NULL AFTER `price`" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception($wpdb->print_error());
			}

		} catch (Exception $ex) {
			// $ex
			return;
		}

		$wpdb->query( 'SET autocommit = 0;' );

		$wpdb->query('START TRANSACTION;');

		$wpdb->query("LOCK TABLES tds_subscriptions WRITE, tds_options WRITE;");

		try {

			$wpdb->query( "UPDATE `tds_subscriptions` SET `curr_name` = 'USD' WHERE `curr_name` IS NULL" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception($wpdb->print_error());
			}

			$wpdb->query( "UPDATE `tds_subscriptions` SET `curr_pos` = 'left_space' WHERE `curr_pos` IS NULL" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception($wpdb->print_error());
			}

			$wpdb->query( "UPDATE `tds_subscriptions` SET `curr_th_sep` = ',' WHERE `curr_th_sep` IS NULL" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception($wpdb->print_error());
			}

			$wpdb->query( "UPDATE `tds_subscriptions` SET `curr_dec_sep` = '.' WHERE `curr_dec_sep` IS NULL" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception($wpdb->print_error());
			}

			$wpdb->query( "UPDATE `tds_subscriptions` SET `curr_dec_no` = '0' WHERE `curr_dec_no` IS NULL" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception($wpdb->print_error());
			}

			foreach ( [ 'curr_name'    => 'USD',
			            'curr_pos'     => 'left',
			            'curr_th_sep'   => '.',
			            'curr_dec_sep' => ',',
			            'curr_dec_no'  => '0'
			] as $key => $val
			) {
				$wpdb->insert( 'tds_options',
					array(
						'name'  => $key,
						'value' => $val
					),
					array( '%s', '%s' ) );

				if ( '' !== $wpdb->last_error ) {
					throw new Exception($wpdb->print_error());
				}
			}

			tds_util::set_tds_option('version', '1.2');
			$wpdb->query('COMMIT');

		} catch (Exception $ex) {
			// $ex
			$wpdb->query( 'ROLLBACK' );
		} finally {
			$wpdb->query('UNLOCK TABLES');
			$wpdb->query( 'SET autocommit = 1;' );
		}
	}

	static function _to_1p3 () {
		global $wpdb;

		try {

			$wpdb->query( "ALTER TABLE `tds_subscriptions` ADD `stripe_payment_info` TEXT DEFAULT NULL AFTER `paypal_order_capture_update_time`" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception($wpdb->print_error());
			}

			$wpdb->query( "ALTER TABLE `tds_subscriptions` ADD `stripe_payment_status` VARCHAR(40) DEFAULT NULL AFTER `paypal_order_capture_update_time`" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception($wpdb->print_error());
			}

			$wpdb->query( "ALTER TABLE `tds_subscriptions` ADD `stripe_payment_intent` VARCHAR(40) DEFAULT NULL AFTER `paypal_order_capture_update_time`" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception($wpdb->print_error());
			}

			tds_util::set_tds_option('version', '1.3');

		} catch (Exception $ex) {
			// $ex
			return;
		}
	}

	static function _to_1p3p2 () {
		global $wpdb;

		try {

			$wpdb->query( "ALTER TABLE `tds_plans` ADD `list` VARCHAR(255) DEFAULT NULL AFTER `options`" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception( $wpdb->print_error() );
			}

			$wpdb->query( "ALTER TABLE `tds_subscriptions` ADD `canceled` TINYINT NOT NULL DEFAULT '0' AFTER `created_at`" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception( $wpdb->print_error() );
			}

			$wpdb->query( "ALTER TABLE `tds_subscriptions` ADD `coupon_id` INT NOT NULL DEFAULT 0 AFTER `canceled`" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception( $wpdb->print_error() );
			}

            $wpdb->query( "ALTER TABLE `tds_companies` convert to character set utf8mb4 collate utf8mb4_unicode_520_ci" );
            if ( '' !== $wpdb->last_error ) {
                throw new Exception( $wpdb->print_error() );
            }

            $wpdb->query( "ALTER TABLE `tds_payment_bank` convert to character set utf8mb4 collate utf8mb4_unicode_520_ci" );
            if ( '' !== $wpdb->last_error ) {
                throw new Exception( $wpdb->print_error() );
            }

            $wpdb->query( "ALTER TABLE `tds_subscriptions` convert to character set utf8mb4 collate utf8mb4_unicode_520_ci" );
            if ( '' !== $wpdb->last_error ) {
                throw new Exception( $wpdb->print_error() );
            }

			tds_util::set_tds_option('version', '1.3.2');

		} catch ( Exception $ex ) {
			// $ex
			return;
		}
	}

	static function _to_1p4 () {
		global $wpdb;

		try {

			$wpdb->query( "ALTER TABLE `tds_options` MODIFY value LONGTEXT;" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception( $wpdb->print_error() );
			}

			$wpdb->query( "ALTER TABLE `tds_subscriptions` MODIFY stripe_payment_intent VARCHAR(255);" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception( $wpdb->print_error() );
			}

			$wpdb->query( "ALTER TABLE `tds_subscriptions` MODIFY stripe_payment_status VARCHAR(255);" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception( $wpdb->print_error() );
			}

            $wpdb->query( "ALTER TABLE `tds_payment_bank` MODIFY description VARCHAR(1000);" );
            if ( '' !== $wpdb->last_error ) {
                throw new Exception( $wpdb->print_error() );
            }

            $wpdb->query( "ALTER TABLE `tds_payment_bank` MODIFY instruction VARCHAR(1000);" );
            if ( '' !== $wpdb->last_error ) {
                throw new Exception( $wpdb->print_error() );
            }

            // add columns to tds_subscriptions table
			$columns_to_add = array( 'stripe_customer_id', 'stripe_subscription_id', 'stripe_invoice_details' );
			foreach ( $columns_to_add as $column_name ) {

				$add = true;
				foreach ( $wpdb->get_col( "DESC tds_subscriptions", 0 ) as $column ) {
					if ( $column === $column_name ) {
						$add = false;
					}
				}

				if ( $add ) {

					if ( $column_name === 'stripe_invoice_details' ) {
						$wpdb->query( "ALTER TABLE `tds_subscriptions` ADD `$column_name` TEXT AFTER `stripe_payment_info`" );
					} else {
						$wpdb->query( "ALTER TABLE `tds_subscriptions` ADD `$column_name` VARCHAR(40) DEFAULT '' AFTER `stripe_payment_info`" );
					}

					if ( '' !== $wpdb->last_error ) {
						throw new Exception( $wpdb->print_error() );
					}

				}

			}

			// add default data to tds_options table
			$tds_options = tds_util::get_tds_options();
			foreach ( [ 'from_name'    					   => get_bloginfo('name'),
			            'from_email'     				   => get_bloginfo('admin_email'),
			            'admin_notice_emails'     		   => get_bloginfo('admin_email'),
			            'email_footer_text'     		   => '&copy; ' . get_bloginfo('name'),

			            'register_email_enabled'           => '1',
			            'register_email_enabled_admin'     => '1',
			            'register_email_subject'       	   => '[%blogname%] Activate account',
			            'register_email_subject_admin'	   => '[%blogname%] New user registration',
			            'register_email_body'     		   => 
							'<h3>Welcome onboard!</h3>
							<p>Hello %name%,</p>
							<p>Thank you for registering on %blogname%! To activate your account, please visit the following link:</p>
							<p><a href="%verification_link%">%verification_link%</a></p>',
			            'register_email_body_admin'        => 
							'<h3>New user!</h3>
							<p>A new user has registered on your website!</p>
							<p>Username: %username%<br>
							Email: %useremail%</p>',

			            'optin_email_enabled'       	   => '1',
			            'optin_email_enabled_admin' 	   => '0',
			            'optin_email_subject'       	   => '[%blogname%] Confirm subscription',
			            'optin_email_subject_admin'        => '',
			            'optin_email_body'     			   => 
							'<h3>Welcome onboard!</h3>
							<p>Hello,</p>
							<p>Thank you for subscribing to %blogname%! To confirm your subscription, please visit the following link:</p>
							<p><a href="%optin_confirm_link%">%optin_confirm_link%</a></p>',
			            'optin_email_body_admin'        => '',

			            'password_email_enabled'       	   => '1',
			            'password_email_enabled_admin' 	   => '0',
			            'password_email_subject'       	   => '[%blogname%] Password reset',
			            'password_email_subject_admin'     => '',
			            'password_email_body'     		   => 
							'<h3>Password reset</h3>
							<p>Hello %name%,</p>
							<p>Someone has requested a password reset for your account.</p>
							<p>To reset your password, visit the following address: <a href="%pass_reset_link%">%pass_reset_link%</a>.</p>
							<p>If this was a mistake, just ignore this email and nothing will happen.</p>',
			            'password_email_body_admin'        => '',

			            'subscription_email_enabled'       => '1',
			            'subscription_email_enabled_admin' 	   => '1',
			            'subscription_email_subject'       => '[%blogname%] Subscription confirmation',
			            'subscription_email_subject_admin' => '[%blogname%] New subscriber',
			            'subscription_email_body'     	   => 
							'<h3>Subscription confirmation</h3>
							<p>Hello %name%,</p>
							<p>Thank you for subscribing to %blogname%!</p>
							<p>Subscription plan: %subscription_name%<br>
							Subscription price: %subscription_price%</p>
							%direct_bank_info%',
			            'subscription_email_body_admin'    => 
							'<h3>New subscription</h3>
							<p>A new user has subscribed to your website.</p>
							<p>Username: %username%<br>
							Subscription plan: %subscription_name%<br>
							Subscription price: %subscription_price%</p>',

			            'renewal_email_enabled'       	   => '1',
			            'renewal_email_enabled_admin' 	   => '0',
			            'renewal_email_subject'            => '[%blogname%] Subscription renewal',
			            'renewal_email_subject_admin'      => '[%blogname%] Subscription renewal',
			            'renewal_email_body'     		   => 
							'<h3>Subscription renewal</h3>
							<p>Hello %name%,</p>
							<p>Your subscription on %blogname% has been sucessfully renewed.
							Subscription plan: %subscription_name%<br>
							Subscription price: %subscription_price%</p>',
			            'renewal_email_body_admin'     	   => 
							'<h3>Subscription renewal</h3>
							<p>An user has successfully renewed their subscription.</p>
							<p>Username: %username%<br>
							Subscription plan: %subscription_name%<br>
							Subscription price: %subscription_price%</p>',

			            'cancel_email_enabled'       	   => '1',
			            'cancel_email_enabled_admin' 	   => '1',
			            'cancel_email_subject'              => '[%blogname%] Subscription canceled',
			            'cancel_email_subject_admin'       	   => '[%blogname%] Subscription canceled',
			            'cancel_email_body'     		   => 
							'<h3>Subscription cancelation</h3>
							<p>Hello %name%,</p>
							<p>We are sorry to see you go! Your subscription on %blogname% has been canceled and is only valid until %subscription_expiry%. You will not be charged in the future.</p>',
			            'cancel_email_body_admin'     	   => 
							'<h3>Subscription cancelation</h3>
							<p>An user on your website has canceled their subscription.</p>
							<p>Username: %username%<br>
							Subscription plan: %subscription_name%<br>
							Subscription expiry: %subscription_expiry%</p>',

			            'failed_email_enabled'       	   => '1',
			            'failed_email_enabled_admin' 	   => '0',
			            'failed_email_subject'       	   => '[%blogname%] Your latest payment has failed',
			            'failed_email_subject_admin'       => '[%blogname%] A subscription payment has failed',
			            'failed_email_body'     		   => 
							'<h3>Payment failure</h3>
							<p>Hello %name%,</p>
							<p>Your latest payment for "%subscription_name%" has failed.</p>
							<p>You can go to the <a href="%subscriptions_page_link%">account page</a> in order to try again.</p>',
			            'failed_email_body_admin'     	   => 
							'<h3>Payment failure</h3>
							<p>An user on your website has failed to pay for their subscription.</p>
							<p>Username: %username%<br>
							Subscription plan: %subscription_name%<br>
							Subscription price: %subscription_price%</p>',
			] as $key => $val ) {

				$add = true;
				foreach ( $tds_options as $tds_option ) {
					if ( $tds_option['name'] == $key ) {
						$add = false;
					}
				}

				if ( $add ) {
					$wpdb->insert(
						'tds_options',
						array( 'name'  => $key, 'value' => $val ),
						array( '%s', '%s' )
					);

					if ( '' !== $wpdb->last_error ) {
						throw new Exception( $wpdb->print_error() );
					}
				}

			}

			// get stripe api keys
			$results = $wpdb->get_results("SELECT * FROM tds_payment_stripe LIMIT 1", ARRAY_A );
			if ( null !== $results ) {

				// the stripe payment id
				$tds_stripe_payment_id = $results[0]['id'];

				$is_testing = '';
				if ( !empty( $results[0]['is_sandbox'] ) ) {
					$is_testing = 'sandbox_';
				}

				// the secret api key
				$api_key = $results[0][$is_testing . 'secret_key'];

				if ( !empty($api_key) ) {

					require_once TDS_PATH . '/includes/vendor/stripe/init.php';

					try {
						$stripeClient = new \Stripe\StripeClient($api_key); // set client api key
						$stripeClient->balance->retrieve(); // try to get the balance
						$valid_secret_key = true; // if no error > valid
					} catch ( Exception $ex ) {
						$valid_secret_key = false; // if error > not valid
					}

					// all good ... try to add the tds stripe webhook endpoint
					if ( $valid_secret_key ) {

						\Stripe\Stripe::setApiKey($api_key);

						try {
							// create the tds rest webhook endpoint
							$tds_stripe_webhook_endpoint = \Stripe\WebhookEndpoint::create([
								'url' => rest_url("tds_stripe/webhook/" ),
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
						} catch ( Exception $ex ) {
							$tds_stripe_webhook_endpoint = null;
						}

						// add it to db
						if ( !empty($tds_stripe_webhook_endpoint) ) {
							$wpdb->update( 'tds_payment_stripe',
								array(
									'webhook_endpoint' => $tds_stripe_webhook_endpoint->url,
									'webhook_endpoint_secret' => !empty( $tds_stripe_webhook_endpoint->secret ) ? $tds_stripe_webhook_endpoint->secret : ''
								),
								array( 'id' => $tds_stripe_payment_id ),
								array( '%s', '%s' ),
								array( '%d' )
							);
						}

					}

				}

			}
			if ( '' !== $wpdb->last_error ) {
				throw new Exception( $wpdb->print_error() );
			}

			// check/add billing details table
			$tds_billing_table_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( 'tds_billing' ) );
			if ( $wpdb->get_var( $tds_billing_table_query ) === 'tds_billing' ) {
				// do nothing ... the billing details table was found
			} else {
				// didn't find it, so try to create it
				$wpdb->query( "CREATE TABLE `tds_billing` (
	                    `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	                    `user_id` INT NOT NULL,
	                    `billing_first_name` VARCHAR(50),     
	                    `billing_last_name` VARCHAR(60),
	                    `billing_company_name` VARCHAR(500),
	                    `billing_vat_number` VARCHAR(50), 
	                    `billing_address` VARCHAR(500),
	                    `billing_country` VARCHAR(50),
	                    `billing_city` VARCHAR(50),
	                    `billing_county` VARCHAR(50),
	                    `billing_post_code` VARCHAR(50),
	                    `billing_phone` VARCHAR(50),
	                    `billing_email` VARCHAR(50)
	                );"
				);
			}
			if ( '' !== $wpdb->last_error ) {
				throw new Exception( $wpdb->print_error() );
			}

			tds_util::set_tds_option('version', '1.4' );

		} catch ( Exception $ex ) {
			// $ex
			return;
		}
	}

	static function _to_1p4p1 () {
		global $wpdb;

		try {
			$add_publishing_limits_column = true;
			foreach ( $wpdb->get_col( "DESC tds_plans", 0 ) as $column ) {
				if ( $column === 'publishing_limits' ) {
					$add_publishing_limits_column = false;
				}
			}
			if( $add_publishing_limits_column ) {
				$wpdb->query( "ALTER TABLE `tds_plans` ADD `publishing_limits` LONGTEXT AFTER `list`" );
				if ( '' !== $wpdb->last_error ) {
					throw new Exception( $wpdb->print_error() );
				}
			}

			$add_plan_posts_remaining_column = true;
			foreach ( $wpdb->get_col( "DESC tds_subscriptions", 0 ) as $column ) {
				if ( $column === 'plan_posts_remaining' ) {
					$add_plan_posts_remaining_column = false;
				}
			}
			if( $add_plan_posts_remaining_column ) {
				$wpdb->query( "ALTER TABLE `tds_subscriptions` ADD `plan_posts_remaining` LONGTEXT AFTER `coupon_id`" );
				if ( '' !== $wpdb->last_error ) {
					throw new Exception( $wpdb->print_error() );
				}
			}

			$wpdb->query( "ALTER TABLE `tds_subscriptions` MODIFY stripe_invoice_details TEXT;" );
			if ( '' !== $wpdb->last_error ) {
				throw new Exception( $wpdb->print_error() );
			}

            tds_util::set_tds_option('version', '1.4.1');

		} catch ( Exception $ex ) {
			// $ex
			return;
		}

	}

    static function _to_1p4p4 () {
        global $wpdb;

        try {
            $add_automatic_delistings_column = true;
            foreach ( $wpdb->get_col( "DESC tds_plans", 0 ) as $column ) {
                if ( $column === 'automatic_delistings' ) {
                    $add_automatic_delistings_column = false;
                }
            }
            if( $add_automatic_delistings_column ) {
                $wpdb->query( "ALTER TABLE `tds_plans` ADD `automatic_delistings` LONGTEXT AFTER `publishing_limits`" );
                if ( '' !== $wpdb->last_error ) {
                    throw new Exception( $wpdb->print_error() );
                }
            }

            tds_util::set_tds_option('version', '1.4.4');
        } catch ( Exception $ex ) {
            // $ex
            return;
        }
    }

	static function _to_1p5 () {
		global $wpdb;

        // the update results array
        $result = [];

        // check td log status
        $td_log_status = td_options::get('td_log_status');

        // turn td log on to log tds_update to_1p5 results
        if ( $td_log_status === 'off' ) {
            td_util::update_option('td_log_status', 'on' );
        }

		try {
			
			
			            /**
             * paypal
             */

            // check/create the tds_paypal_payments table
            $tds_paypal_payments_table_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( 'tds_paypal_payments' ) );
            if ( $wpdb->get_var($tds_paypal_payments_table_query) === 'tds_paypal_payments' ) {
                // do nothing ... the tds_paypal_payments table was found
                $result['tds_paypal_payments'] = 'the tds_paypal_payments table was found';
            } else {
                // didn't find it, so try to create it
                $wpdb->query( "CREATE TABLE `tds_paypal_payments` (
	                    `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                        `subscription_id` INT,
                        `order_id` VARCHAR(50) DEFAULT '',
                        `order_intent` VARCHAR(50) DEFAULT '',
                        `order_status` VARCHAR(50) DEFAULT '',                
                        `order_payer_id` VARCHAR(50) DEFAULT '',
                        `order_payer_given_name` VARCHAR(50) DEFAULT '',
                        `order_payer_surname` VARCHAR(50) DEFAULT '',
                        `order_payer_email` VARCHAR(50) DEFAULT '',
                        `order_payee_id` VARCHAR(50) DEFAULT '',
                        `order_payee_email` VARCHAR(50) DEFAULT '',
                        `order_amount_currency_code` VARCHAR(50) DEFAULT '',
                        `order_amount_value` VARCHAR(50) DEFAULT '',
                        `order_info` TEXT DEFAULT '',
                        `order_create_time` VARCHAR(40) DEFAULT '',
                        `order_update_time` VARCHAR(40) DEFAULT '',
                        `order_capture_create_time` VARCHAR(40) DEFAULT '',
                        `order_capture_update_time` VARCHAR(40) DEFAULT '',
                        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
	                );"
                );

                if ( '' !== $wpdb->last_error ) {
                    $result['tds_paypal_payments'] = 'tds_paypal_payments table create error: ' . $wpdb->last_error;
                } else {
                    $result['tds_paypal_payments'] = 'the tds_paypal_payments table was created';
                }

            }

            // drop deprecated paypal columns in tds_subscriptions table
            $paypal_order_id_column_check = false;
            $paypal_column_drop_result = '';
            foreach ( $wpdb->get_col( "DESC tds_subscriptions", 0 ) as $column ) {
                if ( $column === 'paypal_order_id' ) {
                    $paypal_order_id_column_check = true;
                }
            }

            // if paypal column was found
            if ( $paypal_order_id_column_check ) {

                // update result msg
                $paypal_column_drop_result .= 'paypal_order.. columns found ';

                // paypal columns drop query
                $wpdb->query("
                ALTER TABLE `tds_subscriptions`
                DROP COLUMN paypal_order_id,
                DROP COLUMN paypal_order_intent,
                DROP COLUMN paypal_order_status,
                DROP COLUMN paypal_order_payer_id,
                DROP COLUMN paypal_order_payer_given_name,
                DROP COLUMN paypal_order_payer_surname,
                DROP COLUMN paypal_order_payer_email,
                DROP COLUMN paypal_order_payee_id,
                DROP COLUMN paypal_order_payee_email,
                DROP COLUMN paypal_order_amount_currency_code,
                DROP COLUMN paypal_order_amount_value,
                DROP COLUMN paypal_order_info,
                DROP COLUMN paypal_order_create_time,
                DROP COLUMN paypal_order_update_time,
                DROP COLUMN paypal_order_capture_create_time,
                DROP COLUMN paypal_order_capture_update_time
            ");

                // update result msg
                if ( '' !== $wpdb->last_error ) {
                    $paypal_column_drop_result .= '| drop query failed, error: ' . $wpdb->last_error;
                } else {
                    $paypal_column_drop_result .= '| drop query was successful';
                }

            }

            // add tds_subscriptions table paypal columns drop results
            $result['tds_subscriptions']['paypal_column_drop_result'] = $paypal_column_drop_result;
			
			$wpdb->query( "ALTER TABLE `tds_subscriptions` MODIFY billing_company_name VARCHAR(100);" );
            if ( '' !== $wpdb->last_error ) {
                throw new Exception( $wpdb->print_error() );
            }

            // add columns to tds_payment_stripe table
            $columns_to_add = array( 'webhook_endpoint_secret', 'webhook_endpoint', 'payment_methods', 'description', 'instructions' );
            foreach ( $columns_to_add as $column_name ) {

                $add = true;
                foreach ( $wpdb->get_col( "DESC tds_payment_stripe", 0 ) as $column ) {
                    if ( $column === $column_name ) {
                        $add = false;
                    }
                }

                if ( $add ) {

                    if ( $column_name === 'payment_methods' ) {
                        $wpdb->query( "ALTER TABLE `tds_payment_stripe` ADD `$column_name` LONGTEXT AFTER `webhook_endpoint_secret`" );
                    }

                    if ( $column_name === 'description' || $column_name === 'instructions' ) {
                        $wpdb->query( "ALTER TABLE `tds_payment_stripe` ADD `$column_name` VARCHAR(1000) NOT NULL DEFAULT '' AFTER `payment_methods`" );
                    }

                    if ( $column_name === 'webhook_endpoint_secret' || $column_name === 'webhook_endpoint' ) {
                        $wpdb->query( "ALTER TABLE `tds_payment_stripe` ADD `$column_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `sandbox_public_key`" );
                    }

                    if ( '' !== $wpdb->last_error ) {
                        throw new Exception( $wpdb->last_error );
                    } else {
                        $result['tds_payment_stripe'][$column_name] = 'added';
                    }

                } else {
                    $result['tds_payment_stripe'][$column_name] = 'found';
                }

            }

            // add stripe_payment_method column to tds_subscriptions table
			$add_stripe_payment_method_column = true;
			foreach ( $wpdb->get_col( "DESC tds_subscriptions", 0 ) as $column ) {
				if ( $column === 'stripe_payment_method' ) {
                    $add_stripe_payment_method_column = false;
				}
			}

			if( $add_stripe_payment_method_column ) {
				$wpdb->query( "ALTER TABLE `tds_subscriptions` ADD `stripe_payment_method` TEXT AFTER `stripe_payment_intent`" );
				if ( '' !== $wpdb->last_error ) {
					throw new Exception( $wpdb->last_error );
				} else {
                    $result['tds_subscriptions']['stripe_payment_method'] = 'added';
                }
			} else {
                $result['tds_payment_stripe']['stripe_payment_method'] = 'found';
            }

            $wpdb->query( "ALTER TABLE `tds_plans` convert to character set utf8mb4 collate utf8mb4_unicode_520_ci" );
            if ( '' !== $wpdb->last_error ) {
                throw new Exception( $wpdb->last_error );
            } else {
                $result['tds_plans']['convert_to_character_set_utf8mb4'] = 'updated';
            }

            // add columns to tds_plans table
            $tds_plans_columns_to_add = array( 'interval', 'interval_count', 'is_unlimited', 'automatic_delistings' );
            foreach ( $tds_plans_columns_to_add as $column_name ) {

                $add = true;
                foreach ( $wpdb->get_col( "DESC tds_plans", 0 ) as $column ) {
                    if ( $column === $column_name ) {
                        $add = false;
                    }
                }

                if ( $add ) {

                    if ( $column_name === 'interval_count' ) {
                        $wpdb->query("ALTER TABLE `tds_plans` ADD COLUMN $column_name INT NOT NULL DEFAULT 1 AFTER `price`");
                    }

                    if ( $column_name === 'interval' ) {
                        $wpdb->query("ALTER TABLE `tds_plans` ADD COLUMN `$column_name` VARCHAR(50) NOT NULL DEFAULT '' AFTER `price`");
                    }

                    if ( $column_name === 'is_unlimited' ) {
                        $wpdb->query("ALTER TABLE `tds_plans` ADD COLUMN `$column_name` TINYINT(1) DEFAULT 0 AFTER `is_free`");
                    }

                    if( $column_name === 'automatic_delistings' ) {
                        $wpdb->query("ALTER TABLE `tds_plans` ADD COLUMN `$column_name` LONGTEXT AFTER `publishing_limits`");
                    }

                    if ( '' !== $wpdb->last_error ) {
                        throw new Exception( $wpdb->last_error );
                    } else {
                        $result['tds_plans'][$column_name] = 'added';
                    }

                } else {
                    $result['tds_plans'][$column_name] = 'found';
                }

            }

            // flag to update the interval & interval_count columns based on months_in_cycle column in tds_plans table
            $tds_plans_columns_update = false;

            // check for interval & interval_count columns in the tds_plans table
            $tds_plans_columns = $wpdb->get_col( "DESC tds_plans" );
            $check_columns = array( 'interval', 'interval_count' );
            if ( count( array_intersect( $check_columns, $tds_plans_columns ) ) == count($check_columns) ) {
                $tds_plans_columns_update = true;
            }

            $result['tds_plans']['tds_plans_columns_update'] = $tds_plans_columns_update ? 'yes' : 'no';

            // update the interval, interval_count columns based on months_in_cycle column
            if ( $tds_plans_columns_update ) {

                $wpdb->query("
                    UPDATE `tds_plans`
                    SET 
                        `interval_count` = CASE
                            WHEN months_in_cycle = '1' THEN 1
                            WHEN months_in_cycle = '12' THEN 1
                            ELSE `interval_count`
                        END,
                        `interval` = CASE
                            WHEN months_in_cycle = '1' THEN 'month'
                            WHEN months_in_cycle = '12' THEN 'year'
                            ELSE `interval`
                        END
                    WHERE months_in_cycle IS NOT NULL AND months_in_cycle <> ''
                ");

                if ( '' !== $wpdb->last_error ) {
                    throw new Exception( 'tds_plans_columns_update_result error: ' . $wpdb->last_error );
                } else {

                    $result['tds_plans']['tds_plans_columns_update_result'] = 'updated';

                    // drop deprecated months_in_cycle column
                    //$wpdb->query("
                    //    ALTER TABLE `tds_plans`
                    //    DROP COLUMN months_in_cycle
                    //");

                }

            }

            // add cycle_interval && cycle_interval_count columns in the tds_subscriptions table
            $tds_subscriptions_columns_to_add = array( 'cycle_interval_count', 'cycle_interval', 'is_unlimited' );
            foreach ( $tds_subscriptions_columns_to_add as $column_name ) {

                $add = true;
                foreach ( $wpdb->get_col( "DESC tds_subscriptions", 0 ) as $column ) {
                    if ( $column === $column_name ) {
                        $add = false;
                    }
                }

                if ( $add ) {

                    if ( $column_name === 'cycle_interval_count' ) {
                        $wpdb->query("ALTER TABLE tds_subscriptions ADD COLUMN $column_name INT NOT NULL DEFAULT 1 AFTER is_free");
                    }

                    if ( $column_name === 'cycle_interval' ) {
                        $wpdb->query("ALTER TABLE tds_subscriptions ADD COLUMN $column_name VARCHAR(50) NOT NULL DEFAULT '' AFTER is_free");
                    }

                    if ( $column_name === 'is_unlimited' ) {
                        $wpdb->query("ALTER TABLE tds_subscriptions ADD COLUMN `$column_name` TINYINT(1) DEFAULT 0 AFTER `is_free`");
                    }

                    if ( '' !== $wpdb->last_error ) {
                        throw new Exception( $wpdb->last_error );
                    } else {
                        $result['tds_subscriptions'][$column_name] = 'added';
                    }

                } else {
                    $result['tds_subscriptions'][$column_name] = 'found';
                }

            }

            // flag to update the cycle_interval & cycle_interval_count columns based on last_months_in_cycle column in tds_subscriptions table
            $tds_subscriptions_columns_update = false;

            // check for cycle_interval, cycle_interval_count columns in the tds_subscriptions table
            $tds_subscriptions_columns = $wpdb->get_col( "DESC tds_subscriptions" );
            $tds_subs_check_columns = array( 'cycle_interval', 'cycle_interval_count' );
            if ( count( array_intersect( $tds_subs_check_columns, $tds_subscriptions_columns ) ) == count($tds_subs_check_columns) ) {
                $tds_subscriptions_columns_update = true;
            }

            $result['tds_subscriptions']['tds_subscriptions_columns_update'] = $tds_subscriptions_columns_update ? 'yes' : 'no';

            // update the cycle_interval, cycle_interval_count columns based on 'last_months_in_cycle'
            if ( $tds_subscriptions_columns_update ) {

                $wpdb->query("
                    UPDATE `tds_subscriptions`
                    SET 
                        cycle_interval_count = CASE
                            WHEN last_months_in_cycle = '1' THEN 1
                            WHEN last_months_in_cycle = '12' THEN 1
                            ELSE cycle_interval_count
                        END,
                        cycle_interval = CASE
                            WHEN last_months_in_cycle = '1' THEN 'month'
                            WHEN last_months_in_cycle = '12' THEN 'year'
                            ELSE cycle_interval
                        END
                    WHERE last_months_in_cycle IS NOT NULL AND last_months_in_cycle <> ''
                ");

                if ( '' !== $wpdb->last_error ) {
                    throw new Exception( 'tds_subscriptions_columns_update_result error: ' . $wpdb->last_error );
                } else {

                    $result['tds_subscriptions']['tds_subscriptions_columns_update_result'] = 'updated';

                    // drop deprecated last_months_in_cycle & start_day columns
                    //$wpdb->query("
                    //    ALTER TABLE `tds_subscriptions`
                    //    DROP COLUMN last_months_in_cycle,
                    //    DROP COLUMN start_day
                    //");

                }

            }

            /**
             * stripe webhook update/add events
             */

            // the tds stripe webhook rest endpoint to look for and add/update
            $stripe_webhook_endpoint = rest_url("tds_stripe/webhook/" );

            // get stripe api keys
            $tds_payment_stripe_results = $wpdb->get_results("SELECT * FROM tds_payment_stripe LIMIT 1", ARRAY_A );
            if ( $tds_payment_stripe_results ) {

                // the stripe payment id
                $tds_stripe_payment_id = $tds_payment_stripe_results[0]['id'];

                $is_testing = '';
                if ( !empty( $tds_payment_stripe_results[0]['is_sandbox'] ) ) {
                    $is_testing = 'sandbox_';
                }

                // the secret api key
                $api_key = $tds_payment_stripe_results[0][$is_testing . 'secret_key'];

                if ( !empty($api_key) ) {

                    // load stripe api
                    require_once TDS_PATH . '/includes/vendor/stripe/init.php';

                    // set client api key
                    $stripe_client = new \Stripe\StripeClient($api_key);

                    try {
                        // try to get the balance
                        $stripe_client->balance->retrieve();
                        // if no error > valid
                        $valid_secret_key = true;
                    } catch ( Exception $ex ) {
                        // if error > not valid
                        $valid_secret_key = false;
                    }

                    // if we have a valid key try to add the tds stripe webhook endpoint
                    if ( $valid_secret_key ) {

                        // get/check endpoints
                        try {

                            // get all endpoints
                            $all_endpoints = $stripe_client->webhookEndpoints->all();

                            $endpoints = $all_endpoints->data;
                            if ( !empty($endpoints) ) {

                                $result['tds_payment_stripe']['stripe_client_webhookEndpoints_all'] = $endpoints;

                                foreach ( $endpoints as $endpoint ) {
                                    if ( $endpoint->url === $stripe_webhook_endpoint ) {
                                        $result['tds_payment_stripe']['tds_stripe_webhook_endpoint'] = 'found';
                                        $tds_stripe_webhook_endpoint = $endpoint;
                                        break;
                                    }
                                }

                            }

                            // if no endpoints or the tds stripe webhook endpoint was not found
                            if ( empty($endpoints) || empty($tds_stripe_webhook_endpoint) ) {

                                // create the tds rest webhook endpoint
                                $tds_stripe_webhook_endpoint = $stripe_client->webhookEndpoints->create([
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

                            } else {

                                // update the tds rest webhook endpoint
                                $tds_stripe_webhook_endpoint = $stripe_client->webhookEndpoints->update(
                                    $tds_stripe_webhook_endpoint->id,
                                    [
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
                                        //'disabled' => false
                                    ]
                                );

                            }

                            $result['tds_payment_stripe']['tds_stripe_webhook_endpoint'] = $tds_stripe_webhook_endpoint;

                        } catch ( Exception $ex ) {
                            $result['tds_payment_stripe']['stripe_client_webhookEndpoints_exception'] = $ex->getMessage();
                        }

                        // add/update it into db
                        if ( !empty($tds_stripe_webhook_endpoint) ) {

                            $data_array = [
                                'webhook_endpoint' => $tds_stripe_webhook_endpoint->url
                            ];
                            $data_array_format = ['%s'];

                            if ( !empty( $tds_stripe_webhook_endpoint->secret ) ) {
                                $data_array['webhook_endpoint_secret'] = $tds_stripe_webhook_endpoint->secret;
                                $data_array_format[] = '%s';
                            }

                            $result['tds_payment_stripe']['tds_stripe_webhook_endpoint_data_array'] = $data_array;

                            $wpdb->update( 'tds_payment_stripe',
                                $data_array,
                                array( 'id' => $tds_stripe_payment_id ),
                                $data_array_format,
                                array( '%d' )
                            );

                            if ( '' !== $wpdb->last_error ) {
                                throw new Exception( 'tds_stripe_webhook_endpoint_data_update error: ' . $wpdb->last_error );
                            }

                        }

                    } else {
                        $result['tds_payment_stripe']['api_secret_key'] = 'not valid';
                    }

                } else {
                    $result['tds_payment_stripe']['api_key'] = 'N/A';
                }

            } else {
                $result['tds_payment_stripe']['stripe_payments_data'] = 'N/A';
            }



            tds_util::set_tds_option('version', '1.5' );

            // log tds_update to_1p5 results
            td_log::log(__FILE__, __FUNCTION__, 'tds_update to_1p5 results', $result );

            // td log was turned off, set it back to off
            if ( $td_log_status === 'off' ) {
                td_util::update_option('td_log_status', $td_log_status );
            }

            return $result;

		} catch ( Exception $ex ) {

            // log results
            td_log::log( __FILE__, __FUNCTION__, 'tds_update::_to_1p5 $ex: ' . $ex->getMessage(), [ 'last_query' => $wpdb->last_query ] );

            // td log was turned off, set it back to off
            if ( $td_log_status === 'off' ) {
                td_util::update_option('td_log_status', $td_log_status );
            }

            return [
                'exception' => $ex->getMessage(),
                'result' => $result
            ];

		}

	}

}