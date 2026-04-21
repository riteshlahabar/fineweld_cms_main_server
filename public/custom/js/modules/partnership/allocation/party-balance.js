$(function() {
	"use strict";

    const tableId = $('#datatable');

    let originalButtonText;


    const datatableForm = $("#datatableForm");

    /**
     *Server Side Datatable Records
    */
    window.loadDatatables = function() {
        //Delete previous data
        tableId.DataTable().destroy();

        var exportColumns = [1,2,3,4,5,6,7,8];//Index Starts from 0

        var table = tableId.DataTable({
            processing: true,
            serverSide: true,
            method:'get',
            ajax: {
                    url: baseURL+'/partner/party-balance/allocation/datatable-list',
                    data:{
                            //party_id : $('#party_id').val(),
                            user_id : $('#user_id').val(),

                            from_date : $('input[name="from_date"]').val(),
                            to_date : $('input[name="to_date"]').val(),
                        },
                },
            columns: [
                {targets: 0, data:'id', orderable:true, visible:false},
                {data: 'transaction_date', name: 'transaction_date'},
                {data: 'party_type', name: 'party_type'},
                {data: 'party_name', name: 'party_name'},
                {data: 'mobile', name: 'mobile'},

                {data: 'amount', name: 'amount', className: 'text-end'},
                {
                    data: null,
                    name: 'allocated_amount',
                    className: 'text-end',
                    orderable:false,
                    render: function(data, type, full, meta) {
                        var text = data.allocated_amount;
                        var cssClass = data.allocated_amount > 0 ? 'text-primary fw-bold' : '';
                        return `<span class="${cssClass}">${text}</span>`;
                    }

                },
                {
                    data: null,
                    name: 'balance',
                    className: 'text-end',
                    orderable:false,
                    render: function(data, type, full, meta) {
                        var text = data.balance;
                        var cssClass = data.balance_color;
                        return `<span class="${cssClass}">${text}</span>`;
                    }

                },


                {
                    data: null,
                    name: 'payment_direction',
                    className: 'text-center',
                    render: function(data, type, full, meta) {
                        var text = data.payment_direction.text;
                        var color = data.payment_direction.color;
                        return `<div class="badge text-${color} bg-light-${color} p-2 text-uppercase px-3">${text}</div>`;
                    }

                },
                {data: 'username', name: 'username'},
                {data: 'created_at', name: 'created_at'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ],

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

            buttons: [
                {
                    className: 'btn btn-outline-danger buttons-copy buttons-html5 multi_delete',
                    text: 'Delete',
                    action: function ( e, dt, node, config ) {
                        //Confirm user then trigger submit event
                       requestDeleteRecords();
                    }
                },
                // Apply exportOptions only to Copy button
                {
                    extend: 'copyHtml5',
                    exportOptions: {
                        columns: exportColumns
                    }
                },
                // Apply exportOptions only to Excel button
                {
                    extend: 'excelHtml5',
                    exportOptions: {
                        columns: exportColumns
                    }
                },
                // Apply exportOptions only to CSV button
                {
                    extend: 'csvHtml5',
                    exportOptions: {
                        columns: exportColumns
                    }
                },
                // Apply exportOptions only to PDF button
                {
                    extend: 'pdfHtml5',
                    orientation: 'portrait',//or "landscape"
                    exportOptions: {
                        columns: exportColumns,
                    },
                },

            ],

            select: {
                style: 'os',
                selector: 'td:first-child'
            },
            order: [[0, 'desc']],
            drawCallback: function() {
                /**
                 * Initialize Tooltip
                 * */
                setTooltip();
            }


        });

        table.on('click', '.deleteRequest', function () {
              let deleteId = $(this).attr('data-delete-id');

              deleteRequest(deleteId);

        });

        //Adding Space on top & bottom of the table attributes
        $('.dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate').wrap("<div class='card-body py-3'>");
    }

    // Handle header checkbox click event
    tableId.find('thead').on('click', '.row-select', function() {
        var isChecked = $(this).prop('checked');
        tableId.find('tbody .row-select').prop('checked', isChecked);
    });

    $(document).on("submit", "#partnerPartyPaymentAllocationForm", function(e) {
        e.preventDefault();
        const form = $(this);
        const formArray = {
            formId: form.attr("id"),
            csrf: form.find('input[name="_token"]').val(),
            _method: form.find('input[name="_method"]').val(),
            url: form.attr('action'),
            formObject: form,
            formData: new FormData(document.getElementById(form.attr("id"))),
            _from : 'partnerPartyPaymentAllocationForm',
        };
        ajaxRequest(formArray);
    });

    $(document).on('click', '.partner-party-balance-allocation', function() {
        var itemId = $(this).attr('data-id');
        if(!itemId){
            iziToast.error({title: 'Error', layout: 2, message: 'Item ID not found'});
            return false;
        }
        var url = baseURL + '/partner/party-balance/allocation/ajax/modal/show-modal/';
        ajaxGetRequest(url , itemId , 'show-partner-party-balance-allocation-modal');

    });

    $(document).on('click', '.delete-partner-transaction', function() {

        //confirm before delete
        const confirmed = confirm("Are you sure you want to delete this transaction?");
        if (!confirmed) {
            return false;
        }

        var transactionId = $(this).attr('data-id');
        if(!transactionId){
            iziToast.error({title: 'Error', layout: 2, message: 'Transaction ID not found'});
            return false;
        }

        var url = baseURL + '/partner/party-balance/allocation/delete/';
        ajaxGetRequest(url , transactionId , 'delete-partner-transaction');

    });



    function ajaxGetRequest(url, id, _from) {
          $.ajax({
            url: url + id,
            type: 'GET',
            beforeSend: function() {
              showSpinner();
            },
            success: function(response) {
              if(_from == 'show-partner-party-balance-allocation-modal'){
                handlePartnerPartyPaymmentAllocationResponse(response);
              }
              if(_from == 'delete-partner-transaction'){
                handlePartnerPartyPaymmentDeleteResponse(response);
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

    function handlePartnerPartyPaymmentDeleteResponse(response) {
        // Deleted successfully
        iziToast.success({title: 'Success', layout: 2, message: response.message});

        //Close the modal if open
        $('#partnerPartyPaymentAllocationModal').modal('hide');

        // Reload datatable to reflect changes
        loadDatatables();

    }

    function handlePartnerPartyPaymmentAllocationResponse(response) {

        // Remove any existing modal container
        $('#modalContainer').remove();

        // Create the modal container and append to body
        $('body').append('<div id="modalContainer"></div>');

        // Add the modal HTML to the container
        $('#modalContainer').html(response.html);

        // Show the newly loaded modal
        const modal = new bootstrap.Modal(document.getElementById('partnerPartyPaymentAllocationModal'));
        modal.show();

        $(".datepicker, .datepicker-edit").flatpickr({
            dateFormat: dateFormatOfApp, // Set the date format
        });
        initSelect2Partners({ dropdownParent: $('#partnerPartyPaymentAllocationModal') });
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
                // Actions to be performed before sending the AJAX request
                if (typeof beforeCallAjaxRequest === 'function') {
                    beforeCallAjaxRequest(formArray.formObject);
                }
            },
        });
        jqxhr.done(function(data) {

            iziToast.success({title: 'Success', layout: 2, message: data.message});


            if(formArray._from == 'partnerPartyPaymentAllocationForm'){
                console.log(data.status);
                if(data.status){
                    //close the modal
                    $('#partnerPartyPaymentAllocationModal').modal('hide');

                    //Reload datatable
                    loadDatatables();

                }
            }

        });
        jqxhr.fail(function(response) {
                var message = response.responseJSON.message;
                iziToast.error({title: 'Error', layout: 2, message: message});
        });
        jqxhr.always(function() {
            // Actions to be performed after the AJAX request is completed, regardless of success or failure
            if (typeof afterCallAjaxResponse === 'function') {
                afterCallAjaxResponse(formArray);
            }
        });
    }

    function beforeCallAjaxRequest(formObject){
        disableSubmitButton(formObject);
    }
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

    function afterCallAjaxResponse(formArray){
        //loadDatatables();
        if(formArray._from == 'partnerPartyPaymentAllocationForm'){
            enableSubmitButton(formArray.formObject);
        }

    }

    $(document).ready(function() {
        //Load Datatable
        loadDatatables();
	} );

    $(document).on("change", '#party_id, #user_id, input[name="from_date"], input[name="to_date"]', function function_name(e) {
        loadDatatables();
    });

});
