$(function() {
    "use strict";

    let originalButtonText;

    var tableId = $('#recordsTable');
    var tableId2 = $('#recordsTable2');

    /**
     * Language
     * */
    const _lang = {
                total : "Total",
                noRecordsFound : "No Records Found!!",
            };

    $("#reportForm").on("submit", function(e) {
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
        originalButtonText = form.find('button[type="submit"]').text();
        form.find('button[type="submit"]')
            .prop('disabled', true)
            .html('  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading...');
    }

    function enableSubmitButton(form) {
        form.find('button[type="submit"]')
            .prop('disabled', false)
            .html(originalButtonText);
    }

    function beforeCallAjaxRequest(formObject){
        disableSubmitButton(formObject);
        showSpinner();
    }
    function afterCallAjaxResponse(formObject){
        enableSubmitButton(formObject);
        hideSpinner();
    }
    function afterSeccessOfAjaxRequest(formObject, response){
        formAdjustIfSaveOperation(response);
    }
    function afterFailOfAjaxRequest(formObject){
        showNoRecordsMessageOnTableBody();
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
        jqxhr.done(function(response) {
            // Actions to be performed after response from the AJAX request
            if (typeof afterSeccessOfAjaxRequest === 'function') {
                afterSeccessOfAjaxRequest(formArray.formObject, response);
            }
        });
        jqxhr.fail(function(response) {
            var message = response.responseJSON.message;
            iziToast.error({title: 'Error', layout: 2, message: message});
            if (typeof afterFailOfAjaxRequest === 'function') {
                afterFailOfAjaxRequest(formArray.formObject);
            }
        });
        jqxhr.always(function() {
            // Actions to be performed after the AJAX request is completed, regardless of success or failure
            if (typeof afterCallAjaxResponse === 'function') {
                afterCallAjaxResponse(formArray.formObject);
            }
        });
    }

    function populateDetailedTable(response){
        var tableBody = tableId2.find('tbody');

        var id = 1;
        var tr = "";

        var totalProfit = parseFloat(0);
        var totalPaidAmount = parseFloat(0);
        var totalReceivedAmount = parseFloat(0);

        $.each(response.data, function(index, item) {
            totalProfit += parseFloat(item.total_distributed_profit);
            totalPaidAmount += parseFloat(item.distributed_paid_amount);
            totalReceivedAmount += parseFloat(item.distributed_received_amount);

            tr  +=`
                <tr>
                    <td>${id++}</td>
                    <td>${item.transaction_date}</td>
                    <td>${item.transaction_type}</td>
                    <td>${item.sale_or_return_code}</td>
                    <td>${item.party_name}</td>
                    <td>${item.item_name}</td>
                    <td>${item.brand_name}</td>
                    <td>${item.partner_name}</td>
                    <td>${item.share_type}</td>
                    <td class='' data-tableexport-celltype="number" >${item.share_value}</td>
                    <td class='text-end' data-tableexport-celltype="number" >${_formatNumber(item.total_distributed_profit)}</td>
                </tr>
            `;
        });

        tr  +=`
            <tr class='fw-bold'>
                <td colspan='10' class='text-end'>${_lang.total}</td>
                <td class='text-end' data-tableexport-celltype="number">${_formatNumber(totalProfit)}</td>
            </tr>
        `;

        // Clear existing rows:
        tableBody.empty();
        tableBody.append(tr);

    }

    function formAdjustIfSaveOperation(response){

        var isDetailed = $('#show_detailed').is(':checked');

        if(isDetailed){
            populateDetailedTable(response);
            return;
        }

        var tableBody = tableId.find('tbody');

        var id = 1;
        var tr = "";

        var totalProfit = parseFloat(0);

        $.each(response.data, function(index, item) {
            totalProfit += parseFloat(item.total_distributed_profit);

            tr  +=`
                <tr>
                    <td>${id++}</td>
                    <td>${item.item_name}</td>
                    <td>${item.brand_name}</td>
                    <td>${item.partner_name}</td>
                    <td class='text-end' data-tableexport-celltype="number" >${_formatNumber(item.total_distributed_profit)}</td>
                </tr>
            `;
        });

        tr  +=`
            <tr class='fw-bold'>
                <td colspan='0' class='text-end tfoot-first-td'>${_lang.total}</td>
                <td class='text-end' data-tableexport-celltype="number">${_formatNumber(totalProfit)}</td>
            </tr>
        `;

        // Clear existing rows:
        tableBody.empty();
        tableBody.append(tr);

        /**
         * Set colspan of the table bottom
         * */
        $('.tfoot-first-td').attr('colspan', columnCountWithoutDNoneClass(1));
    }

    function showNoRecordsMessageOnTableBody() {
        var tableBody = tableId.find('tbody');

        var tr = "<tr class='fw-bold'>";
        tr += `<td colspan='0' class='text-end tfoot-first-td text-center'>${_lang.noRecordsFound}</td>"`;
        tr += "</tr>";

        tableBody.empty();
        tableBody.append(tr);

        /**
         * Set colspan of the table bottom
         * */
        $('.tfoot-first-td').attr('colspan', columnCountWithoutDNoneClass(0));
    }
    function columnCountWithoutDNoneClass(minusCount) {
        return tableId.find('thead > tr:first > th').not('.d-none').length - minusCount;
    }

    /**
     *
     * Table Exporter
     * PDF, SpreadSheet
     * */
    $(document).on("click", '#generate_pdf', function() {
        tableId.tableExport({type:'pdf',escape:'false', fileName: 'Sale-Report',});
    });

    $(document).on("click", '#generate_excel', function() {
        tableId.tableExport({
            formats: ["xlsx"],
            fileName: 'Sale-Report',
            xlsx: {
                onCellFormat: function (cell, e) {
                    if (typeof e.value === 'string') {
                        // Remove commas and convert to number
                        var numValue = parseFloat(e.value.replace(/,/g, ''));
                        if (!isNaN(numValue)) {
                            return numValue;
                        }
                    }
                    return e.value;
                }
            }
        });
    });

    $(document).on("click", '#generate_pdf2', function() {
        tableId2.tableExport({type:'pdf',escape:'false', fileName: 'Sale-Report',});
    });

    $(document).on("click", '#generate_excel2', function() {
        tableId2.tableExport({
            formats: ["xlsx"],
            fileName: 'Sale-Report',
            xlsx: {
                onCellFormat: function (cell, e) {
                    if (typeof e.value === 'string') {
                        // Remove commas and convert to number
                        var numValue = parseFloat(e.value.replace(/,/g, ''));
                        if (!isNaN(numValue)) {
                            return numValue;
                        }
                    }
                    return e.value;
                }
            }
        });
    });

    //on change checkbox hide show columns
    $(document).on('change', '#show_detailed', function() {
        var isChecked = $(this).is(':checked');

        // Show #recordsTable
        if (!isChecked) {//regular

            //Hide Detailed
            $('.item-wise-table-detailed').addClass('d-none');

            //Show Regular
            $('.item-wise-table').removeClass('d-none');

        }else{//detailed

            //Show Detailed
            $('.item-wise-table-detailed').removeClass('d-none');
            //Hide Regular
            $('.item-wise-table').addClass('d-none');

        }

    });

});//main function
