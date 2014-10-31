<?php if ( $added ): ?>
	<div class="updated"><p><?php _e( 'Category added', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p></div>
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
					<?php wp_nonce_field( 'add-ticket-category' ); ?>
					<div class="form-field">
						<label for="cat_name"><?php _e( 'Category Name', INCSUB_SUPPORT_LANG_DOMAIN ); ?></label>
						<input name="cat_name" id="cat_name" type="text" value="<?php echo $category_name; ?>" size="40" aria-required="true"><br/>
						<p><?php _e('The name is used to identify the category to which tickets relate', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
					</div>
					<div class="form-field">
						<label for="admin_user"><?php _e( 'Assign to user', INCSUB_SUPPORT_LANG_DOMAIN ); ?></label>
						<?php echo $super_admins_dropdown; ?>
						<p><?php _e( 'Any new opened ticket with this category will be assigned to this user', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
					</div>
					<?php submit_button( __( 'Add New Category', INCSUB_SUPPORT_LANG_DOMAIN ), 'primary', 'submit-new-ticket-category' ); ?>
				</form>
			</div>
		</div>
	</div>
</div>