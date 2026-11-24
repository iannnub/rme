<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $isPublicLogin = request()->routeIs('login') || request()->is('login');
            $seoTitle = 'Masuk - RME Keperawatan Kamar Operasi (OKA)';
            $seoDescription = 'Sistem RME Keperawatan Kamar Operasi untuk dokumentasi asuhan perioperatif, Surgical Safety Checklist WHO, dan serah terima pasien.';
            $canonicalUrl = request()->url();
            $ogImageUrl = request()->getSchemeAndHttpHost() . '/og-image.png';
            $jsonLd = [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'WebApplication',
                        'name' => 'RME Keperawatan Kamar Operasi (OKA)',
                        'applicationCategory' => 'HealthApplication',
                        'operatingSystem' => 'Web',
                        'inLanguage' => 'id-ID',
                        'url' => $canonicalUrl,
                        'description' => $seoDescription,
                    ],
                    [
                        '@type' => 'MedicalOrganization',
                        'name' => 'Unit Kamar Operasi (OKA) - RSU Universitas Muhammadiyah Jember',
                        'url' => request()->getSchemeAndHttpHost(),
                    ],
                ],
            ];
        @endphp

        <title inertia>{{ $seoTitle }}</title>
        <meta name="description" content="{{ $seoDescription }}">
        <meta name="robots" content="{{ $isPublicLogin ? 'index, follow' : 'noindex, nofollow' }}">
        <meta name="theme-color" content="#0A2540">
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="alternate icon" href="/favicon.ico">

        @if ($isPublicLogin)
            <link rel="canonical" href="{{ $canonicalUrl }}">

            <meta property="og:type" content="website">
            <meta property="og:site_name" content="RME OKA">
            <meta property="og:locale" content="id_ID">
            <meta property="og:url" content="{{ $canonicalUrl }}">
            <meta property="og:title" content="{{ $seoTitle }}">
            <meta property="og:description" content="{{ $seoDescription }}">
            <meta property="og:image" content="{{ $ogImageUrl }}">
            <meta property="og:image:width" content="1200">
            <meta property="og:image:height" content="630">
            <meta property="og:image:alt" content="RME Keperawatan Kamar Operasi (OKA) - Sistem Dokumentasi Perioperatif">

            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ $seoTitle }}">
            <meta name="twitter:description" content="{{ $seoDescription }}">
            <meta name="twitter:image" content="{{ $ogImageUrl }}">

            <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
