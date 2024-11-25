<?php

namespace App\Enums;

enum OrderStatusEnum: int
{
    case PENDING = 1;
    case SOLD = 2;

    case CANCELED = 3;
}
