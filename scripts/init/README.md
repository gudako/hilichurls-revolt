## Initialization Description

### Requirement to complete

- Database is configured properly.
- The main Shmop has proper structure.
- All indexed memories are properly initialized.

### Session variables

These variables can be used throughout the global space.

| Name                  | Type                | Description                                |
|-----------------------|---------------------|--------------------------------------------|
| $_SESSION["db_con"]   | \mysqli             | A database connection to the game's schema |
| $_SESSION["mem"]      | \Shmop              | The main Shmop memory.                     |
| $_SESSION["lang"]     | LanguageBook   | A **LanguageBook** object.                 |
| $_SESSION["cate1"]    | TrophyCategory | The first trophy category.                 |
| $_SESSION["char_ref"] | IndexedMemory  |                                            |
| $_SESSION["item_ref"] | IndexedMemory  |                                            |
| $_SESSION["stat_ref"] | IndexedMemory  |                                            |
