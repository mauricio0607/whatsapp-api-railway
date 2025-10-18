"use strict";

const device_id = $('#uuid').val();
const base_url  = $('#base_url').val();
const whatsappicon = base_url+'/assets/img/whatsapp.png';

checkSession();

function checkSession() {

	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		}
	});
	$.ajax({
		type: 'POST',
		url: base_url+'/user/check-session/'+device_id,
		dataType: 'json',
		success: function(response) {
			if (response.connected === true) {
				$('.server_disconnect').remove();
				$('.qr-area').remove();
				NotifyAlert('success', null, response.message);
				getChatList();
			}
			else{
				NotifyAlert('error', null, 'device not ready for sending message');
			}
		},
		error: function(xhr, status, error) {
			if (xhr.status == 500) {
				$('.server_disconnect').show();
				$('.main-area').hide();
			}
		}
	});
}

function getChatList() {
	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		}
	});
	$.ajax({
		type: 'POST',
		url: base_url+'/user/get-groups/'+device_id,
		dataType: 'json',
		success: function(response) {
			
			$('.qr-area').remove();

			$.each(response.chats, function (key, item) {

				

				var html = `<li class="list-group-item px-0 contact contact${key}">
				<div class="row align-items-center">
				<div class="col-auto">
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input group-id" id="group-${key}" name="groups[]" value="${item.id}">
					<label for="group-${key}" class="custom-control-label pt-1 avatar rounded-circle  ml-2 wa-link" data-active=".contact${key}" data-name="${item.name}"  data-id="${item.id}" > <img alt=""  src="${whatsappicon}"></label>
				</div>
				
				</div>
				<div class="col ml--2">
				<h4 class="mb-0">
				<a href="javascript:void(0)" data-active=".contact${key}" class="wa-link" data-name="${item.name}"  data-id="${item.id}">${item.name}</a>
				<a href="javascript:void(0)" class="btn btn-sm btn-success text-right float-right show-group-data"  data-id="${item.id}"><i class="fi fi-rs-eye"></i></a> 
				
				</h4>
				
				</div>
				</div>
				</li>`;

				$('.contact-list').append(html);
			});


		},
		error: function(xhr, status, error) {
			if (xhr.status == 500) {

			}

		}
	});
}


var group_id = null;

$(document).on('click','.show-group-data',function(){
	var id = $(this).data('id');
	if (group_id == id) {
		return false;
	}

	group_id = id;

	$('.group-metadata-section').hide();

	$('.group-info-loader').show();
	$('.page-default-section').hide();

	const url = base_url+'/user/get-group-metadata';

	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		}
	});
	$.ajax({
		type: 'POST',
		url: url,
		data: {
			id: id,
			device_id: device_id
		},
		dataType: 'json',
		success: function(response) {
			
			$('.group-info-loader').hide();
			

			const group_meta = response.data.data;
			
			$('#group_name').html(group_meta.subject);
			$('#group_owner').html(filter_number(group_meta.owner));
			$('#group_members').html(group_meta.size);

			var participants = '';

			$.each(group_meta.participants, function (index, item) {
				const wa_number = item.id.replace('@s.whatsapp.net','');
				const comma = index == 0  ? '' : ','; 

				participants = participants+comma+wa_number;

			});

			$('#contact-list').val(participants);

			$('.group-metadata-section').show();

		},
		error: function(xhr, status, error) {
			

		}
	});


});

$(document).on('change','.group-id',function(){

	$('.bulk-sent-area').show();

	visibleGroupSentForm();
});

$(document).on('change','#group-message-type',function(){
	if ($(this).val() == 'template') {
		$('.templates-list').show();
		$('#group-bulk-text').hide();
	}
	else{
		$('.templates-list').hide();
		$('#group-bulk-text').show();
	}
});

function visibleGroupSentForm() {
	var selectedLength = $('.group-id:checked').length;

	selectedLength > 0 ? $('.bulk-sent-area-form').show() : $('.bulk-sent-area-form').hide();
}

$('.select-all').on('change',function(){
 	if ($(this).is(':checked')) {
 	   $('.group-id').prop('checked', true);
 	}
 	else{
 		$('.group-id').prop('checked', false);
 	}

 	visibleGroupSentForm();
});


$('.copy-btn').click(function() {
    var textareaValue = $('#contact-list').val();
    
    // Create a temporary textarea element
    var tempTextarea = $('<textarea>');
    
    // Set its value to the textarea value
    tempTextarea.val(textareaValue);
    
    // Append the temporary textarea to the body
    $('body').append(tempTextarea);
    
    // Select the text inside the temporary textarea
    tempTextarea.select();
    
    // Copy the selected text to the clipboard
    document.execCommand('copy');
    
    // Remove the temporary textarea from the body
    tempTextarea.remove();
    
    // Alert the user that the text has been copied
    
    NotifyAlert('success', null, 'Contacts number has been copied!');
  });


function filter_number(text) {
	
	var row = text.replace('@s.whatsapp.net','');

	return row;
}

function successCallBack() {
	$('#plain-text').val('');
}


$(document).on('click','.wa-link',function(){
	group_id = null;
	
	$('.group-metadata-section').hide();
	$('.page-default-section').show();

	const name = $(this).data('name');
	const id = $(this).data('id');

	const activeTarget = $(this).data('active');


	$('.contact').removeClass('active');
	$(activeTarget).addClass('active');

	
	$('.sendble-row').removeClass('none');
	$('.reciver-group').val(name);
	$('.reciver-id').val(id);

});

$(document).on('change','#select-type',function(){
	var type = $(this).val();


	if (type == 'plain-text') {
		$('#plain-text').show();
		$('#templates').hide();
	}
	else{
		$('#plain-text').hide();
		$('#templates').show();
	}


});

function sortByKey(array, key) {
	return array.sort(function(a, b) {
		var x = a[key]; var y = b[key];
		return ((x > y) ? -1 : ((x < y) ? 1 : 0));
	});
}

function formatTimestamp(unixTimestamp) {

	    var d=new Date();  // Gets the current time
	    var nowTs = Math.floor(d.getTime()/1000); //
	    var seconds = nowTs-unixTimestamp;

	    // more that two days
	    if (seconds > 2*24*3600) {
	    	return "a few days ago";
	    }
	    // a day
	    if (seconds > 24*3600) {
	    	return "yesterday";
	    }

	    if (seconds > 3600) {
	    	return "a few hours ago";
	    }
	    if (seconds > 1800) {
	    	return "Half an hour ago";
	    }
	    if (seconds > 60) {
	    	return Math.floor(seconds/60) + " minutes ago";
	    }

	    return "Few seconds ago";

	}