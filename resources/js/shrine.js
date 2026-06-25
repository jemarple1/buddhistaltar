import {
    FLOWER_TYPES,
    randomFlowerType,
    randomVaseColor,
    vaseColorForId,
    flowerSvg,
    lampSvg,
    incenseSvg,
    dranyenSvg,
    waterBowlSvg,
} from './offering-graphics.js';

const OFFERING_FLAME_ID = 'offering-flame';
const OFFERED_LAMPS_ID = 'offered-lamps';
const OFFERED_MUSIC_ID = 'offered-music';
const OFFERED_FLOWERS_ID = 'offered-flowers';
const LAMP_NAME_ID = 'lamp-name';
const BTN_LIGHT_ID = 'btn-light';
const BTN_OFFER_ID = 'btn-offer';
const OFFERING_LAMP_ID = 'offering-lamp';
const MANTRA_COUNT_ID = 'mantra-count';
const MANTRA_TOTAL_ID = 'mantra-total';
const MANTRA_DEDICATION_ID = 'mantra-dedication';
const BTN_ADD_MANTRA_ID = 'btn-add-mantra';
const SUTRA_MODAL_ID = 'sutra-modal';
const LAMP_OFFERING_MODAL_ID = 'lamp-offering-modal';
const MODAL_OFFERING_FLAME_ID = 'modal-offering-flame';
const BTN_OPEN_SUTRA_ID = 'btn-open-sutra';
const BTN_CLOSE_SUTRA_ID = 'btn-close-sutra';
const BTN_CLOSE_LAMP_MODAL_ID = 'btn-close-lamp-modal';
const BTN_OFFER_INCENSE_ID = 'btn-offer-incense';
const BTN_OFFER_FLOWER_ID = 'btn-offer-flower';
const BTN_OFFER_WATER_ID = 'btn-offer-water';
const WATER_NAME_ID = 'water-name';
const WATER_SHRINE_NAME_ID = 'water-shrine-name';
const BTN_OFFER_MUSIC_ID = 'btn-offer-music';
const BTN_CLOSE_MUSIC_MODAL_ID = 'btn-close-music-modal';
const BTN_SUBMIT_MUSIC_SUGGESTION_ID = 'btn-submit-music-suggestion';
const MUSIC_MODAL_ID = 'music-offering-modal';
const MUSIC_CATALOG_ID = 'music-catalog';
const MUSIC_NAME_ID = 'music-name';
const MUSIC_SUGGEST_URL_ID = 'music-suggest-url';
const MUSIC_SUGGEST_STATUS_ID = 'music-suggest-status';
const INCENSE_SHRINE_SELECTOR = '.altar-incense-flank .incense-shrine, #incense-shrine-extra .incense-shrine';
const INCENSE_SHRINE_EXTRA_ID = 'incense-shrine-extra';
const MAX_STICKS_PER_HOLDER = 3;
const SYLLABLE_SMOKE_ID = 'syllable-smoke';
const OFFERED_WATER_ID = 'offered-water-bowls';
const VISITOR_TOKEN_KEY = 'shrine_visitor_token';
const PRACTITIONER_TOKEN_KEY = VISITOR_TOKEN_KEY;
const LIVE_PRACTITIONERS_COUNT_ID = 'live-practitioners-count';
const SHRINE_POLL_MS = 4000;
const COOKIE_CONSENT_KEY = 'shrine_cookies_accepted';
const REFUGE_DISMISSED_KEY = 'shrine_refuge_dismissed';
const COOKIE_CONSENT_ID = 'cookie-consent';
const REFUGE_MODAL_ID = 'refuge-modal';
const DEDICATION_MODAL_ID = 'dedication-modal';
const BTN_ACCEPT_COOKIES_ID = 'btn-accept-cookies';
const BTN_CLOSE_REFUGE_ID = 'btn-close-refuge';
const BTN_OPEN_DEDICATION_ID = 'btn-open-dedication';
const BTN_CLOSE_DEDICATION_ID = 'btn-close-dedication';
const MERIT_NAMES_CAROUSEL_ID = 'merit-names-carousel';
const MERIT_NAMES_TRACK_ID = 'merit-names-track';
const MERIT_NAMES_PIXELS_PER_SECOND = 28;

const SYLLABLES = [
    { text: 'OṂ', className: 'syllable-particle--om' },
    { text: 'ĀḤ', className: 'syllable-particle--ah' },
    { text: 'HŪṂ', className: 'syllable-particle--hum' },
];

let isLit = false;
let isOffering = false;
let isAddingMantra = false;
let shrineState = {};
let offeredLamps = [];
let syllableCloudInterval = null;
let syllableRiseInterval = null;
let syllableLampRayInterval = null;
let statePollInterval = null;
let shrinePollInterval = null;
let practitionerHeartbeatInterval = null;

const CLOUD_TARGET_BASE = 36;
const CLOUD_TARGET_PER_STICK = 14;
const CLOUD_BAND_TOP = 0.03;
const CLOUD_BAND_HEIGHT = 0.34;
const CLOUD_FILL_MS_BASE = 120000;
const CLOUD_FILL_MS_PER_STICK = -8000;
const RISE_INTERVAL_BASE = 420;
const RISE_INTERVAL_PER_STICK = 28;
const RISE_INTERVAL_MIN = 110;
const CLOUD_MAINTAIN_BASE = 950;
const CLOUD_MAINTAIN_PER_STICK = 55;
const CLOUD_MAINTAIN_MIN = 450;
const CLOUD_DISSOLVE_AT = 0.78;
const LAMP_RAY_INTERVAL_BASE = 2600;
const LAMP_RAY_INTERVAL_PER_LAMP = 220;
const LAMP_RAY_INTERVAL_MIN = 750;
const OFFERING_NAME_SELECTOR = '.offering-name, .lamp-name, .music-offering-name, .water-offering-name';

let syllableSmokeStartedAt = 0;
let currentIncenseSticks = 1;
let syllableSmokeRunning = false;
let isSelectingMusic = false;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function getVisitorToken() {
    let token = sessionStorage.getItem(VISITOR_TOKEN_KEY);
    if (!token) {
        token = crypto.randomUUID?.() ?? `${Date.now()}-${Math.random().toString(36).slice(2)}`;
        sessionStorage.setItem(VISITOR_TOKEN_KEY, token);
    }

    return token;
}

function offeringPayload(extra = {}) {
    return {
        visitor_token: getVisitorToken(),
        ...extra,
    };
}

function offeringErrorMessage(data, fallback) {
    if (typeof data?.message === 'string' && data.message !== '') {
        return data.message;
    }

    const errors = data?.errors ?? {};
    for (const messages of Object.values(errors)) {
        if (Array.isArray(messages) && messages[0]) {
            return messages[0];
        }
    }

    return fallback;
}

function mergeShrineState(nextState) {
    if (nextState && typeof nextState === 'object') {
        shrineState = nextState;
    }
}

function updateMantraTotal(total) {
    const totalEl = document.getElementById(MANTRA_TOTAL_ID);
    if (totalEl && typeof total === 'number') {
        totalEl.textContent = formatCount(total);
    }
}

function loadInitialState() {
    const el = document.getElementById('shrine-state');
    if (!el) {
        return;
    }

    try {
        shrineState = JSON.parse(el.textContent ?? '{}');
    } catch {
        shrineState = {};
    }

    applyShrineState();
}

function formatCount(value) {
    return new Intl.NumberFormat().format(value);
}

function renderDedication(names) {
    const dedication = document.getElementById(MANTRA_DEDICATION_ID);
    if (!dedication) {
        return;
    }

    if (!names.length) {
        dedication.textContent = 'Dedicated toward all butter lamp offerings.';
        return;
    }

    const formattedNames =
        names.length === 1
            ? names[0]
            : `${names.slice(0, -1).join(', ')} and ${names[names.length - 1]}`;

    dedication.textContent = `Dedicated toward all butter lamp offerings, including ${formattedNames}.`;
}

function applyShrineState() {
    updateIncenseDisplay(shrineState.incense);
    syncFlowers(shrineState.flowers ?? []);
    syncLamps(shrineState.lamps ?? []);
    renderMusicOfferings(shrineState.music);
    applyWaterState(shrineState.water ?? {});
    populateMeritNamesCarousel(shrineState.offering_names ?? []);
    updateLivePractitioners(shrineState.live_practitioners ?? 0);
    updateMantraTotal(shrineState.mantra_total ?? 0);
    renderDedication(shrineState.dedication_names ?? []);
}

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function isCoarsePointerDevice() {
    return window.matchMedia('(max-width: 639px), (pointer: coarse)').matches;
}

function youtubeEmbedUrl(youtubeId, { mute = true, autoplay = true, start = 0, controls = true } = {}) {
    const params = new URLSearchParams({
        autoplay: autoplay ? '1' : '0',
        mute: mute ? '1' : '0',
        controls: controls ? '1' : '0',
        modestbranding: '1',
        rel: '0',
        playsinline: '1',
        loop: '1',
        playlist: youtubeId,
        enablejsapi: '1',
    });

    if (start > 0) {
        params.set('start', String(Math.floor(start)));
    }

    return `https://www.youtube.com/embed/${youtubeId}?${params.toString()}`;
}

function musicLandingPoint() {
    const container = document.getElementById(OFFERED_MUSIC_ID);
    if (!container) {
        return null;
    }

    const lastPlayer = container.querySelector('.music-player:last-of-type');
    if (lastPlayer) {
        const rect = lastPlayer.getBoundingClientRect();
        return {
            x: rect.left + rect.width + Math.min(rect.width * 0.35, 48),
            y: rect.top + rect.height / 2,
        };
    }

    return offeringRowLandingPoint(container);
}

function unmuteMusicPlayer(playerEl, youtubeId, start = 0) {
    const iframe = playerEl.querySelector('iframe');
    const btn = playerEl.querySelector('.music-unmute-btn');
    if (!iframe) {
        return;
    }

    iframe.hidden = false;
    iframe.src = youtubeEmbedUrl(youtubeId, { mute: false, autoplay: true, start, controls: true });
    btn?.classList.add('is-unmuted');
}

function startMusicPlayer(playerEl, track) {
    const iframe = playerEl.querySelector('iframe');
    const overlay = playerEl.querySelector('.music-play-overlay');
    if (!iframe || playerEl.dataset.playing === 'true') {
        return;
    }

    playerEl.dataset.playing = 'true';
    overlay?.remove();

    const start = track.youtube_start_seconds ?? 0;
    iframe.hidden = false;
    iframe.src = youtubeEmbedUrl(track.youtube_id, {
        mute: false,
        autoplay: true,
        start,
        controls: true,
    });
}

function createMusicPlayerElement(offering) {
    const player = document.createElement('div');
    player.className = 'music-player';
    player.dataset.offeringId = String(offering.id);
    const track = offering.track;
    const start = track.youtube_start_seconds ?? 0;
    const mobile = isCoarsePointerDevice();

    if (mobile) {
        player.innerHTML = `
            <div class="music-player-frame">
                <button type="button" class="music-play-overlay" aria-label="Play ${escapeHtml(track.title)}">
                    <img class="music-play-overlay-thumb" src="${escapeHtml(track.thumbnail_url)}" alt="">
                    <span class="music-play-overlay-icon" aria-hidden="true"></span>
                </button>
                <iframe
                    hidden
                    title="${escapeHtml(track.title)}"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin"
                    allowfullscreen
                ></iframe>
            </div>
            ${offering.name ? `<span class="music-offering-name">${escapeHtml(offering.name)}</span>` : ''}
        `;

        player.querySelector('.music-play-overlay')?.addEventListener('click', () => {
            startMusicPlayer(player, track);
        });
    } else {
        const embedUrl = youtubeEmbedUrl(track.youtube_id, { mute: true, autoplay: true, start, controls: true });

        player.innerHTML = `
            <div class="music-player-frame">
                <iframe
                    src="${embedUrl}"
                    title="${escapeHtml(track.title)}"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin"
                    allowfullscreen
                ></iframe>
            </div>
            <button type="button" class="music-unmute-btn">Unmute</button>
            ${offering.name ? `<span class="music-offering-name">${escapeHtml(offering.name)}</span>` : ''}
        `;

        player.querySelector('.music-unmute-btn')?.addEventListener('click', () => {
            unmuteMusicPlayer(player, track.youtube_id, start);
        });
    }

    return player;
}

function renderMusicOfferings(musicState) {
    const container = document.getElementById(OFFERED_MUSIC_ID);
    if (!container) {
        return;
    }

    const active = musicState?.active ?? [];
    const activeIds = new Set(active.map((offering) => String(offering.id)));

    container.querySelectorAll('.music-player').forEach((player) => {
        if (!activeIds.has(player.dataset.offeringId ?? '')) {
            player.remove();
        }
    });

    active.forEach((offering) => {
        if (container.querySelector(`[data-offering-id="${offering.id}"]`)) {
            return;
        }

        container.appendChild(createMusicPlayerElement(offering));
    });
}

function renderMusicCatalog(tracks) {
    const catalog = document.getElementById(MUSIC_CATALOG_ID);
    if (!catalog) {
        return;
    }

    catalog.replaceChildren();

    (tracks ?? []).forEach((track) => {
        const card = document.createElement('button');
        card.type = 'button';
        card.className = 'music-track-card';
        card.setAttribute('role', 'listitem');
        card.dataset.trackId = String(track.id);
        card.innerHTML = `
            <img class="music-track-card-thumb" src="${escapeHtml(track.thumbnail_url)}" alt="">
            <span class="music-track-card-body">
                <span class="music-track-card-title">${escapeHtml(track.title)}</span>
            </span>
        `;
        card.addEventListener('click', () => selectMusicTrack(track, card));
        catalog.appendChild(card);
    });
}

function openMusicModal() {
    const modal = document.getElementById(MUSIC_MODAL_ID);
    if (!modal) {
        return;
    }

    renderMusicCatalog(shrineState.music?.tracks ?? []);
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');
    document.getElementById(BTN_CLOSE_MUSIC_MODAL_ID)?.focus();
}

function closeMusicModal(force = false) {
    if (isSelectingMusic && !force) {
        return;
    }

    const modal = document.getElementById(MUSIC_MODAL_ID);
    if (!modal) {
        return;
    }

    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
    document.getElementById(BTN_OFFER_MUSIC_ID)?.focus();
}

async function animateMusicToAltar(sourceRect, track) {
    const landing = musicLandingPoint();
    if (!landing) {
        return;
    }

    const flying = document.createElement('div');
    flying.className = 'flying-music';
    flying.innerHTML = `<img src="${escapeHtml(track.thumbnail_url)}" alt="">`;
    flying.style.left = `${sourceRect.left + sourceRect.width / 2}px`;
    flying.style.top = `${sourceRect.top}px`;
    flying.style.transform = 'translate(-50%, 0)';
    document.body.appendChild(flying);

    await new Promise((resolve) => {
        const startX = sourceRect.left + sourceRect.width / 2;
        const startY = sourceRect.top;
        const endX = landing.x;
        const endY = landing.y;
        const duration = 1700;
        const startTime = performance.now();

        const animate = (now) => {
            const progress = Math.min((now - startTime) / duration, 1);
            const eased = 1 - (1 - progress) ** 3;
            flying.style.left = `${startX + (endX - startX) * eased}px`;
            flying.style.top = `${startY + (endY - startY) * eased - Math.sin(progress * Math.PI) * 70}px`;
            flying.style.opacity = `${0.65 + progress * 0.35}`;

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                flying.remove();
                resolve();
            }
        };

        requestAnimationFrame(animate);
    });
}

async function selectMusicTrack(track, cardEl) {
    if (isSelectingMusic) {
        return;
    }

    isSelectingMusic = true;
    document.querySelectorAll('.music-track-card').forEach((card) => {
        card.disabled = true;
    });

    const nameInput = document.getElementById(MUSIC_NAME_ID);
    const name = nameInput?.value.trim() || null;
    const sourceRect = cardEl.getBoundingClientRect();

    closeMusicModal(true);

    try {
        await animateMusicToAltar(sourceRect, track);

        const response = await fetch('/music-offerings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(offeringPayload({ track_id: track.id, name })),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(offeringErrorMessage(data, 'Music offering failed'));
        }

        mergeShrineState(data.shrine_state);
        renderMusicOfferings(shrineState.music);
        populateMeritNamesCarousel(shrineState.offering_names ?? []);

        if (nameInput) {
            nameInput.value = '';
        }
    } catch (error) {
        alert(error.message ?? 'Unable to offer this music. Please try again.');
    } finally {
        isSelectingMusic = false;
        document.querySelectorAll('.music-track-card').forEach((card) => {
            card.disabled = false;
        });
    }
}

async function submitMusicSuggestion() {
    const urlInput = document.getElementById(MUSIC_SUGGEST_URL_ID);
    const statusEl = document.getElementById(MUSIC_SUGGEST_STATUS_ID);
    const nameInput = document.getElementById(MUSIC_NAME_ID);
    const btn = document.getElementById(BTN_SUBMIT_MUSIC_SUGGESTION_ID);
    const url = urlInput?.value.trim() ?? '';

    if (!url) {
        return;
    }

    btn?.setAttribute('disabled', 'disabled');

    try {
        const response = await fetch('/music-suggestions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(offeringPayload({
                url,
                name: nameInput?.value.trim() || null,
            })),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(offeringErrorMessage(data, 'Suggestion failed'));
        }

        if (urlInput) {
            urlInput.value = '';
        }

        if (statusEl) {
            statusEl.textContent = data.message ?? 'Thank you — your suggestion has been recorded.';
            statusEl.classList.remove('hidden');
        }
    } catch {
        alert('Unable to submit your suggestion. Please check the link and try again.');
    } finally {
        btn?.removeAttribute('disabled');
    }
}

function incenseStickCount(incense) {
    if (typeof incense === 'object' && incense !== null && typeof incense.sticks === 'number') {
        return Math.max(2, incense.sticks);
    }

    return 2;
}

function getCloudTargetMax() {
    return CLOUD_TARGET_BASE + currentIncenseSticks * CLOUD_TARGET_PER_STICK;
}

function getCloudFillMs() {
    return Math.max(45000, CLOUD_FILL_MS_BASE + currentIncenseSticks * CLOUD_FILL_MS_PER_STICK);
}

function getRiseIntervalMs() {
    return Math.max(RISE_INTERVAL_MIN, RISE_INTERVAL_BASE - currentIncenseSticks * RISE_INTERVAL_PER_STICK);
}

function getCloudMaintainMs() {
    return Math.max(CLOUD_MAINTAIN_MIN, CLOUD_MAINTAIN_BASE - currentIncenseSticks * CLOUD_MAINTAIN_PER_STICK);
}

function incenseHolderCount(sticks) {
    if (sticks <= MAX_STICKS_PER_HOLDER) {
        return 2;
    }

    return Math.max(3, Math.ceil(sticks / MAX_STICKS_PER_HOLDER));
}

function distributeIncenseSticks(totalSticks, holderCount) {
    const counts = Array(holderCount).fill(0);
    let remaining = totalSticks;
    let index = 0;

    while (remaining > 0) {
        if (counts[index] < MAX_STICKS_PER_HOLDER) {
            counts[index]++;
            remaining--;
        }

        index = (index + 1) % holderCount;
    }

    return counts;
}

function ensureIncenseHolderElements(holderCount) {
    const extraContainer = document.getElementById(INCENSE_SHRINE_EXTRA_ID);
    const primary = [...document.querySelectorAll('.altar-incense-flank .incense-shrine')];
    const extrasNeeded = Math.max(0, holderCount - primary.length);
    const extras = extraContainer ? [...extraContainer.querySelectorAll('.incense-shrine')] : [];

    if (extraContainer) {
        while (extras.length < extrasNeeded) {
            const shrine = document.createElement('div');
            shrine.className = 'incense-shrine';
            shrine.setAttribute('aria-label', 'Burning incense offering');
            extraContainer.appendChild(shrine);
            extras.push(shrine);
        }

        while (extras.length > extrasNeeded) {
            extras.pop()?.remove();
        }

        extraContainer.classList.toggle('hidden', extrasNeeded === 0);
        extraContainer.toggleAttribute('hidden', extrasNeeded === 0);
    }

    return [...primary, ...extras.slice(0, extrasNeeded)];
}

function renderIncenseShrines(sticks) {
    const holderCount = incenseHolderCount(sticks);
    const distribution = distributeIncenseSticks(sticks, holderCount);
    const holders = ensureIncenseHolderElements(holderCount);

    holders.forEach((shrine, index) => {
        const isFlank = shrine.classList.contains('incense-shrine--flank');
        let count = distribution[index] ?? 0;

        if (isFlank && count < 1) {
            count = 1;
        }

        shrine.dataset.incenseSticks = String(count);

        if (count > 0) {
            shrine.innerHTML = incenseSvg({ lit: true, sticks: count });
            shrine.classList.remove('hidden');
            shrine.removeAttribute('hidden');
            return;
        }

        shrine.innerHTML = incenseSvg({ lit: false, sticks: 0 });
        if (!isFlank) {
            shrine.classList.add('hidden');
            shrine.setAttribute('hidden', '');
        }
    });
}

function updateIncenseDisplay(incense) {
    const sticks = incenseStickCount(incense);
    currentIncenseSticks = sticks;
    renderIncenseShrines(sticks);
    ensureSyllableSmoke(sticks);
}

function getCloudTargetNow() {
    if (!syllableSmokeStartedAt) {
        return 0;
    }

    const elapsed = Date.now() - syllableSmokeStartedAt;
    const target = getCloudTargetMax();
    return Math.min(target, Math.floor((elapsed / getCloudFillMs()) * target));
}

function getIncenseShrines() {
    return [...document.querySelectorAll(INCENSE_SHRINE_SELECTOR)].filter(
        (shrine) => Number.parseInt(shrine.dataset.incenseSticks ?? '0', 10) > 0,
    );
}

function getOfferedButterLamps() {
    return [...document.querySelectorAll('#offered-lamps .butter-lamp[data-lamp-id]')];
}

function getOfferingNameTargets() {
    return [...document.querySelectorAll(OFFERING_NAME_SELECTOR)].filter((element) => {
        if ((element.textContent?.trim() ?? '') === '') {
            return false;
        }

        return isVisibleInViewport(element);
    });
}

function isVisibleInViewport(element) {
    const rect = element.getBoundingClientRect();
    if (rect.width < 1 || rect.height < 1) {
        return false;
    }

    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height / 2;

    return (
        centerX >= 0
        && centerY >= 0
        && centerX <= window.innerWidth
        && centerY <= window.innerHeight
    );
}

function getOfferingNamePoint(target) {
    const rect = target.getBoundingClientRect();

    return {
        x: rect.left + rect.width / 2,
        y: rect.top + rect.height / 2,
    };
}

function getCloudSpawnX() {
    const sources = getIncenseShrines();
    let baseX = window.innerWidth / 2;

    if (sources.length) {
        const source = sources[Math.floor(Math.random() * sources.length)];
        const rect = source.getBoundingClientRect();
        baseX = rect.left + rect.width / 2;
    }

    const spread = window.innerWidth * (0.35 + Math.random() * 0.25);
    return Math.max(16, Math.min(window.innerWidth - 16, baseX + (Math.random() - 0.5) * spread));
}

function spawnCloudSyllable(position = null) {
    const container = document.getElementById(SYLLABLE_SMOKE_ID);
    if (!container) {
        return;
    }

    if (!position) {
        const sources = getIncenseShrines();
        if (!sources.length) {
            return;
        }

        const current = container.querySelectorAll('.syllable-particle--cloud').length;
        if (current >= getCloudTargetNow()) {
            return;
        }
    }

    const syllable = SYLLABLES[Math.floor(Math.random() * SYLLABLES.length)];
    const particle = document.createElement('span');
    particle.className = `syllable-particle syllable-particle--cloud ${syllable.className}`;
    particle.textContent = syllable.text;

    const x = position?.x ?? getCloudSpawnX();
    const y =
        position?.y ??
        window.innerHeight * (CLOUD_BAND_TOP + Math.random() * CLOUD_BAND_HEIGHT);

    particle.style.left = `${x}px`;
    particle.style.top = `${y}px`;
    particle.style.setProperty('--drift-x', `${(Math.random() - 0.5) * 96}px`);
    particle.style.setProperty('--drift-y', `${(Math.random() - 0.5) * 28}px`);
    particle.style.setProperty('--sway-x', `${(Math.random() - 0.5) * 36}px`);
    particle.style.setProperty('--peak-opacity', `${0.38 + Math.random() * 0.42}`);
    const durationSec = 16 + Math.random() * 14;
    particle.style.setProperty('--cloud-duration', `${durationSec}s`);
    particle.style.fontSize = `${0.52 + Math.random() * 0.38}rem`;

    container.appendChild(particle);

    let finished = false;
    const finish = () => {
        if (finished) {
            return;
        }

        finished = true;
        particle.remove();
    };

    particle.addEventListener('animationend', (event) => {
        if (event.animationName === 'syllable-cloud' || event.animationName === 'syllable-hover-dissolve') {
            finish();
        }
    });

    scheduleCloudSyllableDescent(particle, durationSec);
}

function scheduleCloudSyllableDescent(particle, durationSec) {
    if (!getOfferingNameTargets().length) {
        return;
    }

    window.setTimeout(() => {
        if (!particle.isConnected || particle.dataset.descending === '1') {
            return;
        }

        beginCloudSyllableDescent(particle);
    }, durationSec * 1000 * CLOUD_DISSOLVE_AT);
}

function beginCloudSyllableDescent(particle) {
    const targets = getOfferingNameTargets();
    if (!targets.length || particle.dataset.descending === '1') {
        return;
    }

    const target = targets[Math.floor(Math.random() * targets.length)];
    const end = getOfferingNamePoint(target);
    const particleRect = particle.getBoundingClientRect();
    const startX = particleRect.left + particleRect.width / 2;
    const startY = particleRect.top + particleRect.height / 2;

    if (!Number.isFinite(end.x) || !Number.isFinite(end.y) || (end.x === 0 && end.y === 0)) {
        return;
    }

    particle.dataset.descending = '1';
    particle.classList.remove('syllable-particle--cloud');
    particle.classList.add('syllable-particle--descending');
    particle.style.left = `${startX}px`;
    particle.style.top = `${startY}px`;

    const deltaX = end.x - startX;
    const deltaY = end.y - startY;
    const arcSpread = Math.min(160, Math.abs(deltaX) * 0.55 + 48);
    const arcBias = (Math.random() - 0.5) * arcSpread;
    const midX = deltaX * 0.42 + arcBias;

    particle.style.setProperty('--descend-x', `${deltaX}px`);
    particle.style.setProperty('--descend-y', `${deltaY}px`);
    particle.style.setProperty('--descend-sway-x', `${midX}px`);
    particle.style.setProperty('--descend-mid-x', `${deltaX * 0.82 + midX * 0.2}px`);
    particle.style.setProperty('--descend-mid-y', `${deltaY * 0.78}px`);
    particle.style.setProperty('--descend-duration', `${3.4 + Math.random() * 2.2}s`);
    particle.style.animation = 'none';
    void particle.offsetWidth;
    particle.style.removeProperty('animation');

    particle.addEventListener('animationend', function onDescendEnd(event) {
        if (event.animationName !== 'syllable-descend') {
            return;
        }

        particle.removeEventListener('animationend', onDescendEnd);
        beginSyllableHoverDissolve(particle, target);
    });
}

function beginSyllableHoverDissolve(particle, target) {
    if (!particle.isConnected || !target?.isConnected || !isVisibleInViewport(target)) {
        particle.remove();
        return;
    }

    const { x, y } = getOfferingNamePoint(target);

    particle.classList.remove('syllable-particle--descending');
    particle.classList.add('syllable-particle--hovering');
    particle.style.left = `${x}px`;
    particle.style.top = `${y}px`;
    particle.style.setProperty('--hover-duration', `${2.8 + Math.random() * 1.6}s`);
    particle.style.animation = 'none';
    void particle.offsetWidth;
    particle.style.removeProperty('animation');
}

function spawnLampRaySyllable() {
    const container = document.getElementById(SYLLABLE_SMOKE_ID);
    const lamps = getOfferedButterLamps();
    if (!container || !lamps.length) {
        return;
    }

    const lamp = lamps[Math.floor(Math.random() * lamps.length)];
    const rect = lamp.getBoundingClientRect();
    const syllable = SYLLABLES[Math.floor(Math.random() * SYLLABLES.length)];
    const particle = document.createElement('span');
    particle.className = `syllable-particle syllable-particle--lamp-ray ${syllable.className}`;
    particle.textContent = syllable.text;

    const originX = rect.left + rect.width / 2;
    const originY = rect.top + rect.height * 0.12;
    const angleFromUp = (Math.random() * 120 - 60) * (Math.PI / 180);
    const outwardDist = 36 + Math.random() * 88;
    const riseDist = window.innerHeight * (0.06 + Math.random() * 0.26);
    const rayX = Math.sin(angleFromUp) * outwardDist;
    const rayY = -Math.abs(Math.cos(angleFromUp) * outwardDist) - riseDist;

    particle.style.left = `${originX}px`;
    particle.style.top = `${originY}px`;
    particle.style.setProperty('--ray-x', `${rayX}px`);
    particle.style.setProperty('--ray-y', `${rayY}px`);
    particle.style.setProperty('--ray-duration', `${4.2 + Math.random() * 3.8}s`);
    particle.style.fontSize = `${0.42 + Math.random() * 0.26}rem`;

    container.appendChild(particle);
    particle.addEventListener('animationend', () => {
        const finalRect = particle.getBoundingClientRect();
        const cloudMinY = window.innerHeight * CLOUD_BAND_TOP;
        const cloudMaxY = window.innerHeight * (CLOUD_BAND_TOP + CLOUD_BAND_HEIGHT);
        const centerY = finalRect.top + finalRect.height / 2;

        if (centerY >= cloudMinY - 24 && centerY <= cloudMaxY + 48) {
            depositCloudFromRiser(particle);
        }

        particle.remove();
    }, { once: true });
}

function getLampRayIntervalMs() {
    const count = getOfferedButterLamps().length;
    if (!count) {
        return null;
    }

    return Math.max(LAMP_RAY_INTERVAL_MIN, LAMP_RAY_INTERVAL_BASE - count * LAMP_RAY_INTERVAL_PER_LAMP);
}

function maintainLampRaySyllables() {
    if (getOfferedButterLamps().length) {
        spawnLampRaySyllable();
    }
}

function startLampRayInterval() {
    if (syllableLampRayInterval) {
        clearInterval(syllableLampRayInterval);
        syllableLampRayInterval = null;
    }

    const intervalMs = getLampRayIntervalMs();
    if (!intervalMs) {
        return;
    }

    syllableLampRayInterval = window.setInterval(maintainLampRaySyllables, intervalMs);
    spawnLampRaySyllable();
}

function depositCloudFromRiser(particle) {
    const rect = particle.getBoundingClientRect();
    const x = rect.left + rect.width / 2 + (Math.random() - 0.5) * 24;
    const minY = window.innerHeight * CLOUD_BAND_TOP;
    const maxY = window.innerHeight * (CLOUD_BAND_TOP + CLOUD_BAND_HEIGHT);
    const y = Math.max(minY, Math.min(maxY, rect.top + (Math.random() - 0.5) * 20));

    spawnCloudSyllable({ x, y });
}

function spawnRisingSyllable() {
    const container = document.getElementById(SYLLABLE_SMOKE_ID);
    const sources = getIncenseShrines();
    if (!container || !sources.length) {
        return;
    }

    const source = sources[Math.floor(Math.random() * sources.length)];
    const rect = source.getBoundingClientRect();
    const syllable = SYLLABLES[Math.floor(Math.random() * SYLLABLES.length)];
    const particle = document.createElement('span');
    particle.className = `syllable-particle syllable-particle--rising ${syllable.className}`;
    particle.textContent = syllable.text;

    particle.style.left = `${rect.left + rect.width / 2 + (Math.random() - 0.5) * 36}px`;
    particle.style.top = `${rect.top + 6 + Math.random() * 14}px`;
    particle.style.setProperty(
        '--rise-y',
        `-${window.innerHeight * (0.2 + Math.random() * 0.22)}px`,
    );
    particle.style.setProperty('--rise-drift', `${(Math.random() - 0.5) * 72}px`);
    particle.style.setProperty('--rise-duration', `${8 + Math.random() * 7}s`);
    particle.style.fontSize = `${0.55 + Math.random() * 0.32}rem`;

    container.appendChild(particle);
    particle.addEventListener('animationend', () => {
        depositCloudFromRiser(particle);
        particle.remove();
    });
}

function maintainSyllableCloud() {
    const container = document.getElementById(SYLLABLE_SMOKE_ID);
    const sources = getIncenseShrines();
    if (!container || !sources.length) {
        return;
    }

    const count = container.querySelectorAll('.syllable-particle--cloud').length;
    const target = getCloudTargetNow();
    const deficit = target - count;

    if (deficit <= 0) {
        return;
    }

    spawnCloudSyllable();
}

function clearSyllableIntervals() {
    if (syllableCloudInterval) {
        clearInterval(syllableCloudInterval);
        syllableCloudInterval = null;
    }

    if (syllableRiseInterval) {
        clearInterval(syllableRiseInterval);
        syllableRiseInterval = null;
    }
}

function startSyllableIntervals() {
    clearSyllableIntervals();
    syllableCloudInterval = window.setInterval(maintainSyllableCloud, getCloudMaintainMs());
    syllableRiseInterval = window.setInterval(spawnRisingSyllable, getRiseIntervalMs());
    spawnRisingSyllable();
}

function ensureSyllableSmoke(sticks) {
    currentIncenseSticks = sticks;

    if (!syllableSmokeRunning) {
        syllableSmokeStartedAt = Date.now();
        syllableSmokeRunning = true;
    }

    startSyllableIntervals();
}

function updateLivePractitioners(count) {
    const el = document.getElementById(LIVE_PRACTITIONERS_COUNT_ID);
    if (el && typeof count === 'number') {
        el.textContent = formatCount(count);
    }
}

function getPractitionerToken() {
    return getVisitorToken();
}

async function sendPractitionerHeartbeat() {
    try {
        const response = await fetch('/practitioner-presence', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ token: getPractitionerToken() }),
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();
        if (typeof data.live_practitioners === 'number') {
            updateLivePractitioners(data.live_practitioners);
        }
    } catch {
        // ignore heartbeat errors
    }
}

function startPractitionerPresence() {
    sendPractitionerHeartbeat();

    if (practitionerHeartbeatInterval) {
        return;
    }

    practitionerHeartbeatInterval = window.setInterval(sendPractitionerHeartbeat, 120000);
}

function offeringRowLandingPoint(container) {
    if (!container) {
        return null;
    }

    const rect = container.getBoundingClientRect();

    return {
        x: rect.left + rect.width / 2,
        y: rect.top + rect.height / 2,
    };
}

function seedOfferedLamps() {
    const container = document.getElementById(OFFERED_LAMPS_ID);
    const lamps = container?.querySelectorAll('.butter-lamp[data-lamp-id]') ?? [];

    offeredLamps = [...lamps].map((lamp) => ({
        id: lamp.dataset.lampId,
        name: lamp.querySelector('.lamp-name')?.textContent ?? null,
    }));
}

function syncFlowers(flowers) {
    const container = document.getElementById(OFFERED_FLOWERS_ID);
    if (!container) {
        return;
    }

    const ids = new Set(flowers.map((flower) => String(flower.id)));

    container.querySelectorAll('[data-flower-id]').forEach((element) => {
        if (!ids.has(element.dataset.flowerId ?? '')) {
            element.remove();
        }
    });

    flowers.forEach((flower) => {
        if (container.querySelector(`[data-flower-id="${flower.id}"]`)) {
            return;
        }

        container.appendChild(
            createFlowerElement(flower.name, flower.id, flower.flower_type, flower.vase_color, false),
        );
    });
}

function syncLamps(lamps) {
    const container = document.getElementById(OFFERED_LAMPS_ID);
    if (!container) {
        return;
    }

    const ids = new Set(lamps.map((lamp) => String(lamp.id)));

    container.querySelectorAll('[data-lamp-id]').forEach((element) => {
        if (!ids.has(element.dataset.lampId ?? '')) {
            element.remove();
        }
    });

    lamps.forEach((lamp) => {
        if (container.querySelector(`[data-lamp-id="${lamp.id}"]`)) {
            return;
        }

        container.appendChild(createLampElement(lamp.name, lamp.id, false));
    });

    startLampRayInterval();
}

function renderFlowers(flowers) {
    syncFlowers(flowers);
}

function renderLamps(lamps) {
    syncLamps(lamps);
}

function refreshFlowerPreview() {
    const preview = document.getElementById('flower-preview');
    if (!preview) {
        return;
    }

    preview.innerHTML = flowerSvg(randomFlowerType(), randomVaseColor());
}

function hydrateFlowerVases() {
    document.querySelectorAll('.flower-vase[data-flower-type]').forEach((element) => {
        const type = element.dataset.flowerType ?? randomFlowerType();
        const vaseColor = element.dataset.vaseColor
            ?? (element.dataset.flowerId ? vaseColorForId(element.dataset.flowerId) : randomVaseColor());
        const label = element.querySelector('.offering-name');
        element.innerHTML = flowerSvg(type, vaseColor);
        if (label) {
            element.appendChild(label);
        }
    });
}

function createFlowerElement(name, id = null, flowerType = null, vaseColor = null, animate = true) {
    const flower = document.createElement('div');
    flower.className = `flower-vase${animate ? ' offered-flower' : ''}`;
    const type = flowerType || randomFlowerType();
    const color = vaseColor || (id ? vaseColorForId(id) : randomVaseColor());
    flower.dataset.flowerType = type;
    flower.dataset.vaseColor = color;
    if (id) {
        flower.dataset.flowerId = String(id);
    }

    flower.innerHTML = flowerSvg(type, color);

    if (name) {
        const label = document.createElement('span');
        label.className = 'offering-name';
        label.textContent = name;
        flower.appendChild(label);
    }

    return flower;
}

function createLampElement(name, id = null, animate = true) {
    const lamp = document.createElement('div');
    lamp.className = `butter-lamp${animate ? ' offered-lamp' : ''}`;
    lamp.innerHTML = lampSvg({ lit: true });

    if (id) {
        lamp.dataset.lampId = String(id);
    }

    if (name) {
        const label = document.createElement('span');
        label.className = 'lamp-name';
        label.textContent = name;
        lamp.appendChild(label);
    }

    return lamp;
}

function syncShrineWaterBowls(water) {
    const positions = water.display_positions ?? [];
    const name = water.display_name ?? null;
    const display = document.getElementById(OFFERED_WATER_ID);
    const nameEl = document.getElementById(WATER_SHRINE_NAME_ID);

    display?.querySelectorAll('.water-bowl-shrine').forEach((bowl) => {
        const pos = Number.parseInt(bowl.dataset.position ?? '0', 10);
        bowl.innerHTML = waterBowlSvg({ filled: positions.includes(pos) });
    });

    if (nameEl) {
        if (name) {
            nameEl.textContent = name;
            nameEl.classList.remove('hidden');
            nameEl.removeAttribute('aria-hidden');
        } else {
            nameEl.textContent = '';
            nameEl.classList.add('hidden');
            nameEl.setAttribute('aria-hidden', 'true');
        }
    }
}

function applyWaterState(water) {
    syncShrineWaterBowls(water ?? {});
}

function animateWaterBowlFlight(sourceEl, targetEl, delayMs) {
    return new Promise((resolve) => {
        window.setTimeout(() => {
            const sourceRect = sourceEl.getBoundingClientRect();
            const targetRect = targetEl.getBoundingClientRect();
            const flying = document.createElement('div');
            flying.className = 'flying-water-bowl';
            flying.innerHTML = waterBowlSvg({ filled: true });

            const startX = sourceRect.left + sourceRect.width / 2;
            const startY = sourceRect.top + sourceRect.height / 2;
            const endX = targetRect.left + targetRect.width / 2;
            const endY = targetRect.top + targetRect.height / 2;
            const lateral = (Math.random() - 0.5) * 44;

            flying.style.left = `${startX}px`;
            flying.style.top = `${startY}px`;
            document.body.appendChild(flying);

            const duration = 1600;
            const startTime = performance.now();

            const animate = (now) => {
                const progress = Math.min((now - startTime) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 2.2);
                const x = startX + (endX - startX) * eased + Math.sin(progress * Math.PI) * lateral;
                const y = startY + (endY - startY) * eased - Math.sin(progress * Math.PI) * 40;
                const opacity = progress < 0.86 ? 1 : 1 - ((progress - 0.86) / 0.14);

                flying.style.left = `${x}px`;
                flying.style.top = `${y}px`;
                flying.style.opacity = String(opacity);

                if (progress < 1) {
                    requestAnimationFrame(animate);
                    return;
                }

                targetEl.innerHTML = waterBowlSvg({ filled: true });
                flying.remove();
                resolve();
            };

            requestAnimationFrame(animate);
        }, delayMs);
    });
}

async function animateWaterBowlsToShrine() {
    const previewBowls = [...document.querySelectorAll('#water-offering-preview .water-bowl-preview')];
    const shrineBowls = [...document.querySelectorAll('#offered-water-bowls .water-bowl-shrine')];

    if (!previewBowls.length || !shrineBowls.length) {
        return;
    }

    await Promise.all(
        previewBowls.map((previewBowl, index) => {
            const position = Number.parseInt(previewBowl.dataset.position ?? String(index + 1), 10);
            const shrineBowl =
                shrineBowls.find((bowl) => Number.parseInt(bowl.dataset.position ?? '0', 10) === position)
                ?? shrineBowls[index];

            return animateWaterBowlFlight(previewBowl, shrineBowl, index * 130);
        }),
    );
}

async function offerWater() {
    const btn = document.getElementById(BTN_OFFER_WATER_ID);
    const nameInput = document.getElementById(WATER_NAME_ID);
    btn?.setAttribute('disabled', 'disabled');
    const name = nameInput?.value.trim() ?? '';

    try {
        const response = await fetch('/water-offerings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(offeringPayload({ name: name || null })),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(offeringErrorMessage(data, 'Water offering failed'));
        }

        await animateWaterBowlsToShrine();

        mergeShrineState(data.shrine_state);
        applyShrineState();
        if (Array.isArray(data.offering_names)) {
            shrineState.offering_names = data.offering_names;
            populateMeritNamesCarousel(data.offering_names);
        }
        if (nameInput) {
            nameInput.value = '';
        }
    } catch (error) {
        alert(error.message ?? 'Unable to offer water. Please try again.');
    } finally {
        btn?.removeAttribute('disabled');
    }
}

function initOfferingGraphics() {
    hydrateFlowerVases();
    refreshFlowerPreview();

    document.getElementById('offering-lamp')?.replaceChildren();
    document.getElementById('offering-lamp')?.insertAdjacentHTML(
        'beforeend',
        lampSvg({ flameId: OFFERING_FLAME_ID }),
    );

    document.getElementById('modal-offering-lamp')?.replaceChildren();
    document.getElementById('modal-offering-lamp')?.insertAdjacentHTML(
        'beforeend',
        lampSvg({ lit: true, flameId: MODAL_OFFERING_FLAME_ID }),
    );

    document.querySelector('.incense-preview')?.replaceChildren();
    document.querySelector('.incense-preview')?.insertAdjacentHTML(
        'beforeend',
        incenseSvg({ lit: true, sticks: 1 }),
    );

    const musicPreview = document.getElementById('music-preview');
    if (musicPreview) {
        musicPreview.innerHTML = dranyenSvg();
    }

    document.querySelectorAll('.water-bowl-preview').forEach((bowl) => {
        bowl.innerHTML = waterBowlSvg({ filled: false });
    });

    document.querySelectorAll('#offered-lamps .butter-lamp').forEach((lamp) => {
        const label = lamp.querySelector('.lamp-name');
        lamp.innerHTML = lampSvg({ lit: true });
        if (label) {
            lamp.appendChild(label);
        }
    });
}

function lightLamp() {
    if (isLit || isOffering) {
        return;
    }

    isLit = true;
    document.getElementById(OFFERING_FLAME_ID)?.classList.add('lit');
    document.getElementById(MODAL_OFFERING_FLAME_ID)?.classList.add('lit');
    document.getElementById(BTN_LIGHT_ID)?.setAttribute('disabled', 'disabled');

    const modal = document.getElementById(LAMP_OFFERING_MODAL_ID);
    if (!modal) {
        return;
    }

    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');
    document.getElementById(LAMP_NAME_ID)?.focus();
}

function closeLampOfferingModal(force = false) {
    if (isOffering && !force) {
        return;
    }

    const modal = document.getElementById(LAMP_OFFERING_MODAL_ID);
    if (!modal) {
        return;
    }

    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
    document.getElementById(OFFERING_FLAME_ID)?.classList.remove('lit');
    document.getElementById(MODAL_OFFERING_FLAME_ID)?.classList.remove('lit');
    document.getElementById(BTN_LIGHT_ID)?.removeAttribute('disabled');
    isLit = false;
    document.getElementById(BTN_LIGHT_ID)?.focus();
}

function openSutraModal() {
    const modal = document.getElementById(SUTRA_MODAL_ID);
    if (!modal) {
        return;
    }

    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');
    document.getElementById(BTN_CLOSE_SUTRA_ID)?.focus();
}

function closeSutraModal() {
    const modal = document.getElementById(SUTRA_MODAL_ID);
    if (!modal) {
        return;
    }

    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
    document.getElementById(BTN_OPEN_SUTRA_ID)?.focus();
}

function openRefugeModal() {
    const modal = document.getElementById(REFUGE_MODAL_ID);
    if (!modal) {
        return;
    }

    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');
    document.getElementById(BTN_CLOSE_REFUGE_ID)?.focus();
}

function closeRefugeModal() {
    const modal = document.getElementById(REFUGE_MODAL_ID);
    if (!modal) {
        return;
    }

    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
    localStorage.setItem(REFUGE_DISMISSED_KEY, '1');
}

function showCookieConsent() {
    if (localStorage.getItem(COOKIE_CONSENT_KEY)) {
        return;
    }

    const banner = document.getElementById(COOKIE_CONSENT_ID);
    if (!banner) {
        return;
    }

    banner.hidden = false;
    banner.setAttribute('aria-hidden', 'false');
}

function acceptCookies() {
    localStorage.setItem(COOKIE_CONSENT_KEY, '1');

    const banner = document.getElementById(COOKIE_CONSENT_ID);
    if (!banner) {
        return;
    }

    banner.hidden = true;
    banner.setAttribute('aria-hidden', 'true');
}

function initFirstVisitPrompts() {
    showCookieConsent();

    if (!localStorage.getItem(REFUGE_DISMISSED_KEY)) {
        openRefugeModal();
    }
}

function syncMeritNamesDuration(track) {
    const set = track?.querySelector('.merit-names-set');
    if (!set) {
        return;
    }

    const width = set.getBoundingClientRect().width;
    if (width <= 0) {
        return;
    }

    const duration = Math.max(60, width / MERIT_NAMES_PIXELS_PER_SECOND);
    track.style.setProperty('--merit-names-duration', `${duration}s`);
}

function populateMeritNamesCarousel(names) {
    const carousel = document.getElementById(MERIT_NAMES_CAROUSEL_ID);
    const track = document.getElementById(MERIT_NAMES_TRACK_ID);
    if (!carousel || !track) {
        return;
    }

    const list = names.filter((name) => typeof name === 'string' && name.trim() !== '');

    if (list.length === 0) {
        carousel.classList.add('hidden');
        track.replaceChildren();
        track.classList.remove('is-animating');
        return;
    }

    carousel.classList.remove('hidden');
    track.replaceChildren();
    track.classList.remove('is-animating');

    const setA = document.createElement('div');
    setA.className = 'merit-names-set';
    const setB = document.createElement('div');
    setB.className = 'merit-names-set';
    setB.setAttribute('aria-hidden', 'true');

    list.forEach((name) => {
        const chip = document.createElement('span');
        chip.className = 'merit-name-chip';
        chip.textContent = name;
        setA.appendChild(chip);
    });

    list.forEach((name) => {
        const chip = document.createElement('span');
        chip.className = 'merit-name-chip';
        chip.textContent = name;
        setB.appendChild(chip);
    });

    track.append(setA, setB);

    requestAnimationFrame(() => {
        syncMeritNamesDuration(track);
        track.classList.add('is-animating');
    });
}

function openDedicationModal() {
    const modal = document.getElementById(DEDICATION_MODAL_ID);
    if (!modal) {
        return;
    }

    populateMeritNamesCarousel(shrineState.offering_names ?? []);
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');
    document.getElementById(BTN_CLOSE_DEDICATION_ID)?.focus();
}

function closeDedicationModal() {
    const modal = document.getElementById(DEDICATION_MODAL_ID);
    if (!modal) {
        return;
    }

    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
    document.getElementById(BTN_OPEN_DEDICATION_ID)?.focus();
}

async function offerLamp() {
    if (!isLit || isOffering) {
        return;
    }

    isOffering = true;
    const offerBtn = document.getElementById(BTN_OFFER_ID);
    const lightBtn = document.getElementById(BTN_LIGHT_ID);
    const nameInput = document.getElementById(LAMP_NAME_ID);
    const offeringLamp = document.getElementById(OFFERING_LAMP_ID);

    offerBtn?.setAttribute('disabled', 'disabled');
    const name = nameInput?.value.trim() ?? '';

    try {
        const response = await fetch('/butter-lamps', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ name: name || null, ...offeringPayload() }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(offeringErrorMessage(data, 'Offering failed'));
        }
        const savedName = data.lamp?.name ?? null;
        const offeredContainer = document.getElementById(OFFERED_LAMPS_ID);
        const landing = offeringRowLandingPoint(offeredContainer);

        if (offeringLamp && landing) {
            const sourceRect = offeringLamp.getBoundingClientRect();
            const flyingLamp = createLampElement(savedName, null, false);
            flyingLamp.classList.add('flying-lamp');
            flyingLamp.style.left = `${sourceRect.left + sourceRect.width / 2}px`;
            flyingLamp.style.top = `${sourceRect.top}px`;
            flyingLamp.style.transform = 'translate(-50%, 0)';
            document.body.appendChild(flyingLamp);

            await new Promise((resolve) => {
                const startX = sourceRect.left + sourceRect.width / 2;
                const startY = sourceRect.top;
                const endX = landing.x;
                const endY = landing.y;
                const duration = 1800;
                const startTime = performance.now();

                const animate = (now) => {
                    const progress = Math.min((now - startTime) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    flyingLamp.style.left = `${startX + (endX - startX) * eased}px`;
                    flyingLamp.style.top = `${startY + (endY - startY) * eased - Math.sin(progress * Math.PI) * 80}px`;
                    flyingLamp.style.opacity = `${0.6 + progress * 0.4}`;

                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        flyingLamp.remove();
                        resolve();
                    }
                };

                requestAnimationFrame(animate);
            });
        }

        if (data.lamp?.id) {
            mergeShrineState(data.shrine_state);
            applyShrineState();
        }

        document.getElementById(OFFERING_FLAME_ID)?.classList.remove('lit');
        document.getElementById(MODAL_OFFERING_FLAME_ID)?.classList.remove('lit');
        if (nameInput) {
            nameInput.value = '';
        }
        if (Array.isArray(data.dedication_names)) {
            renderDedication(data.dedication_names);
        }
        if (Array.isArray(data.offering_names)) {
            shrineState.offering_names = data.offering_names;
            populateMeritNamesCarousel(data.offering_names);
        }

        isLit = false;
        closeLampOfferingModal(true);
    } catch (error) {
        alert(error.message ?? 'Unable to place your offering. Please try again.');
    } finally {
        isOffering = false;
        offerBtn?.removeAttribute('disabled');
    }
}

async function addMantraRepetitions() {
    if (isAddingMantra) {
        return;
    }

    const countInput = document.getElementById(MANTRA_COUNT_ID);
    const addBtn = document.getElementById(BTN_ADD_MANTRA_ID);
    const count = Number.parseInt(countInput?.value ?? '', 10);

    if (!Number.isFinite(count) || count < 1 || count > 100000) {
        alert('Please enter a repetition count between 1 and 100,000.');
        return;
    }

    isAddingMantra = true;
    addBtn?.setAttribute('disabled', 'disabled');

    try {
        const response = await fetch('/mantra-repetitions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(offeringPayload({ count })),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(offeringErrorMessage(data, 'Mantra offering failed'));
        }

        mergeShrineState(data.shrine_state);
        updateMantraTotal(data.total_count ?? shrineState.mantra_total ?? 0);
        if (Array.isArray(data.dedication_names)) {
            renderDedication(data.dedication_names);
        }
        applyShrineState();
    } catch (error) {
        alert(error.message ?? 'Unable to add your repetitions. Please try again.');
    } finally {
        isAddingMantra = false;
        addBtn?.removeAttribute('disabled');
    }
}

async function offerIncense() {
    const btn = document.getElementById(BTN_OFFER_INCENSE_ID);
    const nameInput = document.getElementById('incense-name');
    btn?.setAttribute('disabled', 'disabled');

    try {
        const response = await fetch('/incense-offerings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(offeringPayload({ name: nameInput?.value.trim() || null })),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(offeringErrorMessage(data, 'Incense offering failed'));
        }

        mergeShrineState(data.shrine_state);
        applyShrineState();
        if (nameInput) {
            nameInput.value = '';
        }
    } catch (error) {
        alert(error.message ?? 'Unable to offer incense. Please try again.');
    } finally {
        btn?.removeAttribute('disabled');
    }
}

async function offerFlower() {
    const btn = document.getElementById(BTN_OFFER_FLOWER_ID);
    const nameInput = document.getElementById('flower-name');
    const preview = document.getElementById('flower-preview');
    btn?.setAttribute('disabled', 'disabled');

    const name = nameInput?.value.trim() ?? '';

    try {
        const response = await fetch('/flower-offerings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(offeringPayload({ name: name || null })),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(offeringErrorMessage(data, 'Flower offering failed'));
        }

        const offering = data.offering;
        const flowerContainer = document.getElementById(OFFERED_FLOWERS_ID);
        const landing = offeringRowLandingPoint(flowerContainer);

        if (preview && landing && offering) {
            const sourceRect = preview.getBoundingClientRect();
            const flying = createFlowerElement(name || null, null, offering.flower_type, offering.vase_color);
            flying.classList.add('flying-flower');
            flying.style.left = `${sourceRect.left + sourceRect.width / 2}px`;
            flying.style.top = `${sourceRect.top}px`;
            flying.style.transform = 'translate(-50%, 0)';
            document.body.appendChild(flying);

            await new Promise((resolve) => {
                const startX = sourceRect.left + sourceRect.width / 2;
                const startY = sourceRect.top;
                const endX = landing.x;
                const endY = landing.y;
                const duration = 1600;
                const startTime = performance.now();

                const animate = (now) => {
                    const progress = Math.min((now - startTime) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    flying.style.left = `${startX + (endX - startX) * eased}px`;
                    flying.style.top = `${startY + (endY - startY) * eased - Math.sin(progress * Math.PI) * 60}px`;

                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        flying.remove();
                        resolve();
                    }
                };

                requestAnimationFrame(animate);
            });
        }

        mergeShrineState(data.shrine_state);
        applyShrineState();
        if (nameInput) {
            nameInput.value = '';
        }
        refreshFlowerPreview();
    } catch (error) {
        alert(error.message ?? 'Unable to offer flowers. Please try again.');
    } finally {
        btn?.removeAttribute('disabled');
    }
}

async function refreshShrineState() {
    try {
        const params = new URLSearchParams({
            visitor_token: getVisitorToken(),
        });

        const response = await fetch(`/offerings/state?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            return;
        }

        mergeShrineState(await response.json());
        applyShrineState();
    } catch {
        // ignore polling errors
    }
}

function startShrinePolling() {
    if (shrinePollInterval) {
        return;
    }

    refreshShrineState();
    shrinePollInterval = window.setInterval(refreshShrineState, SHRINE_POLL_MS);
}

function startStatePolling() {
    startShrinePolling();
}

function stopStatePolling() {
    // Shrine polling stays active for all visitors.
}

document.getElementById(BTN_LIGHT_ID)?.addEventListener('click', lightLamp);
document.getElementById(BTN_OFFER_ID)?.addEventListener('click', offerLamp);
document.getElementById(BTN_CLOSE_LAMP_MODAL_ID)?.addEventListener('click', closeLampOfferingModal);
document.querySelectorAll('[data-close-lamp]').forEach((element) => {
    element.addEventListener('click', closeLampOfferingModal);
});
document.getElementById(BTN_ADD_MANTRA_ID)?.addEventListener('click', addMantraRepetitions);
document.getElementById(BTN_OPEN_SUTRA_ID)?.addEventListener('click', openSutraModal);
document.getElementById(BTN_CLOSE_SUTRA_ID)?.addEventListener('click', closeSutraModal);
document.getElementById(BTN_OFFER_INCENSE_ID)?.addEventListener('click', offerIncense);
document.getElementById(BTN_OFFER_FLOWER_ID)?.addEventListener('click', offerFlower);
document.getElementById(BTN_OFFER_WATER_ID)?.addEventListener('click', offerWater);
document.getElementById(BTN_OFFER_MUSIC_ID)?.addEventListener('click', openMusicModal);
document.getElementById(BTN_CLOSE_MUSIC_MODAL_ID)?.addEventListener('click', closeMusicModal);
document.getElementById(BTN_SUBMIT_MUSIC_SUGGESTION_ID)?.addEventListener('click', submitMusicSuggestion);
document.querySelectorAll('[data-close-music]').forEach((element) => {
    element.addEventListener('click', closeMusicModal);
});

document.querySelectorAll('[data-close-sutra]').forEach((element) => {
    element.addEventListener('click', closeSutraModal);
});

document.getElementById(BTN_ACCEPT_COOKIES_ID)?.addEventListener('click', acceptCookies);
document.getElementById(BTN_CLOSE_REFUGE_ID)?.addEventListener('click', closeRefugeModal);
document.querySelectorAll('[data-close-refuge]').forEach((element) => {
    element.addEventListener('click', closeRefugeModal);
});

document.getElementById(BTN_OPEN_DEDICATION_ID)?.addEventListener('click', openDedicationModal);
document.getElementById(BTN_CLOSE_DEDICATION_ID)?.addEventListener('click', closeDedicationModal);
document.querySelectorAll('[data-close-dedication]').forEach((element) => {
    element.addEventListener('click', closeDedicationModal);
});

document.addEventListener('keydown', (event) => {
    const sutraModal = document.getElementById(SUTRA_MODAL_ID);
    const lampModal = document.getElementById(LAMP_OFFERING_MODAL_ID);
    const refugeModal = document.getElementById(REFUGE_MODAL_ID);
    const dedicationModal = document.getElementById(DEDICATION_MODAL_ID);
    const musicModal = document.getElementById(MUSIC_MODAL_ID);

    if (event.key === 'Escape') {
        if (musicModal && !musicModal.hidden) {
            closeMusicModal();
            return;
        }

        if (lampModal && !lampModal.hidden) {
            closeLampOfferingModal();
            return;
        }

        if (dedicationModal && !dedicationModal.hidden) {
            closeDedicationModal();
            return;
        }

        if (refugeModal && !refugeModal.hidden) {
            closeRefugeModal();
            return;
        }

        if (sutraModal && !sutraModal.hidden) {
            closeSutraModal();
        }
    }
});

document.getElementById(LAMP_NAME_ID)?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
        event.preventDefault();
        if (isLit && !isOffering) {
            offerLamp();
        }
    }
});

document.getElementById(MANTRA_COUNT_ID)?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
        event.preventDefault();
        addMantraRepetitions();
    }
});

loadInitialState();
initOfferingGraphics();
updateIncenseDisplay(shrineState.incense);
startPractitionerPresence();
startShrinePolling();
startLampRayInterval();
initFirstVisitPrompts();
