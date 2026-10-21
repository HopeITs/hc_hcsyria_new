<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Cutover step 3 of 3: indexes, under the new table names.
 *
 *  - A pair that must not repeat becomes a unique key (user roles, news tags,
 *    initiative donors ...). Rows that already repeat are removed first, the
 *    lowest id kept.
 *  - A content table holds one row per owner, so its owner column is unique.
 *  - The listing and lookup columns the site filters or sorts by get a key.
 *
 * Every step checks first, so a half-applied run can be run again.
 */
return new class extends Migration
{
    /** Pivot or child tables: table => the columns that must be unique together. */
    private const UNIQUE_PAIRS = [
        'role_user' => ['user_id', 'role_id'],
        'place_user' => ['user_id', 'place_id'],
        'permission_user' => ['user_id', 'permission_id'],
        'study_zone_center_user' => ['user_id', 'studyzone_center_id'],
        'initiative_donors' => ['initiative_id', 'donor_id'],
        'news_tags' => ['news_id', 'tag_id'],
    ];

    /** One row per owner: table => owner column. */
    private const ONE_PER_OWNER = [
        'initiative_contents' => 'initiative_id',
        'news_contents' => 'news_id',
        'project_contents' => 'project_id',
        'testimony_contents' => 'testimony_id',
    ];

    public function up(): void
    {
        foreach (self::UNIQUE_PAIRS as $table => [$a, $b]) {
            $name = "{$table}_{$a}_{$b}_unique";

            if (Schema::hasIndex($table, $name)) {
                continue;
            }

            DB::statement("DELETE t1 FROM `{$table}` t1 JOIN `{$table}` t2 ON t1.`{$a}` = t2.`{$a}` AND t1.`{$b}` = t2.`{$b}` AND t1.id > t2.id");

            Schema::table($table, fn (Blueprint $t) => $t->unique([$a, $b], $name));
        }

        // news_id leads the new pair, so its own key is redundant.
        $this->dropIndex('news_tags', 'news_id');

        foreach (self::ONE_PER_OWNER as $table => $column) {
            $this->index($table, [$column], "{$table}_{$column}_unique", unique: true);
        }

        $this->index('tags', ['name'], 'tags_name_unique', unique: true);

        // Lookups the site makes on a column with no key.
        $this->index('projects', ['MicroProject_ID'], 'projects_micro_project_id_index');
        $this->index('team_members', ['email'], 'team_members_email_index');
        $this->index('successes', ['online', 'created_at'], 'successes_online_created_at_index');
        $this->index('project_donors', ['what_category_id', 'isActive', 'rank'], 'project_donors_category_active_rank_index');

        // The activity log has half a million rows: the bell filters on seen and
        // age, the tabs on category, newest first.
        $this->index('logs', ['seen', 'created_at'], 'logs_seen_created_at_index');
        $this->index('logs', ['category', 'id'], 'logs_category_id_index');

        // Same leading columns as the keys they replace, plus the sort column.
        $this->index('team_members', ['belong_to', 'inactive', 'rank'], 'team_members_group_inactive_rank_index');
        $this->dropIndex('team_members', 'belong_to');
        $this->index('news', ['online', 'hidden', 'date'], 'news_online_hidden_date_index');
        $this->dropIndex('news', 'news_state_hidden_index');
        $this->index('projects', ['MPE_Published', 'hidden', 'MPE_PublishDate'], 'projects_published_hidden_date_index');
        $this->dropIndex('projects', 'projects_state_hidden_index');
    }

    public function down(): void
    {
        $this->index('projects', ['MPE_Published', 'hidden'], 'projects_state_hidden_index');
        $this->dropIndex('projects', 'projects_published_hidden_date_index');
        $this->index('news', ['online', 'hidden'], 'news_state_hidden_index');
        $this->dropIndex('news', 'news_online_hidden_date_index');
        $this->index('team_members', ['belong_to'], 'belong_to');
        $this->dropIndex('team_members', 'team_members_group_inactive_rank_index');

        foreach ([
            'logs' => ['logs_category_id_index', 'logs_seen_created_at_index'],
            'project_donors' => ['project_donors_category_active_rank_index'],
            'successes' => ['successes_online_created_at_index'],
            'team_members' => ['team_members_email_index'],
            'projects' => ['projects_micro_project_id_index'],
            'tags' => ['tags_name_unique'],
        ] as $table => $names) {
            foreach ($names as $name) {
                $this->dropIndex($table, $name);
            }
        }

        // A foreign key needs a plain key on its column before the unique one goes.
        foreach (self::ONE_PER_OWNER as $table => $column) {
            $this->index($table, [$column], "{$table}_{$column}_index");
            $this->dropIndex($table, "{$table}_{$column}_unique");
        }

        $this->index('news_tags', ['news_id'], 'news_id');

        foreach (self::UNIQUE_PAIRS as $table => [$a, $b]) {
            $this->index($table, [$a], "{$table}_{$a}_index");
            $this->dropIndex($table, "{$table}_{$a}_{$b}_unique");
        }
    }

    /** @param list<string> $columns */
    private function index(string $table, array $columns, string $name, bool $unique = false): void
    {
        if (Schema::hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, fn (Blueprint $t) => $unique ? $t->unique($columns, $name) : $t->index($columns, $name));
    }

    private function dropIndex(string $table, string $name): void
    {
        if (Schema::hasIndex($table, $name)) {
            Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
        }
    }
};
