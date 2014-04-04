<?php

class PWG_SettingsView {
  function __construct() {
    return ;
  }
  function index($data) {
    extract($data);
?>
  <div class="wrap">
    <form name="" method="POST" action="<?php echo $action_url ?>">
      <h2> <?php _e("Settings", 'bcs-gallery') ?> </h2>
      <table class="form-table">
        <tr>
          <th>
            <label for="using_bcs">using bcs: </label>
          </th>
          <td><input type="checkbox" name="using_bcs" id="using_bcs" <?php if ($options['using_bcs']) { echo 'checked="checked"'; }?> value="on"></td>
        </tr>
        <tr>
          <th>
            <label for="folder">gallery folder (please contain leading '/'): </label>
          </th>
          <td>
            <input type="text" name="folder" id="folder" value="<?php echo $options['gallery_folder'] ?>"/> 
          </td>
        </tr>
      </table>
      <div id="mod-bcs-settings">
        <h2> <?php _e("BCS Settings", 'bcs-gallery') ?> </h2>
        <table class="form-table">
             <?php
             if ( false === getenv ( 'HTTP_BAE_ENV_AK' ) || false === getenv ( 'HTTP_BAE_ENV_SK' )) {
             ?>
          <tr>
            <th><label for="ak">ak: </label>
            </th>
            <td><input type="text" name="ak" id="ak" value="<?php echo $options['ak'] ?>"/>
            </td>
          </tr>
          <tr>
            <th>
              <label for="sk">sk: </label>
            </th>
            <td>
              <input type="text" name="sk" id="sk" value="<?php echo $options['sk'] ?>"/> 
              <a href="http://developer.bgidu.com/bae/ref/key/" target="_blank"> <?php _e("What hell is ak & sk?") ?> </a>
            </td>
          </tr>
        <?php } ?>
          <tr>
            <th>
              <label for="bucket">bucket: </label>
            </th>
            <td>
              <input type="text" name="bucket" id="bucket" value="<?php echo $options['bucket'] ?>"/> 
            </td>
          </tr>
        </table>
      </div>
      <div class="submit">
        <input type="submit" name="settings_submit" class="button button-primary" value="<?php _e('submit', 'bcs-gallery') ?>"/>
      </div>
      <script type="text/javascript">
        if (!$) {
          $ = window.jQuery;
        }
        $('#using_bcs').change(function(e) {
          var t = $(this).attr('checked');
          if (t) {
            $('#mod-bcs-settings').show();
          } else {
            $('#mod-bcs-settings').hide();
          }
        }).change();
      </script>
    </form>
    <form method="POST" action="<?php echo $sync_url ?>">
      <input type="submit" name="resync_submit" class="button" value="<?php _e('Resync whole galleries with BCS', 'bcs-gallery') ?>"/>
    </form>
  </div>
<?php
  }
}
?>