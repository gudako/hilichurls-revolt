<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

/**
 * @deprecated
 * Represents a rewriter to rewrite files line by line by regex replacement.
 */
final class TextcodeRewriter
{
    private readonly string $pattern, $replacement;
    private array $textcodes = [];
    private string $objCode = '';
    private bool $executed = false;

    /**
     * Creates a new rewriter with patterns that will be used in {@link preg_replace}.<br>
     * <i><b>NOTICE: </b>Constructing with erratic pattern(s) or replacement pattern(s) will not immediately report error,
     * but running {@link executeOn} on such objects will result in failure.</i>
     * @param string $pattern <p>
     * [optional] The replacement pattern. Make sure a capture group is named '<i>textcode</i>' (without apostrophes)
     * which is the textcode to be scanned. String '<i>ccc_obj_code</i>' (without apostrophes) will be replaced to the object code
     * which can be set in {@link setObjCode} (default is empty string).
     * </p>
     * @param string $replacement <p>
     * [optional] The pattern to be replaced with. Make sure a string '<i>ccc_context</i>' (without apostrophes) is included,
     * which is the textcode context.
     * </p>
     * @see https://www.php.net/manual/en/function.preg-replace.php
     */
    public function __construct(string $pattern=/**@lang RegExp*/'/(\d+, *\d+|\d+|) *(?<save>\/\* *ccc_obj_code%(?<textcode>[a-z\d][a-z\d_]+[a-z\d?!]) *\*\/)/',
                                string $replacement='ccc_context$save'){
        $this->pattern = $pattern;
        $this->replacement = $replacement;
    }

    /**
     * Set the '<i>ccc_obj_code</i>' special variable in patterns. Can only be set once, no overwriting allowed.
     * @param string $obj_code A <b>string</b> to set.
     * @return bool Returns <b>true</b> on success and <b>false</b> if it's already set or empty string is passed as argument.
     */
    public function setObjCode(string $obj_code):bool {
        $trimmed = trim($obj_code);
        if ($trimmed === '' || $this->objCode !== '') return false;
        $this->objCode = $trimmed;
        return true;
    }

    /**
     * Adds a new textcode to the rewriter.
     * @param string $textcode The textcode.
     * @param string $meaning The meaning of the textcode.
     */
    public function addTextcode(string $textcode, string $meaning):void {
        $this->textcodes[$textcode] = $meaning;
    }

    /**
     * The procedure traverses in files that match conditions, <u>line by line</u> do the replacement for each textcode.
     * For security measures, each <b>TextcodeRewriter</b> can only be executed once, <u>whether successful or not</u>. Second or more
     * executions will result in failure.<br>
     * <i><b>NOTICE: </b>If an error happens on a single dir or file (e.g. denied access), an <b>E_WARNING</b> or
     * <b>E_USER_WARNING</b> is thrown, but does not stop the procedure from running on other dirs&files.</i>
     * @param array $dir <p>
     * [optional] The directories in which files are being rewritten. The path(s) must be relative to the document root,
     * with a backslash at beginning and no backslash at ending.
     * </p>
     * @param string $file_pattern [optional] The regex file pattern to be matched. Only matched file are rewritten.
     * @return array|false <p>
     * Returns <b>false</b> on failure, or an array of replacement details on success. The array items are arrays whose
     * first item is a file path (relative to document root) and second item is an <b>int</b> indicating how many replacements
     * are made in this file.
     * </p>
     */
    public function executeOn(array $dir=['/scripts'], string $file_pattern='/.*\.php$/i'):array|false {
        if ($this->executed) {
            trigger_error('Already executed this rewrite', E_USER_WARNING);
            return false;
        }
        $this->executed = true;

        if (preg_match($file_pattern,'') === false) return false;
        if (preg_match($this->pattern,'') === false) return false;
        $pat = str_replace('ccc_obj_code', $this->objCode, $this->pattern);
        if (!str_contains($this->replacement, 'ccc_context')) {
            trigger_error('Not found ccc_context in replacement', E_USER_WARNING);
        }
        $res = [];
        $alert_textcode = true;

        $exec_on = function ($dir) use (&$exec_on, &$alert_textcode, &$res, $pat, $file_pattern) {
            $paths = scandir($_SERVER['DOCUMENT_ROOT'].$dir);
            if ($paths === false) return;
            foreach ($paths as $path) {
                if (in_array(basename($path), ['.', '..'])) continue;
                $path = $_SERVER['DOCUMENT_ROOT'].$dir.'/'.$path;
                if (is_dir($path)) {
                    $exec_on($path);
                } else {
                    if (!preg_match($file_pattern,basename($path))) continue;
                    $lines = file($path);
                    if ($lines===false)continue;

                    $rep_count = 0;
                    for ($i=0;$i<=count($lines)-1;$i++) {
                        $lines[$i] = preg_replace_callback($pat, function (array $matches)use(&$alert_textcode):string{
                            $search = [];
                            $replace = [];
                            if (!isset($matches['textcode'])) {
                                if ($alert_textcode) {
                                    trigger_error('No textcode replacement set',E_USER_WARNING);
                                    $alert_textcode=false;
                                }
                            } elseif (!isset($this->textcodes[$matches['textcode']])) {
                                trigger_error("Cannot find context for textcode \"{$matches['textcode']}\"", E_USER_WARNING);
                            } else {
                                $search []= 'ccc_context';
                                $replace []= $this->textcodes[$matches['textcode']];
                            }
                            foreach ($matches as $key=>$val) {
                                $search []= '$'.$key;
                                $replace []= $val;
                            }
                            return str_replace($search, $replace, $this->replacement);
                        }, $lines[$i], -1, $rep_count);
                    }
                    if (file_put_contents($path, implode('', $lines)) === false) continue;
                    $res []= [$path, $rep_count];
                }
            }
        };
        foreach ($dir as $val) {
            $exec_on($val);
        }
        return $res;
    }
}
