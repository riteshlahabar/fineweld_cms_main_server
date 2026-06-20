<?php

namespace App\Providers;

use App\Enums\App;
use App\Enums\Date;
use App\Enums\Timezone;
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class CompanyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        if (env('INSTALLATION_STATUS')) {
            // Bind the timezone to a service
            $this->app->singleton('company', function () {
                $company = CacheService::get('company');

                $timezone = $company ? $company->timezone : Timezone::APP_DEFAULT_TIME_ZONE->value;

                $dateFormat = $company ? $company->date_format : Date::APP_DEFAULT_DATE_FORMAT->value;

                $timeFormat = $company ? $company->time_format : App::APP_DEFAULT_TIME_FORMAT->value;

                $active_sms_api = $company ? $company->active_sms_api : null;

                $isEnableCrm = $company ? $company->is_enable_crm : null;

                return [
                    'name' => $company->name ?? '',
                    'email' => $company->email ?? '',
                    'mobile' => $company->mobile ?? '',
                    'address' => $company->address ?? '',
                    'tax_number' => $company->tax_number ?? '',
                    'timezone' => $timezone,
                    'date_format' => $dateFormat,
                    'time_format' => $timeFormat,
                    'active_sms_api' => $active_sms_api,
                    'number_precision' => $company->number_precision ?? 2,
                    'quantity_precision' => $company->quantity_precision ?? 2,

                    'show_sku' => $company->show_sku ?? 1, // Item Settings, Sidebar-> Settings -> Company ->Item
                    'show_mrp' => $company->show_mrp ?? 1, // Item Settings, Sidebar-> Settings -> Company ->Item
                    'restrict_to_sell_above_mrp' => $company->restrict_to_sell_above_mrp ?? 0, // Item Settings, Sidebar-> Settings -> Company ->Item
                    'restrict_to_sell_below_msp' => $company->restrict_to_sell_below_msp ?? 0, // Item Settings, Sidebar-> Settings -> Company ->Item
                    'auto_update_sale_price' => $company->auto_update_sale_price ?? 0, // Item Settings, Sidebar-> Settings -> Company ->Item
                    'auto_update_purchase_price' => $company->auto_update_purchase_price ?? 0, // Item Settings, Sidebar-> Settings -> Company ->Item
                    'auto_update_average_purchase_price' => $company->auto_update_average_purchase_price ?? 0, // Item Settings, Sidebar-> Settings -> Company ->Item

                    'is_item_name_unique' => $company->is_item_name_unique ?? 1, // Item Settings, Sidebar-> Settings -> Company ->Item
                    'tax_type' => $company->tax_type ?? 'tax', // Item Settings, Sidebar-> Settings -> Company ->Item

                    'enable_serial_tracking' => $company->enable_serial_tracking ?? 1, // Item Settings, Sidebar-> Settings -> Company ->Item
                    'enable_batch_tracking' => $company->enable_batch_tracking ?? 2, // Item Settings, Sidebar-> Settings -> Company ->Item
                    'is_batch_compulsory' => $company->is_batch_compulsory ?? 0, // Item Settings, Sidebar-> Settings -> Company ->Item
                    'enable_mfg_date' => $company->enable_mfg_date ?? 1, // Item Settings, Sidebar-> Settings -> Company ->Item
                    'enable_exp_date' => $company->enable_exp_date ?? 1, // Item Settings, Sidebar-> Settings -> Company ->Item
                    'enable_color' => $company->enable_color ?? 0, // Item Settings, Sidebar-> Settings -> Company ->Item
                    'enable_size' => $company->enable_size ?? 0, // Item Settings, Sidebar-> Settings -> Company ->Item
                    'enable_model' => $company->enable_model ?? 0, // Item Settings, Sidebar-> Settings -> Company ->Item

                    'show_tax_summary' => $company->show_tax_summary ?? 1, // Print Settings, Sidebar-> Settings -> Company ->Print
                    'state_id' => $company->state_id ?? null, // Print Settings, Sidebar-> Settings -> Company ->Print
                    'terms_and_conditions' => $company->terms_and_conditions ?? null, // Print Settings, Sidebar-> Settings -> Company ->Print
                    'show_terms_and_conditions_on_invoice' => $company->show_terms_and_conditions_on_invoice ?? 1, // Print Settings, Sidebar-> Settings -> Company ->Print
                    'show_party_due_payment' => $company->show_party_due_payment ?? 1, // Print Settings, Sidebar-> Settings -> Company ->Print
                    'bank_details' => $company->bank_details ?? null, // Print Settings, Sidebar-> Settings -> Company ->Print
                    'signature' => $company->signature ?? null, // Print Settings, Sidebar-> Settings -> Company ->Print
                    'show_signature_on_invoice' => $company->show_signature_on_invoice ?? 1, // Print Settings, Sidebar-> Settings -> Company ->Print
                    'show_brand_on_invoice' => $company->show_brand_on_invoice ?? 1, // Item Settings, Sidebar-> Settings -> Company ->Print
                    'show_tax_number_on_invoice' => $company->show_tax_number_on_invoice ?? 1, // Item Settings, Sidebar-> Settings -> Company ->Print
                    'colored_logo' => $company->colored_logo ?? null, // Print Settings, Sidebar-> Settings -> Company ->Print

                    'is_enable_crm' => $isEnableCrm ?? 0, // Print Settings, Sidebar-> Settings -> Company ->Module
                    'is_enable_carrier' => $company->is_enable_carrier ?? 1, // Print Settings, Sidebar-> Settings -> Company ->Module
                    'is_enable_carrier_charge' => $company->is_enable_carrier_charge ?? 1, // Print Settings, Sidebar-> Settings -> Company ->General
                    'show_discount' => $company->show_discount ?? 1, // Enable Discount Setting: Sidebar-> Settings -> Company ->General
                    'allow_negative_stock_billing' => $company->allow_negative_stock_billing ?? 1, // Enable Negative Stock Billing - Setting: Sidebar-> Settings -> Company ->General
                    'show_hsn' => $company->show_hsn ?? 1, // Item Settings, Sidebar-> Settings -> Company ->Item
                    'is_enable_secondary_currency' => $company->is_enable_secondary_currency ?? 1, // Item Settings, Sidebar-> Settings -> Company ->General

                ];
            });
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (env('INSTALLATION_STATUS')) {
            $company = app('company');

            // Set the default timezone
            date_default_timezone_set($company['timezone']);

            // Use the timezone and date format in Carbon
            // Carbon::setLocale(app('company')['timezone']);

            /**
             * depricated
             * Carbon::useStrictMode(true);
             */
            $carbon = new Carbon;
            $carbon->settings(['strictMode' => true]);

            /**
             * Email setup
             * */
            if (! empty($company['email'])) {
                Config::set('mail.from.address', $company['email']);
            }

            if (! empty($company['name'])) {
                Config::set('mail.from.name', $company['name']);
            }
        }
    }
}
