<?php

namespace App\Enums;

use App\Actions\Admin\DeleteInitiative;
use App\Actions\Admin\DeleteMagazine;
use App\Actions\Admin\DeleteNews;
use App\Actions\Admin\DeleteSuccess;
use App\Actions\DeleteProject;
use App\Models\Initiative;
use App\Models\News;
use App\Models\Project;
use App\Models\RootsOfHope;
use App\Models\Success;
use App\Support\Admin\PhotoFolders;
use App\Support\Admin\ProjectReadiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/*
 * The kinds of content the staff dashboard manages the same way: what each is
 * called, where its pages are, how its state is stored (legacy used different
 * columns for each), what its activity-log rows say, and what it still needs
 * before it can go online. The shared pieces (the state changes, the actions
 * menu, the alerts) ask the kind instead of knowing about any one table.
 */
enum ContentKind: string
{
    case Project = 'project';
    case Initiative = 'initiative';
    case Success = 'success';
    case News = 'news';
    case Roots = 'roots';

    public static function of(Model $item): self
    {
        return match (true) {
            $item instanceof Initiative => self::Initiative,
            $item instanceof Success => self::Success,
            $item instanceof News => self::News,
            $item instanceof RootsOfHope => self::Roots,
            default => self::Project,
        };
    }

    /** @return class-string<Model> */
    public function modelClass(): string
    {
        return match ($this) {
            self::Project => Project::class,
            self::Initiative => Initiative::class,
            self::Success => Success::class,
            self::News => News::class,
            self::Roots => RootsOfHope::class,
        };
    }

    /** The word used in sentences: "project #3071 is online". */
    public function noun(): string
    {
        return $this === self::Roots ? 'magazine' : $this->value;
    }

    public function nouns(): string
    {
        return Str::plural($this->noun());
    }

    /** The route of one of its pages: index, show, create, edit. */
    public function route(string $page): string
    {
        // The address of the section: the plural of what it is called, except where the section is named after the thing (news, roots).
        $section = match ($this) {
            self::News, self::Roots => $this->value,
            default => Str::plural($this->value),
        };

        return 'admin.'.$section.'.'.$page;
    }

    /** Legacy's category in the activity log (`logs.category`). */
    public function logCategory(): int
    {
        return match ($this) {
            self::Project => 0,
            self::Initiative => 1,
            self::Success => 2,
            self::News => 3,
            self::Roots => 11,
        };
    }

    /**
     * The table holding its gallery photos, and the columns that link and name them.
     *
     * @return array{table: string, owner: string, path: string}
     */
    public function photoTable(): array
    {
        return match ($this) {
            self::Project => ['table' => 'images', 'owner' => 'project_id', 'path' => 'MPI_Path'],
            self::Initiative => ['table' => 'initiatives_images', 'owner' => 'initiative_id', 'path' => 'path'],
            self::News => ['table' => 'news_images', 'owner' => 'news_id', 'path' => 'path'],
            self::Success, self::Roots => throw new LogicException('This kind has one picture, not a gallery.'),
        };
    }

    /**
     * Which rows of the photo table are the gallery: a news item keeps its thumbnail and its Important and
     * Pinned pictures there too (sizetype 0, 1, 2); the gallery is sizetype 3.
     *
     * @return array<string, int>
     */
    public function photoConstraints(): array
    {
        return $this === self::News ? ['sizetype' => 3] : [];
    }

    /**
     * Columns every new photo row needs besides the owner, the path and the order.
     *
     * @return array<string, int>
     */
    public function photoExtras(Model $item): array
    {
        return match ($this) {
            self::Project => ['MicroProject_ID' => $this->number($item)],
            self::News => $this->photoConstraints(),
            default => [],
        };
    }

    /** The folder under MEDIA_ROOT its photos go in. */
    public function photoDirectory(Model $item): string
    {
        return match (true) {
            $item instanceof Project => PhotoFolders::project((string) DB::table('places')->where('id', $item->MPE_City)->value('name'), (int) $item->MicroProject_ID),
            $item instanceof Initiative => PhotoFolders::initiative((string) DB::table('what_categories')->where('id', $item->what_category_id)->value('name'), (int) $item->getKey()),
            $item instanceof News => PhotoFolders::news((int) $item->getKey()),
            default => '/images/other',
        };
    }

    /**
     * The sizes of a gallery photo: the small one is cut to exactly its box, the big one scaled down to fit its box.
     *
     * @return array{small: array{0: int<1, max>, 1: int<1, max>}, big: array{0: int<1, max>, 1: int<1, max>}}
     */
    public function photoSizes(): array
    {
        return match ($this) {
            self::Project => ['small' => [630, 420], 'big' => [1620, 1080]],
            self::Initiative => ['small' => [720, 480], 'big' => [2000, 1333]],
            self::News => ['small' => [920, 614], 'big' => [2080, 1387]],
            self::Success, self::Roots => throw new LogicException('This kind has one picture, not a gallery.'),
        };
    }

    /** Legacy sent an online project back to the pending list when its photos changed; initiatives were left online. */
    public function photosChangeTakesOffline(): bool
    {
        return $this === self::Project;
    }

    /** The action that deletes one and its files; its handle() takes the id. @return class-string */
    public function deleteAction(): string
    {
        return match ($this) {
            self::Project => DeleteProject::class,
            self::Initiative => DeleteInitiative::class,
            self::Success => DeleteSuccess::class,
            self::News => DeleteNews::class,
            self::Roots => DeleteMagazine::class,
        };
    }

    /** Legacy's log text for a deletion. */
    public function deleteOperation(): string
    {
        return match ($this) {
            self::Project => 'Project Deleted',
            self::Initiative => 'Initiative deleted',
            self::Success => 'Success deleted',
            self::News => 'News deleted',
            self::Roots => 'Magazine deleted',
        };
    }

    /** What people call it by: a project's own number, an initiative's id. */
    public function number(Model $item): int
    {
        return (int) ($item instanceof Project ? $item->MicroProject_ID : $item->getKey());
    }

    /** Change the state columns for a move (the caller saves). */
    public function apply(Model $item, ContentTransition $transition, Carbon $now): void
    {
        $item->forceFill(match ($this) {
            self::Project => match ($transition) {
                ContentTransition::Publish => ['MPE_Published' => '1', 'hidden' => false, 'MPE_PublishDate' => $now, 'MPE_Done' => '1'],
                ContentTransition::Unpublish => ['MPE_Published' => '0', 'MPE_PublishDate' => $now],
                ContentTransition::Hide => ['MPE_Published' => '0', 'hidden' => true],
                ContentTransition::Unhide => ['hidden' => false],
                ContentTransition::CheckEnglish => ['english_check' => 1],
            },
            self::Initiative => match ($transition) {
                ContentTransition::Publish => ['online' => 1, 'hidden' => false],
                ContentTransition::Unpublish => ['online' => 0],
                ContentTransition::Hide => ['online' => 0, 'hidden' => true],
                ContentTransition::Unhide => ['hidden' => false],
                ContentTransition::CheckEnglish => ['english_check' => 1],
            },
            self::Success => match ($transition) {
                ContentTransition::Publish => ['online' => '1'],
                ContentTransition::Unpublish => ['online' => '0'],
                ContentTransition::CheckEnglish => ['english_check' => 1],
                ContentTransition::Hide, ContentTransition::Unhide => [],
            },
            self::News => match ($transition) {
                ContentTransition::Publish => ['online' => 1, 'hidden' => false],
                ContentTransition::Unpublish => ['online' => 0],
                ContentTransition::Hide => ['online' => 0, 'hidden' => true],
                ContentTransition::Unhide => ['hidden' => false],
                ContentTransition::CheckEnglish => ['english_check' => 1],
            },
            self::Roots => match ($transition) {
                ContentTransition::Publish => ['online' => 1],
                ContentTransition::Unpublish => ['online' => 0],
                ContentTransition::Hide, ContentTransition::Unhide, ContentTransition::CheckEnglish => [],
            },
        });
    }

    /** The text legacy wrote to the activity log for the same move. */
    public function operation(ContentTransition $transition): string
    {
        return match ($this) {
            self::Project => match ($transition) {
                ContentTransition::Publish => 'Made Online',
                ContentTransition::Unpublish => 'Made Offline',
                ContentTransition::Hide => 'Made Hidden',
                ContentTransition::Unhide => 'Made Pending to publish',
                ContentTransition::CheckEnglish => 'English Checked',
            },
            self::Initiative => match ($transition) {
                ContentTransition::Publish => 'Initiative Status: Online',
                ContentTransition::Unpublish => 'Initiative Status: Offline',
                ContentTransition::Hide => 'Initiative Status: Hidden',
                ContentTransition::Unhide => 'Initiative Status: Pending to publish',
                ContentTransition::CheckEnglish => 'Initiative English Checked',
            },
            self::Success => match ($transition) {
                ContentTransition::Publish => 'Made online',
                ContentTransition::Unpublish => 'Made offline',
                ContentTransition::CheckEnglish => 'English Checked',
                ContentTransition::Hide, ContentTransition::Unhide => '',
            },
            self::News => match ($transition) {
                ContentTransition::Publish => 'News Status: Online',
                ContentTransition::Unpublish => 'News Status: Offline',
                ContentTransition::Hide => 'News Status: Hidden',
                ContentTransition::Unhide => 'News Status: Pending to publish',
                ContentTransition::CheckEnglish => 'English Content Checked',
            },
            self::Roots => match ($transition) {
                ContentTransition::Publish => 'Magazine Status: Online',
                ContentTransition::Unpublish => 'Magazine Status: Offline',
                ContentTransition::Hide, ContentTransition::Unhide, ContentTransition::CheckEnglish => '',
            },
        };
    }

    /**
     * What must be done before it may be published: keys of admin.needs.
     *
     * @return list<string>
     */
    public function blockers(Model $item): array
    {
        if ($item instanceof Project) {
            return ProjectReadiness::blockers((object) [
                'english_check' => $item->english_check,
                'photos' => $item->images()->count(),
                'donor_id' => $item->MPE_Donor,
            ]);
        }

        if ($item instanceof RootsOfHope) {
            return $item->image_path === '0' || $item->image_path === '' ? ['cover'] : [];
        }

        return ($item instanceof Initiative || $item instanceof Success || $item instanceof News) && (int) $item->english_check !== 1 ? ['english'] : [];
    }

    /**
     * The same, for a list row (columns english_check, photos, donor_id).
     *
     * @param  object{english_check?: int|string, photos?: int|string, donor_id?: int|string|null, image_path?: string}  $row
     * @return list<string>
     */
    public function rowBlockers(object $row): array
    {
        return match ($this) {
            self::Project => ProjectReadiness::blockers($row),
            self::Initiative, self::Success, self::News => (int) ($row->english_check ?? 0) === 1 ? [] : ['english'],
            self::Roots => ($row->image_path ?? '0') === '0' ? ['cover'] : [],
        };
    }

    /** Does this kind have the move? A success story has no hidden state; a magazine is only online or offline. */
    public function supports(ContentTransition $transition): bool
    {
        return match ($this) {
            self::Success => ! in_array($transition, [ContentTransition::Hide, ContentTransition::Unhide], true),
            self::Roots => in_array($transition, [ContentTransition::Publish, ContentTransition::Unpublish], true),
            default => true,
        };
    }
}
