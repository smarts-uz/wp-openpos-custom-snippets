<?php
    global $op_in_pos_screen;
    $op_in_pos_screen = true;
    $base_dir = dirname(dirname(dirname(dirname(__DIR__))));
    if(!file_exists($base_dir.'/wp-load.php'))
    {
        $sampe_paths = array(
            dirname(__DIR__),
            '/opt/bitnami/apps/wordpress/htdocs',
            '/opt/bitnami/wordpress',
        );
        foreach($sampe_paths as $s)
        {
            if(file_exists($s.'/wp-load.php')){
                $base_dir = $s;
            }
        }
    } 
    
    /** UPDATE YOUR CUSTOM WORDPRESS DIR AT HERE */

    # $base_dir = 'ENTER_YOUR_WORDPRESS_BASE_PATH'; // enter your custom wordpress base dir and uncomment    

    /** END */

    $wordpress_load = $base_dir.'/wp-load.php'; 
    if(!file_exists($wordpress_load))
    {
        ?>
        <h2>No wordpress base dir found. </h2>
        <p>Please goto <b><?php echo __FILE__ ; ?></b> , find the line</p>
        <pre>
        # $base_dir = 'ENTER_YOUR_WORDPRESS_BASE_PATH'; // enter your custom wordpress base dir and uncomment  
        </pre>
        and replace with your new wordpress patch + uncomment (remove "#"). And try again!
        <pre>
        $base_dir = 'ENTER_YOUR_WORDPRESS_BASE_PATH'; // enter your custom wordpress base dir and uncomment  
        </pre>
        <?php
        
        exit;
    }   

    require_once ($wordpress_load);
    global $OPENPOS_SETTING;
    global $OPENPOS_CORE;

    $lang = $OPENPOS_SETTING->get_option('pos_language','openpos_pos');
    if(!$lang || $lang == '_auto')
    {
        $lang = false;
    }
    $pos_url =  rtrim($OPENPOS_CORE->get_pos_url(),'/');
    $plugin_info = $OPENPOS_CORE->getPluginInfo();
?>
<!doctype html>
<html lang="<?php echo $lang ? $lang : 'en'?>" style="height: calc(100% - 0px);">
<head>
    <meta charset="utf-8">
    <title><?php echo apply_filters('openpos_pos_title','POS'); ?></title>
    <meta NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">
    <base href="<?php echo $pos_url ?>/">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1,user-scalable=0"/>
    <link rel="icon" type="image/x-icon" href="<?php echo apply_filters('openpos_pos_favicon','favicon.ico'); ?>">
    <meta name="generator" content="OpenPOS - <?php echo  esc_attr($plugin_info['Version']); ?>" />
    <link rel="manifest" href="<?php echo apply_filters('openpos_pos_manifest',$pos_url.'/manifest.json');?>" />
    <link  href="<?php echo $pos_url; ?>/assets/i18n/en.json" as="fetch" />
    <?php
        $handes = array(
            'openpos.material.icon',
            'openpos.styles',
            'openpos.front'
        );
        wp_print_styles(apply_filters('openpos_pos_header_style',$handes));
    ?>
    <script type="text/javascript">
        
        var action_url = '<?php echo admin_url('admin-ajax.php'); ?>';
        <?php if($lang): ?>
        var pos_lang = '<?php echo $lang; ?>';
        <?php endif; ?>
        var pos_receipt_css = <?php echo json_encode($OPENPOS_CORE->getReceiptFontCss()); ?>;
        var global = global || window;
        global.action_url = action_url;
        
        <?php $allow_location = false;  ?>

        <?php if($allow_location): ?>
        
        global.allow_location = 'no';
        
        <?php endif; ?>
        global.version = '<?php echo  esc_attr($plugin_info['Version']); ?>';
        var Buffer = Buffer || [];
        var process = process || {
            env: { DEBUG: undefined },
            version: []
        };
        
    </script>
    <script type="text/javascript">
        window.addEventListener("beforeunload", function (e) {
            var confirmationMessage = 'It looks like you have been editing something. '
                + 'If you leave before saving, your changes will be lost.';

            (e || window.event).returnValue = confirmationMessage; 
            return confirmationMessage; 
        });
        window.addEventListener("focus", function(event) { document.body.className = 'focused'; }, false);
        window.addEventListener("click", function(event) { document.body.className = 'focused'; }, false);
        window.addEventListener("blur", function(event) { document.body.className = 'non-focused'; }, false);
    </script>
</head>
<?php
$handes = array();
wp_print_scripts(apply_filters('openpos_pos_header_js',$handes));
?>
<body style="width: 100%; height: 100%; overflow: hidden;">

<app-root>
    <style>

        body {
            background: #27ae60;
            margin: 0;
            padding: 0;
        }

        .sk-circle {
            margin: 25% auto;
            width: 40px;
            height: 40px;
            position: relative;
        }
        .sk-circle .sk-child {
            width: 100%;
            height: 100%;
            position: absolute;
            left: 0;
            top: 0;
        }
        .sk-circle .sk-child:before {
            content: '';
            display: block;
            margin: 0 auto;
            width: 15%;
            height: 15%;
            background-color: #fff;
            border-radius: 100%;
            -webkit-animation: sk-circleBounceDelay 1.2s infinite ease-in-out both;
            animation: sk-circleBounceDelay 1.2s infinite ease-in-out both;
        }
        .sk-circle .sk-circle2 {
            -webkit-transform: rotate(30deg);
            -ms-transform: rotate(30deg);
            transform: rotate(30deg); }
        .sk-circle .sk-circle3 {
            -webkit-transform: rotate(60deg);
            -ms-transform: rotate(60deg);
            transform: rotate(60deg); }
        .sk-circle .sk-circle4 {
            -webkit-transform: rotate(90deg);
            -ms-transform: rotate(90deg);
            transform: rotate(90deg); }
        .sk-circle .sk-circle5 {
            -webkit-transform: rotate(120deg);
            -ms-transform: rotate(120deg);
            transform: rotate(120deg); }
        .sk-circle .sk-circle6 {
            -webkit-transform: rotate(150deg);
            -ms-transform: rotate(150deg);
            transform: rotate(150deg); }
        .sk-circle .sk-circle7 {
            -webkit-transform: rotate(180deg);
            -ms-transform: rotate(180deg);
            transform: rotate(180deg); }
        .sk-circle .sk-circle8 {
            -webkit-transform: rotate(210deg);
            -ms-transform: rotate(210deg);
            transform: rotate(210deg); }
        .sk-circle .sk-circle9 {
            -webkit-transform: rotate(240deg);
            -ms-transform: rotate(240deg);
            transform: rotate(240deg); }
        .sk-circle .sk-circle10 {
            -webkit-transform: rotate(270deg);
            -ms-transform: rotate(270deg);
            transform: rotate(270deg); }
        .sk-circle .sk-circle11 {
            -webkit-transform: rotate(300deg);
            -ms-transform: rotate(300deg);
            transform: rotate(300deg); }
        .sk-circle .sk-circle12 {
            -webkit-transform: rotate(330deg);
            -ms-transform: rotate(330deg);
            transform: rotate(330deg); }
        .sk-circle .sk-circle2:before {
            -webkit-animation-delay: -1.1s;
            animation-delay: -1.1s; }
        .sk-circle .sk-circle3:before {
            -webkit-animation-delay: -1s;
            animation-delay: -1s; }
        .sk-circle .sk-circle4:before {
            -webkit-animation-delay: -0.9s;
            animation-delay: -0.9s; }
        .sk-circle .sk-circle5:before {
            -webkit-animation-delay: -0.8s;
            animation-delay: -0.8s; }
        .sk-circle .sk-circle6:before {
            -webkit-animation-delay: -0.7s;
            animation-delay: -0.7s; }
        .sk-circle .sk-circle7:before {
            -webkit-animation-delay: -0.6s;
            animation-delay: -0.6s; }
        .sk-circle .sk-circle8:before {
            -webkit-animation-delay: -0.5s;
            animation-delay: -0.5s; }
        .sk-circle .sk-circle9:before {
            -webkit-animation-delay: -0.4s;
            animation-delay: -0.4s; }
        .sk-circle .sk-circle10:before {
            -webkit-animation-delay: -0.3s;
            animation-delay: -0.3s; }
        .sk-circle .sk-circle11:before {
            -webkit-animation-delay: -0.2s;
            animation-delay: -0.2s; }
        .sk-circle .sk-circle12:before {
            -webkit-animation-delay: -0.1s;
            animation-delay: -0.1s; }

        @-webkit-keyframes sk-circleBounceDelay {
            0%, 80%, 100% {
                -webkit-transform: scale(0);
                transform: scale(0);
            } 40% {
                  -webkit-transform: scale(1);
                  transform: scale(1);
              }
        }

        @keyframes sk-circleBounceDelay {
            0%, 80%, 100% {
                -webkit-transform: scale(0);
                transform: scale(0);
            } 40% {
                  -webkit-transform: scale(1);
                  transform: scale(1);
              }
        }
    </style>
    <div class="sk-circle">
        <div class="sk-circle1 sk-child"></div>
        <div class="sk-circle2 sk-child"></div>
        <div class="sk-circle3 sk-child"></div>
        <div class="sk-circle4 sk-child"></div>
        <div class="sk-circle5 sk-child"></div>
        <div class="sk-circle6 sk-child"></div>
        <div class="sk-circle7 sk-child"></div>
        <div class="sk-circle8 sk-child"></div>
        <div class="sk-circle9 sk-child"></div>
        <div class="sk-circle10 sk-child"></div>
        <div class="sk-circle11 sk-child"></div>
        <div class="sk-circle12 sk-child"></div>
    </div>
</app-root>
<script type='text/javascript' src='<?php echo $pos_url; ?>/runtime.js?ver=<?php echo  esc_attr($plugin_info['Version']); ?>'></script>
<script type='text/javascript' src='<?php echo $pos_url; ?>/polyfills.js?ver=<?php echo  esc_attr($plugin_info['Version']); ?>'></script>
<script type='text/javascript' src='<?php echo $pos_url; ?>/main.js?ver=<?php echo  esc_attr($plugin_info['Version']); ?>'></script>
<script type='text/javascript'>
        
</script>

<?php
    $handes = array(
        'openpos.pos.main',
        'openpos.pos.ga'
    );
    wp_print_scripts(apply_filters('openpos_pos_footer_js',$handes));
    do_action('op_pos_page_after');
?>


</body>
</html>
