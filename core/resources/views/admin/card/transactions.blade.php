@extends('admin.layouts.app')
@section('panel')
    @php
        $request = request();
        $tableName = 'vcard_transactions';
        $tableConfiguration = tableConfiguration($tableName);
        $columns = collect([
            prepareTableColumn('trx', 'Trx No.'),
            prepareTableColumn('created_at', 'Transacted At','showDateTime($item->created_at)', filter: 'date'),
            prepareTableColumn('card_details', 'Virtual Card', filter:'select', filterColumn:'virtual_card_id', filterOptions: $cards),
            prepareTableColumn('transaction_type', 'Transaction Type', filter: 'select', filterOptions: ['Debited', 'Credited'], className: '$item->trx_type=="+"? "text--success fw-bold": "text--danger fw-bold"'),
            prepareTableColumn('amount', 'Amount', 'showAmount($item->amount)'),
            prepareTableColumn('post_balance', 'Post Balance', 'showAmount($item->post_balance)'),
            prepareTableColumn('details', 'Details')
        ]);

        if ($tableConfiguration) {
            $visibleColumns = $tableConfiguration->visible_columns;
        } else {
            $visibleColumns = $columns->pluck('id')->toArray();
        }

        $action = ['show' => false];
    @endphp

<x-viser_table.table :action="$action" :data="$transactions" :columns="$columns" :columnConfig="true" :tableName="$tableName" :visibleColumns="$visibleColumns" class="table-responsive--md table-responsive"/>
@endsection
