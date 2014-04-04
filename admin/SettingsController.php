<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }
include_once(PWG_ABSPATH . 'admin/SettingsView.php');

function PWG_settings_index() {
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['resync_submit'])) {
      include_once(PWG_ABSPATH . 'admin/GalleryModel.php');
      $gallery = new PWG_GalleryModel();
      if (! $gallery->resync()) {
        echo "resync failed.";
      } else {
        echo "resync done.";
      }
    }
    if (isset($_POST['settings_submit']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'pwg_settings') ) {
      // update options in wpdb
      $options = array();
      if (array_key_exists('ak', $_POST) && false === getenv ( 'HTTP_BAE_ENV_AK' )) {
        $options['ak'] = trim(stripslashes($_POST['ak']));
      }
      if (array_key_exists('sk', $_POST) && false === getenv ( 'HTTP_BAE_ENV_SK' )) {
        $options['sk'] = trim(stripslashes($_POST['sk']));
      }
      if (array_key_exists('bucket', $_POST)) {
        $options['bucket'] = trim(stripslashes($_POST['bucket']));
      }
      if (array_key_exists('folder', $_POST)) {
        $options['gallery_folder'] = trim(stripslashes($_POST['folder']));
      }
      if ($_POST['using_bcs'] /* == 'on' */) {
        $options['using_bcs'] = true;
      } else {
        $options['using_bcs'] = false;
      }
      if(!empty($options)){
        update_option('pwg_options', $options);
      }
    }
  }
  // generate view variables
  $data = array();
  $data['action_url'] = wp_nonce_url(admin_url('admin.php?page=' . $_GET['page']), 'pwg_settings');
  $data['sync_url'] = admin_url('admin.php?page=' . $_GET['page']);
  $options = get_option('pwg_options');
  // escape option values
  $options['ak'] = esc_attr($options['ak']);
  $options['sk'] = esc_attr($options['sk']);
  $options['bucket'] = esc_attr($options['bucket']);
  $options['gallery_folder'] = esc_attr($options['gallery_folder']);
  $data['options'] = $options;
  $viewer = new PWG_SettingsView();
  $viewer->index($data);
}
PWG_settings_index();
?>