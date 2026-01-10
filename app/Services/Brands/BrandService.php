<?php

namespace App\Services\Brands;

use App\Models\Brand;
use Illuminate\Support\Facades\Cache;

class BrandService
{
    /**
     * Get the current active brand.
     * Priorities:
     * 1. Explicitly passed ID (not handled here but in caller)
     * 2. Config/Env default (if any)
     * 3. Database default
     * 4. First brand found
     */
    public function getCurrentBrand(): ?Brand
    {
        // For multi-tenant apps, we would check subdomain or user session.
        // For now, we return the default brand.

        return Cache::remember('default_brand', 3600, function () {
            $brand = Brand::where('is_default', true)->first();

            if (!$brand) {
                $brand = Brand::first();
            }

            return $brand;
        });
    }

    /**
     * Get brand variables for template injection.
     */
    public function getBrandVariables(?Brand $brand = null): array
    {
        if (!$brand) {
            $brand = $this->getCurrentBrand();
        }

        if (!$brand) {
            return [
                'brand_name' => config('app.name'),
                'brand_logo' => '',
                'brand_color_primary' => '#000000', // Default black
                'brand_color_secondary' => '#ffffff',
                'brand_footer' => '',
                'brand_url' => config('app.url'),
                'brand_support_email' => '',
            ];
        }

        return [
            'brand_name' => $brand->name,
            'brand_logo' => $brand->logo_url,
            'brand_color_primary' => $brand->primary_color,
            'brand_color_secondary' => $brand->secondary_color,
            'brand_footer' => $brand->footer_text,
            'brand_url' => $brand->website_url ?? config('app.url'),
            'brand_support_email' => $brand->support_email,
        ];
    }
}
