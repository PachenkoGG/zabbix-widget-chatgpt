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
     * Get item history with statistics
     */
    public function getItemHistoryStats($itemId, $periodSeconds = 7200) {
        try {
            $timeFrom = time() - $periodSeconds;
            
            $history = $this->apiRequest('history.get', [
                'output' => 'extend',
                'itemids' => $itemId,
                'time_from' => $timeFrom,
                'sortfield' => 'clock',
                'sortorder' => 'ASC',
                'limit' => 1000
            ]);
            
            if (empty($history)) {
                return null;
            }
            
            // Calculate statistics
            $values = array_map(function($h) { return floatval($h['value']); }, $history);
            
            return [
                'min' => min($values),
                'max' => max($values),
                'avg' => array_sum($values) / count($values),
                'current' => end($values),
                'count' => count($values),
                'period_hours' => $periodSeconds / 3600
            ];
            
        } catch (\Exception $e) {
            error_log('ZabbixAPIProvider::getItemHistoryStats error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get host items (metrics) with latest data AND history
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
     * Get top hosts by specific metric (CPU, Memory, etc.)
     */
    public function getTopHosts($limit = 10) {
        try {
            $hosts = $this->apiRequest('host.get', [
                'output' => ['hostid', 'host', 'name'],
                'monitored_hosts' => true,
                'limit' => $limit
            ]);
            
            error_log('=== DEBUG: Host Items ===');
            
            // Get key metrics for each host
            $hostsWithMetrics = [];
            foreach ($hosts as $host) {
                $metrics = [];
                
                error_log("Host: {$host['name']} ({$host['host']})");
                
                // Get items directly for this host (not through selectItems)
                $items = $this->apiRequest('item.get', [
                    'output' => ['itemid', 'name', 'key_', 'lastvalue', 'units'],
                    'hostids' => $host['hostid'],
                    'monitored' => true,
                    'search' => [
                        'key_' => ['cpu', 'memory', 'vfs.fs', 'net']
                    ],
                    'searchByAny' => true,
                    'sortfield' => 'name',
                    'limit' => 100
                ]);
                
                error_log("Total items: " . count($items));
                
                // Collect candidate metrics
                $diskCandidates = [];
                
                foreach ($items as $item) {
                    $key = $item['key_'];
                    $name = strtolower($item['name']);
                    
                    // Log disk-related items
                    if (stripos($key, 'disk') !== false || stripos($key, 'vfs.fs') !== false || 
                        stripos($name, 'disk') !== false || stripos($name, 'space') !== false) {
                        error_log("  - Disk item: {$item['name']} | key: {$item['key_']} | value: {$item['lastvalue']} | units: {$item['units']}");
                        
                        // Collect disk candidates for / filesystem
                        if ((stripos($name, 'fs [/]') !== false || stripos($key, '[/,') !== false) &&
                            stripos($name, 'used') !== false && stripos($name, 'inode') === false) {
                            $diskCandidates[] = $item;
                        }
                    }
                    
                    // Match CPU metrics
                    if (!isset($metrics['cpu'])) {
                        if (stripos($key, 'cpu') !== false || stripos($name, 'cpu') !== false) {
                            if (stripos($name, 'utilization') !== false || stripos($name, 'usage') !== false) {
                                $metrics['cpu'] = [
                                    'itemid' => $item['itemid'],
                                    'name' => $item['name'],
                                    'value' => $item['lastvalue'],
                                    'units' => $item['units']
                                ];
                                error_log("  -> Selected CPU: {$item['name']}");
                            }
                        }
                    }
                    
                    // Match Memory metrics
                    if (!isset($metrics['memory'])) {
                        if (stripos($key, 'memory') !== false || stripos($name, 'memory') !== false || stripos($name, 'ram') !== false) {
                            if (stripos($name, 'utilization') !== false || 
                                stripos($name, 'usage') !== false ||
                                (stripos($name, 'used') !== false && stripos($name, 'percent') !== false)) {
                                $metrics['memory'] = [
                                    'itemid' => $item['itemid'],
                                    'name' => $item['name'],
                                    'value' => $item['lastvalue'],
                                    'units' => $item['units']
                                ];
                                error_log("  -> Selected Memory: {$item['name']}");
                            }
                        }
                    }
                    
                    // Match Network metrics
                    if (!isset($metrics['network'])) {
                        if (stripos($key, 'net.if') !== false || stripos($name, 'network') !== false || 
                            stripos($name, 'traffic') !== false || stripos($name, 'bandwidth') !== false) {
                            $metrics['network'] = [
                                'itemid' => $item['itemid'],
                                'name' => $item['name'],
                                'value' => $item['lastvalue'],
                                'units' => $item['units']
                            ];
                            error_log("  -> Selected Network: {$item['name']}");
                        }
                    }
                }
                
                // Now select best disk metric from candidates
                if (!empty($diskCandidates) && !isset($metrics['disk'])) {
                    // Priority 1: Find percentage metric
                    foreach ($diskCandidates as $candidate) {
                        if ($candidate['units'] === '%' || stripos($candidate['name'], 'in %') !== false) {
                            $metrics['disk'] = [
                                'itemid' => $candidate['itemid'],
                                'name' => $candidate['name'],
                                'value' => $candidate['lastvalue'],
                                'units' => $candidate['units']
                            ];
                            error_log("  -> Selected Disk: {$candidate['name']} (PERCENTAGE - best choice)");
                            break;
                        }
                    }
                    
                    // Priority 2: If no percentage found, use bytes
                    if (!isset($metrics['disk']) && !empty($diskCandidates)) {
                        $metrics['disk'] = [
                            'itemid' => $diskCandidates[0]['itemid'],
                            'name' => $diskCandidates[0]['name'],
                            'value' => $diskCandidates[0]['lastvalue'],
                            'units' => $diskCandidates[0]['units']
                        ];
                        error_log("  -> Selected Disk: {$diskCandidates[0]['name']} (bytes fallback)");
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
                            $context .= "   - {$metric['name']}: {$value}";
                            
                            // Add history stats if available
                            if (isset($metric['itemid'])) {
                                $stats = $this->getItemHistoryStats($metric['itemid'], 7200); // Last 2 hours
                                if ($stats) {
                                    $minVal = $this->formatMetricValue($stats['min'], $metric['units']);
                                    $maxVal = $this->formatMetricValue($stats['max'], $metric['units']);
                                    $avgVal = $this->formatMetricValue($stats['avg'], $metric['units']);
                                    $context .= " (Last 2h: Min={$minVal}, Max={$maxVal}, Avg={$avgVal})";
                                }
                            }
                            
                            $context .= "\n";
                        }
                    } else {
                        $context .= "   - No metrics available\n";
                    }
                    $context .= "\n";
                }
            }
            
            $context .= "\n=== END ZABBIX STATUS ===\n";
            $context .= "\n**Important Notes:**\n";
            $context .= "- Each metric shows: Current value (Last 2h: Min, Max, Avg)\n";
            $context .= "- You can answer questions about current values and recent trends (last 2 hours)\n";
            $context .= "- If user asks about specific time periods, use the available 2-hour statistics\n";
            $context .= "- Example: 'Son 2 saat CPU %X ile %Y arasında değişti, ortalama %Z oldu'\n";
            
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

