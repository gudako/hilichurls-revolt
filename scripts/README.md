# Sorting things in class

This is more like a personal preference than some fixed rules. However, to make things consistent, we specify the "rules" below.

## Table of contents

- [Precedence](#precedence)
  - [Properties](#properties)
  - [Methods](#methods)
- [Comments](#comments)

## Precedence

Properties are always above methods.

| 1st      |
|----------|
| property |
| method   |

### Properties

Below is the precedence table of properties:

| 2nd       | 3rd      | 4th                 | 5th<sup>*</sup> |
|-----------|----------|---------------------|-----------------|
| private   | const    | without initializer | shorter name    |
| protected | static   | with initializer    | longer name     |
| public    | readonly |                     |                 |
|           | normal   |                     |                 |

<sup>*</sup>5th is not mandated.

### Methods

Below is the precedence table of methods:

| 2nd                                                                                   | 3rd       | 4th      | 5th<sup>*</sup>   |
|---------------------------------------------------------------------------------------|-----------|----------|-------------------|
| [__construct](https://www.php.net/manual/en/language.oop5.decon.php#object.construct) | private   | static   | shorter signature |
| [__constructStatic](https://github.com/vladimmi/construct-static)                     | protected | abstract | longer signature  |
| [other magic methods](https://www.php.net/manual/en/language.oop5.magic.php)          | public    | normal   |                   |

<sup>*</sup>5th is not mandated.

## Comments

We put comments in a class that denote things as the followings:

| Comment                             | Comment it before                                                                     | Optional |
|-------------------------------------|---------------------------------------------------------------------------------------|----------|
| `// PRIVATE PROPERTIES`             | private properties                                                                    | YES      |
| `// PROTECTED PROPERTIES`           | protected properties                                                                  | YES      |
| `// PRIVATE PROPERTIES`             | private properties                                                                    | YES      |
| `// CONSTRUCTORS AND MAGIC METHODS` | [__construct](https://www.php.net/manual/en/language.oop5.decon.php#object.construct) | NO       |
| `// PRIVATE METHODS`                | private methods                                                                       | YES      |
| `// PROTECTED METHODS`              | protected methods                                                                     | YES      |
| `// PUBLIC METHODS`                 | public methods                                                                        | YES      |
