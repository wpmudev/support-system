<?php

if(!class_exists('WP_List_Table'))
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class Incsub_Support_FAQ_Categories_Table extends WP_List_Table {

    private $data;

	function __construct(){
        parent::__construct( array(
            'singular'  => __( 'Category', INCSUB_SUPPORT_LANG_DOMAIN ),  
            'plural'    => __( 'Categories', INCSUB_SUPPORT_LANG_DOMAIN ), 
            'ajax'      => false        
        ) );
        
    }

    function column_default( $item, $column_name ){

        $value = '';
    	switch ( $column_name ) {
            default		: $value = $item[ $column_name ]; break;
    	}
        return $value;
    }


    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'id'        => __( 'ID', INCSUB_SUPPORT_LANG_DOMAIN ),
            'name'      => __( 'Name', INCSUB_SUPPORT_LANG_DOMAIN ),
            'faqs'      => __( 'FAQs', INCSUB_SUPPORT_LANG_DOMAIN )
        );
        return $columns;
    }

    function column_cb($item){
        if ( '0' == $item->defcat ) {
            return sprintf(
                '<input type="checkbox" name="%1$s[]" value="%2$s" />',
                $this->_args['singular'],
                $item->cat_id
            );
        }
        else {
            return '';
        }
    }

    function column_id( $item ) {
        return $item->cat_id;
    }

    function column_faqs( $item ) {
        return $item->get_faqs_count();
    }

    function column_name( $item ) {

        $base_url = remove_query_arg( 'added' );
        $base_url = remove_query_arg( 'updated', $base_url );

        $delete_link = add_query_arg( 
            array( 
                'action' => 'delete',
                'category' => (int)$item->cat_id 
            ),
            $base_url
        );

        $set_default_link = add_query_arg( 
            array( 
                'action' => 'set_default',
                'category' => (int)$item->cat_id 
            ),
            $base_url
        );

        $edit_link = add_query_arg( 
            array( 
                'action' => 'edit',
                'category' => (int)$item->cat_id 
            ),
            $base_url
        );

        $actions = array(
            'edit' => sprintf( __( '<a href="%s">Edit</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $edit_link )   
        );

        if ( $item->defcat ) {
            return '<a href="' . esc_url( $edit_link ) . '" title="' . esc_attr( __( 'Edit faq category', INCSUB_SUPPORT_LANG_DOMAIN ) ) . '">' . $item->cat_name . '</a> <strong>' . __( '[Default category]', INCSUB_SUPPORT_LANG_DOMAIN ) . '</strong>'  . $this->row_actions($actions);
        }
        else {
            $more_actions = array( 
                'delete'    => sprintf( __( '<a href="%s">Delete</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $delete_link ),
                'set_default' => sprintf( __( '<a href="%s">Set as default</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $set_default_link )      
            );
            $actions = array_merge( $actions, $more_actions );
            return '<a href="' . esc_url( $edit_link ) . '" title="' . esc_attr( __( 'Edit faq category', INCSUB_SUPPORT_LANG_DOMAIN ) ) . '">' . $item->cat_name . '</a>' . $this->row_actions($actions);
        }
    }


    function get_bulk_actions() {
        $actions = array(
            'delete'    => __( 'Delete', INCSUB_SUPPORT_LANG_DOMAIN )
        );
        return $actions;
    }

    function process_bulk_action() {
        if ( 'delete' === $this->current_action() ) {
            $categories = array();
            if ( ! empty( $_REQUEST['category'] ) && ! is_array( $_REQUEST['category'] ) )
                $categories = array( absint( $_REQUEST['category'] ) );
            elseif ( is_array( $_REQUEST['category'] ) )
                $categories = array_map( 'absint', $_REQUEST['category'] );

            foreach ( $categories as $cat_id )
                incsub_support_delete_faq_category( $cat_id );
        }
        if ( 'set_default' === $this->current_action() ) {
            incsub_support_set_default_faq_category( absint( $_GET['category'] ) );
        }
    }

 

    function prepare_items() {

        $this->process_bulk_action();
        

        $per_page = 7;

        $columns = $this->get_columns();
        $hidden = array( 'id' );
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array(
            $columns, 
            $hidden, 
            $sortable
        );

        $current_page = $this->get_pagenum();

        $args = array(
            'per_page' => $per_page,
            'page' => $current_page
        );
        $this->items = incsub_support_get_faq_categories( $args );
        $total_items = incsub_support_get_faq_categories_count( $args );

        $this->set_pagination_args( array(
            'total_items' => $total_items,                
            'per_page'    => $per_page,                   
            'total_pages' => ceil($total_items/$per_page) 
        ) );

    }

}
?>