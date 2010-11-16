(function() {
    tinymce.create('tinymce.plugins.tp_insert_product', {
 
        init : function(ed, url){
	    	ed.addCommand('tp_insert_product_dialog', function() {
	    		tb_show('2Performant Product Importer', '../wp-content/plugins/2performant-product-importer/tinymce-insert/insert-from-feed.php?TB_iframe=1&width=280&height=600');
	    		tp_insert_tb_setsize();	    		
	    		tinymce.DOM.setStyle( ['TB_overlay','TB_window','TB_load'], 'z-index', '999999' );
			});

    		ed.addButton('tp_insert_product', {
    			title : 'Insert 2Performant Product',
                cmd: 'tp_insert_product_dialog',
                image: url + "/2p.png"
            });
        }
    });
 
    tinymce.PluginManager.add('tp_insert_product', tinymce.plugins.tp_insert_product);
 
})();