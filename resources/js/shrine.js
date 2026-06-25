import {
    FLOWER_TYPES,
    randomFlowerType,
    randomVaseColor,
    vaseColorForId,
    flowerSvg,
    lampSvg,
    incenseSvg,
    waterBowlSvg,
    waterPitcherSvg,
} from './offering-graphics.js';

const OFFERING_FLAME_ID = 'offering-flame';
const OFFERED_LAMPS_ID = 'offered-lamps';
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
const BTN_BEGIN_WATER_ID = 'btn-begin-water';
const INCENSE_SHRINE_SELECTOR = '.deity-title-row .incense-shrine';
const SYLLABLE_SMOKE_ID = 'syllable-smoke';
const WATER_STACK_ID = 'water-bowls-stack';
const WATER_ACTIVE_ID = 'water-bowls-active';
const WATER_STATUS_ID = 'water-status';
const OFFERED_WATER_ID = 'offered-water-bowls';
const WATER_PITCHER_ID = 'water-pitcher';
const WATER_TOKEN_KEY = 'shrine_water_token';
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
let waterToken = sessionStorage.getItem(WATER_TOKEN_KEY);
let syllableCloudInterval = null;
let syllableRiseInterval = null;
let syllableTimeout = null;
let statePollInterval = null;

const CLOUD_TARGET = 95;
const CLOUD_BAND_TOP = 0.03;
const CLOUD_BAND_HEIGHT = 0.34;
const CLOUD_FILL_MS = 90000;
const INCENSE_BURN_MS = 15 * 60 * 1000;

let syllableSmokeStartedAt = 0;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
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

    seedOfferedLamps();
    applyShrineState();
    renderLamps(offeredLamps);
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
    updateIncenseDisplay(shrineState.incense_expires_at);
    renderFlowers(shrineState.flowers ?? []);
    applyWaterState(shrineState.water ?? {});
    populateMeritNamesCarousel(shrineState.offering_names ?? []);
}

function getIncenseShrines() {
    return [...document.querySelectorAll(INCENSE_SHRINE_SELECTOR)];
}

function updateIncenseDisplay(expiresAt) {
    const shrines = getIncenseShrines();
    if (!shrines.length) {
        return;
    }

    if (!expiresAt || new Date(expiresAt) <= new Date()) {
        shrines.forEach((shrine) => shrine.classList.add('hidden'));
        stopSyllableSmoke();
        return;
    }

    shrines.forEach((shrine) => {
        shrine.classList.remove('hidden');
        shrine.innerHTML = incenseSvg({ lit: true });
    });
    startSyllableSmoke(expiresAt);
}

function getCloudTargetNow() {
    if (!syllableSmokeStartedAt) {
        return 0;
    }

    const elapsed = Date.now() - syllableSmokeStartedAt;
    return Math.min(CLOUD_TARGET, Math.floor((elapsed / CLOUD_FILL_MS) * CLOUD_TARGET));
}

function getCloudSpawnX() {
    const sources = getIncenseShrines().filter((shrine) => !shrine.classList.contains('hidden'));
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
    const sources = getIncenseShrines().filter((shrine) => !shrine.classList.contains('hidden'));
    if (!container || !sources.length) {
        return;
    }

    const current = container.querySelectorAll('.syllable-particle--cloud').length;
    if (current >= getCloudTargetNow()) {
        return;
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
    particle.style.setProperty('--cloud-duration', `${16 + Math.random() * 14}s`);
    particle.style.fontSize = `${0.52 + Math.random() * 0.38}rem`;

    container.appendChild(particle);
    particle.addEventListener('animationend', () => particle.remove());
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
    const sources = getIncenseShrines().filter((shrine) => !shrine.classList.contains('hidden'));
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
    const sources = getIncenseShrines().filter((shrine) => !shrine.classList.contains('hidden'));
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

function startSyllableSmoke(expiresAt) {
    stopSyllableSmoke();

    const expiresMs = new Date(expiresAt).getTime();
    const remaining = expiresMs - Date.now();

    if (remaining <= 0) {
        return;
    }

    syllableSmokeStartedAt = Math.max(Date.now() - (INCENSE_BURN_MS - remaining), Date.now() - INCENSE_BURN_MS);

    syllableCloudInterval = window.setInterval(maintainSyllableCloud, 900);
    syllableRiseInterval = window.setInterval(spawnRisingSyllable, 240);
    syllableTimeout = window.setTimeout(stopSyllableSmoke, remaining);

    spawnRisingSyllable();
}

function stopSyllableSmoke() {
    if (syllableCloudInterval) {
        clearInterval(syllableCloudInterval);
        syllableCloudInterval = null;
    }

    if (syllableRiseInterval) {
        clearInterval(syllableRiseInterval);
        syllableRiseInterval = null;
    }

    if (syllableTimeout) {
        clearTimeout(syllableTimeout);
        syllableTimeout = null;
    }

    syllableSmokeStartedAt = 0;

    const container = document.getElementById(SYLLABLE_SMOKE_ID);
    container?.querySelectorAll('.syllable-particle').forEach((particle) => {
        particle.style.animation = 'none';
        particle.style.transition = 'opacity 1.8s ease';
        particle.style.opacity = '0';
        window.setTimeout(() => particle.remove(), 1800);
    });
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

function renderFlowers(flowers) {
    const container = document.getElementById(OFFERED_FLOWERS_ID);
    if (!container) {
        return;
    }

    container.replaceChildren();
    flowers.forEach((flower) => {
        container.appendChild(
            createFlowerElement(flower.name, flower.id, flower.flower_type, flower.vase_color),
        );
    });
}

function renderLamps(lamps) {
    const container = document.getElementById(OFFERED_LAMPS_ID);
    if (!container) {
        return;
    }

    container.replaceChildren();
    lamps.forEach((lamp) => {
        container.appendChild(createLampElement(lamp.name, lamp.id, true));
    });
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

function createFlowerElement(name, id = null, flowerType = null, vaseColor = null) {
    const flower = document.createElement('div');
    flower.className = 'flower-vase offered-flower';
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

function applyWaterState(water) {
    const stack = document.getElementById(WATER_STACK_ID);
    const active = document.getElementById(WATER_ACTIVE_ID);
    const status = document.getElementById(WATER_STATUS_ID);
    const beginBtn = document.getElementById(BTN_BEGIN_WATER_ID);
    const display = document.getElementById(OFFERED_WATER_ID);

    const session = water.session;
    const isOwner = session && waterToken && session.token === waterToken;
    const inProgress = water.active && session && !session.completed_at;

    if (display) {
        const positions = water.display_positions ?? [];
        const bowls = display.querySelectorAll('.water-bowl-filled');
        if (positions.length >= 7) {
            display.classList.remove('hidden');
            bowls.forEach((bowl) => {
                const pos = Number.parseInt(bowl.dataset.position ?? '0', 10);
                bowl.hidden = !positions.includes(pos);
            });
        } else {
            display.classList.add('hidden');
        }
    }

    if (inProgress && isOwner) {
        stack?.classList.add('hidden');
        active?.classList.remove('hidden');
        beginBtn?.setAttribute('disabled', 'disabled');
        status.textContent = `Fill each bowl (${session.filled_positions.length}/7)`;
        syncActiveBowls(session.filled_positions ?? []);
    } else if (inProgress && water.locked_by_other) {
        stack?.classList.remove('hidden');
        active?.classList.add('hidden');
        beginBtn?.setAttribute('disabled', 'disabled');
        status.textContent = 'Someone is currently offering water.';
    } else {
        stack?.classList.remove('hidden');
        active?.classList.add('hidden');
        beginBtn?.removeAttribute('disabled');
        status.textContent = 'Seven bowls — one offering at a time';
        if (!inProgress) {
            waterToken = null;
            sessionStorage.removeItem(WATER_TOKEN_KEY);
        }
    }
}

function syncActiveBowls(filledPositions) {
    document.querySelectorAll('.water-bowl-offer').forEach((btn) => {
        const pos = Number.parseInt(btn.dataset.position ?? '0', 10);
        const filled = filledPositions.includes(pos);
        btn.classList.toggle('filled', filled);
        btn.disabled = filled;
        btn.innerHTML = waterBowlSvg({ filled });
    });
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

    document.querySelectorAll('.incense-shrine').forEach((shrine) => {
        shrine.innerHTML = incenseSvg({ lit: !shrine.classList.contains('hidden') });
    });

    document.querySelector('.incense-preview')?.replaceChildren();
    document.querySelector('.incense-preview')?.insertAdjacentHTML(
        'beforeend',
        incenseSvg({ lit: false }),
    );

    document.querySelectorAll('.water-bowl-stacked').forEach((bowl) => {
        bowl.innerHTML = waterBowlSvg({ filled: false });
    });

    document.querySelectorAll('.water-bowl-offer').forEach((btn) => {
        btn.innerHTML = waterBowlSvg({ filled: btn.classList.contains('filled') });
    });

    document.querySelectorAll('.water-bowl-filled').forEach((bowl) => {
        bowl.innerHTML = waterBowlSvg({ filled: true });
    });

    document.querySelectorAll('#offered-lamps .butter-lamp').forEach((lamp) => {
        const label = lamp.querySelector('.lamp-name');
        lamp.innerHTML = lampSvg({ lit: true });
        if (label) {
            lamp.appendChild(label);
        }
    });

    const pitcher = document.getElementById(WATER_PITCHER_ID);
    if (pitcher) {
        pitcher.innerHTML = `${waterPitcherSvg()}<div class="pitcher-stream"></div>`;
    }
}

async function animatePitcherPour(targetButton) {
    const pitcher = document.getElementById(WATER_PITCHER_ID);
    if (!pitcher || !targetButton) {
        return;
    }

    const targetRect = targetButton.getBoundingClientRect();
    pitcher.classList.remove('hidden');
    pitcher.style.left = `${targetRect.left + targetRect.width / 2 - 16}px`;
    pitcher.style.top = `${targetRect.top - 80}px`;
    pitcher.style.opacity = '1';

    const stream = pitcher.querySelector('.pitcher-stream');
    stream?.style.removeProperty('animation');
    void stream?.offsetWidth;
    stream?.style.setProperty('animation', 'pour-stream 0.9s ease-in forwards');

    await new Promise((resolve) => setTimeout(resolve, 950));
    pitcher.classList.add('hidden');
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
            body: JSON.stringify({ name: name || null }),
        });

        if (!response.ok) {
            throw new Error('Offering failed');
        }

        const data = await response.json();
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
            offeredLamps.unshift({ id: data.lamp.id, name: savedName });
            renderLamps(offeredLamps);
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
    } catch {
        alert('Unable to place your offering. Please try again.');
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
            body: JSON.stringify({ count }),
        });

        if (!response.ok) {
            throw new Error('Mantra offering failed');
        }

        const data = await response.json();
        const totalEl = document.getElementById(MANTRA_TOTAL_ID);
        if (totalEl && typeof data.total_count === 'number') {
            totalEl.textContent = formatCount(data.total_count);
        }
        if (Array.isArray(data.dedication_names)) {
            renderDedication(data.dedication_names);
        }
    } catch {
        alert('Unable to add your repetitions. Please try again.');
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
            body: JSON.stringify({ name: nameInput?.value.trim() || null }),
        });

        if (!response.ok) {
            throw new Error('Incense offering failed');
        }

        const data = await response.json();
        shrineState = data.shrine_state ?? shrineState;
        applyShrineState();
        if (nameInput) {
            nameInput.value = '';
        }
    } catch {
        alert('Unable to offer incense. Please try again.');
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
            body: JSON.stringify({ name: name || null }),
        });

        if (!response.ok) {
            throw new Error('Flower offering failed');
        }

        const data = await response.json();
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

        shrineState = data.shrine_state ?? shrineState;
        renderFlowers(shrineState.flowers ?? []);
        populateMeritNamesCarousel(shrineState.offering_names ?? []);
        if (nameInput) {
            nameInput.value = '';
        }
        refreshFlowerPreview();
    } catch {
        alert('Unable to offer flowers. Please try again.');
    } finally {
        btn?.removeAttribute('disabled');
    }
}

async function beginWaterOffering() {
    const btn = document.getElementById(BTN_BEGIN_WATER_ID);
    btn?.setAttribute('disabled', 'disabled');

    try {
        const response = await fetch('/water-bowls/acquire', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ token: waterToken }),
        });

        const data = await response.json();

        if (response.status === 423) {
            shrineState = data.shrine_state ?? shrineState;
            applyShrineState();
            startStatePolling();
            return;
        }

        if (!response.ok) {
            throw new Error('Could not begin water offering');
        }

        waterToken = data.session?.token ?? null;
        if (waterToken) {
            sessionStorage.setItem(WATER_TOKEN_KEY, waterToken);
        }

        shrineState = data.shrine_state ?? shrineState;
        applyShrineState();
    } catch {
        alert('Unable to begin water offering. Please try again.');
        btn?.removeAttribute('disabled');
    }
}

async function fillWaterBowl(position, button) {
    if (!waterToken || button.classList.contains('filled')) {
        return;
    }

    button.disabled = true;

    try {
        await animatePitcherPour(button);

        const response = await fetch('/water-bowls/fill', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ token: waterToken, position }),
        });

        const data = await response.json();

        if (response.status === 410) {
            waterToken = null;
            sessionStorage.removeItem(WATER_TOKEN_KEY);
            shrineState = data.shrine_state ?? shrineState;
            applyShrineState();
            alert('Your water offering session expired.');
            return;
        }

        if (!response.ok) {
            throw new Error(data.message ?? 'Could not fill bowl');
        }

        button.classList.add('filled');
        shrineState = data.shrine_state ?? shrineState;
        applyShrineState();

        if (data.session?.completed_at) {
            waterToken = null;
            sessionStorage.removeItem(WATER_TOKEN_KEY);
            stopStatePolling();
        }
    } catch {
        button.disabled = false;
        alert('Unable to fill this bowl. Please try again.');
    }
}

async function refreshShrineState() {
    try {
        const response = await fetch('/offerings/state', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            return;
        }

        shrineState = await response.json();
        applyShrineState();

        if (!shrineState.water?.active || shrineState.water?.session?.token === waterToken) {
            stopStatePolling();
        }
    } catch {
        // ignore polling errors
    }
}

function startStatePolling() {
    if (statePollInterval) {
        return;
    }

    statePollInterval = window.setInterval(refreshShrineState, 3000);
}

function stopStatePolling() {
    if (statePollInterval) {
        clearInterval(statePollInterval);
        statePollInterval = null;
    }
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
document.getElementById(BTN_BEGIN_WATER_ID)?.addEventListener('click', beginWaterOffering);

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

document.querySelectorAll('.water-bowl-offer').forEach((button) => {
    button.addEventListener('click', () => {
        const position = Number.parseInt(button.dataset.position ?? '0', 10);
        fillWaterBowl(position, button);
    });
});

document.addEventListener('keydown', (event) => {
    const sutraModal = document.getElementById(SUTRA_MODAL_ID);
    const lampModal = document.getElementById(LAMP_OFFERING_MODAL_ID);
    const refugeModal = document.getElementById(REFUGE_MODAL_ID);
    const dedicationModal = document.getElementById(DEDICATION_MODAL_ID);

    if (event.key === 'Escape') {
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
initFirstVisitPrompts();

if (waterToken) {
    beginWaterOffering();
} else if (shrineState.water?.locked_by_other) {
    startStatePolling();
}
