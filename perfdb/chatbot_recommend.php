<?php
/**
 * Perfume Recommendation Chatbot API
 * 
 * This API processes user preferences and returns perfume recommendations
 * using ONLY existing database data with virtual logic for concepts like
 * occasion and intensity.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'connect.php';

// ============================================
// VIRTUAL LOGIC MAPPINGS (Not in database)
// ============================================

/**
 * Occasion mapping to brand preferences
 * These are virtual rules applied in code, not stored in DB
 */
$occasion_brand_mapping = [
    'daily' => [
        'brands' => [], // All brands acceptable
        'price_range' => ['min' => 0, 'max' => 100],
        'description' => 'Casual and light scents for everyday wear'
    ],
    'date' => [
        'brands' => [], // Prefer romantic/luxury brands
        'price_range' => ['min' => 80, 'max' => 200],
        'description' => 'Romantic and captivating scents'
    ],
    'party' => [
        'brands' => [], // Bold scents
        'price_range' => ['min' => 50, 'max' => 150],
        'description' => 'Bold and memorable scents'
    ],
    'work' => [
        'brands' => [], // Professional, subtle scents
        'price_range' => ['min' => 60, 'max' => 120],
        'description' => 'Professional and subtle scents'
    ],
    'friends' => [
        'brands' => [], // Fun, casual scents
        'price_range' => ['min' => 40, 'max' => 120],
        'description' => 'Fun and casual scents'
    ]
];

/**
 * Intensity mapping to price/description keywords
 * Virtual concept - no DB column exists
 */
$intensity_mapping = [
    'light' => [
        'price_factor' => 0.8,
        'keywords' => ['fresh', 'light', 'soft', 'subtle', 'eau de toilette'],
        'description' => 'Light and fresh scent'
    ],
    'moderate' => [
        'price_factor' => 1.0,
        'keywords' => ['balanced', 'eau de parfum'],
        'description' => 'Balanced intensity'
    ],
    'strong' => [
        'price_factor' => 1.2,
        'keywords' => ['intense', 'bold', 'parfum', 'strong', 'rich'],
        'description' => 'Strong and long-lasting scent'
    ]
];

/**
 * Scent preference virtual categories
 * Maps user-friendly terms to actual perfume characteristics
 */
$scent_preference_mapping = [
    'floral' => [
        'keywords' => ['rose', 'jasmine', 'flower', 'floral', 'lily', 'orchid', 'violet'],
        'description' => 'Floral and romantic'
    ],
    'woody' => [
        'keywords' => ['wood', 'cedar', 'sandalwood', 'oud', 'oak', 'vetiver'],
        'description' => 'Warm and woody'
    ],
    'fresh' => [
        'keywords' => ['fresh', 'citrus', 'lemon', 'bergamot', 'aqua', 'marine', 'cool'],
        'description' => 'Fresh and invigorating'
    ],
    'oriental' => [
        'keywords' => ['oriental', 'spice', 'amber', 'vanilla', 'musk', 'incense'],
        'description' => 'Rich and exotic'
    ],
    'fruity' => [
        'keywords' => ['fruit', 'apple', 'peach', 'berry', 'cherry', 'pear'],
        'description' => 'Sweet and fruity'
    ],
    'sweet' => [
        'keywords' => ['sweet', 'candy', 'vanilla', 'caramel', 'honey', 'sugar'],
        'description' => 'Sweet and gourmand'
    ]
];

// ============================================
// API ENDPOINTS
// ============================================

$input = json_decode(file_get_contents('php://input'), true);
$action = isset($input['action']) ? $input['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch ($action) {
    case 'get_options':
        getOptions($conn);
        break;
    case 'get_recommendations':
        getRecommendations($conn, $input);
        break;
    case 'get_custom_blend_options':
        getCustomBlendOptions($conn, $input);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

/**
 * Get available options for chatbot UI
 */
function getOptions($conn) {
    global $occasion_brand_mapping, $intensity_mapping, $scent_preference_mapping;
    
    try {
        // Get genders from database
        $genders_stmt = $conn->query("SELECT gender_id, gender_name FROM genders ORDER BY gender_id");
        $genders = $genders_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get brands from database
        $brands_stmt = $conn->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name");
        $brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'options' => [
                'genders' => $genders,
                'brands' => $brands,
                'occasions' => array_keys($occasion_brand_mapping),
                'intensities' => array_keys($intensity_mapping),
                'scent_preferences' => array_keys($scent_preference_mapping),
                'perfume_types' => ['ready', 'custom'],
                'ready_sizes' => [50, 100], // Ready-made sizes in ml
                'custom_sizes' => [30, 50, 100] // Custom blend sizes in ml
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get perfume recommendations based on user preferences
 */
function getRecommendations($conn, $input) {
    global $occasion_brand_mapping, $intensity_mapping, $scent_preference_mapping;
    
    // Extract user preferences
    $gender_id = isset($input['gender_id']) ? intval($input['gender_id']) : 0;
    $scent_preference = isset($input['scent_preference']) ? strtolower($input['scent_preference']) : '';
    $occasion = isset($input['occasion']) ? strtolower($input['occasion']) : 'daily';
    $intensity = isset($input['intensity']) ? strtolower($input['intensity']) : 'moderate';
    $perfume_type = isset($input['perfume_type']) ? strtolower($input['perfume_type']) : 'ready';
    $size = isset($input['size']) ? intval($input['size']) : 50;
    $brand_id = isset($input['brand_id']) ? intval($input['brand_id']) : 0;
    
    try {
        // Build base query
        $query = "SELECT 
            p.p_id, 
            p.p_name, 
            p.brand_id, 
            p.gender_id, 
            p.stock, 
            p.price, 
            p.description,
            p.image_url,
            b.brand_name,
            g.gender_name
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        LEFT JOIN genders g ON p.gender_id = g.gender_id
        WHERE p.stock > 0";
        
        $params = [];
        
        // Filter by gender
        if ($gender_id > 0) {
            $query .= " AND p.gender_id = :gender_id";
            $params[':gender_id'] = $gender_id;
        }
        
        // Filter by brand if specified
        if ($brand_id > 0) {
            $query .= " AND p.brand_id = :brand_id";
            $params[':brand_id'] = $brand_id;
        }
        
        // Apply occasion-based price filter (virtual logic)
        if (isset($occasion_brand_mapping[$occasion])) {
            $price_range = $occasion_brand_mapping[$occasion]['price_range'];
            $query .= " AND p.price >= :min_price AND p.price <= :max_price";
            $params[':min_price'] = $price_range['min'];
            $params[':max_price'] = $price_range['max'];
        }
        
        $query .= " ORDER BY p.price ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Apply virtual scent preference filtering (keyword matching in name/description)
        if (!empty($scent_preference) && isset($scent_preference_mapping[$scent_preference])) {
            $keywords = $scent_preference_mapping[$scent_preference]['keywords'];
            $products = array_filter($products, function($product) use ($keywords) {
                $searchText = strtolower($product['p_name'] . ' ' . ($product['description'] ?? ''));
                foreach ($keywords as $keyword) {
                    if (strpos($searchText, $keyword) !== false) {
                        return true;
                    }
                }
                // If no keyword match, still include with lower priority (we'll sort later)
                return true;
            });
        }
        
        // Score products based on preference matching
        $scored_products = [];
        foreach ($products as $product) {
            $score = 50; // Base score
            $searchText = strtolower($product['p_name'] . ' ' . ($product['description'] ?? ''));
            
            // Scent preference scoring
            if (!empty($scent_preference) && isset($scent_preference_mapping[$scent_preference])) {
                foreach ($scent_preference_mapping[$scent_preference]['keywords'] as $keyword) {
                    if (strpos($searchText, $keyword) !== false) {
                        $score += 20;
                        break;
                    }
                }
            }
            
            // Intensity scoring (virtual)
            if (isset($intensity_mapping[$intensity])) {
                foreach ($intensity_mapping[$intensity]['keywords'] as $keyword) {
                    if (strpos($searchText, $keyword) !== false) {
                        $score += 15;
                        break;
                    }
                }
            }
            
            // Stock availability scoring
            if ($product['stock'] > 10) {
                $score += 10;
            }
            
            // Add random factor for variety
            $score += rand(0, 10);
            
            $product['match_score'] = $score;
            $scored_products[] = $product;
        }
        
        // Sort by score descending
        usort($scored_products, function($a, $b) {
            return $b['match_score'] - $a['match_score'];
        });
        
        // Get top recommendations
        $recommendations = array_slice($scored_products, 0, 5);
        
        // Calculate adjusted prices based on size
        foreach ($recommendations as &$rec) {
            // Base price is for 50ml, 100ml adds $50
            $rec['size'] = $size;
            if ($size === 100) {
                $rec['adjusted_price'] = $rec['price'] + 50;
            } else {
                $rec['adjusted_price'] = $rec['price'];
            }
            $rec['size_label'] = $size . 'ml';
        }
        
        // Build response message
        $occasion_desc = isset($occasion_brand_mapping[$occasion]) 
            ? $occasion_brand_mapping[$occasion]['description'] 
            : '';
        
        echo json_encode([
            'success' => true,
            'message' => count($recommendations) > 0 
                ? "Based on your preferences, here are my top picks for " . $occasion_desc
                : "I couldn't find exact matches, but here are some alternatives you might like",
            'recommendations' => $recommendations,
            'preferences_applied' => [
                'gender_id' => $gender_id,
                'scent_preference' => $scent_preference,
                'occasion' => $occasion,
                'intensity' => $intensity,
                'size' => $size
            ]
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get options for custom blend perfumes
 */
function getCustomBlendOptions($conn, $input) {
    global $scent_preference_mapping;
    
    $gender_id = isset($input['gender_id']) ? intval($input['gender_id']) : 0;
    $scent_preference = isset($input['scent_preference']) ? strtolower($input['scent_preference']) : '';
    
    try {
        // Get all available products for the specified gender
        $query = "SELECT 
            p.p_id, 
            p.p_name, 
            p.brand_id, 
            p.gender_id, 
            p.stock, 
            p.price, 
            p.description,
            p.image_url,
            b.brand_name,
            g.gender_name
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        LEFT JOIN genders g ON p.gender_id = g.gender_id
        WHERE p.stock > 0";
        
        $params = [];
        
        if ($gender_id > 0) {
            $query .= " AND p.gender_id = :gender_id";
            $params[':gender_id'] = $gender_id;
        }
        
        $query .= " ORDER BY p.p_name ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Score and categorize products for blending suggestions
        $categorized = [
            'recommended' => [],
            'compatible' => [],
            'all' => $products
        ];
        
        // Find products matching the scent preference
        if (!empty($scent_preference) && isset($scent_preference_mapping[$scent_preference])) {
            $keywords = $scent_preference_mapping[$scent_preference]['keywords'];
            foreach ($products as $product) {
                $searchText = strtolower($product['p_name'] . ' ' . ($product['description'] ?? ''));
                foreach ($keywords as $keyword) {
                    if (strpos($searchText, $keyword) !== false) {
                        $categorized['recommended'][] = $product;
                        break;
                    }
                }
            }
        }
        
        // Limit recommended to top 6
        $categorized['recommended'] = array_slice($categorized['recommended'], 0, 6);
        
        // Custom blend pricing info
        $pricing_info = [
            'price_per_10ml' => 2.5,
            'bottle_prices' => [
                30 => 3.0,
                50 => 4.0,
                100 => 6.0
            ],
            'max_perfumes' => 3,
            'available_sizes' => [30, 50, 100]
        ];
        
        echo json_encode([
            'success' => true,
            'products' => $categorized,
            'pricing' => $pricing_info,
            'blend_rules' => [
                'max_perfumes' => 3,
                'sizes_available' => [30, 50, 100],
                'same_gender_required' => true
            ],
            'suggestion' => count($categorized['recommended']) > 0
                ? "I recommend starting with these perfumes that match your " . $scent_preference . " preference"
                : "Here are all available perfumes for your custom blend"
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
