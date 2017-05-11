<?php
return [
    'name'      => 'node230',
    'watchDirs' => [
        '/data/inotify/node22',
        '/data/inotify/node23'
    ],
    'clients'   => [
        'node1' => ['host' => '11.11.1.231']
    ],
    'db'        => [
        'options' => [/* default open options */
            'create_if_missing'      => TRUE,    // if the specified database didn't exist will create a new one
            'error_if_exists'        => FALSE,   // if the opened database exsits will throw exception
            'paranoid_checks'        => FALSE,
            'block_cache_size'       => 1024 * 1024 * 32, //32M
            'write_buffer_size'      => 1024 * 1024 * 128, //128M
            'block_size'             => 4096,
            'max_open_files'         => 1000,
            'block_restart_interval' => 16,
            'compression'            => LEVELDB_SNAPPY_COMPRESSION,
            'comparator'             => NULL,   // any callable parameter which returns 0, -1, 1
        ],
        'r'       => [/* default readoptions */
            'verify_check_sum' => FALSE,
            'fill_cache'       => TRUE,
            'snapshot'         => NULL
        ],
        'w'       => [/* default write options */
            'sync' => FALSE
        ]
    ]

];
