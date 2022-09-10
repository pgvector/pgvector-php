<?php

use PHPUnit\Framework\TestCase;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    public $timestamps = false;
    protected $fillable = ['factors'];
}

final class LaravelTest extends TestCase
{
    public function testWorks()
    {
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'pgsql',
            'database' => 'pgvector_php_test'
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Pgvector\Laravel\Schema::register();

        Capsule::schema()->dropIfExists('items');
        Capsule::schema()->create('items', function ($table) {
            $table->increments('id');
            $table->vector('factors', 3);
        });

        foreach (['[1,1,1]', '[2,2,2]', '[1,1,2]'] as $v) {
            Item::create(['factors' => $v]);
        }

        $neighbors = Item::orderByRaw('factors <-> ?', ['[1,1,1]'])->take(5)->get();
        $ids = array_map(fn ($v) => $v['id'], $neighbors->toArray());
        $factors = array_map(fn ($v) => $v['factors'], $neighbors->toArray());
        $this->assertEquals([1, 3, 2], $ids);
        $this->assertEquals(['[1,1,1]', '[1,1,2]', '[2,2,2]'], $factors);

        $distances = Item::selectRaw('factors <-> ? AS distance', ['[1,1,1]'])->pluck('distance');
        $this->assertEqualsWithDelta([0, sqrt(3), 1], $distances->toArray(), 0.00001);
    }
}
