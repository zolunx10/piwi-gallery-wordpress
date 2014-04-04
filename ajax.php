<?php 

if(! defined('PWG_ABSPATH')) { die('You are not allowed to call this page directly.'); }

@require_once(PWG_ABSPATH . 'admin/GalleryModel.php');

/****
  @return {
    errNo:   0 means success
    message: error description when errNo!=0
    images[]: {
      imageURL:
      thumbURL:
      title:
    }
    galleries[]: {
  
    }
  }
 */

function PWG_ajax_index() {
  $_GET['ajax'] = 1;
  header('Content-Type: application/json');

  $method = array_key_exists('method', $_GET) ? $_GET['method'] : null;
  $id = array_key_exists('id', $_GET) ? $_GET['id'] : null;
  $result = null;
  if (!$method) {
    $result = array('errNo'=>1, 'message'=>"ERROR: <method> required");
  } else if (!$id) {
    $result = array('errNo'=>2, 'message'=>"ERROR: <id> required");
  } else {
    switch ($method) {
      case 'gallery':
        $gallery = new PWG_GalleryModel();
        $pwg_site = $gallery->get_baseurl();
        $imgs_raw = $gallery->get_images_by_gallery($id);
        $imgs = array();
        foreach ($imgs_raw as $v) {
          $imgs[] = array(
            'iid' => $v['id'],
            'gid' => $v['gid'],
            'title' => $v['title'],
            'imageURL' => $pwg_site . $v['path'],
            'thumbURL' => $pwg_site . PWG_GalleryModel::get_thumbpath_by_path($v['path'])
          );
        }
        $result = array('errNo'=>0, 'images'=>$imgs);
        break;
      default:
        $result = array('errNo'=>1, 'message'=>"ERROR: <method> unknown");
        break;
    }
  }
  echo json_encode($result);
  die();
}
function PWG_ajax_admin() {
  $_GET['ajax'] = 1;
  include_once(PWG_ABSPATH . 'admin/GalleryController.php');
  die();
}
function PWG_ajax_upload() {
  $_GET['ajax'] = 1;
  include_once(PWG_ABSPATH . 'admin/UploadController.php');
  die();
}
add_action('wp_ajax_nopriv_pwg_index', 'PWG_ajax_index'); // nopriv for front end
add_action('wp_ajax_pwg_index', 'PWG_ajax_index');
add_action('wp_ajax_pwg_admin', 'PWG_ajax_admin'); // PWG_ajax_index();
add_action('wp_ajax_pwg_upload', 'PWG_ajax_upload');
?>