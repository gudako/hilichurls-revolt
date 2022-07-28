<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

/**
 * Represents a memory segment used to store trophy data.
 */
final class TrophyBook extends IndexedMemory
{
    // PRIVATE PROPERTIES

    /** @var int Identifier of trophy book. */
    private const IDENTIFIER_BYTE = 0X0002;

    /** @var int Size of an <i>category size indicator</i>. */
    private readonly int $csiSize;

    /** @var int Size of an <i>category attributes size indicator</i>. */
    private readonly int $casiSize;

    /** @var int Size of an <i>category attributes size indicator</i>. */
    private readonly int $isiSize;

    /** @var int The internal pointer for reading trophy data. */
    private int $pointer = 0;

    /** @var int The offset of the <i>category size hole</i>. Only makes sense when writing. */
    private int $holeOffset = -1;

    /** @var int The offset of the first invisible trophy item in current category. Only makes sense when writing. */
    private int $fiiOffset = -1;

    /** @var int The accumulated size of the current category during writing progress. Only makes sense when writing. */
    private int $accumCateSize = 0;

    /** @var bool Whether the trophy book has an invisible category. Only makes sense when writing. */
    private bool $hasInvisibleCategory = false;

    // CONSTRUCTORS AND MAGIC METHODS

    /**
     * Creates a new <b>IndexedMemory</b> object.
     * @param IndexedMemProp $mem_prop Memory properties of the new <b>IndexedMemory</b> object.
     */
    private function __construct(IndexedMemProp $mem_prop, int $csi_size, int $casi_size, int $isi_size) {
        parent::__construct($mem_prop);
        $this->csiSize = $csi_size;
        $this->casiSize = $casi_size;
        $this->isiSize = $isi_size;
    }

    // PUBLIC METHODS

    /**
     * Creates a new <b>TrophyBook</b> memory.
     * @param int $keyHi High-part of a <b>Shmop</b> key.
     * @param int $size Size of the <b>Shmop</b> memory.
     * @param int $bkt_count Number of buckets to fit into the hashtable.
     * @param int $csi_size Size of an <i>category size indicator</i>.
     * @param int $casi_size Size of an <i>category attributes size indicator</i>.
     * @param int $isi_size Size of an <i>category attributes size indicator</i>.
     * @return static|false Returns an <b>TrophyBook</b> on success, or <b>false</b> on error.
     */
    public static function create(int $keyHi, int $size, int $bkt_count, int $csi_size, int $casi_size,
                                  int $isi_size):self|false {
        $mem = parent::newMemory(self::IDENTIFIER_BYTE, $keyHi, $size, $bkt_count);
        if ($mem === false) return false;
        $obj = new self($mem, $csi_size, $casi_size, $isi_size);
        $obj->flush();
        return $obj;
    }

    /**
     * Creates a new <b>TrophyBook</b> memory according to the config.
     * @return static|false Returns an <b>TrophyBook</b> on success, or <b>false</b> on error.
     */
    public static function createAccConfig():self|false {
        $imem_trophy = conf('imem_trophy');
        if (!is_array($imem_trophy) ||
            !isset($imem_trophy['hid'], $imem_trophy['buckets'], $imem_trophy['size'], $imem_trophy['csi_size'],
                $imem_trophy['casi_size'], $imem_trophy['isi_size'])) {
            trigger_error('Malformed config item "imem_trophy"', E_USER_WARNING);
            return false;
        }
        return self::create($imem_trophy['hid'], $imem_trophy['buckets'], $imem_trophy['size'], $imem_trophy['csi_size'],
            $imem_trophy['casi_size'], $imem_trophy['isi_size']);
    }

    /**
     * Gets a <b>TrophyBook</b> instance by its key.
     * @param int $key A <b>Shmop</b> key.
     * @param int $size Assumed size of the <b>Shmop</b>.
     * @param int $csi_size Size of an <i>category size indicator</i>.
     * @param int $casi_size Size of an <i>category attributes size indicator</i>.
     * @param int $isi_size Size of an <i>category attributes size indicator</i>.
     * @return static|false Returns an <b>TrophyBook</b> on success, or <b>false</b> on error.
     */
    public static function link(int $key, int $size, int $csi_size, int $casi_size, int $isi_size):self|false {
        $mem = parent::extractMemory(self::IDENTIFIER_BYTE, $key, $size);
        if ($mem === false) return false;
        return new self($mem, $csi_size, $casi_size, $isi_size);
    }

    /**
     * Gets a <b>TrophyBook</b> instance by its low-part key.
     * @param int $key_lo The low-part key.
     * @return static|false Returns an <b>TrophyBook</b> on success, or <b>false</b> on error.
     */
    public static function linkAccConfig(int $key_lo):self|false {
        if ($key_lo < 0x0000 || $key_lo > 0xFFFF) {
            trigger_error('The low-part key is out of range', E_USER_WARNING);
            return false;
        }
        $imem_trophy = conf('imem_trophy');
        if (!is_array($imem_trophy) || !isset($imem_trophy['hid'], $imem_trophy['size'], $imem_trophy['csi_size'],
                $imem_trophy['casi_size'], $imem_trophy['isi_size'])) {
            trigger_error('Malformed config item "imem_trophy"', E_USER_WARNING);
            return false;
        }
        $key_hi = $imem_trophy['hid'];
        $key = hexdec(dechex($key_hi).str_pad(dechex($key_lo), 4, '0', STR_PAD_LEFT));
        return self::link($key, $imem_trophy['size'], $imem_trophy['csi_size'], $imem_trophy['casi_size'], $imem_trophy['isi_size']);
    }

    /**
     * Makes a new category. In a new category, the <i>visibility switch</i> is initially <b>ON</b>. You can call {@link turnOff}
     * to turn it off at any time. When a new trophy is added, its visibility depends on the <i>visibility switch</i>.
     * @param string $seed A seed.
     * @param string $data Data that denotes the category's attributes.
     * @param bool $visible Whether the category is visible. It is highly recommended to <b>last</b> add the invisible categories.
     * @param array|null $res [optional] See {@link IndexedMemory::writeEntity}
     * @return bool Returns <b>true</b> on success or <b>false</b> on error.
     */
    public function newCategory(string $seed, string $data, bool $visible = true, array &$res = null):bool {
        // todo: Implement newCategory method
    }

    /**
     * Makes a new trophy. The trophy belongs to the category created in the last {@link newCategory} call, and its its
     * visibility depends on the <i>visibility switch</i>.
     * @param string $seed A seed.
     * @param string $data Data that denotes the trophy's attributes.
     * @param array|null $res [optional] See {@link IndexedMemory::writeEntity}
     * @return bool Returns <b>true</b> on success or <b>false</b> on error.
     */
    public function newTrophy(string $seed, string $data, array &$res = null):bool {
        if ($this->holeOffset === -1) {

        }
        // todo: Implement newTrophy method
    }

    /**
     * @return bool
     */
    public function turnOff():bool {
        // todo: Implement turnOff method
    }

    /** @inheritDoc */
    public function seal(): bool
    {
        // todo
        return parent::seal();
    }

    /** @inheritDoc */
    protected function flush(bool $flush_htprop = false):bool {
        $this->holeOffset = -1;
        $this->fiiOffset = -1;
        $this->accumCateSize = 0;
        $this->hasInvisibleCategory = false;
        return parent::flush($flush_htprop);
    }

    public function resetPointer():bool {
        // todo: Implement resetPointer method
    }
}
