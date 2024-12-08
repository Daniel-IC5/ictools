# Invision Community 5 phpStorm DevTools Suite

This file will create phpStorm [Metadata](https://www.jetbrains.com/help/phpstorm/ide-advanced-metadata.html) files, providing autocompletion support for Invision Community 5
* application names
* language strings
* extensions
* database tables
* settings
* [exitpoints](https://www.jetbrains.com/help/phpstorm/ide-advanced-metadata.html#define-exit-points)

It also creates a file with all known constants, so that the IDE doesn't complain about unknown constants.

![phpstorm autocomplete](docs/demo.gif)


## usage
`php ipstools.php PATH_TO_IC5`
This can also be combined with [File Watchers](https://www.jetbrains.com/help/phpstorm/using-file-watchers.html) to rebuild the files automatically.
