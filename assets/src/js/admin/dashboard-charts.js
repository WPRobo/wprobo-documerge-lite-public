/**
 * WPRobo DocuMerge Lite — Dashboard Charts
 *
 * Reads chart data + translated strings from the localized
 * `wprobo_documerge_dashboard` global and renders Chart.js
 * daily-submissions line chart and status doughnut chart.
 *
 * @package WPRobo_DocuMerge
 * @since   1.0.0
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		if (typeof Chart === 'undefined' || typeof wprobo_documerge_dashboard === 'undefined') {
			return;
		}

		var cfg = wprobo_documerge_dashboard;
		var blue = '#042157';
		var green = '#166441';

		// ── Line Chart: Daily Submissions ──────────────────────
		var canvasDaily = document.getElementById('wdm-chart-daily');
		if (canvasDaily && Array.isArray(cfg.daily)) {
			var dailyLabels = cfg.daily.map(function (d) { return d.label; });
			var dailyCounts = cfg.daily.map(function (d) { return d.count; });

			new Chart(canvasDaily, {
				type: 'line',
				data: {
					labels: dailyLabels,
					datasets: [{
						label: cfg.i18n.submissions,
						data: dailyCounts,
						borderColor: blue,
						backgroundColor: 'rgba(4, 33, 87, 0.08)',
						borderWidth: 2.5,
						pointBackgroundColor: blue,
						pointBorderColor: '#ffffff',
						pointBorderWidth: 2,
						pointRadius: 5,
						pointHoverRadius: 7,
						fill: true,
						tension: 0.35
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { display: false },
						tooltip: {
							backgroundColor: blue,
							titleFont: { size: 13, weight: '600' },
							bodyFont: { size: 12 },
							padding: 10,
							cornerRadius: 6
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							ticks: { stepSize: 1, font: { size: 11 }, color: '#6b7280' },
							grid: { color: '#f0f4fa' }
						},
						x: {
							ticks: { font: { size: 11 }, color: '#6b7280' },
							grid: { display: false }
						}
					}
				}
			});
		}

		// ── Doughnut Chart: Status Breakdown ───────────────────
		var canvasStatus = document.getElementById('wdm-chart-status');
		if (!canvasStatus) {
			return;
		}

		var statusData = Array.isArray(cfg.statuses) ? cfg.statuses : [];
		var statusLabels = [];
		var statusCounts = [];
		var statusColors = {
			'completed': green,
			'processing': '#3b82f6',
			'pending_payment': '#d97706',
			'error': '#dc2626',
			'payment_failed': '#ef4444'
		};
		var bgColors = [];

		statusData.forEach(function (s) {
			var lbl = String(s.status || '').replace(/_/g, ' ');
			lbl = lbl.charAt(0).toUpperCase() + lbl.slice(1);
			statusLabels.push(lbl);
			statusCounts.push(parseInt(s.count, 10));
			bgColors.push(statusColors[s.status] || '#94a3b8');
		});

		if (statusLabels.length > 0) {
			new Chart(canvasStatus, {
				type: 'doughnut',
				data: {
					labels: statusLabels,
					datasets: [{
						data: statusCounts,
						backgroundColor: bgColors,
						borderWidth: 2,
						borderColor: '#ffffff',
						hoverOffset: 6
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					cutout: '65%',
					plugins: {
						legend: {
							position: 'bottom',
							labels: {
								padding: 16,
								usePointStyle: true,
								pointStyle: 'circle',
								font: { size: 12 }
							}
						},
						tooltip: {
							backgroundColor: blue,
							padding: 10,
							cornerRadius: 6
						}
					}
				}
			});
		} else {
			var parent = canvasStatus.parentNode;
			if (parent) {
				var msg = document.createElement('p');
				msg.className = 'wdm-chart-empty';
				msg.textContent = cfg.i18n.no_submissions;
				parent.replaceChild(msg, canvasStatus);
			}
		}
	});

})();
