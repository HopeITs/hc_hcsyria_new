<?php

/*
 * Every word the staff dashboard says that is not drawn by a page itself: the
 * alert messages (toasts), the reasons an action is refused, and the labels the
 * shared components share. One place, so a message is worded once.
 *
 * Alerts are sent with App\Livewire\Concerns\SendsAlerts::alert('key', [...]),
 * which looks the key up under 'alerts'.
 */
return [
    'alerts' => [
        // Moving a project between states (ChangeContentState).
        'state' => [
            'publish' => ':Noun #:number is online.',
            'unpublish' => ':Noun #:number is offline and waits to be published.',
            'hide' => ':Noun #:number is hidden.',
            'unhide' => ':Noun #:number is back in the pending list.',
            'check-english' => 'English of :noun #:number is marked as checked.',
        ],
        'state_many' => [
            'done' => ':count :nouns updated.',
            'partly' => ':done of :count :nouns updated; :skipped could not be (not allowed, or not ready).',
            'none' => 'None of the :count :nouns could be updated.',
        ],
        'saved' => ':Noun #:number saved.',
        'created' => ':Noun #:number created. It waits to be published.',
        'deleted' => ':Noun #:number deleted.',
        // Photos (ManageProjectPhotos).
        'photos' => [
            'added' => ':count photo added to :noun #:number.|:count photos added to :noun #:number.',
            'added_offline' => ':count photo added to :noun #:number. It went back to the pending list because its photos changed.|:count photos added to :noun #:number. It went back to the pending list because its photos changed.',
            'removed' => 'Photo removed from :noun #:number.',
            'removed_offline' => 'Photo removed from :noun #:number. It went back to the pending list because its photos changed.',
            'rejected' => 'Not saved: :reason',
            'unavailable' => 'Photos cannot be processed on this server: the GD image extension is not enabled.',
        ],
        'logo' => [
            'saved' => 'Logo saved for initiative #:number.',
            'removed' => 'Logo removed from initiative #:number.',
        ],
        'pairs' => [
            'added' => 'Before and after pair added to initiative #:number.',
            'removed' => 'Before and after pair removed from initiative #:number.',
        ],
        'testimonies' => [
            'created' => 'Testimony added to initiative #:number.',
            'saved' => 'Testimony saved.',
            'deleted' => 'Testimony removed from initiative #:number.',
        ],
        'news_pictures' => [
            'saved' => ':label saved for news #:number.',
            'removed' => ':label removed from news #:number.',
            'priority' => 'News #:number is now :priority.',
        ],
        'tags' => [
            'created' => 'Tag “:name” created.',
            'renamed' => 'Tag renamed to “:name”.',
            'deleted' => 'Tag “:name” deleted and taken off its news items.',
        ],
        'editions' => [
            'created' => 'Edition “:name” created. It is offline until you put it online.',
            'online' => 'Edition “:name” is online.',
            'offline' => 'Edition “:name” is offline.',
            'deleted' => 'Edition “:name” deleted.',
        ],
        'subscribers' => [
            'deleted' => 'Subscriber removed from the list.',
            'gone' => 'That subscriber is no longer on the list.',
        ],
        'users' => [
            'created' => 'User “:name” created.',
            'saved' => 'User “:name” saved.',
            'activated' => 'User “:name” is active again.',
            'deactivated' => 'User “:name” is deactivated and can no longer sign in.',
            'deleted' => 'User “:name” deleted.',
        ],
        'invalid' => 'Some fields need attention. They are marked below.',
        'forbidden' => 'Your role cannot do that.',
        'gone' => 'That :noun no longer exists.',
        'undone' => 'Change undone.',
    ],

    // Why an action is refused (thrown by the actions, shown as an error alert).
    'refusals' => [
        'not_ready' => ':Noun #:number is not ready: :missing.',
        'has_successes' => ':Noun #:number has success stories attached. Remove or reassign them first.',
        'path_too_long' => 'The picture could not be saved: its folder name is too long for the database. Shorten the category name or ask for help.',
        'testimony_gone' => 'That testimony no longer exists.',
        'tag_exists' => 'There is already a tag called “:name”.',
        'edition_exists' => 'There is already an edition called “:name”.',
        'edition_in_use' => 'Edition “:name” belongs to :count news items. Take it off them first.',
        'duplicate' => 'There is already a project #:number in :city.',
        'own_account' => 'You cannot do that to your own account.',
        'last_admin' => 'That would leave no active Admin. Make someone else an Admin first.',
        'user_has_activity' => ':name has made changes that the activity log keeps, so the account cannot be deleted. Deactivate it instead.',
    ],

    // What a project still needs before it can go online (ProjectReadiness).
    'needs' => [
        'english' => 'the English text is not checked',
        'cover' => 'it has no cover',
        'photos' => 'it has no photos',
        'donor' => 'it has no donor',
        'funded' => 'no fund date',
        'location' => 'no map location',
    ],

    // The checklist on a project's page: each need, said as what is true once it is met.
    'checks' => [
        'english' => 'English text checked',
        'photos' => 'At least one photo',
        'donor' => 'A donor chosen',
        'funded' => 'Fund date set',
        'location' => 'Map location set',
    ],

    'status' => [
        'online' => 'Online',
        'pending' => 'Pending publish',
        'hidden' => 'Hidden',
        'cancelled' => 'Cancelled',
    ],

    'transitions' => [
        'publish' => 'Publish',
        'unpublish' => 'Take offline',
        'hide' => 'Hide',
        'unhide' => 'Unhide',
        'check-english' => 'Mark English checked',
    ],

    'confirm' => [
        'delete_title' => 'Delete :noun #:number?',
        'delete_body' => 'The :noun and its files are removed for good. This cannot be undone.',
        'delete_action' => 'Delete',
        'cancel' => 'Cancel',
        'photo_title' => 'Remove this photo?',
        'photo_body' => 'Both sizes of the photo are deleted from the server. This cannot be undone.',
        'photo_action' => 'Remove',
    ],

    'tabs' => [
        'all' => 'All',
        'pending' => 'Pending publish',
        'online' => 'Online',
        'hidden' => 'Hidden',
        'cancelled' => 'Cancelled',
        'english' => 'English not checked',
        'nophoto' => 'No photos',
        'projects' => 'Micro projects',
        'initiatives' => 'Initiatives',
        'successes' => 'Successes',
        'newsletter' => 'Newsletter',
        'magazine' => 'Roots of Hope',
        'team' => 'Our Team',
        'covers' => 'Cover photos',
        'content' => 'Page content',
        'users' => 'Users',
        'students' => 'Students',
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
];
