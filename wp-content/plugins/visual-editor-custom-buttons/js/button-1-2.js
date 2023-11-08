// JavaScript Document

function getBaseURL () {
   return location.protocol + '//' + location.hostname + 
      (location.port && ':' + location.port) + '/';
}

(function() {
    tinymce.create('tinymce.plugins.vecb_button2', {
        init : function(ed, url) {
            ed.addButton('vecb_button2', {
                title : 'Box modri',image : 'https://flamboyant-carver.65-108-229-32.plesk.page/wp-content/uploads/vecb/box-b.png',onclick : function() {
                     ed.selection.setContent('[color-box color="blue"] ' + ed.selection.getContent() + '[/color-box]');
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
    });
    tinymce.PluginManager.add('vecb_button2', tinymce.plugins.vecb_button2);
})();