<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Вынести минус за скобку — PALOMATIKA</title>
    @include('partials.head-config')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
            onload="renderMathInElement(document.body, {
                delimiters: [
                    {left: '$$', right: '$$', display: true},
                    {left: '$', right: '$', display: false}
                ],
                throwOnError: false
            })"></script>
</head>
<body class="min-h-screen" style="background: #060b14;">

<div class="max-w-5xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="text-center mb-10">
        <a href="javascript:history.back()"
           class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-400 transition mb-5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            назад
        </a>

        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full mb-4"
             style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #fca5a5;">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"/>
            </svg>
            <span class="text-xs font-medium">Тренировка</span>
        </div>

        <h1 class="text-3xl font-bold text-slate-100 mb-2">Вынести минус за скобку</h1>
        <p class="text-slate-500 text-sm max-w-md mx-auto">
            Запиши каждое выражение в виде $-(\ \ldots\ )$, вынеся знак «минус» за скобку
        </p>
    </div>

    {{-- Правило --}}
    <div class="rounded-xl p-5 mb-8"
         style="background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.18);">
        <p class="text-sm font-semibold text-red-400 mb-2 uppercase tracking-wider">Правило</p>
        <p class="text-slate-300 text-sm leading-relaxed">
            Чтобы вынести минус за скобку, меняй знак <em>каждого</em> слагаемого на противоположный:
        </p>
        <div class="mt-3 text-slate-200 text-center text-base">
            $-a + b - c = -(a - b + c)$
        </div>
    </div>

    {{-- Сетка заданий --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4">

        @php
        $examples = [
            ['n' =>  1, 'expr' => '-2a - 6b',                   'blank' => '-2a - 6b = -(\ \ldots\ )'],
            ['n' =>  2, 'expr' => '-3x + 9',                    'blank' => '-3x + 9 = -(\ \ldots\ )'],
            ['n' =>  3, 'expr' => '-5m - 15n',                  'blank' => '-5m - 15n = -(\ \ldots\ )'],
            ['n' =>  4, 'expr' => '-x + y',                     'blank' => '-x + y = -(\ \ldots\ )'],
            ['n' =>  5, 'expr' => '-4a + 8b - 12c',             'blank' => '-4a + 8b - 12c = -(\ \ldots\ )'],
            ['n' =>  6, 'expr' => '-7x - 14y + 21z',            'blank' => '-7x - 14y + 21z = -(\ \ldots\ )'],
            ['n' =>  7, 'expr' => '-2a^2 - 6a + 4',             'blank' => '-2a^2 - 6a + 4 = -(\ \ldots\ )'],
            ['n' =>  8, 'expr' => '-9x + 3y - 6',               'blank' => '-9x + 3y - 6 = -(\ \ldots\ )'],
            ['n' =>  9, 'expr' => '-m - n',                     'blank' => '-m - n = -(\ \ldots\ )'],
            ['n' => 10, 'expr' => '-6p + 12q - 3r',             'blank' => '-6p + 12q - 3r = -(\ \ldots\ )'],
            ['n' => 11, 'expr' => '-5x^2 + 10x - 15',           'blank' => '-5x^2 + 10x - 15 = -(\ \ldots\ )'],
            ['n' => 12, 'expr' => '-a - b - c',                 'blank' => '-a - b - c = -(\ \ldots\ )'],
            ['n' => 13, 'expr' => '-4k - 8',                    'blank' => '-4k - 8 = -(\ \ldots\ )'],
            ['n' => 14, 'expr' => '-3x + 6y',                   'blank' => '-3x + 6y = -(\ \ldots\ )'],
            ['n' => 15, 'expr' => '-2a^3 - 4a^2 + 8a',         'blank' => '-2a^3 - 4a^2 + 8a = -(\ \ldots\ )'],
            ['n' => 16, 'expr' => '-10m + 5n - 20',             'blank' => '-10m + 5n - 20 = -(\ \ldots\ )'],
            ['n' => 17, 'expr' => '-p + q + r',                 'blank' => '-p + q + r = -(\ \ldots\ )'],
            ['n' => 18, 'expr' => '-8x - 4y + 16z',             'blank' => '-8x - 4y + 16z = -(\ \ldots\ )'],
            ['n' => 19, 'expr' => '-a^2 + 2ab - b^2',          'blank' => '-a^2 + 2ab - b^2 = -(\ \ldots\ )'],
            ['n' => 20, 'expr' => '-6c + 9d - 3e + 12f',       'blank' => '-6c + 9d - 3e + 12f = -(\ \ldots\ )'],
        ];
        @endphp

        @foreach($examples as $ex)
        <div class="rounded-xl px-6 py-5 flex items-center gap-4"
             style="background: rgba(15,23,42,0.7); border: 1px solid rgba(30,41,59,0.8);">
            <span class="text-2xl font-bold tabular-nums flex-shrink-0 w-8 text-right"
                  style="color: rgba(239,68,68,0.4);">{{ $ex['n'] }}</span>
            <div class="text-slate-200 text-lg leading-snug min-w-0">
                ${{ $ex['blank'] }}$
            </div>
        </div>
        @endforeach

    </div>

    <p class="text-center text-slate-600 text-xs mt-10">PALOMATIKA · Алгебра</p>
</div>

</body>
</html>
