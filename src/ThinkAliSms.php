<?php
declare (strict_types = 1);

namespace bingher\sms;

use think\facade\Config;

class ThinkAliSms extends AliSms
{
    public function __construct($config = [])
    {
        $default = Config::get('ali_sms', []);
        $config  = array_merge($default, $config);
        parent::__construct($config);
    }
}
