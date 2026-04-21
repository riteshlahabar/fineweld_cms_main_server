$(function () {
    "use strict";

    const tableId = $('#datatable');
    const datatableForm = $("#datatableForm");
    const partyType = $('input[name="party_type"]').val();
    let partyPaymentHistoryModal = $('#partyPaymentHistoryModal');

    /**
     * Server-side Datatable
     */
    function loadDatatables() {

        if ($.fn.DataTable.isDataTable('#datatable')) {
            tableId.DataTable().destroy();
        }

        const exportColumns = [2,3,4,5,6,7,8,9,10]; // same as original

        const table = tableId.DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            autoWidth: false,
            ajax: {
                url: baseURL + '/party/' + partyType + '/datatable-list',
                data: function (d) {
                    d.vendor_type  = $('#vendor_type').val();
                    d.company_type = $('#company_type').val();
                }
            },

            columns: [
                { data: 'id', visible: false },

                {
                    data: 'checkbox',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },

                { data: 'company_name' },
                { data: 'company_address' },
                { data: 'vendor_type' },
                { data: 'primary_name' },
                { data: 'primary_email' },
                { data: 'primary_mobile' },
                { data: 'primary_whatsapp' },
                { data: 'balance', orderable: false, searchable: false },
                { data: 'status', orderable: false, searchable: false },
                { data: 'action', orderable: false, searchable: false }
            ],

            order: [[0, 'desc']],

            /**
             * ðŸ”¥ THIS DOM MATCHES YOUR SCREENSHOT
             * Buttons in center, search on right
             */
            dom: "<'row' "+
                    "<'col-sm-12' "+
                        "<'float-start' l"+
                            /* card-body class - auto created here */
                        ">"+
                        "<'float-end' fr"+
                            /* card-body class - auto created here */
                        ">"+
                        "<'float-end ms-2'"+
                            "<'card-body ' B >"+
                        ">"+
                    ">"+
                  ">"+
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",


            /**
             * ðŸ”¥ SAME 5 BUTTONS AS SCREENSHOT
             */
           buttons: [
    {
        className: 'btn btn-outline-danger multi_delete',
        text: 'Delete',
        action: function () {
            requestDeleteRecords();
        }
    },
    {
        className: 'btn btn-outline-success',
        text: 'Excel',
        action: function () {
            window.location = `${baseURL}/party/${partyType}/export?type=csv`;
        }
    },
    {
        className: 'btn btn-outline-primary',
        text: 'CSV',
        action: function () {
            window.location =
                `${baseURL}/party/${partyType}/export?type=csv`;
        }
    },
    
],


            select: {
                style: 'os',
                selector: 'td:first-child'
            },
            order: [[0, 'desc']]


        });

        table.on('click', '.deleteRequest', async function (e) {
    e.preventDefault();  // ✅ Prevent any default behavior
    e.stopPropagation(); // ✅ Stop event bubbling
    
    let deleteId = $(this).attr('data-delete-id');
    await deleteRequest(deleteId);
});


        //Adding Space on top & bottom of the table attributes
        $('.dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate').wrap("<div class='card-body py-3'>");
    }

    // Handle header checkbox click event
    tableId.find('thead').on('click', '.row-select', function() {
        var isChecked = $(this).prop('checked');
        tableId.find('tbody .row-select').prop('checked', isChecked);
    });


    /**
     * Filters (Vendor Type / Company Type)
     */
    $('#vendor_type, #company_type').on('change', function () {
        loadDatatables();
    });

    /**
     * Header checkbox (select all)
     */
    tableId.find('thead').on('click', '.row-select', function () {
        const checked = $(this).prop('checked');
        tableId.find('tbody .row-select').prop('checked', checked);
    });

    /**
     * @return count
     * How many checkbox are checked
    */
   function countCheckedCheckbox(){
        var checkedCount = $('input[name="record_ids[]"]:checked').length;
        return checkedCount;
   }

   /**
    * Validate checkbox are checked
    */
   async function validateCheckedCheckbox(){
        const confirmed = await confirmAction();//Defined in ./common/common.js
        if (!confirmed) {
            return false;
        }
        if(countCheckedCheckbox() == 0){
            iziToast.error({title: 'Warning', layout: 2, message: "Please select at least one record to delete"});
            return false;
        }
        return true;
   }
    /**
     * Caller:
     * Function to single delete request
     * Call Delete Request
    */
    async function deleteRequest(id) {
        const confirmed = await confirmAction();//Defined in ./common/common.js
        if (confirmed) {
            deleteRecord(id);
        }
    }

    /**
     * Create Ajax Request:
     * Multiple Data Delete
    */
   async function requestDeleteRecords(){
        //validate checkbox count
        const confirmed = await confirmAction();//Defined in ./common/common.js
        if (confirmed) {
            //Submit delete records
            datatableForm.trigger('submit');
        }
   }
    datatableForm.on("submit", function(e) {
        e.preventDefault();

            //Form posting Functionality
            const form = $(this);
            const formArray = {
                formId: form.attr("id"),
                csrf: form.find('input[name="_token"]').val(),
                _method: form.find('input[name="_method"]').val(),
                url: form.closest('form').attr('action'),
                formObject : form,
                formData : new FormData(document.getElementById(form.attr("id"))),
            };
            ajaxRequest(formArray); //Defined in ./common/common.js

    });

    /**
     * Create AjaxRequest:
     * Single Data Delete
    */
    function deleteRecord(id){
    const form = datatableForm;
    const formArray = {
        formId: form.attr("id"),
        csrf: form.find('input[name="_token"]').val(),
        _method: form.find('input[name="_method"]').val(),
        url: form.attr('action'),  // ✅ Use form action URL
        formObject: form,
        formData: new FormData()
    };
    formArray.formData.append('record_ids[]', id);
    
    // ✅ Show loading spinner FIRST
    showSpinner();
    
    ajaxRequest(formArray);
}


    /**
    * Ajax Request
    */
    function ajaxRequest(formArray){
    var jqxhr = $.ajax({
        type: formArray._method,
        url: formArray.url,
        data: formArray.formData,
        dataType: 'json',
        contentType: false,
        processData: false,
        headers: {
            'X-CSRF-TOKEN': formArray.csrf
        },
        beforeSend: function() {
            showSpinner();
        },
    });
    
    jqxhr.done(function(data) {
        iziToast.success({title: 'Success', layout: 2, message: data.message});
    });
    
    jqxhr.fail(function(response) {
        var message = response.responseJSON ? response.responseJSON.message : 'Something went wrong';
        iziToast.error({title: 'Error', layout: 2, message: message});
    });
    
    jqxhr.always(function() {
        hideSpinner();  // ✅ HIDE SPINNER FIRST
        if (typeof afterCallAjaxResponse === 'function') {
            afterCallAjaxResponse(formArray.formObject);
        }
    });
}


    function afterCallAjaxResponse(formObject){
        loadDatatables();
    }

    $(document).ready(function() {
        //Load Datatable
        loadDatatables();
	} );

    $(document).on('click', '.party-payment-history', function() {
        var partyId = $(this).attr('data-party-id');
        var url = baseURL + `/party/payment-history/`;
        ajaxGetRequest(url ,partyId, 'party-payment-history');
    });

    $(document).on('click', '.party-delete-payment', function() {
        var paymentId = $(this).closest('tr').attr('id');
        deletePaymentRequest(paymentId);
    });

    /**
     * Caller:
     * Function to single delete request
     * Call Delete Request
    */
    async function deletePaymentRequest(paymentId) {
        const confirmed = await confirmAction();//Defined in ./common/common.js
        if (confirmed) {
            var url = baseURL + `/party/payment-delete/`;
            ajaxGetRequest(url ,paymentId, 'delete-party-payment');
        }
    }

    function ajaxGetRequest(url, id, _from) {
          $.ajax({
            url: url + id,
            type: 'GET',
            headers: {
              'X-CSRF-TOKEN': datatableForm.find('input[name="_token"]').val(),
            },
            beforeSend: function() {
              showSpinner();
            },
            success: function(response) {
              if(_from == 'delete-party-payment'){
                handlePartyPaymentDeleteResponse(response);
              }
              else if (_from == 'party-payment-history') {
                handlePartyPaymentHistoryResponse(response);
              } else {
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

    function handlePartyPaymentHistoryResponse(response, showModel = true) {
        $("#party-name").text(response.party_name);
        $("#balance-amount").text(_parseFix(response.balance));

        let totalAmount = 0;
        
        var table = $('#payment-history-table tbody');

        table.empty(); // Clear existing rows
        
        $.each(response.partyPayments, function(index, payment) {
            totalAmount += parseFloat(payment.amount);
            var newRow = `
                <tr id="${payment.payment_id}">
                    <td>${payment.transaction_date}</td>
                    <td>${payment.payment_direction}</td>
                    <td>${payment.reference_no}</td>
                    <td>${payment.payment_type}</td>
                    <td class="text-end text-${payment.color}">${payment.amount}</td>
                    <td>
                        <div class="d-flex order-actions justify-content-center">
                            <a href="${baseURL}/party/payment-receipt/print/${payment.payment_id}" target="_blank" class="text-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Print"><i class="bx bxs-printer"></i></a>
                            <a href="${baseURL}/party/payment-receipt/pdf/${payment.payment_id}" target="_blank" class="ms-1 text-success" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="PDF"><i class="bx bxs-file-pdf"></i></a>
                            <a href="javascript:;" role="button" class="ms-1 party-delete-payment text-danger" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Delete"><i class="bx bxs-trash"></i></a>
                        </div>
                    </td>
                </tr>
            `;

            table.append(newRow);
        });

        //show only if not shown, in delete payment condition no need to show modal
        if(showModel){
            partyPaymentHistoryModal.modal('show');
        }

        setTooltip();
    }

    function handlePartyPaymentDeleteResponse(response) {
        iziToast.success({title: 'Success', layout: 2, message: response.message});
        partyPaymentHistoryModal.modal('hide');
        loadDatatables();
    }

    function isWholesaleCustomer() {

        if(partyType != 'customer'){
            return 0;//0 retail
        }
        
        /**
         * @return 0 if wholesaler else 0 for retailer
         * */
        return $("#customer_type").val();
    }

    $(document).on("change", '#customer_type', function function_name() {
        loadDatatables();
    });
});
