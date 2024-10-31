<?php
/**
Plugin Name:  Product Attribute Global Order and Visibility
Plugin URI:   #
Description:  This plugin provide feature to globally change product attribute order there show and visibility to hide and show product  attribute.
Version:      1.0
Author:       Vishal Solanki
Author URI:   http://vishalksolanki.com/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  pagov
Domain Path:  /languages
*/

define( 'PAGOV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PAGOV_VERSION', '1.0' );

register_activation_hook( __FILE__, 'pagov_product_attribute_order_visibility' );
register_deactivation_hook( __FILE__, 'pagov_remove_product_content' );

/* Included all necessary files */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
require_once( PAGOV_PLUGIN_DIR . 'inc/class.product-attribute-order.php' );
require_once( PAGOV_PLUGIN_DIR . 'inc/hook.product-attribute-display.php' );


/**
* Activated hook
* pagov_product_attribute_order_visibility
* @return object
*/
function pagov_product_attribute_order_visibility(){

	global $wpdb;
	$wpdb->query("ALTER TABLE {$wpdb->prefix}woocommerce_attribute_taxonomies ADD attribute_invisible INT(1) NULL");
	$wpdb->query("ALTER TABLE {$wpdb->prefix}woocommerce_attribute_taxonomies ADD attribute_custom_order INT(10) NULL");

	new Pagov_Product_Attribute_Order();
}

/**
* Deactivated hook
* pagov_remove_product_content
* @return Void
*/
function pagov_remove_product_content(){
	global $wpdb;
	$wpdb->query("ALTER TABLE {$wpdb->prefix}woocommerce_attribute_taxonomies DROP COLUMN attribute_invisible, DROP COLUMN attribute_custom_order ");
}

/**
* Display notice error on dashboard
* pagov_admin_notice_error
* @return Void
*/
function pagov_admin_notice_error() {
	if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		$class = 'notice notice-error';
		$message = __( 'Display Product attribute page, we need to Activated Woocommerce plugin.', 'pagov' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
	}	
}
add_action( 'admin_notices', 'pagov_admin_notice_error' );


/**
* Create subpage inside Woo Product 
* pagov_product_attribute_order_page
* @return Void
*/
function pagov_product_attribute_order_page() {
    add_submenu_page(
        'edit.php?post_type=product',
        __( 'Product Attribute Order', 'dimix' ),
        __( 'Product Attribute Order', 'dimix' ),
        'manage_options',
        'product_attribute_order',
        array( new Pagov_Product_Attribute_Order(),'pagov_product_attribute_listing') 
    );
}

add_action( 'admin_menu','pagov_product_attribute_order_page' );

/**
 * Remove product data tabs
 */
add_filter( 'woocommerce_product_tabs', 'pagov_remove_product_tabs', 98 );

function pagov_remove_product_tabs( $tabs ) {
    unset( $tabs['additional_information'] );  	// Remove the additional information tab
    $tabs['additional_information'] = array(
		'title' 	=> __( 'Additional Information', 'pagov' ),
		'priority' 	=> 15,
		'callback' 	=> 'pagov_woo_product_additional_information'
	);
    return $tabs;
}

function pagov_woo_product_additional_information(){
	global $product;
	?>
	<table class="shop_attributes">
	<?php if ( $display_dimensions && $product->has_weight() ) : ?>
		<tr>
			<th><?php _e( 'Weight', 'pagov' ) ?></th>
			<td class="product_weight"><?php echo esc_html( wc_format_weight( $product->get_weight() ) ); ?></td>
		</tr>
	<?php endif; ?>

	<?php if ( $display_dimensions && $product->has_dimensions() ) : ?>
		<tr>
			<th><?php _e( 'Dimensions', 'pagov' ) ?></th>
			<td class="product_dimensions"><?php echo esc_html( wc_format_dimensions( $product->get_dimensions( false ) ) ); ?></td>
		</tr>
	<?php endif; ?>
	<?php $pagov_get_attribute_taxonomies = pagov_get_attribute_taxonomies();
	foreach ( $pagov_get_attribute_taxonomies as $attribute ) {
		if ( $name = wc_attribute_taxonomy_name( $attribute->attribute_name ) ) {
			$label = ! empty( $attribute->attribute_label ) ? $attribute->attribute_label : $attribute->attribute_name; 
            $wc_product_attributes[ $name ] = $attribute;
            $attribute_values = wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'all' ) );
            if($attribute_values){ ?>
            <tr>
            	<th><?php echo $label; ?></th>
            	<td>
            		<?php
						$values = array();
					  	if ( $name ) {
							foreach ( $attribute_values as $attribute_item ) {
								$value_name = esc_html( $attribute_item->name );
								if ( $attribute->attribute_public ) {
									$values[] = '<a href="' . esc_url( get_term_link( $attribute_item->term_id, $name ) ) . '" rel="tag">' . $value_name . '</a>';
								} else {
									$values[] = $value_name;
								}
							}		
						}
					    else { }
						echo wpautop( wptexturize( implode( ', ', $values ) ) );
					?>
				</td>				
			</tr>
			<?php
		}
	}
}
?>
</table>
<?php
}
function pagov_get_attribute_taxonomies() {
  	$attribute_taxonomies = get_transient( 'wc_attribute_taxonomies' );
    global $wpdb;
    $attribute_taxonomies = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name != '' AND attribute_invisible != '1' ORDER BY ISNULL(attribute_custom_order), attribute_custom_order  ASC;" );

    set_transient( 'wc_attribute_taxonomies', $attribute_taxonomies );
    return (array) array_filter( apply_filters( 'woocommerce_attribute_taxonomies', $attribute_taxonomies ) );
}