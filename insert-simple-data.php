<?php

// Include necessary files
require_once PLUGIN_PATH . '/vendor/autoload.php';
use Automattic\WooCommerce\Client;

// Function to insert products into WooCommerce
function product_insert_woocommerce() {

    // Get global $wpdb object
    global $wpdb;

    // Define table names
    $table_name = $wpdb->prefix . 'sync_products';

    // Retrieve pending products from the database
    $products = $wpdb->get_results( "SELECT * FROM $table_name LIMIT 1" );

    // WooCommerce store information
    $website_url     = home_url();
    $consumer_key    = 'ck_b38274103f71580c80b5fe2a268451152a75e546';
    $consumer_secret = 'cs_4105c00f5c8bc420039a3c6f0faa02054f5974fd';

    foreach ( $products as $product ) {

        // Retrieve product data
        $p_id                = $product->id;
        $fournisseur         = $product->supplier;
        $product_category    = $product->product_category;
        $id_bigbuy           = $product->id_bigbuy;
        $Category_code       = $product->category_code;
        $product_name        = $product->product_name;
        $attributes1         = $product->attributes1;
        $attributes2         = $product->attributes2;
        $values1             = $product->values1;
        $values2             = $product->values2;
        $product_description = $product->product_description;
        $benefices           = $product->benefices;
        $testimonial         = $product->testimonial;
        $claim_1             = $product->claim_1;
        $meta_description    = $product->meta_description;
        $seo_title           = $product->seo_title;
        $brand               = $product->brand;
        $feature             = $product->feature;
        $EAN                 = $product->EAN;
        $stock               = $product->stock;

        //Get product price
        $regular_price = $product->product_price;
        $sale_price    = $product->product_pvp;

        // product images
        $image_url_string = $product->image_urls;
        $image_url_array  = explode( ',', $image_url_string );

        // Set up the API client with WooCommerce store URL and credentials
        $client = new Client(
            $website_url,
            $consumer_key,
            $consumer_secret,
            [
                'verify_ssl' => false,
            ]
        );

        // Check if the product already exists in WooCommerce
        $args = array(
            'post_type'  => 'product',
            'meta_query' => array(
                array(
                    'key'     => '_sku',
                    'value'   => $id_bigbuy,
                    'compare' => '=',
                ),
            ),
        );

        // Check if the product already exists
        $exiting_products = new WP_Query( $args );

        if ( $exiting_products->have_posts() ) {
            $exiting_products->the_post();

            // get product id
            $product_id = get_the_ID();

            // Update the product  if already exists
            $product_data = [
                'name'        => $product_name,
                'sku'         => $id_bigbuy,
                'type'        => 'simple',
                'description' => $product_description,
                'attributes'  => [
                    [
                        'name'      => 'Dimensions',
                        'visible'   => true,
                        'variation' => true,
                    ],
                ],
            ];

            // update product
            $client->put( 'products/' . $product_id, $product_data );

        } else {

            // Create a new product if not exists
            $product_data = [
                'name'        => $product_name,
                'sku'         => $id_bigbuy,
                'type'        => 'simple',
                'description' => $product_description,
                'attributes'  => [
                    [
                        'name'      => 'Dimensions',
                        'visible'   => true,
                        'variation' => true,
                    ],
                ],
            ];

            // Create the product
            $product    = $client->post( 'products', $product_data );
            $product_id = $product->id;

            // Set product information
            wp_set_object_terms( $product_id, 'simple', 'product_type' );
            update_post_meta( $product_id, '_visibility', 'visible' );
            update_post_meta( $product_id, '_stock_status', 'instock' );
            update_post_meta( $product_id, '_regular_price', $regular_price );
            update_post_meta( $product_id, '_sale_price', $sale_price );
            update_post_meta( $product_id, '_price', $sale_price );
            update_post_meta( $product_id, '_bigbuy-testimonial', $testimonial );
            update_post_meta( $product_id, '_claim_1', $claim_1 );
            update_post_meta( $product_id, '_benefices', $benefices );
            update_post_meta( $product_id, '_seo_title', $seo_title );

            // Update product meta data in WordPress
            update_post_meta( $product_id, '_stock', $stock );

            // display out of stock message if stock is 0
            if ( $stock <= 0 ) {
                update_post_meta( $product_id, '_stock_status', 'outofstock' );
            } else {
                update_post_meta( $product_id, '_stock_status', 'instock' );
            }
            update_post_meta( $product_id, '_manage_stock', 'yes' );


            // set product gallery images
            foreach ( $image_url_array as $image_url ) {

                // Extract image name
                $image_name = basename( $image_url );
                // Get WordPress upload directory
                $upload_dir = wp_upload_dir();

                // Download the image from URL and save it to the upload directory
                $image_data = file_get_contents( $image_url );

                if ( $image_data !== false ) {
                    $image_file = $upload_dir['path'] . '/' . $image_name;
                    file_put_contents( $image_file, $image_data );

                    // Prepare image data to be attached to the product
                    $file_path = $upload_dir['path'] . '/' . $image_name;
                    $file_name = basename( $file_path );

                    // Insert the image as an attachment
                    $attachment = [
                        'post_mime_type' => mime_content_type( $file_path ),
                        'post_title'     => preg_replace( '/\.[^.]+$/', '', $file_name ),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    ];

                    $attach_id = wp_insert_attachment( $attachment, $file_path, $product_id );

                    // Add the image to the product gallery
                    $gallery_ids   = get_post_meta( $product_id, '_product_image_gallery', true );
                    $gallery_ids   = explode( ',', $gallery_ids );
                    $gallery_ids[] = $attach_id;
                    update_post_meta( $product_id, '_product_image_gallery', implode( ',', $gallery_ids ) );

                    set_post_thumbnail( $product_id, $attach_id );

                }
            }

            return "Product Inserted Successfully";
        }
    }
}
add_shortcode( 'insert_product_api', 'product_insert_woocommerce' );