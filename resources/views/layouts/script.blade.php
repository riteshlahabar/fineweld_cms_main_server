	<!-- Bootstrap JS -->
	<script src="{{ versionedAsset('assets/js/bootstrap.bundle.min.js') }}"></script>
	<!--plugins-->
	<script src="{{ versionedAsset('assets/js/jquery.min.js') }}"></script>
	<script src="{{ versionedAsset('assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
	<script src="{{ versionedAsset('assets/plugins/metismenu/js/metisMenu.min.js') }}"></script>
	<script src="{{ versionedAsset('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
	<script src="{{ versionedAsset('assets/plugins/vectormap/jquery-jvectormap-2.0.2.min.js') }}"></script>
    <script src="{{ versionedAsset('assets/plugins/vectormap/jquery-jvectormap-world-mill-en.js') }}"></script>
	<script src="{{ versionedAsset('assets/plugins/chartjs/js/chart.js') }}"></script>
    <!-- select2 -->
    <script src="{{ versionedAsset('custom/libraries/select2-theme/select2-4.1.0-rc.0/dist/js/select2.min.js') }}"></script>
    <!-- Sweetalert -->
    <script src="{{ versionedAsset('custom/libraries/sweetalert/sweetalert.min.js') }}"></script>
	<!-- Notification Toast -->
    <script src="{{ versionedAsset('custom/libraries/iziToast/dist/js/iziToast.min.js') }}"></script>
    <!-- Date & Time Picker -->
    <script src="{{ versionedAsset('custom/libraries/flatpickr/flatpickr.min.js') }}"></script>
    <!-- Autocomplete -->
	<script src="{{ versionedAsset('assets/plugins/jquery-ui/jquery-ui.js') }}"></script>
    <!-- Number Library -->
    <script src="{{ versionedAsset('custom/libraries/numbro/numbro.min.js') }}"></script>
    <!-- All libraries Settings -->
    <script src="{{ versionedAsset('custom/js/plugin-settings.js') }}"></script>

    @php
        $companySettings = app()->bound('company') ? app('company') : [];
        $scriptSettings = [
            'appCompanyName' => (string) data_get($companySettings, 'name', ''),
            'appTaxType' => (string) data_get($companySettings, 'tax_type', ''),
            'dateFormatOfApp' => (string) data_get($companySettings, 'date_format', ''),
            'numberPrecision' => (int) data_get($companySettings, 'number_precision', 2),
            'quantityPrecision' => (int) data_get($companySettings, 'quantity_precision', 2),
            'itemSettings' => [
                'show_sku' => (int) data_get($companySettings, 'show_sku', 0),
                'show_mrp' => (int) data_get($companySettings, 'show_mrp', 0),
                'show_discount' => (int) data_get($companySettings, 'show_discount', 0),
                'enable_serial_tracking' => (int) data_get($companySettings, 'enable_serial_tracking', 0),
                'enable_batch_tracking' => (int) data_get($companySettings, 'enable_batch_tracking', 0),
                'enable_mfg_date' => (int) data_get($companySettings, 'enable_mfg_date', 0),
                'enable_exp_date' => (int) data_get($companySettings, 'enable_exp_date', 0),
                'enable_color' => (int) data_get($companySettings, 'enable_color', 0),
                'enable_size' => (int) data_get($companySettings, 'enable_size', 0),
                'enable_model' => (int) data_get($companySettings, 'enable_model', 0),
            ],
            'baseURL' => url(''),
            'csrfToken' => csrf_token(),
            'allowUserToPurchaseDiscount' => auth()->check() && auth()->user()->can('general.permission.to.apply.discount.to.purchase') ? 1 : 0,
            'allowUserToSaleDiscount' => auth()->check() && auth()->user()->can('general.permission.to.apply.discount.to.sale') ? 1 : 0,
            'allowUserToChangeSalePrice' => auth()->check() && auth()->user()->can('general.permission.to.change.sale.price') ? 1 : 0,
            'isEnableSecondaryCurrency' => auth()->check() && data_get($companySettings, 'is_enable_secondary_currency') ? 1 : 0,
            'isEnableCarrierCharge' => auth()->check() && data_get($companySettings, 'is_enable_carrier_charge') ? 1 : 0,
        ];
        $scriptSettingsJson = json_encode($scriptSettings, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    @endphp

    <span id="appScriptSettings" data-settings="{!! e($scriptSettingsJson) !!}" hidden></span>

    <script type="text/javascript">
		/*Configure the Application Date Format*/
		var scriptSettingsElement = document.getElementById('appScriptSettings');
		var scriptSettings = {};
		if (scriptSettingsElement) {
			try {
				scriptSettings = JSON.parse(scriptSettingsElement.getAttribute('data-settings') || '{}');
			} catch (error) {
				scriptSettings = {};
			}
		}
		var appCompanyName = scriptSettings.appCompanyName;
		var appTaxType = scriptSettings.appTaxType;
		var dateFormatOfApp = scriptSettings.dateFormatOfApp;
		var numberPrecision = scriptSettings.numberPrecision;
		var quantityPrecision = scriptSettings.quantityPrecision;
		var itemSettings = scriptSettings.itemSettings;
		var baseURL = scriptSettings.baseURL;
        var _csrf_token = scriptSettings.csrfToken;
        var allowUserToPurchaseDiscount = scriptSettings.allowUserToPurchaseDiscount;
        var allowUserToSaleDiscount = scriptSettings.allowUserToSaleDiscount;
        var allowUserToChangeSalePrice = scriptSettings.allowUserToChangeSalePrice;
        var isEnableSecondaryCurrency = scriptSettings.isEnableSecondaryCurrency;
        var isEnableCarrierCharge = scriptSettings.isEnableCarrierCharge;
	</script>
    <!-- Clear Cache -->
    <script src="{{ versionedAsset('custom/js/cache.js') }}"></script>

	@yield('js')
	<!--app JS-->
	@if(($appDirection ?? 'ltr') == 'ltr')
		<script src="{{ versionedAsset('assets/js/app.js') }}"></script>
	@else
		<script src="{{ versionedAsset('assets/rtl/js/app.js') }}"></script>
	@endif

	<!-- Custom Library -->
	<script src="{{ versionedAsset('custom/js/custom.js') }}"></script>
