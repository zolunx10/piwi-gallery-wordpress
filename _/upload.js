'use strict';

angular.module('PWG_GalleryAdmin')    
  /**
  * The ng-thumb directive
  * @author: nerv
  * @version: 0.1.2, 2014-01-09
  */
  .directive('ngThumb', ['$window', function($window) {
      var helper = {
          support: !!($window.FileReader && $window.CanvasRenderingContext2D),
          isFile: function(item) {
              return angular.isObject(item) && item instanceof $window.File;
          },
          isImage: function(file) {
              var type =  '|' + file.type.slice(file.type.lastIndexOf('/') + 1) + '|';
              return '|jpg|png|jpeg|bmp|gif|'.indexOf(type) !== -1;
          }
      };

      return {
          restrict: 'A',
          template: '<canvas/>',
          link: function(scope, element, attributes) {
              if (!helper.support) return;

              var params = scope.$eval(attributes.ngThumb);

              if (!helper.isFile(params.file)) return;
              if (!helper.isImage(params.file)) return;

              var canvas = element.find('canvas');
              var reader = new FileReader();

              reader.onload = onLoadFile;
              reader.readAsDataURL(params.file);

              function onLoadFile(event) {
                  var img = new Image();
                  img.onload = onLoadImage;
                  img.src = event.target.result;
              }

              function onLoadImage() {
                  var width = params.width || this.width / this.height * params.height;
                  var height = params.height || this.height / this.width * params.width;
                  canvas.attr({ width: width, height: height });
                  canvas[0].getContext('2d').drawImage(this, 0, 0, width, height);
              }
          }
      };
  }])
  .factory('imageUploader', function($http, $fileUploader) {
    (function() {
      function ImageUploader(options) {
        this.uploader = $fileUploader.create(options);
      }
      ImageUploader.prototype = {

      }
      var self = function(options) {
        new ImageUploader(options);
      }
      return self;
    })();
  })
  .directive('gallerySelect', function() {
    return {
      restrict: 'E',
      replace: true,
      template: '<div>Choose a gallery: <select name="gallery" id="" ng-disabled="uploader.queue.length"></select> <label>Name: <input type="text" name="newgallery" placeholder="Enter new gallery name" ng-model="formData.upname" ng-disabled="uploader.queue.length" required/></label></div>',
      compile: function(tElement, tAttrs, transclude) {
        tElement.addClass(tAttrs['class']);
        // toggle the visibility of new gallery name
        var $newbox = tElement.find('label');
        return {
          // render <option> list
          post: function(scope, element, attrs, controller) {
            element.find('select').off().on('change', function(e) {
              if (this.value == '_new') {
                $newbox.removeClass('ng-hide');
              } else {
                $newbox.addClass('ng-hide');
              }
              scope.formData.gid = this.value;
            });
            var $list = element.find('select');
            scope.$watch(attrs.galleries, function(newVal) {
              $list.empty().append(angular.element('<option value="_new">Create a New Gallery</option>'));
              angular.forEach(scope[attrs.galleries], function(v) {
                $list.append(angular.element('<option value="'+ v.gid + '"></option>').text(v.title));
                if (scope.gallery) {
                  // in the single gallery page
                  if (scope.gallery.gid == v.gid) {
                    $list.val(v.gid);
                    $list.triggerHandler('change');
                  }
                }
              });
            });
          }
        }
      }
    }
  })
  .controller('UploadCtrl', function($scope, $rootScope, $fileUploader, format) {
    var formData0 = $scope.formData = {
      gid: "_new",
      upname: ""
    };
    var uploader = $rootScope.uploader = $fileUploader.create({
      scope: $scope,
      url: wpData.ajaxUrl + '?action=pwg_upload',
      formData: [$scope.formData]
    });
    $scope.$on('uploadAll', function() {
      // valid new gallery
      if (formData0.gid == '_new') {
        if (!formData0.upname) {
          return false;
        }
      }
      uploader.uploadAll();
    });
    // would be triggered each file
    uploader.bind('complete', function(err, xhr, item, response) {
      if (response.errNo) {
        return;
      }
      if ('gallery' in $scope) {
        // in images page, update list
        if ($scope.gallery.gid == formData0.gid) {
          $scope.images.push(response.newimg);
        }
      } else {
        // in galleries page
        if ('newgallery' in response) {
          $scope.galleries.push(format.gallery(response.newgallery));
        }
      }
    });
    uploader.bind('completeall', function() {
      $scope.$apply();
    });
    var controller = $scope.controller = {
      isImage: function(item) {
        var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
        return '|jpg|png|jpeg|bmp|gif|'.indexOf(type) !== -1;
      }
    };
  });