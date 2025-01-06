<?php

namespace Samcbdev\MailNotifier\Facades;

use Illuminate\Support\Facades\Facade;

class MailerFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'Mailer';
    }
}
