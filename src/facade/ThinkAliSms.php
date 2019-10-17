<?php
declare (strict_types = 1);

namespace bingher\sms\facade;

use think\Facade;

/**
 * @see \bingher\sms\ThinkAliSms
 * @package think\facade
 * @mixin \bingher\sms\ThinkAliSms
 */
class ThinkAliSms extends Facade
{
    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'bingher\sms\ThinkAliSms';
    }
}
