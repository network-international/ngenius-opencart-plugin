function getURLVar (key) {
  let value = {};

  let query = String(document.location).split('?');

  if (query[1]) {
    let part = query[1].split('&');

    for (const element of part) {
      let data = element.split('=');

      if (data[0] && data[1]) {
        value[data[0]] = data[1];
      }
    }

    if (value[key]) {
      return value[key];
    } else {
      return '';
    }
  }
}

$(document).ready(function () {
  //Form Submit for IE Browser
  $('button[type=\'submit\']').on('click', function () {
    $('form[id*=\'form-\']').submit();
  });

  // Highlight any found errors
  $('.text-danger').each(function () {
    let element = $(this).parent().parent();

    if (element.hasClass('form-group')) {
      element.addClass('has-error');
    }
  });

  // tooltips on hover
  $('[data-toggle=\'tooltip\']').tooltip({ container: 'body', html: true });

  // Makes tooltips work on ajax generated content
  $(document).ajaxStop(function () {
    $('[data-toggle=\'tooltip\']').tooltip({ container: 'body' });
  });

  // https://github.com/opencart/opencart/issues/2595
  $.event.special.remove = {
    remove: function (o) {
      if (o.handler) {
        o.handler.apply(this, arguments);
      }
    }
  };

  // tooltip remove
  $('[data-toggle=\'tooltip\']').on('remove', function () {
    $(this).tooltip('destroy');
  });

  // Tooltip remove fixed
  $(document).on('click', '[data-toggle=\'tooltip\']', function (e) {
    $('body > .tooltip').remove();
  });

  $('#button-menu').on('click', function (e) {
    e.preventDefault();

    $('#column-left').toggleClass('active');
  });

  // Set last page opened on the menu
  $('#menu a[href]').on('click', function () {
    sessionStorage.setItem('menu', $(this).attr('href'));
  });

  if (!sessionStorage.getItem('menu')) {
    $('#menu #dashboard').addClass('active');
  } else {
    // Sets active and open to selected page in the left column menu.
    $('#menu a[href=\'' + sessionStorage.getItem('menu') + '\']').parent().addClass('active');
  }

  $('#menu a[href=\'' + sessionStorage.getItem('menu') + '\']').parents('li > a').removeClass('collapsed');

  $('#menu a[href=\'' + sessionStorage.getItem('menu') + '\']').parents('ul').addClass('in');

  $('#menu a[href=\'' + sessionStorage.getItem('menu') + '\']').parents('li').addClass('active');

  // Image Manager
  $(document).on('click', 'a[data-toggle=\'image\']', function (e) {
    let $element = $(this);
    let $popover = $element.data('bs.popover'); // element has bs popover?

    e.preventDefault();

    // destroy all image popovers
    $('a[data-toggle="image"]').popover('destroy');

    // remove flickering (do not re-add popover when clicking for removal)
    if ($popover) {
      return;
    }

    $element.popover({
      html: true,
      placement: 'right',
      trigger: 'manual',
      content: function () {
        return '<button type="button" id="button-image" class="btn btn-primary"><i class="fa fa-pencil"></i></button> <button type="button" id="button-clear" class="btn btn-danger"><i class="fa fa-trash-o"></i></button>';
      }
    });

    $element.popover('show');

    setTimeout(function () { // fix bind events on new popover when

      $('#button-image').on('click', function () {
        let $button = $(this);
        let $icon = $button.find('> i');

        $('#modal-image').remove();

        $.ajax({
          url: 'index.php?route=common/filemanager&user_token=' + getURLVar('user_token') + '&target=' + $element.parent().find('input').attr('id') + '&thumb=' + $element.attr('id'),
          dataType: 'html',
          beforeSend: function () {
            $button.prop('disabled', true);
            if ($icon.length) {
              $icon.attr('class', 'fa fa-circle-o-notch fa-spin');
            }
          },
          complete: function () {
            $button.prop('disabled', false);

            if ($icon.length) {
              $icon.attr('class', 'fa fa-pencil');
            }
          },
          success: function (html) {
            $('body').append('<div id="modal-image" class="modal">' + html + '</div>');

            $('#modal-image').modal('show');
          }
        });

        $element.popover('destroy');
      });

      $('#button-clear').on('click', function () {
        $element.find('img').attr('src', $element.find('img').attr('data-placeholder'));

        $element.parent().find('input').val('');

        $element.popover('destroy');
      });

    }, 250); // end timeout fix

  });
});

// Autocomplete */
(function ($) {
  $.fn.autocomplete = function (option) {
    return this.each(function () {
      let $this = $(this);
      let $dropdown = $('<ul class="dropdown-menu" />');

      this.timer = null;
      this.items = [];

      $.extend(this, option);

      $this.attr('autocomplete', 'off');

      // Focus
      $this.on('focus', function () {
        this.request();
      });

      // Blur
      $this.on('blur', function () {
        setTimeout(function (object) {
          object.hide();
        }, 200, this);
      });

      // Keydown
      $this.on('keydown', function (event) {
        if (event.keyCode === 27 || event.keyCode === '27') {
          this.hide();
        } else {
          this.request();
        }
      });

      // Click
      this.click = function (event) {
        event.preventDefault();

        let value = $(event.target).parent().attr('data-value');

        if (value && this.items[value]) {
          this.select(this.items[value]);
        }
      };

      // Show
      this.show = function () {
        let pos = $this.position();

        $dropdown.css({
          top: pos.top + $this.outerHeight(),
          left: pos.left
        });

        $dropdown.show();
      };

      // Hide
      this.hide = function () {
        $dropdown.hide();
      };

      // Request
      this.request = function () {
        clearTimeout(this.timer);

        this.timer = setTimeout(function (object) {
          object.source($(object).val(), $.proxy(object.response, object));
        }, 200, this);
      };

      // Response
      this.response = function (json) {
        let html = '';
        let category = {};
        let name;
        let j = 0;

        if (json.length) {
          for (const element of json) {
            // update element items
            this.items[element['value']] = element;

            if (!element['category']) {
              // ungrouped items
              html += '<li data-value="' + element['value'] + '"><a href="#">' + element['label'] + '</a></li>';
            } else {
              // grouped items
              name = element['category'];
              if (!category[name]) {
                category[name] = [];
              }

              category[name].push(element);
            }
          }

          for (name in category) {
            html += '<li class="dropdown-header">' + name + '</li>';

            for (j = 0; j < category[name].length; j++) {
              html += '<li data-value="' + category[name][j]['value'] + '"><a href="#">&nbsp;&nbsp;&nbsp;' + category[name][j]['label'] + '</a></li>';
            }
          }
        }

        if (html) {
          this.show();
        } else {
          this.hide();
        }

        $dropdown.html(html);
      };

      $dropdown.on('click', '> li > a', $.proxy(this.click, this));
      $this.after($dropdown);
    });
  };
})(window.jQuery);
