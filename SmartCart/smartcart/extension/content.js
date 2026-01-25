/**
 * 🛒 SmartCart - Content Script v3
 * Встроенная панель снизу страницы
 */

// === КОНФИГУРАЦИЯ ===
const STORES = {
  perekrestok: { name: 'Перекрёсток', baseUrl: 'https://market-delivery.yandex.ru/retail/perekrestok?placeSlug=perekrestok_7stl6&relatedBrandSlug=perekrestok' },
  magnit: { name: 'Магнит', baseUrl: 'https://market-delivery.yandex.ru/retail/magnit_celevaya?placeSlug=magnit_celevaya_pmnr6&relatedBrandSlug=magnit_celevaya' },
  pyaterochka: { name: 'Пятёрочка', baseUrl: 'https://market-delivery.yandex.ru/retail/paterocka?placeSlug=pyaterochka_ciskb&relatedBrandSlug=paterocka' },
  vkusvill: { name: 'ВкусВилл', baseUrl: 'https://market-delivery.yandex.ru/retail/vkusvill?placeSlug=vkusvill_ekspress_cs6mz&relatedBrandSlug=vkusvill' },
  vkusvill_giper: { name: 'ВкусВилл Гипер', baseUrl: 'https://market-delivery.yandex.ru/retail/vkusvill_giper?placeSlug=vkusvill_qcpbx&relatedBrandSlug=vkusvill_giper' },
  lenta: { name: 'Гиперлента', baseUrl: 'https://market-delivery.yandex.ru/retail/lenta?placeSlug=lenta_zrmdq&relatedBrandSlug=lenta' },
  lenta_super: { name: 'Супер Лента', baseUrl: 'https://market-delivery.yandex.ru/retail/lenta_onlajn?placeSlug=lenta_zvdfl&relatedBrandSlug=lenta_onlajn' },
  dixy: { name: 'Дикси', baseUrl: 'https://market-delivery.yandex.ru/retail/diksi_celevaa?placeSlug=diksi_celevaya_f328j&relatedBrandSlug=diksi_celevaa' },
  chizhik: { name: 'Чижик', baseUrl: 'https://market-delivery.yandex.ru/retail/cizik?placeSlug=chizhik_8csdz&relatedBrandSlug=cizik' },
  verny: { name: 'Верный', baseUrl: 'https://market-delivery.yandex.ru/retail/vernyj_obaij?placeSlug=vernyj_mira_9a&relatedBrandSlug=vernyj_obaij' }
};

const PRODUCTS = [
  // Мясо - общие запросы
  { id: 'chicken', name: 'Курица', query: 'курица', emoji: '🍗' },
  { id: 'turkey', name: 'Индейка', query: 'индейка', emoji: '🦃' },
  { id: 'pork', name: 'Свинина', query: 'свинина', emoji: '🥩' },
  { id: 'beef', name: 'Говядина', query: 'говядина', emoji: '🥩' },
  { id: 'minced', name: 'Фарш', query: 'фарш', emoji: '🍖' },
  
  // Рыба
  { id: 'fish', name: 'Рыба', query: 'рыба', emoji: '🐟' },
  
  // Молочка
  { id: 'eggs', name: 'Яйца', query: 'яйца', emoji: '🥚' },
  { id: 'milk', name: 'Молоко', query: 'молоко', emoji: '🥛' },
  { id: 'cheese', name: 'Сыр', query: 'сыр', emoji: '🧀' },
  { id: 'tvorog', name: 'Творог', query: 'творог', emoji: '🥛' },
  { id: 'smetana', name: 'Сметана', query: 'сметана', emoji: '🥛' },
  { id: 'butter', name: 'Масло сливочное', query: 'масло сливочное', emoji: '🧈' },
  
  // Крупы
  { id: 'rice', name: 'Рис', query: 'рис', emoji: '🍚' },
  { id: 'buckwheat', name: 'Гречка', query: 'гречка', emoji: '🌾' },
  { id: 'oatmeal', name: 'Овсянка', query: 'овсянка', emoji: '🌾' },
  { id: 'pasta', name: 'Макароны', query: 'макароны', emoji: '🍝' },
  
  // Овощи
  { id: 'potato', name: 'Картофель', query: 'картофель', emoji: '🥔' },
  { id: 'onion', name: 'Лук', query: 'лук', emoji: '🧅' },
  { id: 'carrot', name: 'Морковь', query: 'морковь', emoji: '🥕' },
  { id: 'cabbage', name: 'Капуста', query: 'капуста', emoji: '🥬' },
  { id: 'cucumber', name: 'Огурцы', query: 'огурцы', emoji: '🥒' },
  { id: 'tomato', name: 'Помидоры', query: 'помидоры', emoji: '🍅' },
  
  // Другое
  { id: 'bread', name: 'Хлеб', query: 'хлеб', emoji: '🍞' },
  { id: 'oil', name: 'Масло растительное', query: 'масло подсолнечное', emoji: '🫒' },
  { id: 'sugar', name: 'Сахар', query: 'сахар', emoji: '🍬' },
  { id: 'salt', name: 'Соль', query: 'соль', emoji: '🧂' },
];

// === СОСТОЯНИЕ ===
let state = {
  isMinimized: false,
  currentStore: null,
  currentQuery: null,
  parsedData: {},
  completedQuests: {},
};

// === ОПРЕДЕЛЕНИЕ СТРАНИЦЫ ===
function detectPage() {
  const url = window.location.href;
  const params = new URLSearchParams(window.location.search);
  
  // Определяем магазин по URL
  let storeId = null;
  const retailMatch = url.match(/\/retail\/([^?\/]+)/);
  if (retailMatch) {
    const retailSlug = retailMatch[1];
    Object.entries(STORES).forEach(([id, store]) => {
      const storeSlug = store.baseUrl.match(/\/retail\/([^?]+)/)?.[1];
      if (storeSlug && retailSlug.includes(storeSlug.split('?')[0])) {
        storeId = id;
      }
    });
  }
  
  const query = params.get('query');
  const isSearch = !!query;
  const isProduct = url.includes('/product/');
  
  return {
    storeId,
    storeName: storeId ? STORES[storeId]?.name : null,
    isSearch,
    query,
    isProduct,
    url
  };
}

// === СОЗДАНИЕ ПАНЕЛИ ===
function createPanel() {
  const existing = document.getElementById('smartcart-panel');
  if (existing) existing.remove();
  
  const panel = document.createElement('div');
  panel.id = 'smartcart-panel';
  panel.innerHTML = `
    <div class="sc-panel ${state.isMinimized ? 'minimized' : ''}">
      <div class="sc-header" id="sc-header">
        <div class="sc-logo">🛒 SmartCart</div>
        <div class="sc-page-info" id="sc-page-info"></div>
        <button class="sc-toggle" id="sc-toggle">${state.isMinimized ? '▲' : '▼'}</button>
      </div>
      <div class="sc-body" id="sc-body">
        <div class="sc-content" id="sc-content"></div>
      </div>
    </div>
  `;
  
  document.body.appendChild(panel);
  document.body.style.paddingBottom = state.isMinimized ? '60px' : '240px';
  
  document.getElementById('sc-toggle').addEventListener('click', togglePanel);
  document.getElementById('sc-header').addEventListener('dblclick', togglePanel);
  
  updatePanel();
}

function togglePanel() {
  state.isMinimized = !state.isMinimized;
  saveState();
  
  const panel = document.querySelector('.sc-panel');
  const toggle = document.getElementById('sc-toggle');
  
  panel.classList.toggle('minimized', state.isMinimized);
  toggle.textContent = state.isMinimized ? '▲' : '▼';
  document.body.style.paddingBottom = state.isMinimized ? '60px' : '240px';
}

// === ОБНОВЛЕНИЕ КОНТЕНТА ===
function updatePanel() {
  const page = detectPage();
  state.currentStore = page.storeId;
  state.currentQuery = page.query;
  
  const pageInfo = document.getElementById('sc-page-info');
  if (page.storeName) {
    pageInfo.innerHTML = `
      <span class="sc-store-badge">${page.storeName}</span>
      ${page.query ? `<span class="sc-query-badge">🔍 ${decodeURIComponent(page.query)}</span>` : ''}
      <button class="sc-btn-mini" id="sc-show-results">👁 Результаты</button>
    `;
  } else {
    pageInfo.innerHTML = '<span class="sc-hint">Выберите магазин</span>';
  }
  
  const content = document.getElementById('sc-content');
  
  if (page.isSearch) {
    content.innerHTML = renderSearchPage(page);
  } else if (page.storeId) {
    content.innerHTML = renderStorePage(page);
  } else {
    content.innerHTML = renderSelectStore();
  }
  
  initPanelEvents();
}

function renderSearchPage(page) {
  const completed = state.completedQuests[page.storeId] || [];
  const currentProduct = PRODUCTS.find(p => 
    page.query && decodeURIComponent(page.query).toLowerCase().includes(p.query.split(' ')[0].toLowerCase())
  );
  
  const nextProduct = PRODUCTS.find(p => !completed.includes(p.id));
  const savedCount = state.parsedData[page.storeId]?.[currentProduct?.id]?.length || 0;
  
  return `
    <div class="sc-search-panel">
      <div class="sc-main-action">
        <button class="sc-btn sc-btn-primary sc-btn-large" id="sc-scan-btn">
          🔍 СОБРАТЬ ТОВАРЫ
        </button>
        <button class="sc-btn sc-btn-scroll" id="sc-scroll-scan-btn">
          ⬇️ ПРОКРУТИТЬ И СОБРАТЬ ВСЁ
        </button>
        ${savedCount > 0 ? `<div class="sc-saved-info">✅ Сохранено: ${savedCount} товаров</div>` : ''}
      </div>
      
      <div class="sc-navigation">
        ${nextProduct && nextProduct.id !== currentProduct?.id ? `
          <button class="sc-btn sc-btn-next" id="sc-next-btn" data-product="${nextProduct.id}">
            ➡️ Следующий: ${nextProduct.emoji} ${nextProduct.name}
          </button>
        ` : `
          <div class="sc-all-done">🎉 Все продукты собраны!</div>
        `}
      </div>
      
      <div class="sc-progress-box">
        ${renderProgressBar(page.storeId)}
      </div>
    </div>
  `;
}

function renderStorePage(page) {
  const completed = state.completedQuests[page.storeId] || [];
  
  return `
    <div class="sc-store-panel">
      <div class="sc-section-title">Кликни на продукт для поиска:</div>
      <div class="sc-products-grid">
        ${PRODUCTS.map(p => {
          const isDone = completed.includes(p.id);
          const count = state.parsedData[page.storeId]?.[p.id]?.length || 0;
          return `
            <button class="sc-product-btn ${isDone ? 'done' : ''}" data-product="${p.id}">
              <span class="sc-emoji">${p.emoji}</span>
              <span class="sc-name">${p.name}</span>
              ${count > 0 ? `<span class="sc-count">${count}</span>` : ''}
              ${isDone ? '<span class="sc-check">✓</span>' : ''}
            </button>
          `;
        }).join('')}
      </div>
      <div class="sc-progress-box">
        ${renderProgressBar(page.storeId)}
      </div>
    </div>
  `;
}

function renderSelectStore() {
  return `
    <div class="sc-stores-panel">
      <div class="sc-section-title">Выбери магазин для начала сбора цен:</div>
      <div class="sc-stores-grid">
        ${Object.entries(STORES).map(([id, store]) => {
          const completed = state.completedQuests[id]?.length || 0;
          const percent = Math.round((completed / PRODUCTS.length) * 100);
          return `
            <button class="sc-store-btn ${percent === 100 ? 'complete' : ''}" data-store="${id}">
              <span class="sc-store-name">${store.name}</span>
              <span class="sc-store-progress ${percent > 0 ? 'has-data' : ''}">${percent}%</span>
            </button>
          `;
        }).join('')}
      </div>
      <div class="sc-total-stats">
        ${renderTotalStats()}
      </div>
    </div>
  `;
}

function renderProgressBar(storeId) {
  const completed = state.completedQuests[storeId]?.length || 0;
  const total = PRODUCTS.length;
  const percent = Math.round((completed / total) * 100);
  
  return `
    <div class="sc-progress">
      <span class="sc-progress-label">${STORES[storeId]?.name}: ${completed}/${total}</span>
      <div class="sc-progress-bar">
        <div class="sc-progress-fill" style="width: ${percent}%"></div>
      </div>
      <span class="sc-progress-percent">${percent}%</span>
    </div>
  `;
}

function renderTotalStats() {
  let totalProducts = 0;
  let storesWithData = 0;
  
  Object.entries(state.parsedData).forEach(([storeId, products]) => {
    let count = 0;
    Object.values(products).forEach(items => {
      count += items.length;
      totalProducts += items.length;
    });
    if (count > 0) storesWithData++;
  });
  
  return `
    <div class="sc-stats">
      <div class="sc-stat">
        <span class="sc-stat-value">${totalProducts}</span>
        <span class="sc-stat-label">товаров</span>
      </div>
      <div class="sc-stat">
        <span class="sc-stat-value">${storesWithData}</span>
        <span class="sc-stat-label">магазинов</span>
      </div>
      <button class="sc-btn sc-btn-export" id="sc-export-btn">💾 Экспорт</button>
    </div>
  `;
}

// === СОБЫТИЯ ===
function initPanelEvents() {
  const scanBtn = document.getElementById('sc-scan-btn');
  if (scanBtn) scanBtn.addEventListener('click', scanPage);
  
  const scrollScanBtn = document.getElementById('sc-scroll-scan-btn');
  if (scrollScanBtn) scrollScanBtn.addEventListener('click', scrollAndScan);
  
  const showResultsBtn = document.getElementById('sc-show-results');
  if (showResultsBtn) showResultsBtn.addEventListener('click', showResults);
  
  document.querySelectorAll('.sc-product-btn').forEach(btn => {
    btn.addEventListener('click', () => goToProduct(btn.dataset.product));
  });
  
  const nextBtn = document.getElementById('sc-next-btn');
  if (nextBtn) nextBtn.addEventListener('click', () => goToProduct(nextBtn.dataset.product));
  
  document.querySelectorAll('.sc-store-btn').forEach(btn => {
    btn.addEventListener('click', () => goToStore(btn.dataset.store));
  });
  
  const exportBtn = document.getElementById('sc-export-btn');
  if (exportBtn) exportBtn.addEventListener('click', exportData);
  
  // Закрытие модалки
  const closeModal = document.getElementById('sc-close-modal');
  if (closeModal) closeModal.addEventListener('click', hideResultsModal);
}

// Показать результаты парсинга
function showResults() {
  const page = detectPage();
  const currentProduct = PRODUCTS.find(p => 
    page.query && decodeURIComponent(page.query).toLowerCase().includes(p.query.split(' ')[0].toLowerCase())
  );
  
  const productKey = currentProduct?.id || 'other';
  const items = state.parsedData[page.storeId]?.[productKey] || [];
  
  // Создаём модалку
  let modal = document.getElementById('sc-results-modal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'sc-results-modal';
    document.body.appendChild(modal);
  }
  
  modal.innerHTML = `
    <div class="sc-modal-overlay" id="sc-modal-overlay">
      <div class="sc-modal">
        <div class="sc-modal-header">
          <h3>📦 Собранные товары (${items.length})</h3>
          <button class="sc-modal-close" id="sc-close-modal">✕</button>
        </div>
        <div class="sc-modal-body">
          ${items.length === 0 ? `
            <p class="sc-empty">Пока ничего не собрано. Нажмите "Собрать товары".</p>
          ` : `
            <table class="sc-results-table">
              <thead>
                <tr>
                  <th>Название</th>
                  <th>Цена</th>
                  <th>Вес</th>
                  <th>₽/кг</th>
                </tr>
              </thead>
              <tbody>
                ${items.map((item, i) => `
                  <tr>
                    <td class="sc-name-cell" title="${item.name}">${i+1}. ${item.name.substring(0, 40)}${item.name.length > 40 ? '...' : ''}</td>
                    <td class="sc-price-cell">
                      ${item.price} ₽
                      ${item.originalPrice ? `<span class="sc-old-price">${item.originalPrice} ₽</span>` : ''}
                    </td>
                    <td>${item.weight} ${item.unit}</td>
                    <td>${item.pricePerKg ? item.pricePerKg + ' ₽' : '—'}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          `}
        </div>
        <div class="sc-modal-footer">
          <button class="sc-btn sc-btn-test" id="sc-test-parse">🔬 Тест парсинга (консоль)</button>
        </div>
      </div>
    </div>
  `;
  
  modal.style.display = 'block';
  
  // Обработчики
  document.getElementById('sc-modal-overlay').addEventListener('click', (e) => {
    if (e.target.id === 'sc-modal-overlay') hideResultsModal();
  });
  document.getElementById('sc-close-modal').addEventListener('click', hideResultsModal);
  document.getElementById('sc-test-parse').addEventListener('click', () => {
    const testResults = extractProducts();
    console.log('SmartCart TEST:', testResults);
    showNotification(`Тест: найдено ${testResults.length} товаров (см. консоль F12)`);
  });
}

function hideResultsModal() {
  const modal = document.getElementById('sc-results-modal');
  if (modal) modal.style.display = 'none';
}

// Автопрокрутка и сбор всех товаров
async function scrollAndScan() {
  const btn = document.getElementById('sc-scroll-scan-btn');
  btn.textContent = '⏳ Прокручиваю...';
  btn.disabled = true;
  
  // Собираем товары ВО ВРЕМЯ скролла (для виртуального списка)
  const allProducts = new Map(); // Используем Map чтобы избежать дубликатов
  
  try {
    let lastScrollY = 0;
    let stableRounds = 0;
    const maxRounds = 150;
    
    // Сначала скроллим наверх
    window.scrollTo(0, 0);
    await new Promise(r => setTimeout(r, 300));
    
    for (let round = 0; round < maxRounds; round++) {
      // Собираем текущие видимые карточки
      const currentProducts = extractProducts();
      currentProducts.forEach(p => {
        const key = `${p.name}-${p.price}-${p.weight}`;
        if (!allProducts.has(key)) {
          allProducts.set(key, p);
        }
      });
      
      btn.textContent = `⏳ Собрано: ${allProducts.size}...`;
      
      // Прокручиваем вниз
      window.scrollBy(0, 600);
      await new Promise(r => setTimeout(r, 350));
      
      // Проверяем достигли ли конца
      const currentScrollY = window.scrollY;
      if (currentScrollY === lastScrollY) {
        stableRounds++;
        if (stableRounds >= 3) {
          console.log(`SmartCart: конец страницы, собрано ${allProducts.size}`);
          break;
        }
      } else {
        stableRounds = 0;
        lastScrollY = currentScrollY;
      }
      
      // Дополнительная проверка конца страницы
      if ((window.innerHeight + window.scrollY) >= document.body.scrollHeight - 50) {
        // Делаем ещё пару итераций чтобы собрать последние товары
        for (let i = 0; i < 3; i++) {
          await new Promise(r => setTimeout(r, 300));
          const lastProducts = extractProducts();
          lastProducts.forEach(p => {
            const key = `${p.name}-${p.price}-${p.weight}`;
            if (!allProducts.has(key)) {
              allProducts.set(key, p);
            }
          });
        }
        console.log(`SmartCart: достигнут конец, итого ${allProducts.size}`);
        break;
      }
    }
    
    // Возвращаемся наверх
    window.scrollTo(0, 0);
    await new Promise(r => setTimeout(r, 200));
    
    // Сохраняем собранные данные
    const products = Array.from(allProducts.values());
    console.log(`SmartCart: всего собрано ${products.length} товаров`);
    
    if (products.length > 0) {
      const page = detectPage();
      const currentProduct = PRODUCTS.find(p => 
        page.query && decodeURIComponent(page.query).toLowerCase().includes(p.query.split(' ')[0].toLowerCase())
      );
      
      if (!state.parsedData[page.storeId]) state.parsedData[page.storeId] = {};
      
      const productKey = currentProduct?.id || 'other';
      state.parsedData[page.storeId][productKey] = products;
      
      if (currentProduct) {
        if (!state.completedQuests[page.storeId]) state.completedQuests[page.storeId] = [];
        if (!state.completedQuests[page.storeId].includes(currentProduct.id)) {
          state.completedQuests[page.storeId].push(currentProduct.id);
        }
      }
      
      await saveState();
      
      // Отправляем на сервер
      const sent = await sendToServer(page.storeId, productKey, products);
      
      showNotification(`✅ Сохранено ${products.length} товаров!${sent ? ' (отправлено на сервер)' : ''}`);
      updatePanel();
    } else {
      showNotification('⚠️ Товары не найдены', 'error');
    }
    
  } catch (e) {
    console.error('SmartCart: ошибка автоскролла', e);
    showNotification('❌ Ошибка', 'error');
  } finally {
    btn.textContent = '⬇️ ПРОКРУТИТЬ И СОБРАТЬ ВСЁ';
    btn.disabled = false;
  }
}

function goToProduct(productId) {
  const product = PRODUCTS.find(p => p.id === productId);
  const store = STORES[state.currentStore];
  if (product && store) {
    window.location.href = store.baseUrl + '&query=' + encodeURIComponent(product.query);
  }
}

function goToStore(storeId) {
  const store = STORES[storeId];
  if (store) window.location.href = store.baseUrl;
}

// === ПАРСИНГ ===
async function scanPage() {
  const btn = document.getElementById('sc-scan-btn');
  btn.textContent = '⏳ Сканирование...';
  btn.disabled = true;
  
  try {
    const products = extractProducts();
    
    if (products.length > 0) {
      const page = detectPage();
      const currentProduct = PRODUCTS.find(p => 
        page.query && decodeURIComponent(page.query).toLowerCase().includes(p.query.split(' ')[0].toLowerCase())
      );
      
      if (!state.parsedData[page.storeId]) state.parsedData[page.storeId] = {};
      
      const productKey = currentProduct?.id || 'other';
      state.parsedData[page.storeId][productKey] = products;
      
      if (currentProduct) {
        if (!state.completedQuests[page.storeId]) state.completedQuests[page.storeId] = [];
        if (!state.completedQuests[page.storeId].includes(currentProduct.id)) {
          state.completedQuests[page.storeId].push(currentProduct.id);
        }
      }
      
      await saveState();
      
      // Отправляем на сервер
      const sent = await sendToServer(page.storeId, productKey, products);
      
      showNotification(`✅ Сохранено ${products.length} товаров!${sent ? ' (отправлено на сервер)' : ''}`);
      updatePanel();
    } else {
      showNotification('⚠️ Товары не найдены. Прокрутите страницу вниз.', 'error');
    }
  } catch (e) {
    console.error('Ошибка:', e);
    showNotification('❌ Ошибка сканирования', 'error');
  } finally {
    btn.textContent = '🔍 СОБРАТЬ ТОВАРЫ СО СТРАНИЦЫ';
    btn.disabled = false;
  }
}

function extractProducts() {
  const products = [];
  
  // Ищем все названия товаров
  const nameElements = document.querySelectorAll('[data-testid="product-card-name"]');
  console.log(`SmartCart: найдено ${nameElements.length} названий товаров`);
  
  nameElements.forEach((nameEl, index) => {
    try {
      // Название
      const name = nameEl.textContent?.trim() || nameEl.getAttribute('title') || '';
      if (!name || name.length < 3) {
        console.log(`SmartCart [${index}]: пропуск - пустое название`);
        return;
      }
      
      // Находим родительский контейнер (descriptionWrapper)
      const wrapper = nameEl.parentElement;
      if (!wrapper) {
        console.log(`SmartCart [${index}]: пропуск - нет родителя`);
        return;
      }
      
      // Ищем цену внутри wrapper или рядом
      let priceEl = wrapper.querySelector('[data-testid="product-card-price"]');
      if (!priceEl) {
        // Может быть в соседнем элементе или выше
        const grandParent = wrapper.parentElement;
        if (grandParent) {
          priceEl = grandParent.querySelector('[data-testid="product-card-price"]');
        }
      }
      
      if (!priceEl) {
        console.log(`SmartCart [${index}]: пропуск - нет цены для "${name.substring(0, 20)}..."`);
        return;
      }
      
      const priceText = priceEl.textContent || '';
      const priceMatch = priceText.match(/(\d+)\s*₽/);
      if (!priceMatch) {
        console.log(`SmartCart [${index}]: пропуск - не распарсил цену "${priceText}"`);
        return;
      }
      const price = parseInt(priceMatch[1]);
      
      // Ищем старую цену
      let originalPrice = null;
      const oldPriceEl = wrapper.querySelector('[data-testid="product-card-old-price"]') ||
                         wrapper.parentElement?.querySelector('[data-testid="product-card-old-price"]');
      if (oldPriceEl) {
        const oldMatch = oldPriceEl.textContent?.match(/(\d+)/);
        if (oldMatch) originalPrice = parseInt(oldMatch[1]);
      }
      
      // Ищем вес
      let weightEl = wrapper.querySelector('[data-testid="product-card-weight"]');
      if (!weightEl && wrapper.parentElement) {
        weightEl = wrapper.parentElement.querySelector('[data-testid="product-card-weight"]');
      }
      
      let weight = null;
      let unit = null;
      if (weightEl) {
        const weightText = weightEl.textContent?.replace(/\s+/g, ' ')?.trim() || '';
        const weightMatch = weightText.match(/(\d+(?:[.,]\d+)?)\s*(г|кг|мл|л|шт)/i);
        if (weightMatch) {
          weight = parseFloat(weightMatch[1].replace(',', '.'));
          unit = weightMatch[2].toLowerCase();
        }
      }
      
      // Цена за кг
      let pricePerKg = null;
      if (weight && unit) {
        if (unit === 'г') pricePerKg = Math.round(price / weight * 1000);
        if (unit === 'кг') pricePerKg = Math.round(price / weight);
      }
      
      // URL - ищем ссылку в родителях
      let url = '';
      let parent = nameEl;
      for (let i = 0; i < 8 && parent; i++) {
        const link = parent.querySelector('a[href*="/product/"]');
        if (link) {
          url = link.href;
          break;
        }
        parent = parent.parentElement;
      }
      
      console.log(`SmartCart [${index}]: ✓ ${name.substring(0, 30)}... = ${price}₽, ${weight}${unit}`);
      
      products.push({
        name: name.substring(0, 100),
        price,
        originalPrice,
        discount: originalPrice && originalPrice > price ? Math.round((1 - price / originalPrice) * 100) : null,
        weight,
        unit,
        pricePerKg,
        url,
        parsedAt: new Date().toISOString()
      });
      
    } catch (e) {
      console.error(`SmartCart [${index}]: ошибка`, e);
    }
  });
  
  // Убираем дубликаты
  const unique = [];
  const seen = new Set();
  products.forEach(p => {
    const key = `${p.name.toLowerCase()}-${p.price}-${p.weight}`;
    if (!seen.has(key)) {
      seen.add(key);
      unique.push(p);
    }
  });
  
  console.log(`SmartCart: итого ${unique.length} уникальных товаров`);
  return unique;
}

// === ЭКСПОРТ ===
function exportData() {
  const data = { exportedAt: new Date().toISOString(), stores: state.parsedData };
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `smartcart-${new Date().toISOString().slice(0, 10)}.json`;
  a.click();
  URL.revokeObjectURL(url);
  showNotification('💾 JSON сохранён!');
}

// === ОТПРАВКА НА СЕРВЕР ===
const API_BASE = 'https://cw95865.tmweb.ru';

async function sendToServer(storeId, productKey, products) {
  try {
    const payload = {
      exportedAt: new Date().toISOString(),
      store: storeId,
      category: productKey,
      products: products.map(p => ({
        name: p.name,
        price: p.price,
        originalPrice: p.originalPrice,
        discount: p.discount,
        weight: p.weight,
        unit: p.unit,
        pricePerKg: p.pricePerKg,
        url: p.url,
        parsedAt: p.parsedAt
      }))
    };
    
    console.log(`SmartCart: отправляю ${products.length} товаров на сервер...`);
    
    const response = await fetch(`${API_BASE}/api/prices/bulk`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload)
    });
    
    if (response.ok) {
      const result = await response.json();
      console.log('SmartCart: данные отправлены на сервер', result);
      return true;
    } else {
      console.error('SmartCart: ошибка сервера', response.status);
      return false;
    }
  } catch (e) {
    console.error('SmartCart: не удалось отправить на сервер', e);
    return false;
  }
}

// === STORAGE ===
async function loadState() {
  try {
    const data = await chrome.storage.local.get(['parsedData', 'completedQuests', 'isMinimized']);
    if (data.parsedData) state.parsedData = data.parsedData;
    if (data.completedQuests) state.completedQuests = data.completedQuests;
    if (data.isMinimized !== undefined) state.isMinimized = data.isMinimized;
  } catch (e) {
    console.error('Ошибка загрузки:', e);
  }
}

async function saveState() {
  try {
    await chrome.storage.local.set({
      parsedData: state.parsedData,
      completedQuests: state.completedQuests,
      isMinimized: state.isMinimized
    });
  } catch (e) {
    console.error('Ошибка сохранения:', e);
  }
}

// === УВЕДОМЛЕНИЯ ===
function showNotification(message, type = 'success') {
  const existing = document.getElementById('sc-notification');
  if (existing) existing.remove();
  
  const notif = document.createElement('div');
  notif.id = 'sc-notification';
  notif.className = `sc-notification ${type}`;
  notif.textContent = message;
  document.body.appendChild(notif);
  
  setTimeout(() => notif.classList.add('show'), 10);
  setTimeout(() => {
    notif.classList.remove('show');
    setTimeout(() => notif.remove(), 300);
  }, 3000);
}

// === СТИЛИ ===
function injectStyles() {
  if (document.getElementById('smartcart-styles')) return;
  
  const style = document.createElement('style');
  style.id = 'smartcart-styles';
  style.textContent = `
    #smartcart-panel {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      z-index: 999999;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .sc-panel {
      background: linear-gradient(180deg, #0f1419 0%, #080b0f 100%);
      border-top: 3px solid #00d4ff;
      box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.6);
    }
    
    .sc-panel.minimized .sc-body { display: none; }
    
    .sc-header {
      display: flex;
      align-items: center;
      padding: 14px 24px;
      background: rgba(0, 212, 255, 0.08);
      border-bottom: 1px solid rgba(0, 212, 255, 0.15);
      cursor: pointer;
    }
    
    .sc-logo {
      font-size: 22px;
      font-weight: 800;
      color: #00d4ff;
      margin-right: 24px;
      text-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
    }
    
    .sc-page-info {
      flex: 1;
      display: flex;
      gap: 12px;
      align-items: center;
    }
    
    .sc-store-badge {
      background: linear-gradient(135deg, #00d4ff, #7c3aed);
      color: #fff;
      padding: 6px 16px;
      border-radius: 20px;
      font-size: 15px;
      font-weight: 700;
    }
    
    .sc-query-badge {
      background: rgba(0, 212, 255, 0.15);
      color: #00d4ff;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 15px;
      font-weight: 500;
    }
    
    .sc-hint {
      color: #6b7280;
      font-size: 16px;
    }
    
    .sc-toggle {
      background: rgba(0, 212, 255, 0.15);
      border: 2px solid rgba(0, 212, 255, 0.4);
      color: #00d4ff;
      width: 44px;
      height: 44px;
      border-radius: 10px;
      cursor: pointer;
      font-size: 20px;
      font-weight: bold;
      transition: all 0.2s;
    }
    
    .sc-toggle:hover {
      background: rgba(0, 212, 255, 0.25);
      transform: scale(1.05);
    }
    
    .sc-body {
      padding: 18px 24px;
      max-height: 200px;
      overflow-y: auto;
    }
    
    .sc-body::-webkit-scrollbar { width: 8px; }
    .sc-body::-webkit-scrollbar-thumb { background: rgba(0, 212, 255, 0.3); border-radius: 4px; }
    
    .sc-section-title {
      font-size: 16px;
      font-weight: 600;
      color: #9ca3af;
      margin-bottom: 14px;
    }
    
    /* Продукты */
    .sc-products-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 16px;
    }
    
    .sc-product-btn {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 16px;
      background: rgba(31, 41, 55, 0.9);
      border: 2px solid rgba(0, 212, 255, 0.2);
      border-radius: 10px;
      color: #e5e7eb;
      font-size: 15px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .sc-product-btn:hover {
      background: rgba(0, 212, 255, 0.15);
      border-color: #00d4ff;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 212, 255, 0.2);
    }
    
    .sc-product-btn.done {
      background: rgba(34, 197, 94, 0.15);
      border-color: rgba(34, 197, 94, 0.4);
      color: #22c55e;
    }
    
    .sc-emoji { font-size: 18px; }
    
    .sc-count {
      background: #00d4ff;
      color: #000;
      padding: 2px 10px;
      border-radius: 12px;
      font-size: 13px;
      font-weight: 700;
    }
    
    .sc-check { color: #22c55e; font-size: 18px; font-weight: bold; }
    
    /* Магазины */
    .sc-stores-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 12px;
      margin-bottom: 18px;
    }
    
    .sc-store-btn {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 14px 10px;
      background: rgba(31, 41, 55, 0.9);
      border: 2px solid rgba(0, 212, 255, 0.2);
      border-radius: 12px;
      color: #e5e7eb;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .sc-store-btn:hover {
      background: rgba(0, 212, 255, 0.15);
      border-color: #00d4ff;
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(0, 212, 255, 0.25);
    }
    
    .sc-store-btn.complete {
      background: rgba(34, 197, 94, 0.15);
      border-color: rgba(34, 197, 94, 0.4);
    }
    
    .sc-store-name { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
    .sc-store-progress { font-size: 14px; color: #6b7280; font-weight: 600; }
    .sc-store-progress.has-data { color: #22c55e; }
    
    /* Поиск */
    .sc-search-panel {
      display: flex;
      align-items: center;
      gap: 24px;
    }
    
    .sc-main-action { display: flex; flex-direction: column; gap: 10px; min-width: 280px; }
    
    .sc-btn {
      padding: 12px 24px;
      border: none;
      border-radius: 10px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .sc-btn-primary {
      background: linear-gradient(135deg, #00d4ff, #7c3aed);
      color: #fff;
      box-shadow: 0 4px 15px rgba(0, 212, 255, 0.3);
    }
    
    .sc-btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 25px rgba(0, 212, 255, 0.5);
    }
    
    .sc-btn-large {
      padding: 16px 32px;
      font-size: 17px;
    }
    
    .sc-btn-scroll {
      background: rgba(124, 58, 237, 0.2);
      border: 2px solid rgba(124, 58, 237, 0.5);
      color: #a78bfa;
      padding: 12px 20px;
      font-size: 14px;
    }
    
    .sc-btn-scroll:hover {
      background: rgba(124, 58, 237, 0.3);
    }
    
    .sc-btn-next {
      background: rgba(34, 197, 94, 0.2);
      border: 2px solid rgba(34, 197, 94, 0.5);
      color: #22c55e;
      padding: 14px 24px;
      font-size: 16px;
    }
    
    .sc-btn-next:hover {
      background: rgba(34, 197, 94, 0.3);
      transform: translateY(-2px);
    }
    
    .sc-btn-export {
      background: rgba(0, 212, 255, 0.15);
      border: 2px solid rgba(0, 212, 255, 0.4);
      color: #00d4ff;
      padding: 10px 20px;
    }
    
    .sc-saved-info { font-size: 15px; color: #22c55e; font-weight: 600; }
    .sc-all-done { font-size: 16px; color: #22c55e; font-weight: 600; }
    
    .sc-navigation { flex: 1; }
    .sc-progress-box { margin-left: auto; }
    
    /* Прогресс */
    .sc-progress {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .sc-progress-label { font-size: 14px; color: #9ca3af; min-width: 140px; }
    
    .sc-progress-bar {
      width: 180px;
      height: 10px;
      background: rgba(0, 212, 255, 0.15);
      border-radius: 5px;
      overflow: hidden;
    }
    
    .sc-progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #00d4ff, #22c55e);
      transition: width 0.4s;
    }
    
    .sc-progress-percent {
      font-size: 16px;
      font-weight: 700;
      color: #00d4ff;
      min-width: 50px;
    }
    
    /* Статистика */
    .sc-stats { display: flex; align-items: center; gap: 30px; }
    .sc-stat { display: flex; align-items: baseline; gap: 8px; }
    .sc-stat-value { font-size: 28px; font-weight: 800; color: #00d4ff; }
    .sc-stat-label { font-size: 14px; color: #6b7280; }
    
    /* Уведомления */
    .sc-notification {
      position: fixed;
      top: 24px;
      right: 24px;
      padding: 16px 28px;
      background: #22c55e;
      color: #fff;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 700;
      box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
      transform: translateX(130%);
      transition: transform 0.3s ease;
      z-index: 9999999;
    }
    
    .sc-notification.show { transform: translateX(0); }
    .sc-notification.error { background: #ef4444; }
    
    /* Кнопка показа результатов */
    .sc-btn-mini {
      padding: 4px 12px;
      background: rgba(0, 212, 255, 0.15);
      border: 1px solid rgba(0, 212, 255, 0.3);
      border-radius: 15px;
      color: #00d4ff;
      font-size: 13px;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .sc-btn-mini:hover {
      background: rgba(0, 212, 255, 0.25);
    }
    
    /* Модалка результатов */
    .sc-modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 99999999;
    }
    
    .sc-modal {
      background: #0f1419;
      border: 2px solid #00d4ff;
      border-radius: 12px;
      width: 90%;
      max-width: 800px;
      max-height: 80vh;
      display: flex;
      flex-direction: column;
      box-shadow: 0 10px 50px rgba(0, 212, 255, 0.3);
    }
    
    .sc-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 20px;
      border-bottom: 1px solid rgba(0, 212, 255, 0.2);
    }
    
    .sc-modal-header h3 {
      margin: 0;
      color: #00d4ff;
      font-size: 18px;
    }
    
    .sc-modal-close {
      background: none;
      border: none;
      color: #6b7280;
      font-size: 24px;
      cursor: pointer;
      padding: 0;
      line-height: 1;
    }
    
    .sc-modal-close:hover { color: #ef4444; }
    
    .sc-modal-body {
      padding: 16px 20px;
      overflow-y: auto;
      flex: 1;
    }
    
    .sc-modal-footer {
      padding: 12px 20px;
      border-top: 1px solid rgba(0, 212, 255, 0.2);
    }
    
    .sc-empty {
      color: #6b7280;
      text-align: center;
      padding: 40px;
    }
    
    .sc-results-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }
    
    .sc-results-table th {
      text-align: left;
      padding: 10px 8px;
      border-bottom: 2px solid rgba(0, 212, 255, 0.3);
      color: #00d4ff;
      font-weight: 600;
    }
    
    .sc-results-table td {
      padding: 10px 8px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      color: #e5e7eb;
    }
    
    .sc-results-table tr:hover {
      background: rgba(0, 212, 255, 0.05);
    }
    
    .sc-name-cell {
      max-width: 300px;
    }
    
    .sc-price-cell {
      white-space: nowrap;
    }
    
    .sc-old-price {
      text-decoration: line-through;
      color: #6b7280;
      font-size: 12px;
      margin-left: 6px;
    }
    
    .sc-btn-test {
      background: rgba(124, 58, 237, 0.2);
      border: 1px solid rgba(124, 58, 237, 0.4);
      color: #a78bfa;
      padding: 8px 16px;
      font-size: 13px;
    }
    
    #sc-results-modal { display: none; }
  `;
  
  document.head.appendChild(style);
}

// === ИНИЦИАЛИЗАЦИЯ ===
async function init() {
  console.log('🛒 SmartCart: Запуск...');
  await loadState();
  injectStyles();
  createPanel();
  
  // SPA навигация
  let lastUrl = window.location.href;
  const observer = new MutationObserver(() => {
    if (window.location.href !== lastUrl) {
      lastUrl = window.location.href;
      setTimeout(updatePanel, 500);
    }
  });
  observer.observe(document.body, { childList: true, subtree: true });
  
  window.addEventListener('popstate', () => setTimeout(updatePanel, 500));
  
  console.log('🛒 SmartCart: Готов!');
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
