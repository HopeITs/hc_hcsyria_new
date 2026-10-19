<?php

namespace App\Enums;

/*
 * What a staff member can do to the site's content (micro-projects, initiatives), and which legacy roles may do
 * it. Taken from the checks in the legacy admin views (projects-index,
 * project-view, home) and made one table, so the sidebar, the buttons and the
 * server all ask the same question.
 *
 *  - Edit and Create: Admin, English Editor (legacy: the Edit and "New project"
 *    buttons).
 *  - Delete and Unpublish ("Offline"): Admin only.
 *  - Publish: Admin or Publisher, and only once the English text is checked.
 *  - English check: Admin or English Checker (legacy home page).
 *  - Photos: Admin or Photos Editor; Publisher too, as on the legacy home page.
 *  - Subscribers (their addresses are personal data): Admin and Publisher, who send the
 *    newsletter, may see the list; only the Admin removes an address.
 *  - Users and the activity log: Admin only (legacy's `admin` middleware).
 *  - View: every role. Legacy gave Publisher and English Checker only a Home
 *    page, so they could not open the project they were asked to review; they
 *    can here (a deliberate change, recorded in docs/upgrade-plan.md).
 */
enum AdminCapability: string
{
    case View = 'view';
    case Create = 'create';
    case Edit = 'edit';
    case Delete = 'delete';
    case Publish = 'publish';
    case Unpublish = 'unpublish';
    case Hide = 'hide';
    case CheckEnglish = 'check-english';
    case ManagePhotos = 'manage-photos';
    case ViewSubscribers = 'view-subscribers';
    case DeleteSubscribers = 'delete-subscribers';
    case ManageUsers = 'manage-users';
    case ViewLog = 'view-log';

    /** @return list<string> role names, as stored in the `roles` table */
    public function roles(): array
    {
        return match ($this) {
            self::View => ['Admin', 'Publisher', 'English Editor', 'English Checker', 'Photos Editor', 'Viewer'],
            self::Create, self::Edit => ['Admin', 'English Editor'],
            self::Delete, self::Unpublish => ['Admin'],
            self::Publish, self::Hide => ['Admin', 'Publisher'],
            self::CheckEnglish => ['Admin', 'English Checker'],
            self::ManagePhotos => ['Admin', 'Photos Editor', 'Publisher'],
            self::ViewSubscribers => ['Admin', 'Publisher'],
            self::DeleteSubscribers, self::ManageUsers, self::ViewLog => ['Admin'],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::View => 'View content',
            self::Create => 'Create content',
            self::Edit => 'Edit text',
            self::Delete => 'Delete content',
            self::Publish => 'Publish',
            self::Unpublish => 'Take offline',
            self::Hide => 'Hide',
            self::CheckEnglish => 'Mark English as checked',
            self::ManagePhotos => 'Manage photos',
            self::ViewSubscribers => 'See newsletter subscribers',
            self::DeleteSubscribers => 'Remove subscribers',
            self::ManageUsers => 'Manage users and roles',
            self::ViewLog => 'See the activity log',
        };
    }
}
