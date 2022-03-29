## Examples of usage

### Table `my_database.my_table`

|id |key|value|
|---|---|-----|
|1  |a  |10   |
|2  |b  |11   |
|3  |c  |20   |
|4  |c  |30   |

---

```php
$query = $container->get('alhames_db.manager')->db('my_table')
    ->select();

$query->getAlias(); // my_table
$query->getTable(); // `my_database`.`my_table`
$query->getMethod(); // select
$query->getQuery(); // SELECT * FROM `my_database`.`my_table`
```

---

```mysql
SELECT *
FROM `my_database`.`my_table`;
```

```php
$result1 = $container->get('alhames_db.manager')->db('my_table')
    ->select()
    ->getRows();
// or
$result1 = $container->get('alhames_db.manager')->db('my_table')
    ->select('*')
    ->getRows();
// or
$result1 = $container->get('alhames_db.manager')->db('my_table')
    ->select(null)
    ->getRows();
// $result1 = [
//     ['id' => '1', 'key' => 'a', 'value' => '10'],
//     ['id' => '2', 'key' => 'b', 'value' => '11'],
//     ['id' => '3', 'key' => 'c', 'value' => '20'],
//     ['id' => '4', 'key' => 'c', 'value' => '30'],
// ];

$result2 = $container->get('alhames_db.manager')->db('my_table')
    ->select()
    ->getRows('id');
// $result2 = [
//     1 => ['id' => '1', 'key' => 'a', 'value' => '10'],
//     2 => ['id' => '2', 'key' => 'b', 'value' => '11'],
//     3 => ['id' => '3', 'key' => 'c', 'value' => '20'],
//     4 => ['id' => '4', 'key' => 'c', 'value' => '30'],
// ];

$result3 = $container->get('alhames_db.manager')->db('my_table')
    ->select()
    ->getRows('key', 'value');
// $result3 = [
//     'a' => '10',
//     'b' => '11',
//     'c' => '30',
// ];

$result4 = $container->get('alhames_db.manager')->db('my_table')
    ->select()
    ->getRows(null, 'value');
// $result4 = ['10', '11', '20', '30'];

$result5 = $container->get('alhames_db.manager')->db('my_table')
    ->select()
    ->getRows('key', 'value', true);
// $result5 = [
//     'a' => ['10'],
//     'b' => ['11'],
//     'c' => ['20', '30'],
// ];

$result6 = $container->get('alhames_db.manager')->db('my_table')
    ->select()
    ->getRows('key', null, true);
// $result6 = [
//     'a' => [
//         ['id' => '1', 'key' => 'a', 'value' => '10'],
//     ],
//     'b' => [
//         ['id' => '2', 'key' => 'b', 'value' => '11'],
//     ],
//     'c' => [
//         ['id' => '3', 'key' => 'c', 'value' => '20'],
//         ['id' => '4', 'key' => 'c', 'value' => '30'],
//     ],
// ];
```

---

```mysql
SELECT `id`
FROM `my_database`.`my_table`;
```

```php
$result = $container->get('alhames_db.manager')->db('my_table')
    ->select('id')
    ->getRows();
// or
$result = $container->get('alhames_db.manager')->db('my_table')
    ->select(['id'])
    ->getRows();
// $result = [
//     ['id' => '1'],
//     ['id' => '2'],
//     ['id' => '3'],
//     ['id' => '4'],
// ];
```

---

```mysql
SELECT `id`, `key`
FROM `my_database`.`my_table`
LIMIT 1
OFFSET 2;
```

```php
$result1 = $container->get('alhames_db.manager')->db('my_table')
    ->select(['id', 'key'])
    ->limit(1)
    ->offset(2)
    ->getRows();
// $result1 = [
//     ['id' => '3', 'key' => 'c'],
// ];

$result2 = $container->get('alhames_db.manager')->db('my_table')
    ->select(['id', 'key'])
    ->limit(1)
    ->offset(2)
    ->getRow();
// $result2 = ['id' => '3', 'key' => 'c'];

$result3 = $container->get('alhames_db.manager')->db('my_table')
    ->select(['id', 'key'])
    ->limit(1)
    ->offset(2)
    ->getRow('id');
// $result3 = '3';
```

---

```mysql
SELECT SQL_CALC_FOUND_ROWS `value`
FROM `my_database`.`my_table`
LIMIT 2;

SELECT FOUND_ROWS();
```

```php
use \Alhames\DbBundle\Db\Db;

/** @var \Alhames\DbBundle\Db\DbQuery $query */
$query = $container->get('alhames_db.manager')->db('my_table')
    ->select('value', Db::CALC_FOUND_ROWS)
    ->limit(2);
$result = $query->getRows(null, 'value');
$count = $query->getRowCount();
// $result = ['10', '11'];
// $count = 4;
```

---

```mysql
SELECT `id`
FROM `my_database`.`my_table`
WHERE `key` = 'c';
```

```php
$result = $container->get('alhames_db.manager')->db('my_table')
    ->select(['id'])
    ->where(['key' => 'c'])
    ->getRows(null, 'id');
// or
$result = $container->get('alhames_db.manager')->db('my_table')
    ->select(['id'])
    ->where(null, "`key` = 'c'")
    ->getRows(null, 'id');
// $result = ['3', '4'];
```

---

```mysql
SELECT `id`
FROM `my_database`.`my_table`
WHERE `key` != 'c' AND `id` > 1;
```

```php
use \Alhames\DbBundle\Db\Db;

$result = $container->get('alhames_db.manager')->db('my_table')
    ->select(['id'])
    ->where(['key' => Db::not('c'), 'id' => Db::more(1)])
    ->getRows(null, 'id');
// $result = ['2'];
```

---

```mysql
SELECT `id`
FROM `my_database`.`my_table`
WHERE `value` BETWEEN 10 AND 20
    AND `key` IN('b', 'c');
```

```php
use \Alhames\DbBundle\Db\Db;

$result = $container->get('alhames_db.manager')->db('my_table')
    ->select(['id'])
    ->where(['value' => Db::between(10, 20), 'key' => ['b', 'c']])
    ->getRows(null, 'id');
// $result = ['2', '3'];
```

---

```mysql
SELECT `id`
FROM `my_database`.`my_table`
ORDER BY `key` DESC, `id` ASC;
```

```php
$result = $container->get('alhames_db.manager')->db('my_table')
    ->select(['id'])
    ->orderBy(['value' => 'DESC'])
    ->getRows(null, 'id');
// $result = ['3', '4', '2', '1'];
```

---

```mysql
SELECT `key` AS `name`, COUNT(*) AS `count`
FROM `my_database`.`my_table`
GROUP BY `key`
HAVING `count` > 1;
```

```php
use \Alhames\DbBundle\Db\Db;

$result = $container->get('alhames_db.manager')->db('my_table')
    ->select(['key' => 'name', 'COUNT(*)' => 'count'])
    ->groupBy(['key'])
    ->having(['count' => Db::more(1)])
    ->getRows('name', 'count');
// $result = ['c' => 2];
```

---

```mysql
SELECT self.id
FROM `my_database`.`my_table` AS self
JOIN `my_database`.`your_table` AS y ON self.value = y.id;
```

```php
use \Alhames\DbBundle\Db\Db;

$result = $container->get('alhames_db.manager')->db('my_table')
    ->select(['self.id'])
    ->join('your_table', 'y', ['self.value' => Db::field('y.id')])
    ->getRows(null, 'id');
```

---

```mysql
SELECT self.id
FROM `my_database`.`my_table` AS self
LEFT JOIN `my_database`.`your_table` AS y ON self.value = y.id;
```

```php
use \Alhames\DbBundle\Db\Db;

$result = $container->get('alhames_db.manager')->db('my_table')
    ->select(['self.id'])
    ->join('your_table', 'y', ['self.value' => Db::field('y.id')], 'LEFT')
    ->getRows(null, 'id');
```

---

```mysql
INSERT INTO `my_database`.`my_table`
SET `key` = "d", `value` = 40;
```

```php
$container->get('alhames_db.manager')->db('my_table')
    ->insert(['key' => 'd', 'value' => 40])
    ->exec();
// or
$id = $container->get('alhames_db.manager')->db('my_table')
          ->insert(['key' => 'd', 'value' => 40])
          ->getInsertId();
```

---

```mysql
INSERT INTO `my_database`.`my_table`
SET `key` = "d", `value` = 40
ON DUPLICATE KEY UPDATE `key` = "e";
```

```php
$container->get('alhames_db.manager')->db('my_table')
    ->insert(['key' => 'd', 'value' => 40])
    ->onDuplicateKey(['key' => 'e'])
    ->exec();
```

---

```mysql
INSERT INTO `my_database`.`my_table`
(`key`,`value`) VALUES
("d",40),
("e",50);
```

```php
$container->get('alhames_db.manager')->db('my_table')
    ->insert([
        ['key' => 'd', 'value' => 40],
        ['key' => 'e', 'value' => 50]
    ])
    ->exec();
```

---

```mysql
UPDATE `my_database`.`my_table`
SET `key` = "d", `value` = 40
WHERE `id` = 4;
```

```php
$container->get('alhames_db.manager')->db('my_table')
    ->update(['key' => 'd', 'value' => 40])
    ->where(['id' => 4])
    ->exec();
```

---

```mysql
UPDATE `my_database`.`my_table`
SET `key` = "d";
```

```php
$container->get('alhames_db.manager')->db('my_table')
    ->update(['key' => 'd'])
    ->disableSecurity()
    ->exec();
```

---

```mysql
DELETE
FROM `my_database`.`my_table`
WHERE `id` = 4;
```

```php
$container->get('alhames_db.manager')->db('my_table')
    ->delete()
    ->where(['id' => 4])
    ->exec();
```

---

```mysql
REPLACE INTO `my_database`.`my_table`
SET `key` = "d", `value` = 40;
```

```php
$container->get('alhames_db.manager')->db('my_table')
    ->replace(['key' => 'd', 'value' => 40])
    ->exec();
```

---

```mysql
TRUNCATE TABLE `my_database`.`my_table`;
```

```php
$container->get('alhames_db.manager')->db('my_table')
    ->truncate()
    ->disableSecurity()
    ->exec();
```

---

```mysql
OPTIMIZE TABLE `my_database`.`my_table`;
```

```php
$container->get('alhames_db.manager')->db('my_table')
    ->optimize()
    ->exec();
```

---

```mysql
START TRANSACTION;

INSERT INTO `my_database`.`my_table`
SET `key` = "d", `value` = 40;

SELECT `id`
FROM `my_database`.`my_table`;

COMMIT;
```

```php
$dbm = $container->get('alhames_db.manager');

$dbm->getConnection()->beginTransaction();
try {
    $dbm->db('my_table')
        ->insert(['key' => 'd', 'value' => 40])
        ->exec();
    $ids = $dbm->db('my_table')
        ->select('id')
        ->getRows(null, 'id');
    $dbm->getConnection()->commit();
} catch (\Throwable $e) {
    $dbm->getConnection()->rollback();
}
```

---

```php
$result = $container->get('alhames_db.manager')->db('my_table')
    ->select('id')
    ->setCaching('my_table_ids', 60 * 60) // 1 hour
    ->getRows();
```

