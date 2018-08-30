/*
 * Block Double Logins
 * (c) Web factory Ltd, 2014
 * www.webfactoryltd.com
 */

jQuery(document).ready(function($){
  $('#wf-bdl-tabs').tabs({
    active: bdl_active_tab(),
    activate: function() { $.cookie('wf_bdl_tabs', $(this).tabs('option', 'active'), { expires: 30 }); },
    beforeActivate: function(event, ui) {
      var old_tab = ui.oldTab;
      var new_tab = ui.newTab;

      $(old_tab).removeClass('nav-tab-active');
      $(new_tab).addClass('nav-tab-active');
    },
    create: function(event, ui) {
      $('#wf-bdl-tabs .ui-state-active').addClass('nav-tab-active');
    }
  });

  $(document).ajaxError(function() {
    alert('An undocumented error has occured. Please reload the page and try again.');
  });

  $('#bdl_clear_usernames').on('click', function() {
    if (confirm('Are you sure you want to reset all username based locks? There is no undo!')) {
      $.post(ajaxurl, {action: 'bdl_clear_usernames'}, function(response) {
        if (response) {
          alert('All username based locks have been reset!');
        } else {
          alert('Undocumented error. Please reload the page and try again.');
        }
      });
    }

    return false;
  });

  $('#bdl_view_ips').on('click', function() {
    $.post(ajaxurl, {action: 'bdl_view_ips'}, function(response) {
      $("#bdl_dialog #dialog_content").html(response);
      $("#bdl_dialog").dialog({title: 'Active IP based locks', 'dialogClass': 'wp-dialog', modal: 1, width: '450', height: '400'});
    }, 'json');

    return false;
  });

  $('#bdl_view_log').on('click', function() {
    $.post(ajaxurl, {action: 'bdl_view_log'}, function(response) {
      $("#bdl_dialog #dialog_content").html(response);
      $("#bdl_dialog").dialog({title: 'Block Double Logins Log', 'dialogClass': 'wp-dialog', modal: 1, width: '550', height: '400'});
    }, 'json');

    return false;
  });

  $('#bdl_view_usernames').on('click', function() {
    $.post(ajaxurl, {action: 'bdl_view_usernames'}, function(response) {
      $("#bdl_dialog #dialog_content").html(response);
      $("#bdl_dialog").dialog({title: 'Active username based locks', 'dialogClass': 'wp-dialog', modal: 1, width: '450', height: '400'});
    }, 'json');

    return false;
  });

  $('#bdl_clear_ips').on('click', function() {
    if (confirm('Are you sure you want to reset all IP based locks? There is no undo!')) {
      $.post(ajaxurl, {action: 'bdl_clear_ips'}, function(response) {
        if (response) {
          alert('All IP based locks have been reset!');
        } else {
          alert('Undocumented error. Please reload the page and try again.');
        }
      });
    }

    return false;
  });

  $('#bdl_send_override_code').on('click', function() {
    $.post(ajaxurl, {action: 'bdl_send_override_code'}, function(response) {
      if (response != '0') {
        alert('Email sent to ' + response);
      } else {
        alert('Unable to send email. Please check your wp_mail() function.');
      }
    });

    return false;
  });
}); // on load

  function bdl_active_tab() {
    return parseInt(0 + jQuery.cookie('wf_bdl_tabs'), 10);
  }

// -------------------------

/*!
 * jQuery Cookie Plugin v1.3.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
(function (factory) {
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as anonymous module.
    define(['jquery'], factory);
  } else {
    // Browser globals.
    factory(jQuery);
  }
}(function ($) {

  var pluses = /\+/g;

  function raw(s) {
    return s;
  }

  function decoded(s) {
    return decodeURIComponent(s.replace(pluses, ' '));
  }

  function converted(s) {
    if (s.indexOf('"') === 0) {
      // This is a quoted cookie as according to RFC2068, unescape
      s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
    }
    try {
      return config.json ? JSON.parse(s) : s;
    } catch(er) {}
  }

  var config = $.cookie = function (key, value, options) {

    // write
    if (value !== undefined) {
      options = $.extend({}, config.defaults, options);

      if (typeof options.expires === 'number') {
        var days = options.expires, t = options.expires = new Date();
        t.setDate(t.getDate() + days);
      }

      value = config.json ? JSON.stringify(value) : String(value);

      return (document.cookie = [
        config.raw ? key : encodeURIComponent(key),
        '=',
        config.raw ? value : encodeURIComponent(value),
        options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
        options.path    ? '; path=' + options.path : '',
        options.domain  ? '; domain=' + options.domain : '',
        options.secure  ? '; secure' : ''
      ].join(''));
    }

    // read
    var decode = config.raw ? raw : decoded;
    var cookies = document.cookie.split('; ');
    var result = key ? undefined : {};
    for (var i = 0, l = cookies.length; i < l; i++) {
      var parts = cookies[i].split('=');
      var name = decode(parts.shift());
      var cookie = decode(parts.join('='));

      if (key && key === name) {
        result = converted(cookie);
        break;
      }

      if (!key) {
        result[name] = converted(cookie);
      }
    }

    return result;
  };

  config.defaults = {};

  $.removeCookie = function (key, options) {
    if ($.cookie(key) !== undefined) {
      // Must not alter options, thus extending a fresh object...
      $.cookie(key, '', $.extend({}, options, { expires: -1 }));
      return true;
    }
    return false;
  };
}));