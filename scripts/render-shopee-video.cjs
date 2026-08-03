/**
 * Build video slideshow MP4 dari 9 banner.
 * Durasi: 4s/slide + 0.6s cross-fade transition. Total ~31 detik.
 * Output: shopee/video-marketing.mp4 (1920x1080 H.264).
 */
const { spawn } = require('child_process');
const ffmpegPath = require('ffmpeg-static');
const path = require('path');
const fs = require('fs');

const SLIDES_DIR = path.join(__dirname, '..', 'shopee');
const OUT        = path.join(SLIDES_DIR, 'video-marketing.mp4');

const PER_SLIDE_SEC = 4;       // berapa detik tiap slide tampil
const FADE_SEC      = 0.6;     // cross-fade duration

const inputs = [];
for (let i = 1; i <= 9; i++) {
    const p = path.join(SLIDES_DIR, `${String(i).padStart(2, '0')}-banner.png`);
    if (!fs.existsSync(p)) throw new Error(`Missing: ${p}`);
    inputs.push(p);
}

const args = [];
// Each input is a still image looped for PER_SLIDE_SEC
for (const f of inputs) {
    args.push('-loop', '1', '-t', String(PER_SLIDE_SEC), '-i', f);
}

// Build xfade filter chain
const filters = [];
let lastLabel = '[0:v]';
let cumulativeOffset = PER_SLIDE_SEC - FADE_SEC;
for (let i = 1; i < inputs.length; i++) {
    const out = `[v${i}]`;
    filters.push(
        `${lastLabel}[${i}:v]xfade=transition=fade:duration=${FADE_SEC}:offset=${cumulativeOffset.toFixed(2)}${out}`
    );
    lastLabel = out;
    cumulativeOffset += (PER_SLIDE_SEC - FADE_SEC);
}
// Add scale + format to ensure consistent output
filters.push(`${lastLabel}format=yuv420p[outv]`);

args.push(
    '-filter_complex', filters.join(';'),
    '-map', '[outv]',
    '-r', '30',
    '-c:v', 'libx264',
    '-preset', 'medium',
    '-crf', '20',
    '-movflags', '+faststart',
    '-y',
    OUT
);

console.log(`FFmpeg: ${ffmpegPath}`);
console.log(`Rendering ${inputs.length} slides → ${OUT}`);
console.log(`Per slide: ${PER_SLIDE_SEC}s, fade: ${FADE_SEC}s`);

const proc = spawn(ffmpegPath, args, { stdio: ['ignore', 'inherit', 'inherit'] });
proc.on('exit', (code) => {
    if (code === 0) {
        const stat = fs.statSync(OUT);
        const mb = (stat.size / 1024 / 1024).toFixed(2);
        console.log(`\nDone. ${OUT} (${mb} MB)`);
    } else {
        console.error('FFmpeg failed with exit code', code);
        process.exit(1);
    }
});
