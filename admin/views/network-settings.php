<?php if ( $errors ): ?>
	<?php foreach ( $errors as $error ): ?>
		<div class="error">
			<p><?php echo esc_html( $error['message'] ); ?></p>
		</div>
	<?php endforeach; ?>
<?php elseif ( $updated ): ?>
	<div class="updated">
		<p><?php _e( 'Settings updated', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
	</div>
<?php endif; ?>

<form method="post" action="">
	<h3><?php _e( 'General Settings', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h3>
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
			
		    	<?php foreach ( $roles as $key => $value ):	?>
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

		    <?php $this->render_row( __( 'User roles that can see the FAQs <span class="description">(uncheck all if you want to disable this feature)</span>', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );

		    if ( is_plugin_active( 'pro-sites/pro-sites.php' ) ) {
			    ob_start();
			    ?>
			    	<p><label for="pro_sites">
			    		<input type="checkbox" id="pro_sites" name="pro_sites" <?php checked( $allow_only_pro_sites ); ?>>
			    		<span> <?php _e( 'Check and select a minimum Pro Site Level to allow <strong>Support Tickets</strong> in a blog (if unchecked, Support will be available for any blog)', INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
			    	</label></p>
			    	<p><label for="pro_sites_levels">
			    		<?php psts_levels_select( 'pro_sites_levels', $pro_sites_level ); ?> 
			    		<span class="description"><?php _e( 'Minimum Pro Site Level', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
			    	</label></p>

			    	<p><label for="pro_sites_faq">
			    		<input type="checkbox" id="pro_sites_faq" name="pro_sites_faq" <?php checked( $allow_only_pro_sites_faq ); ?>>
			    		<span> <?php _e( 'Check and select a minimum Pro Site Level to allow <strong>Support FAQ</strong> in a blog (if unchecked, Support FAQ will be available for any blog)', INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
			    	</label></p>
			    	<p><label for="pro_sites_faq_levels">
			    		<?php psts_levels_select( 'pro_sites_faq_levels', $pro_sites_faq_level ); ?> 
			    		<span class="description"><?php _e( 'Minimum Pro Site Level', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
			    	</label></p>
			    <?php
		    	$this->render_row( __( 'Pro Sites Integration', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );
			}
			?>
		</table>

		<h3><?php _e( 'Front', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h3>
		<table class="form-table">
			<?php $this->render_row( __( 'Support Page', INCSUB_SUPPORT_LANG_DOMAIN ), $pages_dropdown ); ?>

			<?php ob_start(); ?>
				<input type="number" class="small-text" value="<?php echo absint( $blog_id ); ?>" name="support_blog_id" />
				<span class="description"><?php _e( 'Support System allows to display tickets in the front in one of your sites...' ); ?></span>
			<?php $this->render_row( __( 'Blog ID', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>

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

		
	<p class="submit">
		<?php wp_nonce_field( 'do-support-settings' ); ?>
		<?php submit_button( __( 'Save changes', INCSUB_SUPPORT_LANG_DOMAIN ), 'primary', 'submit', false ); ?>
	</p>
</form>