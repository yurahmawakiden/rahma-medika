<?php
// vendor/autoload.php - Autoloader Composer & FPDF untuk Rahma Medika

spl_autoload_register(function (string $class): void {
    if ($class === 'FPDF') {
        $fpdfPath = __DIR__ . '/setasign/fpdf/fpdf.php';
        if (file_exists($fpdfPath)) {
            require_once $fpdfPath;
        }
    }
});

if (!class_exists('FPDF') && file_exists(__DIR__ . '/setasign/fpdf/fpdf.php')) {
    require_once __DIR__ . '/setasign/fpdf/fpdf.php';
}

