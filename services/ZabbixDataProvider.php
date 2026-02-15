<?php
/*
** Zabbix Data Provider Service
** Fetches real-time data from Zabbix API
**/

namespace Modules\OpenAIAssistant\Services;

class ZabbixDataProvider
{
    /**
     * Get current Zabbix problems/triggers
     */
    public static function getProblems($limit = 10) {
        try {
            // Use Zabbix API Manager
            $api = new \CAPIManager();
            
            $problems = $api->call('problem.get', [
                'output' => ['eventid', 'objectid', 'name', 'severity', 'clock'],
                'selectHosts' => ['hostid', 'host', 'name'],
                'recent' => true,
                'sortfield' => ['clock'],
                'sortorder' => 'DESC',
                'limit' => $limit
            ]);
            
            return $problems ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get hosts with issues
     */
    public static function getHostsWithProblems($limit = 20) {
        try {
            $api = new \CAPIManager();
            
            $hosts = $api->call('host.get', [
                'output' => ['hostid', 'host', 'name', 'status'],
                'selectGroups' => ['groupid', 'name'],
                'withProblemsSuppressed' => false,
                'severities' => [2, 3, 4, 5], // Warning and above
                'limit' => $limit
            ]);
            
            return $hosts ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get overall Zabbix statistics
     */
    public static function getStatistics() {
        try {
            $api = new \CAPIManager();
            
            // Total hosts
            $totalHosts = $api->call('host.get', [
                'countOutput' => true
            ]);
            
            // Hosts with problems
            $hostsWithProblems = $api->call('host.get', [
                'countOutput' => true,
                'withProblemsSuppressed' => false
            ]);
            
            // Active problems by severity
            $problems = $api->call('problem.get', [
                'recent' => true,
                'output' => ['severity']
            ]);
            
            $severityCounts = [
                'disaster' => 0,
                'high' => 0,
                'average' => 0,
                'warning' => 0,
                'information' => 0
            ];
            
            foreach ($problems as $problem) {
                switch ($problem['severity']) {
                    case 5: $severityCounts['disaster']++; break;
                    case 4: $severityCounts['high']++; break;
                    case 3: $severityCounts['average']++; break;
                    case 2: $severityCounts['warning']++; break;
                    case 1: $severityCounts['information']++; break;
                }
            }
            
            return [
                'total_hosts' => $totalHosts,
                'hosts_with_problems' => $hostsWithProblems,
                'severity_counts' => $severityCounts,
                'total_problems' => count($problems)
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Format Zabbix data for AI context
     */
    public static function formatForAI() {
        $stats = self::getStatistics();
        $problems = self::getProblems(10);
        $hosts = self::getHostsWithProblems(10);
        
        if (!$stats) {
            return "Zabbix data unavailable.";
        }
        
        $context = "=== CURRENT ZABBIX STATUS ===\n\n";
        
        // Statistics
        $context .= "**Overall Statistics:**\n";
        $context .= "- Total Hosts: {$stats['total_hosts']}\n";
        $context .= "- Hosts with Problems: {$stats['hosts_with_problems']}\n";
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
                $severityName = self::getSeverityName($problem['severity']);
                $hostName = $problem['hosts'][0]['name'] ?? 'Unknown';
                $problemName = $problem['name'];
                $time = date('Y-m-d H:i:s', $problem['clock']);
                
                $context .= ($i + 1) . ". [{$severityName}] {$hostName}: {$problemName} (at {$time})\n";
            }
            $context .= "\n";
        }
        
        // Affected hosts
        if (!empty($hosts)) {
            $context .= "**Hosts with Issues (Top 10):**\n";
            foreach ($hosts as $i => $host) {
                $hostName = $host['name'];
                $hostname = $host['host'];
                $groups = implode(', ', array_column($host['groups'], 'name'));
                
                $context .= ($i + 1) . ". {$hostName} ({$hostname}) - Groups: {$groups}\n";
            }
        }
        
        $context .= "\n=== END ZABBIX STATUS ===\n";
        
        return $context;
    }
    
    /**
     * Get severity name from code
     */
    private static function getSeverityName($severity) {
        $severities = [
            0 => 'Not classified',
            1 => 'Information',
            2 => 'Warning',
            3 => 'Average',
            4 => 'High',
            5 => 'Disaster'
        ];
        
        return $severities[$severity] ?? 'Unknown';
    }
}

