<?php 
Class Pagov_Product_Attribute_Order{
    
    /**
     * Display the list of product attributes
     *
     * @return Void
     */
    public function pagov_product_attribute_listing()
    {
        $pagov_attribute_order_listing = new Pagov_Product_Attribute_Order_Listing();
        if($_POST['submit']){
            global $wpdb;
            $woocommerce_attribute_taxonomies = $wpdb->prefix.'woocommerce_attribute_taxonomies';
            $order_num = sanitize_text_field($_POST['order_num']);
            $invisible = sanitize_text_field($_POST['invisible']);
            if($order_num){
                foreach ($order_num as $key => $value) {
                    if($value){
                        $wpdb->update( 
                            $woocommerce_attribute_taxonomies, 
                            array( 
                                'attribute_custom_order' => (int)$value, 
                            ), 
                            array( 'attribute_id' => (int)$key ),
                            array( 
                                '%d'
                            ), 
                            array( '%d' )  
                        );
                    }
                }
            }

            if($invisible){
                foreach ($invisible as $check => $check_value) {
                     $wpdb->update( 
                        $woocommerce_attribute_taxonomies, 
                        array( 
                            'attribute_invisible' => (int)$check_value, 
                        ), 
                        array( 'attribute_id' => (int)$check ),
                        array( 
                            '%d'    // value2
                        ), 
                        array( '%d' )  
                    );
                }
            }
        }
        $pagov_attribute_order_listing->prepare_items();
        ?>
            <div class="wrap">
                <form method="post">
                    <div id="icon-users" class="icon32"></div>
                    <h2><?php esc_html_e('Product Attribute Order & Visibility','pagov'); ?></h2>
                    <?php $pagov_attribute_order_listing->display(); ?>
                    <?php submit_button( __('Submit','pagov') ); ?>
                </form>
            </div>
        <?php
    }
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class Pagov_Product_Attribute_Order_Listing extends WP_List_Table
{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns    = $this->get_columns();
        $hidden     = $this->get_hidden_columns();
        $sortable   = $this->get_sortable_columns();
        $data       = $this->table_data();
       
        usort( $data, array( &$this, 'sort_data' ) );
        
        $perPage        = 10;
        $currentPage    = $this->get_pagenum();
        $totalItems     = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ));

        $data                   = array_slice($data,(($currentPage-1)*$perPage),$perPage);
        $this->_column_headers  = array($columns, $hidden, $sortable);
        $this->items            = $data;
    }

    /**
     * Override the parent columns method. 
     * Defines the columns to use in product attribute listing table
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'attribute_label'       => __('Name','pagov'),
            'attribute_name'        => __('Slug','pagov'),
            'attribute_type'        => __('Type','pagov'),
            'Order'                 => __('Order No','pagov'),
            'Invisible'             => __('Invisible','pagov'),
        );
        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('attribute_label' => array('attribute_label', false));
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        global $wpdb;     
        $data = array();
        $woocommerce_attribute_taxonomies = $wpdb->prefix.'woocommerce_attribute_taxonomies';
        $woocommerce_attribute_taxonomies_result = $wpdb->get_results( "SELECT * FROM $woocommerce_attribute_taxonomies", OBJECT );
        if($woocommerce_attribute_taxonomies_result)
        {
            foreach ($woocommerce_attribute_taxonomies_result as $key => $value) 
            {
                $data[$key]['attribute_label']      = $value->attribute_label;
                $data[$key]['attribute_name']       = $value->attribute_name;
                $data[$key]['attribute_type']       = $value->attribute_type;
                $data[$key]['id']                   = $value->attribute_id;
                $data[$key]['attribute_order']      = $value->attribute_custom_order;
                $data[$key]['attribute_invisible']  = $value->attribute_invisible;
            }
        }    
        return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        if($item['attribute_invisible']){
            $attribute_invisible = 'checked';
        }
        switch( $column_name ) {
            case 'attribute_label':
                return $item[ $column_name ];
            case 'attribute_name':
                return $item[ $column_name ];
            case 'attribute_type':
                return ucfirst($item[ $column_name ]);
            case 'Order':
                return '<input type="number" value="'.__( $item['attribute_order'],'pagov').'" name="order_num['.$item['id'].']" />';
            case 'Invisible':
                return '<input type="hidden" name="invisible['.$item['id'].']" value="0"><input type="checkbox" '.$attribute_invisible.' name="invisible['.$item['id'].']" value="1" /> Invisible';
            default:
                return print_r( $item, true ) ;
        }
    }
    
    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'title';
        $order = 'asc';
        // If orderby is set, use this as the sort column
        if(!empty(sanitize_text_field($_GET['orderby'])))
        {
            $orderby = sanitize_text_field($_GET['orderby']);
        }
        // If order is set use this as the order
        if(!empty(sanitize_text_field($_GET['order'])))
        {
            $order = sanitize_text_field($_GET['order']);
        }
        $result = strcmp( $a[$orderby], $b[$orderby] );
        if($order === 'asc')
        {
            return $result;
        }
        return -$result;
    }
}