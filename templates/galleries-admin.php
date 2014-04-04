<?php  include_once(PWG_ABSPATH . 'templates/admin-header.php');  ?>
  <table ng-controller="GalleryListCtrl" class="wp-list-table widefat fixed">
    <thead>
      <tr>
        <th scope="row" class="check-column">
          <input type="checkbox" name="gid[]" value="" class="check-all">
        </th>
        <th class="gid" ng-click="sort('gid')">gid{{orderProp | sortmark:"gid":orderReverse}}</th>
        <th class="image-thumb">thumb</th>
        <th class="path" ng-click="sort('path')">path{{orderProp | sortmark:"path":orderReverse}}</th>
        <th class="title" ng-click="sort('title')">title{{orderProp | sortmark:"title":orderReverse}}</th>
        <th>description</th>
      </tr>
    </thead>
    <tfoot>
      <tr>
        <th scope="row" class="check-column">
          <input type="checkbox" name="gid[]" class="check-all">
        </th>
        <th class="gid" ng-click="sort('gid')">gid</th>
        <th class="image-thumb">thumb</th>
        <th class="path" ng-click="sort('path')">path</th>
        <th class="title" ng-click="sort('title')">title</th>
        <th>description</th>
      </tr>
    </tfoot>
    <tbody>
      <tr ng-repeat="gallery in galleries | orderBy:orderProp:orderReverse">
        <th scope="row" class="check-column">
          <input type="checkbox" name="gid[]" value="{{gallery.gid}}" ng-model="gallery.checked">
        </th>
        <td class="gid">{{gallery.gid}}</td>
        <td class="image-thumb">
          <a href="{{gallery.href}}">
            <img ng-src="{{gallery.thumb_url}}"/>
          </a>
        </td>
        <td>
          <a href="{{gallery.href}}">
            {{gallery.path}}
          </a>
        </td>
        <td class="title">
          <textarea gallery-prop ng-model="gallery.title"></textarea>
        </td>
        <td class="description"><textarea gallery-prop ng-model="gallery.description"></textarea></td>
      </tr>
    </tbody>
  </table>
<?php include_once(PWG_ABSPATH . 'templates/admin-footer.php'); ?>