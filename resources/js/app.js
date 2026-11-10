// JS volontairement minimal (connexions lentes) : la carte et la recherche FAQ sont chargées à la demande.

// Carte OpenStreetMap (Leaflet) : module chargé uniquement si la page contient #map.
const mapEl = document.getElementById('map');
if (mapEl) {
    import('./map.js').then((m) => m.initMap(mapEl));
}

// Recherche instantanée dans la FAQ (sans requête serveur).
const faqSearch = document.getElementById('faq-search');
if (faqSearch) {
    const items = Array.from(document.querySelectorAll('[data-faq-item]'));
    const empty = document.getElementById('faq-empty');
    const normalize = (s) => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

    faqSearch.addEventListener('input', () => {
        const q = normalize(faqSearch.value.trim());
        let visible = 0;
        items.forEach((el) => {
            const match = q === '' || normalize(el.textContent).includes(q);
            el.hidden = !match;
            if (match) visible++;
            if (q !== '' && match) el.open = true;
            if (q === '') el.open = false;
        });
        if (empty) empty.hidden = visible !== 0;
    });
}
