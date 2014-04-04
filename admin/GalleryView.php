<?php 

class PWG_GalleryView {
  function __construct() {

  }
  /****
    nextgen-gallery compatible view (using templates under THEME/nggallery)
    @type: gallery | gallery
  */
  function _format_template_path($type, $template_name) {
    $theme_dir = get_template_directory(); // absolute path
    if ($template_name) {
      $template_name = '-' . $template_name;
    }
    $template_path = $theme_dir . '/piwi-gallery/' . $type . $template_name . '.php';
    if ( !file_exists($template_path)) {
      $template_path = PWG_ABSPATH . 'templates/' . $type . $template_name . '.php';
      if ( !file_exists($template_path)) {
        $template_path = PWG_ABSPATH . 'templates/'. $type . '.php';
      }
    }
    return $template_path;
  }
  function ngg_show_galleries($galleries, $template_name = "") {
    $template_path = $this->_format_template_path('galleries', $template_name);
    include_once($template_path);
  }
  function ngg_show_gallery_detail($gallery, $images, $galleries = array(), $template_name = "") {
    $template_path = $this->_format_template_path('gallery', $template_name);
    include_once($template_path);
  }
}
?>