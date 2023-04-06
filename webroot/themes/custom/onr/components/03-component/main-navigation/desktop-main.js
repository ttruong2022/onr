(function ($, Drupal) {
  'use strict';

  document.addEventListener("DOMContentLoaded", function () {
    Array.from(document.getElementsByClassName('usa-megamenu'))
        .forEach(m => {

          console.log('Building observer');

          let mobs = new MutationObserver(function (mutations) {
            mutations.forEach((muta => {
              let thisMegamenu = m.getElementsByClassName('usa-megamenu-inner')[0];

              if (muta.attributeName === 'hidden' && window.innerWidth >= 1024) {
                setTallestSubmenu(thisMegamenu);
                setMegamenuHeight(thisMegamenu);
              }
              else {
                clearHeight();
              }
            }));
          });

          mobs.observe(m, {"attributes": true});
        });
  });

  function clearHeight() {
    Array.from(document.getElementsByClassName('usa-megamenu-inner'))
        .forEach(m => {
          m.style.height = '100%';
        });
  }

  function setTallestSubmenu(m) { // m for megamenu, the parent element
    var tallestElement = null;
    Array.from(m.querySelectorAll('div.usa-nav__submenu-group'))
        .forEach(el => {

          // set tallestElement to the taller of the two,
          // the current element or previously tallest element
          if (el.offsetHeight > (tallestElement?.offsetHeight ?? 0)) {
            tallestElement = el;
          }
        });

    // add class 'tallest' to the element determined to be the tallest
    tallestElement?.classList?.add('tallest');
  }

  function setMegamenuHeight(m) { // m for megamenu, the parent element
    // fn returns height of styled flex column, parameter must be string
    let getColHeight = (order) => Array.from(m.querySelectorAll('div.usa-nav__submenu-group'))

        // filter out elements which are not in specified column
        .filter(e => window.getComputedStyle(e).order === order)

        // add all resulting element heights together
        .reduce((prev, curr) => prev + curr.offsetHeight, 0);

    m.style.height = `${getColHeight('1')}px`;

    // Array of column heights (the tallest element is in column 1 by itself)
    [getColHeight('2'), getColHeight('3'), getColHeight('4')]

        // set submenu height to the greatest col height
        .forEach(h => m.style.height = `${h > m.offsetHeight ? h : m.offsetHeight}px`);
  }

})(null, Drupal);
