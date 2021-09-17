var fbecommevnt = {
	'initjson': function() {
		$(document).delegate('button[onclick*="cart.add"]', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/fbecommevnt/trackevent',
				type: 'post', dataType: 'json', cache: false, data: 'product_id=' + ($(this).attr('onclick').match(/[0-9]+/)),
				success: function(jsonevent) {
					if(jsonevent['eventdata']) { fbq('track', jsonevent['langdata']['atctxt'], jsonevent['eventdata']); }
				}
			});
		});
		$(document).delegate('button[onclick*="wishlist.add"]', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/fbecommevnt/trackevent',
				type: 'post', dataType: 'json', cache: false, data: 'product_id=' + ($(this).attr('onclick').match(/[0-9]+/)),
				success: function(jsonevent) {
					if(jsonevent['eventdata']) { fbq('track', jsonevent['langdata']['atwtxt'], jsonevent['eventdata']); }
				}
			});
		});
		$(document).delegate('button[onclick*="compare.add"]', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/fbecommevnt/trackevent',
				type: 'post', dataType: 'json', cache: false, data: 'product_id=' + ($(this).attr('onclick').match(/[0-9]+/)),
				success: function(jsonevent) {
					if(jsonevent['eventdata']) { fbq('track', jsonevent['langdata']['atcmtxt'], jsonevent['eventdata']); }
				}
			});
		});
		$(document).delegate('button[onclick*="cart.remove"]', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/fbecommevnt/trackevent',
				type: 'post', dataType: 'json', cache: false, data: 'product_id=' + ($(this).attr('onclick').match(/[0-9]+/)),
				success: function(jsonevent) {
					if(jsonevent['eventdata']) { fbq('trackCustom', jsonevent['langdata']['rmctxt'], jsonevent['eventdata']); }
				}
			});
		});
		var product_id = false;
		if($("input[name='product_id']").length) {
			var product_id = $("input[name='product_id']").val().toString();
		}
		if($('.button-group-page').length) {
			var product_id = $('.button-group-page').find("input[name='product_id']").val().toString();
		}
		if (typeof product_id !== 'undefined' && product_id) {
			$(document).delegate('#button-cart', 'click', function() {
				$.ajax({
					url: 'index.php?route=extension/fbecommevnt/trackevent',
					type: 'post', dataType: 'json', cache: false, data: 'product_id=' + product_id,
					success: function(jsonevent) {
						if(jsonevent['eventdata']) { fbq('track', jsonevent['langdata']['atctxt'], jsonevent['eventdata']); }
					}
				});
			});
		}
		
		/* checkout funnel */
		$(document).delegate('#button-login, #button-account', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/fbecommevnt/trackchkfunnel',
				type: 'post', dataType: 'json', cache: false, data: { stepnum: 1 },		
				success: function(jsonevent) {
					if(jsonevent['checkout_progress'] && jsonevent['stepname']) { fbq('trackCustom', jsonevent['stepname'], jsonevent['checkout_progress']); }
				}
			});
		});		
		$(document).delegate('#button-guest, #button-register', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/fbecommevnt/trackchkfunnel',
				type: 'post', dataType: 'json', cache: false, data: { stepnum: 2 },		
				success: function(jsonevent) {
					if(jsonevent['checkout_progress'] && jsonevent['stepname']) { fbq('trackCustom', jsonevent['stepname'], jsonevent['checkout_progress']); }
				}
			});
			$.ajax({
				url: 'index.php?route=extension/fbecommevnt/trackchkfunnel',
				type: 'post', dataType: 'json', cache: false, data: { stepnum: 3 },		
				success: function(jsonevent) {
					if(jsonevent['checkout_progress'] && jsonevent['stepname']) { fbq('trackCustom', jsonevent['stepname'], jsonevent['checkout_progress']); }
				}
			});
		});
		$(document).delegate('#button-payment-address', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/fbecommevnt/trackchkfunnel',
				type: 'post', dataType: 'json', cache: false, data: { stepnum: 2 },		
				success: function(jsonevent) {
					if(jsonevent['checkout_progress'] && jsonevent['stepname']) { fbq('trackCustom', jsonevent['stepname'], jsonevent['checkout_progress']); }
				}
			});
		});
		$(document).delegate('#button-shipping-address', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/fbecommevnt/trackchkfunnel',
				type: 'post', dataType: 'json', cache: false, data: { stepnum: 3 },		
				success: function(jsonevent) {
					if(jsonevent['checkout_progress'] && jsonevent['stepname']) { fbq('trackCustom', jsonevent['stepname'], jsonevent['checkout_progress']); }
				}
			});
		});
		$(document).delegate('#button-shipping-method', 'click', function() {	
			setTimeout(function(){
				$.ajax({
					url: 'index.php?route=extension/fbecommevnt/trackchkfunnel',
					type: 'post', dataType: 'json', cache: false, data: { stepnum: 4 },		
					success: function(jsonevent) {
						if(jsonevent['checkout_progress'] && jsonevent['stepname']) { fbq('trackCustom', jsonevent['stepname'], jsonevent['checkout_progress']); }
					}
				});	
				$.ajax({
					url: 'index.php?route=extension/fbecommevnt/trackshipinfo',
					type: 'post', dataType: 'json', cache: false,		
					success: function(jsonevent) {
						if(jsonevent['add_shipping_info'] && jsonevent['shipping_tier']) {
							fbq('trackCustom', 'AddShippingInfo', jsonevent['add_shipping_info']);
							fbq('trackCustom', jsonevent['shipping_tier'], jsonevent['add_shipping_info']);
						}
					}
				});
			}, 3000);			
		});
		$(document).delegate('#button-payment-method', 'click', function() {	
			setTimeout(function(){
				$.ajax({
					url: 'index.php?route=extension/fbecommevnt/trackchkfunnel',
					type: 'post', dataType: 'json', cache: false, data: { stepnum: 5 },		
					success: function(jsonevent) {
						if(jsonevent['checkout_progress'] && jsonevent['stepname']) { fbq('trackCustom', jsonevent['stepname'], jsonevent['checkout_progress']); }
					}
				});
				$.ajax({
					url: 'index.php?route=extension/fbecommevnt/trackpayinfo',
					type: 'post', dataType: 'json', cache: false,		
					success: function(jsonevent) {
						if(jsonevent['add_payment_info'] && jsonevent['payment_type']) {
							fbq('track', 'AddPaymentInfo', jsonevent['add_payment_info']);
							fbq('trackCustom', jsonevent['payment_type'], jsonevent['add_payment_info']);
						}
					}
				});
			}, 3000);
		});
		$(document).delegate('#button-confirm', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/fbecommevnt/trackchkfunnel',
				type: 'post', dataType: 'json', cache: false, data: { stepnum: 5 },		
				success: function(jsonevent) {
					if(jsonevent['checkout_progress'] && jsonevent['stepname']) { fbq('trackCustom', jsonevent['stepname'], jsonevent['checkout_progress']); }
				}
			});
		});
	}
}
$(document).ready(function() {
fbecommevnt.initjson();
});