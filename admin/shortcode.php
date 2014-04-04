<?php 

if(! defined('PWG_ABSPATH')) { die('You are not allowed to call this page directly.'); }

@require_once(PWG_ABSPATH . 'admin/GalleryView.php');
@require_once(PWG_ABSPATH . 'admin/GalleryModel.php');

class PWG_NGGallery {
  public $gid, $galdesc, $pagelink, $name, $title, $previewurl;
  public $show_piclens = false,
    $anchor, $columns = 0;
  function __construct($atts) {
    $this->gid      = $atts['gid'];
    $this->galdesc  = $atts['description'];
    $this->pagelink = $atts['pagelink'];
    $this->name     = $atts['name'];
    $this->title    = $atts['title'];
    $this->previewurl = $atts['previewurl'];

    $this->anchor   = array_key_exists('anchor', $atts) ? $atts['anchor'] : 'pwg-' . $atts['gid'];
  }
}
class PWG_NGImage {
  public $pid, $title, $imageURL, $thumbnailURL, $style="", $thumbcode="", $hidden, $alttext, $size;
  function __construct($atts) {
    $this->pid      = $atts['iid'];
    $this->title    = $atts['title'];
    $this->imageURL = $atts['path'];
    $this->thumbnailURL = $atts['thumb'];

    $this->size     = "";  //TODO: get image size?
    $this->alttext  = $this->title;
    $this->hidden   = $atts['status'];
  }
}
class PWG_Shortcode {
  var $model;
  function __construct() {
    add_shortcode('piwi-galleries', array(&$this, 'show_galleries'));
    add_shortcode('piwi-gallery-detail', array(&$this, 'show_gallery_detail'));

    $this->model = new PWG_GalleryModel();
  }
  public function show_galleries($atts) {
    extract(shortcode_atts(array(
      'id'        => '',
      'template'  => ''
      ), $atts));
    $ids = explode(',', $atts['id']);
    $pwg_site = $this->model->get_baseurl();
    $rows = $this->model->get_galleries($ids);
    // adjusted to fit nextgen-gallery templates 
    // http://localhost/studio12f_on_bae/0/?callback=json&format=json&method=gallery&api_key=studio12F&id=17
    foreach ($rows as $v) {
      $galleries[] = new PWG_NGGallery(array(
        'gid'       => $v['gid'],
        'description'   => $v['description'],
        'pagelink'  => "",
        'name'      => basename($v['path']),
        'title'     => $v['title'],
        'previewurl'=> $pwg_site . PWG_GalleryModel::get_thumbpath_by_path($v['thumb_path'])
        ));
    }
    $view = new PWG_GalleryView();
    $view->ngg_show_galleries($galleries, $atts['template']);
  }
  public function show_gallery_detail($atts) {
    extract(shortcode_atts(array(
      'id'        => '',
      'template'  => ''
      ), $atts));
    $raw_imgs = $this->model->get_images_by_gallery($atts['id']);
    $pwg_site = $this->model->get_baseurl();
    $images = array();
    foreach ($raw_imgs as $v) {
      $v['path'] = $pwg_site . $v['path'];
      $v['thumb'] = PWG_GalleryModel::get_thumbpath_by_path($v['path']);
      $images[] = new PWG_NGImage($v);
    }
    $gallery = new PWG_NGGallery(array(
      'gid' => $atts['id']
      ));
    $view = new PWG_GalleryView();
    $view->ngg_show_gallery_detail($gallery, $images, null, $atts['template']);
  }
}
$pwg_shortcode = new PWG_Shortcode();
 ?>