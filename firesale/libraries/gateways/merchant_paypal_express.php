<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * CI-Merchant Library
 *
 * Copyright (c) 2011-2012 Adrian Macneil
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

require_gateway('paypal_base');

/**
 * Merchant Paypal Express Class
 *
 * Payment processing using Paypal Express Checkout
 * Documentation: https://cms.paypal.com/cms_content/US/en_US/files/developer/PP_ExpressCheckout_IntegrationGuide.pdf
 */

class Merchant_paypal_express extends Merchant_paypal_base
{
    public function default_settings()
    {
        return array(
            'username' => '',
            'password' => '',
            'signature' => '',
            'test_mode' => FALSE,
            'solution_type' => array('type' => 'select', 'default' => 'Sole', 'options' => array(
                'Sole' => 'merchant_solution_type_sole',
                'Mark' => 'merchant_solution_type_mark')),
            'landing_page' => array('type' => 'select', 'default' => 'Billing', 'options' => array(
                'Billing'   => 'merchant_landing_page_billing',
                'Login'     => 'merchant_landing_page_login')),
            'skip_checkout' => true
        );
    }

    public function authorize()
    {
        $request = $this->_build_authorize_or_purchase();
        $response = $this->_post_paypal_request($request);

        return new Merchant_paypal_response('redirect', $this->_checkout_url().'?'.http_build_query(array(
            'cmd' => '_express-checkout',
            'useraction' => 'commit',
            'token' => $response['TOKEN'],
        )));
    }

    public function authorize_return()
    {
        $response = $this->_express_checkout_return('Authorization');

        return new Merchant_paypal_api_response($response, Merchant_response::AUTHORIZED);
    }

    public function purchase()
    {
        // authorize first then process as 'Sale' in DoExpressCheckoutPayment
        return $this->authorize();
    }

    public function purchase_return()
    {
        $response = $this->_express_checkout_return('Sale');

        $data_request = $this->_build_express_checkout_details($response);
        $data = $this->_post_paypal_request($data_request);

        return new Merchant_paypal_express_response($response, Merchant_response::COMPLETE, $data);
    }

    protected function _build_express_checkout_details()
    {
        $request = $this->_new_request('GetExpressCheckoutDetails');
        $request['TOKEN'] = $request['TOKEN'] = $this->CI->input->get_post('token');

        return $request;
    }

    protected function _build_authorize_or_purchase()
    {
        $this->require_params('return_url');

        $request = $this->_new_request('SetExpressCheckout');
        $prefix = 'PAYMENTREQUEST_0_';
        $this->_add_request_details($request, 'Authorization', $prefix);

        // pp express specific fields
        $request['SOLUTIONTYPE'] = $this->setting('solution_type');
        $request['LANDINGPAGE'] = $this->setting('landing_page');
        $request['NOSHIPPING'] = $this->param('shipping') == false;
        $request['ALLOWNOTE'] = 0;
        $request['RETURNURL'] = $this->param('return_url');
        $request['CANCELURL'] = $this->param('cancel_url');
        $request[$prefix.'SHIPTONAME'] = $this->param('name');
        $request[$prefix.'SHIPTOSTREET'] = $this->param('address1');
        $request[$prefix.'SHIPTOSTREET2'] = $this->param('address2');
        $request[$prefix.'SHIPTOCITY'] = $this->param('city');
        $request[$prefix.'SHIPTOSTATE'] = $this->param('region');
        $request[$prefix.'SHIPTOCOUNTRYCODE'] = $this->param('country');
        $request[$prefix.'SHIPTOZIP'] = $this->param('postcode');
        $request[$prefix.'SHIPTOPHONENUM'] = $this->param('phone');
        $request['EMAIL'] = $this->param('email');

        return $request;
    }

    protected function _express_checkout_return($action)
    {
        $request = $this->_new_request('DoExpressCheckoutPayment');
        $this->_add_request_details($request, $action, 'PAYMENTREQUEST_0_');

        $request['TOKEN'] = $this->CI->input->get_post('token');
        $request['PAYERID'] = $this->CI->input->get_post('PayerID');

        return $this->_post_paypal_request($request);
    }
}

class Merchant_paypal_response extends Merchant_response
{
    public function __construct($status, $url = null)
    {
        parent::__construct($status);

        $this->_redirect_url = $url;
    }
}

class Merchant_paypal_express_response extends Merchant_paypal_api_response
{
    protected $shipping;

    public function __construct($response, $success_status, $data)
    {
        parent::__construct($response, $success_status);

        $this->shipping = new StdClass;

        $name = explode(' ', $data['SHIPTONAME'], 2);

        $this->shipping->firstname = $name[0];
        $this->shipping->lastname = isset($name[1]) ? $name[1] : null;
        $this->shipping->email = $data['EMAIL'];
        $this->shipping->address1 = $data['SHIPTOSTREET'];
        $this->shipping->city = $data['SHIPTOCITY'];
        $this->shipping->county = $data['SHIPTOSTATE'];
        $this->shipping->postcode = $data['SHIPTOZIP'];
        $this->shipping->country = $data['SHIPTOCOUNTRYCODE'];
    }

    public function shipping($value)
    {
        if (property_exists($this->shipping, $value)) return $this->shipping->$value;

        return null;
    }
}

/* End of file ./libraries/merchant/drivers/merchant_paypal_express.php */
