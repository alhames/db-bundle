<?php

use Alhames\DbBundle\Db\Db;

return [
    ['*'],
    ['*', '*'],
    ['DISTINCT *', null, Db::DISTINCT],
    ['SQL_CALC_FOUND_ROWS DISTINCT *', null, 'SQL_CALC_FOUND_ROWS DISTINCT'],
    ['self.*', 'self.*'],
    ['`test_field`', 'test_field'],
    ['self.test_field', 'self.test_field'],
    ['field_one, field_two', 'field_one, field_two'],
    ['self.field_one, self.field_two', 'self.field_one, self.field_two'],
    ['`field_one`, `field_two`', ['field_one', 'field_two']],
    ['self.field_one, self.field_two', ['self.field_one', 'self.field_two']],
    ['`field_one`, `field_two`', '`field_one`, `field_two`'],
    ['`field_one` AS `one`, `field_two`', ['field_one' => 'one', 'field_two']],
    ['COUNT(*)', 'COUNT(*)'],
    ['COUNT(*) AS `c`', ['COUNT(*)' => 'c']],
    ['COUNT(*) AS c', 'COUNT(*) AS c'],
    ['*, COUNT(*)', '*, COUNT(*)'],
    ['*, COUNT(*)', ['*', 'COUNT(*)']],
    ['*, COUNT(*) AS `c`', ['*', 'COUNT(*)' => 'c']],
];
