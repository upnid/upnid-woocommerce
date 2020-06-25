(function ($) {

    var checkout_form = $('form.checkout');

    checkout_form.on('checkout_place_order', function () {

        var upnid_card_number = $('#upnid-card-number').val(),
            upnid_card_expiry = $('#upnid-card-expiry').val(),
            upnid_card_cvc = $('#upnid-card-cvc').val(),
            upnid_installments = $('#upnid-installments').val(),
            upnid_card_holder_name = $('#upnid-card-holder-name').val();

        // append the credit card form to the checkout form
        checkout_form.append('<input type="hidden" name="upnid-card-number" value="' + upnid_card_number + '">');
        checkout_form.append('<input type="hidden" name="upnid-card-expiry" value="' + upnid_card_expiry + '">');
        checkout_form.append('<input type="hidden" name="upnid-card-cvc" value="' + upnid_card_cvc + '">');
        checkout_form.append('<input type="hidden" name="upnid-card-holder-name" value="' + upnid_card_holder_name + '">');
        checkout_form.append('<input type="hidden" name="upnid-installments" value="' + upnid_installments + '">');

        // return true to continue the submission or false to prevent it;
        return true;
    });

    $(document.body).on('updated_checkout', function () {
        $('form#upnid').card({
            container: '.card-wrapper',
            formSelectors: {
                numberInput: 'input#upnid-card-number',
                expiryInput: 'input#upnid-card-expiry',
                cvcInput: 'input#upnid-card-cvc',
                nameInput: 'input#upnid-card-holder-name'
            },
            messages: {
                validDate: 'válido\naté',
                monthYear: 'mês / ano',
            },
            placeholders: {
                number: '**** **** **** ****',
                name: 'Seu Nome',
                expiry: '**/****',
                cvc: '***'
            },
        });

    });

}(jQuery));
