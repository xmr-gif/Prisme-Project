// upload.js - Version simple sans AJAX
console.log('🚀 Upload script loaded (simple version)');

document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('file-input');
    const browseBtn = document.getElementById('browse-btn');
    const visualizeBtn = document.getElementById('visualize-btn');
    const uploadZone = document.getElementById('upload-zone');

    if (!fileInput || !browseBtn || !uploadZone) {
        console.error('❌ Required elements not found');
        return;
    }

    console.log('✅ Elements found, setting up events');

    // Browse button
    browseBtn.addEventListener('click', () => {
        console.log('Browse clicked');
        fileInput.click();
    });

    // Upload zone click
    uploadZone.addEventListener('click', (e) => {
        if (e.target === uploadZone || e.target.closest('.upload-zone-content')) {
            console.log('Zone clicked');
            fileInput.click();
        }
    });

    // File selection
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            const file = e.target.files[0];
            console.log('✅ File selected:', file.name, file.size);

            // Validate file
            if (file.size > 10485760) { // 10MB
                alert('File too large. Maximum size is 10MB.');
                return;
            }

            const extension = file.name.split('.').pop().toLowerCase();
            if (!['log', 'txt'].includes(extension)) {
                alert('Invalid file type. Only .log and .txt files are allowed.');
                return;
            }

            // Enable visualize button
            if (visualizeBtn) {
                visualizeBtn.disabled = false;
                visualizeBtn.style.background = '#3b82f6';
                visualizeBtn.style.cursor = 'pointer';
            }

            // Show file info
            showFileInfo(file);
        }
    });

    function showFileInfo(file) {
        const sizeFormatted = formatFileSize(file.size);
        uploadZone.innerHTML = `
            <div style="display: flex; align-items: center; gap: 16px; padding: 24px; background: #f8fafc; border: 2px solid #10b981; border-radius: 12px;">
                <div style="font-size: 32px;">📄</div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: #1e293b;">${file.name}</div>
                    <div style="color: #64748b; font-size: 0.9rem;">${sizeFormatted}</div>
                </div>
            </div>
        `;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    console.log('📋 Upload handler initialized successfully');
});
