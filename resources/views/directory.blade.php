<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.google-analytics')
    @include('partials.seo')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="theme-color" content="#4aabf0">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Buddhist Altar">
    <title>{{ $directory['page_title'] }}</title>
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="shrine-bg min-h-screen text-sky-950 antialiased">
    <div class="sky-clouds" aria-hidden="true"></div>
    <div class="light-rays" aria-hidden="true"></div>

    <div class="relative flex min-h-screen flex-col overflow-x-hidden">
        <header class="directory-hero relative z-10 px-4 pb-8 pt-10 text-center sm:pt-14">
            <h1 class="font-medium tracking-wide text-white drop-shadow-md sm:text-3xl">
                {{ $directory['title'] }}
            </h1>
            <p class="mt-2 text-lg italic text-white/90 drop-shadow-sm sm:text-xl">
                {{ $directory['subtitle'] }}
            </p>
            <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-white/80">
                Enter a shrine to light butter lamps, offer incense, water, flowers, and music. This rimé platform gathers buddhas and bodhisattvas of all traditions for practitioners everywhere.
            </p>
        </header>

        <section class="directory-buddhas relative z-10 px-4 pb-10" aria-label="Buddhas and bodhisattvas">
            <div class="directory-buddha-rows mx-auto max-w-6xl">
                @foreach ($rows as $rowIndex => $row)
                    <div class="directory-buddha-row" style="--row-index: {{ $rowIndex }};">
                        @foreach ($row as $figureIndex => $figure)
                            @php
                                $delay = ($rowIndex * 0.7) + ($figureIndex * 0.35);
                                $hasImage = ! empty($figure['image']);
                                $imagePath = $hasImage ? public_path($figure['image']) : null;
                                $imageVersion = $imagePath && file_exists($imagePath) ? filemtime($imagePath) : time();
                            @endphp
                            <a
                                href="{{ url($figure['url']) }}"
                                class="directory-buddha-card"
                                style="--float-delay: {{ $delay }}s;"
                                aria-label="{{ $hasImage ? 'Enter the '.$figure['label'].' shrine' : 'Read homage to '.$figure['label'] }}"
                            >
                                <div class="directory-buddha-image-shell">
                                    @if ($hasImage)
                                        <img
                                            src="{{ asset($figure['image']) }}?v={{ $imageVersion }}"
                                            alt="{{ $figure['alt'] }}"
                                            width="256"
                                            height="256"
                                            class="{{ $figure['image_class'] }} directory-buddha-image"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    @else
                                        <div class="directory-buddha-placeholder" aria-hidden="true">
                                            <span>{{ mb_substr($figure['label'], 0, 1) }}</span>
                                        </div>
                                    @endif
                                </div>
                                <span class="directory-buddha-label">{{ $figure['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>

        <main class="directory-main relative z-10 mx-auto w-full max-w-3xl px-4 pb-16">
            <section class="directory-panel" aria-labelledby="homage-prayer-heading">
                <header class="text-center">
                    <h2 id="homage-prayer-heading" class="text-sm tracking-[0.25em] uppercase text-sky-950/80">Verses of Homage</h2>
                </header>
                @include('partials.buddhas-homage-prayer')
            </section>

            <section class="directory-panel mt-8" aria-labelledby="offering-benefits-heading">
                @include('partials.shrine-offering-benefits')
            </section>
        </main>
    </div>

    <div id="cookie-consent" class="cookie-consent" hidden aria-hidden="true" role="dialog" aria-labelledby="cookie-consent-title">
        <p id="cookie-consent-title" class="cookie-consent-text">This site uses cookies and local storage to remember your preferences.</p>
        <button type="button" id="btn-accept-cookies" class="btn-shrine cookie-consent-btn">Accept</button>
    </div>

    <script>
        (function () {
            const key = 'buddhist_altar_cookies_accepted';
            const banner = document.getElementById('cookie-consent');
            const button = document.getElementById('btn-accept-cookies');

            if (!localStorage.getItem(key) && banner) {
                banner.hidden = false;
                banner.setAttribute('aria-hidden', 'false');
            }

            button?.addEventListener('click', function () {
                localStorage.setItem(key, '1');
                if (banner) {
                    banner.hidden = true;
                    banner.setAttribute('aria-hidden', 'true');
                }
            });
        })();
    </script>
</body>
</html>
