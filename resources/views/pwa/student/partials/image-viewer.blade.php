<style>
  .q-svg-wrap.is-clickable {
    cursor: zoom-in;
  }

  .image-viewer-overlay {
    position: fixed;
    inset: 0;
    z-index: 90;
    background: rgba(6, 10, 18, 0.86);
    backdrop-filter: blur(10px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
  }

  .image-viewer-overlay[x-cloak] {
    display: none !important;
  }

  .image-viewer-overlay.open {
    display: flex;
  }

  .image-viewer-dialog {
    width: min(96vw, 980px);
    max-height: 92vh;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 24px;
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.42);
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }

  .image-viewer-dialog.is-landscape {
    width: min(96vw, 1280px);
  }

  .image-viewer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    background: var(--surface);
  }

  .image-viewer-title {
    font-size: 13px;
    font-weight: 800;
    color: var(--text);
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .image-viewer-close {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: var(--bg);
    color: var(--text);
    font-size: 18px;
    cursor: pointer;
  }

  .image-viewer-hint {
    padding: 10px 16px 0;
    font-size: 12px;
    font-weight: 700;
    color: var(--muted);
    text-align: center;
  }

  .image-viewer-stage {
    padding: 16px;
    overflow: auto;
    -webkit-overflow-scrolling: touch;
    background: color-mix(in srgb, var(--surface) 72%, transparent);
  }

  .image-viewer-media {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 160px;
  }

  .image-viewer-media img,
  .image-viewer-media svg {
    max-width: 100%;
    height: auto;
    display: block;
    margin: 0 auto;
  }

  .image-viewer-media.is-landscape {
    width: min(1200px, 92vw);
    max-width: none;
  }

  .image-viewer-media.is-landscape img,
  .image-viewer-media.is-landscape svg {
    width: 100%;
    max-width: none;
  }

  @media (orientation: portrait) and (max-width: 768px) {
    .image-viewer-media.is-landscape {
      width: 1100px;
    }
  }
</style>

<div class="image-viewer-overlay"
     x-cloak
     :class="{ 'open': imageViewer.open }"
     x-show="imageViewer.open"
     x-transition.opacity
     @click.self="closeImageViewer()"
     @keydown.escape.window="closeImageViewer()">
  <div class="image-viewer-dialog" :class="{ 'is-landscape': imageViewer.landscape }">
    <div class="image-viewer-header">
      <div class="image-viewer-title">Просмотр изображения</div>
      <button type="button" class="image-viewer-close" @click="closeImageViewer()" aria-label="Закрыть просмотр">
        ×
      </button>
    </div>

    <template x-if="imageViewer.landscape">
      <div class="image-viewer-hint">Поверните телефон горизонтально, чтобы посмотреть изображение целиком.</div>
    </template>

    <div class="image-viewer-stage">
      <template x-if="imageViewer.inlineSvg">
        <div class="image-viewer-media" :class="{ 'is-landscape': imageViewer.landscape }" x-html="imageViewer.inlineSvg"></div>
      </template>

      <template x-if="!imageViewer.inlineSvg && imageViewer.src">
        <div class="image-viewer-media" :class="{ 'is-landscape': imageViewer.landscape }">
          <img :src="imageViewer.src" :alt="imageViewer.alt || 'Изображение задания'">
        </div>
      </template>
    </div>
  </div>
</div>
