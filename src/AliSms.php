<?php
declare (strict_types = 1);

namespace bingher\sms;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

// Download：https://github.com/aliyun/openapi-sdk-php
// Usage：https://github.com/aliyun/openapi-sdk-php/blob/master/README.md

class AliSms
{
    protected $config = [
        'api'           => 'Dysmsapi',
        'version'       => '2017-05-25',
        'host'          => 'dysmsapi.aliyuncs.com',
        'scheme'        => 'http',
        'region_id'     => 'cn-hangzhou',
        'product'       => '',
        'access_key'    => '',
        'access_secret' => '',
        'phone_regex'   => "/^1(3|4|5|6|7|8|9)\d{9}$/",
    ];
    protected $error             = 'not error';
    protected static $snakeCache = [];

    protected $mobileList   = []; //手机号列表
    protected $templateCode = ''; //模板编号
    protected $signName     = ''; //短信签名
    protected $params       = []; //模板传参

    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
        if (empty($this->config['access_key']) || empty($this->config['access_secret'])) {
            throw new \Exception('accessKeyId or accessKeySecret is empty');
        }
        AlibabaCloud::accessKeyClient($this->config['access_key'], $this->config['access_secret'])
            ->regionId($this->config['region_id'])
            ->asDefaultClient();
    }

    /**
     * 数据初始化
     *
     * @return self
     */
    public function init()
    {
        $this->mobileList   = []; //手机号列表
        $this->templateCode = ''; //模板编号
        $this->signName     = ''; //短信签名
        $this->params       = []; //模板传参
        return $this;
    }

    /**
     * 根据配置的actions进行调用
     * @param  string $fun  方法名:register,login,changePassword ...
     * @param  array $args 传参数组,$args[0]为手机号,$args[1]为传参数组如:['code'=>123456,'product'=>'efile',...]
     * @return 发送结果
     */
    public function __call($fun, $args)
    {
        $fun = static::snake($fun);
        if (empty($this->config['actions'][$fun])) {
            throw new \Exception('actions not found:' . $fun);
        }
        $conf          = $this->config['actions'][$fun];
        $phoneNumber   = $args[0];
        $params        = $args[1];
        $templateCode  = $conf['template_code'];
        $signName      = empty($conf['sign_name']) ? $this->config['product'] : $conf['sign_name'];
        $templateParam = $conf['template_param'];
        foreach ($templateParam as $k => $v) {
            $templateParam[$k] = empty($params[$k]) ? '' : $params[$k];
        }
        if (isset($templateParam['product'])) {
            $templateParam['product'] = empty($arg['product']) ? $this->config['product'] : $arg['product'];
        }
        return $this->sendSms($phoneNumber, $signName, $templateCode, $templateParam);
    }

    /**
     * 指定收信手机号
     *
     * @param string|array $phone
     * @return self
     */
    public function mobile($phone)
    {
        if (is_string($phone)) {
            $phone = [$phone];
        }
        if (!is_array($phone)) {
            throw new \Exception('手机号传参请传入string或array类型');
        }

        foreach ($phone as &$num) {
            $num = trim($num);
            if (!preg_match($this->config['phone_regex'], $num)) {
                throw new \Exception('手机号格式有误:' . $num);
            }
        }
        $this->mobileList = array_merge($phone);
        return $this;
    }

    /**
     * 设置签名
     *
     * @param string $signName
     * @return self
     */
    public function sign($signName)
    {
        $this->signName = $signName;
        return $this;
    }

    /**
     * 设置短信模板编号
     *
     * @param string $code 模板编号
     * @param array $params 模板传参
     * @return self
     */
    public function template($code, $params = [])
    {
        $this->templateCode = $code;
        if (is_array($params) && !empty($params)) {
            $this->params = $params;
        }
        return $this;
    }

    /**
     * 设置传参
     *
     * @param string|array $key 参数名或传参数组
     * @param mixed $value 参数值,如果为null则unset($this->params[$key])
     * @return self
     */
    public function param($key, $value = '')
    {
        if (is_array($key)) {
            $params = $key;
        }
        if (is_string($key)) {
            if (is_null($value)) {
                unset($this->param[$key]);
            } else {
                $params = [$key => $value];
            }
        }
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * 链式操作:发送短信
     *
     * @param boolean $cache 是否保持设置值
     * @return mixed
     */
    public function send($cache = false)
    {
        if (empty($this->mobileList)) {
            throw new \Exception('请输入手机号');
        }
        if (empty($this->signName)) {
            $this->signName = $this->config['product'];
        }
        if (empty($this->templateCode)) {
            throw new \Exception('请输入短信模板编号');
        }
        $error = '';
        foreach ($this->mobileList as $mobile) {
            $res = $this->sendSms($mobile, $this->signName, $this->templateCode, $this->params);
            if ($res !== true) {
                $error = $error . $mobile . '短信发送失败:' . $res . ';';
            }
        }
        $this->mobileList = [];
        if (!$cache) {
            $this->init();
        }
        if (!empty($error)) {
            $this->init();
            return $error;
        }

        return true;
    }

    /**
     * 发送短信
     * @param  string $phoneNumber   手机号
     * @param  string $signName      短信签名名称
     * @param  string $templateCode  短信模板
     * @param  array  $templateParam 模板传参(数组)
     * @return 发送结果
     */
    public function sendSms(string $phoneNumber, string $signName, string $templateCode, array $templateParam = [])
    {
        $action = 'SendSms';
        $query  = [
            'PhoneNumbers'  => $phoneNumber,
            'SignName'      => $signName,
            'TemplateCode'  => $templateCode,
            'TemplateParam' => json_encode($templateParam),
        ];
        $req = $this->request($action, $query);
        if ($req === false) {
            return $this->getError();
        }
        return true;
    }

    /**
     * 发送请求
     * @param  string $action 请求操作
     * @param  array  $query  请求传参
     * @return array|bool         请求结果
     */
    public function request(string $action, array $query = [])
    {
        $query['RegionId'] = $this->config['region_id'];
        try {
            $result = AlibabaCloud::rpc()
                ->product($this->config['api'])
                ->scheme($this->config['scheme']) // https | http
                ->version($this->config['version'])
                ->action($action)
                ->method('POST')
                ->host($this->config['host'])
                ->options([
                    'query' => $query,
                ])
                ->request();
            $result->toArray();
            if ($result['Code'] != 'OK') {
                throw new \Exception($result['Message']);
            }
            return $result;
        } catch (ClientException $e) {
            $this->error = $e->getErrorMessage();
        } catch (ServerException $e) {
            $this->error = $e->getErrorMessage();
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
        return false;
    }

    /**
     * 获取错误信息
     * @return string 错误信息
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * 驼峰转下划线
     *
     * @param  string $value
     * @param  string $delimiter
     * @return string
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        $key = $value;

        if (isset(static::$snakeCache[$key][$delimiter])) {
            return static::$snakeCache[$key][$delimiter];
        }

        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', $value);

            $value = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value), 'UTF-8');
        }

        return static::$snakeCache[$key][$delimiter] = $value;
    }
}
