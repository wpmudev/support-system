<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class MU_Support_Admin_Tickets_Table extends WP_List_Table {

    // Status variables for filtering purposes
    private $status = false;
    private $category = false;
    private $ticket_status = false;
    private $filter_category = false;
    private $filter_status = false;

	function __construct( $view, $filter_status, $filter_category ) {

        $this->status = $view;
        $this->filter_status = $filter_status;
        $this->filter_category = absint( $filter_category );

        //Set parent defaults
        parent::__construct( array(
            'singular'  => __( 'Ticket', INCSUB_SUPPORT_LANG_DOMAIN ),  
            'plural'    => __( 'Tickets', INCSUB_SUPPORT_LANG_DOMAIN ), 
            'ajax'      => false        
        ) );
        
    }

    function column_default( $item, $column_name ) {

        $value = '';
    	switch ( $column_name ) {
    		case 'id'           : $value = (int)$item->ticket_id; break;
            case 'priority'     : $value = incsub_support_get_ticket_priority_name( (int)$item->ticket_priority ); break;
            case 'staff'        : $value = $item->get_staff_name(); break;                       	
    	}
        return $value;
    }

    function column_status( $item ) {
        return incsub_support_get_ticket_status_name( (int)$item->ticket_status );
    }

    function column_category( $item ) {
        
        return $item->get_category_name();
    }

    function column_subject( $item ) {

        // Link to the single ticket page
        $link = add_query_arg(
            'tid',
            (int)$item->ticket_id,
            MU_Support_System::$admin_single_ticket_menu->get_permalink()
        );
        return '<a href="' . $link . '">' . stripslashes_deep( $item->title ) . '</a>'; 
    }


    function column_updated( $item ) {
        return get_date_from_gmt( $item->ticket_updated, get_option("date_format") ." ". get_option("time_format") ); 
    }


    function get_columns(){
        $columns = array(
            'id'		=> __( 'Ticket ID', INCSUB_SUPPORT_LANG_DOMAIN ),
            'subject'	=> __( 'Subject', INCSUB_SUPPORT_LANG_DOMAIN ),
            'status'	=> __( 'Status', INCSUB_SUPPORT_LANG_DOMAIN ),
            'priority'  => __( 'Priority', INCSUB_SUPPORT_LANG_DOMAIN ),
            'category'  => __( 'Category', INCSUB_SUPPORT_LANG_DOMAIN ),
            'staff'		=> __( 'Staff Member', INCSUB_SUPPORT_LANG_DOMAIN ),
            'updated'	=> __( 'Last updated (GMT)', INCSUB_SUPPORT_LANG_DOMAIN )
        );
        return apply_filters( 'support_ticket_columns', $columns );
    }

    function extra_tablenav( $which ) {
        if ( 'top' == $which) {
            ?>
                <div class="alignleft actions">
                    <select name="filter_category">
                        <?php $selected = $this->filter_category !== false ? $this->filter_category : false; ?>
                        <?php incsub_support_ticket_categories_dropdown( $selected ); ?>
                    </select>
                    <select name="filter_status">
                        <?php $selected = $this->filter_status !== false ? $this->filter_status : false; ?>
                        <?php incsub_support_ticket_type_dropdown( $selected ); ?>
                    </select>
                    <input type="submit" name="support-filter-submit" id="support-filter-submit" class="button" value="<?php _e( 'Filter', INCSUB_SUPPORT_LANG_DOMAIN ); ?>">
                </div>
            <?php
                
        }
        
    }

    function prepare_items() {

    	$per_page = 30;

    	$columns = $this->get_columns();
        $hidden = array( 'id' );
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array(
        	$columns, 
        	$hidden, 
        	$sortable
        );

        $current_page = $this->get_pagenum();

        $model = incsub_support_get_ticket_model();

        $args = array(
            'per_page'      => $per_page,
            'page'          => $current_page,
            'blog_id'       => get_current_blog_id()
        );

        $privacy = MU_Support_System::$settings['incsub_ticket_privacy'];

        if ( 'requestor' == $privacy && ! current_user_can( 'manage_options' ) )
            $args['user_in'] = array( get_current_user_id() );

        if ( $this->filter_category )
            $args['category_in'] = $this->filter_category;

        if ( $this->filter_status !== false )
            $args['status'] = absint( $this->filter_status );
        else
            $args['status'] = $this->status;

        $tickets = incsub_support_get_tickets( $args );

        if ( ! $this->filter_category && $this->filter_status === false ) {
            $total_items = incsub_support_get_tickets_count();

            if ( 'archive' == $this->status )
                $total_items = $total_items['closed'];
            elseif ( 'active' == $this->status )
                $total_items = $total_items['opened'];
            else
                $total_items = $total_items['all'];
        }
        else {
            $_args = array();
            if ( isset( $args['user_in'] ) )
                $_args['user_in'] = $args['user_in'];

            $_args['blog_id'] = $args['blog_id'];

            $total_items = incsub_support_get_filtered_tickets_count( $this->filter_status, $this->filter_category, $_args );
        }

        $this->items = $tickets;

        $this->set_pagination_args( array(
            'total_items' => $total_items,                
            'per_page'    => $per_page,                   
            'total_pages' => ceil($total_items/$per_page) 
        ) );

    }

}
?>