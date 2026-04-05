@push('script')
    <script>
        $(document).ready(function() {
            const cardId = @json($card->card_id);
            const dbCardId = @json($card->id);

            const $card = $('.v--card');
            const $numberSpan = $card.find('.v--card__number span');
            const $cvcSpan = $card.find('.v--card__cvc span');
            const $pinSpan = $card.find('.v--card__pin span');
            const $icon = $('.show-full-card').find('i');
            const last4 = $numberSpan.attr('data-last4');

            let card_number = null;
            let card_cvc = null;
            let card_pin = null;


            $(document).on('click', '.show-full-card', function() {

                const $numberSpan = $('.v--card').find('.v--card__number span');
                const isHidden = Boolean($numberSpan.attr('data-hidden'));

                // If card details are visible, hide them
                if (!isHidden) {
                    hideSecret();
                    return;
                }

                // if already loaded then show
                if (card_number && card_cvc && card_pin) {
                    showSecret();
                    return;
                }

                $('#passwordModal').modal('show');
            });

            $(document).on('submit', '.revealSecretForm', async function(e) {
                e.preventDefault();

                const URL = $(this).attr('action');
                $(this).find('[type=submit]').html(`<i class="fas fa-spinner fa-spin"></i>`);

                try {
                    let resp = await fetchCardDetails(URL);
                    card_number = resp.card_number;
                    card_cvc = resp.card_cvc;
                    card_pin = resp.card_pin;

                    showSecret();
                    $('#passwordModal').modal('hide');

                } catch (error) {
                    notify('error', error.message);
                    $(this).trigger('reset');
                    $(this).find('[type=submit]').html(`@lang('Submit')`).prop('disabled', false);
                }
            });

            async function fetchCardDetails(url) {
                let password = $('.revealSecretForm').find('[name=password]').val();
                const cardResponse = await fetch(`${url}?password=${password}`, {
                    method: 'GET'
                });

                if (!cardResponse.ok) {
                    throw new Error('Failed to get card details');
                }

                let response = await cardResponse.json();
                if (!response.status) {
                    throw new Error(response.message);
                }

                return response.data;

            }

            function hideSecret() {
                $numberSpan.text(`**** **** **** ${last4}`).attr('data-hidden', 'true');
                $cvcSpan.text('***').attr('data-hidden', 'true');
                $pinSpan.text('****').attr('data-hidden', 'true');
                $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            }

            function showSecret() {
                const formattedNumber = card_number.replace(/\s/g, '').replace(/(.{4})/g, '$1 ').trim();

                $numberSpan.text(formattedNumber).removeAttr('data-hidden');
                $cvcSpan.text(card_cvc).removeAttr('data-hidden');
                $pinSpan.text(card_pin).removeAttr('data-hidden');
                $icon.removeClass('fas fa-spinner fa-spin').addClass('fa fa-eye');
            }
        });
    </script>
@endpush
