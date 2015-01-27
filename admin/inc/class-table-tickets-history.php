<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Incsub_Support_Tickets_History_Table extends WP_List_Table {

    private $ticket_id;

	function __construct( $args = array() ){
        //Set parent defaults
        parent::__construct( array(
            'singular'  => __( 'Ticket History', INCSUB_SUPPORT_LANG_DOMAIN ),  
            'plural'    => __( 'Tickets History', INCSUB_SUPPORT_LANG_DOMAIN ), 
            'ajax'      => false        
        ) );
        
    }

    public function set_ticket( $ticket_id ) {
        $this->ticket_id = $ticket_id;
    }

    function column_default( $item, $column_name ){

        $value = '';
    	switch ( $column_name ) {
            default		: $value = $item->$column_name; break;
    	}
        return $value;
    }


    function get_columns(){
        $columns = array(
            'poster'        => __( 'Author', INCSUB_SUPPORT_LANG_DOMAIN ),
            'message'       => __( 'Ticket Message/Reply', INCSUB_SUPPORT_LANG_DOMAIN ),
        );

        if ( incsub_support_current_user_can( 'insert_faq' ) ) {
            $columns['create_faq'] = '';
        }

        return $columns;
    }

        public function display() {

        ?>
            <table class="<?php echo implode( ' ', $this->get_table_classes() ); ?>">

                <tbody id="ticket-replies-list">
                    <?php $this->display_rows_or_placeholder(); ?>
                </tbody>

            </table>


            <style>
                #ticket-replies-list td {
                    border-bottom:1px solid #E5E5E5;
                }
            </style>
        <?php

    }

    function column_poster( $item ) {
        $poster = get_userdata( $item->get_poster_id() );
        if ( ! $poster )
            return __( 'Unknown user', INCSUB_SUPPORT_LANG_DOMAIN );

        if ( function_exists( "get_avatar" ) )
            $avatar = get_avatar( $poster->ID, 32 );
        
        return '<p>' . $avatar . '<div><strong>' . $poster->data->display_name . '</strong></div></p>';
    }

    function column_create_faq( $item ) {
        ob_start();
        $link = add_query_arg( 'action', 'create-faq-from-ticket' );
        $link = add_query_arg( 'tid', absint( $this->ticket_id ), $link );
        $link = add_query_arg( 'rid', absint( $item->message_id ), $link );
        $link = wp_nonce_url( $link, 'create-faq-from-ticket-' . $this->ticket_id . '-' . $item->message_id );
        ?>
            <a title="<?php _e( 'Create a FAQ from this response', INCSUB_SUPPORT_LANG_DOMAIN ); ?>"
                href="<?php echo $link; ?>"><?php _e( 'Create a FAQ', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a>

        <?php
        return ob_get_clean();
    }

    function column_message( $item ) {
        ob_start();
        ?>
            <div class="submitted-on"><?php printf( _x( 'Sent on %s', 'Reply sent on date', INCSUB_SUPPORT_LANG_DOMAIN ), incsub_support_get_translated_date( $item->message_date ) ); ?></div>
            <?php if ( $item->is_main_reply ): ?>
                <h3 class="support-system-reply-subject"><?php echo $item->subject; ?></h3>
            <?php endif; ?>
            <p><?php echo $item->message; ?></p>
            <?php if ( ! empty( $item->attachments ) ): ?>
                <div class="ticket-acttachments-wrap">
                    <h4><?php _e( 'Attachments', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h4>
                    <ul class="ticket-acttachments" >
                        <?php foreach ( $item->attachments as $attachment ): ?>
                            <li class="ticket-attachment-item"><a href="<?php echo $attachment; ?>" title="<?php _e( 'Download file', INCSUB_SUPPORT_LANG_DOMAIN ); ?>"><?php echo basename( $attachment ); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    function prepare_items() {

    	$columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array(
        	$columns, 
        	$hidden, 
        	$sortable
        );

        $this->items = incsub_support_get_ticket_replies( $this->ticket_id );

        $total_items = count( $this->items );
        $per_page = $total_items;
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                
            'per_page'    => $per_page,                   
            'total_pages' => ceil($total_items/$per_page) 
        ) );

    }

}
?>