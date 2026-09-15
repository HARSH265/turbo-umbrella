/**
 * Tappable list rows on phones.
 *
 * Below the md breakpoint each data-table row renders as a card (see "Responsive Data
 * Table" in resources/css/app.css). A card that shows a whole record but only responds
 * to a small "View" link reads as broken on touch, so the whole card opens the record.
 *
 * Delegated from the document, so it covers every listing without any view needing to
 * opt in, including tables rendered after load.
 */

const MOBILE_BREAKPOINT = 768;

/** Elements that handle their own clicks and must not be hijacked. */
const INTERACTIVE = 'a, button, input, select, textarea, label, summary, [role="button"]';

function rowTarget(row) {
    // Prefer an explicit view/detail link over a destructive action such as Delete.
    const links = Array.from(row.querySelectorAll('a[href]')).filter(
        (a) => !a.hasAttribute('download') && !a.href.startsWith('javascript:')
    );

    if (links.length === 0) {
        return null;
    }

    const preferred = links.find((a) => /view|detail/i.test(a.textContent.trim()));

    return preferred || links[0];
}

document.addEventListener('click', (event) => {
    if (window.innerWidth >= MOBILE_BREAKPOINT) {
        return;
    }

    const row = event.target.closest('.data-table tbody tr');

    if (!row || event.target.closest(INTERACTIVE)) {
        return;
    }

    // Don't fight a text selection the user is making inside the card.
    if (window.getSelection()?.toString()) {
        return;
    }

    const target = rowTarget(row);

    if (target) {
        window.location.href = target.href;
    }
});
