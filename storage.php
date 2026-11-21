<?php
define('INVITIA_NO_SESSION', true);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Hanya izinkan nama file upload INVITIA yang valid.
$file = $_GET['file'] ?? '';

if (!preg_match('/^[a-f0-9]{13,15}\.[0-9]{8}\.(?:jpe?g|png|mp3)$/i', $file)) {
    http_response_code(404);
    exit('File tidak ditemukan');
}

try {
    $s3 = new S3Client([
        'version' => 'latest',
        'region'  => AWS_REGION,
    ]);

    $result = $s3->getObject([
        'Bucket' => S3_BUCKET,
        'Key'    => $file,
    ]);

    $contentType = $result['ContentType'] ?? 'application/octet-stream';

    if (!in_array($contentType, [
        'image/jpeg',
        'image/png',
        'audio/mpeg',
    ], true)) {
        http_response_code(403);
        exit('Tipe file tidak diizinkan');
    }

    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . $result['ContentLength']);
    header('Cache-Control: public, max-age=3600');
    header('X-Content-Type-Options: nosniff');

    echo $result['Body'];
} catch (AwsException $e) {
    http_response_code(404);
    exit('File tidak ditemukan');
}
