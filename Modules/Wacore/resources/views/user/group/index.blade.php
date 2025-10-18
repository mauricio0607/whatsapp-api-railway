@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',[
'title' => __('Group List'),
'buttons' =>[
	[
		'name'=>__('Create Group'),
		'url'=>'#',
		'components'=>'data-toggle="modal" data-target="#create-modal" ',
		'is_button'=>true
	],
	[
		'name'=>__('Contact List'),
		'url'=> route('user.contact.index'),
	],
]])
@endsection

@section('content')
<div class="row justify-content-center">
	<div class="col-12">
		<div class="row d-flex justify-content-between flex-wrap">
			<div class="col">
				<div class="card card-stats">
					<div class="card-body">
						<div class="row">
							<div class="col">
								<span class="h2 font-weight-bold mb-0 total-transfers" id="total-device">
									{{ $total_groups ?? 0 }}
								</span>
							</div>
							<div class="col-auto">
								<div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
									<i class="fi  fi-rs-address-book mt-2"></i>
								</div>
							</div>
						</div>
						<p class="mt-3 mb-0 text-sm">
						</p><h5 class="card-title  text-muted mb-0">{{ __('Total Groups') }}</h5>
						<p></p>
					</div>
				</div>
			</div>
			<div class="col">
				<div class="card card-stats">
					<div class="card-body">
						<div class="row">
							<div class="col">
								<span class="h2 font-weight-bold mb-0 total-transfers" id="total-active">
								  {{ $limit ?? 0 }}
								</span>
							</div>
							<div class="col-auto">
								<div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
									<i class="fas fa-signal"></i>
								</div>
							</div>
						</div>
						<p class="mt-3 mb-0 text-sm">
						</p><h5 class="card-title  text-muted mb-0">{{ __('Groups statics') }}</h5>
						<p></p>
					</div>
				</div>
			</div>
			
		</div>

		@if(count($groups ?? []) > 0)
		<div class="card">
			
			<div class="card-body">
				<div class="row">
					<div class="col-sm-12 table-responsive">
						<table class="table col-12">
							<thead>
								<tr>
									<th class="col-3">{{ __('Group Name') }}</th>
									<th class="col-7 text-right">{{ __('Total Contact Numbers') }}</th>
									
									<th class="col-2 text-right">{{ __('Action') }}</th>
								</tr>
							</thead>
							<tbody class="tbody">
								@foreach($groups ?? [] as $group)
								<tr>
									<td>
										{{ $group->name }}
									</td>
									<td class="text-right">
										{{ $group->groupcontacts_count }}
									</td>									
									<td>
										<div class="btn-group mb-2 float-right">
											<button class="btn btn-neutral btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
												{{ __('Action') }}
											</button>
											<div class="dropdown-menu">
												<a class="dropdown-item has-icon edit-contact" href="#" 
												data-action="{{ route('user.group.update',$group->id) }}" 
												data-name="{{ $group->name }}"  
												
												data-toggle="modal" 
												data-target="#editModal"
												>
												<i class="ni ni-align-left-2"></i>{{ __('Edit') }}</a>
												<a class="dropdown-item has-icon delete-confirm" href="javascript:void(0)" data-action="{{ route('user.group.destroy',$group->id) }}"><i class="fas fa-trash"></i>{{ __('Remove Number') }}</a>
											</div>
										</div>
									</td>
								</tr>
								@endforeach
							</tbody>
						</table>
						<div class="d-flex justify-content-center">{{ $groups->links('vendor.pagination.bootstrap-4') }}</div>
					</div>
				</div>
			</div>
		</div>
		@else
		<div class="alert  bg-gradient-primary text-white"><span class="text-left">{{ __('Opps There Is No Groups Found....') }}</span></div>
		@endif
	</div>
</div>

<div class="modal fade" id="create-modal" tabindex="-1" aria-labelledby="create-modal" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form type="POST" action="{{ route('user.group.store') }}" class="edit-modal ajaxform_instant_reload">
				@csrf
				

				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">{{ __('Create Group') }}</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label>{{ __('Group Name') }}</label>
						<input type="text" name="name" placeholder="{{ __('Enter your group name') }}" maxlength="50" class="form-control" required="">
					</div>
					

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
					<button type="submit" class="btn btn-primary submit-btn">{{ __('Save') }}</button>
				</div>
			</form>
		</div>
	</div>
</div>


<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="edit-modal" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form type="POST" action="" class="edit-modal ajaxform_instant_reload">
				@csrf
				@method("PUT")
				

				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">{{ __('Edit Group') }}</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label>{{ __('Group Name') }}</label>
						<input type="text" name="name" placeholder="{{ __('Enter your group name') }}" maxlength="50" id="groupname" class="form-control groupname" required="">
					</div>
					

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
					<button type="submit" class="btn btn-primary submit-btn">{{ __('Save') }}</button>
				</div>
			</form>
		</div>
	</div>
</div>


@endsection
@push('js')

<script type="text/javascript">
	$('.edit-contact').on('click',function(){
		const name = $(this).data('name');
		const action = $(this).data('action');

		$('.groupname').val(name);
		
		$('.edit-modal').attr('action',action);
	});
</script>
@endpush
