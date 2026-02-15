<?php
/*
** External Zabbix API Data Provider
** Uses HTTP requests to Zabbix API with token authentication
**/

namespace Modules\OpenAIAssistant\Services;

class ZabbixAPIProvider
{
    private $apiUrl;
    private $apiToken;
    
    public function __construct($apiUrl, $apiToken) {
        $this->apiUrl = $apiUrl;
        $this->apiToken = $apiToken;
    }
    
    /**
     * Make Zabbix API request
     */
    private function apiRequest($method, $params = []) {
        $data = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => 1
        ];
        
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiToken
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception("cURL Error: $error");
        }
        
        if ($httpCode !== 200) {
            throw new \Exception("HTTP Error: $httpCode");
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['error'])) {
            throw new \Exception("Zabbix API Error: " . $result['error']['data']);
        }
        
        return $result['result'] ?? [];
    }
    
    /**
     * Get current problems
     */
    public function getProblems($limit = 10) {
        try {
            $problems = $this->apiRequest('problem.get', [
                'output' => ['eventid', 'objectid', 'name', 'severity', 'clock'],
                'recent' => true,
                'sortfield' => 'eventid',
                'sortorder' => 'DESC',
                'limit' => $limit
            ]);
            
            // Get host info separately for each problem
            foreach ($problems as &$problem) {
                try {
                    $triggers = $this->apiRequest('trigger.get', [
                        'output' => ['triggerid'],
                        'triggerids' => $problem['objectid'],
                        'selectHosts' => ['hostid', 'host', 'name'],
                        'limit' => 1
                    ]);
                    
                    if (!empty($triggers[0]['hosts'])) {
                        $problem['hosts'] = $triggers[0]['hosts'];
                    }
                } catch (\Exception $e) {
                    error_log('Could not fetch host for problem: ' . $e->getMessage());
                    $problem['hosts'] = [['name' => 'Unknown']];
                }
            }
            
            return $problems;
        } catch (\Exception $e) {
            error_log('ZabbixAPIProvider::getProblems error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get hosts
     */
    public function getHosts($limit = 20) {
        try {
            return $this->apiRequest('host.get', [
                'output' => ['hostid', 'host', 'name', 'status'],
                'selectGroups' => ['groupid', 'name'],
                'limit' => $limit
            ]);
        } catch (\Exception $e) {
            error_log('ZabbixAPIProvider::getHosts error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get host items (metrics) with latest data
     */
    public function getHostMetrics($hostName, $limit = 10) {
        try {
            // First, get the host
            $hosts = $this->apiRequest('host.get', [
                'output' => ['hostid', 'host', 'name'],
                'filter' => ['host' => $hostName],
                'limit' => 1
            ]);
            
            if (empty($hosts)) {
                // Try by name if exact host not found
                $hosts = $this->apiRequest('host.get', [
                    'output' => ['hostid', 'host', 'name'],
                    'search' => ['name' => $hostName],
                    'limit' => 1
                ]);
            }
            
            if (empty($hosts)) {
                return ['error' => 'Host not found'];
            }
            
            $hostId = $hosts[0]['hostid'];
            
            // Get items for this host (CPU, Memory, Disk, Network, etc.)
            $items = $this->apiRequest('item.get', [
                'output' => ['itemid', 'name', 'key_', 'lastvalue', 'units', 'value_type'],
                'hostids' => $hostId,
                'monitored' => true,
                'filter' => [
                    'value_type' => [0, 3] // Numeric (float and unsigned int)
                ],
                'sortfield' => 'name',
                'limit' => $limit
            ]);
            
            return [
                'host' => $hosts[0],
                'items' => $items
            ];
            
        } catch (\Exception $e) {
            error_log('ZabbixAPIProvider::getHostMetrics error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Get specific item history
     */
    public function getItemHistory($itemId, $limit = 10) {
        try {
            $history = $this->apiRequest('history.get', [
                'output' => 'extend',
                'itemids' => $itemId,
                'sortfield' => 'clock',
                'sortorder' => 'DESC',
                'limit' => $limit
            ]);
            
            return $history;
        } catch (\Exception $e) {
            error_log('ZabbixAPIProvider::getItemHistory error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get item graph URL
     */
    public function getItemGraphUrl($itemId, $period = 172800, $width = 900, $height = 200) {
        // Parse API URL to get base Zabbix URL
        $urlParts = parse_url($this->apiUrl);
        $baseUrl = $urlParts['scheme'] . '://' . $urlParts['host'];
        if (isset($urlParts['port'])) {
            $baseUrl .= ':' . $urlParts['port'];
        }
        
        // Construct chart URL
        $chartUrl = $baseUrl . '/chart.php';
        $params = [
            'itemids[]' => $itemId,
            'period' => $period,
            'width' => $width,
            'height' => $height
        ];
        
        return $chartUrl . '?' . http_build_query($params);
    }
    
    /**
     * Get graphs for host with key metrics
     */
    public function getHostGraphs($hostName, $period = 172800) {
        try {
            $metrics = $this->getHostMetrics($hostName, 20);
            
            if (isset($metrics['error'])) {
                return ['error' => $metrics['error']];
            }
            
            $graphs = [];
            
            // Find key metrics (CPU, Memory, Disk, Network)
            foreach ($metrics['items'] as $item) {
                $key = strtolower($item['key_']);
                $name = strtolower($item['name']);
                
                // CPU metrics
                if (stripos($key, 'cpu') !== false || stripos($name, 'cpu') !== false) {
                    $graphs['cpu'][] = [
                        'name' => $item['name'],
                        'url' => $this->getItemGraphUrl($item['itemid'], $period),
                        'value' => $item['lastvalue'],
                        'units' => $item['units']
                    ];
                }
                // Memory metrics
                elseif (stripos($key, 'memory') !== false || stripos($name, 'memory') !== false || stripos($name, 'ram') !== false) {
                    $graphs['memory'][] = [
                        'name' => $item['name'],
                        'url' => $this->getItemGraphUrl($item['itemid'], $period),
                        'value' => $item['lastvalue'],
                        'units' => $item['units']
                    ];
                }
                // Disk metrics
                elseif (stripos($key, 'disk') !== false || stripos($name, 'disk') !== false || stripos($name, 'storage') !== false) {
                    $graphs['disk'][] = [
                        'name' => $item['name'],
                        'url' => $this->getItemGraphUrl($item['itemid'], $period),
                        'value' => $item['lastvalue'],
                        'units' => $item['units']
                    ];
                }
                // Network metrics
                elseif (stripos($key, 'net') !== false || stripos($name, 'network') !== false || stripos($name, 'traffic') !== false) {
                    $graphs['network'][] = [
                        'name' => $item['name'],
                        'url' => $this->getItemGraphUrl($item['itemid'], $period),
                        'value' => $item['lastvalue'],
                        'units' => $item['units']
                    ];
                }
            }
            
            return [
                'host' => $metrics['host'],
                'graphs' => $graphs,
                'period_days' => $period / 86400
            ];
            
        } catch (\Exception $e) {
            error_log('ZabbixAPIProvider::getHostGraphs error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Get top hosts by specific metric (CPU, Memory, etc.)
     */
    public function getTopHosts($limit = 10) {
        try {
            $hosts = $this->apiRequest('host.get', [
                'output' => ['hostid', 'host', 'name'],
                'monitored_hosts' => true,
                'selectItems' => ['itemid', 'name', 'key_', 'lastvalue', 'units'],
                'limit' => $limit
            ]);
            
            // Get key metrics for each host
            $hostsWithMetrics = [];
            foreach ($hosts as $host) {
                $metrics = [];
                
                foreach ($host['items'] as $item) {
                    $key = $item['key_'];
                    
                    // Match common metrics
                    if (stripos($key, 'cpu') !== false || stripos($item['name'], 'CPU') !== false) {
                        $metrics['cpu'] = [
                            'name' => $item['name'],
                            'value' => $item['lastvalue'],
                            'units' => $item['units']
                        ];
                    }
                    elseif (stripos($key, 'memory') !== false || stripos($item['name'], 'Memory') !== false) {
                        $metrics['memory'] = [
                            'name' => $item['name'],
                            'value' => $item['lastvalue'],
                            'units' => $item['units']
                        ];
                    }
                    elseif (stripos($key, 'disk') !== false || stripos($item['name'], 'Disk') !== false) {
                        $metrics['disk'] = [
                            'name' => $item['name'],
                            'value' => $item['lastvalue'],
                            'units' => $item['units']
                        ];
                    }
                }
                
                if (!empty($metrics)) {
                    $hostsWithMetrics[] = [
                        'host' => $host['name'],
                        'hostname' => $host['host'],
                        'metrics' => $metrics
                    ];
                }
            }
            
            return $hostsWithMetrics;
            
        } catch (\Exception $e) {
            error_log('ZabbixAPIProvider::getTopHosts error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get statistics
     */
    public function getStatistics() {
        try {
            // Get all problems
            $problems = $this->apiRequest('problem.get', [
                'recent' => true,
                'output' => ['severity']
            ]);
            
            // Get all hosts
            $hosts = $this->apiRequest('host.get', [
                'countOutput' => true
            ]);
            
            // Count by severity
            $severityCounts = [
                'disaster' => 0,
                'high' => 0,
                'average' => 0,
                'warning' => 0,
                'information' => 0
            ];
            
            foreach ($problems as $problem) {
                switch ($problem['severity']) {
                    case '5': $severityCounts['disaster']++; break;
                    case '4': $severityCounts['high']++; break;
                    case '3': $severityCounts['average']++; break;
                    case '2': $severityCounts['warning']++; break;
                    case '1': $severityCounts['information']++; break;
                }
            }
            
            return [
                'total_hosts' => $hosts,
                'total_problems' => count($problems),
                'severity_counts' => $severityCounts
            ];
            
        } catch (\Exception $e) {
            error_log('ZabbixAPIProvider::getStatistics error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Format data for AI
     */
    public function formatForAI() {
        try {
            $stats = $this->getStatistics();
            if (!$stats) {
                return '';
            }
            
            $problems = $this->getProblems(10);
            $hostsWithMetrics = $this->getTopHosts(15); // Get hosts with metrics
            
            $context = "=== CURRENT ZABBIX STATUS ===\n\n";
            
            // Statistics
            $context .= "**System Overview:**\n";
            $context .= "- Total Hosts: {$stats['total_hosts']}\n";
            $context .= "- Total Active Problems: {$stats['total_problems']}\n\n";
            
            // Severity breakdown
            $context .= "**Problems by Severity:**\n";
            $context .= "- 🔴 Disaster: {$stats['severity_counts']['disaster']}\n";
            $context .= "- 🟠 High: {$stats['severity_counts']['high']}\n";
            $context .= "- 🟡 Average: {$stats['severity_counts']['average']}\n";
            $context .= "- 🟢 Warning: {$stats['severity_counts']['warning']}\n";
            $context .= "- ℹ️ Information: {$stats['severity_counts']['information']}\n\n";
            
            // Recent problems
            if (!empty($problems)) {
                $context .= "**Recent Problems (Top 10):**\n";
                foreach ($problems as $i => $problem) {
                    $severityName = $this->getSeverityName($problem['severity']);
                    $hostName = $problem['hosts'][0]['name'] ?? 'Unknown';
                    $problemName = $problem['name'];
                    $time = date('Y-m-d H:i:s', $problem['clock']);
                    
                    $context .= ($i + 1) . ". [{$severityName}] {$hostName}: {$problemName} (at {$time})\n";
                }
                $context .= "\n";
            }
            
            // Hosts with current metrics
            if (!empty($hostsWithMetrics)) {
                $context .= "**Monitored Hosts with Current Metrics:**\n\n";
                foreach ($hostsWithMetrics as $i => $hostData) {
                    $context .= ($i + 1) . ". **{$hostData['host']}** ({$hostData['hostname']})\n";
                    
                    if (!empty($hostData['metrics'])) {
                        foreach ($hostData['metrics'] as $metricType => $metric) {
                            $value = $this->formatMetricValue($metric['value'], $metric['units']);
                            $context .= "   - {$metric['name']}: {$value}\n";
                        }
                    } else {
                        $context .= "   - No metrics available\n";
                    }
                    $context .= "\n";
                }
            }
            
            $context .= "\n=== END ZABBIX STATUS ===\n";
            $context .= "\n**Available Capabilities:**\n";
            $context .= "- You can show Zabbix metric graphs using markdown image syntax\n";
            $context .= "- Graph URL format: `http://ZABBIX_URL/chart.php?itemids[]=ITEM_ID&period=SECONDS&width=800&height=200`\n";
            $context .= "- To show a graph, use: `![Graph Name](GRAPH_URL)`\n";
            $context .= "- Common periods: 3600 (1h), 86400 (1d), 172800 (2d), 604800 (7d)\n";
            $context .= "- If user asks for graphs, tell them graphs require authentication and may not display directly\n";
            $context .= "- You can provide instructions on how to view graphs in Zabbix UI instead\n";
            
            return $context;
            
        } catch (\Exception $e) {
            error_log('ZabbixAPIProvider::formatForAI error: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Format metric value with units
     */
    private function formatMetricValue($value, $units) {
        if (empty($units)) {
            return number_format($value, 2);
        }
        
        // Handle percentage
        if ($units === '%') {
            return number_format($value, 2) . '%';
        }
        
        // Handle bytes
        if (stripos($units, 'B') !== false || stripos($units, 'byte') !== false) {
            $size = floatval($value);
            $units_arr = ['B', 'KB', 'MB', 'GB', 'TB'];
            $index = 0;
            
            while ($size >= 1024 && $index < count($units_arr) - 1) {
                $size /= 1024;
                $index++;
            }
            
            return number_format($size, 2) . ' ' . $units_arr[$index];
        }
        
        // Handle bits per second
        if (stripos($units, 'bps') !== false) {
            $speed = floatval($value);
            $units_arr = ['bps', 'Kbps', 'Mbps', 'Gbps'];
            $index = 0;
            
            while ($speed >= 1000 && $index < count($units_arr) - 1) {
                $speed /= 1000;
                $index++;
            }
            
            return number_format($speed, 2) . ' ' . $units_arr[$index];
        }
        
        // Default: value + units
        return number_format($value, 2) . ' ' . $units;
    }
    
    /**
     * Get severity name
     */
    private function getSeverityName($severity) {
        $severities = [
            '0' => 'Not classified',
            '1' => 'Information',
            '2' => 'Warning',
            '3' => 'Average',
            '4' => 'High',
            '5' => 'Disaster'
        ];
        return $severities[$severity] ?? 'Unknown';
    }
}

