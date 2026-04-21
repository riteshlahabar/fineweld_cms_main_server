$(document).ready(function(){
    loadEngineers();
});

setInterval(function () {
    document.querySelectorAll('.sla-timer').forEach(function (el) {

        let card = el.closest('.ticket-card');
        let statusBadge = card.querySelector('.status-badge');

        // STOP timer if status is completed
        if (statusBadge && statusBadge.innerText.trim().toLowerCase() === 'completed') {
            return;
        }

        let created = new Date(el.dataset.created);
        let now = new Date();
        let diff = now - created;

        let hours = Math.floor(diff / 3600000);
        let minutes = Math.floor((diff % 3600000) / 60000);
        let seconds = Math.floor((diff % 60000) / 1000);

        el.innerHTML =
            String(hours).padStart(2,'0') + ':' +
            String(minutes).padStart(2,'0') + ':' +
            String(seconds).padStart(2,'0');
    });
}, 1000);


$(document).on('change', '.status-dropdown', function () {

    let ticketId = $(this).data('ticket-id');
    let selectedStatus = $(this).val();
    let card = $(this).closest('.card');

    let currentEngineerId = card.data('engineer-id');
    let currentEngineerName = card.data('engineer-name');

    $('#modal_ticket_id').val(ticketId);

    // RESET UI
    $('#engineerDropdownWrapper').removeClass('d-none');
    $('#engineerNameWrapper').addClass('d-none');
    $('.engineer-dropdown-modal').prop('disabled', false);

    if (selectedStatus === 'assigned') {

        $('#modalTitle').text('Assign Ticket');
        $('#modalActionBtn').text('Assign');

    }

    else if (selectedStatus === 'reassign') {

        $('#modalTitle').text('Reassign Ticket');
        $('#modalActionBtn').text('Reassign');

    }

    else if (selectedStatus === 'reschedule') {

        $('#modalTitle').text('Reschedule Ticket');
        $('#modalActionBtn').text('Reschedule');

        // Hide dropdown
        $('#engineerDropdownWrapper').addClass('d-none');

        // Show current engineer name
        $('#engineerNameWrapper').removeClass('d-none');
        $('#modal_engineer_name').val(currentEngineerName);
    }

    $('#scheduleModal').modal('show');
});

function loadEngineers() {

    $.ajax({
        url: '/tickets/live-map-data',
        type: 'GET',
        success: function (response) {

            if (response.status) {

                let select = $('.engineer-dropdown-modal');
                select.empty();
                select.append('<option value="">Select Engineer</option>');

                response.engineers.forEach(function (engineer) {

                    select.append(
                        `<option value="${engineer.id}">
                            ${engineer.name}
                         </option>`
                    );
                });
            }
        }
    });
}

$(document).on('click', '.assign-btn', function() {

    let ticketId = $(this).data('ticket-id');

    $('#modalTitle').text('Assign Ticket');
    $('#modalActionBtn').text('Assign');
    $('#modal_ticket_id').val(ticketId);

    $('#scheduleModal').modal('show');
});



$('#modalActionBtn').on('click', function(){

    let ticketId = $('#modal_ticket_id').val();
    let engineerId = $('.engineer-dropdown-modal option:selected').val();
    let date = $('#schedule_date').val();
    let time = $('#schedule_time').val();
    let actionType = $(this).text();

    if(actionType !== 'Reschedule' && !engineerId){
        alert("Please select engineer");
        return;
    }

    if(!date || !time){
        alert("Please select date and time");
        return;
    }
console.log("Ticket ID:", ticketId);
console.log("Engineer ID:", engineerId);
console.log("Date:", date);
console.log("Time:", time);
console.log("Action:", actionType);
    $.ajax({
        url: '/tickets/assign-engineer',
        type: 'POST',
        data: {
            _token: $('#csrf_token').val(),
            ticket_id: ticketId,
            engineer_id: engineerId,
            schedule_date: date,
            schedule_time: time,
            action_type: actionType
        },
        success: function (response) {

            if(response.status){
                $('#scheduleModal').modal('hide');
                location.reload();
            }
        }
    });
});

$('#status_filter, #priority_filter, #technician_filter, #date_range').on('change', function () {

    let status = $('#status_filter').val();
    let priority = $('#priority_filter').val();
    let tech = $('#technician_filter').val();
    let date = $('#date_range').val();

    $('.ticket-card').parent().show();

    $('.ticket-card').each(function () {

        let card = $(this);
        let cardStatus = card.data('status');
        let cardPriority = card.data('priority');
        let cardTech = card.data('technician');
        let cardDate = card.data('date');

        if (status && cardStatus != status) card.parent().hide();
        if (priority && cardPriority != priority) card.parent().hide();
        if (tech && cardTech != tech) card.parent().hide();
        if (date && cardDate != date) card.parent().hide();

    });

});

$('.btn-outline-secondary').click(function(){

    $('#status_filter').val('');
    $('#priority_filter').val('');
    $('#technician_filter').val('');
    $('#date_range').val('');

    $('.ticket-card').parent().show();

});