@props([
    'name' => 'Name will be here',
    'last4' => '1234',
    'expire_month' => '06',
    'expire_year' => '24',
    'status' => 'inactive',
    'id' => '1234567890',
    'hide_status' => false,
    'hide_eye' => false,
    'placeholder' => false,
    'link' => false,
])

@if ($placeholder)
    <div class="v--card placeholder d-flex align-items-center justify-content-center flex-column gap-3">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 11h-6V5a1 1 0 0 0-2 0v6H5a1 1 0 0 0 0 2h6v6a1 1 0 0 0 2 0v-6h6a1 1 0 0 0 0-2Z" />
        </svg>
        <h5 class="mb-0">@lang('Issue New Card')</h5>
    </div>
@else
    <div @if ($link) data-bs-toggle="tooltip" data-bs-placement="top" title="@lang('View Details')" @endif class="v--card" style="--color-code: #{{ gs('branding_config')?->text_color }};--card-background: url({{ getImage(getFilePath('cardBackground') . '/' . gs('branding_config')->background) }})">
        <div class="v--card__header">
            <div class="v--card__brand">
                <span class="fw-bolder fs-italic">@lang('VISA')</span>
            </div>
        </div>
        <div class="v--card__details d-flex justify-content-between align-items-start">
            <div>
                <p class="v--card__name m-0">{{ $name }}</p>
                <div class="v--card__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="24" height="24" x="0" y="0" viewBox="0 0 609.928 609.928" style="enable-background:new 0 0 512 512" xml:space="preserve" class="">
                        <g>
                            <path fill="#ffc738" d="M570.267 542.516H39.542C17.746 542.516 0 524.769 0 502.854V106.955c0-21.796 17.746-39.542 39.542-39.542h530.605c21.915 0 39.661 17.746 39.661 39.542v395.899c.001 21.915-17.745 39.662-39.541 39.662z" opacity="1" data-original="#ffc738" class=""></path>
                            <path fill="#ffb42e" d="M570.267 67.412H304.904v475.103h265.362c21.915 0 39.542-17.746 39.542-39.542V106.955c.001-21.796-17.745-39.543-39.541-39.543z" opacity="1" data-original="#ffb42e" class=""></path>
                            <path fill="#c66d4e" d="M609.809 313.123v-16.317H396.733v-72.057l55.026-55.026h158.05c0-5.598-.596-11.077-1.548-16.317H448.305c-2.144 0-4.288.953-5.717 2.501l-57.408 57.289h-72.057V67.412h-16.317v145.663h-71.224l-57.289-57.289c-1.548-1.548-3.692-2.501-5.836-2.501H1.667c-.953 5.36-1.548 10.838-1.548 16.317h159.003l53.954 53.954v73.01H0v16.317h213.076v73.01l-53.954 54.311H.119c0 5.598.596 11.077 1.548 16.317h160.909c2.144 0 4.288-.953 5.836-2.501l57.289-57.289h71.224v145.663h16.317V396.733h72.057l57.408 57.289c1.429 1.548 3.573 2.501 5.717 2.501H608.38c.953-5.36 1.548-10.838 1.548-16.317H451.64l-55.026-55.026v-72.057zm-229.393 67.293H229.393V229.393h150.904v151.023z" opacity="1" data-original="#c66d4e" class=""></path>
                            <path fill="#af5a35" d="M609.809 313.123v-16.317H396.733v-72.057l55.026-55.026h158.05c0-5.598-.596-11.077-1.548-16.317H448.305c-2.144 0-4.288.953-5.717 2.501l-57.408 57.289h-72.057V67.412h-8.218v161.98h75.512v151.023h-75.512v161.981h8.218V396.733h72.057l57.408 57.289c1.429 1.548 3.573 2.501 5.717 2.501h159.956c.953-5.36 1.548-10.719 1.548-16.317H451.64l-55.026-55.026v-72.057z" opacity="1" data-original="#af5a35" class=""></path>
                        </g>
                    </svg>
                </div>
            </div>
            <div>
                <p class="v--card__expiry">@lang('Expires'): {{ $expire_month }}/{{ $expire_year }}</p>
                <div class="d-flex gap-3">
                    <p class="v--card__cvc">@lang('CVC'): <span data-hidden="true">***</span></p>
                    <p class="v--card__pin">@lang('Pin'): <span data-hidden="true">****</span></p>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-end">
            <div class="v--card__number">
                <span data-last4="{{ $last4 }}" data-hidden="true">**** **** **** {{ $last4 }}</span>
            </div>
            @if (!$hide_eye)
                <button class="show-full-card" data-card-id="{{ $id }}" class="v--card__reveal"><i class="fa fa-eye-slash"></i></button>
            @endif
        </div>
        @if (!$hide_status)
            <div class="v--card__status {{ $status === 'active' ? 'v--card__status--active' : 'v--card__status--inactive' }}">
                {{ ucfirst($status) }}
            </div>
        @endif
    </div>
@endif
