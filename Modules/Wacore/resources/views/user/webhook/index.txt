@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> 'Webhooks log reports'])
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
									{{ number_format($total) }}
								</span>
							</div>
							<div class="col-auto">
								<div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
									<i class="fi fi-rs-rocket-lunch mt-2"></i>
								</div>
							</div>
						</div>
						<p class="mt-3 mb-0 text-sm">
						</p><h5 class="card-title  text-muted mb-0">{{ __('Total Payloads') }}</h5>
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
									{{ number_format($sent_hooks)  }}
								</span>
							</div>
							<div class="col-auto">
								<div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
									<i class="fi fi-rs-calendar-day mt-2"></i>
								</div>
							</div>
						</div>
						<p class="mt-3 mb-0 text-sm">
						</p><h5 class="card-title  text-muted mb-0">{{ __('Sent Hooks') }}</h5>
						<p></p>
					</div>
				</div>
			</div>
			<div class="col">
				<div class="card card-stats">
					<div class="card-body">
						<div class="row">
							<div class="col">
								<span class="h2 font-weight-bold mb-0 completed-transfers" id="total-inactive">
									{{ number_format($failed) }}
								</span>
							</div>
							<div class="col-auto">
								<div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
									<i class="ni ni-calendar-grid-58"></i>
								</div>
							</div>
						</div>
						<p class="mt-3 mb-0 text-sm">
						</p><h5 class="card-title  text-muted mb-0">{{ __('Failed Hooks') }}</h5>
						<p></p>
					</div>
				</div>
			</div>
		</div>

		@if(count($hooks ?? []) == 0)
		<div class="row">
			<div class="col-sm-12">
				<div class="card">
					<div class="card-body">
						<center>
							<img src="{{ asset('assets/img/404.jpg') }}" height="500">
							<h3 class="text-center">{{ __('!Opps no records found') }}</h3>
						</center>
					</div>
				</div>
			</div>
		</div>
		@else
		<div class="card">			
			<div class="card-body">
				<div class="row">
					<div class="col-sm-12 table-responsive">
						<table class="table col-12">
							<thead>
								<tr>
									<th>{{ __('Device') }}</th>
									<th>{{ __('Hook Url') }}</th>
									<th>{{ __('Payload') }}</th>
									<th>{{ __('Status') }}</th>
									<th>{{ __('Http Status') }}</th>
									<th class="text-right">{{ __('Requested At') }}</th>
								</tr>
							</thead>
							<tbody class="tbody">
								@foreach($hooks ?? [] as $hook)
								<tr>
									<td>
										{{ $hook->device->name ?? 'None' }}
									</td>
									<td>
										{{ $hook->hook }}
									</td>
									<td>
										<textarea class="form-control">{{ $hook->payload }}</textarea>
									</td>
									<td><span class="badge badge-{{ $hook->status == 1 ? 'success' : ($hook->status == 2 ? 'warning' : 'danger') }}">{{ $hook->status == 1 ? 'Sent' : ($hook->status == 2 ? 'pending' : 'Failed') }}</span></td>
									<td>
										{{$hook->status_code}}
									</td>
									
									<td class="text-right">
										{{ $hook->created_at->format('d F Y') }}
									</td>
								</tr>
								@endforeach
							</tbody>
						</table>
						<div class="d-flex justify-content-center">{{ $hooks->links('vendor.pagination.bootstrap-4') }}</div>
					</div>
				</div>
			</div>
		</div>
		@endif
	</div>
</div>


@endsection
