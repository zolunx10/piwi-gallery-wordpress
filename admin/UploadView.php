<?php 
class PWG_UploadView {
  function index($data = null) {
?>
  <div class="mod-upload">
    <div action="" method="POST" ng-controller="UploadCtrl" class="clearfix">
      <gallery-select galleries="galleries" class="mod-gallery">
      </gallery-select>
      <input ng-file-select type="file" multiple />
      <h3>Total number: {{ uploader.queue.length }}</h3>
      <ul class="item-list">
        <li class="clearfix" ng-repeat="item in uploader.queue">
          <span class="name">Name: {{ item.file.name }}</span>
          <span class="size">{{ item.file.size/1024/1024|number:2 }} Mb</span>
          <span>
            <button ng-click="item.cancel()" ng-disabled="!item.isUploading">Cancel</button>
            <button ng-click="item.remove()">×</button>
          </span>
          <div class="item-progress-box progress-box" ng-show="uploader.isHTML5">
            Progress: {{ item.progress }} %
            <div class="item-progress" ng-style="{ 'width': item.progress + '%' }"></div>
          </div>
        </li>
      </ul>
      <div>
        <div class="total-progress-box progress-box">
        Total progress: {{ uploader.progress }} %
          <div class="total-progress" ng-style="{ 'width': uploader.progress + '%' }"></div>
        </div>
        <button ng-click="$emit('uploadAll')" ng-disabled="!uploader.getNotUploadedItems().length">Upload all</button>
        <button ng-click="uploader.cancelAll()" ng-disabled="!uploader.isUploading">Cancel all</button>
        <button ng-click="uploader.clearQueue()" ng-disabled="!uploader.queue.length">Remove all</button>
      </div>
    </div>
  </div>
<?php
  }
}
?>