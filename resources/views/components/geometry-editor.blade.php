{{--
    SVG Geometry Editor - Редактор геометрических фигур

    Использование:
    @include('components.geometry-editor', ['taskId' => '15OGE123'])
--}}

<style>
    /* SVG visibility fix for Alpine.js */
    #geometry-canvas g[x-show] {
        display: inline !important;
    }
    #geometry-canvas g[x-show][style*="display: none"] {
        display: none !important;
    }
</style>

<div id="geometry-editor-modal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm"
     x-data="geometryEditor()"
     x-show="isOpen"
     x-cloak
     @keydown.escape.window="close()"
     @keydown.ctrl.z.window.prevent="undo()"
     @keydown.ctrl.shift.z.window.prevent="redo()"
     @keydown.ctrl.y.window.prevent="redo()">

    <div class="bg-[#0f0f1a] rounded-2xl shadow-2xl w-[95vw] max-w-[1400px] h-[90vh] flex flex-col overflow-hidden border border-purple-500/30">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-purple-500/20 bg-[#1a1a2e]">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                    </svg>
                    Редактор геометрии
                </h2>
                <span class="text-sm text-gray-400" x-text="'Задание: ' + taskId"></span>
                <span x-show="mode === 'legacy_view'" class="px-2 py-1 text-xs bg-amber-500/20 text-amber-400 rounded">Legacy</span>
            </div>

            <div class="flex items-center gap-2">
                {{-- Undo/Redo --}}
                <button @click="undo()" :disabled="!canUndo"
                        class="p-2 rounded-lg hover:bg-purple-500/20 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                        title="Отменить (Ctrl+Z)">
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                </button>
                <button @click="redo()" :disabled="!canRedo"
                        class="p-2 rounded-lg hover:bg-purple-500/20 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                        title="Повторить (Ctrl+Y)">
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6"/>
                    </svg>
                </button>

                <div class="w-px h-6 bg-gray-600 mx-2"></div>

                {{-- Close --}}
                <button @click="close()" class="p-2 rounded-lg hover:bg-red-500/20 transition-colors" title="Закрыть (Esc)">
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Main content --}}
        <div class="flex flex-1 overflow-hidden">

            {{-- Canvas area --}}
            <div class="flex-1 flex flex-col bg-[#0a0a14]">

                {{-- Toolbar --}}
                <div class="flex items-center gap-2 px-4 py-3 border-b border-purple-500/10 bg-[#12121f]">
                    {{-- Add figure --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center gap-2 px-3 py-2 bg-purple-600 hover:bg-purple-500 rounded-lg text-white text-sm font-medium transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Добавить фигуру
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition
                             class="absolute top-full left-0 mt-2 w-56 bg-[#1e1e32] rounded-lg shadow-xl border border-purple-500/20 py-2 z-50">
                            <div class="px-3 py-1 text-xs text-gray-500 uppercase tracking-wider">Планиметрия</div>
                            <button @click="addFigure('triangle'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">△</span> Треугольник
                            </button>
                            <button @click="addFigure('quadrilateral'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">▢</span> Четырёхугольник
                            </button>
                            <button @click="addFigure('circle'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">○</span> Окружность
                            </button>
                            <div class="border-t border-gray-700 my-2"></div>
                            <div class="px-3 py-1 text-xs text-gray-500 uppercase tracking-wider">Стереометрия</div>
                            <button @click="addFigure('cube'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">⬡</span> Куб
                            </button>
                            <button @click="addFigure('prism'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">⬡</span> Призма
                            </button>
                            <button @click="addFigure('pyramid'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">△</span> Пирамида
                            </button>
                            <button @click="addFigure('cylinder'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">⬭</span> Цилиндр
                            </button>
                            <button @click="addFigure('cone'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">▲</span> Конус
                            </button>
                            <button @click="addFigure('sphere'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">●</span> Шар
                            </button>
                        </div>
                    </div>

                    {{-- Presets --}}
                    <div class="relative" x-data="{ open: false }" x-show="selectedFigure && selectedFigure.type === 'triangle'">
                        <button @click="open = !open"
                                class="flex items-center gap-2 px-3 py-2 bg-[#1e1e32] hover:bg-[#2a2a42] rounded-lg text-gray-200 text-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/>
                            </svg>
                            Пресеты
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition
                             class="absolute top-full left-0 mt-2 w-48 bg-[#1e1e32] rounded-lg shadow-xl border border-purple-500/20 py-2 z-50">
                            <button @click="applyPreset('isosceles'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20">
                                Равнобедренный
                            </button>
                            <button @click="applyPreset('equilateral'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20">
                                Равносторонний
                            </button>
                            <button @click="applyPreset('right'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20">
                                Прямоугольный
                            </button>
                            <button @click="applyPreset('free'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20">
                                Произвольный
                            </button>
                        </div>
                    </div>

                    {{-- Grid toggle --}}
                    <button @click="toggleGrid()"
                            :class="showGrid ? 'bg-purple-600 text-white' : 'bg-[#1e1e32] text-gray-200'"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 9h16M4 15h16M9 4v16M15 4v16"/>
                        </svg>
                        Сетка
                    </button>

                    {{-- Grid size (when grid is on) --}}
                    <div x-show="showGrid" class="flex items-center gap-2 ml-2">
                        <label class="text-xs text-gray-400">Клетка:</label>
                        <input type="range" min="10" max="50" x-model="gridSize"
                               class="w-20 h-1 bg-gray-700 rounded-lg appearance-none cursor-pointer">
                        <input type="number" x-model="gridSize" min="10" max="50"
                               class="w-12 px-1 py-1 text-xs bg-[#1e1e32] text-gray-200 rounded border border-gray-600 text-center">
                        <span class="text-xs text-gray-500">px</span>
                    </div>

                    {{-- ViewBox settings --}}
                    <div class="flex items-center gap-2 ml-4 border-l border-gray-700 pl-4">
                        <label class="text-xs text-gray-400">ViewBox:</label>
                        <input type="number" x-model.number="canvasWidth" min="100" max="800" step="10"
                               class="w-14 px-1 py-1 text-xs bg-[#1e1e32] text-gray-200 rounded border border-gray-600 text-center"
                               title="Ширина">
                        <span class="text-xs text-gray-500">×</span>
                        <input type="number" x-model.number="canvasHeight" min="100" max="600" step="10"
                               class="w-14 px-1 py-1 text-xs bg-[#1e1e32] text-gray-200 rounded border border-gray-600 text-center"
                               title="Высота">
                        <button @click="canvasWidth = 350; canvasHeight = 280"
                                class="px-2 py-1 text-xs bg-[#1e1e32] hover:bg-[#2a2a42] text-gray-400 rounded"
                                title="Стандартный размер (как в заданиях)">
                            350×280
                        </button>
                        <button @click="canvasWidth = 400; canvasHeight = 320"
                                class="px-2 py-1 text-xs bg-[#1e1e32] hover:bg-[#2a2a42] text-gray-400 rounded"
                                title="Большой размер">
                            400×320
                        </button>
                    </div>

                    <div class="flex-1"></div>

                    {{-- Delete selected --}}
                    <button @click="deleteSelected()" x-show="selectedFigure"
                            class="flex items-center gap-2 px-3 py-2 bg-red-600/20 hover:bg-red-600/40 rounded-lg text-red-400 text-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Удалить
                    </button>
                </div>

                {{-- SVG Canvas --}}
                <div class="flex-1 flex items-center justify-center overflow-auto bg-[#0a0a14]" id="canvas-container">
                    <svg id="geometry-canvas"
                         :style="`width: ${canvasWidth}px; height: ${canvasHeight}px`"
                         :viewBox="`0 0 ${canvasWidth} ${canvasHeight}`"
                         @mousedown="onCanvasMouseDown($event)"
                         @mousemove="onCanvasMouseMove($event)"
                         @mouseup="onCanvasMouseUp($event)"
                         @mouseleave="onCanvasMouseUp($event)">

                        {{-- Dark background (как в оригинальных SVG) --}}
                        <rect width="100%" height="100%" fill="#0a1628"/>

                        {{-- Grid --}}
                        <template x-if="showGrid">
                            <g class="grid-layer">
                                <defs>
                                    <pattern :id="'grid-' + gridSize" :width="gridSize" :height="gridSize" patternUnits="userSpaceOnUse">
                                        <path :d="`M ${gridSize} 0 L 0 0 0 ${gridSize}`" fill="none" stroke="#1e3a5f" stroke-width="0.5"/>
                                    </pattern>
                                </defs>
                                <rect width="100%" height="100%" :fill="`url(#grid-${gridSize})`"/>
                            </g>
                        </template>

                        {{-- Figures - rendered via x-html for SVG compatibility --}}
                        <g x-html="renderAllFigures()"></g>
                    </svg>

                    {{-- Legacy view overlay --}}
                    <div x-show="mode === 'legacy_view'"
                         class="absolute inset-0 flex flex-col items-center justify-center bg-black/60">
                        <div class="text-center p-8">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-500/20 flex items-center justify-center">
                                <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Legacy изображение</h3>
                            <p class="text-gray-400 mb-6 max-w-md">
                                Это изображение создано в старой системе.<br>
                                Для редактирования нужно пересоздать его в редакторе.
                            </p>
                            <button @click="recreateInEditor()"
                                    class="px-6 py-3 bg-purple-600 hover:bg-purple-500 rounded-lg text-white font-medium transition-colors">
                                🔄 Пересоздать в редакторе
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Bottom toolbar --}}
                <div class="flex items-center justify-between px-4 py-3 border-t border-purple-500/10 bg-[#12121f]">
                    <div class="flex items-center gap-4">
                        <button @click="resetCanvas()"
                                class="px-3 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-gray-200 text-sm transition-colors">
                            🔄 Сбросить
                        </button>
                        <button @click="exportSvg()"
                                class="px-3 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-gray-200 text-sm transition-colors">
                            📥 Экспорт SVG
                        </button>
                    </div>
                    <div class="flex items-center gap-4">
                        <button @click="copySvgCode()"
                                class="px-3 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-gray-200 text-sm transition-colors">
                            📋 Копировать код
                        </button>
                        <button @click="save()" :disabled="saving"
                                class="px-6 py-2 bg-green-600 hover:bg-green-500 disabled:opacity-50 rounded-lg text-white font-medium transition-colors flex items-center gap-2">
                            <span x-show="!saving">💾 Сохранить</span>
                            <span x-show="saving" class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Сохранение...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Properties panel --}}
            <div class="w-80 border-l border-purple-500/20 bg-[#1a1a2e] overflow-y-auto">
                <div class="p-4 space-y-4">

                    {{-- No selection --}}
                    <div x-show="!selectedFigure" class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                        </svg>
                        <p>Выберите фигуру для редактирования</p>
                    </div>

                    {{-- Triangle properties --}}
                    <template x-if="selectedFigure && selectedFigure.type === 'triangle'">
                        <div class="space-y-4">
                            {{-- Vertices --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3 flex items-center gap-2">
                                    <span>📍</span> Вершины
                                </h3>
                                <div class="space-y-2">
                                    <template x-for="vName in ['A', 'B', 'C']" :key="vName">
                                        <div class="flex items-center gap-2">
                                            <input type="text" :value="selectedFigure.vertices[vName].label || vName"
                                                   @change="updateVertexLabel(vName, $event.target.value)"
                                                   class="w-10 px-2 py-1 text-sm bg-[#1e1e32] text-orange-400 rounded border border-gray-600 text-center font-bold">
                                            <span class="text-gray-500 text-xs">x:</span>
                                            <input type="number" :value="Math.round(selectedFigure.vertices[vName].x)"
                                                   @change="updateVertexCoord(vName, 'x', $event.target.value)"
                                                   class="w-16 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                            <span class="text-gray-500 text-xs">y:</span>
                                            <input type="number" :value="Math.round(selectedFigure.vertices[vName].y)"
                                                   @change="updateVertexCoord(vName, 'y', $event.target.value)"
                                                   class="w-16 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Angles --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3 flex items-center gap-2">
                                    <span>📐</span> Углы
                                </h3>
                                <div class="space-y-2">
                                    <template x-for="vName in ['A', 'B', 'C']" :key="'angle-' + vName">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="w-8 text-orange-400 font-bold">∠<span x-text="selectedFigure.vertices[vName].label || vName"></span></span>
                                                <input type="number" min="1" max="178"
                                                       :value="getAngleValue(selectedFigure, vName)"
                                                       @change="setAngleValue(vName, $event.target.value)"
                                                       class="w-16 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                                <span class="text-gray-500 text-xs">°</span>
                                                <label class="flex items-center gap-1 text-xs text-gray-400 ml-auto">
                                                    <input type="checkbox"
                                                           :checked="selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].showArc"
                                                           @change="toggleAngleArc(vName, $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    дуга
                                                </label>
                                                <label class="flex items-center gap-1 text-xs text-gray-400">
                                                    <input type="checkbox"
                                                           :checked="selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].showValue"
                                                           @change="toggleAngleValue(vName, $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    °
                                                </label>
                                            </div>
                                            {{-- Arc radius slider (visible when arc is on) --}}
                                            <div x-show="selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].showArc"
                                                 class="flex items-center gap-2 pl-8">
                                                <span class="text-[10px] text-gray-500 w-6">R</span>
                                                <input type="range" min="10" max="80" step="1"
                                                       :value="(selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].arcRadius) || 30"
                                                       @input="setArcRadius(vName, $event.target.value)"
                                                       class="flex-1 h-1 accent-orange-500">
                                                <span class="text-[10px] text-gray-500 w-6" x-text="(selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].arcRadius) || 30"></span>
                                            </div>
                                            {{-- Arc stroke width slider (visible when arc is on) --}}
                                            <div x-show="selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].showArc"
                                                 class="flex items-center gap-2 pl-8">
                                                <span class="text-[10px] text-gray-500 w-6">W</span>
                                                <input type="range" min="0.5" max="6" step="0.5"
                                                       :value="(selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].arcStrokeWidth) || 2.5"
                                                       @input="setArcStrokeWidth(vName, $event.target.value)"
                                                       class="flex-1 h-1 accent-orange-500">
                                                <span class="text-[10px] text-gray-500 w-6" x-text="(selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].arcStrokeWidth) || 2.5"></span>
                                            </div>
                                            {{-- Label offset controls (visible when value is on) --}}
                                            <div x-show="selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].showValue"
                                                 class="flex items-center gap-2 pl-8">
                                                <span class="text-[10px] text-gray-500">dx</span>
                                                <input type="number" min="-50" max="50"
                                                       :value="(selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].labelDx) || 0"
                                                       @change="setAngleLabelOffset(vName, 'dx', $event.target.value)"
                                                       class="w-12 px-1 py-0.5 text-[10px] bg-[#1e1e32] text-gray-300 rounded border border-gray-600">
                                                <span class="text-[10px] text-gray-500">dy</span>
                                                <input type="number" min="-50" max="50"
                                                       :value="(selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].labelDy) || 0"
                                                       @change="setAngleLabelOffset(vName, 'dy', $event.target.value)"
                                                       class="w-12 px-1 py-0.5 text-[10px] bg-[#1e1e32] text-gray-300 rounded border border-gray-600">
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Lines --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3 flex items-center gap-2">
                                    <span>📏</span> Линии
                                </h3>
                                <div class="space-y-3">
                                    <template x-for="vName in ['A', 'B', 'C']" :key="'lines-' + vName">
                                        <div class="space-y-1">
                                            <div class="text-xs text-gray-500 mb-1">Из вершины <span x-text="selectedFigure.vertices[vName].label || vName" class="text-orange-400"></span></div>
                                            <div class="flex flex-wrap gap-2">
                                                <label class="flex items-center gap-1 text-xs text-gray-300">
                                                    <input type="checkbox"
                                                           :checked="selectedFigure.lines && selectedFigure.lines['bisector_' + vName.toLowerCase()] && selectedFigure.lines['bisector_' + vName.toLowerCase()].enabled"
                                                           @change="toggleLine('bisector_' + vName.toLowerCase(), $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    <span class="text-purple-400">Биссектриса</span>
                                                </label>
                                                {{-- Дуги половинных углов (показывается когда биссектриса включена) --}}
                                                <label x-show="selectedFigure.lines && selectedFigure.lines['bisector_' + vName.toLowerCase()] && selectedFigure.lines['bisector_' + vName.toLowerCase()].enabled"
                                                       class="flex items-center gap-1 text-xs text-gray-300 ml-2">
                                                    <input type="checkbox"
                                                           :checked="selectedFigure.lines && selectedFigure.lines['bisector_' + vName.toLowerCase()] && selectedFigure.lines['bisector_' + vName.toLowerCase()].showHalfArcs"
                                                           @change="toggleBisectorHalfArcs('bisector_' + vName.toLowerCase(), $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    <span class="text-amber-400">дуги</span>
                                                </label>
                                                <label class="flex items-center gap-1 text-xs text-gray-300">
                                                    <input type="checkbox"
                                                           :checked="selectedFigure.lines && selectedFigure.lines['median_' + vName.toLowerCase()] && selectedFigure.lines['median_' + vName.toLowerCase()].enabled"
                                                           @change="toggleLine('median_' + vName.toLowerCase(), $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    <span class="text-blue-400">Медиана</span>
                                                </label>
                                                <label class="flex items-center gap-1 text-xs text-gray-300">
                                                    <input type="checkbox"
                                                           :checked="selectedFigure.lines && selectedFigure.lines['altitude_' + vName.toLowerCase()] && selectedFigure.lines['altitude_' + vName.toLowerCase()].enabled"
                                                           @change="toggleLine('altitude_' + vName.toLowerCase(), $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    <span class="text-green-400">Высота</span>
                                                </label>
                                            </div>
                                            {{-- Half-arc radius slider (visible when bisector arcs are on) --}}
                                            <div x-show="selectedFigure.lines && selectedFigure.lines['bisector_' + vName.toLowerCase()] && selectedFigure.lines['bisector_' + vName.toLowerCase()].showHalfArcs"
                                                 class="flex items-center gap-2 pl-4 mt-1">
                                                <span class="text-[10px] text-gray-500 w-6">R</span>
                                                <input type="range" min="10" max="80" step="1"
                                                       :value="(selectedFigure.lines && selectedFigure.lines['bisector_' + vName.toLowerCase()] && selectedFigure.lines['bisector_' + vName.toLowerCase()].halfArcRadius) || 30"
                                                       @input="setHalfArcRadius('bisector_' + vName.toLowerCase(), $event.target.value)"
                                                       class="flex-1 h-1 accent-amber-500">
                                                <span class="text-[10px] text-gray-500 w-6"
                                                      x-text="(selectedFigure.lines && selectedFigure.lines['bisector_' + vName.toLowerCase()] && selectedFigure.lines['bisector_' + vName.toLowerCase()].halfArcRadius) || 30"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Equal sides --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3 flex items-center gap-2">
                                    <span>═</span> Равные стороны
                                </h3>
                                <div class="space-y-2">
                                    <template x-for="group in [1, 2, 3]" :key="'equal-group-' + group">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-500 w-16">Группа <span x-text="group"></span>:</span>
                                            <template x-for="side in ['AB', 'BC', 'AC']" :key="side">
                                                <label class="flex items-center gap-1 text-xs">
                                                    <input type="checkbox"
                                                           :checked="isSideInEqualGroup(group, side)"
                                                           @change="toggleSideInEqualGroup(group, side, $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    <span class="text-gray-300" x-text="side"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Circle properties --}}
                    <template x-if="selectedFigure && selectedFigure.type === 'circle'">
                        <div class="space-y-4">
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3">⭕ Окружность</h3>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Центр:</span>
                                        <input type="text" :value="selectedFigure.centerLabel || 'O'"
                                               @change="selectedFigure.centerLabel = $event.target.value; saveState()"
                                               class="w-10 px-2 py-1 text-sm bg-[#1e1e32] text-orange-400 rounded border border-gray-600 text-center font-bold">
                                        <span class="text-gray-500 text-xs">x:</span>
                                        <input type="number" :value="Math.round(selectedFigure.center.x)"
                                               @change="selectedFigure.center.x = parseInt($event.target.value); saveState()"
                                               class="w-16 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                        <span class="text-gray-500 text-xs">y:</span>
                                        <input type="number" :value="Math.round(selectedFigure.center.y)"
                                               @change="selectedFigure.center.y = parseInt($event.target.value); saveState()"
                                               class="w-16 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Радиус:</span>
                                        <input type="number" :value="Math.round(selectedFigure.radius)" min="10"
                                               @change="selectedFigure.radius = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                        <span class="text-gray-500 text-xs">px</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Circle elements --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3">📏 Элементы</h3>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-sm text-gray-300">
                                        <input type="checkbox"
                                               :checked="selectedFigure.showDiameter"
                                               @change="selectedFigure.showDiameter = $event.target.checked; saveState()"
                                               class="rounded bg-gray-700 border-gray-600">
                                        Диаметр
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-300">
                                        <input type="checkbox"
                                               :checked="selectedFigure.showRadius"
                                               @change="selectedFigure.showRadius = $event.target.checked; saveState()"
                                               class="rounded bg-gray-700 border-gray-600">
                                        Радиус
                                    </label>
                                    <button @click="addChord()"
                                            class="w-full px-3 py-2 bg-[#1e1e32] hover:bg-[#2a2a42] rounded text-sm text-gray-300 text-left">
                                        + Добавить хорду
                                    </button>
                                    <button @click="addTangent()"
                                            class="w-full px-3 py-2 bg-[#1e1e32] hover:bg-[#2a2a42] rounded text-sm text-gray-300 text-left">
                                        + Добавить касательную
                                    </button>
                                    <button @click="addSecant()"
                                            class="w-full px-3 py-2 bg-[#1e1e32] hover:bg-[#2a2a42] rounded text-sm text-gray-300 text-left">
                                        + Добавить секущую
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Quadrilateral properties --}}
                    <template x-if="selectedFigure && selectedFigure.type === 'quadrilateral'">
                        <div class="space-y-4">
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3">▢ Четырёхугольник</h3>
                                <div class="space-y-2">
                                    <template x-for="vName in ['A', 'B', 'C', 'D']" :key="vName">
                                        <div class="flex items-center gap-2">
                                            <input type="text" :value="selectedFigure.vertices[vName].label || vName"
                                                   @change="updateVertexLabel(vName, $event.target.value)"
                                                   class="w-10 px-2 py-1 text-sm bg-[#1e1e32] text-orange-400 rounded border border-gray-600 text-center font-bold">
                                            <span class="text-gray-500 text-xs">x:</span>
                                            <input type="number" :value="Math.round(selectedFigure.vertices[vName].x)"
                                                   @change="updateVertexCoord(vName, 'x', $event.target.value)"
                                                   class="w-16 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                            <span class="text-gray-500 text-xs">y:</span>
                                            <input type="number" :value="Math.round(selectedFigure.vertices[vName].y)"
                                                   @change="updateVertexCoord(vName, 'y', $event.target.value)"
                                                   class="w-16 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Presets --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3">📐 Тип</h3>
                                <div class="grid grid-cols-2 gap-2">
                                    <button @click="applyQuadPreset('parallelogram')"
                                            :class="selectedFigure.preset === 'parallelogram' ? 'bg-purple-600' : 'bg-[#1e1e32]'"
                                            class="px-3 py-2 rounded text-sm text-gray-200 hover:bg-purple-500/50">
                                        Параллелограмм
                                    </button>
                                    <button @click="applyQuadPreset('rectangle')"
                                            :class="selectedFigure.preset === 'rectangle' ? 'bg-purple-600' : 'bg-[#1e1e32]'"
                                            class="px-3 py-2 rounded text-sm text-gray-200 hover:bg-purple-500/50">
                                        Прямоугольник
                                    </button>
                                    <button @click="applyQuadPreset('rhombus')"
                                            :class="selectedFigure.preset === 'rhombus' ? 'bg-purple-600' : 'bg-[#1e1e32]'"
                                            class="px-3 py-2 rounded text-sm text-gray-200 hover:bg-purple-500/50">
                                        Ромб
                                    </button>
                                    <button @click="applyQuadPreset('square')"
                                            :class="selectedFigure.preset === 'square' ? 'bg-purple-600' : 'bg-[#1e1e32]'"
                                            class="px-3 py-2 rounded text-sm text-gray-200 hover:bg-purple-500/50">
                                        Квадрат
                                    </button>
                                    <button @click="applyQuadPreset('trapezoid')"
                                            :class="selectedFigure.preset === 'trapezoid' ? 'bg-purple-600' : 'bg-[#1e1e32]'"
                                            class="px-3 py-2 rounded text-sm text-gray-200 hover:bg-purple-500/50">
                                        Трапеция
                                    </button>
                                    <button @click="applyQuadPreset('free')"
                                            :class="selectedFigure.preset === 'free' ? 'bg-purple-600' : 'bg-[#1e1e32]'"
                                            class="px-3 py-2 rounded text-sm text-gray-200 hover:bg-purple-500/50">
                                        Произвольный
                                    </button>
                                </div>
                            </div>

                            {{-- Angles --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3 flex items-center gap-2">
                                    <span>📐</span> Углы
                                </h3>
                                <div class="space-y-2">
                                    <template x-for="vName in ['A', 'B', 'C', 'D']" :key="'quad-angle-' + vName">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="w-8 text-orange-400 font-bold text-sm">∠<span x-text="selectedFigure.vertices[vName].label || vName"></span></span>
                                                <span class="text-gray-400 text-xs" x-text="Math.round(calculateQuadAngle(selectedFigure, vName)) + '°'"></span>
                                                <label class="flex items-center gap-1 text-xs text-gray-400 ml-auto">
                                                    <input type="checkbox"
                                                           :checked="selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].showArc"
                                                           @change="toggleQuadAngleArc(vName, $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    дуга
                                                </label>
                                                <label class="flex items-center gap-1 text-xs text-gray-400">
                                                    <input type="checkbox"
                                                           :checked="selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].showValue"
                                                           @change="toggleQuadAngleValue(vName, $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    °
                                                </label>
                                            </div>
                                            {{-- Arc radius slider --}}
                                            <div x-show="selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].showArc"
                                                 class="flex items-center gap-2 pl-8">
                                                <span class="text-[10px] text-gray-500 w-6">R</span>
                                                <input type="range" min="10" max="80" step="1"
                                                       :value="(selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].arcRadius) || 30"
                                                       @input="setArcRadius(vName, $event.target.value)"
                                                       class="flex-1 h-1 accent-orange-500">
                                                <span class="text-[10px] text-gray-500 w-6" x-text="(selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].arcRadius) || 30"></span>
                                            </div>
                                            {{-- Label offset controls --}}
                                            <div x-show="selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].showValue"
                                                 class="flex items-center gap-2 pl-8">
                                                <span class="text-[10px] text-gray-500">dx</span>
                                                <input type="number" min="-50" max="50"
                                                       :value="(selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].labelDx) || 0"
                                                       @change="setAngleLabelOffset(vName, 'dx', $event.target.value)"
                                                       class="w-12 px-1 py-0.5 text-[10px] bg-[#1e1e32] text-gray-300 rounded border border-gray-600">
                                                <span class="text-[10px] text-gray-500">dy</span>
                                                <input type="number" min="-50" max="50"
                                                       :value="(selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].labelDy) || 0"
                                                       @change="setAngleLabelOffset(vName, 'dy', $event.target.value)"
                                                       class="w-12 px-1 py-0.5 text-[10px] bg-[#1e1e32] text-gray-300 rounded border border-gray-600">
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Diagonals --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3 flex items-center gap-2">
                                    <span>📏</span> Диагонали
                                </h3>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-sm text-gray-300">
                                        <input type="checkbox"
                                               :checked="selectedFigure.lines && selectedFigure.lines.diagonal_ac && selectedFigure.lines.diagonal_ac.enabled"
                                               @change="toggleQuadLine('diagonal_ac', $event.target.checked)"
                                               class="rounded bg-gray-700 border-gray-600">
                                        Диагональ AC
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-300">
                                        <input type="checkbox"
                                               :checked="selectedFigure.lines && selectedFigure.lines.diagonal_bd && selectedFigure.lines.diagonal_bd.enabled"
                                               @change="toggleQuadLine('diagonal_bd', $event.target.checked)"
                                               class="rounded bg-gray-700 border-gray-600">
                                        Диагональ BD
                                    </label>
                                </div>
                            </div>

                            {{-- Auxiliary lines (bisectors, altitudes) --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3 flex items-center gap-2">
                                    <span>📐</span> Вспомогательные линии
                                </h3>
                                <div class="space-y-3">
                                    <template x-for="vName in ['A', 'B', 'C', 'D']" :key="'quad-lines-' + vName">
                                        <div class="space-y-1">
                                            <div class="text-xs text-gray-500 mb-1">Из вершины <span x-text="selectedFigure.vertices[vName].label || vName" class="text-orange-400"></span></div>
                                            <div class="flex flex-wrap gap-2">
                                                <label class="flex items-center gap-1 text-xs text-gray-300">
                                                    <input type="checkbox"
                                                           :checked="selectedFigure.lines && selectedFigure.lines['bisector_' + vName.toLowerCase()] && selectedFigure.lines['bisector_' + vName.toLowerCase()].enabled"
                                                           @change="toggleQuadLine('bisector_' + vName.toLowerCase(), $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    <span class="text-purple-400">Биссектриса</span>
                                                </label>
                                                <label class="flex items-center gap-1 text-xs text-gray-300">
                                                    <input type="checkbox"
                                                           :checked="selectedFigure.lines && selectedFigure.lines['altitude_' + vName.toLowerCase()] && selectedFigure.lines['altitude_' + vName.toLowerCase()].enabled"
                                                           @change="toggleQuadLine('altitude_' + vName.toLowerCase(), $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    <span class="text-green-400">Высота</span>
                                                </label>
                                            </div>
                                            {{-- Half-arc radius slider for quad bisector --}}
                                            <div x-show="selectedFigure.lines && selectedFigure.lines['bisector_' + vName.toLowerCase()] && selectedFigure.lines['bisector_' + vName.toLowerCase()].enabled"
                                                 class="flex items-center gap-2 pl-4 mt-1">
                                                <span class="text-[10px] text-gray-500 w-10">R дуг</span>
                                                <input type="range" min="10" max="80" step="1"
                                                       :value="(selectedFigure.lines && selectedFigure.lines['bisector_' + vName.toLowerCase()] && selectedFigure.lines['bisector_' + vName.toLowerCase()].halfArcRadius) || 30"
                                                       @input="setHalfArcRadius('bisector_' + vName.toLowerCase(), $event.target.value)"
                                                       class="flex-1 h-1 accent-amber-500">
                                                <span class="text-[10px] text-gray-500 w-6"
                                                      x-text="(selectedFigure.lines && selectedFigure.lines['bisector_' + vName.toLowerCase()] && selectedFigure.lines['bisector_' + vName.toLowerCase()].halfArcRadius) || 30"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Equal sides --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3 flex items-center gap-2">
                                    <span>═</span> Равные стороны
                                </h3>
                                <div class="space-y-2">
                                    <template x-for="group in [1, 2]" :key="'quad-equal-group-' + group">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-500 w-16">Группа <span x-text="group"></span>:</span>
                                            <template x-for="side in ['AB', 'BC', 'CD', 'DA']" :key="'quad-' + side">
                                                <label class="flex items-center gap-1 text-xs">
                                                    <input type="checkbox"
                                                           :checked="isQuadSideInEqualGroup(group, side)"
                                                           @change="toggleQuadSideInEqualGroup(group, side, $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    <span class="text-gray-300" x-text="side"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Stereometry properties --}}
                    <template x-if="selectedFigure && selectedFigure.type === 'stereometry'">
                        <div class="space-y-4">
                            {{-- Type indicator --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3 flex items-center gap-2">
                                    <span>🎲</span>
                                    <span x-text="getStereometryTypeName(selectedFigure)"></span>
                                </h3>

                                {{-- Polyhedra vertices --}}
                                <div x-show="selectedFigure.vertices" class="space-y-2 max-h-40 overflow-y-auto">
                                    <template x-for="(vertex, vName) in selectedFigure.vertices" :key="vName">
                                        <div class="flex items-center gap-2">
                                            <input type="text" :value="vertex.label || vName"
                                                   @change="updateVertexLabel(vName, $event.target.value)"
                                                   class="w-12 px-2 py-1 text-xs bg-[#1e1e32] text-orange-400 rounded border border-gray-600 text-center font-bold">
                                            <span class="text-gray-500 text-xs">x:</span>
                                            <input type="number" :value="Math.round(vertex.x)"
                                                   @change="updateVertexCoord(vName, 'x', $event.target.value)"
                                                   class="w-14 px-1 py-1 text-xs bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                            <span class="text-gray-500 text-xs">y:</span>
                                            <input type="number" :value="Math.round(vertex.y)"
                                                   @change="updateVertexCoord(vName, 'y', $event.target.value)"
                                                   class="w-14 px-1 py-1 text-xs bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                        </div>
                                    </template>
                                </div>

                                {{-- Cylinder/Cone/Sphere params --}}
                                <div x-show="selectedFigure.stereometryType === 'cylinder' || selectedFigure.stereometryType === 'cone'" class="space-y-2 mt-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Центр X:</span>
                                        <input type="number" :value="Math.round(selectedFigure.center?.x || 0)"
                                               @change="selectedFigure.center.x = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Центр Y:</span>
                                        <input type="number" :value="Math.round(selectedFigure.center?.y || 0)"
                                               @change="selectedFigure.center.y = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Радиус X:</span>
                                        <input type="number" :value="Math.round(selectedFigure.radiusX || 0)" min="10"
                                               @change="selectedFigure.radiusX = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Радиус Y:</span>
                                        <input type="number" :value="Math.round(selectedFigure.radiusY || 0)" min="5"
                                               @change="selectedFigure.radiusY = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Высота:</span>
                                        <input type="number" :value="Math.round(selectedFigure.height || 0)" min="10"
                                               @change="selectedFigure.height = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                </div>

                                {{-- Sphere params --}}
                                <div x-show="selectedFigure.stereometryType === 'sphere'" class="space-y-2 mt-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Центр X:</span>
                                        <input type="number" :value="Math.round(selectedFigure.center?.x || 0)"
                                               @change="selectedFigure.center.x = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Центр Y:</span>
                                        <input type="number" :value="Math.round(selectedFigure.center?.y || 0)"
                                               @change="selectedFigure.center.y = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Радиус:</span>
                                        <input type="number" :value="Math.round(selectedFigure.radius || 0)" min="10"
                                               @change="selectedFigure.radius = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                </div>
                            </div>

                            {{-- Edge visibility --}}
                            <div x-show="selectedFigure.edges" class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3">👁 Видимость рёбер</h3>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-sm text-gray-300">
                                        <input type="checkbox"
                                               :checked="selectedFigure.autoVisibility"
                                               @change="toggleAutoVisibility($event.target.checked)"
                                               class="rounded bg-gray-700 border-gray-600">
                                        Автоматически
                                    </label>
                                    <div x-show="!selectedFigure.autoVisibility" class="mt-2 space-y-1 max-h-32 overflow-y-auto">
                                        <template x-for="(edge, idx) in selectedFigure.edges" :key="idx">
                                            <label class="flex items-center gap-2 text-xs text-gray-400">
                                                <input type="checkbox"
                                                       :checked="edge.visible"
                                                       @change="toggleEdgeVisibility(idx, $event.target.checked)"
                                                       class="rounded bg-gray-700 border-gray-600">
                                                <span x-text="edge.from + ' → ' + edge.to"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Display options --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3">📐 Отображение</h3>
                                <div class="space-y-2">
                                    <label x-show="selectedFigure.stereometryType === 'cylinder' || selectedFigure.stereometryType === 'cone'"
                                           class="flex items-center gap-2 text-sm text-gray-300">
                                        <input type="checkbox"
                                               :checked="selectedFigure.showAxis"
                                               @change="selectedFigure.showAxis = $event.target.checked; saveState()"
                                               class="rounded bg-gray-700 border-gray-600">
                                        Показать ось
                                    </label>
                                    <label x-show="selectedFigure.stereometryType === 'sphere'"
                                           class="flex items-center gap-2 text-sm text-gray-300">
                                        <input type="checkbox"
                                               :checked="selectedFigure.showRadius"
                                               @change="selectedFigure.showRadius = $event.target.checked; saveState()"
                                               class="rounded bg-gray-700 border-gray-600">
                                        Показать радиус
                                    </label>
                                    <label x-show="selectedFigure.stereometryType === 'sphere'"
                                           class="flex items-center gap-2 text-sm text-gray-300">
                                        <input type="checkbox"
                                               :checked="selectedFigure.showMeridian"
                                               @change="selectedFigure.showMeridian = $event.target.checked; saveState()"
                                               class="rounded bg-gray-700 border-gray-600">
                                        Показать меридиан
                                    </label>
                                </div>
                            </div>

                            {{-- Sections --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3">✂️ Сечения</h3>
                                <button @click="addSection()"
                                        class="w-full px-3 py-2 bg-[#1e1e32] hover:bg-[#2a2a42] rounded text-sm text-gray-300">
                                    + Добавить сечение
                                </button>
                                <div class="mt-2 space-y-1">
                                    <template x-for="(section, idx) in (selectedFigure.sections || [])" :key="section.id">
                                        <div class="flex items-center justify-between text-xs text-gray-400 bg-[#1a1a2e] p-2 rounded">
                                            <span>Сечение <span x-text="idx + 1"></span></span>
                                            <button @click="removeSection(idx)"
                                                    class="text-red-400 hover:text-red-300">✕</button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Include geometry helpers --}}
<script src="{{ asset('js/geometry-helpers.js') }}"></script>
<script src="{{ asset('js/geometry-editor.js') }}"></script>
