/**
 * 🛒 SmartCart - Popup Script
 * Основная логика всплывающего окна расширения
 */

// === КОНФИГУРАЦИЯ ===
const CONFIG = {
  // Базовый URL Delivery Club
  deliveryBaseUrl: 'https://market-delivery.yandex.ru',
  
  // Категории для парсинга с URL путями
  categories: [
    { slug: 'meat', name: 'Мясо и птица', emoji: '🍗', path: 'myaso-i-ptitsa' },
    { slug: 'fish', name: 'Рыба и морепродукты', emoji: '🐟', path: 'ryba-i-moreprodukty' },
    { slug: 'dairy', name: 'Молочные продукты', emoji: '🥛', path: 'molochnye-produkty' },
    { slug: 'eggs', name: 'Яйца', emoji: '🥚', path: 'yaytsa' },
    { slug: 'cereals', name: 'Крупы и макароны', emoji: '🌾', path: 'krupy-i-makarony' },
    { slug: 'vegetables', name: 'Овощи и фрукты', emoji: '🥬', path: 'ovoschi-i-frukty' },
    { slug: 'bread', name: 'Хлеб и выпечка', emoji: '🍞', path: 'khleb-i-vypechka' },
    { slug: 'drinks', name: 'Напитки', emoji: '☕', path: 'napitki' },
  ],
  
  // Магазины
  stores: {
    perekrestok: { name: 'Перекрёсток', slug: 'perekrestok' },
    pyaterochka: { name: 'Пятёрочка', slug: 'pyaterochka' },
    magnit: { name: 'Магнит', slug: 'magnit' },
    vkusvill: { name: 'ВкусВилл', slug: 'vkusvill' },
    lenta: { name: 'Лента', slug: 'lenta' },
    dixy: { name: 'Дикси', slug: 'dixy' },
  }
};

// === СОСТОЯНИЕ ===
let state = {
  currentStore: 'perekrestok',
  serverUrl: 'https://cw95865.tmweb.ru',
  parsedProducts: {},        // { store: { category: [products] } }
  parsedCategories: {},      // { store: [category_slugs] }
  shoppingList: [],          // Текущий список покупок
  currentShoppingItem: null, // Текущий товар для покупки
  isConnected: false
};

// === ИНИЦИАЛИЗАЦИЯ ===
document.addEventListener('DOMContentLoaded', async () => {
  await loadState();
  initTabs();
  initStoreSelector();
  renderCategories();
  renderStats();
  initEventListeners();
  checkConnection();
});

// === ЗАГРУЗКА/СОХРАНЕНИЕ СОСТОЯНИЯ ===
async function loadState() {
  try {
    const data = await chrome.storage.local.get([
      'serverUrl',
      'parsedProducts',
      'parsedCategories',
      'shoppingList',
      'currentStore'
    ]);
    
    if (data.serverUrl) state.serverUrl = data.serverUrl;
    if (data.parsedProducts) state.parsedProducts = data.parsedProducts;
    if (data.parsedCategories) state.parsedCategories = data.parsedCategories;
    if (data.shoppingList) state.shoppingList = data.shoppingList;
    if (data.currentStore) state.currentStore = data.currentStore;
    
    // Обновляем UI
    document.getElementById('serverUrl').value = state.serverUrl;
    
  } catch (e) {
    console.error('Ошибка загрузки состояния:', e);
  }
}

async function saveState() {
  try {
    await chrome.storage.local.set({
      serverUrl: state.serverUrl,
      parsedProducts: state.parsedProducts,
      parsedCategories: state.parsedCategories,
      shoppingList: state.shoppingList,
      currentStore: state.currentStore
    });
  } catch (e) {
    console.error('Ошибка сохранения:', e);
  }
}

// === ТАБЫ ===
function initTabs() {
  const tabs = document.querySelectorAll('.tab');
  
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
      
      tab.classList.add('active');
      const panelId = `panel-${tab.dataset.tab}`;
      document.getElementById(panelId).classList.add('active');
      
      // Обновляем контент при переключении
      if (tab.dataset.tab === 'shop') {
        renderShoppingList();
      } else if (tab.dataset.tab === 'settings') {
        renderLocalStats();
      }
    });
  });
}

// === ВЫБОР МАГАЗИНА ===
function initStoreSelector() {
  const buttons = document.querySelectorAll('.store-btn');
  
  // Устанавливаем активный магазин из состояния
  buttons.forEach(btn => {
    btn.classList.toggle('active', btn.dataset.store === state.currentStore);
    
    btn.addEventListener('click', () => {
      buttons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      state.currentStore = btn.dataset.store;
      saveState();
      renderCategories();
    });
  });
}

// === КАТЕГОРИИ ===
function renderCategories() {
  const container = document.getElementById('categoryList');
  const store = state.currentStore;
  const parsedCats = state.parsedCategories[store] || [];
  
  container.innerHTML = CONFIG.categories.map(cat => {
    const isParsed = parsedCats.includes(cat.slug);
    const productCount = state.parsedProducts[store]?.[cat.slug]?.length || 0;
    
    return `
      <button class="category-btn ${isParsed ? 'parsed' : ''}" data-slug="${cat.slug}" data-path="${cat.path}">
        <span>
          <span class="emoji">${cat.emoji}</span>
          ${cat.name}
        </span>
        <span class="count">${productCount > 0 ? productCount + ' товаров' : ''}</span>
        <span class="status-icon">${isParsed ? '✅' : '➡️'}</span>
      </button>
    `;
  }).join('');
  
  // Обработчики на кнопки категорий
  container.querySelectorAll('.category-btn').forEach(btn => {
    btn.addEventListener('click', () => openCategory(btn.dataset.slug, btn.dataset.path));
  });
}

async function openCategory(slug, path) {
  const store = state.currentStore;
  
  // Формируем URL категории
  // Примерный формат: https://market-delivery.yandex.ru/retail/{store}/category/{path}
  const url = `${CONFIG.deliveryBaseUrl}/retail/${store}/category/${path}`;
  
  try {
    // Открываем в новой вкладке
    await chrome.tabs.create({ url, active: true });
    
    showNotification(`📂 Открыта категория: ${slug}`);
    
  } catch (e) {
    console.error('Ошибка открытия категории:', e);
    showNotification('❌ Не удалось открыть категорию', 'error');
  }
}

// === ПАРСИНГ ===
async function scanCurrentPage() {
  const btn = document.getElementById('scanPageBtn');
  btn.textContent = '⏳ Сканирование...';
  btn.disabled = true;
  
  try {
    // Получаем активную вкладку
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    
    // Проверяем, что мы на нужном сайте
    if (!tab.url.includes('market-delivery.yandex.ru')) {
      showNotification('⚠️ Откройте market-delivery.yandex.ru', 'error');
      return;
    }
    
    // Внедряем скрипт для парсинга
    const results = await chrome.scripting.executeScript({
      target: { tabId: tab.id },
      func: extractProductsFromPage
    });
    
    if (results && results[0] && results[0].result) {
      const { products, store, category } = results[0].result;
      
      if (products.length > 0) {
        // Сохраняем спарсенные данные
        if (!state.parsedProducts[store]) {
          state.parsedProducts[store] = {};
        }
        
        // Добавляем/обновляем товары
        if (!state.parsedProducts[store][category]) {
          state.parsedProducts[store][category] = [];
        }
        
        // Мержим с существующими (обновляем цены)
        products.forEach(newProduct => {
          const existingIndex = state.parsedProducts[store][category].findIndex(
            p => p.name === newProduct.name
          );
          
          if (existingIndex >= 0) {
            state.parsedProducts[store][category][existingIndex] = newProduct;
          } else {
            state.parsedProducts[store][category].push(newProduct);
          }
        });
        
        // Отмечаем категорию как спарсенную
        if (!state.parsedCategories[store]) {
          state.parsedCategories[store] = [];
        }
        if (!state.parsedCategories[store].includes(category)) {
          state.parsedCategories[store].push(category);
        }
        
        await saveState();
        renderCategories();
        renderStats();
        
        showNotification(`✅ Найдено ${products.length} товаров`);
      } else {
        showNotification('⚠️ Товары не найдены на странице', 'error');
      }
    }
    
  } catch (e) {
    console.error('Ошибка парсинга:', e);
    showNotification('❌ Ошибка парсинга', 'error');
  } finally {
    btn.textContent = '🔍 Собрать товары со страницы';
    btn.disabled = false;
  }
}

// Функция, которая выполняется в контексте страницы
function extractProductsFromPage() {
  const products = [];
  
  // Определяем магазин из URL
  const url = window.location.href;
  let store = 'unknown';
  let category = 'unknown';
  
  // Парсим URL вида: /retail/{store}/category/{category}
  const storeMatch = url.match(/\/retail\/([^\/]+)/);
  if (storeMatch) store = storeMatch[1];
  
  const categoryMatch = url.match(/\/category\/([^\/\?]+)/);
  if (categoryMatch) {
    // Конвертируем path в slug
    const pathToSlug = {
      'myaso-i-ptitsa': 'meat',
      'ryba-i-moreprodukty': 'fish',
      'molochnye-produkty': 'dairy',
      'yaytsa': 'eggs',
      'krupy-i-makarony': 'cereals',
      'ovoschi-i-frukty': 'vegetables',
      'khleb-i-vypechka': 'bread',
      'napitki': 'drinks',
    };
    category = pathToSlug[categoryMatch[1]] || categoryMatch[1];
  }
  
  // Ищем карточки товаров (селекторы могут меняться!)
  const selectors = [
    '[data-testid="product-card"]',
    '[class*="ProductCard"]',
    '[class*="product-card"]',
    'article[class*="product"]',
    '[class*="GoodsList"] > div',
  ];
  
  let cards = [];
  for (const selector of selectors) {
    cards = document.querySelectorAll(selector);
    if (cards.length > 0) break;
  }
  
  cards.forEach(card => {
    try {
      // Название
      const nameEl = card.querySelector('[class*="name"], [class*="title"], [class*="Name"], h3, h4');
      const name = nameEl?.textContent?.trim();
      if (!name) return;
      
      // Цена
      const priceEl = card.querySelector('[class*="price"]:not([class*="old"]), [class*="Price"]:not([class*="Old"])');
      const priceText = priceEl?.textContent || '';
      const priceMatch = priceText.match(/(\d+)/);
      const price = priceMatch ? parseInt(priceMatch[1]) : null;
      if (!price) return;
      
      // Старая цена
      const oldPriceEl = card.querySelector('[class*="old"], [class*="Old"], del, s');
      const oldPriceText = oldPriceEl?.textContent || '';
      const oldPriceMatch = oldPriceText.match(/(\d+)/);
      const originalPrice = oldPriceMatch ? parseInt(oldPriceMatch[1]) : null;
      
      // Скидка
      let discount = null;
      if (originalPrice && price && originalPrice > price) {
        discount = Math.round((1 - price / originalPrice) * 100);
      }
      
      // Вес
      const weightEl = card.querySelector('[class*="weight"], [class*="measure"], [class*="Weight"]');
      let weight = null;
      let unit = 'г';
      if (weightEl) {
        const weightText = weightEl.textContent;
        const weightMatch = weightText.match(/(\d+(?:[.,]\d+)?)\s*(г|кг|мл|л|шт)/i);
        if (weightMatch) {
          weight = parseFloat(weightMatch[1].replace(',', '.'));
          unit = weightMatch[2].toLowerCase();
        }
      }
      
      // URL товара
      const linkEl = card.querySelector('a[href]');
      const productUrl = linkEl?.href || '';
      
      products.push({
        name,
        price,
        originalPrice,
        discount,
        weight,
        unit,
        url: productUrl,
        parsedAt: new Date().toISOString()
      });
      
    } catch (e) {
      console.error('Ошибка парсинга карточки:', e);
    }
  });
  
  return { products, store, category };
}

// === ОТПРАВКА НА СЕРВЕР ===
async function sendToServer() {
  const btn = document.getElementById('sendToServerBtn');
  btn.textContent = '⏳ Отправка...';
  btn.disabled = true;
  
  try {
    const store = state.currentStore;
    const storeProducts = state.parsedProducts[store];
    
    if (!storeProducts || Object.keys(storeProducts).length === 0) {
      showNotification('⚠️ Нет данных для отправки', 'error');
      return;
    }
    
    // Собираем все товары в один массив
    const allProducts = [];
    Object.entries(storeProducts).forEach(([category, products]) => {
      products.forEach(p => {
        allProducts.push({
          ...p,
          category
        });
      });
    });
    
    const payload = {
      store_slug: store,
      parsed_at: new Date().toISOString(),
      products: allProducts
    };
    
    const response = await fetch(`${state.serverUrl}/api/prices/bulk`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload)
    });
    
    if (response.ok) {
      const result = await response.json();
      showNotification(`✅ Отправлено ${allProducts.length} товаров`);
    } else {
      throw new Error(`HTTP ${response.status}`);
    }
    
  } catch (e) {
    console.error('Ошибка отправки:', e);
    showNotification('❌ Ошибка отправки на сервер', 'error');
  } finally {
    btn.textContent = '📤 Отправить на сервер';
    btn.disabled = false;
  }
}

// === СПИСОК ПОКУПОК ===
function renderShoppingList() {
  const container = document.getElementById('shoppingList');
  const card = document.getElementById('shoppingListCard');
  const alert = document.getElementById('noListAlert');
  const startBtn = document.getElementById('startShoppingBtn');
  const compareDiv = document.getElementById('storeCompare');
  
  if (state.shoppingList.length === 0) {
    card.style.display = 'none';
    alert.style.display = 'block';
    startBtn.style.display = 'none';
    compareDiv.style.display = 'none';
    return;
  }
  
  card.style.display = 'block';
  alert.style.display = 'none';
  startBtn.style.display = 'block';
  
  // Считаем прогресс
  const checkedCount = state.shoppingList.filter(i => i.checked).length;
  document.getElementById('listProgress').textContent = `${checkedCount}/${state.shoppingList.length}`;
  
  // Рендерим список
  container.innerHTML = state.shoppingList.map((item, index) => {
    const isCurrent = state.currentShoppingItem === index;
    return `
      <div class="shopping-item ${item.checked ? 'done' : ''} ${isCurrent ? 'highlight' : ''}" data-index="${index}">
        <input type="checkbox" class="checkbox" ${item.checked ? 'checked' : ''} onchange="toggleShoppingItem(${index})">
        <span class="name">${item.name}</span>
        ${item.expectedPrice ? `<span class="price">${item.expectedPrice}₽</span>` : ''}
        <button class="go-btn" onclick="goToProduct(${index})">➡️</button>
      </div>
    `;
  }).join('');
  
  // Рендерим сравнение магазинов
  renderStoreComparison();
}

function renderStoreComparison() {
  const compareDiv = document.getElementById('storeCompare');
  const compareList = document.getElementById('compareList');
  
  if (state.shoppingList.length === 0) {
    compareDiv.style.display = 'none';
    return;
  }
  
  // Считаем стоимость корзины в каждом магазине
  const totals = {};
  
  Object.keys(CONFIG.stores).forEach(storeSlug => {
    totals[storeSlug] = {
      name: CONFIG.stores[storeSlug].name,
      total: 0,
      available: 0,
      missing: 0
    };
    
    state.shoppingList.forEach(item => {
      // Ищем цену этого товара в этом магазине
      const storeProducts = state.parsedProducts[storeSlug];
      if (!storeProducts) {
        totals[storeSlug].missing++;
        return;
      }
      
      // Ищем по всем категориям
      let found = false;
      Object.values(storeProducts).forEach(categoryProducts => {
        const matchingProduct = categoryProducts.find(p => 
          p.name.toLowerCase().includes(item.searchTerm?.toLowerCase() || item.name.toLowerCase())
        );
        if (matchingProduct && !found) {
          totals[storeSlug].total += matchingProduct.price;
          totals[storeSlug].available++;
          found = true;
        }
      });
      
      if (!found) {
        totals[storeSlug].missing++;
      }
    });
  });
  
  // Сортируем по цене
  const sorted = Object.entries(totals)
    .filter(([_, data]) => data.available > 0)
    .sort((a, b) => a[1].total - b[1].total);
  
  if (sorted.length === 0) {
    compareDiv.style.display = 'none';
    return;
  }
  
  compareDiv.style.display = 'block';
  
  compareList.innerHTML = sorted.map(([slug, data], index) => {
    const isBest = index === 0;
    const deliveryTime = '30-60 мин'; // Можно брать из конфига
    
    return `
      <div class="compare-row ${isBest ? 'best' : ''}">
        <span class="store-name">${data.name}</span>
        <span class="total">${data.total}₽</span>
        <span class="delivery">${deliveryTime}</span>
        ${isBest ? '<span class="badge">Лучшая цена</span>' : ''}
      </div>
    `;
  }).join('');
}

// Глобальные функции для onclick
window.toggleShoppingItem = async (index) => {
  state.shoppingList[index].checked = !state.shoppingList[index].checked;
  await saveState();
  renderShoppingList();
};

window.goToProduct = async (index) => {
  const item = state.shoppingList[index];
  state.currentShoppingItem = index;
  await saveState();
  
  // Если есть URL товара — открываем его
  if (item.url) {
    await chrome.tabs.create({ url: item.url, active: true });
  } else {
    // Иначе открываем поиск
    const searchUrl = `${CONFIG.deliveryBaseUrl}/retail/${state.currentStore}/search?query=${encodeURIComponent(item.name)}`;
    await chrome.tabs.create({ url: searchUrl, active: true });
  }
  
  // Отправляем сообщение content script для подсветки
  setTimeout(async () => {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    if (tab) {
      chrome.tabs.sendMessage(tab.id, {
        action: 'highlightProduct',
        searchTerm: item.searchTerm || item.name
      });
    }
  }, 2000);
  
  renderShoppingList();
};

async function importShoppingList() {
  try {
    // Пробуем загрузить с сервера
    const response = await fetch(`${state.serverUrl}/api/cart`);
    
    if (response.ok) {
      const data = await response.json();
      
      if (data.items && data.items.length > 0) {
        state.shoppingList = data.items.map(item => ({
          id: item.id,
          name: item.name,
          searchTerm: item.search_term || item.name,
          quantity: item.quantity || 1,
          expectedPrice: item.expected_price,
          url: item.url,
          checked: false
        }));
        
        await saveState();
        renderShoppingList();
        showNotification(`✅ Импортировано ${data.items.length} товаров`);
      } else {
        showNotification('⚠️ Список на сервере пуст', 'error');
      }
    } else {
      throw new Error('Ошибка загрузки');
    }
    
  } catch (e) {
    console.error('Ошибка импорта:', e);
    
    // Если сервер недоступен — предлагаем ввести вручную
    const input = prompt('Введите список покупок (через запятую):');
    if (input) {
      state.shoppingList = input.split(',').map((name, i) => ({
        id: i + 1,
        name: name.trim(),
        searchTerm: name.trim(),
        checked: false
      }));
      await saveState();
      renderShoppingList();
    }
  }
}

async function startShopping() {
  if (state.shoppingList.length === 0) return;
  
  // Находим первый невыполненный пункт
  const firstUnchecked = state.shoppingList.findIndex(i => !i.checked);
  
  if (firstUnchecked === -1) {
    showNotification('✅ Все товары уже куплены!');
    return;
  }
  
  // Переходим к первому товару
  goToProduct(firstUnchecked);
}

// === СТАТИСТИКА ===
function renderStats() {
  let totalProducts = 0;
  
  Object.values(state.parsedProducts).forEach(storeData => {
    Object.values(storeData).forEach(categoryProducts => {
      totalProducts += categoryProducts.length;
    });
  });
  
  document.getElementById('parsedCount').textContent = totalProducts;
  
  // Последний парсинг
  let lastSync = '—';
  Object.values(state.parsedProducts).forEach(storeData => {
    Object.values(storeData).forEach(categoryProducts => {
      categoryProducts.forEach(p => {
        if (p.parsedAt) {
          const date = new Date(p.parsedAt);
          const now = new Date();
          const diff = now - date;
          
          if (diff < 60000) {
            lastSync = 'только что';
          } else if (diff < 3600000) {
            lastSync = Math.floor(diff / 60000) + ' мин назад';
          } else if (diff < 86400000) {
            lastSync = Math.floor(diff / 3600000) + ' ч назад';
          } else {
            lastSync = date.toLocaleDateString('ru-RU');
          }
        }
      });
    });
  });
  
  document.getElementById('lastSync').textContent = lastSync;
}

function renderLocalStats() {
  let totalProducts = 0;
  let storesCount = 0;
  
  Object.entries(state.parsedProducts).forEach(([store, storeData]) => {
    if (Object.keys(storeData).length > 0) storesCount++;
    Object.values(storeData).forEach(categoryProducts => {
      totalProducts += categoryProducts.length;
    });
  });
  
  document.getElementById('localProductsCount').textContent = totalProducts;
  document.getElementById('localStoresCount').textContent = storesCount;
}

// === НАСТРОЙКИ ===
async function testConnection() {
  const btn = document.getElementById('testConnectionBtn');
  btn.textContent = '⏳ Проверка...';
  btn.disabled = true;
  
  try {
    const response = await fetch(`${state.serverUrl}/api/stores`, {
      method: 'GET',
      headers: { 'Content-Type': 'application/json' }
    });
    
    if (response.ok) {
      state.isConnected = true;
      updateConnectionStatus(true);
      showNotification('✅ Подключение успешно!');
    } else {
      throw new Error(`HTTP ${response.status}`);
    }
    
  } catch (e) {
    state.isConnected = false;
    updateConnectionStatus(false);
    showNotification('❌ Не удалось подключиться', 'error');
  } finally {
    btn.textContent = '🔗 Проверить подключение';
    btn.disabled = false;
  }
}

function updateConnectionStatus(connected) {
  const dot = document.getElementById('statusDot');
  const text = document.getElementById('statusText');
  
  if (connected) {
    dot.classList.remove('offline');
    text.textContent = 'Подключено';
  } else {
    dot.classList.add('offline');
    text.textContent = 'Офлайн';
  }
}

async function exportLocalData() {
  const data = {
    exportedAt: new Date().toISOString(),
    parsedProducts: state.parsedProducts,
    shoppingList: state.shoppingList
  };
  
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  
  const a = document.createElement('a');
  a.href = url;
  a.download = `smartcart-export-${new Date().toISOString().slice(0, 10)}.json`;
  a.click();
  
  URL.revokeObjectURL(url);
  showNotification('✅ Данные экспортированы');
}

async function clearLocalData() {
  if (!confirm('Удалить все локальные данные?')) return;
  
  state.parsedProducts = {};
  state.parsedCategories = {};
  state.shoppingList = [];
  
  await saveState();
  renderCategories();
  renderStats();
  renderLocalStats();
  
  showNotification('🗑️ Данные удалены');
}

async function checkConnection() {
  try {
    const response = await fetch(`${state.serverUrl}/api/stores`, {
      method: 'GET'
    });
    state.isConnected = response.ok;
  } catch (e) {
    state.isConnected = false;
  }
  updateConnectionStatus(state.isConnected);
}

// === ОБРАБОТЧИКИ СОБЫТИЙ ===
function initEventListeners() {
  // Парсинг
  document.getElementById('scanPageBtn').addEventListener('click', scanCurrentPage);
  document.getElementById('sendToServerBtn').addEventListener('click', sendToServer);
  
  // Покупки
  document.getElementById('importListBtn').addEventListener('click', importShoppingList);
  document.getElementById('startShoppingBtn').addEventListener('click', startShopping);
  
  // Настройки
  document.getElementById('serverUrl').addEventListener('change', async (e) => {
    state.serverUrl = e.target.value;
    await saveState();
  });
  
  document.getElementById('testConnectionBtn').addEventListener('click', testConnection);
  document.getElementById('exportLocalBtn').addEventListener('click', exportLocalData);
  document.getElementById('clearLocalBtn').addEventListener('click', clearLocalData);
}

// === УВЕДОМЛЕНИЯ ===
function showNotification(message, type = 'success') {
  const notification = document.getElementById('notification');
  notification.textContent = message;
  notification.className = `notification show ${type === 'error' ? 'error' : ''}`;
  
  setTimeout(() => {
    notification.classList.remove('show');
  }, 2500);
}
