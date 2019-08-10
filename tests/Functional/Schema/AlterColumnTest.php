<?php

declare(strict_types=1);

namespace Umbrellio\Postgres\Tests\Functional\Schema;

use Closure;
use Generator;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Umbrellio\Postgres\Schema\Blueprint;
use Umbrellio\Postgres\Tests\Functional\Helpers\ColumnAssertions;
use Umbrellio\Postgres\Tests\Functional\Helpers\TableAssertions;
use Umbrellio\Postgres\Tests\FunctionalTestCase;

class AlterColumnTest extends FunctionalTestCase
{
    use DatabaseTransactions, ColumnAssertions, InteractsWithDatabase, TableAssertions, InteractsWithDatabase;

    /**
     * @test
     * @dataProvider provideChanges
     */
    public function checkTruncateVarying(string $name, Closure $callback, string $expected): void
    {
        Schema::create('test_table', function (Blueprint $table) {
            $table->increments('id');
            $table->char('name', 1)->unique();
        });

        DB::table('test_table')->insert(compact('name'));

        $this->assertDatabaseHas('test_table', compact('name'));
        $this->assertDatabaseMissing('test_table', ['name' => $expected]);

        Schema::create('test_table', function (Blueprint $table) use ($callback) {
            $callback($table);
        });

        $this->assertDatabaseMissing('test_table', compact('name'));
        $this->assertDatabaseHas('test_table', ['name' => $expected]);
    }

    public function provideChanges(): Generator
    {
        yield ['M', function (Blueprint $table) {
            $table->string('name', 255)->change();
        }, 'M         '];
        yield [
            'M',
            function (Blueprint $table) {
                $table->string('name', 255)
                    ->using('(name)::character varying')
                    ->change();
            },
            'M',
        ];
    }
}
