@props(['renderColumns', 'action'])


<thead>
    <tr>
        @if ($action['show'])
            <th>
                {{ __($action['name']) }}
            </th>
        @endif

        @foreach ($renderColumns as $column)
            <th data-key="{{ $column['id'] }}">{{ __($column['name']) }}</th>
        @endforeach
    </tr>
</thead>
