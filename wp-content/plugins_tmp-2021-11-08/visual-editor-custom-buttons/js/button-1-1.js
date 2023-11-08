// JavaScript Document

function getBaseURL () {
   return location.protocol + '//' + location.hostname + 
      (location.port && ':' + location.port) + '/';
}

(function() {
    tinymce.create('tinymce.plugins.vecb_button1', {
        init : function(ed, url) {
            ed.addButton('vecb_button1', {
                title : 'Box rdeči',image : 'http://nova.nova24tv.si/wp-content/uploads/vecb/box-r.png',onclick : function() {
                     ed.selection.setContent('[color-box color="red"] ' + ed.selection.getContent() + '[/color-box]');
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
    });
    tinymce.PluginManager.add('vecb_button1', tinymce.plugins.vecb_button1);
})();