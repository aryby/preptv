<?php

namespace App\Domain\Messaging\Enums;

enum EmailLogStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
}
