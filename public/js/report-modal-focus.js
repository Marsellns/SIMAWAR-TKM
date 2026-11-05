(() => {
    const openers = new WeakMap();

    document.addEventListener('show.bs.modal', (event) => {
        const relatedTarget = event.relatedTarget;
        const activeElement = document.activeElement;
        const trigger = relatedTarget instanceof HTMLElement && relatedTarget.isConnected
            ? relatedTarget
            : (activeElement instanceof HTMLElement
                && activeElement !== document.body
                && !event.target.contains(activeElement) ? activeElement : null);

        openers.set(event.target, {
            trigger,
            table: trigger?.closest('table'),
        });
    });

    document.addEventListener('hide.bs.modal', (event) => {
        const modal = event.target;
        const opener = openers.get(modal);
        if (!opener || !modal.contains(document.activeElement)) return;

        // Bootstrap releases its focus trap after hide.bs.modal finishes.
        // Restore focus before the closing modal receives aria-hidden.
        queueMicrotask(() => {
            if (!modal.contains(document.activeElement)) return;

            const target = opener.trigger?.isConnected
                ? opener.trigger
                : (opener.table?.isConnected ? opener.table : document.body);

            const needsTabindex = !target.hasAttribute('tabindex');
            if (needsTabindex) target.setAttribute('tabindex', '-1');
            target.focus({ preventScroll: true });

            if (needsTabindex) {
                if (document.activeElement === target) {
                    target.addEventListener('blur', () => target.removeAttribute('tabindex'), { once: true });
                } else {
                    target.removeAttribute('tabindex');
                }
            }
        });
    });

    document.addEventListener('hidden.bs.modal', (event) => openers.delete(event.target));
})();
