<?php

/*

Plugin Name: Nice Fixed Menu
Plugin URI:
Version: 1.0
Description: Display nice menu fixed on your webiste
Author: Manu225
Author URI: 
Network: false
Text Domain: nice-fixed-menu
Domain Path: 

*/

register_activation_hook( __FILE__, 'nice_fixed_menu_install' );
register_uninstall_hook(__FILE__, 'nice_fixed_menu_desinstall');

global $NFM_MENU_POSITIONS;

$NFM_MENU_POSITIONS = array(
	1 => 'top',
	2 => 'bottom',
	3 => 'left',
	4 => 'right');

function nice_fixed_menu_install() {

	global $wpdb;

	$nice_fixed_menu_table = $wpdb->prefix . "nice_fixed_menu";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	$sql = "
        CREATE TABLE `".$nice_fixed_menu_table."` (
          id int(11) NOT NULL AUTO_INCREMENT,
          name varchar(50) NOT NULL,
          icon varchar(500) NOT NULL,
          link varchar(500) NOT NULL,
          blank int(1) NOT NULL,
          text_color varchar(30) NOT NULL,
          bg_color varchar(30) NOT NULL,
          `order` int(2) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
    ";

    dbDelta($sql);

    //paramètres du plugin enregistrer dans une option
    $settings_default = array(
    	'menu_position' => 4,
    	'font_size' => 20,
    	'icon_size' => 45);
    add_option('nice-fixed-menu-settings', $settings_default);
}

function nice_fixed_menu_desinstall() {

	global $wpdb;

	$nice_fixed_menu_table = $wpdb->prefix . "nice_fixed_menu";

	//suppression des tables
	$sql = "DROP TABLE ".$nice_fixed_menu.";";

	$wpdb->query($sql);

}

add_action('admin_print_styles', 'nfm_css' );
function nfm_css() {
    wp_enqueue_style( 'NFMCSS', plugins_url('css/admin.css', __FILE__) );
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css');
}

add_action( 'admin_enqueue_scripts', 'nfm_script' );
function nfm_script() {
    wp_enqueue_script( 'wp-color-picker');
}

add_action( 'admin_menu', 'register_nfm_menu' );
function register_nfm_menu() {
	add_menu_page('Nice fixed menu', 'Nice fixed menu', 'edit_pages', 'nice_fixed_menu', 'nice_fixed_menu',   '', 29);
	add_submenu_page( 'nice_fixed_menu', 'Settings', 'Settings', 'edit_pages', 'nice_fixed_menu_settings', 'nice_fixed_menu_settings');
}

function nice_fixed_menu() {

	global $wpdb;

	$nice_fixed_menu_table = $wpdb->prefix . "nice_fixed_menu";

	//formulaire soumis ?
	if(sizeof($_POST))
	{
		if(is_numeric($_POST['id']))
		{
			check_admin_referer( 'update_nfm_'.$_POST['id'] );
			$order = intval($_POST['order']);
		}
		else
		{
			check_admin_referer( 'new_nfm' );
			$max_order = $wpdb->get_row( "SELECT MAX(`order`) as max_order FROM ".$nice_fixed_menu_table );
			if($max_order)
				$order = ($max_order->max_order+1);
			else
				$order = 1;
		}

		$query = "REPLACE INTO ".$nice_fixed_menu_table." (`id`, name, icon, link, blank, text_color, bg_color, `order`)
		VALUES (%d, %s, %s, %s, %d, %s, %s, %d)";

		$query = $wpdb->prepare( $query, $_POST['id'], sanitize_text_field(stripslashes_deep($_POST['name'])), sanitize_text_field(stripslashes_deep($_POST['icon'])), sanitize_text_field(stripslashes_deep($_POST['link'])), $_POST['blank'], sanitize_text_field(stripslashes_deep($_POST['text_color'])), sanitize_text_field(stripslashes_deep($_POST['bg_color'])), $order);

		$wpdb->query( $query );
	}

	$query = "SELECT * FROM ".$nice_fixed_menu_table." ORDER BY `order` ASC";

	$menus = $wpdb->get_results( $query );

	include(plugin_dir_path( __FILE__ ) . 'views/menus.php');
}

function nice_fixed_menu_settings() {

	//formulaire soumis ?
	if(sizeof($_POST))
	{
		check_admin_referer( 'nfm_settings' );
		$settings = $_POST['settings'];
		update_option('nice-fixed-menu-settings', $settings);
	}
	else
		$settings = get_option('nice-fixed-menu-settings');

	include(plugin_dir_path( __FILE__ ) . 'views/settings.php');

}

//Ajax : suppression d'un flipping card
add_action( 'wp_ajax_nfm_remove_menu', 'nfm_remove_menu' );

function nfm_remove_menu() {

	check_ajax_referer( 'nfm_remove_menu' );

	if (current_user_can('edit_pages')) {

		global $wpdb;

		$nice_fixed_menu_table = $wpdb->prefix . "nice_fixed_menu";

		if(is_numeric($_POST['id']))
		{
			//on récupère le order du menu a supprimer
			$query = "SELECT `order` FROM ".$nice_fixed_menu_table." WHERE id = %d";
			$menu = $wpdb->get_row( $wpdb->prepare( $query, $_POST['id'] ));
			if($menu)
			{
				//on met à jour les orders des menus suivants
				$wpdb->query( $wpdb->prepare( "UPDATE ".$nice_fixed_menu_table." SET `order` = `order` - 1 WHERE `order` > %d", $menu->order));

				//supprime le menu
				$query = $wpdb->prepare( 
					"DELETE FROM ".$nice_fixed_menu_table."
					 WHERE id=%d", $_POST['id']
				);
				$res = $wpdb->query( $query	);
			}
			
		}
		wp_die();
	}
}

//Ajax : changement de position d'un menu
add_action( 'wp_ajax_nfm_order_menu', 'nfm_order_menu' );

function nfm_order_menu() {

	check_ajax_referer( 'nfm_order_menu' );

	if (current_user_can('edit_pages')) {
		global $wpdb;

		$nice_fixed_menu_table = $wpdb->prefix . "nice_fixed_menu";

		$order = intval($_POST['order']);

		if(is_numeric($_POST['id']) && $order > 0)
		{
			$menu = $wpdb->get_row( $wpdb->prepare( "SELECT `order` FROM ".$nice_fixed_menu_table." WHERE id = %d", $_POST['id'] ));
			if($_POST['order'] > $menu->order)
				$wpdb->query( $wpdb->prepare( "UPDATE ".$nice_fixed_menu_table." SET `order` = `order` - 1 WHERE `order` <= %d AND `order` > %d", $order, $menu->order ));
			else
				$wpdb->query( $wpdb->prepare( "UPDATE ".$nice_fixed_menu_table." SET `order` = `order` + 1 WHERE `order` >= %d AND `order` < %d", $order, $menu->order ));
			$wpdb->query( $wpdb->prepare( "UPDATE ".$nice_fixed_menu_table." SET `order` = %d WHERE id = %d", $order, $_POST['id'] ));
			
		}
		wp_die();
	}
}

//Ajax : autocomplète icons
add_action( 'wp_ajax_nfm_fa_icons_list', 'nfm_fa_icons_list' );

function nfm_fa_icons_list() {

	if(current_user_can('edit_pages'))
	{

		check_ajax_referer( 'nfm_fa_icons_list' );

		require_once(plugin_dir_path( __FILE__ ) . 'icons_lists.php');

		global $NFM_FA_ICONS;

		if($_POST['q'])
			$icons_list = preg_grep("/^(.*)".preg_quote($_POST['q'])."(.*)$/", $NFM_FA_ICONS);
		else
			$icons_list = $NFM_FA_ICONS;

		if(sizeof($icons_list) > 0)
		{
			include(plugin_dir_path( __FILE__ ) . 'views/icons_list.php');
		}
		else
			echo 'No icon found !';
	}
	wp_die();
}


add_action( 'wp_head', 'head_nice_fixe_menu' );
function head_nice_fixe_menu()
{
	wp_enqueue_style( 'NFMFRONTCSS', plugins_url('css/front.css', __FILE__) );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'NFMFRONTJS', plugins_url( 'js/front.js', __FILE__ ));
	wp_enqueue_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css');
}


add_filter( 'wp_footer', 'display_nice_fixe_menu' );
function display_nice_fixe_menu() {

		$settings = get_option('nice-fixed-menu-settings');

		global $NFM_MENU_POSITIONS, $NFM_MENU_HOVER;
		global $wpdb;
		$nice_fixed_menu_table = $wpdb->prefix . "nice_fixed_menu";
		$query = "SELECT * FROM ".$nice_fixed_menu_table." ORDER BY `order` ASC";
		$menus = $wpdb->get_results( $query );

		echo '<div id="nice-fixed-menu" class="'.$NFM_MENU_POSITIONS[$settings['menu_position']].'">';
		echo '<ul>';
		foreach($menus as $menu)
		{
			$li_style = 'background-color: '.$menu->bg_color.'; line-height: '.$settings['icon_size'].'px;';
			echo '<li style="'.$li_style.'">';
			echo '<a href="'.$menu->link.'" '.($menu->blank == 1 ? 'target="_blank"' : '').' style="color: '.$menu->text_color.'; font-size: '.$settings['font_size'].'px">';
			echo '<i class="fa fa-'.$menu->icon.'" style="font-size: '.$settings['icon_size'].'px"></i>';
			echo '<span>'.$menu->name.'</span></a>
			</li>';
		}
		echo '</ul>';
		echo '</div>';

}