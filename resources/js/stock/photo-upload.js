const MAX_FILE_BYTES = 4 * 1024 * 1024;
const ALLOWED_TYPES = new Set(['image/jpeg', 'image/png', 'image/webp']);

function initialiseStockPhotoUpload(input) {
    const preview = document.querySelector('[data-stock-photo-preview]');
    const count = document.querySelector('[data-stock-photo-count]');
    const errors = document.querySelector('[data-stock-photo-errors]');

    if (!preview || !count || !errors) return;

    const existingCount = Number(input.dataset.existingCount || 0);
    const maximum = Number(input.dataset.maxPhotos || 10);
    let selectedFiles = [];
    let objectUrls = [];

    function revokePreviews() {
        objectUrls.forEach((url) => URL.revokeObjectURL(url));
        objectUrls = [];
    }

    function syncInput() {
        const transfer = new DataTransfer();
        selectedFiles.forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
    }

    function render() {
        revokePreviews();
        preview.replaceChildren();

        count.textContent =
            `${existingCount + selectedFiles.length} / ${maximum} photos in total`;

        selectedFiles.forEach((file, index) => {
            const url = URL.createObjectURL(file);
            objectUrls.push(url);

            const card = document.createElement('div');
            card.className =
                'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm';

            const image = document.createElement('img');
            image.src = url;
            image.alt = `Preview of ${file.name}`;
            image.className = 'h-32 w-full bg-slate-50 object-cover';

            const details = document.createElement('div');
            details.className = 'p-2';

            const name = document.createElement('p');
            name.className = 'truncate text-xs font-medium text-slate-700';
            name.textContent = file.name;
            name.title = file.name;

            const size = document.createElement('p');
            size.className = 'mt-1 text-xs text-slate-500';
            size.textContent =
                `${(file.size / (1024 * 1024)).toFixed(2)} MB`;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className =
                'mt-2 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50';
            remove.textContent = 'Remove';
            remove.setAttribute('aria-label', `Remove ${file.name}`);

            remove.addEventListener('click', () => {
                selectedFiles.splice(index, 1);
                syncInput();
                errors.textContent = '';
                render();
            });

            details.append(name, size, remove);
            card.append(image, details);
            preview.append(card);
        });
    }

    input.addEventListener('change', () => {
        errors.textContent = '';

        const incoming = Array.from(input.files || []);
        // Clear the picker value so selecting the same file again can trigger change.
        input.value = '';

        const accepted = [];
        const messages = [];

        incoming.forEach((file) => {
            if (!ALLOWED_TYPES.has(file.type)) {
                messages.push(`${file.name}: choose JPG, PNG or WebP.`);
            } else if (file.size > MAX_FILE_BYTES) {
                messages.push(`${file.name}: exceeds the 4 MB limit.`);
            } else if (
                existingCount + selectedFiles.length + accepted.length >= maximum
            ) {
                messages.push(`${file.name}: maximum ${maximum} photos per item.`);
            } else {
                accepted.push(file);
            }
        });

        selectedFiles = selectedFiles.concat(accepted);
        syncInput();

        if (messages.length) {
            errors.textContent = messages.join(' ');
        }

        render();
    });

    window.addEventListener('beforeunload', revokePreviews, { once: true });
    render();
}

document.addEventListener('DOMContentLoaded', () => {
    document
        .querySelectorAll('[data-stock-photo-input]')
        .forEach(initialiseStockPhotoUpload);
});
