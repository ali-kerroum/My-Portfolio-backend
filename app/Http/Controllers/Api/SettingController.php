<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Get visible sections (public — used by the portfolio frontend).
     */
    public function visibleSections()
    {
        $sections = Setting::getValue('visible_sections', [
            'hero', 'about', 'experience', 'services', 'projects', 'contact'
        ]);

        return response()->json($sections);
    }

    /**
     * Get all sections with their visibility state (admin).
     */
    public function sections()
    {
        $allSections = [
            ['key' => 'hero', 'label' => 'Hero'],
            ['key' => 'about', 'label' => 'About'],
            ['key' => 'experience', 'label' => 'Experiences'],
            ['key' => 'services', 'label' => 'Services'],
            ['key' => 'projects', 'label' => 'Projects'],
            ['key' => 'contact', 'label' => 'Contact'],
        ];

        $visible = Setting::getValue('visible_sections', [
            'hero', 'about', 'experience', 'services', 'projects', 'contact'
        ]);

        $result = array_map(function ($section) use ($visible) {
            $section['visible'] = in_array($section['key'], $visible);
            return $section;
        }, $allSections);

        return response()->json($result);
    }

    /**
     * Update visible sections (admin).
     */
    public function updateSections(Request $request)
    {
        $request->validate([
            'visible_sections' => 'required|array',
            'visible_sections.*' => 'string|in:hero,about,experience,services,projects,contact',
        ]);

        Setting::setValue('visible_sections', $request->visible_sections);

        return response()->json(['message' => 'Sections updated']);
    }

    /**
     * Get hero content (public).
     */
    public function heroContent()
    {
        $hero = Setting::getValue('hero_content', null);
        return response()->json($hero);
    }

    /**
     * Update hero content (admin).
     */
    public function updateHeroContent(Request $request)
    {
        $request->validate([
            'eyebrow' => 'nullable|string|max:200',
            'title' => 'nullable|string|max:300',
            'subtitle' => 'nullable|string|max:300',
            'description' => 'nullable|string|max:1000',
            'highlights' => 'nullable|array',
            'highlights.*' => 'string|max:200',
            'cta_primary_label' => 'nullable|string|max:100',
            'cta_primary_section' => 'nullable|string|max:100',
            'cta_secondary_label' => 'nullable|string|max:100',
            'cta_secondary_section' => 'nullable|string|max:100',
            'links' => 'nullable|array',
            'links.*.label' => 'required_with:links|string|max:100',
            'links.*.href' => 'required_with:links|string|max:500',
            'profile_image' => 'nullable|string|max:500',
            'name' => 'nullable|string|max:200',
            'bio' => 'nullable|string|max:500',
            'status_text' => 'nullable|string|max:200',
            'role_text' => 'nullable|string|max:300',
            'metrics' => 'nullable|array',
            'metrics.*.value' => 'required_with:metrics|string|max:100',
            'metrics.*.label' => 'required_with:metrics|string|max:200',
        ]);

        Setting::setValue('hero_content', $request->all());

        return response()->json(['message' => 'Hero content updated']);
    }

    /**
     * Upload hero profile image.
     */
    public function uploadHeroImage(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        // Use Cloudinary in production, local storage in development
        if (env('CLOUDINARY_URL')) {
            $result = $request->file('image')->storeOnCloudinary('portfolio/hero');
            $url = $result->getSecurePath();
        } else {
            $path = $request->file('image')->store('hero', 'public');
            $url = '/storage/' . $path;
        }

        return response()->json([
            'url' => $url,
        ]);
    }
}
