<?php

namespace fulicommons\util\image;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;

class Qr
{
    /**
     * 二维码
     * @param string $text 内容
     * @param string $title 标题
     * @param string $logoPath LOGO绝对路径
     * @param int $type 类型：1-返回URL；2-输出文件流；3-输出BASE64；
     * @param int $size 二维码大小
     * @param int $logoSize LOGO大小
     * @return mixed
     */
    public static function create($text, $title = '', $logoPath = '', $type = 1, $size = 300, $logoSize = 80)
    {
        $qrCode = new QrCode($text);
        $qrCode->setSize($size);

        $qrCode->setWriterByName('png');
        $qrCode->setMargin(10);
        $qrCode->setEncoding('UTF-8');
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH());
        $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0]);
        $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255]);
        $qrCode->setValidateResult(false);

        if ($title != '') {
            // __DIR__ . '/../../../vendor/endroid/qr-code/assets/fonts/noto_sans.otf'
            $qrCode->setLabel($title, 16, null,  LabelAlignment::CENTER);
        }
        if ($logoPath != '') {
            $qrCode->setLogoPath($logoPath);
            $qrCode->setLogoWidth($logoSize);
        }

        if ($type == 1) {
            $path = '/uploads/qrcode/' . date('Ymd') . '/';
            if (!is_dir($_SERVER['DOCUMENT_ROOT'] . $path)) {
                mkdir($_SERVER['DOCUMENT_ROOT'] . $path, 0777, true);
            }
            $filename = date('His') . rand(1000, 9999) . '.png';
            // Save it to a file
            $qrCode->writeFile($_SERVER['DOCUMENT_ROOT'] . $path . $filename);
            return $path . $filename;
        } elseif ($type == 2) {
            // Directly output the QR code
            header('Content-Type: ' . $qrCode->getContentType());
            return $qrCode->writeString();
        } elseif ($type == 3) {
            // Generate a data URI to include image data inline (i.e. inside an <img> tag)
            $dataUri = $qrCode->writeDataUri();
            return $dataUri;
        }
        return null;
    }
}
