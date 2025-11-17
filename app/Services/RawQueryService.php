<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class RawQueryService
{
    /**
     * List of dangerous SQL keywords that should be blocked
     */
    protected array $dangerousKeywords = [
        'DROP',
        'DELETE',
        'TRUNCATE',
        'ALTER',
        'CREATE',
        'INSERT',
        'UPDATE',
        'GRANT',
        'REVOKE',
        'EXEC',
        'EXECUTE',
        'CALL',
    ];

    /**
     * Allowed query types (SELECT only for safety)
     */
    protected array $allowedQueryTypes = ['SELECT'];

    /**
     * Execute a raw SQL query with tenant isolation
     * 
     * @param string $query
     * @param string|null $organizationId
     * @param bool $allowWriteOperations
     * @return array
     * @throws Exception
     */
    public function executeQuery(string $query, ?string $organizationId = null, bool $allowWriteOperations = false): array
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new Exception('User must be authenticated');
        }

        // Get organization ID
        $orgId = $organizationId ?? $user->organization_id ?? session('current_organization_id');
        
        if (!$orgId) {
            throw new Exception('Organization ID is required');
        }

        // Validate query safety
        $this->validateQuery($query, $allowWriteOperations);

        // Ensure tenant isolation
        $isolatedQuery = $this->ensureTenantIsolation($query, $orgId);

        try {
            // Execute query
            $results = DB::select($isolatedQuery);
            
            // Log the query execution
            $this->logQuery($user->id, $orgId, $query, $isolatedQuery, count($results));

            return [
                'success' => true,
                'data' => $results,
                'count' => count($results),
                'executed_query' => $isolatedQuery,
            ];
        } catch (Exception $e) {
            Log::error('Raw query execution failed', [
                'user_id' => $user->id,
                'organization_id' => $orgId,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Query execution failed: ' . $e->getMessage());
        }
    }

    /**
     * Execute a write operation (INSERT, UPDATE, DELETE) with tenant isolation
     * 
     * @param string $query
     * @param string|null $organizationId
     * @return array
     * @throws Exception
     */
    public function executeWriteQuery(string $query, ?string $organizationId = null): array
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new Exception('User must be authenticated');
        }

        // Only super admins or organization owners can execute write queries
        if (!$user->isSuperAdmin() && !$user->isOwnerOfOrganization($organizationId ?? $user->organization_id)) {
            throw new Exception('Write operations require super admin or organization owner privileges');
        }

        $orgId = $organizationId ?? $user->organization_id ?? session('current_organization_id');
        
        if (!$orgId) {
            throw new Exception('Organization ID is required');
        }

        // Validate query (allow write operations)
        $this->validateQuery($query, true);

        // Ensure tenant isolation
        $isolatedQuery = $this->ensureTenantIsolation($query, $orgId);

        try {
            // Execute write query
            $affected = DB::statement($isolatedQuery);
            
            // Log the query execution
            $this->logQuery($user->id, $orgId, $query, $isolatedQuery, $affected, true);

            return [
                'success' => true,
                'affected_rows' => $affected,
                'executed_query' => $isolatedQuery,
            ];
        } catch (Exception $e) {
            Log::error('Raw write query execution failed', [
                'user_id' => $user->id,
                'organization_id' => $orgId,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Query execution failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate query safety
     * 
     * @param string $query
     * @param bool $allowWriteOperations
     * @return void
     * @throws Exception
     */
    protected function validateQuery(string $query, bool $allowWriteOperations = false): void
    {
        $upperQuery = strtoupper(trim($query));

        // Check for dangerous keywords
        foreach ($this->dangerousKeywords as $keyword) {
            if (str_contains($upperQuery, $keyword)) {
                if (!$allowWriteOperations || in_array($keyword, ['DROP', 'TRUNCATE', 'ALTER', 'CREATE', 'GRANT', 'REVOKE'])) {
                    throw new Exception("Dangerous keyword '{$keyword}' is not allowed in queries");
                }
            }
        }

        // For read-only mode, ensure it's a SELECT query
        if (!$allowWriteOperations) {
            if (!str_starts_with($upperQuery, 'SELECT')) {
                throw new Exception('Only SELECT queries are allowed in read-only mode');
            }
        }

        // Check for multiple statements (prevent SQL injection)
        if (str_contains($query, ';') && substr_count($query, ';') > 1) {
            throw new Exception('Multiple statements are not allowed');
        }
    }

    /**
     * Ensure tenant isolation by adding organization_id filter
     * 
     * @param string $query
     * @param string $organizationId
     * @return string
     */
    protected function ensureTenantIsolation(string $query, string $organizationId): string
    {
        $upperQuery = strtoupper(trim($query));
        
        // List of tables that have organization_id column
        $tenantTables = [
            'customers', 'invoices', 'quotes', 'sales', 'sale_items',
            'money_movements', 'money_accounts', 'goods_and_services',
            'team_members', 'departments', 'documents', 'attachments',
            'budget_lines', 'payments', 'payment_allocations',
            'leave_requests', 'commission_earnings', 'payroll_runs',
            'projects', 'stock_movements', 'depreciation_logs',
            'okrs', 'key_results', 'key_result_check_ins',
            'strategic_goals', 'goal_milestones', 'business_valuations',
            'licenses', 'certificates', 'templates', 'notifications',
        ];

        // If query doesn't reference any tenant table, return as-is
        $hasTenantTable = false;
        foreach ($tenantTables as $table) {
            if (preg_match('/\b' . preg_quote($table, '/') . '\b/i', $query)) {
                $hasTenantTable = true;
                break;
            }
        }

        if (!$hasTenantTable) {
            return $query;
        }

        // For SELECT queries, add WHERE clause with organization_id
        if (str_starts_with($upperQuery, 'SELECT')) {
            // Check if WHERE clause already exists
            if (preg_match('/\bWHERE\b/i', $query)) {
                // Add organization_id to existing WHERE clause
                $query = preg_replace(
                    '/\bWHERE\b/i',
                    "WHERE organization_id = '{$organizationId}' AND",
                    $query,
                    1
                );
            } else {
                // Add WHERE clause before ORDER BY, GROUP BY, or LIMIT
                if (preg_match('/\b(ORDER BY|GROUP BY|LIMIT)\b/i', $query, $matches, PREG_OFFSET_CAPTURE)) {
                    $position = $matches[0][1];
                    $query = substr_replace(
                        $query,
                        " WHERE organization_id = '{$organizationId}' ",
                        $position,
                        0
                    );
                } else {
                    // Add at the end
                    $query .= " WHERE organization_id = '{$organizationId}'";
                }
            }
        }

        // For UPDATE/DELETE queries, ensure organization_id is in WHERE clause
        if (preg_match('/\b(UPDATE|DELETE FROM)\b/i', $query)) {
            if (!preg_match('/\borganization_id\s*=\s*[\'"]?' . preg_quote($organizationId, '/') . '[\'"]?/i', $query)) {
                if (preg_match('/\bWHERE\b/i', $query)) {
                    $query = preg_replace(
                        '/\bWHERE\b/i',
                        "WHERE organization_id = '{$organizationId}' AND",
                        $query,
                        1
                    );
                } else {
                    // Add WHERE clause
                    $query = preg_replace(
                        '/\b(UPDATE|DELETE FROM)\s+(\w+)/i',
                        "$1 $2 WHERE organization_id = '{$organizationId}'",
                        $query,
                        1
                    );
                }
            }
        }

        // For INSERT queries, ensure organization_id is included
        if (preg_match('/\bINSERT INTO\b/i', $query)) {
            // Check if organization_id is already in the column list
            if (!preg_match('/\borganization_id\b/i', $query)) {
                // Try to add organization_id to column list and values
                // Handle both explicit column list and no column list cases
                if (preg_match('/\bINSERT INTO\s+(\w+)\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/i', $query)) {
                    $query = preg_replace(
                        '/\bINSERT INTO\s+(\w+)\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/i',
                        "INSERT INTO $1 ($2, organization_id) VALUES ($3, '{$organizationId}')",
                        $query
                    );
                } elseif (preg_match('/\bINSERT INTO\s+(\w+)\s*VALUES\s*\(([^)]+)\)/i', $query)) {
                    // For INSERT without column list, we can't safely add organization_id
                    // This would require knowing the table structure
                    // For safety, we'll let it fail if organization_id is required
                }
            } else {
                // If organization_id is already in the query, ensure it matches the current organization
                $query = preg_replace(
                    '/\borganization_id\s*=\s*[\'"]?[^\'",\s\)]+[\'"]?/i',
                    "organization_id = '{$organizationId}'",
                    $query
                );
            }
        }

        return $query;
    }

    /**
     * Get table structure for tenant tables
     * 
     * @param string|null $organizationId
     * @return array
     */
    public function getTableStructure(?string $organizationId = null): array
    {
        $user = Auth::user();
        $orgId = $organizationId ?? $user->organization_id ?? session('current_organization_id');

        $tables = [
            'customers', 'invoices', 'quotes', 'sales', 'sale_items',
            'money_movements', 'money_accounts', 'goods_and_services',
            'team_members', 'departments', 'documents', 'attachments',
        ];

        $structures = [];

        foreach ($tables as $table) {
            try {
                $columns = DB::select("DESCRIBE {$table}");
                $structures[$table] = $columns;
            } catch (Exception $e) {
                // Table might not exist, skip it
                continue;
            }
        }

        return $structures;
    }

    /**
     * Log query execution
     * 
     * @param int $userId
     * @param string $organizationId
     * @param string $originalQuery
     * @param string $executedQuery
     * @param int $resultCount
     * @param bool $isWriteOperation
     * @return void
     */
    protected function logQuery(
        int $userId,
        string $organizationId,
        string $originalQuery,
        string $executedQuery,
        int $resultCount,
        bool $isWriteOperation = false
    ): void {
        Log::info('Raw query executed', [
            'user_id' => $userId,
            'organization_id' => $organizationId,
            'original_query' => $originalQuery,
            'executed_query' => $executedQuery,
            'result_count' => $resultCount,
            'is_write_operation' => $isWriteOperation,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}

