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

        // Canvas (350×280 — как отображается на странице заданий)
        canvasWidth: 350,
        canvasHeight: 280,
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
            // Координаты для viewBox 350×280 (масштаб ×1.75 от 200×160)
            return {
                id: `triangle_${this.figureCounter}`,
                type: 'triangle',
                preset: 'free',
                vertices: {
                    A: { x: 35, y: 228, label: `A${suffix ? '₊' + suffix : ''}` },
                    B: { x: 315, y: 228, label: `B${suffix ? '₊' + suffix : ''}` },
                    C: { x: 175, y: 44, label: `C${suffix ? '₊' + suffix : ''}` }
                },
                angles: {
                    A: { value: null, showArc: false, arcType: 'single', showValue: false },
                    B: { value: null, showArc: false, arcType: 'single', showValue: false },
                    C: { value: null, showArc: false, arcType: 'single', showValue: false }
                },
                lines: {
                    bisector_a: { enabled: false, intersectionLabel: 'D', showHalfArcs: false },
                    bisector_b: { enabled: false, intersectionLabel: 'E', showHalfArcs: false },
                    bisector_c: { enabled: false, intersectionLabel: 'F', showHalfArcs: false },
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
            // Координаты для viewBox 350×280 (масштаб ×1.75)
            return {
                id: `quad_${this.figureCounter}`,
                type: 'quadrilateral',
                preset: 'free',
                vertices: {
                    A: { x: 35, y: 228, label: `A${suffix ? '₊' + suffix : ''}` },
                    B: { x: 315, y: 228, label: `B${suffix ? '₊' + suffix : ''}` },
                    C: { x: 280, y: 53, label: `C${suffix ? '₊' + suffix : ''}` },
                    D: { x: 70, y: 53, label: `D${suffix ? '₊' + suffix : ''}` }
                },
                angles: {},
                lines: {},
                equalGroups: { sides: [], angles: [] }
            };
        },

        createCircle(index) {
            // Координаты для viewBox 350×280 (масштаб ×1.75)
            return {
                id: `circle_${this.figureCounter}`,
                type: 'circle',
                center: { x: 175, y: 140 },
                radius: 88,
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
            // Размер адаптируется к viewBox (80% от меньшей стороны)
            const size = Math.min(this.canvasWidth, this.canvasHeight) * 0.4;
            const margin = 15; // Отступ для подписей

            switch (preset) {
                case 'isosceles':
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: margin, y: this.canvasHeight - margin };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: this.canvasWidth - margin, y: this.canvasHeight - margin };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: cx, y: margin };
                    this.selectedFigure.preset = 'isosceles';
                    break;

                case 'equilateral':
                    const h = size * Math.sqrt(3);
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: cx - size, y: this.canvasHeight - margin };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: cx + size, y: this.canvasHeight - margin };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: cx, y: this.canvasHeight - margin - h };
                    this.selectedFigure.preset = 'equilateral';
                    break;

                case 'right':
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: margin, y: this.canvasHeight - margin };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: this.canvasWidth - margin, y: this.canvasHeight - margin };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: margin, y: margin };
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
            const margin = 15;
            // Размеры адаптируются к viewBox
            const w = (this.canvasWidth - margin * 2) / 2 * 0.9;
            const h = (this.canvasHeight - margin * 2) / 2 * 0.8;
            const offset = w * 0.2; // Смещение для параллелограмма

            switch (preset) {
                case 'parallelogram':
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: margin, y: this.canvasHeight - margin };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: this.canvasWidth - margin - offset, y: this.canvasHeight - margin };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: this.canvasWidth - margin, y: margin };
                    this.selectedFigure.vertices.D = { ...this.selectedFigure.vertices.D, x: margin + offset, y: margin };
                    this.selectedFigure.preset = 'parallelogram';
                    break;

                case 'rectangle':
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: margin, y: this.canvasHeight - margin };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: this.canvasWidth - margin, y: this.canvasHeight - margin };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: this.canvasWidth - margin, y: margin };
                    this.selectedFigure.vertices.D = { ...this.selectedFigure.vertices.D, x: margin, y: margin };
                    this.selectedFigure.preset = 'rectangle';
                    break;

                case 'rhombus':
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: margin, y: cy };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: cx, y: this.canvasHeight - margin };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: this.canvasWidth - margin, y: cy };
                    this.selectedFigure.vertices.D = { ...this.selectedFigure.vertices.D, x: cx, y: margin };
                    this.selectedFigure.preset = 'rhombus';
                    break;

                case 'square':
                    const s = Math.min(this.canvasWidth, this.canvasHeight) / 2 - margin;
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: cx - s, y: cy + s };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: cx + s, y: cy + s };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: cx + s, y: cy - s };
                    this.selectedFigure.vertices.D = { ...this.selectedFigure.vertices.D, x: cx - s, y: cy - s };
                    this.selectedFigure.preset = 'square';
                    break;

                case 'trapezoid':
                    const topWidth = w * 0.6;
                    this.selectedFigure.vertices.A = { ...this.selectedFigure.vertices.A, x: margin, y: this.canvasHeight - margin };
                    this.selectedFigure.vertices.B = { ...this.selectedFigure.vertices.B, x: this.canvasWidth - margin, y: this.canvasHeight - margin };
                    this.selectedFigure.vertices.C = { ...this.selectedFigure.vertices.C, x: cx + topWidth, y: margin };
                    this.selectedFigure.vertices.D = { ...this.selectedFigure.vertices.D, x: cx - topWidth, y: margin };
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
                    } else if (figure.vertices) {
                        // Clicked on the figure but not on a vertex - drag whole figure
                        this.startDragWholeFigure(figure, event);
                    } else if (figure.center) {
                        // Circle - drag by center
                        this.startDragCenter(figure, event);
                    }
                    return;
                }
            }

            // Check if clicked inside any polygon (for clicks that didn't hit SVG elements)
            const svg = document.getElementById('geometry-canvas');
            const pt = this.getSvgPoint(svg, event);
            for (const figure of this.figures) {
                if (figure.vertices && this.isPointInPolygon(pt, figure.vertices)) {
                    this.selectFigure(figure);
                    this.startDragWholeFigure(figure, event);
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
            } else if (vertex === 'whole' && figure.vertices) {
                // Перетаскивание всей фигуры
                const dx = pt.x - this.dragOffset.x;
                const dy = pt.y - this.dragOffset.y;
                const initialVertices = this.draggingVertex.initialVertices;

                // Перемещаем все вершины на одинаковое смещение
                Object.keys(figure.vertices).forEach(vName => {
                    let newVx = initialVertices[vName].x + dx;
                    let newVy = initialVertices[vName].y + dy;

                    // Snap to grid
                    if (this.showGrid) {
                        newVx = Math.round(newVx / this.gridSize) * this.gridSize;
                        newVy = Math.round(newVy / this.gridSize) * this.gridSize;
                    }

                    figure.vertices[vName].x = newVx;
                    figure.vertices[vName].y = newVy;
                });
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
        // Свойство: диагонали делят друг друга пополам, т.е. A + C = B + D
        moveParallelogramVertex(figure, vertex, newX, newY) {
            const v = figure.vertices;

            // Обновляем текущую вершину
            v[vertex].x = newX;
            v[vertex].y = newY;

            // Противоположная вершина вычисляется из свойства параллелограмма
            // A + C = B + D, поэтому:
            // C = B + D - A
            // A = B + D - C
            // B = A + C - D
            // D = A + C - B
            const opposite = { A: 'C', B: 'D', C: 'A', D: 'B' };
            const oppVertex = opposite[vertex];

            if (vertex === 'A' || vertex === 'C') {
                // Перемещаем A или C → пересчитываем противоположную, B и D фиксированы
                v[oppVertex].x = v.B.x + v.D.x - v[vertex].x;
                v[oppVertex].y = v.B.y + v.D.y - v[vertex].y;
            } else {
                // Перемещаем B или D → пересчитываем противоположную, A и C фиксированы
                v[oppVertex].x = v.A.x + v.C.x - v[vertex].x;
                v[oppVertex].y = v.A.y + v.C.y - v[vertex].y;
            }
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

        // Проверка, находится ли точка внутри полигона (ray casting algorithm)
        isPointInPolygon(point, vertices) {
            const vertexArray = Object.values(vertices);
            let inside = false;
            const n = vertexArray.length;

            for (let i = 0, j = n - 1; i < n; j = i++) {
                const xi = vertexArray[i].x, yi = vertexArray[i].y;
                const xj = vertexArray[j].x, yj = vertexArray[j].y;

                if (((yi > point.y) !== (yj > point.y)) &&
                    (point.x < (xj - xi) * (point.y - yi) / (yj - yi) + xi)) {
                    inside = !inside;
                }
            }
            return inside;
        },

        // Получить центроид полигона
        getPolygonCentroid(vertices) {
            const vertexArray = Object.values(vertices);
            let cx = 0, cy = 0;
            vertexArray.forEach(v => {
                cx += v.x;
                cy += v.y;
            });
            return {
                x: cx / vertexArray.length,
                y: cy / vertexArray.length
            };
        },

        // Начать перетаскивание всей фигуры
        startDragWholeFigure(figure, event) {
            const svg = document.getElementById('geometry-canvas');
            const pt = this.getSvgPoint(svg, event);

            // Сохраняем начальные позиции всех вершин
            this.draggingVertex = {
                figure,
                vertex: 'whole',
                initialVertices: JSON.parse(JSON.stringify(figure.vertices))
            };
            this.selectedFigure = figure;
            this.dragOffset = { x: pt.x, y: pt.y };
        },

        // ==================== SVG Rendering (x-html approach for SVG compatibility) ====================

        // Цветовая палитра (соответствует оригинальным SVG на страницах тем)
        colors: {
            background: '#0a1628',       // Тёмный синий фон
            shapeStroke: '#c8dce8',      // Основные линии фигур
            shapeStrokeSelected: '#ffffff', // Выделенная фигура
            circleStroke: '#5a9fcf',     // Окружности
            auxiliaryLine: '#5a9fcf',    // Вспомогательные линии (пунктир)
            angleArc: '#d4a855',         // Дуги углов (золотой)
            angleArc2: '#5a9fcf',        // Дуги углов второй группы (голубой, как Claude Code)
            angleArc3: '#55d4a8',        // Дуги углов третьей группы (бирюзовый)
            vertexMarker: '#7eb8da',     // Маркеры вершин
            label: '#c8dce8',            // Подписи вершин
            auxiliaryLabel: '#5a9fcf',   // Подписи вспомогательных точек
            hiddenEdge: '#6b7280',       // Скрытые рёбра (стереометрия)
        },

        renderAllFigures() {
            let svg = '';
            this.figures.forEach((figure, index) => {
                const isSelected = this.selectedFigure && this.selectedFigure.id === figure.id;
                const strokeColor = isSelected ? this.colors.shapeStrokeSelected : this.colors.shapeStroke;

                svg += `<g class="${isSelected ? 'selected-figure' : ''}" data-figure-id="${figure.id}">`;

                if (figure.type === 'triangle') {
                    svg += this.renderTriangle(figure, strokeColor, isSelected);
                } else if (figure.type === 'quadrilateral') {
                    svg += this.renderQuadrilateral(figure, strokeColor, isSelected);
                } else if (figure.type === 'circle') {
                    svg += this.renderCircle(figure, isSelected);
                } else if (figure.type === 'stereometry') {
                    svg += this.renderStereometry(figure, strokeColor, isSelected);
                }

                svg += '</g>';
            });
            return svg;
        },

        // Рендер маркера вершины (крестик с кружком, как в оригинале)
        renderVertexMarker(x, y, vertexName, isSelected = false) {
            const color = isSelected ? this.colors.shapeStrokeSelected : this.colors.vertexMarker;
            return `
                <g transform="translate(${x}, ${y})" class="cursor-grab" data-vertex="${vertexName}">
                    <circle cx="0" cy="0" r="10" fill="transparent"/>
                    <line x1="-5" y1="0" x2="5" y2="0" stroke="${color}" stroke-width="1"/>
                    <line x1="0" y1="-5" x2="0" y2="5" stroke="${color}" stroke-width="1"/>
                    <circle cx="0" cy="0" r="2" fill="none" stroke="${color}" stroke-width="0.8"/>
                </g>
            `;
        },

        renderTriangle(figure, strokeColor, isSelected) {
            const v = figure.vertices;
            const points = `${v.A.x},${v.A.y} ${v.B.x},${v.B.y} ${v.C.x},${v.C.y}`;
            let svg = '';

            // 0. Hit area for dragging (transparent fill)
            svg += `<polygon points="${points}" fill="rgba(0,0,0,0.01)" stroke="none" style="cursor: move;"/>`;

            // 1. Main polygon
            svg += `<polygon points="${points}" fill="none" stroke="${strokeColor}" stroke-width="1.5" stroke-linejoin="round" style="pointer-events: none;"/>`;

            // 2. Auxiliary lines (bisectors, medians, altitudes)
            svg += this.renderTriangleAuxiliaryLines(figure);

            // 3. Angle arcs and values
            svg += this.renderTriangleAngles(figure);

            // 4. Equality tick marks on sides
            svg += this.renderTriangleEqualityMarks(figure);

            // 5. Vertex markers and labels
            ['A', 'B', 'C'].forEach(vName => {
                const vertex = v[vName];
                const labelPos = this.getLabelPosition(figure, vName);
                svg += this.renderVertexMarker(vertex.x, vertex.y, vName, isSelected);
                svg += `<text x="${labelPos.x}" y="${labelPos.y}" fill="${this.colors.label}" font-size="14" font-family="'Times New Roman', serif" font-style="italic" font-weight="500" text-anchor="middle" dominant-baseline="middle" class="geo-label">${vertex.label || vName}</text>`;
            });

            return svg;
        },

        // Рендер вспомогательных линий (биссектрисы, медианы, высоты)
        renderTriangleAuxiliaryLines(figure) {
            let svg = '';
            const v = figure.vertices;
            const lines = figure.lines || {};

            // Биссектрисы (голубой, пунктир — как в целевом стиле)
            const vertexNeighbors = { 'A': ['C', 'B'], 'B': ['A', 'C'], 'C': ['B', 'A'] };
            ['a', 'b', 'c'].forEach(vKey => {
                const lineKey = `bisector_${vKey}`;
                if (lines[lineKey] && lines[lineKey].enabled) {
                    const vName = vKey.toUpperCase();
                    const endpoint = this.getBisectorEnd(figure, vName);
                    const vertex = v[vName];
                    svg += `<line x1="${vertex.x}" y1="${vertex.y}" x2="${endpoint.x}" y2="${endpoint.y}"
                            stroke="${this.colors.auxiliaryLine}" stroke-width="1" stroke-dasharray="8,4"/>`;
                    // Точка пересечения
                    svg += `<circle cx="${endpoint.x}" cy="${endpoint.y}" r="3" fill="${this.colors.auxiliaryLine}"/>`;
                    // Подпись точки
                    const label = lines[lineKey].intersectionLabel || 'D';
                    svg += `<text x="${endpoint.x}" y="${endpoint.y + 15}" fill="${this.colors.auxiliaryLine}" font-size="12"
                            font-family="'Times New Roman', serif" font-style="italic" font-weight="500"
                            text-anchor="middle" dominant-baseline="middle" class="geo-label">${label}</text>`;

                    // Дуги половинных углов (если включены)
                    if (lines[lineKey].showHalfArcs) {
                        const [prev, next] = vertexNeighbors[vName];
                        const arc1 = window.makeAngleArc(vertex, v[prev], endpoint, 20);
                        const arc2 = window.makeAngleArc(vertex, endpoint, v[next], 25);
                        svg += `<path d="${arc1}" fill="none" stroke="${this.colors.angleArc}" stroke-width="1.2"/>`;
                        svg += `<path d="${arc2}" fill="none" stroke="${this.colors.angleArc}" stroke-width="1.2"/>`;
                    }
                }
            });

            // Медианы (голубой, пунктир)
            ['a', 'b', 'c'].forEach(vKey => {
                const lineKey = `median_${vKey}`;
                if (lines[lineKey] && lines[lineKey].enabled) {
                    const vName = vKey.toUpperCase();
                    const endpoint = this.getMedianEnd(figure, vName);
                    const vertex = v[vName];
                    svg += `<line x1="${vertex.x}" y1="${vertex.y}" x2="${endpoint.x}" y2="${endpoint.y}"
                            stroke="${this.colors.auxiliaryLine}" stroke-width="1" stroke-dasharray="8,4"/>`;
                    // Точка пересечения (середина стороны)
                    svg += `<circle cx="${endpoint.x}" cy="${endpoint.y}" r="3" fill="${this.colors.auxiliaryLine}"/>`;
                    // Подпись точки
                    const label = lines[lineKey].intersectionLabel || 'M';
                    svg += `<text x="${endpoint.x}" y="${endpoint.y + 15}" fill="${this.colors.auxiliaryLine}" font-size="12"
                            font-family="'Times New Roman', serif" font-style="italic" font-weight="500"
                            text-anchor="middle" dominant-baseline="middle" class="geo-label">${label}</text>`;
                }
            });

            // Высоты (зелёный, пунктир)
            ['a', 'b', 'c'].forEach(vKey => {
                const lineKey = `altitude_${vKey}`;
                if (lines[lineKey] && lines[lineKey].enabled) {
                    const vName = vKey.toUpperCase();
                    const endpoint = this.getAltitudeEnd(figure, vName);
                    const vertex = v[vName];
                    svg += `<line x1="${vertex.x}" y1="${vertex.y}" x2="${endpoint.x}" y2="${endpoint.y}"
                            stroke="#10b981" stroke-width="1" stroke-dasharray="8,4"/>`;
                    // Точка пересечения (основание высоты)
                    svg += `<circle cx="${endpoint.x}" cy="${endpoint.y}" r="3" fill="#10b981"/>`;
                    // Прямой угол
                    svg += `<path d="${this.getAltitudeRightAngle(figure, vName)}"
                            fill="none" stroke="#10b981" stroke-width="1"/>`;
                    // Подпись точки
                    const label = lines[lineKey].intersectionLabel || 'H';
                    svg += `<text x="${endpoint.x}" y="${endpoint.y + 15}" fill="#10b981" font-size="12"
                            font-family="'Times New Roman', serif" font-style="italic" font-weight="500"
                            text-anchor="middle" dominant-baseline="middle" class="geo-label">${label}</text>`;
                }
            });

            return svg;
        },

        // Рендер углов (дуги и значения)
        renderTriangleAngles(figure) {
            let svg = '';
            const v = figure.vertices;
            const angles = figure.angles || {};

            // Вычисляем углы и группируем равные
            const angleValues = {};
            ['A', 'B', 'C'].forEach(vName => {
                angleValues[vName] = Math.round(this.getAngleValue(figure, vName));
            });

            // Назначаем цвета: равные углы - одинаковый цвет
            const angleColors = this.getAngleColorsForTriangle(angleValues);

            ['A', 'B', 'C'].forEach(vName => {
                const angleData = angles[vName];
                if (!angleData) return;

                const isRightAngle = this.isVertexRightAngle(figure, vName);
                const color = angleColors[vName];

                // Дуга угла или прямой угол
                if (angleData.showArc) {
                    if (isRightAngle) {
                        // Прямой угол - квадратик
                        svg += `<path d="${this.getRightAnglePath(figure, vName)}"
                                fill="none" stroke="${color}" stroke-width="1.5"/>`;
                    } else {
                        // Обычная дуга
                        svg += `<path d="${this.getAngleArc(figure, vName)}"
                                fill="none" stroke="${color}" stroke-width="1.5"/>`;
                    }
                }

                // Значение угла
                if (angleData.showValue) {
                    const labelPos = this.getAngleLabelPos(figure, vName);
                    const angleValue = this.getAngleValue(figure, vName);
                    svg += `<text x="${labelPos.x}" y="${labelPos.y}" fill="${color}" font-size="11"
                            font-family="'Times New Roman', serif" font-weight="500"
                            text-anchor="middle" dominant-baseline="middle">${angleValue}°</text>`;
                }
            });

            return svg;
        },

        // Получить цвета для углов треугольника (равные углы - одинаковый цвет)
        getAngleColorsForTriangle(angleValues) {
            const colors = [this.colors.angleArc, this.colors.angleArc2, this.colors.angleArc3]; // золотой, фиолетовый, бирюзовый
            const result = {};
            const usedColors = {};

            ['A', 'B', 'C'].forEach(vName => {
                const val = angleValues[vName];
                if (usedColors[val] !== undefined) {
                    result[vName] = usedColors[val];
                } else {
                    const colorIdx = Object.keys(usedColors).length;
                    usedColors[val] = colors[colorIdx % colors.length];
                    result[vName] = usedColors[val];
                }
            });

            return result;
        },

        // Рендер маркеров равных сторон
        renderTriangleEqualityMarks(figure) {
            let svg = '';
            const equalGroups = figure.equalGroups || {};
            const sideGroups = equalGroups.sides || [];

            // Цвета для групп
            const groupColors = ['#3b82f6', '#f59e0b', '#ef4444'];

            sideGroups.forEach((group, idx) => {
                const color = groupColors[idx % groupColors.length];
                const tickCount = group.group; // 1, 2, или 3 черточки

                group.sides.forEach(side => {
                    // side = 'AB', 'BC', 'AC'
                    const p1 = figure.vertices[side[0]];
                    const p2 = figure.vertices[side[1]];

                    if (tickCount === 1) {
                        const tick = this.getEqualityTick(figure, side);
                        svg += `<line x1="${tick.x1}" y1="${tick.y1}" x2="${tick.x2}" y2="${tick.y2}"
                                stroke="${color}" stroke-width="2"/>`;
                    } else if (tickCount === 2) {
                        const ticks = this.getDoubleEqualityTick(figure, side);
                        svg += `<line x1="${ticks.tick1.x1}" y1="${ticks.tick1.y1}" x2="${ticks.tick1.x2}" y2="${ticks.tick1.y2}"
                                stroke="${color}" stroke-width="2"/>`;
                        svg += `<line x1="${ticks.tick2.x1}" y1="${ticks.tick2.y1}" x2="${ticks.tick2.x2}" y2="${ticks.tick2.y2}"
                                stroke="${color}" stroke-width="2"/>`;
                    } else if (tickCount === 3) {
                        const ticks = this.getTripleEqualityTick(figure, side);
                        svg += `<line x1="${ticks.tick1.x1}" y1="${ticks.tick1.y1}" x2="${ticks.tick1.x2}" y2="${ticks.tick1.y2}"
                                stroke="${color}" stroke-width="2"/>`;
                        svg += `<line x1="${ticks.tick2.x1}" y1="${ticks.tick2.y1}" x2="${ticks.tick2.x2}" y2="${ticks.tick2.y2}"
                                stroke="${color}" stroke-width="2"/>`;
                        svg += `<line x1="${ticks.tick3.x1}" y1="${ticks.tick3.y1}" x2="${ticks.tick3.x2}" y2="${ticks.tick3.y2}"
                                stroke="${color}" stroke-width="2"/>`;
                    }
                });
            });

            return svg;
        },

        // Тройная черточка для равных сторон
        getTripleEqualityTick(figure, sideName) {
            const v = figure.vertices;
            const p1 = v[sideName[0]];
            const p2 = v[sideName[1]];

            const dx = p2.x - p1.x;
            const dy = p2.y - p1.y;
            const len = Math.sqrt(dx * dx + dy * dy);
            const ux = dx / len;
            const uy = dy / len;
            const nx = -dy / len;
            const ny = dx / len;

            const mid = { x: (p1.x + p2.x) / 2, y: (p1.y + p2.y) / 2 };
            const length = 8;
            const gap = 4;
            const half = length / 2;

            return {
                tick1: {
                    x1: mid.x - ux * gap - nx * half,
                    y1: mid.y - uy * gap - ny * half,
                    x2: mid.x - ux * gap + nx * half,
                    y2: mid.y - uy * gap + ny * half
                },
                tick2: {
                    x1: mid.x - nx * half,
                    y1: mid.y - ny * half,
                    x2: mid.x + nx * half,
                    y2: mid.y + ny * half
                },
                tick3: {
                    x1: mid.x + ux * gap - nx * half,
                    y1: mid.y + uy * gap - ny * half,
                    x2: mid.x + ux * gap + nx * half,
                    y2: mid.y + uy * gap + ny * half
                }
            };
        },

        renderQuadrilateral(figure, strokeColor, isSelected) {
            const v = figure.vertices;
            const points = `${v.A.x},${v.A.y} ${v.B.x},${v.B.y} ${v.C.x},${v.C.y} ${v.D.x},${v.D.y}`;
            let svg = '';

            // 0. Hit area for dragging (transparent fill)
            svg += `<polygon points="${points}" fill="rgba(0,0,0,0.01)" stroke="none" style="cursor: move;"/>`;

            // 1. Main polygon
            svg += `<polygon points="${points}" fill="none" stroke="${strokeColor}" stroke-width="1.5" stroke-linejoin="round" style="pointer-events: none;"/>`;

            // 2. Diagonals
            svg += this.renderQuadDiagonals(figure);

            // 3. Angle arcs and values
            svg += this.renderQuadAngles(figure);

            // 4. Equality tick marks on sides
            svg += this.renderQuadEqualityMarks(figure);

            // 5. Vertex markers and labels
            ['A', 'B', 'C', 'D'].forEach(vName => {
                const vertex = v[vName];
                const labelPos = this.getLabelPositionQuad(figure, vName);
                svg += this.renderVertexMarker(vertex.x, vertex.y, vName, isSelected);
                svg += `<text x="${labelPos.x}" y="${labelPos.y}" fill="${this.colors.label}" font-size="14" font-family="'Times New Roman', serif" font-style="italic" font-weight="500" text-anchor="middle" dominant-baseline="middle" class="geo-label">${vertex.label || vName}</text>`;
            });

            return svg;
        },

        // Рендер диагоналей и вспомогательных линий четырёхугольника
        renderQuadDiagonals(figure) {
            let svg = '';
            const v = figure.vertices;
            const lines = figure.lines || {};

            // Диагональ AC
            if (lines.diagonal_ac && lines.diagonal_ac.enabled) {
                svg += `<line x1="${v.A.x}" y1="${v.A.y}" x2="${v.C.x}" y2="${v.C.y}"
                        stroke="${this.colors.auxiliaryLine}" stroke-width="1" stroke-dasharray="8,4"/>`;
            }

            // Диагональ BD
            if (lines.diagonal_bd && lines.diagonal_bd.enabled) {
                svg += `<line x1="${v.B.x}" y1="${v.B.y}" x2="${v.D.x}" y2="${v.D.y}"
                        stroke="${this.colors.auxiliaryLine}" stroke-width="1" stroke-dasharray="8,4"/>`;
            }

            // Точка пересечения диагоналей (если обе включены)
            if (lines.diagonal_ac && lines.diagonal_ac.enabled && lines.diagonal_bd && lines.diagonal_bd.enabled) {
                const intersection = this.getDiagonalsIntersection(figure);
                if (intersection) {
                    svg += `<circle cx="${intersection.x}" cy="${intersection.y}" r="3" fill="${this.colors.auxiliaryLine}"/>`;
                    const label = lines.intersection_label || 'O';
                    svg += `<text x="${intersection.x}" y="${intersection.y + 15}" fill="${this.colors.auxiliaryLabel}" font-size="12"
                            font-family="'Times New Roman', serif" font-style="italic" font-weight="500"
                            text-anchor="middle" dominant-baseline="middle" class="geo-label">${label}</text>`;
                }
            }

            // Биссектрисы (голубой, пунктир — как в целевом стиле)
            const vertexPairs = {
                'A': ['D', 'B'],
                'B': ['A', 'C'],
                'C': ['B', 'D'],
                'D': ['C', 'A']
            };

            ['a', 'b', 'c', 'd'].forEach(vKey => {
                const lineKey = `bisector_${vKey}`;
                if (lines[lineKey] && lines[lineKey].enabled) {
                    const vName = vKey.toUpperCase();
                    const endpoint = this.getQuadBisectorEnd(figure, vName);
                    const vertex = v[vName];
                    svg += `<line x1="${vertex.x}" y1="${vertex.y}" x2="${endpoint.x}" y2="${endpoint.y}"
                            stroke="${this.colors.auxiliaryLine}" stroke-width="1" stroke-dasharray="8,4"/>`;
                    // Точка пересечения
                    svg += `<circle cx="${endpoint.x}" cy="${endpoint.y}" r="3" fill="${this.colors.auxiliaryLine}"/>`;
                    // Две дуги половинных углов (показывают, что угол делится пополам)
                    const [prev, next] = vertexPairs[vName];
                    svg += `<path d="${window.makeAngleArc(vertex, v[prev], endpoint, 20)}"
                            fill="none" stroke="${this.colors.auxiliaryLine}" stroke-width="1"/>`;
                    svg += `<path d="${window.makeAngleArc(vertex, endpoint, v[next], 20)}"
                            fill="none" stroke="${this.colors.auxiliaryLine}" stroke-width="1"/>`;
                }
            });

            // Высоты (зелёный, пунктир)
            ['a', 'b', 'c', 'd'].forEach(vKey => {
                const lineKey = `altitude_${vKey}`;
                if (lines[lineKey] && lines[lineKey].enabled) {
                    const vName = vKey.toUpperCase();
                    const altitudeData = this.getQuadAltitudeEnd(figure, vName);
                    const vertex = v[vName];
                    svg += `<line x1="${vertex.x}" y1="${vertex.y}" x2="${altitudeData.foot.x}" y2="${altitudeData.foot.y}"
                            stroke="#10b981" stroke-width="1" stroke-dasharray="8,4"/>`;
                    // Точка основания высоты
                    svg += `<circle cx="${altitudeData.foot.x}" cy="${altitudeData.foot.y}" r="3" fill="#10b981"/>`;
                    // Прямой угол
                    svg += `<path d="${window.rightAnglePath(altitudeData.foot, vertex, altitudeData.sideEnd, 10)}"
                            fill="none" stroke="#10b981" stroke-width="1"/>`;
                    // Подпись точки
                    const label = lines[lineKey].intersectionLabel || 'H';
                    svg += `<text x="${altitudeData.foot.x}" y="${altitudeData.foot.y + 15}" fill="#10b981" font-size="12"
                            font-family="'Times New Roman', serif" font-style="italic" font-weight="500"
                            text-anchor="middle" dominant-baseline="middle" class="geo-label">${label}</text>`;
                }
            });

            return svg;
        },

        // Конечная точка биссектрисы угла четырёхугольника
        getQuadBisectorEnd(figure, vName) {
            const v = figure.vertices;
            const vertexPairs = {
                'A': { prev: 'D', next: 'B', oppositeSide: ['B', 'C'] },
                'B': { prev: 'A', next: 'C', oppositeSide: ['C', 'D'] },
                'C': { prev: 'B', next: 'D', oppositeSide: ['D', 'A'] },
                'D': { prev: 'C', next: 'A', oppositeSide: ['A', 'B'] }
            };

            const { prev, next, oppositeSide } = vertexPairs[vName];
            const vertex = v[vName];
            const p1 = v[prev];
            const p2 = v[next];

            // Направление биссектрисы
            const dir = window.bisectorDirection(vertex, p1, p2);

            // Ищем пересечение с противоположной стороной
            const sideP1 = v[oppositeSide[0]];
            const sideP2 = v[oppositeSide[1]];
            const intersection = this.raySegmentIntersection(vertex, dir, sideP1, sideP2);

            if (intersection) return intersection;

            // Если не пересекает противоположную сторону, пробуем соседние стороны
            // Сторона от next
            const nextSideEnd = vertexPairs[next].next;
            const intersection2 = this.raySegmentIntersection(vertex, dir, v[next], v[nextSideEnd]);
            if (intersection2) return intersection2;

            // Сторона от prev
            const prevSidePrev = vertexPairs[prev].prev;
            const intersection3 = this.raySegmentIntersection(vertex, dir, v[prevSidePrev], v[prev]);
            if (intersection3) return intersection3;

            // Fallback: продлить на 200px
            return { x: vertex.x + dir.x * 200, y: vertex.y + dir.y * 200 };
        },

        // Пересечение луча с отрезком
        raySegmentIntersection(rayOrigin, rayDir, segP1, segP2) {
            const dx = segP2.x - segP1.x;
            const dy = segP2.y - segP1.y;
            const denom = rayDir.x * dy - rayDir.y * dx;
            if (Math.abs(denom) < 1e-10) return null;
            const t = ((segP1.x - rayOrigin.x) * dy - (segP1.y - rayOrigin.y) * dx) / denom;
            const s = ((segP1.x - rayOrigin.x) * rayDir.y - (segP1.y - rayOrigin.y) * rayDir.x) / denom;
            if (t > 0.001 && s >= 0 && s <= 1) {
                return { x: rayOrigin.x + t * rayDir.x, y: rayOrigin.y + t * rayDir.y };
            }
            return null;
        },

        // Основание высоты четырёхугольника
        getQuadAltitudeEnd(figure, vName) {
            const v = figure.vertices;
            // Высота опускается на противоположную сторону
            const oppositeSides = {
                'A': ['C', 'D'],  // Высота из A на сторону CD
                'B': ['D', 'A'],  // Высота из B на сторону DA
                'C': ['A', 'B'],  // Высота из C на сторону AB
                'D': ['B', 'C']   // Высота из D на сторону BC
            };

            const [sideStart, sideEnd] = oppositeSides[vName];
            const vertex = v[vName];
            const p1 = v[sideStart];
            const p2 = v[sideEnd];

            // Проекция vertex на прямую p1-p2
            const dx = p2.x - p1.x;
            const dy = p2.y - p1.y;
            const t = ((vertex.x - p1.x) * dx + (vertex.y - p1.y) * dy) / (dx * dx + dy * dy);

            return {
                foot: {
                    x: p1.x + t * dx,
                    y: p1.y + t * dy
                },
                sideEnd: p2
            };
        },

        // Рендер углов четырёхугольника
        renderQuadAngles(figure) {
            let svg = '';
            const v = figure.vertices;
            const angles = figure.angles || {};

            const vertexPairs = {
                'A': ['D', 'B'],
                'B': ['A', 'C'],
                'C': ['B', 'D'],
                'D': ['C', 'A']
            };

            // Вычисляем углы и назначаем цвета (равные углы - одинаковый цвет)
            const angleValues = {};
            ['A', 'B', 'C', 'D'].forEach(vName => {
                angleValues[vName] = Math.round(this.calculateQuadAngle(figure, vName));
            });
            const angleColors = this.getAngleColorsForQuad(angleValues);

            ['A', 'B', 'C', 'D'].forEach(vName => {
                const angleData = angles[vName];
                if (!angleData) return;

                const [prev, next] = vertexPairs[vName];
                const vertex = v[vName];
                const p1 = v[prev];
                const p2 = v[next];
                const color = angleColors[vName];

                // Проверка на прямой угол
                const isRightAngle = this.isQuadVertexRightAngle(figure, vName);

                if (angleData.showArc) {
                    if (isRightAngle) {
                        svg += `<path d="${window.rightAnglePath(vertex, p1, p2, 12)}"
                                fill="none" stroke="${color}" stroke-width="1.5"/>`;
                    } else {
                        svg += `<path d="${window.makeAngleArc(vertex, p1, p2, 20)}"
                                fill="none" stroke="${color}" stroke-width="1.5"/>`;
                    }
                }

                if (angleData.showValue) {
                    const labelPos = window.angleLabelPos(vertex, p1, p2, 42, 0.5);
                    const angleValue = angleData.value || this.calculateQuadAngle(figure, vName);
                    svg += `<text x="${labelPos.x}" y="${labelPos.y}" fill="${color}" font-size="11"
                            font-family="'Times New Roman', serif" font-weight="500"
                            text-anchor="middle" dominant-baseline="middle">${Math.round(angleValue)}°</text>`;
                }
            });

            return svg;
        },

        // Получить цвета для углов четырёхугольника (равные углы - одинаковый цвет)
        getAngleColorsForQuad(angleValues) {
            const colors = [this.colors.angleArc, this.colors.angleArc2, this.colors.angleArc3, '#d455a8']; // золотой, фиолетовый, бирюзовый, розовый
            const result = {};
            const usedColors = {};

            ['A', 'B', 'C', 'D'].forEach(vName => {
                const val = angleValues[vName];
                if (usedColors[val] !== undefined) {
                    result[vName] = usedColors[val];
                } else {
                    const colorIdx = Object.keys(usedColors).length;
                    usedColors[val] = colors[colorIdx % colors.length];
                    result[vName] = usedColors[val];
                }
            });

            return result;
        },

        // Рендер маркеров равных сторон четырёхугольника
        renderQuadEqualityMarks(figure) {
            let svg = '';
            const equalGroups = figure.equalGroups || {};
            const sideGroups = equalGroups.sides || [];

            const groupColors = ['#3b82f6', '#f59e0b', '#ef4444', '#10b981'];

            sideGroups.forEach((group, idx) => {
                const color = groupColors[idx % groupColors.length];
                const tickCount = group.group;

                group.sides.forEach(side => {
                    const p1 = figure.vertices[side[0]];
                    const p2 = figure.vertices[side[1]];

                    const tick = this.getQuadEqualityTick(p1, p2, tickCount);
                    tick.forEach(t => {
                        svg += `<line x1="${t.x1}" y1="${t.y1}" x2="${t.x2}" y2="${t.y2}"
                                stroke="${color}" stroke-width="2"/>`;
                    });
                });
            });

            return svg;
        },

        // Получить черточки равенства для стороны четырёхугольника
        getQuadEqualityTick(p1, p2, count = 1) {
            const ticks = [];
            const dx = p2.x - p1.x;
            const dy = p2.y - p1.y;
            const len = Math.sqrt(dx * dx + dy * dy);
            const ux = dx / len;
            const uy = dy / len;
            const nx = -dy / len;
            const ny = dx / len;

            const mid = { x: (p1.x + p2.x) / 2, y: (p1.y + p2.y) / 2 };
            const length = 8;
            const gap = 4;
            const half = length / 2;

            for (let i = 0; i < count; i++) {
                const offset = (i - (count - 1) / 2) * gap;
                ticks.push({
                    x1: mid.x + ux * offset - nx * half,
                    y1: mid.y + uy * offset - ny * half,
                    x2: mid.x + ux * offset + nx * half,
                    y2: mid.y + uy * offset + ny * half
                });
            }

            return ticks;
        },

        // Точка пересечения диагоналей
        getDiagonalsIntersection(figure) {
            const v = figure.vertices;
            // Линия AC: A + t*(C-A)
            // Линия BD: B + s*(D-B)
            const ax = v.A.x, ay = v.A.y;
            const cx = v.C.x, cy = v.C.y;
            const bx = v.B.x, by = v.B.y;
            const dx = v.D.x, dy = v.D.y;

            const denom = (ax - cx) * (by - dy) - (ay - cy) * (bx - dx);
            if (Math.abs(denom) < 1e-10) return null;

            const t = ((ax - bx) * (by - dy) - (ay - by) * (bx - dx)) / denom;

            return {
                x: ax + t * (cx - ax),
                y: ay + t * (cy - ay)
            };
        },

        // Проверка прямого угла в четырёхугольнике
        isQuadVertexRightAngle(figure, vName) {
            const angle = this.calculateQuadAngle(figure, vName);
            return Math.abs(angle - 90) < 1;
        },

        // Вычисление угла в четырёхугольнике
        calculateQuadAngle(figure, vName) {
            const v = figure.vertices;
            const vertexPairs = {
                'A': ['D', 'B'],
                'B': ['A', 'C'],
                'C': ['B', 'D'],
                'D': ['C', 'A']
            };
            const [prev, next] = vertexPairs[vName];
            const vertex = v[vName];
            const p1 = v[prev];
            const p2 = v[next];

            const v1 = { x: p1.x - vertex.x, y: p1.y - vertex.y };
            const v2 = { x: p2.x - vertex.x, y: p2.y - vertex.y };

            const dot = v1.x * v2.x + v1.y * v2.y;
            const len1 = Math.sqrt(v1.x * v1.x + v1.y * v1.y);
            const len2 = Math.sqrt(v2.x * v2.x + v2.y * v2.y);

            const cos = dot / (len1 * len2);
            return Math.acos(Math.max(-1, Math.min(1, cos))) * 180 / Math.PI;
        },

        renderCircle(figure, isSelected) {
            const strokeColor = isSelected ? this.colors.shapeStrokeSelected : this.colors.circleStroke;
            let svg = `<circle cx="${figure.center.x}" cy="${figure.center.y}" r="${figure.radius}" fill="none" stroke="${strokeColor}" stroke-width="1.5"/>`;

            // Center marker and label
            svg += this.renderVertexMarker(figure.center.x, figure.center.y, 'center', isSelected);
            svg += `<text x="${figure.center.x}" y="${figure.center.y + 18}" fill="${this.colors.auxiliaryLabel}" font-size="14" font-family="'Times New Roman', serif" font-style="italic" font-weight="500" text-anchor="middle" dominant-baseline="middle" class="geo-label">${figure.centerLabel || 'O'}</text>`;

            return svg;
        },

        renderStereometry(figure, strokeColor, isSelected) {
            let svg = '';

            if (figure.edges && figure.vertices) {
                // Hidden edges (dashed)
                figure.edges.filter(e => !e.visible).forEach(edge => {
                    const from = figure.vertices[edge.from];
                    const to = figure.vertices[edge.to];
                    if (from && to) {
                        svg += `<line x1="${from.x}" y1="${from.y}" x2="${to.x}" y2="${to.y}" stroke="${this.colors.hiddenEdge}" stroke-width="1" stroke-dasharray="6,4"/>`;
                    }
                });

                // Visible edges
                figure.edges.filter(e => e.visible).forEach(edge => {
                    const from = figure.vertices[edge.from];
                    const to = figure.vertices[edge.to];
                    if (from && to) {
                        svg += `<line x1="${from.x}" y1="${from.y}" x2="${to.x}" y2="${to.y}" stroke="${strokeColor}" stroke-width="1.5"/>`;
                    }
                });

                // Vertices
                Object.entries(figure.vertices).forEach(([vName, vertex]) => {
                    const labelPos = this.getStereometryLabelPos(figure, vName);
                    const isHidden = vertex.visible === false;
                    const labelColor = isHidden ? this.colors.hiddenEdge : this.colors.label;
                    svg += this.renderVertexMarker(vertex.x, vertex.y, vName, isSelected);
                    svg += `<text x="${labelPos.x}" y="${labelPos.y}" fill="${labelColor}" font-size="14" font-family="'Times New Roman', serif" font-style="italic" font-weight="500" text-anchor="middle" dominant-baseline="middle" class="geo-label">${vertex.label || vName}</text>`;
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
                <path d="M ${cx - rx} ${cy} A ${rx} ${ry} 0 0 0 ${cx + rx} ${cy}" fill="none" stroke="${this.colors.hiddenEdge}" stroke-width="1" stroke-dasharray="6,4"/>
                <ellipse cx="${cx}" cy="${cy}" rx="${rx}" ry="${ry}" fill="none" stroke="${this.colors.shapeStroke}" stroke-width="1.5"/>
                <line x1="${cx - rx}" y1="${cy}" x2="${cx - rx}" y2="${cy - h}" stroke="${this.colors.shapeStroke}" stroke-width="1.5"/>
                <line x1="${cx + rx}" y1="${cy}" x2="${cx + rx}" y2="${cy - h}" stroke="${this.colors.shapeStroke}" stroke-width="1.5"/>
                <ellipse cx="${cx}" cy="${cy - h}" rx="${rx}" ry="${ry}" fill="none" stroke="${this.colors.shapeStroke}" stroke-width="1.5"/>
            `;
        },

        renderCone(figure) {
            const cx = figure.center.x;
            const cy = figure.center.y;
            const rx = figure.radiusX;
            const ry = figure.radiusY;
            const apex = figure.apex;

            return `
                <path d="M ${cx - rx} ${cy} A ${rx} ${ry} 0 0 0 ${cx + rx} ${cy}" fill="none" stroke="${this.colors.hiddenEdge}" stroke-width="1" stroke-dasharray="6,4"/>
                <ellipse cx="${cx}" cy="${cy}" rx="${rx}" ry="${ry}" fill="none" stroke="${this.colors.shapeStroke}" stroke-width="1.5"/>
                <line x1="${cx - rx}" y1="${cy}" x2="${apex.x}" y2="${apex.y}" stroke="${this.colors.shapeStroke}" stroke-width="1.5"/>
                <line x1="${cx + rx}" y1="${cy}" x2="${apex.x}" y2="${apex.y}" stroke="${this.colors.shapeStroke}" stroke-width="1.5"/>
                ${this.renderVertexMarker(apex.x, apex.y, 'apex', false)}
            `;
        },

        renderSphere(figure) {
            const cx = figure.center.x;
            const cy = figure.center.y;
            const r = figure.radius;
            const ry = r * 0.3;

            return `
                <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${this.colors.shapeStroke}" stroke-width="1.5"/>
                <path d="M ${cx - r} ${cy} A ${r} ${ry} 0 0 0 ${cx + r} ${cy}" fill="none" stroke="${this.colors.hiddenEdge}" stroke-width="1" stroke-dasharray="6,4"/>
                <ellipse cx="${cx}" cy="${cy}" rx="${r}" ry="${ry}" fill="none" stroke="${this.colors.shapeStroke}" stroke-width="1.5"/>
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

            return window.makeAngleArc(vertex, p1, p2, 20);
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

        toggleBisectorHalfArcs(lineKey, enabled) {
            if (!this.selectedFigure) return;
            if (!this.selectedFigure.lines) {
                this.selectedFigure.lines = {};
            }
            if (!this.selectedFigure.lines[lineKey]) {
                this.selectedFigure.lines[lineKey] = { enabled: false, showHalfArcs: false };
            }
            this.selectedFigure.lines[lineKey].showHalfArcs = enabled;
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

        // ==================== Quadrilateral Toggles ====================

        toggleQuadAngleArc(vertexName, checked) {
            if (!this.selectedFigure) return;
            if (!this.selectedFigure.angles) {
                this.selectedFigure.angles = {};
            }
            if (!this.selectedFigure.angles[vertexName]) {
                this.selectedFigure.angles[vertexName] = { showArc: false, showValue: false };
            }
            this.selectedFigure.angles[vertexName].showArc = checked;
            this.saveState();
        },

        toggleQuadAngleValue(vertexName, checked) {
            if (!this.selectedFigure) return;
            if (!this.selectedFigure.angles) {
                this.selectedFigure.angles = {};
            }
            if (!this.selectedFigure.angles[vertexName]) {
                this.selectedFigure.angles[vertexName] = { showArc: false, showValue: false };
            }
            this.selectedFigure.angles[vertexName].showValue = checked;
            this.saveState();
        },

        toggleQuadLine(lineKey, enabled) {
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

        isQuadSideInEqualGroup(groupNum, sideName) {
            if (!this.selectedFigure || !this.selectedFigure.equalGroups) return false;
            const groups = this.selectedFigure.equalGroups.sides || [];
            const group = groups.find(g => g.group === groupNum);
            return group ? group.sides.includes(sideName) : false;
        },

        toggleQuadSideInEqualGroup(groupNum, sideName, checked) {
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

        // Вычислить bounding box фигуры
        calculateFigureBounds(figure) {
            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;

            if (figure.vertices) {
                Object.values(figure.vertices).forEach(v => {
                    minX = Math.min(minX, v.x);
                    minY = Math.min(minY, v.y);
                    maxX = Math.max(maxX, v.x);
                    maxY = Math.max(maxY, v.y);
                });
            }

            if (figure.center && figure.radius) {
                minX = Math.min(minX, figure.center.x - figure.radius);
                minY = Math.min(minY, figure.center.y - figure.radius);
                maxX = Math.max(maxX, figure.center.x + figure.radius);
                maxY = Math.max(maxY, figure.center.y + figure.radius);
            }

            return { minX, minY, maxX, maxY };
        },

        generateSvg() {
            if (this.figures.length === 0) return '';

            // Стандартный viewBox как в Claude Code
            const targetWidth = 220;
            const targetHeight = 160;
            const padding = 15; // Отступ для меток

            // 1. Вычисляем bounding box всех фигур в координатах редактора
            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;

            this.figures.forEach(figure => {
                const bounds = this.calculateFigureBounds(figure);
                minX = Math.min(minX, bounds.minX);
                minY = Math.min(minY, bounds.minY);
                maxX = Math.max(maxX, bounds.maxX);
                maxY = Math.max(maxY, bounds.maxY);
            });

            // Добавляем padding
            minX -= padding;
            minY -= padding;
            maxX += padding;
            maxY += padding;

            const contentWidth = maxX - minX;
            const contentHeight = maxY - minY;

            // 2. Вычисляем параметры трансформации
            const scale = Math.min(
                (targetWidth - 2 * padding) / contentWidth,
                (targetHeight - 2 * padding) / contentHeight
            );
            const offsetX = padding + (targetWidth - 2 * padding - contentWidth * scale) / 2;
            const offsetY = padding + (targetHeight - 2 * padding - contentHeight * scale) / 2;

            // 3. Функция для пересчёта координат (замыкание)
            const transformPoint = (p) => ({
                x: offsetX + (p.x - minX) * scale,
                y: offsetY + (p.y - minY) * scale
            });

            // 4. Генерируем SVG БЕЗ transform
            let svgContent = '';
            svgContent += `<rect width="100%" height="100%" fill="${this.colors.background}"/>\n`;

            this.figures.forEach(figure => {
                if (figure.type === 'triangle') {
                    svgContent += this.renderTriangleForExportTransformed(figure, transformPoint, scale);
                } else if (figure.type === 'quadrilateral') {
                    svgContent += this.renderQuadrilateralForExportTransformed(figure, transformPoint, scale);
                } else if (figure.type === 'circle') {
                    svgContent += this.renderCircleForExportTransformed(figure, transformPoint, scale);
                }
            });

            return `<svg viewBox="0 0 ${targetWidth} ${targetHeight}" class="w-full max-w-[350px] h-auto mx-auto">\n${svgContent}</svg>`;
        },

        // Рендер маркера вершины для экспорта (крестик + круг)
        renderVertexMarkerForExport(x, y) {
            return `<g transform="translate(${x}, ${y})">
    <line x1="-5" y1="0" x2="5" y2="0" stroke="${this.colors.vertexMarker}" stroke-width="1"/>
    <line x1="0" y1="-5" x2="0" y2="5" stroke="${this.colors.vertexMarker}" stroke-width="1"/>
    <circle cx="0" cy="0" r="2" fill="none" stroke="${this.colors.vertexMarker}" stroke-width="0.8"/>
  </g>`;
        },

        // Рендер треугольника для экспорта
        renderTriangleForExport(figure) {
            const v = figure.vertices;
            const points = `${v.A.x},${v.A.y} ${v.B.x},${v.B.y} ${v.C.x},${v.C.y}`;
            let svg = '';

            // Основной полигон (stroke-width="2" как в оригинале)
            svg += `<polygon points="${points}" fill="none" stroke="${this.colors.shapeStroke}" stroke-width="2"/>`;

            // Вспомогательные линии
            svg += this.renderTriangleAuxiliaryLines(figure);

            // Углы
            svg += this.renderTriangleAngles(figure);

            // Маркеры равенства
            svg += this.renderTriangleEqualityMarks(figure);

            // Маркеры вершин (крестик + круг)
            ['A', 'B', 'C'].forEach(vName => {
                svg += this.renderVertexMarkerForExport(v[vName].x, v[vName].y);
            });

            // Подписи вершин
            ['A', 'B', 'C'].forEach(vName => {
                const vertex = v[vName];
                const labelPos = this.getLabelPosition(figure, vName);
                svg += `<text x="${labelPos.x}" y="${labelPos.y}" fill="${this.colors.label}" font-size="14" font-family="'Times New Roman', serif" font-style="italic" font-weight="500" text-anchor="middle" dominant-baseline="middle" class="geo-label">${vertex.label || vName}</text>`;
            });

            return svg;
        },

        // Рендер четырёхугольника для экспорта
        renderQuadrilateralForExport(figure) {
            const v = figure.vertices;
            const points = `${v.A.x},${v.A.y} ${v.B.x},${v.B.y} ${v.C.x},${v.C.y} ${v.D.x},${v.D.y}`;
            let svg = '';

            // Основной полигон (stroke-width="2" как в оригинале)
            svg += `<polygon points="${points}" fill="none" stroke="${this.colors.shapeStroke}" stroke-width="2"/>`;

            // Диагонали и вспомогательные линии
            svg += this.renderQuadDiagonals(figure);

            // Углы
            svg += this.renderQuadAngles(figure);

            // Маркеры равенства
            svg += this.renderQuadEqualityMarks(figure);

            // Маркеры вершин (крестик + круг)
            ['A', 'B', 'C', 'D'].forEach(vName => {
                svg += this.renderVertexMarkerForExport(v[vName].x, v[vName].y);
            });

            // Подписи вершин
            ['A', 'B', 'C', 'D'].forEach(vName => {
                const vertex = v[vName];
                const labelPos = this.getLabelPositionQuad(figure, vName);
                svg += `<text x="${labelPos.x}" y="${labelPos.y}" fill="${this.colors.label}" font-size="14" font-family="'Times New Roman', serif" font-style="italic" font-weight="500" text-anchor="middle" dominant-baseline="middle" class="geo-label">${vertex.label || vName}</text>`;
            });

            return svg;
        },

        // Рендер окружности для экспорта
        renderCircleForExport(figure) {
            let svg = '';
            const c = figure.center;
            const r = figure.radius;

            svg += `<circle cx="${c.x}" cy="${c.y}" r="${r}" fill="none" stroke="${this.colors.circleStroke}" stroke-width="2"/>`;

            // Центр
            svg += this.renderVertexMarkerForExport(c.x, c.y);

            // Хорды, касательные и т.д.
            if (figure.chords) {
                figure.chords.forEach(chord => {
                    svg += `<line x1="${chord.point1.x}" y1="${chord.point1.y}" x2="${chord.point2.x}" y2="${chord.point2.y}" stroke="${this.colors.shapeStroke}" stroke-width="2"/>`;
                });
            }

            return svg;
        },

        // ==================== Transformed Export Methods (без transform) ====================

        /**
         * Рендер треугольника с пересчитанными координатами (без transform)
         * Координаты пересчитываются напрямую в viewBox
         */
        renderTriangleForExportTransformed(figure, transformPoint, scale) {
            const v = figure.vertices;

            // Пересчитываем все точки
            const A = transformPoint(v.A);
            const B = transformPoint(v.B);
            const C = transformPoint(v.C);

            // Центр для расчёта позиций меток
            const center = {
                x: (A.x + B.x + C.x) / 3,
                y: (A.y + B.y + C.y) / 3
            };

            const points = `${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y}`;
            let svg = '';

            // Основной полигон (stroke-width="2" как в Claude Code)
            svg += `  <polygon points="${points}" fill="none" stroke="${this.colors.shapeStroke}" stroke-width="2"/>\n`;

            // Вспомогательные линии (трансформированные)
            svg += this.renderTriangleAuxiliaryLinesTransformed(figure, transformPoint);

            // Дуги углов (трансформированные)
            svg += this.renderTriangleAnglesTransformed(figure, A, B, C);

            // Маркеры равенства (трансформированные)
            svg += this.renderTriangleEqualityMarksTransformed(figure, transformPoint);

            // Маркеры вершин (крестик + круг)
            svg += this.renderVertexMarkerForExport(A.x, A.y);
            svg += this.renderVertexMarkerForExport(B.x, B.y);
            svg += this.renderVertexMarkerForExport(C.x, C.y);

            // Подписи вершин
            const labelA = this.labelPosFromCenter(A, center, 18);
            const labelB = this.labelPosFromCenter(B, center, 18);
            const labelC = this.labelPosFromCenter(C, center, 18);

            svg += this.labelText(v.A.label || 'A', labelA);
            svg += this.labelText(v.B.label || 'B', labelB);
            svg += this.labelText(v.C.label || 'C', labelC);

            return svg;
        },

        /**
         * Рендер четырёхугольника с пересчитанными координатами (без transform)
         */
        renderQuadrilateralForExportTransformed(figure, transformPoint, scale) {
            const v = figure.vertices;

            // Пересчитываем все точки
            const A = transformPoint(v.A);
            const B = transformPoint(v.B);
            const C = transformPoint(v.C);
            const D = transformPoint(v.D);

            // Центр для расчёта позиций меток
            const center = {
                x: (A.x + B.x + C.x + D.x) / 4,
                y: (A.y + B.y + C.y + D.y) / 4
            };

            const points = `${A.x},${A.y} ${B.x},${B.y} ${C.x},${C.y} ${D.x},${D.y}`;
            let svg = '';

            // Основной полигон (stroke-width="2" как в Claude Code)
            svg += `  <polygon points="${points}" fill="none" stroke="${this.colors.shapeStroke}" stroke-width="2"/>\n`;

            // Диагонали и вспомогательные линии (трансформированные)
            svg += this.renderQuadDiagonalsTransformed(figure, transformPoint);

            // Дуги углов (трансформированные)
            svg += this.renderQuadAnglesTransformed(figure, A, B, C, D);

            // Маркеры равенства (трансформированные)
            svg += this.renderQuadEqualityMarksTransformed(figure, transformPoint);

            // Маркеры вершин (крестик + круг)
            svg += this.renderVertexMarkerForExport(A.x, A.y);
            svg += this.renderVertexMarkerForExport(B.x, B.y);
            svg += this.renderVertexMarkerForExport(C.x, C.y);
            svg += this.renderVertexMarkerForExport(D.x, D.y);

            // Подписи вершин
            const labelA = this.labelPosFromCenter(A, center, 18);
            const labelB = this.labelPosFromCenter(B, center, 18);
            const labelC = this.labelPosFromCenter(C, center, 18);
            const labelD = this.labelPosFromCenter(D, center, 18);

            svg += this.labelText(v.A.label || 'A', labelA);
            svg += this.labelText(v.B.label || 'B', labelB);
            svg += this.labelText(v.C.label || 'C', labelC);
            svg += this.labelText(v.D.label || 'D', labelD);

            return svg;
        },

        /**
         * Рендер окружности с пересчитанными координатами
         */
        renderCircleForExportTransformed(figure, transformPoint, scale) {
            const c = transformPoint(figure.center);
            const r = figure.radius * scale;

            let svg = '';
            svg += `  <circle cx="${c.x}" cy="${c.y}" r="${r}" fill="none" stroke="${this.colors.circleStroke}" stroke-width="2"/>\n`;
            svg += this.renderVertexMarkerForExport(c.x, c.y);

            // Хорды (трансформированные)
            if (figure.chords) {
                figure.chords.forEach(chord => {
                    const p1 = transformPoint(chord.point1);
                    const p2 = transformPoint(chord.point2);
                    svg += `  <line x1="${p1.x}" y1="${p1.y}" x2="${p2.x}" y2="${p2.y}" stroke="${this.colors.shapeStroke}" stroke-width="2"/>\n`;
                });
            }

            return svg;
        },

        /**
         * Рендер дуг углов треугольника (с пересчитанными координатами)
         */
        renderTriangleAnglesTransformed(figure, A, B, C) {
            let svg = '';
            const angles = figure.angles || {};

            // Цвета для равных/разных углов
            const arcColors = [this.colors.angleArc, this.colors.angleArc2, this.colors.angleArc3];

            // Вычисляем значения углов для определения равных
            const angleValues = {
                A: Math.round(this.calculateAngleFromPoints(A, C, B)),
                B: Math.round(this.calculateAngleFromPoints(B, A, C)),
                C: Math.round(this.calculateAngleFromPoints(C, A, B))
            };

            // Назначаем цвета (равные углы = одинаковый цвет)
            const usedColors = {};
            const angleColorMap = {};

            ['A', 'B', 'C'].forEach(vName => {
                const val = angleValues[vName];
                if (usedColors[val] !== undefined) {
                    angleColorMap[vName] = usedColors[val];
                } else {
                    const colorIdx = Object.keys(usedColors).length;
                    usedColors[val] = arcColors[colorIdx % arcColors.length];
                    angleColorMap[vName] = usedColors[val];
                }
            });

            // Рендерим дуги
            const vertexMap = { A, B, C };
            const adjacentMap = {
                A: { p1: C, p2: B },
                B: { p1: A, p2: C },
                C: { p1: A, p2: B }
            };

            ['A', 'B', 'C'].forEach(vName => {
                const angleData = angles[vName];
                if (!angleData || !angleData.showArc) return;

                const vertex = vertexMap[vName];
                const { p1, p2 } = adjacentMap[vName];
                const color = angleColorMap[vName];
                const isRight = Math.abs(angleValues[vName] - 90) < 2;

                if (isRight) {
                    // Прямой угол - квадратик
                    const path = this.rightAnglePathFromPoints(vertex, p1, p2, 12);
                    svg += `  <path d="${path}" fill="none" stroke="${color}" stroke-width="1.5"/>\n`;
                } else {
                    // Обычная дуга (радиус 20 как в Claude Code)
                    const arc = this.makeAngleArcFromPoints(vertex, p1, p2, 20);
                    svg += `  <path d="${arc}" fill="none" stroke="${color}" stroke-width="1.5"/>\n`;
                }

                // Значение угла (если включено)
                if (angleData.showValue) {
                    const labelPos = this.angleLabelPosFromPoints(vertex, p1, p2, 35);
                    svg += `  <text x="${labelPos.x}" y="${labelPos.y}" fill="${color}" font-size="11" font-family="'Times New Roman', serif" font-weight="500" text-anchor="middle" dominant-baseline="middle">${angleValues[vName]}°</text>\n`;
                }
            });

            return svg;
        },

        /**
         * Рендер дуг углов четырёхугольника (с пересчитанными координатами)
         */
        renderQuadAnglesTransformed(figure, A, B, C, D) {
            let svg = '';
            const angles = figure.angles || {};

            const arcColors = [this.colors.angleArc, this.colors.angleArc2, this.colors.angleArc3, '#d455a8'];

            const vertexMap = { A, B, C, D };
            const adjacentMap = {
                A: { p1: D, p2: B },
                B: { p1: A, p2: C },
                C: { p1: B, p2: D },
                D: { p1: C, p2: A }
            };

            // Вычисляем значения углов
            const angleValues = {};
            ['A', 'B', 'C', 'D'].forEach(vName => {
                const vertex = vertexMap[vName];
                const { p1, p2 } = adjacentMap[vName];
                angleValues[vName] = Math.round(this.calculateAngleFromPoints(vertex, p1, p2));
            });

            // Назначаем цвета
            const usedColors = {};
            const angleColorMap = {};

            ['A', 'B', 'C', 'D'].forEach(vName => {
                const val = angleValues[vName];
                if (usedColors[val] !== undefined) {
                    angleColorMap[vName] = usedColors[val];
                } else {
                    const colorIdx = Object.keys(usedColors).length;
                    usedColors[val] = arcColors[colorIdx % arcColors.length];
                    angleColorMap[vName] = usedColors[val];
                }
            });

            // Рендерим дуги
            ['A', 'B', 'C', 'D'].forEach(vName => {
                const angleData = angles[vName];
                if (!angleData || !angleData.showArc) return;

                const vertex = vertexMap[vName];
                const { p1, p2 } = adjacentMap[vName];
                const color = angleColorMap[vName];
                const isRight = Math.abs(angleValues[vName] - 90) < 2;

                if (isRight) {
                    const path = this.rightAnglePathFromPoints(vertex, p1, p2, 12);
                    svg += `  <path d="${path}" fill="none" stroke="${color}" stroke-width="1.5"/>\n`;
                } else {
                    const arc = this.makeAngleArcFromPoints(vertex, p1, p2, 20);
                    svg += `  <path d="${arc}" fill="none" stroke="${color}" stroke-width="1.5"/>\n`;
                }

                // Значение угла (если включено)
                if (angleData.showValue) {
                    const labelPos = this.angleLabelPosFromPoints(vertex, p1, p2, 35);
                    svg += `  <text x="${labelPos.x}" y="${labelPos.y}" fill="${color}" font-size="11" font-family="'Times New Roman', serif" font-weight="500" text-anchor="middle" dominant-baseline="middle">${angleValues[vName]}°</text>\n`;
                }
            });

            return svg;
        },

        /**
         * Рендер вспомогательных линий треугольника (трансформированные)
         */
        renderTriangleAuxiliaryLinesTransformed(figure, transformPoint) {
            let svg = '';
            const auxLines = figure.auxiliaryLines || [];

            auxLines.forEach(line => {
                const p1 = transformPoint(line.point1);
                const p2 = transformPoint(line.point2);
                const dashArray = line.dashed ? '6,4' : 'none';
                svg += `  <line x1="${p1.x}" y1="${p1.y}" x2="${p2.x}" y2="${p2.y}" stroke="${this.colors.auxiliaryLine}" stroke-width="1.5" stroke-dasharray="${dashArray}"/>\n`;

                // Точка на линии (если есть)
                if (line.midpoint) {
                    const mp = transformPoint(line.midpoint);
                    svg += this.renderVertexMarkerForExport(mp.x, mp.y);
                    if (line.midpointLabel) {
                        const center = { x: (p1.x + p2.x) / 2, y: (p1.y + p2.y) / 2 };
                        const labelPos = this.labelPosFromCenter(mp, center, 15);
                        svg += this.labelText(line.midpointLabel, labelPos, this.colors.auxiliaryLabel);
                    }
                }
            });

            return svg;
        },

        /**
         * Рендер диагоналей и вспомогательных линий четырёхугольника (трансформированные)
         */
        renderQuadDiagonalsTransformed(figure, transformPoint) {
            let svg = '';

            // Диагонали
            if (figure.showDiagonals) {
                const v = figure.vertices;
                const A = transformPoint(v.A);
                const B = transformPoint(v.B);
                const C = transformPoint(v.C);
                const D = transformPoint(v.D);

                svg += `  <line x1="${A.x}" y1="${A.y}" x2="${C.x}" y2="${C.y}" stroke="${this.colors.auxiliaryLine}" stroke-width="1.5" stroke-dasharray="8,4"/>\n`;
                svg += `  <line x1="${B.x}" y1="${B.y}" x2="${D.x}" y2="${D.y}" stroke="${this.colors.auxiliaryLine}" stroke-width="1.5" stroke-dasharray="8,4"/>\n`;
            }

            // Другие вспомогательные линии
            const auxLines = figure.auxiliaryLines || [];
            auxLines.forEach(line => {
                const p1 = transformPoint(line.point1);
                const p2 = transformPoint(line.point2);
                const dashArray = line.dashed !== false ? '8,4' : 'none';
                svg += `  <line x1="${p1.x}" y1="${p1.y}" x2="${p2.x}" y2="${p2.y}" stroke="${this.colors.auxiliaryLine}" stroke-width="1.5" stroke-dasharray="${dashArray}"/>\n`;
            });

            return svg;
        },

        /**
         * Рендер маркеров равенства треугольника (трансформированные)
         */
        renderTriangleEqualityMarksTransformed(figure, transformPoint) {
            let svg = '';
            const equalGroups = figure.equalGroups || {};
            const sideGroups = equalGroups.sides || [];

            const groupColors = ['#3b82f6', '#f59e0b', '#ef4444'];

            sideGroups.forEach((group, idx) => {
                const color = groupColors[idx % groupColors.length];
                const tickCount = group.group;

                group.sides.forEach(side => {
                    const p1 = transformPoint(figure.vertices[side[0]]);
                    const p2 = transformPoint(figure.vertices[side[1]]);

                    if (tickCount === 1) {
                        const tick = this.getEqualityTickFromPoints(p1, p2);
                        svg += `  <line x1="${tick.x1}" y1="${tick.y1}" x2="${tick.x2}" y2="${tick.y2}" stroke="${color}" stroke-width="2"/>\n`;
                    } else if (tickCount === 2) {
                        const ticks = this.getDoubleEqualityTickFromPoints(p1, p2);
                        svg += `  <line x1="${ticks.tick1.x1}" y1="${ticks.tick1.y1}" x2="${ticks.tick1.x2}" y2="${ticks.tick1.y2}" stroke="${color}" stroke-width="2"/>\n`;
                        svg += `  <line x1="${ticks.tick2.x1}" y1="${ticks.tick2.y1}" x2="${ticks.tick2.x2}" y2="${ticks.tick2.y2}" stroke="${color}" stroke-width="2"/>\n`;
                    } else if (tickCount === 3) {
                        const ticks = this.getTripleEqualityTickFromPoints(p1, p2);
                        svg += `  <line x1="${ticks.tick1.x1}" y1="${ticks.tick1.y1}" x2="${ticks.tick1.x2}" y2="${ticks.tick1.y2}" stroke="${color}" stroke-width="2"/>\n`;
                        svg += `  <line x1="${ticks.tick2.x1}" y1="${ticks.tick2.y1}" x2="${ticks.tick2.x2}" y2="${ticks.tick2.y2}" stroke="${color}" stroke-width="2"/>\n`;
                        svg += `  <line x1="${ticks.tick3.x1}" y1="${ticks.tick3.y1}" x2="${ticks.tick3.x2}" y2="${ticks.tick3.y2}" stroke="${color}" stroke-width="2"/>\n`;
                    }
                });
            });

            return svg;
        },

        /**
         * Рендер маркеров равенства четырёхугольника (трансформированные)
         */
        renderQuadEqualityMarksTransformed(figure, transformPoint) {
            let svg = '';
            const equalGroups = figure.equalGroups || {};
            const sideGroups = equalGroups.sides || [];

            const groupColors = ['#3b82f6', '#f59e0b', '#ef4444', '#10b981'];

            sideGroups.forEach((group, idx) => {
                const color = groupColors[idx % groupColors.length];
                const tickCount = group.group;

                group.sides.forEach(side => {
                    const p1 = transformPoint(figure.vertices[side[0]]);
                    const p2 = transformPoint(figure.vertices[side[1]]);

                    if (tickCount === 1) {
                        const tick = this.getEqualityTickFromPoints(p1, p2);
                        svg += `  <line x1="${tick.x1}" y1="${tick.y1}" x2="${tick.x2}" y2="${tick.y2}" stroke="${color}" stroke-width="2"/>\n`;
                    } else if (tickCount === 2) {
                        const ticks = this.getDoubleEqualityTickFromPoints(p1, p2);
                        svg += `  <line x1="${ticks.tick1.x1}" y1="${ticks.tick1.y1}" x2="${ticks.tick1.x2}" y2="${ticks.tick1.y2}" stroke="${color}" stroke-width="2"/>\n`;
                        svg += `  <line x1="${ticks.tick2.x1}" y1="${ticks.tick2.y1}" x2="${ticks.tick2.x2}" y2="${ticks.tick2.y2}" stroke="${color}" stroke-width="2"/>\n`;
                    }
                });
            });

            return svg;
        },

        // ==================== Helper Methods for Transformed Export ====================

        /**
         * Позиция метки относительно центра
         */
        labelPosFromCenter(point, center, distance = 15) {
            const dx = point.x - center.x;
            const dy = point.y - center.y;
            const len = Math.sqrt(dx * dx + dy * dy);

            if (len === 0) {
                return { x: point.x, y: point.y - distance };
            }

            return {
                x: point.x + (dx / len) * distance,
                y: point.y + (dy / len) * distance
            };
        },

        /**
         * Генерация текстовой метки
         */
        labelText(text, pos, color = null, size = 14) {
            color = color || this.colors.label;
            return `  <text x="${pos.x}" y="${pos.y}" fill="${color}" font-size="${size}" ` +
                   `font-family="'Times New Roman', serif" font-style="italic" font-weight="500" ` +
                   `text-anchor="middle" dominant-baseline="middle" class="geo-label">${text}</text>\n`;
        },

        /**
         * Вычисление угла из трёх точек (в градусах)
         */
        calculateAngleFromPoints(vertex, p1, p2) {
            const v1 = { x: p1.x - vertex.x, y: p1.y - vertex.y };
            const v2 = { x: p2.x - vertex.x, y: p2.y - vertex.y };

            const dot = v1.x * v2.x + v1.y * v2.y;
            const len1 = Math.sqrt(v1.x * v1.x + v1.y * v1.y);
            const len2 = Math.sqrt(v2.x * v2.x + v2.y * v2.y);

            const cos = dot / (len1 * len2);
            return Math.acos(Math.max(-1, Math.min(1, cos))) * 180 / Math.PI;
        },

        /**
         * Дуга угла из трёх точек (уже в viewBox координатах)
         */
        makeAngleArcFromPoints(vertex, point1, point2, radius) {
            const angle1 = Math.atan2(point1.y - vertex.y, point1.x - vertex.x);
            const angle2 = Math.atan2(point2.y - vertex.y, point2.x - vertex.x);

            const x1 = vertex.x + radius * Math.cos(angle1);
            const y1 = vertex.y + radius * Math.sin(angle1);
            const x2 = vertex.x + radius * Math.cos(angle2);
            const y2 = vertex.y + radius * Math.sin(angle2);

            let angleDiff = angle2 - angle1;
            while (angleDiff > Math.PI) angleDiff -= 2 * Math.PI;
            while (angleDiff < -Math.PI) angleDiff += 2 * Math.PI;

            const sweep = angleDiff > 0 ? 1 : 0;

            return `M ${x1} ${y1} A ${radius} ${radius} 0 0 ${sweep} ${x2} ${y2}`;
        },

        /**
         * Квадратик прямого угла из трёх точек
         */
        rightAnglePathFromPoints(vertex, p1, p2, size = 12) {
            const angle1 = Math.atan2(p1.y - vertex.y, p1.x - vertex.x);
            const angle2 = Math.atan2(p2.y - vertex.y, p2.x - vertex.x);

            const c1 = {
                x: vertex.x + size * Math.cos(angle1),
                y: vertex.y + size * Math.sin(angle1)
            };
            const c2 = {
                x: vertex.x + size * Math.cos(angle2),
                y: vertex.y + size * Math.sin(angle2)
            };
            const diag = {
                x: c1.x + size * Math.cos(angle2),
                y: c1.y + size * Math.sin(angle2)
            };

            return `M ${c1.x} ${c1.y} L ${diag.x} ${diag.y} L ${c2.x} ${c2.y}`;
        },

        /**
         * Позиция метки угла из трёх точек
         */
        angleLabelPosFromPoints(vertex, p1, p2, labelRadius, bias = 0.5) {
            const angle1 = Math.atan2(p1.y - vertex.y, p1.x - vertex.x);
            const angle2 = Math.atan2(p2.y - vertex.y, p2.x - vertex.x);

            let diff = angle2 - angle1;
            while (diff > Math.PI) diff -= 2 * Math.PI;
            while (diff < -Math.PI) diff += 2 * Math.PI;

            const midAngle = angle1 + diff * bias;

            return {
                x: vertex.x + labelRadius * Math.cos(midAngle),
                y: vertex.y + labelRadius * Math.sin(midAngle)
            };
        },

        /**
         * Маркер равенства из двух точек
         */
        getEqualityTickFromPoints(p1, p2, t = 0.5, length = 8) {
            const mid = {
                x: p1.x + (p2.x - p1.x) * t,
                y: p1.y + (p2.y - p1.y) * t
            };
            const dx = p2.x - p1.x;
            const dy = p2.y - p1.y;
            const len = Math.sqrt(dx * dx + dy * dy);
            const nx = -dy / len;
            const ny = dx / len;
            const half = length / 2;
            return {
                x1: mid.x - nx * half,
                y1: mid.y - ny * half,
                x2: mid.x + nx * half,
                y2: mid.y + ny * half
            };
        },

        /**
         * Двойной маркер равенства из двух точек
         */
        getDoubleEqualityTickFromPoints(p1, p2, t = 0.5, length = 8, gap = 4) {
            const dx = p2.x - p1.x;
            const dy = p2.y - p1.y;
            const len = Math.sqrt(dx * dx + dy * dy);
            const ux = dx / len;
            const uy = dy / len;
            const nx = -dy / len;
            const ny = dx / len;
            const mid = { x: p1.x + dx * t, y: p1.y + dy * t };
            const half = length / 2;
            const halfGap = gap / 2;
            return {
                tick1: {
                    x1: mid.x - ux * halfGap - nx * half,
                    y1: mid.y - uy * halfGap - ny * half,
                    x2: mid.x - ux * halfGap + nx * half,
                    y2: mid.y - uy * halfGap + ny * half
                },
                tick2: {
                    x1: mid.x + ux * halfGap - nx * half,
                    y1: mid.y + uy * halfGap - ny * half,
                    x2: mid.x + ux * halfGap + nx * half,
                    y2: mid.y + uy * halfGap + ny * half
                }
            };
        },

        /**
         * Тройной маркер равенства из двух точек
         */
        getTripleEqualityTickFromPoints(p1, p2, t = 0.5, length = 8, gap = 4) {
            const dx = p2.x - p1.x;
            const dy = p2.y - p1.y;
            const len = Math.sqrt(dx * dx + dy * dy);
            const ux = dx / len;
            const uy = dy / len;
            const nx = -dy / len;
            const ny = dx / len;
            const mid = { x: p1.x + dx * t, y: p1.y + dy * t };
            const half = length / 2;
            return {
                tick1: {
                    x1: mid.x - ux * gap - nx * half,
                    y1: mid.y - uy * gap - ny * half,
                    x2: mid.x - ux * gap + nx * half,
                    y2: mid.y - uy * gap + ny * half
                },
                tick2: {
                    x1: mid.x - nx * half,
                    y1: mid.y - ny * half,
                    x2: mid.x + nx * half,
                    y2: mid.y + ny * half
                },
                tick3: {
                    x1: mid.x + ux * gap - nx * half,
                    y1: mid.y + uy * gap - ny * half,
                    x2: mid.x + ux * gap + nx * half,
                    y2: mid.y + uy * gap + ny * half
                }
            };
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
