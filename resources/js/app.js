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

// Start Alpine
Alpine.start();