(function( $ ) {
	'use strict';
	$(function() {
		var wt_pklist_cloud_print_manual=
		{
			on_progress:false,
			Set:function()
			{
				if(wt_pklist_manual_cloud_print_params.default_printer_id==0)
				{
					$('.wt_pklist_manual_cloud_print').hide();
				}

				$('.wt_pklist_manual_cloud_print').removeAttr('href').css({'cursor':'pointer'});
				
				$('.wt_pklist_manual_cloud_print').click(function(){
					var btn=$(this);
					if(btn.attr('data-on-progress')==1)
					{
						return false;
					}
					var template_type=$.trim(btn.attr('data-template_type'));
					var order_id=$.trim(btn.attr('data-id'));
					if(template_type!="" && order_id!="")
					{

						var data = {
				            _wpnonce:wt_pklist_manual_cloud_print_params.nonces.main,
				            action: "wt_pklist_cloud_print_ajax",
				            cloud_print_action: "manual_print",
				            template_type: template_type,
				            order_id: order_id,
				        };
				        var btn_html_bck=btn.html();
				        btn.html(wt_pklist_manual_cloud_print_params.msgs.wait).attr('data-on-progress', 1).css({'cursor':'wait'});
						$.ajax({
							type: 'POST',
			            	url:wt_pklist_manual_cloud_print_params.ajax_url,
			            	data:data,
			            	dataType:'json',
			            	success:function(data)
			            	{
			            		btn.html(btn_html_bck).attr('data-on-progress', 0).css({'cursor':'pointer'});
			            		if(data.status==1)
			            		{	
			            			wf_notify_msg.success(wt_pklist_manual_cloud_print_params.msgs.success);
			            		}else
			            		{
			            			wf_notify_msg.error(data.msg);
			            		}
			            	},
			            	error:function()
			            	{
			            		btn.html(btn_html_bck).attr('data-on-progress', 0).css({'cursor':'pointer'});
			            		wf_notify_msg.error(wt_pklist_manual_cloud_print_params.msgs.error);
			            	}
						});
					}
				});
			},
		};

		wt_pklist_cloud_print_manual.Set();
	});

})( jQuery );