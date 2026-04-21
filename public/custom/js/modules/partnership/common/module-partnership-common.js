"use strict";

/**
 * Load Partners from PartnerController
 * Ajax Operation for select2
 * */
function initSelect2Partners(options = {}) {
    $('.partner-ajax').select2({
        theme: 'bootstrap-5',
        allowClear: true,
        cache: true,
        ajax: {
            url: baseURL + '/partner/ajax/get-list',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                var query = {
                    search: params.term,
                    page: params.page || 1
                };
                return query;
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return {
                    results: data.results.map(function(item) {
                        return {
                            id: item.id,
                            text: item.text,
                            mobile: item.mobile,
                        };
                    }),
                    pagination: {
                        more: data.hasMore // your backend should return hasMore: true/false
                    }
                };
            }
        },
        templateResult: function(data) {
            if (!data.id) {
                return data.text; // Placeholder
            }
            // Customize the dropdown item display
            return $(
                `<div>
                    <span class='fs-4'>${data.text}</span><br>
                    <small><i class="fadeIn text-primary bx bx-mobile"></i>${data.mobile || '-'}</small>
                </div>`
            );
        },
        templateSelection: function(data) {
            if (!data.id) {
                return data.text; // Placeholder
            }
            // Customize the selected item display
            return $(
                `<span>${data.text} <small class="text-muted">(${data.mobile || '-'})</small></span>`
            );
        },
        escapeMarkup: function(markup) {
            return markup; // Allow HTML rendering
        },
        ...options //its a valid code, which is called spread operator
    });
}



$(document).ready(function($) {
    //Partners
    initSelect2Partners();
});

