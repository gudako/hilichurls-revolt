# What the hell is this?

Text below explains what is an indexed memory......

## Table of contents

- [Why indexed memory?](#why-indexed-memory)
  - [The Database Scenario: Int or String?](#the-database-scenario-int-or-string)
  - [Multi-lang Scenario: When things get bigger](#multi-lang-scenario-when-things-get-bigger)
- [Implement in the right way](#implement-in-the-right-way)
  - [Copy them for each connection?](#copy-them-for-each-connection)
  - [Shmop: Warriors don't fear session drop](#shmop-warriors-dont-fear-session-drop)
- [Intro to Indexed Memory](#intro-to-indexed-memory)
  - [Terms and abbreviations](#terms-and-abbreviations)
  - [Overview of Memory Structure](#overview-of-memory-structure)
  - [Fetching & Writing Data](#fetching--writing-data)
- [The Remaining: Exploration](#the-remaining-exploration)

## Why indexed memory?

Assume we have an **extensive** dictionary on the server. The dictionary is a bunch of key-value pairs. When a user knows a key and wants the value in the dictionary, the server must respond effectively.

Such a scenario is prevalent, as we will show it right now.

### The Database Scenario: Int or String?

Assume a player has two characters, "Texas" and "Lappland" in the game. We need to record the two character names to the database when he saves the game. We have two options:

1. Directly put two strings, "Texas" (5 bytes, UTF-8) and "Lappland" (8 bytes, UTF-8), to the database;
2. Give each character a code (an unsigned int) and put the code to the database.

The second way seems much more efficient. Assume we have a total of 40000 characters, then 2 bytes are enough to represent a character (because a 2-bytes unsigned int has a maximum value of 0xFFFF = 65535).

The size difference is not just a matter of "occupying more or less database space". It matters a lot when it comes to **Indexing** <sup>[(how?)](https://dba.stackexchange.com/questions/176318/mysql-large-table-indexes-recommended-length-of-index)</sup>.

Generally, we have an abstract class as the parent of all characters, named `Character`. Class `Texas` and `Lappland` extends `Character`.

Now we can add an abstract method, `getCode`. Things are like:

```php
abstract class Character
{
    public abstract function getCode():int;
    ......
}

final class Texas extends Character
{
    public function getCode():int{
        return 0x0000;
    }
    ......
}

final class Lappland extends Character
{
    public function getCode():int{
        return 0x0001;
    }
    ......
}
```

Assume array `$chars` contains the player's all characters. We can add them to the database easily, like:

```php
foreach ($chars as $char){
    Database::saveCharacter($char->getCode(), ...);
}
```

However, the problem is, when the data is loaded back, how can we recognize that `0x0000` is for `Texas` and `0x0001` is for `Lappland`?

Therefore, **we need a one-way dictionary** to give us the value `Texas` when we input the key `0x0000`. Then we can call:

```php
$ref_class = new ReflectionClass('Texas');
$char = $ref_class->newInstance(...);
......
```

To get the `Character` instance back from the database.

### Multi-lang Scenario: When things get bigger

Assume our website now supports five languages: English, Chinese Simplified, Russian, French, and Latin.
We want to output text on a webpage based on the user's language.

It turns out to be easy if we just:

```php
<p class="para">
    <?php
    echo match ($lang){
        'en' => 'Perhaps the world itself is just as insane. Lappland, '.
                'on the other hand, while appearing to be insane, is '.
                'just trying to keep her sanity and sobriety towards '.
                'this ridiculous world in her own way.',
        'zh' => '也许这个世界本就与疯狂别无二致。从另一方面来讲，尽管拉普兰德看上'.
                '去有些疯癫，但是她只是试图以她自己的方式保持她对这个荒谬世界的理'.
                '智和清醒罢了。',
        'ru' => 'Возможно, сам мир так же безумен. Лапландия, с другой '.
                'стороны, хотя и выглядит безумной, просто пытается '.
                'по-своему сохранить здравомыслие и трезвость по отношению '.
                'к этому нелепому миру.',
        'fr' => 'Peut-être que le monde lui-même est tout aussi fou. '.
                'Laponie, d\'autre part, tout en semblant folle, essaie '.
                'juste de garder sa raison et sa sobriété envers ce monde '.
                'ridicule à sa manière.',
        'lat'=> 'Forsitan ipse mundus tantum insanit. Lapplandus vero, dum '.
                'insanire videtur, suam sanitatem et sobrietatem in ridiculo '.
                'mundo suo modo servare conatur.',
        default => trigger_error("Undefined language \"$lang\"", E_USER_WARNING)
    }
    ?>
</p>
......
```

However, it makes the page file not only large but also hard to read. If a typo is found during a debug session, it can be hard to fix because we may need to search through all these pages to make a change. (Yes, indeed, we do **CTRL+SHIFT+R** in Intellij IDEA)

Things become even more troublesome on a non-static webpage, where the Classes are left to deal with the abundance, at the expense of our PHP code quality and readability - Because we will have large bunches of texts **here and there**.

What if we can do:

```php
<p class="para">
    <?php echo get_text('mainpage_text_1');?>
</p>
......
```

And put all the texts into one big JSON file like:

```json
{
  "mainpage_text_1": {
    "en": "Perhaps the world itself is just as insane. Lappland, on the other hand, while appearing to be insane, is just trying to keep her sanity and sobriety towards this ridiculous world in her own way.",
    "zh": "也许这个世界本就与疯狂别无二致。从另一方面来讲，尽管拉普兰德看上去有些疯癫，但是她只是试图以她自己的方式保持她对这个荒谬世界的理智和清醒罢了。",
    "ru": "Возможно, сам мир так же безумен. Лапландия, с другой стороны, хотя и выглядит безумной, просто пытается по-своему сохранить здравомыслие и трезвость по отношению к этому нелепому миру.",
    "fr": "Peut-être que le monde lui-même est tout aussi fou. Laponie, d'autre part, tout en semblant folle, essaie juste de garder sa raison et sa sobriété envers ce monde ridicule à sa manière.",
    "lat": "Forsitan ipse mundus tantum insanit. Lapplandus vero, dum insanire videtur, suam sanitatem et sobrietatem in ridiculo mundo suo modo servare conatur."
  },
  "......": "......"
}
```

This way, the codes are brief and neat without being entangled by such bunches of texts. The texts are all moved to one place: the JSON file.

Now, all we need is a one-way dictionary.

## Implement in the right way

Now we have been given the necessaries to implement a one-way dictionary. How to implement it? It becomes a question.

Perhaps nothing is more straightforward than putting the extensive contents into a JSON file, as we described in the [Multi-lang Scenario](#multi-lang-scenario-when-things-get-bigger). We will begin with that.

### Copy them for each connection?

The first and the most straightforward way that comes to our mind should be this:

```php
class LanguageTextData
{
    public static array $everything;
    
    private static function __constructStatic(){
        $contents = file_get_contents('link/to/the/big/json/file');
        self::$everything = json_decode($contents, true);
    }
}
```

Now just access `LanguageTextData::$everything` to get everything we want! It seems incredible because we only need to decode the JSON and put the data into a static or global array. Such an awesome easy thing, right? **No, not at all.**

This is **HELLISH**. Because **for each client-server connection**, the JSON data is fetched and stored. It means if we have 1000 connections simultaneously, the server memory is loaded with 1000 copies of the same data. Moreover, the data is typically **huge** (e.g., the multi-language JSON file). The server will soon run out of memory and be knocked off.

### Shmop: Warriors do not fear session drop

So we are looking for something that can stay alive between sessions. This is how [Shmop - Shared Memory](https://www.php.net/manual/en/book.shmop.php) caught our sight.

Each Shmop has an int as a key. Once we create a Shmop memory, **we can access it later as long as we remember the key**. Meanwhile, as long as our server is not shut down or rebooted, they **continue to exist** until we manually delete them (by using `shmop_delete`).

Shmop, the **shared memory**, shares a **persistent** memory segment to all connections. When we read data from shared memory (by using `shmop_read`), we will need to specify **which part** we read by specifying the offset and size. Therefore, we get what we exactly need, without the mess of needing to copy the entire big thing before a read operation to just a tiny part of it. This is our key to solving the problem.

However, with Shmop, we have to deal with binary data on ourselves. Even though PHP's built-in array is a beautiful one-way direction already, it is impossible to pass an array to the `shmop_write` function.

Therefore, we need to build a one-way dictionary for ourselves.

## Intro to Indexed Memory

The class `IndexedMemory` encapsulates an **indexed** Shmop memory segment. The term **indexed** means the memory works as a one-way dictionary like the PHP built-in array. We can add key-value pairs to the memory like how we do with an array. We can get the value by supplying the key.

It differs from PHP array because the key and value must be binary data (A string is also binary data, supposed we encode them all in UTF-8). In practice, the key is usually a readable string like "mainpage_text_1" or "value_1".

### Terms and abbreviations

The following abbreviations may be used in the texts, method comments, or property comments:

| Abbr.  | Actual Term          |
|--------|----------------------|
| htprop | Hashtable properties |
| htx    | Hashtable context    |
| ht     | Hashtable            |
| bkt    | Bucket               |

### Overview of Memory Structure

An indexed Shmop memory is structured as below:

![](/img/img1.jpeg)

### Fetching & Writing Data

The graph below shows the process of writing new data to the memory:

![](/img/img2.jpeg)

To clarify, we made this text as a developer's note on how this "indexed memory" works. We print the outline and will not cover all the topics here when the graph above is enough to bring you back to the spot.

Topics not covered here:

- Hash collision
- Reading data by seed
- Multiple buckets can point to a single entity

For more details about what a hashtable is and how a hashtable is implemented, [click here](https://en.wikipedia.org/wiki/Hash_table).

## The Remaining: Exploration

The README is made because it may seem complicated to understand the class and its underlying memory without a bit of cutting-in. But this is not documentation, so we got the point, and that's OK.

You'll explore the remaining on yourself, it's not so hard now :)
