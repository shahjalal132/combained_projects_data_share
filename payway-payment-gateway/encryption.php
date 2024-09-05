<?php

function make_api_call() {

    $req_time    = date( 'YmdHis' );
    $merchant_id = 'atlanticscschool';
    $public_key  = 'e52f2f0e-e5ba-4a36-ae8c-54fee5646c02';
    $trant_id    = 12543;
    $amount      = 10.00;

    // Generate Items
    $items = [
        [
            'name'     => 'test',
            'quantity' => 1,
            'price'    => $amount,
        ],
    ];
    $items = json_encode( $items );
    $items = base64_encode( $items );

    $shipping             = '';
    $ctid                 = '';
    $pwt                  = '';
    $firstname            = 'Muhammad';
    $lastname             = 'Shahjalal';
    $email                = 'ffshahjalal@gmail.com';
    $phone                = '016 220 854';
    $type                 = 'purchase';
    $payment_option       = 'abapay';
    $return_url           = 'https://imjol.com/success';
    $cancel_url           = 'https://imjol.com/cancel';
    $continue_success_url = 'https://imjol.com/success';
    $return_deeplink      = '';
    $currency             = 'KHR';
    $custom_fields        = '';
    $return_params        = '';

    /**
     * String generation method
     * String = req_time + merchant_id + tran_id + amount + items + shipping + ctid + pwt + firstname + lastname + email + phone + type + payment_option + return_url + cancel_url + continue_success_url + return_deeplink + currency + custom_fields + return_params with public_key.
     */

    // Generate String
    $string = $req_time . $merchant_id . $trant_id . $amount . $items . $shipping . $ctid . $pwt . $firstname . $lastname . $email . $phone . $type . $payment_option . $return_url . $cancel_url . $continue_success_url . $return_deeplink . $currency . $custom_fields . $return_params . $public_key;

    /**
     * Hash generation method: base64_encode(hash_hmac('sha512', string, $public_key, true));
     * 
     * Generate Hash
     */
    $hash = base64_encode( hash_hmac( 'sha512', $string, $public_key, true ) );

    // Api url 
    $api_url = 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase';
    /**
     * Method: POST
     * Content Type: multipart/form-data
     * Body: form-data
     */



}

make_api_call();