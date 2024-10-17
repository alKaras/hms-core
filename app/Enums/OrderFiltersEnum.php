<?php
namespace App\Enums;

enum OrderFiltersEnum: string
{
    case OrdersById = "OrdersbyId";
    case OrdersbySession = "OrdersbySession";
    case OrdersbyDoctor = "OrdersbyDoctor";
    case OrdersbyUser = "OrdersbyUser";
}