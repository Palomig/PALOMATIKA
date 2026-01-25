/**
 * 🛒 SmartCart - Content Script
 * Работает в контексте страницы market-delivery.yandex.ru
 * 
 * Функции:
 * - Подсветка товаров из списка покупок
 * - Индикация добавления в корзину
 * - Извлечение данных о товарах
 */

console.log('🛒 SmartCart Content Script загружен');

// === СОСТОЯНИЕ ===
let highlightedProduct = null;
let observer = null;

// === СЛУШАТЕЛЬ СООБЩЕНИЙ ОТ POPUP ===
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  console.log('📨 SmartCart: получено сообщение:', request.action);
  
  switch (request.action) {
    case 'highlightProduct':
      highlightProduct(request.searchTerm);
      sendResponse({ success: true });
      break;
      
    case 'clearHighlight':
      clearHighlight();
      sendResponse({ success: true });
      break;
      
    case 'extractProducts':
      const products = extractAllProducts();
      sendResponse({ products });
      break;
      
    case 'ping':
      sendResponse({ status: 'ok', url: window.location.href });
      break;
  }
  
  return true;
});

// === ПОДСВЕТКА ТОВАРА ===
function highlightProduct(searchTerm) {
  console.log('🔍 SmartCart: ищем товар:', searchTerm);
  
  // Сначала убираем предыдущую подсветку
  clearHighlight();
  
  // Ждём загрузки товаров (динамический контент)
  setTimeout(() => {
    findAndHighlight(searchTerm);
  }, 1500);
  
  // Также наблюдаем за изменениями DOM
  startObserver(searchTerm);
}

function findAndHighlight(searchTerm) {
  const cards = findProductCards();
  const searchLower = searchTerm.toLowerCase();
  
  let found = false;
  
  cards.forEach(card => {
    const nameEl = card.querySelector('[class*="name"], [class*="title"], [class*="Name"], h3, h4');
    const name = nameEl?.textContent?.toLowerCase() || '';
    
    // Проверяем совпадение
    const searchWords = searchLower.split(' ').filter(w => w.length > 2);
    const matches = searchWords.every(word => name.includes(word));
    
    if (matches && !found) {
      // Нашли нужный товар - подсвечиваем
      highlightCard(card);
      scrollToCard(card);
      found = true;
      highlightedProduct = card;
      
      console.log('✅ SmartCart: товар найден:', name);
    }
  });
  
  if (!found) {
    console.log('⚠️ SmartCart: товар не найден');
    showFloatingMessage(`Товар "${searchTerm}" не найден на странице`, 'warning');
  }
}

function findProductCards() {
  const selectors = [
    '[data-testid="product-card"]',
    '[class*="ProductCard"]',
    '[class*="product-card"]',
    'article[class*="product"]',
    '[class*="GoodsList"] > div > div',
  ];
  
  let cards = [];
  for (const selector of selectors) {
    cards = document.querySelectorAll(selector);
    if (cards.length > 0) break;
  }
  
  return cards;
}

function highlightCard(card) {
  // Добавляем стили подсветки
  card.style.cssText += `
    outline: 3px solid #00d4ff !important;
    outline-offset: 3px !important;
    box-shadow: 0 0 30px rgba(0, 212, 255, 0.5) !important;
    animation: smartcart-pulse 1.5s infinite !important;
    position: relative !important;
    z-index: 1000 !important;
  `;
  
  // Добавляем бейдж
  const badge = document.createElement('div');
  badge.className = 'smartcart-badge';
  badge.innerHTML = '🛒 Добавь этот товар';
  badge.style.cssText = `
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #00d4ff, #7c3aed);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    font-family: -apple-system, BlinkMacSystemFont, sans-serif;
    white-space: nowrap;
    z-index: 1001;
    box-shadow: 0 4px 15px rgba(0, 212, 255, 0.4);
    animation: smartcart-bounce 0.5s ease-out;
  `;
  
  card.style.position = 'relative';
  card.appendChild(badge);
  
  // Следим за кликом на кнопку "Добавить в корзину"
  watchAddToCartButton(card);
}

function scrollToCard(card) {
  card.scrollIntoView({
    behavior: 'smooth',
    block: 'center'
  });
}

function clearHighlight() {
  if (highlightedProduct) {
    highlightedProduct.style.outline = '';
    highlightedProduct.style.outlineOffset = '';
    highlightedProduct.style.boxShadow = '';
    highlightedProduct.style.animation = '';
    highlightedProduct.style.zIndex = '';
    
    const badge = highlightedProduct.querySelector('.smartcart-badge');
    if (badge) badge.remove();
    
    highlightedProduct = null;
  }
  
  // Удаляем все бейджи
  document.querySelectorAll('.smartcart-badge').forEach(el => el.remove());
}

function watchAddToCartButton(card) {
  // Ищем кнопку добавления в корзину
  const buttonSelectors = [
    'button[class*="add"]',
    'button[class*="cart"]',
    'button[class*="buy"]',
    '[class*="AddButton"]',
    '[class*="CartButton"]',
  ];
  
  let addButton = null;
  for (const selector of buttonSelectors) {
    addButton = card.querySelector(selector);
    if (addButton) break;
  }
  
  if (addButton) {
    addButton.addEventListener('click', () => {
      // Товар добавлен - показываем уведомление
      showFloatingMessage('✅ Товар добавлен! Отметь галочку в расширении', 'success');
      
      // Меняем стиль подсветки на зелёный
      card.style.outline = '3px solid #22c55e';
      card.style.boxShadow = '0 0 30px rgba(34, 197, 94, 0.5)';
      
      const badge = card.querySelector('.smartcart-badge');
      if (badge) {
        badge.innerHTML = '✅ Добавлено!';
        badge.style.background = '#22c55e';
      }
    }, { once: true });
  }
}

// === НАБЛЮДАТЕЛЬ ЗА DOM ===
function startObserver(searchTerm) {
  if (observer) observer.disconnect();
  
  observer = new MutationObserver((mutations) => {
    // Если появились новые товары, пробуем найти нужный
    const hasNewProducts = mutations.some(m => 
      m.addedNodes.length > 0 && 
      [...m.addedNodes].some(n => 
        n.nodeType === 1 && 
        (n.matches?.('[class*="product"]') || n.querySelector?.('[class*="product"]'))
      )
    );
    
    if (hasNewProducts && !highlightedProduct) {
      findAndHighlight(searchTerm);
    }
  });
  
  observer.observe(document.body, {
    childList: true,
    subtree: true
  });
  
  // Отключаем через 30 секунд
  setTimeout(() => {
    if (observer) observer.disconnect();
  }, 30000);
}

// === ИЗВЛЕЧЕНИЕ ТОВАРОВ ===
function extractAllProducts() {
  const products = [];
  const cards = findProductCards();
  
  // Определяем магазин и категорию из URL
  const url = window.location.href;
  let store = 'unknown';
  let category = 'unknown';
  
  const storeMatch = url.match(/\/retail\/([^\/]+)/);
  if (storeMatch) store = storeMatch[1];
  
  const categoryMatch = url.match(/\/category\/([^\/\?]+)/);
  if (categoryMatch) category = categoryMatch[1];
  
  cards.forEach(card => {
    try {
      const nameEl = card.querySelector('[class*="name"], [class*="title"], [class*="Name"], h3, h4');
      const name = nameEl?.textContent?.trim();
      if (!name) return;
      
      const priceEl = card.querySelector('[class*="price"]:not([class*="old"]), [class*="Price"]:not([class*="Old"])');
      const priceText = priceEl?.textContent || '';
      const priceMatch = priceText.match(/(\d+)/);
      const price = priceMatch ? parseInt(priceMatch[1]) : null;
      if (!price) return;
      
      const oldPriceEl = card.querySelector('[class*="old"], del, s');
      const oldPriceText = oldPriceEl?.textContent || '';
      const oldPriceMatch = oldPriceText.match(/(\d+)/);
      const originalPrice = oldPriceMatch ? parseInt(oldPriceMatch[1]) : null;
      
      let discount = null;
      if (originalPrice && price && originalPrice > price) {
        discount = Math.round((1 - price / originalPrice) * 100);
      }
      
      const weightEl = card.querySelector('[class*="weight"], [class*="measure"]');
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
        store,
        category,
        parsedAt: new Date().toISOString()
      });
      
    } catch (e) {
      console.error('SmartCart: ошибка парсинга карточки:', e);
    }
  });
  
  console.log(`🛒 SmartCart: извлечено ${products.length} товаров`);
  return products;
}

// === UI ЭЛЕМЕНТЫ ===
function showFloatingMessage(message, type = 'info') {
  // Удаляем предыдущее сообщение
  const existing = document.getElementById('smartcart-message');
  if (existing) existing.remove();
  
  const colors = {
    info: { bg: '#0a0e17', border: '#00d4ff', text: '#00d4ff' },
    success: { bg: '#0a0e17', border: '#22c55e', text: '#22c55e' },
    warning: { bg: '#0a0e17', border: '#f59e0b', text: '#f59e0b' },
    error: { bg: '#0a0e17', border: '#ef4444', text: '#ef4444' }
  };
  
  const color = colors[type] || colors.info;
  
  const msg = document.createElement('div');
  msg.id = 'smartcart-message';
  msg.textContent = message;
  msg.style.cssText = `
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 15px 20px;
    background: ${color.bg};
    color: ${color.text};
    border: 2px solid ${color.border};
    border-radius: 10px;
    font-family: -apple-system, BlinkMacSystemFont, 'JetBrains Mono', sans-serif;
    font-size: 14px;
    font-weight: 500;
    z-index: 999999;
    box-shadow: 0 4px 20px rgba(0, 212, 255, 0.3);
    animation: smartcart-slideIn 0.3s ease;
  `;
  
  document.body.appendChild(msg);
  
  setTimeout(() => {
    msg.style.animation = 'smartcart-slideOut 0.3s ease forwards';
    setTimeout(() => msg.remove(), 300);
  }, 4000);
}

// === СТИЛИ ===
function injectStyles() {
  if (document.getElementById('smartcart-styles')) return;
  
  const style = document.createElement('style');
  style.id = 'smartcart-styles';
  style.textContent = `
    @keyframes smartcart-pulse {
      0%, 100% {
        box-shadow: 0 0 20px rgba(0, 212, 255, 0.4);
      }
      50% {
        box-shadow: 0 0 40px rgba(0, 212, 255, 0.7);
      }
    }
    
    @keyframes smartcart-bounce {
      0% {
        transform: translateX(-50%) scale(0);
        opacity: 0;
      }
      50% {
        transform: translateX(-50%) scale(1.1);
      }
      100% {
        transform: translateX(-50%) scale(1);
        opacity: 1;
      }
    }
    
    @keyframes smartcart-slideIn {
      from {
        transform: translateX(100px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    
    @keyframes smartcart-slideOut {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(100px);
        opacity: 0;
      }
    }
    
    .smartcart-badge {
      pointer-events: none;
    }
  `;
  
  document.head.appendChild(style);
}

// === ИНИЦИАЛИЗАЦИЯ ===
injectStyles();

// Показываем что расширение активно
console.log('✅ SmartCart Content Script готов к работе');
