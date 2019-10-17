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
    ];
    protected $error;
    protected static $snakeCache = [];

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
        $signName      = $conf['sign_name'];
        $templateParam = $conf['template_param'];
        foreach ($templateParam as $k => $v) {
            $templateParam[$k] = empty($params[$k]) ? '' : $params[$k];
        }
        if (empty($templateParam['product'])) {
            $templateParam['product'] = $this->config['product'];
        }
        return $this->send($phoneNumber, $signName, $templateCode, $templateParam);
    }

    /**
     * 发送短信
     * @param  string $phoneNumber   手机号
     * @param  string $signName      短信签名名称
     * @param  string $templateCode  短信模板
     * @param  array  $templateParam 模板传参(数组)
     * @return 发送结果
     */
    public function send(string $phoneNumber, string $signName, string $templateCode, array $templateParam = [])
    {
        $action = 'SendSms';
        $query  = [
            'PhoneNumbers'  => $phoneNumber,
            'SignName'      => $signName,
            'TemplateCode'  => $templateCode,
            'TemplateParam' => json_encode($templateParam),
        ];
        return $this->request($action, $query);
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

            $value = mb_strtolower($value(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value)), 'UTF-8');
        }

        return static::$snakeCache[$key][$delimiter] = $value;
    }
}
