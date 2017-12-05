# AlhamesDbBundle

Symfony Bundle for MySQL/MariaDB Database

Installation
============

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require alhames/db-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require alhames/db-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new \Alhames\DbBundle\AlhamesDbBundle(),
        );

        // ...
    }

    // ...
}
```

Configuration
=============

Full Default Configuration
--------------------------

```yaml
alhames_db:
  default_connection: 'default'
  default_database: ~
  cache: ~ # null or service name
  logger: ~ # null, false or service name
  query_formatter: ~ # null or service name
  
  connections:

    # Prototype
    name:
      host: '127.0.0.1'
      username: 'root'
      password: ''
      database: ~
      port: 3306
      charset: 'utf8mb4'
  
  tables:

    # Prototype
    name:
      table: ~
      database: ~
      connection: ~
```
