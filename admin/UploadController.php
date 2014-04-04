<?php 
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }
require_once(PWG_ABSPATH . 'admin/UploadView.php');
require_once(PWG_ABSPATH . 'admin/GalleryModel.php');

function _ajax_die($msg) {
  echo json_encode($msg);
  die();  
}
/**
 * create a thumbnail of fitting 'small' size of wp setting
 * TODO: also limit the size of origin image to 2*'large' size
 */
function _create_thumb($img_path) {
  $img = wp_get_image_editor($img_path);
  if ( is_wp_error( $img ) ) {
    return false;
  }
  $sizes = get_intermediate_image_sizes();

  $thumb_size = array(intval(get_option('thumbnail_size_w')), intval(get_option('thumbnail_size_h')));
  $max_size = array(2 * get_option('large_size_w'), 2 * get_option('large_size_h'));
  $t = $img->get_size();
  $current_size = array($t['width'], $t['height']);
  // resize too large image
  if ($current_size[0] > $max_size[0] || $current_size[1] > $max_size[1]) {
    $img->resize($max_size[0], $max_size[1]);
    $new_path = $img_path . '.png';   //.save() would automatically generate new extension for file, so mv is needed
    $img->save($new_path);
    rename($new_path, $img_path);
  }
  $eold = error_reporting(E_ERROR | E_PARSE);
  $thumb_path = tempnam(sys_get_temp_dir(), 'PWG'); 
  $img->resize($thumb_size[0], $thumb_size[1]);
  $res = $img->save($thumb_path);
  $e = error_reporting($eold);
  return $res['path'];
}
function PWG_upload_index($req) {
  $view = new PWG_UploadView();
  wp_enqueue_script('PWG_upload');
  $view->index();
}
function PWG_upload_post($req) {
  header('Content-Type: application/json');
  if(!current_user_can('upload_files')) {
    _ajax_die(array('errNo'=>401, 'msg'=>__('You do not have sufficient permissions to access this page.'))); 
    return;
  }
  if (empty($_FILES)) {
    _ajax_die(array('errNo'=>1, 'msg'=>__("No file.")));
    return;
  }
  // check file type
  if (!preg_match('/^image/', $_FILES['file']['type'])) {
    _ajax_die(array('errNo'=>2, 'msg'=>__("Only images are permitted.")));
  }
  $temp_path = $_FILES['file']['tmp_name'];
  $filename = sanitize_file_name($_FILES['file']['name']);
  // if ($filename{0} == '.') return 0;  // skip ".xxx" files on MAC
  $model = new PWG_GalleryModel();
  if ($req['gid'] == "_new") {
    $gallery = $model->add_gallery(sanitize_file_name($req['upname']), $req['upname']);
    $gid = $gallery['gid'];
  } else {
    $gid = intval($req['gid']);
  }
  $thumb_path = _create_thumb($temp_path);
  $info = mb_pathinfo($_FILES['file']['name']);
  print_r($info);
  $row = $model->add_image($gid, $filename, $temp_path, $thumb_path, $info['filename']);
  if ($row === false) {
    _ajax_die(array('errNo'=>500, 'msg'=>__("DB error.")));
  }
  $row['url'] = $model->get_baseurl() . $row['path'];
  $res = array('errNo'=>0, 'newimg'=>$row);
  if (isset($gallery) && array_key_exists('new', $gallery)) {
    $res['newgallery'] = $gallery;
  }
  echo json_encode($res);
  die();
}
// format application/json request
switch ($_SERVER['REQUEST_METHOD']) {
  case 'POST':
    PWG_upload_post($_REQUEST);
    break;
  case 'GET':
  default:
    PWG_upload_index($_GET);  
    break;
}
 ?>