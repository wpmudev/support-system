<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class MU_Support_FAQS_Table extends WP_List_Table {

    // Status variables for filtering purposes
    private $status = false;
    private $category = false;

    // All categories
    private $categories = array();

	function __construct(){
        if ( isset( $_GET['category'] ) && absint( $_GET['category'] ) )
            $this->category = absint( $_GET['category'] );

        //Set parent defaults
        parent::__construct( array(
            'singular'  => __( 'Ticket', INCSUB_SUPPORT_LANG_DOMAIN ),  
            'plural'    => __( 'Tickets', INCSUB_SUPPORT_LANG_DOMAIN ), 
            'ajax'      => false        
        ) );
        
    }

    function column_default( $item, $column_name ) {
    	
        return $item[ $column_name ];
    }

    function column_category( $item ) {
        
        $link = MU_Support_System::$network_faq_manager_menu->get_permalink();
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

    function column_question( $item ) {

        $edit_link = MU_Support_System::$network_single_faq_question_menu->get_permalink();
        $edit_link = add_query_arg( 'fid', $item['faq_id'], $edit_link );

        $delete_link = add_query_arg( 
            array( 
                'fid' => $item['faq_id'],
                'action' => 'delete'
            ) 
        );

        $actions = array(
            'edit'      => sprintf( __( '<a href="%s">Edit</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $edit_link),
            'delete'    => sprintf( __( '<a href="%s">Delete</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $delete_link)
        );

        return '<a href="' . $edit_link . '">' . stripslashes_deep( $item['question'] ) . '</a>' . $this->row_actions($actions);

    }

    function column_helpful( $item ) {
        if ( $item['help_yes'] + $item['help_no'] == 0 )
            return '0.00 %';
        else
            return number_format_i18n( ( $item['help_yes'] / ( $item['help_yes'] + $item['help_no'] ) )  * 100, 2 ) . ' %';
    }

    function column_no_helpful( $item ) {
        if ( $item['help_yes'] + $item['help_no'] == 0 )
            return '0.00 %';
        else
            return number_format_i18n( ( $item['help_no'] / ( $item['help_yes'] + $item['help_no'] ) )  * 100, 2 ) . ' %';
    }


    function get_columns(){
        $columns = array(
            'faq_id'		    => __( 'Question ID', INCSUB_SUPPORT_LANG_DOMAIN ),
            'question'	    => __( 'Question', INCSUB_SUPPORT_LANG_DOMAIN ),
            'category'      => __( 'Category', INCSUB_SUPPORT_LANG_DOMAIN ),
            'helpful'       => __( 'Think is helpful', INCSUB_SUPPORT_LANG_DOMAIN ),
            'no_helpful'    => __( 'Think is not helpful', INCSUB_SUPPORT_LANG_DOMAIN )
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
                                        MU_Support_System::$network_faq_manager_menu->get_permalink()
                                ); 
                        ?>
                    </div>
                <?php
            }
                
        }
        
    }

    function prepare_items() {

    	$per_page = 10;

    	$columns = $this->get_columns();
        $hidden = array( 'faq_id' );
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

        $data = $model->get_questions( ($current_page - 1) * $per_page, $per_page, $args );

        $total_items = $data['total'];
        $this->items = $data['results'];
        
        // Categories
        $categories = $model->get_faq_categories();
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