$(function () {
    $('#datatable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '/tickets/products/datatable-list',
            type: 'GET'
        },
        columns: [
            // 0️⃣ Hidden ID
            { data: 'id', visible: false },

            // 1️⃣ Checkbox
            {
    data: 'id',
    orderable: false,
    searchable: false,
    render: function (data) {
        return `<input class="form-check-input row-select" type="checkbox" value="${data}">`;
    }
},

            // 2️⃣ Company Name
            { data: 'company_name' },

            // 3️⃣ Contact Person
            { data: 'primary_name' },

            // 4️⃣ Mobile No.
            { data: 'primary_mobile' },

            // 5️⃣ Purchase Order No.
            { data: 'purchase_order_no' },

            // 6️⃣ Purchase Order Date
            { data: 'purchase_order_date' },

            // 7️⃣ Tax Invoice No.
            { data: 'tax_invoice_no' },

            // 8️⃣ Tax Invoice Date
            { data: 'tax_invoice_date' },

            // 9️⃣ Product Name
            { data: 'product_name' },
            
            {
    data: 'product_image',
    orderable: false,
    searchable: false,
    render: function (data) {
        if (!data) {
            return '<span class="text-muted">No Image</span>';
        }
       return `
    <img src="${data}"
         class="product-thumb"
         style="width:50px;height:50px;object-fit:cover;border-radius:6px;border:1px solid #ddd;cursor:pointer;">
`;
    }
},


            // 🔟 Model Number
            { data: 'model_number' },

            // 1️⃣1️⃣ Serial Number
            { data: 'serial_number' },

            // 1️⃣2️⃣ Installation Date
            { data: 'installation_date' },

            // 1️⃣3️⃣ Warranty Start
            { data: 'warranty_start' },

            // 1️⃣4️⃣ Warranty End
            { data: 'warranty_end' },

            // 1️⃣5️⃣ Warranty Remaining
            { data: 'warranty_remaining' },

            // 1️⃣6️⃣ Installed By
            { data: 'installed_by' },

            // 1️⃣7️⃣ Remarks
            { data: 'remarks' },

            // 1️⃣8️⃣ Action
            { data: 'action', orderable: false, searchable: false }
        ]
    });
});

$(document).on('click','.product-thumb',function(){

    let img = $(this).attr('src');

    $('#previewImage').attr('src', img);

    let modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));

    modal.show();

});


