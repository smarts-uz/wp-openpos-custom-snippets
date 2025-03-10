<?php
/**
 * Created by PhpStorm.
 * User: anhvnit
 * Date: 10/19/18
 * Time: 17:57
 */
class OP_Woocommerce_Product_Addons{
    public $plugin_path = '';
    public function init(){
        $this->plugin_path = WP_CONTENT_DIR.'/plugins/woocommerce-product-addons/woocommerce-product-addons.php';
        if(!file_exists($this->plugin_path))
        {
            $this->plugin_path = dirname(OPENPOS_DIR ).'/woocommerce-product-addons/woocommerce-product-addons.php';
        }
        if(file_exists( $this->plugin_path))
        {
            $this->wc_addons();
        }

    }
    // woocommerce global addons
    public function wc_addons(){
        if(is_plugin_active( 'woocommerce-product-addons/woocommerce-product-addons.php' ))
        {
            if(!class_exists('WC_Product_Addons') )
            {
                require_once $this->plugin_path;
            }
            add_filter('op_product_data',[$this,'wc_product_addons'],10,3);

            add_filter('op_get_online_order_data',[$this,'op_get_online_order_data'],10,1);
        }

    }
    public function wc_product_addons($response_data,$_product,$warehouse_id){
        $options = array();
        $addons = array();
        $product_id = $_product->ID;
        $product = wc_get_product($_product->ID);

        $type = $product->get_type();
        if($type == 'variation')
        {
            $product_id = $product->get_parent_id();

        }
        if(function_exists('get_product_addons'))
        {
            $addons = get_product_addons($product_id);
        }else{
            $addons = WC_Product_Addons_Helper::get_product_addons( $product_id, false, false, true );
        }

        $display_mode = wc_prices_include_tax();


        foreach($addons as $a)
        {
           
            if(in_array($a['type'], array('radiobutton','select','checkbox','multiple_choice','custom_text','input_multiplier')))
            {
                if($a['type'] == 'custom_text')
                {
                    $a['type'] = 'text';
                }
                if($a['type'] == 'multiple_choice')
                {
                    if($a['display'] == 'images')
                    {
                        $a['type'] =  'radiobutton';
                    }else{
                        $a['type'] = $a['display'];
                    }
                }

                if($a['type'] == 'radiobutton')
                {
                    $a['type'] = 'radio';
                }

                

                $radio = array(
                    'label' => $a['name'],
                    'option_id' => isset($a['field-name']) ? $a['field-name']: $a['field_name'],
                    'type' => $a['type'],
                    'require' => $a['required'],
                    'options' => array(),
                    'setting' => array()
                );
                $is_include = false;
                if($a['type'] == 'input_multiplier')
                {
                   
                    $radio['setting'] = array(
                        'restrictions' => isset($a['restrictions']) ? $a['restrictions'] : 0, // min / max enable 
                        'min' => isset($a['min']) ? $a['min'] : 0,
                        'max' => isset($a['max']) ? $a['max'] : null,
                        'price_type' => isset($a['price_type']) ? $a['price_type'] : 'quantity_based',
                        'price' => isset($a['price']) ? $a['price'] : 0,
                    );
                    
                }else{
                    foreach($a['options'] as $key => $a_option)
                    {
                        $a_price = $a_option['price'];
                        if($a_price)
                        {
                            if( $display_mode )
                            {
                                $product = wc_get_product($_product->ID);
                                
                                $tax_rates = array();
                                if( isset($response_data['tax']) && !empty($response_data['tax']))
                                {
                                    foreach($response_data['tax'] as $r)
                                    {
                                        $key = $r['code'];
                                        $tax_rates[$key] = $r;
                                    }
                                }
                                if(!empty($tax_rates))
                                {
                                    // $tax_amount = array_sum(@WC_Tax::calc_tax( $a_price, $tax_rates, wc_prices_include_tax()  ));
                                    // $a_price -= $tax_amount;
                                }
                            }
                        }else{
                            $a_price = 0;
                        }
                        
                        $cost_type = $a_option['price_type'];
                        
                        $tmp = array(
                            'value_id' => $a_option['label'],
                            'label' => $a_option['label'],
                            'cost' => 1*$a_price,
                            'cost_type' => $cost_type,
                        );
                        if($a_price)
                        {
                            $tmp['label'] = $a_option['label'].' ('.html_entity_decode(strip_tags(wc_price($a_price))).')';
                        }
    
                        $radio['options'][] = $tmp;
                    }
                }

               
                
                
                $response_data['options'][] = $radio;
            }

        }
//            $radio = array(
//                'label' => "Radio Label",
//                'option_id' => 1,
//                'type' => 'radio',
//                'require' => true,
//                'options' => array(
//                   ['value_id' => 1, 'label' => 'radio value 1','cost' => 5],  // cost_type support
  //                 ['value_id' => 2, 'label' => 'radio value 1','cost' => 5, 'cost_type'=> 'discount_percent'],  // cost_type support discount_percent, discount_fixed, quantity_based,percentage_based
   //                ['value_id' => 4, 'label' => 'radio value 2','cost' => 6, 'cost_type'=> 'discount_fixed'],
    //               ['value_id' => 7, 'label' => 'radio value 3','cost' => 7],            
//                )
//            );
//            $options[]= $radio;
//            $response_data['options'] = $options;
        return $response_data;
    }
    public function op_get_online_order_data($order_data){
        $source_type = isset($order_data['source_type']) ? $order_data['source_type'] : '';
        
        $items = $order_data['items'];
        $order_id = $order_data['id'];
        $order_items = array();
        $order= wc_get_order($order_id);
        
        
        foreach($items as $item)
        {
            $item_id = $item['item_id'] ? $item['item_id'] : $item['id'];
            $_item = $order->get_item($item_id);
            if(!$_item)
            {
                continue;
            }
            $product_data = $item['product'];
            $product_options = isset($product_data['options']) ? $product_data['options'] : array();
            if($source_type != 'openpos')
            {
                if($_wc_pao_addon_field_type = $_item->get_meta('_wc_pao_addon_field_type',true))
                {
                        $_wc_pao_addon_value = $_item->get_meta('_wc_pao_addon_value',true);  
                        $_wc_pao_addon_name = $_item->get_meta('_wc_pao_addon_name',true);  
                }else{
                    $option_pass = true;
                    $option_total = 0;
                    $item_option = array();
                    
                    if( !empty($product_options) && $options = $_item->get_meta('_wc_pao_attached_addons',true))
                    {
                        
                        foreach($options as $op)
                        {
                            $product_option = array();
                            foreach($product_options as $product_op)
                            {
                                    if($product_op['option_id'] == $op['field_name']){
                                    $product_option = $product_op;
                                    }
                            }
                            if(!empty($product_option))
                            {
                                $product_option_value = array();
                                $value_label = array();
                                $value_id = array();
                                
                                $option_id = $product_option['option_id'];
                                $cost = 0;
                                if(isset($item_option[$option_id]))
                                {
                                    $value_label = $item_option[$option_id]['value_label'];
                                    $value_id = $item_option[$option_id]['value_id'];
                                    $cost = $item_option[$option_id]['cost'];
                                }

                                $value_id[] = $op['value'];
                                foreach($product_option['options'] as $p_o)
                                {
                                    if($p_o['value_id'] == $op['value'])
                                    {
                                        
                                        $value_label[] = $p_o['label'];
                                        $product_option_value = $p_o;
                                    }
                                }
                                
                                $cost += isset($product_option_value['cost']) ? $product_option_value['cost'] : 0;
                                $option_total += $cost;
                                $tmp_option = array(
                                    'cost' => $cost,
                                    'option_id'=> $op['field_name'],
                                    'required'=> $product_option['require'],
                                    'title'=> $op['name'],
                                    'type'=> $product_option['type'],
                                    'value_id'=> $value_id,
                                    'value_label'=> $value_label,
                                );
                                $item_option[$option_id] = $tmp_option;
                            }
                            
                        }
                        $item['options'] = array_values($item_option);
                        $item['option_total'] = $option_total;
                        $item['option_pass'] = $option_pass;
                    }
                   
                }
            }
            $order_items[] = $item;
            
            
            
        }
        
        $order_data['items'] = $order_items; 
        
        
        
        
        return $order_data;
    }
}