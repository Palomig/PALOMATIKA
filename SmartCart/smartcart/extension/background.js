/**
 * 🛒 SmartCart - Background Service Worker
 * Фоновый процесс расширения
 */

// === УСТАНОВКА ===
chrome.runtime.onInstalled.addListener((details) => {
  if (details.reason === 'install') {
    console.log('🛒 SmartCart установлен');
    
    // Инициализация хранилища
    chrome.storage.local.set({
      serverUrl: 'https://cw95865.tmweb.ru',
      parsedProducts: {},
      parsedCategories: {},
      shoppingList: [],
      currentStore: 'perekrestok',
      settings: {
        autoSync: false,
        notifications: true
      },
      installedAt: new Date().toISOString()
    });
    
    // Показываем уведомление
    chrome.notifications.create({
      type: 'basic',
      iconUrl: 'icons/icon128.png',
      title: '🛒 SmartCart установлен!',
      message: 'Откройте market-delivery.yandex.ru и начните отслеживать цены'
    });
  }
  
  if (details.reason === 'update') {
    console.log('🛒 SmartCart обновлён до версии', chrome.runtime.getManifest().version);
  }
});

// === ОБРАБОТКА СООБЩЕНИЙ ===
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  console.log('Background: получено сообщение', request.action);
  
  switch (request.action) {
    case 'getState':
      chrome.storage.local.get(null, (data) => {
        sendResponse(data);
      });
      return true;
      
    case 'syncWithServer':
      syncWithServer(request.data)
        .then(result => sendResponse(result))
        .catch(error => sendResponse({ error: error.message }));
      return true;
      
    case 'notify':
      chrome.notifications.create({
        type: 'basic',
        iconUrl: 'icons/icon128.png',
        title: request.title || 'SmartCart',
        message: request.message
      });
      sendResponse({ success: true });
      break;
      
    case 'openTab':
      chrome.tabs.create({ url: request.url, active: true });
      sendResponse({ success: true });
      break;
  }
});

// === СИНХРОНИЗАЦИЯ С СЕРВЕРОМ ===
async function syncWithServer(data) {
  try {
    const storage = await chrome.storage.local.get(['serverUrl']);
    const serverUrl = storage.serverUrl || 'https://cw95865.tmweb.ru';
    
    const response = await fetch(`${serverUrl}/api/prices/bulk`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data)
    });
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    
    const result = await response.json();
    return { success: true, result };
    
  } catch (error) {
    console.error('Ошибка синхронизации:', error);
    return { success: false, error: error.message };
  }
}

// === КОНТЕКСТНОЕ МЕНЮ ===
chrome.runtime.onInstalled.addListener(() => {
  // Добавляем пункт в контекстное меню
  chrome.contextMenus.create({
    id: 'smartcart-scan',
    title: '🛒 SmartCart: собрать цены',
    contexts: ['page'],
    documentUrlPatterns: ['https://market-delivery.yandex.ru/*']
  });
});

chrome.contextMenus.onClicked.addListener((info, tab) => {
  if (info.menuItemId === 'smartcart-scan') {
    // Отправляем сообщение content script
    chrome.tabs.sendMessage(tab.id, {
      action: 'extractProducts'
    }, (response) => {
      if (response && response.products) {
        chrome.notifications.create({
          type: 'basic',
          iconUrl: 'icons/icon128.png',
          title: '🛒 SmartCart',
          message: `Найдено ${response.products.length} товаров`
        });
      }
    });
  }
});

// === BADGE (значок на иконке) ===
function updateBadge(count) {
  if (count > 0) {
    chrome.action.setBadgeText({ text: count.toString() });
    chrome.action.setBadgeBackgroundColor({ color: '#00d4ff' });
  } else {
    chrome.action.setBadgeText({ text: '' });
  }
}

// === СЛУШАТЕЛЬ ИЗМЕНЕНИЙ В STORAGE ===
chrome.storage.onChanged.addListener((changes, namespace) => {
  if (namespace === 'local') {
    if (changes.shoppingList) {
      const list = changes.shoppingList.newValue || [];
      const uncheckedCount = list.filter(i => !i.checked).length;
      updateBadge(uncheckedCount);
    }
  }
});

// === ИНИЦИАЛИЗАЦИЯ ===
console.log('🛒 SmartCart Background Service Worker запущен');
