<?php

namespace App\Enums;

/*
 * What a staff member can do to a micro-project, and which legacy roles may do
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
 *  - View: every role. Legacy gave Publisher and English Checker only a Home
 *    page, so they could not open the project they were asked to review; they
 *    can here (a deliberate change, recorded in docs/upgrade-plan.md).
 */
enum AdminCapability: string
{
    case ViewProjects = 'view-projects';
    case CreateProjects = 'create-projects';
    case EditProjects = 'edit-projects';
    case DeleteProjects = 'delete-projects';
    case PublishProjects = 'publish-projects';
    case UnpublishProjects = 'unpublish-projects';
    case HideProjects = 'hide-projects';
    case CheckEnglish = 'check-english';
    case ManagePhotos = 'manage-photos';

    /** @return list<string> role names, as stored in the `roles` table */
    public function roles(): array
    {
        return match ($this) {
            self::ViewProjects => ['Admin', 'Publisher', 'English Editor', 'English Checker', 'Photos Editor', 'Viewer'],
            self::CreateProjects, self::EditProjects => ['Admin', 'English Editor'],
            self::DeleteProjects, self::UnpublishProjects => ['Admin'],
            self::PublishProjects, self::HideProjects => ['Admin', 'Publisher'],
            self::CheckEnglish => ['Admin', 'English Checker'],
            self::ManagePhotos => ['Admin', 'Photos Editor', 'Publisher'],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ViewProjects => 'View projects',
            self::CreateProjects => 'Create projects',
            self::EditProjects => 'Edit project text',
            self::DeleteProjects => 'Delete projects',
            self::PublishProjects => 'Publish projects',
            self::UnpublishProjects => 'Take projects offline',
            self::HideProjects => 'Hide projects',
            self::CheckEnglish => 'Mark English as checked',
            self::ManagePhotos => 'Manage photos',
        };
    }
}
