DROP DATABASE hilichurls_revolt;
CREATE DATABASE IF NOT EXISTS hilichurls_revolt;
USE hilichurls_revolt;

CREATE TABLE account_data
(
    `id`              MEDIUMINT UNSIGNED    NOT NULL    KEY       AUTO_INCREMENT,
    `username`        VARCHAR(20)           NOT NULL    UNIQUE    COLLATE utf8_unicode_520_ci,
    `password_sha1`   BINARY(20)            NOT NULL

) ENGINE = InnoDB;

CREATE TABLE trophy_data
(
    `id`            MEDIUMINT UNSIGNED    NOT NULL    KEY AUTO_INCREMENT,
    `user_id`       MEDIUMINT UNSIGNED    NOT NULL,
    `trophy_rd`     SMALLINT UNSIGNED     NOT NULL,
    
    UNIQUE         (`user_id`, `trophy_rd`),
    FOREIGN KEY    (`user_id`) REFERENCES account_data(`id`)
) ENGINE = InnoDB;

CREATE TABLE item_data
(
    `id`            MEDIUMINT UNSIGNED    NOT NULL    KEY AUTO_INCREMENT,
    `user_id`       MEDIUMINT UNSIGNED    NOT NULL,
    `item_rd`       SMALLINT UNSIGNED     NOT NULL,
    `item_count`    MEDIUMINT UNSIGNED    NOT NULL    DEFAULT 1,
    
    UNIQUE         (`user_id`, `item_rd`),
    FOREIGN KEY    (`user_id`) REFERENCES account_data(`id`)
) ENGINE = InnoDB;

CREATE TABLE character_data
(
    `id`              MEDIUMINT UNSIGNED    NOT NULL    KEY AUTO_INCREMENT,
    `user_id`         MEDIUMINT UNSIGNED    NOT NULL,
    `character_rd`    SMALLINT UNSIGNED     NOT NULL,
    `stat_level`      TINYINT UNSIGNED      NOT NULL    DEFAULT 1,
    `stat_hp`         MEDIUMINT UNSIGNED    NOT NULL,
    
    UNIQUE         (`user_id`, `character_rd`),
    FOREIGN KEY    (`user_id`) REFERENCES account_data(`id`)
) ENGINE = InnoDB;

CREATE TABLE error_log_data
(
    `id`                MEDIUMINT UNSIGNED    NOT NULL    KEY AUTO_INCREMENT,
    `user_id`           MEDIUMINT UNSIGNED    NULL,
    `remote_addr`       VARBINARY(16)         NOT NULL,
    `error_type`        SMALLINT UNSIGNED     NULL,
    `error_detail`      TEXT                  NOT NULL,
    `first_log_time`    DATETIME              NOT NULL,
    `last_log_time`     DATETIME              NULL        DEFAULT NULL,
    `logs_count`        SMALLINT UNSIGNED     NOT NULL    DEFAULT 1,

    KEY            (`user_id`, `remote_addr`),
    KEY            (`last_log_time`, `user_id`),
    FOREIGN KEY    (`user_id`) REFERENCES account_data(`id`)
) ENGINE = InnoDB;

CREATE TABLE user_log_data
(
    `id`             MEDIUMINT UNSIGNED    NOT NULL                    KEY AUTO_INCREMENT,
    `user_id`        MEDIUMINT UNSIGNED    NOT NULL,
    `message_type`   VARCHAR(20)           NOT NULL,
    `message`        TEXT                  NULL        DEFAULT NULL    COLLATE utf8_unicode_520_ci,
    `remote_addr`    VARBINARY(16)         NOT NULL,
    `significance`   TINYINT UNSIGNED      NOT NULL    DEFAULT 0,
    `log_time`       DATETIME              NOT NULL,

    KEY            (`user_id`, `log_time`, `significance`),
    KEY            (`user_id`, `remote_addr`),
    FOREIGN KEY    (`user_id`) REFERENCES account_data(`id`)
) ENGINE = InnoDB;

CREATE TABLE php_sessions(
    `session_id`         VARCHAR(255)    NOT NULL    COLLATE utf8_unicode_520_ci KEY,
    `session_expires`    DATETIME        NOT NULL,
    `session_data`       TEXT            NULL        COLLATE utf8_unicode_520_ci
) ENGINE = InnoDB;
