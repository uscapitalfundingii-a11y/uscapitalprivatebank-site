@extends('admin.layouts.app')
@section('panel')
    @php
        $request = request();
        $tableName = 'users_list';
        $branches = App\Models\Branch::orderBy('name')->get()->pluck('name')->toArray();
        $branches[] = 'Online';

        $tableConfiguration = $tableConfiguration = tableConfiguration($tableName);
        $countries = json_decode(file_get_contents(resource_path('views/partials/country.json')));

        $countries = collect($countries)->map(function($country){
            return $country->country;
        });

        $countryOptions = array_values($countries->toArray());

        $columns = collect([
            prepareTableColumn('account_number', 'Account No.'),
            prepareTableColumn('username', 'Username'),
            prepareTableColumn('fullname', 'Name'),
            prepareTableColumn('email', 'Email'),
            prepareTableColumn('mobile', 'Mobile'),
            prepareTableColumn('country_name', 'Country', filter: 'select', filterOptions: $countryOptions),
            prepareTableColumn('state', 'State', filter: 'text'),
            prepareTableColumn('city', 'City', filter: 'text'),
            prepareTableColumn('zip', 'Zip', filter: 'text'),
            prepareTableColumn('branch_name', 'Branch', filter: 'select', filterColumn: 'branch_name', filterOptions: $branches),
            prepareTableColumn('balance', 'Balance', 'showAmount($item->balance)', filter: 'range'),
            prepareTableColumn('created_at', 'Registered At', 'showDateTime("$item->created_at", "d M, Y")', filter: 'date')
        ]);

        $action = [
            'name' => 'Action',
            'style' => 'dropdown',
            'show' => can('admin.users.detail') || can('admin.users.kyc.details') || can('admin.users.login') || can('admin.report.login.history') || can('admin.users.notification.log') || can('admin.users.notification.single'),
            'buttons' => [
                [
                    'name' => 'View Details',
                    'link' => 'route("admin.users.detail", $item->id)',
                    'show' => can('admin.users.detail'),
                ],
                [
                    'name' => 'View KYC Data',
                    'link' => 'route("admin.users.kyc.details", $item->id)',
                    'show' => can('admin.users.kyc.details'),
                ],
                [
                    'name' => 'Login As User',
                    'link' => 'route("admin.users.login", $item->id)',
                    'show' => can('admin.users.login'),
                    'attributes' => [
                        'target' => "json_encode('blank')"
                    ]
                ],
                [
                    'name' => 'Login History',
                    'link' => 'route("admin.report.login.history", $item->id)',
                    'show' => can('admin.report.login.history'),
                ],
                [
                    'name' => 'Send Notification',
                    'link' => 'route("admin.users.notification.single", $item->id)',
                    'show' => can('admin.users.notification.single'),
                ],
                [
                    'name' => 'All Notifications',
                    'link' => 'route("admin.users.notification.log", $item->id)',
                    'show' => can('admin.users.notification.log'),
                ],
            ],
        ];

        if($tableConfiguration){
            $visibleColumns = $tableConfiguration->visible_columns;
        }else{
            $visibleColumns = $columns->pluck('id')->toArray();
        }
    @endphp

    <x-viser_table.table :data="$users" :columns="$columns" :action="$action" :columnConfig="true" :tableName="$tableName" :visibleColumns="$visibleColumns" class="table-responsive--md table-responsive">
        @if (request()->routeIs('admin.users.banned'))
            <x-slot:tbody>
                <tbody>
                    @forelse ($users as $item)
                        <tr>
                            <td>
                                <div class="dropdown">
                                    <button aria-expanded="false" class="btn btn-sm btn--light" data-bs-toggle="dropdown" type="button">
                                        <i class="las la-ellipsis-v m-0"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a href="{{ route('admin.users.detail', $item->id) }}" class="dropdown-item">
                                            @lang('View Details')
                                        </a>
                                        @can('admin.users.status')
                                            <button
                                                type="button"
                                                class="dropdown-item text--success unban-user-btn"
                                                data-action="{{ route('admin.users.status', $item->id) }}"
                                                data-name="{{ $item->fullname }}"
                                                data-account="{{ $item->account_number }}"
                                            >
                                                @lang('Unban Account')
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </td>
                            <x-viser_table.table-data-columns :renderColumns="$columns->whereIn('id', $visibleColumns)" :item="$item" />
                        </tr>
                    @empty
                        <tr>
                            <td class="text-muted text-center" colspan="100%">@lang('No data found')</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-slot:tbody>
        @endif
    </x-viser_table.table>

    @can('admin.users.status')
        <div class="modal fade" id="unbanUserModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('Unban Account')</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                    <form id="unbanUserForm" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p class="mb-1">@lang('This will remove the banned status from this account.')</p>
                            <p class="mb-0"><strong id="unbanUserLabel"></strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn--dark" data-bs-dismiss="modal">@lang('Cancel')</button>
                            <button type="submit" class="btn btn--success">@lang('Unban')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@if($users->total() > 0 && can('admin.users.notification.all.send'))
@push('breadcrumb-plugins')
    <a href="{{appendQuery('notify', 1)}}" class="btn btn--dark">
        <i class="fas fa-bell"></i>
        @lang('Notify') <strong class="mx-1">{{$users->total()}}</strong> {{__(str_replace('All', '' ,$pageTitle))}} @lang('Holders')
        @if($request->has('filter'))(@lang('Filtered'))@endif
    </a>
    @endpush
@endif

@can('admin.users.status')
    @push('script')
        <script>
            (function($) {
                "use strict";

                const modal = $('#unbanUserModal');
                const form = $('#unbanUserForm');
                const label = $('#unbanUserLabel');

                $('.unban-user-btn').on('click', function() {
                    const action = $(this).data('action');
                    const name = $(this).data('name');
                    const account = $(this).data('account');

                    form.attr('action', action);
                    label.text(`${name} (${account})`);
                    modal.modal('show');
                });
            })(jQuery);
        </script>
    @endpush
@endcan
