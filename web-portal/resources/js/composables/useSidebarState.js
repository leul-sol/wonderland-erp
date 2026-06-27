const SCROLL_KEY = 'wh-sidebar-scroll';
const EXPANDED_KEY = 'wh-sidebar-expanded';

export function readSidebarScroll() {
    const raw = sessionStorage.getItem(SCROLL_KEY);

    return raw === null ? null : Number(raw);
}

export function writeSidebarScroll(scrollTop) {
    sessionStorage.setItem(SCROLL_KEY, String(Math.max(0, scrollTop)));
}

export function readExpandedKeys() {
    try {
        const raw = sessionStorage.getItem(EXPANDED_KEY);

        return raw ? new Set(JSON.parse(raw)) : new Set();
    } catch {
        return new Set();
    }
}

export function writeExpandedKeys(keys) {
    sessionStorage.setItem(EXPANDED_KEY, JSON.stringify([...keys]));
}
