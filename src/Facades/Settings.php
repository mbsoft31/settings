<?php

namespace MBsoft\Settings\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MBsoft\Settings\Settings
 */
class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \MBsoft\Settings\Settings::class;
    }
}
