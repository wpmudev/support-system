<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class MU_Support_Ticket_History_Table extends WP_List_Table {

    private $data;
    private $create_faq;

	function __construct( $data, $create_faq = true ){

        $this->data = $data;

        $this->create_faq = $create_faq;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => __( 'Ticket', INCSUB_SUPPORT_LANG_DOMAIN ),  
            'plural'    => __( 'Tickets', INCSUB_SUPPORT_LANG_DOMAIN ), 
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
            'poster'        => __( 'Author', INCSUB_SUPPORT_LANG_DOMAIN ),
            'message'       => __( 'Ticket Message/Reply', INCSUB_SUPPORT_LANG_DOMAIN ),
            'date'          => __( 'Date/Time', INCSUB_SUPPORT_LANG_DOMAIN ),
        );

        if ( $this->create_faq )
            $columns['create_faq'] = '';

        return $columns;
    }

    function column_poster( $item ) {
        if ( ! empty( $item['reporting_name'] ) ) {
            $avatar_id = $item['user_avatar_id'];
            $display_name = $item['reporting_name'] ."<br /><br />";
        } elseif ( ! empty( $item['staff_member'] ) ) {
            $avatar_id = $item['admin_avatar_id'];
            $display_name = $item['staff_member'] ."<br /><br />";
        } else {
            $avatar_id = "";
            $display_name = __("User", INCSUB_SUPPORT_LANG_DOMAIN);
        }
        if ( function_exists("get_avatar") ) {
            // check for blog avatar function, as get_avatar is too common.
            $avatar = get_avatar( $avatar_id,'32','gravatar_default' );
        }
        
        return '<p>' . $avatar . '<strong style="display:block;vertical-align:middle">' . $display_name . '</strong></p>';
    }

    function column_create_faq( $item ) {
        ob_start();
        $link = MU_Support_System::$network_single_faq_question_menu->get_permalink();
        $link = add_query_arg( 'action', 'new', $link );
        $link = add_query_arg( 'tid', absint( $item['message_id'] ), $link );
        ?>
            <a title="<?php _e( 'Create a FAQ from this response', INCSUB_SUPPORT_LANG_DOMAIN ); ?>"
                href="<?php echo $link; ?>"><?php _e( 'Create a FAQ', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a>

        <?php
        return ob_get_clean();
    }

    function column_date( $item ) {
        return date_i18n(get_option("date_format") ." ". get_option("time_format"), strtotime( $item['ticket_updated'] ), true ); 
    }

    function column_message( $item ) {
        ob_start();
        ?>
            <h4><?php echo $item['subject']; ?></h4>
            <p><?php echo $item['message']; ?></p>
            <?php if ( ! empty( $item['attachments'] ) ): ?>
                <div class="ticket-acttachments-wrap" style="background: #EBEBEB; padding: 15px; margin-top: 20px; display: inline-block; margin-bottom: 20px; border:1px solid #DADADA; border-radius:3px">
                    <h4><?php _e( 'Attachments', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h4>
                    <ul class="ticket-acttachments" >
                        <?php foreach ( $item['attachments'] as $attachment ): ?>
                            <li class="ticket-attachment-item"><a href="<?php echo $attachment; ?>" title="<?php _e( 'Download file', INCSUB_SUPPORT_LANG_DOMAIN ); ?>"><?php echo basename( $attachment ); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    function prepare_items() {

    	$per_page = 100;

    	$columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array(
        	$columns, 
        	$hidden, 
        	$sortable
        );

        $current_page = $this->get_pagenum();


        $total_items = count( $this->data );

        $this->data = array_slice( $this->data, ( ( $current_page - 1 ) * $per_page ), $per_page );
        $this->items = $this->data;

        $this->set_pagination_args( array(
            'total_items' => $total_items,                
            'per_page'    => $per_page,                   
            'total_pages' => ceil($total_items/$per_page) 
        ) );

    }

}
?>