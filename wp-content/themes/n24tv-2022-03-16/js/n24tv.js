/**
 * Our main JS file
 *
 * Not really needed for now.
 */

var n24tv = function(){

    this.clockId = null;

}

jQuery.extend(n24tv.prototype, {

    init: function(){
        //this.clock();
        this.masonry();
    },

    clock: function(){
        setInterval(this.clock, 1000);
        jQuery('#n24tv_clock').text(new Date().toLocaleString());
    },

    masonry(){
        jQuery('.grid').masonry({
          itemSelector: '.grid-item',
          columnWidth: '.grid-sizer',
          percentPosition: true
        });
    }

});

jQuery(document).ready(function(){

    var N24TV = new n24tv;
    N24TV.init();

});

