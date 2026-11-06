// ---- Confirm dialogs without inline onsubmit (keeps the CSP strict) ----
document.addEventListener('submit', (e) => {
    const message = e.target?.dataset?.confirm;
    if (message && !window.confirm(message)) e.preventDefault();
});

// ---- Horizontal rows: arrow buttons ----
document.querySelectorAll('[data-row]').forEach((row) => {
    const track = row.querySelector('[data-track]');
    row.querySelectorAll('[data-dir]').forEach((btn) =>
        btn.addEventListener('click', () =>
            track.scrollBy({ left: Number(btn.dataset.dir) * track.clientWidth * 0.85, behavior: 'smooth' }),
        ),
    );
});

// ---- Trailer player: lazy source + release buffer when paused ----
//
// * Nothing is downloaded until the user presses play (no src, preload="none").
// * If the video stays paused for IDLE_MS we drop the source: the browser frees the decoded/buffered
//   data and stops any background download. Pressing play again re-attaches the file and resumes
//   from the same second (the file is cached + range-requested, so this is fast).
// * The video also pauses when the tab is hidden.
const IDLE_MS = 15000;

document.querySelectorAll('[data-player]').forEach((box) => {
    const video = box.querySelector('video[data-src]');
    const overlay = box.querySelector('[data-play]');
    const label = box.querySelector('[data-play-label]');
    if (!video || !overlay) return;

    let resumeAt = 0;
    let timer = null;

    const attach = () => {
        clearTimeout(timer);
        video.preload = 'auto';
        video.src = video.dataset.src;
        video.controls = true;
        overlay.classList.add('hidden');
        video.addEventListener(
            'loadedmetadata',
            () => {
                if (resumeAt > 0 && resumeAt < video.duration) video.currentTime = resumeAt;
                video.play().catch(() => {});
            },
            { once: true },
        );
    };

    const release = () => {
        resumeAt = video.currentTime;
        video.removeAttribute('src');
        video.controls = false;
        video.preload = 'none';
        video.load();
        if (label) label.textContent = 'Resume';
        overlay.classList.remove('hidden');
    };

    overlay.addEventListener('click', attach);
    video.addEventListener('play', () => clearTimeout(timer));
    video.addEventListener('pause', () => {
        if (video.ended || !video.currentSrc) return;
        clearTimeout(timer);
        timer = setTimeout(release, IDLE_MS);
    });
    video.addEventListener('ended', () => {
        resumeAt = 0;
    });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && !video.paused) video.pause();
    });
});
