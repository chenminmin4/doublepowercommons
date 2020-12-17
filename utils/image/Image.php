<?php


namespace fulicommons\util\image;


/**
 * 图片
 */
class Image
{

    /**
     * 获取类型
     * @param string $key 键值
     * @return string
     */
    public static function getExtension($key)
    {
        $extension = [
            '1'  => 'GIF', '2' => 'JPG', '3' => 'PNG', '4' => 'SWF', '5' => 'PSD',
            '6'  => 'BMP', '7' => 'TIFF', '8' => 'TIFF', '9' => 'JPC', '10' => 'JP2',
            '11' => 'JPX', '12' => 'JB2', '13' => 'SWC', '14' => 'IFF', '15' => 'WBMP',
            '16' => 'XBM'
        ];
        return $extension[$key];
    }

    /**
     * 判断是否是图片
     * @param $type
     * @return bool
     */
    public static function isImg($type){
        $typeArr = [
             'GIF',  'JPG',  'PNG',  'SWF',  'PSD',
            'BMP',  'TIFF',  'TIFF',  'JPC',  'JP2',
            'JPX',  'JB2',  'SWC',  'IFF',  'WBMP',
            'XBM'
        ];

        return in_array(strtoupper($type),$typeArr);
    }

    /**
     * 图片旋转
     * @param string $src       图片完整路径
     * @param int    $direction 方向：1-顺时针90；2-逆时针90
     */
    public static function turn($src, $direction = 2)
    {
        $ext = pathinfo($src)['extension'];
        switch ($ext) {
            case 'gif':
                $img = imagecreatefromgif($src);
                break;
            case 'jpg':
            case 'jpeg':
                $img = imagecreatefromjpeg($src);
                break;
            case 'png':
                $img = imagecreatefrompng($src);
                break;
            default:
                die('图片格式错误!');
                break;
        }
        $width = imagesx($img);
        $height = imagesy($img);
        $img2 = imagecreatetruecolor($height, $width);

        //顺时针旋转90度
        if ($direction == 1) {
            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    imagecopy($img2, $img, $height - 1 - $y, $x, $x, $y, 1, 1);
                }
            }

        } else if ($direction == 2) {
            //逆时针旋转90度
            for ($x = 0; $x < $height; $x++) {
                for ($y = 0; $y < $width; $y++) {
                    imagecopy($img2, $img, $x, $y, $width - 1 - $y, $x, 1, 1);
                }
            }
        }

        switch ($ext) {
            case 'jpg':
            case "jpeg":
                imagejpeg($img2, $src, 9);
                break;

            case "gif":
                imagegif($img2, $src);
                break;

            case "png":
                imagepng($img2, $src, 9);
                break;

            default:
                die('图片格式错误!');
                break;
        }
        imagedestroy($img);
        imagedestroy($img2);
    }

    /**
     * 解决上传图片翻转问题
     * @param $src
     */
    public static function newPictureSteeringCorrection($src){
        $image = \think\Image::open($src);
        $exif = function_exists('exif_read_data') && @exif_imagetype($src) == 2 ? @exif_read_data($src):NULL;

        if(isset($exif) && !empty($exif) && !empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 8:
                    $image->rotate(90)->save($src);
                    break;
                case 3:
                    $image->rotate(180)->save($src);
                    break;
                case 6:
                    if(isset($exif['ColorSpace'])){
                        $image->rotate(0)->save($src);
                    }else{
                        $image->rotate(90)->save($src);
                    }
                    break;
            }
        }
    }

    public static function pictureSteeringCorrection($src){
        $image = imagecreatefromstring(file_get_contents($src));
        $exif = exif_read_data($src);
        if(!empty($exif['Orientation'])) {
            if(in_array($exif['Orientation'],[3,6,8])){
                switch($exif['Orientation']) {
                    case 8:
                        $image = imagerotate($image,90,0);
                        break;
                    case 3:
                        $image = imagerotate($image,180,0);
                        break;
                    case 6:
                        $image = imagerotate($image,-90,0);
                        break;
                }
                imagejpeg($image,$src);
                imagedestroy($image);
            }

        }
    }



}
