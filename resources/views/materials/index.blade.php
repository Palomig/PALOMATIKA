<!doctype html>
<html lang="en">
<head>
    @include('partials.head-config')
    <title>JARVIS Materials</title>
    <style>
        .material-bg {
            background: radial-gradient(circle at top, #243b6b 0%, #101323 50%, #0b0d17 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="material-bg text-slate-100">
<div class="max-w-4xl mx-auto px-6 py-12">
    <header class="mb-8">
        <p class="text-cyan-300 uppercase tracking-[0.3em] text-xs mb-2">JARVIS</p>
        <h1 class="text-4xl md:text-5xl font-bold leading-tight">Learning Materials</h1>
        <p class="text-slate-300 mt-3 max-w-2xl">Published notes with clean math rendering and focused reading layout.</p>
    </header>

    <div class="grid gap-4">
        @forelse($materials as $material)
            <a href="{{ url('/materials/' . $material->slug) }}" class="block rounded-2xl border border-white/10 bg-white/5 hover:bg-white/10 transition p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-white">{{ $material->title }}</h2>
                        @if($material->excerpt)
                            <p class="text-slate-300 mt-2">{{ $material->excerpt }}</p>
                        @endif
                    </div>
                    <span class="text-xs px-2.5 py-1 rounded-full bg-cyan-500/20 text-cyan-200 border border-cyan-300/20">Published</span>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-white/10 bg-white/5 p-8 text-slate-300">No published materials yet.</div>
        @endforelse
    </div>
</div>
</body>
</html>
