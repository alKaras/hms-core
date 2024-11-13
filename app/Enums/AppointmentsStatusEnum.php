<?php

namespace App\Enums;

enum AppointmentsStatusEnum: string
{
    case CREATED = "created";
    case SCHEDULED = "scheduled";

    case COMPLETED = 'completed';

    case CANCELLED = 'cancelled';
}