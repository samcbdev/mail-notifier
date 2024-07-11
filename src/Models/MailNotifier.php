<?php

namespace Samcbdev\MailNotifier\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MailNotifier extends Model
{
    use SoftDeletes;

    public function getTable()
    {
        return config('mail-notifier.table_name');
    }

    protected $casts = [
        'custom_fields' => 'json'
    ];
}
