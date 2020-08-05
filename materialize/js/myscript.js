debug = false;

function printMsg(json_msgs) {
    for (var msg in json_msgs) {
        Materialize.toast(json_msgs[msg].message, json_msgs[msg].duration, json_msgs[msg].style);
    }
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function download(filename, text, base64) {
    base64 = base64 === undefined ? 'charset=utf-8,' : 'base64,';
    text = text ? text : '\0';
    if(filename){
        var element = document.createElement('a');
        element.setAttribute('href', 'data:application/octet-stream;' + base64 + text);
        element.setAttribute('download', filename);

        element.style.display = 'none';
        document.body.appendChild(element);

        element.click();

        document.body.removeChild(element);
    }
}

/**
 * @return {string}
 */
function JSONtoCSV(json) {
    if(json && json.length){
        var fields = Object.keys(json[0]);
        var replacer = function(key, value) { return value === null ? '' : value };
        var csv = json.map(function(row){
            return fields.map(function(fieldName){
                return JSON.stringify(row[fieldName], replacer)
            }).join(';')
        });
        csv.unshift(fields.map(function(fieldName){return JSON.stringify(fieldName)}).join(';'));

        return csv.join('\r\n');
    }
}

function currentDate() {
    var today = new Date();
    var dd = today.getDate();
    var mm = today.getMonth()+1; //January is 0!
    var yyyy = today.getFullYear();

    if(dd<10) {
        dd = '0'+dd
    }

    if(mm<10) {
        mm = '0'+mm
    }

    today = yyyy + '_' + dd + '_' + mm;

    return today;
}

function my_scrollTo(target, time, delay) {
    if (delay === undefined) {
        delay = 0;
    }
    setTimeout(function () {
        $('html, body').animate({
            scrollTop: $(target).offset().top
        }, time);
    }, delay);
}

jQuery.fn.updateTextFields = function () {
    var form = $(this);
    var input_selector = 'input[type=text], input[type=password], input[type=email], input[type=url], input[type=tel], input[type=number], input[type=search], textarea';
    form.find(input_selector).each(function (index, element) {
        var $this = $(this);
        if ($(element).val().length > 0 || $(element).is(':focus') || element.autofocus || $this.attr('placeholder') !== undefined) {
            $this.siblings('label').addClass('active');
        } else if ($(element)[0].validity) {
            $this.siblings('label').toggleClass('active', $(element)[0].validity.badInput === true);
        } else {
            $this.siblings('label').removeClass('active');
        }
    });
    form.find('select').material_select();
};

jQuery.fn.ajaxFormEx = function (success_callback, before_callback) {
    $(this).each(function () {
        var o = $(this);
        var preloaderID = '#' + o.data('preloader');
        var preloader = $(preloaderID);
        o.ajaxForm({
            beforeSubmit: function (data) {
                if (preloader) {
                    preloader.show();
                }
                if (before_callback) before_callback(o, data);
            },
            success: function (d) {
                if (debug) {
                    console.log(d);
                }
                if (preloader) {
                    preloader.hide();
                }
                if (d) {
                    if (d.r) {
                        if (success_callback) success_callback(d.data, o);
                    }
                    if (d.msgs) {
                        printMsg(d.msgs);
                    }
                }
            },
            error: function (e) {
                if(debug){
                    console.log(e);
                }
                if(e.responseJSON.msgs) {
                    printMsg(e.responseJSON.msgs);
                }
            },
            data: {ajax: true},
            dataType: 'json'
        });
    })
};

$(document).ready(function () {
    $('select').material_select();
    $('.modal').modal();
    $('form').on('reset', function () {
        o = $(this);
        setTimeout(function () {
            o.updateTextFields();
        }, 0);
    });
    $('ul.tabs').each(function () {
        var t = false;
        $(this).tabs({
            swipeable: true, onShow: function (active) {
                if (t) {
                    $('.tabs-content').animate({
                            "max-height": $(active).height(),
                            "min-height": $(active).height()
                        }, 500,
                        function () {
                            clearTimeout(t);
                            t = setTimeout(function () {
                                $('.tabs-content').css('max-height', 'none').css('min-height', '');
                            }, 550)
                        });
                }
                t = true;
            }
        });
        $('.tabs > .tab').on('click', function () {
            var o = $('.tabs-content');
            o.css('min-height', o.height())
        });
    });
});