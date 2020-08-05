function setSearchHtml(obj, html, searchbar) {
    var prev_height = obj[0].scrollHeight;
    obj.css('max-height', prev_height);
    obj.html(html);
    if (obj[0].scrollHeight < prev_height) {
        obj.css('padding-bottom', prev_height - obj[0].scrollHeight);
        obj.animate({paddingBottom: '0px'}, 200);
    }
    obj.animate({maxHeight: obj[0].scrollHeight + 'px'}, 200, "swing", function(){obj.css('max-height', 'none')});
}

jQuery.fn.material_searchbar = function() {
    var searchbar = $(this[0]);
    var action = searchbar.data('target');
    var data = searchbar.data('data');
    var uniqid = Date.now();
    searchbar.html(
        '<div class="container">' +
        '   <div class="row">' +
        '       <nav class="transparent inline">' +
        '           <div class="input-field">' +
        '               <input type="hidden" name="material-search-bar-id" value="search-'+ uniqid +'">' +
        '               <input clasS="material-search-bar-input" id="search-' + uniqid + '" type="search" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="Search something!">' +
        '               <label class="label-icon"  for="search-' + uniqid + '"><i class="material-icons">search</i></label>' +
        '               <i class="material-icons">close</i>' +
        '           </div>' +
        '       </nav>' +
        '       <form method="post" action=" '+ action + '" class="z-depth-3 white" style="border-bottom-right-radius: 2px; border-bottom-left-radius: 2px;">' +
        '           <ul id="search-result-' + uniqid + '" class="collection collection-flex" style="margin: 0;">' +
        '           </ul>' +
        '       </form>' +
        '   </div>' +
        '</div>'
    );

    $("#search-" + uniqid).each(function () {
        var is_searching = 0;
        var is_searching_for = '';
        var self = $(this);
        var target = $("#search-result-" + uniqid);

        var search = function (s) {
            $.ajax({
                url: action,
                method: "POST",
                data: data + '=' + s+ '&id=search-' + uniqid
            }).done(function (html) {
                setSearchHtml(target, html);
                searchbar.find('.look-for').each(function(){
                    $(this).on('click', function(){$('#search-'+uniqid).val($(this).html()).trigger('change')});
                });
            });
        };
        self.on('input change', function (e) {
            if(self.val() !== is_searching_for){
                if (is_searching) {
                    clearTimeout(is_searching);
                }
                is_searching_for = self.val();
                is_searching = setTimeout(function(){search(is_searching_for)}, e.type === 'input' ? 500 : 0);
            }
        }).siblings('i').on('click', function () {
            is_searching_for = 0;
            self.val('');
            setSearchHtml(target, '');
        });
    });

    return this;
};

$(document).ready(
   function(){
        $('.material-search-bar').material_searchbar();

   }
);

//APP SPECIFIC

function gotoUser(mail) {
    if(window.location.href.indexOf("manage-users.php") === -1){
        window.location = "manage-users.php?goto-user=" + mail;
    }
    else {
	    $(document).ready(function() {
		    $('#search-user-mail').val(mail).trigger('change').siblings('label').addClass("active");
		    my_scrollTo($('#edit-user').parent(), 500, 250);
	    });
    }
}

function gotoCategory(ISBN) {
    if(window.location.href.indexOf("manage-books.php") === -1){
        window.location = "manage-books.php?goto-category=" + ISBN;
    }
    else {
    	$(document).ready(function(){
		    $('ul.tabs').tabs('select_tab', 'tab-category-edit');
		    $('#category-edit-search').val(ISBN).trigger('change').siblings('label').addClass("active");
		    my_scrollTo($('#tab-category-edit'), 500, 250);
	    });
    }
}