<div class="wrap">
	<h2><?php echo _e('Post Form Maker', 'postformmaker');?></h2>
	<?php
		$string = '';
		if ( empty( $stats ) ){
			$string .= '<h3>'.__('No form has been selected, please select one to see the results', 'postformmaker').'</h3><br/>
			<a class="button-primary" href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=postFormMaker/postFormMaker.php">'.__('Go To Form List', 'postformmaker').'</a>';
		} else {
			// Header
			$string .= '<h3>'.__('Post').' : '.$postName.'</h3>';
			// Making rendering
			foreach( $stats as $name=>$values ){
				$string .= '<h3 class="header-results">'.$name.'</h3><table class="form-results">
			<thead>
				<tr>
					<th>'.__('Name', 'postformmaker').'</th>
					<th>'.__('Total', 'postformmaker').'</th>
					<th>'.__('Percent', 'postformmaker').'</th>
				</tr>
			</thead>
			<tbody>';
				foreach( $values as $vname=>$value )
					$string .= '<tr>
		<td>'.$vname.'</td>
		<td>'.$value.'</td>
		<td>'.(empty($voteNb)?'oo':round($value*10000/$voteNb, 2)/100).'%</td>
		</tr>';
				$string .= '</tbody>
		</table>';
			}
			// Sum up
			$string .= '<table class="form-results">
			<tbody>
				<tr>
					<td>'.__('Total votes', 'postformmaker').' :</td>
					<td>'.$voteNb.'</td>
				</tr>
			</tbody>
		</table>';
		}
		echo $string;
	?>
</div>