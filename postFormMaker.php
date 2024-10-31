<?php
/*
Plugin Name: postFormMaker
Plugin URI: http://fredpointzero.com/plugins-wordpress/post-form-maker/
Description: Create form inside posts
Version: 0.1.2
Author: Frederic Vauchelles, Cathy Vauchelles
Author URI: http://fredpointzero.com
Text Domain: postformmaker

  Copyright 2009 postFormMaker  (email : fredpointzero@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
class postFormMaker {
	// Singleton pattern
	private function __construct(){
		global $wpdb;
		$this->wpdb = $wpdb;
		
		preg_match( '/^.*(wp-content.*)$/', dirname(__FILE__), $matches );
		$this->pluginDir = $matches[1];
		
		// Hook - register db install
		register_activation_hook( __FILE__, array( $this, 'createDB' ) );
		add_option('postFormMakerDbBVersion', '0.1');
		add_option('postFormMakerOptions');
		add_filter('the_posts', array( &$this, 'the_posts' ) );
		// Hook - process request on init
		add_action( 'init', array( $this, 'processRequest' ) );
		add_action( 'init', array( $this, 'init' ) );
		
		if ( is_admin() ){
			// Admin generation menu
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		} else {
			add_action( 'init', array( $this, 'main_init' ) );
		}
	}
	
	private $wpdb = null;
	public $pluginDir = null;
	
	private static $instance = null;
	public static function getInstance(){
		if ( null === self::$instance )
			self::$instance = new postFormMaker();
		return self::$instance;
	}
	public function init() {
		load_plugin_textdomain( 'postformmaker', null, 'postFormMaker/lang' );
	}
	// Hook - init (include default css and locale)
	public function main_init(){
		wp_enqueue_style(
			'postFormMaker.default',
			'/'.$this->pluginDir.'/css/default.css'
		);
	}
	// Filters
	/**
	 *	Filter of the_posts hook
	 */
	public function the_posts( $posts ){
		foreach( $posts as $key=>$post ){
			$form = $this->getFormByPost( $post->ID );
			if ( null != $form )
				$posts[$key]->post_content = $post->post_content.$this->renderForm( $form );
		}
		return $posts;
	}
	// Process requests
	/**
	 *	Check if user can submit the form
	 *	@return null on failure, string of the search pattern on success
	 */
	private function checkAuth( $form ){
		// Check if user can submit info
		if ( $form->limitation == 'IP' ){
			$search = $_SERVER['REMOTE_ADDR'];
		} elseif ( $form->limitation == 'IPPerDay' ){
			$search = array( $_SERVER['REMOTE_ADDR'], date('Y-m-d', time()) );
		} else
			return null;
		$search = serialize( $search );
		// Search existing entries
		$entry = $this->wpdb->get_row( 'SELECT * FROM '.$this->wpdb->prefix.'formresults WHERE extra="'.$this->wpdb->escape( $search ).'" AND form="'.$this->wpdb->escape( $form->formId ).'"' );
		if ( !empty( $entry ) )
			return null;
		else
			return $search;
	}
	/**
	 *	@return 1 on success, null on failure
	 */
	public function processRequest(){
		if ( !empty ($_POST['postForm'] ) ){
			// Get form info
			$form = $this->wpdb->get_results(
				'SELECT * FROM '.$this->wpdb->prefix.'form WHERE formId="'.$this->wpdb->escape($_POST['postForm']).'"'
			);
			$form = $form[0];
			// No form found, exiting
			if ( empty( $form ) )
				return;
			else {
				$search = $this->checkAuth( $form );
				if ( $search == null )
					return;
				// Get results
				$fields = empty( $form->fields )?array():unserialize( $form->fields );
				$result = array();
				if ( !empty( $fields['name'] ) ){
					foreach( $fields['name'] as $name ){
						$safeName = $this->safeInputName( $name );
						$result[$name] = empty( $_POST[$safeName] )?null:$_POST[$safeName];
					}
				}
				// Insert results
				$this->wpdb->insert(
					$this->wpdb->prefix.'formresults',
					array(
						'form' => $form->formId,
						'results' => serialize( $result ),
						'extra' => $search
					)
				);
				$options = get_option('postFormMakerOptions');
				if ( $optiond['sendMail'] == 1 )
					$this->mailSubmit( $result, $form->formId );
				return 1;
			}
		}
	}
	// Constants
	private static $fieldTypes = array(
		'text',
		'radio',
		'select',
		'checkbox'
	);
	public static $defaultOptions = array(
		'sendMail' => 0
	);
	public static $submitLimitationValues = array(
		'IP',
		'IPPerDay'
	);
	// DB install
	public function createDB(){
		// Table names to install
		$tables = array(
			'form' => 'formId int(11) NOT NULL AUTO_INCREMENT,
fields text NOT NULL,
post int(11) NOT NULL,
limitation varchar(255) NOT NULL DEFAULT "IP",
title varchar(255) NOT NULL,
PRIMARY KEY  (formId)',
			'formResults' => 'formResultId int(11) NOT NULL AUTO_INCREMENT,
form int(11) NOT NULL,
results text NOT NULL,
extra text NULL DEFAULT NULL,
PRIMARY KEY  (formResultId)'
		);
		$queries = '';
		foreach( $tables as $name=>$content ){
			// Check if table already exists
			if( $this->wpdb->get_var( 'SHOW TABLES LIKE \''.$this->wpdb->prefix.$name.'\'' ) != $this->wpdb->prefix.$name )
				$queries .= 'CREATE TABLE '.$this->wpdb->prefix.$name.' (
'.$content.'
) DEFAULT CHARACTER SET UTF8 ;';
		}
		// Require db delta for table generation
		require_once( ABSPATH.'wp-admin/includes/upgrade.php' );
		dbDelta( $queries );
	}
	
	// Admin menu
	public function admin_menu(){
		add_menu_page( __('Post Form Maker', 'postformmaker'), __('Post Form Maker', 'postformmaker'), 8, __FILE__, array( $this, 'admin_main_menu' ) );
		add_submenu_page(__FILE__, __('Post Form Maker', 'postformmaker'), __('Add/Edit Forms', 'postformmaker'), 8, 'addedit', array( $this, 'admin_addedit_menu') );
		add_submenu_page(__FILE__, __('Post Form Maker', 'postformmaker'), __('Results', 'postformmaker'), 8, 'results', array( $this, 'admin_results_menu') );
		add_submenu_page(__FILE__, __('Post Form Maker', 'postformmaker'), __('Options', 'postformmaker'), 8, 'options', array( $this, 'admin_options_menu') );
	}
	public function admin_main_menu(){
		$forms = $this->getForms(array());
		include( 'admin_menu.php' );
	}
	public function admin_options_menu(){
		include( 'admin_options.php' );
	}
	public function admin_addedit_menu(){
		// Delete action
		if (!empty($_POST['formToDelete']))
			$this->deleteFormByPK($_POST['formToDelete']);
		// Add/edit action
		$datas = array(
			'fields' => array(),
			'post_hint' => '',
			'formId' => 0,
			'title' => ''
		);
		if (!(empty($_POST['fields']) || empty($_POST['post_hint']))){
			$id = $this->addEditForm($_POST['fields'], $_POST['post_hint'], $_POST['limitation'], $_POST['title']);
			$datas = $_POST;
			$datas['formId'] = $id;
		} elseif (!empty($_GET['form'])){
			$form = $this->getForm($_GET['form']);
			if (null != $form){
				$datas['fields'] = empty($form->fields)?array():unserialize($form->fields);
				$datas['post_hint'] = $this->getPostName($form->post);
				$datas['formId'] = $form->formId;
				$datas['limitation'] = $form->limitation;
				$datas['title'] = $form->title;
			}
		}
		$fieldTypes = self::$fieldTypes;
		include( 'admin_addedit.php' );
	}
	public function admin_results_menu(){
		$post = 0;
		$form = 0;
		if ( !empty( $_GET['form'] ) && is_numeric( $_GET['form'] ) ){
			$form = $_GET['form'];
			$post = $this->wpdb->get_var( 'SELECT post FROM '.$this->wpdb->prefix.'form WHERE formId="'.$this->wpdb->escape( $_GET['form'] ).'"' );
			if ( empty( $post ) )
				$post = 0;
		}
		list( $stats, $voteNb ) = $this->getProcessedResults( $form );
		$postName = '';
		if ( !empty( $post ) )
			$postName = $this->wpdb->get_var( 'SELECT post_name FROM '.$this->wpdb->posts.' WHERE ID="'.$this->wpdb->escape( $post ).'"' );
		include( 'admin_results.php' );
	}
	// Admin init
	public function admin_init(){
		register_setting( 'post-form-maker-options', 'postFormMakerOptions' );
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script(
			'jquery-formEditor',
			'/'.$this->pluginDir.'/js/jquery.formEditor.js',
			array( 'jquery', 'jquery-ui-core' )
		);
		wp_enqueue_script(
			'jquery-ui-autocomplete',
			'/'.$this->pluginDir.'/js/ui.autocomplete.js',
			array( 'jquery-ui-core' )
		);
		wp_enqueue_style(
			'jquery-ui-autocomplete',
			'/'.$this->pluginDir.'/js/ui.autocomplete.css'
		);
		wp_enqueue_style(
			'postFormMaker.default',
			'/'.$this->pluginDir.'/css/default_admin.css'
		);
	}
	// Model functions
	/**
	 *	@param int $id : id of the post
	 *	@return string : post_name or null
	 */
	private function getPostName( $id ){
		$post = $this->wpdb->get_var( 'SELECT post_name FROM '.$this->wpdb->posts.' WHERE ID="'.$this->wpdb->escape( $id ).'"');
		return empty( $post )?'':$post;
	}
	/**
	 *	@param int $post : post id
	 *	@return form linked to the post or null
	 */
	private function getFormByPost( $post ){
		if ( is_numeric( $post ) ){
			$form = $this->wpdb->get_row( 'SELECT * FROM '.$this->wpdb->prefix.'form WHERE post="'.$this->wpdb->escape( $post ).'"' );
			return empty($form)?null:$form;
		} else
			return null;
	}
	/**
	 *	@param int $id : id of the form tho fetch
	 */
	private function getForm( $id ){
		if ( is_numeric( $id ) ){
			$form = $this->wpdb->get_row( 'SELECT * FROM '.$this->wpdb->prefix.'form WHERE formId="'.$this->wpdb->escape( $id ).'"' );
			return empty($form)?null:$form;
		} else
			return null;
	}
	/**
	 *	@param array $filters : filters
		array(
			array(
				'name' => string,
				'op' => string
				'values' => array(
					value
				)
			)
		)
	 *	@return array(object) : last 10 forms
	 */
	private function getForms(array $filters){
		$prefix = $this->wpdb->prefix;
		return $this->wpdb->get_results(
			'SELECT '.$prefix.'form.fields,
'.$prefix.'form.formId,
'.$prefix.'form.post,
'.$prefix.'posts.post_name,
'.$prefix.'posts.post_date
FROM '.$prefix.'form
LEFT JOIN '.$prefix.'posts ON '.$prefix.'form.post='.$prefix.'posts.ID
ORDER BY '.$prefix.'posts.post_date DESC
LIMIT 0,10'
		);
	}
	/**
	 *	Add/Edit a form to the post with given fields
	 *	(Put null as fieldGroupName to not render the field group name)
	 *	@param array $fields : field of the form
	 array(
		'name' => array(
			fieldNumber => string
		),
		'type' => array(
			fieldNumber => string
		),
		'values' => array(
			fieldNumber => array(
				value
			)
		)
	 )
	 *	@param string $post : post name
	 *	@param string $limitation : limitation type
	 */
	private function addEditForm( array $fields, $post, $limitation, $title ){
		// Check fields
		if ( empty($fields['name'] ) || !is_array( $fields['name'] ) )
			return;
		// Get post id and check post name
		$postId = $this->wpdb->get_var('SELECT ID FROM '.$this->wpdb->posts.' WHERE post_name="'.$this->wpdb->escape($post).'"');
		if ( empty( $postId ) )
			return;
		else {
			// Check if form exists
			$formId = $this->wpdb->get_var('SELECT formId FROM '.$this->wpdb->prefix.'form WHERE post='.$this->wpdb->escape($postId));
			if ( empty( $formId ) ){
				$this->wpdb->insert(
					$this->wpdb->prefix.'form',
					array(
						'fields' => serialize($fields),
						'post' => $postId,
						'limitation' => $limitation,
						'title' => $title
					)
				);
				return $this->wpdb->get_var('SELECT formId FROM '.$this->wpdb->prefix.'form WHERE post='.$this->wpdb->escape($postId));
			} else {
				$this->wpdb->update(
					$this->wpdb->prefix.'form',
					array(
						'fields' => serialize($fields),
						'post' => $postId,
						'limitation' => $limitation,
						'title' => $title
					),
					array(
						'formId' => $formId
					)
				);
				return $formId;
			}
		}
	}
	/**
	 *	Delete a form 
	 *	@param int $form : form id
	 */
	private function deleteFormByPK( $form ){
		if ( is_numeric( $form ) )
			$this->wpdb->query( 'DELETE FROM '.$this->wpdb->prefix.'form WHERE formId="'.$this->wpdb->escape($form).'"' );
	}
	/**
	 *	@param int $form : form id
	 *	@return array : results of the form
	 */
	private function getResultsByForm( $form ){
		
	}
	// Rendering functions
	/**
	 *	@param array $fields : field definition
	 */
	public function renderFields( array $fields){
		if (!empty($fields['name']) && is_array($fields['name'])){
			$string = '';
			foreach($fields['name'] as $key=>$name){
				$string .= '<tr rel="'.$key.'">
	<td><span class="removeField button-secondary">-</span></td>
	<td><input type="text" name="fields[name]['.$key.']" value="'.stripslashes( $name ).'"/></td>
	<td><select name="fields[type]['.$key.']">';
				foreach(self::$fieldTypes as $type)
					$string .= '<option value="'.$type.'"'.(!empty($fields['type'][$key]) && $fields['type'][$key] == $type?' selected="selected"':'').'>'.$type.'</option>';
				$string .= '</select></td>
	<td><div class="addValue button-secondary"><center>+</center></div>';
				if (!empty($fields['values'][$key]) && is_array($fields['values'][$key])){
					foreach($fields['values'][$key] as $value)
						$string .= '<div class="value"><span class="removeValue button-secondary">-</span><input type="text" name="fields[values]['.$key.'][]" value="'.stripslashes( $value ).'"/></div>';
				}
				$string .= '</td>
</tr>';
			}
			return $string;
		}
		return '';
	}
	/**
	 *	@param stdClass $form : form object
	 *	@return string
	 */
	protected function renderForm( $form ){
		if ( $this->checkAuth( $form ) == null )
			return __('You have already submitted your answers', 'postformmaker');
		else {
			$string = '';
			if ( !( empty( $form->fields ) || is_array( $form->fields ) ) )
				$form->fields = unserialize($form->fields);
			if ( !empty( $form->fields['name'] ) && is_array( $form->fields['name'] ) ){
				$string = '<form action="#" method="post" class="generatedForm">
				<input type="hidden" name="postForm" value="'.$form->formId.'" />
		<table class="form-input generated">
			<thead>
				<tr>
					<th colspan="2">'.stripslashes( $form->title ).'</th>
				</tr>
			</thead>
			<tbody>';
				foreach( $form->fields['name'] as $key=>$name ){
					$string .= '<tr>
		<td>'.stripslashes( $name ).'</td>
		<td>';
					$type = empty($form->fields['type'][$key])?'':$form->fields['type'][$key];
					if ( $type == 'text' ){
						$string .= '<input type="text" name="'.$this->safeInputName( $name ).'" />';
					} elseif( $type == 'select' ){
						$string .= '<select name="'.$this->safeInputName( $name ).'">';
						if ( !empty( $form->fields['values'][$key] ) && is_array( $form->fields['values'][$key] ) ){
							foreach( $form->fields['values'][$key] as $value )
								$string .= '<option value="'.$this->safeInputName( $value ).'">'.stripslashes( $value ).'</option>';
						}
						$string .= '</select>';
					} elseif ( $type == 'radio' ) {
						if ( !empty( $form->fields['values'][$key] ) && is_array( $form->fields['values'][$key] ) ){
							foreach( $form->fields['values'][$key] as $value )
								$string .= '<span class="value"><input type="radio" name="'.$this->safeInputName( $name ).'" value="'.stripslashes( $value ).'"/>'.stripslashes( $value ).'</span>';
						}
					} elseif ( $type == 'checkbox') {
						if ( !empty( $form->fields['values'][$key] ) && is_array( $form->fields['values'][$key] ) ){
							foreach( $form->fields['values'][$key] as $value )
								$string .= '<span class="value"><input type="checkbox" name="'.$this->safeInputName( $name ).'[]" value="'.stripslashes( $value ).'"/>'.stripslashes( $value ).'</span>';
						}
					}
					$string .= '</td></tr>';
				}
				$string .= '</tbody>
			<tfoot>
				<tr>
					<td colspan="2"><button>'.__('Submit', 'postformmaker').'</button></td>
				</tr>
			</tfoot>
		</table>
	</form>';
			}
			return $string;
		}
	}
	/**
	 *	@param int $formId : id of the form
	 *	@return array( stats, vote number )
	 */
	public function getProcessedResults( $formId ){
		$results = $this->wpdb->get_results(
			'SELECT * FROM '.$this->wpdb->prefix.'formresults WHERE form="'.$this->wpdb->escape( $formId ).'"'
		);
		$stats = array();
		$voteNb = 0;
		foreach( $results as $result ){
			$voteNb++;
			$datas = empty($result->results)?array():unserialize($result->results);
			foreach( $datas as $name=>$value ){
				if ( is_array( $value ) )
					foreach( $value as $val )
						$stats[$name][$val] = ( empty( $stats[$name][$val] ) ? 0 : $stats[$name][$val] ) + 1;
				else
					$stats[$name][$value] = ( empty( $stats[$name][$value] ) ? 0 : $stats[$name][$value] ) + 1;
			}
		}
		// Adding other values
		$form = $this->wpdb->get_row( 'SELECT * FROM '.$this->wpdb->prefix.'form WHERE formId="'.$this->wpdb->escape( $formId ).'"' );
		$fields = empty($form->fields)?array():unserialize($form->fields);
		if ( !empty( $fields['name'] ) ){
			foreach( $fields['name'] as $key=>$name ){
				if ( !empty( $fields['values'][$key] ) && is_array( $fields['values'][$key] ) )
					foreach( $fields['values'][$key] as $value )
						if ( empty( $stats[$name][$value] ) )
							$stats[$name][$value] = 0;
			}
		}
		return array( $stats, $voteNb );
	}
	// Text processing
	/**
	 *	@param string $name : name to sanitize
	 *	@return string : sanitized name
	 */
	private function safeInputName( $name ){
		return preg_replace(
			array(
				'/\s/',
				'/[^\w]/'
			),
			array(
				'_',
				'_'
			),
			$name
		);
	}
	// Mail functions
	/**
	 *	Mail a submit to the author of the post
	 *	@param array $datas : result of the submit
	 *	@param int $formId : id of the form
	 */
	public function mailSubmit( $datas, $formId ){
		// fetch author's mail
		$postId = $this->wpdb->get_var( 'SELECT post FROM '.$this->wpdb->prefix.'form WHERE formId="'.$this->wpdb->escape( $formId ).'"' );
		if ( !empty( $postId ) ){
			$postAuthor = $this->wpdb->get_var( 'SELECT post_author FROM '.$this->wpdb->posts.' WHERE ID="'.$this->wpdb->escape( $postId ).'"' );
			if( !empty( $postAuthor ) ){
				$authorMail = $this->wpdb->get_var( 'SELECT user_email FROM '.$this->wpdb->users.' WHERE ID="'.$this->wpdb->escape( $postAuthor ).'"' );
				if ( !empty( $authorMail ) ){
					mail(
						$authorMail,
						__('Some one has answered to your form', 'postformmaker'),
						var_export( $datas, true ),
						'From : '.get_option( 'admin_email' )
					);
				}
			}
		}
	}
}
postFormMaker::getInstance();
?>