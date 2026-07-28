<?php

$source = $argv[1] ?? null;
$dest = $argv[2] ?? null;

if (! $source || ! $dest) {
    fwrite(STDERR, "Usage: php scripts/binarize-logo.php <source.png> <dest.png>\n");
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

$image = imagecreatefrompng($source);

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

        if ($alpha >= 120 || ($red > 240 && $green > 240 && $blue > 240)) {
            $color = imagecolorallocatealpha($image, 0, 0, 0, 127);
        } else {
            $color = imagecolorallocatealpha($image, 255, 255, 255, 0);
        }

        imagesetpixel($image, $x, $y, $color);
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
