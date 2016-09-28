<?php

class PaypalController extends ClientController
{
    /**
     * handle the return from paypal and process accordingly
     *
     */
    public function payReturn($invoice_id = 0)
    {
        // get the invoice
        $invoice = Invoice::find($invoice_id);

        if (is_object($invoice)) {

            $total_due = $invoice->total - $invoice->total_paid;

            $gateway_data = array(
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'currency_id' => $invoice->currency_id,
                'total_due' => $total_due
            );

            $addon = Addon::where('directory', '=', 'paypal')
                ->where('is_gateway', '=', 1)
                ->with('Gateway')
                ->first();

            $transaction_ref = \App\Libraries\Payments::checkPayment($addon, $gateway_data);

            if ($transaction_ref !== false) {

                // check we haven't already logged this transaction (JIC)
                if (! \App\Libraries\Payments::referenceExists($invoice_id, $transaction_ref)) {

                    // not found, add the transaction row
                    \App\Libraries\Payments::finalisePayment($invoice_id, $transaction_ref, $addon);
                }

                \App::get('session')->setFlash('success', \App::get('translation')->get('payment_successfully_taken'));
                return header("Location: ".App::get('router')->generate('client-manage-invoice', array('id' => $invoice_id)));
            }
        }

        \App::get('session')->setFlash('error', \App::get('translation')->get('error_taking_payment'));
        return header("Location: ".App::get('router')->generate('client-manage-invoice', array('id' => $invoice_id)));
    }

    /**
     * payment was cancelled display errors
     */
    public function payCancel($invoice_id = 0)
    {
        \App::get('session')->setFlash('error', \App::get('translation')->get('error_taking_payment'));
        return header("Location: ".App::get('router')->generate('client-manage-invoice', array('id' => $invoice_id)));
    }

}
