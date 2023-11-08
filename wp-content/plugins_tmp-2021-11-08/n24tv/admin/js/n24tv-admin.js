(function( $ ) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

     $('.n24tv-sortable').sortable(
        {
            placeholder: "ui-state-highlight",
            revert: true,
            forcePlaceholderSize: true,
            update: function (event, ui){
                var cat_id = $(this).data('id'); // get data-id value from ul where item was dropped
                var item = ui.item; // the element that was dropped
                item.width('90%');
                var input = item.children('input'); // get <input> from dropped <li>
                // change input name
                input.attr('name', 'n24tv_featured_posts[' + cat_id + '][]');
            }

        }
    );
    $('.n24tv-draggable').draggable(
        {
            connectToSortable: '.n24tv-sortable',
            helper: 'clone',
            revert: 'invalid',
            cursor: "move"
        }
    );

    n24tv_admin_right_affix();

    /**
     * for each post under latest from category
                                    <li class="n24tv-draggable">
                                        <input type=hidden name="n24tv_latest_posts[]" value="<?=$post['ID'];?>">
                                        <?=$post['post_title'];?> (<?=$post['post_status']?>)
                                        <a onClick="n24tv_admin_delete_element(jQuery(this).parent('li'))"><span style="float: right;" class="dashicons dashicons-no-alt n24tv-remove-icon"></span></a>
                                    </li>
     */


    // install the change event on select
    $('#n24tv_admin_featured_category_list').change(
        function(){
            n24tv_admin_latest_posts_load();
        }
    );

    n24tv_admin_keypress_delay( document.getElementById("n24tv_search_title"), n24tv_admin_latest_posts_load, 1000 );

    n24tv_admin_latest_posts_load(-1);

})( jQuery );

function n24tv_admin_right_affix(){
    var el = jQuery('#n24tv-admin-right');
    var width = el.width();
    var position = el.position();

    el.css('position', 'fixed').css('width', width + 'px').css('left', (position.left+200) + 'px');
}

function n24tv_admin_keypress_delay(textArea, callback, delay) {
    var timer = null;
    textArea.onkeypress = function() {
        if (timer) {
            window.clearTimeout(timer);
        }
        timer = window.setTimeout( function() {
            timer = null;
            callback();
        }, delay );
    };
    textArea = null;
}

function n24tv_admin_latest_posts_load(){
    var myUl = jQuery('#n24tv-admin-latest-posts');
    var mySel = jQuery('#n24tv_admin_featured_category_list');
    var myTxt = jQuery('#n24tv_search_title');

    var cat_id = mySel.find('option:selected').val();
    var title = myTxt.val();

    var data = {
        'action' : 'n24tv_admin_get_latest_posts', 
        'cat_id' : cat_id,
        'title'  : title
    };

    // empty the list
    myUl.children('li').remove();
    myUl.append('<li> Loading ... </li>');
    // send ajax request
    jQuery.post(ajaxurl, data, function(res){
        myUl.children('li').remove();
        var posts = jQuery.parseJSON(res);
        for(i = 0; i < posts.length; i++){
            var post = posts[i];
            var myLi = jQuery('<li class="n24tv-draggable">' +
                       '<input type=hidden name="n24tv_latest_posts[]" value="' + post.id + '">' +
                       post.title + ' ' + '(' + post.status + ')' + 
                       '<a onClick="n24tv_admin_delete_element(jQuery(this).parent(\'li\'))">' + 
                       '<span style="float: right;" class="dashicons dashicons-no-alt n24tv-remove-icon"></span></a>' + 
                       '</li>');
            myLi.draggable(
                {
                    connectToSortable: '.n24tv-sortable',
                    helper: 'clone',
                    revert: 'invalid',
                    cursor: "move"
                }
            );
            myUl.append(myLi);
        }
    });
}

function n24tv_admin_delete_element(el){
    if (confirm('Are you sure?')){
        el.remove();
    }
}

function n24tv_admin_featured_add_category(){
    var sel = jQuery('#n24tv_admin_category_list');
    var cat_id = sel.val();
    var cat_name = sel.find('option:selected').text();


    if (cat_id == -1){
        cat_name = prompt('Enter custom name');
        if (cat_name === null){
            return false;
        }
        cat_name = cat_name.toLowerCase().replace(/[^0-9\_a-z]+/g, '_');
        cat_id = cat_name;
    }


    var tmpl = '<div class="postbox n24tv-featured-category">' +
               '<h2>' +
               cat_name + 
               '<a onClick="n24tv_admin_delete_element(jQuery(this).parents(\'.n24tv-featured-category\'));">' +
               '<span class="dashicons dashicons-trash" style="float: right;"></span>' +
               '</a>' +
               '</h2>' +
               '<div class="inside">' +
               '<ul class="n24tv-sortable n24tv-post-list" data-id="' + cat_id + '">' +
               '</ul>' +
               '</div>' +
               '</div>';
    
    jQuery('#n24tv-admin-left').append(tmpl).last().find('ul').sortable(        {
            placeholder: "ui-state-highlight",
            revert: true,
            forcePlaceholderSize: true,
            update: function (event, ui){
                var cat_id = jQuery(this).data('id'); // get data-id value from ul where item was dropped
                var item = ui.item; // the element that was dropped
                item.width('90%');
                var input = item.children('input'); // get <input> from dropped <li>
                // change input name
                input.attr('name', 'n24tv_featured_posts[' + cat_id + '][]');
            }

        });

    return false;
}
