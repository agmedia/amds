$(document).on('click','.showWishList', function(e){
	var modal = $('#show_wishlist_modal'), modalBody = $('#show_wishlist_modal .modal-body');
	document.getElementById('customer_id').value = this.id;
	document.getElementById('customer_name').value =  $('#customers .table.list tr[id="' + this.id + '"] td:nth-child(2)').text();
	modal
        .on('show.bs.modal', function () { 
			document.getElementById('customer_id').value = this.id;
			document.getElementById('customer_name').value =  $('#customers .table.list tr[id="' + this.id + '"] td:nth-child(2)').text();
            modalBody.load('index.php?route=module/wishlistdiscounts/wishlist&user_token=' + getURLVar('user_token'));
        })
        .modal();
    e.preventDefault();
});

$(document).on('click','.sendToAll', function(e){
	if($('input[id^=\'customer\']').length){
		var modal = $('#send_discount_modal'), modalBody = $('#send_discount_modal .modal-body');
		modal
			.on('show.bs.modal', function () { 
				modalBody.load('index.php?route=module/wishlistdiscounts/mailForm&user_token=' + getURLVar('user_token'))
			})
			.modal();
		e.preventDefault();
	} else {
		alert("There are no customers with wishlist!");
	}
});

$(document).on('click','.sendToSelected', function(e){
	var modal = $('#send_selected_discount_modal'), modalBody = $('#send_selected_discount_modal .modal-body');
    modal
        .on('show.bs.modal', function () { 
            modalBody.load('index.php?route=module/wishlistdiscounts/mailForm&user_token=' + getURLVar('user_token'))
        })
        .modal();
    e.preventDefault();
});

var sendToAll;
$('input[name="selectAllCustomers"]').on('click', function(e) { 
	$('input[id^=\'customer\']').attr('checked', this.checked);
	sendToAll= this.checked;
});
