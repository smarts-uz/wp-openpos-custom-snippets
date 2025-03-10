<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * weDevs Settings API wrapper class
 *
 * @version 1.3 (27-Sep-2016)
 *
 * @author Tareq Hasan <tareq@weDevs.com>
 * @link https://tareq.co Tareq Hasan
 * @example example/oop-example.php How to use the class
 */
if ( !class_exists( 'OP_Settings' ) ):
class OP_Settings {

    /**
     * settings sections array
     *
     * @var array
     */
    protected $settings_sections = array();

    /**
     * Settings fields array
     *
     * @var array
     */
    protected $settings_fields = array();

    public function __construct() {

    }

    /**
     * Enqueue scripts and styles
     */
    function admin_enqueue_scripts() {
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_media();
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_script( 'jquery' );

        wp_enqueue_style( 'jquery.tagthis',OPENPOS_URL.'/assets/css/tokenize2.min.css' );
        wp_enqueue_script('jquery.tagthis',OPENPOS_URL.'/assets/js/tokenize2.min.js',array('jquery'));

    }

    /**
     * Set settings sections
     *
     * @param array   $sections setting sections array
     */
    function set_sections( $sections ) {
        $this->settings_sections = $sections;

        return $this;
    }

    /**
     * Add a single section
     *
     * @param array   $section
     */
    function add_section( $section ) {
        $this->settings_sections[] = $section;

        return $this;
    }

    /**
     * Set settings fields
     *
     * @param array   $fields settings fields array
     */
    function set_fields( $fields ) {
        $this->settings_fields = $fields;

        return $this;
    }

    public function get_fields()
    {
        return $this->settings_fields;
    }
    public function get_sections()
    {
         return $this->settings_sections;
    }

    function add_field( $section, $field ) {
        $defaults = array(
            'name'  => '',
            'label' => '',
            'desc'  => '',
            'type'  => 'text'
        );

        $arg = wp_parse_args( $field, $defaults );
        $this->settings_fields[$section][] = $arg;

        return $this;
    }

    /**
     * Initialize and registers the settings sections and fileds to WordPress
     *
     * Usually this should be called at `admin_init` hook.
     *
     * This function gets the initiated settings sections and fields. Then
     * registers them to WordPress and ready for use.
     */
    function admin_init() {
        //register settings sections
        foreach ( $this->settings_sections as $section ) {
            if ( false == get_option( $section['id'] ) ) {
                add_option( $section['id'] );
            }

            if ( isset($section['desc']) && !empty($section['desc']) ) {
                $section['desc'] = '<div class="inside">' . $section['desc'] . '</div>';
                $callback = create_function('', 'echo "' . str_replace( '"', '\"', $section['desc'] ) . '";');
            } else if ( isset( $section['callback'] ) ) {
                $callback = $section['callback'];
            } else {
                $callback = null;
            }

            add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
        }

        //register settings fields
        foreach ( $this->settings_fields as $section => $field ) {
            foreach ( $field as $option ) {
                if(empty($option))
                {
                    continue;
                }
                $name = isset($option['name']) ? $option['name'] : '';
                $type = isset( $option['type'] ) ? $option['type'] : 'text';
                $label = isset( $option['label'] ) ? $option['label'] : '';
                $callback = isset( $option['callback'] ) ? $option['callback'] : array( $this, 'callback_' . $type );

                $args = array(
                    'id'                => $name,
                    'class'             => isset( $option['class'] ) ? $option['class'] : $name,
                    'label_for'         => "{$section}[{$name}]",
                    'desc'              => isset( $option['desc'] ) ? $option['desc'] : '',
                    'name'              => $label,
                    'section'           => $section,
                    'size'              => isset( $option['size'] ) ? $option['size'] : null,
                    'options'           => isset( $option['options'] ) ? $option['options'] : '',
                    'std'               => isset( $option['default'] ) ? $option['default'] : '',
                    'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
                    'type'              => $type,
                    'placeholder'       => isset( $option['placeholder'] ) ? $option['placeholder'] : '',
                    'min'               => isset( $option['min'] ) ? $option['min'] : '',
                    'max'               => isset( $option['max'] ) ? $option['max'] : '',
                    'step'              => isset( $option['step'] ) ? $option['step'] : '',
                );

                add_settings_field( "{$section}[{$name}]", $label, $callback, $section, $section, $args );
            }
        }

        // creates our settings in the options table
        foreach ( $this->settings_sections as $section ) {
            register_setting( $section['id'], $section['id'], array( $this, 'sanitize_options' ) );
        }
    }

    /**
     * Get field description for display
     *
     * @param array   $args settings field args
     */
    public function get_field_description( $args ) {
        if ( ! empty( $args['desc'] ) ) {
            $desc = sprintf( '<p class="description">%s</p>', $args['desc'] );
        } else {
            $desc = '';
        }

        return $desc;
    }

    /**
     * Displays a text field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_text( $args ) {

        $value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $type        = isset( $args['type'] ) ? $args['type'] : 'text';
        $placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

        $html        = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder );
        $html       .= $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Displays a url field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_url( $args ) {
        $this->callback_text( $args );
    }

    /**
     * Displays a number field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_number( $args ) {
        $value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $type        = isset( $args['type'] ) ? $args['type'] : 'number';
        $placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
        $min         = empty( $args['min'] ) ? '' : ' min="' . $args['min'] . '"';
        $max         = empty( $args['max'] ) ? '' : ' max="' . $args['max'] . '"';
        $step        = empty( $args['max'] ) ? 'step="any"' : ' step="' . $args['step'] . '"';

        $html        = sprintf( '<input type="%1$s" class="%2$s-number" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s%7$s%8$s%9$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder, $min, $max, $step );
        $html       .= $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Displays a checkbox for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_checkbox( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

        $html  = '<fieldset>';
        $html  .= sprintf( '<label for="wpuf-%1$s[%2$s]">', $args['section'], $args['id'] );
        $html  .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'] );
        $html  .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s]" name="%1$s[%2$s]" value="on" %3$s />', $args['section'], $args['id'], checked( $value, 'on', false ) );
        $html  .= sprintf( '%1$s</label>', $args['desc'] );
        $html  .= '</fieldset>';

        echo $html;
    }
    

    /**
     * Displays a multicheckbox for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_multicheck( $args ) {

        $value = $this->get_option( $args['id'], $args['section'], $args['std'] );
        $html  = '<fieldset>';
        $html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="" />', $args['section'], $args['id'] );
        foreach ( $args['options'] as $key => $label ) {
            $checked = isset( $value[$key] ) ? $value[$key] : '0';
            $html    .= sprintf( '<label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
            $html    .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ) );
            $html    .= sprintf( '%1$s</label><br>',  $label );
        }

        $html .= $this->get_field_description( $args );
        $html .= '</fieldset>';

        echo $html;
    }

    function callback_multicheck_sortable( $args ) {

        $value = $this->get_option( $args['id'], $args['section'], $args['std'] );
        $html  = '<ul class="op-sortable multicheck-sortable">';
        $html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="" />', $args['section'], $args['id'] );
        $active_options = array();
        foreach($value as $key => $v)
        {
            if(isset($args['options'][$key]))
            {
                $active_options[$key] = $args['options'][$key];
            }
        }
        foreach ( $active_options as $key => $label ) {
            
            $checked = isset( $value[$key] ) ? $value[$key] : '0';
            $html .= '<li>';
            $html .= '<span class="dashicons dashicons-editor-justify"></span>';
            $html    .= sprintf( '<label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
            $html    .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ) );
            $html    .= sprintf( '%1$s</label><br>',  $label );
            $html .= "</li>";
        }
        foreach ( $args['options'] as $key => $label ) {
            if(isset($active_options[$key]))
            {
                continue;
            }
            $checked = isset( $value[$key] ) ? $value[$key] : '0';
            $html .= '<li>';
            $html .= '<span class="dashicons dashicons-editor-justify"></span>';
            $html    .= sprintf( '<label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
            $html    .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ) );
            $html    .= sprintf( '%1$s</label><br>',  $label );
            $html .= "</li>";
        }

        $html .= $this->get_field_description( $args );
        $html .= '</ul>';

        echo $html;
    }
    /**
     * Displays a radio button for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_radio( $args ) {

        $value = $this->get_option( $args['id'], $args['section'], $args['std'] );
        $html  = '<fieldset>';

        foreach ( $args['options'] as $key => $label ) {
            $html .= sprintf( '<label for="wpuf-%1$s[%2$s][%3$s]">',  $args['section'], $args['id'], $key );
            $html .= sprintf( '<input type="radio" class="radio" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ) );
            $html .= sprintf( '%1$s</label><br>', $label );
        }

        $html .= $this->get_field_description( $args );
        $html .= '</fieldset>';

        echo $html;
    }

    /**
     * Displays a selectbox for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_select( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $html  = sprintf( '<select class="%1$s" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );
        foreach ( $args['options'] as $key => $label ) {
            $html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
        }

        $html .= sprintf( '</select>' );
        $html .= $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Displays a textarea for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_textarea( $args ) {

        $value       = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="'.$args['placeholder'].'"';

        $html        = sprintf( '<textarea rows="5" cols="55" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]"%4$s>%5$s</textarea>', $size, $args['section'], $args['id'], $placeholder, $value );
        $html        .= $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Displays the html for a settings field
     *
     * @param array   $args settings field args
     * @return string
     */
    function callback_html( $args ) {
        echo $this->get_field_description( $args );
    }

    /**
     * Displays a rich text textarea for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_wysiwyg( $args ) {

        $value = $this->get_option( $args['id'], $args['section'], $args['std'] );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : '500px';

        echo '<div style="max-width: ' . $size . ';">';

        $editor_settings = array(
            'teeny'         => true,
            'textarea_name' => $args['section'] . '[' . $args['id'] . ']',
            'textarea_rows' => 10
        );

        if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
            $editor_settings = array_merge( $editor_settings, $args['options'] );
        }

        wp_editor( $value, $args['section'] . '-' . $args['id'], $editor_settings );

        echo '</div>';

        echo $this->get_field_description( $args );
    }

    /**
     * Displays a file upload field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_file( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $id    = $args['section']  . '[' . $args['id'] . ']';
        $label = isset( $args['options']['button_label'] ) ? $args['options']['button_label'] : __( 'Choose File' );

        $html  = sprintf( '<input type="text" class="%1$s-text wpsa-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
        $html  .= '<input type="button" class="button wpsa-browse" value="' . $label . '" />';
        $html  .= $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Displays a password field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_password( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

        $html  = sprintf( '<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
        $html  .= $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Displays a color picker field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_color( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

        $html  = sprintf( '<input type="text" class="%1$s-text wp-color-picker-field" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s" />', $size, $args['section'], $args['id'], $value, $args['std'] );
        $html  .= $this->get_field_description( $args );

        echo $html;
    }


    /**
     * Displays a select box for creating the pages select box
     *
     * @param array   $args settings field args
     */
    function callback_pages( $args ) {

        $dropdown_args = array(
            'selected' => esc_attr($this->get_option($args['id'], $args['section'], $args['std'] ) ),
            'name'     => $args['section'] . '[' . $args['id'] . ']',
            'id'       => $args['section'] . '[' . $args['id'] . ']',
            'echo'     => 0
        );
        $html = wp_dropdown_pages( $dropdown_args );
        echo $html;
    }

    function callback_category_tags($args){

        $values =  $this->get_option( $args['id'], $args['section'], $args['std'] );




        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $html = '<div class="category-tag-container"><div class="category-tag-list">';
        $html  .= sprintf( '<select class="%1$s category_tags" multiple name="%2$s[%3$s][]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );
        $args['options'] = array();
        if(is_array($values))
        {
            foreach ( $values as $key => $cat_id ) {
                $term = get_term_by( 'id', $cat_id, 'product_cat', 'ARRAY_A' );
                if($term)
                {
                    $html .= sprintf( '<option value="%s"%s>%s</option>', $cat_id, 'selected', $term['name'] );
                }

            }
        }

        $html .= sprintf( '</select>' );
        $html .= sprintf( '</div><div class="choose-btn"><a data-toggle="modal" data-target="#modal-cat-categories" href="#" class="choose-category-tag-btn"><span class="glyphicon glyphicon-list-alt"></span></a></div></div>' );
        $html .= $this->get_field_description( $args );



        echo $html;
    }

    /**
     * Sanitize callback for Settings API
     *
     * @return mixed
     */
    function sanitize_options( $options ) {

        if ( !$options ) {
            return $options;
        }

        foreach( $options as $option_slug => $option_value ) {
            $sanitize_callback = $this->get_sanitize_callback( $option_slug );

            // If callback is set, call it
            if ( $sanitize_callback ) {
                $options[ $option_slug ] = call_user_func( $sanitize_callback, $option_value );
                continue;
            }
        }

        return $options;
    }

    /**
     * Get sanitization callback for given option slug
     *
     * @param string $slug option slug
     *
     * @return mixed string or bool false
     */
    function get_sanitize_callback( $slug = '' ) {
        if ( empty( $slug ) ) {
            return false;
        }

        // Iterate over registered fields and see if we can find proper callback
        foreach( $this->settings_fields as $section => $options ) {
           
            foreach ( $options as $option ) {
                if ( !isset($option['name']) || $option['name'] != $slug ) {
                    continue;
                }

                // Return the callback name
                return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
            }
        }

        return false;
    }

    /**
     * Get the value of a settings field
     *
     * @param string  $option  settings field name
     * @param string  $section the section name this field belongs to
     * @param string  $default default text if it's not found
     * @return string
     */
    function get_option( $option, $section, $default = '' ) {

        $options = get_option( $section );


        if ( isset( $options[$option] ) ) {
            return $options[$option];
        }
        if($default)
        {
            return $default;
        }
        $sections = $this->get_fields();
        if ( isset( $sections[$section]) ) {
            foreach($sections[$section] as $s)
            {
                if ( isset($s['name']) && $s['name'] == $option && isset($s['default']) ) {
                    return $s['default'];
                }
            }

        }
        
        return $default;
    }
    function get_options(  $section ) {

        $options = get_option( $section );
        if(!$options){
            $options = array();
        }
        if(empty($options))
        {
            $fields = $this->get_fields();
            if(isset($fields[$section]))
            {
                foreach($fields[$section] as $s)
                {
                    if ( isset($s['name']) && isset($s['default']) ) {
                        $options[$s['name']] =  $s['default'];
                    }
                }
            }
        }
        return $options;
    }

    /**
     * Show navigations as tab
     *
     * Shows all the settings section labels as tab
     */
    function show_navigation() {
        $html = '<h2 class="nav-tab-wrapper openpos-nav-tab-wrapper">';

        $count = count( $this->settings_sections );
        $fields = $this->settings_fields;
        
        

        // don't show the navigation if only one section exists
        if ( $count === 1 ) {
            return;
        }

        foreach ( $this->settings_sections as $tab ) {
            if(isset($fields[$tab['id']]))
            {
                $html .= sprintf( '<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s</a>', $tab['id'], $tab['title'] );
            }
            
        }

        $html .= '</h2>';

        echo $html;
    }

    /**
     * Show the section settings forms
     *
     * This function displays every sections in a different form
     */
    function show_forms() {
        $fields = $this->settings_fields;
        ?>
        <div class="metabox-holder openpos-metabox-holder">
            <?php foreach ( $this->settings_sections as $form ) { ?>
                <?php  if(isset($fields[$form['id']])) : ?>
                    <div id="<?php echo $form['id']; ?>" class="group" style="display: none;">
                        <form method="post" action="options.php">
                            <?php
                            do_action( 'wsa_form_top_' . $form['id'], $form );
                            settings_fields( $form['id'] );
                            do_settings_sections( $form['id'] );
                            do_action( 'wsa_form_bottom_' . $form['id'], $form );
                            if ( isset( $this->settings_fields[ $form['id'] ] ) ):
                            ?>
                            <div style="padding-left: 10px">
                                <?php submit_button(); ?>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endif; ?>
            <?php } ?>
        </div>
        <?php
        $this->script();
    }

    /**
     * Tabbable JavaScript codes & Initiate Color Picker
     *
     * This code uses localstorage for displaying active tabs
     */
    function script() {
        $op_nonce = wp_create_nonce( 'op_nonce' );
        ?>
        <script>
            (function($) {
   
                $(document).ready(function($) {
                        //Initiate Color Picker
                        //$('.wp-color-picker-field').wpColorPicker();

                        var receipt_css = CodeMirror.fromTextArea(document.getElementById("openpos_receipt[receipt_css]"), {
                            mode: "text/css",
                            styleActiveLine: true,
                            lineNumbers: true,
                            lineWrapping: true,
                            autoRefresh: true
                        });
                        var custom_css = CodeMirror.fromTextArea(document.getElementById("openpos_pos[pos_custom_css]"), {
                            mode: "text/css",
                            styleActiveLine: true,
                            lineNumbers: true,
                            lineWrapping: true,
                            autoRefresh: true
                        });

                        // Switches option sections
                        $('.group').hide();
                        var activetab = '';
                        if (typeof(localStorage) != 'undefined' ) {
                            activetab = localStorage.getItem("activetab");
                        }
                        if (activetab != '' && $(activetab).length ) {
                            $(activetab).fadeIn();

                            receipt_css.refresh();
                            custom_css.refresh();

                        } else {
                            $('.group:first').fadeIn();

                            receipt_css.refresh();
                            custom_css.refresh();
                        }
                        $('.group .collapsed').each(function(){
                            $(this).find('input:checked').parent().parent().parent().nextAll().each(
                            function(){
                                if ($(this).hasClass('last')) {
                                    $(this).removeClass('hidden');
                                    return false;
                                }
                                $(this).filter('.hidden').removeClass('hidden');
                            });
                        });

                        if (activetab != '' && $(activetab + '-tab').length ) {
                            $(activetab + '-tab').addClass('nav-tab-active');
                        }
                        else {
                            $('.nav-tab-wrapper a:first').addClass('nav-tab-active');
                        }
                        $('.nav-tab-wrapper a').click(function(evt) {
                            $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                            $(this).addClass('nav-tab-active').blur();
                            var clicked_group = $(this).attr('href');
                            if (typeof(localStorage) != 'undefined' ) {
                                localStorage.setItem("activetab", $(this).attr('href'));
                            }
                            $('.group').hide();
                            $(clicked_group).fadeIn();

                            receipt_css.refresh();
                            custom_css.refresh();

                            evt.preventDefault();
                        });

                        $('.wpsa-browse').on('click', function (event) {
                            event.preventDefault();

                            var self = $(this);

                            // Create the media frame.
                            var file_frame = wp.media.frames.file_frame = wp.media({
                                title: self.data('uploader_title'),
                                button: {
                                    text: self.data('uploader_button_text'),
                                },
                                multiple: false
                            });

                            file_frame.on('select', function () {
                                attachment = file_frame.state().get('selection').first().toJSON();
                                self.prev('.wpsa-url').val(attachment.url).change();
                            });

                            // Finally, open the modal
                            file_frame.open();
                        });

                    // $('.category_tags').tagThis(); op_ajax_category
                        var object = $('.category_tags').tokenize2({
                            sortable: true,
                            dataSource: '<?php echo admin_url('admin-ajax.php?action=op_ajax_category&op_nonce='.$op_nonce); ?>'
                        });
                        $('.save-categories-popup').click(function(){
                            var categories = $('form#frm-list-categories').serializeArray();
                            for(var i =0;i < categories.length; i++){
                                var category = categories[i];
                                var select_cat = $('select[name="show_categories[]"]').find('option[value="'+category.value+'"]').first().text();
                                var object = $('.category_tags').tokenize2({
                                    sortable: true,
                                    dataSource: '<?php echo admin_url('admin-ajax.php?action=op_ajax_category&op_nonce='.$op_nonce); ?>'
                                });
                                object.trigger('tokenize:tokens:add', [category.value, select_cat, true]);

                            }
                            $('#close-category-popup').trigger('click');

                        });

                        var object = $('.list_tags').tokenize2({
                            sortable: true,
                            dataSource: '<?php echo admin_url('admin-ajax.php?action=op_ajax_order_status&op_nonce='.$op_nonce); ?>'
                        });
                        
                        $('.op-sortable').sortable();
                        

                });

            }(jQuery));
            
        </script>
        <?php
        $this->_style_fix();
    }

    function _style_fix() {
        global $wp_version;

        if (version_compare($wp_version, '3.8', '<=')):
        ?>
        <style type="text/css">
            /** WordPress 3.8 Fix **/
            .form-table th { padding: 20px 10px; }
            .#wpbody-content .metabox-holder {
                padding-top: 5px;

            }
        </style>
        <?php
        endif;
    }

    public function category_widget() {

        $categories = get_terms( 'product_cat', array( 'orderby' => 'name' ) );
        ?>
       <div class="modal fade"  id="modal-cat-categories" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
               <div class="modal-header">
                    <button type="button" class="close" id="close-category-popup" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="exampleModalLabel"><?php echo __('Choose Categories','openpos') ?></h4>
                </div>
                <div class="modal-body">

                    <form method="GET" id="frm-list-categories">
                        <div>
                            <select multiple="multiple" aria-disabled="true"  data-placeholder="<?php esc_attr_e( 'Select categories&hellip;', 'openpos' ); ?>" class="wc-enhanced-select" id="show_categories" name="show_categories[]" style="width: 100%;max-width:100%;min-height:300px;">
                                <?php
                                $r                 = array();
                                $r['pad_counts']   = 1;
                                $r['hierarchical'] = 1;
                                $r['hide_empty']   = false;
                                $r['value']        = 'id';
                                $r['selected']     =  array();//$this->show_categories;

                                include_once WC()->plugin_path() . '/includes/walkers/class-wc-product-cat-dropdown-walker.php';

                                echo wc_walk_category_dropdown_tree( $categories, 0, $r ); // @codingStandardsIgnoreLine
                                ?>
                            </select>

                        </div>

                    </form>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close','openpos') ?></button>
                    <button type="button" class="btn btn-primary  save-categories-popup"><?php echo __('Save changes','openpos') ?></button>
                </div>
            </div>
             </div>
        </div>

        <?php
    }
    function callback_textarea_code( $args ) {

        $value       = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="'.$args['placeholder'].'"';

        $html        = sprintf( '<textarea rows="5" cols="55" class="%1$s-text textarea-code" id="%2$s[%3$s]" name="%2$s[%3$s]"%4$s>%5$s</textarea>', $size, $args['section'], $args['id'], $placeholder, $value );
        $html        .= $this->get_field_description( $args );

        echo $html;
    }
    function callback_pos_grid($args){
        $value       = $this->get_option( $args['id'], $args['section'], $args['std'] ) ;

        $size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $type        = 'number';
        $placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

        

        $html        = sprintf( '<input type="%1$s" class="%2$s-pos-grid" id="%3$s[%4$s]" name="%3$s[%4$s][col]" value="%5$s"%7$s/> x <input type="%1$s" class="%2$s-pos-grid" id="%3$s[%4$s]" name="%3$s[%4$s][row]" value="%6$s"%7$s/>', $type, $size, $args['section'], $args['id'], $value['col'],$value['row'], $placeholder );
        $html       .= $this->get_field_description( $args );

        echo $html;
    }
    function callback_list_tags($args){


        $values =  $this->get_option( $args['id'], $args['section'], $args['std'] );


        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $html = '<div class="category-tag-container"><div class="tag-list">';
        $html  .= sprintf( '<select class="%1$s list_tags" multiple name="%2$s[%3$s][]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );
        $args['options'] = array();

        $wc_order_status = wc_get_order_statuses();
       

        if(is_array($values))
        {
            foreach ( $values as $key => $status_id ) {
                //$term = get_term_by( 'id', $cat_id, 'product_cat', 'ARRAY_A' );
                
                if(isset($wc_order_status[$status_id]))
                {
                    $html .= sprintf( '<option value="%s"%s>%s</option>', $status_id, 'selected', $wc_order_status[$status_id] );
                }
            }
        }
        $html .= sprintf( '</select>' );
        $html .= sprintf( '</div></div>' );
        $html .= $this->get_field_description( $args );

        echo $html;
    }

}

endif;
