<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class MU_Support_Tickets_Table extends WP_List_Table {

    // Status variables for filtering purposes
    private $status = false;
    private $category = false;
    private $ticket_status = false;

    // All categories
    private $categories = array();

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
    		case 'id'			: $value = (int)$item['ticket_id']; break;
            case 'priority' 	: $value = MU_Support_System::$ticket_priority[ (int)$item['ticket_priority'] ]; break;
            case 'staff'		: $value = empty( $item['display_name'] ) ? __( 'Not yet assigned', INCSUB_SUPPORT_LANG_DOMAIN ) : $item['display_name']; break;            	
    	}
        return $value;
    }

    function column_status( $item ) {
        $link = add_query_arg( 'ticket_status', absint( $item['ticket_status'] ) );
        return '<a href="' . $link . '">' . MU_Support_System::$ticket_status[ (int)$item['ticket_status'] ] . '</a>';
    }

    function column_category( $item ) {
        
        $link = MU_Support_System::$network_main_menu->get_permalink();
        $link = add_query_arg( 'view', $this->status, $link );
        $link = add_query_arg( 'category', $item['cat_id'], $link );

        ob_start();
            ?>
                <a href="<?php echo $link; ?>">
                    <?php echo $this->categories[ $item['cat_id'] ]; ?>
                </a>
            <?php
        return ob_get_clean();
    }

    function column_subject( $item ) {

        // Link to the single ticket page
        $link = add_query_arg(
            'tid',
            (int)$item['ticket_id'],
            MU_Support_System::$network_single_ticket_menu->get_permalink()
        );
        return '<a href="' . $link . '">' . stripslashes_deep( $item['title'] ) . '</a>'; 
    }

    function column_submitted( $item ) {
        $blog_details = get_blog_details( array( 'blog_id' => (int)$item['blog_id'] ) );
        $value = __( 'Unknown', INCSUB_SUPPORT_LANG_DOMAIN );
        if ( ! empty( $blog_details ) )
            $value = '<a href="' . get_site_url( $item['blog_id'] ) . '">' . $blog_details->blogname . '</a>';

        return $value;
    }

    function column_updated( $item ) {
        return date_i18n(get_option("date_format") ." ". get_option("time_format"), strtotime( $item['ticket_updated'] ), true ); 
    }


    function get_columns(){
        $columns = array(
            'id'		=> __( 'Ticket ID', INCSUB_SUPPORT_LANG_DOMAIN ),
            'subject'	=> __( 'Subject', INCSUB_SUPPORT_LANG_DOMAIN ),
            'status'	=> __( 'Status', INCSUB_SUPPORT_LANG_DOMAIN ),
            'priority'  => __( 'Priority', INCSUB_SUPPORT_LANG_DOMAIN ),
            'category'  => __( 'Category', INCSUB_SUPPORT_LANG_DOMAIN ),
            'staff'		=> __( 'Staff Member', INCSUB_SUPPORT_LANG_DOMAIN ),
            'submitted' => __( 'Submitted From', INCSUB_SUPPORT_LANG_DOMAIN ),
            'updated'	=> __( 'Last updated (GMT)', INCSUB_SUPPORT_LANG_DOMAIN )
        );
        return $columns;
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

        $model = MU_Support_System_Model::get_instance();

        $args = array();

        // Are we filtering by category?
        if ( $this->category )
            $args['category'] = $this->category;

        if ( is_integer( $this->ticket_status ) )
            $args['ticket_status'] = $this->ticket_status;

        $data = $model->get_tickets( $this->status, ($current_page - 1) * $per_page, $per_page, $args );

        $total_items = $data['total'];

        $this->items = $data['results'];

        // Categories
        $categories = $model->get_ticket_categories();
        foreach ( $categories as $category ) {
            $this->categories[ $category['cat_id'] ] = $category['cat_name'];
        }

        $this->set_pagination_args( array(
            'total_items' => $total_items,                
            'per_page'    => $per_page,                   
            'total_pages' => ceil($total_items/$per_page) 
        ) );

    }

}
?>