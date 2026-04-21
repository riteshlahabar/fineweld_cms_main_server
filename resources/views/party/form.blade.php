<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Registration | Fineweld System</title>

    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        .datepicker {
            cursor: pointer;
        }
        .was-validated .form-control:invalid,
        .was-validated .form-select:invalid {
            border-color: #dc3545;
        }
        .was-validated .form-control:valid,
        .was-validated .form-select:valid {
            border-color: #198754;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <div class="card shadow-sm">
                <div class="card-header text-center bg-primary text-white">
                    <h3 class="mb-0">Fineweld System</h3>
                    <small>Vendor Registration Form</small>
                </div>

                <div class="card-body p-4">
                    <form method="POST"
                          action="{{ route('vendor.public.store') }}"
                          enctype="multipart/form-data"
                          class="row g-3 needs-validation"
                          id="vendorForm"
                          novalidate>

                        @csrf
                        @method('POST')

                        {{-- HIDDEN FIELDS --}}
                        <input type="hidden" name="party_type" value="vendor">
                        <input type="hidden" name="operation" value="save">
                        <input type="hidden" name="status" value="1">
                        <input type="hidden" id="base_url" value="{{ url('/') }}">

                        {{-- COMPANY DETAILS --}}
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">Company Details</h5>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Company Name *</label>
                            <input type="text" name="company_name" class="form-control" required>
                            <div class="invalid-feedback">Please enter company name.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Company Type *</label>
                            <select name="company_type" class="form-select" required>
                                <option value="">Select Company Type</option>
                                <option value="proprietor">Proprietor</option>
                                <option value="partnership">Partnership</option>
                                <option value="private_limited">Private Limited</option>
                                <option value="public_limited">Public Limited</option>
                                <option value="one_person_company">One Person Company</option>
                                <option value="limited_liability_partnership">Limited Liability Partnership</option>
                            </select>
                            <div class="invalid-feedback">Please select company type.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Vendor Type *</label><br>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="vendor_type" id="vendor_customer" value="customer" required>
                                    <label class="form-check-label fw-bold" for="vendor_customer">Customer</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="vendor_type" id="vendor_supplier" value="supplier" required>
                                    <label class="form-check-label fw-bold" for="vendor_supplier">Supplier</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="vendor_type" id="vendor_both" value="both" required>
                                    <label class="form-check-label fw-bold" for="vendor_both">Both</label>
                                </div>
                            </div>
                            <div class="invalid-feedback d-block">Please select vendor type.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Company PAN *</label>
                            <input type="text" name="company_pan" class="form-control" required>
                            <div class="invalid-feedback">Please enter company PAN.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Company GST *</label>
                            <input type="text" name="company_gst" class="form-control" required>
                            <div class="invalid-feedback">Please enter company GST.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Company TAN *</label>
                            <input type="text" name="company_tan" class="form-control" required>
                            <div class="invalid-feedback">Please enter company TAN.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Company MSME No *</label>
                            <input type="text" name="company_msme" class="form-control" required>
                            <div class="invalid-feedback">Please enter company MSME number.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Date of Incorporation *</label>
                            <div class="input-group">
                                <input type="date" name="date_of_incorporation" class="form-control" required>
                                
                            </div>
                            <div class="invalid-feedback">Please select date of incorporation.</div>
                        </div>

                        {{-- CONTACT DETAILS TABS --}}
                        <div class="col-12 mt-4">
                            <ul class="nav nav-tabs nav-success mb-3" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#primaryContact" role="tab">
                                        <i class="bx bx-user-check me-1"></i>Primary Contact Person
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" href="#secondaryContact" role="tab">
                                        <i class="bx bx-user-plus me-1"></i>Secondary Contact Person
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" href="#companyAddress" role="tab">
                                        <i class='bx bx-map font-18 me-1'></i>Company Address
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" href="#bankDetails" role="tab">
                                        <i class='bx bxs-bank font-18 me-1'></i>Bank Details
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" href="#documents" role="tab">
                                        <i class='bx bx-file font-18 me-1'></i>Company Documents
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content py-3">
                                {{-- PRIMARY CONTACT --}}
                                <div class="tab-pane fade show active" id="primaryContact" role="tabpanel">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Name *</label>
                                            <input type="text" name="primary_name" class="form-control" required>
                                            <div class="invalid-feedback">Please enter primary contact name.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email Id *</label>
                                            <input type="email" name="primary_email" class="form-control" required>
                                            <div class="invalid-feedback">Please enter valid email.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Mob No *</label>
                                            <input type="tel" name="primary_mobile" class="form-control" pattern="[0-9]{10}" required>
                                            <div class="invalid-feedback">Please enter 10 digit mobile number.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">What's App No *</label>
                                            <input type="tel" name="primary_whatsapp" class="form-control" pattern="[0-9]{10}" required>
                                            <div class="invalid-feedback">Please enter 10 digit WhatsApp number.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Date of Birth *</label>
                                            <div class="input-group">
                                                <input type="date" name="primary_dob" class="form-control" required>
                                                
                                            </div>
                                            <div class="invalid-feedback">Please select date of birth.</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- SECONDARY CONTACT --}}
                                <div class="tab-pane fade" id="secondaryContact" role="tabpanel">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Name *</label>
                                            <input type="text" name="secondary_name" class="form-control" required>
                                            <div class="invalid-feedback">Please enter secondary contact name.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email Id *</label>
                                            <input type="email" name="secondary_email" class="form-control" required>
                                            <div class="invalid-feedback">Please enter valid email.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Mob No *</label>
                                            <input type="tel" name="secondary_mobile" class="form-control" pattern="[0-9]{10}" required>
                                            <div class="invalid-feedback">Please enter 10 digit mobile number.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">What's App No *</label>
                                            <input type="tel" name="secondary_whatsapp" class="form-control" pattern="[0-9]{10}" required>
                                            <div class="invalid-feedback">Please enter 10 digit WhatsApp number.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Date of Birth *</label>
                                            <div class="input-group">
                                                <input type="date" name="secondary_dob" class="form-control" required>
                                                
                                            </div>
                                            <div class="invalid-feedback">Please select date of birth.</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- COMPANY ADDRESS --}}
                                <div class="tab-pane fade" id="companyAddress" role="tabpanel">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Billing Address *</label>
                                            <textarea name="billing_address" class="form-control" rows="3" required></textarea>
                                            <div class="invalid-feedback">Please enter billing address.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Shipping Address *</label>
                                            <textarea name="shipping_address" class="form-control" rows="3" required></textarea>
                                            <div class="invalid-feedback">Please enter shipping address.</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- BANK DETAILS --}}
                                <div class="tab-pane fade" id="bankDetails" role="tabpanel">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Bank Name *</label>
                                            <input type="text" name="bank_name" class="form-control" required>
                                            <div class="invalid-feedback">Please enter bank name.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Bank Branch *</label>
                                            <input type="text" name="bank_branch" class="form-control" required>
                                            <div class="invalid-feedback">Please enter bank branch.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Bank A/c No *</label>
                                            <input type="text" name="bank_account_no" class="form-control" required>
                                            <div class="invalid-feedback">Please enter bank account number.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">IFSC Code *</label>
                                            <input type="text" name="ifsc_code" class="form-control" required>
<div class="invalid-feedback">Please enter IFSC code.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">MICR Code *</label>
                                            <input type="text" name="micr_code" class="form-control" required>
<div class="invalid-feedback">Please enter MICR code.</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- COMPANY DOCUMENTS --}}
                                <div class="tab-pane fade" id="documents" role="tabpanel">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">PAN Document *</label>
                                            <input type="file" class="form-control" name="pan_document" accept="image/*,application/pdf" required>
                                            <div class="invalid-feedback">Please upload PAN document.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">TAN Document *</label>
                                            <input type="file" class="form-control" name="tan_document" accept="image/*,application/pdf" required>
                                            <div class="invalid-feedback">Please upload TAN document.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">GST Document *</label>
                                            <input type="file" class="form-control" name="gst_document" accept="image/*,application/pdf" required>
                                            <div class="invalid-feedback">Please upload GST document.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">MSME Document *</label>
                                            <input type="file" class="form-control" name="msme_document" accept="image/*,application/pdf" required>
                                            <div class="invalid-feedback">Please upload MSME document.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Cancelled Cheque *</label>
                                            <input type="file" class="form-control" name="cancelled_cheque" accept="image/*,application/pdf" required>
                                            <div class="invalid-feedback">Please upload cancelled cheque.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- HIDDEN FIELDS FOR BACKWARD COMPATIBILITY --}}
                        <div class="col-md-6 d-none">
                            <input type="text" name="first_name" value="">
                        </div>
                        <div class="col-md-6 d-none">
                            <input type="text" name="last_name" value="">
                        </div>
                        <div class="col-md-6 d-none">
                            <input type="email" name="email" value="">
                        </div>
                        <div class="col-md-6 d-none">
                            <input type="number" name="phone" value="">
                        </div>
                        <div class="col-md-6 d-none">
                            <input type="number" name="mobile" value="">
                        </div>
                        <div class="col-md-6 d-none">
                            <input type="number" name="whatsapp" value="">
                        </div>

                        {{-- SUBMIT BUTTON --}}
                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="bx bx-send me-1"></i> Submit Registration
                            </button>
                        </div>

                    </form>
                </div>

                <div class="card-footer text-center text-muted">
                    After submission, our team will review and activate your vendor account.
                </div>
            </div>

        </div>
    </div>
</div>

<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function () {
    'use strict';

    const form = document.getElementById('vendorForm');

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        event.stopPropagation();

        // Bootstrap validation
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            
            // Find first invalid field and scroll to it
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
                
                // Show error alert
                Swal.fire({
                    icon: 'error',
                    title: 'All field Compulsory',
                    text: 'Please fill all required fields correctly!',
                    confirmButtonColor: '#d33'
                });
            }
            return false;
        }

        // If validation passes, submit via AJAX
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        
        // Disable submit button
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

        $.ajax({
            url: form.action,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Form submitted successfully!',
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        form.reset();
                        form.classList.remove('was-validated');
                        // Redirect or reload if needed
                        // window.location.href = '/thank-you';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Something went wrong!',
                        confirmButtonColor: '#d33'
                    });
                }
            },
            error: function(xhr) {
    let errorMessage = 'An error occurred. Please try again.';
    
    if (xhr.responseJSON) {
        // 1. Get the main message (e.g., "Validation failed")
        if (xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message; 
        }

        // 2. IMPORTANT: Append specific field errors if they exist
        // The original code used 'else if', skipping this part!
        if (xhr.responseJSON.errors) {
            const errorList = Object.values(xhr.responseJSON.errors).flat();
            // Append errors to the message
            errorMessage += '<br><br><ul style="text-align: left; color: red;">' + 
                            errorList.map(e => `<li>${e}</li>`).join('') + 
                            '</ul>';
        }
    }
    
    Swal.fire({
        icon: 'error',
        title: 'Submission Failed',
        html: errorMessage, // Use 'html' instead of 'text' to render the list
        confirmButtonColor: '#d33'
    });
},
            complete: function() {
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="bx bx-send me-1"></i> Submit Registration';
            }
        });
    });
})();
</script>

</body>
</html>
