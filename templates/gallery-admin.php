<?php
/****
  here use the raw pwa array, not the nggallery adjusted ones
*/
  include_once(PWG_ABSPATH . 'templates/admin-header.php');
?>
  <table  id="gallery-<?php echo $gallery['gid'] ?>" ng-controller="GalleryCtrl" class="wp-list-table widefat fixed">
    <thead>
      <tr>
        <th scope="row" class="check-column">
          <input type="checkbox" name="iid[]" value="" class="check-all">
        </th>
        <th class="iid" ng-click="sort('iid')">iid{{orderProp | sortmark:"iid":orderReverse}}</th>
        <th class="set-thumb">-</th>
        <th class="image-thumb">image</th>
        <th class="title" ng-click="sort('title')">title{{orderProp | sortmark:"title":orderReverse}}</th>
        <th>description</th>
      </tr>
    </thead>
    <tfoot>
      <tr>
        <th scope="row" class="check-column">
          <input type="checkbox" name="iid[]" value="" class="check-all">
        </th>
        <th class="iid" ng-click="sort('iid')">iid</th>
        <th class="set-thumb">-</th>
        <th class="image-thumb">image</th>
        <th class="title" ng-click="sort('title')">title</th>
        <th>description</th>
      </tr>
    </tfoot>
    <tbody>
      <tr ng-repeat="(idx,image) in images | orderBy:orderProp:orderReverse">
        <th scope="row" class="check-column">
          <input type="checkbox" name="iid[]" value="{{image.iid}}" ng-model="image.checked">
        </th>
        <td class="iid">{{image.iid}}</td>
        <td ng-switch="image.iid == gallery.thumb_id">
          <input type="button" class="button" ng-switch-when="false" value="设为封面" ng-click="setThumb(image.iid)"/>
          <span ng-switch-when="true"/>已设为封面</span>
        </td>
        <td class="image-thumb">
          <a href="{{image.url}}" target="show_image">
            <img ng-src="{{image.url}}"/>
          </a>
        </td>
        <td class="title">
          <textarea image-prop ng-model="image.title" disabled="true"></textarea>
        </td>
        <td class="description"><textarea image-prop ng-model="image.description"></textarea></td>
      </tr>
    </tbody>
  </table>
<?php include_once(PWG_ABSPATH . 'templates/admin-footer.php'); ?>