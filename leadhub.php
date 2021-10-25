<?php 
/**
 * Leadhub
 *
 * @package           Leadhub Package
 * @author            Daniel Koch
 * @copyright         2020 Leadhub
 * @license           GPL-2.0
 *
 * @wordpress-plugin
 * Plugin Name:       Leadhub
 * Plugin URI:        https://www.leadhub.co/
 * Description:       Leadhub pixel integration
 * Version:           1.61
 * Requires at least: 5.2
 * Requires PHP:      7
 * Author:            Leadhub s.r.o.
 * Author URI:        https://www.leadhub.co/
 * Text Domain:       leadhub
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH'))
{
    exit;
} // Exit if accessed directly





function get_leadhub_feed_url() {
    $protocols = array('http://', 'http://www.', 'www.');
    $web_url = str_replace($protocols, '', get_bloginfo('wpurl'));
    $hash_url = hash('md5', $web_url);

    return $hash_url;
}
 
/**
 * custom option and settings
 */
function leadhub_settings_init() {
    // Register a new setting for "leadhub" page.
    register_setting( 'leadhub', 'leadhub_options' );
 
    // Register a new section in the "leadhub" page.
    add_settings_section(
        'leadhub_section_developers',
        __( 'Nastavení kontejneru', 'leadhub' ), 'leadhub_section_developers_callback',
        'leadhub'
    );
 
    // Register a new field in the "leadhub_section_developers" section, inside the "leadhub" page.
    add_settings_field(
        'leadhub_field',
         __( 'Kontejner', 'leadhub' ),
        'leadhub_field_cb',
        'leadhub',
        'leadhub_section_developers',
        array(
            'label_for'         => 'leadhub_field',
            'class'             => 'leadhub_row',
            'leadhub_custom_data' => 'custom',
        )
    );
}
 
/**
 * Register our leadhub_settings_init to the admin_init action hook.
 */
add_action( 'admin_init', 'leadhub_settings_init' );
 
/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function leadhub_section_developers_callback( $args ) {
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Zadejte ID containeru z aplikace leadhub', 'leadhub' ); ?></p>
    <?php
}
 
/**
 * 
 *
 * @param array $args
 */
function leadhub_field_cb( $args ) {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option( 'leadhub_options' );
    ?>
    <input type="text" name="leadhub_options[<?php echo esc_attr( $args['label_for'] ); ?>]" id="<?php echo esc_attr( $args['label_for'] ); ?>" data-custom="<?php echo esc_attr( $args['leadhub_custom_data'] ); ?>" value="<?=$options['leadhub_field'];?>">
    <?php
}
 
/**
 * Add the top level menu page.
 */
function leadhub_options_page() {
    add_menu_page(
        'Leadhub',
        'Leadhub',
        'manage_options',
        'leadhub',
        'leadhub_options_page_html'
    );
}
 
 
/**
 * Register our leadhub_options_page to the admin_menu action hook.
 */
add_action( 'admin_menu', 'leadhub_options_page' );
 
 
/**
 * Top level menu callback function
 */
function leadhub_options_page_html() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
 
    if ( isset( $_GET['settings-updated'] ) ) {
        // add settings saved message with the class of "updated"
        add_settings_error( 'leadhub_messages', 'leadhub_message', __( 'Nastavení uloženo', 'leadhub' ), 'updated' );
    }
 
    // show error/update messages
    settings_errors( 'leadhub_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            // output security fields for the registered setting "leadhub"
            settings_fields( 'leadhub' );
            // output setting sections and their fields
            // (sections are registered for "leadhub", each field is registered to a specific section)
            do_settings_sections( 'leadhub' );
            // output save settings button
            submit_button( 'Uložit nastavení' );
            ?>
        </form>
        <h2>URL dynamicky generovaného rss feedu</h2>
        <a href="<?=bloginfo('url');?>/feed/lh-<?=get_leadhub_feed_url();?>" target="_blank"><?=bloginfo('url');?>/feed/lh-<?=get_leadhub_feed_url();?></a>
        <div style="margin-top: 16px">
            <button class="button" onclick="copyToClipboard()">Zkopírovat url</button>
        </div>
        <h2>URL statického XML Feedu</h2>
        <p>Vhodné pro velké počty produktů. Tato operace může trvat několik minut, nezavírejte prosím okno.</p>
        <?php 
        if ( 0 != filesize( plugin_dir_path( __FILE__ ) . 'includes/feed.xml' ) ):
        ?>
        <a href="<?=bloginfo('url');?>/wp-content/plugins/leadhub/includes/feed.xml" target="_blank"><?=bloginfo('url');?>/wp-content/plugins/leadhub/includes/feed.xml</a>
        <?php endif;?>
        <div style="margin-top: 16px">
            <button class="button" id="lh-generate-xml">Vygenerovat feed</button>
        </div>
        <script>
            function copyToClipboard(text) {
                var inputc = document.body.appendChild(document.createElement("input"));
                inputc.value = '<?=bloginfo('url');?>/feed/lh-<?=get_leadhub_feed_url();?>';
                inputc.focus();
                inputc.select();
                document.execCommand('copy');
                inputc.parentNode.removeChild(inputc);
                //alert("URL Copied.");
            }
        </script>
         <script>
            jQuery("#lh-generate-xml").click(function () {
                event.preventDefault();

                jQuery.ajax({
                    url: '<?= admin_url('admin-ajax.php');?>',
                    type: 'POST',
                    data: { 
                        'action': 'lh_create_xml'
                    },
                beforeSend: function(msg){
                    jQuery("#lh-generate-xml").text("Vytvářím");
                },
                success: function (data) {
                    jQuery("#lh-generate-xml").text('Hotovo');
                    location.reload();
                }
                });
            });
        </script>
    </div>
    <?php
}

 /**
 * Add tracking code to head
 */

function leadhub_add_tracking() {
    $options = get_option( 'leadhub_options' );
    ?>
        <!-- Begin Leadhub Pixel Code -->
        <script>
            (function(w,d,x,n,u,t,f,s,o){f='LHInsights';w[n]=w[f]=w[f]||function(n,d){
            (w[f].q=w[f].q||[]).push([n,d])};w[f].l=1*new Date();s=d.createElement(x);
            s.async=1;s.src=u+'?t='+t;o=d.getElementsByTagName(x)[0];o.parentNode.insertBefore(s,o)
            })(window,document,'script','lhi','//www.lhinsights.com/agent.js','<?=$options['leadhub_field'];?>');
            lhi('pageview');
        </script>
        <!-- End Leadhub Pixel Code -->
    <?php
}
add_action('wp_head', 'leadhub_add_tracking');

/**
 * Track user after login
 * 
 */


function leadhub_set_transient_for_login( $user_login ) {
    set_transient( $user_login, '1', 0 );
}
add_action( 'wp_login', 'leadhub_set_transient_for_login' );

function leadhub_track_login_info() {
    global $current_user;
    wp_get_current_user();

    if ( ! is_user_logged_in() )
        return;

    if ( ! get_transient( $current_user->data->user_login ) )
        return;

    $phone = get_user_meta($current_user->ID,'billing_phone',true);
    //insert script in head
    ?>
    <script>
        lhi('Identify', {
            email:'<?=$current_user->data->user_email;?>', // povinny; str;
            user_id: '<?=$current_user->ID;?>', 
            subscribe: ['login'],
            <?php if($current_user->user_firstname):?>
            first_name: '<?=$current_user->user_firstname;?>', 
            <?php endif;?>
            <?php if($current_user->user_lastname):?>
            last_name: '<?=$current_user->user_lastname;?>', 
            <?php endif;?>
            <?php if($phone):?>
            phone: '<?=$phone;?>' 
            <?php endif;?>
        })
    </script>
<?php 
    delete_transient( $current_user->data->user_login );
}
add_action( 'wp_footer', 'leadhub_track_login_info' );

/**
 * Track user for product page and category page
 * 
 */

function leadhub_track_product_cat_page() {

    //Check if woocommerce is active
    if ( !class_exists( 'WooCommerce' ) ) {
        return;
    }
    //Check if is product page of woocommerce
    if (is_product()):
        global $product;
        $ID = $product->get_id();
    ?>
        <script>
            lhi('ViewContent', {
                products: [{ // povinny; array of dicts;
                product_id: '<?= $ID;?>' // povinny; str
                }]
            });
        </script>
    <?php
    endif;

    //Check if is category page of woocommerce
    if (is_product_category()):
        //Get current categoriy
        $category = get_queried_object();
    ?>
        <script>
            lhi('ViewCategory', {
                category: '<?=$category->name?>'
            });
        </script>
<?php
endif;
}
add_action('wp_footer', 'leadhub_track_product_cat_page');

function leadhub_update_cart_products() {
    if ( !class_exists( 'WooCommerce' ) ) {
        return;
    }

    global $woocommerce;

    //Get items in cart
    $items = $woocommerce->cart->get_cart();

    $cart_minified = "<script>lhi('SetCart', {products: [";
        foreach($items as $item => $values) { 
            $_product =  wc_get_product( $values['data']->get_id()); 
            if($values['variation_id']):
                $product_ID = $values['variation_id'];
            else:
                $product_ID = $values['product_id'];
            endif;
            $cart_minified .= "{product_id: '".$product_ID."',quantity: ".$values['quantity'].",value: ".get_post_meta($product_ID , '_price', true).",currency: 'CZK'},";
        } 
    $cart_minified .= "]})<";

    echo $cart_minified;

    wp_die();
}
    
add_action('wp_ajax_leadhub_update_cart_products', 'leadhub_update_cart_products');
add_action('wp_ajax_nopriv_leadhub_update_cart_products', 'leadhub_update_cart_products');

function leadhub_track_users_cart() {

    if ( !class_exists( 'WooCommerce' ) ) {
        return;
    }

    global $woocommerce;

    //Get items in cart
    $items = $woocommerce->cart->get_cart();

    //Insert script in head
    ?>
    <div id="lhi_setcart">
    <script>
    lhi('SetCart', {
        products: [<?php 
                foreach($items as $item => $values) { 
                    if($values['variation_id']):
                        $product_ID = $values['variation_id'];
                    else:
                        $product_ID = $values['product_id'];
                    endif;
                    echo '{';
                        echo "product_id: '".$product_ID."',";
                        echo "quantity: ".$values['quantity'].",";
                        echo "value: ".get_post_meta($product_ID , '_price', true).",";
                        echo " currency: 'CZK'";
                    echo '},';
                } 
        ?>]
    });
    </script>
    </div>
    <script>
        jQuery( document.body ).on( 'added_to_cart removed_from_cart', function(){
            jQuery.ajax({
                type: "POST",
                url: '<?=admin_url( 'admin-ajax.php' );?>',
                data: {action : 'leadhub_update_cart_products'},
                success: function (data) {
                    jQuery('#lhi_setcart').html(data + '/script>');
                }
            });
        });
    </script>
    
<?php
}
add_action('wp_footer', 'leadhub_track_users_cart');

/**
 * Track order info
 * 
 */

add_action( 'wp_footer', 'leadhub_send_order_info' );

function leadhub_send_order_info(){
    // On Order received endpoint only
    if( is_wc_endpoint_url( 'order-received' ) ) :

    $order_id = absint( get_query_var('order-received') ); // Get order ID

    if( get_post_type( $order_id ) !== 'shop_order' ) return; // Exit

    //getting order object
    $order = wc_get_order($order_id);

    $data = $order->get_data();

    $order_items = $order->get_items();
    $products_output = [];

    ?>
    <script>
        lhi('Purchase', {
            email: '<?=$data['billing']['email'];?>',
            value: <?=$data['total'];?>,
            currency: '<?=$data['currency'];?>',
            products: [ 
                <?php 
                foreach( $order_items as $item_id => $item ){ 
                    $item_data = $item->get_data();
                    if($item_data['variation_id']):
                        $product_ID = $item_data['variation_id'];
                    else:
                        $product_ID = $item_data['product_id'];
                    endif;
                    echo '{';
                        echo "product_id: '".$product_ID."',";
                        echo "quantity: ".$item_data['quantity'].",";
                        echo "value: ".get_post_meta($product_ID , '_price', true).",";
                        echo " currency: '".$data['currency']."'";
                    echo '},';
                }    
                ?>
            ],
            order_id: '<?= $order->id ?>', 
            <?php if($data['customer_id']):?>
            user_id: '<?=$data['customer_id'];?>', 
            <?php endif;?>
            first_name: '<?=$data['billing']['first_name'];?>', 
            last_name: '<?=$data['billing']['last_name'];?>', 
            phone: '<?=$data['billing']['phone'];?>', 
            address: { 
                street: '<?=$data['billing']['address_1'];?> <?=$data['billing']['address_2'];?>', 
                city: '<?=$data['billing']['city'];?>', 
                zip: '<?=$data['billing']['postcode'];?>', 
                country_code: '<?=$data['billing']['country'];?>' 
            }
        });

    </script>
    <?php   
    endif;
}


function leadhubRSS(){
    $url = get_leadhub_feed_url();
    add_feed('lh-'.$url, 'leadhubRSSFunc');
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
}
add_action('init', 'leadhubRSS');

function leadhubRSSFunc(){
    header( 'Content-Type: application/rss+xml;charset=utf-8' );
    include ABSPATH . 'wp-content/plugins/leadhub/rss-leadhub.php';
}

require plugin_dir_path( __FILE__ ) . 'includes/xml.php';

