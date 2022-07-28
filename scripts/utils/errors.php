<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

/**
 * Gets the last error wrapped into an {@link ErrorException}.
 * @return ErrorException|null Returns the <b>ErrorException</b> of the last error exists, otherwise returns <b>null</b>.
 */
function error_wrap_last():?ErrorException {
    $last_err = error_get_last();
    if ($last_err === null) return null;
    error_clear_last();
    return new ErrorException($last_err['message'], 0, $last_err['type'], $last_err['file'], $last_err['line']);
}

/**
 * Gets a clear stack trace in readable format.
 * @param Throwable $ex An exception to read.
 * @return string <p>
 * Returns the stack trace.<br>
 * <i><b>NOTICE: </b>The returned string ends with a linebreak.</i>
 * </p>
 */
function get_stack_trace_v2(Throwable $ex):string {
    $rtn = '';
    $count = 0;
    foreach ($ex->getTrace() as $frame) {
        $args = '';
        if (isset($frame['args'])) {
            $args = [];
            foreach ($frame['args'] as $arg) {
                if (is_string($arg)) $args []= "'$arg'";
                elseif (is_array($arg)) $args []= 'Array';
                elseif (is_null($arg)) $args []= 'NULL';
                elseif (is_bool($arg)) $args []= ($arg) ? 'true' : 'false';
                elseif (is_object($arg)) $args []= get_class($arg);
                elseif (is_resource($arg)) $args []= get_resource_type($arg);
                else $args[] = $arg;
            }
            $args = join(', ', $args);
        }
        $rtn .= sprintf("#%s %s(%s): %s%s%s(%s)\r\n", $count, $frame['file'], $frame['line'],
            $frame['class'] ?? '', $frame['type'] ?? '', $frame['function'], $args);
        $count++;
    }
    return $rtn;
}
