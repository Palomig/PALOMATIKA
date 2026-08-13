{{--
  Просмотрщик страниц тетради: фото открывается поверх страницы, а не в новой
  вкладке — учитель не теряет место в списке, ученик не уходит с урока.

  Контракт с окружающим x-data (обязателен, партиал своего состояния не заводит):
    photos  — массив { src, full, label }
    viewer  — bool, открыт ли просмотрщик
    vi      — индекс текущей страницы
    close() — закрыть
    step(d) — листнуть на d страниц по кругу

  Клавиши Esc / ← / → вешает на window тот же x-data. Открывается вызовом
  open(i), который тоже живёт снаружи: у разных экранов разные источники фото.
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
  .viewer-img {
    max-width: 100%; max-height: 100%;
    object-fit: contain; border-radius: 8px; display: block;
  }
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
  .viewer-count {
    position: absolute; left: 0; right: 0; bottom: calc(14px + var(--safe-bottom));
    text-align: center; color: rgba(255,255,255,.75); font-size: 12px; font-weight: 800;
  }
@endpush
@endonce

{{-- Живёт в DOM только когда открыт, чтобы полноразмерные фото не грузились заранее. --}}
<template x-if="viewer">
  <div class="viewer" @click.self="close()">
    <div class="viewer-bar">
      <div class="viewer-label" x-text="photos[vi].label"></div>
      <a class="viewer-btn" :href="photos[vi].full" target="_blank" rel="noopener">Оригинал</a>
      <button type="button" class="viewer-btn viewer-close" @click="close()" aria-label="Закрыть">✕</button>
    </div>

    <img class="viewer-img" :src="photos[vi].src" :alt="photos[vi].label">

    <template x-if="photos.length > 1">
      <div>
        <button type="button" class="viewer-nav viewer-prev" @click="step(-1)" aria-label="Предыдущая страница">‹</button>
        <button type="button" class="viewer-nav viewer-next" @click="step(1)" aria-label="Следующая страница">›</button>
        <div class="viewer-count" x-text="(vi + 1) + ' / ' + photos.length"></div>
      </div>
    </template>
  </div>
</template>
