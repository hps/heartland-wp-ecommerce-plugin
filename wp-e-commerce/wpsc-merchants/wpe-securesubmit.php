<?php
if ( ! class_exists('HpsConfiguration' ) ) {
    require_once(WPSC_FILE_PATH.'/wpsc-merchants/library/securesubmit/Hps.php');
}

$nzshpcrt_gateways[$num]['name'] = __( 'SecureSubmit', 'wpsc' );
$nzshpcrt_gateways[$num]['api_version'] = 2.0;
$nzshpcrt_gateways[$num]['image'] = WPSC_URL . '/images/cc.gif';
$nzshpcrt_gateways[$num]['internalname'] = 'wpe_securesubmit';
$nzshpcrt_gateways[$num]['class_name'] = 'wpe_securesubmit';
$nzshpcrt_gateways[$num]['form'] = "form_securesubmit";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_securesubmit";
$nzshpcrt_gateways[$num]['is_exclusive'] = true;
$nzshpcrt_gateways[$num]['payment_type'] = "wpe_securesubmit";
$nzshpcrt_gateways[$num]['display_name'] = __( 'SecureSubmit', 'wpsc' );

$error = '';

/**
 * WP eCommerce SecureSubmit Class
 *
 * This is the SecureSubmit class, it extends the base merchant class
 *
 * @package wpe_securesubmit
 * @since 3.7.6
 * @subpackage wpsc-merchants
 * @author Heartland Payment Systems
 */

class wpe_securesubmit extends wpsc_merchant {

	var $name = '';

	function __construct( $purchase_id = null, $is_receiving = false ) {
		$this->name = __( 'SecureSubmit', 'wpsc' );
		parent::__construct( $purchase_id, $is_receiving );
	}

	function construct_value_array() {
	}

	function submit() {
        $processing_mode = get_option("PROCESSING_MODE");
        $config = new HpsConfiguration();

        $config->secretApiKey = get_option("HPS_SECRET_KEY_LIVE") ? get_option("HPS_SECRET_KEY_LIVE") : get_option("HPS_SECRET_KEY_TEST");
        $config->versionNumber = '1645';
        $config->developerId = '002914';
        
        $chargeService = new HpsChargeService($config);

        $hpsaddress = new HpsAddress();
        $hpsaddress->address = $this->cart_data['billing_address']['address'];
        $hpsaddress->city = $this->cart_data['billing_address']['city'];
        $hpsaddress->state = $this->cart_data['billing_address']['state'];
        $hpsaddress->zip = preg_replace('/[^0-9]/', '', $this->cart_data['billing_address']['post_code']);
        $hpsaddress->country = $this->cart_data['billing_address']['country'];

        $cardHolder = new HpsCardHolder();
        $cardHolder->firstName = $this->cart_data['billing_address']['first_name'];
        $cardHolder->lastName = $this->cart_data['billing_address']['last_name'];
        $cardHolder->emailAddress = $this->cart_data['email_address'];
        $cardHolder->address = $hpsaddress;

        $hpstoken = new HpsTokenData();
        $hpstoken->tokenValue = $_POST['securesubmitToken'];
        
        $details = new HpsTransactionDetails();
        $details->invoiceNumber = $this->cart_data['session_id'];
        $details->memo = 'WP eCommerce Order Id: ' . $this->cart_data['session_id'];

		try{
            if($processing_mode == 'capture') {
                $response = $chargeService->charge(
                    $this->cart_data['total_price'],
                    'usd',
                    $hpstoken,
                    $cardHolder,
                    false,
                    $details);
            } else {
                $response = $chargeService->authorize(
                    $this->cart_data['total_price'],
                    'usd',
                    $hpstoken,
                    $cardHolder,
                    false,
                    $details);
            }

		    error_log($response);

            $this->set_authcode($response->authCode);
            $this->set_transaction_details($response->transactionId, 3);
		    $this->go_to_transaction_results($this->cart_data['session_id']);
		} catch(Exception $e) {
			$this->set_error_message(__('There was an error posting your payment.', 'wpsc'));
		    $this->return_to_checkout();
			exit();
			break;
		}
	}
}

function submit_securesubmit() {
	
	if (isset($_POST['SecureSubmit']['SECURESUBMIT_MODE']))
		update_option( 'SECURESUBMIT_MODE', $_POST['SecureSubmit']['SECURESUBMIT_MODE'] );
        
	if (isset($_POST['SecureSubmit']['PROCESSING_MODE']))
		update_option( 'PROCESSING_MODE', $_POST['SecureSubmit']['PROCESSING_MODE'] );

	if (isset($_POST['SecureSubmit']['HPS_PUBLIC_KEY_LIVE']))
		update_option( 'HPS_PUBLIC_KEY_LIVE', $_POST['SecureSubmit']['HPS_PUBLIC_KEY_LIVE'] );

	if (isset($_POST['SecureSubmit']['HPS_SECRET_KEY_LIVE']))
		update_option( 'HPS_SECRET_KEY_LIVE', $_POST['SecureSubmit']['HPS_SECRET_KEY_LIVE'] );

	if (isset($_POST['SecureSubmit']['HPS_PUBLIC_KEY_TEST']))
		update_option( 'HPS_PUBLIC_KEY_TEST', $_POST['SecureSubmit']['HPS_PUBLIC_KEY_TEST'] );

	if (isset($_POST['SecureSubmit']['HPS_SECRET_KEY_TEST']))
		update_option( 'HPS_SECRET_KEY_TEST', $_POST['SecureSubmit']['HPS_SECRET_KEY_TEST'] );

	return true;
}

function form_securesubmit() {
	global $wpsc_gateways, $wpdb;
	$test_mode_selected = '';
	$live_mode_selected = '';
    $capture_mode_selected = '';
    $authorize_mode_selected = '';
    
	if (get_option('SECURESUBMIT_MODE') == "live" )
		$live_mode_selected = 'checked="checked"';
	else
		$test_mode_selected = 'checked="checked"';
        
	if (get_option('PROCESSING_MODE') == "capture" )
		$capture_mode_selected = 'checked="checked"';
	else
		$authorize_mode_selected = 'checked="checked"';

	$output = '
	<tr>
		<td>
			<label>' . __( 'API mode:', 'wpsc' ) . '</label>
		</td>
		<td>
			<label for="securesubmit_test">' . __('Test Mode:', 'wpsc') . '</label>
			<input type="radio" name="SecureSubmit[SECURESUBMIT_MODE]" id="securesubmit_test" value="test" ' . $test_mode_selected . '/>
            <br />
			<label for="securesubmit_live">' . __('Live Mode:', 'wpsc') . '</label>
			<input type="radio" name="SecureSubmit[SECURESUBMIT_MODE]" id="securesubmit_live" value="live" ' . $live_mode_selected . '/>
		</td>
	</tr>
	<tr>
		<td>
			<label>' . __( 'Processing Type:', 'wpsc' ) . '</label>
		</td>
		<td>
			<label for="securesubmit_capture">' . __('Authorize and Capture', 'wpsc') . '</label>
			<input type="radio" name="SecureSubmit[PROCESSING_MODE]" id="securesubmit_capture" value="capture" ' . $capture_mode_selected . '/>
            <br />
			<label for="securesubmit_authorize">' . __('Authorize', 'wpsc') . '</label>
			<input type="radio" name="SecureSubmit[PROCESSING_MODE]" id="securesubmit_authorize" value="authorize" ' . $authorize_mode_selected . '/>
		</td>
	</tr>
	<tr>
		<td>
		</td>
		<td>
			<p class="description">
				Test transactions are visible from your SecureSubmit dashboard.
			</p>
		</td>
	</tr>
	<tr>
		<td>
			<label for="HPS_SECRET_KEY_TEST">' . __( 'Test - Secret Key:', 'wpsc' ) . '</label>
		</td>
		<td>
			<input type="password" name="SecureSubmit[HPS_SECRET_KEY_TEST]" id="HPS_SECRET_KEY_TEST" value="' . get_option( "HPS_SECRET_KEY_TEST" ) . '" size="30" size="30" style="width:500px;"/>
		</td>
	</tr>
	<tr>
		<td>
			<label for="HPS_PUBLIC_KEY_TEST">' . __( 'Test - Public Key:', 'wpsc' ) . '</label>
		</td>
		<td>
			<input type="text" name="SecureSubmit[HPS_PUBLIC_KEY_TEST]" id="HPS_PUBLIC_KEY_TEST" value="' . get_option( "HPS_PUBLIC_KEY_TEST" ) . '" size="30" size="30" style="width:500px;"/>
		</td>
	</tr>
	<tr>
		<td>
			<label for="HPS_SECRET_KEY_LIVE">' . __( 'Live - Secret Key:', 'wpsc' ) . '</label>
		</td>
		<td>
			<input type="password" name="SecureSubmit[HPS_SECRET_KEY_LIVE]" id="HPS_SECRET_KEY_LIVE" value="' . get_option( "HPS_SECRET_KEY_LIVE" ) . '" size="30" size="30" style="width:500px;"/>
		</td>
	</tr>
	<tr>
		<td>
			<label for="HPS_PUBLIC_KEY_LIVE">' . __( 'Live - Public Key:', 'wpsc' ) . '</label>
		</td>
		<td>
			<input type="text" name="SecureSubmit[HPS_PUBLIC_KEY_LIVE]" id="HPS_PUBLIC_KEY_LIVE" value="' . get_option( "HPS_PUBLIC_KEY_LIVE" ) . '" size="30" style="width:500px;"/>
		</td>
	</tr>';

	return $output;
}

function load_js_files()
{
	wp_enqueue_script('jquery');
	wp_enqueue_script('ssplugin', WP_PLUGIN_URL.'/wp-e-commerce/wpsc-merchants/js/secure.submit-1.0.2.js');
}

add_action('wp_enqueue_scripts', 'load_js_files');

$years = '';
$months = '';

if (in_array('wpe_securesubmit', (array)get_option('custom_gateway_options'))) {
	$public_key = '';
	if ( get_option('SECURESUBMIT_MODE') == "live" )
		$public_key = get_option( "HPS_PUBLIC_KEY_LIVE" );
	else
		$public_key = get_option( "HPS_PUBLIC_KEY_TEST" );
        
	$curryear = date('Y');
    
	for ( $i = 0; $i < 10; $i++ ) {
		$years .= "<option value='" . $curryear . "'>" . $curryear . "</option>\r\n";
		$curryear++;
	}
    
	$output = "<script>
		jQuery(function ($) {
			function secureSubmitResponseHandler(response) {
	            var paymentForm = $('.wpsc_checkout_forms');
	            $('.error').hide();
                
                if ( response.message ) {
                    alert(response.message);
                    return false;
	            } else {
	                $('#securesubmitToken').val(response.token_value);
	                paymentForm.get(0).submit();
	            }
	        }
            
	        $(document).ready(function() {
	        	var paymentForm = $('.wpsc_checkout_forms');
	            paymentForm.on('submit', function() {
                    hps.tokenize({
                        data: {
                            public_key: '".$public_key."',
                            number: jQuery('#securesubmit-card-number').val().replace(/\D/g, ''),
                            cvc: jQuery('#securesubmit-card-cvc').val(),
                            exp_month: jQuery('#securesubmit-card-expiry-month').val(),
                            exp_year: jQuery('#securesubmit-card-expiry-year').val()
                        },
                        success: function(response) {
                            secureSubmitResponseHandler(response);
                        },
                        error: function(response) {
                            secureSubmitResponseHandler(response);
                        }
                    });

	                return false;
	            });
	        });
		});
	</script>
	<tr>
	  <td colspan=2>
	     <h4>Credit Card Details</h4>
	  </td>
    </tr>
	<tr>
		<td style='width:151px'>" . __( 'Credit Card Number *', 'wpsc' ) . "</td>
		<td>
			<input placeholder='Credit Card Number' type='text' id='securesubmit-card-number' class='text'/>
			<div id='card_number' class='error' style='color:#ff0000;display:none;'>" . __( 'Please enter a valid card number', 'wpsc' ) . "</div>
		</td>
	</tr>
	<tr>
		<td>" . __( 'Credit Card Expiry *', 'wpsc' ) . "</td>
		<td>
			<select id='securesubmit-card-expiry-month'>
			" . $months . "
			<option value='01'>01</option>
			<option value='02'>02</option>
			<option value='03'>03</option>
			<option value='04'>04</option>
			<option value='05'>05</option>
			<option value='06'>06</option>
			<option value='07'>07</option>
			<option value='08'>08</option>
			<option value='09'>09</option>
			<option value='10'>10</option>
			<option value='11'>11</option>
			<option value='12'>12</option>
			</select>
			<select class='wpsc_ccBox' id='securesubmit-card-expiry-year'>
			" . $years . "
			</select>
			<div id='card_expMonth' class='error' style='color:#ff0000;display:none;'>" . __( 'Expiration month is invalid', 'wpsc' ) . "</div>
			<div id='card_expYear' class='error' style='color:#ff0000;display:none;'>" . __( 'Expiration year is invalid', 'wpsc' ) . "</div>
		</td>
	</tr>
	<tr>
		<td>" . __( 'Security Code', 'wpsc' ) . "</td>
		<td>
			<input type='text' size='4' maxlength='4' id='securesubmit-card-cvc'/>
			<input type='hidden' id='securesubmitToken' name='securesubmitToken' value='' />
		</td>
	</tr>
";

$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] = $output;

}
?>
