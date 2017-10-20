jQuery(document).ready(function($) {

    // Minimal custom table theme with inverted (white) font-awesome column sorting icons
    $.tablesorter.themes = {
        "minimal" : {
          table      : '',
          caption    : '',
          header     : '',
          footerRow  : '',
          footerCells: '',
          icons      : 'fa fa-inverse',
          sortNone   : '',
          sortAsc    : 'fa-sort-down tablesorter-icon-asc',
          sortDesc   : 'fa-sort-up tablesorter-icon-desc',
          active     : '',
          hover      : '',
          filterRow  : '',
          even       : '',
          odd        : ''
        }
    };

    $.extend($.tablesorter.defaults, {
        theme: 'minimal',
        widgets: ['uitheme'],
        headerTemplate: '{content} {icon}'
    });

    // AJAX spin.js options
    // var spinOpts = {
    //     lines: 13, // The number of lines to draw
    //     length: 20, // The length of each line
    //     width: 10, // The line thickness
    //     radius: 30, // The radius of the inner circle
    //     corners: 1, // Corner roundness (0..1)
    //     rotate: 0, // The rotation offset
    //     direction: 1, // 1: clockwise, -1: counterclockwise
    //     color: '#000', // #rgb or #rrggbb or array of colors
    //     speed: 1, // Rounds per second
    //     trail: 60, // Afterglow percentage
    //     shadow: false, // Whether to render a shadow
    //     hwaccel: false, // Whether to use hardware acceleration
    //     className: 'spinner', // The CSS class to assign to the spinner
    //     zIndex: 2e9, // The z-index (defaults to 2000000000)
    //     top: '50%', // Top position relative to parent
    //     left: '50%' // Left position relative to parent
    // };

    // Add an image size validator to the jQuery Validation plugin
    $.validator.addMethod('maxImageSize', function(value, element, param) {
        if (element.files && element.files[0]) {
            return element.files[0].size <= param;
        } else {
            return true;
        }
    }, 'The image is too large.');

    /*
     * Thanks to http://stackoverflow.com/users/329624/adam-jimenez for this awesome function!
     *
     * Removed 'select' from serialization because it was causing problems for 'admin' users creating applications.
     *
     * Original: var rselectTextarea = /^(?:select|textarea)/i;
     * Current:  var rselectTextarea = /^(textarea)/i;
     */
    $.fn.serializeAll = function() {
        var rselectTextarea = /^(?:textarea)/i;
        var rinput = /^(?:color|date|datetime|datetime-local|email|file|hidden|month|number|password|range|search|tel|text|time|url|week)$/i;
        var rCRLF = /\r?\n/g;

        var arr = this.map(function(){
            return this.elements ? jQuery.makeArray( this.elements ) : this;
        })
        .filter(function(){
            return this.name && !this.disabled &&
                ( this.checked || rselectTextarea.test( this.nodeName ) ||
                    rinput.test( this.type ) );
        })
        .map(function( i, elem ){
            var val = jQuery( this ).val();

            return val == null ?
                null :
                jQuery.isArray( val ) ?
                    jQuery.map( val, function( val, i ){
                        return { name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
                    }) :
                    { name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
        }).get();

        return $.param(arr);
    }
}(jQuery));

function displayDate(timestamp, format) {
    var date = new Date(timestamp);
    document.write(moment(date).format(format));
}

function toProperCase(s) {
    return s.toLowerCase().replace(/^(.)|\s(.)/g, function($1) { return $1.toUpperCase(); });
}

function escapeHTML(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function toggleChevron(e) {
    (function($) {
        $(e.target)
          .prev('.panel-heading')
          .find("i.indicator")
          .toggleClass('fa-chevron-down fa-chevron-right');
    })(jQuery);
}

function validateImage() {
    (function($) {
        $("#application-form").find('#image').valid();
    })(jQuery);
}

function resetApplicationForm() {
    (function($) {
        // For some reason, calling reset() on the form with the 'fileinput' field
        // causes an error. So, reset fields individually...

        $('.fileinput').fileinput('clear');

        $('#application-form').find('input[type=text]').each(function() {
            var $defaultValue = $(this).prop('defaultValue');
            $(this).val($defaultValue);
        });

        $('#application-form').find('textarea').each(function() {
            var $defaultValue = $(this).prop('defaultValue');
            $(this).val($defaultValue);
        });

        $('#application-form').find('input:checkbox').each(function() {
            var $defaultValue = $(this).prop('defaultChecked');
            $(this).prop('checked', $defaultValue);
        });
    })(jQuery);
}

function resetUserForm() {
    (function($) {
        // For some reason, calling reset() on the form with the 'fileinput' field
        // causes an error. So, reset fields individually...

        $('#user-form').find('input[type=text]').each(function() {
            var $defaultValue = $(this).prop('defaultValue');
            $(this).val($defaultValue);
        });

        $('#user-form').find('input:checkbox').each(function() {
            var $defaultValue = $(this).prop('defaultChecked');
            $(this).prop('checked', $defaultValue);
        });
    })(jQuery);
}

/**
 * JS strip tags
 * @param input
 * @param allowed
 * @returns {string}
 */
function strip_tags(input, allowed) {
    allowed = (((allowed || '') + '')
        .toLowerCase()
        .match(/<[a-z][a-z0-9]*>/g) || [])
        .join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
    var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
        commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
    return input.replace(commentsAndPhpTags, '')
        .replace(tags, function($0, $1) {
            return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
        });
}

/**
 * Clean markdown language and set newlines with slash
 * @param input
 * @param length
 * @returns {string}
 */
function cleanMarkdownTxt(input, length) {
    if (input.length > 0) {
        input = marked(input);
        input = strip_tags(jQuery.trim(input));
        input = input.replace(/(?:(?:\r\n|\r|\n)\s*){2}/g, '\n');
        input = input.replace(/(\n)/g, " / ");
        input = input.replace('/[^\p{L}0-9_:.@\s\-\/]+/u', '');
        if (length == '' || length == null || length == 'undefined') {
            length = 235;
        }
        return input.substr(0, length) + ' ...';
    }

    return "-";
}

/**
 * Render external URL or manual description
 * @param description
 * @param documentationUrl
 * @param markdown
 * @returns {*}
 */
function apiDescriptionRender(description, documentationUrl, markdown) {
    if (documentationUrl != undefined && documentationUrl != '') {
        return "<a href='"+documentationUrl+"' target='blank'>"+documentationUrl+"</a>";
    } else {
        if (markdown) {
            return marked(escapeHTML(description));
        } else {
            return cleanMarkdownTxt(description);
        }
    }
}

/**
 * Validate URL with regex
 * @param url
 * @returns boolean
 */
function validateURL(url) {
    return url.match(/(http(s)?:\/\/.)?(www\.)?[^\p{L}0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/g);
}