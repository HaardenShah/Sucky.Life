// Theme toggle functionality
(function() {
    'use strict';

    // Get theme from localStorage or default to light
    const getTheme = () => localStorage.getItem('admin-theme') || 'light';
    
    // Set theme
    const setTheme = (theme) => {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('admin-theme', theme);
    };

    // Toggle theme
    const toggleTheme = () => {
        const currentTheme = getTheme();
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        setTheme(newTheme);
    };

    // Apply saved theme on page load
    setTheme(getTheme());

    // Attach to window so it can be called from HTML
    window.toggleTheme = toggleTheme;
})();