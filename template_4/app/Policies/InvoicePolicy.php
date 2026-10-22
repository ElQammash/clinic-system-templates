<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::InvoicesView->value);
    }

    /** Billing staff see every invoice; doctors only the invoices of their own visits. */
    public function view(User $user, Invoice $invoice): bool
    {
        if (! $user->can(Permission::InvoicesView->value)) {
            return false;
        }

        return $user->can(Permission::InvoicesCreate->value)
            || $user->can(Permission::ReportsViewFinancial->value)
            || $invoice->doctor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::InvoicesCreate->value);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can(Permission::InvoicesUpdate->value) && ! $invoice->isVoid() && ! $invoice->trashed();
    }

    public function pay(User $user, Invoice $invoice): bool
    {
        return $user->can(Permission::PaymentsCreate->value) && ! $invoice->isVoid() && ! $invoice->trashed();
    }

    public function void(User $user, Invoice $invoice): bool
    {
        return $user->can(Permission::InvoicesVoid->value) && ! $invoice->isVoid();
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->can(Permission::InvoicesDelete->value);
    }
}
