'use strict';

/****
  filter that adding visual style to sorted header
*/
var mod = angular.module('PWG_GalleryAdmin', ['PWG_Common', 'angularFileUpload']);
mod.factory('galleryService', function($http, $rootScope) {
    return {
      update: function(gid, fields, callback) {
        fields.gid = gid;
        var t = $http.put(wpData.ajaxUrl+"?action=pwg_admin", fields);
        if (typeof(callback) === 'function') {
          t.success(callback);
        }
      },
      del: function(gid, callback) {
        var t = $http.delete(wpData.ajaxUrl+"?action=pwg_admin&gid[]=" + (gid instanceof Array ? gid.join(',') : gid)).error(function() {
          $rootScope.galleries = $rootScope.oldGalleries;
          $rootScope.$apply();
        });
        if (typeof(callback) === 'function') {
          t.success(callback);
          $rootScope.$apply();
        }
      }
    }
  })
  /**
   * enable lazy-loading controllers
  .config(['$controllerProvider', function($controllerProvider) {
    mod.controller = $controllerProvider.register;
  }]) 
   */
  .factory('imageService', function($http) {
    return {
      update: function(iid, fields, callback) {

      },
      del: function(iid, callback) {
        var t = $http.delete(wpData.ajaxUrl+"?action=pwg_admin&iid[]=" + (iid instanceof Array ? iid.join(',') : iid)).error(function() {
          scope.images = scope.oldimages;
          scope.$apply();
        });
        if (typeof(callback) === 'function') {
          t.success(callback);
        }
      }
    }
  })
  .factory('format', function() {
    var matched = location.search.match(/[?&](page=[^&]+)/);
    var baseUrl = location.href;
    if (matched) {
      baseUrl = location.pathname + "?" + matched[1];
    }
    return {
      gallery: function(gallery) {
        if (!gallery) {
          return null;
        }
        if (gallery instanceof Array) {
          for (var i=0,ii=gallery.length; i<ii; i++) {
            gallery[i].href = baseUrl + "&gid=" + gallery[i].gid;
            gallery[i].checked = false;
          }
        } else {        
          gallery.href = baseUrl + "&gid=" + gallery.gid;
          gallery.checked = false;
        }
        return gallery;
      },
      image: function(image) {
        if (!image) {
          return null;
        }
        if (image instanceof Array) {
          for (var i=0,ii=image.length; i<ii; i++) {
            image[i].checked = false;
          }
        } else {
          image.checked = false;
        }
        return image;
      }
    }
  })
  .directive('galleryProp', function(galleryService, notifyService) {
    return {
      restrict: 'AC',
      require: '?ngModel',
      link: function(scope, element, attr, ngModel) {
        if (!ngModel) 
          return;
        
        ngModel.$render = function() {
          element.html(ngModel.$viewValue || "");  // seems the visual value on page would be auto-updated, but element.html() wouldn't change
        }
        element.on('change', function($event) {
          /**
           * @this: the html dom of element
           * @element: a wrapper of [this]
           * @scope: contains .gallery
           * @ngModel: the changed field (distinguish with .gallery)
           * instead of scope.$watch, use on-change for efficiency
           */
          // scope.$apply(update);
          var t = attr.ngModel.lastIndexOf(".");
          var propName = t < 0 ? attr.ngModel : attr.ngModel.substring(t+1);
          var fields = {};
          fields[propName] = element.val();

          notifyService.addMessage("updating gallery fileds...");
          galleryService.update(scope.gallery.gid, fields, function(data, status, headers, config) {
            notifyService.addMessage("gallery info updated.")
          });  // ngModel.$modelValue
        });
      }
    };
  })
  .directive('adminPanel', ['tboxAdjuster', function(tboxAdjuster) {
    return {
      restrict: 'AC',
      link: function(scope, element) {
        element.find('#upload').on('click', function() {
          // tb_show('upload', wpData.ajaxUrl + '?action=pwg_upload');

          tboxAdjuster.show({ 
            templateUrl: wpData.ajaxUrl + '?action=pwg_upload'
          });
        });
        element.find('#delete').on('click', function(e) {
          scope.$emit('delete');
        });
        $("input.check-all").on('change', function(e) {
          scope.$emit('checkall', this.checked);
        });
      }
    }
  }])
  .filter('sortmark', function() {
    return function(orderProp, prop, orderReverse) {
      return (prop == orderProp) ? (orderReverse ? "▼" : "▲") : "";
    }
  })
  .controller('GalleryListCtrl', function($scope, $rootScope, format, galleryService, notifyService) {
    $rootScope.galleries = format.gallery(window.galleries);

    /****
      sort according to specified prop
    */
    $scope.orderProp = 'gid';
    $scope.orderReverse = false;
    $scope.sort = function(prop) {
      if (prop == this.orderProp) {
        this.orderReverse = !this.orderReverse;
      } else {
        this.orderProp = prop;
        this.orderReverse = false;
      }
    }
    $rootScope.$on('delete', function(e) {
      var checkedGid = [];
      var galleries = $rootScope.galleries;
      $rootScope.oldGalleries = angular.copy($rootScope.galleries);
      for (var i=galleries.length-1; i>=0; i--) {
        if (galleries[i].checked) {
          checkedGid.push(galleries[i].gid);
          galleries.splice(i, 1);
        }
      }
      if (checkedGid.length <= 0) {
        return;
      }
      galleryService.del(checkedGid, function(data) {
        notifyService.addMessage("Galleries deleted", "info");
      })
    });
    $rootScope.$on('checkall', function(e, checked) {
      for (var i=galleries.length-1; i>=0; i--) {
        $rootScope.galleries[i].checked = checked;
      }
    });
  })
  .controller('GalleryCtrl', function($scope, $rootScope, galleryService, imageService, format, notifyService) {
    $rootScope.gallery = format.gallery(window.gallery);
    $rootScope.galleries = window.galleries;
    $rootScope.images = format.image(window.images);
    $scope.orderProp = 'gid';
    $scope.orderReverse = false;

    $scope.sort = function(prop) {
      if (prop == this.orderProp) {
        this.orderReverse = !this.orderReverse;
      } else {
        this.orderProp = prop;
        this.orderReverse = false;
      }
    }
    $scope.setThumb = function(iid) {
      notifyService.addMessage("updating gallery thumb...");
      galleryService.update($scope.gallery.gid, {'thumb_id': iid}, function(data, status, headers, config) {
              notifyService.addMessage("gallery thumb updated.", 'info');
      });
      $scope.gallery.thumb_id = iid;
    }
    $rootScope.$on('delete', function(e) {
      var checkedID = [];
      var images = $rootScope.images;
      $rootScope.oldImages = angular.copy($rootScope.images);
      for (var i=images.length-1; i>=0; i--) {
        if (images[i].checked) {
          checkedID.push(images[i].iid);
          images.splice(i, 1);
        }
      }
      if (checkedID.length <= 0) {
        return;
      }
      imageService.del(checkedID, function(data) {
        notifyService.addMessage("Images deleted", "info");
      })
    });
    $rootScope.$on('checkall', function(e, checked) {
      for (var i=images.length-1; i>=0; i--) {
        $rootScope.images[i].checked = checked;
      }
    });
  });