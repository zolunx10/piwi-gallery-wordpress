<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }
require_once(PWG_ABSPATH . 'admin/GalleryView.php');
require_once(PWG_ABSPATH . 'admin/GalleryModel.php');

function PWG_gallery_index($request) {
  $model = new PWG_GalleryModel();
  $view = new PWG_GalleryView();

  wp_enqueue_script('PWG_gallery');
  wp_enqueue_script('PWG_upload');

  if (array_key_exists('gid', $request)) {
    $galleries = $model->get_galleries();
    $gallery = $model->get_galleries($request['gid']);
    $images = $model->get_images_by_gallery($request['gid']);
    $pwg_site = $model->get_baseurl();
    foreach ($images as &$v) {
      $v['url'] = $pwg_site . $v['path'];
    }
    $view->ngg_show_gallery_detail($gallery[0], $images, $galleries, 'admin');
  } else {
    $galleries = $model->get_galleries();
    $pwg_site = $model->get_baseurl();
    foreach ($galleries as &$v) {
      $v['thumb_url'] = $pwg_site . PWG_GalleryModel::get_thumbpath_by_path($v['thumb_path']);
    }
    $view->ngg_show_galleries($galleries, 'admin');
  }
}
function PWG_gallery_show($request) {

}
function PWG_gallery_add($request) {
  
}
function PWG_gallery_edit($request) {
  $model = new PWG_GalleryModel();
  header('Content-Type: application/json');
  if (!array_key_exists('gid', $request)) {
    $res = array('errNo'=>1, 'message'=>"<gid> required");
  } else {
    $gid = $request['gid'];
    $fields = array();
    // title, description, thumb_id, author are permitted
    if (array_key_exists('title', $request)) {
      $fields['title'] = $request['title'];
    }
    if (array_key_exists('description', $request)) {
      $fields['description'] = $request['description'];
    }
    if (array_key_exists('author', $request)) {
      $fields['author'] = $request['author'];
    }
    if (array_key_exists('thumb_id', $request)) {
      $fields['thumb_id'] = $request['thumb_id'];
    }
    $affected_row = $model->update_gallery($gid, $fields);
    if ($affected_row===false) {
      $res = array('errNo'=>2, 'message'=>"error in update");
    } else {
      $res = array('errNo'=>0, 'message'=>"success");
    }
  }
  global $wpdb;
  $wpdb->print_error();
  echo json_encode($res);
  die();
}
function PWG_gallery_del($request) {
  header('Content-Type: application/json');
  $model = new PWG_GalleryModel();
  if (array_key_exists('gid', $request)) {
    $model->del_gallery($request['gid']);
  }
  if (array_key_exists('iid', $request)) {
    $model->del_image($request['iid']);
  }
  echo json_encode($request);
  die();
}
// format application/json request
$request = array();
$content_type_args = explode(';', $_SERVER['CONTENT_TYPE']);
if ($content_type_args[0] == 'application/json') {
  $request = json_decode(file_get_contents('php://input'), true);
} else {
  parse_str(file_get_contents('php://input'), $request);
}
switch ($_SERVER['REQUEST_METHOD']) {
  case 'POST':
    $_POST = $request;
    print_r($request);
    die();
    break;
  case 'PUT':
    $GLOBALS['_PUT'] = $request;
    PWG_gallery_edit(array_merge($request, $_GET));
    break;
  case 'DELETE':
    $GLOBALS['_DELETE'] = $request;
    PWG_gallery_del(array_merge($request, $_GET));
    break;
  case 'GET':
  default:
    PWG_gallery_index($_GET);  
    break;
}
?>
