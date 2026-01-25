/**
 * 🛒 SmartCart - Popup Script v2
 * С реальными ссылками на магазины и системой сбора цен
 */

// === КОНФИГУРАЦИЯ МАГАЗИНОВ ===
const STORES = {
  perekrestok: {
    name: 'Перекрёсток',
    baseUrl: 'https://market-delivery.yandex.ru/retail/perekrestok?placeSlug=perekrestok_7stl6&relatedBrandSlug=perekrestok',
    color: '#4CAF50'
  },
  magnit: {
    name: 'Магнит',
    baseUrl: 'https://market-delivery.yandex.ru/retail/magnit_celevaya?placeSlug=magnit_celevaya_pmnr6&relatedBrandSlug=magnit_celevaya',
    color: '#E91E63'
  },
  pyaterochka: {
    name: 'Пятёрочка',
    baseUrl: 'https://market-delivery.yandex.ru/retail/paterocka?placeSlug=pyaterochka_ciskb&relatedBrandSlug=paterocka',
    color: '#FF5722'
  },
  vkusvill: {
    name: 'ВкусВилл',
    baseUrl: 'https://market-delivery.yandex.ru/retail/vkusvill?placeSlug=vkusvill_ekspress_cs6mz&relatedBrandSlug=vkusvill',
    color: '#8BC34A'
  },
  vkusvill_giper: {
    name: 'ВкусВилл Гипер',
    baseUrl: 'https://market-delivery.yandex.ru/retail/vkusvill_giper?placeSlug=vkusvill_qcpbx&relatedBrandSlug=vkusvill_giper',
    color: '#689F38'
  },
  lenta: {
    name: 'Гиперлента',
    baseUrl: 'https://market-delivery.yandex.ru/retail/lenta?placeSlug=lenta_zrmdq&relatedBrandSlug=lenta',
    color: '#2196F3'
  },
  lenta_super: {
    name: 'Супер Лента',
    baseUrl: 'https://market-delivery.yandex.ru/retail/lenta_onlajn?placeSlug=lenta_zvdfl&relatedBrandSlug=lenta_onlajn',
    color: '#1976D2'
  },
  dixy: {
    name: 'Дикси',
    baseUrl: 'https://market-delivery.yandex.ru/retail/diksi_celevaa?placeSlug=diksi_celevaya_f328j&relatedBrandSlug=diksi_celevaa',
    color: '#F44336'
  },
  chizhik: {
    name: 'Чижик',
    baseUrl: 'https://market-delivery.yandex.ru/retail/cizik?placeSlug=chizhik_8csdz&relatedBrandSlug=cizik',
    color: '#FFC107'
  },
  verny: {
    name: 'Верный',
    baseUrl: 'https://market-delivery.yandex.ru/retail/vernyj_obaij?placeSlug=vernyj_mira_9a&relatedBrandSlug=vernyj_obaij',
    color: '#9C27B0'
  }
};

// === СПИСОК ПРОДУКТОВ ДЛЯ ПОИСКА ===
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
  currentStore: null,
  currentProduct: null,
  serverUrl: 'https://cw95865.tmweb.ru',
  parsedData: {},
  completedQuests: {},
};

// === ИНИЦИАЛИЗАЦИЯ ===
document.addEventListener('DOMContentLoaded', async () => {
  await loadState();
  renderUI();
});

async function loadState() {
  try {
    const data = await chrome.storage.local.get([
      'serverUrl', 'parsedData', 'completedQuests', 'currentStore'
    ]);
    if (data.serverUrl) state.serverUrl = data.serverUrl;
    if (data.parsedData) state.parsedData = data.parsedData;
    if (data.completedQuests) state.completedQuests = data.completedQuests;
    if (data.currentStore) state.currentStore = data.currentStore;
  } catch (e) {
    console.error('Ошибка загрузки:', e);
  }
}

async function saveState() {
  try {
    await chrome.storage.local.set({
      serverUrl: state.serverUrl,
      parsedData: state.parsedData,
      completedQuests: state.completedQuests,
      currentStore: state.currentStore
    });
  } catch (e) {
    console.error('Ошибка сохранения:', e);
  }
}

// === РЕНДЕРИНГ ===
function renderUI() {
  const app = document.getElementById('app');
  
  let totalProducts = 0;
  let totalStores = 0;
  Object.entries(state.parsedData).forEach(([store, products]) => {
    const hasData = Object.keys(products).length > 0;
    if (hasData) totalStores++;
    Object.values(products).forEach(items => {
      totalProducts += items.length;
    });
  });
  
  app.innerHTML = `
    <div class="header">
      <div class="logo">🛒 <span>SmartCart</span></div>
      <div class="stats-mini">${totalProducts} товаров • ${totalStores} магазинов</div>
    </div>
    
    <div class="tabs">
      <button class="tab active" data-tab="collect">📊 Сбор</button>
      <button class="tab" data-tab="data">💾 Данные</button>
      <button class="tab" data-tab="settings">⚙️</button>
    </div>
    
    <div class="panel active" id="panel-collect">
      ${renderCollectPanel()}
    </div>
    
    <div class="panel" id="panel-data">
      ${renderDataPanel()}
    </div>
    
    <div class="panel" id="panel-settings">
      ${renderSettingsPanel()}
    </div>
    
    <div class="notification" id="notification"></div>
  `;
  
  initEventListeners();
}

function renderCollectPanel() {
  const storeProgress = {};
  Object.keys(STORES).forEach(storeId => {
    const completed = state.completedQuests[storeId]?.length || 0;
    storeProgress[storeId] = Math.round((completed / PRODUCTS.length) * 100);
  });
  
  return `
    <div class="section">
      <div class="section-title">1️⃣ Выбери магазин</div>
      <div class="store-grid">
        ${Object.entries(STORES).map(([id, store]) => `
          <button class="store-btn ${state.currentStore === id ? 'active' : ''}" data-store="${id}">
            <span class="store-name">${store.name}</span>
            <span class="store-progress">${storeProgress[id]}%</span>
          </button>
        `).join('')}
      </div>
    </div>
    
    ${state.currentStore ? `
    <div class="section">
      <div class="section-title">2️⃣ Кликни на продукт → откроется поиск</div>
      <div class="product-grid">
        ${renderProductButtons()}
      </div>
    </div>
    
    <div class="section">
      <div class="section-title">3️⃣ На странице нажми</div>
      <button class="btn btn-primary" id="scanBtn">
        🔍 Собрать товары со страницы
      </button>
    </div>
    ` : `
    <div class="empty-hint">👆 Выбери магазин</div>
    `}
  `;
}

function renderProductButtons() {
  const completed = state.completedQuests[state.currentStore] || [];
  
  const categories = {
    meat: { name: '🍖 Мясо', items: [] },
    fish: { name: '🐟 Рыба', items: [] },
    eggs: { name: '🥚 Яйца', items: [] },
    dairy: { name: '🥛 Молочка', items: [] },
    cereals: { name: '🌾 Крупы', items: [] },
    vegetables: { name: '🥬 Овощи', items: [] },
    bread: { name: '🍞 Хлеб', items: [] },
    other: { name: '📦 Другое', items: [] },
  };
  
  PRODUCTS.forEach(p => {
    if (categories[p.category]) {
      categories[p.category].items.push(p);
    }
  });
  
  return Object.entries(categories).map(([catId, cat]) => {
    if (cat.items.length === 0) return '';
    
    return `
      <div class="product-category">
        <div class="cat-name">${cat.name}</div>
        ${cat.items.map(p => {
          const isDone = completed.includes(p.id);
          const count = state.parsedData[state.currentStore]?.[p.id]?.length || 0;
          return `
            <button class="product-btn ${isDone ? 'done' : ''}" data-product="${p.id}">
              ${p.name}
              ${count > 0 ? `<span class="cnt">${count}</span>` : ''}
              ${isDone ? '✓' : ''}
            </button>
          `;
        }).join('')}
      </div>
    `;
  }).join('');
}

function renderDataPanel() {
  let totalProducts = 0;
  const storeStats = [];
  
  Object.entries(state.parsedData).forEach(([storeId, products]) => {
    let storeTotal = 0;
    Object.values(products).forEach(items => {
      storeTotal += items.length;
      totalProducts += items.length;
    });
    if (storeTotal > 0) {
      storeStats.push({ name: STORES[storeId]?.name || storeId, count: storeTotal });
    }
  });
  
  return `
    <div class="section">
      <div class="section-title">📊 Собрано</div>
      <div class="stats-row">
        <div class="stat-box"><div class="val">${totalProducts}</div><div class="lbl">товаров</div></div>
        <div class="stat-box"><div class="val">${storeStats.length}</div><div class="lbl">магазинов</div></div>
      </div>
      
      ${storeStats.map(s => `
        <div class="data-row">${s.name} <span>${s.count}</span></div>
      `).join('')}
    </div>
    
    <div class="section">
      <button class="btn" id="exportJsonBtn">💾 Скачать JSON</button>
      <button class="btn" id="sendServerBtn">📤 Отправить на сервер</button>
      <button class="btn btn-danger" id="clearDataBtn">🗑️ Очистить всё</button>
    </div>
  `;
}

function renderSettingsPanel() {
  return `
    <div class="section">
      <div class="section-title">🌐 Сервер</div>
      <input type="text" id="serverUrlInput" class="input" value="${state.serverUrl}">
      <button class="btn" id="testServerBtn">🔗 Проверить</button>
    </div>
    <div class="section">
      <p class="hint">SmartCart v2.0</p>
    </div>
  `;
}

// === ОБРАБОТЧИКИ ===
function initEventListeners() {
  // Табы
  document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById(`panel-${tab.dataset.tab}`).classList.add('active');
    });
  });
  
  // Магазины
  document.querySelectorAll('.store-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const storeId = btn.dataset.store;
      state.currentStore = storeId;
      state.currentProduct = null;
      await saveState();
      
      // Открываем страницу магазина
      const store = STORES[storeId];
      if (store) {
        chrome.tabs.create({ url: store.baseUrl, active: true });
      }
      renderUI();
    });
  });
  
  // Продукты
  document.querySelectorAll('.product-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const productId = btn.dataset.product;
      const product = PRODUCTS.find(p => p.id === productId);
      const store = STORES[state.currentStore];
      
      if (!product || !store) return;
      
      state.currentProduct = productId;
      await saveState();
      
      // URL поиска — добавляем query к baseUrl
      const searchUrl = store.baseUrl + '&query=' + encodeURIComponent(product.query);
      chrome.tabs.create({ url: searchUrl, active: true });
    });
  });
  
  // Сканирование
  const scanBtn = document.getElementById('scanBtn');
  if (scanBtn) {
    scanBtn.addEventListener('click', scanPage);
  }
  
  // Экспорт
  const exportBtn = document.getElementById('exportJsonBtn');
  if (exportBtn) {
    exportBtn.addEventListener('click', exportJson);
  }
  
  // Отправка на сервер
  const sendBtn = document.getElementById('sendServerBtn');
  if (sendBtn) {
    sendBtn.addEventListener('click', sendToServer);
  }
  
  // Очистка
  const clearBtn = document.getElementById('clearDataBtn');
  if (clearBtn) {
    clearBtn.addEventListener('click', async () => {
      if (confirm('Удалить все данные?')) {
        state.parsedData = {};
        state.completedQuests = {};
        await saveState();
        renderUI();
        showNotification('Данные удалены');
      }
    });
  }
  
  // Тест сервера
  const testBtn = document.getElementById('testServerBtn');
  if (testBtn) {
    testBtn.addEventListener('click', testServer);
  }
  
  // URL сервера
  const serverInput = document.getElementById('serverUrlInput');
  if (serverInput) {
    serverInput.addEventListener('change', async (e) => {
      state.serverUrl = e.target.value;
      await saveState();
    });
  }
}

// === ПАРСИНГ ===
async function scanPage() {
  const btn = document.getElementById('scanBtn');
  btn.textContent = '⏳ Сканирую...';
  btn.disabled = true;
  
  try {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    
    if (!tab.url.includes('market-delivery.yandex.ru')) {
      showNotification('Открой market-delivery.yandex.ru', 'error');
      return;
    }
    
    const results = await chrome.scripting.executeScript({
      target: { tabId: tab.id },
      func: extractProducts
    });
    
    if (results && results[0]?.result) {
      const products = results[0].result;
      
      if (products.length > 0) {
        if (!state.parsedData[state.currentStore]) {
          state.parsedData[state.currentStore] = {};
        }
        
        const productKey = state.currentProduct || 'other';
        state.parsedData[state.currentStore][productKey] = products;
        
        if (state.currentProduct) {
          if (!state.completedQuests[state.currentStore]) {
            state.completedQuests[state.currentStore] = [];
          }
          if (!state.completedQuests[state.currentStore].includes(state.currentProduct)) {
            state.completedQuests[state.currentStore].push(state.currentProduct);
          }
        }
        
        await saveState();
        renderUI();
        showNotification(`✅ Сохранено ${products.length} товаров`);
      } else {
        showNotification('Товары не найдены', 'error');
      }
    }
  } catch (e) {
    console.error('Ошибка:', e);
    showNotification('Ошибка сканирования', 'error');
  } finally {
    btn.textContent = '🔍 Собрать товары со страницы';
    btn.disabled = false;
  }
}

function extractProducts() {
  const products = [];
  
  const selectors = [
    '[data-testid="product-card"]',
    '[class*="ProductCard"]',
    '[class*="product-card"]',
    '[class*="sku-card"]',
    'article',
  ];
  
  let cards = [];
  for (const sel of selectors) {
    cards = document.querySelectorAll(sel);
    if (cards.length > 0) break;
  }
  
  cards.forEach(card => {
    try {
      const nameEl = card.querySelector('span, h3, h4, [class*="name"], [class*="title"]');
      const name = nameEl?.textContent?.trim();
      if (!name || name.length < 3) return;
      
      const allText = card.textContent;
      const priceMatches = allText.match(/(\d+)\s*₽/g);
      if (!priceMatches) return;
      
      const prices = priceMatches.map(p => parseInt(p.replace(/\D/g, ''))).filter(p => p > 0);
      if (prices.length === 0) return;
      
      const price = Math.min(...prices);
      const originalPrice = prices.length > 1 ? Math.max(...prices) : null;
      
      const weightMatch = allText.match(/(\d+(?:[.,]\d+)?)\s*(г|кг|мл|л|шт)/i);
      const weight = weightMatch ? parseFloat(weightMatch[1].replace(',', '.')) : null;
      const unit = weightMatch ? weightMatch[2].toLowerCase() : null;
      
      let pricePerKg = null;
      if (weight && unit) {
        if (unit === 'г') pricePerKg = Math.round(price / weight * 1000);
        if (unit === 'кг') pricePerKg = Math.round(price / weight);
      }
      
      const link = card.querySelector('a');
      const url = link?.href || '';
      
      products.push({
        name, price, originalPrice,
        discount: originalPrice ? Math.round((1 - price / originalPrice) * 100) : null,
        weight, unit, pricePerKg, url,
        parsedAt: new Date().toISOString()
      });
    } catch (e) {}
  });
  
  const unique = [];
  const seen = new Set();
  products.forEach(p => {
    const key = `${p.name}-${p.price}`;
    if (!seen.has(key)) {
      seen.add(key);
      unique.push(p);
    }
  });
  
  return unique;
}

// === ЭКСПОРТ ===
function exportJson() {
  const data = { exportedAt: new Date().toISOString(), stores: state.parsedData };
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `smartcart-${new Date().toISOString().slice(0, 10)}.json`;
  a.click();
  URL.revokeObjectURL(url);
  showNotification('JSON сохранён');
}

async function sendToServer() {
  const btn = document.getElementById('sendServerBtn');
  btn.textContent = '⏳ ...';
  btn.disabled = true;
  
  try {
    const payload = { exportedAt: new Date().toISOString(), stores: {} };
    
    Object.entries(state.parsedData).forEach(([storeId, products]) => {
      const all = [];
      Object.entries(products).forEach(([key, items]) => {
        items.forEach(item => all.push({ ...item, searchCategory: key }));
      });
      if (all.length > 0) payload.stores[storeId] = all;
    });
    
    const response = await fetch(`${state.serverUrl}/api/prices/bulk`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    
    if (response.ok) {
      showNotification('✅ Отправлено');
    } else {
      throw new Error();
    }
  } catch (e) {
    showNotification('Ошибка', 'error');
  } finally {
    btn.textContent = '📤 Отправить на сервер';
    btn.disabled = false;
  }
}

async function testServer() {
  try {
    const response = await fetch(`${state.serverUrl}/api/stores`);
    showNotification(response.ok ? '✅ Сервер OK' : '❌ Ошибка', response.ok ? 'success' : 'error');
  } catch (e) {
    showNotification('❌ Недоступен', 'error');
  }
}

function showNotification(message, type = 'success') {
  const el = document.getElementById('notification');
  el.textContent = message;
  el.className = `notification show ${type}`;
  setTimeout(() => el.classList.remove('show'), 2500);
}
