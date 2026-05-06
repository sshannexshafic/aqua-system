<!-- In farmer/monitoring/add_reading.php -->
<form id="waterQualityForm" onsubmit="saveWithOfflineSupport(event)">
    <input type="hidden" name="pond_id" value="<?php echo $pond_id; ?>">
    
    <div class="form-group">
        <label>pH Level</label>
        <input type="number" step="0.1" name="ph" required>
    </div>
    
    <div class="form-group">
        <label>Temperature (°C)</label>
        <input type="number" step="0.1" name="temperature" required>
    </div>
    
    <div class="form-group">
        <label>Dissolved Oxygen (mg/L)</label>
        <input type="number" step="0.1" name="oxygen" required>
    </div>
    
    <button type="submit">Save Reading</button>
</form>

<script>
async function saveWithOfflineSupport(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const reading = {
        pond_id: formData.get('pond_id'),
        ph: parseFloat(formData.get('ph')),
        temperature: parseFloat(formData.get('temperature')),
        oxygen: parseFloat(formData.get('oxygen')),
        timestamp: new Date().toISOString()
    };
    
    if (!navigator.onLine) {
        // Offline - save to IndexedDB
        await offlineManager.savePendingData('pendingReadings', reading);
        showNotification('Saved offline. Will sync when online.', 'warning');
        event.target.reset();
    } else {
        // Online - send to server
        try {
            const response = await fetch('/api/sync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'water_quality', data: reading })
            });
            
            if (response.ok) {
                showNotification('Reading saved successfully!', 'success');
                event.target.reset();
            } else {
                throw new Error('Server error');
            }
        } catch (error) {
            // If online but server fails, save offline
            await offlineManager.savePendingData('pendingReadings', reading);
            showNotification('Saved offline due to server error', 'warning');
        }
    }
}
</script>