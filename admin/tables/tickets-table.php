<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class MU_Support_Tickets_Table extends WP_List_Table {

    // Status variables for filtering purposes
    private $status = false;
    private $category = false;
    private $ticket_status = false;

	function __construct( $view ){
        $this->status = $view;
        if ( isset( $_GET['category'] ) && absint( $_GET['category'] ) )
            $this->category = absint( $_GET['category'] );

        if ( isset( $_GET['ticket_status'] ) )
            $this->ticket_status = absint( $_GET['ticket_status'] );

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
    		case 'id'			: $value = (int)$item->ticket_id; break;
            case 'priority' 	: $value = incsub_support_get_ticket_priority_name( (int)$item->ticket_priority ); break;
            case 'staff'		: $value = $item->get_staff_name(); break;            	
    	}
        return $value;
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  
            /*$2%s*/ $item->ticket_id                
        );
    }

    function column_status( $item ) {
        $link = add_query_arg( 'ticket_status', absint( $item->ticket_status ) );
        $link = remove_query_arg( 'view', $link );
        return '<a href="' . $link . '">' . incsub_support_get_ticket_status_name( (int)$item->ticket_status ) . '</a>';
    }

    function column_category( $item ) {
        
        $link = MU_Support_System::$network_main_menu->get_permalink();
        $link = add_query_arg( 'view', $this->status, $link );
        $link = add_query_arg( 'category', $item->cat_id, $link );

        ob_start();
            ?>
                <a href="<?php echo $link; ?>">
                    <?php echo $item->get_category_name(); ?>
                </a>
            <?php
        return ob_get_clean();
    }

    function column_subject( $item ) {

        // Link to the single ticket page
        $link = add_query_arg(
            'tid',
            (int)$item->ticket_id,
            MU_Support_System::$network_single_ticket_menu->get_permalink()
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

        if ( 'archive' == $this->status ) {
            $actions = array(
                'delete'    => sprintf( __( '<a href="%s">Delete ticket</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $delete_link ),
                'open'      => sprintf( __( '<a href="%s">Open ticket</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $open_link )
            );
            return '<a href="' . $link . '">' . stripslashes_deep( $item->title ) . '</a>' . $this->row_actions($actions); 
        }
        else {
            $actions = array();
            if ( 5 == (int)$item->ticket_status ) {
                $actions['delete'] = sprintf( __( '<a href="%s">Delete ticket</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $delete_link );
                $actions['open'] = sprintf( __( '<a href="%s">Open ticket</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $open_link );
            }
            else {
                $actions['close'] = sprintf( __( '<a href="%s">Close ticket</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $close_link );
            }
            return '<a href="' . $link . '">' . stripslashes_deep( $item->title ) . '</a>' . $this->row_actions($actions); 
        }
        
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
        return get_date_from_gmt( $item->ticket_updated, get_option("date_format") ." ". get_option("time_format") ); 
    }


    function get_columns(){
        $columns = array();

        if ( in_array( $this->status, array( 'archive', 'active' ) ) ) {
            $columns['cb'] = '<input type="checkbox" />';
        }
        $columns = array_merge( $columns, array(
            'id'		=> __( 'Ticket ID', INCSUB_SUPPORT_LANG_DOMAIN ),
            'subject'	=> __( 'Subject', INCSUB_SUPPORT_LANG_DOMAIN ),
            'status'	=> __( 'Status', INCSUB_SUPPORT_LANG_DOMAIN ),
            'priority'  => __( 'Priority', INCSUB_SUPPORT_LANG_DOMAIN ),
            'category'  => __( 'Category', INCSUB_SUPPORT_LANG_DOMAIN ),
            'staff'		=> __( 'Staff Member', INCSUB_SUPPORT_LANG_DOMAIN ),
            'submitted' => __( 'Submitted From', INCSUB_SUPPORT_LANG_DOMAIN ),
            'updated'	=> __( 'Last updated (GMT)', INCSUB_SUPPORT_LANG_DOMAIN )
        ));
        return apply_filters( 'support_network_ticket_columns', $columns );
    }

    function extra_tablenav( $which ) {
        if ( 'top' == $which) {
            if ( $this->category ) {
                ?>
                    <div class="alignleft actions">
                        <?php 
                            echo sprintf( __( 'Filtering by category <a class="button" href="%s" title="remove filter">Remove filter</a>', INCSUB_SUPPORT_LANG_DOMAIN ),
                                        MU_Support_System::$network_main_menu->get_permalink()
                                ); 
                        ?>
                    </div>
                <?php
            }
            if ( is_integer( $this->ticket_status ) ) {
                ?>
                    <div class="alignleft actions">
                        <?php 
                            echo sprintf( __( 'Filtering by status <a class="button" href="%s" title="remove filter">Remove filter</a>', INCSUB_SUPPORT_LANG_DOMAIN ),
                                        MU_Support_System::$network_main_menu->get_permalink()
                                ); 
                        ?>
                    </div>
                <?php
            }
                
        }
        
    }

    function get_bulk_actions() {
        if ( 'archive' == $this->status ) {
            $actions = array(
                'delete'    => __( 'Delete', INCSUB_SUPPORT_LANG_DOMAIN )
            );
            return $actions;    
        }
        elseif ( 'active' == $this->status ) {
            $actions = array(
                'close'    => __( 'Close', INCSUB_SUPPORT_LANG_DOMAIN )
            );
            return $actions; 
        }
        return array();
        
    }

    function process_bulk_action() {
        
        //Detect when a bulk action is being triggered...
        $model = MU_Support_System_Model::get_instance();
        $link = MU_Support_System::$network_main_menu->get_permalink();

        if( 'delete' === $this->current_action() ) {

            if ( isset( $_POST['ticket'] ) && is_array( $_POST['ticket'] ) ) {
                $tickets = incsub_support_get_tickets( $_POST['ticket'] );
                foreach ( $tickets as $ticket )
                    $ticket->delete();
            }
            elseif ( isset( $_GET['tid'] ) && is_numeric( $_GET['tid'] ) ) {
                $ticket = incsub_support_get_ticket( $_GET['tid'] );
                if ( $ticket )
                    $ticket->delete();
            }

        }

        if( 'open' === $this->current_action() ) {
            if ( isset( $_POST['ticket'] ) && is_array( $_POST['ticket'] ) ) {
                $tickets = incsub_support_get_tickets( $_POST['ticket'] );
                foreach ( $tickets as $ticket )
                    $ticket->open();
            }
            elseif ( isset( $_GET['tid'] ) && is_numeric( $_GET['tid'] ) ) {
                $ticket = incsub_support_get_ticket( $_GET['tid'] );
                if ( $ticket )
                    $ticket->open();
            }
        }

        if( 'close' === $this->current_action() ) {
            if ( isset( $_POST['ticket'] ) && is_array( $_POST['ticket'] ) ) {
                $tickets = incsub_support_get_tickets( $_POST['ticket'] );
                foreach ( $tickets as $ticket ) {
                    $ticket->close();
                    incsub_support_send_user_closed_mail( $ticket->ticket_id );
                }
            }
            elseif ( isset( $_GET['tid'] ) && is_numeric( $_GET['tid'] ) ) {
                $ticket = incsub_support_get_ticket( $_GET['tid'] );
                if ( $ticket ) {
                    $ticket->close();
                    incsub_support_send_user_closed_mail( $ticket->ticket_id );
                }
            }
        }

    }

    function single_row( $item ) {
        static $row_class = '';

        $row_class = ( $row_class == '' ? ' class="alternate"' : '' );

        $background = '';
        if ( ! $item->view_by_superadmin )
            $background .= 'style="background-color:#e8f3b9" ';

        echo '<tr ' . $background . $row_class . '>';
        echo $this->single_row_columns( $item );
        echo '</tr>';
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

        $this->process_bulk_action();

        $current_page = $this->get_pagenum();

        $model = incsub_support_get_model();

        $args = array();

        // Are we filtering by category?
        if ( $this->category )
            $args['category'] = $this->category;

        if ( is_integer( $this->ticket_status ) )
            $args['ticket_status'] = $this->ticket_status;

        $args['type'] = $this->status;
        $data = $model->get_tickets_beta( ( $current_page - 1 ) * $per_page, $per_page, $args );

        $total_items = $data['total'];
        $this->items = $data['tickets'];

        $this->set_pagination_args( array(
            'total_items' => $total_items,                
            'per_page'    => $per_page,                   
            'total_pages' => ceil($total_items/$per_page) 
        ) );

    }

}
?>