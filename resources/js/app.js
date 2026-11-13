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

    // Phones: swipe left / right to change slide
    let swipeX = null;
    hero.addEventListener('touchstart', (e) => { swipeX = e.touches[0].clientX; }, { passive: true });
    hero.addEventListener('touchend', (e) => {
        if (swipeX === null) return;
        const dx = e.changedTouches[0].clientX - swipeX;
        swipeX = null;
        if (Math.abs(dx) > 40) show(current + (dx < 0 ? 1 : -1));
    }, { passive: true });
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

// ---- Add/edit form: cast rows ----
document.querySelectorAll('[data-cast-panel]').forEach((panel) => {
    const list = panel.querySelector('[data-cast-list]');
    const template = panel.querySelector('template[data-cast-template]');
    let next = list.children.length + 100; // unique row numbers, so every photo input matches its own row

    panel.querySelector('[data-cast-add]').addEventListener('click', () => {
        if (list.children.length >= 24) return;
        const html = template.innerHTML.replaceAll('__i__', String(next));
        next += 1;
        list.insertAdjacentHTML('beforeend', html);
        list.lastElementChild.querySelector('input[type=text]').focus();
    });
    list.addEventListener('click', (e) => {
        const remove = e.target.closest('[data-cast-remove]');
        if (remove) remove.closest('[data-cast-row]').remove();
    });
});

// ---- Gallery viewer: click a picture to see it large; arrows / keyboard to move ----
document.querySelectorAll('[data-lightbox]').forEach((dialog) => {
    const buttons = [...document.querySelectorAll('[data-lightbox-open]')];
    const img = dialog.querySelector('[data-lightbox-img]');
    const count = dialog.querySelector('[data-lightbox-count]');
    if (!buttons.length || typeof dialog.showModal !== 'function') return;
    let index = 0;

    const show = (i) => {
        index = (i + buttons.length) % buttons.length;
        img.src = buttons[index].dataset.full;
        count.textContent = `${index + 1} / ${buttons.length}`;
    };
    buttons.forEach((btn, i) =>
        btn.addEventListener('click', () => {
            show(i);
            dialog.showModal();
        }),
    );
    dialog.querySelector('[data-lightbox-prev]').addEventListener('click', () => show(index - 1));
    dialog.querySelector('[data-lightbox-next]').addEventListener('click', () => show(index + 1));
    dialog.querySelector('[data-lightbox-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) dialog.close();
    });
    dialog.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') show(index - 1);
        if (e.key === 'ArrowRight') show(index + 1);
    });
    dialog.addEventListener('close', () => img.removeAttribute('src'));
});

// ---- "Watch now" player mount function (NO sandbox: ensures Streamtape works) ----
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

// Click Watch Now button
document.querySelectorAll('[data-embed-load]').forEach((button) => {
    button.addEventListener('click', () => {
        const container = button.closest('[data-embed]');
        if (container) {
            mountPlayer(container, button.dataset.embedSrc, button.dataset.embedTitle);
        }
    });
});

// Click Episode button -> Load episode in Watch player
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

// ---- Trailer pop-up: the video file is only requested when the dialog opens ----
document.querySelectorAll('[data-trailer-dialog]').forEach((dialog) => {
    const video = dialog.querySelector('video[data-src]');
    const open = document.querySelector('[data-trailer-open]');
    if (!video || !open || typeof dialog.showModal !== 'function') return;

    open.addEventListener('click', () => {
        dialog.showModal();
        if (!video.getAttribute('src')) video.src = video.dataset.src;
        video.play().catch(() => {});
    });

    // Release the connection and memory when it is closed (Esc, X button or a click outside the box).
    dialog.addEventListener('close', () => {
        video.pause();
        video.removeAttribute('src');
        video.load();
        open.focus();
    });
    dialog.querySelector('[data-trailer-close]')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) dialog.close();
    });
});

// ---- Quick trailer from a poster card: one shared dialog, the file is only requested when it opens ----
(() => {
    const dialog = document.querySelector('[data-card-trailer-dialog]');
    const video = dialog?.querySelector('video');
    const title = dialog?.querySelector('[data-card-trailer-title]');
    if (!dialog || !video || typeof dialog.showModal !== 'function') return;

    let opener = null;
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-card-trailer]');
        if (!btn) return;
        opener = btn;
        title.textContent = btn.dataset.title + ' \u00b7 Trailer';
        video.src = btn.dataset.src;
        dialog.showModal();
        video.play().catch(() => {});
    });
    dialog.addEventListener('close', () => {
        video.pause();
        video.removeAttribute('src');
        video.load();
        opener?.focus();
    });
    dialog.querySelector('[data-card-trailer-close]')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) dialog.close();
    });
})();

// ---- Live search: matching titles appear under the search box while you type ----
document.querySelectorAll('[data-live-search]').forEach((form) => {
    const input = form.querySelector('input[name="q"]');
    const box = form.querySelector('[data-live-results]');
    if (!input || !box) return;

    let timer = null;
    let controller = null;
    let active = -1;

    const hide = () => {
        box.hidden = true;
        box.replaceChildren();
        active = -1;
        input.setAttribute('aria-expanded', 'false');
    };

    const links = () => [...box.querySelectorAll('a[data-live-item]')];

    const highlight = (i) => {
        const items = links();
        if (!items.length) return;
        active = (i + items.length) % items.length;
        items.forEach((a, n) => a.classList.toggle('bg-orange-500/15', n === active));
        items[active].scrollIntoView({ block: 'nearest' });
    };

    const el = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text) node.textContent = text;
        return node;
    };

    // Spinner shown from the first keystroke until the results arrive.
    const showLoading = () => {
        const row = el('div', 'flex items-center justify-center gap-3 px-4 py-6 text-sm font-semibold text-sky-400');
        row.setAttribute('role', 'status');
        row.append(el('span', 'h-5 w-5 animate-spin rounded-full border-2 border-sky-400 border-t-transparent'), el('span', '', '\u0DC3\u0DD9\u0DC0\u0DD4\u0DB8\u0DCA \u0D9A\u0DBB\u0DB8\u0DD2\u0DB1\u0DCA...'));
        box.replaceChildren(row);
        active = -1;
        box.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    };

    const render = (data, query) => {
        box.replaceChildren();
        active = -1;

        if (!data.items.length) {
            box.append(el('p', 'px-4 py-5 text-center text-sm text-slate-400', `No titles match "${query}"`));
        } else {
            data.items.forEach((item) => {
                const a = el('a', 'flex items-center gap-3 border-b border-white/5 px-3 py-2.5 transition hover:bg-orange-500/15');
                a.href = item.url;
                a.setAttribute('role', 'option');
                a.dataset.liveItem = '';

                const thumb = el('span', 'h-16 w-11 shrink-0 overflow-hidden rounded-md bg-zinc-800 ring-1 ring-white/10');
                if (item.poster) {
                    const img = el('img', 'h-full w-full object-cover');
                    img.src = item.poster;
                    img.alt = '';
                    img.width = 44;
                    img.height = 64;
                    img.loading = 'lazy';
                    thumb.append(img);
                }

                const text = el('span', 'min-w-0 flex-1');
                text.append(el('span', 'block truncate text-sm font-semibold text-white', item.year ? `${item.title} (${item.year})` : item.title));
                if (item.meta) text.append(el('span', 'mt-0.5 block truncate text-xs font-medium text-orange-300', item.meta));

                a.append(thumb, text);
                box.append(a);
            });

            const all = el('a', 'btn-aura !m-3 !rounded-lg !py-2.5 !text-sm', `View all ${data.total} result${data.total === 1 ? '' : 's'}`);
            all.href = `${form.action}?q=${encodeURIComponent(query)}`;
            all.dataset.liveItem = '';
            box.append(all);
        }

        box.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    };

    const search = async () => {
        const query = input.value.trim();
        if (query.length < 2) return hide();

        controller?.abort();
        controller = new AbortController();
        showLoading();
        try {
            const res = await fetch(`${form.dataset.liveUrl}?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });
            if (!res.ok) return hide();
            if (input.value.trim() === query) render(await res.json(), query);
        } catch (err) {
            if (err.name !== 'AbortError') hide();
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        if (input.value.trim().length >= 2) showLoading();
        timer = setTimeout(search, 200);
    });
    input.addEventListener('focus', () => {
        if (input.value.trim().length >= 2 && box.hidden) search();
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') return hide();
        if (box.hidden) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); highlight(active + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(active - 1); }
        else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); links()[active].click(); }
    });
    document.addEventListener('click', (e) => {
        if (!form.contains(e.target)) hide();
    });
});
