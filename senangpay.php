<?php

/*
 * @package senangPay for Hikashop Payment Plugin
 * @version 1.0.0
 * @author senangPay
 */

//Prevent from direct access
defined('_JEXEC') or die('Restricted access');

// You need to extend from the hikashopPaymentPlugin class which already define lots of functions in order to simplify your work
class plgHikashoppaymentSenangpay extends hikashopPaymentPlugin
{

    var $url;
    public static $url_production = 'https://app.senangpay.my/payment/';
    public static $url_staging = 'https://sandbox.senangpay.my/payment/';
    //List of the plugin's accepted currencies. The plugin won't appear on the checkout if the current currency is not in that list. You can remove that attribute if you want your payment plugin to display for all the currencies
    var $accepted_currencies = array("MYR", "RM");
    // Multiple plugin configurations. It should usually be set to true
    var $multiple = true;
    //Payment plugin name (the name of the PHP file)
    var $name = 'senangpay';
    // This array contains the specific configuration needed (Back end > payment plugin edition), depending of the plugin requirements.
    // They will vary based on your needs for the integration with your payment gateway.
    // The first parameter is the name of the field. In upper case for a translation key.
    // The available types (second parameter) are: input (an input field), html (when you want to display some custom HTML to the shop owner), textarea (when you want the shop owner to write a bit more than in an input field), big-textarea (when you want the shop owner to write a lot more than in an input field), boolean (for a yes/no choice), checkbox (for checkbox selection), list (for dropdown selection) , orderstatus (to be able to select between the available order statuses)
    // The third parameter is the default value.
    var $pluginConfig = array(
        // User's API Secret Key
        'secretkey' => array("Secret Key", 'input'),
        // User's Collection ID
        'merchantid' => array("Merchant ID", 'input'),
        //senangPay Mode: Production or Staging
        'mode' => array('Mode', 'list', array(
                'Production' => 'Production',
                'Staging' => 'Sandbox'
            )),
        // Write some things on the debug file
        'debug' => array('DEBUG', 'boolean', '0'),
        // The URL where the user is redirected after a fail during the payment process
        //'cancel_url' => array('CANCEL_URL_DEFINE','html',''),
        // The URL where the user is redirected after the payment is done on the payment gateway. It's a pre determined URL that has to be given to the payment gateway
        //'return_url_gateway' => array('RETURN_URL_DEFINE', 'html',''),
        // The URL where the user is redirected by HikaShop after the payment is done ; "Thank you for purchase" page
        //'return_url' => array('RETURN_URL', 'input'),
        // The URL where the payment platform the user about the payment (fail or success)
        //'notify_url' => array('NOTIFY_URL_DEFINE','html',''),
        // Invalid status for order in case of problem during the payment process
        'invalid_status' => array('INVALID_STATUS', 'orderstatus'),
        // Valid status for order if the payment has been done well
        'verified_status' => array('VERIFIED_STATUS', 'orderstatus')
    );

    /**
     * The constructor is optional if you don't need to initialize some parameters of some fields of the configuration and not that it can also be done in the getPaymentDefaultValues function as you will see later on
     */
    function __construct(&$subject, $config)
    {
        $this->pluginConfig['notification'][0] = JText::sprintf('ALLOW_NOTIFICATIONS_FROM_X', 'senangpay');

        // This is the cancel URL of HikaShop that should be given to the payment gateway so that it can redirect to it when the user cancel the payment on the payment gateway page. That URL will automatically cancel the order of the user and redirect him to the checkout so that he can choose another payment method
        //$this->pluginConfig['cancel_url'][2] = HIKASHOP_LIVE . "index.php?option=com_hikashop&ctrl=order&task=cancel_order";
        // This is the "thank you" or "return" URL of HikaShop that should be given to the payment gateway so that it can redirect to it when the payment of the user is valid. That URL will reinit some variables in the session like the cart and will then automatically redirect to the "return_url" parameter
        //$this->pluginConfig['return_url'][2] = HIKASHOP_LIVE . "index.php?option=com_hikashop&ctrl=checkout&task=after_end";
        // This is the "notification" URL of HikaShop that should be given to the payment gateway so that it can send a request to that URL in order to tell HikaShop that the payment has been done (sometimes the payment gateway doesn't do that and passes the information to the return URL, in which case you need to use that notification URL as return URL and redirect the user to the HikaShop return URL at the end of the onPaymentNotification function)


        return parent::__construct($subject, $config);
    }

    /**
     * This function is called at the end of the checkout. That's the function which should display your payment gateway redirection form with the data from HikaShop
     */
    function onAfterOrderConfirm(&$order, &$methods, $method_id)
    {
        // This is a mandatory line in order to initialize the attributes of the payment method
        parent::onAfterOrderConfirm($order, $methods, $method_id);

        // Here we can do some checks on the options of the payment method and make sure that every required parameter is set and otherwise display an error message to the user
        // The plugin can only work if those parameters are configured on the website's backend
        if (empty($this->payment_params->secretkey))
        {
            // Enqueued messages will appear to the user, as Joomla's error messages
            $this->app->enqueueMessage('You have to configure an Secret Key for the senangPay plugin payment first : check your plugin\'s parameters, on your website backend', 'error');
            return false;
        }
        elseif (empty($this->payment_params->merchantid))
        {
            $this->app->enqueueMessage('You have to configure a Merchant ID for the senangPay plugin payment first : check your plugin\'s parameters, on your website backend', 'error');
            return false;
        }
        else
        {
            // Here, all the required parameters are valid, so we can proceed to the payment platform
            // The order's amount, here in cents and rounded with 2 decimals because of the payment platform's requirements
            // There is a lot of information in the $order variable, such as price with/without taxes, customer info, products... you can do a var_dump here if you need to display all the available information

            $address = $this->app->getUserState(HIKASHOP_COMPONENT . '.billing_address');

            if (!empty($address))
            {
                $amount = round($order->cart->full_total->prices[0]->price_value_with_tax, 2);
                //   $notify_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment=senangpay&tmpl=component&lang=' . $this->locale . $this->url_itemid . '&orderid=' . $order->order_id;
                //  $return_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=after_end&orderid=' . $order->order_id;
                $order_detail = "Order No " . $order->order_number;

                $vars = array(
                    'name' => @$order->cart->billing_address->address_firstname . " " . @$order->cart->billing_address->address_lastname,
                    'email' => $this->user->user_email,
                    'phone' => @$order->cart->billing_address->address_telephone,
                    'detail' => $order_detail,
                    'hash' => $this->getHash($this->payment_params->secretkey, $order_detail, $amount, $order->order_id),
                    'order_id' => $order->order_id,
                    'amount' => $amount,
                    'mode' => $this->payment_params->mode,
                    'url' => $this->getURL($this->payment_params->mode, $this->payment_params->merchantid)
                );
            }

            $this->vars = $vars;

            // Ending the checkout, ready to be redirect to the plateform payment final form
            // The showPage function will call the example_end.php file which will display the redirection form containing all the parameters for the payment platform
            return $this->showPage('end');
        }
    }

    /**
     * To set the specific configuration (back end) default values (see $pluginConfig array)
     */
    function getPaymentDefaultValues(&$element)
    {
        $element->payment_name = 'senangPay';
        $element->payment_description = 'Pay using <strong>Pay using Online Banking FPX and Credit/Debit Card</strong>';
        $element->payment_images = '';
        $element->payment_params->mode = "Production";
        $element->payment_params->currency = $this->accepted_currencies[0];
        $element->payment_params->invalid_status = 'cancelled';
        $element->payment_params->verified_status = 'confirmed';
    }

    /**
     * After submiting the platform payment form, this is where the website will receive the response information from the payment gateway servers and then validate or not the order
     */
    function onPaymentNotification(&$statuses)
    {
        // We first create a filtered array from the parameters received
        $vars = array();

        $filter = JFilterInput::getInstance();

        // A loop to create an array $var with all the parameters sent by the payment gateway with a POST method, and loaded in the $_REQUEST
        foreach ($_REQUEST as $key => $value)
        {
            $key = $filter->clean($key);
            $value = JRequest::getString($key);
            $vars[$key] = $value;
        }

        $order_id = $vars['order_id'];
        $dbOrder = $this->getOrder($order_id);

        $this->loadPaymentParams($dbOrder);
        $this->loadOrderData($dbOrder);
        #check hash
        if (isset($vars['hash']))
        {
            # verify that the data was not tempered, verify the hash
            $hashed_string = md5($this->payment_params->secretkey . urldecode($vars['status_id']) . urldecode($vars['order_id']) . urldecode($vars['transaction_id']) . urldecode($vars['msg']));

            # if hash is not same, data may be tempered
            if ($hashed_string != urldecode($vars['hash']))
                return false;

            // Here we are configuring the "succes URL" and the "fail URL". After checking all the parameters sent by the payment gateway, we will redirect the customer to one or another of those URL (not necessary for our example platform).
            $return_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id=' . $order_id . $this->url_itemid;
            $cancel_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id=' . $order_id . $this->url_itemid;

            if ($vars['status_id'] == 1)
            {
                // Save to DB only 1 times. 
                $this->modifyOrder($order_id, $this->payment_params->verified_status, true, true);
                if (isset($_GET['status_id']))
                {
                    $this->app->redirect($return_url);
                }
                else
                {
                    echo 'OK';
                    exit();
                }
            }
            else
            {
                $this->modifyOrder($order_id, $this->payment_params->invalid_status, true, true);
                if (isset($_GET['status_id']))
                {
                    $this->app->redirect($cancel_url);
                }
                else
                {
                    echo 'OK';
                    exit();
                }
            }
        }
        else
        {
            return false;
        }
    }

    function onPaymentConfigurationSave(&$element)
    {
        if (empty($element->payment_params->currency))
            $element->payment_params->currency = $this->accepted_currencies[0];
        return true;
    }

    public function getHash($secretkey, $detail, $amount, $orderid)
    {
        $hashed_string = md5($secretkey . urldecode($detail) . urldecode($amount) . urldecode($orderid));

        return $hashed_string;
    }

    public function getURL($mode, $merchantid)
    {
        if ($mode == 'Staging')
        {
            $this->url = self::$url_staging . $merchantid;
        }
        else
        {
            $this->url = self::$url_production . $merchantid;
        }

        return $this->url;
    }

}
