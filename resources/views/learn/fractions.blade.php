<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Сложение дробей — PALOMATIKA</title>
    @include('partials.head-config')
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        .math-font { font-family: 'PT Serif', Georgia, serif; }
    </style>
</head>
<body class="min-h-screen" style="background: #060b14;">

<div class="max-w-2xl mx-auto px-4 py-10"
     x-data="{ step: 0 }">

    {{-- Header --}}
    <div class="text-center mb-10">
        <a href="javascript:history.back()"
           class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-400 transition mb-6">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            назад
        </a>

        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-medium mb-4">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
            </svg>
            Интерактивный урок
        </div>

        <h1 class="text-2xl font-bold text-slate-100 mb-1">Сложение дробей</h1>
        <p class="text-slate-500 text-sm">с разными знаменателями</p>

        {{-- Step progress --}}
        <div class="flex items-center justify-center gap-2 mt-5">
            @foreach(range(0, 5) as $i)
                <div class="rounded-full transition-all duration-300"
                     :class="step >= {{ $i }} ? 'w-2 h-2 bg-blue-400' : 'w-1.5 h-1.5 bg-slate-700'">
                </div>
            @endforeach
        </div>
    </div>

    {{-- ─── CARD 0: Problem (always visible) ──────────────────────────── --}}
    <div class="rounded-2xl border border-slate-700/60 overflow-hidden mb-3" style="background: #0f1a2a;">

        <div class="px-5 pt-5 pb-3 flex items-center gap-2">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest">Задача</span>
        </div>

        {{-- Main fraction SVG --}}
        <div class="mx-4 mb-4 rounded-xl flex items-center justify-center py-6" style="background: #070f1c;">
            <svg viewBox="0 0 380 120" class="w-full max-w-xs math-font" xmlns="http://www.w3.org/2000/svg">
                {{-- 1/4 --}}
                <text x="72" y="48" text-anchor="middle" font-size="38" fill="#e2e8f0">1</text>
                <line x1="44" y1="63" x2="100" y2="63" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round"/>
                <text x="72" y="100" text-anchor="middle" font-size="38" fill="#e2e8f0">4</text>

                {{-- + --}}
                <text x="130" y="77" text-anchor="middle" font-size="30" fill="#334155">+</text>

                {{-- 5/12 --}}
                <text x="196" y="48" text-anchor="middle" font-size="38" fill="#e2e8f0">5</text>
                <line x1="163" y1="63" x2="229" y2="63" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round"/>
                <text x="196" y="100" text-anchor="middle" font-size="38" fill="#e2e8f0">12</text>

                {{-- = --}}
                <text x="262" y="77" text-anchor="middle" font-size="30" fill="#334155">=</text>

                {{-- ? --}}
                <text x="313" y="82" text-anchor="middle" font-size="46" fill="#1e3a5f" font-style="italic">?</text>
            </svg>
        </div>

        <div class="px-5 pb-5">
            <p class="text-slate-400 text-sm mb-4 leading-relaxed">
                Знаменатели разные — <span class="text-slate-300 font-medium">4</span> и
                <span class="text-slate-300 font-medium">12</span>. Нельзя складывать напрямую.
                Нужно найти <strong class="text-slate-200">общий знаменатель</strong>.
            </p>

            <div x-show="step === 0">
                <button @click="step = 1"
                        class="flex items-center gap-2 w-full px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 group"
                        style="background: rgba(124,58,237,0.25); border: 1px solid rgba(139,92,246,0.3); color: #c4b5fd;">
                    <svg class="w-4 h-4 shrink-0" style="color: #facc15;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>Как найти общий знаменатель?</span>
                    <svg class="w-3.5 h-3.5 ml-auto opacity-50 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ─── CARD 1: Factor trees ───────────────────────────────────────── --}}
    <div x-show="step >= 1" x-cloak
         x-transition:enter="transition ease-out duration-350"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="rounded-2xl border border-slate-700/60 overflow-hidden mb-3"
         style="background: #0f1a2a;">

        <div class="px-5 py-4 flex items-center gap-3" style="border-bottom: 1px solid rgba(30,41,59,0.8);">
            <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold"
                  style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.35); color: #34d399;">1</span>
            <span class="text-slate-200 font-medium text-sm">Разложим знаменатели через НОД</span>
        </div>

        <div class="mx-4 my-4 rounded-xl flex items-center justify-center py-3" style="background: #070f1c;">
            <svg viewBox="0 0 480 195" class="w-full max-w-sm math-font" xmlns="http://www.w3.org/2000/svg">
                {{-- ─── Left tree: denominator 4 ─── --}}
                <text x="120" y="25" text-anchor="middle" font-size="12" fill="#475569">знаменатель</text>

                {{-- "4" root --}}
                <text x="120" y="67" text-anchor="middle" font-size="38" fill="#e2e8f0">4</text>

                {{-- Branch lines --}}
                <line x1="103" y1="74" x2="65" y2="118" stroke="#1e293b" stroke-width="1.8"/>
                <line x1="137" y1="74" x2="175" y2="118" stroke="#1e293b" stroke-width="1.8"/>

                {{-- Left child: 4 (НОД, green, crossed out) --}}
                <text x="57" y="148" text-anchor="middle" font-size="32" fill="#10b981">4</text>
                <line x1="35" y1="133" x2="79" y2="158" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round"/>

                {{-- Right child: 1 (unique factor, amber) --}}
                <text x="183" y="148" text-anchor="middle" font-size="32" fill="#f59e0b">1</text>

                {{-- Labels --}}
                <text x="57" y="173" text-anchor="middle" font-size="10.5" fill="#10b981">НОД</text>
                <text x="183" y="173" text-anchor="middle" font-size="10.5" fill="#f59e0b">доп. множ.</text>

                {{-- Divider --}}
                <line x1="240" y1="15" x2="240" y2="188" stroke="#0f172a" stroke-width="2"/>

                {{-- ─── Right tree: denominator 12 ─── --}}
                <text x="360" y="25" text-anchor="middle" font-size="12" fill="#475569">знаменатель</text>

                {{-- "12" root --}}
                <text x="360" y="67" text-anchor="middle" font-size="38" fill="#e2e8f0">12</text>

                {{-- Branch lines --}}
                <line x1="342" y1="74" x2="305" y2="118" stroke="#1e293b" stroke-width="1.8"/>
                <line x1="378" y1="74" x2="415" y2="118" stroke="#1e293b" stroke-width="1.8"/>

                {{-- Left child: 4 (НОД, green, crossed out) --}}
                <text x="297" y="148" text-anchor="middle" font-size="32" fill="#10b981">4</text>
                <line x1="275" y1="133" x2="319" y2="158" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round"/>

                {{-- Right child: 3 (unique factor, amber) --}}
                <text x="423" y="148" text-anchor="middle" font-size="32" fill="#f59e0b">3</text>

                {{-- Labels --}}
                <text x="297" y="173" text-anchor="middle" font-size="10.5" fill="#10b981">НОД</text>
                <text x="423" y="173" text-anchor="middle" font-size="10.5" fill="#f59e0b">доп. множ.</text>
            </svg>
        </div>

        <div class="px-5 pb-5 space-y-2.5">
            <div class="text-sm text-slate-300 leading-relaxed">
                Представим каждый знаменатель как <span class="text-green-400 font-semibold">НОД × уникальная часть</span>:
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="rounded-lg px-3 py-2 text-sm" style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2);">
                    <span class="text-slate-400">4 = </span>
                    <span class="text-green-400 font-bold">4</span>
                    <span class="text-slate-500"> × </span>
                    <span class="text-amber-400 font-bold">1</span>
                </div>
                <div class="rounded-lg px-3 py-2 text-sm" style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2);">
                    <span class="text-slate-400">12 = </span>
                    <span class="text-green-400 font-bold">4</span>
                    <span class="text-slate-500"> × </span>
                    <span class="text-amber-400 font-bold">3</span>
                </div>
            </div>
            <div class="rounded-lg px-3 py-2.5 text-sm" style="background: rgba(30,41,59,0.7); border: 1px solid #1e293b;">
                <span class="text-slate-400">НОД(4, 12) = </span>
                <span class="text-green-400 font-bold">4</span>
                <span class="text-slate-600 mx-2">→</span>
                <span class="text-slate-400">НОК = </span>
                <span class="text-green-400 font-bold">4</span>
                <span class="text-slate-500"> × </span>
                <span class="text-amber-400 font-bold">1</span>
                <span class="text-slate-500"> × </span>
                <span class="text-amber-400 font-bold">3</span>
                <span class="text-slate-500"> = </span>
                <span class="text-blue-400 font-bold text-base">12</span>
            </div>

            <div x-show="step === 1" class="pt-1">
                <button @click="step = 2"
                        class="flex items-center gap-2 w-full px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 group"
                        style="background: rgba(124,58,237,0.25); border: 1px solid rgba(139,92,246,0.3); color: #c4b5fd;">
                    <svg class="w-4 h-4 shrink-0" style="color: #facc15;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>Найти дополнительные множители</span>
                    <svg class="w-3.5 h-3.5 ml-auto opacity-50 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ─── CARD 2: Multipliers ─────────────────────────────────────────── --}}
    <div x-show="step >= 2" x-cloak
         x-transition:enter="transition ease-out duration-350"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="rounded-2xl border border-slate-700/60 overflow-hidden mb-3"
         style="background: #0f1a2a;">

        <div class="px-5 py-4 flex items-center gap-3" style="border-bottom: 1px solid rgba(30,41,59,0.8);">
            <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold"
                  style="background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.35); color: #fbbf24;">2</span>
            <span class="text-slate-200 font-medium text-sm">Дополнительные множители</span>
        </div>

        <div class="mx-4 my-4 rounded-xl flex items-center justify-center py-4" style="background: #070f1c;">
            <svg viewBox="0 0 460 145" class="w-full max-w-sm math-font" xmlns="http://www.w3.org/2000/svg">
                {{-- ×3 label above first fraction --}}
                <text x="90" y="22" text-anchor="middle" font-size="13" fill="#f59e0b" font-weight="bold">× 3</text>
                <line x1="90" y1="26" x2="90" y2="34" stroke="#f59e0b" stroke-width="1.5" stroke-linecap="round"/>
                <polygon points="86,34 94,34 90,40" fill="#f59e0b"/>

                {{-- Fraction 1·3 / 4·3 --}}
                <text x="62" y="62" text-anchor="middle" font-size="30" fill="#e2e8f0">1</text>
                <text x="80" y="62" text-anchor="middle" font-size="22" fill="#334155">·</text>
                <text x="98" y="62" text-anchor="middle" font-size="30" fill="#f59e0b">3</text>
                <line x1="38" y1="74" x2="122" y2="74" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round"/>
                <text x="62" y="106" text-anchor="middle" font-size="30" fill="#e2e8f0">4</text>
                <text x="80" y="106" text-anchor="middle" font-size="22" fill="#334155">·</text>
                <text x="98" y="106" text-anchor="middle" font-size="30" fill="#f59e0b">3</text>

                {{-- + --}}
                <text x="152" y="86" text-anchor="middle" font-size="28" fill="#334155">+</text>

                {{-- ×1 label above second fraction --}}
                <text x="250" y="22" text-anchor="middle" font-size="13" fill="#f59e0b" font-weight="bold">× 1</text>
                <line x1="250" y1="26" x2="250" y2="34" stroke="#f59e0b" stroke-width="1.5" stroke-linecap="round"/>
                <polygon points="246,34 254,34 250,40" fill="#f59e0b"/>

                {{-- Fraction 5·1 / 12·1 --}}
                <text x="210" y="62" text-anchor="middle" font-size="30" fill="#e2e8f0">5</text>
                <text x="232" y="62" text-anchor="middle" font-size="22" fill="#334155">·</text>
                <text x="252" y="62" text-anchor="middle" font-size="30" fill="#f59e0b">1</text>
                <line x1="183" y1="74" x2="280" y2="74" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round"/>
                <text x="218" y="106" text-anchor="middle" font-size="30" fill="#e2e8f0">12</text>
                <text x="246" y="106" text-anchor="middle" font-size="22" fill="#334155">·</text>
                <text x="264" y="106" text-anchor="middle" font-size="30" fill="#f59e0b">1</text>

                {{-- = --}}
                <text x="310" y="86" text-anchor="middle" font-size="28" fill="#334155">=</text>

                {{-- Result: ?/12 (hint) --}}
                <text x="362" y="62" text-anchor="middle" font-size="30" fill="#1e3a5f" font-style="italic">?</text>
                <line x1="338" y1="74" x2="386" y2="74" stroke="#dc2626" stroke-width="2" stroke-linecap="round"/>
                <text x="362" y="106" text-anchor="middle" font-size="30" fill="#60a5fa">12</text>
            </svg>
        </div>

        <div class="px-5 pb-5 space-y-2.5">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="rounded-lg px-3 py-2.5" style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.2);">
                    <div class="text-slate-400 text-xs mb-1">для дроби 1/4:</div>
                    <div class="text-slate-200">НОК ÷ 4 = 12 ÷ 4 = <span class="text-amber-400 font-bold text-base">3</span></div>
                </div>
                <div class="rounded-lg px-3 py-2.5" style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.2);">
                    <div class="text-slate-400 text-xs mb-1">для дроби 5/12:</div>
                    <div class="text-slate-200">НОК ÷ 12 = 12 ÷ 12 = <span class="text-amber-400 font-bold text-base">1</span></div>
                </div>
            </div>

            <div x-show="step === 2" class="pt-1">
                <button @click="step = 3"
                        class="flex items-center gap-2 w-full px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 group"
                        style="background: rgba(124,58,237,0.25); border: 1px solid rgba(139,92,246,0.3); color: #c4b5fd;">
                    <svg class="w-4 h-4 shrink-0" style="color: #facc15;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>Привести к общему знаменателю</span>
                    <svg class="w-3.5 h-3.5 ml-auto opacity-50 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ─── CARD 3: Common denominator ─────────────────────────────────── --}}
    <div x-show="step >= 3" x-cloak
         x-transition:enter="transition ease-out duration-350"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="rounded-2xl border border-slate-700/60 overflow-hidden mb-3"
         style="background: #0f1a2a;">

        <div class="px-5 py-4 flex items-center gap-3" style="border-bottom: 1px solid rgba(30,41,59,0.8);">
            <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold"
                  style="background: rgba(96,165,250,0.15); border: 1px solid rgba(96,165,250,0.35); color: #60a5fa;">3</span>
            <span class="text-slate-200 font-medium text-sm">Общий знаменатель — 12</span>
        </div>

        <div class="mx-4 my-4 rounded-xl flex items-center justify-center py-6" style="background: #070f1c;">
            <svg viewBox="0 0 400 120" class="w-full max-w-xs math-font" xmlns="http://www.w3.org/2000/svg">
                {{-- 3/12 (numerator is blue — converted) --}}
                <text x="62" y="45" text-anchor="middle" font-size="38" fill="#60a5fa">3</text>
                <line x1="36" y1="59" x2="88" y2="59" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round"/>
                <text x="62" y="97" text-anchor="middle" font-size="38" fill="#e2e8f0">12</text>

                {{-- + --}}
                <text x="120" y="73" text-anchor="middle" font-size="28" fill="#334155">+</text>

                {{-- 5/12 (unchanged) --}}
                <text x="177" y="45" text-anchor="middle" font-size="38" fill="#e2e8f0">5</text>
                <line x1="151" y1="59" x2="203" y2="59" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round"/>
                <text x="177" y="97" text-anchor="middle" font-size="38" fill="#e2e8f0">12</text>

                {{-- = --}}
                <text x="240" y="73" text-anchor="middle" font-size="28" fill="#334155">=</text>

                {{-- ?/12 --}}
                <text x="300" y="45" text-anchor="middle" font-size="38" fill="#1e3a5f" font-style="italic">?</text>
                <line x1="274" y1="59" x2="326" y2="59" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round"/>
                <text x="300" y="97" text-anchor="middle" font-size="38" fill="#e2e8f0">12</text>
            </svg>
        </div>

        <div class="px-5 pb-5 space-y-2.5">
            <p class="text-sm text-slate-300 leading-relaxed">
                <span class="text-blue-400 font-semibold">1/4 = 3/12</span> — умножили числитель и знаменатель на 3.<br>
                <span class="text-slate-300 font-semibold">5/12 = 5/12</span> — дробь не изменилась (множитель 1).
            </p>
            <p class="text-sm text-slate-400">Теперь оба знаменателя равны 12. Можно складывать!</p>

            <div x-show="step === 3" class="pt-1">
                <button @click="step = 4"
                        class="flex items-center gap-2 w-full px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 group"
                        style="background: rgba(124,58,237,0.25); border: 1px solid rgba(139,92,246,0.3); color: #c4b5fd;">
                    <svg class="w-4 h-4 shrink-0" style="color: #facc15;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>Сложить числители</span>
                    <svg class="w-3.5 h-3.5 ml-auto opacity-50 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ─── CARD 4: Addition ───────────────────────────────────────────── --}}
    <div x-show="step >= 4" x-cloak
         x-transition:enter="transition ease-out duration-350"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="rounded-2xl border border-slate-700/60 overflow-hidden mb-3"
         style="background: #0f1a2a;">

        <div class="px-5 py-4 flex items-center gap-3" style="border-bottom: 1px solid rgba(30,41,59,0.8);">
            <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold"
                  style="background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.35); color: #a78bfa;">4</span>
            <span class="text-slate-200 font-medium text-sm">Складываем числители</span>
        </div>

        <div class="mx-4 my-4 rounded-xl flex items-center justify-center py-6" style="background: #070f1c;">
            <svg viewBox="0 0 380 120" class="w-full max-w-xs math-font" xmlns="http://www.w3.org/2000/svg">
                {{-- (3+5)/12 --}}
                <text x="52" y="44" text-anchor="middle" font-size="30" fill="#60a5fa">3</text>
                <text x="74" y="44" text-anchor="middle" font-size="24" fill="#334155">+</text>
                <text x="96" y="44" text-anchor="middle" font-size="30" fill="#e2e8f0">5</text>
                <line x1="28" y1="57" x2="120" y2="57" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round"/>
                <text x="74" y="96" text-anchor="middle" font-size="38" fill="#e2e8f0">12</text>

                {{-- = --}}
                <text x="158" y="72" text-anchor="middle" font-size="28" fill="#334155">=</text>

                {{-- 8/12 --}}
                <text x="215" y="44" text-anchor="middle" font-size="42" fill="#e2e8f0">8</text>
                <line x1="188" y1="58" x2="242" y2="58" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round"/>
                <text x="215" y="100" text-anchor="middle" font-size="38" fill="#e2e8f0">12</text>

                {{-- Bracket label "3+5=8" --}}
                <text x="74" y="20" text-anchor="middle" font-size="12" fill="#60a5fa">3 + 5 = 8</text>
                <line x1="28" y1="23" x2="55" y2="30" stroke="#60a5fa" stroke-width="1" stroke-dasharray="2,2"/>
                <line x1="120" y1="23" x2="93" y2="30" stroke="#60a5fa" stroke-width="1" stroke-dasharray="2,2"/>
            </svg>
        </div>

        <div class="px-5 pb-5 space-y-2.5">
            <p class="text-sm text-slate-300 leading-relaxed">
                Складываем только числители: <span class="text-blue-400 font-semibold">3</span> + <span class="text-slate-300 font-semibold">5</span> = <strong class="text-white text-base">8</strong>.
                Знаменатель остаётся <strong class="text-white">12</strong>.
            </p>

            <div x-show="step === 4" class="pt-1">
                <button @click="step = 5"
                        class="flex items-center gap-2 w-full px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 group"
                        style="background: rgba(124,58,237,0.25); border: 1px solid rgba(139,92,246,0.3); color: #c4b5fd;">
                    <svg class="w-4 h-4 shrink-0" style="color: #facc15;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>Сократить дробь</span>
                    <svg class="w-3.5 h-3.5 ml-auto opacity-50 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ─── CARD 5: Simplify + Answer ──────────────────────────────────── --}}
    <div x-show="step >= 5" x-cloak
         x-transition:enter="transition ease-out duration-350"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="rounded-2xl overflow-hidden mb-4"
         style="background: #0a1f18; border: 1px solid rgba(16,185,129,0.3);">

        <div class="px-5 py-4 flex items-center gap-3" style="border-bottom: 1px solid rgba(16,185,129,0.15);">
            <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold"
                  style="background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.5); color: #34d399;">5</span>
            <span class="text-slate-200 font-medium text-sm">Сокращаем дробь</span>
        </div>

        <div class="mx-4 my-4 rounded-xl flex items-center justify-center py-6" style="background: #060f0a;">
            <svg viewBox="0 0 420 130" class="w-full max-w-xs math-font" xmlns="http://www.w3.org/2000/svg">
                {{-- 8/12 --}}
                <text x="50" y="45" text-anchor="middle" font-size="42" fill="#e2e8f0">8</text>
                <line x1="24" y1="60" x2="76" y2="60" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round"/>
                <text x="50" y="102" text-anchor="middle" font-size="42" fill="#e2e8f0">12</text>

                {{-- ÷4 labels --}}
                <text x="100" y="47" text-anchor="start" font-size="13" fill="#f59e0b">÷ 4</text>
                <text x="100" y="100" text-anchor="start" font-size="13" fill="#f59e0b">÷ 4</text>

                {{-- = --}}
                <text x="158" y="74" text-anchor="middle" font-size="28" fill="#334155">=</text>

                {{-- 2/3 (answer, emerald) --}}
                <text x="218" y="45" text-anchor="middle" font-size="50" fill="#4ade80">2</text>
                <line x1="188" y1="62" x2="248" y2="62" stroke="#10b981" stroke-width="3" stroke-linecap="round"/>
                <text x="218" y="108" text-anchor="middle" font-size="50" fill="#4ade80">3</text>

                {{-- Checkmark --}}
                <circle cx="290" cy="72" r="18" fill="none" stroke="#10b981" stroke-width="2" opacity="0.6"/>
                <path d="M282 72 L288 78 L298 65" stroke="#10b981" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <div class="px-5 pb-5 space-y-3">
            <p class="text-sm text-slate-300 leading-relaxed">
                НОД(8, 12) = 4. Делим числитель и знаменатель на 4:
                8 ÷ 4 = <strong class="text-white">2</strong>,
                12 ÷ 4 = <strong class="text-white">3</strong>.
            </p>

            <div class="flex items-center gap-3 px-4 py-3.5 rounded-xl"
                 style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.25);">
                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <span class="text-emerald-300 font-semibold">Ответ:</span>
                    <span class="text-white font-bold text-xl ml-2">2/3</span>
                </div>
                <div class="ml-auto text-slate-500 text-xs">
                    1/4 + 5/12 = <strong class="text-white">2/3</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Restart ─────────────────────────────────────────────────────── --}}
    <div x-show="step >= 5" x-cloak
         x-transition:enter="transition ease-out duration-350 delay-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="text-center mt-6 pb-8">
        <button @click="step = 0"
                class="px-5 py-2.5 rounded-xl text-slate-400 text-sm font-medium transition-all duration-200 hover:text-slate-300"
                style="background: rgba(30,41,59,0.5); border: 1px solid #1e293b;">
            ↺ Начать заново
        </button>
    </div>

</div>

</body>
</html>
