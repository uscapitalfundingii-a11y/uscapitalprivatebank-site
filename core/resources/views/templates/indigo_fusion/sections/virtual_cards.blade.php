@php
    $content = getContent('virtual_cards.content', true);
@endphp
<div class="virtual-card-section section--bg pt-100 pb-100">
    <div class="container">
        <div class="row gy-4 g-lg-5">
            <div class="col-xl-5 col-lg-6">
                <div class="virtual-card-content">
                    <div class="section-header">
                        <div class="section-top-title border-left text--base">{{ __(@$content->data_values->heading) }}</div>
                        <h2 class="section-title">{{ __(@$content->data_values->subheading) }}</h2>
                    </div>
                    <div class="virtual-card-content__dec">
                        @php echo @$content->data_values->description @endphp
                    </div>
                </div>
            </div>
            <div class="col-xl-7 col-lg-6">
                <div class="virtual-card__thumb">
                    <img src="{{ frontendImage('virtual_cards', @$content->data_values->image, '1250x920') }}" alt="img">
                </div>
            </div>
        </div>
    </div>
</div>
