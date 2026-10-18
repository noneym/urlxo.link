import './bootstrap';

// Scroll reveal for marketing sections. IntersectionObserver only, no scroll listeners.
function initReveal() {
    const els = document.querySelectorAll('.reveal:not(.is-in)');
    if (!('IntersectionObserver' in window)) {
        els.forEach((el) => el.classList.add('is-in'));
        return;
    }
    const io = new IntersectionObserver((entries) => {
        entries.forEach((e) => {
            if (e.isIntersecting) {
                e.target.classList.add('is-in');
                io.unobserve(e.target);
            }
        });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0.12 });
    els.forEach((el) => io.observe(el));
}

document.addEventListener('DOMContentLoaded', initReveal);
document.addEventListener('livewire:navigated', initReveal);

// Copy button used all over the app: <button data-copy="text">.
document.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('[data-copy]');
    if (!btn) return;
    ev.preventDefault();
    try {
        await navigator.clipboard.writeText(btn.dataset.copy);
    } catch {
        const ta = Object.assign(document.createElement('textarea'), { value: btn.dataset.copy });
        document.body.append(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
    }
    const label = btn.querySelector('[data-copy-label]');
    if (label) {
        const old = label.textContent;
        label.textContent = 'Copied';
        setTimeout(() => (label.textContent = old), 1400);
    }
});
