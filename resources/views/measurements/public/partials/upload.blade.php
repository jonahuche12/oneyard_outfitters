<div class="mt-4 rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-700"
     data-upload
     data-upload-url="{{ route('measurements.public.upload', ['token' => $token, 'item' => $id]) }}"
     data-template-url="{{ route('measurements.public.template', ['token' => $token, 'item' => $id]) }}">
    <p class="font-medium text-slate-900">Prefer a spreadsheet?</p>
    <p class="mt-1">Download the template, fill it in, and upload it as .xlsx or .csv (max 2 MB). You can review and edit the entries afterwards.</p>
    <div class="mt-3 flex flex-wrap items-center gap-3">
        <a href="#" data-template-link class="rounded-lg border border-slate-300 px-3 py-2 font-semibold text-slate-800">Download {{ ucfirst($garment ?? "") }} template</a>
        <button type="button" data-upload-open class="rounded-lg bg-slate-900 px-3 py-2 font-semibold text-white">Upload file</button>
        <input type="file" accept=".xlsx,.csv" data-upload-file class="hidden">
    </div>
    <ul class="mt-3 list-disc pl-5 text-red-700" data-upload-errors hidden></ul>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" data-upload-modal hidden>
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-slate-900">Replace saved entries?</h3>
            <p class="mt-2 text-sm text-slate-600">Uploading this file will replace the entries you have already saved for this item.</p>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" data-upload-cancel class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Cancel</button>
                <button type="button" data-upload-confirm class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Replace</button>
            </div>
        </div>
    </div>
</div>
