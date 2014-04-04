// (function(angular, $) {
'use strict';
var $ = window.jQuery;

angular.module('PWG_Common', [])
  /**
   * service to display messages on the top
   */
  .factory('notifyService', function($rootElement, $timeout) {
    var box = null;
    var cls = null;
    var hideBox = function() {
      if (cls) {
        $timeout.cancel(cls);
      }
      cls = null;
      box.addClass('ng-hide');
      box.empty();
    }
    return {
      addMessage: function(msg, type) {
        if (!box) {     // late create here to prevent internal error
          box = angular.element('<div class="ng-hide"></div>');
          box.on('click', hideBox);
          $rootElement.prepend(box);
        }
        if (cls) {
          $timeout.cancel(cls);
        }
        box.append('<p>' + msg + '</p>');
        if ('error' == type) {
          box.removeClass().addClass('error');
          cls = $timeout(hideBox, 5000);
        } else if ('info' == type) {
          box.removeClass().addClass('updated');
          cls = $timeout(hideBox, 4000);
        } else {
          box.removeClass().addClass('updated');
        }
      },
      clearMessage: hideBox
    }
  })
  .factory('tboxAdjuster', ['$http', '$templateCache', '$compile', '$rootScope', function($http, $templateCache, $compile, $rootScope) {
    var elOverlay = null, elBox = null;
    var $body = $('body');
    var self = {
      _showContent: function(rawTemplate) {
        if (!elBox) {
          elOverlay = angular.element('<div id="TB_overlay" class="TB_overlayBG"></div>')
            .on('click', function() {
              self.hide();
            });
          $body.append(elOverlay);
          elBox = angular.element('<div id="TB_window" class="ng-show" style="min-width:600px;width:60%;margin-left:-30%;top:10%;"><div id="TB_ajaxContent"></div></div>');
          $body.append(elBox);
        } else {
          elOverlay.removeClass('ng-hide');
          elBox.removeClass('ng-hide').addClass('ng-show');
        }
        elBox.children().eq(0).empty().html(rawTemplate);
        $compile(elBox.contents())(self.options.scope || $rootScope);
      },
      show: function(options) {
        self.options = options;
        options.template ? template :
              $http.get(options.templateUrl, {cache: $templateCache}).success(function (data, status) {
                self._showContent(data);
              });
      },
      hide: function() {
        if (elBox) {
          elOverlay.addClass('ng-hide');
          elBox.addClass('ng-hide');
        }
      }
    }
    return self;
  }]);
// })(window.angular, window.jQuery);