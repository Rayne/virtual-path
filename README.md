# Rayne\VirtualPath

The `VirtualPath` library normalises paths and
prevents directory traversal attacks
without querying a file system.

[![Latest Stable Version](https://poser.pugx.org/rayne/virtual-path/v/stable)](https://packagist.org/packages/rayne/virtual-path)
[![Code Coverage](https://scrutinizer-ci.com/g/rayne/virtual-path/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/rayne/virtual-path/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rayne/virtual-path/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rayne/virtual-path/?branch=master)
[![License](https://poser.pugx.org/rayne/virtual-path/license)](https://packagist.org/packages/rayne/virtual-path)

## Contents

* [Installation](#installation)
* [Dependencies](#dependencies)
* [Usage](#usage)
* [Examples](#examples)
* [Implementation Details](#implementation-details)
* [Tests](#tests)

## Installation

It's recommended to use the dependency manager
[Composer](https://getcomposer.org/download)
to install `rayne/virtual-path`.

```bash
composer require rayne/virtual-path
```

## Dependencies

* PHP 5.6 or better

## Usage

The `VirtualPath` class normalises inputs to absolute virtual paths
without querying any file system.
It also detects and flags directory traversal attacks.

The `JailedPath` class utilises `VirtualPath` to build safe paths
which can be used for working with real files.
The normalisation is done relative to a `jail` called path
which is used as virtual root for any path entered by the user.
As `JailedPath` does not query the file system
it's suited for working with local, remote or fictional paths.

Please read the [Implementation Details](#implementation-details) section for more details.

**TL;DR** *Use the `JailedPath` class when in doubt.*

## Examples

### `JailedPath`

In this example website visitors are allowed to download any file
from the local directory `/test`
by specifying the relative path as `GET` parameter.
To prevent users from leaving the directory with directory traversal attacks,
`JailedPath` is used with `/test` as the virtual root directory.

```php
<?php

use Rayne\VirtualPath\JailedPath;

$jailedPath = new JailedPath('/test', $_GET['path'] ?? '');

if ($jailedPath->hasJailbreakAttempt()) {
    // Log jailbreak attempt, ban user, …
    return;
}

if (is_file($jailedPath->getAbsolutePath())) {
    @readfile($jailedPath->getAbsolutePath());
}
```

The following table shows how user defined paths are normalised
and how they are interpreted relative to the virtual root.

User Input       | `hasJailbreakAttempt()` | `getAbsolutePath()` | `getRelativePath()`
-----------------|:-----------------------:|---------------------|---
Empty String     | `false`                 | `/test`             | Empty String
`.`              | `false`                 | `/test`             | Empty String
`a.png/../b.png` | `false`                 | `/test/b.png`       | `b.png`
`/a/./b`         | `false`                 | `/test/a/b`         | `a/b`
`..`             | `true`                  | `/test`             | Empty String
`../example`     | `true`                  | `/test/example`     | `example`
`../etc/passwd`  | `true`                  | `/test/etc/passwd`  | `etc/passwd`
Array            | `true`                  | `/test`             | Empty String

### `VirtualPath`

If a fixed prefix or the sugar coating of `JailedPath` isn't required,
then `VirtualPath` is sufficient as it is the class used for normalising paths.
`VirtualPath` normalises the input and provides a trusted
(normalised, with a leading `/`)
and an untrusted
(a string representation of the probably malicious user input)
path.

The previous example can be easily recreated with `VirtualPath`
when the instance of `VirtualPath` (which is `(string)` cast-able)
is appended to the virtual root directory.

```php
<?php

use Rayne\VirtualPath\VirtualPath;

$path = new VirtualPath($_GET['path'] ?? '');
$absolutePath = '/test' . $path;
```

Depending on the usage scenario it's sometimes useful to work with the
normalised trusted path even if the original input is not trustworthy,
e.g. when explicitly supporting relative paths
and giving the user the benefit of doubt when *accidentally*
trying to access files outside of the virtual path.

**Note**: `VirtualPath` returns the normalised path with a leading `/`.
When working with files it's recommended to add a trusted path as prefix
(see code example in the current section)
as otherwise files relative to the file system's root would be referenced.
*To not forget to add the prefix use the `JailedPath` class instead when working with real files.*

Input                 |  `isTrusted()` |  `getTrustedPath()` | `getUntrustedPath()`
----------------------|:--------------:|---------------------|-------------------
Array                 | `false`        | `/`                 | Empty String
Empty String          | `true`         | `/`                 | Empty String
`../articles`         | `false`        | `/articles`         | `../articles`
`tags/../../articles` | `false`        | `/articles`         | `tags/../../articles`
`tags/../articles`    | `true`         | `/articles`         | `tags/../articles`
`../etc/passwd`       | `false`        | `/etc/passwd`       | `../etc/passwd`
`/etc/passwd`         | `true`         | `/etc/passwd`       | `/etc/passwd`
`etc/passwd`          | `true`         | `/etc/passwd`       | `etc/passwd`

## Implementation Details

Using a pure virtual normalised path has different benefits:

* Path normalisation is done without querying a file system

* It's impossible to forge timing attacks for files
  outside of the scope of the virtual path

* No complex comparisons are required to limit directory traversals
  to a specific directory and its children

* Only `.`, `..`, `\\` (normalised to `/`) and `/` are interpreted for path normalisation

* No unexpected and information leaking `~` expansions as seen in other libraries

The implementation of `VirtualPath` does not interpret,
alter or remove control characters and Unicode:

* Directory and file paths are allowed to contain control characters on some systems

* Removing control characters is out of scope for the library

## Tests

1. Clone the repository

   ```bash
   git clone https://github.com/rayne/virtual-path.git
   ```

2. Install the development dependencies

   ```bash
   composer install --dev
   ```

3. Run the tests

   ```bash
   composer test
   ```
