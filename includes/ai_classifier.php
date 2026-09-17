<?php
/**
 * AI disease classification service for the "Snap & Detect" feature (FR-AI.1 - FR-AI.6).
 *
 * Design note (see SRS NFR-MAINT.2): this function is the single integration point
 * between the marketplace/UI code and the disease-detection model. It currently
 * ships with a lightweight colour-signature heuristic (built on PHP's GD extension,
 * which is bundled with XAMPP) so the full system is runnable end-to-end without a
 * GPU or external services. To use the trained Convolutional Neural Network from the
 * research methodology (Section 4.5 of the proposal) instead, replace the body of
 * classify_mushroom_image() with a call to that model's inference API (e.g. a small
 * Flask/TensorFlow-Serving endpoint) and keep the same return shape - no other file
 * needs to change.
 *
 * Returns: ['disease' => string, 'confidence' => float(0-100), 'disease_id' => int|null]
 */

function classify_mushroom_image(string $imagePath): array {
    $diseases = classify_load_disease_map();

    $signature = classify_extract_color_signature($imagePath);
    if ($signature === null) {
        // Image could not be read (corrupt file); default to a low-confidence Healthy guess.
        return ['disease' => 'Healthy', 'confidence' => 40.0, 'disease_id' => $diseases['Healthy'] ?? null];
    }

    [$avgR, $avgG, $avgB, $darkRatio, $greenRatio] = $signature;

    // Heuristic scoring per class. Each score is a rough proxy built from average
    // colour channels and the proportion of dark/green pixels sampled from the image.
    $scores = [
        'Green Mold' => max(0, ($greenRatio * 140) - ($avgB > $avgG ? 20 : 0)),
        'Bacterial Blotch' => max(0, ($darkRatio * 100) + max(0, ($avgR - $avgG) * 0.4)),
        'Pest Attack' => max(0, (abs($avgR - $avgB) * 0.3) + ($darkRatio * 40)),
        'Healthy' => max(0, 60 - ($greenRatio * 100) - ($darkRatio * 80)),
    ];

    arsort($scores);
    $topDisease = array_key_first($scores);
    $topScore = reset($scores);

    $total = array_sum($scores) ?: 1;
    $confidence = round(min(97, max(35, ($topScore / $total) * 100 + 25)), 1);

    return [
        'disease' => $topDisease,
        'confidence' => $confidence,
        'disease_id' => $diseases[$topDisease] ?? null,
    ];
}

function classify_load_disease_map(): array {
    global $pdo;
    static $map = null;
    if ($map === null) {
        $map = [];
        $stmt = $pdo->query('SELECT id, name FROM disease_types');
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['name']] = (int) $row['id'];
        }
    }
    return $map;
}

/**
 * Samples a grid of pixels from the image and returns average RGB channels
 * plus the ratio of "dark" pixels and "green-dominant" pixels.
 * Returns null if the image cannot be decoded.
 */
function classify_extract_color_signature(string $path): ?array {
    $info = @getimagesize($path);
    if (!$info) {
        return null;
    }

    $image = match ($info['mime']) {
        'image/jpeg' => @imagecreatefromjpeg($path),
        'image/png' => @imagecreatefrompng($path),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
        default => null,
    };
    if (!$image) {
        return null;
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $gridSize = 20; // sample a 20x20 grid regardless of source resolution

    $sumR = $sumG = $sumB = 0;
    $darkCount = 0;
    $greenCount = 0;
    $samples = 0;

    for ($gx = 0; $gx < $gridSize; $gx++) {
        for ($gy = 0; $gy < $gridSize; $gy++) {
            $x = (int) (($gx + 0.5) / $gridSize * $width);
            $y = (int) (($gy + 0.5) / $gridSize * $height);
            $x = min($width - 1, max(0, $x));
            $y = min($height - 1, max(0, $y));

            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            $sumR += $r;
            $sumG += $g;
            $sumB += $b;
            $samples++;

            $brightness = ($r + $g + $b) / 3;
            if ($brightness < 70) {
                $darkCount++;
            }
            if ($g > $r + 15 && $g > $b + 15) {
                $greenCount++;
            }
        }
    }

    imagedestroy($image);

    if ($samples === 0) {
        return null;
    }

    return [
        $sumR / $samples,
        $sumG / $samples,
        $sumB / $samples,
        $darkCount / $samples,
        $greenCount / $samples,
    ];
}
