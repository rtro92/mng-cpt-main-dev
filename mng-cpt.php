<?php
/*
Plugin Name: Manage CPTs
Description: A Plugin to create and delete Custom Post Types
Author: Myles Taylor
Version: 1.0.0
*/

class mng_cpt {

	public function __construct() {

		// Enqueue scripts and styles
		add_action('admin_enqueue_scripts', array($this, 'mng_cpt_enqueue_styles'));		

		// Create Admin page
		add_action('admin_menu', array($this, 'create_settings_page'));

		// Setup content
		add_action('admin_init', array($this, 'setup_sections'));
		add_action('admin_init', array($this, 'setup_fields'));
		
		// Activate CPTS
		add_action('init', array($this, 'activate_cpts'));
				
		// ADMIN POST ACTIONS
		add_action('admin_post_mng_cpt_delete', array($this, 'custom_post_type_manager_delete'));
		add_action('admin_post_mng_cpt_rename', array($this, 'mng_cpt_rename_posts'));
		
	}

	

	// Enqueue scripts and styles
	public function mng_cpt_enqueue_styles() {
		wp_enqueue_style('mng_cpt_css', plugin_dir_url(__FILE__).'css/mng-cpt.css', array(), '1.0.0', 'all');		
		wp_enqueue_script('vue', 'https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.min.js', array(), '2.6.14', true);
		wp_enqueue_script('mng_cpt_js', plugin_dir_url(__FILE__).'js/mng-cpt.js', array('jquery'), '1.0.0', true);
	}



	// Create Admin page
	public function create_settings_page() {
		$title = 'Manage CPTs';
		$menu_title = 'Manage CPTs';
		$capability = 'manage_options';
		$slug = 'manage_cpts';
		$callback = array($this, 'mng_cpt_content');
		$icon = 'dashicons-admin-plugins';
		$position = 100;

		add_menu_page($title, $menu_title, $capability, $slug, $callback, $icon, $position);
	}



	// Generate main content body
	public function mng_cpt_content() {		

		?>
		<div class="wrap">
			<h2>Manage Custom Post Types</h2>
			<?php
				$this->generate_existing_cpts();
				$this->create_new_cpts();
			?>									
		</div>							
		<?php
	}



	// Setup Sections
	public function setup_sections() {
		add_settings_section('first_section', '', '', 'manage_cpts');		
	}



	// Set up fields for new CPT
	public function setup_fields() {
		$fields = array(
			array(
				'uid'			=> 'mng_cpt_names',
				'label'			=> 'Name of Custom Post Type',
				'section'		=> 'first_section',
				'type'			=> 'text',
				'options'		=> false,
				'placeholder'	=> 'e.g. Movies',
				'helper'		=> '',
				'supplemental'  => 'Enter the name of the new post type to create',
				'default' 		=> array()
			)				
		);

		foreach($fields as $field) {

			add_settings_field( $field['uid'], $field['label'], array($this, 'field_callback'), 'manage_cpts', $field['section'], $field);
			register_setting('manage_cpts', 'mng_cpt_names', array($this, 'sanitize_cpt_names'));

		}		

	}



	public function field_callback($args) {

		$value = get_option($args['uid']); // Get the current value, if there is one
		
		if(!$value) { // if no value exists
			$value = $args['default']; // Set default
		}
		
	
		if (is_array($value)) {
			$value = implode(',',$value);
		}
		printf(
		    '<input name="%1$s[]" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />',
		    $args['uid'],
		    $args['type'],
		    $args['placeholder'],
		    ''
		);
				
		// If there is helper text
		if($helper = $args['helper']) {
			printf( '<span class="helper">%s</span>', $helper ); 
		}

		//If there is supplemental text
		if( $supplemental = $args['supplemental']) {
			printf( '<p class="description">%s</p>', $supplemental);
		}
	}



	// Sanitizes names
	public function sanitize_cpt_names($value) {
		
	    $existing_cpts = get_option('mng_cpt_names', array());

	    if (!empty($value)) {
	        $sanitized_values = array_map('sanitize_text_field', $value);
	        $merged_values = array_merge($existing_cpts, $sanitized_values);
	        return array_unique($merged_values);
	    }
	    
	    return $existing_cpts;
	}



	// Generate existing custom post types
	public function generate_existing_cpts() {
		?>
		<h2>Current Custom Post Types</h2>
		<?php

			$cpts = get_option('mng_cpt_names');

			echo '<div class="mng-cpt-current-wrap">';

			if($cpts) {
				foreach( $cpts as $index => $cpt) {

					$post_count = wp_count_posts(strtolower($cpt));						
					
					$total_posts = $post_count->publish;
					if($total_posts == NULL) {
						$total_posts = 0;
					} 

					$link = admin_url('admin-post.php?action=mng_cpt_rename&post_type='.$cpt.'&mng_cpt_nonce='.wp_create_nonce('mng_cpt_nonce').'&rename=');						
					?>

					<div class="mng-cpt-current-row">
						<div class="mng-cpt-col col-name"><?php echo $cpt;?></div>
						<div class="mng-cpt-col col-number">Number of Posts: <?php echo $total_posts;?></div>
						<div class="mng-cpt-col col-rename">
							<div class="rename-container" data-index="<?php echo $index;?>" data-static-url=<?php echo $link;?> v-cloak>
						  		<input type="text" v-model="textInput">
						  		<a :class="['mng-cpt-btn', 'btn-rename', { 'has-text': hasTextClass }]" :href="dynamicUrl" :disabled="isLinkDisabled">Rename</a>
							</div>
						</div>
						<div class="mng-cpt-col col-delete">
							<div class="mng-cpt-btn btn-delete">Delete</div>
							<div class="mng-hidden">Are you sure?
								<?php
									echo '<a class="del-yes" href="' . admin_url('admin-post.php?action=mng_cpt_delete&post_type='.$cpt.'&mng_cpt_nonce='. wp_create_nonce('mng_cpt_nonce')) . '">Yes</a>';
								?>
								<span class="del-no">No</span>
							</div>
						</div>
					</div>
					<?php
				}
			}
			echo '</div>';
	}



	// Create new CPTs
	public function create_new_cpts() {
		?>
		<div class="create-new-cpts">
			<h2>Create new Custom Post Type</h2>
			<div class="create-box">
				<form method="post" action="options.php">
					<?php
						settings_fields('manage_cpts');
						do_settings_sections('manage_cpts');
						submit_button('Add New Custom Post Type');
					?>
				</form>
			</div>
		</div>
		<?php
	}



	// Activate CPTs in back-end
	public function activate_cpts() {
		$cpts = get_option('mng_cpt_names');

		if($cpts) {
			foreach($cpts as $k=>$cpt){
				
				$args = array(
					'labels' => array(
						'name' => $cpt,
						'singular_name' => $cpt
					),
					'public' => true,
					'has_archive' => true
				);

				register_post_type($cpt, $args);
			}
		}
	}
	


	// Set up Vue fields
	public function render_rename_link($cpt, $index) {
		
		$link = admin_url('admin-post.php?action=mng_cpt_rename&post_type='.$cpt.'&mng_cpt_nonce='.wp_create_nonce('mng_cpt_nonce').'&rename=');
		
		?>
		<div class="rename-container" data-index="<?php echo $index;?>" data-static-url=<?php echo $link;?> v-cloak>
  			<input type="text" v-model="textInput">
  			<a :class="['mng-cpt-btn', 'btn-rename', { 'has-text': hasTextClass }]" :href="dynamicUrl" :disabled="isLinkDisabled">Rename</a>
		</div>
		<?php
	}



	// Ignore Upper & Lower case in search
	public function ignore_case_in_search($query) {
    	if (is_admin() || !is_search()) {
        	return;
    	}

    	$search_term = $query->get('s');
    	if (!empty($search_term)) {
        	$query->set('s', strtolower($search_term));
    	}
	}



	/******************************************************************
	 * 
	 * ADMIN-POST FUNCTIONS
	 * 
	 *****************************************************************/



	// Delete Custom post types. Does not delete posts in database.
	public function custom_post_type_manager_delete() {
		
		$post_type = sanitize_text_field($_GET['post_type']);
		$nonce = sanitize_text_field($_GET['mng_cpt_nonce']);

		if (wp_verify_nonce($nonce, 'mng_cpt_nonce')) {		

			$existing_cpts = get_option('mng_cpt_names', array());		
			$index = array_search($post_type, $existing_cpts);

			if( $index !== FALSE) {
				unset($existing_cpts[$index]);					
				delete_option('mng_cpt_names');
				update_option('mng_cpt_names', $existing_cpts);
			}
						
			wp_redirect(admin_url('admin.php?page=manage_cpts'));
						
			exit();			
		}		
		
	}



	// Rename CPTs in database. Renames post type and slug
	public function mng_cpt_rename_posts() {
						
		$post_type = sanitize_text_field($_GET['post_type']);
		$nonce = sanitize_text_field($_GET['mng_cpt_nonce']);
		$rename = sanitize_text_field($_GET['rename']);

		if (wp_verify_nonce($nonce, 'mng_cpt_nonce')) {

			
			// Update any actual CPTs that exist		
			$args = array(
				'post_type' => $post_type,
				'posts_per_page' => -1
			);

			$posts = get_posts($args);

			foreach ($posts as $post) {
				$updated_post = array(
					'ID' => $post->ID,
					'post_type' => $rename		
				);

				wp_update_post($updated_post);
			}

			// Update options table array storing CPTs
			$existing_cpts = get_option('mng_cpt_names', array());
			$index = array_search($post_type, $existing_cpts);

			if( $index !== FALSE) {
				$existing_cpts[$index] = $rename;
				delete_option('mng_cpt_names');
				update_option('mng_cpt_names', $existing_cpts);
			}

			wp_redirect(admin_url('admin.php?page=manage_cpts'));

			exit();

		}
	}
	
}


new mng_cpt();