<?php

namespace App\Enums;

enum TimeslotStateEnum: int
{
    case FREE = 1;
    case SOLD = 2;

    case RESERVED = 4;
}