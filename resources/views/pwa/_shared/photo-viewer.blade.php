{{--
  Просмотрщик страниц тетради: фото открывается поверх страницы, а не в новой
  вкладке — учитель не теряет место в списке, ученик не уходит с урока.

  Почерк на фото мелкий, поэтому страницу можно приблизить прямо здесь:
  колесо мыши (и трекпад) на десктопе, щипок двумя пальцами на телефоне,
  двойной клик/тап — приблизить или сбросить. Увеличенное фото таскается
  мышью и пальцем.

  Контракт с окружающим x-data (обязателен, партиал своего состояния не заводит):
    photos  — массив { src, full, label }
    viewer  — bool, открыт ли просмотрщик
    vi      — индекс текущей страницы
    close() — закрыть
    step(d) — листнуть на d страниц по кругу

  Клавиши Esc / ← / → вешает на window тот же x-data. Открывается вызовом
  open(i), который тоже живёт снаружи: у разных экранов разные источники фото.
  Масштаб — собственное состояние партиала (photoZoom), оно сбрасывается при
  смене страницы и умирает вместе с закрытым просмотрщиком.
--}}
@once
@push('styles')
  /* Выше кнопки багрепорта из layout (z-index 1100), но ниже её модалки (1150). */
  .viewer {
    position: fixed; inset: 0; z-index: 1120;
    background: rgba(0,0,0,.95);
    display: flex; align-items: center; justify-content: center;
    padding: calc(56px + var(--safe-top)) 12px calc(56px + var(--safe-bottom));
  }
  /* Сцена зума: обрезает вылезающее за края фото и забирает у браузера
     жесты прокрутки/масштаба, иначе щипок зумит всю страницу, а не тетрадь. */
  .viewer-stage {
    flex: 1; align-self: stretch; min-width: 0; min-height: 0;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; touch-action: none;
  }
  .viewer-img {
    max-width: 100%; max-height: 100%;
    object-fit: contain; border-radius: 8px; display: block;
    transform-origin: center center; will-change: transform;
    user-select: none; -webkit-user-drag: none;
  }
  .viewer-img.is-zoomed { cursor: grab; border-radius: 0; }
  .viewer-img.is-dragging { cursor: grabbing; }
  .viewer-bar {
    position: absolute; top: 0; left: 0; right: 0;
    display: flex; align-items: center; gap: 10px;
    padding: calc(10px + var(--safe-top)) 12px 10px;
    background: linear-gradient(rgba(0,0,0,.75), transparent);
  }
  .viewer-label { flex: 1; color: #fff; font-size: 12px; font-weight: 800; overflow-wrap: anywhere; }
  .viewer-btn {
    flex-shrink: 0; border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.1);
    color: #fff; border-radius: 10px; cursor: pointer;
    font-size: 12px; font-weight: 800; padding: 7px 11px; text-decoration: none; line-height: 1;
  }
  .viewer-btn:active { opacity: .7; }
  .viewer-close { font-size: 16px; padding: 6px 11px; }
  .viewer-nav {
    position: absolute; top: 50%; transform: translateY(-50%);
    width: 40px; height: 56px; border-radius: 10px;
    border: 1px solid rgba(255,255,255,.2); background: rgba(0,0,0,.45);
    color: #fff; font-size: 26px; line-height: 1; cursor: pointer;
  }
  .viewer-nav:active { opacity: .7; }
  .viewer-prev { left: 8px; }
  .viewer-next { right: 8px; }
  .viewer-foot {
    position: absolute; left: 0; right: 0; bottom: calc(14px + var(--safe-bottom));
    display: flex; justify-content: center; gap: 10px;
    color: rgba(255,255,255,.75); font-size: 12px; font-weight: 800;
    pointer-events: none;
  }
  .viewer-hint { color: rgba(255,255,255,.4); font-weight: 700; }
  .viewer-reset {
    pointer-events: auto; cursor: pointer;
    border: 1px solid rgba(255,255,255,.2); background: rgba(0,0,0,.45);
    color: rgba(255,255,255,.8); border-radius: 8px; padding: 4px 9px;
    font-size: 12px; font-weight: 800; font-family: inherit; line-height: 1;
  }
  .viewer-reset:active { opacity: .7; }
@endpush

@push('scripts')
<script>
/**
 * Масштабирование фото тетради. Живёт рядом с контрактом просмотрщика:
 * снаружи о зуме знать не нужно, состояние умирает вместе с разметкой.
 */
function photoZoom() {
  return {
    scale: 1, x: 0, y: 0,
    hiRes: false,          // приблизили — показываем оригинал вместо версии 1600px
    dragging: false,
    moved: false,          // был ли сдвиг между нажатием и отпусканием
    max: 6,

    // Указатели и состояние щипка меняются десятки раз в секунду и никому,
    // кроме обработчиков, не нужны — держим вне реактивных зависимостей.
    _pts: new Map(), _pinch: null, _lastTap: 0, _tapZoom: 0, _noFull: false,

    reset() {
      this.scale = 1; this.x = 0; this.y = 0;
      this.hiRes = false; this._noFull = false;
      this.dragging = false; this.moved = false;
      this._pts.clear(); this._pinch = null;
    },

    /**
     * В просмотрщик приходит ужатая до 1600px копия — на первый экран её хватает
     * с запасом. Как только страницу приблизили, дальше растягивать эти пиксели
     * бессмысленно: тянем оригинал, а он в кадре мельче не станет.
     */
    get imgSrc() {
      const photo = this.photos[this.vi];
      return this.hiRes && photo.full ? photo.full : photo.src;
    },

    /** Экранная точка → смещение от центра сцены (там же центр фото при x=y=0). */
    _local(clientX, clientY) {
      const r = this.$refs.stage.getBoundingClientRect();
      return { cx: clientX - r.left - r.width / 2, cy: clientY - r.top - r.height / 2 };
    },

    /** Масштаб вокруг точки (cx, cy): пиксель под курсором остаётся под курсором. */
    zoomAt(next, cx, cy) {
      next = Math.min(this.max, Math.max(1, next));
      const k = next / this.scale;
      this.x = cx - (cx - this.x) * k;
      this.y = cy - (cy - this.y) * k;
      this.scale = next;
      if (next > 1.2 && !this._noFull) this.hiRes = true;
      if (next === 1) { this.x = 0; this.y = 0; } else this.clamp();
    },

    /** Не даём утащить фото за пределы сцены: пустоты по краям быть не должно. */
    clamp() {
      const stage = this.$refs.stage, img = this.$refs.img;
      if (!stage || !img) return;
      const maxX = Math.max(0, (img.clientWidth * this.scale - stage.clientWidth) / 2);
      const maxY = Math.max(0, (img.clientHeight * this.scale - stage.clientHeight) / 2);
      this.x = Math.min(maxX, Math.max(-maxX, this.x));
      this.y = Math.min(maxY, Math.max(-maxY, this.y));
    },

    onWheel(e) {
      const { cx, cy } = this._local(e.clientX, e.clientY);
      // deltaMode 1 — прокрутка строками (Firefox), приводим к пикселям.
      const delta = e.deltaY * (e.deltaMode === 1 ? 16 : 1);
      this.zoomAt(this.scale * Math.exp(-delta * 0.0018), cx, cy);
    },

    _pinchState() {
      const [a, b] = [...this._pts.values()];
      return {
        dist: Math.hypot(a.x - b.x, a.y - b.y) || 1,
        ...this._local((a.x + b.x) / 2, (a.y + b.y) / 2),
      };
    },

    onDown(e) {
      if (e.pointerType === 'mouse' && e.button !== 0) return;
      this.$refs.stage.setPointerCapture?.(e.pointerId);
      this._pts.set(e.pointerId, { x: e.clientX, y: e.clientY });
      this.moved = false;
      if (this._pts.size === 2) this._pinch = this._pinchState();
      else if (this._pts.size === 1 && this.scale > 1) this.dragging = true;
    },

    onMove(e) {
      const prev = this._pts.get(e.pointerId);
      if (!prev) return;
      this._pts.set(e.pointerId, { x: e.clientX, y: e.clientY });

      if (this._pts.size >= 2) {
        const now = this._pinchState();
        if (this._pinch) {
          // Сначала едем за серединой щипка, потом растягиваем вокруг неё.
          this.x += now.cx - this._pinch.cx;
          this.y += now.cy - this._pinch.cy;
          this.zoomAt(this.scale * now.dist / this._pinch.dist, now.cx, now.cy);
        }
        this._pinch = now;
        this.moved = true;
        return;
      }

      if (this.dragging) {
        this.x += e.clientX - prev.x;
        this.y += e.clientY - prev.y;
        this.moved = true;
        this.clamp();
      }
    },

    onUp(e) {
      if (!this._pts.delete(e.pointerId)) return;
      if (this._pts.size < 2) this._pinch = null;
      if (this._pts.size === 0) this.dragging = false;
      // Двойной тап пальцем работает как двойной клик мышью.
      if (e.pointerType !== 'mouse' && !this.moved) {
        const now = Date.now();
        if (now - this._lastTap < 300) { this._lastTap = 0; this._tapZoom = now; this.onDouble(e); }
        else this._lastTap = now;
      }
    },

    /**
     * Клик мимо фото закрывает просмотрщик. Проверяем попадание руками, а не
     * через .self: во время перетаскивания указатель захвачен сценой, и click
     * прилетает на неё же, даже если курсор стоял на фотографии.
     */
    onStageClick(e) {
      if (this.moved || !this.$refs.img) return;
      const r = this.$refs.img.getBoundingClientRect();
      const inside = e.clientX >= r.left && e.clientX <= r.right
        && e.clientY >= r.top && e.clientY <= r.bottom;
      if (!inside) this.close();
    },

    onDouble(e) {
      // Двойной тап браузер дублирует синтетическим dblclick — иначе масштаб
      // успел бы дёрнуться дважды и вернуться туда же, откуда начали.
      if (e.type === 'dblclick' && Date.now() - this._tapZoom < 700) return;
      if (this.scale > 1) { this.reset(); return; }
      const { cx, cy } = this._local(e.clientX, e.clientY);
      this.zoomAt(2.5, cx, cy);
    },
  };
}
</script>
@endpush
@endonce

{{-- Живёт в DOM только когда открыт, чтобы полноразмерные фото не грузились заранее. --}}
<template x-if="viewer">
  <div class="viewer" x-data="photoZoom()" x-effect="vi; reset()"
       @click.self="close()" @wheel.prevent="onWheel($event)">
    <div class="viewer-bar">
      <div class="viewer-label" x-text="photos[vi].label"></div>
      <a class="viewer-btn" :href="photos[vi].full" target="_blank" rel="noopener">Оригинал</a>
      <button type="button" class="viewer-btn viewer-close" @click="close()" aria-label="Закрыть">✕</button>
    </div>

    <div class="viewer-stage" x-ref="stage"
         @click="onStageClick($event)"
         @pointerdown="onDown($event)"
         @pointermove="onMove($event)"
         @pointerup="onUp($event)"
         @pointercancel="onUp($event)"
         @dblclick.prevent="onDouble($event)"
         @gesturestart.prevent="void 0"
         @gesturechange.prevent="void 0">
      <img class="viewer-img" x-ref="img" draggable="false"
           :src="imgSrc" :alt="photos[vi].label"
           {{-- x-on:error, а не @error: @error — директива Blade, шаблон не соберётся --}}
           x-on:error="if (hiRes) { _noFull = true; hiRes = false; }"
           :class="{ 'is-zoomed': scale > 1, 'is-dragging': dragging }"
           :style="`transform: translate(${x}px, ${y}px) scale(${scale})`">
    </div>

    <template x-if="photos.length > 1">
      <div>
        <button type="button" class="viewer-nav viewer-prev" @click="step(-1)" aria-label="Предыдущая страница">‹</button>
        <button type="button" class="viewer-nav viewer-next" @click="step(1)" aria-label="Следующая страница">›</button>
      </div>
    </template>

    <div class="viewer-foot">
      <span x-show="photos.length > 1" x-text="(vi + 1) + ' / ' + photos.length"></span>
      <span class="viewer-hint" x-show="scale === 1">колесо или щипок — увеличить</span>
      <button type="button" class="viewer-reset" x-show="scale > 1" @click="reset()"
              x-text="'×' + scale.toFixed(1) + ' · сбросить'"></button>
    </div>
  </div>
</template>
