<?php

require_once __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

use PHPUnit\Framework\TestCase;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Config\BaseService;
use CodeIgniter\Config\Factories;
use CodeIgniter\Database\Config;
use CodeIgniter\Model;
use CodeIgniter\Settings\Settings;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;
use Pgvector\Vector;

class ItemModel extends Model
{
    protected $table = 'ci_items';
    protected $allowedFields = ['embedding'];
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

        $items = $itemModel->orderBy("embedding <-> '[1,1,1]'")->findAll();
        $this->assertEquals([1, 3, 2], array_map(fn ($v) => $v['id'], $items));
    }
}
