<?php

namespace App\Modules\Tenancy\Enums;

enum TenantRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Agent = 'agent';
}
