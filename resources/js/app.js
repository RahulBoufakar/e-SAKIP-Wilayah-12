import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import collapse from '@alpinejs/collapse';
 
window.Alpine = Alpine;
window.Chart = Chart;
 
Alpine.plugin(collapse);

Alpine.start();