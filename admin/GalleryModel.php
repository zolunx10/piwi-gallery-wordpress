<?php 
define('REG_IMAGES', '/\.(?:bmp|gif|jpg|jpeg|png)\s*$/');
/**
 * the buildin basename() function cannot handle Chinese character
 */
class PWG_GalleryModel {
  const OBJ_NOT_EXISTS = 4;
  const OBJ_TO_DELETE = 2;

  var $last_results = array();
  var $fs = null;
  var $options;
  public function __construct() {
    $this->options = get_option('pwg_options');
    if ($this->options['using_bcs']) {
      require_once(PWG_ABSPATH . 'bcs_adjuster.php');
      $ak = $this->options['ak'];
      $sk = $this->options['sk'];
      if ($ak && $sk) {
        $this->fs = new BCSAdjuster($ak, $sk, $this->options['bucket']);
      }
    } else {
      require_once(PWG_ABSPATH . 'ls_adjuster.php');
      $this->fs = new LocalStorageAdjuster();
    }
  }

  /****
   * resynchronize image data with BSC server
   * images those no longer exist on BCS would be marked status=1
   */
  public function resync() {
    global $wpdb;
    $dir = $this->options['gallery_folder'];
    $objs = $this->fs->list_object_by_dir($dir . '/', 1);  // set list_model=1 to retrieve sub dirctories only
    if (!$objs) {
      return false;
    }
    $wpdb->query("UPDATE `$wpdb->pwg_galleries` SET `status` = " . PWG_GalleryModel::OBJ_NOT_EXISTS);
    foreach ($objs as $row) {
      $this->resync_gallery($row['object']);
    }
    return true;
    // TODO: delete wpdb rows that not exist in BCS
  }
  /****
   * $dir should contain images (nor sub directories)
   */
  public function resync_gallery($dir) {
    global $wpdb;
    $objs = $this->fs->list_object_by_dir($dir, 0);
    $gallery_name = sbasename($dir);
    $gid = 0;
    $db_imgs = array();
    $db_gallery = $wpdb->get_row($wpdb->prepare(
      "SELECT `gid`, `thumb_id` FROM `$wpdb->pwg_galleries` WHERE `path`='%s'", $dir), ARRAY_A);
    if (is_null($db_gallery)) {   // add an gallery
      $user_id = get_current_user_id();
      $wpdb->query($wpdb->prepare(
        "INSERT INTO `$wpdb->pwg_galleries` (`path`, `title`, `author`) VALUES ('%s', '%s', %d)", $dir, $gallery_name, $user_id));
      $gid = $wpdb->insert_id;
    } else {
      $gid = $db_gallery['gid'];
      $wpdb->query($wpdb->prepare("UPDATE `$wpdb->pwg_galleries` SET `status` = 0 WHERE `gid`=%d", $gid));
      $db_res = $wpdb->get_results($wpdb->prepare(
        "SELECT `iid`, `path` FROM `$wpdb->pwg_images` WHERE `gid` = %d", $gid), ARRAY_A);
      foreach ($db_res as $row) {
        $db_imgs[$row['path']] = array(
          'iid' => $row['iid'],
          'path'=> $row['path'],
          'checked'=> false);
      }
    }

    $bcs_imgs = array();
    foreach ($objs as $obj) {
      if (preg_match(REG_IMAGES, $obj['object']))
        $bcs_imgs[trim($obj['object'])] = $obj;
    }
    $sql_new_values = array();
    $sql_placehoders = array();
    foreach ($bcs_imgs as $k => $v) {
      if (array_key_exists($k, $db_imgs)) {   // the image already exists in wpdb
        $db_imgs[$k]['checked'] = true;
      } else {    // insert new images into wpdb
        array_push($sql_new_values, $gid, $v['object'], "FROM_UNIXTIME(" . $v['mdatetime'] . ")", $v['object']);
        array_push($sql_placehoders, "(%d, '%s', '%s', '%s')");
      }
    }
    $wpdb->query($wpdb->prepare("UPDATE `$wpdb->pwg_images` SET `status` = 0 WHERE `gid` = %d", $gid));
    if ($sql_new_values) {
        $wpdb->query($wpdb->prepare( 
          "INSERT INTO `$wpdb->pwg_images` (`gid`, `path`, `date`, `title`) VALUES " . implode($sql_placehoders, ','), $sql_new_values));
    }
    $sql_expired_values = array();
    foreach ($db_imgs as $k => $v) {
      if (! $v['checked']) {
        array_push($sql_expired_values, $v['iid']);
      }
    }
    if ($sql_expired_values) {
      $wpdb->query("UPDATE `$wpdb->pwg_images` SET `status` = " . PWG_GalleryModel::OBJ_NOT_EXISTS . " " . $wpdb->escape("WHERE `gid` = $gid AND `iid` IN (" . implode($sql_expired_values, ',') . ")" ));
    }

    // update thumbnail id
    if (is_null($db_gallery) || $db_gallery['thumb_id'] == 0) {
      $row = $wpdb->get_row($wpdb->prepare(
        "SELECT `iid` FROM `$wpdb->pwg_images` WHERE `gid` = %d AND `status` = 0", $gid), ARRAY_A);
      $thumb_id = ($row['iid'] ? $row['iid'] : 0);
      $wpdb->query($wpdb->prepare(
        "UPDATE `$wpdb->pwg_galleries` SET `thumb_id` = %d WHERE `gid` = %d", $thumb_id, $gid));
    }
  }
  private function _where_id($id = null, $id_name = "`id`") {
    global $wpdb;
    if (!isset($id)) {
      return "1";
    } elseif (is_array($id)) {
      return $wpdb->escape($id_name . " IN (" . implode(',', $id) . ")");
    } else {
      return $wpdb->escape($id_name . " = " . $id);
    }
  }
  /****
  */
  public function get_galleries($gid = null, $status = 0) {
    global $wpdb;
    $sql = "SELECT ta.*, ti.`path` AS `thumb_path` FROM `$wpdb->pwg_galleries` AS ta LEFT JOIN `$wpdb->pwg_images` AS ti ON ta.`thumb_id` = ti.`iid` WHERE " . $this->_where_id($gid, "ta.`gid`");
    $sql = $sql . " AND ta.`status` = " . $status;
    $results = $wpdb->get_results($sql, ARRAY_A);
    foreach ($results as &$v) {      
      $v['gid'] = intval($v['gid']);
      $v['thumb_id'] = intval($v['thumb_id']);
    }
    return $results;
  }
  /****
  * fetch images belonging to specific gallery(s)
  */
  public function get_images_by_gallery($gid) {
    global $wpdb;
    $sql = "SELECT * FROM `$wpdb->pwg_images` WHERE `status` = 0 AND " . $this->_where_id($gid, '`gid`');
    $sql = $sql . " ORDER BY `gid`, `sortorder` ASC";
    $results = $wpdb->get_results($sql, ARRAY_A);
    foreach ($results as &$v) {      
      $v['gid'] = intval($v['gid']);
      $v['iid'] = intval($v['iid']);
    }
    return $results;
  }
  public function update_gallery($gid, $fields) {
    global $wpdb;
    $res = $wpdb->update($wpdb->pwg_galleries, $fields, array('gid'=>$gid));
    return $res;
  }
  /**
  * here only insert a row into wpdb, the actual mkdir() is delayed to file uploading
  */
  public function add_gallery($dir, $visual_name = null) {
    global $wpdb;
    if (!isset($visual_name)) {
      $visual_name = $name;
    }
    $row = array('path'=>'/' . $dir . '/', 'title'=>$visual_name, 'author'=> get_current_user_id(), 'thumb_url'=>"");
    // check if the gallery already exists
    $t = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$wpdb->pwg_galleries` WHERE `path` = '%s'", $row['path']), ARRAY_A);
    if ($t) {
      $wpdb->query($wpdb->prepare("UPDATE `$wpdb->pwg_galleries` SET `status` = 0 WHERE `gid` = %d", $t['gid']));
      return $t;
    }

    $wpdb->query($wpdb->prepare(
      "INSERT INTO `$wpdb->pwg_galleries` (`path`, `title`, `author`) VALUES ('%s', '%s', %d)", $row['path'], $row['title'], $user_id));
    $row['gid'] = $wpdb->insert_id;
    $row['new'] = true;
    return $row;
  }
  /**
  * @return new added image row
  */
  public function add_image($gid, $name, $img_path, $thumb_path = "", $visual_name = null) {
    if (!isset($visual_name)) {
      $visual_name = $name;
    }
    global $wpdb;
    $db_gallery = $wpdb->get_row($wpdb->prepare(
      "SELECT `path`, `title` FROM `$wpdb->pwg_galleries` WHERE `gid`='%d'", $gid), ARRAY_A);
    if (empty($db_gallery)) {
      return false;
    }
    $this->fs->upload_file($db_gallery['path'], $name, $img_path, $visual_name);
    $this->fs->upload_thumb($db_gallery['path'] . 'thumbs/', 'thumbs_' . $name, $thumb_path, $visual_name);
    $row = array('gid'=>$gid, 'path'=>$db_gallery['path'] . $name, 'title'=>$visual_name, 'status'=>0, 'description'=>"");
    $sql_res = $wpdb->query($wpdb->prepare( 
          "INSERT INTO `$wpdb->pwg_images` (`gid`, `path`, `date`, `title`) VALUES (%d, %s, FROM_UNIXTIME('%s'), '%s')", $gid, $row['path'], time(), $row['title']));
    if (!$sql_res) {
      return false;
    }
    $row['iid'] = $wpdb->insert_id;
    return $row;
  }
  /**
  * delete a gallery by gid
  * @param is_hard: if set to true the corresponding file would be delete
  */
  public function del_gallery($gid, $is_hard = false) {
    global $wpdb;
    $sql = "UPDATE `$wpdb->pwg_galleries` SET `status` = " . PWG_GalleryModel::OBJ_TO_DELETE . " WHERE " . $this->_where_id($gid, "`gid`");
    $res = $wpdb->query($sql);
    if ($is_hard) {
      // $this->fs->del_obj(); // TODO: real delete
    }
    return $res;
  }
  public function del_image($iid, $is_hard = false) {
    global $wpdb;
    $sql = "UPDATE `$wpdb->pwg_images` SET `status` = " . PWG_GalleryModel::OBJ_TO_DELETE . " WHERE " . $this->_where_id($iid, "`iid`");
    $res = $wpdb->query($sql);
    if ($is_hard) {
      // $this->fs->del_obj(); // TODO: real delete
    }
    return $res;
  }
  public function get_baseurl() {
    return $this->fs->baseurl;
  }
  /**
  * conpose thumb path via bcs_path
  */
  public static function get_thumbpath_by_path($path) {
    return dirname($path) . '/thumbs/thumbs_' . sbasename($path);
  }
}
?>