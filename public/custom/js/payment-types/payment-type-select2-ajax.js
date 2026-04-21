$(function() {

  "use strict";

  var nextRowNumber = 1; // Track the next row number

  var amountLang = $('#amount_lang').data('name')?.trim() || '';

  var paymentTypeLang = $('#payment_type_lang').text().trim();

  var paymentNoteLang = $('#payment_note_lang').text().trim();

  function getOperationValue() {
    return ($('#operation').val() || '').toString().toLowerCase();
  }

  function parseSelectedPaymentTypes() {
    var jsonString = $("#selectedPaymentTypesArray").val();
    if (!jsonString) {
      return null;
    }

    try {
      return JSON.parse(jsonString);
    } catch (error) {
      return null;
    }
  }



  // Function to add a new payment type row
  function addPaymentTypeRow() {

    // Create the new row element
    var newRow = $('<div class="row payment-type-row-' + nextRowNumber + ' py-3"></div>');

    // Append your complete HTML code as content
    newRow.append(`
      <div class="col-md-6">
        <label class="form-label " for="amount"><strong>#${nextRowNumber+1}</strong> ${amountLang}</label>
        <div class="input-group mb-3">
          <input type="text" name="payment_amount[${nextRowNumber}]" value="" class="form-control " placeholder="">
          <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-dollar"></i></span>
        </div>
      </div>
      <div class="col-md-6">
        <label class="form-label " for="payment_type">${paymentTypeLang}</label>
        <div class="input-group">
          <select class="form-select select2 payment-type-ajax" name="payment_type_id[${nextRowNumber}]" data-placeholder="Choose one thing">
          </select>
          <button type="button" class="input-group-text" data-bs-toggle="modal" data-bs-target="#paymentTypeModal">
            <i class='text-primary bx bx-plus-circle'></i>
          </button>
        </div>
      </div>
      <div class="col-md-6">
        <label class="form-label " for="payment_note">${paymentNoteLang}</label>
        <textarea name="payment_note[${nextRowNumber}]" class="form-control" placeholder=""></textarea>
      </div>
      <div class="col-md-3">
        <label class="form-label " for="">Delete</label>
        <div class="input-group mb-3">
        <button type="button" class="btn btn-outline-danger remove_payment"><i class="bx bx-trash me-0"></i></button>
        </div>
      </div>

    `);

    $('.payment-container').append(newRow);

    $('.payment-type-ajax').select2(); // Notify only Select2 of changes

    $('input[name="amount[' + nextRowNumber + ']"]').focus();

    nextRowNumber++; // Increment for the next row

    $('input[name="row_count_payments"]').val(nextRowNumber);

    initSelect2PaymentType();

  }

  /**
   * Add Payment
   * */
  $(document).on('click', '.add_payment_type', function() {
    addPaymentTypeRow();
  });

  /**
   * Delete Payment
   * */
  $(document).on('click', '.remove_payment', function(){
    $(this).closest('.row').remove();
  });

  $(document).ready(function() {
    initSelect2PaymentType();

    var operationValue = getOperationValue();
    var parsedPaymentData = parseSelectedPaymentTypes();

    // Default record is used by create/update where selectedPaymentTypesArray is a single object.
    if (operationValue == 'save' || operationValue == 'update') {
      autoSetDefaultPayment(parsedPaymentData);
      return;
    }

    // Convert/update-expense can carry payment history array, but for safety fallback to default object.
    if (operationValue == 'convert' || operationValue == 'update-expense') {
      if (Array.isArray(parsedPaymentData)) {
        autoAddPaymentRecordsInTable(parsedPaymentData);
      } else {
        autoSetDefaultPayment(parsedPaymentData);
      }
    }

  });

  window.calculateTotalPayment = function() {
    let total = 0;

    $('[name^="payment_amount"]').each(function() {
        var value = $(this).val();
        var numericValue = parseFloat(value);
        if (!isNaN(numericValue) && value !== '') {
            total += numericValue;
        }
    });

    return parseFloat(total);
  }

  function autoSetDefaultPayment(paymentData){
    var jsonObject = paymentData ?? parseSelectedPaymentTypes();
    if (!jsonObject) {
      return;
    }

    // If array is passed accidentally, use first record.
    if (Array.isArray(jsonObject)) {
      jsonObject = jsonObject[0] || null;
    }
    if (!jsonObject) {
      return;
    }

    var optionId = jsonObject.id ?? jsonObject.payment_type_id ?? null;
    var optionText = jsonObject.name ?? jsonObject.type ?? '';
    if (!optionId || !optionText) {
      return;
    }

    var data = {
         id: optionId,
         text: optionText,
     };

    var newOption = new Option(data.text, data.id, false, false);
    $('select[name="payment_type_id[0]"]').append(newOption).trigger('change');
  }

function autoAddPaymentRecordsInTable(paymentRecords){
    var jsonObject = paymentRecords ?? parseSelectedPaymentTypes();
    if (!Array.isArray(jsonObject)) {
      return;
    }

    jsonObject.forEach((data, index) => {
          if($(`select[name="payment_type_id[${index}]"]`).length == 0){
              addPaymentTypeRow();
          }
          var newOption = new Option(data.type, data.payment_type_id, false, false);
          $(`select[name="payment_type_id[${index}]"]`).append(newOption).trigger('change');
          $(`input[name="payment_amount[${index}]"]`).val(_parseFix(data.amount));
          $(`textarea[name="payment_note[${index}]"]`).val(data.note);
      });
  }
});
