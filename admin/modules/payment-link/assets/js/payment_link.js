(function( $ ) {
	function wf_toggle_payment_link_field(){

		var paylater_title_tr = $('[name="woocommerce_wf_pay_later_title"]').parents('tr');
		var paylater_desc_tr = $('[name="woocommerce_wf_pay_later_description"]').parents('tr');
		var paylater_inst_tr = $('[name="woocommerce_wf_pay_later_instuction"]').parents('tr');
		paylater_title_tr.hide().find('th label').css({'padding-left':'0px'});
		paylater_desc_tr.hide().find('th label').css({'padding-left':'0px'});
		paylater_inst_tr.hide().find('th label').css({'padding-left':'0px'});

		$('.form-table th label').css({'float':'left','width':'100%'});

		if(jQuery('[name="woocommerce_wf_show_pay_later_in_checkout"]').is(':checked'))
		{	
			console.log('hai');
			paylater_title_tr.show().find('th label').animate({'padding-left':'15px'});
			paylater_desc_tr.show().find('th label').animate({'padding-left':'15px'});
			paylater_inst_tr.show().find('th label').animate({'padding-left':'15px'});
		}
	}
	$('#woocommerce_wf_show_pay_later_in_checkout').change(function(){
		console.log('yes');
		wf_toggle_payment_link_field();
	});

	wf_toggle_payment_link_field();
})( jQuery );