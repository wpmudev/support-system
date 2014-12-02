<?php if ( $errors ): ?>
	<?php foreach ( $errors as $error ): ?>
		<div class="error">
			<p><?php echo esc_html( $error['message'] ); ?></p>
		</div>
	<?php endforeach; ?>
<?php endif; ?>

<form method="post" action="">
	<table class="form-table">
		
		<?php ob_start(); ?>
			<input type="checkbox" name="activate_front" value="true" <?php checked( $front_active ); ?>/>
		<?php $this->render_row( __( 'Activate Front End', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>

		<?php if ( $front_active ): ?>

			<?php if ( is_multisite() ): ?>
				<?php ob_start(); ?>
					<input type="number" class="small-text" value="<?php echo $blog_id; ?>" name="support_blog_id" />
					<span class="description"><?php _e( 'Support System allows to display tickets in the front in one of your sites...' ); ?></span>
				<?php $this->render_row( __( 'Blog ID', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>
			<?php endif; ?>

			<?php if ( $pages_dropdowns ): ?>
				<?php $this->render_row( __( 'Support Page', INCSUB_SUPPORT_LANG_DOMAIN ), $support_pages_dropdown ); ?>
				<?php $this->render_row( __( 'Submit new ticket Page', INCSUB_SUPPORT_LANG_DOMAIN ), $submit_ticket_pages_dropdown ); ?>
			<?php endif; ?>

		<?php endif; ?>
		

	</table>
		
	<?php $this->render_submit_block(); ?>
</form>
