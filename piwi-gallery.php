<?php
/**
 * @package piwi-gallery
 * @version 0.1.1
*/
/*
Plugin Name: Piwi-Gallery
Version: 0.1.1
Description: Just another wp-gallery plugin supporting flexible storage (BaiduBCS, local, etc.).
Author: zolunX10
Require: bcs.class.php
*/
// require_once(ABSPATH.'dBug.php');
if (WP_DEBUG)
  error_reporting(E_ALL ^ E_NOTICE);

// define constants
if ( !defined('WP_PLUGIN_URL') )
  define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins' );
if ( !defined('WP_PLUGIN_URL') )
  define('WINABSPATH', str_replace("\\", "/", ABSPATH) );
define('PWG_FOLDER', basename(dirname(__FILE__)));
define('PWG_ABSPATH', trailingslashit( str_replace("\\","/", WP_PLUGIN_DIR . '/' . PWG_FOLDER)));
define('PWG_URLPATH', trailingslashit( plugins_url(PWG_FOLDER)));

/**
 * PHP's buildin basename() cannot support filename containing chinese
 */
if (!function_exists('sbasename')) {
  function sbasename($filename, $ext = "") {
    preg_match('/([^\/]+)\/?$/', $filename, $matches);
    return $matches[1];
  }
}
if (!function_exists('mb_pathinfo')) {
  function mb_pathinfo($filepath) {
       preg_match('%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^\.\\\\/]+?)|))[\\\\/\.]*$%im',$filepath,$m);
       if($m[1]) $ret['dirname']=$m[1];
       if($m[2]) $ret['basename']=$m[2];
       if($m[5]) $ret['extension']=$m[5];
       if($m[3]) $ret['filename']=$m[3];
       return $ret;
   }
 }

require_once(PWG_ABSPATH . 'conf.inc.php');

if (!class_exists('PWG_GalleryLoader')) {
  class PWG_GalleryLoader {
    var $options;
    var $plugin_name = '';
    function __construct() {
      $this->options = get_option('pwg_options');
      // define db tables
      global $wpdb;
      $wpdb->pwg_images = $wpdb->prefix.'pwg_images';
      $wpdb->pwg_galleries = $wpdb->prefix.'pwg_galleries';

      $this->plugin_name = basename(dirname(__FILE__)).'/'.basename(__FILE__);

      // embed js & css
      add_action('admin_enqueue_scripts', array(&$this, 'prepare_scripts'));
      // $this->prepare_scripts();
      // hook json request
      require_once(PWG_ABSPATH . 'ajax.php');

      // load dependencies
      require_once(PWG_ABSPATH . 'admin/shortcode.php');

      register_activation_hook($this->plugin_name, array(&$this, 'activate'));
      register_deactivation_hook( $this->plugin_name, array(&$this, 'deactivate'));
      register_uninstall_hook($this->plugin_name, array(__CLASS__, 'uninstall'));

      // hook menu
      add_action('admin_menu', array(&$this, 'add_menu'));
    }
    /****
     * trigger when activate the plugin
     */
    function activate() {
      global $wpdb;
      if (version_compare(PHP_VERSION, '5.2.0', '<')) {
        deactivate_plugins($this->plugin_name); // Deactivate ourself
        wp_die("Sorry, but you can't run this plugin, it requires PHP 5.2 or higher.");
        return;
      }
      self::remove_transients();
      include_once(PWG_ABSPATH . 'admin/install.php');

      if (is_multisite()) {
        $network=isset($_SERVER['SCRIPT_NAME'])?$_SERVER['SCRIPT_NAME']:"";
        $is_network=strstr($network, '/wp-admin/network/plugins.php')?true:false;
        // $activate=isset($_GET['action'])?$_GET['action']:"";
        // $is_activation=($activate=='deactivate')?false:true;

        if ($is_network) {  // activate on all blogs
          $old_blog = $wpdb->blogid;
          $blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs", NULL));
          foreach ($blogids as $blog_id) {
            switch_to_blog($blog_id);
            PWG_install();
          }
          switch_to_blog($old_blog);
        } else {
          PWG_install();
        }
      } else {
        PWG_install();
      }
    }

    function deactivate() {
      
    }
    static function unsintall() {
      include_once(PWG_ABSPATH . 'admin/install.php');
      PWG_uninstall();
    }

    static function remove_transients() {
      //TODO ?
    }
    /****
     * add sidbar menus to wp-admin
     */
    function add_menu() {
      add_menu_page(__("piwi-gallery", 'piwi-gallery'), __("piwi-gallery", 'piwi-gallery'), 'upload_files', 'piwi_gallery', array(&$this, 'dispatch_page'));
      add_submenu_page('piwi_gallery', __("manage galleries", 'piwi-gallery'), __("manage galleries", 'piwi-gallery'), 'upload_files', 'piwi_gallery', array(&$this, 'dispatch_page'));
      // add_submenu_page('piwi_gallery', __("settings", 'piwi-gallery'), __("upload", 'piwi-gallery'), 'manage_options', 'pwg_upload', array(&$this, 'dispatch_page'));
      add_submenu_page('piwi_gallery', __("settings", 'piwi-gallery'), __("settings", 'piwi-gallery'), 'manage_options', 'pwg_settings', array(&$this, 'dispatch_page'));
    }
    function dispatch_page() {
      switch ($_GET['page']) {
        case 'piwi_gallery':
          if(!current_user_can('upload_files')) { wp_die(__('You do not have sufficient permissions to access this page.')); }
          include_once(PWG_ABSPATH . 'admin/GalleryController.php');
          break;
        case 'pwg_settings':
          if(!current_user_can('manage_options')) { wp_die(__('You do not have sufficient permissions to access this page.')); }
          include_once(PWG_ABSPATH . 'admin/SettingsController.php');
          break;
        case 'pwg_upload':
          if(!current_user_can('upload_files')) { wp_die(__('You do not have sufficient permissions to access this page.')); }
          include_once(PWG_ABSPATH . 'admin/UploadController.php');
          break;
        default:
          return 0;
      }
      return 1;
    }
    function prepare_scripts() {
      wp_register_style('PWG_style', PWG_URLPATH . '_/style.css', array('thickbox'));
      wp_enqueue_style('PWG_style');

      wp_register_script( 'es5-shim', PWG_URLPATH . '_/vendor/es5-shim.min.js');
      wp_register_script( 'angular',  PWG_URLPATH . '_/vendor/angular.min.js', array('es5-shim'));
      wp_register_script('PWG_common', PWG_URLPATH . '_/common.js', array('angular'));
      wp_localize_script('PWG_common', 'wpData', array('siteUrl'=>site_url(), 'ajaxUrl'=>admin_url('admin-ajax.php')));
      wp_register_script('angular.file-upload', PWG_URLPATH . '_/vendor/angular-file-upload.min.js', array('angular'));
      wp_register_script('PWG_gallery', PWG_URLPATH . '_/gallery.js', array('PWG_common'));
      wp_register_script('PWG_upload', PWG_URLPATH . '_/upload.js', array('angular.file-upload', 'PWG_common', 'PWG_gallery'));
    }
    function body_attrs() {

    }
  }
}

global $piwi_gallery; 
$piwi_gallery = new PWG_GalleryLoader();
?>