<?php
/**
 * AI disease classification service for the "Snap & Detect" feature (FR-AI.1 - FR-AI.6).
 *
 * Design note (SRS NFR-MAINT.2): classify_mushroom_image() is the single integration
 * point between the website and the disease-detection model.
 *
 *  1. If AI_API_URL is set (config/app.php), the image is sent to the trained CNN
 *     served by ai_model/app.py.
 *  2. Otherwise (or if that service is down) a built-in colour-pattern analyser runs,
 *     using PHP's GD extension, so the feature works on plain XAMPP.
 *
 * Returns:
 *   ['disease' => string, 'confidence' => float 0-100, 'disease_id' => int|null,
 *    'scores' => [disease => percentage, ...], 'model' => string]
 * or ['error' => 'gd_missing' | 'unreadable'] when the image cannot be analysed.
 */

const AI_CLASSES = ['Healthy', 'Green Mold', 'Bacterial Blotch', 'Pest Attack'];

function classify_mushroom_image(string $imagePath): array {
    $diseases = classify_load_disease_map();

    if (AI_API_URL !== '') {
        $remote = classify_via_cnn_api($imagePath);
        if ($remote !== null) {
            $remote['disease_id'] = $diseases[$remote['disease']] ?? null;
            return $remote;
        }
        // AI service unreachable: fall back so farmers still get a result.
    }

    if (!function_exists('imagecreatetruecolor')) {
        return ['error' => 'gd_missing'];
    }

    $features = classify_extract_features($imagePath);
    if ($features === null) {
        return ['error' => 'unreadable'];
    }

    // Not enough light mushroom tissue visible (e.g. a dark brown variety or a
    // photo of the bag/background): report a low-confidence result so an
    // expert reviews it, rather than guessing.
    if ($features['clean'] < 0.10 && $features['green'] < 0.04) {
        $scores = ['Healthy' => 40.0, 'Bacterial Blotch' => 25.0, 'Pest Attack' => 20.0, 'Green Mold' => 15.0];
        return [
            'disease' => 'Healthy',
            'confidence' => 40.0,
            'disease_id' => $diseases['Healthy'] ?? null,
            'scores' => $scores,
            'model' => 'built-in colour analyser',
        ];
    }

    // Evidence for each disease = share of the mushroom covered by its symptom;
    // about 8% coverage counts as full evidence.
    $severity = [
        'Green Mold' => min(1, $features['green'] / 0.08),
        'Bacterial Blotch' => min(1, $features['blotch_in_cap'] / 0.08),
        'Pest Attack' => min(1, $features['dark_in_cap'] / 0.06),
    ];
    $raw = ['Healthy' => 1 - max($severity)] + $severity;
    foreach ($raw as $name => $value) {
        $raw[$name] = $value + 0.05; // never claim 100% certainty
    }
    $total = array_sum($raw);
    $scores = [];
    foreach ($raw as $name => $value) {
        $scores[$name] = round($value / $total * 100, 1);
    }
    arsort($scores);
    $top = array_key_first($scores);

    return [
        'disease' => $top,
        'confidence' => $scores[$top],
        'disease_id' => $diseases[$top] ?? null,
        'scores' => $scores,
        'model' => 'built-in colour analyser',
    ];
}

function classify_load_disease_map(): array {
    global $pdo;
    static $map = null;
    if ($map === null) {
        $map = [];
        foreach ($pdo->query('SELECT id, name FROM disease_types')->fetchAll() as $row) {
            $map[$row['name']] = (int) $row['id'];
        }
    }
    return $map;
}

/**
 * Samples a 40x40 grid of pixels and measures how much of the image is clean
 * mushroom tissue and green mould, and what share of the mushroom cap carries
 * yellow/brown blotches or dark specks (typical of pest holes / larvae).
 */
function classify_extract_features(string $path): ?array {
    $info = @getimagesize($path);
    if (!$info) {
        return null;
    }
    $image = null;
    if ($info[2] === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) {
        $image = @imagecreatefromjpeg($path);
    } elseif ($info[2] === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) {
        $image = @imagecreatefrompng($path);
    } elseif (defined('IMAGETYPE_WEBP') && $info[2] === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
        $image = @imagecreatefromwebp($path);
    }
    if (!$image) {
        return null;
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $grid = 40;
    $cells = [];
    $counts = ['clean' => 0, 'green' => 0, 'blotch' => 0, 'dark' => 0];

    for ($gy = 0; $gy < $grid; $gy++) {
        for ($gx = 0; $gx < $grid; $gx++) {
            $x = min($width - 1, (int) (($gx + 0.5) / $grid * $width));
            $y = min($height - 1, (int) (($gy + 0.5) / $grid * $height));
            $rgb = imagecolorat($image, $x, $y);
            [$h, $s, $v] = classify_rgb_to_hsv(($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF);

            $type = 'other';
            if ($v < 0.22) {
                $type = 'dark';
            } elseif ($h >= 60 && $h <= 170 && $s > 0.25) {
                $type = 'green';
            } elseif ($h >= 15 && $h <= 55 && $s > 0.35 && $v <= 0.85) {
                $type = 'blotch';
            } elseif ($s < 0.25 && $v > 0.55) {
                $type = 'clean';
            }
            $cells[$gy][$gx] = $type;
            if (isset($counts[$type])) {
                $counts[$type]++;
            }
        }
    }
    imagedestroy($image);

    // A blotch or dark speck only counts when it lies inside the mushroom cap,
    // i.e. there is clean tissue to its left, right, above and below. This stops
    // brown soil/bags or a dark background from being read as disease.
    $enclosed = function ($gy, $gx) use ($cells, $grid) {
        $found = 0;
        foreach ([[0, -1], [0, 1], [-1, 0], [1, 0]] as [$dy, $dx]) {
            for ($y = $gy + $dy, $x = $gx + $dx; $y >= 0 && $y < $grid && $x >= 0 && $x < $grid; $y += $dy, $x += $dx) {
                if ($cells[$y][$x] === 'clean') {
                    $found++;
                    break;
                }
            }
        }
        return $found === 4;
    };
    $blotchInCap = 0;
    $darkInCap = 0;
    for ($gy = 0; $gy < $grid; $gy++) {
        for ($gx = 0; $gx < $grid; $gx++) {
            if ($cells[$gy][$gx] === 'blotch' && $enclosed($gy, $gx)) {
                $blotchInCap++;
            } elseif ($cells[$gy][$gx] === 'dark' && $enclosed($gy, $gx)) {
                $darkInCap++;
            }
        }
    }

    $n = $grid * $grid;
    $capArea = max(1, $counts['clean'] + $blotchInCap + $darkInCap);
    return [
        'clean' => $counts['clean'] / $n,
        'green' => $counts['green'] / $n,
        'blotch_in_cap' => $blotchInCap / $capArea,
        'dark_in_cap' => $darkInCap / $capArea,
    ];
}

/** Returns [hue 0-360, saturation 0-1, value 0-1]. */
function classify_rgb_to_hsv(int $r, int $g, int $b): array {
    $r /= 255; $g /= 255; $b /= 255;
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $delta = $max - $min;
    $h = 0;
    if ($delta > 0) {
        if ($max === $r) {
            $h = 60 * fmod(($g - $b) / $delta, 6);
        } elseif ($max === $g) {
            $h = 60 * (($b - $r) / $delta + 2);
        } else {
            $h = 60 * (($r - $g) / $delta + 4);
        }
    }
    if ($h < 0) {
        $h += 360;
    }
    return [$h, $max > 0 ? $delta / $max : 0, $max];
}

/**
 * Sends the image to the Python CNN inference API (ai_model/app.py).
 * Returns the result array, or null if the service is unavailable.
 */
function classify_via_cnn_api(string $imagePath): ?array {
    if (!function_exists('curl_init')) {
        return null;
    }
    $ch = curl_init(AI_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['image' => new CURLFile($imagePath)],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 10, // NFR-PERF.1: result within 10 seconds
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = $body ? json_decode($body, true) : null;
    if ($status !== 200 || !isset($data['disease'], $data['confidence'])) {
        return null;
    }
    $scores = isset($data['scores']) && is_array($data['scores']) ? $data['scores'] : [$data['disease'] => $data['confidence']];
    arsort($scores);
    return [
        'disease' => (string) $data['disease'],
        'confidence' => round((float) $data['confidence'], 1),
        'scores' => $scores,
        'model' => (string) ($data['model'] ?? 'cnn'),
    ];
}
