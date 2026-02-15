<?php
/*
** Simplified Zabbix Data Provider
** Uses direct database queries as fallback
**/

namespace Modules\OpenAIAssistant\Services;

class ZabbixDataProviderSimple
{
    /**
     * Get Zabbix data using direct DB access
     */
    public static function formatForAI() {
        try {
            // Try to get database connection
            global $DB;
            
            if (!isset($DB) || !is_object($DB)) {
                error_log('OpenAI Widget: No database connection available');
                return '';
            }
            
            // Get problem count
            $problemQuery = 'SELECT COUNT(*) as cnt FROM problem WHERE source=0 AND object=0';
            $problemResult = DBfetch(DBselect($problemQuery));
            $problemCount = $problemResult['cnt'] ?? 0;
            
            // Get host count
            $hostQuery = 'SELECT COUNT(*) as cnt FROM hosts WHERE status=0';
            $hostResult = DBfetch(DBselect($hostQuery));
            $hostCount = $hostResult['cnt'] ?? 0;
            
            // Build context
            $context = "=== CURRENT ZABBIX STATUS ===\n\n";
            $context .= "**System Overview:**\n";
            $context .= "- Total Monitored Hosts: {$hostCount}\n";
            $context .= "- Active Problems: {$problemCount}\n";
            $context .= "\n";
            
            // Get recent problems
            $recentProblems = DBselect(
                'SELECT p.name, p.severity, p.clock, h.name as hostname '.
                'FROM problem p '.
                'LEFT JOIN triggers t ON p.objectid=t.triggerid '.
                'LEFT JOIN functions f ON t.triggerid=f.triggerid '.
                'LEFT JOIN items i ON f.itemid=i.itemid '.
                'LEFT JOIN hosts h ON i.hostid=h.hostid '.
                'WHERE p.source=0 AND p.object=0 '.
                'ORDER BY p.clock DESC '.
                'LIMIT 10'
            );
            
            if ($recentProblems) {
                $context .= "**Recent Problems:**\n";
                $idx = 1;
                while ($problem = DBfetch($recentProblems)) {
                    $severityName = self::getSeverityName($problem['severity']);
                    $time = date('Y-m-d H:i:s', $problem['clock']);
                    $context .= "{$idx}. [{$severityName}] {$problem['hostname']}: {$problem['name']} ({$time})\n";
                    $idx++;
                }
            }
            
            $context .= "\n=== END ZABBIX STATUS ===\n";
            
            return $context;
            
        } catch (\Exception $e) {
            error_log('OpenAI Widget: Error in ZabbixDataProviderSimple - ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Get severity name
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

