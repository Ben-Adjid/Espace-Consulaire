<?php

namespace Tests\Feature;

use App\Models\DataAccessLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Journal d'accès : une ligne par consultation, lecture seule ; la revue des droits le lit. */
class DataAccessLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_is_recorded_and_the_log_is_read_only(): void
    {
        DataAccessLog::record(null, 'RequestDocument', '12', 'download', null, '127.0.0.1');
        $this->assertSame(1, DataAccessLog::count());

        $log = DataAccessLog::first();
        $this->expectException(\LogicException::class);
        $log->update(['action' => 'autre']);
    }

    public function test_the_rights_review_runs(): void
    {
        $this->artisan('consulat:review-access-rights')->assertSuccessful();
    }
}
