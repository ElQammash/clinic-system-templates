<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Doctor = 'doctor';
    case Receptionist = 'receptionist';
    case Accountant = 'accountant';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Admin'),
            self::Doctor => __('Doctor'),
            self::Receptionist => __('Receptionist'),
            self::Accountant => __('Accountant'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'violet',
            self::Doctor => 'primary',
            self::Receptionist => 'sky',
            self::Accountant => 'amber',
        };
    }

    /**
     * Default permissions granted to each role on install.
     * Admin bypasses every check through Gate::before, but also receives
     * all permissions so the role matrix in Settings reads correctly.
     *
     * @return list<Permission>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),

            self::Doctor => [
                Permission::PatientsView, Permission::PatientsCreate, Permission::PatientsUpdate,
                Permission::AttachmentsView, Permission::AttachmentsUpload,
                Permission::AppointmentsView, Permission::AppointmentsManage, Permission::QueueManage,
                Permission::VisitsView, Permission::VisitsCreate, Permission::VisitsUpdate,
                Permission::PrescriptionsManage, Permission::PrescriptionTemplatesManage, Permission::MedicinesManage,
                Permission::LabView, Permission::LabManage,
                Permission::InvoicesView,
                Permission::ReportsViewOwn,
                Permission::NotificationsSend,
            ],

            self::Receptionist => [
                Permission::PatientsView, Permission::PatientsCreate, Permission::PatientsUpdate,
                Permission::AttachmentsUpload,
                Permission::AppointmentsView, Permission::AppointmentsViewAll, Permission::AppointmentsManage,
                Permission::QueueManage,
                Permission::InvoicesView, Permission::InvoicesCreate, Permission::InvoicesUpdate,
                Permission::PaymentsCreate,
                Permission::NotificationsSend,
            ],

            self::Accountant => [
                Permission::PatientsView,
                Permission::InvoicesView, Permission::InvoicesCreate, Permission::InvoicesUpdate, Permission::InvoicesVoid,
                Permission::PaymentsCreate, Permission::PaymentsDelete,
                Permission::ExpensesView, Permission::ExpensesManage,
                Permission::InventoryView,
                Permission::ReportsViewFinancial,
            ],
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $r) => [$r->value => $r->label()])->all();
    }
}
