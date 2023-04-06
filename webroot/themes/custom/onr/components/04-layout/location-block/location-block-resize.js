(function ($, Drupal) {
  'use strict';

  $(document).ready(function () {
    mosaic();

    window.onresize = function () {
      mosaic();
    };

    function mosaic() {
      if (window.innerWidth <= 480) {
        const container = document.getElementsByClassName('location-block');
        Array.from(container).forEach((child, j) => {
          container[j].style.height = 'auto';
        });
      }
      else if (window.innerWidth > 480) {
        let numCols = 3;
        if (window.innerWidth > 480 && window.innerWidth < 880) {
          numCols = 2;
        }
        else {
          numCols = 3;
        }
        const colHeights = Array(numCols).fill(0);
        const container = document.getElementsByClassName('location-block');
        if (container) {
          Array.from(container).forEach((child, j) => {
            // eslint-disable-next-line max-nested-callbacks
            Array.from(container[j].children).forEach((child, i) => {
              if (jQuery(child).hasClass('views-row')) {
                const order = i % numCols;
                child.style.height = child.children[0].clientHeight + 'px';
                colHeights[order] += parseFloat(child.clientHeight);
              }
            });
            container[j].style.height = (Math.max(...colHeights)) + 'px';
          });
        }
      }
    }
  });
})(jQuery, Drupal);