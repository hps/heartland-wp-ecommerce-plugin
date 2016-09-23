<?php
/*
Plugin Name: Heartland Secure Submit Gateway for WP eCommerce
Plugin URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
Description: Heartland Payment Systems gateway for WP eCommerce.
Version: 1.0.0
Author: Secure Submit
Author URI: https://developer.heartlandpaymentsystems.com/SecureSubmit/
*/

// WP eCommerce required
// Manually require WP eCommerce if we're loaded before it
$dir = "";
$arr = get_option('active_plugins');
foreach ($arr as $v) {
    if (substr($v, -21) == "/wp-shopping-cart.php") {
        $dir = substr($v, 0, -21);
    }
}
$wpscMerchantLocation = sprintf(
    '%s/%s/wpsc-includes/merchant.class.php',
    WP_PLUGIN_DIR,
    $dir
);
if (!$dir || !file_exists($wpscMerchantLocation)) {
    return;
}
if (!class_exists('wpsc_merchant')) {
    require_once($wpscMerchantLocation);
}

if (!class_exists('HpsServicesConfig')) {
    require_once('library/Hps.php');
}

$nzshpcrt_gateways['wpe_securesubmit'] = array(
    'name' => __('Secure Submit', 'wpsc'),
    'api_version' => 2.0,
    'image' => plugins_url('images/cc.gif', __FILE__),
    'internalname' => 'wpe_securesubmit',
    'class_name' => 'wpe_securesubmit',
    'form' => "form_securesubmit",
    'submit_function' => "submit_securesubmit",
    'is_exclusive' => true,
    'payment_type' => "wpe_securesubmit",
    'display_name' => __('Secure Submit', 'wpsc'),
);

$error = '';

/**
 * WP eCommerce Secure Submit Class
 *
 * This is the Secure Submit class, it extends the base merchant class
 *
 * @package wpe_securesubmit
 * @since 3.7.6
 * @subpackage wpsc-merchants
 * @author Heartland Payment Systems
 */

class wpe_securesubmit extends wpsc_merchant {

    var $name = '';

    function __construct($purchase_id = null, $is_receiving = false) {
        $this->name = __('SecureSubmit', 'wpsc');
        parent::__construct($purchase_id, $is_receiving);
    }

    function construct_value_array() {
    }

    function submit() {
        $processing_mode = get_option("PROCESSING_MODE");
        $config = new HpsServicesConfig();

        $config->secretApiKey = get_option("HPS_SECRET_KEY_LIVE") ? get_option("HPS_SECRET_KEY_LIVE") : get_option("HPS_SECRET_KEY_TEST");
        $config->versionNumber = '1645';
        $config->developerId = '002914';

        $chargeService = new HpsCreditService($config);

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

        try {
            if ($processing_mode == 'capture') {
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

            $this->set_authcode($response->authorizationCode);
            $this->set_transaction_details($response->transactionId, 3);
            $this->go_to_transaction_results($this->cart_data['session_id']);
        } catch (Exception $e) {
            $this->set_error_message(__('There was an error posting your payment.', 'wpsc'));
            $this->return_to_checkout();
            exit();
            break;
        }
    }
}

function submit_securesubmit() {

    if (isset($_POST['SecureSubmit']['SECURESUBMIT_MODE']))
        update_option('SECURESUBMIT_MODE', $_POST['SecureSubmit']['SECURESUBMIT_MODE']);

    if (isset($_POST['SecureSubmit']['PROCESSING_MODE']))
        update_option('PROCESSING_MODE', $_POST['SecureSubmit']['PROCESSING_MODE']);

    if (isset($_POST['SecureSubmit']['HPS_PUBLIC_KEY_LIVE']))
        update_option('HPS_PUBLIC_KEY_LIVE', $_POST['SecureSubmit']['HPS_PUBLIC_KEY_LIVE']);

    if (isset($_POST['SecureSubmit']['HPS_SECRET_KEY_LIVE']))
        update_option('HPS_SECRET_KEY_LIVE', $_POST['SecureSubmit']['HPS_SECRET_KEY_LIVE']);

    if (isset($_POST['SecureSubmit']['HPS_PUBLIC_KEY_TEST']))
        update_option('HPS_PUBLIC_KEY_TEST', $_POST['SecureSubmit']['HPS_PUBLIC_KEY_TEST']);

    if (isset($_POST['SecureSubmit']['HPS_SECRET_KEY_TEST']))
        update_option('HPS_SECRET_KEY_TEST', $_POST['SecureSubmit']['HPS_SECRET_KEY_TEST']);

    return true;
}

function form_securesubmit() {
    global $wpsc_gateways, $wpdb;
    $test_mode_selected = '';
    $live_mode_selected = '';
    $capture_mode_selected = '';
    $authorize_mode_selected = '';

    if (get_option('SECURESUBMIT_MODE') == "live")
        $live_mode_selected = 'checked="checked"';
    else
        $test_mode_selected = 'checked="checked"';

    if (get_option('PROCESSING_MODE') == "capture")
        $capture_mode_selected = 'checked="checked"';
    else
        $authorize_mode_selected = 'checked="checked"';

    $output = '
      <tr>
        <td>
          <label>' . __('API mode:', 'wpsc') . '</label>
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
          <label>' . __('Processing Type:', 'wpsc') . '</label>
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
          <label for="HPS_PUBLIC_KEY_TEST">' . __('Test - Public Key:', 'wpsc') . '</label>
        </td>
        <td>
          <input type="text" name="SecureSubmit[HPS_PUBLIC_KEY_TEST]" id="HPS_PUBLIC_KEY_TEST" value="' . get_option("HPS_PUBLIC_KEY_TEST") . '" size="30" size="30" style="width:500px;"/>
        </td>
      </tr>
      <tr>
        <td>
          <label for="HPS_SECRET_KEY_TEST">' . __('Test - Secret Key:', 'wpsc') . '</label>
        </td>
        <td>
          <input type="password" name="SecureSubmit[HPS_SECRET_KEY_TEST]" id="HPS_SECRET_KEY_TEST" value="' . get_option("HPS_SECRET_KEY_TEST") . '" size="30" size="30" style="width:500px;"/>
        </td>
      </tr>
      <tr>
        <td>
          <label for="HPS_PUBLIC_KEY_LIVE">' . __('Live - Public Key:', 'wpsc') . '</label>
        </td>
        <td>
          <input type="text" name="SecureSubmit[HPS_PUBLIC_KEY_LIVE]" id="HPS_PUBLIC_KEY_LIVE" value="' . get_option("HPS_PUBLIC_KEY_LIVE") . '" size="30" style="width:500px;"/>
        </td>
      </tr>
      <tr>
        <td>
          <label for="HPS_SECRET_KEY_LIVE">' . __('Live - Secret Key:', 'wpsc') . '</label>
        </td>
        <td>
          <input type="password" name="SecureSubmit[HPS_SECRET_KEY_LIVE]" id="HPS_SECRET_KEY_LIVE" value="' . get_option("HPS_SECRET_KEY_LIVE") . '" size="30" size="30" style="width:500px;"/>
        </td>
      </tr>';

    return $output;
}

function load_js_files()
{
    wp_enqueue_script('jquery');
    wp_enqueue_script('ssplugin', 'https://api.heartlandportico.com/SecureSubmit.v1/token/2.1/securesubmit.js');
    wp_enqueue_style('ssstyles', plugins_url('css/securesubmit.css', __FILE__));
}

add_action('wp_enqueue_scripts', 'load_js_files');

$years = '';
$months = '';

if (in_array('wpe_securesubmit', (array)get_option('custom_gateway_options'))) {
    $public_key = '';
    if (get_option('SECURESUBMIT_MODE') == "live")
        $public_key = get_option("HPS_PUBLIC_KEY_LIVE");
    else
        $public_key = get_option("HPS_PUBLIC_KEY_TEST");

    $curryear = date('Y');

    for ($i = 0; $i < 10; $i++) {
        $years .= "<option value='" . $curryear . "'>" . $curryear . "</option>\r\n";
        $curryear++;
    }

    $output = "<script>
        jQuery(function ($) {
          function secureSubmitResponseHandler(response) {
            $('.wpsc_make_purchase').hide();
                  var paymentForm = $('.wpsc_checkout_forms');
                  $('.error').hide();

                    if (response.message) {
                        alert(response.message);
                        return false;
                  } else {
                      $('#securesubmitToken').val(response.token_value);
                      paymentForm.get(0).submit();
                  }
              }

              $(document).ready(function() {
                var paymentForm = $('.wpsc_checkout_forms');
                Heartland.Card.attachNumberEvents('#securesubmit-card-number');
                Heartland.Card.attachExpirationEvents('#securesubmit-card-expiry');
                Heartland.Card.attachCvvEvents('#securesubmit-card-cvc');

                  paymentForm.on('submit', function() {
                        var card       = document.getElementById('securesubmit-card-number');
                        var expiration = document.getElementById('securesubmit-card-expiry');
                        var cvv        = document.getElementById('securesubmit-card-cvc');

                        // Parse the expiration date
                        var split = expiration.value.split(' / ');
                        var month = split[0] ? split[0].replace(/^\s+|\s+$/g, '') : '';
                        var year  = split[1] ? split[1].replace(/^\s+|\s+$/g, '') : '';

                        (new Heartland.HPS({
                            publicKey:    '".$public_key."',
                            cardNumber:   card.value.replace(/\D/g, ''),
                            cardCvv:      cvv.value.replace(/\D/g, ''),
                            cardExpMonth: month.replace(/\D/g, ''),
                            cardExpYear:  year.replace(/\D/g, ''),
                            success: secureSubmitResponseHandler,
                            error: function (resp) {
                                $('.wpsc_make_purchase').show();
                                alert(resp.message);
                            }
                        })).tokenize();

                      return false;
                  });
              });
        });
      </script>


    <tr>
        <td>
            <div class='ss-head'></div>

            <div class='cc-number'>
            <div class='cc-input-label'>" . __( 'Credit Card Number *', 'wpsc' ) . "</div>
            <input placeholder='Credit Card Number' type='tel' id='securesubmit-card-number' class='text'/>
            <div id='card_number' class='error' style='color:#ff0000;display:none;'>" . __( 'Please enter a valid card number', 'wpsc' ) . "</div>
            </div>

            <div class='exp-date'>
            <div class='exp-date-input-label'>" . __( 'Credit Card Expiry *', 'wpsc' ) . "</div>
            <input id='securesubmit-card-expiry' placeholder='MM / YY' type='tel' />
            <div id='card_expMonth' class='error' style='color:#ff0000;display:none;'>" . __( 'Expiration month is invalid', 'wpsc' ) . "</div>
            <div id='card_expYear' class='error' style='color:#ff0000;display:none;'>" . __( 'Expiration year is invalid', 'wpsc' ) . "</div>
            </div>

            <div class='cvc'>
            <div class='cvc-input-label'>" . __( 'Security Code', 'wpsc' ) . "</div>
            <input type='tel' size='4' maxlength='4' id='securesubmit-card-cvc' placeholder='CVV'/>
            <input type='hidden' id='securesubmitToken' name='securesubmitToken' value='' />
            </div>
        </td>
    </tr>

    ";
    $gateway_checkout_form_fields[$nzshpcrt_gateways['wpe_securesubmit']['internalname']] = $output;
}
