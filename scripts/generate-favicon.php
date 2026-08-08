<?php

/**
 * Generate favicon assets from the FAST color logo.
 *
 * Usage: php scripts/generate-favicon.php
 */

if (! extension_loaded('gd')) {
    fwrite(STDERR, "GD extension is required.\n");
    exit(1);
}

$root = dirname(__DIR__);
$source = $root.'/public/images/fast-logo-color.png';
$imagesDir = $root.'/public/images';

if (! is_file($source)) {
    fwrite(STDERR, "Source logo not found: {$source}\n");
    exit(1);
}

$image = imagecreatefrompng($source);

if ($image === false) {
    fwrite(STDERR, "Failed to load source image.\n");
    exit(1);
}

imagesavealpha($image, true);
imagealphablending($image, false);

$width = imagesx($image);
$height = imagesy($image);

// Remove black background.
for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        $rgba = imagecolorat($image, $x, $y);
        $alpha = ($rgba >> 24) & 0x7F;
        $red = ($rgba >> 16) & 0xFF;
        $green = ($rgba >> 8) & 0xFF;
        $blue = $rgba & 0xFF;

        if ($alpha >= 120 || ($red < 30 && $green < 30 && $blue < 30)) {
            $color = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagesetpixel($image, $x, $y, $color);
        }
    }
}

// Crop to visible content.
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

function resizeToSquare(GdImage $source, int $size): GdImage
{
    $srcW = imagesx($source);
    $srcH = imagesy($source);
    $padding = (int) round($size * 0.08);
    $maxDim = $size - ($padding * 2);

    $scale = min($maxDim / $srcW, $maxDim / $srcH);
    $destW = max(1, (int) round($srcW * $scale));
    $destH = max(1, (int) round($srcH * $scale));

    $dest = imagecreatetruecolor($size, $size);
    imagesavealpha($dest, true);
    imagealphablending($dest, false);
    $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
    imagefill($dest, 0, 0, $transparent);

    $offsetX = (int) round(($size - $destW) / 2);
    $offsetY = (int) round(($size - $destH) / 2);

    imagecopyresampled($dest, $source, $offsetX, $offsetY, 0, 0, $destW, $destH, $srcW, $srcH);

    return $dest;
}

function writePng(GdImage $image, string $path): void
{
    if (! imagepng($image, $path)) {
        fwrite(STDERR, "Failed to write {$path}\n");
        exit(1);
    }

    echo "Wrote {$path}\n";
}

function writeIco(array $pngPaths, string $path): void
{
    $entries = [];

    foreach ($pngPaths as $pngPath) {
        $data = file_get_contents($pngPath);

        if ($data === false) {
            fwrite(STDERR, "Failed to read {$pngPath}\n");
            exit(1);
        }

        $image = imagecreatefrompng($pngPath);
        $entries[] = [
            'width' => imagesx($image),
            'height' => imagesy($image),
            'data' => $data,
        ];
        imagedestroy($image);
    }

    $count = count($entries);
    $header = pack('vvv', 0, 1, $count);
    $directory = '';
    $offset = 6 + ($count * 16);
    $blob = '';

    foreach ($entries as $entry) {
        $size = strlen($entry['data']);
        $directory .= pack(
            'CCCCvvVV',
            $entry['width'] >= 256 ? 0 : $entry['width'],
            $entry['height'] >= 256 ? 0 : $entry['height'],
            0,
            0,
            1,
            32,
            $size,
            $offset
        );
        $blob .= $entry['data'];
        $offset += $size;
    }

    if (file_put_contents($path, $header.$directory.$blob) === false) {
        fwrite(STDERR, "Failed to write {$path}\n");
        exit(1);
    }

    echo "Wrote {$path}\n";
}

$sizes = [
    'favicon-16x16.png' => 16,
    'favicon-32x32.png' => 32,
    'apple-touch-icon.png' => 180,
];

$pngPaths = [];

foreach ($sizes as $filename => $size) {
    $resized = resizeToSquare($image, $size);
    $path = $imagesDir.'/'.$filename;
    writePng($resized, $path);
    $pngPaths[] = $path;
    imagedestroy($resized);
}

writeIco([$pngPaths[0], $pngPaths[1]], $root.'/public/favicon.ico');

imagedestroy($image);
