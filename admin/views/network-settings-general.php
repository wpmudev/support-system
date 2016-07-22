<?php if ( $errors ): ?>
	<?php foreach ( $errors as $error ): ?>
		<div class="error">
			<p><?php echo esc_html( $error['message'] ); ?></p>
		</div>
	<?php endforeach; ?>
<?php endif; ?>

<form method="post" action="">
	<table class="form-table">
		<?php
			ob_start();
		    ?>
				<input type="text" class="regular-text" name="menu_name" value="<?php echo esc_attr( $menu_name ); ?>">
				<span class="description"><?php _e("Change the text of the 'Support' menu item to anything you need.", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
		    <?php
		    $this->render_row( __( 'Support menu name', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );

		    ob_start();
		    ?>
				<input type="text" class="regular-text" name="from_name" value="<?php echo esc_attr( $from_name ); ?>">
				<span class="description"><?php _e("Support mail from name.", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
		    <?php
		    $this->render_row( __( 'Support from name', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );

		    ob_start();
		    ?>
				<input type="text" class="regular-text" name="from_mail" value="<?php echo esc_attr( $from_email ); ?>">
				<span class="description"><?php _e("Support mail from address.", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
		    <?php
		    $this->render_row( __( 'Support from e-mail', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );

		    ob_start(); 
		    ?>
		    	<?php echo $staff_dropdown; ?>
		    	<span class="description"> <?php _e( 'If the ticket is not assigned to any staff member, This will be the Administrator who receives all emails with any updates about the ticket', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
		    <?php $this->render_row( __( 'Main Administrator', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>
	</table>

	<h3><?php _e( 'Permissions Settings', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h3>
	<table class="form-table">
	    
	    <?php ob_start(); ?>
		
	    	<?php foreach ( $roles as $key => $value ): if( $key == 'support-guest' ) continue;	?>
	    		<label for="tickets_role_<?php echo $key; ?>">						    		
    				<input type="checkbox" value="<?php echo $key; ?>" id="tickets_role_<?php echo $key; ?>" name="tickets_role[]" <?php checked( in_array( $key, $tickets_role ) ); ?> /> <?php echo $value; ?><br/>
	    		</label>
	    	<?php endforeach; ?>

	    <?php $this->render_row( __( 'User roles that can open/see tickets.', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );

	    	ob_start();
	    ?>
	    	<?php foreach ( $roles as $key => $value ): ?>
	    		<label for="faqs_role_<?php echo $key; ?>">
    				<input type="checkbox" value="<?php echo $key; ?>" id="faqs_role_<?php echo $key; ?>" name="faqs_role[]" <?php checked( in_array( $key, $faqs_role ) ); ?> /> <?php echo $value; ?><br/>
	    		</label>
	    	<?php endforeach; ?>

	    <?php $this->render_row( __( 'User roles that can see the FAQs <span class="description">(uncheck all if you want to disable this feature)</span>', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>

	</table>


	<h3><?php _e( 'Privacy Settings', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h3>
	<table class="form-table">
		<?php ob_start(); ?>
	    	<select name="privacy" id="privacy">
	    		<?php foreach ( MU_Support_System::$privacy as $key => $value ): ?>
	    			<option value="<?php echo $key; ?>" <?php selected( $ticket_privacy, $key ); ?>><?php echo $value; ?></option>
	    		<?php endforeach; ?>
	    	</select>
	    <?php $this->render_row( __( 'Privacy', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>
	</table>

	<?php do_action( 'support_sytem_general_settings' ); ?>

		
	<?php $this->render_submit_block(); ?>
</form>