<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

/**** 
 * create wpdb tables & init options
 */
function PWG_install() {
  global $wpdb , $wp_roles, $wp_version;
  if ( !current_user_can('activate_plugins') ) {
    return; 
  }
  /*
  $role = get_role('administrator');
  // We need this role, no other chance
  if ( empty($role) ) {
    wp_die("bcs-gallery: need administrator role");
    return;
  }
  $role->add_cap('bcs-gallery_set_options');
  $role->add_cap('bcs-gallery_manage_galleries');
  */
  
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

  $table_images = $wpdb->prefix . 'pwg_images';
  $table_galleries = $wpdb->prefix . 'pwg_galleries';
  $charset_collate = '';
  if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') ) {
    if ( ! empty($wpdb->charset) )
      $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
    if ( ! empty($wpdb->collate) )
      $charset_collate .= " COLLATE $wpdb->collate";
  }
  $sql = "CREATE TABLE $table_images (
      `iid` BIGINT(20) NOT NULL AUTO_INCREMENT,
      `gid` BIGINT(20) DEFAULT '0' NOT NULL,
      `post_id` BIGINT(20) DEFAULT '0' NOT NULL,
      `path` VARCHAR(512) NOT NULL,
      `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
      `title` TEXT NULL, 
      `description` TEXT NULL,
      `status` TINYINT NOT NULL DEFAULT '0',
      `sortorder` BIGINT(20) NOT NULL DEFAULT '0',
      `meta_data` MEDIUMTEXT,
      PRIMARY KEY (`iid`),
      INDEX gid (`gid`),
      INDEX path (`path`),
      INDEX status (`status`),
      INDEX sortorder (`sortorder`)
    ) $charset_collate;";
  dbDelta($sql);

  $sql = "CREATE TABLE $table_galleries (
      `gid` BIGINT(20) NOT NULL AUTO_INCREMENT,
      `path` VARCHAR(512) NOT NULL,
      `title` TEXT NULL,
      `description` TEXT NULL,
      `thumb_id` BIGINT(20) NOT NULL DEFAULT '0',
      `author` BIGINT(20) NOT NULL DEFAULT '0',
      `status` TINYINT NOT NULL DEFAULT '0',
      PRIMARY KEY (`gid`),
      INDEX path (`path`),
      INDEX status (`status`)
    ) $charset_collate;";
  dbDelta($sql);

  // verify the table has been successfully created
  if (!$wpdb->get_var("SHOW TABLES LIKE '$table_images'")) {
    wp_die("bcs-gallery: unalbe to create tables, please check your database settings");
  }
  // init options
  $options = get_option('pwg_options');
  if (empty($options)) {
    PWG_set_default_options();
  }
  // prepare local directory
  require_once(PWG_ABSPATH . 'ls_adjuster.php');
  LocalStorageAdjuster::init_folder($options['gallery_folder']);
}

function PWG_uninstall() {
  global $wpdb;
  $table_images = $wpdb->pwg_images;
  $table_galleries = $wpdb->pwg_galleries;
  $wpdb->query("DROP TABLE IF EXISTS `$table_images`");
  $wpdb->query("DROP TABLE IF EXISTS `$table_galleries`");
  delete_option('pwg_options');
}

/****
 * set default options & save to wpdb
 */
function PWG_set_default_options() {
  $options = array(
    'using_bcs'   => false,
    'bucket'      => "",
    'gallery_folder'=> "/piwi-gallery",
    'ak'          => "",
    'sk'          => "",

    'delete_img'  => true,  // force delete file when "delete"
    'img_auto_resize'=> true,  // resize after upload

    'gallery_pagesize'=> 20,  // number of image per page
    );
  update_option('pwg_options', $options);
}
?>