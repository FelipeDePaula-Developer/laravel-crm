<?php

namespace Webkul\Billing\Enums;

enum BillingStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
}
