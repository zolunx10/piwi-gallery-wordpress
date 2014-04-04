<?php
/**
 * implement local storage
 */
define('PWG_LOCAL_FOLDER', 'piwi-gallery');
class LocalStorageAdjuster {
  var $basepath;
  var $baseurl;
  var $last_info; 
  function __construct() {
    $options = get_option('pwg_options');
    $upload_dir = wp_upload_dir();
    $this->baseurl = $upload_dir['baseurl'] . '/' . PWG_LOCAL_FOLDER;
    $this->basepath = $upload_dir['basedir'] . '/' . PWG_LOCAL_FOLDER;
  }
  /**
   * @param list_model  = 0:object 1:subdirectory
   */
  function list_object_by_dir($dir = '/', $list_model = 2, $opt = null) {
    $path = $this->basepath . $dir;
    if (!is_dir($path)) {
      return false;
    } else {
      $raw = scandir($path);
      $res = array();
      if ($list_model == 0) {
        foreach ($raw as $key => $v) {
          if (!is_dir($path . $v)) {
            $res[] = array('object' => $dir . $v);
          }
        }
      } elseif ($list_model == 1) {
        foreach ($raw as $key => $v) {
          if ($v != '.' && $v !='..' && is_dir($path . $v)) {
            $res[] = array('object' => $dir . $v . '/');
          }
        }
      } else {
        foreach ($raw as $key => $v) {
          if ($v != '.' && $v !='..') {
            $res[] = array('object' => $dir . $v . '/');
          }
        }
      }
      return $res;
    }
  }
  /**
   */
  function create_folder($pathname, $name) {

  }
  /**
   * @param dir: would contain leading & trailing '/'
   * @param dst_name: name of the file to store on server
   * @param file: local fullpath of file
   */
  function upload_file($dir, $dst_name, $file, $visual_name = "") {
    // if (!isset($visual_name)) {  $visual_name = $dst_name;}
    $path = $this->basepath . $dir;
    if (!is_dir($path)) {
      mkdir($path, 0777, true);
    }
    move_uploaded_file($file, $path . $dst_name);
  }
  function upload_thumb($dir, $dst_name, $file, $visual_name = "") {
    // if (!isset($visual_name)) {  $visual_name = $dst_name;}
    $path = $this->basepath . $dir;
    if (!is_dir($path)) {
      mkdir($path, 0777, true);
    }
    rename($file, $path . $dst_name);
  }
  static function init_folder() {
    $upload_dir = wp_upload_dir();
    $upfolder = $upload_dir['basedir'] . '/' . PWG_LOCAL_FOLDER;
    if (!is_dir($upfolder)) {
      mkdir($upfolder);
    }
  }
}

?>