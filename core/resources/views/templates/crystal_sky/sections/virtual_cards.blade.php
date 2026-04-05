@php
    $content = getContent('virtual_cards.content', true);
@endphp

<div class="virtual-card-section py-120">
    <div class="container">
        <div class="row gy-4 g-lg-5 flex-wrap-reverse">
            <div class="col-lg-6">
                <div class="virtual-card__thumb">
                    <img src="{{ frontendImage('virtual_cards', @$content->data_values->image, '1250x920') }}" alt="img">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="virtual-card-content">
                    <div class="section-heading style-left">
                        <h6 class="section-heading__subtitle">{{ __(@$content->data_values->heading) }}</h6>
                        <h2 class="section-heading__title">{{ __(@$content->data_values->subheading) }}</h2>
                    </div>
                    <div class="virtual-card-content__dec">
                        @php echo @$content->data_values->description @endphp
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
