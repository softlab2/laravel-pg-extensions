<?php

declare(strict_types=1);

namespace Umbrellio\Postgres\Tests\Functional;

use Generator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Umbrellio\Postgres\Schema\Blueprint;

class ExcludeUsingTest extends FunctionalTestCase
{
    /**
     * @test
     * @dataProvider provideIndexes
     */
    public function createPartialUniqueWithNull($expected, $callback): void
    {
        Schema::create('test_table', function (Blueprint $table) use ($callback) {
            $table->increments('id');
            $table->string('name');
            $table->string('code');
            $table->integer('phone');
            $table->boolean('enabled');
            $table->integer('icq');
            $table->softDeletes();
            $callback($table);
        });

        $this->assertTrue(Schema::hasTable('test_table'));

        $indexes = $this->getIndexByName('test_table_name_unique');

        $this->assertTrue(isset($indexes->indexdef));
        $this->assertSame($this->getDummyIndex() . $expected, $indexes->indexdef);
    }

    public function provideIndexes(): Generator
    {
        yield ['', function (Blueprint $table) {
            $table->addColumn('daterange', 'period')->exclude();
        }];
        yield [
            ' WHERE (deleted_at IS NULL)',
            function (Blueprint $table) {
                $table->uniquePartial('name')->whereNull('deleted_at');
            },
        ];
    }

    protected function getIndexByName($name)
    {
        return collect(DB::select("SELECT indexdef FROM pg_indexes WHERE  indexname = '{$name}'"))->first();
    }
}
