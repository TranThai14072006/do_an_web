/* Sang trong (Luxurious) Page Transition JS */
document.addEventListener("DOMContentLoaded", () => {
    const links = document.querySelectorAll("a");
    links.forEach(link => {
        link.addEventListener("click", function(e) {
            // Validate link
            const targetUrl = this.getAttribute("href");
            if (!targetUrl) return;
            
            // Ignore links that open in a new tab, or javascript void links
            if (this.target === "_blank" || this.hasAttribute("download") || targetUrl.startsWith("javascript:") || targetUrl.startsWith("#")) {
                return;
            }
            
            // Allow same-page anchors to jump without animation
            if (this.href.includes('#') && this.pathname === window.location.pathname) {
                return;
            }

            // Only animate if it's pointing to the same origin (internal links)
            if (this.hostname !== window.location.hostname) {
                return;
            }

            // Animate only if navigating to a different page/url (ignore current page reloads without query change)
            if (this.href !== window.location.href) {
                e.preventDefault();
                document.body.classList.add("fade-out");
                
                setTimeout(() => {
                    window.location.href = this.href;
                }, 50); // Cut timeout down significantly for maximum speed and snappiness
            }
        });
    });
});

// Handle back/forward cache navigation
window.addEventListener("pageshow", function(event) {
    if (event.persisted || document.body.classList.contains("fade-out")) {
        document.body.classList.remove("fade-out");
    }
});
