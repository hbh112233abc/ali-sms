# ali-sms
php plugin for aliyun sms,only send sms

## install
```
composer require bingher/ali-sms
```

## use
```
use bingher\sms\AliSms;
$config = [
    'version' => '2017-05-25',
    'host' => 'dysmsapi.aliyuncs.com',
    'scheme' => 'http',
    'region_id' => 'cn-hangzhou',
    'access_key' => 'your aliyun accessKeyId',
    'access_secret' => 'your aliyun accessSecret',
    'product' => 'xxx平台',
    'actions' => [
        'register' => [
            'sign_name' => '注册验证',
            'template_code' => 'SMS_67105498',
            'template_param' => [
                'code' => '',
                'product' => '',
            ]
        ],
        'login' => [
            'sign_name' => '登录验证',
            'template_code' => 'SMS_67105500',
            'template_param' => [
                'code' => '',
                'product' => '',
            ]
        ],
        'change_password' => [
            'sign_name' => '变更验证',
            'template_code' => 'SMS_67105496',
            'template_param' => [
                'code' => '',
                'product' => '',
            ]
        ],
    ],
];

$sms = new AliSms($config);
//注册验证
$sms->register('18759201xxx',['code'=>123456]);
//或者
$sms->register('18759201xxx',['code'=>123456,'product'=>'xxx平台']);
//传参中请根据actions中不同动作的template_param的值设置,如果不传product默认取配置的product值

//登录验证
$sms->login('18759201xxx',['code'=>123456]);
//或者
$sms->login('18759201xxx',['code'=>123456,'product'=>'xxx平台']);

//修改密码
$sms->change_password('18759201xxx',['code'=>123456]);
//或者
$sms->changePassword('18759201xxx',['code'=>123456,'product'=>'xxx平台']);

//AliSms中的短信方法可以根据actions配置自动匹配,如上配置有三个方法分别是:register,login,change_password,用户可以根据自己的业务需求增加其他配置
```

## 链式调用
```
//基础用法
\bingher\sms\facade\ThinkAliSms::template('模板编号',['code'=>'12345'])->mobile('18759201xxx')->send();
//单独传参
\bingher\sms\facade\ThinkAliSms::template('模板编号')->param('code','12345')->mobile('18759201xxx')->send();
//数组传参,可以同时传入多个参数
\bingher\sms\facade\ThinkAliSms::template('模板编号')->param(['code'=>'12345'])->mobile('18759201xxx')->send();
//添加签名,默认是配置的product
\bingher\sms\facade\ThinkAliSms::template('模板编号')->param(['code'=>'12345'])->sign('xxx平台')->mobile('18759201xxx')->send();
//多个参数
\bingher\sms\facade\ThinkAliSms::template('模板编号')->param('code','12345')->param('product','xxx平台')->mobile('18759201xxx')->send();
//给多个号码发信息
\bingher\sms\facade\ThinkAliSms::template('模板编号')->param(['code'=>'12345'])->mobile('18759201xxx')->mobile('13950804xxx')->send();
\bingher\sms\facade\ThinkAliSms::template('模板编号')->param(['code'=>'12345'])->mobile(['18759201xxx','13950804xxx'])->send();
```

## config remark
|配置|类型|默认|必须配置|说明|
|-|-|-|-|-|
|version|string|`2017-05-25`|N|日期格式,阿里云短信sdk版本|
|host|string|`dysmsapi.aliyuncs.com`|N|阿里云短信服务器域名|
|scheme|string|http|N|请求协议,https/http|
|region_id|string|`cn-hangzhou`|Y|阿里云短信服务器所在地区,请从阿里云短信服务获取|
|access_key|string||Y|你的阿里云accessKeyId|
|access_secret|string||Y|你的阿里云accessSecrect|
|product|string||Y|你的平台产品名称,actons中template_param参数product用的默认值|
|actions|array||Y|操作配置,不同动作的配置数组,格式为`动作名=>配置项数组`,*动作名*请用全小写下划线格式,如:change_password,如此调用时可以访问`$sms->change_password(...);`亦可`$sms->changePassword(...);`,*配置项*内容请参考阿里云短信模板|
|sign_name|string||N|短信签名,默认为空,如果未设置或者为空则取product值|
|template_code|string||Y|短信模板编号|
|template_param|array||Y|短信传参,根据模板详情设置|

## for thinkphp6
### step1 新增配置文件config/ali_sms.php
```
<?php
return [
    'version' => '2017-05-25',
    'host' => 'dysmsapi.aliyuncs.com',
    'scheme' => 'http',
    'region_id' => 'cn-hangzhou',
    'access_key' => '',
    'access_secret' => '',
    'product' => 'xxx平台',
    'actions' => [
        'register' => [
            'sign_name' => '注册验证',
            'template_code' => 'SMS_67105498',
            'template_param' => [
                'code' => '',
                'product' => '',
            ]
        ],
        'login' => [
            'sign_name' => '登录验证',
            'template_code' => 'SMS_67105500',
            'template_param' => [
                'code' => '',
                'product' => '',
            ]
        ],
        'change_password' => [
            'sign_name' => '变更验证',
            'template_code' => 'SMS_67105496',
            'template_param' => [
                'code' => '',
                'product' => '',
            ]
        ],
    ],
];
```
### step2 使用
#### example 1
```
use bingher\sms\ThinkAliSms;

$sms = new ThinkAliSms;
$sms->login('18759201xxx',['code'=>123456]);

//动态配置
$config = [...];
$sms = new ThinkAliSms($config);
$sms->login('18759201xxx',['code'=>123456]);
```
#### example 2
```
use bingher\sms\facade\ThinkAliSms;

ThinkAliSms::login('18759201xxx',['code'=>123456]);
```
