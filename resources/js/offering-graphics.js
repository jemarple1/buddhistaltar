export const FLOWER_TYPES = ['lotus', 'marigold', 'peony', 'orchid', 'rose', 'chrysanthemum'];

export const VASE_COLORS = ['blue', 'white', 'yellow', 'red', 'green'];

export function randomFlowerType() {
    return FLOWER_TYPES[Math.floor(Math.random() * FLOWER_TYPES.length)];
}

export function randomVaseColor() {
    return VASE_COLORS[Math.floor(Math.random() * VASE_COLORS.length)];
}

export function vaseColorForId(id) {
    if (!id) {
        return randomVaseColor();
    }

    return VASE_COLORS[Number(id) % VASE_COLORS.length];
}

let svgIdCounter = 0;

function nextSvgIds() {
    svgIdCounter += 1;
    const n = svgIdCounter;
    return {
        bronze: `vg-bronze-${n}`,
        rim: `vg-rim-${n}`,
        flame: `vg-flame-${n}`,
        aura: `vg-aura-${n}`,
        water: `vg-water-${n}`,
        glow: `vg-glow-${n}`,
        petal: `vg-petal-${n}`,
        vaseBody: `vg-vase-body-${n}`,
        vaseShade: `vg-vase-shade-${n}`,
        vaseRim: `vg-vase-rim-${n}`,
        vaseClip: `vg-vase-clip-${n}`,
    };
}

const VASE_PALETTES = {
    blue: {
        stops: ['#dbeafe', '#38bdf8', '#1d4ed8', '#1e3a8a'],
        stroke: '#1e3a8a',
        pattern: '#ffffff',
        accent: '#fde68a',
    },
    white: {
        stops: ['#ffffff', '#f8fafc', '#e2e8f0', '#cbd5e1'],
        stroke: '#1e40af',
        pattern: '#2563eb',
        accent: '#dc2626',
    },
    yellow: {
        stops: ['#fef9c3', '#fde047', '#eab308', '#a16207'],
        stroke: '#854d0e',
        pattern: '#b91c1c',
        accent: '#14532d',
    },
    red: {
        stops: ['#fecaca', '#ef4444', '#b91c1c', '#7f1d1d'],
        stroke: '#450a0a',
        pattern: '#fde68a',
        accent: '#ffffff',
    },
    green: {
        stops: ['#dcfce7', '#4ade80', '#16a34a', '#14532d'],
        stroke: '#14532d',
        pattern: '#ffffff',
        accent: '#fde047',
    },
};

function vasePaletteDefs(ids, color) {
    const palette = VASE_PALETTES[color] ?? VASE_PALETTES.blue;
    const [s0, s1, s2, s3] = palette.stops;

    return `
  <linearGradient id="${ids.vaseBody}" x1="15%" y1="0%" x2="85%" y2="100%">
    <stop offset="0%" stop-color="${s0}"/>
    <stop offset="28%" stop-color="${s1}"/>
    <stop offset="62%" stop-color="${s2}"/>
    <stop offset="100%" stop-color="${s3}"/>
  </linearGradient>
  <linearGradient id="${ids.vaseShade}" x1="0%" y1="0%" x2="100%" y2="0%">
    <stop offset="0%" stop-color="#000000" stop-opacity="0.18"/>
    <stop offset="18%" stop-color="#ffffff" stop-opacity="0.35"/>
    <stop offset="50%" stop-color="#ffffff" stop-opacity="0.12"/>
    <stop offset="82%" stop-color="#000000" stop-opacity="0.12"/>
    <stop offset="100%" stop-color="#000000" stop-opacity="0.22"/>
  </linearGradient>
  <linearGradient id="${ids.vaseRim}" x1="0%" y1="0%" x2="100%" y2="0%">
    <stop offset="0%" stop-color="${palette.stroke}" stop-opacity="0.85"/>
    <stop offset="25%" stop-color="${palette.accent}" stop-opacity="0.95"/>
    <stop offset="50%" stop-color="#ffffff" stop-opacity="0.9"/>
    <stop offset="75%" stop-color="${palette.accent}" stop-opacity="0.95"/>
    <stop offset="100%" stop-color="${palette.stroke}" stop-opacity="0.85"/>
  </linearGradient>
  <clipPath id="${ids.vaseClip}">
    <path d="M24 73 C22 86 26 96 40 97 C54 96 58 86 56 73 L54 73 L26 73 Z"/>
  </clipPath>`;
}

function vasePatternMarkup(ids, color) {
    const palette = VASE_PALETTES[color] ?? VASE_PALETTES.blue;
    const p = palette.pattern;
    const a = palette.accent;
    const s = palette.stroke;

    if (color === 'blue') {
        return `
    <path d="M22 78 Q28 76 34 78 T46 78 T58 78" fill="none" stroke="${a}" stroke-width="0.55" opacity="0.85"/>
    <path d="M24 82 Q32 80 40 82 T56 82" fill="none" stroke="${p}" stroke-width="0.45" opacity="0.7"/>
    <path d="M26 86 Q34 84 40 86 T54 86" fill="none" stroke="${a}" stroke-width="0.45" opacity="0.75"/>
    <path d="M28 90 Q36 88 40 90 T52 90" fill="none" stroke="${p}" stroke-width="0.4" opacity="0.65"/>
    <circle cx="30" cy="80" r="1.4" fill="${p}" opacity="0.85"/>
    <circle cx="40" cy="84" r="1.6" fill="${a}" opacity="0.9"/>
    <circle cx="50" cy="80" r="1.4" fill="${p}" opacity="0.85"/>
    <path d="M28 88 L32 84 L36 88 L32 92 Z" fill="none" stroke="${p}" stroke-width="0.4" opacity="0.65"/>
    <path d="M44 88 L48 84 L52 88 L48 92 Z" fill="none" stroke="${a}" stroke-width="0.4" opacity="0.7"/>
    <path d="M34 79 Q36 81 34 83 Q32 81 34 79" fill="none" stroke="${p}" stroke-width="0.35" opacity="0.55"/>
    <path d="M46 79 Q48 81 46 83 Q44 81 46 79" fill="none" stroke="${p}" stroke-width="0.35" opacity="0.55"/>`;
    }

    if (color === 'white') {
        return `
    <path d="M26 78 C28 76 30 78 32 76 C34 78 36 76 38 78 C40 76 42 78 44 76 C46 78 48 76 50 78" fill="none" stroke="${p}" stroke-width="0.5"/>
    <path d="M27 84 C29 82 31 84 33 82 C35 84 37 82 39 84 C41 82 43 84 45 82 C47 84 49 82 51 84" fill="none" stroke="${p}" stroke-width="0.45" opacity="0.85"/>
    <path d="M28 89 C30 87 32 89 34 87 C36 89 38 87 40 89 C42 87 44 89 46 87 C48 89 50 87 52 89" fill="none" stroke="${p}" stroke-width="0.4" opacity="0.7"/>
    <circle cx="32" cy="88" r="1.4" fill="${palette.accent}" opacity="0.8"/>
    <circle cx="40" cy="80" r="1.5" fill="${p}" opacity="0.7"/>
    <circle cx="48" cy="88" r="1.4" fill="${palette.accent}" opacity="0.8"/>
    <path d="M29 90 Q32 87 35 90" fill="none" stroke="${p}" stroke-width="0.35" opacity="0.6"/>
    <path d="M45 90 Q48 87 51 90" fill="none" stroke="${p}" stroke-width="0.35" opacity="0.6"/>
    <path d="M36 82 L38 84 L36 86 L34 84 Z" fill="${p}" opacity="0.35"/>
    <path d="M44 82 L46 84 L44 86 L42 84 Z" fill="${palette.accent}" opacity="0.4"/>`;
    }

    if (color === 'yellow') {
        return `
    <path d="M26 79 L30 83 L26 87 L22 83 Z" fill="none" stroke="${p}" stroke-width="0.45"/>
    <path d="M34 79 L38 83 L34 87 L30 83 Z" fill="none" stroke="${p}" stroke-width="0.45"/>
    <path d="M42 79 L46 83 L42 87 L38 83 Z" fill="none" stroke="${p}" stroke-width="0.45"/>
    <path d="M50 79 L54 83 L50 87 L46 83 Z" fill="none" stroke="${p}" stroke-width="0.45"/>
    <path d="M24 84 H56" fill="none" stroke="${a}" stroke-width="0.35" opacity="0.55"/>
    <path d="M24 88 H56" fill="none" stroke="${a}" stroke-width="0.35" opacity="0.55"/>
    <path d="M24 92 H56" fill="none" stroke="${s}" stroke-width="0.3" opacity="0.45"/>
    <circle cx="28" cy="83" r="0.9" fill="${a}" opacity="0.75"/>
    <circle cx="40" cy="83" r="0.9" fill="${a}" opacity="0.75"/>
    <circle cx="52" cy="83" r="0.9" fill="${a}" opacity="0.75"/>
    <path d="M30 86 L32 88 L30 90 L28 88 Z" fill="${p}" opacity="0.35"/>
    <path d="M50 86 L52 88 L50 90 L48 88 Z" fill="${p}" opacity="0.35"/>`;
    }

    if (color === 'red') {
        return `
    <path d="M24 79 Q28 77 32 79 Q36 81 40 79 Q44 77 48 79 Q52 81 56 79" fill="none" stroke="${p}" stroke-width="0.55" opacity="0.9"/>
    <path d="M26 83 Q30 81 34 83 Q38 85 42 83 Q46 81 50 83 Q54 85 58 83" fill="none" stroke="${p}" stroke-width="0.5" opacity="0.85"/>
    <path d="M28 87 Q32 85 36 87 Q40 89 44 87 Q48 85 52 87" fill="none" stroke="${p}" stroke-width="0.45" opacity="0.8"/>
    <path d="M30 91 Q34 89 38 91 Q42 93 46 91 Q50 89 54 91" fill="none" stroke="${p}" stroke-width="0.4" opacity="0.75"/>
    <circle cx="32" cy="80" r="1.2" fill="${p}" opacity="0.95"/>
    <circle cx="48" cy="86" r="1.2" fill="${p}" opacity="0.95"/>
    <circle cx="40" cy="90" r="1.1" fill="${a}" opacity="0.85"/>
    <path d="M36 80 Q38 82 36 84 Q34 82 36 80" fill="none" stroke="${a}" stroke-width="0.35" opacity="0.7"/>
    <path d="M44 80 Q46 82 44 84 Q42 82 44 80" fill="none" stroke="${a}" stroke-width="0.35" opacity="0.7"/>`;
    }

    return `
    <path d="M25 79 Q29 77 33 79 T41 79 T49 79 T57 79" fill="none" stroke="${p}" stroke-width="0.45" opacity="0.8"/>
    <path d="M27 83 Q31 81 35 83 T43 83 T51 83 T55 83" fill="none" stroke="${p}" stroke-width="0.4" opacity="0.75"/>
    <path d="M28 87 Q32 85 36 87 T44 87 T52 87" fill="none" stroke="${p}" stroke-width="0.38" opacity="0.7"/>
    <path d="M30 78 V92 M36 77 V93 M42 77 V93 M48 78 V92" fill="none" stroke="${a}" stroke-width="0.35" opacity="0.55"/>
    <circle cx="33" cy="86" r="1.1" fill="${a}" opacity="0.85"/>
    <circle cx="47" cy="86" r="1.1" fill="${a}" opacity="0.85"/>
    <path d="M38 80 Q40 82 38 84 Q36 82 38 80" fill="none" stroke="${p}" stroke-width="0.35" opacity="0.6"/>
    <path d="M42 80 Q44 82 42 84 Q40 82 42 80" fill="none" stroke="${p}" stroke-width="0.35" opacity="0.6"/>`;
}

function svgDefs(ids, vaseColor = null) {
    const vaseExtra = vaseColor ? vasePaletteDefs(ids, vaseColor) : '';

    return `<defs>
  <linearGradient id="${ids.bronze}" x1="0%" y1="0%" x2="100%" y2="100%">
    <stop offset="0%" stop-color="#fff8dc"/>
    <stop offset="18%" stop-color="#f0d060"/>
    <stop offset="48%" stop-color="#c9a227"/>
    <stop offset="78%" stop-color="#7a5c10"/>
    <stop offset="100%" stop-color="#2a2006"/>
  </linearGradient>
  <linearGradient id="${ids.rim}" x1="0%" y1="0%" x2="100%" y2="0%">
    <stop offset="0%" stop-color="#4a3808"/>
    <stop offset="20%" stop-color="#e8d48a"/>
    <stop offset="50%" stop-color="#fffef5"/>
    <stop offset="80%" stop-color="#e8d48a"/>
    <stop offset="100%" stop-color="#4a3808"/>
  </linearGradient>
  <radialGradient id="${ids.flame}" cx="50%" cy="88%" r="60%">
    <stop offset="0%" stop-color="#ffffff"/>
    <stop offset="28%" stop-color="#ffe9a8"/>
    <stop offset="58%" stop-color="#ff8c1a"/>
    <stop offset="100%" stop-color="#cc3300" stop-opacity="0"/>
  </radialGradient>
  <radialGradient id="${ids.aura}" cx="50%" cy="72%" r="68%">
    <stop offset="0%" stop-color="#ffc866" stop-opacity="0.65"/>
    <stop offset="100%" stop-color="#ff5500" stop-opacity="0"/>
  </radialGradient>
  <linearGradient id="${ids.water}" x1="0%" y1="0%" x2="0%" y2="100%">
    <stop offset="0%" stop-color="#ecfaff"/>
    <stop offset="35%" stop-color="#7dd3fc"/>
    <stop offset="100%" stop-color="#0369a1"/>
  </linearGradient>
  <radialGradient id="${ids.petal}" cx="35%" cy="30%" r="70%">
    <stop offset="0%" stop-color="#ffffff" stop-opacity="0.95"/>
    <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
  </radialGradient>
  <filter id="${ids.glow}" x="-50%" y="-50%" width="200%" height="200%">
    <feGaussianBlur stdDeviation="1.4" result="blur"/>
    <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
  </filter>
  ${vaseExtra}
</defs>`;
}

const VASE_PATH = 'M24 73 C22 86 26 96 40 97 C54 96 58 86 56 73 Z';

function vaseMarkup(ids, color = 'blue') {
    const palette = VASE_PALETTES[color] ?? VASE_PALETTES.blue;

    return `
    <ellipse cx="40" cy="93" rx="19" ry="4.5" fill="#000000" opacity="0.22"/>
    <path d="${VASE_PATH}" fill="url(#${ids.vaseBody})" stroke="${palette.stroke}" stroke-width="0.7"/>
    <path d="${VASE_PATH}" fill="url(#${ids.vaseShade})" stroke="none"/>
    <g clip-path="url(#${ids.vaseClip})">${vasePatternMarkup(ids, color)}</g>
    <path d="M26 73 Q40 68 54 73" fill="none" stroke="url(#${ids.vaseRim})" stroke-width="1.55"/>
    <ellipse cx="40" cy="74.5" rx="14" ry="2.3" fill="#ffffff" opacity="0.42"/>
    <path d="M37 58 C39 52 41 52 43 58 L42 73 L38 73 Z" fill="#1f5a2a" stroke="#12361a" stroke-width="0.45"/>
    <path d="M36 60 Q40 57 44 60" fill="none" stroke="#3d8f4a" stroke-width="0.35" opacity="0.7"/>
    `;
}

function petalPath(cx, cy, w, h, rot, fill, stroke = 'rgba(0,0,0,0.15)') {
    return `<path d="M${cx} ${cy - h} C${cx + w} ${cy - h * 0.35} ${cx + w * 0.85} ${cy + h * 0.55} ${cx} ${cy + h * 0.65} C${cx - w * 0.85} ${cy + h * 0.55} ${cx - w} ${cy - h * 0.35} ${cx} ${cy - h} Z" fill="${fill}" stroke="${stroke}" stroke-width="0.35" transform="rotate(${rot} ${cx} ${cy})"/>`;
}

const flowerDrawers = {
    lotus(ids) {
        let s = '';
        for (let i = 0; i < 12; i++) {
            const tone = i % 3 === 0 ? '#fff7fb' : i % 3 === 1 ? '#fce7f3' : '#fbcfe8';
            s += petalPath(40, 36, 8, 13, i * 30, tone);
        }
        return `${s}
        <circle cx="40" cy="36" r="5.5" fill="#fde68a" stroke="#ca8a04" stroke-width="0.45"/>
        <circle cx="38.5" cy="34.5" r="1.4" fill="#fffef0" opacity="0.9"/>`;
    },
    marigold(ids) {
        let s = '';
        for (let i = 0; i < 24; i++) {
            s += petalPath(40, 34, 3.8, 10, i * 15, i % 2 ? '#fbbf24' : '#f59e0b', '#92400e');
        }
        return `${s}<circle cx="40" cy="34" r="5.5" fill="#78350f" stroke="#451a03" stroke-width="0.5"/>`;
    },
    peony(ids) {
        let s = '';
        for (let i = 0; i < 14; i++) {
            s += petalPath(40, 33, 7, 10, i * 25.7, i % 2 ? '#fecdd3' : '#fb7185');
        }
        for (let i = 0; i < 8; i++) {
            s += petalPath(40, 33, 4.5, 7, i * 45 + 8, '#e11d48', '#881337');
        }
        return `${s}<circle cx="40" cy="33" r="3.5" fill="#fde047" opacity="0.9"/>`;
    },
    orchid(ids) {
        return `
        ${petalPath(40, 38, 13, 6, 0, '#ddd6fe', '#5b21b6')}
        ${petalPath(27, 32, 8, 5, -38, '#c4b5fd', '#4c1d95')}
        ${petalPath(53, 32, 8, 5, 38, '#c4b5fd', '#4c1d95')}
        ${petalPath(40, 26, 4.5, 7, 0, '#a855f7', '#581c87')}
        <path d="M39 38 L40 54 Q40 57 43 56" fill="none" stroke="#65a30d" stroke-width="1.2" stroke-linecap="round"/>
        <ellipse cx="42" cy="55" rx="3.5" ry="2.2" fill="#eab308" stroke="#a16207" stroke-width="0.35"/>`;
    },
    rose(ids) {
        let s = '';
        for (let i = 0; i < 12; i++) {
            s += petalPath(40, 34, 6.5, 8.5, i * 30, i < 6 ? '#fda4af' : '#e11d48', '#881337');
        }
        s += petalPath(40, 34, 3.5, 4.5, 15, '#9f1239');
        return `${s}<circle cx="40" cy="34" r="2" fill="#4c0519"/>`;
    },
    chrysanthemum(ids) {
        let s = '';
        for (let i = 0; i < 28; i++) {
            s += petalPath(40, 34, 2.8, 11, i * 12.85, i % 2 ? '#fde047' : '#facc15', '#b45309');
        }
        return `${s}<circle cx="40" cy="34" r="4.5" fill="#92400e"/>`;
    },
};

export function flowerSvg(type, vaseColor = null) {
    const ids = nextSvgIds();
    const color = vaseColor && VASE_PALETTES[vaseColor] ? vaseColor : randomVaseColor();
    const drawer = flowerDrawers[type] ?? flowerDrawers.lotus;
    return `<svg class="offering-svg offering-svg--flower" viewBox="0 0 80 100" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">${svgDefs(ids, color)}${drawer(ids)}${vaseMarkup(ids, color)}</svg>`;
}

export function lampSvg({ lit = false, flameId = null } = {}) {
    const ids = nextSvgIds();
    const flameClass = lit ? 'lamp-flame-svg lit' : 'lamp-flame-svg';
    const idAttr = flameId ? ` id="${flameId}"` : '';
    const flameOpacity = lit ? 1 : 0;
    return `<svg class="offering-svg offering-svg--lamp" viewBox="0 0 64 72" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">${svgDefs(ids)}
    <ellipse cx="32" cy="69" rx="17" ry="3.5" fill="#000" opacity="0.22"/>
    <path d="M17 49 C15 63 19 67 32 67 C45 67 49 63 47 49 Z" fill="url(#${ids.bronze})" stroke="#3d2e08" stroke-width="0.75"/>
    <path d="M19 49 Q32 44 45 49" fill="none" stroke="url(#${ids.rim})" stroke-width="1.7"/>
    <ellipse cx="32" cy="51" rx="12" ry="2.2" fill="#fff8dc" opacity="0.4"/>
    <ellipse cx="32" cy="55" rx="10" ry="3.8" fill="#fbbf24" opacity="0.28"/>
    <ellipse cx="28" cy="54" rx="3" ry="1.2" fill="#fffef0" opacity="0.35"/>
    <g class="${flameClass}"${idAttr} opacity="${flameOpacity}">
      <ellipse cx="32" cy="35" rx="15" ry="17" fill="url(#${ids.aura})" filter="url(#${ids.glow})"/>
      <path d="M32 16 C39 28 37 40 34 44 C32 46 30 44 28 40 C27 28 32 16 Z" fill="url(#${ids.flame})" filter="url(#${ids.glow})"/>
      <path d="M32 20 C34 28 33 36 32 38 C31 36 30 28 32 20 Z" fill="#fffff5" opacity="0.75"/>
    </g>
  </svg>`;
}

export function incenseSvg({ lit = true, sticks = 1 } = {}) {
    const ids = nextSvgIds();

    if (sticks <= 0) {
        return `<svg class="offering-svg offering-svg--incense" viewBox="0 0 64 80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">${svgDefs(ids)}
    <ellipse cx="32" cy="75" rx="21" ry="4" fill="#000" opacity="0.18"/>
    <path d="M13 59 C11 73 15 77 32 77 C49 77 53 73 51 59 Z" fill="#78716c" stroke="#44403c" stroke-width="0.65"/>
    <ellipse cx="32" cy="61" rx="17" ry="3.2" fill="#a8a29e"/>
    <ellipse cx="32" cy="59" rx="19" ry="2.2" fill="#d6d3d1"/>
  </svg>`;
    }

    const count = Math.max(1, Math.min(sticks, 3));
    const width = 64 + Math.max(0, count - 1) * 10;
    const center = width / 2;
    const startX = center - ((count - 1) * 5) / 2;

    let stickMarkup = '';
    for (let i = 0; i < count; i++) {
        const x = startX + i * 5;
        const tipY = 11 + (i % 3);
        stickMarkup += `<path d="M${x} 57 L${x} ${tipY + 2}" stroke="#92400e" stroke-width="2.1" stroke-linecap="round"/>`;
        stickMarkup += `<path d="M${x} 30 Q${x + 0.6} ${tipY + 10} ${x} ${tipY + 2}" stroke="#b45309" stroke-width="0.55" opacity="0.45" fill="none"/>`;
        if (lit) {
            stickMarkup += `<circle cx="${x}" cy="${tipY}" r="2.4" fill="#ff5500" filter="url(#${ids.glow})"><animate attributeName="opacity" values="0.82;1;0.82" dur="${1.4 + (i % 3) * 0.2}s" repeatCount="indefinite"/></circle>`;
            stickMarkup += `<circle cx="${x}" cy="${tipY}" r="5.5" fill="#ff8800" opacity="0.28"/>`;
            stickMarkup += `<circle cx="${x - 0.6}" cy="${tipY - 0.8}" r="0.85" fill="#fff5cc" opacity="0.9"/>`;
        } else {
            stickMarkup += `<circle cx="${x}" cy="${tipY}" r="2" fill="#666" opacity="0.35"/>`;
        }
    }

    return `<svg class="offering-svg offering-svg--incense" viewBox="0 0 ${width} 80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">${svgDefs(ids)}
    <ellipse cx="${center}" cy="75" rx="${21 + Math.max(0, count - 3) * 2}" ry="4" fill="#000" opacity="0.18"/>
    <path d="M${center - 19} 59 C${center - 21} 73 ${center - 17} 77 ${center} 77 C${center + 17} 77 ${center + 21} 73 ${center + 19} 59 Z" fill="#78716c" stroke="#44403c" stroke-width="0.65"/>
    <ellipse cx="${center}" cy="61" rx="${17 + Math.max(0, count - 3) * 2}" ry="3.2" fill="#a8a29e"/>
    <ellipse cx="${center}" cy="59" rx="${19 + Math.max(0, count - 3) * 2}" ry="2.2" fill="#d6d3d1"/>
    ${stickMarkup}
  </svg>`;
}

export function waterBowlSvg({ filled = false } = {}) {
    const ids = nextSvgIds();
    const water = filled
        ? `<ellipse cx="32" cy="35" rx="14" ry="5.5" fill="url(#${ids.water})"/>
           <ellipse cx="27" cy="33" rx="5.5" ry="1.6" fill="#ffffff" opacity="0.5"/>
           <ellipse cx="36" cy="37" rx="3" ry="0.8" fill="#ffffff" opacity="0.25"/>`
        : '';
    return `<svg class="offering-svg offering-svg--water" viewBox="0 0 64 48" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">${svgDefs(ids)}
    <ellipse cx="32" cy="45" rx="15" ry="2.8" fill="#000" opacity="0.14"/>
    <path d="M15 29 C13 41 17 45 32 45 C47 45 51 41 49 29 Z" fill="url(#${ids.bronze})" stroke="#3d2e08" stroke-width="0.65"/>
    <path d="M17 29 Q32 24 47 29" fill="none" stroke="url(#${ids.rim})" stroke-width="1.5"/>
    ${water}
  </svg>`;
}

export function waterPitcherSvg() {
    const ids = nextSvgIds();
    return `<svg class="offering-svg offering-svg--pitcher" viewBox="0 0 48 64" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">${svgDefs(ids)}
    <path d="M15 21 L15 53 Q15 59 24 59 L28 59 Q37 59 37 53 L37 21 Z" fill="url(#${ids.bronze})" stroke="#3d2e08" stroke-width="0.65"/>
    <path d="M37 29 L43 31 L43 37 L37 35 Z" fill="#a67c1a" stroke="#3d2e08" stroke-width="0.45"/>
    <ellipse cx="26" cy="21" rx="10.5" ry="3.2" fill="url(#${ids.rim})"/>
    <ellipse cx="23" cy="33" rx="4.5" ry="1.3" fill="#fffef0" opacity="0.45"/>
  </svg>`;
}
