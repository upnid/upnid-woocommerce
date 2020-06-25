/* global wcUpnidParams */
(function ($) {

    //console.log(wcUpnidParams);

    var checkout_form = $('form.checkout');

    checkout_form.on('checkout_place_order', function () {

        var upnid_card_number = $('#upnid-card-number').val(),
            upnid_card_expiry = $('#upnid-card-expiry').val(),
            upnid_card_cvc = $('#upnid-card-cvc').val(),
            upnid_installments = $('#upnid-installments').val(),
            upnid_card_holder_name = $('#upnid-card-holder-name').val();

        // do your custom stuff
        checkout_form.append('<input type="hidden" name="upnid-card-number" value="' + upnid_card_number + '">');
        checkout_form.append('<input type="hidden" name="upnid-card-expiry" value="' + upnid_card_expiry + '">');
        checkout_form.append('<input type="hidden" name="upnid-card-cvc" value="' + upnid_card_cvc + '">');
        checkout_form.append('<input type="hidden" name="upnid-card-holder-name" value="' + upnid_card_holder_name + '">');
        checkout_form.append('<input type="hidden" name="upnid-installments" value="' + upnid_installments + '">');

        // return true to continue the submission or false to prevent it return true;
        return true;
    });

    $(document.body).on('updated_checkout', function () {
        $('form#upnid').card({
            // a selector or DOM element for the container
            // where you want the card to appear
            container: '.card-wrapper', // *required*
            //container: '.card-wrapper', // *required*
            formSelectors: {
                numberInput: 'input#upnid-card-number', // optional — default input[name="number"]
                expiryInput: 'input#upnid-card-expiry', // optional — default input[name="expiry"]
                cvcInput: 'input#upnid-card-cvc', // optional — default input[name="cvc"]
                nameInput: 'input#upnid-card-holder-name' // optional - defaults input[name="name"]
            },
            messages: {
                validDate: 'válido\naté', // optional - default 'valid\nthru'
                monthYear: 'mês / ano', // optional - default 'month/year'
            },
            placeholders: {
                number: '**** **** **** ****',
                name: 'Seu Nome',
                expiry: '**/****',
                cvc: '***'
            },

            // all of the other options from above
        });

        $('#upnid-card-number').val('5574232831253202');
        $('#upnid-card-expiry').val('112024');
        $('#upnid-card-cvc').val('000');
        $('#upnid-installments').val('1');
        $('#upnid-card-holder-name').val('José Josias');

    });

}(jQuery));
