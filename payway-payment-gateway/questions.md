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
this is my codes to create a hash and make a api call.
{
    "req_time":"20210123234559",
    "merchant_id":"onlinesshop24",
    "tran_id":"00002894",
    "firstname":"Fristname",
    "lastname":"Customer Last name",
    "email":"ema_il@textdomain.com",
    "phone":"0965965965",
    "amount":5000,
    "type":"purcahse",
    "payment_option":"abapay",
    "items":"W3snbmFtZSc6J3Rlc3QnLCdxdWFudGl0eSc6JzEnLCdwcmljZSc6JzEuMDAnfV0=",
    "currency":"KHR",
    "continue_success_url":"www.staticmerchanturl.com/Success",
    "return_deeplink":,
    "custom_fields":"{"Purcahse order ref":"Po-MX9901", "Customfield2":"value for custom field"}",
    "return_param":"500 Character notes included here will be returned on pushback notification after transaction is successful.",
    "hash":"K3nd/2Z4g45Paoqx06QA3UQeHRC2Ts37zjudG7DqyyU2Cq0cvOFMYqwtEsXkaEmNOSiFh6Y+IHRdwnA2WA/M/Qg=="
}
this is example request body payload
<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => array('req_time' => '20240905053739','merchant_id' => 'atlanticscschool'),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
this is the php curl example. make a api call with provided values