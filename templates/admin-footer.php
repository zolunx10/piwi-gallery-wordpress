</form>
</div>
<!--[if lte IE 8]>
  <script src="<?php echo PWG_URLPATH . 'js/json2.js' ?>"></script>
<![endif]-->

<script id="images-data" type="text/javascript">
  <?php if (isset($galleries)) { ?>
    window.galleries = JSON.parse('<?php echo json_encode($galleries) ?>');
  <?php } 
  if (isset($images)) { ?>
    window.images = JSON.parse('<?php echo json_encode($images) ?>');
    window.gallery = JSON.parse('<?php echo json_encode($gallery) ?>');
  <?php } ?>
</script>