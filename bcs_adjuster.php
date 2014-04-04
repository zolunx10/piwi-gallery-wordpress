<?php
define('BCS_HOST', 'bcs.duapp.com');
require_once(PWG_ABSPATH . 'bcs.class.php');
/**
 * a wrapper class to BCS api
 */
class BCSAdjuster {
  var $bcs;
  var $baseurl;
  var $last_info; 
  var $bucket;
  function __construct($ak, $sk, $bucket) {
    $this->bcs = new BgiduBCS($ak, $sk);
    $this->bucket = $bucket;
    $options = get_option('pwg_options');
    $this->baseurl = 'http://' . BCS_HOST . '/' . $options['bucket'];
  }
  function list_object_by_dir($dir = '/', $list_model = 2, $opt = null) {
    $bcs_res = $this->bcs->list_object_by_dir($this->bucket, $dir, $list_model);
    if (!$bcs_res->isOk()) {
      return false;
    } else {
      $res = json_decode($bcs_res->body, true);
      return $res['object_list'];
    }
  }
  function delete_object($object, $opt = NULL) {
    $bcs_res = $this->bcs->delete_object($this->bucket, $object, $opt);
    return $bcs_res->isOk();
  }
  /**
   * @param dir: would contain leading & trailing '/'
   * @param dst_name: name of the file to store on server
   * @param file: local fullpath of file
   */
  function upload_file($dir, $dst_name, $file, $visual_name = "") {
    $path = $this->basepath . $dir;
    $opts = array();
    if (!empty($visual_name)) {
      $opts['filename'] = $visual_name;
    }
    $bcs_res = $this->bcs->create_object($this->bucket, $path . $dst_name, $file, $opts);
    return $bcs_res->isOk();
  }
  function upload_thumb($dir, $dst_name, $file, $visual_name = "") {
    return $this->upload_file($dir, $dst_name, $file, $visual_name);
  }
}

?>