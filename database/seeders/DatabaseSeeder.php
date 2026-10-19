<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ensure directories exist
        Storage::disk('public')->makeDirectory('user_photos');
        Storage::disk('public')->makeDirectory('fnb_images');

        $adminSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">
  <defs>
    <linearGradient id="adminBg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#1e1b4b"/>
      <stop offset="100%" stop-color="#3b82f6"/>
    </linearGradient>
    <linearGradient id="shieldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#60a5fa"/>
      <stop offset="100%" stop-color="#2563eb"/>
    </linearGradient>
    <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="4" stdDeviation="6" flood-color="#3b82f6" flood-opacity="0.4"/>
    </filter>
  </defs>
  <rect width="200" height="200" rx="36" fill="url(#adminBg)"/>
  <circle cx="100" cy="100" r="75" fill="none" stroke="#60a5fa" stroke-width="2" stroke-opacity="0.25" stroke-dasharray="6 4"/>
  <g filter="url(#glow)">
    <circle cx="100" cy="72" r="28" fill="#ffffff"/>
    <path d="M52 142 C52 112, 74 106, 100 106 C126 106, 148 112, 148 142 Z" fill="#e0e7ff"/>
  </g>
  <g transform="translate(116, 92)">
    <circle cx="16" cy="16" r="18" fill="#1e1b4b" stroke="#3b82f6" stroke-width="2.5"/>
    <path d="M16 7 L24 11 V18 C24 23 20.5 26.5 16 28 C11.5 26.5 8 23 8 18 V11 Z" fill="url(#shieldGrad)"/>
    <path d="M13 17 L15.5 19.5 L19.5 14" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
  </g>
  <rect x="58" y="160" width="84" height="24" rx="12" fill="#312e81" stroke="#60a5fa" stroke-width="1.5"/>
  <text x="100" y="176" text-anchor="middle" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="11" font-weight="700" fill="#bfdbfe" letter-spacing="1.5">ADMIN</text>
</svg>
SVG;

        $kasirSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">
  <defs>
    <linearGradient id="kasirBg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#064e3b"/>
      <stop offset="100%" stop-color="#059669"/>
    </linearGradient>
    <linearGradient id="posGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#34d399"/>
      <stop offset="100%" stop-color="#059669"/>
    </linearGradient>
    <filter id="glowKasir" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="4" stdDeviation="6" flood-color="#10b981" flood-opacity="0.4"/>
    </filter>
  </defs>
  <rect width="200" height="200" rx="36" fill="url(#kasirBg)"/>
  <circle cx="100" cy="100" r="75" fill="none" stroke="#34d399" stroke-width="2" stroke-opacity="0.25" stroke-dasharray="6 4"/>
  <g filter="url(#glowKasir)">
    <circle cx="100" cy="72" r="28" fill="#ffffff"/>
    <path d="M52 142 C52 112, 74 106, 100 106 C126 106, 148 112, 148 142 Z" fill="#d1fae5"/>
  </g>
  <path d="M72 70 C72 52, 128 52, 128 70" stroke="#a7f3d0" stroke-width="4" stroke-linecap="round" fill="none"/>
  <circle cx="70" cy="72" r="6" fill="#34d399"/>
  <circle cx="130" cy="72" r="6" fill="#34d399"/>
  <path d="M130 72 Q130 90 114 90" stroke="#34d399" stroke-width="3" stroke-linecap="round" fill="none"/>
  <circle cx="112" cy="90" r="3.5" fill="#a7f3d0"/>
  <g transform="translate(116, 92)">
    <circle cx="16" cy="16" r="18" fill="#064e3b" stroke="#10b981" stroke-width="2.5"/>
    <rect x="9" y="9" width="14" height="14" rx="3" fill="url(#posGrad)"/>
    <text x="16" y="20" text-anchor="middle" font-family="sans-serif" font-size="10" font-weight="900" fill="#ffffff">$</text>
  </g>
  <rect x="60" y="160" width="80" height="24" rx="12" fill="#064e3b" stroke="#34d399" stroke-width="1.5"/>
  <text x="100" y="176" text-anchor="middle" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="11" font-weight="700" fill="#a7f3d0" letter-spacing="1.5">KASIR</text>
</svg>
SVG;

        Storage::disk('public')->put('user_photos/admin.svg', $adminSvg);
        Storage::disk('public')->put('user_photos/kasir.svg', $kasirSvg);

        User::factory()->create([
            'name' => 'Admin System',
            'username' => 'admin',
            'role' => 'admin',
            'password' => bcrypt('password'),
            'photo_path' => 'user_photos/admin.svg'
        ]);

        User::factory()->create([
            'name' => 'Kasir 1',
            'username' => 'kasir',
            'role' => 'kasir',
            'password' => bcrypt('password'),
            'photo_path' => 'user_photos/kasir.svg'
        ]);

        \App\Models\Package::create(['name' => 'Regular Siang', 'price' => 25000]);
        \App\Models\Package::create(['name' => 'Regular Malam', 'price' => 35000]);
        \App\Models\Package::create(['name' => 'VIP', 'price' => 50000]);
        
        for($i = 1; $i <= 16; $i++) {
            \App\Models\Table::create(['name' => 'Meja ' . $i, 'relay_channel' => $i]);
        }

        $itemMeta = [
            'Es Teh Manis'         => ['icon' => '🧋', 'bg1' => '#0284c7', 'bg2' => '#0369a1', 'tag' => 'DINGIN • MANIS'],
            'Es Teh Tawar'         => ['icon' => '🥤', 'bg1' => '#0ea5e9', 'bg2' => '#0284c7', 'tag' => 'SEGAR ALAMI'],
            'Es Jeruk Segar'       => ['icon' => '🍊', 'bg1' => '#ea580c', 'bg2' => '#c2410c', 'tag' => 'PERASAN ASLI'],
            'Es Lemon Tea'         => ['icon' => '🍋', 'bg1' => '#ca8a04', 'bg2' => '#a16207', 'tag' => 'ASAM MANIS'],
            'Es Milo'              => ['icon' => '🍫', 'bg1' => '#78350f', 'bg2' => '#451a03', 'tag' => 'COKELAT PEKAT'],
            'Es Good Day'          => ['icon' => '☕', 'bg1' => '#854d0e', 'bg2' => '#713f12', 'tag' => 'KOPI DINGIN'],
            'Es Nutrisari'         => ['icon' => '🍹', 'bg1' => '#f97316', 'bg2' => '#c2410c', 'tag' => 'SARI JERUK'],
            'Air Mineral'          => ['icon' => '💧', 'bg1' => '#0284c7', 'bg2' => '#075985', 'tag' => 'MINERAL SEGAR'],

            'Kopi Tubruk'          => ['icon' => '☕', 'bg1' => '#451a03', 'bg2' => '#1c0a00', 'tag' => 'KOPI HITAM'],
            'Kopi Susu'            => ['icon' => '☕', 'bg1' => '#92400e', 'bg2' => '#78350f', 'tag' => 'KOPI SUSU'],
            'Teh Panas'            => ['icon' => '🍵', 'bg1' => '#15803d', 'bg2' => '#166534', 'tag' => 'HANGAT ALAMI'],
            'Kapal Api'            => ['icon' => '☕', 'bg1' => '#451a03', 'bg2' => '#1c0a00', 'tag' => 'LEGENDARIS'],
            'Indocafe'             => ['icon' => '☕', 'bg1' => '#854d0e', 'bg2' => '#713f12', 'tag' => 'KOPI KRIMER'],

            'Kentang Goreng'       => ['icon' => '🍟', 'bg1' => '#db2777', 'bg2' => '#9d174d', 'tag' => 'CRISPY GURIH'],
            'Pisang Goreng'        => ['icon' => '🍌', 'bg1' => '#d97706', 'bg2' => '#b45309', 'tag' => 'MANIS RENYAH'],
            'Roti Bakar'           => ['icon' => '🍞', 'bg1' => '#b45309', 'bg2' => '#78350f', 'tag' => 'PANGGANG GURIH'],
            'French Fries'         => ['icon' => '🍟', 'bg1' => '#e11d48', 'bg2' => '#9f1239', 'tag' => 'GOLDEN FRIES'],
            'Chicken Wings'        => ['icon' => '🍗', 'bg1' => '#dc2626', 'bg2' => '#991b1b', 'tag' => 'SPICY CRISPY'],
            'Nugget Goreng'        => ['icon' => '🍢', 'bg1' => '#d97706', 'bg2' => '#9a3412', 'tag' => 'CRISPY NUGGET'],

            'Nasi Goreng'          => ['icon' => '🍳', 'bg1' => '#16a34a', 'bg2' => '#15803d', 'tag' => 'SPESIAL BILIAR'],
            'Mie Goreng'           => ['icon' => '🍜', 'bg1' => '#d97706', 'bg2' => '#b45309', 'tag' => 'GORENG SPESIAL'],
            'Mie Rebus'            => ['icon' => '🍲', 'bg1' => '#ca8a04', 'bg2' => '#854d0e', 'tag' => 'KUAH HANGAT'],
            'Nasi Ayam Goreng'     => ['icon' => '🍗', 'bg1' => '#b45309', 'bg2' => '#78350f', 'tag' => 'AYAM & SAMBAL'],
            'Nasi Rendang'         => ['icon' => '🥩', 'bg1' => '#831843', 'bg2' => '#500724', 'tag' => 'DAGING EMPUK'],
            'Gado-gado'            => ['icon' => '🥗', 'bg1' => '#15803d', 'bg2' => '#14532d', 'tag' => 'BUMBU KACANG'],
            'Sate Ayam (10 tusuk)' => ['icon' => '🍢', 'bg1' => '#c2410c', 'bg2' => '#7c2d12', 'tag' => 'SATE PANGGANG'],
            'Sosis Goreng'         => ['icon' => '🌭', 'bg1' => '#e11d48', 'bg2' => '#881337', 'tag' => 'SOSIS JUMBO'],
        ];

        $fnbItems = [
            // Minuman Dingin
            ['name' => 'Es Teh Manis', 'category' => 'Minuman Dingin', 'price' => 8000],
            ['name' => 'Es Teh Tawar', 'category' => 'Minuman Dingin', 'price' => 5000],
            ['name' => 'Es Jeruk Segar', 'category' => 'Minuman Dingin', 'price' => 12000],
            ['name' => 'Es Lemon Tea', 'category' => 'Minuman Dingin', 'price' => 15000],
            ['name' => 'Es Milo', 'category' => 'Minuman Dingin', 'price' => 15000],
            ['name' => 'Es Good Day', 'category' => 'Minuman Dingin', 'price' => 12000],
            ['name' => 'Es Nutrisari', 'category' => 'Minuman Dingin', 'price' => 10000],
            ['name' => 'Air Mineral', 'category' => 'Minuman Dingin', 'price' => 5000],

            // Minuman Panas
            ['name' => 'Kopi Tubruk', 'category' => 'Minuman Panas', 'price' => 8000],
            ['name' => 'Kopi Susu', 'category' => 'Minuman Panas', 'price' => 15000],
            ['name' => 'Teh Panas', 'category' => 'Minuman Panas', 'price' => 5000],
            ['name' => 'Kapal Api', 'category' => 'Minuman Panas', 'price' => 8000],
            ['name' => 'Indocafe', 'category' => 'Minuman Panas', 'price' => 8000],

            // Snack
            ['name' => 'Kentang Goreng', 'category' => 'Snack', 'price' => 18000],
            ['name' => 'Pisang Goreng', 'category' => 'Snack', 'price' => 12000],
            ['name' => 'Roti Bakar', 'category' => 'Snack', 'price' => 15000],
            ['name' => 'French Fries', 'category' => 'Snack', 'price' => 20000],
            ['name' => 'Chicken Wings', 'category' => 'Snack', 'price' => 25000],
            ['name' => 'Nugget Goreng', 'category' => 'Snack', 'price' => 18000],

            // Makanan
            ['name' => 'Nasi Goreng', 'category' => 'Makanan', 'price' => 22000],
            ['name' => 'Mie Goreng', 'category' => 'Makanan', 'price' => 20000],
            ['name' => 'Mie Rebus', 'category' => 'Makanan', 'price' => 20000],
            ['name' => 'Nasi Ayam Goreng', 'category' => 'Makanan', 'price' => 25000],
            ['name' => 'Nasi Rendang', 'category' => 'Makanan', 'price' => 30000],
            ['name' => 'Gado-gado', 'category' => 'Makanan', 'price' => 18000],
            ['name' => 'Sate Ayam (10 tusuk)', 'category' => 'Makanan', 'price' => 25000],
            ['name' => 'Sosis Goreng', 'category' => 'Makanan', 'price' => 15000],
        ];

        foreach ($fnbItems as $item) {
            $meta = $itemMeta[$item['name']] ?? [
                'icon' => '🍽️',
                'bg1' => '#0284c7',
                'bg2' => '#0369a1',
                'tag' => strtoupper($item['category'] ?? 'MENU')
            ];

            $slug = \Illuminate\Support\Str::slug($item['name']);
            $filename = "fnb_images/{$slug}.svg";
            $formattedPrice = 'Rp ' . number_format($item['price'], 0, ',', '.');
            $safeName = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');
            $safeTag = htmlspecialchars($meta['tag'], ENT_QUOTES, 'UTF-8');
            $safePrice = htmlspecialchars($formattedPrice, ENT_QUOTES, 'UTF-8');

            $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240" viewBox="0 0 240 240">
  <defs>
    <linearGradient id="grad_{$slug}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$meta['bg1']}"/>
      <stop offset="100%" stop-color="{$meta['bg2']}"/>
    </linearGradient>
    <filter id="shadow_{$slug}" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="4" stdDeviation="5" flood-color="#000000" flood-opacity="0.3"/>
    </filter>
  </defs>
  <rect width="240" height="240" rx="32" fill="url(#grad_{$slug})"/>
  <circle cx="215" cy="25" r="55" fill="#ffffff" fill-opacity="0.08"/>
  <circle cx="25" cy="215" r="65" fill="#000000" fill-opacity="0.08"/>
  <g filter="url(#shadow_{$slug})">
    <circle cx="120" cy="92" r="46" fill="#ffffff" fill-opacity="0.95"/>
    <text x="120" y="108" text-anchor="middle" font-size="50">{$meta['icon']}</text>
  </g>
  <rect x="130" y="16" width="94" height="26" rx="13" fill="#000000" fill-opacity="0.25"/>
  <text x="177" y="33" text-anchor="middle" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="11" font-weight="800" fill="#ffffff">{$safePrice}</text>
  <text x="120" y="170" text-anchor="middle" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="10" font-weight="700" fill="#ffffff" fill-opacity="0.8" letter-spacing="1.2">{$safeTag}</text>
  <text x="120" y="198" text-anchor="middle" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="15" font-weight="800" fill="#ffffff">{$safeName}</text>
</svg>
SVG;

            Storage::disk('public')->put($filename, $svg);

            \App\Models\FnbItem::create(array_merge($item, ['image_path' => $filename]));
        }
    }
}
