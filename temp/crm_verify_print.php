<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

$baseDir = __DIR__ . '/files';
$requestedFile = basename((string) ($_GET['file'] ?? ''));
$filePath = $requestedFile !== '' ? $baseDir . '/' . $requestedFile : '';
$paper = strtolower((string) ($_GET['paper'] ?? 'legal'));

if ($requestedFile === '' || !is_file($filePath)) {
    http_response_code(404);
    exit('Verification file not found.');
}

$documentCode = pathinfo($requestedFile, PATHINFO_FILENAME);
$verifyUrl = 'https://www.uscapitalprivatebank.com/crm/verify/verifycode.php?code=' . rawurlencode($documentCode);
$verifyLandingUrl = 'www.uscapitalprivatebank.com/crm/verify';
$extension = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));
$paperOptions = [
    'legal' => ['format' => 'LEGAL', 'width' => 612, 'height' => 1008, 'label' => 'Legal 8.5 x 14'],
    'letter' => ['format' => 'LETTER', 'width' => 612, 'height' => 792, 'label' => 'Letter 8.5 x 11'],
    'a4' => ['format' => 'A4', 'width' => 595, 'height' => 842, 'label' => 'A4'],
];
$paperConfig = $paperOptions[$paper] ?? $paperOptions['legal'];

require_once __DIR__ . '/phpqrcode/qrlib.php';
require_once dirname(__DIR__) . '/application/vendor/tecnickcom/tcpdf/tcpdf.php';

$cacheDir = __DIR__ . '/generated';
$qrDir = __DIR__ . '/tempqr';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0775, true);
}
if (!is_dir($qrDir)) {
    @mkdir($qrDir, 0775, true);
}

$qrPath = $qrDir . '/' . md5($requestedFile . '-print') . '.png';
if (!is_file($qrPath)) {
    QRcode::png($verifyUrl, $qrPath, QR_ECLEVEL_L, 6);
}

$pdf = new TCPDF('P', 'pt', $paperConfig['format'], true, 'UTF-8', false);
$pdf->SetCreator('US Capital Private Bank');
$pdf->SetAuthor('US Capital Private Bank');
$pdf->SetTitle('Verified Print Copy - ' . $documentCode);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

function verify_render_page_to_tcpdf($pdf, $imageBlob, $qrPath, $documentCode, $requestedFile, $verifyLandingUrl, array $paperConfig)
{
    $pageImage = new Imagick();
    $pageImage->readImageBlob($imageBlob);
    $pageImage->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
    $pageImage->setImageBackgroundColor('white');
    $pageImage = $pageImage->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

    $pageWidthPt = (int) $paperConfig['width'];
    $pageHeightPt = (int) $paperConfig['height'];
    $pageMargin = 24;
    $footerHeight = 118;
    $dpi = 144;
    $canvasWidth = (int) round($pageWidthPt * ($dpi / 72));
    $canvasHeight = (int) round($pageHeightPt * ($dpi / 72));
    $marginPx = (int) round($pageMargin * ($dpi / 72));
    $footerHeightPx = (int) round($footerHeight * ($dpi / 72));
    $contentWidth = $canvasWidth - ($marginPx * 2);
    $contentHeight = $canvasHeight - ($marginPx * 2) - $footerHeightPx;

    $width = $pageImage->getImageWidth();
    $height = $pageImage->getImageHeight();
    $contentScale = min($contentWidth / $width, $contentHeight / $height);
    $renderWidth = max(1, (int) round($width * $contentScale));
    $renderHeight = max(1, (int) round($height * $contentScale));
    $pageImage->resizeImage($renderWidth, $renderHeight, Imagick::FILTER_LANCZOS, 1);

    $canvas = new Imagick();
    $canvas->newImage($canvasWidth, $canvasHeight, new ImagickPixel('white'));
    $contentX = (int) round(($canvasWidth - $renderWidth) / 2);
    $contentY = $marginPx;
    $canvas->compositeImage($pageImage, Imagick::COMPOSITE_DEFAULT, $contentX, $contentY);

    $footer = new Imagick();
    $footer->newImage($canvasWidth, $footerHeightPx, new ImagickPixel('#f7fbff'));
    $footerDraw = new ImagickDraw();
    $footerDraw->setFillColor(new ImagickPixel('#d9ecff'));
    $footerDraw->rectangle(0, 0, $canvasWidth, 14);
    $footer->drawImage($footerDraw);
    $footerY = $canvasHeight - $footerHeightPx;
    $canvas->compositeImage($footer, Imagick::COMPOSITE_DEFAULT, 0, $footerY);

    $draw = new ImagickDraw();
    $draw->setFillColor(new ImagickPixel('#0f1f45'));
    $draw->setFontSize(12);
    $draw->setFontWeight(500);
    $leftColumnX = 36;
    $centerColumnX = 218;
    $rightColumnX = $canvasWidth - 136;

    $canvas->annotateImage($draw, $leftColumnX, $footerY + 40, 0, 'Document Code');
    $canvas->annotateImage($draw, $leftColumnX, $footerY + 58, 0, $documentCode);
    $canvas->annotateImage($draw, $leftColumnX, $footerY + 80, 0, 'Verify Online');
    $canvas->annotateImage($draw, $leftColumnX, $footerY + 98, 0, $verifyLandingUrl);
    $canvas->annotateImage($draw, $leftColumnX, $footerY + 120, 0, 'File Reference');
    $canvas->annotateImage($draw, $leftColumnX, $footerY + 138, 0, $requestedFile);

    $statementTitleDraw = new ImagickDraw();
    $statementTitleDraw->setFillColor(new ImagickPixel('#0f1f45'));
    $statementTitleDraw->setFontSize(18);
    $statementTitleDraw->setFontWeight(700);
    $statementTitle = 'Certified US Capital Private Bank Document';
    $statementRule = 'Under Rule 902';

    $statementBodyDraw = new ImagickDraw();
    $statementBodyDraw->setFillColor(new ImagickPixel('#0f1f45'));
    $statementBodyDraw->setFontSize(11);
    $statementBodyDraw->setFontWeight(500);
    $statementLines = [
        'Evidence That Is Self-Authenticating.',
        'The following items of evidence are self-authenticating; they require no extrinsic evidence of authenticity.',
        'Recipients of documents of the U.S. Capital Private Bank are advised to verify authenticity using the QR code',
        'or document code at the secure link in this document footer prior to acceptance, reliance, or transaction use.',
        'All documents issued by this bank are verified this way.',
    ];
    $statementAreaLeft = $centerColumnX;
    $statementAreaRight = $rightColumnX - 24;
    $statementAreaCenter = (int) round(($statementAreaLeft + $statementAreaRight) / 2);
    $statementY = $footerY + 40;

    $titleMetrics = $canvas->queryFontMetrics($statementTitleDraw, $statementTitle);
    $titleX = (int) round($statementAreaCenter - (($titleMetrics['textWidth'] ?? 0) / 2));
    $canvas->annotateImage($statementTitleDraw, $titleX, $statementY, 0, $statementTitle);
    $statementY += 18;

    $ruleMetrics = $canvas->queryFontMetrics($statementBodyDraw, $statementRule);
    $ruleX = (int) round($statementAreaCenter - (($ruleMetrics['textWidth'] ?? 0) / 2));
    $canvas->annotateImage($statementBodyDraw, $ruleX, $statementY, 0, $statementRule);
    $statementY += 16;

    $lineHeight = 14;
    foreach ($statementLines as $line) {
        $lineMetrics = $canvas->queryFontMetrics($statementBodyDraw, $line);
        $lineX = (int) round($statementAreaCenter - (($lineMetrics['textWidth'] ?? 0) / 2));
        $canvas->annotateImage($statementBodyDraw, $lineX, $statementY, 0, $line);
        $statementY += $lineHeight;
    }

    $qr = new Imagick($qrPath);
    $qr->resizeImage(116, 116, Imagick::FILTER_LANCZOS, 1, true);
    $canvas->compositeImage($qr, Imagick::COMPOSITE_DEFAULT, $rightColumnX, $footerY + 16);
    $labelMetrics = $canvas->queryFontMetrics($draw, 'QR Verification');
    $labelX = (int) round($rightColumnX + ((116 - ($labelMetrics['textWidth'] ?? 0)) / 2));
    $canvas->annotateImage($draw, $labelX, $footerY + 146, 0, 'QR Verification');

    $canvas->setImageFormat('jpeg');
    $jpgBlob = $canvas->getImageBlob();

    $tempImage = tempnam(sys_get_temp_dir(), 'verify_print_') . '.jpg';
    file_put_contents($tempImage, $jpgBlob);

    $pdf->AddPage('P', $paperConfig['format']);
    $pdf->Image($tempImage, 0, 0, $pageWidthPt, $pageHeightPt, 'JPG', '', '', true, 300, '', false, false, 0, false, false, false);
    $verifyLinkMetrics = $canvas->queryFontMetrics($draw, $verifyLandingUrl);
    $linkScale = 72 / $dpi;
    $pdf->Link(
        $leftColumnX * $linkScale,
        ($footerY + 84) * $linkScale,
        (($verifyLinkMetrics['textWidth'] ?? 180) + 8) * $linkScale,
        18 * $linkScale,
        'https://' . ltrim($verifyLandingUrl, '/')
    );

    @unlink($tempImage);
}

try {
    if (in_array($extension, ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
        if ($extension === 'pdf') {
            $imagick = new Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($filePath);
            foreach ($imagick as $page) {
                $page->setImageFormat('png');
                verify_render_page_to_tcpdf($pdf, $page->getImageBlob(), $qrPath, $documentCode, $requestedFile, $verifyLandingUrl, $paperConfig);
            }
        } else {
            $imagick = new Imagick($filePath);
            $imagick->setImageFormat('png');
            verify_render_page_to_tcpdf($pdf, $imagick->getImageBlob(), $qrPath, $documentCode, $requestedFile, $verifyLandingUrl, $paperConfig);
        }
    } else {
        $pdf->AddPage('P', $paperConfig['format']);
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetTextColor(15, 31, 69);
        $pdf->Text(40, 60, 'This file type cannot be stamped directly for print.');
        $pdf->Text(40, 88, 'Document Code: ' . $documentCode);
        $pdf->Text(40, 116, 'Verify Online: ' . $verifyLandingUrl);
        $pdf->Text(40, 144, 'File Reference: ' . $requestedFile);
        $pdf->Text(40, 172, 'Recipients are advised to verify authenticity prior to acceptance, reliance, or transaction use.');
        $pdf->Text(40, 200, 'Formatted For: ' . $paperConfig['label']);
        $pdf->Image($qrPath, 420, 60, 120, 120, 'PNG');
    }
} catch (Throwable $e) {
    http_response_code(500);
    exit('Unable to generate the print-ready verification copy: ' . $e->getMessage());
}

$pdf->Output('verified-' . $documentCode . '.pdf', 'I');
