<?php

use DbBundle\Db\Db;

return [
    // Test Types
    [
        '`field` = 1',
        ['field' => 1],
    ],
    [
        '`field` = "a"',
        ['field' => 'a'],
    ],
    [
        '`field` = 1.1',
        ['field' => 1.1],
    ],
    [
        '`field` IS NULL',
        ['field' => null],
    ],
    [
        '`field` = "2017-07-12 18:55:43"',
        ['field' => new \DateTime('2017-07-12 18:55:43')],
    ],
    [
        '`field` = 0',
        ['field' => false],
    ],
    [
        '`field` = 1',
        ['field' => true],
    ],

    // Test IN()
    [
        '`field` IN (1)',
        ['field' => [1]],
    ],
    [
        '`field` IN (1,2)',
        ['field' => [1, 2]],
    ],
    [
        '`field` IN ("a",2,NULL)',
        ['field' => ['a', 2, null]],
    ],

    // Test AND
    [
        '`field_one` = 1 AND `field_two` = 2',
        ['field_one' => 1, 'field_two' => 2],
    ],
    [
        '`field_one` = 1 AND `field_two` = 2 AND `field_three` = 3',
        ['field_one' => 1, 'field_two' => 2, 'field_three' => 3],
    ],

    // Test Db::value()
    [
        '`field` = 1',
        ['field' => Db::value('=', 1)],
    ],
    [
        '`field` > 1',
        ['field' => Db::value('>', 1)],
    ],
    [
        '`field` <= 1',
        ['field' => Db::value('<=', 1)],
    ],
    [
        '`field` IN (1,2)',
        ['field' => Db::value('=', [1, 2])],
    ],
    [
        '`field` IN (1,2)',
        ['field' => Db::value('IN', [1, 2])],
    ],
    [
        '`field` != 1',
        ['field' => Db::value('!=', 1)],
    ],
    [
        '`field` NOT IN (1,2)',
        ['field' => Db::value('!=', [1, 2])],
    ],
    [
        '`field` NOT IN (1,2)',
        ['field' => Db::value('NOT IN', [1, 2])],
    ],
    [
        '`field` BETWEEN 1 AND 2',
        ['field' => Db::value('BETWEEN', [1, 2])],
    ],
    [
        '`field` BETWEEN "2017-07-11 18:55:43" AND "2017-07-12 18:55:43"',
        ['field' => Db::value('BETWEEN', [new \DateTime('2017-07-11 18:55:43'), new \DateTime('2017-07-12 18:55:43')])],
    ],

    // Test LIKE
    [
        '`field` LIKE "abc"',
        ['field' => Db::value('LIKE', 'abc')],
    ],
    [
        '`field` LIKE "%a_c%"',
        ['field' => Db::value('LIKE', '%a_c%')],
    ],
    [
        '`field` LIKE "%a\\\\_c\\\\%"',
        ['field' => Db::value('LIKE', '%'.Db::escapeLike('a_c%'))],
    ],

    // Test escaping
    [
        '`field` = "a\" AND `b` = \"0"',
        ['field' => 'a" AND `b` = "0'],
    ],

    // Test field
    [
        'self.a = "a"',
        ['self.a' => 'a'],
    ],
    [
        'self.a = `b`',
        ['self.a' => Db::field('b')],
    ],
    [
        'self.a = self.b',
        ['self.a' => Db::field('self.b')],
    ],
];
