<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ gs()->siteName($pageTitle ?? '') }}</title>
    <!-- favicon -->
    <link rel="shortcut icon" type="image/png" href="{{ siteFavicon() }}">

    <link rel="stylesheet" href="{{ asset('assets/global/css/pdf.css') }}">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto+Mono:ital,wght@0,100..700;1,100..700&display=swap');

        @font-face {
            font-family: "mono";
            src: url({{ asset('asset/pdf_font/RobotoMono-Light.ttf') }}) format("opentype");
            font-weight: normal;
            font-style: normal;
        }

        .table>thead {
            vertical-align: bottom;
            background: none;
            color: rgb(56, 56, 56);
            border-top: 1px dashed #838383 !important;
            border-bottom: 1px dashed #838383 !important;
            border-left: none;
            border-right: none;
        }

        table td {
            background: none !important;
        }

        table {
            border: none !important;
            font-family: "mono", monospace;
        }

        table td {
            padding: 8px 10px;
        }

        table tbody {
            border: none !important;
        }

        table tr td.border-top {
            border-top: 1px dashed #858585 !important;
        }

        .devide-two {
            padding-bottom: 5px;
        }

        .devide-two span,
        .devide-two b {
            display: inline-block;
        }

        .devide-two b:first-child {
            min-width: 100px !important;
        }

        .bank-location .bank-address {
            display: inline-block;
            padding-bottom: 10px;
        }

        .account-info .devide-two span:last-child {
            font-family: "mono", monospace;
        }

        .account-address {
            font-family: "mono", monospace;
            display: block !important;
        }

        .statement_box {
            padding: 8px 0;
        }

        .left_side {
            float: left;
            width: 50%;
        }

        .right_side {
            float: right;
            width: 45%;
        }

        .logo_box {
            padding-bottom: 15px;
        }

        .logo_box img {
            max-width: 210px;
        }

        b {
            font-weight: 500;
        }

        .transaction-detail {
            max-width: 180px;
        }
    </style>

    @stack('style')
</head>

<body>
    <main>
        @php
            $contact = getContent('contact_us.content', true);
        @endphp
        <div class="list--row">
            <div class="left_side">
                <div class="logo_box">
                    <img src="{{ siteLogo('dark') }}" class="logo" alt="Logo">
                </div>
                <div class="bank-location">
                    <span class="bank-address">
                        {{ __(@$contact->data_values->contact_address) }}
                    </span>
                    <div class="devide-two">
                        <b>@lang('Email') </b>
                        <span>: {{ __(@$contact->data_values->email_address) }}</span>
                    </div>
                    <div class="devide-two">
                        <b>@lang('Mobile Number')</b>
                        <span>: {{ __(@$contact->data_values->contact_number) }}</span>
                    </div>
                    <div class="devide-two">
                        <b>@lang('Website') </b>
                        <span>: {{ request()->getHost() }} </span>
                    </div>
                </div>
            </div>
            <div class="right_side">
                <div class="bank-location account-info">
                    <b class="bank-address">
                        {{ __($user->fullname) }}
                    </b>
                    <span class="bank-address account-address">
                        {{ __($user->address) }}{{ $user->city ? ',' . __($user->city) : '' }}{{ $user->country_name ? ',' . __($user->country_name) : '' }}
                    </span>
                    <div class="devide-two">
                        <b>@lang('Accoun No')</b>
                        <span>:{{ $user->account_number }}</span>
                    </div>
                    <div class="devide-two">
                        <b>@lang('Branch')</b>
                        <span>:{{ $user->branch->name ?? 'Online' }}</span>
                    </div>
                    <div class="devide-two">
                        <b>@lang('Username') </b>
                        <span>:{{ $user->username }}</span>
                    </div>
                    <div class="devide-two">
                        <b>@lang('Issue Date') </b>
                        <span>:{{ showDateTime(now(), 'F d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="statement_box">
            <b>@lang('STATEMENT OF ACCOUNT FOR THE PERIOD :') {{ strtoupper(request()->date) }}</b>
        </div>

        <div class="body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>@lang('DATE')</th>
                        <th>@lang('DETAILS')</th>
                        <th>@lang('DEBIT')</th>
                        <th>@lang('CREDIT')</th>
                        <th>@lang('BALANCE')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transactions as $transaction)
                        <tr>
                            <td>
                                {{ showDateTime($transaction->created_at, 'd-M-Y') }}
                            </td>
                            <td class="transaction-detail">
                                {{ $transaction->details }}
                            </td>
                            <td>
                                {{ $transaction->trx_type == '-' ? showAmount($transaction->amount) : '-' }}
                            </td>

                            <td>{{ $transaction->trx_type == '+' ? showAmount($transaction->amount) : '-' }}</td>

                            <td>{{ showAmount($transaction->post_balance) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td>
                        </td>
                        <td>
                        </td>
                        <td class="border-top">
                            {{ showAmount($minusSumAmount) }}
                        </td>
                        <td class="border-top">{{ showAmount($plusSumAmount) }}</td>
                        <td class="border-top">{{ showAmount($transactions->last()->post_balance) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <footer>
        <div class="d-block text-center">
            @lang('Powered by') {{ __(gs()->site_name) }}
        </div>
    </footer>
</body>

</html>
