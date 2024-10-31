<?php
	$rootPath = realpath(dirname(__FILE__).'/../../../');
	require_once($rootPath.'/wp-load.php');
	if (empty($_GET['q']))
		return;
	else{
		$hint = $_GET['q'];
		global $wpdb;
		$posts = $wpdb->get_results(
			'SELECT post_name FROM '.$wpdb->posts.' WHERE post_name LIKE \''.$wpdb->escape('%'.$hint.'%').'\'' 
		);
		foreach($posts as $post)
			echo $post->post_name.'
';
	}
?>