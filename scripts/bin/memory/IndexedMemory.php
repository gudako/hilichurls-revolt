<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

/**
 * Represents an Hashtable-Indexed shared memory segment. See the <b>README.md</b> in the same directory for details.
 * @property-read int $shmopKey Key of the underlying <b>Shmop</b>.
 * @property-read int $shmopSize Size of the underlying <b>Shmop</b>.
 * @property-read int $bktCount Number of buckets that can be fit into the hashtable.
 * @property-read int $bktHashSize Size of the validator hash in a bucket.
 * @property-read int $bktNumericSize Size of numbers that denote offset and size in a bucket.
 */
abstract class IndexedMemory
{
    // PUBLIC PROPERTIES

    //region constants for keyStat
    /** See details in method {@link keyStat} */
    public const STAT_NOTHING = 0x0000, STAT_EXIST = 0x0001, STAT_UNSHIFTED = 0x0002, STAT_LINKED = 0x0004,
        STAT_SEALED = 0x0008, STAT_WRITING = 0x0010;
    //endregion

    //region constants for dumpMemory
    /** See details in method {@link dumpMemory} */
    public const PS_NOTHING = 0x0000, PS_PRINT_HTPROP = 0x0001, PS_PRINT_HTCONTEXT = 0x0002,
        PS_PRINT_MEMCONTEXT = 0x0004, PS_LBON_GROUPS = 0x0008, PS_LBON_BUCKETS = 0x0010, PS_LBON_MEMCONTEXT = 0x0020,
        PS_PRINT_HASHTABLE = self::PS_PRINT_HTPROP | self::PS_PRINT_HTCONTEXT,
        PS_PRINT_ALL = self::PS_PRINT_HASHTABLE | self::PS_PRINT_MEMCONTEXT,
        PS_LBON_HASHTABLE = self::PS_LBON_GROUPS | self::PS_LBON_BUCKETS,
        PS_LBON_ALL = self::PS_LBON_HASHTABLE | self::PS_LBON_MEMCONTEXT;
    //endregion

    // PRIVATE PROPERTIES

    /** @var array Array that contains all instances of <b>IndexedMemory</b>. <i>Released</i> instances are not contained. */
    private static array $registered = [];

    /** @var IndexedMemProp Memory properties of this <b>IndexedMemory</b>. */
    private readonly IndexedMemProp $memProp;

    /** @var bool Whether the memory is released. */
    private bool $released = false;

    /** @var array Array of all seeds written with values. */
    private array $regSeeds = [];

    /**
     * @var int <p>
     * Pointer to locate where to write the next <i>entity</i>. It is <b>0</b> when at the end of the hashtable.
     * Value <b>-1</b> denotes the memory is <i>sealed</i>.
     * </p>
     */
    private int $writePointer = -1;

    // CONSTRUCTORS AND MAGIC METHODS

    /**
     * Creates a new <b>IndexedMemory</b> object.
     * @param IndexedMemProp $mem_prop Memory properties of the new <b>IndexedMemory</b> object.
     */
    protected function __construct(IndexedMemProp $mem_prop) {
        $this->memProp = $mem_prop;
        self::$registered []= $this;
    }

    /**
     * @noinspection PhpMixedReturnTypeCanBeReducedInspection
     * @noinspection PhpEnforceDocCommentInspection
     * @noinspection RedundantSuppression
     */
    public function __get(string $name):mixed {
        return match ($name) {
            'shmopKey' => $this->memProp->shmopKey,
            'shmopSize' => $this->memProp->shmopSize,
            'bktCount' => $this->memProp->bktCount,
            'bktHashSize' => $this->memProp->bktHashSize,
            'bktNumericSize' => $this->memProp->bktNumericSize,
            default => trigger_error("Access to undefined property \$$name",E_USER_ERROR)
        };
    }

    // PRIVATE METHODS

    /**
     * Generates an <b>IndexedMemProp</b> for the creation of an <b>IndexedMemory</b> object.
     * @return IndexedMemProp Returns the property object.
     */
    private static function generateIMProperty(Shmop $shmop, int $key, int $size, int $bkt_count,
                                        int   $bkt_hash_size, int $bkt_numeric_size):IndexedMemProp {
        $ref_class = new ReflectionClass('IndexedMemProp');
        $create = $ref_class->getMethod('create');
        return $create->invokeArgs(null, [$shmop,$key,$size,$bkt_count,$bkt_hash_size,$bkt_numeric_size]);
    }

    // PROTECTED METHODS

    /**
     * Creates a new <b>Shmop</b> and make it ready to be <i>linked</i> to an <b>IndexedMemory</b>.<br>
     * <b>NOTICE: </b>Remember to call {@link flush} immediately after the creation to make sure all bytes are set to <b>0x00</b>.
     * @param int $identifier An <b>int</b> as the <i>identifier</i>.
     * @param int $keyHi High-part of a <b>Shmop</b> key.
     * @param int $size Size of the <b>Shmop</b> memory.
     * @param int $bkt_count Number of buckets to fit into the hashtable.
     * @param bool $override <p>
     * [optional] Whether to override if the <b>Shmop</b> key is already occupied.
     * If set to <b>false</b>, the method returns <b>false</b> on occupation.<br>
     * <b>NOTICE: </b>The overriding functionality has necessary invocation on {@link shmop_delete}, which has been known
     * that <a href='https://bugs.php.net/bug.php?id=65987'>in some environments, the <b>Shmop</b> is not immediately
     * deleted even if the call is successful.</a> Therefore, in cases overriding is needed,
     * after calling shmop_delete on the old <b>Shmop</b>, the new one will be created at the nearest unoccupied key
     * if the old key is not released. This only changes the <i>low-part</i> of the key and will fail if all <i>low-part</i>
     * values are occupied.
     * Make sure you record the <b>Shmop</b> key each time it may change, to prevent losing the access in next sessions.
     * </p>
     * @return IndexedMemProp|false Returns a <b>IndexedMemProperty</b>, or <b>false</b> on error.
     */
    protected static function newMemory(int $identifier, int $keyHi, int $size, int $bkt_count, bool $override = true):IndexedMemProp|false {
        if ($identifier < 0x00 || $identifier > 0xFF) {
            trigger_error('The identifier value is out of range for a single byte',E_USER_WARNING);
            return false;
        }
        if ($keyHi < 0x0000 || $keyHi >= 0x7FFF) {
            trigger_error('The high-part key is invalid',E_USER_WARNING);
            return false;
        }
        if ($size < 1) {
            trigger_error('The predefined Shmop size must be positive',E_USER_WARNING);
            return false;
        }
        if ($bkt_count < 1) {
            trigger_error('The bucket size must be positive',E_USER_WARNING);
            return false;
        }
        $bkt_hash_size = 1;
        while(1 - (1 - 256 ** -$bkt_hash_size) ** ($bkt_count - 1) >= 1e-10) {
            $bkt_hash_size++;
            if ($bkt_hash_size === 20) break;
        }
        $bkt_numeric_size = 1;
        while($bkt_numeric_size <= log($size - $bkt_count * ($bkt_hash_size + $bkt_numeric_size * 2),256)) {
            $bkt_numeric_size++;
        }
        $htprop_size = 6;
        $bkt_size = $bkt_hash_size + $bkt_numeric_size * 2;
        $htx_size = $bkt_size * $bkt_count;
        $ht_size = $htx_size + $htprop_size;
        $min_size = $htprop_size + $htx_size + $bkt_count - 1;
        if ($size < $min_size) {
            trigger_error("The predefined Shomp size ($size bytes) is too small to fit any data",E_USER_WARNING);
            return false;
        }
        if ($ht_size > 0xFFFFFF) {
            trigger_error("The hashtable size ($ht_size bytes) exceeded its maximum value (16777215 bytes)",E_USER_WARNING);
            return false;
        }
        $ht_size_ratio = $ht_size / $size;
        $ht_size_prec = round($ht_size_ratio * 100,3);
        $ht_big_warn = 0.25;
        $ht_small_warn = 0.05;
        if ($ht_size_ratio > $ht_big_warn) {
            trigger_error("The hash table size has occupied $ht_size_prec% of total size. This ratio is too big.".
                'This imbalance may make your remaining memory too small to contain your data afterwards.', E_USER_WARNING);
        } elseif ($ht_size_ratio < $ht_small_warn) {
            trigger_error("The hash table size has only $ht_size_prec% of total size. This ratio is too small.".
                'This imbalance may make your available buckets too few afterwards.', E_USER_WARNING);
        }
        $key = hexdec(str_pad(dechex($keyHi), 4, '0', STR_PAD_LEFT) .'0000');
        if (self::keyStat($key) & self::STAT_EXIST) {
            if (!$override) {
                $key_hex = dechex($key);
                trigger_error("The Shmop key $key_hex is occupied.",E_USER_WARNING);
                return false;
            }
            $shmop = shmop_open($key, 'w', 0, 0);
            if ($shmop === false || !shmop_delete($shmop)) return false;
            while (self::keyStat($key) & self::STAT_EXIST) {
                if ($keyHi * 0x10000 + 0xFFFF === $key) {
                    trigger_error("The lower-part has gone over the domain of the high-part $keyHi.",E_USER_WARNING);
                    return false;
                }
                $key++;
            }
        }
        $shmop = shmop_open($key, 'c', 0o666, $size);
        if ($shmop === false) return false;
        $htprop = hex2bin(str_pad(dechex($identifier),2,'0',STR_PAD_LEFT).
            str_pad(dechex($ht_size),6,'0',STR_PAD_LEFT).
            str_pad(dechex($bkt_hash_size),2,'0',STR_PAD_LEFT).
            str_pad(dechex($bkt_numeric_size),2,'0', STR_PAD_LEFT));
        shmop_write($shmop, $htprop, 0);
        return self::generateIMProperty($shmop, $key, $size, $bkt_count, $bkt_hash_size, $bkt_numeric_size);
    }

    /**
     * Extracts properties from an existing <b>Shmop</b> by its key, which is then made ready to be <i>linked</i> to an
     * <b>IndexedMemory</b>.
     * @param int $identifier The <b>Shmop</b>'s <i>identifier</i> as an indexed memory. The method fails if it doesn't match.
     * @param int $key A <b>Shmop</b> key.
     * @param int $size Assumed size of the <b>Shmop</b>.
     * @return IndexedMemProp|false Returns an <b>IndexedMemProperty</b> on success, or <b>false</b> on error.
     * </p>
     */
    protected static function extractMemory(int $identifier, int $key, int $size):IndexedMemProp|false {
        $stat = self::keyStat($key);
        if ($stat === 0 || ($stat & self::STAT_LINKED)) {
            trigger_error("Shmop key $key not ready for link",E_USER_WARNING);
            return false;
        }
        $shmop = shmop_open($key, 'c', 0o666, $size);
        if ($shmop === false) return false;
        $htprop_size = 6;
        $htstat = shmop_read($shmop, 0, $htprop_size);
        /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
        if ($htstat === false) return false;

        $assumed_identifier = bindec(substr($htstat,0,1));
        if ($assumed_identifier !== $identifier) {
            trigger_error('Identifier is wrong',E_USER_WARNING);
            return false;
        }
        $ht_size = bindec(substr($htstat,1,3));
        $bkt_hash_size = bindec(substr($htstat,4,1));
        $bkt_numeric_size = bindec(substr($htstat,5,1));
        $bkt_count = ($ht_size - $htprop_size) / ($bkt_hash_size + $bkt_numeric_size * 2);
        if (!is_int($bkt_count)) {
            trigger_error('Malformed memory segment as indexed memory',E_USER_WARNING);
            return false;
        }
        return self::generateIMProperty($shmop, $key, $size, $bkt_count, $bkt_hash_size, $bkt_numeric_size);
    }

    /**
     * Flushes the memory by setting all bytes to <b>0x00</b> and <i>unseal</i> the <b>IndexedMemory</b> by setting its
     * write-pointer to <b>0</b>.
     * @param bool $flush_htprop [optional] Whether to flush <i>htprop</i>. If set to <b>true</b>, the entire memory is flushed.
     * @return bool Returns <b>true</b> on success and <b>false</b> on error.
     */
    protected function flush(bool $flush_htprop = false):bool {
        if ($this->released) {
            trigger_error('IndexedMemory already released',E_USER_WARNING);
            return false;
        }
        $htprop_size = 6;
        if ($flush_htprop) {
            $htprop_size = 0;
        }
        shmop_write($this->memProp->shmop, str_repeat(hex2bin('00'),$this->shmopSize - $htprop_size), $htprop_size);
        $this->writePointer = 0;
        return true;
    }

    /**
     * Writes an <i>entity</i> to the memory at the current write-pointer, and increment the pointer by the size of the <i>entity</i>.
     * @param string $seed The seed to get access to the entity via the hashtable.
     * @param string $value The entity's value.
     * @param array|null $res <p>
     * [optional] Supply a placeholder to record the result of writing.<br>
     * The position will be replaced by an array with the following values:<br>
     * <b>bkt_offset</b>  - Offset of the bucket in <b>Shmop</b>.<br>
     * <b>bkt_size</b>    - Size of the bucket in <b>Shmop</b>.<br>
     * <b>val_offset</b>  - Offset of the value in <b>Shmop</b>.<br>
     * <b>val_size</b>    - Size of the value in <b>Shmop</b>.<br>
     * <b>collisions</b>  - Total times of hash collisions when adding to the hashtable.<br>
     * <b>hits</b>        - Always has the value: <b>hits = collisions + 1</b><br>
     * </p>
     * @return bool Returns <b>true</b> on success or <b>false</b> on error.
     */
    protected function writeEntity(string $seed, string $value, array &$res = null):bool {
        if ($this->released) {
            trigger_error('IndexedMemory already released',E_USER_WARNING);
            return false;
        } elseif ($this->writePointer === -1) {
            trigger_error('IndexedMemory is sealed',E_USER_WARNING);
            return false;
        } elseif (in_array($seed, $this->regSeeds, true)) {
            trigger_error("Seed \"$seed\" has already been occupied",E_USER_WARNING);
            return false;
        }
        $htprop_size = 6;
        $bkt_size = $this->bktHashSize + $this->bktNumericSize * 2;
        $ht_size = $bkt_size * $this->bktCount + $htprop_size;
        $htx_size = $ht_size - $htprop_size;
        $write_at = $ht_size + $this->writePointer;
        $lens = strlen($value);
        $after_size = $write_at + $lens;
        $overflow_size = $after_size - $this->shmopSize;
        if ($overflow_size > 0) {
            trigger_error("Overflow of Shmop memory size: $overflow_size bytes larger than the maximum",E_USER_WARNING);
            return false;
        }
        /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
        if (shmop_write($this->memProp->shmop, $value, $write_at) === false) {
            return false;
        }
        $txtc_hash = substr(sha1($seed), 0, $this->bktHashSize * 2);
        $bkt_init_loc = (hexdec($txtc_hash) % $this->bktCount) * $bkt_size + $htprop_size;
        $bkt_loc = $bkt_init_loc;

        $hits = 0;
        $misses = 0;
        while(true) {
            $hits++;
            $org_val = shmop_read($this->memProp->shmop, $bkt_loc, $bkt_size);
            /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
            if ($org_val === false)return false;
            if ($org_val === str_repeat(hex2bin('00'), $bkt_size)) break;
            $misses++;
            $bkt_loc += $bkt_size;
            if ($bkt_loc >= $ht_size) $bkt_loc -= $htx_size;
            if ($bkt_init_loc === $bkt_loc) {
                trigger_error("Index table is full at $this->bktCount buckets",E_USER_WARNING);
                return false;
            }
        }
        $write_as = hex2bin($txtc_hash.
            str_pad(dechex($this->writePointer),$this->bktNumericSize * 2,'0',STR_PAD_LEFT).
            str_pad(dechex($lens),$this->bktNumericSize * 2,'0',STR_PAD_LEFT));
        /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
        if (shmop_write($this->memProp->shmop, $write_as, $bkt_loc) === false) {
            return false;
        }
        $this->writePointer = $after_size - $ht_size;
        if ($res !== null) {
            $res = [
                'bkt_offset' => $bkt_loc,
                'bkt_size'=> $bkt_size,
                'val_offset' => $write_at,
                'val_size' => $lens,
                'collisions' => $misses,
                'hits' => $hits
            ];
        }
        $this->regSeeds []= $seed;
        return true;
    }

    /**
     * Write a data to the entities section of the indexed memory. The offset of the end of this writing must not exceed
     * the write-pointer.
     * @param string $data Data to write.
     * @param int $offset Offset starting at the end of hashtable.
     * @return bool Returns <b>true</b> on success and <b>false</b> on error.
     */
    protected function writeAt(string $data, int $offset):bool {
        if ($this->released) {
            trigger_error('IndexedMemory already released',E_USER_WARNING);
            return false;
        } elseif ($this->writePointer === -1) {
            trigger_error('IndexedMemory is sealed',E_USER_WARNING);
            return false;
        } elseif ($offset < 0) {
            trigger_error('Negative offset not allowed',E_USER_WARNING);
            return false;
        }
        $lens = strlen($data);
        $overflow = $offset + $lens - $this->writePointer;
        if ($overflow > 0) {
            trigger_error("Writing domain overflowed the pointer for $overflow bytes",E_USER_WARNING);
            return false;
        }
        $htprop_size = 6;
        $ht_size = $this->bktCount * ($this->bktHashSize + $this->bktNumericSize * 2) + $htprop_size;
        /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
        if (shmop_write($this->memProp->shmop, $data, $ht_size + $offset) === false) {
            return false;
        }
        return true;
    }

    /**
     * Reads an <i>entity</i>, or any part of memory in the entities section.<br>
     * <b>NOTICE: </b>The <b>IndexMemory</b> must be <i>sealed</i> before values can be read. Calling this on an unsealed
     * <b>IndexMemory</b> will result in error.
     * @param ...$args <p>
     * The arguments can be one of the followings:<br>
     * (a) A <b>string</b> representing a <i>seed</i>.<br>
     * (b) An array with two <b>int</b>, respectively the memory offset and size. <b>(deprecated!)</b><br>
     * (c) Two <b>int</b>, respectively the memory offset and size. <b>(deprecated!)</b><br>
     * The offset we talked above is the offset <b>relative to the end of the hashtable</b>.
     * </p>
     * @return string|false|null <p>
     * If the argument is a <i>seed</i> (a <b>string</b>), returns the <i>entity</i> matched by the <i>seed</i>,
     * or <b>null</b> if none is matched;
     * If the argument(s) denote an offset and size, the data is fetched from the memory and returned;
     * If any error occurs, returns <b>false</b>.
     * </p>
     */
    protected function readEntity(...$args):string|false|null {
        if ($this->writePointer!==-1) {
            trigger_error('Getting value from unsealed IndexedMemory',E_USER_WARNING);
            return false;
        }elseif ($this->released) {
            trigger_error('IndexedMemory already released',E_USER_WARNING);
            return false;
        }
        $args_array = func_get_args();
        $lens = func_num_args();
        if ($lens<=0||$lens===1&&(gettype($args_array[0])!=='string'&&!is_array($args_array[0])||is_array($args_array[0])
                &&(count($args_array[0])!==2||!is_int($args_array[0][0])||!is_int($args_array[0][1])))||$lens===2&&
            (!is_int($args_array[0])||!is_int($args_array[1]))||$lens>2) {
            trigger_error('Invalid arguments',E_USER_WARNING);
            return false;
        }
        $htprop_size = 6;
        $bkt_size = $this->bktHashSize + $this->bktNumericSize * 2;
        $ht_size = $bkt_size * $this->bktCount + $htprop_size;
        if ($lens === 1 && is_array($args_array[0])) {
            $offset = $args_array[0][0];
            $size = $args_array[0][1];
            trigger_error('Reading directly via segment arguments is deprecated', E_USER_DEPRECATED);
        } elseif ($lens === 2) {
            $offset = $args_array[0];
            $size = $args_array[1];
            trigger_error('Reading directly via segment arguments is deprecated', E_USER_DEPRECATED);
        } else {
            $htx_size = $ht_size - $htprop_size;
            $txtc_hash = substr(sha1($args_array[0]), 0, $this->bktHashSize * 2);
            $bkt_init_loc = (hexdec($txtc_hash) % $this->bktCount) * $bkt_size + $htprop_size;
            $pointer = $bkt_init_loc;
            while(true) {
                $bkt = shmop_read($this->memProp->shmop, $pointer, $bkt_size);
                /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
                if ($bkt === false) return false;
                if (hash_equals($txtc_hash, bin2hex(substr($bkt,0, $this->bktHashSize)))) break;
                $pointer++;
                if ($pointer >= $ht_size) $pointer -= $htx_size;
                if ($pointer === $bkt_init_loc) return null;
            }
            $offset = hexdec(bin2hex(substr($bkt, $this->bktHashSize, $this->bktNumericSize)));
            $size = hexdec(bin2hex(substr($bkt,$this->bktHashSize + $this->bktNumericSize, $this->bktNumericSize)));
        }
        $overflow = $ht_size + $offset - $this->shmopSize;
        if ($overflow > 0) {
            trigger_error("The segment specified for reading has run out the memory $overflow bytes", E_USER_WARNING);
            return false;
        }
        $payload = shmop_read($this->memProp->shmop,$ht_size + $offset, $size);
        /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
        if ($payload === false) return false;
        return $payload;
    }

    /**
     * Release the <b>IndexedMemory</b> and deletes the underlying <b>Shmop</b> memory.<br>
     * <i><b>NOTICE: </b>After calling <b>release</b>, <u>this object</u> is meant to be later garbage-collected by the GC.
     * Calling any method (including <b>release</b> itself) on an released <b>IndexedMemory</b> results in error.</i>
     * @return bool Returns <b>true</b> on success and <b>false</b> on error.
     */
    protected function release():bool {
        if ($this->released) {
            trigger_error('IndexedMemory already released',E_USER_WARNING);
            return false;
        }
        if (shmop_delete($this->memProp->shmop)===false) {
            return false;
        }
        $remov_index = -1;
        $lens = count(self::$registered);
        for($i = 0;$i <= $lens - 1; $i++) {
            if (self::$registered[$i] === $this) {
                $remov_index = $i;
                break;
            }
        }
        unset(self::$registered[$remov_index]);
        self::$registered = array_values(self::$registered);
        $this->released = true;
        return true;
    }

    // PUBLIC METHODS

    /**
     * Seal the memory so no further writing can be done.
     * @return bool Returns <b>true</b> on success and <b>false</b> on error.
     */
    public function seal():bool {
        if ($this->released) {
            trigger_error('IndexedMemory already released',E_USER_WARNING);
            return false;
        } elseif ($this->writePointer === 0) {
            trigger_error('Sealing an IndexedMemory whose pointer is at 0 is disallowed',E_USER_WARNING);
            return false;
        }
        $this->writePointer = -1;
        return true;
    }

    /**
     * Gets the status of a <b>Shmop</b> key.
     * @param int $key A <b>Shmop</b> key.
     * @return int <p>
     * The result can be a combination of the following values:<br>
     * <b>{@link STAT_NOTHING}</b>   - The key is unoccupied.<br>
     * <b>{@link STAT_EXIST}</b>     - The key belongs to a <b>Shmop</b>.<br>
     * <b>{@link STAT_UNSHIFTED}</b> - The key has a low-part value of <b>0</b>.<br>
     * <b>{@link STAT_LINKED}</b>    - The key is linked to a <b>LinkedMemory</b>.<br>
     * <b>{@link STAT_SEALED}</b>    - The key's <b>LinkedMemory</b> is sealed.<br>
     * <b>{@link STAT_WRITING}</b>   - <b>LinkedMemory</b> has a positive write-pointer.<br>
     * <b>NOTICE: </b>Ending a session also drops all <b>IndexMemory</b> instances as the PHP memory finalizes.
     * Therefore, when a new session starts, You have to get them back with their <b>Shmop</b> IDs by calling {@link extractMemory}.
     * </p>
     */
    public static function keyStat(int $key):int {
        $val = 0;
        $key_hex = dechex($key);
        if (str_ends_with($key_hex, '0000')) $val |= self::STAT_UNSHIFTED;
        $linked=false;
        foreach (self::$registered as $item) {
            if ($item->shmopKey === $key) {
                $linked = true;
                break;
            }
        }
        if ($linked === false) {
            $shmop = @shmop_open($key, 'a', 0, 0);
            if ($shmop !== false) $val |= self::STAT_EXIST;
            else return $val;
        } else {
            $val |= self::STAT_EXIST | self::STAT_LINKED;
        }
        if ($linked->writePointer === -1) $val |= self::STAT_SEALED;
        elseif ($linked->writePointer !== 0) $val |= self::STAT_WRITING;
        return $val;
    }
    
    /**
     * Dumps the memory of the underlying <b>Shmop</b>.
     * @param int $setting <p>
     * [optional] The parameter should be a combination of the following values:<br>
     * <b>{@link PS_NOTHING}</b>          - Nothing.<br>
     * <b>{@link PS_PRINT_HTPROP}</b>     - Print the hashtable properties (the first 5 bytes).<br>
     * <b>{@link PS_PRINT_HTCONTEXT}</b>  - Print the hashtable context (excluding hashtable properties).<br>
     * <b>{@link PS_PRINT_HASHTABLE}</b>  - Print the hashtable (including hashtable properties).<br>
     * <b>{@link PS_PRINT_MEMCONTEXT}</b> - Print the memory context.<br>
     * <b>{@link PS_PRINT_ALL}</b>        - Print everything.<br>
     * <b>{@link PS_LBON_GROUPS}</b>      - Place linebreaks to split three groups. The three groups are: Hashtable properties;
     *                                      Hashtable context; Memory context.<br>
     * <b>{@link PS_LBON_BUCKETS}</b>     - Place linebreaks between hashtable buckets.<br>
     * <b>{@link PS_LBON_ALL}</b>         - Both <b>PS_LBON_GROUPS</b> and <b>PS_LBON_BUCKETS</b>.<br>
     * </p>
     * @return string|false Returns the hex dumped, or <b>false</b> on error.
     */
    public function dumpMemory(int $setting = self::PS_PRINT_ALL):string|false {
        if ($this->released) {
            trigger_error('IndexedMemory already released',E_USER_WARNING);
            return false;
        }
        $htprop_size = 6;
        $bkt_size = $this->bktHashSize + $this->bktNumericSize * 2;
        $ht_size = $htprop_size + $bkt_size * $this->bktCount;
        $mem_end_at = $this->shmopSize;
        $htx_size = $ht_size - $htprop_size;
        $mem_size = $mem_end_at - $ht_size;

        $pr_htprop = $setting & self::PS_PRINT_HTPROP;
        $pr_htx = $setting & self::PS_PRINT_HTCONTEXT;
        $pr_mem = $setting & self::PS_PRINT_MEMCONTEXT;

        $payload = '';
        $linebreak = (string)conf('char_linebreak');
        $shmop = $this->memProp->shmop;
        if ($setting & self::PS_PRINT_HTPROP) {
            $res=shmop_read($shmop, 0, $htprop_size);
            /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
            if ($res === false)return false;
            $res = bin2hex($res);
            $payload .= $res;
        }
        if (($setting & self::PS_LBON_GROUPS) && $pr_htprop && ($pr_htx || $pr_mem)) {
            $payload.=$linebreak;
        }
        if ($setting & self::PS_PRINT_HTCONTEXT) {
            $res = shmop_read($shmop, $htprop_size, $htx_size);
            /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
            if ($res === false) return false;
            $res = bin2hex($res);
            if ($setting & self::PS_LBON_HASHTABLE) {
                $res = implode($linebreak, str_split($res, $bkt_size * 2));
            }
            $payload.=$res;
        }
        if (($setting & self::PS_LBON_GROUPS) && $pr_htx && $pr_mem) {
            $payload.=$linebreak;
        }
        if ($setting & self::PS_PRINT_MEMCONTEXT) {
            $res = shmop_read($shmop, $ht_size, $mem_size);
            /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
            if ($res === false)return false;
            $res = bin2hex($res);
            $payload .= $res;
        }
        return $payload.$linebreak;
    }
}
