<div class="wrap">
	<script type="text/javascript">
	jQuery(function($){
		$("#formEditor").formEditor();
		$("#post_hint").autocomplete({
			url:'<?php bloginfo('wpurl'); echo '/'.postFormMaker::getInstance()->pluginDir;?>/autocomplete_post.php',
			types:<?php echo json_encode($fieldTypes);?>
		});
	});
	</script>
	<style>
		#formEditor tbody td:first-child, #formEditor thead th:first-child{
			width:10%;
			text-align:right;
		}
	</style>
	<h2><?php echo _e('Post Form Maker', 'postformmaker');?></h2>
	<h3><?php echo _e('Make a form', 'postformmaker');?></h3>
	<form action="#" method="post">
		<table id="formEditor" class="form-table form-editor">
			<thead>
				<tr>
					<th><?php _e('Title', 'postformmaker');?> :</th>
					<th colspan="3"><input type="text" name="title" value="<?php echo stripslashes( $datas['title'] );?>" /></th>
				</tr>
				<tr>
					<th><?php _e('Limitation', 'postformmaker');?> :</th>
					<th colspan="3"><select name="limitation">
						<?php
							$string = '';
							foreach( postFormMaker::$submitLimitationValues as $limit)
								$string .= '<option'.($limit == $datas['limitation']?' selected="selected"':'').' value="'.$limit.'">'.__($limit, 'postformmaker').'</option>';
							echo $string;
						?>
					</select></th>
				</tr>
				<tr>
					<th><?php _e('Post', 'postformmaker');?> :</th>
					<th colspan="3">
						<input name="post_hint" id="post_hint" value="<?php echo $datas['post_hint'];?>" />
					</th>
				</tr>
				<tr>
					<th><span class="addField button-primary">+</span></th>
					<th><?php _e('Label', 'postformmaker');?></th>
					<th><?php _e('Type', 'postformmaker');?></th>
					<th><?php _e('Values', 'postformmaker');?></th>
				</tr>
			</thead>
			<tbody>
				<?php echo postFormMaker::getInstance()->renderFields($datas['fields']);?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="4"><input type="submit" class="button-primary" value="<?php _e('Save Changes', 'postformmaker') ?>" /></td>
				</tr>
			</tfoot>
		</table>
	</form>
	<form action="#" method="post">
		<input type="hidden" name="formToDelete" value="<?php echo $datas['formId'];?>"/>
		<div>
			<input style="display:block;float:left;" type="submit" class="button-primary" value="<?php _e('Delete', 'postformmaker') ?>" />
			<a style="display:block;float:left;" class="button-primary" href=""><?php _e('New Form', 'postformmaker');?></a>
			<div style="clear:both;"></div>
		</div>
	</form>
</div>