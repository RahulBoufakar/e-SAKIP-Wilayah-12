import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import Chart from 'chart.js/auto'; // Impor Chart.js lengkap (auto register controller & scales)

// Registrasi Alpine Plugin
Alpine.plugin(collapse);

// Impor Store Workflow Status
import './stores/workflow-status';

// Set Global Variables
window.Alpine = Alpine;
window.Chart = Chart;

// Komponen bell notifikasi in-app — dipakai bareng di navbar Admin, Tim Kerja, dan Validator
window.notificationBell = function () {
    return {
        open: false,
        count: 0,
        items: [],
        init() {
            this.fetchNotifications();
            setInterval(() => this.fetchNotifications(), 20000);
        },
        async fetchNotifications() {
            try {
                const res = await fetch('/notifications/unread', { headers: { Accept: 'application/json' } });
                const data = await res.json();
                this.count = data.count;
                this.items = data.items;
            } catch (e) {
                // diamkan, polling berikutnya akan coba lagi
            }
        },
        async markAllRead() {
            await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
            });
            this.count = 0;
            this.items = [];
        },
    };
};

// Start Alpine
Alpine.start();