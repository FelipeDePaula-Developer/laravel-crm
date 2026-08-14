<?php

namespace Webkul\Billing\Enums;

enum InstallmentStatus: string
{
    case Pending = 'pending';
    case Overdue = 'overdue';
    case Paid = 'paid';
    case Renegotiated = 'renegotiated';
    case Cancelled = 'cancelled';
}
