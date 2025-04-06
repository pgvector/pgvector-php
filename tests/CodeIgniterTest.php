<?php

require_once __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

use PHPUnit\Framework\TestCase;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Config\BaseService;
use CodeIgniter\Config\Factories;
use CodeIgniter\DataCaster\Cast\BaseCast;
use CodeIgniter\Database\Config;
use CodeIgniter\Model;
use CodeIgniter\Settings\Settings;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;
use Pgvector\Vector;

class CastVector extends BaseCast
{
    public static function get(mixed $value, array $params = [], ?object $helper = null): Vector
    {
        return new Vector($value);
    }
}

class ItemModel extends Model
{
    protected $table = 'ci_items';
    protected $allowedFields = ['embedding'];

    protected array $casts = [
        'embedding' => 'vector',
    ];

    protected array $castHandlers = [
        'vector' => CastVector::class,
    ];
}

final class CodeIgniterTest extends TestCase
{
    public function testWorks()
    {
        $config = [
            'DSN' => 'Postgre://localhost/pgvector_php_test?charset=utf8',
        ];
        $db = Database::connect($config);

        $db->query('CREATE EXTENSION IF NOT EXISTS vector');
        $db->query('DROP TABLE IF EXISTS ci_items');
        $db->query('CREATE TABLE IF NOT EXISTS ci_items (id bigserial PRIMARY KEY, embedding vector(3))');

        $itemModel = new ItemModel($db);
        $itemModel->insert(['embedding' => new Vector([1, 1, 1])], false);
        $itemModel->insert(['embedding' => new Vector([2, 2, 2])], false);
        $itemModel->insert(['embedding' => new Vector([1, 1, 2])], false);

        $escaped = $db->escape(new Vector([1, 1, 1]));
        $items = $itemModel->orderBy("embedding <-> $escaped")->findAll();
        $this->assertEquals([1, 3, 2], array_map(fn ($v) => $v['id'], $items));
        $this->assertEquals(new Vector([1, 1, 2]), $items[1]['embedding']);
        $this->assertInstanceOf(Vector::class, $items[1]['embedding']);
    }
}
