// JavaScript Document

function getBaseURL () {
   return location.protocol + '//' + location.hostname + 
      (location.port && ':' + location.port) + '/';
}

(function() {
    tinymce.create('tinymce.plugins.vecb_button4', {
        init : function(ed, url) {
            ed.addButton('vecb_button4', {
                title : 'Box rumeni',image : 'http://nova.nova24tv.si/wp-content/uploads/vecb/box-y.png',onclick : function() {
                     ed.selection.setContent('[color-box color="yellow"] ' + ed.selection.getContent() + '[/color-box]');
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
    });
    tinymce.PluginManager.add('vecb_button4', tinymce.plugins.vecb_button4);
})();