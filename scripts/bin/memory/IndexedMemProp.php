<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

/**
 * Represents a bunch of memory properties necessary for initializing an {@link IndexedMemory} object.
 */
final class IndexedMemProp
{
    // PUBLIC PROPERTIES

    /** @var Shmop The underlying <b>Shmop</b> that stores the memory segment. */
    public readonly Shmop $shmop;

    /** @var int Key of the underlying <b>Shmop</b>. */
    public readonly int $shmopKey;

    /** @var int Size of the underlying <b>Shmop</b>. */
    public readonly int $shmopSize;

    /**
     * @var int <p>
     * Number of <a href='https://stackoverflow.com/a/9073935'>buckets</a> that can be fit into the hashtable.
     * Each bucket has a validator hash and two numbers, respectively representing memory offset and size in a memory segment.
     * </p>
     */
    public readonly int $bktCount;

    /** @var int Size of the validator hash in a bucket. */
    public readonly int $bktHashSize;

    /** @var int Size of numbers that denote offset and size in a bucket. */
    public readonly int $bktNumericSize;

    // CONSTRUCTORS AND MAGIC METHODS

    /** Creates a new <b>IndexedMemProp</b> object. */
    private function __construct(Shmop $shmop, int $key, int $size,
                                 int $bkt_count, int $bkt_hash_size, int $bkt_numeric_size) {
        $this->shmop = $shmop;
        $this->shmopKey = $key;
        $this->shmopSize = $size;
        $this->bktCount = $bkt_count;
        $this->bktHashSize = $bkt_hash_size;
        $this->bktNumericSize = $bkt_numeric_size;
    }

    // PRIVATE METHODS

    /** Creates a new <b>IndexedMemProp</b> object. */
    private static function create(Shmop $shmop, int $key, int $size,
                                   int $bkt_count, int $bkt_hash_size, int $bkt_numeric_size):self {
        return new self($shmop, $key, $size, $bkt_count, $bkt_hash_size, $bkt_numeric_size);
    }
}
