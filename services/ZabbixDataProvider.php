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
            global $DB;
            
            if (!isset($DB) || !is_object($DB)) {
                return [];
            }
            
            // Use direct SQL query
            $query = 'SELECT p.eventid, p.objectid, p.name, p.severity, p.clock, '.
                     'h.hostid, h.host, h.name as hostname '.
                     'FROM problem p '.
                     'LEFT JOIN triggers t ON p.objectid=t.triggerid '.
                     'LEFT JOIN functions f ON t.triggerid=f.triggerid '.
                     'LEFT JOIN items i ON f.itemid=i.itemid '.
                     'LEFT JOIN hosts h ON i.hostid=h.hostid '.
                     'WHERE p.source=0 AND p.object=0 '.
                     'ORDER BY p.clock DESC '.
                     'LIMIT ' . intval($limit);
            
            $result = DBselect($query);
            $problems = [];
            
            while ($row = DBfetch($result)) {
                $problems[] = [
                    'eventid' => $row['eventid'],
                    'objectid' => $row['objectid'],
                    'name' => $row['name'],
                    'severity' => $row['severity'],
                    'clock' => $row['clock'],
                    'hosts' => [[
                        'hostid' => $row['hostid'],
                        'host' => $row['host'],
                        'name' => $row['hostname']
                    ]]
                ];
            }
            
            return $problems;
            
        } catch (\Exception $e) {
            error_log('ZabbixDataProvider::getProblems error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get hosts with issues
     */
    public static function getHostsWithProblems($limit = 20) {
        try {
            global $DB;
            
            if (!isset($DB) || !is_object($DB)) {
                return [];
            }
            
            // Get hosts with active problems
            $query = 'SELECT DISTINCT h.hostid, h.host, h.name, h.status, '.
                     'GROUP_CONCAT(DISTINCT hg.name SEPARATOR ", ") as groups '.
                     'FROM hosts h '.
                     'LEFT JOIN hosts_groups hgh ON h.hostid=hgh.hostid '.
                     'LEFT JOIN hstgrp hg ON hgh.groupid=hg.groupid '.
                     'WHERE h.hostid IN ('.
                     '  SELECT DISTINCT i.hostid FROM problem p '.
                     '  LEFT JOIN triggers t ON p.objectid=t.triggerid '.
                     '  LEFT JOIN functions f ON t.triggerid=f.triggerid '.
                     '  LEFT JOIN items i ON f.itemid=i.itemid '.
                     '  WHERE p.source=0 AND p.object=0 AND p.severity >= 2'.
                     ') '.
                     'GROUP BY h.hostid, h.host, h.name, h.status '.
                     'LIMIT ' . intval($limit);
            
            $result = DBselect($query);
            $hosts = [];
            
            while ($row = DBfetch($result)) {
                $hosts[] = [
                    'hostid' => $row['hostid'],
                    'host' => $row['host'],
                    'name' => $row['name'],
                    'status' => $row['status'],
                    'groups' => array_map(function($name) {
                        return ['name' => trim($name)];
                    }, explode(',', $row['groups']))
                ];
            }
            
            return $hosts;
            
        } catch (\Exception $e) {
            error_log('ZabbixDataProvider::getHostsWithProblems error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get overall Zabbix statistics
     */
    public static function getStatistics() {
        try {
            global $DB;
            
            if (!isset($DB) || !is_object($DB)) {
                return null;
            }
            
            // Total hosts
            $result = DBselect('SELECT COUNT(*) as cnt FROM hosts WHERE status=0');
            $row = DBfetch($result);
            $totalHosts = $row['cnt'];
            
            // Hosts with problems
            $result = DBselect(
                'SELECT COUNT(DISTINCT i.hostid) as cnt FROM problem p '.
                'LEFT JOIN triggers t ON p.objectid=t.triggerid '.
                'LEFT JOIN functions f ON t.triggerid=f.triggerid '.
                'LEFT JOIN items i ON f.itemid=i.itemid '.
                'WHERE p.source=0 AND p.object=0'
            );
            $row = DBfetch($result);
            $hostsWithProblems = $row['cnt'];
            
            // Active problems by severity
            $result = DBselect('SELECT severity, COUNT(*) as cnt FROM problem WHERE source=0 AND object=0 GROUP BY severity');
            
            $severityCounts = [
                'disaster' => 0,
                'high' => 0,
                'average' => 0,
                'warning' => 0,
                'information' => 0
            ];
            
            $totalProblems = 0;
            while ($row = DBfetch($result)) {
                $totalProblems += $row['cnt'];
                switch ($row['severity']) {
                    case 5: $severityCounts['disaster'] = $row['cnt']; break;
                    case 4: $severityCounts['high'] = $row['cnt']; break;
                    case 3: $severityCounts['average'] = $row['cnt']; break;
                    case 2: $severityCounts['warning'] = $row['cnt']; break;
                    case 1: $severityCounts['information'] = $row['cnt']; break;
                }
            }
            
            return [
                'total_hosts' => $totalHosts,
                'hosts_with_problems' => $hostsWithProblems,
                'severity_counts' => $severityCounts,
                'total_problems' => $totalProblems
            ];
        } catch (\Exception $e) {
            error_log('ZabbixDataProvider::getStatistics error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Format Zabbix data for AI context
     */
    public static function formatForAI() {
        // Check if we're in Zabbix environment
        global $DB;
        
        if (!isset($DB) || !is_object($DB)) {
            error_log('OpenAI Widget: Database not available - Zabbix data disabled');
            return '';
        }
        
        try {
            $stats = self::getStatistics();
            $problems = self::getProblems(10);
            $hosts = self::getHostsWithProblems(10);
            
            if (!$stats) {
                return '';
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
        
        } catch (\Exception $e) {
            error_log('OpenAI Widget: Error formatting Zabbix data - ' . $e->getMessage());
            return '';
        }
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

