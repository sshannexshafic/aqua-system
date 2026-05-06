// Offline Data Management
class OfflineDataManager {
    constructor() {
        this.dbName = 'AquaFarmDB';
        this.dbVersion = 1;
        this.db = null;
        this.init();
    }
    
    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Create object stores
                if (!db.objectStoreNames.contains('pendingReadings')) {
                    db.createObjectStore('pendingReadings', { autoIncrement: true });
                }
                if (!db.objectStoreNames.contains('pendingHealthRecords')) {
                    db.createObjectStore('pendingHealthRecords', { autoIncrement: true });
                }
                if (!db.objectStoreNames.contains('cachedData')) {
                    db.createObjectStore('cachedData', { keyPath: 'key' });
                }
            };
        });
    }
    
    async savePendingData(storeName, data) {
        if (!this.db) await this.init();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.add(data);
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    
    async getPendingData(storeName) {
        if (!this.db) await this.init();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.getAll();
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    
    async clearPendingData(storeName) {
        if (!this.db) await this.init();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.clear();
            
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }
    
    async cacheData(key, value) {
        if (!this.db) await this.init();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['cachedData'], 'readwrite');
            const store = transaction.objectStore('cachedData');
            const request = store.put({ key, value, timestamp: new Date() });
            
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }
    
    async getCachedData(key) {
        if (!this.db) await this.init();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['cachedData'], 'readonly');
            const store = transaction.objectStore('cachedData');
            const request = store.get(key);
            
            request.onsuccess = () => resolve(request.result?.value);
            request.onerror = () => reject(request.error);
        });
    }
}

// Initialize offline manager
const offlineManager = new OfflineDataManager();

// Auto-sync when back online
window.addEventListener('online', async () => {
    console.log('Back online, syncing data...');
    await syncPendingData();
});

async function syncPendingData() {
    // Sync water quality readings
    const pendingReadings = await offlineManager.getPendingData('pendingReadings');
    for (const reading of pendingReadings) {
        try {
            const response = await fetch('/api/sync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'water_quality', data: reading })
            });
            if (response.ok) {
                await offlineManager.clearPendingData('pendingReadings');
            }
        } catch (error) {
            console.error('Sync failed:', error);
        }
    }
}

// Helper to show notifications
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}