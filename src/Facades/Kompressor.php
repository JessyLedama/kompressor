<?php

namespace JessyLedama\Kompressor\Facades;

use Illuminate\Support\Facades\Facade;

class Kompressor extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'kompressor';
    }
}
