"use strict";

    const tableId = $('#contractItemsTable');

    let originalButtonText;

    let submitButton = 'button[id="submit_form"]';

    let addRowButtonText;

    const rowCountStartFrom = 0;

    const operation = $('#operation').val();

    const itemSearchInputBoxId = $("#search_item");

    var buttonId = $("#add_row");

    var shareTypeArrayData = ['percentage'];
    /**
     * Language
     * */
    const _lang = {
                pleaseSelectItem : "Item Name Should not be empty",
                pleaseSelectItemFromSearchBox : "Choose Item from Search Results!!",
                clickTochange : "Click to Change",
                clickToChangeTaxType : "Click to Change Tax Type",
                clickToSelectSerial : "Click to Select Serial Numbers",
                enterValidNumber : "Please enter a valid number",
                wantToDelete : "Do you want to delete?",
                paymentAndGrandTotalMismatched : "Total Payment Amount Should be equal to Grand Total!",
                rowAddedSuccessdully : "Item Added!",
                taxTypeChanged : "Tax type has changed!",
                discountNotAllowed : "Discount not allowed! Permission Revoked!",
                salePriceNotAllowed : "Sale Price not allowed! Permission Revoked!",
                selectPartner : "Please select Partner for all Items",
            };

    $("#submit_form").on('click', function(event) {
        event.preventDefault();

        /**
         * validate Partner is selected for each item
         * */
        if(!validatePartnerFields()){
            iziToast.error({title: 'Warning', layout: 2, message: _lang.selectPartner});
            return false;
        }

        $("#contractForm").submit();

    });

    $("#contractForm").on("submit", function(e) {
        e.preventDefault();
        const form = $(this);
        const formArray = {
            formId: form.attr("id"),
            csrf: form.find('input[name="_token"]').val(),
            url: form.closest('form').attr('action'),
            formObject : form,
        };
        ajaxRequest(formArray);
    });

    function disableSubmitButton(form) {
        originalButtonText = form.find(submitButton).text();
        form.find(submitButton)
            .prop('disabled', true)
            .html('  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading...');
    }

    function enableSubmitButton(form) {
        form.find(submitButton)
            .prop('disabled', false)
            .html(originalButtonText);
    }

    function beforeCallAjaxRequest(formObject){
        disableSubmitButton(formObject);
    }
    function afterCallAjaxResponse(formObject){
        enableSubmitButton(formObject);
    }
    function afterSeccessOfAjaxRequest(formObject){
        formAdjustIfSaveOperation(formObject);
        pageRedirect(formObject);
    }

    function pageRedirect(formObject){
        var redirectTo = '';
        if(formObject.response.id !== undefined){
            redirectTo = '/partner/contract/details/'+formObject.response.id;
        }else{
            redirectTo = '/partner/contract/list';
        }
        setTimeout(function() {
           location.href = baseURL + redirectTo;
        }, 1000);
    }

    function ajaxRequest(formArray){
        var formData = new FormData(document.getElementById(formArray.formId));
        var jqxhr = $.ajax({
            type: 'POST',
            url: formArray.url,
            data: formData,
            dataType: 'json',
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-TOKEN': formArray.csrf
            },
            beforeSend: function() {
                // Actions to be performed before sending the AJAX request
                if (typeof beforeCallAjaxRequest === 'function') {
                    beforeCallAjaxRequest(formArray.formObject);
                }
            },
        });
        jqxhr.done(function(data) {
            formArray.formObject.response = data;
            iziToast.success({title: 'Success', layout: 2, message: data.message});
            // Actions to be performed after response from the AJAX request
            if (typeof afterSeccessOfAjaxRequest === 'function') {
                afterSeccessOfAjaxRequest(formArray.formObject);
            }
        });
        jqxhr.fail(function(response) {
                var message = response.responseJSON.message;
                iziToast.error({title: 'Error', layout: 2, message: message});
        });
        jqxhr.always(function() {
            // Actions to be performed after the AJAX request is completed, regardless of success or failure
            if (typeof afterCallAjaxResponse === 'function') {
                afterCallAjaxResponse(formArray.formObject);
            }
        });
    }

    function formAdjustIfSaveOperation(formObject){
        const _method = formObject.find('input[name="_method"]').val();
        /* Only if Save Operation called*/
        if(_method.toUpperCase() == 'POST' ){
            var formId = formObject.attr("id");
            $("#"+formId)[0].reset();
        }
    }
    /**
     * When i click on Enter Button, call addRow()
     * */
    itemSearchInputBoxId.on("keydown", function(event) {
        if (event.key === "Enter") {
            //iziToast.info({title: 'Info', layout: 2, message: _lang.pleaseSelectItemFromSearchBox});
        }
    });
    /**
     * Add Service or Products on front view
     * call addRow()
     * */
    $("#add_row").on('click', function() {
        addRow();
    });

    /**
     * Add Row to front view
     * */
    function addRow(recordObject){
        if(Object.keys(recordObject).length === 0){
            iziToast.error({title: 'Warning', layout: 2, message: _lang.pleaseSelectItem});
            itemSearchInputBoxId.focus();
            return;
        }

        //Disable the Button
        disableAddRowButton(buttonId);
        //JSON Data to add row
        addRowToInvoiceItemsTable(recordObject);
        /*Enable the Disabled Button*/
        enableAddRowButton(buttonId);
        //Make Input box empty and keep curson on it
        itemSearchInputBoxId.val('').focus();
        //Row Added Message
        rowAddedSuccessdully();
    }

    function rowAddedSuccessdully(){
        //iziToast.success({title: _lang.rowAddedSuccessdully, layout: 2, message: ''});
        itemSearchInputBoxId.autocomplete("close");

    }
    /**
     * Prepaired JSON data
     * */
    function defaultJsonData() {
        var dataObject = {
              name: $('#search_item').val().trim(),
              description: "",
              quantity: 1,
              total_price: 0
            };

        return dataObject;
    }

    /**
     * Make Loading of Add Button
     * */
    function disableAddRowButton(buttonId) {
        addRowButtonText = buttonId.text();
        // Set button text to "Loading..."
        buttonId.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading...');
        // Disable the button
        buttonId.prop('disabled', true);
    }

    function enableAddRowButton(buttonId) {
        //Restore the actual button name
        buttonId.html(addRowButtonText);
        // Enable the button
        buttonId.prop('disabled', false);
    }

    /**
     * return Table row count
     * */
    function getRowCount(){
        var rowCount = returnDecimalValueByName('row_count');
        return rowCount;
    }

    /**
     * set table row count
     * */
    function setRowCount(){
        var increamentRowCount = getRowCount();
            increamentRowCount++;
        $('input[name="row_count"]').val(increamentRowCount);
    }

    /**
     * Create Service Table Row
     * Params: record Object
     *
     * autoLoadUpdateOperation parameter is true only when update page autoloaded
     * ex: Sale Order -> Edit
     * */
    function addRowToInvoiceItemsTable(recordObject, loadedFromUpdateOperation=false){

       //Find service table row count
        var currentRowId = getRowCount();

        var tableBody = tableId.find('tbody');
        var hiddenItemId  = '<input type="hidden" name="item_id['+ currentRowId +']" class="form-control" value="' + recordObject.id + '">';
        var inputItemName  = `<label class="form-label mb-0" role="button">${recordObject.text}</label> `;
            inputItemName += (recordObject.brand_name !== undefined)
            ? `<br><span class="badge bg-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Brand Name">${recordObject.brand_name}</span>`
            : '';
        var inputDescription  = '<textarea rows="1" type="text" name="description['+ currentRowId +']" class="form-control" placeholder="Description">' + (recordObject.description || '') + '</textarea>';
        var shareHolders = `<input type="text" name="" class="form-control form-control-plaintext text-center" readonly="" value="${recordObject.totalShareHolders}">`;
            shareHolders += recordObject.totalShareHolders > 0 ? `<span class="btn btn-outline-success btn-sm p-1 ms-1 mt-1 show-share-holders-data" title="Like"><i class="bx bx-show"></i>Show</span>` :'';

        var shareType = '<input type="hidden" name="share_type['+ currentRowId +']" class="" value="percentage">';
            shareType += '<input type="text" name="" class="form-control form-control-plaintext" readonly value="Percentage %">';

        var shareValue = '<div class="input-group">';
            shareValue +='<input type="text" name="share_value['+ currentRowId +']" class="form-control" value="' + _parseFix(recordObject.share_value || 0) + '"' + '>';
            shareValue +='</div>';

        var partners = generatePartnerSelectionBox(recordObject.partnerList, currentRowId, recordObject.selected_partner_id);//fixed/percentage

        /*Keeping the Scheduled Job Records*/
        var removeClass = (!recordObject.assigned_user_id)? 'remove' : '';
        var inputDeleteButton = '<button type="button" class="btn btn-outline-danger '+removeClass+'"><i class="bx bx-trash me-0"></i></button>';


        var newRow = $('<tr id="'+ currentRowId +'" class="highlight">');
            newRow.append('<td>' + inputDeleteButton + '</td>');
            newRow.append('<td>' + hiddenItemId + inputItemName + inputDescription + '</td>');
            newRow.append('<td class="text-center">' + shareHolders + '</td>');
            newRow.append('<td>' + shareType + '</td>');
            newRow.append('<td>' + shareValue + '</td>');
            newRow.append(`<td>` + partners + '</td>');
            // Add action buttons
            var actionButtonCell = $('<td>');

            // Append new row to the table body
            tableBody.prepend(newRow);

            afterAddRowFunctionality(currentRowId);
    }

    /**
     * HTML : After Add Row Functionality
     * */
    function afterAddRowFunctionality(currentRowId){
        //Remove Default existing row if exist
        removeDefaultRowFromTable();

        //Set Row Count
        setRowCount();

        //Custom Function Reset Date & Time Picker
        resetFlatpickr();

        //Reinitiate Tooltip
        setTooltip();

        //Calculate Row Records
        //rowCalculator(currentRowId);

        //Validate the partner selected or not
        validatePartnerFields();
    }

    /**
     * Generate Tax Type Selection Box
     * */
    function generatePartnerSelectionBox(partnerList, currentRowId, selectId = null) {
        const options = partnerList
            .map(partner => `<option value="${partner.id}" ${selectId == partner.id ? 'selected' : ''}>${partner.first_name + ' ' + (partner.last_name === null ? '' : partner.last_name)}</option>`)
            .join('');

        return `<select class="form-select" name="partner_id[${currentRowId}]">
                <option style="color: red;" value="">-- Select --</option>
                ${options}
                </select>

                `;
    }

    /**
     * Validate partner_id fields and show/hide required class
     */
    function validatePartnerFields() {
        $('#contractItemsTable tbody tr').each(function() {
            const $row = $(this);
            const $select = $row.find('select[name^="partner_id["]');
            const $requiredDiv = $select.siblings('.text-danger');
            if ($select.length) {
                if (!$select.val()) {
                    $select.addClass('is-invalid');
                    $requiredDiv.addClass('required');
                    return false; // Exit the loop on first invalid field
                } else {
                    $select.removeClass('is-invalid');
                    $requiredDiv.removeClass('required');
                }
            }
        });

        return true;
    }


    // Optionally, validate on change
    $(document).on('change', 'select[name^="partner_id["]', function() {
        validatePartnerFields();
    });


    /**
     * Generate Unit Selection Box
     * */
    function generateShareTypeSelectionBox(shareTypeArray, currentRowId, selectValue = null) {
        const options = shareTypeArray
            .map(share =>
                `<option value="${share}" ${selectValue === share ? 'selected' : ''}>${share.charAt(0).toUpperCase()+share.slice(1)}</option>`
            ).join('');

        return `<select class="form-select" name="share_type[${currentRowId}]">${options}</select>`;
    }


    /**
     * Remove Default Row from table
     * */
    function removeDefaultRowFromTable() {
        if($('.default-row').length){
            $('.default-row').closest('tr').remove();
        }
    }

    /**
     * Delete Row
     * */
    $(document).on('click', '.remove', function() {
      $(this).closest('tr').remove();
      //afterRemoveFunctions();
    });
    /**
     * Reset Date & Time Pickers
     * */
    function resetFlatpickr(){
              $(".datepicker").flatpickr({
                dateFormat: dateFormatOfApp, // Set the date format
              });

            flatpickr(".time-picker",{
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",//"K" for AM & PM
            });
    }

    /**
     * Row: Find disocunt type
     * @return percentage or fixed
     * */
    function returnRowDiscountType(rowId){
        return $("input[name='discount_type["+rowId+"]'").val();
    }

    /**
     * return Decimal input value
     * */
    function returnDecimalValueByName(inputBoxName){
        var _inpuBoxId = $("input[name ='"+inputBoxName+"']");
        var inputBoxValue = _inpuBoxId.val();

        if(inputBoxValue == '' || isNaN(inputBoxValue)){
            return parseFloat(0);
        }
        return parseFloat(inputBoxValue);
    }

   /**
    * Autocomplete
    * Item Search Input box
    * */
   itemSearchInputBoxId.on('click', function() {
        initItemAutocomplete(itemSearchInputBoxId, {
            request_from: 'contract',
            onSelect: function(item) {
                addRow(item); // Your existing addRow logic
            }
        });
    });


    $(document).ready(function(){
        /**
         * Toggle the sidebar of the template
         * */
        toggleSidebar();

        /**
         * Update Opetation
         * */
        if(operation == 'update'){
            updateOperation(itemsTableRecords);
        }
    });
    /**
     * Used this in sale-order -> operation -> create
     *
     * */
    window.addRowToInvoiceItemsTableFromBatchTracking = function(dataObject){
        /**
         * Remove already added row while selecting batch
         * */
        tableId.find(`tr#${dataObject.mainTableRowId}`).remove();

        /**
         * @argument (object, true)
         * Dynamically adding row then no need to show pop up so it's second argument is true
         * */
        addRowToInvoiceItemsTable(dataObject,true);
    }

    function updateOperation(stringData){

        var jsonObject = JSON.parse(stringData);

        jsonObject.forEach((data, index) => {


                var dataObject = {
                    id              : data.item_id,
                    text            : data.item_name,
                    brand_name      : data.brand_name,
                    totalShareHolders : data.totalShareHolders,
                    description     : (data.description != null) ? data.description : '',
                    share_value      : data.share_value,
                    partnerList         : data.partnerList,
                    selected_partner_id : data.partner_id,
                    selected_share_type : data.share_type,
                };
                addRowToInvoiceItemsTable(dataObject,true);
          });
    }

    $(document).on('click', '.show-share-holders-data', function() {
        var currentRowId = $(this).closest('tr').attr('id');
        var itemId = $(`input[name='item_id[${currentRowId}]']`).val();

        if(!itemId){
            iziToast.error({title: 'Error', layout: 2, message: 'Item ID not found'});
            return false;
        }

        var url = baseURL + '/partner/contract/ajax/modal/share-holders/';
        ajaxGetRequest(url , itemId , 'show-share-holders');

    });

    function ajaxGetRequest(url, id, _from) {
          $.ajax({
            url: url + id,
            type: 'GET',
            beforeSend: function() {
              showSpinner();
            },
            success: function(response) {
              if(_from == 'show-share-holders'){
                handleShareHoldersResponse(response);
              }
              else {
                //
              }
            },
            error: function(response) {
               var message = response.responseJSON.message;
               iziToast.error({title: 'Error', layout: 2, message: message});
            },
            complete: function() {
              hideSpinner();
            },
          });
    }

    function handleShareHoldersResponse(response) {

        // Remove any existing modal container
        $('#modalContainer').remove();

        // Create the modal container and append to body
        $('body').append('<div id="modalContainer"></div>');

        // Add the modal HTML to the container
        $('#modalContainer').html(response.html);

        // Show the newly loaded modal
        const modal = new bootstrap.Modal(document.getElementById('shareHoldersModal'));
        modal.show();
    }

