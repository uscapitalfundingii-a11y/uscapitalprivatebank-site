$(function() {


    $('#kt_login_signin_submit').attr('disabled', true);

    $('#password').keyup(function() {
        if($(this).val().length == 0 || $('#email').val().length == 0) {
            $('#kt_login_signin_submit').attr('disabled', true);
        } else {
            $('#kt_login_signin_submit').attr('disabled', false);
        }
    });

    $('#login_formulare').submit(function() {
        $('#kt_login_signin_submit').attr('disabled', true);
    });

    if($('#payment_error').length) {

        Swal.fire({
            title: 'Erro no pagamento',
            type: 'error',
            text: $('#payment_error').val(),
            confirmButtonText: 'Ok'
        });

        Swal.fire('Erro no pagamento', $('#payment_error').val(), 'error');

        setTimeout(function() {
            $("#pm_cartao:radio:first").click();
        }, 2000);
    }

    if($('#payment_success').length) {

        Swal.fire('Pronto!', $('#payment_success').val(), 'success');
        setTimeout(function() {
            $('.dropdown.bootstrap-select').hide()
        }, 1000)

        $('.dropdown.bootstrap-select').load(function() {
            $('.dropdown.bootstrap-select').hide()
        })
    }

    $('[name=paymentmode]').change(function() {
        let paymentmode = $('[name=paymentmode]:checked').val();

        if(paymentmode == 'cartao' || paymentmode == 'debito') {
            $('#card_fields').fadeIn();
        } else {
            $('#card_fields').fadeOut();
        }
    });

    $('#online_payment_form').submit(function(e) {
        let paymentmode = $('[name=paymentmode]:checked').val();

        if(paymentmode == 'cartao' || paymentmode == 'debito') {

            var error_label = null;

            $('.required-field').each(function() {
                if($(this).val() == '') {
                    error_label = $(this).data('label'); return false;
                }
            });

            if(error_label) {

                Swal.fire('Aviso', 'Preencha o ' + error_label, 'warning'); return false;

            }
        }

        $('[name=make_payment]').val('Por favor, aguarde...');

        Swal.showLoading();
    });

    $('[name=card_number]').mask('0000 0000 0000 0000');
    $('[name=expiration_month]').mask('00');
    $('[name=expiration_year]').mask('0000');
});
