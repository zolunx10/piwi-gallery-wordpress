<?php 
/**
Template Page for the gallery overview

Follow variables are useable :

  $gallery     : Contain all about the gallery
  $images      : Contain all images, path, title
  $pagination  : Contain the pagination content

 You can check the content when you insert the tag <?php var_dump($variable) ?>
 If you would like to show the timestamp of the image ,you can use <?php echo $exif['created_timestamp'] ?>
**/
?>
<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?><?php if (!empty ($gallery)) : ?>

<div class="ngg-galleryoverview clearfix" id="<?php echo $gallery->anchor ?>">  
  <!-- Thumbnails -->
  <?php $i = 0;
  foreach ( $images as $image ) : ?>
  
    <a id="ngg-image-<?php echo $image->pid ?>" class="ngg-gallery-thumbnail" href="<?php echo $image->imageURL ?>" title="<?php echo $image->title ?>" rel="<?php echo $gallery->anchor ?>" <?php echo $image->thumbcode ?> >
      <?php if ( !$image->hidden ) { ?>
      <img title="<?php echo $image->alttext ?>" alt="<?php echo $image->alttext ?>" src="<?php echo $image->thumbnailURL ?>" <?php echo $image->size ?> />
      <?php } ?>
    </a>
  
    <?php if ( $image->hidden ) continue;
    if ( $gallery->columns > 0 && ++$i % $gallery->columns == 0 ) {
      echo '<br style="clear: both" />';
    } ?>

  <?php endforeach; ?>
  
  <!-- Pagination -->
  <?php echo $pagination ?>
  
</div>

<?php endif; ?>