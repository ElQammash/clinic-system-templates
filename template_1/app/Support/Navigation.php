<?php

namespace App\Support;

use App\Enums\Module;
use App\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * The application menu, shared by all templates. Each template decides how
 * to render it (sidebar, top bar, bottom tabs) and maps the icon keys to its
 * own icon set. Items whose route does not exist yet, whose module is off,
 * or which the user may not access are filtered out.
 */
class Navigation
{
    /**
     * @return list<array{key: string, label: string, items: list<array{label: string, route: string, url: string, icon: string, active: bool}>}>
     */
    public static function for(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return collect(self::definition())
            ->map(function (array $group) use ($user) {
                $group['items'] = collect($group['items'])
                    ->filter(fn (array $item) => Route::has($item['route']))
                    ->filter(fn (array $item) => ! isset($item['module']) || $item['module']->enabled())
                    ->filter(fn (array $item) => self::allowed($user, $item['can'] ?? null))
                    ->map(fn (array $item) => [
                        'label' => $item['label'],
                        'route' => $item['route'],
                        'url' => route($item['route']),
                        'icon' => $item['icon'],
                        'active' => request()->routeIs($item['active'] ?? $item['route']),
                    ])
                    ->values()
                    ->all();

                return $group;
            })
            ->filter(fn (array $group) => $group['items'] !== [])
            ->values()
            ->all();
    }

    /** Flat list (used by bottom tab bars / command palettes). */
    public static function flat(?User $user): array
    {
        return collect(self::for($user))->flatMap(fn ($group) => $group['items'])->all();
    }

    /** @param  Permission|list<Permission>|null  $can  any of */
    protected static function allowed(User $user, Permission|array|null $can): bool
    {
        if ($can === null) {
            return true;
        }

        foreach (is_array($can) ? $can : [$can] as $permission) {
            if ($user->can($permission->value)) {
                return true;
            }
        }

        return false;
    }

    protected static function definition(): array
    {
        return [
            [
                'key' => 'main',
                'label' => __('Main'),
                'items' => [
                    ['label' => __('Dashboard'), 'route' => 'dashboard', 'icon' => 'home'],
                    ['label' => __('Waiting room'), 'route' => 'queue', 'icon' => 'queue', 'can' => [Permission::QueueManage, Permission::VisitsView]],
                    ['label' => __('Appointments'), 'route' => 'appointments.index', 'active' => 'appointments.*', 'icon' => 'calendar', 'can' => Permission::AppointmentsView],
                    ['label' => __('Patients'), 'route' => 'patients.index', 'active' => 'patients.*', 'icon' => 'patients', 'can' => Permission::PatientsView],
                    ['label' => __('Reminders'), 'route' => 'reminders', 'icon' => 'whatsapp', 'can' => Permission::NotificationsSend],
                ],
            ],
            [
                'key' => 'clinical',
                'label' => __('Clinical'),
                'items' => [
                    ['label' => __('Visits'), 'route' => 'visits.index', 'active' => 'visits.*', 'icon' => 'stethoscope', 'can' => Permission::VisitsView],
                    ['label' => __('Lab & imaging'), 'route' => 'lab.index', 'active' => 'lab.*', 'icon' => 'lab', 'can' => Permission::LabView, 'module' => Module::Lab],
                    ['label' => __('Medicines'), 'route' => 'medicines.index', 'active' => 'medicines.*', 'icon' => 'pill', 'can' => [Permission::MedicinesManage, Permission::PrescriptionTemplatesManage]],
                ],
            ],
            [
                'key' => 'finance',
                'label' => __('Finance'),
                'items' => [
                    ['label' => __('Invoices'), 'route' => 'invoices.index', 'active' => 'invoices.*', 'icon' => 'invoice', 'can' => Permission::InvoicesView],
                    ['label' => __('Expenses'), 'route' => 'expenses.index', 'active' => 'expenses.*', 'icon' => 'wallet', 'can' => Permission::ExpensesView, 'module' => Module::Expenses],
                    ['label' => __('Inventory'), 'route' => 'inventory.index', 'active' => 'inventory.*', 'icon' => 'box', 'can' => Permission::InventoryView, 'module' => Module::Inventory],
                    ['label' => __('Reports'), 'route' => 'reports.index', 'active' => 'reports.*', 'icon' => 'chart', 'can' => [Permission::ReportsViewAll, Permission::ReportsViewFinancial, Permission::ReportsViewOwn]],
                ],
            ],
            [
                'key' => 'admin',
                'label' => __('Administration'),
                'items' => [
                    ['label' => __('Settings'), 'route' => 'settings.clinic', 'active' => 'settings.clinic', 'icon' => 'settings', 'can' => Permission::SettingsManage],
                    ['label' => __('Schedule & visit types'), 'route' => 'settings.schedule', 'icon' => 'clock', 'can' => Permission::ScheduleManage],
                    ['label' => __('Services & prices'), 'route' => 'settings.services', 'icon' => 'tag', 'can' => Permission::ServicesManage],
                    ['label' => __('Users'), 'route' => 'settings.users', 'icon' => 'users', 'can' => Permission::UsersManage],
                    ['label' => __('Roles & permissions'), 'route' => 'settings.roles', 'icon' => 'shield', 'can' => Permission::RolesManage],
                    ['label' => __('Custom fields'), 'route' => 'settings.custom-fields', 'icon' => 'fields', 'can' => Permission::CustomFieldsManage],
                    ['label' => __('Activity log'), 'route' => 'activity-log', 'icon' => 'log', 'can' => Permission::ActivityLogView],
                    ['label' => __('Backups'), 'route' => 'settings.backups', 'icon' => 'backup', 'can' => Permission::BackupsManage],
                ],
            ],
        ];
    }
}
