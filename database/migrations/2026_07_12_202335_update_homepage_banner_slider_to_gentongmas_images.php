<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Source images shipped inside the repo (tracked in git), keyed by
     * destination slide position.
     */
    const SOURCE_BASE_PATH = 'packages/Webkul/Installer/src/Resources/assets/images/seeders/theme/sliders/gentongmas/';

    const SLIDES = [
        [
            'title' => 'Solusi Elektronik Untuk Kebutuhan Anda',
            'link' => '/',
            'source' => '1.webp',
        ],
        [
            'title' => 'Belanja Elektronik Jadi Lebih Mudah',
            'link' => '/',
            'source' => '2.webp',
        ],
        [
            'title' => 'Termurah, Terlengkap & Terpercaya',
            'link' => '/',
            'source' => '3.webp',
        ],
    ];

    /**
     * Run the migrations.
     *
     * Replaces the homepage image-carousel ("Karousel Gambar",
     * theme_customizations.id = 1) slides with the GentongMas Elektronik
     * banners for every locale that currently has a translation row.
     */
    public function up(): void
    {
        $themeCustomizationId = DB::table('theme_customizations')
            ->where('type', 'image_carousel')
            ->value('id');

        if (! $themeCustomizationId) {
            return;
        }

        $images = [];

        foreach (self::SLIDES as $slide) {
            $sourcePath = base_path(self::SOURCE_BASE_PATH.$slide['source']);

            if (! File::exists($sourcePath)) {
                continue;
            }

            $destinationDirectory = 'theme/'.$themeCustomizationId;
            $destinationFilename = 'gentongmas-'.pathinfo($slide['source'], PATHINFO_FILENAME).'.webp';
            $destinationPath = $destinationDirectory.'/'.$destinationFilename;

            Storage::disk('public')->put($destinationPath, File::get($sourcePath));

            $images[] = [
                'title' => $slide['title'],
                'link' => $slide['link'],
                'image' => 'storage/'.$destinationPath,
            ];
        }

        if (empty($images)) {
            return;
        }

        $locales = DB::table('theme_customization_translations')
            ->where('theme_customization_id', $themeCustomizationId)
            ->pluck('locale');

        foreach ($locales as $locale) {
            DB::table('theme_customization_translations')
                ->where('theme_customization_id', $themeCustomizationId)
                ->where('locale', $locale)
                ->update([
                    'options' => json_encode(['images' => $images]),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * Intentionally left as a no-op: reverting to the original stock
     * slider images/copy is handled by re-running the installer's
     * ThemeCustomizationTableSeeder, not by this migration.
     */
    public function down(): void
    {
        //
    }
};
