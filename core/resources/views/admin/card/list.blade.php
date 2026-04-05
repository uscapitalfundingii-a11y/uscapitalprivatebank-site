@extends('admin.layouts.app')
@section('panel')
@php
    $request = request();
    $tableName = 'vcards';
    $tableConfiguration = tableConfiguration($tableName);

    $columns = collect([
        prepareTableColumn('name', 'Name'),
        prepareTableColumn('last4', 'Card Number', '"**** **** **** ". $item->last4'),
        prepareTableColumn('exp_month', 'Exp Month'),
        prepareTableColumn('exp_year', 'Exp Year'),
        prepareTableColumn('created_at', 'Created At', 'showDateTime($item->created_at)'),
        prepareTableColumn('status', 'Status', '$item->status_badge', echoable:true)
    ]);

    $action = [
            'name' => 'Action',
            'style' => '',
            'show' => can('admin.card.detail'),
            'buttons' => [
                [
                    'name' => 'Details',
                    'show' => 'can("admin.card.detail")',
                    'link' => 'route("admin.card.detail", $item->id)',
                    'icon' => 'la la-desktop',
                    'class' => 'btn btn-outline--primary'
                ]
            ],
        ];

    if ($tableConfiguration) {
        $visibleColumns = $tableConfiguration->visible_columns;
    } else {
        $visibleColumns = $columns->pluck('id')->toArray();
    }
@endphp

<x-viser_table.table :data="$cards" :action="$action" :columns="$columns" :columnConfig="true" :tableName="$tableName" :visibleColumns="$visibleColumns" class="table-responsive--md table-responsive"/>

@endsection
