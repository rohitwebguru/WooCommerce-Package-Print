(function( $ ) {
	'use strict';
	$(function() {

		$('.wf_cst_change_addrlabel').change(function(){
			var ind=$(this).val();
			if(ind==""){ return false; }
			var trgt_elm=$('.wf_default_template_list_item:eq('+ind+')').find('.wfte_hidden_template_html');
			if(trgt_elm.length>0)
			{				
				/* taking current inner HTML */
				var current_code=$('#wfte_code').val();
				var temp_elm=$('<div />').html(current_code);
				var inner_html=temp_elm.find('.wfte_addresslabel_data').html();

				/* applying new config to current inner HTML */
				var template_html=trgt_elm.html();
				temp_elm=$('<div />').html(template_html);
				temp_elm.find('.wfte_addresslabel_data').html(inner_html);
				template_html=temp_elm.html();

				$('#wfte_code').val(template_html);
				pklist_customize.updateFromCodeView();
			}
		});

	});
})( jQuery );