$(function() {
    "use strict";

    function confirmUninstall() {
        if (confirm('Are you sure you want to uninstall the Partnership module?')) {
            showSpinner();
            document.getElementById('uninstall-partnership').submit();
        }
    }

    $(document).on('click', '.uninstall', function() {
        confirmUninstall();
    });

    $(document).on('click', '.install', function() {
        showSpinner();
        document.getElementById('install-partnership').submit();
    });

    $(document).on('click', '.activate', function() {
        showSpinner();
        document.getElementById('activate-partnership').submit();
    });

    $(document).on('click', '.deactivate', function() {
        showSpinner();
        document.getElementById('deactivate-partnership').submit();
    });

});
