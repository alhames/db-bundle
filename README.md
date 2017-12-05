# AlhamesDbBundle

Symfony Bundle for MySQL/MariaDB Database

```yaml
alhames_db:
  default_connection: 'default'
  default_database: ~
  cache: ~
  logger: ~ # can be null, false or service name
  query_formatter: ~
  
  connections:

    default:
      host: '127.0.0.1'
      username: 'root'
      password: ''
      database: ~
      port: 3306
      charset: 'utf8mb4'
  
  tables:

    example:
      table: 'example_table'
      database: 'example_db'
      connection: 'default'
```
