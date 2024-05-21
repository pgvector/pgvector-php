<?php

use PHPUnit\Framework\TestCase;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;
use Pgvector\Laravel\HalfVector;
use Pgvector\Laravel\SparseVector;

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
    $table->halfvec('half_embedding', 3)->nullable();
    $table->bit('binary_embedding', 3)->nullable();
    $table->sparsevec('sparse_embedding', 3)->nullable();
});

class Item extends Model
{
    use HasNeighbors;

    public $timestamps = false;
    protected $fillable = ['id', 'embedding', 'half_embedding', 'binary_embedding', 'sparse_embedding'];
    protected $casts = ['embedding' => Vector::class, 'half_embedding' => HalfVector::class, 'sparse_embedding' => SparseVector::class];
}

final class LaravelTest extends TestCase
{
    public function setUp(): void
    {
        Item::truncate();
    }

    public function testVectorL2Distance()
    {
        $this->createItems();
        $neighbors = Item::orderByRaw('embedding <-> ?', [new Vector([1, 1, 1])])->take(5)->get();
        $this->assertEquals([1, 3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEquals([[1, 1, 1], [1, 1, 2], [2, 2, 2]], array_map(fn ($v) => $v->toArray(), $neighbors->pluck('embedding')->toArray()));
    }

    public function testVectorMaxInnerProduct()
    {
        $this->createItems();
        $neighbors = Item::orderByRaw('embedding <#> ?', [new Vector([1, 1, 1])])->take(5)->get();
        $this->assertEquals([2, 3, 1], $neighbors->pluck('id')->toArray());
    }

    public function testVectorCosineDistance()
    {
        $this->createItems();
        $neighbors = Item::orderByRaw('embedding <=> ?', [new Vector([1, 1, 1])])->take(5)->get();
        $this->assertEquals([1, 2, 3], $neighbors->pluck('id')->toArray());
    }

    public function testVectorL1Distance()
    {
        $this->createItems();
        $neighbors = Item::orderByRaw('embedding <+> ?', [new Vector([1, 1, 1])])->take(5)->get();
        $this->assertEquals([1, 3, 2], $neighbors->pluck('id')->toArray());
    }

    public function testVectorScopeL2Distance()
    {
        $this->createItems();
        $neighbors = Item::query()->nearestNeighbors('embedding', [1, 1, 1], Distance::L2)->take(5)->get();
        $this->assertEquals([1, 3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([0, 1, sqrt(3)], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testVectorScopeMaxInnerProduct()
    {
        $this->createItems();
        $neighbors = Item::query()->nearestNeighbors('embedding', [1, 1, 1], Distance::InnerProduct)->take(5)->get();
        $this->assertEquals([2, 3, 1], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([6, 4, 3], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testVectorScopeCosineDistance()
    {
        $this->createItems();
        $neighbors = Item::query()->nearestNeighbors('embedding', [1, 1, 1], Distance::Cosine)->take(5)->get();
        $this->assertEquals([1, 2, 3], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([0, 0, 0.05719], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testVectorScopeL1Distance()
    {
        $this->createItems();
        $neighbors = Item::query()->nearestNeighbors('embedding', [1, 1, 1], Distance::L1)->take(5)->get();
        $this->assertEquals([1, 3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([0, 1, 3], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testVectorInstanceL2Distance()
    {
        $this->createItems();
        $item = Item::find(1);
        $neighbors = $item->nearestNeighbors('embedding', Distance::L2)->take(5)->get();
        $this->assertEquals([3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([1, sqrt(3)], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testVectorInstanceMaxInnerProduct()
    {
        $this->createItems();
        $item = Item::find(1);
        $neighbors = $item->nearestNeighbors('embedding', Distance::InnerProduct)->take(5)->get();
        $this->assertEquals([2, 3], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([6, 4], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testVectorInstanceCosineDistance()
    {
        $this->createItems();
        $item = Item::find(1);
        $neighbors = $item->nearestNeighbors('embedding', Distance::Cosine)->take(5)->get();
        $this->assertEquals([2, 3], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([0, 0.05719], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testVectorInstanceL1Distance()
    {
        $this->createItems();
        $item = Item::find(1);
        $neighbors = $item->nearestNeighbors('embedding', Distance::L1)->take(5)->get();
        $this->assertEquals([3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([1, 3], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testHalfvecL2Distance()
    {
        $this->createItems('half_embedding');
        $neighbors = Item::orderByRaw('half_embedding <-> ?', [new HalfVector([1, 1, 1])])->take(5)->get();
        $this->assertEquals([1, 3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEquals([[1, 1, 1], [1, 1, 2], [2, 2, 2]], array_map(fn ($v) => $v->toArray(), $neighbors->pluck('half_embedding')->toArray()));
    }

    public function testHalfvecScopeL2Distance()
    {
        $this->createItems('half_embedding');
        $neighbors = Item::query()->nearestNeighbors('half_embedding', [1, 1, 1], Distance::L2)->take(5)->get();
        $this->assertEquals([1, 3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([0, 1, sqrt(3)], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testHalfvecScopeMaxInnerProduct()
    {
        $this->createItems('half_embedding');
        $neighbors = Item::query()->nearestNeighbors('half_embedding', [1, 1, 1], Distance::InnerProduct)->take(5)->get();
        $this->assertEquals([2, 3, 1], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([6, 4, 3], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testHalfvecInstance()
    {
        $this->createItems('half_embedding');
        $item = Item::find(1);
        $neighbors = $item->nearestNeighbors('half_embedding', Distance::L2)->take(5)->get();
        $this->assertEquals([3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([1, sqrt(3)], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testHalfvecInstanceL1()
    {
        $this->createItems('half_embedding');
        $item = Item::find(1);
        $neighbors = $item->nearestNeighbors('half_embedding', Distance::L1)->take(5)->get();
        $this->assertEquals([3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([1, 3], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testBitHammingDistance()
    {
        Item::create(['id' => 1, 'binary_embedding' => '000']);
        Item::create(['id' => 2, 'binary_embedding' => '101']);
        Item::create(['id' => 3, 'binary_embedding' => '111']);
        $neighbors = Item::query()->nearestNeighbors('binary_embedding', '101', Distance::Hamming)->take(5)->get();
        $this->assertEquals([2, 3, 1], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([0, 1, 2], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testBitJaccardDistance()
    {
        Item::create(['id' => 1, 'binary_embedding' => '000']);
        Item::create(['id' => 2, 'binary_embedding' => '101']);
        Item::create(['id' => 3, 'binary_embedding' => '111']);
        $neighbors = Item::query()->nearestNeighbors('binary_embedding', '101', Distance::Jaccard)->take(5)->get();
        $this->assertEquals([2, 3, 1], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([0, 1/3, 1], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testSparsevecL2Distance()
    {
        $this->createItems('sparse_embedding');
        $neighbors = Item::orderByRaw('sparse_embedding <-> ?', [SparseVector::fromDense([1, 1, 1])])->take(5)->get();
        $this->assertEquals([1, 3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEquals([[1, 1, 1], [1, 1, 2], [2, 2, 2]], array_map(fn ($v) => $v->toArray(), $neighbors->pluck('sparse_embedding')->toArray()));
    }

    public function testSparsevecScopeL2Distance()
    {
        $this->createItems('sparse_embedding');
        $neighbors = Item::query()->nearestNeighbors('sparse_embedding', '{1:1,2:1,3:1}/3', Distance::L2)->take(5)->get();
        $this->assertEquals([1, 3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([0, 1, sqrt(3)], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testSparsevecScopeMaxInnerProduct()
    {
        $this->createItems('sparse_embedding');
        $neighbors = Item::query()->nearestNeighbors('sparse_embedding', '{1:1,2:1,3:1}/3', Distance::InnerProduct)->take(5)->get();
        $this->assertEquals([2, 3, 1], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([6, 4, 3], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testSparsevecInstance()
    {
        $this->createItems('sparse_embedding');
        $item = Item::find(1);
        $neighbors = $item->nearestNeighbors('sparse_embedding', Distance::L2)->take(5)->get();
        $this->assertEquals([3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([1, sqrt(3)], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testSparsevecInstanceL1()
    {
        $this->createItems('sparse_embedding');
        $item = Item::find(1);
        $neighbors = $item->nearestNeighbors('sparse_embedding', Distance::L1)->take(5)->get();
        $this->assertEquals([3, 2], $neighbors->pluck('id')->toArray());
        $this->assertEqualsWithDelta([1, 3], $neighbors->pluck('neighbor_distance')->toArray(), 0.00001);
    }

    public function testDistances()
    {
        $this->createItems();
        $distances = Item::selectRaw('embedding <-> ? AS distance', [new Vector([1, 1, 1])])->pluck('distance');
        $this->assertEqualsWithDelta([0, sqrt(3), 1], $distances->toArray(), 0.00001);
    }

    public function testMissingAttribute()
    {
        $this->expectException(MissingAttributeException::class);
        $this->expectExceptionMessage('The attribute [factors] either does not exist or was not retrieved for model [Item].');

        $this->createItems();
        $item = Item::find(1);
        $item->nearestNeighbors('factors', Distance::L2);
    }

    public function testInvalidDistance()
    {
        $this->expectException(TypeError::class);

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

    private function createItems($attribute = 'embedding')
    {
        foreach ([[1, 1, 1], [2, 2, 2], [1, 1, 2]] as $i => $v) {
            Item::create(['id' => $i + 1, $attribute => $v]);
        }
    }
}
