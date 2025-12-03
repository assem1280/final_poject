<?php
/**
 * Parameter Binding Validator
 * Checks all PDO execute() calls for parameter name consistency
 */

echo "=== PDO Parameter Binding Validator ===\n\n";

// Files to check
$perfdb_files = glob('perfdb/*.php');
$files_to_check = array_merge($perfdb_files);

$issues_found = 0;
$total_files = 0;

foreach ($files_to_check as $file) {
    $total_files++;
    $content = file_get_contents($file);
    
    // Skip test/debug files for now
    if (strpos($file, 'test') !== false || strpos($file, 'debug') !== false) {
        continue;
    }
    
    // Look for prepare statements followed by execute
    $pattern = '/\$(\w+)->prepare\s*\(\s*"([^"]+)"\s*\)[;\n\s]*\$\1->execute\s*\(\s*(\[.*?\])\s*\)/s';
    
    if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $idx => $full_match) {
            $query = $matches[2][$idx][0];
            $array_str = $matches[3][$idx][0];
            
            // Extract parameter names from query
            preg_match_all('/:(\w+)/', $query, $query_params);
            $query_params = $query_params[1];
            
            // Extract parameter names from execute array
            preg_match_all('/[\'"](:?)(\w+)[\'"]/', $array_str, $array_params);
            $execute_params = [];
            for ($i = 0; $i < count($array_params[2]); $i++) {
                $has_colon = $array_params[1][$i] === ':' ? ':' : '';
                $execute_params[] = $has_colon . $array_params[2][$i];
            }
            
            // Compare
            $missing = array_diff($query_params, $execute_params);
            $extra = array_diff($execute_params, $query_params);
            
            if (!empty($missing) || !empty($extra)) {
                $issues_found++;
                echo "⚠️  FILE: " . basename($file) . "\n";
                echo "   QUERY: " . substr($query, 0, 80) . "...\n";
                if (!empty($missing)) {
                    echo "   MISSING IN EXECUTE: " . implode(', ', $missing) . "\n";
                }
                if (!empty($extra)) {
                    echo "   EXTRA IN EXECUTE: " . implode(', ', $extra) . "\n";
                }
                echo "\n";
            }
        }
    }
}

if ($issues_found === 0) {
    echo "✓ All PDO parameter bindings are consistent!\n";
} else {
    echo "✗ Found $issues_found potential issues in $total_files files\n";
}

echo "\n=== Summary ===\n";
echo "Total files checked: $total_files\n";
echo "Issues found: $issues_found\n";
?>
