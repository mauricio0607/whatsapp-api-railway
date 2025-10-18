@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',[
'title'=> __('Create Page'),
'buttons'=>[
	[
		'name'=>__('Back'),
		'url'=>route('admin.page.index'),
	]
 ]
])
@endsection
@section('content')

	<div class="row justify-content-center">
		<div class="col-lg-10 card-wrapper">	
			@if(Session::has('error'))
			<div class="alert bg-gradient-danger text-white alert-dismissible fade show success-alert" role="alert">
			<span class="alert-text">{{ Session::get('error') }}</span>
			<button type="button" class="close" data-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">×</span>
			</button>
		   </div>
		   @endif

		   @if(Session::has('success'))
			<div class="alert bg-gradient-success text-white alert-dismissible fade show success-alert" role="alert">
			<span class="alert-text">{{ Session::get('success') }}</span>
			<button type="button" class="close" data-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">×</span>
			</button>
		   </div>
		   @endif

		   @if(!Session::has('update-data'))
			<!-- Alerts -->
			<div class="card">
				<div class="card-header">
					<div class="row w-100">
						<div class="col-6">
							<h3 class="mb-0">{{ __('Site Update') }}</h3>
						</div>

						<div class="col-6">
							<h3 class="float-right"> {{ __('Current Version: '). env('APP_VERSION') }}</h3>
						</div>
					</div>
				</div>
				<div class="card-body">
					<div class="form-group">
						<label>{{ __('Purchase Key') }}</label>
						<input type="text" name="title" readonly="" value="{{ env
						('SITE_KEY') }}" class="form-control">
					</div>
					
		
					<div class="from-group  mt-3">						
						<form class="ajaxform_instant_reload" method="post" action="{{ route('admin.update.store') }}">
							@csrf
							<button class="btn btn-neutral submit-btn"><i class="fi  fi-rs-search-alt"></i> {{ __('Check New Update') }}</button>
						</form>
					</div>
				</div>
			</div>
			@endif
			
			<div class="alert bg-gradient-primary text-white alert-dismissible fade show success-alert" role="alert">
				<span class="alert-text"><strong>{{ __('Note') }}</strong> {{ __('If you have customised the script from codebase do not use this option. you will lose your customization.') }} </span>
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">×</span>
				</button>
			</div>

			@if(Session::has('update-data'))
			<div class="card">
				<div class="card-header">
					<div class="row w-100">
						<div class="col-6">
							<h3 class="mb-0">{{ __('Version') }}</h3>
						</div>

						<div class="col-6">
							<h3 class="float-right"> {{ __('Update') }}</h3>
						</div>
					</div>
				</div>
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-striped table-hover">
							
						<tbody class="list">
							<tr>
								<td>
									v1
								</td>
								<td>								
									<form class="update_form" method="post" action="{{ route('admin.update.update',Session::get('update-data')['version']) }}">
										@csrf
										@method('PUT')
									
									<button class="btn btn-neutral btn-sm float-right submit-btn"><i class="fi fi-rs-download"></i> {{ __('Update now') }}</button>
									</form>
								</td>
							</tr>							
						</tbody>
						
						</table>
					</div>
				</div>
				@if(!empty(Session::get('update-data')['message']))
				<div class="card-footer">
					Note: {{ Session::get('update-data')['message'] ?? '' }}
				</div>
				@endif
			</div>
			@endif

		</div>		
	</div>

@endsection
@push('js')
<script type="text/javascript">
	"use strict";



let $update_form = $('.update_form');
let $updateLoader = '<div class="spinner-border spinner-border-sm" role="status">' +
    '<span class="visually-hidden">please wait...</span>' +
    '</div>';
$update_form.initFormValidation();

$(document).on('submit', '.update_form', function (e) {
	e.preventDefault();

	Swal.fire({
		title: 'Note!',
		text: "Before sent a request for update please take a backup of your site with database.",
		icon: 'warning',
		confirmButtonText: 'Procced for update',
		showCancelButton: true,
		confirmButtonColor: '#6777ef',
		cancelButtonColor: '#fc544b',
	}).then((result) => {
		if (result.value) {

			 let $this = $(this);
    let $submitBtn = $this.find('.submit-btn');
    let $oldSubmitBtn = $submitBtn.html();

    if ($update_form.valid()) {
        $.ajax({
            type: 'POST',
            url: this.action,
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                $submitBtn.html($updateLoader).addClass('disabled').attr('disabled', true);
            },
            success: function (res) {
                $submitBtn.html($oldSubmitBtn).removeClass('disabled').attr('disabled', false);
                window.sessionStorage.hasPreviousMessage = true;
                window.sessionStorage.previousMessage = res.message ?? null;

                if (res.redirect) {
                    location.href = res.redirect;
                }
            },
            error: function (xhr) {
                $submitBtn.html($oldSubmitBtn).removeClass('disabled').attr('disabled', false);
                NotifyAlert('error', xhr.responseJSON);
                showInputErrors(xhr.responseJSON);
            }
        });
      }
		}
	});
});

</script>
@endpush