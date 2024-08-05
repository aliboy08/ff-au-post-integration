<?php
if ( ! defined( 'ABSPATH' ) ) die();

class FF_AU_Post {

    public function __construct(){
        add_filter('woocommerce_package_rates', [ $this, 'add_au_post_rates' ], 20, 2);
    }

    public function add_au_post_rates( $rates, $package ){
        
        $is_active = false;
        
        foreach( $rates as $rate_id => $rate ) {
            $label = $rate->get_label();
            if( $label != 'FF Australia Post' ) continue;
            if( !$this->enable_rate() ) continue;
            
            $au_post_data = $this->au_post_get_data();
            if( !$au_post_data ) continue;

            $is_active = true;
            
            if( $au_post_data['service_name'] ) {
                $label = $au_post_data['service_name'] . ' ('. $au_post_data['delivery_time'] .')';
            }
            
            $label = apply_filters( 'ff_au_post_shipping_label', $label );
            $rate->set_label( $label );

            if( $au_post_data['total'] ) {
                $rate->set_cost( $au_post_data['total'] );
            }
            
        }

        if( $is_active ) {
            $rates = $this->disable_other_shipping_methods($rates);
        }
        
        return $rates;
        
    }

    public function au_post_get_data(){
        $total = 0;
        $products = $this->get_cart_items_data();

        $shipping_address = WC()->customer->get_shipping();
        $postcode_to = $shipping_address['postcode'];
        if( !$postcode_to ) return false; 

        $postcode_from = get_option('woocommerce_store_postcode');
        $postcode_from = apply_filters( 'ff_au_post_postcode_from', $postcode_from );
    
        $service_name = '';
        $delivery_time = '';

        foreach( $products as $product ) {

            $item_fee_data = $this->au_post_api_get_data($product, $postcode_from, $postcode_to);
            if( !$item_fee_data ) continue; 

            if( !$service_name ) $service_name = $item_fee_data['service'];
            if( !$delivery_time ) $delivery_time = $item_fee_data['delivery_time'];
            $total += $item_fee_data['total_cost'] * $product['quantity'];
        }
    
        return [
            'total' => $total,
            'service_name' => $service_name,
            'delivery_time' => $delivery_time,
        ];
    }

    public function get_cart_items_data(){
        $products = [];

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product = [
                'id' => $cart_item['data']->get_id(),
                'name' => $cart_item['data']->get_name(),
                'height' => $cart_item['data']->get_height(),
                'width' => $cart_item['data']->get_width(),
                'length' => $cart_item['data']->get_length(),
                'weight' => $cart_item['data']->get_weight(),
                'quantity' => $cart_item['quantity'],
            ];
            $products[] = $product;
        }

        return $products;
    }
    
    public function au_post_api_get_data( $product, $postcode_from, $postcode_to ){
        
        $api_key = '150d8ca7-9ea7-4425-8ecd-97e9a9094ed7';
        $api_key = apply_filters( 'ff_au_post_api_key', $api_key );
     
        $p_length = $product['length'];
        $p_width = $product['width'];
        $p_height = $product['height'];
        $p_weight = $product['weight'];
    
        // Limits
        if( $p_weight > 22 ) {
            $p_weight = 22;
        }
        $cubic_meter = ($p_length / 100) * ($p_width / 100) * ($p_height / 100);
        if ($cubic_meter > 0.24) {
            $p_length = 24;
            $p_width = 100;
            $p_height = 100;
        }
    
        $query_params = [
            "from_postcode" => $postcode_from,
            "to_postcode" => $postcode_to,
            "length" => $p_length,
            "width" => $p_width,
            "height" => $p_height,
            "weight" => $p_weight,
            "service_code" => 'AUS_PARCEL_REGULAR',
        ];

        $query_url = 'https://digitalapi.auspost.com.au/postage/parcel/domestic/calculate.json?'. http_build_query($query_params);
    
        $request_context = stream_context_create([
            'http' => [
                'header' => 'AUTH-KEY: ' . $api_key,
                'method' => 'GET',
            ],
        ]);
    
        $result = file_get_contents($query_url, false, $request_context);
        $result = json_decode($result, true);
        if( !$result ) return false; 
    
        return $result['postage_result'];
    }

    public function cart_items_contains_shipping_class( $search_ids ){
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $item_shipping_classes = wc_get_product_terms( $cart_item['product_id'], 'product_shipping_class' );
            if( !$item_shipping_classes ) continue;
            foreach( $item_shipping_classes as $term ) {
                if( in_array( $term->term_id, $search_ids ) ) {
                    return true;
                }
            }
        }
        return false;
    }

    public function enable_rate(){

        if( !get_option('au_post_enable_on_shipping_classes') ) return true;

        $shipping_classes = get_option('au_post_shipping_classes');
        if( !$shipping_classes ) return true;

        if( !$this->cart_items_contains_shipping_class( $shipping_classes ) ) {
            return false;
        }

        return true;
    }

    public function disable_other_shipping_methods($rates){
        if( !get_option('au_post_disable_other_methods') ) return $rates;

        $disable_rates = get_option('au_post_disable_other_methods_names');
        
        $enabled_methods = [];
        foreach( $rates as $rate_id => $rate ) {
            if( in_array( $rate->get_label(), $disable_rates ) ) continue;
            $enabled_methods[$rate_id] = $rate;
        }
        
        return $enabled_methods;
    }

}