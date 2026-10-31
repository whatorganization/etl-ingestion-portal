<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Ingest workspace</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#101314] text-slate-100 antialiased">
        <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,rgba(44,122,123,0.22),transparent_34%),linear-gradient(135deg,#101314_0%,#151c1c_52%,#0d1010_100%)]">
            <header class="border-b border-white/10">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5 lg:px-8">
                    <a href="{{ route('ingestion.index') }}" class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-[#b9f36b] text-[#12201b] shadow-[0_0_30px_rgba(185,243,107,0.2)]">↗</span>
                        <span><span class="block font-semibold tracking-tight">weather warehouse</span><span class="block text-xs text-slate-400">ingestion workspace</span></span>
                    </a>
                    <span class="hidden rounded-full border border-emerald-300/25 bg-emerald-300/10 px-3 py-1 text-xs text-emerald-200 sm:block">schema-ready pipeline</span>
                </div>
            </header>
            <main class="mx-auto max-w-7xl px-5 py-10 lg:px-8 lg:py-16">
                <div class="mb-10 max-w-3xl"><p class="mb-3 text-xs font-semibold uppercase tracking-[0.28em] text-[#b9f36b]">source intake / step 01</p><h1 class="text-4xl font-semibold tracking-[-0.04em] text-white sm:text-6xl">Bring your data into focus.</h1><p class="mt-5 max-w-2xl text-base leading-7 text-slate-400">Preview flat files or connect to a read-only MySQL source before it moves toward the normalized weather warehouse.</p></div>
                <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                    <section class="rounded-2xl border border-white/10 bg-white/4.5 p-6 shadow-2xl shadow-black/20 sm:p-8">
                        <div class="mb-7 flex items-start justify-between gap-4"><div><span class="mb-3 inline-flex rounded-md bg-[#b9f36b]/10 px-2 py-1 text-xs font-semibold text-[#b9f36b]">01 / flat file</span><h2 class="text-2xl font-semibold text-white">Upload a source file</h2><p class="mt-2 text-sm text-slate-400">CSV or JSON, up to 10 MB. We will show the first five rows.</p></div><span class="text-2xl text-slate-500">↓</span></div>
                        <form id="file-form" action="{{ route('ingestion.file-preview') }}" enctype="multipart/form-data">@csrf<label id="drop-zone" for="file" class="group flex min-h-56 cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-slate-600 bg-black/10 px-6 text-center transition hover:border-[#b9f36b] hover:bg-[#b9f36b]/5"><span class="mb-4 grid size-12 place-items-center rounded-full bg-white/10 text-2xl text-[#b9f36b] transition group-hover:scale-110">↑</span><span id="file-label" class="font-medium text-slate-200">Drop your file here or <span class="text-[#b9f36b]">browse</span></span><span class="mt-2 text-xs text-slate-500">.csv or .json</span><input id="file" name="file" type="file" accept=".csv,.json,application/json,text/csv" class="sr-only"></label><button type="submit" class="mt-5 w-full rounded-lg bg-[#b9f36b] px-4 py-3 text-sm font-bold text-[#12201b] transition hover:bg-[#d2ff9a] disabled:cursor-not-allowed disabled:opacity-50">Preview file</button></form>
                    </section>
                    <section class="rounded-2xl border border-white/10 bg-[#e6edf0] p-6 text-[#172124] shadow-2xl shadow-black/20 sm:p-8">
                        <div class="mb-7 flex items-start justify-between gap-4"><div><span class="mb-3 inline-flex rounded-md bg-[#173b3d] px-2 py-1 text-xs font-semibold text-[#b9f36b]">02 / database</span><h2 class="text-2xl font-semibold">Connect a source</h2><p class="mt-2 text-sm text-slate-600">Credentials are used in memory for this request only.</p></div><span class="text-2xl text-slate-500">⌘</span></div>
                        <form id="database-form" action="{{ route('ingestion.database-preview') }}" class="grid gap-4 sm:grid-cols-2">@csrf<label class="text-sm font-semibold">Host<input name="host" required placeholder="db.example.com" class="form-input"></label><label class="text-sm font-semibold">Port<input name="port" type="number" min="1" max="65535" value="3306" required class="form-input"></label><label class="text-sm font-semibold">Database name<input name="database" required placeholder="weather_source" class="form-input"></label><label class="text-sm font-semibold">Username<input name="username" required placeholder="readonly_user" class="form-input"></label><label class="text-sm font-semibold sm:col-span-2">Password<input name="password" type="password" autocomplete="off" placeholder="••••••••" class="form-input"></label><label class="text-sm font-semibold sm:col-span-2">Custom SQL query<textarea name="query" required rows="3" placeholder="SELECT * FROM weather_readings LIMIT 100" class="form-input resize-y font-mono text-xs"></textarea></label><button type="submit" class="sm:col-span-2 rounded-lg bg-[#173b3d] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#245b5c] disabled:cursor-not-allowed disabled:opacity-50">Test connection & preview</button></form>
                    </section>
                </div>
                <section id="result" class="mt-6 hidden rounded-2xl border border-white/10 bg-black/20 p-6 sm:p-8" aria-live="polite"></section>
            </main>
        </div>
        <script>
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const result = document.getElementById('result');
            const dropZone = document.getElementById('drop-zone');
            const fileInput = document.getElementById('file');
            const fileLabel = document.getElementById('file-label');
            function showResult(payload, isError = false) { result.classList.remove('hidden'); result.innerHTML = isError ? `<div class="flex items-start gap-3 text-rose-200"><span class="text-xl">!</span><div><h3 class="font-semibold">Preview failed</h3><p class="mt-1 text-sm text-rose-200/70">${escapeHtml(payload)}</p></div></div>` : renderTable(payload); result.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
            function renderTable(payload) { const headers = payload.columns.map((column) => `<th class="whitespace-nowrap px-4 py-3 text-left font-semibold">${escapeHtml(column)}</th>`).join(''); const rows = payload.rows.map((row) => `<tr class="border-t border-white/10">${payload.columns.map((column) => `<td class="max-w-xs truncate px-4 py-3 text-slate-300">${escapeHtml(valueFor(row[column]))}</td>`).join('')}</tr>`).join(''); return `<div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#b9f36b]">preview ready</p><h3 class="mt-1 text-xl font-semibold text-white">${payload.total} row${payload.total === 1 ? '' : 's'} detected</h3></div><span class="text-xs text-slate-500">Showing ${payload.rows.length} rows</span></div><div class="mt-5 overflow-x-auto rounded-xl border border-white/10"><table class="min-w-full text-sm"><thead class="bg-white/[0.06] text-slate-400"><tr>${headers}</tr></thead><tbody>${rows || '<tr><td class="px-4 py-6 text-slate-500" colspan="99">No rows returned.</td></tr>'}</tbody></table></div>`; }
            function valueFor(value) { return value === null || value === undefined ? '—' : typeof value === 'object' ? JSON.stringify(value) : String(value); }
            function escapeHtml(value) { return String(value).replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character])); }
            async function submitForm(form, button) { button.disabled = true; button.textContent = 'Working...'; try { const response = await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: new FormData(form) }); const payload = await response.json(); if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'The request could not be completed.'); showResult(payload); } catch (error) { showResult(error.message, true); } finally { button.disabled = false; button.textContent = form.id === 'file-form' ? 'Preview file' : 'Test connection & preview'; } }
            fileInput.addEventListener('change', () => { fileLabel.textContent = fileInput.files[0]?.name || 'Drop your file here or browse'; });
            ['dragenter', 'dragover'].forEach((eventName) => dropZone.addEventListener(eventName, (event) => { event.preventDefault(); dropZone.classList.add('border-[#b9f36b]', 'bg-[#b9f36b]/10'); }));
            ['dragleave', 'drop'].forEach((eventName) => dropZone.addEventListener(eventName, (event) => { event.preventDefault(); dropZone.classList.remove('border-[#b9f36b]', 'bg-[#b9f36b]/10'); }));
            dropZone.addEventListener('drop', (event) => { fileInput.files = event.dataTransfer.files; fileInput.dispatchEvent(new Event('change')); });
            document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', (event) => { event.preventDefault(); submitForm(form, form.querySelector('button[type="submit"]')); }));
        </script>
    </body>
</html>