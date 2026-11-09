// ---- Confirm dialogs without inline onsubmit (keeps the CSP strict) ----
document.addEventListener('submit', (e) => {
    const message = e.target?.dataset?.confirm;
    if (message && !window.confirm(message)) e.preventDefault();
});

// ---- Add/edit form: one-click keyword suggestions append to the Keywords box ----
document.querySelectorAll('[data-tag-add]').forEach((btn) =>
    btn.addEventListener('click', () => {
        const input = document.getElementById('tags');
        if (!input) return;
        const tag = btn.dataset.tagAdd;
        const current = input.value.split(',').map((t) => t.trim()).filter(Boolean);
        if (!current.map((t) => t.toLowerCase()).includes(tag.toLowerCase())) current.push(tag);
        input.value = current.join(', ');
    }),
);

// ---- Horizontal rows: arrow buttons ----
document.querySelectorAll('[data-row]').forEach((row) => {
    const track = row.querySelector('[data-track]');
    row.querySelectorAll('[data-dir]').forEach((btn) =>
        btn.addEventListener('click', () =>
            track.scrollBy({ left: Number(btn.dataset.dir) * track.clientWidth * 0.85, behavior: 'smooth' }),
        ),
    );
});

// ---- Navbar dropdowns (<details>): one open at a time, close on outside click / Escape ----
const menus = [...document.querySelectorAll('details[data-menu]')];
menus.forEach((menu) =>
    menu.addEventListener('toggle', () => {
        if (menu.open) menus.filter((other) => other !== menu).forEach((other) => (other.open = false));
    }),
);
document.addEventListener('click', (e) => {
    menus.forEach((menu) => {
        if (menu.open && !menu.contains(e.target)) menu.open = false;
    });
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') menus.forEach((menu) => (menu.open = false));
});

// ---- Animated hero carousel ----
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

document.querySelectorAll('[data-hero]').forEach((hero) => {
    const slides = [...hero.querySelectorAll('[data-slide]')];
    const dots = [...hero.querySelectorAll('[data-dot]')];
    const bar = hero.querySelector('.hero-bar');
    if (slides.length < 2) return;

    let current = 0;

    const restartBar = () => {
        if (!bar || reduceMotion) return;
        bar.classList.remove('run');
        void bar.offsetWidth;
        bar.classList.add('run');
    };

    const show = (n) => {
        const next = (n + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            slide.classList.toggle('is-active', i === next);
            slide.classList.toggle('is-prev', i === current && i !== next);
            slide.setAttribute('aria-hidden', i === next ? 'false' : 'true');
        });
        dots.forEach((dot, i) => dot.setAttribute('aria-current', i === next ? 'true' : 'false'));
        current = next;
        restartBar();
    };

    hero.querySelector('[data-hero-prev]')?.addEventListener('click', () => show(current - 1));
    hero.querySelector('[data-hero-next]')?.addEventListener('click', () => show(current + 1));
    dots.forEach((dot, i) => dot.addEventListener('click', () => show(i)));
    bar?.addEventListener('animationend', () => show(current + 1));

    let offscreen = false;
    const syncPause = () => hero.classList.toggle('is-paused', document.hidden || offscreen);
    document.addEventListener('visibilitychange', syncPause);
    new IntersectionObserver(([entry]) => {
        offscreen = !entry.isIntersecting;
        syncPause();
    }).observe(hero);

    restartBar();
});

// ---- Rows that glide by themselves (add data-auto to a row) ----
document.querySelectorAll('[data-row][data-auto]').forEach((row) => {
    const track = row.querySelector('[data-track]');
    if (!track || reduceMotion) return;

    let hovering = false;
    let onscreen = false;
    let lastTouch = 0;
    const bump = () => (lastTouch = Date.now());

    row.addEventListener('pointerenter', () => (hovering = true));
    row.addEventListener('pointerleave', () => (hovering = false));
    ['pointerdown', 'keydown', 'focusin'].forEach((type) => row.addEventListener(type, bump));
    new IntersectionObserver(([entry]) => (onscreen = entry.isIntersecting)).observe(row);

    setInterval(() => {
        if (document.hidden || hovering || !onscreen || Date.now() - lastTouch < 8000) return;
        const max = track.scrollWidth - track.clientWidth;
        if (max <= 0) return;
        if (track.scrollLeft >= max - 4) track.scrollTo({ left: 0, behavior: 'smooth' });
        else track.scrollBy({ left: track.clientWidth * 0.6, behavior: 'smooth' });
    }, 3500);
});

// ---- Add/edit form: type, "has episodes" toggle and the episode rows ----
const episodesToggle = document.querySelector('[data-episodes-toggle]');
if (episodesToggle) {
    const panel = document.querySelector('[data-episodes-panel]');
    const streamField = document.querySelector('[data-stream-field]');
    const list = panel.querySelector('[data-episode-list]');
    const template = panel.querySelector('template[data-episode-template]');
    const bulkInput = panel.querySelector('[data-episode-bulk-count]');
    let counter = list.children.length;

    const sync = () => {
        panel.classList.toggle('hidden', !episodesToggle.checked);
        streamField?.classList.toggle('hidden', episodesToggle.checked);
    };

    const nextNumber = () =>
        [...list.querySelectorAll('[data-episode-number]')].reduce((max, input) => Math.max(max, Number(input.value) || 0), 0) + 1;

    const addRow = (number) => {
        const fragment = template.content.cloneNode(true);
        fragment.querySelectorAll('[name]').forEach((el) => (el.name = el.name.replace('__i__', String(counter))));
        fragment.querySelector('[data-episode-number]').value = number;
        list.appendChild(fragment);
        counter += 1;
        return list.lastElementChild;
    };

    episodesToggle.addEventListener('change', () => {
        sync();
        if (episodesToggle.checked && list.children.length === 0) addRow(1);
    });
    panel.querySelector('[data-episode-add]').addEventListener('click', () => addRow(nextNumber()).querySelector('input[type=url], input[type=text]').focus());
    panel.querySelector('[data-episode-bulk]').addEventListener('click', () => {
        const count = Math.min(200, Math.max(1, parseInt(bulkInput.value, 10) || 0));
        for (let i = 0; i < count; i += 1) addRow(nextNumber());
        list.lastElementChild?.querySelector('input[type=url], input[type=text]')?.scrollIntoView({ block: 'nearest' });
    });
    list.addEventListener('click', (e) => {
        const remove = e.target.closest('[data-episode-remove]');
        if (remove) remove.closest('[data-episode-row]').remove();
    });

    sync();
}

// ---- "Watch now" player mount function (NO sandbox: Streamtape loads safely) ----
function mountPlayer(container, src, title) {
    const frame = document.createElement('iframe');
    frame.src = src;
    frame.title = title || 'Video player';
    frame.className = 'aspect-video w-full rounded-lg bg-black ring-1 ring-white/10 md:aspect-auto md:h-[500px]';
    frame.allowFullscreen = true;
    frame.allow = 'autoplay; fullscreen; picture-in-picture; encrypted-media';
    frame.referrerPolicy = 'no-referrer';
    container.innerHTML = '';
    container.appendChild(frame);
}

// Click "Watch now" button
document.querySelectorAll('[data-embed-load]').forEach((button) => {
    button.addEventListener('click', () => {
        const container = button.closest('[data-embed]');
        if (container) {
            mountPlayer(container, button.dataset.embedSrc, button.dataset.embedTitle);
        }
    });
});

// Click Episode button -> loads in Watch now player
document.querySelectorAll('[data-episode-btn]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const src = btn.dataset.embedSrc;
        const title = btn.dataset.embedTitle;
        const container = document.querySelector('[data-embed]');
        const watchSection = document.getElementById('watch');
        const watchTitle = document.getElementById('watch-title');

        if (watchTitle && title) {
            watchTitle.textContent = title;
        }

        if (container && src) {
            mountPlayer(container, src, title);
            watchSection?.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// ---- Trailer player: lazy source + release buffer when paused ----
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