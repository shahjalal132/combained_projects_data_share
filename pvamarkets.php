<?php
add_action( 'wp_ajax_show_products', 'show_products_callback' );
add_action( 'wp_ajax_nopriv_show_products', 'show_products_callback' );
function show_products_callback() {

    $cat = $_POST['cat'];

    $pr_query = new WP_Query(
        array(
            'post_type'      => 'item',
            'offset'         => 5,
            'posts_per_page' => 500,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'item_cat',
                    'terms'    => $cat,
                ),
            ),
        )
    );

    while ( $pr_query->have_posts() ) {
        $pr_query->the_post();

        get_template_part( 'template-parts/content', 'item' );

    }
    wp_reset_postdata();



    die();
}

add_action( 'wp_ajax_change_description', 'change_description_callback' );
function change_description_callback() {

    $description = $_POST['description'];
    $bid_id      = $_POST['bid_id'];

    if ( !empty( $bid_id ) && !empty( $description ) ) {

        $my_post = array(
            'ID'         => $bid_id,
            'post_title' => $description,
        );

        // Update the post into the database
        wp_update_post( $my_post );

        die( 'yes' );

    } else {
        die( 'no' );
    }


}

add_action( 'wp_ajax_remove_unsold', 'remove_unsold_callback' );
function remove_unsold_callback() {

    $item_id = $_POST['bid_id'];

    global $wpdb;

    $table_name = $wpdb->prefix . "accounts";


    $wpdb->delete( $table_name, array( 'product_id' => $item_id, 'item_status' => 'free' ) );
    $bidStatus = get_post_meta( $item_id, 'bid_status', true );

    if ( $bidStatus !== "declined" ) {
        update_post_meta( $item_id, 'bid_status', 'soldout' );
    }

    die();
}

add_action( 'wp_ajax_item_list', 'item_list_callback' );
function item_list_callback() {

    $item_id = $_POST['bid_id'];
    $mode    = $_POST['mode'];

    global $wpdb;

    $table_name = $wpdb->prefix . "accounts";

    if ( $mode == 0 ) {
        $results = $wpdb->get_results( "SELECT * FROM $table_name WHERE product_id = $item_id" );
    } else {
        $results = $wpdb->get_results( "SELECT * FROM $table_name WHERE product_id = $item_id and item_status = 'free'" );
    }

    $item_format    = get_post_meta( $item_id, 'item_format', true );
    $item_format_ex = explode( ',', $item_format );

    ?>
    <style>
        /* bad-accounts */
        .bad-accounts input[type="checkbox"] {
            margin: 0px !important;
            position: relative;
        }

        .float-none {
            float: none !important;
            margin-right: 15px !important;
        }

        .popup {
            display: none;
        }

        .popup form textarea {
            height: 150px;
            margin-bottom: 10px;
        }

        .remove-close-button .ui-dialog-titlebar-close {
            display: none;
        }
    </style>
    <table class="list zebra ac">
        <tr>
            <th>#</th>
            <th>Account</th>
            <th>Status</th>
            <th>Order Id</th>
            <th>Mark Bad Account</th>
        </tr>

        <?php $num = 1;
        foreach ( $results as $item ) : ?>
            <?php if ( $item->item_status == "bad" ) : ?>
                <tr style="background-color: red; color: #fff">
                    <td><?php echo $num; ?></td>
                    <td>
                        <?php
                        $id        = $item->id;
                        $all_items = array();
                        foreach ( $item_format_ex as $item_single ) {
                            $all_items[] = $item->$item_single;
                        }
                        echo implode( ':', $all_items );

                        ?>
                    </td>
                    <td><?php echo $item->item_status; ?></td>
                    <td><?php

                    $order_id = $item->order_id;
                    if ( $order_id == 0 ) {
                        echo "--";
                    } else {
                        $order        = wc_get_order( $order_id );
                        $order_number = $order->get_order_number();
                        echo $order_number;
                    }



                    ?></td>
                    <td class="bad-accounts">
                        <?php if ( $item->item_status == 'bad' ) : ?>
                            <input type="checkbox" value="<?= $item->id ?>" checked name="account" class="bad-account">
                        <?php elseif ( $item->item_status == 'sold' ) : ?>

                        <?php else : ?>
                            <input type="checkbox" value="<?= $item->id ?>" name="account" class="bad-account">
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else : ?>
                <tr>
                    <td><?php echo $num; ?></td>
                    <td>
                        <?php
                        $id        = $item->id;
                        $all_items = array();
                        foreach ( $item_format_ex as $item_single ) {
                            $all_items[] = $item->$item_single;
                        }
                        echo implode( ':', $all_items );
                        ?>
                    </td>
                    <td><?php echo $item->item_status; ?></td>
                    <td><?php

                    $order_id = $item->order_id;
                    if ( $order_id == 0 ) {
                        echo "--";
                    } else {
                        $order        = wc_get_order( $order_id );
                        $order_number = $order->get_order_number();
                        echo $order_number;
                    }



                    ?></td>
                    <td class="bad-accounts">
                        <?php if ( $item->item_status == 'bad' ) : ?>
                            <input type="checkbox" value="<?= $item->id ?>" checked name="account" class="bad-account">
                        <?php elseif ( $item->item_status == 'sold' ) : ?>

                        <?php else : ?>
                            <input type="checkbox" value="<?= $item->id ?>" name="account" class="bad-account">
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
            <?php $num++; endforeach; ?>
    </table>

    <div id="popup" class="popup">
        <form action="" method="post">
            <textarea name="" id="check-accounts-textarea" cols="10" rows="10"></textarea>
            <p id="loading-message"></p>
            <p id="response-message"></p>
            <button type="submit" name="check-accounts" id="check-accounts">Checked</button>
        </form>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            $(".bad-account").change(function () {
                if (this.checked) {
                    var id = $(this).val();
                    $.ajax({
                        type: 'POST',
                        url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                        data: {
                            action: 'update_database',
                            id: id
                        },
                        success: function (response) {
                            console.log('Check Database update successfully.');
                        },
                        error: function (xhr, status, error) {
                            console.error("Check Error updated database:", error);
                        }
                    });
                } else {
                    var id = $(this).val();
                    $.ajax({
                        type: 'POST',
                        url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                        data: {
                            action: 'update_database_unchecked',
                            id: id
                        },
                        success: function (response) {
                            console.log('Uncheck Update Database');
                        },
                        error: function (xhr, status, error) {
                            console.error("Error Uncheck Update Database");
                        }
                    });
                }
            });

            // Adding the button after each dialog title
            $('.ui-dialog-titlebar > span.ui-dialog-title').each(function (i, el) {
                $(el).addClass('float-none');
                $('<button id="bad-account-upload" style="color:#fff;">Upload Bad Account list</button>').insertAfter(el);
            });

            // Adding an event handler to the button to trigger a popup dialog
            $(document).on('click', '#bad-account-upload', function () {
                // Show the popup dialog
                $('#popup').dialog({
                    modal: false,
                    width: '700px',
                    resizable: true,
                    draggable: true,
                    buttons: {
                        Close: function () {
                            $(this).dialog('close');
                        }
                    },
                    title: 'Bad Account List',
                    dialogClass: 'remove-close-button',


                });
            });

            // check bad accounts
            $(document).on('click', '#check-accounts', function (e) {
                e.preventDefault();

                // Show loading spinner or message
                $('#loading-message').text('Checking bad accounts...');

                // get accounts from form field
                var textareaValue = $('#check-accounts-textarea').val();

                // make ajax call to check accounts
                $.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    data: {
                        action: 'check_accounts',
                        accounts: textareaValue
                    },
                    success: function (response) {
                        // Hide loading spinner or message
                        $('#loading-message').text('');

                        // Handle response
                        $('#response-message').text(response);
                    },
                    error: function (xhr, status, error) {
                        // Hide loading spinner or message
                        $('#loading-message').text('');

                        console.error("Error Does not check accounts");
                    }
                });
            });


        });
    </script>

    <?php

    die();

}

add_action( 'wp_ajax_check_accounts', 'check_accounts' );
add_action( 'wp_ajax_nopriv_check_accounts', 'check_accounts' );

function check_accounts() {
    global $wpdb;

    // Get accounts from ajax request
    $accounts_to_check = $_POST['accounts'];

    // Convert to array
    $accounts_to_check = explode( "\n", $accounts_to_check );

    // Get accounts from database
    $table_name = $wpdb->prefix . "accounts";
    $accounts   = $wpdb->get_results( "SELECT * FROM $table_name" );

    // Initialize count of bad emails
    $badEmailsCount = 0;

    // Iterate through accounts from database
    foreach ( $accounts as $account ) {
        // Check if the email from database matches any of the provided emails
        if ( in_array( $account->email, $accounts_to_check ) ) {
            // Update the record in the database
            $wpdb->update(
                $table_name,
                array( 'item_status' => 'bad' ),
                array( 'id' => $account->id )
            );
            $badEmailsCount++;
        }
    }

    // Prepare response
    $message = '';

    if ( $badEmailsCount >= 0 ) {
        $message = "Bad Accounts checked successfully. $badEmailsCount accounts found.";
    } else {
        $message = 'No bad accounts found.';
    }

    // Send response
    put_api_response_data( $message );
    echo $message;
}

function put_api_response_data( $data ) {
    // Ensure directory exists to store response data
    $directory = __DIR__ . '/api_response/';
    if ( !file_exists( $directory ) ) {
        mkdir( $directory, 0777, true );
    }

    // Construct file path for response data
    $fileName = $directory . 'response.txt';

    // Get the current date and time
    $current_datetime = date( 'Y-m-d H:i:s' );

    // Append current date and time to the response data
    $data = $data . ' - ' . $current_datetime;

    // Append new response data to the existing file
    if ( file_put_contents( $fileName, $data . PHP_EOL, FILE_APPEND | LOCK_EX ) !== false ) {
        return "Data appended to file successfully.";
    } else {
        return "Failed to append data to file.";
    }
}


add_action( 'woocommerce_order_status_processing', 'bcmarket_execute_accounts_on_payment_complete' );
add_action( 'woocommerce_order_status_completed', 'bcmarket_execute_accounts_on_payment_complete' );
function bcmarket_execute_accounts_on_payment_complete( $order_id ) {

    global $wpdb;

    $table_name = $wpdb->prefix . "accounts";

    $order = wc_get_order( $order_id );

    $order_exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE order_id = %d", $order_id ) );


    if ( $order_exists ) {

    } else {

        foreach ( $order->get_items() as $item_id => $item ) {

            $quantity   = $item->get_quantity();
            $product_id = $item->get_product_id();
            $itemid     = get_post_meta( $product_id, 'item_id', true );
            $update     = $wpdb->query( "UPDATE  $table_name SET item_status = 'sold', order_id = $order_id WHERE product_id = $product_id and item_status = 'free' LIMIT $quantity" );



        }



        update_post_meta( $order_id, 'item_id_data', $itemid );


    }






}


add_action( 'woocommerce_order_status_processing', 'update_item_price_after_selling' );
add_action( 'woocommerce_order_status_completed', 'update_item_price_after_selling' );
function update_item_price_after_selling( $order_id ) {
    $order = wc_get_order( $order_id );
    global $wpdb;

    $table_name = $wpdb->prefix . "accounts";

    $itemIDbasedprice = [];
    foreach ( $order->get_items() as $item_id => $item ) {


        $product_id = $item->get_product_id();
        $itemid     = get_post_meta( $product_id, 'item_id', true );



        $item_pro_query = new WP_Query(
            array(
                'posts_per_page' => -1,
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'meta_query'     => array(
                    array(
                        'key'   => 'item_id',
                        'value' => $itemid,
                    ),
                ),
            )
        );


        while ( $item_pro_query->have_posts() ) {
            $item_pro_query->the_post();

            $product_id    = get_the_ID();
            $product       = wc_get_product( $product_id );
            $regular_price = $product->get_regular_price();

            $itemIDbasedprice[$product_id] = $regular_price;
        }




    }


    $newArry = [];

    foreach ( $itemIDbasedprice as $key => $val ) {
        $productID = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT product_id FROM $table_name WHERE product_id = %d AND item_status = %s",
                $key,
                'free'
            )
        );

        if ( $productID ) {
            $newArry[] = $productID;
        }
    }

    if ( count( $newArry ) > 0 ) {
        $newRtotal = [];

        foreach ( $newArry as $newID ) {
            $product       = wc_get_product( $newID );
            $regular_price = $product->get_regular_price();
            $itid          = get_post_meta( $newID, 'item_id', true );

            $newRtotal[$itid] = $regular_price;
        }

        $newprice = min( $newRtotal );

        foreach ( $newRtotal as $newKey => $newitp ) {
            update_post_meta( $newKey, 'item_price', $newprice );
        }
    } else {
        // Handle the case when $newArry is empty
    }


    $serializedArray = serialize( $newRtotal );
    setcookie( 'item_price', $serializedArray, time() + 3600, '/' );
    setcookie( 'newp', $newprice, time() + 3600, '/' );





}

function afdsafdsaf() {

    global $wpdb;

    $table_name = $wpdb->prefix . "accounts";

    $result = $wpdb->query( "SELECT * FROM $table_name WHERE product_id = 148 and item_status = 'free' " );

    print_r( $result );

    $update = $wpdb->query( "UPDATE  $table_name SET item_status = 'sold', order_id = 5415 WHERE product_id = 148 LIMIT 2" );

    echo '<br>';
    print_r( $update );

    //die();
}
//add_action('init', 'afdsafdsaf');



add_filter( 'woocommerce_account_menu_items', 'bcmarket_remove_my_account_links' );
function bcmarket_remove_my_account_links( $menu_links ) {
    unset( $menu_links['downloads'] );
    unset( $menu_links['edit-address'] );

    return $menu_links;
}

add_filter( 'woocommerce_account_menu_items', 'add_tickets_custom_menu_myaccount' );
function add_tickets_custom_menu_myaccount( $menu_links ) {
    $new = array( 'tickets' => __( 'Tickets', 'bcmarket' ) );

    $menu_links = array_slice( $menu_links, 0, 2, true )
        + $new
        + array_slice( $menu_links, 2, NULL, true );

    return $menu_links;
}

// register permalink endpoint
add_action( 'init', 'tirisi_add_endpoint' );
function tirisi_add_endpoint() {

    add_rewrite_endpoint( 'tickets', EP_PAGES );

}
// content for the new page in My Account, woocommerce_account_{ENDPOINT NAME}_endpoint
add_action( 'woocommerce_account_tickets_endpoint', 'tirisi_returns_endpoint_content' );
function tirisi_returns_endpoint_content() {

    wc_get_template_part( 'myaccount/tickets' );

}

// Save the custom field 'wallets' 
add_action( 'woocommerce_save_account_details', 'save_wallets_account_details', 12, 1 );
function save_wallets_account_details( $user_id ) {

    if ( isset( $_POST['wallets'] ) )
        update_user_meta( $user_id, 'wallets', $_POST['wallets'] );

}

add_action( 'template_redirect', 'bcmarket_redirect_custom_thank_you' );
function bcmarket_redirect_custom_thank_you() {

    if ( !is_wc_endpoint_url( 'order-received' ) || empty( $_GET['key'] ) ) {
        return;
    }
    wp_safe_redirect( site_url( 'your-account/orders/' ) );
}


add_filter( 'woocommerce_checkout_fields', 'bcmarket_remove_checkout_fields' );
function bcmarket_remove_checkout_fields( $fields ) {

    unset( $fields['billing']['billing_first_name'] );
    unset( $fields['billing']['billing_last_name'] );
    unset( $fields['billing']['billing_company'] );
    unset( $fields['billing']['billing_country'] );
    unset( $fields['billing']['billing_address_1'] );
    unset( $fields['billing']['billing_address_2'] );
    unset( $fields['billing']['billing_city'] );
    unset( $fields['billing']['billing_phone'] );
    unset( $fields['billing']['billing_state'] );
    unset( $fields['billing']['billing_postcode'] );
    return $fields;

}

function add_payment_title_before_gateway() {
    ?>
    <div class="payment_title">
        <h3>Payment Type</h3>
    </div>
    <?php
}
add_action( 'woocommerce_review_order_before_payment', 'add_payment_title_before_gateway' );



add_filter( 'woocommerce_available_payment_gateways', 'bcmarket_gateway_by_wallet' );
function bcmarket_gateway_by_wallet( $gateways ) {

    if ( is_admin() ) {
        return $gateways;
    }

    foreach ( WC()->cart->get_cart_contents() as $item ) {
        $product_id  = $item['product_id'];
        $item_status = get_post_status( $product_id );

        if ( $item_status !== 'draft' ) {
            if ( isset( $gateways['cheque'] ) ) {
                unset( $gateways['cheque'] );
            }
            if ( isset( $gateways['cp'] ) ) {
                unset( $gateways['cp'] );
            }
            if ( isset( $gateways['cop'] ) ) {
                unset( $gateways['cop'] );
            }
            if ( isset( $gateways['bacs'] ) ) {
                unset( $gateways['bacs'] );
            }
        }


    }

    return $gateways;

}


add_filter( 'woocommerce_gateway_icon', 'custom_gateway_icon_wall', 10, 2 );

function custom_gateway_icon_wall( $icon, $gateway_id ) {
    if ( $gateway_id == 'nowpayments' ) {
        $icon = '<img src="' . esc_url( home_url( '/wp-content/uploads/2023/02/crypto-1.png' ) ) . '" alt="nowpayments">';
    }
    return $icon;
}

add_action( 'woocommerce_email_after_order_table', 'add_additional_text_to_processing_email', 10, 2 );

function add_additional_text_to_processing_email( $order, $is_admin_email ) {

    $item_status = '';

    foreach ( $order->get_items() as $item_id => $item ) {
        $product_id  = $item->get_product_id();
        $item_status = get_post_status( $product_id );
    }
    if ( $item_status != 'private' ) : ?>
        <p>Download Product:
            <a class="download-link"
                href="<?php echo esc_url( home_url( '/download-accounts/' ) ); ?>?order_id=<?php echo $order->get_order_number(); ?>&order_key=<?php echo $order->get_order_key(); ?>"
                target="_blank">
                <?php echo esc_url( home_url( '/download-accounts/' ) ); ?>?order_id=<?php echo $order->get_order_number(); ?>&order_key=<?php echo $order->get_order_key(); ?>
            </a>
        </p>

    <?php endif;
}


function bc_woocommerce_before_email_footer_callback() {
    ?>
    <p style="text-align: center;">Please read these articles to avoid problems when working with accounts</p>
    <p style="text-align: center;">
        <a href="<?php echo esc_url( home_url( '/accounts-guidelines/' ) ); ?>">Recommendations for working with any
            accounts</a>
    </p>
    <p style="text-align: center;">
        <a href="<?php echo esc_url( home_url( '/faq' ) ); ?>">FAQ(frequently asked questions)</a>
    </p>
    <?php
}
add_action( 'bc_woocommerce_before_email_footer', 'bc_woocommerce_before_email_footer_callback' );


add_action( 'woocommerce_order_status_processing', 'send_additional_email_to_customer' );
function send_additional_email_to_customer( $order_id ) {
    $order = wc_get_order( $order_id );
    $to    = $order->get_billing_email();

    $site_name   = get_bloginfo( 'name' );
    $domain_name = parse_url( get_site_url(), PHP_URL_HOST );

    $subject = 'Discounts from our Telegram channel';

    ob_start();

    get_template_part( 'emails/telegram', 'channel' );

    $body = ob_get_clean();


    $headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: ' . $site_name . ' <noreply@' . $domain_name . '>' );

    wp_mail( $to, $subject, $body, $headers );
}





function my_custom_function_after_order_created( $order_id, $posted_data, $order ) {

    foreach ( $order->get_items() as $item_id => $item ) {

        $product_id = $item->get_product_id();
        // Get the product meta value
        $partner_price_value = get_post_meta( $product_id, 'partner_price', true );
        $item->update_meta_data( '__partnfdgfdsger_price', 33333 );

        if ( !empty( $partner_price_value ) ) {
            $item->update_meta_data( 'partner_price', $partner_price_value );
        }

    }

}
//add_action( 'woocommerce_checkout_order_processed', 'my_custom_function_after_order_created', 10, 3 );

function my_custom_function_update_line_item_meta_data( $item, $cart_item_key, $values, $order ) {
    // Get the product ID from the cart item
    $product_id = $values['product_id'];

    // Get the product meta data
    $my_custom_meta = get_post_meta( $product_id, 'partner_price', true );

    // Update the line item meta data with the product meta data
    $item->update_meta_data( 'partner_price', $my_custom_meta );
}
add_action( 'woocommerce_checkout_create_order_line_item', 'my_custom_function_update_line_item_meta_data', 10, 4 );

function my_custom_function_hide_line_item_meta_data( $keys ) {
    $keys[] = 'partner_price';
    return $keys;
}
add_filter( 'woocommerce_hidden_order_itemmeta', 'my_custom_function_hide_line_item_meta_data' );

function my_custom_function_remove_line_item_meta_data_from_emails( $fields, $sent_to_admin, $order ) {
    foreach ( $fields as $key => $field ) {
        if ( $field['label'] === 'partner_price' ) {
            unset( $fields[$key] );
        }
    }
    return $fields;
}
add_filter( 'woocommerce_email_order_meta_fields', 'my_custom_function_remove_line_item_meta_data_from_emails', 10, 3 );


// Generate sequential product ID
add_action( 'woocommerce_new_product', 'generate_sequential_product_id', 10, 1 );
function generate_sequential_product_id( $product_id ) {
    // Get the latest product ID
    $latest_product_id = (int) get_option( 'latest_product_id', 0 );

    // Increment the latest product ID
    $latest_product_id++;

    // Update the latest product ID option
    update_option( 'latest_product_id', $latest_product_id );

    // Set the custom product ID field
    update_post_meta( $product_id, 'custom_product_id', $latest_product_id );
}

function custom_product_redirect() {
    // Check if we're on a single product page
    if ( is_singular( 'product' ) ) {
        global $post;
        $product_id = $post->ID;

        $item_id = get_post_meta( $product_id, 'item_id', true );

        if ( empty( $item_id ) ) {
            wp_redirect( home_url() );
            exit;
        } else {
            wp_redirect( get_permalink( $item_id ) );
            exit;
        }




    }
}
add_action( 'template_redirect', 'custom_product_redirect' );





// Add Payment Gateway Setting option
class WC_Settings_As_Payment_Gateways {

    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
        add_action( 'woocommerce_settings_tabs_as_payment_discount', __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_as_payment_discount', __CLASS__ . '::update_settings' );
    }

    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs['as_payment_discount'] = __( 'Payment Gateways Discount', 'woocommerce' );
        return $settings_tabs;
    }


    public static function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }



    public static function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }


    public static function get_settings() {

        $installed_payment_methods = WC()->payment_gateways->payment_gateways();


        $get_settings = array();

        $get_settings[] = array(
            'name' => 'Enter the discount percentage of your payment gateways!',
            'type' => 'title',
            'desc' => '',
            'id'   => 'wc_settings_tab_as_payment_discount_title',
        );

        foreach ( $installed_payment_methods as $method_id => $method ) {
            $get_settings[] = array(
                'name'    => $method->title,
                'type'    => 'number',
                'desc'    => '',
                'id'      => 'wc_settings_discount_' . $method_id,
                'default' => 0,

            );
        }

        $get_settings[] = array(
            'type' => 'sectionend',
            'desc' => '',
            'id'   => 'wc_settings_tab_as_payment_discount_section_end',
        );



        return apply_filters( 'wc_settings_as_payment_discount', $get_settings );
    }
}
WC_Settings_As_Payment_Gateways::init();


// Calculate Fees based on gateways
add_action( 'woocommerce_cart_calculate_fees', 'as_add_fee_discounter_per_payment_gateways', 25 );
function as_add_fee_discounter_per_payment_gateways( $cart ) {

    $current_method            = WC()->session->get( 'chosen_payment_method' );
    $installed_payment_methods = WC()->payment_gateways->payment_gateways();
    $available_methods         = array();
    $available_methods_title   = array();
    $current_method_title      = WC()->session->get( 'payment_method_title' );
    $subtotal                  = WC()->cart->get_subtotal();

    foreach ( $installed_payment_methods as $method_id => $method ) {
        $available_methods[]                 = $method_id;
        $available_methods_title[$method_id] = $method->title;
    }

    if ( is_admin() && !defined( 'DOING_AJAX' ) ) {
        return;
    }

    if ( in_array( $current_method, $available_methods ) ) {

        $opt_title = $available_methods_title[$current_method];
        $opt_name  = 'wc_settings_discount_' . $current_method;
        $opt_val   = get_option( $opt_name );

        if ( $opt_val != 0 ) {

            if ( $opt_val < 0 ) {
                $dis_text = 'Discount';
            } else {
                $dis_text = 'Fee';
            }

            $calculate_discount = ( $subtotal * $opt_val ) / 100;
            WC()->cart->add_fee( $opt_title . ' ' . $dis_text, $calculate_discount );
        }


    }


}

// Update Checkout on Gateway Selection 
add_action( 'woocommerce_checkout_init', 'as_checkout_refresh_on_payment_method_selection' );
function as_checkout_refresh_on_payment_method_selection() {
    wc_enqueue_js( "jQuery( function( $ ){
        $( 'form.checkout' ).on( 'change', 'input[name^=\"payment_method\"]', function(){
            $( 'body' ).trigger( 'update_checkout' );
        });
    });" );
}



// Add Minimun Options per Payment Gateway
class WC_Settings_As_Payment_Gateways_Minimum {

    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
        add_action( 'woocommerce_settings_tabs_as_payment_min', __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_as_payment_min', __CLASS__ . '::update_settings' );
    }

    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs['as_payment_min'] = __( 'Payment Gateways Minimum', 'woocommerce' );
        return $settings_tabs;
    }


    public static function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }



    public static function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }


    public static function get_settings() {

        $installed_payment_methods = WC()->payment_gateways->payment_gateways();


        $get_settings = array();

        $get_settings[] = array(
            'name' => 'Enter the discount percentage of your payment gateways!',
            'type' => 'title',
            'desc' => '',
            'id'   => 'wc_settings_tab_as_payment_min_title',
        );

        foreach ( $installed_payment_methods as $method_id => $method ) {
            $get_settings[] = array(
                'name'    => $method->title,
                'type'    => 'number',
                'desc'    => '',
                'id'      => 'wc_settings_min_' . $method_id,
                'default' => 0,

            );
        }

        $get_settings[] = array(
            'type' => 'sectionend',
            'desc' => '',
            'id'   => 'wc_settings_tab_as_payment_min_section_end',
        );



        return apply_filters( 'wc_settings_as_payment_min', $get_settings );
    }
}
WC_Settings_As_Payment_Gateways_Minimum::init();



add_action( 'woocommerce_checkout_process', 'check_minimum_order_amount' );
function check_minimum_order_amount() {
    // Get the selected payment gateway
    $chosen_payment_method = WC()->session->get( 'chosen_payment_method' );

    // Get the minimum order amount for the selected payment gateway
    $minimum_amount = get_option( 'wc_settings_min_' . $chosen_payment_method );

    // Get the order total
    $order_total = WC()->cart->total;

    // Check if the order total is less than the minimum amount
    if ( $order_total < $minimum_amount ) {
        // Display an error message
        wc_add_notice( sprintf( 'The minimum order amount for %s is %s. Please adjust your order to continue.', $chosen_payment_method, wc_price( $minimum_amount ) ), 'error' );


    }
}

?>