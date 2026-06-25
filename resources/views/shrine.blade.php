<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Offer a butter lamp and recite the dhāraṇī of Noble Avalokiteśvara.">
    <title>Namo Avalokiteshvaraya — Shrine Offerings</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/shrine.js'])
</head>
<body class="shrine-bg min-h-screen text-sky-950 antialiased overflow-x-hidden">
    <div class="sky-clouds" aria-hidden="true"></div>
    <div id="syllable-smoke" class="syllable-smoke" aria-hidden="true"></div>

    <script type="application/json" id="shrine-state">@json($shrineState)</script>

    <div class="relative flex min-h-screen flex-col">
        <div class="light-rays" aria-hidden="true"></div>

        <header class="relative z-10 flex flex-1 flex-col items-center pb-4">
            <div class="deity-hero w-full">
                <img
                    src="{{ asset('images/avalokiteshvara.webp') }}?v={{ filemtime(public_path('images/avalokiteshvara.webp')) }}"
                    alt="Avalokiteshvara, homage to the one who looks upon beings with compassion"
                    width="1024"
                    height="1024"
                    class="avalokiteshvara-image"
                    loading="eager"
                    decoding="async"
                >
            </div>

            <div class="deity-title-row mt-4 px-4">
                <div class="incense-shrine" aria-label="Burning incense offering on the left"></div>

                <div class="deity-title">
                    <h1 class="text-xl font-medium tracking-wide text-white drop-shadow-md sm:text-2xl">Namo Avalokiteshvaraya!</h1>
                    <p class="mt-1 text-sm italic text-white/85 drop-shadow-sm">Homage to The One Who Looks Upon Beings with Compassion!</p>
                </div>

                <div class="incense-shrine" aria-label="Burning incense offering on the right"></div>
            </div>

            <div id="incense-shrine-extra" class="incense-shrine-extra hidden" aria-label="Additional incense offerings"></div>

            <div id="shrine-altar" class="shrine-altar mt-6 px-4">
                <div class="altar-row altar-row-water">
                    <div id="offered-water-bowls" class="water-bowls-display hidden" aria-label="Water bowl offerings">
                        @for ($i = 1; $i <= 7; $i++)
                            <div class="water-bowl water-bowl-filled" data-position="{{ $i }}" hidden></div>
                        @endfor
                    </div>
                </div>
            </div>

            <div id="offered-flowers" class="offering-row offering-row--flowers mt-4 px-4" aria-label="Flower offerings">
                @foreach ($flowers as $flower)
                    <div class="flower-vase offered-flower" data-flower-id="{{ $flower->id }}" data-flower-type="{{ $flower->flower_type ?? 'lotus' }}" data-vase-color="{{ $flower->vase_color ?? 'blue' }}">
                        @if ($flower->name)
                            <span class="offering-name">{{ $flower->name }}</span>
                        @endif
                    </div>
                @endforeach
            </div>

            <div id="offered-lamps" class="offering-row offering-row--lamps mt-3 px-4" aria-label="Public butter lamp offerings">
                @foreach ($lamps as $lamp)
                    <div class="butter-lamp offered-lamp" data-lamp-id="{{ $lamp->id }}">
                        @if ($lamp->name)
                            <span class="lamp-name">{{ $lamp->name }}</span>
                        @endif
                    </div>
                @endforeach
            </div>

            <div id="offered-music" class="offering-row offering-row--music mt-3 px-4" aria-label="Music offerings"></div>
        </header>

        <footer class="offering-panel relative z-10 px-4 py-8">
            <div class="mx-auto flex max-w-7xl flex-col items-center gap-8">
                <section class="w-full" aria-labelledby="offerings-heading">
                    <div class="text-center">
                        <h2 id="offerings-heading" class="text-sm tracking-[0.25em] uppercase text-sky-950/80">Offerings</h2>
                    </div>

                    <div class="offerings-grid mt-8">
                        {{-- Incense --}}
                        <div class="offering-column">
                            <h3 class="offering-column-title">Incense</h3>
                            <p class="offering-column-desc">Adds a stick for fifteen minutes</p>
                            <div class="offering-preview incense-preview"></div>
                            <input type="text" id="incense-name" maxlength="100" placeholder="Name (optional)" class="name-input mt-4 w-full rounded px-3 py-2 text-sm italic">
                            <button type="button" id="btn-offer-incense" class="btn-shrine mt-3 w-full rounded px-4 py-2.5 text-sm tracking-wide">Offer incense</button>
                        </div>

                        {{-- Water bowls --}}
                        <div class="offering-column">
                            <h3 class="offering-column-title">Water Bowls</h3>
                            <p id="water-status" class="offering-column-desc">Seven bowls — one offering at a time</p>
                            <div id="water-bowls-ui" class="water-bowls-ui mt-2">
                                <div id="water-bowls-stack" class="water-bowls-stack">
                                    @for ($i = 1; $i <= 7; $i++)
                                        <div class="water-bowl water-bowl-stacked" style="--stack-index: {{ $i - 1 }}"></div>
                                    @endfor
                                </div>
                                <div id="water-bowls-active" class="water-bowls-active hidden">
                                    @for ($i = 1; $i <= 7; $i++)
                                        <button type="button" class="water-bowl water-bowl-offer" data-position="{{ $i }}" aria-label="Fill water bowl {{ $i }}"></button>
                                    @endfor
                                </div>
                            </div>
                            <button type="button" id="btn-begin-water" class="btn-shrine mt-4 w-full rounded px-4 py-2.5 text-sm tracking-wide">Begin water offering</button>
                        </div>

                        {{-- Flowers --}}
                        <div class="offering-column" id="flower-column">
                            <h3 class="offering-column-title">Flowers</h3>
                            <p class="offering-column-desc">Offer a bouquet in a decorated vase</p>
                            <div class="offering-preview">
                                <div id="flower-preview" class="flower-vase scale-110"></div>
                            </div>
                            <input type="text" id="flower-name" maxlength="100" placeholder="Name (optional)" class="name-input mt-4 w-full rounded px-3 py-2 text-sm italic">
                            <button type="button" id="btn-offer-flower" class="btn-shrine mt-3 w-full rounded px-4 py-2.5 text-sm tracking-wide">Offer flowers</button>
                        </div>

                        {{-- Butter lamp --}}
                        <div class="offering-column">
                            <h3 class="offering-column-title">Butter Lamp</h3>
                            <p class="offering-column-desc">Light and offer before the shrine</p>
                            <div class="offering-preview">
                                <div id="offering-lamp" class="butter-lamp scale-125"></div>
                            </div>
                            <button type="button" id="btn-light" class="btn-shrine mt-4 w-full rounded px-4 py-2.5 text-sm tracking-wide">Light</button>
                        </div>
                    </div>

                    <div class="offerings-row offerings-row--music mt-4">
                        <div class="offering-column offering-column--music">
                            <h3 class="offering-column-title">Music</h3>
                            <p class="offering-column-desc">Offer sacred music before the shrine</p>
                            <div class="offering-preview music-preview">
                                <div id="music-preview" class="music-preview-dranyen"></div>
                            </div>
                            <input type="text" id="music-name" maxlength="100" placeholder="Name (optional)" class="name-input mt-4 w-full rounded px-3 py-2 text-sm italic">
                            <button type="button" id="btn-offer-music" class="btn-shrine mt-3 w-full rounded px-4 py-2.5 text-sm tracking-wide">Offer music</button>
                        </div>
                    </div>
                </section>

                <section class="w-full border-t border-sky-900/10 pt-8" aria-labelledby="mantra-heading">
                    <div class="text-center">
                        <h2 id="mantra-heading" class="text-sm tracking-[0.25em] uppercase text-sky-950/80">Mantra Repetitions</h2>
                        <p class="mt-1 text-xs text-sky-950/55">Add your recitations to the pooled count</p>
                        <button type="button" id="btn-open-sutra" class="btn-shrine mt-4 rounded px-6 py-2.5 text-sm tracking-wide">
                            Read the Sutra
                        </button>
                    </div>

                    <div class="mt-6 flex flex-col items-center gap-4">
                        <p class="text-center">
                            <span class="text-xs uppercase tracking-wider text-sky-950/55">Pooled recitations</span>
                            <span id="mantra-total" class="mt-1 block text-3xl font-medium tabular-nums text-sky-950">{{ number_format($totalMantraCount) }}</span>
                        </p>

                        <p id="mantra-dedication" class="max-w-xl text-center text-sm italic leading-relaxed text-sky-950/75">
                            @include('partials.mantra-dedication', ['dedicationNames' => $dedicationNames])
                        </p>

                        <div class="flex w-full max-w-md flex-col gap-3 sm:flex-row sm:items-center">
                            <label for="mantra-count" class="sr-only">Number of mantra repetitions</label>
                            <input type="number" id="mantra-count" name="count" min="1" max="100000" value="108" placeholder="Repetitions" class="name-input w-full rounded px-4 py-2.5 text-sm sm:flex-1">
                            <button type="button" id="btn-add-mantra" class="btn-shrine rounded px-5 py-2.5 text-sm tracking-wide sm:shrink-0">Add to pool</button>
                        </div>
                    </div>
                </section>
            </div>

            <div class="live-practitioners-bar mx-auto mt-8 max-w-7xl border-t border-sky-900/10 px-4 pt-5 pb-2 text-center">
                <p class="text-xs tracking-[0.2em] uppercase text-sky-950/55">Live practitioners</p>
                <p class="mt-1 text-2xl font-medium tabular-nums text-sky-950">
                    <span id="live-practitioners-count">0</span>
                </p>
            </div>
        </footer>
    </div>

    <div id="water-pitcher" class="water-pitcher hidden" aria-hidden="true"></div>

    <div id="cookie-consent" class="cookie-consent" hidden aria-hidden="true" role="dialog" aria-labelledby="cookie-consent-title">
        <p id="cookie-consent-title" class="cookie-consent-text">This site uses cookies and local storage to remember your preferences and offering session.</p>
        <button type="button" id="btn-accept-cookies" class="btn-shrine cookie-consent-btn">Accept</button>
    </div>

    <button type="button" id="btn-open-dedication" class="dedicate-merit-fab btn-shrine" aria-haspopup="dialog" aria-controls="dedication-modal">
        Dedicate the merit
    </button>

    <div id="lamp-offering-modal" class="shrine-modal" hidden aria-hidden="true">
        <div class="shrine-modal-backdrop" data-close-lamp aria-hidden="true"></div>
        <div class="shrine-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="lamp-modal-title">
            <div class="shrine-modal-header">
                <h2 id="lamp-modal-title" class="text-sm font-medium tracking-wide text-sky-950">Butter Lamp Offering</h2>
                <button type="button" id="btn-close-lamp-modal" class="shrine-modal-close" aria-label="Close offering">&times;</button>
            </div>
            <div class="shrine-modal-body">
                @include('partials.butter-lamp-offering')

                <div class="mt-6 flex justify-center">
                    <div id="modal-offering-lamp" class="butter-lamp scale-150"></div>
                </div>

                <label for="lamp-name" class="sr-only">Name for this offering</label>
                <input type="text" id="lamp-name" name="name" maxlength="100" placeholder="Name (optional)" class="name-input mt-6 w-full rounded px-4 py-2.5 text-sm italic" autocomplete="name">

                <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <button type="button" id="btn-cancel-lamp" class="btn-shrine rounded px-4 py-2.5 text-sm tracking-wide sm:flex-1" data-close-lamp>Cancel</button>
                    <button type="button" id="btn-offer" class="btn-shrine rounded px-4 py-2.5 text-sm tracking-wide sm:flex-[1.4]">Offer</button>
                </div>
            </div>
        </div>
    </div>

    <div id="sutra-modal" class="shrine-modal" hidden aria-hidden="true">
        <div class="shrine-modal-backdrop" data-close-sutra aria-hidden="true"></div>
        <div class="shrine-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="sutra-modal-title">
            <div class="shrine-modal-header">
                <h2 id="sutra-modal-title" class="text-sm font-medium tracking-wide text-sky-950">The Dhāraṇī of Noble Avalokiteśvara</h2>
                <button type="button" id="btn-close-sutra" class="shrine-modal-close" aria-label="Close sutra">&times;</button>
            </div>
            <div class="shrine-modal-body">
                @include('partials.dharani-sutra')
            </div>
        </div>
    </div>
    <div id="refuge-modal" class="shrine-modal" hidden aria-hidden="true">
        <div class="shrine-modal-backdrop" data-close-refuge aria-hidden="true"></div>
        <div class="shrine-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="refuge-modal-title">
            <div class="shrine-modal-header">
                <h2 id="refuge-modal-title" class="text-sm font-medium tracking-wide text-sky-950">Refuge and Bodhicitta</h2>
                <button type="button" id="btn-close-refuge" class="shrine-modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="shrine-modal-body">
                @include('partials.refuge-bodhicitta')
                <button type="button" id="btn-dismiss-refuge" class="btn-shrine mt-6 w-full rounded px-4 py-2.5 text-sm tracking-wide" data-close-refuge>Continue to the shrine</button>
            </div>
        </div>
    </div>

    <div id="dedication-modal" class="shrine-modal" hidden aria-hidden="true">
        <div class="shrine-modal-backdrop" data-close-dedication aria-hidden="true"></div>
        <div class="shrine-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="dedication-modal-title">
            <div class="shrine-modal-header">
                <h2 id="dedication-modal-title" class="text-sm font-medium tracking-wide text-sky-950">Dedication of Merit</h2>
                <button type="button" id="btn-close-dedication" class="shrine-modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="shrine-modal-body">
                @include('partials.merit-dedication', ['offeringNames' => $offeringNames])
                <button type="button" id="btn-dismiss-dedication" class="btn-shrine mt-6 w-full rounded px-4 py-2.5 text-sm tracking-wide" data-close-dedication>Close</button>
            </div>
        </div>
    </div>

    <div id="music-offering-modal" class="shrine-modal" hidden aria-hidden="true">
        <div class="shrine-modal-backdrop" data-close-music aria-hidden="true"></div>
        <div class="shrine-modal-dialog music-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="music-modal-title">
            <div class="shrine-modal-header">
                <h2 id="music-modal-title" class="text-sm font-medium tracking-wide text-sky-950">Choose Music Offering</h2>
                <button type="button" id="btn-close-music-modal" class="shrine-modal-close" aria-label="Close music selection">&times;</button>
            </div>
            <div class="shrine-modal-body">
                <p class="text-sm text-sky-950/70">Select a piece to offer before the shrine. It will join the music row for fifteen minutes.</p>
                <div id="music-catalog" class="music-catalog-grid mt-4" role="list"></div>

                <div class="music-suggest-panel mt-6 border-t border-sky-900/10 pt-5">
                    <h3 class="text-xs tracking-[0.18em] uppercase text-sky-950/60">Suggest a YouTube link</h3>
                    <p class="mt-1 text-xs text-sky-950/55">Share a piece you would like added to the shrine catalog.</p>
                    <label for="music-suggest-url" class="sr-only">YouTube link</label>
                    <input type="url" id="music-suggest-url" maxlength="500" placeholder="https://www.youtube.com/watch?v=..." class="name-input mt-3 w-full rounded px-3 py-2 text-sm">
                    <button type="button" id="btn-submit-music-suggestion" class="btn-shrine mt-3 w-full rounded px-4 py-2.5 text-sm tracking-wide">Submit suggestion</button>
                    <p id="music-suggest-status" class="mt-2 hidden text-xs text-sky-950/70" role="status"></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
