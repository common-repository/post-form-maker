<div class="wrap">
	<?php
		$options = get_option('postFormMakerOptions');
		$options = array_merge(
			postFormMaker::$defaultOptions,
			$options?$options:array()
		);
	?>
	<h2><?php echo _e('Post Form Maker', 'postformmaker');?></h2>
	<form method="post" action="options.php">
		<?php settings_fields( 'post-form-maker-options' );?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<td scope="row"><?php _e('Send Mail On Submit', 'postformmaker');?></td>
					<td>
						<?php _e('Yes', 'postformmaker');?> : <input type="radio" <?php echo $options['sendMail']==1?'checked="checked"':'';?> name="postFormMakerOptions[sendMail]" value="1" />
						<?php _e('No', 'postformmaker');?> : <input type="radio" <?php echo $options['sendMail']==0?'checked="checked"':'';?>  name="postFormMakerOptions[sendMail]" value="0" /></td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes', 'postformmaker') ?>" />
		</p>
	</form>
</div>