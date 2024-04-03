var wt_pklist_cloud_print=(function( $ ) {
	'use strict';
	var wt_pklist_cloud_print=
	{
		on_progress:false,
		waiting:false, /* waiting for authentication response */
		connected:false,
		popup_close_interval:null,
		default_printer_id:0,
		Set:function()
		{
			this.connected=(wt_pklist_cloud_print_params.connected==1 ? true : false);
			this.default_printer_id=wt_pklist_cloud_print_params.default_printer_id;

			wt_pklist_cloud_print.process_default_toggle_btn_labels();

			/**
			*	Registering connect button click
			*/
			$('[name="wt_pklist_cloud_connect"]').click(function(){
				var btn_action=$(this).attr('data-action');
				if(btn_action=='connect')
				{
					wt_pklist_cloud_print.connect_to_google();
				}else
				{
					wt_pklist_cloud_print.disconnect_from_google();
				}
			});
		},
		process_auth_response:function(status, msg)
		{
			if(status==1)
			{
				wf_notify_msg.success(msg);				
			}else
			{
				wf_notify_msg.error(msg);
			}
			this.on_child_window_close();
		},
		connect_btn_loader:function()
		{
			var btn=$('[name="wt_pklist_cloud_connect"]');
			var btn_text=wt_pklist_cloud_print_params.msgs.connect;
			var btn_disabled=false;
			var btn_action='connect';
			if(this.on_progress)
			{
				btn_text=wt_pklist_cloud_print_params.msgs.connecting;
				btn_disabled=true;
			}
			else if(this.waiting)
			{
				btn_text=wt_pklist_cloud_print_params.msgs.waiting;
				btn_disabled=true;
			}
			else if(this.connected)
			{
				btn_text=wt_pklist_cloud_print_params.msgs.disconnect;
				btn_disabled=false;
				btn_action='disconnect';
			}
			btn.parents('td').find('button').prop('disabled', btn_disabled);
			btn.html(btn_text).attr('data-action', btn_action);
			if(btn_action=='disconnect')
			{
				btn.addClass('button-secondary').removeClass('button-primary');
			}else
			{
				btn.removeClass('button-secondary').addClass('button-primary');
			}
		},
		on_child_window_close:function()
		{
			/* auth action not completed */
			if(this.waiting) 
			{
				this.waiting=false;
				this.refresh_connection_status();
			}
		},
		refresh_connection_status:function()
		{
			if(this.on_progress){ return false; }
			
			var data = {
	            _wpnonce:wt_pklist_cloud_print_params.nonces.main,
	            action: "wt_pklist_cloud_print_ajax",
	            cloud_print_action: "refresh_status",
	        };

			this.on_progress=true;
	        this.connect_btn_loader();
			$.ajax({
				type: 'POST',
            	url:wt_pklist_cloud_print_params.ajax_url,
            	data:data,
            	dataType:'json',
            	success:function(data)
            	{
            		wt_pklist_cloud_print.on_progress=false;
            		wt_pklist_cloud_print.connect_btn_loader();
            		if(data.status==1)
					{
						$('.wt_pklist_cloud_connect_status').html(data.html);
						wt_pklist_cloud_print.connected=data.connected;
						wt_pklist_cloud_print.connect_btn_loader();
					}
					wt_pklist_cloud_print.toggle_credentials_fields_readonly();
            	},
            	error:function()
            	{
            		wt_pklist_cloud_print.on_progress=false;
            		wt_pklist_cloud_print.connect_btn_loader();
            		wt_pklist_cloud_print.toggle_credentials_fields_readonly();
            	}
			});
		},
		disconnect_from_google:function()
		{
			if(this.on_progress){ return false; }
			var data = {
	            _wpnonce:wt_pklist_cloud_print_params.nonces.main,
	            action: "wt_pklist_cloud_print_ajax",
	            cloud_print_action: "deauthorize",
	        };

	        this.on_progress=true;
	        this.connect_btn_loader();
	        
	        $('.wt_pklist_cloud_printers').html('');

	        $.ajax({
				type: 'POST',
            	url:wt_pklist_cloud_print_params.ajax_url,
            	data:data,
            	dataType:'json',
            	success:function(data)
            	{
            		wt_pklist_cloud_print.on_progress=false;
            		wt_pklist_cloud_print.connect_btn_loader();
            		if(data.status==1)
					{
						$('.wt_pklist_cloud_connect_status').html('');
						wt_pklist_cloud_print.connected=false;
						wt_pklist_cloud_print.connect_btn_loader();
					}else
					{
						wf_notify_msg.error(wt_pklist_cloud_print_params.msgs.error);
					}
					wt_pklist_cloud_print.toggle_credentials_fields_readonly();
            	},
            	error:function()
            	{
            		wt_pklist_cloud_print.on_progress=false;
            		wf_notify_msg.error(wt_pklist_cloud_print_params.msgs.error);
            		wt_pklist_cloud_print.connect_btn_loader();
            		wt_pklist_cloud_print.toggle_credentials_fields_readonly();
            	}
			});
		},
		toggle_credentials_fields_readonly:function()
		{
			var credentials_fields=$('[name="wt_pklist_cloud_print_client_id"], [name="wt_pklist_cloud_print_client_secret"]');
			if(wt_pklist_cloud_print.connected)
			{
				credentials_fields.attr('readonly','readonly').css({'background':'#f6f6f6'});
				$('.wt_pklist_cloud_print_update_after_connect_infobox').show();
			}else
			{
				credentials_fields.removeAttr('readonly').css({'background':'#fff'});
				$('.wt_pklist_cloud_print_update_after_connect_infobox').hide();	
			}
		},
		connect_to_google:function()
		{
			if(this.on_progress || this.waiting){ return false; }

			var client_id=$.trim($('[name="wt_pklist_cloud_print_client_id"]').val());
			var client_secret=$.trim($('[name="wt_pklist_cloud_print_client_secret"]').val());

			if(client_id=="")
			{
				wf_notify_msg.error(wt_pklist_cloud_print_params.msgs.client_id_mandatory);
				$('[name="wt_pklist_cloud_print_client_id"]').focus();
				return false;
			}
			if(client_secret=="")
			{
				wf_notify_msg.error(wt_pklist_cloud_print_params.msgs.client_secret_mandatory);
				$('[name="wt_pklist_cloud_print_client_secret"]').focus();
				return false;
			}

			/* generate auth URL */
			var data = {
	            _wpnonce:wt_pklist_cloud_print_params.nonces.main,
	            action: "wt_pklist_cloud_print_ajax",
	            cloud_print_action: "authorize",
	            client_id: client_id,
	            client_secret: client_secret
	        };
	        this.on_progress=true;
	        this.connect_btn_loader();
			$.ajax({
				type: 'POST',
            	url:wt_pklist_cloud_print_params.ajax_url,
            	data:data,
            	dataType:'json',
            	success:function(data)
            	{
            		wt_pklist_cloud_print.on_progress=false;
            		wt_pklist_cloud_print.connect_btn_loader();
            		if(data.status==1)
					{
						var w = 450;
						var h = 600;
						var l = (screen.width/2)-(w/2);
  						var t = 50;
  						
  						wt_pklist_cloud_print.waiting=true; /* waiting for auth response */
  						wt_pklist_cloud_print.connect_btn_loader();
						
						var auth_window=window.open(data.url, 'wt_pklist_cloud_print_auth', "width="+w+",height="+h+",top="+t+",left="+l+",menubar=no,location=no,resizable=no,scrollbars=yes,status=yes");
						auth_window.focus();

						/* Checks the popup window is closed or not */
						wt_pklist_cloud_print.popup_close_interval=setInterval(function(){ 
							if(auth_window)
							{
								if(auth_window.closed) /* closed the popup window */
								{
									clearInterval(wt_pklist_cloud_print.popup_close_interval);
									wt_pklist_cloud_print.on_child_window_close();									
								}
							}else
							{
								clearInterval(wt_pklist_cloud_print.popup_close_interval);
								wt_pklist_cloud_print.on_child_window_close();
							}
							
						}, 1000);

					}else
					{
						wf_notify_msg.error(data.msg);
					}
            	},
            	error:function()
				{
					wt_pklist_cloud_print.on_progress=false;
            		wt_pklist_cloud_print.connect_btn_loader();
					wf_notify_msg.error(wt_pklist_cloud_print_params.msgs.unable_to_generate_auth_url);
				}
			});
		},
		find_printers:function(btn)
		{
			if(this.on_progress){ return false; }
			var btn_elm=$(btn);
			btn_elm.parents('td').find('button').prop('disabled', true);
			$('.wt_pklist_cloud_printers').html('<h4>'+wt_pklist_cloud_print_params.msgs.finding+'</h4>').css({'text-align':'center', 'display':'block'});
			
			var data = {
	            _wpnonce:wt_pklist_cloud_print_params.nonces.main,
	            action: "wt_pklist_cloud_print_ajax",
	            cloud_print_action: "search_printers",
	        };

			this.on_progress=true;
			$.ajax({
				type: 'POST',
            	url:wt_pklist_cloud_print_params.ajax_url,
            	data:data,
            	dataType:'json',
            	success:function(data)
            	{
            		wt_pklist_cloud_print.on_progress=false;
            		btn_elm.parents('td').find('button').prop('disabled', false);
            		if(data.status==1)
            		{
            			$('.wt_pklist_cloud_printers').html(data.html);
            			wt_pklist_cloud_print.process_default_toggle_btn_labels();
            			$(".wt-tips").tipTip({'attribute': 'data-wt-tip'});
            		}else
            		{
            			$('.wt_pklist_cloud_printers').html(data.msg);
            		}
            	},
            	error:function()
            	{
            		wt_pklist_cloud_print.on_progress=false;
            		btn_elm.parents('td').find('button').prop('disabled', false);
            		$('.wt_pklist_cloud_printers').html(wt_pklist_cloud_print_params.msgs.printer_search_error);
            	}
			});
		},
		test_printer:function(elm, printer_id, status)
		{
			if(this.on_progress){ return false; }
			if(status!=='ONLINE')
			{
				return false;
			}

			var data = {
	            _wpnonce:wt_pklist_cloud_print_params.nonces.main,
	            action: "wt_pklist_cloud_print_ajax",
	            cloud_print_action: "test_printer",
	            printer_id: printer_id,
	        };
	        this.on_progress=true;
	        var html_bck=$(elm).html();
	        $(elm).html(wt_pklist_cloud_print_params.msgs.connecting);
	        var msg_box=$('.wt_pklist_cloud_print_test_msg_box');
			msg_box.hide();
			$.ajax({
				type: 'POST',
            	url:wt_pklist_cloud_print_params.ajax_url,
            	data:data,
            	dataType:'json',
            	success:function(data)
            	{
            		wt_pklist_cloud_print.on_progress=false;
            		$(elm).html(html_bck);
            		msg_box.show();
            		if(data.status==1)
            		{
            			msg_box.attr('class', 'wt_pklist_cloud_print_test_msg_box wt_success_box');
            			if(printer_id=='__google__docs')
            			{
            				msg_box.html(wt_pklist_cloud_print_params.msgs.printer_test_gdrive_success);
            			}else
            			{
            				msg_box.html(wt_pklist_cloud_print_params.msgs.printer_test_success);
            			}
            		}else
            		{
            			msg_box.attr('class', 'wt_pklist_cloud_print_test_msg_box wt_error_box');
            			msg_box.html(data.msg);	
            		}
            	},
            	error:function()
            	{
            		wt_pklist_cloud_print.on_progress=false;
            		$(elm).html(html_bck);
            		msg_box.show().html(wt_pklist_cloud_print_params.msgs.printer_test_error).attr('class', 'wt_pklist_cloud_print_test_msg_box wt_error_box');
            	}
			});
		},
		toggle_default_printer:function(elm, printer_id)
		{
			if(this.on_progress){ return false; }

			var data = {
	            _wpnonce:wt_pklist_cloud_print_params.nonces.main,
	            action: "wt_pklist_cloud_print_ajax",
	            cloud_print_action: "toggle_default_printer",
	            printer_id: printer_id,
	        };
	        this.on_progress=true;
	        var html_bck=$(elm).html();
	        $(elm).html(wt_pklist_cloud_print_params.msgs.connecting);
			$.ajax({
				type: 'POST',
            	url:wt_pklist_cloud_print_params.ajax_url,
            	data:data,
            	dataType:'json',
            	success:function(data)
            	{
            		wt_pklist_cloud_print.on_progress=false;
            		$(elm).html(html_bck);
            		if(data.status==1)
            		{
            			wt_pklist_cloud_print.default_printer_id=data.default_printer_id;
            			wt_pklist_cloud_print.process_default_toggle_btn_labels();
            			wf_notify_msg.success(wt_pklist_cloud_print_params.msgs.success);
            		}else
            		{
            			wf_notify_msg.error(data.msg);	
            		}
            	},
            	error:function()
            	{
            		wt_pklist_cloud_print.on_progress=false;
            		$(elm).html(html_bck);
            		wf_notify_msg.error(wt_pklist_cloud_print_params.msgs.error);
            	}
			});
		},
		process_default_toggle_btn_labels:function()
		{
			var tb=$('.wt_pklist_cloud_printers_tb');
			if(tb.length>0)
			{
				tb.find('tbody').find('tr').each(function(){
					var default_toggle_btn=$(this).find('.wt_pklist_cloud_printer_default_toggle_btn');
					if(default_toggle_btn.length>0)
					{
						var printer_id=default_toggle_btn.attr('data-id');
						var display_name_tr=$(this).find('.wt_pklist_cloud_print_printer_display_name');
						display_name_tr.find('.wt_pklist_cloud_print_default_printer_info').remove();
						if(printer_id===wt_pklist_cloud_print.default_printer_id)
						{
							default_toggle_btn.html(wt_pklist_cloud_print_params.msgs.remove_from_default);
							display_name_tr.append(' <span class="wt_pklist_cloud_print_default_printer_info" title="'+wt_pklist_cloud_print_params.msgs.default_printer+'"><span class="dashicons dashicons-yes-alt"></span></span>');
						}else
						{
							default_toggle_btn.html(wt_pklist_cloud_print_params.msgs.set_as_default);	
						}
					}
				});
			}
		}
	};
	return wt_pklist_cloud_print;
})( jQuery );

jQuery(function() {
	wt_pklist_cloud_print.Set();
});