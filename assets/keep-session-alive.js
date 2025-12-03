// Keep session alive across all pages
(function() {
    // Keep session alive every 2 minutes (more frequent)
    const SESSION_KEEP_ALIVE_INTERVAL = 2 * 60 * 1000; // 2 minutes
    const LOG_DEBUG = false; // Set to true for debugging
    
    function log(msg) {
        if (LOG_DEBUG) console.log('[Session Keeper]', msg);
    }
    
    function keepSessionAlive() {
        fetch('/pefumeppp/perfdb/keep_session_alive.php', {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-cache'
        })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(data => {
            log('Session check: ' + (data.session_active ? 'ACTIVE' : 'INACTIVE'));
            
            if (!data.session_active) {
                // Session expired
                console.warn('[Session Keeper] Session expired - redirecting to login');
                window.location.href = '/pefumeppp/indexed/login.html';
            }
        })
        .catch(err => {
            console.error('[Session Keeper] Error:', err);
            // Don't redirect on network error - just retry next time
        });
    }
    
    // Call immediately on page load
    log('Initializing session keeper');
    keepSessionAlive();
    
    // Call periodically
    const intervalId = setInterval(keepSessionAlive, SESSION_KEEP_ALIVE_INTERVAL);
    
    // Keep session alive when user interacts with page (uncomment to enable)
    // document.addEventListener('mousemove', keepSessionAlive, { once: true });
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        clearInterval(intervalId);
    });
})();
