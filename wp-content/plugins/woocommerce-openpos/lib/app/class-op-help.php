<?php
class OpHelpApp extends OP_App_Abstract implements OP_App {
    public $key = 'op_help_app'; // unique
    public $name = 'Help';
    public $thumb = OPENPOS_URL.'/assets/images/help.png';
    public function render()
    {
        
        header('X-Frame-Options: allow-from *');
        $session = $this->get_session();
        require_once OPENPOS_DIR.'/templates/help.php';

        // Add quantity input field and dynamic price update script
        echo '<input type="number" id="quantity" name="quantity" min="1" value="1" />';
        echo '<script>
            document.getElementById("quantity").addEventListener("input", function() {
                var quantity = this.value;
                // Assuming a function updateTotalPrice exists to update the total price
                updateTotalPrice(quantity);
            });
        </script>';
    }
    public function get_name()
    {
        return __( 'Help', 'openpos' );
    }

}