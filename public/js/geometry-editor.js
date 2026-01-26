/**
 * PALOMATIKA SVG Geometry Editor
 * Интерактивный редактор геометрических фигур
 *
 * Зависимости: Alpine.js, geometry-helpers.js
 */

// Глобальная функция для открытия редактора
window.openGeometryEditor = function(taskId, existingSvg = null, metadata = null) {
    // Dispatch custom event that Alpine component listens for
    window.dispatchEvent(new CustomEvent('open-geometry-editor', {
        detail: { taskId, svg: existingSvg, metadata }
    }));
};

// Основной компонент Alpine.js
function geometryEditor() {
    return {
        // Состояние модального окна
        isOpen: false,
        taskId: '',
        mode: 'full_edit', // 'full_edit' | 'legacy_view'

        // Canvas
        canvasWidth: 600,
        canvasHeight: 500,
        showGrid: false,
        gridSize: 20,

        // Фигуры
        figures: [],
        selectedFigure: null,
        figureCounter: 0,

        // Drag & Drop
        draggingVertex: null,
        dragOffset: { x: 0, y: 0 },

        // Undo/Redo
        history: [],
        historyIndex: -1,
        maxHistory: 50,

        // Сохранение
        saving: false,

        // Computed
        get canUndo() {
            return this.historyIndex > 0;
        },
        get canRedo() {
            return this.historyIndex < this.history.length - 1;
        },

        // ==================== Lifecycle ====================

        init() {
            // Слушаем события открытия
            window.addEventListener('open-geometry-editor', (e) => {
                this.open(e.detail.taskId, e.detail.svg, e.detail.metadata);
            });
        },

        open(taskId, existingSvg = null, metadata = null) {
            this.taskId = taskId;
            this.isOpen = true;
            this.figures = [];
            this.selectedFigure = null;
            this.history = [];
            this.historyIndex = -1;
            this.figureCounter = 0;

            if (metadata && metadata.created_via === 'editor') {
                // Загружаем из метаданных
                this.mode = 'full_edit';
                this.loadFromMetadata(metadata);
            } else if (existingSvg) {
                // Legacy SVG
                this.mode = 'legacy_view';
            } else {
                // Новое изображение
                this.mode = 'full_edit';
                this.addDefaultTriangle();
            }

            this.saveState();
            document.body.style.overflow = 'hidden';
        },

        close() {
            this.isOpen = false;
            document.body.style.overflow = '';
        },

        // ==================== Figures ====================

        addFigure(type) {
            this.figureCounter++;
            const index = this.figures.filter(f => f.type === type).length + 1;
            let figure = null;

            switch (type) {
                case 'triangle':
                    figure = this.createTriangle(index);
                    break;
                case 'quadrilateral':
                    figure = this.createQuadrilateral(index);
                    break;
                case 'circle':
                    figure = this.createCircle(index);
                    break;
                case 'cube':
                    figure = this.createCube(index);
                    break;
                case 'prism':
                    figure = this.createPrism(index);
                    break;
                case 'pyramid':
                    figure = this.createPyramid(index);
                    break;
                case 'cylinder':
                    figure = this.createCylinder(index);
                    break;
                case 'cone':
                    figure = this.createCone(index);
                    break;
                case 'sphere':
                    figure = this.createSphere(index);
                    break;
            }

            if (figure) {
                this.figures.push(figure);
                this.selectedFigure = figure;
                this.saveState();
            }
        },

        createTriangle(index) {
            const suffix = index > 1 ? index : '';
            return {
                id: `triangle_${this.figureCounter}`,
                type: 'triangle',
                preset: 'free',
                vertices: {
                    A: { x: 100, y: 400, label: `A${suffix ? '₊' + suffix : ''}` },
                    B: { x: 500, y: 400, label: `B${suffix ? '₊' + suffix : ''}` },
                    C: { x: 300, y: 100, label: `C${suffix ? '₊' + suffix : ''}` }
                },
                angles: {
                    A: { value: null, showArc: false, arcType: 'single', showValue: false },
                    B: { value: null, showArc: false, arcType: 'single', showValue: false },
                    C: { value: null, showArc: false, arcType: 'single', showValue: false }
                },
                lines: {
                    bisector_a: { enabled: false, intersectionLabel: 'D' },
                    bisector_b: { enabled: false, intersectionLabel: 'E' },
                    bisector_c: { enabled: false, intersectionLabel: 'F' },
                    median_a: { enabled: false, intersectionLabel: 'M' },
                    median_b: { enabled: false, intersectionLabel: 'N' },
                    median_c: { enabled: false, intersectionLabel: 'P' },
                    altitude_a: { enabled: false, intersectionLabel: 'H' },
                    altitude_b: { enabled: false, intersectionLabel: 'K' },
                    altitude_c: { enabled: false, intersectionLabel: 'L' }
                },
                equalGroups: { sides: [], angles: [] }
            };
        },

        createQuadrilateral(index) {
            const suffix = index > 1 ? index : '';
            return {
                id: `quad_${this.figureCounter}`,
                type: 'quadrilateral',
                preset: 'free',
                vertices: {
                    A: { x: 100, y: 350, label: `A${suffix ? '₊' + suffix : ''}` },
                    B: { x: 500, y: 350, label: `B${suffix ? '₊' + suffix : ''}` },
                    C: { x: 450, y: 100, label: `C${suffix ? '₊' + suffix : ''}` },
                    D: { x: 150, y: 100, label: `D${suffix ? '₊' + suffix : ''}` }
                },
                angles: {},
                lines: {},
                equalGroups: { sides: [], angles: [] }
            };
        },

        createCircle(index) {
            return {
                id: `circle_${this.figureCounter}`,
                type: 'circle',
                center: { x: 300, y: 250 },
                radius: 120,
                centerLabel: index > 1 ? `O${index}` : 'O',
                showDiameter: false,
                showRadius: false,
                chords: [],
                tangents: [],
                secants: [],
                inscribedAngles: []
            };
        },

        createCube(index) {
            const ox = 100, oy = 300; // Origin
            const s = 150; // Size
            const d = 60; // Depth offset
            return {
                id: `cube_${this.figureCounter}`,
                type: 'stereometry',
                stereometryType: 'cube',
                vertices: {
                    A: { x: ox, y: oy, label: 'A', visible: true },
                    B: { x: ox + s, y: oy, label: 'B', visible: true },
                    C: { x: ox + s, y: oy - s, label: 'C', visible: true },
                    D: { x: ox, y: oy - s, label: 'D', visible: true },
                    A1: { x: ox + d, y: oy - d, label: 'A₁', visible: true },
                    B1: { x: ox + s + d, y: oy - d, label: 'B₁', visible: true },
                    C1: { x: ox + s + d, y: oy - s - d, label: 'C₁', visible: true },
                    D1: { x: ox + d, y: oy - s - d, label: 'D₁', visible: true }
                },
                edges: [
                    // Bottom face
                    { from: 'A', to: 'B', visible: true },
                    { from: 'B', to: 'C', visible: true },
                    { from: 'C', to: 'D', visible: true },
                    { from: 'D', to: 'A', visible: true },
                    // Top face
                    { from: 'A1', to: 'B1', visible: true },
                    { from: 'B1', to: 'C1', visible: true },
                    { from: 'C1', to: 'D1', visible: true },
                    { from: 'D1', to: 'A1', visible: true },
                    // Vertical edges
                    { from: 'A', to: 'A1', visible: false }, // Hidden
                    { from: 'B', to: 'B1', visible: true },
                    { from: 'C', to: 'C1', visible: true },
                    { from: 'D', to: 'D1', visible: true }
                ],
                autoVisibility: true
            };
        },

        createPrism(index) {
            const ox = 150, oy = 350;
            const s = 120;
            const h = 180;
            const d = 50;
            return {
                id: `prism_${this.figureCounter}`,
                type: 'stereometry',
                stereometryType: 'prism',
                vertices: {
                    A: { x: ox, y: oy, label: 'A', visible: true },
                    B: { x: ox + s, y: oy, label: 'B', visible: true },
                    C: { x: ox + s/2, y: oy - s*0.866, label: 'C', visible: true },
                    A1: { x: ox + d, y: oy - h, label: 'A₁', visible: true },
                    B1: { x: ox + s + d, y: oy - h, label: 'B₁', visible: true },
                    C1: { x: ox + s/2 + d, y: oy - h - s*0.866, label: 'C₁', visible: true }
                },
                edges: [
                    { from: 'A', to: 'B', visible: true },
                    { from: 'B', to: 'C', visible: true },
                    { from: 'C', to: 'A', visible: false },
                    { from: 'A1', to: 'B1', visible: true },
                    { from: 'B1', to: 'C1', visible: true },
                    { from: 'C1', to: 'A1', visible: true },
                    { from: 'A', to: 'A1', visible: false },
                    { from: 'B', to: 'B1', visible: true },
                    { from: 'C', to: 'C1', visible: true }
                ],
                autoVisibility: true
            };
        },

        createPyramid(index) {
            const ox = 150, oy = 400;
            const s = 200;
            const d = 80;
            return {
                id: `pyramid_${this.figureCounter}`,
                type: 'stereometry',
                stereometryType: 'pyramid',
                vertices: {
                    A: { x: ox, y: oy, label: 'A', visible: true },
                    B: { x: ox + s, y: oy, label: 'B', visible: true },
                    C: { x: ox + s + d, y: oy - s*0.5, label: 'C', visible: true },
                    D: { x: ox + d, y: oy - s*0.5, label: 'D', visible: true },
                    S: { x: ox + s/2 + d/2, y: oy - s - 50, label: 'S', visible: true }
                },
                edges: [
                    { from: 'A', to: 'B', visible: true },
                    { from: 'B', to: 'C', visible: true },
                    { from: 'C', to: 'D', visible: true },
                    { from: 'D', to: 'A', visible: false },
                    { from: 'A', to: 'S', visible: true },
                    { from: 'B', to: 'S', visible: true },
                    { from: 'C', to: 'S', visible: true },
                    { from: 'D', to: 'S', visible: true }
                ],
                autoVisibility: true
            };
        },

        createCylinder(index) {
            return {
                id: `cylinder_${this.figureCounter}`,
                type: 'stereometry',
                stereometryType: 'cylinder',
                center: { x: 300, y: 350 },
                radiusX: 100,
                radiusY: 30,
                height: 200
            };
        },

        createCone(index) {
            return {
                id: `cone_${this.figureCounter}`,
                type: 'stereometry',
                stereometryType: 'cone',
                center: { x: 300, y: 400 },
                radiusX: 100,
                radiusY: 30,
                height: 250,
                apex: { x: 300, y: 150 }
            };
        },

        createSphere(index) {
            return {
                id: `sphere_${this.figureCounter}`,
                type: 'stereometry',
                stereometryType: 'sphere',
                center: { x: 300, y: 250 },
                radius: 120
            };
        },

        addDefaultTriangle() {
            this.addFigure('triangle');
        },

        selectFigure(figure) {
            this.selectedFigure = figure;
        },

        deleteSelected() {
            if (!this.selectedFigure) return;
            const index = this.figures.findIndex(f => f.id === this.selectedFigure.id);
            if (index !== -1) {
                this.figures.splice(index, 1);
                this.selectedFigure = this.figures.length > 0 ? this.figures[0] : null;
                this.saveState();
            }
        },

        // ==================== Triangle Presets ====================

        applyPreset(preset) {
            if (!this.selectedFigure || this.selectedFigure.type !== 'triangle') return;

            const cx = this.canvasWidth / 2;
            const cy = this.canvasHeight / 2;
            const size = 200;

            switch (preset) {
                case 'isosceles':
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: cx - size, y: cy + size/2 };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: cx + size, y: cy + size/2 };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: cx, y: cy - size };
                    this.selectedFigure.preset = 'isosceles';
                    break;

                case 'equilateral':
                    const h = size * Math.sqrt(3) / 2;
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: cx - size, y: cy + h/2 };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: cx + size, y: cy + h/2 };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: cx, y: cy - h };
                    this.selectedFigure.preset = 'equilateral';
                    break;

                case 'right':
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: cx - size, y: cy + size };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: cx + size, y: cy + size };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: cx - size, y: cy - size };
                    this.selectedFigure.preset = 'right';
                    break;

                case 'free':
                    this.selectedFigure.preset = 'free';
                    break;
            }

            this.saveState();
        },

        // ==================== Quadrilateral Presets ====================

        applyQuadPreset(preset) {
            if (!this.selectedFigure || this.selectedFigure.type !== 'quadrilateral') return;

            const cx = this.canvasWidth / 2;
            const cy = this.canvasHeight / 2;
            const w = 180, h = 120;

            switch (preset) {
                case 'parallelogram':
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: cx - w - 40, y: cy + h };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: cx + w - 40, y: cy + h };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: cx + w + 40, y: cy - h };
                    this.selectedFigure.vertices.D = { ...this.selectedFigure.vertices.D, x: cx - w + 40, y: cy - h };
                    this.selectedFigure.preset = 'parallelogram';
                    break;

                case 'rectangle':
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: cx - w, y: cy + h };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: cx + w, y: cy + h };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: cx + w, y: cy - h };
                    this.selectedFigure.vertices.D = { ...this.selectedFigure.vertices.D, x: cx - w, y: cy - h };
                    this.selectedFigure.preset = 'rectangle';
                    break;

                case 'rhombus':
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: cx - w, y: cy };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: cx, y: cy + h };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: cx + w, y: cy };
                    this.selectedFigure.vertices.D = { ...this.selectedFigure.vertices.D, x: cx, y: cy - h };
                    this.selectedFigure.preset = 'rhombus';
                    break;

                case 'square':
                    const s = 150;
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: cx - s, y: cy + s };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: cx + s, y: cy + s };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: cx + s, y: cy - s };
                    this.selectedFigure.vertices.D = { ...this.selectedFigure.vertices.D, x: cx - s, y: cy - s };
                    this.selectedFigure.preset = 'square';
                    break;

                case 'trapezoid':
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: cx - w - 60, y: cy + h };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: cx + w + 60, y: cy + h };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: cx + w - 40, y: cy - h };
                    this.selectedFigure.vertices.D = { ...this.selectedFigure.vertices.D, x: cx - w + 40, y: cy - h };
                    this.selectedFigure.preset = 'trapezoid';
                    break;

                case 'free':
                    this.selectedFigure.preset = 'free';
                    break;
            }

            this.saveState();
        },

        // ==================== Drag & Drop ====================

        startDragVertex(figure, vertexName, event) {
            this.draggingVertex = { figure, vertex: vertexName };
            this.selectedFigure = figure;

            const svg = document.getElementById('geometry-canvas');
            const pt = this.getSvgPoint(svg, event);

            this.dragOffset = {
                x: pt.x - figure.vertices[vertexName].x,
                y: pt.y - figure.vertices[vertexName].y
            };
        },

        startDragCenter(figure, event) {
            this.draggingVertex = { figure, vertex: 'center' };
            this.selectedFigure = figure;

            const svg = document.getElementById('geometry-canvas');
            const pt = this.getSvgPoint(svg, event);

            this.dragOffset = {
                x: pt.x - figure.center.x,
                y: pt.y - figure.center.y
            };
        },

        onCanvasMouseDown(event) {
            // Handle figure selection via event delegation
            const figureGroup = event.target.closest('[data-figure-id]');
            if (figureGroup) {
                const figureId = figureGroup.getAttribute('data-figure-id');
                const figure = this.figures.find(f => f.id === figureId);
                if (figure) {
                    this.selectFigure(figure);

                    // Check if clicked on a vertex
                    const vertexCircle = event.target.closest('[data-vertex]');
                    if (vertexCircle) {
                        const vertexName = vertexCircle.getAttribute('data-vertex');
                        this.startDragVertex(figure, vertexName, event);
                    }
                    return;
                }
            }

            // Deselect if clicking on empty space
            if (event.target.id === 'geometry-canvas' || event.target.tagName === 'rect') {
                this.selectedFigure = null;
            }
        },

        onCanvasMouseMove(event) {
            if (!this.draggingVertex) return;

            const svg = document.getElementById('geometry-canvas');
            const pt = this.getSvgPoint(svg, event);

            let newX = pt.x - this.dragOffset.x;
            let newY = pt.y - this.dragOffset.y;

            // Snap to grid
            if (this.showGrid) {
                newX = Math.round(newX / this.gridSize) * this.gridSize;
                newY = Math.round(newY / this.gridSize) * this.gridSize;
            }

            // Clamp to canvas bounds
            newX = Math.max(20, Math.min(this.canvasWidth - 20, newX));
            newY = Math.max(20, Math.min(this.canvasHeight - 20, newY));

            const figure = this.draggingVertex.figure;
            const vertex = this.draggingVertex.vertex;
            const dragType = this.draggingVertex.type;

            // Handle different drag types
            if (dragType === 'chord') {
                const chord = this.draggingVertex.chord;
                const pointKey = this.draggingVertex.pointKey;
                // Constrain to circle
                const cx = figure.center.x;
                const cy = figure.center.y;
                const r = figure.radius;
                const dx = newX - cx;
                const dy = newY - cy;
                const dist = Math.sqrt(dx * dx + dy * dy);
                chord[pointKey].x = cx + (dx / dist) * r;
                chord[pointKey].y = cy + (dy / dist) * r;
            } else if (dragType === 'tangent') {
                const tangent = this.draggingVertex.tangent;
                tangent.externalPoint.x = newX;
                tangent.externalPoint.y = newY;
            } else if (dragType === 'secant') {
                const secant = this.draggingVertex.secant;
                const pointKey = this.draggingVertex.pointKey;
                secant[pointKey].x = newX;
                secant[pointKey].y = newY;
            } else if (dragType === 'apex') {
                figure.apex.x = newX;
                figure.apex.y = newY;
                // Also update height
                figure.height = figure.center.y - newY;
            } else if (vertex === 'center' && figure.center) {
                figure.center.x = newX;
                figure.center.y = newY;
            } else if (figure.vertices && figure.vertices[vertex]) {
                // Check preset constraints
                if (figure.preset && figure.preset !== 'free') {
                    this.moveVertexWithConstraints(figure, vertex, newX, newY);
                } else {
                    figure.vertices[vertex].x = newX;
                    figure.vertices[vertex].y = newY;
                }
            }
        },

        onCanvasMouseUp(event) {
            if (this.draggingVertex) {
                this.saveState();
                this.draggingVertex = null;
            }
        },

        moveVertexWithConstraints(figure, vertexName, newX, newY) {
            if (figure.type === 'triangle') {
                switch (figure.preset) {
                    case 'isosceles':
                        this.moveIsoscelesVertex(figure, vertexName, newX, newY);
                        break;
                    case 'equilateral':
                        this.moveEquilateralVertex(figure, vertexName, newX, newY);
                        break;
                    case 'right':
                        this.moveRightTriangleVertex(figure, vertexName, newX, newY);
                        break;
                    default:
                        figure.vertices[vertexName].x = newX;
                        figure.vertices[vertexName].y = newY;
                }
            } else if (figure.type === 'quadrilateral') {
                switch (figure.preset) {
                    case 'parallelogram':
                        this.moveParallelogramVertex(figure, vertexName, newX, newY);
                        break;
                    case 'rectangle':
                        this.moveRectangleVertex(figure, vertexName, newX, newY);
                        break;
                    case 'rhombus':
                        this.moveRhombusVertex(figure, vertexName, newX, newY);
                        break;
                    case 'square':
                        this.moveSquareVertex(figure, vertexName, newX, newY);
                        break;
                    case 'trapezoid':
                        this.moveTrapezoidVertex(figure, vertexName, newX, newY);
                        break;
                    default:
                        figure.vertices[vertexName].x = newX;
                        figure.vertices[vertexName].y = newY;
                }
            } else {
                figure.vertices[vertexName].x = newX;
                figure.vertices[vertexName].y = newY;
            }
        },

        // Равнобедренный: AC = BC
        moveIsoscelesVertex(figure, vertex, newX, newY) {
            const v = figure.vertices;
            if (vertex === 'C') {
                // Вершина на оси симметрии
                const midX = (v.A.x + v.B.x) / 2;
                v.C.x = midX;
                v.C.y = newY;
            } else if (vertex === 'A') {
                v.A.x = newX;
                v.A.y = newY;
                // B симметричен A относительно вертикали через C
                const midX = v.C.x;
                v.B.x = 2 * midX - v.A.x;
                v.B.y = v.A.y;
            } else if (vertex === 'B') {
                v.B.x = newX;
                v.B.y = newY;
                const midX = v.C.x;
                v.A.x = 2 * midX - v.B.x;
                v.A.y = v.B.y;
            }
        },

        // Равносторонний: все стороны равны
        moveEquilateralVertex(figure, vertex, newX, newY) {
            const v = figure.vertices;
            // Упрощённая логика: масштабируем от центра
            const center = window.centroid(v.A, v.B, v.C);
            const dx = newX - center.x;
            const dy = newY - center.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            const side = dist * Math.sqrt(3);

            // Перестраиваем равносторонний треугольник
            const h = side * Math.sqrt(3) / 2;
            v.A.x = center.x - side / 2;
            v.A.y = center.y + h / 3;
            v.B.x = center.x + side / 2;
            v.B.y = center.y + h / 3;
            v.C.x = center.x;
            v.C.y = center.y - 2 * h / 3;
        },

        // Прямоугольный: угол C = 90°
        moveRightTriangleVertex(figure, vertex, newX, newY) {
            const v = figure.vertices;
            if (vertex === 'C') {
                // C должен оставаться так, чтобы угол был 90°
                // Упрощение: фиксируем C в позиции, где AC ⊥ BC
                v.C.x = v.A.x;
                v.C.y = newY;
            } else {
                v[vertex].x = newX;
                v[vertex].y = newY;
            }
        },

        // Параллелограмм: AB || CD, AD || BC
        moveParallelogramVertex(figure, vertex, newX, newY) {
            const v = figure.vertices;
            const dx = newX - v[vertex].x;
            const dy = newY - v[vertex].y;

            v[vertex].x = newX;
            v[vertex].y = newY;

            // Противоположная вершина двигается так же
            const opposite = { A: 'C', B: 'D', C: 'A', D: 'B' };
            v[opposite[vertex]].x += dx;
            v[opposite[vertex]].y += dy;
        },

        // Прямоугольник: все углы 90°
        moveRectangleVertex(figure, vertex, newX, newY) {
            const v = figure.vertices;
            const adjacent = {
                A: ['D', 'B'], B: ['A', 'C'], C: ['B', 'D'], D: ['C', 'A']
            };

            v[vertex].x = newX;
            v[vertex].y = newY;

            // Соседние вершины корректируются
            const [v1, v2] = adjacent[vertex];
            if (vertex === 'A' || vertex === 'C') {
                v.D.x = v.A.x;
                v.B.x = v.C.x;
                v.A.y = v.B.y;
                v.D.y = v.C.y;
            } else {
                v.A.x = v.D.x;
                v.C.x = v.B.x;
                v.A.y = v.B.y;
                v.D.y = v.C.y;
            }
        },

        // Ромб: все стороны равны
        moveRhombusVertex(figure, vertex, newX, newY) {
            const v = figure.vertices;
            const center = {
                x: (v.A.x + v.C.x) / 2,
                y: (v.A.y + v.C.y) / 2
            };

            // Симметрия относительно центра
            if (vertex === 'A' || vertex === 'C') {
                v[vertex].x = newX;
                v[vertex].y = newY;
                const opp = vertex === 'A' ? 'C' : 'A';
                v[opp].x = 2 * center.x - v[vertex].x;
                v[opp].y = 2 * center.y - v[vertex].y;
            } else {
                v[vertex].x = newX;
                v[vertex].y = newY;
                const opp = vertex === 'B' ? 'D' : 'B';
                v[opp].x = 2 * center.x - v[vertex].x;
                v[opp].y = 2 * center.y - v[vertex].y;
            }
        },

        // Квадрат: все стороны равны + все углы 90°
        moveSquareVertex(figure, vertex, newX, newY) {
            // Аналогично прямоугольнику, но сохраняем равенство сторон
            this.moveRectangleVertex(figure, vertex, newX, newY);
        },

        // Трапеция: одна пара параллельных сторон (AB || CD)
        moveTrapezoidVertex(figure, vertex, newX, newY) {
            const v = figure.vertices;
            v[vertex].x = newX;
            v[vertex].y = newY;

            // Сохраняем параллельность AB и CD
            if (vertex === 'A' || vertex === 'B') {
                // Нижнее основание — свободно
            } else {
                // Верхнее основание — поддерживаем параллельность
                v.C.y = v.D.y;
            }
        },

        getSvgPoint(svg, event) {
            const rect = svg.getBoundingClientRect();
            const scaleX = this.canvasWidth / rect.width;
            const scaleY = this.canvasHeight / rect.height;
            return {
                x: (event.clientX - rect.left) * scaleX,
                y: (event.clientY - rect.top) * scaleY
            };
        },

        // ==================== SVG Rendering (x-html approach for SVG compatibility) ====================

        renderAllFigures() {
            let svg = '';
            this.figures.forEach((figure, index) => {
                const isSelected = this.selectedFigure && this.selectedFigure.id === figure.id;
                const strokeColor = isSelected ? '#a855f7' : '#8b5cf6';

                svg += `<g class="${isSelected ? 'selected-figure' : ''}" data-figure-id="${figure.id}">`;

                if (figure.type === 'triangle') {
                    svg += this.renderTriangle(figure, strokeColor);
                } else if (figure.type === 'quadrilateral') {
                    svg += this.renderQuadrilateral(figure, strokeColor);
                } else if (figure.type === 'circle') {
                    svg += this.renderCircle(figure, isSelected);
                } else if (figure.type === 'stereometry') {
                    svg += this.renderStereometry(figure, strokeColor);
                }

                svg += '</g>';
            });
            return svg;
        },

        renderTriangle(figure, strokeColor) {
            const v = figure.vertices;
            const points = `${v.A.x},${v.A.y} ${v.B.x},${v.B.y} ${v.C.x},${v.C.y}`;
            let svg = `<polygon points="${points}" fill="none" stroke="${strokeColor}" stroke-width="2"/>`;

            // Vertex points and labels
            ['A', 'B', 'C'].forEach(vName => {
                const vertex = v[vName];
                const labelPos = this.getLabelPosition(figure, vName);
                svg += `<circle cx="${vertex.x}" cy="${vertex.y}" r="6" fill="#f97316" class="cursor-grab" data-vertex="${vName}"/>`;
                svg += `<text x="${labelPos.x}" y="${labelPos.y}" fill="#f97316" font-size="16" font-weight="bold" font-style="italic" text-anchor="middle" dominant-baseline="middle">${vertex.label || vName}</text>`;
            });

            return svg;
        },

        renderQuadrilateral(figure, strokeColor) {
            const v = figure.vertices;
            const points = `${v.A.x},${v.A.y} ${v.B.x},${v.B.y} ${v.C.x},${v.C.y} ${v.D.x},${v.D.y}`;
            let svg = `<polygon points="${points}" fill="none" stroke="${strokeColor}" stroke-width="2"/>`;

            // Vertex points and labels
            ['A', 'B', 'C', 'D'].forEach(vName => {
                const vertex = v[vName];
                const labelPos = this.getLabelPositionQuad(figure, vName);
                svg += `<circle cx="${vertex.x}" cy="${vertex.y}" r="6" fill="#f97316" class="cursor-grab" data-vertex="${vName}"/>`;
                svg += `<text x="${labelPos.x}" y="${labelPos.y}" fill="#f97316" font-size="16" font-weight="bold" font-style="italic" text-anchor="middle" dominant-baseline="middle">${vertex.label || vName}</text>`;
            });

            return svg;
        },

        renderCircle(figure, isSelected) {
            const strokeColor = isSelected ? '#a855f7' : '#5a9fcf';
            let svg = `<circle cx="${figure.center.x}" cy="${figure.center.y}" r="${figure.radius}" fill="none" stroke="${strokeColor}" stroke-width="2"/>`;

            // Center point and label
            svg += `<circle cx="${figure.center.x}" cy="${figure.center.y}" r="4" fill="#f97316" class="cursor-grab"/>`;
            svg += `<text x="${figure.center.x + 12}" y="${figure.center.y - 12}" fill="#f97316" font-size="14" font-weight="bold">${figure.centerLabel || 'O'}</text>`;

            return svg;
        },

        renderStereometry(figure, strokeColor) {
            let svg = '';

            if (figure.edges && figure.vertices) {
                // Hidden edges (dashed)
                figure.edges.filter(e => !e.visible).forEach(edge => {
                    const from = figure.vertices[edge.from];
                    const to = figure.vertices[edge.to];
                    if (from && to) {
                        svg += `<line x1="${from.x}" y1="${from.y}" x2="${to.x}" y2="${to.y}" stroke="#6b7280" stroke-width="1.5" stroke-dasharray="5,5"/>`;
                    }
                });

                // Visible edges
                figure.edges.filter(e => e.visible).forEach(edge => {
                    const from = figure.vertices[edge.from];
                    const to = figure.vertices[edge.to];
                    if (from && to) {
                        svg += `<line x1="${from.x}" y1="${from.y}" x2="${to.x}" y2="${to.y}" stroke="${strokeColor}" stroke-width="2"/>`;
                    }
                });

                // Vertices
                Object.entries(figure.vertices).forEach(([vName, vertex]) => {
                    const labelPos = this.getStereometryLabelPos(figure, vName);
                    const color = vertex.visible !== false ? '#f97316' : '#6b7280';
                    svg += `<circle cx="${vertex.x}" cy="${vertex.y}" r="5" fill="${color}" class="cursor-grab" data-vertex="${vName}"/>`;
                    svg += `<text x="${labelPos.x}" y="${labelPos.y}" fill="${color}" font-size="14" font-weight="bold" font-style="italic" text-anchor="middle" dominant-baseline="middle">${vertex.label || vName}</text>`;
                });
            } else if (figure.stereometryType === 'cylinder') {
                svg += this.renderCylinder(figure);
            } else if (figure.stereometryType === 'cone') {
                svg += this.renderCone(figure);
            } else if (figure.stereometryType === 'sphere') {
                svg += this.renderSphere(figure);
            }

            return svg;
        },

        renderCylinder(figure) {
            const cx = figure.center.x;
            const cy = figure.center.y;
            const rx = figure.radiusX;
            const ry = figure.radiusY;
            const h = figure.height;

            return `
                <path d="M ${cx - rx} ${cy} A ${rx} ${ry} 0 0 0 ${cx + rx} ${cy}" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-dasharray="5,5"/>
                <ellipse cx="${cx}" cy="${cy}" rx="${rx}" ry="${ry}" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                <line x1="${cx - rx}" y1="${cy}" x2="${cx - rx}" y2="${cy - h}" stroke="#8b5cf6" stroke-width="2"/>
                <line x1="${cx + rx}" y1="${cy}" x2="${cx + rx}" y2="${cy - h}" stroke="#8b5cf6" stroke-width="2"/>
                <ellipse cx="${cx}" cy="${cy - h}" rx="${rx}" ry="${ry}" fill="none" stroke="#8b5cf6" stroke-width="2"/>
            `;
        },

        renderCone(figure) {
            const cx = figure.center.x;
            const cy = figure.center.y;
            const rx = figure.radiusX;
            const ry = figure.radiusY;
            const apex = figure.apex;

            return `
                <path d="M ${cx - rx} ${cy} A ${rx} ${ry} 0 0 0 ${cx + rx} ${cy}" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-dasharray="5,5"/>
                <ellipse cx="${cx}" cy="${cy}" rx="${rx}" ry="${ry}" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                <line x1="${cx - rx}" y1="${cy}" x2="${apex.x}" y2="${apex.y}" stroke="#8b5cf6" stroke-width="2"/>
                <line x1="${cx + rx}" y1="${cy}" x2="${apex.x}" y2="${apex.y}" stroke="#8b5cf6" stroke-width="2"/>
                <circle cx="${apex.x}" cy="${apex.y}" r="5" fill="#f97316" class="cursor-grab"/>
            `;
        },

        renderSphere(figure) {
            const cx = figure.center.x;
            const cy = figure.center.y;
            const r = figure.radius;
            const ry = r * 0.3;

            return `
                <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                <path d="M ${cx - r} ${cy} A ${r} ${ry} 0 0 0 ${cx + r} ${cy}" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-dasharray="5,5"/>
                <ellipse cx="${cx}" cy="${cy}" rx="${r}" ry="${ry}" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                <circle cx="${cx}" cy="${cy}" r="4" fill="#f97316"/>
            `;
        },

        // ==================== Geometry Helpers ====================

        getTrianglePoints(figure) {
            const v = figure.vertices;
            return `${v.A.x},${v.A.y} ${v.B.x},${v.B.y} ${v.C.x},${v.C.y}`;
        },

        getQuadrilateralPoints(figure) {
            const v = figure.vertices;
            return `${v.A.x},${v.A.y} ${v.B.x},${v.B.y} ${v.C.x},${v.C.y} ${v.D.x},${v.D.y}`;
        },

        getTriangleCenter(figure) {
            const v = figure.vertices;
            return window.centroid(v.A, v.B, v.C);
        },

        getLabelPosition(figure, vertexName) {
            const v = figure.vertices;
            const center = this.getTriangleCenter(figure);
            const point = v[vertexName];
            return window.labelPos(point, center, 22);
        },

        getLabelPositionQuad(figure, vertexName) {
            const v = figure.vertices;
            const center = {
                x: (v.A.x + v.B.x + v.C.x + v.D.x) / 4,
                y: (v.A.y + v.B.y + v.C.y + v.D.y) / 4
            };
            const point = v[vertexName];
            return window.labelPos(point, center, 22);
        },

        // ==================== Angles ====================

        getAngleValue(figure, vertexName) {
            if (figure.angles && figure.angles[vertexName] && figure.angles[vertexName].value !== null) {
                return figure.angles[vertexName].value;
            }
            // Calculate from vertices
            return Math.round(this.calculateAngle(figure, vertexName));
        },

        calculateAngle(figure, vertexName) {
            const v = figure.vertices;
            let vertex, p1, p2;

            if (vertexName === 'A') {
                vertex = v.A; p1 = v.B; p2 = v.C;
            } else if (vertexName === 'B') {
                vertex = v.B; p1 = v.A; p2 = v.C;
            } else {
                vertex = v.C; p1 = v.A; p2 = v.B;
            }

            const v1 = { x: p1.x - vertex.x, y: p1.y - vertex.y };
            const v2 = { x: p2.x - vertex.x, y: p2.y - vertex.y };

            const dot = v1.x * v2.x + v1.y * v2.y;
            const len1 = Math.sqrt(v1.x * v1.x + v1.y * v1.y);
            const len2 = Math.sqrt(v2.x * v2.x + v2.y * v2.y);

            const cos = dot / (len1 * len2);
            return Math.acos(Math.max(-1, Math.min(1, cos))) * 180 / Math.PI;
        },

        isVertexRightAngle(figure, vertexName) {
            const angle = this.calculateAngle(figure, vertexName);
            return Math.abs(angle - 90) < 1;
        },

        getAngleArc(figure, vertexName) {
            const v = figure.vertices;
            let vertex, p1, p2;

            if (vertexName === 'A') {
                vertex = v.A; p1 = v.B; p2 = v.C;
            } else if (vertexName === 'B') {
                vertex = v.B; p1 = v.A; p2 = v.C;
            } else {
                vertex = v.C; p1 = v.A; p2 = v.B;
            }

            return window.makeAngleArc(vertex, p1, p2, 25);
        },

        getRightAnglePath(figure, vertexName) {
            const v = figure.vertices;
            let vertex, p1, p2;

            if (vertexName === 'A') {
                vertex = v.A; p1 = v.B; p2 = v.C;
            } else if (vertexName === 'B') {
                vertex = v.B; p1 = v.A; p2 = v.C;
            } else {
                vertex = v.C; p1 = v.A; p2 = v.B;
            }

            return window.rightAnglePath(vertex, p1, p2, 12);
        },

        getAngleLabelPos(figure, vertexName) {
            const v = figure.vertices;
            let vertex, p1, p2;

            if (vertexName === 'A') {
                vertex = v.A; p1 = v.B; p2 = v.C;
            } else if (vertexName === 'B') {
                vertex = v.B; p1 = v.A; p2 = v.C;
            } else {
                vertex = v.C; p1 = v.A; p2 = v.B;
            }

            return window.angleLabelPos(vertex, p1, p2, 42, 0.5);
        },

        setAngleValue(vertexName, value) {
            if (!this.selectedFigure || this.selectedFigure.type !== 'triangle') return;

            const angle = parseInt(value);
            if (isNaN(angle) || angle <= 0 || angle >= 180) return;

            if (!this.selectedFigure.angles) {
                this.selectedFigure.angles = {};
            }
            if (!this.selectedFigure.angles[vertexName]) {
                this.selectedFigure.angles[vertexName] = { showArc: false, showValue: false };
            }
            this.selectedFigure.angles[vertexName].value = angle;

            // Перестраиваем треугольник
            this.rebuildTriangleForAngle(this.selectedFigure, vertexName, angle);
            this.saveState();
        },

        rebuildTriangleForAngle(figure, vertexName, targetAngle) {
            // Сложный алгоритм перестроения — упрощённая версия
            // Фиксируем одну сторону и двигаем вершину
            const v = figure.vertices;

            if (vertexName === 'C') {
                // Фиксируем AB, двигаем C
                const midAB = { x: (v.A.x + v.B.x) / 2, y: (v.A.y + v.B.y) / 2 };
                const AB = window.distance(v.A, v.B);
                const angleRad = targetAngle * Math.PI / 180;

                // Вписанный угол опирается на хорду AB
                // Радиус дуги = AB / (2 * sin(angle))
                const R = AB / (2 * Math.sin(angleRad));

                // C находится на дуге над AB
                v.C.x = midAB.x;
                v.C.y = midAB.y - Math.sqrt(R * R - (AB/2) * (AB/2));
            }
        },

        toggleAngleArc(vertexName, checked) {
            if (!this.selectedFigure || !this.selectedFigure.angles) return;
            if (!this.selectedFigure.angles[vertexName]) {
                this.selectedFigure.angles[vertexName] = { showArc: false, showValue: false };
            }
            this.selectedFigure.angles[vertexName].showArc = checked;
            this.saveState();
        },

        toggleAngleValue(vertexName, checked) {
            if (!this.selectedFigure || !this.selectedFigure.angles) return;
            if (!this.selectedFigure.angles[vertexName]) {
                this.selectedFigure.angles[vertexName] = { showArc: false, showValue: false };
            }
            this.selectedFigure.angles[vertexName].showValue = checked;
            this.saveState();
        },

        // ==================== Lines ====================

        toggleLine(lineKey, enabled) {
            if (!this.selectedFigure) return;
            if (!this.selectedFigure.lines) {
                this.selectedFigure.lines = {};
            }
            if (!this.selectedFigure.lines[lineKey]) {
                this.selectedFigure.lines[lineKey] = { enabled: false };
            }
            this.selectedFigure.lines[lineKey].enabled = enabled;
            this.saveState();
        },

        getBisectorEnd(figure, vertexName) {
            const v = figure.vertices;
            let vertex, p1, p2;

            if (vertexName === 'A') {
                vertex = v.A; p1 = v.B; p2 = v.C;
            } else if (vertexName === 'B') {
                vertex = v.B; p1 = v.A; p2 = v.C;
            } else {
                vertex = v.C; p1 = v.A; p2 = v.B;
            }

            return window.bisectorPoint(vertex, p1, p2);
        },

        getMedianEnd(figure, vertexName) {
            const v = figure.vertices;
            let p1, p2;

            if (vertexName === 'A') {
                p1 = v.B; p2 = v.C;
            } else if (vertexName === 'B') {
                p1 = v.A; p2 = v.C;
            } else {
                p1 = v.A; p2 = v.B;
            }

            return window.pointOnLine(p1, p2, 0.5);
        },

        getAltitudeEnd(figure, vertexName) {
            const v = figure.vertices;
            let vertex, p1, p2;

            if (vertexName === 'A') {
                vertex = v.A; p1 = v.B; p2 = v.C;
            } else if (vertexName === 'B') {
                vertex = v.B; p1 = v.A; p2 = v.C;
            } else {
                vertex = v.C; p1 = v.A; p2 = v.B;
            }

            // Проекция vertex на отрезок p1-p2
            const dx = p2.x - p1.x;
            const dy = p2.y - p1.y;
            const t = ((vertex.x - p1.x) * dx + (vertex.y - p1.y) * dy) / (dx * dx + dy * dy);
            const tClamped = Math.max(0, Math.min(1, t));

            return {
                x: p1.x + tClamped * dx,
                y: p1.y + tClamped * dy
            };
        },

        getAltitudeRightAngle(figure, vertexName) {
            const end = this.getAltitudeEnd(figure, vertexName);
            const v = figure.vertices;
            let p1, p2;

            if (vertexName === 'A') {
                p1 = v.B; p2 = v.C;
            } else if (vertexName === 'B') {
                p1 = v.A; p2 = v.C;
            } else {
                p1 = v.A; p2 = v.B;
            }

            return window.rightAnglePath(end, v[vertexName], p2, 10);
        },

        // ==================== Equal Marks ====================

        getEqualityTick(figure, sideName) {
            const v = figure.vertices;
            const p1 = v[sideName[0]];
            const p2 = v[sideName[1]];
            return window.equalityTick(p1, p2, 0.5, 10);
        },

        getDoubleEqualityTick(figure, sideName) {
            const v = figure.vertices;
            const p1 = v[sideName[0]];
            const p2 = v[sideName[1]];
            return window.doubleEqualityTick(p1, p2, 0.5, 10, 5);
        },

        isSideInEqualGroup(groupNum, sideName) {
            if (!this.selectedFigure || !this.selectedFigure.equalGroups) return false;
            const groups = this.selectedFigure.equalGroups.sides || [];
            const group = groups.find(g => g.group === groupNum);
            return group ? group.sides.includes(sideName) : false;
        },

        toggleSideInEqualGroup(groupNum, sideName, checked) {
            if (!this.selectedFigure) return;

            if (!this.selectedFigure.equalGroups) {
                this.selectedFigure.equalGroups = { sides: [], angles: [] };
            }

            let groups = this.selectedFigure.equalGroups.sides;
            let group = groups.find(g => g.group === groupNum);

            if (checked) {
                // Remove from other groups first
                groups.forEach(g => {
                    const idx = g.sides.indexOf(sideName);
                    if (idx !== -1) g.sides.splice(idx, 1);
                });

                if (!group) {
                    group = { group: groupNum, sides: [] };
                    groups.push(group);
                }
                if (!group.sides.includes(sideName)) {
                    group.sides.push(sideName);
                }
            } else {
                if (group) {
                    const idx = group.sides.indexOf(sideName);
                    if (idx !== -1) group.sides.splice(idx, 1);
                }
            }

            // Clean up empty groups
            this.selectedFigure.equalGroups.sides = groups.filter(g => g.sides.length > 0);
            this.saveState();
        },

        // ==================== Vertex Labels ====================

        updateVertexLabel(vertexName, newLabel) {
            if (!this.selectedFigure || !this.selectedFigure.vertices) return;
            this.selectedFigure.vertices[vertexName].label = newLabel;
            this.saveState();
        },

        updateVertexCoord(vertexName, coord, value) {
            if (!this.selectedFigure || !this.selectedFigure.vertices) return;
            this.selectedFigure.vertices[vertexName][coord] = parseInt(value);
            this.saveState();
        },

        // ==================== Circle Elements ====================

        addChord() {
            if (!this.selectedFigure || this.selectedFigure.type !== 'circle') return;
            if (!this.selectedFigure.chords) this.selectedFigure.chords = [];

            // Default chord: horizontal through center
            const angle = Math.random() * Math.PI;
            const r = this.selectedFigure.radius;
            const cx = this.selectedFigure.center.x;
            const cy = this.selectedFigure.center.y;

            this.selectedFigure.chords.push({
                id: `chord_${Date.now()}`,
                point1: { x: cx + r * Math.cos(angle), y: cy + r * Math.sin(angle) },
                point2: { x: cx - r * Math.cos(angle), y: cy - r * Math.sin(angle) },
                label1: 'P',
                label2: 'Q'
            });
            this.saveState();
        },

        addTangent() {
            if (!this.selectedFigure || this.selectedFigure.type !== 'circle') return;
            if (!this.selectedFigure.tangents) this.selectedFigure.tangents = [];

            const r = this.selectedFigure.radius;
            const cx = this.selectedFigure.center.x;
            const cy = this.selectedFigure.center.y;

            // External point
            this.selectedFigure.tangents.push({
                id: `tangent_${Date.now()}`,
                externalPoint: { x: cx + r * 2, y: cy },
                label: 'T'
            });
            this.saveState();
        },

        addSecant() {
            if (!this.selectedFigure || this.selectedFigure.type !== 'circle') return;
            if (!this.selectedFigure.secants) this.selectedFigure.secants = [];

            const r = this.selectedFigure.radius;
            const cx = this.selectedFigure.center.x;
            const cy = this.selectedFigure.center.y;

            this.selectedFigure.secants.push({
                id: `secant_${Date.now()}`,
                point1: { x: cx - r * 1.5, y: cy - r * 0.5 },
                point2: { x: cx + r * 1.5, y: cy + r * 0.5 }
            });
            this.saveState();
        },

        addInscribedAngle() {
            if (!this.selectedFigure || this.selectedFigure.type !== 'circle') return;
            if (!this.selectedFigure.inscribedAngles) this.selectedFigure.inscribedAngles = [];

            const r = this.selectedFigure.radius;
            const cx = this.selectedFigure.center.x;
            const cy = this.selectedFigure.center.y;

            // Three points on the circle
            this.selectedFigure.inscribedAngles.push({
                id: `inscribed_${Date.now()}`,
                vertex: { x: cx, y: cy - r },
                point1: { x: cx - r * 0.866, y: cy + r * 0.5 },
                point2: { x: cx + r * 0.866, y: cy + r * 0.5 }
            });
            this.saveState();
        },

        addHighlightedArc() {
            if (!this.selectedFigure || this.selectedFigure.type !== 'circle') return;
            if (!this.selectedFigure.highlightedArcs) this.selectedFigure.highlightedArcs = [];

            this.selectedFigure.highlightedArcs.push({
                id: `arc_${Date.now()}`,
                startAngle: 0,
                endAngle: 90,
                color: '#22c55e'
            });
            this.saveState();
        },

        // ==================== Circle Geometry Helpers ====================

        getTangentPoint(figure, tangent) {
            // Calculate tangent point from external point to circle
            const cx = figure.center.x;
            const cy = figure.center.y;
            const r = figure.radius;
            const px = tangent.externalPoint.x;
            const py = tangent.externalPoint.y;

            const dx = px - cx;
            const dy = py - cy;
            const dist = Math.sqrt(dx * dx + dy * dy);

            if (dist <= r) {
                // Point is inside or on circle - return closest point
                return { x: cx + (dx / dist) * r, y: cy + (dy / dist) * r };
            }

            // Calculate tangent point using geometry
            const angle = Math.atan2(dy, dx);
            const tangentAngle = Math.acos(r / dist);

            // Return one of the two tangent points
            return {
                x: cx + r * Math.cos(angle + tangentAngle),
                y: cy + r * Math.sin(angle + tangentAngle)
            };
        },

        getSecantIntersections(figure, secant) {
            const cx = figure.center.x;
            const cy = figure.center.y;
            const r = figure.radius;
            const x1 = secant.point1.x;
            const y1 = secant.point1.y;
            const x2 = secant.point2.x;
            const y2 = secant.point2.y;

            // Line: P = P1 + t * (P2 - P1)
            const dx = x2 - x1;
            const dy = y2 - y1;

            // Solve: |P - C| = r
            const a = dx * dx + dy * dy;
            const b = 2 * (dx * (x1 - cx) + dy * (y1 - cy));
            const c = (x1 - cx) * (x1 - cx) + (y1 - cy) * (y1 - cy) - r * r;

            const discriminant = b * b - 4 * a * c;

            if (discriminant < 0) return [];

            const sqrtD = Math.sqrt(discriminant);
            const t1 = (-b - sqrtD) / (2 * a);
            const t2 = (-b + sqrtD) / (2 * a);

            const points = [];
            if (t1 >= 0 && t1 <= 1) {
                points.push({ x: x1 + t1 * dx, y: y1 + t1 * dy });
            }
            if (t2 >= 0 && t2 <= 1 && Math.abs(t2 - t1) > 0.001) {
                points.push({ x: x1 + t2 * dx, y: y1 + t2 * dy });
            }

            return points;
        },

        getInscribedAnglePath(figure, inscribed) {
            const v = inscribed.vertex;
            const p1 = inscribed.point1;
            const p2 = inscribed.point2;
            return `M ${p1.x} ${p1.y} L ${v.x} ${v.y} L ${p2.x} ${p2.y}`;
        },

        getInscribedAngleArc(figure, inscribed) {
            const v = inscribed.vertex;
            const p1 = inscribed.point1;
            const p2 = inscribed.point2;
            return window.makeAngleArc(v, p1, p2, 20);
        },

        getArcPath(figure, arc) {
            const cx = figure.center.x;
            const cy = figure.center.y;
            const r = figure.radius;

            const startRad = arc.startAngle * Math.PI / 180;
            const endRad = arc.endAngle * Math.PI / 180;

            const x1 = cx + r * Math.cos(startRad);
            const y1 = cy - r * Math.sin(startRad);
            const x2 = cx + r * Math.cos(endRad);
            const y2 = cy - r * Math.sin(endRad);

            const largeArc = Math.abs(arc.endAngle - arc.startAngle) > 180 ? 1 : 0;

            return `M ${x1} ${y1} A ${r} ${r} 0 ${largeArc} 0 ${x2} ${y2}`;
        },

        // Drag handlers for circle elements
        startDragChordPoint(figure, chord, pointKey, event) {
            this.draggingVertex = { figure, chord, pointKey, type: 'chord' };
            this.selectedFigure = figure;
            const svg = document.getElementById('geometry-canvas');
            const pt = this.getSvgPoint(svg, event);
            this.dragOffset = {
                x: pt.x - chord[pointKey].x,
                y: pt.y - chord[pointKey].y
            };
        },

        startDragTangentPoint(figure, tangent, event) {
            this.draggingVertex = { figure, tangent, type: 'tangent' };
            this.selectedFigure = figure;
            const svg = document.getElementById('geometry-canvas');
            const pt = this.getSvgPoint(svg, event);
            this.dragOffset = {
                x: pt.x - tangent.externalPoint.x,
                y: pt.y - tangent.externalPoint.y
            };
        },

        startDragSecantPoint(figure, secant, pointKey, event) {
            this.draggingVertex = { figure, secant, pointKey, type: 'secant' };
            this.selectedFigure = figure;
            const svg = document.getElementById('geometry-canvas');
            const pt = this.getSvgPoint(svg, event);
            this.dragOffset = {
                x: pt.x - secant[pointKey].x,
                y: pt.y - secant[pointKey].y
            };
        },

        startDragApex(figure, event) {
            this.draggingVertex = { figure, vertex: 'apex', type: 'apex' };
            this.selectedFigure = figure;
            const svg = document.getElementById('geometry-canvas');
            const pt = this.getSvgPoint(svg, event);
            this.dragOffset = {
                x: pt.x - figure.apex.x,
                y: pt.y - figure.apex.y
            };
        },

        // ==================== Stereometry Helpers ====================

        getStereometryTypeName(figure) {
            const names = {
                'cube': 'Куб',
                'prism': 'Призма',
                'pyramid': 'Пирамида',
                'cylinder': 'Цилиндр',
                'cone': 'Конус',
                'sphere': 'Шар'
            };
            return names[figure.stereometryType] || 'Фигура';
        },

        getStereometryLabelPos(figure, vertexName) {
            const v = figure.vertices[vertexName];
            if (!v) return { x: 0, y: 0 };

            // Calculate centroid of all vertices
            const vertices = Object.values(figure.vertices);
            const center = {
                x: vertices.reduce((s, v) => s + v.x, 0) / vertices.length,
                y: vertices.reduce((s, v) => s + v.y, 0) / vertices.length
            };

            // Position label away from center
            const dx = v.x - center.x;
            const dy = v.y - center.y;
            const len = Math.sqrt(dx * dx + dy * dy) || 1;
            const dist = 18;

            return {
                x: v.x + (dx / len) * dist,
                y: v.y + (dy / len) * dist
            };
        },

        getCylinderBackArc(figure, position) {
            const cx = figure.center.x;
            const cy = position === 'bottom' ? figure.center.y : figure.center.y - figure.height;
            const rx = figure.radiusX;
            const ry = figure.radiusY;

            // Back half of ellipse (upper half for bottom, lower half for top)
            const startX = cx - rx;
            const endX = cx + rx;

            if (position === 'bottom') {
                return `M ${startX} ${cy} A ${rx} ${ry} 0 0 0 ${endX} ${cy}`;
            } else {
                return `M ${startX} ${cy} A ${rx} ${ry} 0 0 1 ${endX} ${cy}`;
            }
        },

        getConeBackArc(figure) {
            const cx = figure.center.x;
            const cy = figure.center.y;
            const rx = figure.radiusX;
            const ry = figure.radiusY;

            // Back half of base ellipse
            return `M ${cx - rx} ${cy} A ${rx} ${ry} 0 0 0 ${cx + rx} ${cy}`;
        },

        getSphereBackArc(figure) {
            const cx = figure.center.x;
            const cy = figure.center.y;
            const rx = figure.radius;
            const ry = figure.radius * 0.3;

            // Back half of equator
            return `M ${cx - rx} ${cy} A ${rx} ${ry} 0 0 0 ${cx + rx} ${cy}`;
        },

        getSectionPoints(figure, section) {
            if (!section.points || section.points.length === 0) return '';
            return section.points.map(p => `${p.x},${p.y}`).join(' ');
        },

        toggleAutoVisibility(enabled) {
            if (!this.selectedFigure) return;
            this.selectedFigure.autoVisibility = enabled;
            if (enabled) {
                this.recalculateEdgeVisibility(this.selectedFigure);
            }
            this.saveState();
        },

        toggleEdgeVisibility(edgeIndex, visible) {
            if (!this.selectedFigure || !this.selectedFigure.edges) return;
            this.selectedFigure.edges[edgeIndex].visible = visible;
            this.saveState();
        },

        recalculateEdgeVisibility(figure) {
            // Simple heuristic: edges going "into" the figure are hidden
            // This is a simplified version - real 3D visibility is more complex
            if (!figure.edges || !figure.vertices) return;

            // For standard views, some edges are typically hidden
            const hiddenEdges = {
                'cube': ['A-A1'],
                'prism': ['C-A', 'A-A1'],
                'pyramid': ['D-A']
            };

            const hidden = hiddenEdges[figure.stereometryType] || [];

            figure.edges.forEach(edge => {
                const edgeKey = `${edge.from}-${edge.to}`;
                const reverseKey = `${edge.to}-${edge.from}`;
                edge.visible = !hidden.includes(edgeKey) && !hidden.includes(reverseKey);
            });
        },

        addSection() {
            if (!this.selectedFigure || this.selectedFigure.type !== 'stereometry') return;
            if (!this.selectedFigure.sections) this.selectedFigure.sections = [];

            // Default section: a triangle through some vertices
            const vertices = Object.keys(this.selectedFigure.vertices || {});
            const points = [];

            if (vertices.length >= 3) {
                // Pick first 3 vertices for a simple section
                for (let i = 0; i < 3; i++) {
                    const v = this.selectedFigure.vertices[vertices[i]];
                    points.push({ x: v.x, y: v.y, vertexRef: vertices[i] });
                }
            }

            this.selectedFigure.sections.push({
                id: `section_${Date.now()}`,
                points: points,
                color: 'rgba(34, 197, 94, 0.15)'
            });
            this.saveState();
        },

        removeSection(index) {
            if (!this.selectedFigure || !this.selectedFigure.sections) return;
            this.selectedFigure.sections.splice(index, 1);
            this.saveState();
        },

        // ==================== Grid ====================

        toggleGrid() {
            this.showGrid = !this.showGrid;
        },

        // ==================== Undo/Redo ====================

        saveState() {
            // Remove future states if we're not at the end
            if (this.historyIndex < this.history.length - 1) {
                this.history = this.history.slice(0, this.historyIndex + 1);
            }

            // Save current state
            const state = JSON.stringify({
                figures: this.figures,
                showGrid: this.showGrid,
                gridSize: this.gridSize
            });

            this.history.push(state);

            // Limit history size
            if (this.history.length > this.maxHistory) {
                this.history.shift();
            } else {
                this.historyIndex++;
            }
        },

        undo() {
            if (!this.canUndo) return;
            this.historyIndex--;
            this.restoreState(this.history[this.historyIndex]);
        },

        redo() {
            if (!this.canRedo) return;
            this.historyIndex++;
            this.restoreState(this.history[this.historyIndex]);
        },

        restoreState(stateJson) {
            const state = JSON.parse(stateJson);
            this.figures = state.figures;
            this.showGrid = state.showGrid;
            this.gridSize = state.gridSize;

            // Re-select figure if it still exists
            if (this.selectedFigure) {
                const found = this.figures.find(f => f.id === this.selectedFigure.id);
                this.selectedFigure = found || null;
            }
        },

        // ==================== Export/Save ====================

        generateSvg() {
            const svg = document.getElementById('geometry-canvas');
            if (!svg) return '';

            // Clone and clean up
            const clone = svg.cloneNode(true);
            clone.removeAttribute('x-cloak');
            clone.querySelectorAll('[x-show]').forEach(el => el.removeAttribute('x-show'));
            clone.querySelectorAll('[x-for]').forEach(el => el.remove());
            clone.querySelectorAll('template').forEach(el => el.remove());
            clone.querySelectorAll('[:class]').forEach(el => el.removeAttribute(':class'));

            // Add proper SVG header
            const svgString = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${this.canvasWidth} ${this.canvasHeight}">${clone.innerHTML}</svg>`;
            return svgString;
        },

        exportSvg() {
            const svgString = this.generateSvg();
            const blob = new Blob([svgString], { type: 'image/svg+xml' });
            const url = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = `geometry_${this.taskId}.svg`;
            a.click();

            URL.revokeObjectURL(url);
        },

        copySvgCode() {
            const svgString = this.generateSvg();
            navigator.clipboard.writeText(svgString).then(() => {
                alert('SVG код скопирован в буфер обмена!');
            });
        },

        async save() {
            this.saving = true;

            try {
                const metadata = this.buildMetadata();
                const svgString = this.generateSvg();

                const response = await fetch(`/api/geometry/${this.taskId}/save`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        metadata: metadata,
                        svg: svgString
                    })
                });

                if (response.ok) {
                    alert('Изображение сохранено!');
                    this.close();
                } else {
                    const error = await response.json();
                    alert('Ошибка сохранения: ' + (error.message || 'Неизвестная ошибка'));
                }
            } catch (e) {
                console.error('Save error:', e);
                alert('Ошибка сохранения: ' + e.message);
            } finally {
                this.saving = false;
            }
        },

        buildMetadata() {
            return {
                created_via: 'editor',
                created_at: new Date().toISOString(),
                updated_at: new Date().toISOString(),
                canvas: {
                    width: this.canvasWidth,
                    height: this.canvasHeight,
                    showGrid: this.showGrid,
                    gridSize: this.gridSize
                },
                figures: this.figures
            };
        },

        // ==================== Legacy Mode ====================

        recreateInEditor() {
            if (!confirm('Текущее изображение будет заменено. Продолжить?')) return;

            this.mode = 'full_edit';
            this.figures = [];
            this.addDefaultTriangle();
            this.saveState();
        },

        loadFromMetadata(metadata) {
            if (metadata.canvas) {
                this.canvasWidth = metadata.canvas.width || 600;
                this.canvasHeight = metadata.canvas.height || 500;
                this.showGrid = metadata.canvas.showGrid || false;
                this.gridSize = metadata.canvas.gridSize || 20;
            }

            if (metadata.figures) {
                this.figures = metadata.figures;
                this.figureCounter = this.figures.length;
            }
        },

        // ==================== Reset ====================

        resetCanvas() {
            if (!confirm('Сбросить все изменения?')) return;

            this.figures = [];
            this.selectedFigure = null;
            this.history = [];
            this.historyIndex = -1;
            this.figureCounter = 0;
            this.addDefaultTriangle();
            this.saveState();
        }
    };
}

// Регистрируем компонент
document.addEventListener('alpine:init', () => {
    Alpine.data('geometryEditor', geometryEditor);
});
