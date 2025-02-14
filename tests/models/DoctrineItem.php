<?php

use Doctrine\ORM\Mapping as ORM;
use Pgvector\HalfVector;
use Pgvector\SparseVector;
use Pgvector\Vector;

#[ORM\Entity]
#[ORM\Table(name: 'doctrine_items')]
class DoctrineItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    #[ORM\Column(type: 'vector', length: 3)]
    private Vector $embedding;

    #[ORM\Column(type: 'halfvec', length: 3)]
    private HalfVector $halfEmbedding;

    #[ORM\Column(type: 'bit', length: 3)]
    private string $binaryEmbedding;

    #[ORM\Column(type: 'sparsevec', length: 3)]
    private SparseVector $sparseEmbedding;

    public function getEmbedding(): Vector
    {
        return $this->embedding;
    }

    public function setEmbedding(Vector $embedding): void
    {
        $this->embedding = $embedding;
    }

    public function getHalfEmbedding(): HalfVector
    {
        return $this->halfEmbedding;
    }

    public function setHalfEmbedding(HalfVector $embedding): void
    {
        $this->halfEmbedding = $embedding;
    }

    public function getBinaryEmbedding(): string
    {
        return $this->binaryEmbedding;
    }

    public function setBinaryEmbedding(string $embedding): void
    {
        $this->binaryEmbedding = $embedding;
    }

    public function getSparseEmbedding(): SparseVector
    {
        return $this->sparseEmbedding;
    }

    public function setSparseEmbedding(SparseVector $embedding): void
    {
        $this->sparseEmbedding = $embedding;
    }
}
