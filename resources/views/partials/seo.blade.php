<link rel="canonical" href="{{ $seo['canonical'] }}">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Buddhist Altar">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:image" content="{{ $seo['image'] }}">
<meta property="og:locale" content="en">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
<meta name="twitter:image" content="{{ $seo['image'] }}">
<script type="application/ld+json">@json($seo['structured_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)</script>
