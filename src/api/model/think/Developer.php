<?php
namespace fulicommons\api\model\think;


use fulicommons\api\exception\think\AuthInvalidException;
use think\Model;

class Developer extends  Model
{

    /**
     * 根据appid获取开发者相关信息
     * @param string $appid APPID
     * @return array
     */
    public static function checkAppid($appid)
    {

        if (empty($appid)) {
            throw new AuthInvalidException(AuthInvalidException::$APPID_INVALID); //AppID应用ID不能为空
        }
        $one = self::where(['appid' => $appid])->find();
        if (!$one) {
            throw new AuthInvalidException(AuthInvalidException::$APPID_INVALID);; //AppID应用ID不合法
        }
        $one = $one->toArray();
        if ($one) {
            if (!isset($one['appsecret']) || empty($one['appsecret'])) {
                throw new AuthInvalidException(AuthInvalidException::$APPSECRET_INVALID); ////AppSecret应用密钥错误
            } else {
               return $one;
            }
        } else {
            throw new AuthInvalidException(AuthInvalidException::$APPID_INVALID); //AppID应用ID不合法
        }
    }
}
