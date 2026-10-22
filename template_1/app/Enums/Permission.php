<?php

namespace App\Enums;

/**
 * Every permission used in the system. Seeded on install; roles are
 * mapped in Role::defaultPermissions() and editable from Settings.
 */
enum Permission: string
{
    // Patients
    case PatientsView = 'patients.view';
    case PatientsCreate = 'patients.create';
    case PatientsUpdate = 'patients.update';
    case PatientsDelete = 'patients.delete';
    case PatientsExport = 'patients.export';

    // Attachments (X-rays, lab PDFs, images). "view" = open medical files.
    case AttachmentsView = 'attachments.view';
    case AttachmentsUpload = 'attachments.upload';
    case AttachmentsDelete = 'attachments.delete';

    // Scheduling
    case AppointmentsView = 'appointments.view';
    case AppointmentsViewAll = 'appointments.view_all';
    case AppointmentsManage = 'appointments.manage';
    case QueueManage = 'queue.manage';
    case ScheduleManage = 'schedule.manage';

    // Medical records
    case VisitsView = 'visits.view';
    case VisitsCreate = 'visits.create';
    case VisitsUpdate = 'visits.update';
    case VisitsDelete = 'visits.delete';
    case RecordsViewAll = 'records.view_all';
    case PrescriptionsManage = 'prescriptions.manage';
    case PrescriptionTemplatesManage = 'prescription_templates.manage';
    case MedicinesManage = 'medicines.manage';
    case LabView = 'lab.view';
    case LabManage = 'lab.manage';

    // Billing
    case InvoicesView = 'invoices.view';
    case InvoicesCreate = 'invoices.create';
    case InvoicesUpdate = 'invoices.update';
    case InvoicesVoid = 'invoices.void';
    case InvoicesDelete = 'invoices.delete';
    case PaymentsCreate = 'payments.create';
    case PaymentsDelete = 'payments.delete';
    case ServicesManage = 'services.manage';

    // Expenses & inventory
    case ExpensesView = 'expenses.view';
    case ExpensesManage = 'expenses.manage';
    case InventoryView = 'inventory.view';
    case InventoryManage = 'inventory.manage';

    // Reports
    case ReportsViewOwn = 'reports.view_own';
    case ReportsViewFinancial = 'reports.view_financial';
    case ReportsViewAll = 'reports.view_all';

    // Administration
    case SettingsManage = 'settings.manage';
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';
    case CustomFieldsManage = 'custom_fields.manage';
    case ActivityLogView = 'activity_log.view';
    case BackupsManage = 'backups.manage';
    case NotificationsSend = 'notifications.send';

    public function group(): string
    {
        return explode('.', $this->value)[0];
    }

    public function label(): string
    {
        return __('permissions.'.$this->value);
    }

    public static function groupLabel(string $group): string
    {
        return __('permissions.groups.'.$group);
    }

    /** @return array<string, list<self>> */
    public static function grouped(): array
    {
        return collect(self::cases())->groupBy(fn (self $p) => $p->group())->map->all()->all();
    }
}
