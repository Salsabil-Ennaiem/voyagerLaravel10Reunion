 document.addEventListener('DOMContentLoaded', () => {
            const widget = document.getElementById('stats-widget');
            const panel = document.getElementById('stats-panel');
            const closeBtn = document.getElementById('close-stats');

            widget.addEventListener('click', () => {
                panel.classList.remove('-translate-x-full');
            });

            closeBtn.addEventListener('click', () => {
                panel.classList.add('-translate-x-full');
            });

            // Close panel when clicking outside
            document.addEventListener('click', (e) => {
                if (!panel.contains(e.target) && !widget.contains(e.target)) {
                    panel.classList.add('-translate-x-full');
                }
            });
        });