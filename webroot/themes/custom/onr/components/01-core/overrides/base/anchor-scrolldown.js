/* eslint no-var: "error"*/
/* eslint-env es6*/

(function ($, Drupal) {
  'use strict';

  $(document).ready(function() {

    $('main a[href^="#"]').click(function(e) {

      if ($(this).attr('role') !== 'tab' && $(this).attr('role') !== 'undefined') {
        e.preventDefault();

        if ($($(this).attr('href')).length > 0) {
          $($(this).attr('href')).get(0).scrollIntoView({
            'behavior': 'smooth'
          });
        }
      }

    });
  });
})(jQuery, Drupal);
