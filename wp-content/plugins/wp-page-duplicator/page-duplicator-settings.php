<?php 


if (! current_user_can('manage_options')) {
    wp_die( __( 'You do not have sufficient permissions to access this page.', 'page-duplicator' ) );
}

?>
<div class="wrap">
	<h1><?php _e( 'Page Duplicator', 'page-duplicator' ); ?></h1>	
	<form method="POST" action="options.php">
		<?php 
			settings_fields( 'page-duplicator-settings' );	
			do_settings_sections( 'page-duplicator-settings' ); 
			submit_button();
		?>		
	</form>
	
</div>



