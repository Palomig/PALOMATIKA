<!doctype html>
<html lang="en">
<head>
    @include('partials.head-config')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
    <title>{{ $material->title }} | JARVIS</title>
    <style>
        .material-shell {
            background: radial-gradient(circle at top, #1d3557 0%, #0b1021 48%, #090c17 100%);
            min-height: 100vh;
        }

        .material-card {
            background: rgba(10, 14, 28, 0.82);
            border: 1px solid rgba(148, 163, 184, 0.15);
            backdrop-filter: blur(8px);
        }
    </style>
</head>
<body class="material-shell text-slate-100">
<div class="max-w-4xl mx-auto px-5 py-10 md:py-14">
    <a href="{{ url('/materials') }}" class="inline-flex items-center text-sm text-slate-300 hover:text-white mb-6">← Back to materials</a>

    <article class="material-card rounded-3xl p-6 md:p-10 shadow-2xl">
        <header class="mb-8 border-b border-slate-700/70 pb-5">
            <p class="text-cyan-300 uppercase tracking-[0.3em] text-xs mb-2">JARVIS Material</p>
            <h1 class="text-3xl md:text-4xl font-bold text-white leading-tight">{{ $material->title }}</h1>
            @if($material->excerpt)
                <p class="text-slate-300 mt-3 text-lg">{{ $material->excerpt }}</p>
            @endif
        </header>

        <div id="material-content" class="prose prose-invert max-w-none text-slate-100 leading-8 text-[17px] whitespace-pre-wrap break-words">{{ $material->source_content }}</div>
    </article>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof renderMathInElement !== 'function') {
            return;
        }

        const root = document.getElementById('material-content');
        if (!root) {
            return;
        }

        renderMathInElement(root, {
            throwOnError: false,
            delimiters: [
                {left: '$$', right: '$$', display: true},
                {left: '$', right: '$', display: false},
                {left: '\\(', right: '\\)', display: false},
                {left: '\\[', right: '\\]', display: true}
            ]
        });
    });
</script>
</body>
</html>
