<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstalledModule;
use Illuminate\Http\Request;
use App\Services\ModuleManager;

class ModuleController extends Controller
{
    protected $moduleManager;

    public function __construct(ModuleManager $moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    public function showInstallPartnership()
    {
        // Check if already installed
        $modules = InstalledModule::all();

        return view('admin.modules.install-partnership', compact('modules'));
    }

    public function installPartnership(Request $request)
    {
        $request->validate([
            'module_file' => 'required|file|mimes:zip|max:102400' // 100MB max
        ]);

        try {
            $this->moduleManager->installPartnershipModule($request->file('module_file'));

            session(['record' => [
                'type' => 'success',
                'status' => 'Success',
                'message' => __('app.module_installed_successfully'), // Save or update
            ]]);

            return redirect()->route('admin.modules.install-partnership')
                ->with('success', 'Partnership module installed successfully!');

        } catch (\Exception $e) {
            session(['record' => [
                'type' => 'danger',
                'status' => 'Failed',
                'message' => __('app.failed_to_install_module'), // Save or update
            ]]);

            return redirect()->back()
                ->with('error', 'Installation failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function uninstall()
    {
        try {
            $moduleInfo = $this->moduleManager->uninstallPartnershipModule();

            session(['record' => [
                'type' => 'success',
                'status' => 'Success',
                'message' => __('app.module_uninstalled_successfully', ['version' => $moduleInfo['version']]), // Save or update
            ]]);

            return redirect()->route('admin.modules.install-partnership')
                ->with('success', "Partnership module {$moduleInfo['version']} uninstalled successfully!");

        } catch (\Exception $e) {
            return redirect()->route('admin.modules.install-partnership')
                ->with('error', 'Uninstallation failed: ' . $e->getMessage());
        }
    }

    // Activate Partnership Module
    public function activate()
    {
        try {
            $this->moduleManager->activatePartnershipModule();

            session(['record' => [
                'type' => 'success',
                'status' => 'Success',
                'message' => __('app.partnership_module_activated_successfully'), // Save or update
            ]]);

            return redirect()->route('admin.modules.install-partnership')
                ->with('success', 'Partnership module activated successfully!');
        } catch (\Exception $e) {
            return redirect()->route('admin.modules.install-partnership')
                ->with('error', 'Activation failed: ' . $e->getMessage());
        }
    }

    // Deactivate Partnership Module
    public function deactivate()
    {
        try {
            $this->moduleManager->deactivatePartnershipModule();

            session(['record' => [
                'type' => 'success',
                'status' => 'Success',
                'message' => __('app.partnership_module_deactivated_successfully'), // Save or update
            ]]);

            return redirect()->route('admin.modules.install-partnership')
                ->with('success', 'Partnership module deactivated successfully!');
        } catch (\Exception $e) {
            return redirect()->route('admin.modules.install-partnership')
                ->with('error', 'Deactivation failed: ' . $e->getMessage());
        }
    }
}
