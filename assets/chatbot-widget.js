/**
 * Chatbot Floating Widget
 * Adds a floating button to navigate to the perfume recommendation chatbot
 */

(function() {
    'use strict';
    
    // Don't show on chatbot page itself
    if (window.location.pathname.includes('/chatbot/')) {
        return;
    }
    
    // Create floating button
    function createChatbotButton() {
        const button = document.createElement('a');
        button.href = '/pefumeppp/chatbot/index.html';
        button.className = 'chatbot-floating-btn';
        button.setAttribute('title', 'Get Perfume Recommendations');
        button.innerHTML = `
            <span class="emoji">🤖</span>
            <span class="badge">NEW</span>
        `;
        
        document.body.appendChild(button);
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createChatbotButton);
    } else {
        createChatbotButton();
    }
})();
