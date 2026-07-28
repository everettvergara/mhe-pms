<?php

$source = $argv[1] ?? null;
$dest = $argv[2] ?? null;
$key = $argv[3] ?? 'both';

if (! $source || ! $dest) {
    fwrite(STDERR, "Usage: php scripts/transparent-logo.php <source.png> <dest.png> [black|white|both]\n");
    exit(1);
}

if (! in_array($key, ['black', 'white', 'both'], true)) {
    fwrite(STDERR, "Key must be one of: black, white, both\n");
    exit(1);
}

if (! extension_loaded('gd')) {
    fwrite(STDERR, "GD extension is required.\n");
    exit(1);
}

if (! is_file($source)) {
    fwrite(STDERR, "Source file not found: {$source}\n");
    exit(1);
}

$image = match (mime_content_type($source)) {
    'image/png' => imagecreatefrompng($source),
    'image/jpeg' => imagecreatefromjpeg($source),
    default => false,
};

if ($image === false) {
    $image = @imagecreatefrompng($source) ?: @imagecreatefromjpeg($source);
}

if ($image === false) {
    fwrite(STDERR, "Failed to load source image.\n");
    exit(1);
}

imagesavealpha($image, true);
imagealphablending($image, false);

$width = imagesx($image);
$height = imagesy($image);

for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        $rgba = imagecolorat($image, $x, $y);
        $alpha = ($rgba >> 24) & 0x7F;
        $red = ($rgba >> 16) & 0xFF;
        $green = ($rgba >> 8) & 0xFF;
        $blue = $rgba & 0xFF;

        $isNearWhite = $red > 240 && $green > 240 && $blue > 240;
        $isNearBlack = $red < 30 && $green < 30 && $blue < 30;

        $isTransparent = $alpha >= 120
            || ($key !== 'black' && $isNearWhite)
            || ($key !== 'white' && $isNearBlack);

        if ($isTransparent) {
            $color = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagesetpixel($image, $x, $y, $color);
        }
    }
}

$minX = $width;
$minY = $height;
$maxX = 0;
$maxY = 0;

for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        $alpha = (imagecolorat($image, $x, $y) >> 24) & 0x7F;

        if ($alpha < 120) {
            $minX = min($minX, $x);
            $minY = min($minY, $y);
            $maxX = max($maxX, $x);
            $maxY = max($maxY, $y);
        }
    }
}

if ($maxX >= $minX && $maxY >= $minY) {
    $cropWidth = $maxX - $minX + 1;
    $cropHeight = $maxY - $minY + 1;
    $cropped = imagecreatetruecolor($cropWidth, $cropHeight);

    if ($cropped !== false) {
        imagesavealpha($cropped, true);
        imagealphablending($cropped, false);
        $transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
        imagefill($cropped, 0, 0, $transparent);
        imagecopy($cropped, $image, 0, 0, $minX, $minY, $cropWidth, $cropHeight);
        imagedestroy($image);
        $image = $cropped;
    }
}

$destDir = dirname($dest);
if (! is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

if (! imagepng($image, $dest)) {
    fwrite(STDERR, "Failed to write output image.\n");
    imagedestroy($image);
    exit(1);
}

imagedestroy($image);
echo "Wrote {$dest}\n";
