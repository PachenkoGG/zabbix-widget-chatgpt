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
                'sortfield' => ['clock'],
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
            $hosts = $this->getHosts(10);
            
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
            
            // Monitored hosts
            if (!empty($hosts)) {
                $context .= "**Monitored Hosts (Sample):**\n";
                foreach (array_slice($hosts, 0, 10) as $i => $host) {
                    $hostName = $host['name'];
                    $hostname = $host['host'];
                    $groups = implode(', ', array_column($host['groups'] ?? [], 'name'));
                    
                    $context .= ($i + 1) . ". {$hostName} ({$hostname})";
                    if ($groups) {
                        $context .= " - Groups: {$groups}";
                    }
                    $context .= "\n";
                }
            }
            
            $context .= "\n=== END ZABBIX STATUS ===\n";
            
            return $context;
            
        } catch (\Exception $e) {
            error_log('ZabbixAPIProvider::formatForAI error: ' . $e->getMessage());
            return '';
        }
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

