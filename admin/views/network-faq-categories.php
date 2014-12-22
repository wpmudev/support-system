<?php if ( $added ): ?>
	<div class="updated"><p><?php _e( 'Category added', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p></div>
<?php elseif ( $updated ): ?>
	<div class="updated"><p><?php _e( 'Category updated', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p></div>
<?php endif; ?>

<br class="clear">
<div id="col-container">
	<div id="col-right">
		<div class="col-wrap">
			<div class="form-wrap">
				<form id="categories-table-form" action="" method="post">
					<?php $cats_table->display(); ?>
				</form>
			</div>
		</div>
	</div>
	<div id="col-left">
		<div class="col-wrap">
			<div class="form-wrap">
				<h3><?php _e( 'Add new category', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h3>
				<form id="categories-table-form" action="" method="post">
					<?php wp_nonce_field( 'add-faq-category' ); ?>
					<div class="form-field">
						<label for="cat_name"><?php _e( 'Category Name', INCSUB_SUPPORT_LANG_DOMAIN ); ?></label>
						<input name="cat_name" id="cat_name" type="text" value="<?php echo $category_name; ?>" size="40" aria-required="true"><br/>
						<p><?php _e('The name is used to identify the category to which FAQs relate', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
					</div>
					<?php submit_button( __( 'Add New Category', INCSUB_SUPPORT_LANG_DOMAIN ), 'primary', 'submit-new-faq-category' ); ?>
				</form>
			</div>
		</div>
	</div>
</div>