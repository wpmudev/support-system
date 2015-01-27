<ul class="ticket-fields">
	<?php foreach ( $fields as $field_id => $field ): ?>
		<li id="<?php echo esc_attr( $field_id ); ?>">
			<h3 class="ticket-field-label"><?php echo $field['label']; ?></h3> 
			<p class="ticket-field-value"><?php echo $field['content']; ?></p>
		</li>
	<?php endforeach; ?>
	<div class="clear"></div>
</ul>