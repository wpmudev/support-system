<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class MU_Support_Ticket_Categories_Table extends WP_List_Table {

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
            'user'      => __( 'Assign to user', INCSUB_SUPPORT_LANG_DOMAIN ),
            'tickets'   => __( 'Tickets', INCSUB_SUPPORT_LANG_DOMAIN )
        );
        return $columns;
    }

    function column_cb($item){
        if ( '0' == $item['defcat'] ) {
            return sprintf(
                '<input type="checkbox" name="%1$s[]" value="%2$s" />',
                $this->_args['singular'],
                $item['cat_id']
            );
        }
        else {
            return '';
        }
    }

    function column_id( $item ) {
        return $item['cat_id'];
    }

    function column_name( $item ) {
        $delete_link = add_query_arg( 
            array( 
                'action' => 'delete',
                'category' => (int)$item['cat_id'] 
            )
        );

        $set_default_link = add_query_arg( 
            array( 
                'action' => 'set_default',
                'category' => (int)$item['cat_id'] 
            )
        );

        $edit_link = add_query_arg( 
            array( 
                'action' => 'edit',
                'category' => (int)$item['cat_id'] 
            )
        );

        $actions = array(
            'edit' => sprintf( __( '<a href="%s">Edit</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $edit_link )   
        );

        if ( '1' == $item['defcat'] ) {
            return $item['cat_name'] . ' <strong>' . __( '[Default category]', INCSUB_SUPPORT_LANG_DOMAIN ) . '</strong>'  . $this->row_actions($actions);
        }
        else {
            $more_actions = array( 
                'delete'    => sprintf( __( '<a href="%s">Delete</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $delete_link ),
                'set_default' => sprintf( __( '<a href="%s">Set as default</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $set_default_link )      
            );
            $actions = array_merge( $actions, $more_actions );
            return $item['cat_name'] . $this->row_actions($actions);
        }
    }

    function column_user( $item ) {
        $user_login = __( 'None', INCSUB_SUPPORT_LANG_DOMAIN );
        if ( $user = get_user_by( 'id', $item['user_id'] ) )
            $user_login = $user->data->user_login;

        return $user_login;
    }


    function column_tickets( $item ) {
        if( ! $item['tickets'] ) {
            return $item['tickets'];
        }
        else {
            $link = MU_Support_System::$network_main_menu->get_permalink();
            $link = add_query_arg( 'category', $item['cat_id'], $link );
            return '<a href="' . $link . '">' . $item['tickets'] . '</a>';
        }
    }

    function get_bulk_actions() {
        $actions = array(
            'delete'    => __( 'Delete', INCSUB_SUPPORT_LANG_DOMAIN )
        );
        return $actions;
    }

 

    function prepare_items() {

        $model = MU_Support_System_Model::get_instance();

        if( 'delete' === $this->current_action() ) {

            if ( isset( $_POST['category'] ) && is_array( $_POST['category'] ) ) {
                foreach ( $_POST['category'] as $category )
                    $model->delete_ticket_category( absint( $category ) );
            }
            elseif ( isset( $_GET['category'] ) && $category = absint( $_GET['category'] ) ) {
                $model->delete_ticket_category( $category );
            }
        }
        if( 'set_default' === $this->current_action() && isset( $_GET['category'] ) && $category = absint( $_GET['category'] ) ) {
            $model->set_ticket_category_as_default( $category );
        }

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

        
        $data = $model->get_ticket_categories();

        $total_items = count( $data );

        $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
        

        foreach ( $data as $key => $item ) {
            $data[ $key ]['tickets'] = $model->get_tickets_from_cat( $item['cat_id'] );
        }

        $this->items = $data;

        $this->set_pagination_args( array(
            'total_items' => $total_items,                
            'per_page'    => $per_page,                   
            'total_pages' => ceil($total_items/$per_page) 
        ) );

    }

}
?>