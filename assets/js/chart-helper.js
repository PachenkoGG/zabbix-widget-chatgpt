/**
 * Zabbix Chart Helper
 * Fetches history data and renders charts using Chart.js
 */

class ZabbixChartHelper {
    constructor(apiUrl, apiToken) {
        this.apiUrl = apiUrl;
        this.apiToken = apiToken;
    }
    
    /**
     * Get item history data
     */
    async getItemHistory(itemId, periodSeconds = 172800) {
        try {
            const timeFrom = Math.floor(Date.now() / 1000) - periodSeconds;
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.apiToken}`
                },
                body: JSON.stringify({
                    jsonrpc: '2.0',
                    method: 'history.get',
                    params: {
                        output: 'extend',
                        itemids: itemId,
                        time_from: timeFrom,
                        sortfield: 'clock',
                        sortorder: 'ASC',
                        limit: 1000
                    },
                    id: 1
                })
            });
            
            const data = await response.json();
            
            if (data.error) {
                console.error('Zabbix API Error:', data.error);
                return null;
            }
            
            return data.result;
        } catch (error) {
            console.error('Chart data fetch error:', error);
            return null;
        }
    }
    
    /**
     * Render chart using Chart.js
     */
    renderChart(containerId, historyData, title = 'Metric History') {
        if (!historyData || historyData.length === 0) {
            return;
        }
        
        const canvas = document.createElement('canvas');
        canvas.id = `chart-${Date.now()}`;
        canvas.width = 800;
        canvas.height = 300;
        
        const container = document.getElementById(containerId);
        if (!container) return;
        
        container.appendChild(canvas);
        
        // Prepare data for Chart.js
        const labels = historyData.map(d => {
            const date = new Date(d.clock * 1000);
            return date.toLocaleString('tr-TR', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        });
        
        const values = historyData.map(d => parseFloat(d.value));
        
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: title,
                    data: values,
                    borderColor: '#0066cc',
                    backgroundColor: 'rgba(0, 102, 204, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            color: '#f0f0f0'
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: '#999',
                            maxTicksLimit: 10
                        },
                        grid: {
                            color: '#333'
                        }
                    },
                    y: {
                        ticks: {
                            color: '#999'
                        },
                        grid: {
                            color: '#333'
                        }
                    }
                }
            }
        });
    }
}

