# Composer tools

Some useful composer tools

## Content

- [Setup](#setup)
- [Recreate composer.json](#recreate-composerjson)

## Setup

Install with [Composer](https://getcomposer.org)

```sh
composer create-project mabar/composer-tools path/to/install
```

## Recreate composer.json

File `composer.json` can be recreated in case you don't have it but `vendor/composer/installed.json` is still available.

It cannot be recovered in its original form, but at least dependencies can be extracted.

All dependencies are put into `require` with the actual, specific version installed. Modify them to your needs manually.

`bin/recreate-composer-json path/to/project/vendor/composer/installed.json path/to/project/composer.json`
