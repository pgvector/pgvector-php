<?php

use PHPUnit\Framework\TestCase;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

$capsule = new Capsule();
$capsule->addConnection([
    'driver' => 'pgsql',
    'database' => 'pgvector_php_test',
    'prefix' => 'laravel_'
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

Pgvector\Laravel\Schema::register();

Capsule::statement('CREATE EXTENSION IF NOT EXISTS vector');
Capsule::schema()->dropIfExists('items');
Capsule::schema()->create('items', function ($table) {
    $table->increments('id');
    $table->vector('embedding', 3)->nullable();
});

class Item extends Model
{
    use HasNeighbors;

    public $timestamps = false;
    protected $fillable = ['id', 'embedding'];
    protected $casts = ['embedding' => Vector::class];
}

final class LaravelTest extends TestCase
{
    public function setUp(): void
    {
        Item::truncate();
    }

    public function testL2Distance()
    {
        $this->createItems();
        $neighbors = Item::orderByRaw('embedding <-> ?', [new Vector([1, 1, 1])])->take(5)->get();
        $this->assertEquals([1, 3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEquals([[1, 1, 1], [1, 1, 2], [2, 2, 2]], array_map(fn ($v) => $v->toArray(), $neighbors->pluck('embedding')->toArray()));
    }

    public function testMaxInnerProduct()
    {
        $this->createItems();
        $neighbors = Item::orderByRaw('embedding <#> ?', [new Vector([1, 1, 1])])->take(5)->get();
        $this->assertEquals([2, 3, 1], $neighbors->pluck('id')->toArray());
    }

    public function testCosineDistance()
    {
        $this->createItems();
        $neighbors = Item::orderByRaw('embedding <=> ?', [new Vector([1, 1, 1])])->take(5)->get();
        $this->assertEquals([1, 2, 3], $neighbors->pluck('id')->toArray());
    }

    public function testDistances()
    {
        $this->createItems();
        $distances = Item::selectRaw('embedding <-> ? AS distance', [new Vector([1, 1, 1])])->pluck('distance');
        $this->assertEqualsWithDelta([0, sqrt(3), 1], $distances->toArray(), 0.00001);
    }

    public function testScopeL2Distance()
    {
        $this->createItems();
        $neighbors = Item::query()->nearestNeighbors('embedding', [1, 1, 1], Distance::L2)->take(5)->get();
        $this->assertEquals([1, 3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([0, 1, sqrt(3)], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testScopeMaxInnerProduct()
    {
        $this->createItems();
        $neighbors = Item::query()->nearestNeighbors('embedding', [1, 1, 1], Distance::InnerProduct)->take(5)->get();
        $this->assertEquals([2, 3, 1], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([6, 4, 3], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testInstance()
    {
        $this->createItems();
        $item = Item::find(1);
        $neighbors = $item->nearestNeighbors('embedding', Distance::L2)->take(5)->get();
        $this->assertEquals([3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([1, sqrt(3)], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testMissingAttribute()
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Missing attribute');

        $this->createItems();
        $item = Item::find(1);
        $item->nearestNeighbors('factors', Distance::L2);
    }

    public function testInvalidDistance()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid distance');

        Item::query()->nearestNeighbors('embedding', [1, 2, 3], 4);
    }

    public function testCast()
    {
        Item::create(['id' => 1, 'embedding' => [1, 2, 3]]);
        $item = Item::find(1);
        $this->assertEquals([1, 2, 3], $item->embedding->toArray());
    }

    public function testCastNull()
    {
        Item::create(['id' => 1]);
        $item = Item::find(1);
        $this->assertNull($item->embedding);
    }

    private function createItems()
    {
        foreach ([[1, 1, 1], [2, 2, 2], [1, 1, 2]] as $i => $v) {
            Item::create(['id' => $i + 1, 'embedding' => $v]);
        }
    }
}
