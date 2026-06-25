<article class="font-verse text-sm leading-relaxed text-sky-950/90 sm:text-base">
    <header class="mb-5 text-center">
        <h3 class="text-base font-medium not-italic sm:text-lg">The Dedication of Merit</h3>
        <p class="mt-1 text-xs not-italic text-sky-950/60">from the Jātakas</p>
    </header>

    <div
        id="merit-names-carousel"
        class="merit-names-carousel @if (empty($offeringNames)) hidden @endif"
        aria-label="Names of offerings"
    >
        <div id="merit-names-track" class="merit-names-track">
            <div class="merit-names-set">
                @foreach ($offeringNames as $name)
                    <span class="merit-name-chip">{{ $name }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <div>
        <p class="text-xs not-italic text-sky-950/55">sönam di yi tamché zikpa nyi</p>
        <p class="mt-0.5 italic">Through this merit, may all beings attain the omniscient state of enlightenment,</p>

        <p class="mt-3 text-xs not-italic text-sky-950/55">tob né nyepé dra nam pamjé shing</p>
        <p class="mt-0.5 italic">And conquer the enemy of faults and delusion,</p>

        <p class="mt-3 text-xs not-italic text-sky-950/55">kyé ga na chi balong trukpa yi</p>
        <p class="mt-0.5 italic">May they all be liberated from this ocean of saṃsāra</p>

        <p class="mt-3 text-xs not-italic text-sky-950/55">sipé tso lé drowa drolwar shok</p>
        <p class="mt-0.5 italic">And from its pounding waves of birth, old age, sickness and death!</p>
    </div>

    <p class="mt-6 text-center text-xs not-italic text-sky-950/65">Taken from the Jātakas.</p>

    <footer class="mt-4 border-t border-sky-900/10 pt-3 text-xs not-italic leading-normal text-sky-950/55">
        <cite>Rigpa Translations</cite>
    </footer>
</article>
