import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const watchTrigger = document.getElementById('watch-now-trigger');
    const playerWrapper = document.getElementById('watch-now-player-wrapper');

    if (watchTrigger && playerWrapper) {
        watchTrigger.addEventListener('click', (e) => {
            e.preventDefault();
            const embedSrc = playerWrapper.dataset.embedUrl;
            if (embedSrc) {
                playerWrapper.innerHTML = `
                    <div class="relative w-full aspect-video rounded-xl overflow-hidden shadow-2xl bg-black border border-neutral-800">
                        <iframe 
                            src="${embedSrc}" 
                            class="w-full h-full border-0 absolute inset-0" 
                            allowfullscreen 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            scrolling="no">
                        </iframe>
                    </div>
                `;
                watchTrigger.classList.add('hidden');
            }
        });
    }
});
