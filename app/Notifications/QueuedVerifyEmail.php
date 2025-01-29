<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;

class QueuedVerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

}
