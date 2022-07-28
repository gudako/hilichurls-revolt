<?php

/**
 * @inheritDoc
 */
final class DbSessionHandler implements SessionHandlerInterface
{
    private mysqli $link;

    /** @inheritDoc */
    public function open(string $path, string $name):bool {
        $link = Database::init('session', null, conf('db_username'), conf('db_password'), conf('db_schema'));
        if ($link === false) return false;
        $this->link = $link;
        return true;
    }

    /** @inheritDoc */
    public function read(string $id):string|false {
        $result = $this->link->query("SELECT session_data FROM php_sessions WHERE session_id = '{$id}' AND ".
            "session_expires > '".date('Y-m-d H:i:s')."'");
        if ($result === false) return false;
        $row = $result->fetch_assoc()['session_data'];
        if ($row === false) return false;
        elseif ($row === null) {
            $this->write($id, '');
            return '';
        }
        return $row;
    }

    /** @inheritDoc */
    public function write(string $id, string $data):bool {
        $conf_lifespan = conf('session_lifespan');
        $exp = (new DateTime())->add(new DateInterval("PT{$conf_lifespan}S"));
        $format_exp = $exp->format('Y-m-d H:i:s');
        $result = $this->link->query("REPLACE INTO php_sessions SET session_id = '{$id}', session_expires = '{$format_exp}', ".
            "session_data = '{$data}'");
        return $result !== false;
    }

    /** @inheritDoc */
    public function close():bool {
        return $this->link->close();
    }

    /** @inheritDoc */
    public function destroy(string $id):bool {
        return $this->link->query("DELETE FROM php_sessions WHERE session_id = '{$id}'") !== false;
    }

    /** @inheritDoc */
    public function gc(int $max_lifetime):int|false {
        $now = time();
        return $this->link->query("DELETE FROM php_sessions WHERE (UNIX_TIMESTAMP(session_expires) + {$max_lifetime} < {$now})") !== false;
    }
}
