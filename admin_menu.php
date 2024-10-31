<div class="wrap">
	<h2><?php echo _e('Post Form Maker', 'postformmaker');?></h2>
	<table class="form-table">
		<thead>
			<tr>
				<th><?php _e('Post', 'postformmaker');?></th>
				<th><?php _e('Date', 'postformmaker');?></th>
				<th><?php _e('Actions', 'postformmaker');?></th>
			</tr>
		</thead>
		<tbody>
			<?php
				$string = '';
				foreach($forms as $form){
					$string .= '<tr>
	<td>'.$form->post_name.'</td>
	<td>'.date('Y-m-d', strtotime($form->post_date)).'</td>
	<td>
	<a class="button-secondary" href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=addedit&form='.$form->formId.'">'.__('Edit', 'postformmaker').'</a>
	<a class="button-secondary" href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=results&form='.$form->formId.'">'.__('See Results', 'postformmaker').'</a></td>
<tr>';
				}
				echo $string;
			?>
		</tbody>
	</table>
</div>