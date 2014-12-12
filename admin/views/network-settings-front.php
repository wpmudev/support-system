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
	</table>
	
	<div id="front-options" class="<?php echo $front_active ? '' : 'disabled'; ?>">
		<table class="form-table">
			
			<?php ob_start(); ?>
				<input type="checkbox" name="use_default_styles" value="true" <?php checked( $use_default_styles ); ?>/>
			<?php $this->render_row( __( 'Use Support System styles', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>

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
		</table>
		

	</div>
		
	<?php $this->render_submit_block(); ?>
</form>
<style>
	.form-table .support-create-page, 
	.form-table .support-view-page {
		display:none;
		margin-right: 10px;
	}
	
	#front-options {
		display:block;
	}
	#front-options.disabled {
		display:none;
	}
</style>
<script>
	jQuery(document).ready(function($) {

		incsub_support_toggle_buttons();

		function incsub_support_toggle_buttons() {
			var support_page_selector = $('#support_page_id');

			if ( ! support_page_selector.val() ) {
				$( '.support-create-page' )
					.css( 'display', 'inline-block' );
				$( '.support-view-page' ).hide();
			}
			else {
				$( '.support-view-page' )
					.css( 'display', 'inline-block' );
				$( '.support-create-page' ).hide();
			}
		}


		$('input[name="activate_front"]').on( 'change', function() {
			$this = $(this);

			if ( $this.is(':checked') ) {
				$('#front-options').removeClass( 'disabled' );
			}
			else {
				$('#front-options').addClass( 'disabled' );
			}
		});

	});
</script>
