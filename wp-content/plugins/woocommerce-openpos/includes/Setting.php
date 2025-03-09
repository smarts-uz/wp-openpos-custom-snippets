<?php
if(!class_exists('Openpos_Setting'))
{
    class Openpos_Setting
    {
       
        public function __construct()
        {
            
        }
        function get_settings_sections() {
            $sections = array(
                array(
                    'id'    => 'openpos_general',
                    'title' => __( 'General', 'openpos' )
                ),
                array(
                    'id'    => 'openpos_payment',
                    'title' => __( 'Payment', 'openpos' )
                ),
                array(
                    'id'    => 'openpos_shipment',
                    'title' => __( 'Shipping', 'openpos' )
                ),
                array(
                    'id'    => 'openpos_label',
                    'title' => __( 'Barcode Label', 'openpos' )
                ),
                array(
                    'id'    => 'openpos_receipt',
                    'title' => __( 'Receipt', 'openpos' )
                ),
                array(
                    'id'    => 'openpos_pos',
                    'title' => __( 'POS Layout', 'openpos' )
                )
            );
            
            $sections[] = array(
                'id'    => 'openpos_addon',
                'title' => __( 'Add-on', 'openpos' )
            );
        
            return $sections;
        }
    
        
    }
    
}
