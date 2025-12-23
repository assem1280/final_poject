<?php
// Search perfumes by name with "Did you mean?" suggestions
header('Content-Type: application/json');
require_once 'connect.php';

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query too short', 'perfumes' => []]);
    exit;
}

try {
    // Search by name (partial match)
    $sql = "SELECT 
        p.p_id as id, 
        p.p_name as name, 
        p.price, 
        p.image_url as image,
        b.brand_name
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE p.p_name LIKE :query OR b.brand_name LIKE :query2
    ORDER BY p.p_name
    LIMIT 10";

    $stmt = $conn->prepare($sql);
    $searchTerm = '%' . $query . '%';
    $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
    $stmt->bindParam(':query2', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();

    $perfumes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format image URLs
    foreach ($perfumes as &$perfume) {
        if (!empty($perfume['image'])) {
            // Ensure proper path
            if (strpos($perfume['image'], '../') === false && strpos($perfume['image'], 'http') === false) {
                $perfume['image'] = '../images/' . $perfume['image'];
            }
        } else {
            $perfume['image'] = '../images/default-perfume.png';
        }
    }

    // If no results found, try to find similar names (Did you mean?)
    $suggestion = null;
    if (empty($perfumes)) {
        // Get all perfume names for comparison
        $allSql = "SELECT p.p_id as id, p.p_name as name, p.price, p.image_url as image, b.brand_name
                   FROM products p
                   LEFT JOIN brands b ON p.brand_id = b.brand_id";
        $allStmt = $conn->query($allSql);
        $allPerfumes = $allStmt->fetchAll(PDO::FETCH_ASSOC);

        $bestMatch = null;
        $bestScore = 0;
        $queryLower = strtolower($query);

        foreach ($allPerfumes as $perfume) {
            $nameLower = strtolower($perfume['name']);
            $brandLower = strtolower($perfume['brand_name'] ?? '');

            // Calculate similarity using similar_text (percentage)
            similar_text($queryLower, $nameLower, $namePercent);
            similar_text($queryLower, $brandLower, $brandPercent);

            // Also check Levenshtein distance for short strings
            $nameLev = levenshtein($queryLower, $nameLower);
            $brandLev = levenshtein($queryLower, $brandLower);

            // Combine scores - prioritize high similarity percentage with low levenshtein
            $nameScore = $namePercent - ($nameLev * 2);
            $brandScore = $brandPercent - ($brandLev * 2);
            $score = max($nameScore, $brandScore);

            // Require at least 40% similarity to suggest
            if ($score > $bestScore && max($namePercent, $brandPercent) >= 40) {
                $bestScore = $score;
                $bestMatch = $perfume;
            }
        }

        if ($bestMatch) {
            // Format image for suggestion
            if (!empty($bestMatch['image'])) {
                if (strpos($bestMatch['image'], '../') === false && strpos($bestMatch['image'], 'http') === false) {
                    $bestMatch['image'] = '../images/' . $bestMatch['image'];
                }
            } else {
                $bestMatch['image'] = '../images/default-perfume.png';
            }
            $suggestion = $bestMatch;
        }
    }

    echo json_encode([
        'success' => true,
        'perfumes' => $perfumes,
        'suggestion' => $suggestion
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'perfumes' => []
    ]);
}
?>
