<?php

if (function_exists('wp_next_scheduled')) {
    if (!wp_next_scheduled('inventory_sync_hourly')) {
        wp_schedule_event(time(), 'hourly', 'inventory_sync_hourly');
    }
}

function inventory_sync()
{
    $lock_key = 'inventory_sync_lock';
    try {
        if (get_transient($lock_key)) {
            debug_log('Zoho inventory sync is already running');
            exit;
        }
        set_transient($lock_key, true, 3600);
        debug_log('Synchronize Zoho inventory...');
        $access_token = getAccessToken(ZOHO_CLIENT_ID, ZOHO_CLIENT_SECRET, ZOHO_REFRESH_TOKEN);
        $zoho_products = fetch_zoho_products($access_token);
        foreach ($zoho_products as $item) {
            if (!in_array($item['sku'], ZOHO_INVENTORY_NON_PRODUCTS))
                create_or_update_wc_product($item, $access_token);
        }
        debug_log('Zoho Inventory sync complete');
    } finally {
        delete_transient($lock_key);
    }
}

if (function_exists('add_action'))
    add_action('inventory_sync_hourly', 'inventory_sync');

function fetch_zoho_products($access_token, $page = 1, $per_page = 200)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => ZOHO_API_DOMAIN . "inventory/v1/items?page=$page&per_page=$per_page",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Zoho-oauthtoken ' . $access_token
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    // Parse the JSON response
    $data = json_decode($response, true);
    $items = $data['items'];

    debug_log("Fetched " . count($items) . " items from Zoho");

    if ($data['page_context']['has_more_page']) {
        $items = array_merge($items, fetch_zoho_products($access_token, $page + 1, $per_page));
    }

    return $items;
}

function update_product_attributes($product, $attributes_array)
{
    $product_attributes = array();

    foreach ($attributes_array as $name => $value) {
        $slug = sanitize_title($name);

        // Check if the attribute taxonomy exists, and create it if it doesn't
        if (!taxonomy_exists($slug)) {
            register_taxonomy(
                $slug,
                'product',
                array(
                    'label' => $name,
                    'hierarchical' => false,
                    'labels' => array(
                        'name' => $name
                    )
                )
            );
        }

        $attribute = new WC_Product_Attribute();
        $attribute->set_id(0);
        $attribute->set_name($slug);
        $attribute->set_options(explode('|', $value));
        $attribute->set_visible(false);
        $attribute->set_variation(false);

        $product_attributes[$slug] = $attribute;
    }

    $product->set_attributes($product_attributes);
}


function create_or_update_wc_product($item, $access_token)
{
    if (!$item['sku'])
        return;
    $product_id = wc_get_product_id_by_sku($item['sku']);
    $product = $product_id ? wc_get_product($product_id) : new WC_Product_Simple();

    // to get item stock, we need to retrieve the item specifically
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => ZOHO_API_DOMAIN . "inventory/v1/items/" . $item["item_id"] . "?organization_id=" . ZOHO_INVENTORY_ORGANIZATION_ID,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Zoho-oauthtoken ' . $access_token
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $item_details = json_decode($response, true)['item'];

    // deactivated in Zoho - delete in WordPress
    // 19.01.2024 disabled, we suspect that Active status is controlled by some Zoho logic
//    if ($item_details['status'] != 'active') {
//        if ($product_id) {
//            $product->delete();
//        }
//        return;
//    }
    $product->set_name($item['name']);
    // 30.10.2023 disabled, descriptions are handled by WP
    // $product->set_short_description($item['description']);
    $product->set_status("publish");

    // 30.10.2023 disabled, descriptions are handled by WP
    //$product->set_regular_price($item_details['rate']);

    // 30.10.2023 disabled, prices are handled by WP
    // Inventory sales_rate is not discounts, it's the price you sell at
    // $product->set_sale_price($item_details['sales_rate']);
    // $product->set_price($item_details['rate']);
    $product->set_sku($item['sku']);
    // set overall stock
    $product->set_stock_quantity($item_details['available_stock']);
    $product->set_stock_status($item_details['available_stock'] > 0 ? 'instock' : 'outofstock');
    $product->set_manage_stock(true);
    // set stock per warehouse
    foreach ($item_details['warehouses'] as $warehouse) {
        switch ($warehouse['warehouse_name']) {
            case "Harvest Sydney Metro":
                update_post_meta($product_id, '_stock_sydney',$warehouse['warehouse_available_for_sale_stock']);
                break;
            case "Harvest Melbourne Metro":
                update_post_meta($product_id, '_stock_melbourne',$warehouse['warehouse_available_for_sale_stock']);
                break;
            case "Harvest Brisbane Metro":
                update_post_meta($product_id, '_stock_brisbane',$warehouse['warehouse_available_for_sale_stock']);
                break;
        }
    }
    // need to save in case product is new
    if (!$product_id) {
        $product->save();
        $product_id = $product->get_id();
    }
//    // set categories
//    $category_name = $item_details['category_name'];
//    if ($category_name) {
//        $category = get_term_by('name', $category_name, 'product_cat');
//        if ($category === false) {
//            $new_category = wp_insert_term(
//                $category_name,
//                'product_cat',
//                array(
//                    'slug' => sanitize_title($category_name),
//                )
//            );
//            $category_id = $new_category['term_id'];
//        } else {
//            $category_id = $category->term_id;
//        }
//        wp_set_object_terms($product_id, $category_id, 'product_cat');
//    }
    // set custom post meta to use without WC - the fields also used by prescription logic, do not disable!
    $attrs = array();
    foreach ($item_details['custom_fields'] as $field) {
        update_post_meta($product_id, $field['label'], $field['value']);
        $attrs[$field['label']] = $field['value'];
    }
//    // set attributes
//    update_product_attributes($product, $attrs);

    // set brands
    if ($item_details['brand'] != "") {
        update_post_meta($product_id, 'sold_by', $item_details['brand']);
        $attrs['Sold By'] = $item_details['brand'];
    }


    // 30.10.2023 disabled, images are handled by WP
//    // Handle product images
//    if (!empty($item_details['documents'])) {
//        $image_file_types = array('jpg', 'jpeg', 'jpe', 'png', 'gif', 'webp');
//        $is_first = true;
//        $gallery_cleared = false;
//        $existing_attachments = $product->get_gallery_image_ids();
//        array_unshift($existing_attachments, $product->get_image_id());
//        $new_attachments = array();
//        foreach ($item_details['documents'] as $document) {
//            if (in_array($document['file_type'], $image_file_types)) {
//                $zoho_image_filename = $document['file_name'];
//                $attach_id = null;
//                $attachment_exist = false;
//                foreach ($existing_attachments as $attachment) {
//                    $orig_filename = get_post_meta($attachment, "orig_filename", true);
//                    if ($orig_filename == $zoho_image_filename) {
//                        $attach_id = $attachment;
//                        $attachment_exist = true;
//                        debug_log("product $product_id attachment already exist: $zoho_image_filename");
//                        break;
//                    }
//                }
//                if (!$attachment_exist) {
//                    // Construct the image URL
//                    $image_url = ZOHO_INVENTORY_SITE . 'DocTemplates_ItemImage_' . $document['document_id'] . '.zbfs?organization_id=' . ZOHO_INVENTORY_ORGANIZATION_ID;
//                    debug_log("getting item $product_id image $zoho_image_filename from url $image_url");
//                    $image_response = wp_remote_get($image_url, [
//                        'headers' => array(
//                            'Authorization' => 'Zoho-oauthtoken ' . $access_token
//                        )
//                    ]);
//
//                    if (is_wp_error($image_response)) {
//                        debug_log('Failed to download image: ' . $image_response->get_error_message());
//                        continue;
//                    }
//
//                    $image_bits = wp_remote_retrieve_body($image_response);
//
//                    // Upload the image into WordPress media library
//                    $upload = wp_upload_bits($zoho_image_filename, null, $image_bits);
//                    if ($upload['error']) {
//                        debug_log('Upload error: ' . $upload['error']);
//                        continue;
//                    }
//                    $uploaded_filename = $upload['file'];
//                    $wp_filetype = wp_check_filetype($uploaded_filename, null);
//                    $attachment = array(
//                        'post_mime_type' => $wp_filetype['type'],
//                        'post_title' => sanitize_file_name($uploaded_filename),
//                        'post_content' => '',
//                        'post_status' => 'inherit',
//                        'orig_filename' => $zoho_image_filename
//                    );
//
//                    // Generate the metadata for the attachment and update the database record
//                    $attach_id = wp_insert_attachment($attachment, $uploaded_filename, $product->get_id());
//                    update_post_meta($attach_id, 'orig_filename', $zoho_image_filename);
//                    require_once(ABSPATH . 'wp-admin/includes/image.php');
//                    $attach_data = wp_generate_attachment_metadata($attach_id, $uploaded_filename);
//                    wp_update_attachment_metadata($attach_id, $attach_data);
//                }
//                $new_attachments[] = $attach_id;
//            } else {
//                debug_log('Invalid file type.');
//            }
//        }
//        // replace images
//        $product->set_image_id($new_attachments[0]);
//        $product->set_gallery_image_ids(array_slice($new_attachments, 1));
//        // delete everything else
//        foreach ($existing_attachments as $attachment) {
//            if (!in_array($attachment, $new_attachments)) {
//                debug_log("delete image $attachment of product $product_id");
//                wp_delete_attachment($attachment, true);
//            }
//        }
//    }
    $product->save();
}
