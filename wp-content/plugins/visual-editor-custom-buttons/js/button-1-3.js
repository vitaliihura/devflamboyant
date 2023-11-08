// JavaScript Document

function getBaseURL () {
   return location.protocol + '//' + location.hostname + 
      (location.port && ':' + location.port) + '/';
}

(function() {
    tinymce.create('tinymce.plugins.vecb_button3', {
        init : function(ed, url) {
            ed.addButton('vecb_button3', {
                title : 'Box zeleni',image : 'https://flamboyant-carver.65-108-229-32.plesk.page/wp-content/uploads/vecb/box-g.png',onclick : function() {
                     ed.selection.setContent('[color-box color="green"] ' + ed.selection.getContent() + '[/color-box]');
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
    });
    tinymce.PluginManager.add('vecb_button3', tinymce.plugins.vecb_button3);
})();