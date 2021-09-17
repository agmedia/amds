var compgapro = {
	'initjson': function() {
		$(document).delegate('button[onclick*="cart.add"]', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/compgapro/trackevent',
				type: 'post', dataType: 'json', cache: false, data: 'product_id=' + ($(this).attr('onclick').match(/[0-9]+/)),
				success: function(jsonevent) {
					if(jsonevent['eventdata']) { gtag('event', jsonevent['langdata']['atctxt'], jsonevent['eventdata']); }
				}
			});
		});
		$(document).delegate('button[onclick*="wishlist.add"]', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/compgapro/trackevent',
				type: 'post', dataType: 'json', cache: false, data: 'product_id=' + ($(this).attr('onclick').match(/[0-9]+/)),
				success: function(jsonevent) {
					if(jsonevent['eventdata']) { gtag('event', jsonevent['langdata']['atwtxt'], jsonevent['eventdata']); }
				}
			});
		});
		$(document).delegate('button[onclick*="compare.add"]', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/compgapro/trackevent',
				type: 'post', dataType: 'json', cache: false, data: 'product_id=' + ($(this).attr('onclick').match(/[0-9]+/)),
				success: function(jsonevent) {
					if(jsonevent['eventdata']) { gtag('event', jsonevent['langdata']['atcmtxt'], jsonevent['eventdata']); }
				}
			});
		});
		$(document).delegate('button[onclick*="cart.remove"]', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/compgapro/trackevent',
				type: 'post', dataType: 'json', cache: false, data: 'product_id=' + ($(this).attr('onclick').match(/[0-9]+/)),
				success: function(jsonevent) {
					if(jsonevent['eventdata']) { gtag('event', jsonevent['langdata']['rmctxt'], jsonevent['eventdata']); }
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
					url: 'index.php?route=extension/compgapro/trackevent',
					type: 'post', dataType: 'json', cache: false, data: 'product_id=' + product_id,
					success: function(jsonevent) {
						if(jsonevent['eventdata']) { gtag('event', jsonevent['langdata']['atctxt'], jsonevent['eventdata']); }
					}
				});
			});
		}
		
		/* checkout funnel */
		$(document).delegate('#button-login, #button-account', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/compgapro/trackchkfunnel',
				type: 'post', dataType: 'json', cache: false, data: { stepnum: 1 },		
				success: function(jsonevent) {
					if(jsonevent['checkout_progress']) { gtag('event', 'checkout_progress', jsonevent['checkout_progress']); }
					if(jsonevent['checkout_option']) { gtag('event', 'checkout_option', jsonevent['checkout_option']); }
				}
			});
		});		
		$(document).delegate('#button-guest, #button-register', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/compgapro/trackchkfunnel',
				type: 'post', dataType: 'json', cache: false, data: { stepnum: 2 },		
				success: function(jsonevent) {
					if(jsonevent['checkout_progress']) { gtag('event', 'checkout_progress', jsonevent['checkout_progress']); }
					if(jsonevent['checkout_option']) { gtag('event', 'checkout_option', jsonevent['checkout_option']); }
				}
			});
			$.ajax({
				url: 'index.php?route=extension/compgapro/trackchkfunnel',
				type: 'post', dataType: 'json', cache: false, data: { stepnum: 3 },		
				success: function(jsonevent) {
					if(jsonevent['checkout_progress']) { gtag('event', 'checkout_progress', jsonevent['checkout_progress']); }
					if(jsonevent['checkout_option']) { gtag('event', 'checkout_option', jsonevent['checkout_option']); }
				}
			});
		});
		$(document).delegate('#button-payment-address', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/compgapro/trackchkfunnel',
				type: 'post', dataType: 'json', cache: false, data: { stepnum: 2 },		
				success: function(jsonevent) {
					if(jsonevent['checkout_progress']) { gtag('event', 'checkout_progress', jsonevent['checkout_progress']); }
					if(jsonevent['checkout_option']) { gtag('event', 'checkout_option', jsonevent['checkout_option']); }
				}
			});
		});
		$(document).delegate('#button-shipping-address', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/compgapro/trackchkfunnel',
				type: 'post', dataType: 'json', cache: false, data: { stepnum: 3 },		
				success: function(jsonevent) {
					if(jsonevent['checkout_progress']) { gtag('event', 'checkout_progress', jsonevent['checkout_progress']); }
					if(jsonevent['checkout_option']) { gtag('event', 'checkout_option', jsonevent['checkout_option']); }
				}
			});
		});
		$(document).delegate('#button-shipping-method', 'click', function() {	
			setTimeout(function(){
				$.ajax({
					url: 'index.php?route=extension/compgapro/trackchkfunnel',
					type: 'post', dataType: 'json', cache: false, data: { stepnum: 4 },		
					success: function(jsonevent) {
						if(jsonevent['checkout_progress']) { gtag('event', 'checkout_progress', jsonevent['checkout_progress']); }
						if(jsonevent['checkout_option']) { gtag('event', 'checkout_option', jsonevent['checkout_option']); }
					}
				});	
				$.ajax({
					url: 'index.php?route=extension/compgapro/trackshipinfo',
					type: 'post', dataType: 'json', cache: false,		
					success: function(jsonevent) {
						if(jsonevent['add_shipping_info']) { gtag('event', 'add_shipping_info', jsonevent['add_shipping_info']); }
					}
				});
			}, 3000);			
		});
		$(document).delegate('#button-payment-method', 'click', function() {	
			setTimeout(function(){
				$.ajax({
					url: 'index.php?route=extension/compgapro/trackchkfunnel',
					type: 'post', dataType: 'json', cache: false, data: { stepnum: 5 },		
					success: function(jsonevent) {
						if(jsonevent['checkout_progress']) { gtag('event', 'checkout_progress', jsonevent['checkout_progress']); }
						if(jsonevent['checkout_option']) { gtag('event', 'checkout_option', jsonevent['checkout_option']); }
					}
				});
				$.ajax({
					url: 'index.php?route=extension/compgapro/trackpayinfo',
					type: 'post', dataType: 'json', cache: false,		
					success: function(jsonevent) {
						if(jsonevent['add_payment_info']) { gtag('event', 'add_payment_info', jsonevent['add_payment_info']); }
					}
				});
			}, 3000);
		});
		$(document).delegate('#button-confirm', 'click', function() {	
			$.ajax({
				url: 'index.php?route=extension/compgapro/trackchkfunnel',
				type: 'post', dataType: 'json', cache: false, data: { stepnum: 5 },		
				success: function(jsonevent) {
					if(jsonevent['checkout_progress']) { gtag('event', 'checkout_progress', jsonevent['checkout_progress']); }
					if(jsonevent['checkout_option']) { gtag('event', 'checkout_option', jsonevent['checkout_option']); }
				}
			});
		});
	}
}
$(document).ready(function() {
compgapro.initjson();
});