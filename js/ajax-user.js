jQuery(document).ready(function($) {
    
    $('#signin_button').on('click', function(e) {
        e.preventDefault();
        //var formData = $(this).serialize();
        var email_address = $('#formsteps #step-1 #email_address').val();
        var user_password = $('#formsteps #step-1 #password').val();
        var account_check = $('#formsteps #step-1 .account_check:checked').val();
        
        $('#formsteps #step-1 #signin_button').hide();
        $('#formsteps #step-1 .ajax_loader_img').show();
        $.ajax({
            type: 'POST',
            url: ajax_user_params.ajax_url,
            data: 'account_check='+account_check+'&email='+email_address+'&password='+user_password+'&action=ajax_user_check&security=' + ajax_user_params.security,
            dataType: 'json',
            success: function(response) {
                console.log(response);
                if (response.success) {
                    if(response.message == "signin"){
                        /*$('.stepwizard .stepwizard-step .signin_step').removeClass('btn-success').addClass('btn-complete');
                        $('.stepwizard .stepwizard-step .signin_step').text('L');
                        $('.stepwizard .stepwizard-step .delivery_step').removeClass('btn-default').addClass('btn-success');
                        $('#formsteps #step-1').hide();
                        $('#formsteps #step-2').show();*/
                        location.reload();
                    } else if(response.message == "signup"){
                        $('.stepwizard .stepwizard-step .signin_step').removeClass('btn-success').addClass('btn-complete');
                        $('.stepwizard .stepwizard-step .signin_step').text('L');
                        $('.stepwizard .stepwizard-step .delivery_step').removeClass('btn-default').addClass('btn-success');
                        $('#formsteps #step-1').hide();
                        $('#formsteps #step-2').show();
                        $('#formsteps #step-1 #signin_button').show();
                        $('#formsteps #step-1 .ajax_loader_img').hide();
                    } else {
                        alert(response.message);
                        $('#formsteps #step-1 #signin_button').show();
                        $('#formsteps #step-1 .ajax_loader_img').hide();
                    }
                } else {
                    alert('Error: ' + response.message);
                    $('#formsteps #step-1 #signin_button').show();
                    $('#formsteps #step-1 .ajax_loader_img').hide();
                }
            },
            error: function(errorThrown) {
                console.log(errorThrown);
                $('#formsteps #step-1 #signin_button').show();
                $('#formsteps #step-1 .ajax_loader_img').hide();
            }
        });
    });
    
    $('#delivery_button').on('click', function(e) {
        e.preventDefault();
        
        var account_check = $('#formsteps #step-1 .account_check:checked').val();
        var email_address = $('#formsteps #step-1 #email_address').val();
        var user_password = $('#formsteps #step-2 #userPassword').val();
        var user_password_confirm = $('#formsteps #step-2 #userConfirmPassword').val();
        
        var delivery_fullname = $('#formsteps #step-2 #deliveryFullName').val();
        var delivery_company = $('#formsteps #step-2 #deliveryCompanyName').val();
        var delivery_mobile = $('#formsteps #step-2 #deliveryMobileNumber').val();
        var delivery_line1 = $('#formsteps #step-2 #deliveryLine1').val();
        var delivery_line2 = $('#formsteps #step-2 #deliveryLine2').val();
        var delivery_city = $('#formsteps #step-2 #deliveryCity').val();
        var delivery_zip = $('#formsteps #step-2 #deliveryZip').val();
        var delivery_state = $('#formsteps #step-2 #deliveryState').val();
        var delivery_country = $('#formsteps #step-2 #deliveryCountry').val();
        var delivery_method = $('#formsteps #step-2 #deliveryMethod').val();
        var primary_shipping_radio = $('#formsteps #step-2 #primary_shipping_radio').val();
        
        var venue_id = $('#formsteps #step-2 #OrderVenueId').val();
        var event_id = $('#formsteps #step-2 #OrderEventId').val();
        var listing_id = $('#formsteps #step-2 #OrderListingId').val();
        var ticket_qty = $('#formsteps #step-2 #OrderTicketQty').val();
        var configuration_id = $('#formsteps #step-2 #OrderConfigurationId').val();
        
        if(primary_shipping_radio){
            $('.stepwizard .stepwizard-step .delivery_step').removeClass('btn-success').addClass('btn-complete');
            $('.stepwizard .stepwizard-step .delivery_step').text('L');
            $('.stepwizard .stepwizard-step .billing_step').removeClass('btn-default').addClass('btn-success');
            $('#formsteps #step-1').hide();
            $('#formsteps #step-2').hide();
            $('#formsteps #step-3').show();
            return false;
        }
        
        $('#formsteps #delivery_button').hide();
        $('#step-2 .ajax_loader_img').show();
        $.ajax({
            type: 'POST',
            url: ajax_registration_params.ajax_url,
            data: 'account_check='+account_check+'&venue_id='+venue_id+'&event_id='+event_id+'&listing_id='+listing_id+'&ticket_qty='+ticket_qty+'&configuration_id='+configuration_id+'&email='+email_address+'&password='+user_password+'&password_confirm='+user_password_confirm+'&fullname='+delivery_fullname+'&company='+delivery_company+'&mobile='+delivery_mobile+'&line1='+delivery_line1+'&line2='+delivery_line2+'&city='+delivery_city+'&zip='+delivery_zip+'&state='+delivery_state+'&country='+delivery_country+'&delivery_method='+delivery_method+'&action=ajax_register_user&security=' + ajax_registration_params.security,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if(response.message == "user_created"){
                        $('.stepwizard .stepwizard-step .delivery_step').removeClass('btn-success').addClass('btn-complete');
                        $('.stepwizard .stepwizard-step .delivery_step').text('L');
                        $('.stepwizard .stepwizard-step .billing_step').removeClass('btn-default').addClass('btn-success');
                        $('#formsteps #step-1').hide();
                        $('#formsteps #step-2').hide();
                        $('#formsteps #step-3').show();
                        $('#formsteps #delivery_button').show();
                        $('#step-2 .ajax_loader_img').hide();
                        location.reload();
                    }
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(errorThrown) {
                console.log(errorThrown);
            }
        });
    });
    
    $('#formsteps #step-3 #edit_billing_address').on('click', function() {
        $('#formsteps #step-3 #display_billing_address_form').toggle();
    });
    
    $('#formsteps #step-3 #billing_address_create_btn').on('click', function() {
        var existing_client_id = $('#formsteps #step-3 #existing_client_id').val();
        var billing_fullname = $('#formsteps #step-3 #billingFullName').val();
        var billing_company = $('#formsteps #step-3 #billingCompanyName').val();
        var billing_line1 = $('#formsteps #step-3 #billingLine1').val();
        var billing_line2 = $('#formsteps #step-3 #billingLine2').val();
        var billing_city = $('#formsteps #step-3 #billingCity').val();
        var billing_zip = $('#formsteps #step-3 #billingZip').val();
        var billing_state = $('#formsteps #step-3 #billingState').val();
        var billing_country = $('#formsteps #step-3 #billingCountry').val();
        
        $('#formsteps #step-3 #billing_address_create_btn').hide();
        $('#formsteps #step-3 #display_billing_address_form .ajax_loader_img').show();
        $.ajax({
            type: 'POST',
            url: ajax_address_create_param.ajax_url,
            data: 'existing_client_id='+existing_client_id+'&fullname='+billing_fullname+'&line1='+billing_line1+'&line2='+billing_line2+'&city='+billing_city+'&zip='+billing_zip+'&state='+billing_state+'&country='+billing_country+'&action=ajax_address_create&security=' + ajax_address_create_param.security,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#formsteps #step-3 #billing_address_create_btn').show();
                    $('#formsteps #step-3 #display_billing_address_form .ajax_loader_img').hide();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(errorThrown) {
                console.log(errorThrown);
            }
        });
    });
    
    $('#formsteps #step-3 #billing_button').on('click', function() {
        $('.stepwizard .stepwizard-step .billing_step').removeClass('btn-success').addClass('btn-complete');
        $('.stepwizard .stepwizard-step .billing_step').text('L');
        $('.stepwizard .stepwizard-step .order_step').removeClass('btn-default').addClass('btn-success');
        $('#formsteps #step-1').hide();
        $('#formsteps #step-2').hide();
        $('#formsteps #step-3').hide();
        $('.myseatmaps #step-4').show();
    });
    
    $('#step-4 .saved_card_button').on('click', function() {
        var ticket_amount = $('#ticket_amount').val();
        var ticket_qty = $('#OrderTicketQty').val();
        var ticket_venueid = $('#OrderVenueId').val();
        var ticket_eventid = $('#OrderEventId').val();
        var ticket_listingId = $('#OrderListingId').val();
        var ticket_deliveryMethod = $('#deliveryMethod').val();
        var SquareSaveCard = $('#saved_card_onfile_radio').val();
        
        $('#step-4 #payment-form #card-button').hide();
        $('#step-4 #payment-form .ajax_loader_img').show();
        $('#step-4 #payment-form #payment-status-msg').hide();
        $.ajax({
            type: 'POST',
            url: ajax_square_payment_params.ajax_url,
            data: 'SquareSaveCard='+SquareSaveCard+'&delivery_method='+ticket_deliveryMethod+'&ticket_venueid='+ticket_venueid+'&ticket_eventid='+ticket_eventid+'&ticket_listingId='+ticket_listingId+'&ticket_amount='+ticket_amount+'&ticket_qty='+ticket_qty+'&locationId='+locationId+'&sourceId=&verificationToken=&idempotencyKey=&action=ajax_square_payment&security=' + ajax_square_payment_params.security,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#step-4 #payment-form #card-button').hide();
                    $('#step-4 #payment-form .ajax_loader_img').hide();
                    $('#step-4 #payment-form #payment-status-msg').show().html(response.message).css('color', 'green');
                } else {
                    //alert('Error: ' + response.message);
                    $('#step-4 #payment-form #card-button').show();
                    $('#step-4 #payment-form .ajax_loader_img').hide();
                    $('#step-4 #payment-form #payment-status-msg').show().html(response.message).css('color', 'red');;
                }
            },
            error: function(errorThrown) {
                console.log(errorThrown);
            }
        });
    });
    
});

async function initializeCard(payments) {
    const card = await payments.card();
    await card.attach('#card-container');
    return card;
}

async function createPayment(token, verificationToken, ticket_amount, ticket_qty, ticket_venueid, ticket_eventid, ticket_listingId, ticket_deliveryMethod, SquareSaveCard) {
    $('#step-4 #payment-form #card-button').hide();
    $('#step-4 #payment-form .ajax_loader_img').show();
    $('#step-4 #payment-form #payment-status-msg').hide();
    
    $.ajax({
        type: 'POST',
        url: ajax_square_payment_params.ajax_url,
        data: 'SquareSaveCard='+SquareSaveCard+'&delivery_method='+ticket_deliveryMethod+'&ticket_venueid='+ticket_venueid+'&ticket_eventid='+ticket_eventid+'&ticket_listingId='+ticket_listingId+'&ticket_amount='+ticket_amount+'&ticket_qty='+ticket_qty+'&locationId='+locationId+'&sourceId='+token+'&verificationToken='+verificationToken+'&idempotencyKey='+window.crypto.randomUUID()+'&action=ajax_square_payment&security=' + ajax_square_payment_params.security,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#step-4 #payment-form #card-container').hide();
                $('#step-4 #payment-form #card-container-label').hide();
                $('#step-4 #payment-form #card-button').hide();
                $('#step-4 #payment-form .ajax_loader_img').hide();
                $('#step-4 #payment-form #payment-status-msg').show().html(response.message).css('color', 'green');
            } else {
                $('#step-4 #payment-form #card-container').show();
                $('#step-4 #payment-form #card-container-label').show();
                $('#step-4 #payment-form #card-button').show();
                $('#step-4 #payment-form .ajax_loader_img').hide();
                $('#step-4 #payment-form #payment-status-msg').show().html(response.message).css('color', 'red');
            }
        },
        error: function(errorThrown) {
            console.log(errorThrown);
        }
    });
}

async function tokenize(paymentMethod) {
    const tokenResult = await paymentMethod.tokenize();
    if (tokenResult.status === 'OK') {
      return tokenResult.token;
    } else {
      let errorMessage = `Tokenization failed with status: ${tokenResult.status}`;
      if (tokenResult.errors) {
        errorMessage += ` and errors: ${JSON.stringify(
          tokenResult.errors,
        )}`;
      }
    
      throw new Error(errorMessage);
    }
}

//other elements

document.addEventListener('DOMContentLoaded', async function () {
    if (!window.Square) {
      throw new Error('Square.js failed to load properly');
    }
	
    let payments;
    try {
        payments = window.Square.payments(appId, locationId);
    } catch {
      const statusContainer = document.getElementById(
        'payment-status-msg',
      );
      statusContainer.className = 'missing-credentials';
      statusContainer.style.visibility = 'visible';
      return;
    }
    
    let card;
    try {
      card = await initializeCard(payments);
    } catch (e) {
      console.error('Initializing Card failed', e);
      return;
    }
    
    async function handlePaymentMethodSubmission(event, card) {
      event.preventDefault();
        
      try {
            var ticket_amount = document.getElementById('ticket_amount').value;
            var ticket_qty = document.getElementById('OrderTicketQty').value;
            var ticket_venueid = document.getElementById('OrderVenueId').value;
            var ticket_eventid = document.getElementById('OrderEventId').value;
            var ticket_listingId = document.getElementById('OrderListingId').value;
            var ticket_deliveryMethod = document.getElementById('deliveryMethod').value;
            var SquareSaveCard = document.getElementById('square_save_card').checked;
            
            // disable the submit button as we await tokenization and make a payment request.
            cardButton.disabled = true;
            const token = await tokenize(card);
            const verificationToken = await verifyBuyer(payments, token);
            const paymentResults = await createPayment(
              token,
              verificationToken,
              ticket_amount,
              ticket_qty,
              ticket_venueid,
              ticket_eventid,
              ticket_listingId,
              ticket_deliveryMethod,
              SquareSaveCard
            );
            //displayPaymentResults('SUCCESS');
        
            //console.debug('Payment Success', paymentResults);
      } catch (e) {
        cardButton.disabled = false;
        //displayPaymentResults('FAILURE');
        //console.error(e.message);
      }
    }
    
    const cardButton = document.getElementById('card-button');
    cardButton.addEventListener('click', async function (event) {
      await handlePaymentMethodSubmission(event, card);
    });
});