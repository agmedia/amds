$(document).ready(function() {
	$('#search input[name=\'search\']').on('change', function(e) {
		var search_status = $('input[name="me_fb_events_track_search"]').val();
		if($(this).val() != '' && search_status == 1){
			fbq('track', 'Search', {
				search_string: $(this).val(), 
			});
		}	
	});
});
function meaddtocart(product_id, quantity = 1){
	$.ajax({
		url: 'index.php?route=extension/me_fb_events&product_id='+product_id,
		dataType: 'json',
		beforeSend: function() {
		},
		complete: function() {
		},
		success: function(json){
			if(json['me_fb_events_track_cart'] && json['me_fb_events_pixel_id']){
				fbq('track', 'AddToCart', {
					content_name: json['name'], 
					content_ids : [product_id],
				   content_type : 'product',
				   value : json['price'],
				   currency: json['currencycode'],
				 });
			}
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
}
function meaddtowishlist(product_id){
	$.ajax({
		url: 'index.php?route=extension/me_fb_events&product_id='+product_id,
		dataType: 'json',
		beforeSend: function() {
		},
		complete: function() {
		},
		success: function(json){
			if(json['me_fb_events_track_wishlist']){
				fbq('track', 'AddToWishlist', {
					content_name: json['name'], 
					content_ids : [product_id],
				   value : json['price'],
				   currency: json['currencycode'],
				 });
			}
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
}