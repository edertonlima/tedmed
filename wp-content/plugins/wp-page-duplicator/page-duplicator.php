<?php
/*
Plugin Name: Page Duplicator
Plugin URI:  https://wordpress.org/plugins/theme-check/wp-page-duplicator/
Description: Easily duplicate any page or post on your site. Supports taxonomies, custom fields and custom post types.
Version:     0.1.1
Author:      Samuel Kowlaski
Author URI:  https://wordpress.org/plugins/theme-check/wp-page-duplicator/
 */

class Page_Duplicator {
	
	public function __construct() {
		$duplicablePosts = get_option( 'duplicable_post_types', array(
			'post',
			'page'
		) );
		if( ! empty( $duplicablePosts ) ) {
			foreach( $duplicablePosts as $duplicable ) {
				add_filter( $duplicable . '_row_actions', array( $this, 'add_page_duplicator_button' ), 10, 2 );
			}
		}
		add_action( 'init', array( $this, 'duplicate_dat_page' ) );
		add_action( 'admin_notices', array( $this, 'duplicate_dat_page_admin_notice' ) );
		add_action( 'admin_menu', array( $this, 'create_page_duplicator_settings' ) );
		add_action( 'admin_init', array( $this, 'page_duplicator_settings_api_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'page_duplicator_enqueue_scripts_and_styles' ) );
	}
	
	
	public function create_page_duplicator_settings() {
		add_submenu_page( 
			  'options-general.php',   //or 'options.php' 
			  __( 'Page Duplicator', 'page-duplicator' ), 
			__( 'Page Duplicator', 'page-duplicator' ), 
			'manage_options', 
			'page-duplicator-settings', 
			array( $this, 'page_duplicator_settings_page' )
		);
	}	
	
	
	public function page_duplicator_settings_api_init() {
	
		register_setting(
			'page-duplicator-settings',
			'duplicable_post_types'
		);
	
		add_settings_section(
			'page-duplicator-settings',         
			__( 'Post Types', 'page-duplicator' ),
			array( $this, 'page_duplicator_setting_section_callback_function' ),
			'page-duplicator-settings'                          
		);
		
		add_settings_field( 
			'duplicable_post_types',                      
			__( 'Post Types', 'page-duplicator' ),     
			array( $this, 'page_duplicator_duplicable_post_types_callback_function' ), 
			'page-duplicator-settings',                      
			'page-duplicator-settings',    
			array(                           
				__( 'Select post types which you want to be duplicate. By default, posts and pages are duplicable.', 'page-duplicator' )
			)
		);
				
	} 
	
	public function page_duplicator_settings_page() {
		include_once( plugin_dir_path( __FILE__ ) . 'page-duplicator-settings.php' );
	}
	
	
	public function page_duplicator_setting_section_callback_function() {
		//echo '<p>' . __( 'Select post types which you want to be duplicate.', 'page-duplicator' ) . '</p>';
	 }
	 
	
	public function page_duplicator_duplicable_post_types_callback_function( $args ) {
		$previously_saved_post_types = get_option( 'duplicable_post_types', array(
			'post',
			'page'
		) );
		$post_types = get_post_types( '', 'names' );
		unset( $post_types[ 'attachment' ] );
		unset( $post_types[ 'nav_menu_item'] );
		unset( $post_types[ 'revision' ] );
		?>
        <div class="col-sm-6">
			<select  class="form-control " name="duplicable_post_types[]" multiple>
				<?php
					foreach ( $post_types as $post_type ) {
						$post_type_labels = get_post_type_object( $post_type );
						$post_type_name = ( isset( $post_type_labels->labels->singular_name ) ) ? $post_type_labels->labels->singular_name : $post_type;
						$selected = ( in_array( $post_type, $previously_saved_post_types ) ) ? 'selected="selected"' : '';
						echo '<option value="' . $post_type . '" ' . $selected . '>' . ucwords( $post_type_name ) . '</option>';
					}
				?>
			</select>
           
			<p class="description"><?php echo $args[0]; ?></p>
            </div>
		<?php
	}
	
	
	public function add_page_duplicator_button( $actions, $post ) {
		$duplicablePosts = get_option( 'duplicable_post_types', array(
			'post',
			'page'
		) );
		if( ! empty( $duplicablePosts ) ) {
			foreach( $duplicablePosts as $duplicable ) {
	
				if( $duplicable == 'post' ) {
					if( ! in_array( $post->post_type, $duplicablePosts ) ) {
						return $actions;
					}
				}
				$post_type_labels = get_post_type_object( $post->post_type );
				$actions['page_duplicator'] = '<a href="'. add_query_arg( 
					array( 
						'do_action' => 'page_duplicator',
						'nonce' => wp_create_nonce( 'page_duplicator-' . (int) $post->ID ), 
						'post_id' => (int) $post->ID 
					), 
					esc_url( admin_url( 'edit.php?post_type=page' ) ) 
				) . '" >' . sprintf( __( 'Duplicate %s', 'yikes-inc-easy-mailchimp-extender' ), $post_type_labels->labels->singular_name ) . '</a>';
			}
		}
		return $actions;		
	}
	
	
	public function duplicate_dat_page() {
		if( isset( $_GET['do_action'] ) && $_GET['do_action'] == 'page_duplicator' && isset( $_GET['nonce'] ) ) {
	
			wp_verify_nonce( sanitize_key($_GET['nonce']), 'page_duplicator-' . (int) $_REQUEST['post_id'] );
			$page_id = (int) $_GET['post_id'];
			$page_object = get_post( $page_id );
			$taxonomies = get_object_taxonomies( $page_object->post_type );
			$post_meta_data = get_post_meta( $page_id );
			
			if( $page_object ) {
				
				$new_page_title = $page_object->post_title . ' - Copy';
				$author = $page_object->post_author;
				$new_page_content = $page_object->post_content;
				$new_page_image_id = get_post_thumbnail_id( $page_id );
				
				// Create post object
				$newPost = array(
				  'post_title'    => $new_page_title,
				  'post_content'  => $new_page_content,
				  'post_type' => $page_object->post_type,
				  'post_status'   => 'draft',
				  'post_author'   => $author,
				);
				
				// Insert the post into the database
				$new_post = wp_insert_post( $newPost );
								
	
								
				if( $new_post ) {
					// Loop over returned taxonomies, and re-assign them to the new post_type
					if( $taxonomies ) {
						foreach( $taxonomies as $taxonomy ) {
							$terms = wp_get_post_terms( $page_id, $taxonomy );
							if( $terms ) {
								$assigned_terms = array();
								foreach( $terms as $assigned_term ) {
									$assigned_terms[] = $assigned_term->term_id;
								}
								wp_set_object_terms( $new_post, $assigned_terms, $taxonomy, false );
							}
						}
					}
					// Loop over returned metadata, and re-assign them to the new post_type
					if( $post_meta_data ) {
						foreach( $post_meta_data as $meta_data => $value ) {
							if( is_array( $value ) ) {
								foreach( $value as $meta_value => $meta_text ) {
									/* 
									*	- Check for serialized data in some meta field
									*	This is really In place for EDD imports 
									*	The varialble pricing field is a serialized array
									*/
									if( is_serialized( $meta_text ) ) {
										update_post_meta( $new_post, $meta_data,  unserialize( $meta_text ) );
									} else {
										update_post_meta( $new_post, $meta_data,  $meta_text );
									}
								}
							} else {
								update_post_meta( $new_post, $meta_data, $value );
							}
						}
					}
					// re-assign the featured image
					if( $new_page_image_id ) {
						set_post_thumbnail( $new_post, $new_page_image_id );
					}
					wp_redirect( esc_url_raw( admin_url( 'edit.php?post_type=' . $page_object->post_type . '&post_duplicated=true&duplicated_post=' . (int) $new_post ) ) );
					exit();
				}
			}
		}
	}
	
	/*
	*	Display Copy Notices
	*	@since 0.1
	*/
	public function duplicate_dat_page_admin_notice() {
		if( isset( $_GET['post_duplicated'] ) && $_GET['post_duplicated'] == 'true' ) {
			$page_id = (int) $_GET['duplicated_post'];
			$page_data = get_post( $page_id );
			?>
			<div class="updated">
				<p><?php echo str_replace( ' - Copy', '', $page_data->post_title ); ?> <?php _e( 'Sucessfully Copied', 'page-duplicator' ); ?> &#187; <a href="<?php echo esc_url_raw( admin_url( 'post.php?post=' . $page_id . '&action=edit' ) ); ?>">edit post</a></p>
			</div>
			<?php
		}
	}

	/*
	*	Enqueue the page duplicater scripts/styles where needed
	*	@since 0.1
	*/
	public function page_duplicator_enqueue_scripts_and_styles( $hook ) {
		// on our settings page, let's enqueue select2
		if( $hook == 'settings_page_page-duplicator-settings' ) {
			wp_enqueue_style( 'select2.min.css', plugin_dir_url( __FILE__ ) . 'css/select2.css' );
		}
	}
	
}
new Page_Duplicator;