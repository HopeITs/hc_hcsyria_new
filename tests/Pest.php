<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/*
 * Feature tests read the real hcsyria_db: page parity depends on real content,
 * and the schema is shared with the live legacy site, so a copy would drift.
 * Every test runs inside a transaction that is rolled back, so nothing a test
 * does persists. RefreshDatabase must never be used — the migration guard in
 * AppServiceProvider refuses it on hcsyria_db in any case.
 */
pest()->extend(TestCase::class)
    ->use(DatabaseTransactions::class)
    ->in('Feature');
