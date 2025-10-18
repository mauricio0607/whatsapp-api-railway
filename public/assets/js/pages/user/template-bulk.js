"use strict";	

var slider = document.getElementById('slider');
var startSec = 10;
var endSec = 60;

noUiSlider.create(slider, {
	start: [10, 60],
	connect: true,
	range: {
		'min': 1,
		'max': 200
	}
});

slider.noUiSlider.on('update', function(values, handle) {
	console.log(values)
	startSec = values[0];
	endSec = values[1];

	$('#start_time').html(startSec)
	$('#end_time').html(endSec)

});

var sendble_rows=[];

	$(document).on('click','.send_now',function(){
		var forms = $('.bulk_form').length;
	
		if (forms > 0) {
			$('.send_now').attr('disabled','disable');
			
			$('.bulk_form').each(function(index, row){
				sendble_rows.push(`.form-`+$(this).data('key'));
			});
			// $('.bulk_form').each(function(index, item){
			// 	console.log($(this).data('key'))
			// 	submitRequests(sendble_rows[0]);
			// })
			submitRequests(sendble_rows[0]);
			console.log(sendble_rows)
		}
		else{
			$('.send_now').removeAttr('disabled');
			ToastAlert('error', 'No Record Avaible For Sent A Request');
		}

	});

	$(document).on('click','.delete-form',function(){		
		const row= $(this).data('action');
		$(row).remove();
		
		var totalRecords = $('#total_records').text();
		totalRecords = parseInt(totalRecords)
		totalRecords = totalRecords-1;
		$('.total_records').html(parseInt(totalRecords))
	});

	$('.send-message').on('click',function(){
		var formclass = $(this).data('form');
		$(formclass).submit();
	});

	var totalSent = $('.msg-sent').length;
	$('.total_sent').html(totalSent);

	function submitRequests(targetForm) {
		
		var formData = {};
		$.each($(targetForm).serializeArray(), function(i, field) {
			formData[field.name] = field.value;

		});

		
	
		 

		 let $savingLoader = 'Please wait...';

		 let $this = $(targetForm);
		 let $submitBtn = $this.find('.submit-button');
		 let $oldSubmitBtn = $submitBtn.html();
		 const key = $(targetForm).data('key');

			 $.ajaxSetup({
			 	headers: {
			 		'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			 	}
			 });
		 
		 	$.ajax({
		 		type: 'POST',
		 		url: $(targetForm).attr('action'),
		 		data: formData,
		 		dataType: 'json',
		 		
		 		beforeSend: function () {
		 			$submitBtn.html($savingLoader).attr('disabled', true);
		 			$('.badge_'+key).html('Sending.....')
		 		},
		 		success: function (res) {
		 			$submitBtn.html($oldSubmitBtn).attr('disabled', false);
		 			$('.badge_'+key).html('Sent 🚀');
		 			
		 			$('.badge_'+key).removeClass('badge-warning');
		 			$('.badge_'+key).addClass('badge-success');
		 			$('.badge_'+key).removeClass('sendable');
		 			$('.badge_'+key).addClass('msg-sent');
		 			$('.badge_'+key).removeClass('faild-form');
		 			
		 			
		 			NotifyAlert('success', res);

		 			arrayRemove(targetForm);

		 			if (sendble_rows.length != 0) {
		 				var randomTime = Math.floor(Math.random() * Math.floor(endSec - startSec + 1)) + startSec/2;
		 				
		 				setTimeout(function() {
		 					
		 					submitRequests(sendble_rows[0]);
		 					console.log(randomTime)
		 					
		 				}, randomTime * 1000);
		 			}
		 			totalSent++;
		 			$('.total_sent').html(totalSent);
		 		},
		 		error: function (xhr) {
		 			$submitBtn.html($oldSubmitBtn).attr('disabled', false);
		 			
		 			$('.badge_'+key).html('Sending Faild');
		 			$('.badge_'+key).addClass('badge-danger');
		 			$('.badge_'+key).addClass('faild-form');

		 			$('.total-faild').html($('.faild-form').length);
		 			NotifyAlert('error', xhr);

		 			arrayRemove(targetForm);
		 			if (sendble_rows.length != 0) {
		 				var randomTime = Math.floor(Math.random() * Math.floor(endSec - startSec + 1)) + startSec/2;
		 				
		 				setTimeout(function() {
		 					
		 					submitRequests(sendble_rows[0]);
		 					console.log(randomTime)
		 					
		 				}, randomTime * 1000);
		 			}
		 			totalSent++;
		 			$('.total_sent').html(totalSent);
		 		}
		 	});
		 

	}

function arrayRemove(value) { 
	
	sendble_rows =  sendble_rows.filter(function(ele){ 
		return ele != value; 
	});
}

