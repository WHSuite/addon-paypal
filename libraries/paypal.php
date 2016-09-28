<?php
namespace Addon\Paypal\Libraries;

use App\Libraries\Interfaces\Gateway\PaymentInterface;
use \Whsuite\Inputs\Get as GetInput;

class Paypal implements PaymentInterface
{
    /**
     * object to hold the gateway instance
     */
    protected $_apiContext = null;

    /**
     * setup objects
     */
    protected $_objects = array();

    /**
     * apiContext - setup oauth connection when requested
     *
     * @return  PayPal\Rest\ApiContext
     */
    protected function _apiContext()
    {
        if ($this->_apiContext !== null) {
            return $this->_apiContext;
        }

        $config = \App::get('configs')->get('settings.paypal');

        $this->_apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                $config['client_id'],
                $config['secret_code']
            )
        );

        $options = array();

        if ($config['testmode']) {
            $options['mode'] = 'sandbox';
        } else {
            $options['mode'] = 'live';
        }

        // @link https://developer.paypal.com/webapps/developer/docs/api/#inputfields-object
        $this->_apiContext->setConfig(
            $options
        );

        return $this->_apiContext;
    }

    /**
     * setup a payment, load the omnipay module
     * setup params, account names / amount etc..
     *
     * @param   array   array of data in order to perform the transaction
     * @param   bool    Indicator of whether we're setting up for check return
     * @return  bool    Not required. Returning false can stop transaction process however
     */
    public function setup($data, $returnSetup = false)
    {
        $this->_objects['payer'] = new \PayPal\Api\Payer;
        $this->_objects['payer']->setPaymentMethod('paypal');

        $this->_objects['payment'] = new \PayPal\Api\Payment;
        $this->_objects['payment']->setIntent('sale');

        if ($returnSetup === false) {
            // setup web profile
            $this->_setupWebProfile();

            // setup the payment info
            $this->_setupPaymentInfo($data);
        }

        return true;
    }

    /**
     * setup the web profile
     *
     * @return bool
     */
    protected function _setupWebProfile()
    {
        $profileId = \App::get('configs')->get('settings.paypal.paypal_experience_id');

        if (empty($profileId)) {
            // setup the input fields
            $ProfileInputFields = new \PayPal\Api\InputFields;
            $ProfileInputFields->setNoShipping(1);

            $ProfilePresentation = new \PayPal\Api\Presentation;
            $ProfilePresentation->setBrandName(\App::get('configs')->get('settings.general.sitename'));

            if (! empty($this->_config['imageUrl'])) {
                $ProfilePresentation->setLogoImage(\App::get('configs')->get('settings.general.sitelogo'));
            }

            $PaypalProfile = new \PayPal\Api\WebProfile;
            $PaypalProfile->setName(\App::get('configs')->get('settings.general.sitename') . '-' . mt_rand());
            $PaypalProfile->setInputFields($ProfileInputFields);
            $PaypalProfile->setPresentation($ProfilePresentation);

            try {
                $createProfileResponse = $PaypalProfile->create(
                    $this->_apiContext()
                );
            } catch (\Exception $e) {
                return false;
            }

            $Setting = \Setting::where('slug', '=', 'paypal_experience_id')
                ->first();
            if (! empty($Setting)) {
                $Setting->value = $createProfileResponse->getId();
                $Setting->save();
            }

            $profileId = $createProfileResponse->getId();
        }

        if (! empty($profileId)) {
            $this->_objects['payment']->setExperienceProfileId($profileId);
        }

        return true;
    }

    /**
     * setup the payment info
     *
     * @param   array   array of data in order to perform the transaction
     * @return  bool
     */
    protected function _setupPaymentInfo($data)
    {
        // get the currency code
        $Currency = \Currency::find($data['currency_id']);

        $PaypalAmount = new \PayPal\Api\Amount;
        $PaypalAmount->setCurrency($Currency->code)
            ->setTotal($data['total_due']);

        // Setup the transaction
        $PaypalTransaction = new \PayPal\Api\Transaction;
        $PaypalTransaction->setAmount($PaypalAmount)
            ->setDescription('Payment to ' . \App::get('configs')->get('settings.general.sitename'))
            ->setInvoiceNumber($data['invoice_id']);

        // setup redirect urls
        $PaypalUrls = new \PayPal\Api\RedirectUrls;
        $router = \App::get('router');

        $PaypalUrls->setReturnUrl(
            $router->fullUrlGenerate(
                'client-paypal-invoice-return',
                array(
                    'invoice_id' => $data['invoice_id']
                )
            )
        );

        $PaypalUrls->setCancelUrl(
            $router->fullUrlGenerate(
                'client-paypal-invoice-cancel',
                array(
                    'invoice_id' => $data['invoice_id']
                )
            )
        );

        // set the transaction and redirect urls to the payment object so it's ready to create
        $this->_objects['payment']->setRedirectUrls($PaypalUrls)
            ->setTransactions(array($PaypalTransaction))
            ->setPayer($this->_objects['payer']);
    }

    /**
     * process the payment
     *
     * @param   array   array of data in order to perform the transaction
     * @return  bool
     */
    public function process($data)
    {
        // try to create the payment, this will give us a approval url
        try {
            $this->_objects['payment']->create(
                $this->_apiContext()
            );

            // redirect to the approval link
            header("Location: " . $this->_objects['payment']->getApprovalLink());
            exit;

        } catch (\PayPal\Exception\PayPalConnectionException $pce) {
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * check the return of a payment
     *
     * @param   array   array of data in order to perform the transaction
     * @return  bool|string
     */
    public function checkReturn($data)
    {
        $paymentId = GetInput::get('paymentId');
        $payerId = GetInput::get('PayerID');

        $PaypalPayment = \PayPal\Api\Payment::get($paymentId, $this->_apiContext());
        $PaypalExecution = new \PayPal\Api\PaymentExecution;
        $PaypalExecution->setPayerId($payerId);

        try {
            // execute with paypal
            $PaypalPayment->execute(
                $PaypalExecution,
                $this->_apiContext()
            );

            return $paymentId;
        } catch (\PayPal\Exception\PayPalConnectionException $pce) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
