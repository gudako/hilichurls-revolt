<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

/**
 * Represents a memory segment used to store language-text data.
 */
final class LanguageBook extends IndexedMemory
{
    // PRIVATE PROPERTIES

    /** @var int Identifier of language book. */
    private const IDENTIFIER_BYTE = 0X0001;

    /** @var array An array of all languages available. */
    private readonly array $langs;

    /** @var int Size of an <i>language offset indicator</i>. */
    private readonly int $loiSize;

    // CONSTRUCTORS AND MAGIC METHODS

    /**
     * Creates a new <b>IndexedMemory</b> object.
     * @param IndexedMemProp $mem_prop Memory properties of the new <b>IndexedMemory</b> object.
     */
    private function __construct(IndexedMemProp $mem_prop, int $loi_size) {
        parent::__construct($mem_prop);
        $this->loiSize = $loi_size;
        $this->langs = conf('all_lang');
    }

    // PUBLIC METHODS

    /**
     * Creates a new <b>LanguageBook</b> memory.
     * @param int $keyHi High-part of a <b>Shmop</b> key.
     * @param int $size Size of the <b>Shmop</b> memory.
     * @param int $bkt_count Number of buckets to fit into the hashtable.
     * @param int $loi_size Size of an <i>language offset indicator</i>.
     * @return static|false Returns an <b>LanguageBook</b> on success, or <b>false</b> on error.
     */
    public static function create(int $keyHi, int $size, int $bkt_count, int $loi_size):self|false {
        $mem = parent::newMemory(self::IDENTIFIER_BYTE, $keyHi, $size, $bkt_count);
        if ($mem === false) return false;
        $obj = new self($mem, $loi_size);
        $obj->flush();
        return $obj;
    }

    /**
     * Creates a new <b>LanguageBook</b> memory according to the config.
     * @return static|false Returns an <b>LanguageBook</b> on success, or <b>false</b> on error.
     */
    public static function createAccConfig():self|false {
        $imem_lang = conf('imem_lang');
        if (!is_array($imem_lang) ||
            !isset($imem_lang['hid'], $imem_lang['buckets'], $imem_lang['size'], $imem_lang['loi_size'])) {
            trigger_error('Malformed config item "imem_lang"', E_USER_WARNING);
            return false;
        }
        return self::create($imem_lang['hid'], $imem_lang['buckets'], $imem_lang['size'], $imem_lang['loi_size']);
    }

    /**
     * Gets a <b>LanguageBook</b> instance by its key.
     * @param int $key A <b>Shmop</b> key.
     * @param int $size Assumed size of the <b>Shmop</b>.
     * @param int $loi_size Size of an <i>language offset indicator</i>.
     * @return static|false Returns an <b>LanguageBook</b> on success, or <b>false</b> on error.
     */
    public static function link(int $key, int $size, int $loi_size):self|false {
        $mem = parent::extractMemory(self::IDENTIFIER_BYTE, $key, $size);
        if ($mem === false) return false;
        return new self($mem, $loi_size);
    }

    /**
     * Gets a <b>LanguageBook</b> instance by its low-part key.
     * @param int $key_lo The low-part key.
     * @return static|false Returns an <b>LanguageBook</b> on success, or <b>false</b> on error.
     */
    public static function linkAccConfig(int $key_lo):self|false {
        if ($key_lo < 0x0000 || $key_lo > 0xFFFF) {
            trigger_error('The low-part key is out of range', E_USER_WARNING);
            return false;
        }
        $imem_lang = conf('imem_lang');
        if (!is_array($imem_lang) || !isset($imem_lang['hid'], $imem_lang['size'], $imem_lang['loi_size'])) {
            trigger_error('Malformed config item "imem_lang"', E_USER_WARNING);
            return false;
        }
        $key_hi = $imem_lang['hid'];
        $key = hexdec(dechex($key_hi).str_pad(dechex($key_lo), 4, '0', STR_PAD_LEFT));
        return self::link($key, $imem_lang['size'], $imem_lang['loi_size']);
    }

    /**
     * Write a new text definition to the book.
     * @param string $seed A seed.
     * @param array $texts An array that contains keys of all languages.
     * @param array|null $res [optional] See {@link IndexedMemory::writeEntity}
     * @return bool Returns <b>true</b> on success or <b>false</b> on error.
     */
    public function writeText(string $seed, array $texts, array &$res = null):bool {
        $prefix = '';
        $payload = '';
        $offset = 0;
        foreach ($this->langs as $lang) {
            if (!isset($texts[$lang])) {
                trigger_error("Language \"$lang\" is not found in the input.", E_USER_WARNING);
                return false;
            }
            $text = &$texts[$lang];
            $payload .= $text;
            $lens = strlen($text);
            $prefix .= str_pad(dechex($offset), $this->loiSize * 2, '0', STR_PAD_LEFT);
            $offset += $lens;
            $overflow = $offset - 256 ** $this->loiSize - 1;
            if ($overflow > 0) {
                trigger_error("Input text is $overflow bytes larger than the maximum", E_USER_WARNING);
                return false;
            }
        }
        $payload = hex2bin($prefix).$payload;
        return $this->writeEntity($seed, $payload, $res);
    }

    /**
     * Imports a JSON file to finish the writing of the <b>LanguageBook</b>. Calling this will unseal and flush the memory
     * first. Then, the file content is written into the <b>LanguageBook</b>. At last, it seals the memory.
     * @param string $path [optional] A path to the JSON file, relative to the document root. The path should begin with a backslash.
     * @param bool $tolerate <p>
     * [optional] If set to <b>true</b>, when individual keys in the JSON file have problem, it does not fail the entire call.
     * </p>
     * @return bool Returns <b>true</b> on success or <b>false</b> on error.
     */
    public function importJson(string $path = '/scripts/sys/conf/lang.json', bool $tolerate = false):bool {
        $content = file_get_contents($_SERVER['DOCUMENT_ROOT'].$path);
        if ($content === false) return false;
        $group = json_decode($content, true);
        if ($group === null) {
            trigger_error("Unable to decode JSON file: \"$path\"", E_USER_WARNING);
            return false;
        }
        $this->flush();
        foreach ($group as $key => $value) {
            if (!is_array($value)) {
                trigger_error("Bad format in JSON key \"$key\"", E_USER_WARNING);
                if (!$tolerate) return false;
            }
            if (!$this->writeText($key, $value) && !$tolerate) return false;
        }
        $this->seal();
        return true;
    }

    /**
     * Reads a text of specified language.
     * @param string $seed A seed.
     * @param string $lang Language of the returning text.
     * @return string|null|false <p>
     * Returns the text in specified language if the seed is valid, otherwise returns <b>null</b>;
     * Returns <b>null</b> on error.
     * </p>
     */
    public function readText(string $seed, string $lang):string|null|false {
        $lang_index = array_search($lang, $this->langs);
        if ($lang_index === false) {
            trigger_error("Undefined language \"$lang\"", E_USER_WARNING);
            return false;
        }
        $offset_dic = $lang_index * $this->loiSize;
        $lens = count($this->langs);
        $last_lang = $lens - 1 === $lang_index;
        
        $entity = $this->readEntity($seed);
        if (gettype($entity) !== 'string') return $entity;
        $offset_from_text = hexdec(bin2hex(substr($entity, $offset_dic, $this->loiSize)));
        $texts_start_at = count($this->langs) * $this->loiSize;
        
        if ($last_lang) {
            return substr($entity, $texts_start_at + $offset_from_text);
        } else {
            $offset_dic_next = $offset_dic + $this->loiSize;
            $offset_from_text_next = hexdec(bin2hex(substr($entity, $offset_dic_next, $this->loiSize)));
            return substr($entity, $texts_start_at + $offset_from_text, $offset_from_text_next - $offset_from_text);
        }
    }
}
