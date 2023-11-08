(function( $ ) {
    'use strict';

    $(function(){

        $('select#category').change(function(){
            var cat = $(this).val();
            schedule_update_genre(cat);
        });

        $('#wp_media_search').submit(function(ev){
            var form = $(this);
            var search = form.find('#wp_media_search_text').val();
            schedule_search_wp_media(search);
            ev.preventDefault();
        });

    });


})( jQuery );

function schedule_add_select_options(select, options, selected = null){
    select.find('option').remove();
    for(var i = 0; i < options.length; i++){
        var option = options[i];
        select.append('<option value="' + option + '"' + (selected == option ? ' selected' : '') + '>' + option + '</option>');
    }
}

function schedule_update_genre(category, selected = null){
        jQuery.ajax('ajax.php?op=genre&category=' + category)
        .done(function(data){
            var gen = jQuery('#genre');
            schedule_add_select_options(gen, data, selected);
        })
        .error(function(jqXHR, text, error){
            alert('AJAX napaka');
        });
}

function schedule_search_wp_media_render_result(media){
    return  '<div class="media">' + 
            '<div class="media-left media-middle">' + 
                '<a href="#"><img class="media-object" src="' + media.thumbnail + '"></a>' + 
            '</div>' + 
            '<div class="media-body">' +
                '<h4 class="media-heading">' + media.title + '</h4>' + 
                '<div class="row"><div class="col-md-12">' + media.excerpt + '</div></div>' + 
                '<br/></br>' +
                '<div class="row">' + 
                '<div class="col-md-6"><span class="label label-' + (media.suitable ? 'success' : 'danger') + '">' + media.width + 'x' + media.height + '<span></div>' + 
                '<div class="col-md-2 col-md-offset-4">' + 
                '<button ' + (media.suitable ? '' : ' disabled ') + 'type="button" onclick="schedule_search_wp_media_use(\'' + media.id + '\')" data-id="something" class="btn btn-success wp-media-select">Uporabi</button>' + 
                '</div></div>' +
            '</div>' +
            '</div>';
}

function schedule_search_wp_media_results(data){
    var content = jQuery('#wp_media_search_results');
    console.log('update results: ', data);
    content.empty();
    for(var i = 0; i < data.length; i++){
        content.append(schedule_search_wp_media_render_result(data[i]));
    }

}

function schedule_search_wp_media(search = null){

    jQuery.ajax('ajax.php?op=media_search&search=' + search)
        .done(function(data){
            schedule_search_wp_media_results(data);
        })
        .error(function(jqXHR, text, error){
            alert('AJAX napaka');
        });

    return false;
}

function schedule_search_wp_media_use(id){
    jQuery('#picture').val(id);
    jQuery('#myPictureSelect').modal('hide');
}