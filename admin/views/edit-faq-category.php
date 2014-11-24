<?php if ( $updated ): ?>
	<div class="updated"><p><?php _e( 'Category updated', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p></div>
<?php endif; ?>

<form id="categories-table-form" action="" method="post">
	<table class="form-table">
		<?php ob_start(); ?>
			<input type="text" name="cat_name" value="<?php echo esc_attr( $category_name ); ?>">
		<?php $this->render_row( __( 'Category name', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>
	</table>
	<input type="hidden" name="faq_cat_id" value="<?php echo esc_attr( $faq_category->cat_id ); ?>">
	<?php wp_nonce_field( 'edit-faq-category-' . $faq_category->cat_id, '_wpnonce' ); ?>
	<?php submit_button( null, 'primary', 'submit-edit-faq-category' ); ?>
</form>