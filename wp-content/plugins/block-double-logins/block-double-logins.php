<?php
/*
Plugin Name: Block Double Logins
Plugin URI: http://block-double-logins.webfactoryltd.com/
Description: Prevents users from sharing accounts, simulteneously logging in with multiple accounts and hiding behind proxies.
Author: Web factory Ltd
Version: 1.0
Author URI: http://www.webfactoryltd.com/
Text Domain: wf_bdl
Domain Path: lang
*/


if (!function_exists('add_action')) {
  die('Please don\'t open this file directly!');
}


define('WF_BDL_VER', '1.0');
define('WF_BDL_OPTIONS_KEY', 'wf_bdl');
define('WF_BDL_IPS_KEY', 'wf_bdl_ips');
define('WF_BDL_LOG_TABLE', 'bdl_log');
define('WF_BDL_MAX_LOG', 200);


class wf_bdl {
  // init plugin
  static function init() {
    if (is_admin()) {
      // this plugin requires WP v3.7
      if (!version_compare(get_bloginfo('version'), '3.7',  '>=')) {
        add_action('admin_notices', array(__CLASS__, 'min_version_error_wp'));
      }

      // aditional links in plugin description
      add_filter('plugin_action_links_' . basename(dirname(__FILE__)) . '/' . basename(__FILE__), array(__CLASS__, 'plugin_action_links'));
      add_filter('plugin_row_meta', array(__CLASS__, 'plugin_meta_links'), 10, 2);

      // check and set default setting values
      self::default_settings(false);

      // settings registration
      add_action('admin_init', array(__CLASS__, 'register_settings'));

      // add options menu
      add_action('admin_menu', array(__CLASS__, 'add_menus'));

      // enqueue CSS and JS on settings page
      add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'));

      // ajax endpoints
      add_action('wp_ajax_bdl_clear_ips', array(__CLASS__, 'ajax_clear_ips'));
      add_action('wp_ajax_bdl_clear_usernames', array(__CLASS__, 'ajax_clear_usernames'));
      add_action('wp_ajax_bdl_view_log', array(__CLASS__, 'ajax_view_log'));
      add_action('wp_ajax_bdl_view_ips', array(__CLASS__, 'ajax_view_ips'));
      add_action('wp_ajax_bdl_view_usernames', array(__CLASS__, 'ajax_view_usernames'));
      add_action('wp_ajax_bdl_send_override_code', array(__CLASS__, 'ajax_send_override_code'));
    } else {
      // if !is_admin()
      add_filter('login_message', array(__CLASS__, 'login_message'));
      add_action('login_form', array(__CLASS__, 'override_plugin_prepare'));
      add_filter('wp_authenticate_user', array(__CLASS__, 'authenticate_user_check'), 15, 2);
    }

    // show message on logout
    add_filter('wp_login_errors', array(__CLASS__, 'wp_login_errors'), 10, 2);

    // kill session on log out
    add_action('wp_logout', array(__CLASS__, 'wp_logout'));

    // maintain fresh session data at all times
    self::check_session_id();
    self::purge_ips();
  } // init


  // text domain has to be loaded earlier
  static function plugins_loaded() {
    load_plugin_textdomain('wf_bdl', false, basename(dirname(__FILE__)) . '/lang');
  } // plugins_loaded


  // add links to plugin's description in plugins table
  static function plugin_meta_links($links, $file) {
    $documentation_link = '<a target="_blank" href="' . plugin_dir_url(__FILE__) . 'documentation/' .
                          '" title="' . __('View documentation', 'wf_bdl') . '">' . __('Documentation', 'wf_bdl') . '</a>';
    $support_link = '<a target="_blank" href="http://codecanyon.net/user/WebFactory#contact" title="' . __('Contact Web factory', 'wf_bdl') . '">' . __('Support', 'wf_bdl') . '</a>';

    if ($file == plugin_basename(__FILE__)) {
      $links[] = $documentation_link;
      $links[] = $support_link;
    }

    return $links;
  } // plugin_meta_links


  // add settings link to plugins page
  static function plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=wf_bdl') . '" title="' . __('Settings for Block Double Logins', 'wf_bdl') . '">' . __('Settings', 'wf_bdl') . '</a>';
    array_unshift($links, $settings_link);

    return $links;
  } // plugin_action_links


  // check if we're on the plugin's settings page
  static function is_plugin_page() {
    $current_screen = get_current_screen();

    if ($current_screen->id == 'settings_page_wf_bdl') {
      return true;
    } else {
      return false;
    }
  } // is_plugin_page


  // add necessary JS / CSS files
  static function admin_enqueue_scripts() {
    if (self::is_plugin_page()) {
      wp_enqueue_style('wf_bdl', plugins_url('/css/bdl-admin.css', __FILE__), array(), WF_BDL_VER);
      wp_enqueue_style('wp-jquery-ui-dialog');

      wp_enqueue_script('jquery-ui-dialog');
      wp_enqueue_script('jquery-ui-tabs');
      wp_enqueue_script('wf_bdl', plugins_url('/js/bdl-admin.js', __FILE__), array('jquery'), WF_BDL_VER, true);
    } // if plugin page
  } // enqueue_scripts


  // add plugin menus
  static function add_menus() {
    add_options_page(__('Block Double Logins', 'wf_bdl'), __('Block Double Logins', 'wf_bdl'), 'manage_options', 'wf_bdl', array(__CLASS__, 'settings_screen'));
  } // add_menus


  // enables temporary plugin overriding for one login attempt
  static function override_plugin_prepare() {
    $options = self::get_options();

    if (isset($_GET['override_double_login']) && $_GET['override_double_login'] == $options['override_code']) {
      echo '<input type="hidden" name="bdl_override" value="' . esc_attr(trim($_GET['override_double_login'])) . '" />';
    }
  } // override_plugin_prepare


  // purges the IPs array on 5% of pageloads
  static function purge_ips($force = false) {
    if (!$force && rand(0, 100) > 5) {
      return;
    }

    $ips = self::get_options('ips');
    $options = self::get_options();
    $new = array();

    foreach ($ips as $ip => $meta) {
      // don't delete the IP key imediately, there's no need
      if ((time() - $meta['last_seen']) < $options['session_timeout'] * 60 * 2) {
        $new[$ip] = $meta;
      }
    }

    update_option(WF_BDL_IPS_KEY, $new);
  } // purge ips


  // maintains session state
  static function check_session_id() {
    if (!is_user_logged_in() ||
        (defined('DOING_AJAX') && DOING_AJAX) ||
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
      return;
    }

    $options = self::get_options();
    $user = wp_get_current_user();
    $hash = self::get_hash();
    $ips = self::get_options('ips');
    $ip = self::get_ip();
    $last_seen = (int) get_user_meta($user->ID, 'bdl_last_seen', true);

    // check if this user role should be tested at all
    $affected = false;
    if ($options['roles']) {
      foreach($user->roles as $role) {
        if (in_array($role, $options['roles'])) {
          $affected = true;
          break;
        }
      }
    }
    if ($affected == false) {
      return;
    }

    // double login based on username
    if ($options['prevent_double_username']) {
      if ($hash == get_user_meta($user->ID, 'bdl_hash', true)) {
        // hash is the same, update last seen
        update_user_meta($user->ID, 'bdl_last_seen', time());
        update_user_meta($user->ID, 'bdl_ip', self::get_ip());
      } else {
        // different hash, check what's going on
        if ((time() - $last_seen) > ($options['session_timeout'] * 60)) {
          // old session from other user, take over
          update_user_meta($user->ID, 'bdl_last_seen', time());
          update_user_meta($user->ID, 'bdl_ip', self::get_ip());
          update_user_meta($user->ID, 'bdl_hash', $hash);
        } else {
          // active session from another user
          wp_logout();
          header('location: ' . site_url() .'/wp-login.php?loggedout_bdl=true');
          die();
        }
      }
    } // multiple username protection

    // double login based on IP
    if ($options['prevent_double_ip']) {
      if (!isset($ips[$ip]) || $ips[$ip]['username'] == $user->user_login) {
        // username and IP match
        $ips[$ip] = array('username' => $user->user_login, 'last_seen' => time());
        update_option(WF_BDL_IPS_KEY, $ips);
      } else {
        // this IP is already using some other account
        if ((time() - $ips[$ip]['last_seen']) > ($options['session_timeout'] * 60)) {
          // old session from other user, takeover
          $ips[$ip] = array('username' => $user->user_login, 'last_seen' => time());
          update_option(WF_BDL_IPS_KEY, $ips);
        } else {
          // active session from another ip
          wp_logout();
          header('location: ' . site_url() .'/wp-login.php?loggedout_bdl=true');
          die();
        }
      }
    }
  } // check_session_id


  // write events to log
  static function save_log($reason, $username, $ip = false) {
    global $wpdb;

    // prune old logs
    if (rand(0, 100) < 5) {
      $wpdb->query('DELETE FROM ' . $wpdb->prefix . WF_BDL_LOG_TABLE . ' ORDER BY id DESC LIMIT ' . WF_BDL_MAX_LOG . ', 100000');
    }

    if ($ip == false) {
      $ip = self::get_ip();
    }

    $wpdb->insert($wpdb->prefix . WF_BDL_LOG_TABLE, array('username' => $username, 'ip' => $ip, 'reason' => $reason, 'timestamp' => current_time('mysql')), array('%s', '%s', '%s', '%s'));
  } // save_log


  // check all double login restrictions
  static function authenticate_user_check($user, $password) {
    $options = self::get_options();
    $ips = self::get_options('ips');
    $ip = self::get_ip();
    $hash = self::get_hash();
    $last_seen = (int) get_user_meta($user->ID, 'bdl_last_seen', true);

    // plugin override for this attempt
    if (isset($_POST['bdl_override']) && $options['override_code'] == $_POST['bdl_override']) {
      update_user_meta($user->ID, 'bdl_last_seen', time());
      update_user_meta($user->ID, 'bdl_ip', self::get_ip());
      update_user_meta($user->ID, 'bdl_hash', $hash);
      $ips[$ip] = array('username' => $user->user_login, 'last_seen' => time());
      update_option(WF_BDL_IPS_KEY, $ips);
      self::save_log('login by plugin override', $user->user_login);

      return $user;
    }

    // check if this user role should be tested at all
    $affected = false;
    if ($options['roles']) {
      foreach($user->roles as $role) {
        if (in_array($role, $options['roles'])) {
          $affected = true;
          break;
        }
      }
    }
    if ($affected == false) {
      return $user;
    }

    // double login based on username
    if ($options['prevent_double_username']) {
      if ($hash == get_user_meta($user->ID, 'bdl_hash', true)) {
        // hash is the same, update last seen
        update_user_meta($user->ID, 'bdl_last_seen', time());
        update_user_meta($user->ID, 'bdl_ip', self::get_ip());
        update_user_meta($user->ID, 'bdl_hash', $hash);
      } else {
        // different hash, check what's going on
        if ((time() - $last_seen) > ($options['session_timeout'] * 60)) {
          // old session from other user, take over
          update_user_meta($user->ID, 'bdl_last_seen', time());
          update_user_meta($user->ID, 'bdl_ip', self::get_ip());
          update_user_meta($user->ID, 'bdl_hash', $hash);
        } else {
          // active session from another user
          self::save_log('double username', $user->user_login);
          $user = new WP_Error('incorrect_password', '<strong>ERROR:</strong> ' . $options['prevent_double_username_error']);
          return $user;
        }
      }
    } // multiple username protection

    // double login based on IP
    if ($options['prevent_double_ip']) {
      if (!isset($ips[$ip]) || $ips[$ip]['username'] == $user->user_login) {
        // username and IP match
        $ips[$ip] = array('username' => $user->user_login, 'last_seen' => time());
        update_option(WF_BDL_IPS_KEY, $ips);
      } else {
        // this IP is already using some other account
        if ((time() - $ips[$ip]['last_seen']) > ($options['session_timeout'] * 60)) {
          // old session from other user, takeover
          $ips[$ip] = array('username' => $user->user_login, 'last_seen' => time());
          update_option(WF_BDL_IPS_KEY, $ips);
        } else {
          // active session from another user
          self::save_log('double IP', $user->user_login);
          $user = new WP_Error('incorrect_password', '<strong>ERROR:</strong> ' . $options['prevent_double_ip_error']);
          return $user;
        }
      }
    }

    // behind a proxy?
    if ($options['block_proxy']) {
      if (self::get_ip(true)) {
        // user's behind a proxy
        self::save_log('behind a proxy', $user->user_login);
        $user = new WP_Error('incorrect_password', '<strong>ERROR:</strong> ' . $options['block_proxy_error']);
        return $user;
      }
    }

    return $user;
  } // authenticate_user_check


  // kill session when user logs out
  static function wp_logout() {
    $user = wp_get_current_user();
    $ips = self::get_options('ips');
    $ip = self::get_ip();

    unset($ips[$ip]);
    update_option(WF_BDL_IPS_KEY, $ips);

    delete_user_meta($user->ID, 'bdl_hash');
    delete_user_meta($user->ID, 'bdl_last_seen');
    delete_user_meta($user->ID, 'bdl_ip');
  } // wp_logout


  // helper function
  static function get_options($key = 'general') {
    if ($key == 'ips') {
      $tmp = get_option(WF_BDL_IPS_KEY, array());
    } else {
      $tmp = get_option(WF_BDL_OPTIONS_KEY, array());
    }

    return $tmp;
  } // get_options


  // display messages above login form
  static function login_message($msg) {
    $options = self::get_options();

    if ($options['login_msg'] && (!isset($_GET['action']) || empty($_GET['action']))) {
      $msg = '<p class="message register">' . $options['login_msg'] . '</p>' . $msg;
    }

    if (isset($_GET['override_double_login'])
        && $_GET['override_double_login'] == $options['override_code']) {
      $msg = '<p id="login_error">' . __('Block Double Logins protections are disabled for this login attempt.', 'wf_bdl') . '</p>' . $msg;
    }

    return $msg;
  } // login message


  // display message after beeing force logged out
  static function wp_login_errors($errors, $url) {
    if (isset($_GET['loggedout_bdl']) && $_GET['loggedout_bdl']) {
      $errors->add('loggedout_bdl', __('You\'ve been logged out by Block Double Logins plugin.', 'wf_bdl'), 'error');
    }

    return $errors;
  } // wp_login_errors

  // send override URL to user's email for safekeeping
  static function ajax_send_override_code() {
    $user = wp_get_current_user();
    $options = self::get_options();

    $msg = __('Use the following URL to override the plugin for one login attempt. You will be able to login regardless of other people who may be logged in with the same IP or username. It bypasses the proxy block too. Please DO NOT share this URL as it\'s supposed be used only in emergencies.', 'wf_bdl') . "\r\n\r\n";
    $msg .= site_url() .'/wp-login.php?override_double_login=' . $options['override_code'];

    $tmp = wp_mail($user->user_email, __('Block Double Logins Override Code', 'wf_bdl'), $msg);
    if ($tmp) {
      echo $user->user_email;
    } else {
      echo '0';
    }

    die();
  } // ajax_send_override_code

  // view bad logins attempt log
  static function ajax_view_log() {
    global $wpdb;
    $out = '';

    $logs = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . WF_BDL_LOG_TABLE . ' ORDER BY id DESC');
    if (!$logs) {
      $out .= '<p>' . __('Log is empty.', 'wf_bdl') . '</p>';
    } else {
      $out .= '<table autofocus="autofocus" id="small-stats">';
      $out .= '<tr><th>' . __('Event', 'wf_bdl') . '</th><th>' . __('IP', 'wf_bdl') . '</th><th>' . __('Username', 'wf_bdl') . '</th><th>' . __('Timestamp', 'wf_bdl') . '</th></tr>';
      foreach ($logs as $log) {
        $time = date(get_option('date_format'), strtotime($log->timestamp)) . ' @ ' . date(get_option('time_format'), strtotime($log->timestamp));
        $out .= '<tr><td>' . $log->reason . '</td><td>' . $log->ip . '</td> <td><a href="users.php?s=' . $log->username . '">' . $log->username . '</a></td><td>' . $time . '</td></tr>';
      } // foreach
      $out .= '</table>';
    }

    die(json_encode($out));
  } // ajax_view_log


  // view all IP based session locks
  static function ajax_view_ips() {
    $ips = self::get_options('ips');
    $options = self::get_options();
    $out = '';

    if (!$options['prevent_double_ip']) {
      $out .= '<p>' . __('IP based double login protection is <b>disabled</b>.', 'wf_bdl') . '</p>';
    } else {
      $out .= '<table autofocus="autofocus" id="small-stats">';
      $out .= '<tr><th>' . __('IP', 'wf_bdl') . '</th><th>' . __('Username', 'wf_bdl') . '</th><th>' . __('Last seen', 'wf_bdl') . '</th></tr>';
      foreach ($ips as $ip => $tmp) {
        if ((time() - $tmp['last_seen']) > $options['session_timeout'] * 60) {
          continue;
        } elseif (time() - $tmp['last_seen'] < 60) {
          $time = __('a few seconds ago', 'wf_bdl');
        } elseif (time() - $tmp['last_seen'] < 30*60) {
          $time = (int) ((time() - $tmp['last_seen']) / 60) . ' min ago';
        } else {
          $time = date(get_option('date_format'), $tmp['last_seen']) . ' @ ' . date(get_option('time_format'), $tmp['last_seen']);
        }
        $out .= '<tr><td>' . $ip . '</td><td><a href="users.php?s=' . $tmp['username'] . '">' . $tmp['username'] . '</a></td><td>' . $time . '</td></tr>';
      } // foreach
      $out .= '</table>';
    }

    die(json_encode($out));
  } // ajax_view_ips


  // view all username based session locks
  static function ajax_view_usernames() {
    $options = self::get_options();
    $users = new WP_User_Query(array('meta_key' => 'bdl_hash', 'meta_value' => '', 'meta_compare' => '!='));
    $out = '';

    if (!$options['prevent_double_username']) {
      $out .= '<p>' . __('Username based double login protection is <b>disabled</b>.', 'wf_bdl') . '</p>';
    } else {
      $out .= '<table autofocus="autofocus" id="small-stats">';
      $out .= '<tr><th>' . __('IP', 'wf_bdl') . '</th><th>' . __('Username', 'wf_bdl') . '</th><th>' . __('Last seen', 'wf_bdl') . '</th></tr>';
      foreach ($users->results as $user) {
        $time = get_user_meta($user->ID, 'bdl_last_seen', true);
        $ip = get_user_meta($user->ID, 'bdl_ip', true);

        if ((time() - $time) > $options['session_timeout'] * 60) {
          continue;
        } elseif ((time() - $time) < 60) {
          $time = __('a few seconds ago', 'wf_bdl');
        } elseif ((time() - $time) < 30*60) {
          $time = (int) ((time() - $time) / 60) . ' min ago';
        } else {
          $time = date(get_option('date_format'), $time) . ' @ ' . date(get_option('time_format'), $time);
        }
        $out .= '<tr><td>' . $ip . '</td><td><a href="users.php?s=' . $user->user_login . '">' . $user->user_login . '</a></td><td>' . $time . '</td></tr>';
      } // foreach
      $out .= '</table>';
    }

    die(json_encode($out));
  } // ajax_view_usernames


  // resets all IP based session locks
  static function ajax_clear_ips($die = true) {
    update_option(WF_BDL_IPS_KEY, array());

    if ($die) {
      die('1');
    } else {
      return true;
    }
  } // ajax_clear_ips

  // resets all username based session locks
  static function ajax_clear_usernames($die = true) {
    $users = new WP_User_Query(array('meta_key' => 'bdl_hash', 'meta_value' => '', 'meta_compare' => '!='));

    if ($users) {
      foreach ($users->results as $user) {
        delete_user_meta($user->ID, 'bdl_hash');
        delete_user_meta($user->ID, 'bdl_last_seen');
        delete_user_meta($user->ID, 'bdl_ip');
      }
    } // if

    if ($die) {
      die('1');
    } else {
      return true;
    }
  } // ajax_clear_usernames


  // returns unique hash based on ip and user agent
  static function get_hash() {
    $tmp = md5(self::get_ip() . @$_SERVER['HTTP_USER_AGENT'] . @NONCE_SALT);

    return $tmp;
  } // get_hash


  // try to get the real IP if user is behind a proxy
  static function get_ip($only_check_proxy = false) {
    $ip = false;

    $proxy_headers = array(
        'HTTP_VIA',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED',
        'HTTP_CLIENT_IP',
        'HTTP_FORWARDED_FOR_IP',
        'VIA',
        'X_FORWARDED_FOR',
        'FORWARDED_FOR',
        'X_FORWARDED',
        'FORWARDED',
        'CLIENT_IP',
        'FORWARDED_FOR_IP',
        'HTTP_PROXY_CONNECTION');

    foreach($proxy_headers as $tmp){
        if (isset($_SERVER[$tmp]) && filter_var($_SERVER[$tmp], FILTER_VALIDATE_IP) !== false) {
          $ip = $_SERVER[$tmp];
          break;
        }
    } // foreach

    if ($only_check_proxy) {
      if ($ip != false) {
        return true;
      } else {
        return false;
      }
    } else {
      if ($ip) {
        return $ip;
      } else {
        return $_SERVER['REMOTE_ADDR'];
      }
    }
  } // get_ip


  // complete options screen
  static function settings_screen() {
    $options = self::get_options();

    if (!current_user_can('manage_options')) {
      wp_die('Cheating aren\'t you?');
    }

    $rolenames = array();
    $roles = get_editable_roles();
    foreach ($roles as $role_id => $role) {
      $rolenames[] = array('val' => $role_id, 'label' => $role['name']);
    }

    echo '<div class="wrap">';
    screen_icon();
    echo '<h2>' . __('Block Double Logins Settings', 'wf_bdl') . '</h2>';
    echo '<form method="post" action="options.php">';
    settings_fields('wf_bdl');

    echo '<div id="wf-bdl-tabs">';

    echo '<ul class="nav-tab-wrapper">';
    echo '<li class="nav-tab"><a href="#bdl-general">' . __('General Settings', 'wf_bdl') . '</a></li>';
    echo '<li class="nav-tab"><a href="#bdl-username">' . __('Username Based Blocking', 'wf_bdl') . '</a></li>';
    echo '<li class="nav-tab"><a href="#bdl-ip">' . __('IP Based Blocking', 'wf_bdl') . '</a></li>';
    echo '<li class="nav-tab"><a href="#bdl-tools">' . __('Tools', 'wf_bdl') . '</a></li>';
    echo '</ul>';


    echo '<div id="bdl-general">';
    echo '<table class="form-table">';
    echo '<tr valign="top">
          <th scope="row"><label for="login_msg">' . __('Login Warning Message', 'wf_bdl') . '</label></th>
          <td><input name="wf_bdl[login_msg]" type="text" id="login_msg" value="' . esc_attr($options['login_msg']) . '" class="regular-text larger-text" /><p class="description">Message displayed on top of default login form. Leave blank to remove.</p></td>
          </tr>';

    echo '<tr valign="top">
          <th scope="row"><label for="override_code">' . __('Plugin Override Code', 'wf_bdl') . '</label></th>
          <td>' . site_url() .'/wp-login.php?override_double_login=<input name="wf_bdl[override_code]" type="text" id="override_code" value="' . esc_attr($options['override_code']) . '" class="small-text smaller-text" /><p class="description description2">Use this URL to force your next login regardless of other people who may be logged in with the same IP or username. It disables the proxy block too.<br>Please do not share this URL as it\'s supposed to be used only in emergencies. <a id="bdl_send_override_code" class="button-secondary" href="#">Send URL to  email for safekeeping</a></p></td>
          </tr>';

    echo '<tr valign="top">
          <th scope="row"><label for="session_timeout">' . __('Session Lock Timeout', 'wf_bdl') . '</label></th>
          <td><input min="1" step="1" max="240" name="wf_bdl[session_timeout]" type="number" id="session_timeout" value="' . esc_attr($options['session_timeout']) . '" class="small-text" /> minutes<p class="description">Time for user inactivity after which the locks on username and IP are released. If the user properly logs out the lock is imediately released. Default: 10 minutes.</p></td>
          </tr>';

    echo '<tr valign="top">
          <th scope="row"><label for="roles">' . __('Controlled User Roles', 'wf_bdl') . '</label></th>
          <td><select size="6" multiple="multiple" name="wf_bdl[roles][]" id="roles">';
    self::create_select_options($rolenames, $options['roles']);
    echo '</select><span class="description"> Use CTRL to select multiple roles.</span>';
    echo '<p class="description">User roles on which all plugin rules are applied. Default: all roles.</p>';
    echo '</td></tr>';
    echo '</table>';
    submit_button(__('Save Settings', 'wf_bdl'));
    echo '</div>';

    echo '<div id="bdl-username">';
    echo '<table class="form-table">';
    echo '<tr valign="top">
          <th scope="row"><label for="prevent_double_username">' . __('Prevent Double Login Based on Username', 'wf_bdl') . '</label></th>
          <td><input name="wf_bdl[prevent_double_username]" type="checkbox" id="prevent_double_username" value="1"' . checked('1', $options['prevent_double_username'], false) . '/><span class="description">At any given time a single WordPress account can only be used by one person. Default: checked.</span></td></tr>';

    echo '<tr valign="top">
          <th scope="row"><label for="prevent_double_username_error">' . __('Error Message on Login', 'wf_bdl') . '</label></th>
          <td><input name="wf_bdl[prevent_double_username_error]" type="text" id="prevent_double_username_error" value="' . esc_attr($options['prevent_double_username_error']) . '" class="regular-text" /><p class="description">Message displayed on login form when double login is attempted.</p></td>
          </tr>';
    echo '</table>';
    submit_button(__('Save Settings', 'wf_bdl'));
    echo '</div>';

    echo '<div id="bdl-ip">';
    echo '<table class="form-table">';
    echo '<tr valign="top">
          <th scope="row"><label for="prevent_double_ip">' . __('Prevent Double Login Based on IP', 'wf_bdl') . '</label></th>
          <td><input name="wf_bdl[prevent_double_ip]" type="checkbox" id="prevent_double_ip" value="1"' . checked('1', $options['prevent_double_ip'], false) . '/><span class="description">At any given time a single IP can only be logged in with one WordPress account. Default: unchecked.</span></td></tr>';

    echo '<tr valign="top">
          <th scope="row"><label for="prevent_double_ip_error">' . __('Error Message on Login', 'wf_bdl') . '</label></th>
          <td><input name="wf_bdl[prevent_double_ip_error]" type="text" id="prevent_double_ip_error" value="' . esc_attr($options['prevent_double_ip_error']) . '" class="regular-text" /><p class="description">Message displayed on login form when double login is attempted.</p></td>
          </tr>';

    echo '<tr valign="top">
          <th scope="row"><label for="block_proxy">' . __('Block Users Who Use Proxies', 'wf_bdl') . '</label></th>
          <td><input name="wf_bdl[block_proxy]" type="checkbox" id="block_proxy" value="1"' . checked('1', $options['block_proxy'], false) . '/><span class="description">People who use proxies will not be able to login. WARNING! There might be false negatives and in rare cases false positives. Default: unchecked.</span></td></tr>';
    echo '<tr valign="top">
          <th scope="row"><label for="block_proxy_error">' . __('Error Message for Proxy Users', 'wf_bdl') . '</label></th>
          <td><input name="wf_bdl[block_proxy_error]" type="text" id="block_proxy_error" value="' . esc_attr($options['block_proxy_error']) . '" class="regular-text" /><p class="description">Message displayed on login form after users behind a proxy attempt to login.</p></td>
          </tr>';

    echo '</table>';
    submit_button(__('Save Settings', 'wf_bdl'));
    echo '</div>';

    echo '<div id="bdl-tools">';
    echo '<table class="form-table">';
    echo '<tr valign="top">
          <th scope="row"><a id="bdl_view_log" class="button-secondary" href="#">' . __('View blocked logins log', 'wf_bdl') . '</a></th>
          <td><span class="description">Following events are logged: login when plugin override code is used, blocked login when username is in use, blocked login when IP is in use, blocked login when user is behind a proxy. Log always contains last ' . WF_BDL_MAX_LOG . ' events.</span></td></tr>';
    echo '<tr valign="top">
          <th scope="row"><a id="bdl_view_usernames" class="button-secondary" href="#">' . __('View active username based locks', 'wf_bdl') . '</a></th>
          <td><span class="description">If you need to clear locks (sessions) use the buttons below, or wait ' . $options['session_timeout'] . ' minutes for them to timeout.</span></td></tr>';
    echo '<tr valign="top">
          <th scope="row"><a id="bdl_view_ips" class="button-secondary" href="#">' . __('View active IP based locks', 'wf_bdl') . '</a></th>
          <td><span class="description">If you need to clear locks (sessions) use the buttons below, or wait ' . $options['session_timeout'] . ' minutes for them to timeout.</span></td></tr>';
    echo '<tr valign="top">
          <th scope="row"><a id="bdl_clear_usernames" class="button-secondary button-delete" href="#">' . __('Reset all username based locks', 'wf_bdl') . '</a></th>
          <td><span class="description">People who are logged in will not be kicked out. Their sessions will be reinitialized as soon as they reload any admin page.</span></td></tr>';
    echo '<tr valign="top">
          <th scope="row"><a id="bdl_clear_ips" href="#" class="button-secondary button-delete">' . __('Reset all IP based locks', 'wf_bdl') . '</a></th>
          <td><span class="description">People who are logged in will not be kicked out. Their sessions will be reinitialized as soon as they reload any admin page.</span></td></tr>';
    echo '</table>';
    echo '</div>';

    echo '</div>'; // tabs
    echo '<p>&copy; 2014 <a href="http://www.webfactoryltd.com/" target="_blank">Web factory Ltd</a> - <a target="_blank" href="' . plugin_dir_url(__FILE__) . 'documentation/' . '" title="' . __('View documentation', 'wf_bdl') . '">' . __('Documentation', 'wf_bdl') . '</a> - <a target="_blank" href="http://codecanyon.net/user/WebFactory#contact" title="' . __('Contact Web factory', 'wf_bdl') . '">' . __('Support', 'wf_bdl') . '</a></p>';
    echo '</form>';
    echo '</div>'; // wrap
    echo '<div id="bdl_dialog" class="wp-dialog" style="display: none;" title="Block Double Logins"><div id="dialog_content"></div></div>';
  } // settings_screen


  // set default options
  static function default_settings($force = false) {
    global $wp_roles;
    $options = self::get_options();

    $defaults = array('login_msg' => 'Multiple simultaneous logins with a single account are not allowed.',
                      'override_code' => strtoupper(substr(md5(rand(0, 10000)), 0, 8)),
                      'session_timeout' => '10',
                      'roles' => array_keys($wp_roles->roles),
                      'prevent_double_username' => '1',
                      'prevent_double_username_error' => 'This account is already in use by another person.',
                      'prevent_double_ip' => '0',
                      'prevent_double_ip_error' => 'Your IP is already logged in with another account.',
                      'block_proxy' => '0',
                      'block_proxy_error' => 'This site does not allow users behind a proxy to login.');

    if ($force || !$options || @!$options['session_timeout']) {
      update_option(WF_BDL_OPTIONS_KEY, $defaults);
    }
  } // default_settings


  // sanitize settings on save
  static function sanitize_settings($values) {
    $old_options = self::get_options();

    foreach ($values as $key => $value) {
      switch ($key) {
        case 'login_msg':
        case 'override_code':
        case 'prevent_double_username_error':
        case 'prevent_double_ip_error':
        case 'block_proxy_error':
          $values[$key] = trim($value);
        break;
        case 'session_timeout':
        case 'prevent_double_username':
        case 'prevent_double_ip':
        case 'block_proxy':
          $values[$key] = (int) $value;
        break;
      } // switch
    } // foreach

    $values = self::check_var_isset($values, array('roles' => array(), 'prevent_double_username' => 0, 'prevent_double_ip' => 0, 'block_proxy' => 0));

    if (!$values['session_timeout']) {
      $values['session_timeout'] = '5';
    }

    return array_merge($old_options, $values);
  } // sanitize_settings


  // all settings are saved in one option key
  static function register_settings() {
    register_setting(WF_BDL_OPTIONS_KEY, WF_BDL_OPTIONS_KEY, array(__CLASS__, 'sanitize_settings'));
  } // register_settings


  // helper function for creating HTML dropdowns
  static function create_select_options($options, $selected = null, $output = true) {
    $out = "\n";

    if(!is_array($selected)) {
      $selected = array($selected);
    }

    foreach ($options as $tmp) {
      if (in_array($tmp['val'], $selected)) {
        $out .= "<option selected=\"selected\" value=\"{$tmp['val']}\">{$tmp['label']}&nbsp;</option>\n";
      } else {
        $out .= "<option value=\"{$tmp['val']}\">{$tmp['label']}&nbsp;</option>\n";
      }
    } // foreach

    if ($output) {
      echo $out;
    } else {
      return $out;
    }
  } // create_select_options


  // helper function for empty $_POST variables handling
  static function check_var_isset($values, $variables) {
    foreach ($variables as $key => $value) {
      if (!isset($values[$key])) {
        $values[$key] = $value;
      }
    }

    return $values;
  } // check_var_isset


  // display warning if WP is outdated
  static function min_version_error_wp() {
    echo '<div id="message" class="error"><p>' . __('Block Double Logins <b>requires WordPress version 3.7</b> or higher to function properly.', 'wf_bdl') . ' You\'re using WordPress version ' . get_bloginfo('version') . '. Please <a href="' . admin_url('update-core.php') . '" title="Update WP core">update</a>.</p></div>';
  } // min_version_error_wp


  // make sure everything is ok on activation
  static function activate() {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    global $wpdb;

    $log = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . WF_BDL_LOG_TABLE . "` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `username` varchar(64) NOT NULL,
            `ip` varchar(15) NOT NULL,
            `reason` varchar(64) NOT NULL,
            `timestamp` datetime NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
    dbDelta($log);

    self::ajax_clear_ips(false);
    self::ajax_clear_usernames(false);
  } // activate


  // clean-up when deactivated
  static function deactivate() {
    global $wpdb;

    delete_option(WF_BDL_OPTIONS_KEY);
    delete_option(WF_BDL_IPS_KEY);
    self::ajax_clear_usernames(false);
    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . WF_BDL_LOG_TABLE);
  } // deactivate
} // wf_bdl class


// hook everything up
add_action('init', array('wf_bdl', 'init'));

// texdomain has to be loaded earlier
add_action('plugins_loaded', array('wf_bdl', 'plugins_loaded'));

// small stuff on activation
register_activation_hook( __FILE__, array('wf_bdl', 'activate'));

// when deativated clean up
register_deactivation_hook( __FILE__, array('wf_bdl', 'deactivate'));