<?php
/**
 * Lapisan penyimpanan file upload INVITIA.
 * Mendukung penyimpanan lokal dan Amazon S3.
 */
require_once __DIR__ . '/../config.php';

function storage_driver() {
    return defined('STORAGE_DRIVER') ? STORAGE_DRIVER : 'local';
}

function storage_local_dir() {
    return __DIR__ . '/../assets/';
}

function storage_name_pattern() {
    return '[a-f0-9]{13,15}\.[0-9]{8}\.(?:jpe?g|png|mp3)';
}

function storage_s3_client() {
    require_once __DIR__ . '/../vendor/autoload.php';

    return new Aws\S3\S3Client([
        'version' => 'latest',
        'region'  => AWS_REGION,
    ]);
}

/**
 * Simpan file dan kembalikan URL publik yang stabil.
 */
function storage_put($tmpPath, $ext) {
    $name = uniqid('', true) . '.' . strtolower($ext);

    switch (storage_driver()) {
        case 's3':
            try {
                $s3 = storage_s3_client();

                $s3->putObject([
                    'Bucket'      => S3_BUCKET,
                    'Key'         => $name,
                    'SourceFile'  => $tmpPath,
                    'ContentType' => match (strtolower($ext)) {
                        'jpg', 'jpeg' => 'image/jpeg',
                        'png'         => 'image/png',
                        'mp3'         => 'audio/mpeg',
                        default       => 'application/octet-stream',
                    },
                ]);

                return rtrim(BASE_URL, '/') . '/storage/' . $name;
            } catch (Aws\Exception\AwsException $e) {
                error_log('INVITIA S3 upload error: ' . $e->getMessage());
                return null;
            }

        case 'local':
        default:
            $dir = storage_local_dir();

            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            if (!move_uploaded_file($tmpPath, $dir . $name)) {
                return null;
            }

            return rtrim(BASE_URL, '/') . '/assets/' . $name;
    }
}

/**
 * Temukan referensi file dari URL S3 proxy maupun URL lokal lama.
 */
function storage_refs_in($text) {
    $pattern = '~/(?:storage|assets)/(' . storage_name_pattern() . ')~i';

    if (!preg_match_all($pattern, (string)$text, $matches)) {
        return [];
    }

    return array_values(array_unique($matches[1]));
}

/**
 * Hapus file dari S3 atau penyimpanan lokal.
 */
function storage_delete($ref) {
    if (!preg_match('~^' . storage_name_pattern() . '$~i', (string)$ref)) {
        return false;
    }

    switch (storage_driver()) {
        case 's3':
            try {
                $s3 = storage_s3_client();

                $s3->deleteObject([
                    'Bucket' => S3_BUCKET,
                    'Key'    => $ref,
                ]);

                return true;
            } catch (Aws\Exception\AwsException $e) {
                error_log('INVITIA S3 delete error: ' . $e->getMessage());
                return false;
            }

        case 'local':
        default:
            $dir = realpath(storage_local_dir());
            $file = $dir ? realpath($dir . DIRECTORY_SEPARATOR . $ref) : false;

            if ($file && strpos($file, $dir . DIRECTORY_SEPARATOR) === 0 && is_file($file)) {
                return @unlink($file);
            }

            return false;
    }
}
