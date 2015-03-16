<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class Incsub_Support_Tickets_Table extends WP_List_Table {

    function __construct( $args = array() ){

        //Set parent defaults
        parent::__construct( array(
            'singular'  => __( 'Ticket', INCSUB_SUPPORT_LANG_DOMAIN ),  
            'plural'    => __( 'Tickets', INCSUB_SUPPORT_LANG_DOMAIN ), 
            'ajax'      => false        
        ) );

        $defaults = array_merge( $this->_args, array( 'status' => 'all' ) );
        $this->_args = wp_parse_args( $args, $defaults );
        
    }


    function column_default( $item, $column_name ) {

        $value = '';
        switch ( $column_name ) {
            case 'id'           : $value = (int)$item->ticket_id; break;
            case 'staff'        : $value = $item->get_staff_name(); break;              
        }
        return $value;
    }

    function column_priority( $item ) {
        $priority_name = incsub_support_get_ticket_priority_name( (int)$item->ticket_priority );
        $class = 'dashicons-before dashicons-marker ticket-priority-' . $item->ticket_priority;
        return '<span class="' . $class . '"> ' . $priority_name . '</span>';
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  
            /*$2%s*/ $item->ticket_id                
        );
    }

    function column_status( $item ) {
        $status_name = incsub_support_get_ticket_status_name( (int)$item->ticket_status );
        $class = 'dashicons-before ';

        switch ( $item->ticket_status ) {
            case 0: { $class .= 'dashicons-star-filled'; break; }
            case 1: { $class .= 'dashicons-format-status'; break; }
            case 2: { $class .= 'dashicons-id'; break; }
            case 3: { $class .= 'dashicons-businessman'; break; }
            case 4: { $class .= 'dashicons-backup'; break; }
            case 5: { $class .= 'dashicons-no'; break; }
        } 

        $plugin = incsub_support();
        $plugin::$ticket_status = array(
                0   =>  __( 'New', INCSUB_SUPPORT_LANG_DOMAIN ),
                1   =>  __( 'In progress', INCSUB_SUPPORT_LANG_DOMAIN ),
                2   =>  __( 'Waiting on User to reply', INCSUB_SUPPORT_LANG_DOMAIN ),
                3   =>  __( 'Waiting on Admin to reply', INCSUB_SUPPORT_LANG_DOMAIN ),
                4   =>  __( 'Stalled', INCSUB_SUPPORT_LANG_DOMAIN ),
                5   =>  __( 'Closed', INCSUB_SUPPORT_LANG_DOMAIN )
            );


        return '<span class="' . $class . '"> ' . $status_name . '</span>';
    }

    function column_category( $item ) {
        return $item->get_category_name();
    }

    function column_subject( $item ) {

        // Link to the single ticket page
        $link = add_query_arg(
            array( 
                'tid' => (int)$item->ticket_id,
                'action' => 'edit'
            ),
            apply_filters( 'support_system_tickets_table_menu_url', '' )
        );

        $delete_link = add_query_arg( 
            array( 
                'action' => 'delete', 
                'tid' => (int)$item->ticket_id 
            )
        );
        $open_link = add_query_arg( 
            array( 
                'action' => 'open', 
                'tid' => (int)$item->ticket_id 
            ) 
        );
        $close_link = add_query_arg( 
            array( 
                'action' => 'close', 
                'tid' => (int)$item->ticket_id 
            )
        );

        $actions = array(
            'edit'    => sprintf( __( '<a href="%s">Edit</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $link ),
            'delete'    => sprintf( __( '<a href="%s">Delete ticket</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $delete_link )
        );

        if ( incsub_support_current_user_can( 'open_ticket', $item->ticket_id ) )
            $actions['open'] = sprintf( __( '<a href="%s" class="open-ticket">Open ticket</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $open_link );

        if ( incsub_support_current_user_can( 'close_ticket', $item->ticket_id ) )
            $actions['close'] = sprintf( __( '<a href="%s" class="close-ticket">Close ticket</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $close_link );

        $status = $this->_args['status'];

        if ( 'archive' == $status ) {
            unset( $actions['close'] );       
        }
        else {
            if ( 5 == (int)$item->ticket_status ) {
                unset( $actions['close'] );    
            }
            else {
                unset( $actions['open'] );    
                unset( $actions['delete'] );    
            }

        }

        if ( ! incsub_support_current_user_can( 'delete_ticket' ) && isset( $actions['delete'] ) )
            unset( $actions['delete'] );

        $actions = apply_filters( 'support_system_tickets_actions', $actions, $item );        

        return '<a href="' . $link . '">' . stripslashes_deep( $item->title ) . '</a>' . $this->row_actions($actions); 
        
    }

    function column_submitted( $item ) {

        $value = __( 'Unknown', INCSUB_SUPPORT_LANG_DOMAIN );

        if ( is_multisite() ) {
            $blog_details = get_blog_details( array( 'blog_id' => (int)$item->blog_id ) );
            
            if ( ! empty( $blog_details ) )
                $value = '<a href="' . get_site_url( $item->blog_id ) . '">' . $blog_details->blogname . '</a>';
        }
        else {
            $user = get_userdata( $item->user_id );
            if ( ! empty( $user ) )
                $value = '<a href="' . admin_url( 'user-edit.php?user_id=' . $user->ID ) . '">' . $user->display_name . '</a>';
        }

        return $value;
    }

    function column_updated( $item ) {
        return incsub_support_get_translated_date( $item->ticket_updated ); 
    }

    function column_replies( $item ) {
        return $item->num_replies;
    }


    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'id'        => __( 'Ticket ID', INCSUB_SUPPORT_LANG_DOMAIN ),
            'subject'   => __( 'Subject', INCSUB_SUPPORT_LANG_DOMAIN ),
            'status'    => __( 'Status', INCSUB_SUPPORT_LANG_DOMAIN ),
            'priority'  => __( 'Priority', INCSUB_SUPPORT_LANG_DOMAIN ),
            'category'  => __( 'Category', INCSUB_SUPPORT_LANG_DOMAIN ),
            'staff'     => __( 'Staff Member', INCSUB_SUPPORT_LANG_DOMAIN ),
            'submitted' => __( 'Submitted From', INCSUB_SUPPORT_LANG_DOMAIN ),
            'replies' => __( 'Replies no.', INCSUB_SUPPORT_LANG_DOMAIN ),
            'updated'   => __( 'Last updated (GMT)', INCSUB_SUPPORT_LANG_DOMAIN )
        );

        if ( ! $this->get_bulk_actions() )
            unset( $columns['cb'] );
        
        return apply_filters( 'support_network_ticket_columns', $columns );
    }

    protected function get_sortable_columns() {
        return array(
            'subject'       => array( 'title', false ),
            'status'        => array( 'ticket_status', false ),
            'priority'      => array( 'ticket_priority', false ),
            'staff'         => array( 'admin_id', false ),
            'category'      => array( 'cat_id', false ),
            'replies'       => array( 'num_replies', false ),
            'submitted'       => array( 'blog_id', false ),
            'updated'       => array( 'ticket_updated', true )
        );
    }

    function extra_tablenav( $which ) {
        if ( 'top' == $which) {

            $cat_filter_args = array(
                'show_empty' => __( 'View all categories', INCSUB_SUPPORT_LANG_DOMAIN ),
                'selected' => isset( $_GET['category'] ) ? absint( $_GET['category'] ) : false
            );

            $priority_filter_args = array(
                'show_empty' => __( 'All priorities', INCSUB_SUPPORT_LANG_DOMAIN ),
                'selected' => isset( $_GET['priority'] ) ? absint( $_GET['priority'] ) : null
            );

            ?>
                <div class="alignleft actions">
                    <?php incsub_support_ticket_categories_dropdown( $cat_filter_args ); ?>
                    <?php incsub_support_priority_dropdown( $priority_filter_args ); ?>
                    <input type="submit" name="filter_action" id="ticket-query-submit" class="button" value="<?php echo esc_attr( 'Filter', INCSUB_SUPPORT_LANG_DOMAIN ); ?>">     
                </div>
        <?php
           
                
        }
        
    }

    function get_bulk_actions() {
        $actions = array();

        if ( incsub_support_current_user_can( 'delete_ticket' ) )
            $actions['delete'] = __( 'Delete', INCSUB_SUPPORT_LANG_DOMAIN );

        if ( incsub_support_current_user_can( 'open_ticket' ) )
            $actions['open'] = __( 'Open', INCSUB_SUPPORT_LANG_DOMAIN );

        if ( incsub_support_current_user_can( 'close_ticket' ) )
            $actions['close'] = __( 'Close', INCSUB_SUPPORT_LANG_DOMAIN );

        if ( 'archive' == $this->_args['status'] ) {
            unset( $actions['close'] );
        }
        elseif ( 'active' == $this->_args['status'] ) {
            unset( $actions['delete'] );
            unset( $actions['open'] );
        }

        $actions = apply_filters( 'support_system_tickets_bulk_actions', $actions );

        return $actions;

        
        
    }

    function process_bulk_action() {
        
        if( 'delete' === $this->current_action() && incsub_support_current_user_can( 'delete_ticket' ) ) {

            if ( isset( $_POST['ticket'] ) && is_array( $_POST['ticket'] ) ) {
                foreach ( $_POST['ticket'] as $ticket_id ) {
                    if ( incsub_support_is_ticket_closed( $ticket_id ) ) {
                        incsub_support_delete_ticket( absint( $ticket_id ) );
                    }
                }
            }
            elseif ( isset( $_GET['tid'] ) && is_numeric( $_GET['tid'] ) ) {
                $ticket = incsub_support_get_ticket( $_GET['tid'] );
                if ( $ticket )
                    incsub_support_delete_ticket( $ticket->ticket_id );
            }

        }

        if( 'open' === $this->current_action() ) {
            $ids = array();
            
            if ( isset( $_POST['ticket'] ) && is_array( $_POST['ticket'] ) )
                $ids = $_POST['ticket'];
            elseif ( isset( $_GET['tid'] ) && is_numeric( $_GET['tid'] ) )
                $ids = array( $_GET['tid'] );

            $ids = array_map( 'absint', $ids );
            foreach ( $ids as $id ) {
                if ( incsub_support_current_user_can( 'open_ticket', $id ) )
                    incsub_support_restore_ticket_previous_status( $id );
            }
        }

        if( 'close' === $this->current_action() ) {
            $ids = array();
            if ( isset( $_POST['ticket'] ) && is_array( $_POST['ticket'] ) )
                $ids = $_POST['ticket'];
            elseif ( isset( $_GET['tid'] ) && is_numeric( $_GET['tid'] ) )
                $ids = array( $_GET['tid'] );

            $ids = array_map( 'absint', $ids );
            foreach ( $ids as $id ) {
                if ( incsub_support_current_user_can( 'close_ticket', $id ) )
                    incsub_support_close_ticket( $id );
            }
        }

    }

    function single_row( $item ) {
        static $row_class = '';

        $row_class = ( $row_class == '' ? ' class="alternate"' : '' );

        $background = '';
        if ( ! $item->view_by_superadmin && incsub_support_current_user_can( 'manage_options' ) )
            $background .= 'style="background-color:#e8f3b9" ';

        echo '<tr ' . $background . $row_class . '>';
        echo $this->single_row_columns( $item );
        echo '</tr>';
    }


    function prepare_items() {

        $current_screen = get_current_screen();
        $screen_option = $current_screen->get_option( 'per_page', 'option' );

        $per_page = get_user_meta( get_current_user_id(), $screen_option, true );
        if ( empty ( $per_page ) || $per_page < 1 ) {
            $per_page = $current_screen->get_option( 'per_page', 'default' );
        }

        $columns = $this->get_columns();
        $hidden = array( 'id' );
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array(
            $columns, 
            $hidden, 
            $sortable
        );

        $this->process_bulk_action();
        $current_page = $this->get_pagenum();        

        $args = array(
            'status' => $this->_args['status'],
            'per_page' => $per_page,
            'page' => $current_page
        );

        $orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : false;
        $order = isset( $_GET['order'] ) ? $_GET['order'] : false;

        if ( $orderby )
            $args['orderby'] = $orderby;

        if ( $order )
            $args['order'] = $order;

        /**
         * Filters the query arguments in Tickets table
         *
         * @param Array $args Query arguments that will  be passed to incsub_support_get_tickets function
         */
        $args = apply_filters( 'support_system_tickets_table_query_args', $args );

        $this->items = incsub_support_get_tickets( $args );
        $total_items = incsub_support_get_tickets_count( $args );

        $this->set_pagination_args( array(
            'total_items' => $total_items,                
            'per_page'    => $per_page,                   
            'total_pages' => ceil($total_items/$per_page) 
        ) );

    }

}
?>