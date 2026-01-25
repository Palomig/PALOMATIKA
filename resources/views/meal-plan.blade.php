<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['meta']['title'] }} — SmartCart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: #334155;
            --accent: #f97316;
            --accent-secondary: #22c55e;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
        }
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        .card {
            background: var(--bg-secondary);
            border-radius: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        }
        .section-nav {
            scrollbar-width: none;
        }
        .section-nav::-webkit-scrollbar {
            display: none;
        }
        .section-btn {
            background: var(--bg-secondary);
            transition: all 0.2s;
        }
        .section-btn:hover, .section-btn.active {
            background: var(--accent);
            color: white;
        }
        .recipe-card {
            background: var(--bg-card);
            border-radius: 12px;
        }
        .tip-card {
            background: linear-gradient(135deg, #1e3a5f 0%, #1e293b 100%);
            border-left: 4px solid var(--accent);
        }
        .warning-card {
            background: linear-gradient(135deg, #4a1d1d 0%, #1e293b 100%);
            border-left: 4px solid #ef4444;
        }
        .chef-tip {
            background: linear-gradient(135deg, #1a3d2e 0%, #1e293b 100%);
            border-left: 4px solid #22c55e;
        }
        .ingredient-tag {
            background: rgba(249, 115, 22, 0.2);
            color: #fb923c;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 13px;
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .ingredient-tag.has-price {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }
        .ingredient-tag.not-found {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px dashed #f87171;
        }
        .ingredient-tag .price-badge {
            background: rgba(0,0,0,0.3);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .ingredient-tag.loading::after {
            content: '';
            width: 12px;
            height: 12px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .missing-panel {
            position: fixed;
            bottom: 80px;
            right: 24px;
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 16px;
            max-width: 320px;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
            z-index: 100;
            display: none;
        }
        .missing-panel.show {
            display: block;
        }
        .missing-toggle {
            position: fixed;
            bottom: 80px;
            right: 24px;
            z-index: 101;
        }
        .stats-bar {
            background: var(--bg-secondary);
            border-radius: 12px;
            padding: 12px 16px;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: center;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }
        .copy-btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .copy-btn:hover {
            background: #ea580c;
        }
        .copy-btn.copied {
            background: #22c55e;
        }
        .step-number {
            background: var(--accent);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            flex-shrink: 0;
        }
        .mealprep-badge {
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .equipment-badge {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        .storage-zone {
            border-radius: 12px;
            padding: 12px 16px;
        }
        .pantry-item {
            background: var(--bg-card);
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 14px;
        }
        details summary {
            cursor: pointer;
            list-style: none;
        }
        details summary::-webkit-details-marker {
            display: none;
        }
        details[open] summary .chevron {
            transform: rotate(180deg);
        }
        .chevron {
            transition: transform 0.2s;
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Header -->
    <header class="sticky top-0 z-50 backdrop-blur-lg bg-slate-900/80 border-b border-slate-700">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-3">
                        <span class="text-3xl">🍽️</span>
                        {{ $data['meta']['title'] }}
                    </h1>
                    <p class="text-slate-400 text-sm mt-1">{{ $data['meta']['subtitle'] }} • {{ $data['meta']['budget'] }}</p>
                </div>
                <div class="flex gap-2">
                    @foreach($data['meta']['equipment'] as $eq)
                        <span class="equipment-badge">{{ $eq }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </header>

    <!-- Section Navigation -->
    <nav class="sticky top-[73px] z-40 bg-slate-900/95 backdrop-blur border-b border-slate-800">
        <div class="max-w-6xl mx-auto px-4">
            <div class="section-nav flex gap-2 py-3 overflow-x-auto">
                @foreach($data['sections'] as $key => $section)
                    <button onclick="showSection('{{ $key }}')"
                            class="section-btn px-4 py-2 rounded-full whitespace-nowrap text-sm font-medium"
                            data-section="{{ $key }}">
                        {{ $section['icon'] }} {{ $section['title'] }}
                    </button>
                @endforeach
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 py-6">
        @foreach($data['sections'] as $sectionKey => $section)
            <section id="section-{{ $sectionKey }}" class="section-content mb-12" style="{{ $loop->first ? '' : 'display:none' }}">
                <!-- Section Header -->
                <div class="mb-6">
                    <h2 class="text-3xl font-bold flex items-center gap-3">
                        <span class="text-4xl">{{ $section['icon'] }}</span>
                        {{ $section['title'] }}
                    </h2>
                    <p class="text-slate-400 mt-2">{{ $section['description'] }}</p>
                </div>

                <!-- Tips -->
                @if(isset($section['tips']))
                    <div class="mb-8 space-y-4">
                        @foreach($section['tips'] as $tipKey => $tip)
                            <details class="tip-card p-4 rounded-xl">
                                <summary class="flex items-center justify-between font-semibold">
                                    <span>💡 {{ $tip['title'] }}</span>
                                    <svg class="chevron w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </summary>
                                <div class="mt-4">
                                    @if(isset($tip['description']))
                                        <p class="text-slate-300 mb-3">{{ $tip['description'] }}</p>
                                    @endif
                                    @if(isset($tip['data']))
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <tbody>
                                                    @foreach($tip['data'] as $row)
                                                        <tr class="border-b border-slate-700">
                                                            @foreach($row as $key => $val)
                                                                <td class="py-2 px-3 {{ $loop->first ? 'font-medium text-orange-400' : 'text-slate-300' }}">{{ $val }}</td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                    @if(isset($tip['rules']))
                                        <ul class="space-y-2">
                                            @foreach($tip['rules'] as $rule)
                                                <li class="flex items-start gap-2 text-slate-300">
                                                    <span class="text-green-500 mt-1">✓</span>
                                                    {{ $rule }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if(isset($tip['layers']))
                                        <div class="grid gap-2">
                                            @foreach($tip['layers'] as $layer)
                                                <div class="flex items-center gap-3 bg-slate-800/50 rounded-lg p-3">
                                                    <span class="font-medium text-orange-400 w-24">{{ $layer['layer'] }}</span>
                                                    <span class="text-slate-300">{{ implode(', ', $layer['options']) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </details>
                        @endforeach
                    </div>
                @endif

                <!-- Recipes -->
                @if(isset($section['recipes']))
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach($section['recipes'] as $recipe)
                            <article class="recipe-card p-5">
                                <!-- Recipe Header -->
                                <div class="flex items-start justify-between mb-4">
                                    <div>
                                        <h3 class="text-xl font-bold flex items-center gap-2">
                                            <span class="text-2xl">{{ $recipe['emoji'] }}</span>
                                            {{ $recipe['name'] }}
                                        </h3>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <span class="text-slate-400 text-sm">⏱️ {{ $recipe['time'] }}</span>
                                            <span class="text-green-400 text-sm">💰 {{ $recipe['cost'] }}</span>
                                            @if(isset($recipe['storage']))
                                                <span class="text-blue-400 text-sm">🧊 {{ $recipe['storage'] }}</span>
                                            @endif
                                            @if(isset($recipe['servings']))
                                                <span class="text-slate-400 text-sm">👥 {{ $recipe['servings'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        @if(isset($recipe['mealprep']) && $recipe['mealprep'])
                                            <span class="mealprep-badge">MEAL PREP</span>
                                        @endif
                                        @if(isset($recipe['equipment']) && $recipe['equipment'])
                                            <span class="equipment-badge">{{ $recipe['equipment'] }}</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Ingredients -->
                                <div class="mb-4">
                                    <h4 class="text-sm font-semibold text-slate-400 mb-2">Ингредиенты:</h4>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($recipe['ingredients'] as $ing)
                                            <span class="ingredient-tag loading" data-ingredient="{{ $ing['item'] }}">{{ $ing['item'] }} — {{ $ing['amount'] }}</span>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Steps -->
                                @if(isset($recipe['steps']) && count($recipe['steps']) > 0)
                                    <div class="mb-4">
                                        <h4 class="text-sm font-semibold text-slate-400 mb-2">Приготовление:</h4>
                                        <ol class="space-y-2">
                                            @foreach($recipe['steps'] as $index => $step)
                                                <li class="flex items-start gap-3">
                                                    <span class="step-number">{{ $index + 1 }}</span>
                                                    <span class="text-slate-300 pt-1">{{ $step }}</span>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </div>
                                @endif

                                <!-- Mealprep Steps -->
                                @if(isset($recipe['mealprep_steps']))
                                    <div class="mb-4">
                                        <h4 class="text-sm font-semibold text-purple-400 mb-2">🍱 Мил-преп:</h4>
                                        <ol class="space-y-2">
                                            @foreach($recipe['mealprep_steps'] as $index => $step)
                                                <li class="flex items-start gap-3">
                                                    <span class="step-number bg-purple-600">{{ $index + 1 }}</span>
                                                    <span class="text-slate-300 pt-1">{{ $step }}</span>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </div>
                                @endif

                                <!-- Dipping Sauce -->
                                @if(isset($recipe['dipping_sauce']))
                                    <div class="mb-4 bg-slate-800/50 rounded-lg p-3">
                                        <h4 class="text-sm font-semibold text-orange-400 mb-2">🥢 {{ $recipe['dipping_sauce']['title'] }}:</h4>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($recipe['dipping_sauce']['ingredients'] as $ing)
                                                <span class="ingredient-tag loading" data-ingredient="{{ $ing['item'] }}">{{ $ing['item'] }} — {{ $ing['amount'] }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Dressings -->
                                @if(isset($recipe['dressings']))
                                    <div class="mb-4">
                                        <h4 class="text-sm font-semibold text-slate-400 mb-2">Заправки на выбор:</h4>
                                        <ul class="space-y-1">
                                            @foreach($recipe['dressings'] as $dressing)
                                                <li class="text-slate-300 text-sm">• {{ $dressing }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <!-- Warning -->
                                @if(isset($recipe['warning']))
                                    <div class="warning-card p-3 rounded-lg mb-3">
                                        <p class="text-red-300 text-sm">⚠️ {{ $recipe['warning'] }}</p>
                                    </div>
                                @endif

                                <!-- Chef Tip -->
                                @if(isset($recipe['chef_tip']))
                                    <div class="chef-tip p-3 rounded-lg">
                                        <p class="text-green-300 text-sm">👨‍🍳 {{ $recipe['chef_tip'] }}</p>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif

                <!-- Pantry Categories -->
                @if(isset($section['categories']))
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 mb-8">
                        @foreach($section['categories'] as $cat)
                            <div class="card p-5">
                                <h3 class="text-lg font-bold flex items-center gap-2 mb-3">
                                    <span>{{ $cat['icon'] }}</span>
                                    {{ $cat['name'] }}
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($cat['items'] as $item)
                                        <span class="pantry-item">{{ $item }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Budget -->
                    @if(isset($section['budget_weekly']))
                        <div class="card p-5 mb-8">
                            <h3 class="text-xl font-bold mb-4">💰 Бюджет на неделю: {{ $section['budget_weekly']['total'] }}</h3>
                            <p class="text-slate-400 mb-4">{{ $section['budget_weekly']['note'] }}</p>
                            <div class="grid gap-2 md:grid-cols-2 lg:grid-cols-4">
                                @foreach($section['budget_weekly']['breakdown'] as $item)
                                    <div class="flex justify-between bg-slate-800/50 rounded-lg p-3">
                                        <span class="text-slate-300">{{ $item['item'] }}</span>
                                        <span class="text-green-400 font-medium">{{ $item['cost'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Special Ingredients -->
                    @if(isset($section['special_ingredients']))
                        @foreach($section['special_ingredients'] as $ing)
                            <div class="tip-card p-5 rounded-xl">
                                <h3 class="text-lg font-bold mb-2">⭐ {{ $ing['name'] }}</h3>
                                <p class="text-slate-400 mb-3">{{ $ing['purpose'] }}</p>
                                <div class="grid gap-3 md:grid-cols-2">
                                    <div>
                                        <h4 class="text-sm font-semibold text-orange-400 mb-2">Преимущества:</h4>
                                        <ul class="space-y-1">
                                            @foreach($ing['benefits'] as $b)
                                                <li class="text-slate-300 text-sm">✓ {{ $b }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    <div>
                                        <p class="text-sm"><span class="text-slate-400">Как использовать:</span> <span class="text-slate-300">{{ $ing['how_to_use'] }}</span></p>
                                        <p class="text-sm mt-2"><span class="text-slate-400">Пропорции:</span> <span class="text-slate-300">{{ $ing['proportion'] }}</span></p>
                                        <p class="text-sm mt-2"><span class="text-slate-400">Замена:</span> <span class="text-slate-300">{{ $ing['substitute'] }}</span></p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                @endif

                <!-- Storage Section -->
                @if($sectionKey === 'storage')
                    <!-- Fridge Zones -->
                    @if(isset($section['fridge_zones']))
                        <div class="card p-5 mb-6">
                            <h3 class="text-xl font-bold mb-3">🧊 Зоны холодильника</h3>
                            <p class="text-slate-400 text-sm mb-4">{{ $section['fridge_zones']['note'] }}</p>
                            <div class="space-y-2">
                                @foreach($section['fridge_zones']['zones'] as $zone)
                                    <div class="storage-zone bg-slate-800/50 flex flex-wrap items-center gap-4">
                                        <span class="font-medium text-blue-400 w-32">{{ $zone['zone'] }}</span>
                                        <span class="text-slate-500 w-24">{{ $zone['temp'] }}</span>
                                        <span class="text-slate-300 flex-1">{{ $zone['store'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Vegetables Storage -->
                    @if(isset($section['vegetables']))
                        <h3 class="text-xl font-bold mb-4">🥬 Овощи</h3>
                        <div class="grid gap-4 md:grid-cols-2 mb-8">
                            @foreach($section['vegetables'] as $veg)
                                <details class="card p-4">
                                    <summary class="flex items-center justify-between cursor-pointer">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl">{{ $veg['emoji'] }}</span>
                                            <div>
                                                <h4 class="font-bold">{{ $veg['name'] }}</h4>
                                                <p class="text-sm text-slate-400">{{ $veg['ideal_temp'] }} • {{ $veg['shelf_life'] }}</p>
                                            </div>
                                        </div>
                                        <svg class="chevron w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </summary>
                                    <div class="mt-4 space-y-3 text-sm">
                                        <p><span class="text-slate-400">Где хранить:</span> <span class="text-slate-300">{{ $veg['where'] ?? '' }}</span></p>
                                        @if(isset($veg['container']))
                                            <p><span class="text-slate-400">Контейнер:</span> <span class="text-slate-300">{{ $veg['container'] }}</span></p>
                                        @endif
                                        @if(isset($veg['problem']))
                                            <div class="warning-card p-3 rounded-lg">
                                                <p class="text-red-300">⚠️ {{ $veg['problem'] }}</p>
                                            </div>
                                        @endif
                                        @if(isset($veg['chef_tip']))
                                            <div class="chef-tip p-3 rounded-lg">
                                                <p class="text-green-300">👨‍🍳 {{ $veg['chef_tip'] }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    @endif

                    <!-- Meat & Fish -->
                    @if(isset($section['meat_fish']))
                        <h3 class="text-xl font-bold mb-4">🥩 Мясо и рыба</h3>
                        <div class="grid gap-4 md:grid-cols-2 mb-8">
                            @foreach($section['meat_fish'] as $item)
                                <details class="card p-4">
                                    <summary class="flex items-center justify-between cursor-pointer">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl">{{ $item['emoji'] }}</span>
                                            <div>
                                                <h4 class="font-bold">{{ $item['name'] }}</h4>
                                                <p class="text-sm text-slate-400">{{ $item['ideal_temp'] }} • {{ $item['shelf_life'] }}</p>
                                            </div>
                                        </div>
                                        <svg class="chevron w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </summary>
                                    <div class="mt-4 space-y-3 text-sm">
                                        <p><span class="text-slate-400">Где хранить:</span> <span class="text-slate-300">{{ $item['where'] }}</span></p>
                                        @if(isset($item['warning']))
                                            <div class="warning-card p-3 rounded-lg">
                                                <p class="text-red-300">⚠️ {{ $item['warning'] }}</p>
                                            </div>
                                        @endif
                                        @if(isset($item['chef_tip']))
                                            <div class="chef-tip p-3 rounded-lg">
                                                <p class="text-green-300">👨‍🍳 {{ $item['chef_tip'] }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    @endif

                    <!-- Dairy & Eggs -->
                    @if(isset($section['dairy_eggs']))
                        <h3 class="text-xl font-bold mb-4">🥛 Молочка и яйца</h3>
                        <div class="grid gap-4 md:grid-cols-2 mb-8">
                            @foreach($section['dairy_eggs'] as $item)
                                <details class="card p-4">
                                    <summary class="flex items-center justify-between cursor-pointer">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl">{{ $item['emoji'] }}</span>
                                            <div>
                                                <h4 class="font-bold">{{ $item['name'] }}</h4>
                                                <p class="text-sm text-slate-400">{{ $item['ideal_temp'] }} • {{ $item['shelf_life'] }}</p>
                                            </div>
                                        </div>
                                        <svg class="chevron w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </summary>
                                    <div class="mt-4 space-y-3 text-sm">
                                        <p><span class="text-slate-400">Где хранить:</span> <span class="text-slate-300">{{ $item['where'] }}</span></p>
                                        @if(isset($item['freshness_test']))
                                            <div class="tip-card p-3 rounded-lg">
                                                <p class="text-blue-300">🧪 {{ $item['freshness_test'] }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    @endif

                    <!-- Freezing Guide -->
                    @if(isset($section['freezing']))
                        <h3 class="text-xl font-bold mb-4">❄️ Заморозка</h3>
                        <div class="card p-5 mb-6">
                            <h4 class="font-bold mb-3 text-blue-400">Таблица заморозки</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-700">
                                            <th class="text-left py-2 px-3 text-slate-400">Продукт</th>
                                            <th class="text-left py-2 px-3 text-slate-400">Упаковка</th>
                                            <th class="text-left py-2 px-3 text-slate-400">Срок</th>
                                            <th class="text-left py-2 px-3 text-slate-400">Разморозка</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($section['freezing']['guide'] as $item)
                                            <tr class="border-b border-slate-800">
                                                <td class="py-2 px-3 text-orange-400">{{ $item['product'] }}</td>
                                                <td class="py-2 px-3 text-slate-300">{{ $item['container'] }}</td>
                                                <td class="py-2 px-3 text-green-400">{{ $item['shelf_life'] }}</td>
                                                <td class="py-2 px-3 text-slate-300">{{ $item['thaw'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 mb-6">
                            <div class="warning-card p-5 rounded-xl">
                                <h4 class="font-bold mb-3 text-red-400">🚫 Не замораживать</h4>
                                <ul class="space-y-2">
                                    @foreach($section['freezing']['do_not_freeze'] as $item)
                                        <li class="text-sm">
                                            <span class="text-slate-300">{{ $item['item'] }}</span>
                                            <span class="text-slate-500"> — {{ $item['reason'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="chef-tip p-5 rounded-xl">
                                <h4 class="font-bold mb-3 text-green-400">💡 Советы по заморозке</h4>
                                <ul class="space-y-2">
                                    @foreach($section['freezing']['tips'] as $tip)
                                        <li class="text-sm">
                                            <span class="text-orange-400 font-medium">{{ $tip['tip'] }}:</span>
                                            <span class="text-slate-300"> {{ $tip['description'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <!-- Bread -->
                    @if(isset($section['bread']))
                        <div class="card p-5">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="text-3xl">{{ $section['bread']['emoji'] }}</span>
                                <div>
                                    <h3 class="text-xl font-bold">{{ $section['bread']['name'] }}</h3>
                                    <p class="text-sm text-slate-400">{{ $section['bread']['ideal_temp'] }}</p>
                                </div>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <p class="text-sm"><span class="text-slate-400">Комната:</span> <span class="text-slate-300">{{ $section['bread']['shelf_life_room'] }}</span></p>
                                    <p class="text-sm mt-1"><span class="text-slate-400">Морозилка:</span> <span class="text-slate-300">{{ $section['bread']['shelf_life_freezer'] }}</span></p>
                                    <p class="text-sm mt-1"><span class="text-slate-400">Где:</span> <span class="text-slate-300">{{ $section['bread']['where'] }}</span></p>
                                </div>
                                <div>
                                    @if(isset($section['bread']['problem']))
                                        <div class="warning-card p-3 rounded-lg mb-2">
                                            <p class="text-red-300 text-sm">⚠️ {{ $section['bread']['problem'] }}</p>
                                        </div>
                                    @endif
                                    <div class="chef-tip p-3 rounded-lg">
                                        <p class="text-green-300 text-sm">💡 {{ $section['bread']['thaw'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </section>
        @endforeach
    </main>

    <!-- Stats Bar (fixed at bottom) -->
    <div id="priceStats" class="fixed bottom-0 left-0 right-0 bg-slate-900/95 backdrop-blur border-t border-slate-700 p-3 z-50">
        <div class="max-w-6xl mx-auto">
            <div class="stats-bar">
                <div class="stat-item">
                    <span class="text-slate-400">Статус:</span>
                    <span id="loadingStatus" class="text-yellow-400">Загрузка цен...</span>
                </div>
                <div class="stat-item">
                    <span class="text-green-400">✓</span>
                    <span id="foundCount">0</span>
                    <span class="text-slate-500">найдено</span>
                </div>
                <div class="stat-item">
                    <span class="text-red-400">✗</span>
                    <span id="notFoundCount">0</span>
                    <span class="text-slate-500">не найдено</span>
                </div>
                <div class="stat-item">
                    <span class="text-slate-400">~</span>
                    <span id="skippedCount">0</span>
                    <span class="text-slate-500">пропущено</span>
                </div>
                <button id="showMissingBtn" class="copy-btn ml-auto" style="display:none" onclick="toggleMissingPanel()">
                    🛒 Нужно спарсить (<span id="missingBtnCount">0</span>)
                </button>
            </div>
        </div>
    </div>

    <!-- Missing Products Panel -->
    <div id="missingPanel" class="missing-panel">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-lg">🛒 Нужно спарсить</h3>
            <button onclick="toggleMissingPanel()" class="text-slate-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <p class="text-sm text-slate-400 mb-3">Эти продукты отсутствуют в базе. Используй расширение для парсинга:</p>
        <div id="missingList" class="space-y-2 mb-4 max-h-48 overflow-y-auto"></div>
        <button id="copyMissingBtn" class="copy-btn w-full" onclick="copyMissingToClipboard()">
            📋 Скопировать для поиска
        </button>
    </div>

    <!-- Back to top (moved up to avoid stats bar) -->
    <button onclick="window.scrollTo({top:0,behavior:'smooth'})"
            class="fixed bottom-20 right-6 bg-orange-500 hover:bg-orange-600 text-white w-12 h-12 rounded-full shadow-lg flex items-center justify-center transition-all z-40">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
        </svg>
    </button>

    <script>
        // Section navigation
        function showSection(key) {
            document.querySelectorAll('.section-content').forEach(s => s.style.display = 'none');
            document.getElementById('section-' + key).style.display = 'block';
            document.querySelectorAll('.section-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.section === key) btn.classList.add('active');
            });
            window.scrollTo({top: 0, behavior: 'smooth'});
        }
        document.querySelector('.section-btn').classList.add('active');

        // Price loading
        let missingProducts = [];
        let priceResults = {};

        async function loadPrices() {
            // Collect all unique ingredients
            const ingredientTags = document.querySelectorAll('[data-ingredient]');
            const ingredients = [...new Set([...ingredientTags].map(el => el.dataset.ingredient))];

            if (ingredients.length === 0) {
                document.getElementById('loadingStatus').textContent = 'Нет ингредиентов';
                document.getElementById('loadingStatus').className = 'text-slate-400';
                return;
            }

            try {
                const response = await fetch('/smartcart/api/ingredient-prices.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ingredients, store: 'perekrestok' })
                });

                const data = await response.json();
                priceResults = data.results || {};
                missingProducts = data.missing_for_extension || [];

                // Update stats
                document.getElementById('foundCount').textContent = data.stats?.found || 0;
                document.getElementById('notFoundCount').textContent = data.stats?.not_found || 0;
                document.getElementById('skippedCount').textContent = data.stats?.skipped || 0;
                document.getElementById('loadingStatus').textContent = 'Цены загружены';
                document.getElementById('loadingStatus').className = 'text-green-400';

                // Update ingredient tags
                ingredientTags.forEach(tag => {
                    const ingredient = tag.dataset.ingredient;
                    const result = priceResults[ingredient];
                    tag.classList.remove('loading');

                    if (result) {
                        if (result.status === 'found') {
                            tag.classList.add('has-price');
                            const priceSpan = document.createElement('span');
                            priceSpan.className = 'price-badge';
                            priceSpan.textContent = result.price + '₽';
                            if (result.discount) {
                                priceSpan.innerHTML = `<s class="text-slate-500">${result.original_price}₽</s> ${result.price}₽`;
                            }
                            tag.appendChild(priceSpan);
                            tag.title = result.product;
                        } else if (result.status === 'not_found') {
                            tag.classList.add('not-found');
                            tag.title = 'Не найден в базе';
                        }
                        // 'skip' status - just remove loading class
                    }
                });

                // Show missing button if there are missing products
                if (missingProducts.length > 0) {
                    document.getElementById('showMissingBtn').style.display = 'block';
                    document.getElementById('missingBtnCount').textContent = missingProducts.length;

                    // Populate missing list
                    const missingList = document.getElementById('missingList');
                    missingList.innerHTML = missingProducts.map(p =>
                        `<div class="bg-slate-800/50 rounded px-3 py-2 text-sm text-slate-300">${p}</div>`
                    ).join('');
                }

            } catch (error) {
                console.error('Error loading prices:', error);
                document.getElementById('loadingStatus').textContent = 'Ошибка загрузки';
                document.getElementById('loadingStatus').className = 'text-red-400';

                // Remove loading class from all tags
                ingredientTags.forEach(tag => tag.classList.remove('loading'));
            }
        }

        function toggleMissingPanel() {
            const panel = document.getElementById('missingPanel');
            panel.classList.toggle('show');
        }

        function copyMissingToClipboard() {
            const text = missingProducts.join('\n');
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('copyMissingBtn');
                btn.classList.add('copied');
                btn.textContent = '✓ Скопировано!';
                setTimeout(() => {
                    btn.classList.remove('copied');
                    btn.textContent = '📋 Скопировать для поиска';
                }, 2000);
            });
        }

        // Load prices on page load
        document.addEventListener('DOMContentLoaded', loadPrices);
    </script>
</body>
</html>
