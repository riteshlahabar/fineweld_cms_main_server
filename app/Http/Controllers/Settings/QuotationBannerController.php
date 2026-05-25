<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\QuotationBanner;
use Illuminate\Http\Request;

class QuotationBannerController extends Controller
{
    public function index()
    {
        $banner = QuotationBanner::first();

        return view('quotation-banner', compact('banner'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'quotation_ad_banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'quotation_header_banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $banner = QuotationBanner::firstOrCreate([]);

        $folderPath = public_path('quotation_banner');

        if (! is_dir($folderPath)) {
            mkdir($folderPath, 0755, true);
        }

        if ($request->hasFile('quotation_ad_banner')) {
            if ($banner->quotation_ad_banner && file_exists($folderPath.'/'.$banner->quotation_ad_banner)) {
                unlink($folderPath.'/'.$banner->quotation_ad_banner);
            }

            $file = $request->file('quotation_ad_banner');
            $fileName = 'quotation-ad-'.time().'.'.$file->getClientOriginalExtension();
            $file->move($folderPath, $fileName);

            $banner->quotation_ad_banner = $fileName;
        }

        if ($request->hasFile('quotation_header_banner')) {
            if ($banner->quotation_header_banner && file_exists($folderPath.'/'.$banner->quotation_header_banner)) {
                unlink($folderPath.'/'.$banner->quotation_header_banner);
            }

            $file = $request->file('quotation_header_banner');
            $fileName = 'quotation-header-'.time().'.'.$file->getClientOriginalExtension();
            $file->move($folderPath, $fileName);

            $banner->quotation_header_banner = $fileName;
        }

        $banner->save();

        return redirect()
            ->route('settings.quotation.banner')
            ->with('success', 'Quotation banner updated successfully.');
    }
}