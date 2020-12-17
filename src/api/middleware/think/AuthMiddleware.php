<?php
namespace fulicommons\api\middleware\think;

use fulicommons\api\model\think\Developer;
use fulicommons\util\crypt\Json;
use think\exception\HttpException;
use think\Request;
use think\Response;

class AuthMiddleware
{
    private $headers;
    private $headerRequires = [
        'h-appid|require', 'h-appsecret', 'h-appversion|require', 'h-deviceid|require', 'h-devicemodel', 'h-os', 'h-osversion', 'h-nettype', 'h-timestamp|require', 'h-signature|require'
    ];
    /**
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {

        //
        //$this->checkVisitor($request);

        //排除不签名路径
        $action = strtolower(str_replace(".",'/',$request->controller()."/".$request->action()));
        $actionExperts = config('app.except_sign');
        if(!in_array($action,$actionExperts)) {
            $res = $this->checkSign($request);
            if (!$res) {
                return $this->throwError(['errmsg' => '禁止访问']);
            }
        }
        $response = $next($request);

        return $this->responseSign($response);
    }

    /**
     * @param Response $response
     * @return Response
     */
    private function responseSign($response)
    {
        $data = $this->headers;
        $data['h-timestamp'] = time();
        unset($data['h-signature']);
        $resData = $response->getData();
        if (gettype($resData) !== 'array') $resData = [$resData];
        $sign = $this->getSign(array_merge($resData, $data));
        $data['h-signature'] = $sign;
        unset($data['h-appsecret']);

        $response->header($data);

        return $response;
    }


    /**
     * 校验访客是否可以访问
     * @param Request $request
     * @return boolean
     */
    protected function checkVisitor($request)
    {
        $ip = $request->ip();
        $clientId = $request->header('h-deviceid');
        $res = Visitor::check($ip, $clientId);
        if ($res) {
            return $this->throwError(['errerrmsg' => '禁止访问']);
        } else {
            return true;
        }
    }

    /**
     * 校检签名
     * @param Request $request
     * @return bool
     */
    private function checkSign($request)
    {
        $this->collectHeaders($request);
        $this->chechAppid($this->headers['h-appid']);
        $tmpData = array_merge($this->headers, $request->post());
        unset($tmpData['h-signature']); //移除签名
        $sign = $this->getSign($tmpData); //本地签名
        if ($this->headers['h-signature'] == $sign) {
            return true;
        }
        return false;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    private function collectHeaders($request)
    {
        $in = $request->header();
        $data = [];
        foreach ($this->headerRequires as $key) {
            if (strpos($key, "|")) {
                list($key, $rule) = explode("|", $key);
                if ($rule == "require" && !isset($in[$key])) {
                    return $this->throwError(['errmsg' => '禁止访问:'.$key]);
                }
            }

            $data[$key] = isset($in[$key]) ? $in[$key] : '';
        }
        $this->headers = $data;
    }

    private function throwError($data)
    {
        throw  new HttpException(403, Json::encode($data));
    }

    private function chechAppid($appid)
    {
        $developer = Developer::checkAppid($appid);
        $this->headers['h-appsecret'] = $developer['appsecret'];
    }

    /**
     * 生成签名
     * @param array $data
     * @return string MD5大写
     */
    private function getSign($data)
    {
        $String = '';
        $this->formatSign($data, $String);

        $String = md5($String);
        $result = strtoupper($String);
        return $result;
    }

    /**
     * 格式化参数，签名过程需要使用
     * @param array $paraMap 需拼接数组
     * @param string $buff URL拼接结果
     */
    private function formatSign($paraMap, &$buff)
    {
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            //是数组递归
            if (is_array($v)) {
                ksort($v);
                $this->formatSign($v, $buff);
            } else {
                $buff .= $buff ? "&" : "";
                $buff .= $k . "=" . $v;
            }
        }
    }
}
