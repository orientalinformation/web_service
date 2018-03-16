<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */
    'default' => env('FILESYSTEM_DRIVER', 'local'),
    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */
    'cloud' => env('FILESYSTEM_CLOUD', 's3'),
    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3", "rackspace"
    |
    */
    'disks' => [
        'cloud' => [
            'driver' => 's3',
            'key'    => env('S3_KEY'),
            'secret' => env('S3_SECRET'),
            'region' => env('S3_REGION'),
            'bucket' => env('S3_BUCKET'),
            'version' => 'latest',
            'visibility' => 'public',
        ],
        'uploads' => [
            'driver' => 'local',
            'root' => rtrim(app()->basePath('public/uploads'), '/'),
            'url' => 'http://'.$_SERVER['HTTP_HOST'] . '/assets',
        ],
        'private' => [
            'driver' => 'local',
            'root' => app()->storagePath('private'),
        ],
        'local' => [
            'driver' => 'local',
            'root' => app()->storagePath('app'),
        ],
        'public' => [
            'driver' => 'local',
            'root' => app()->basePath('public/uploads'),
            'visibility' => 'public',
            'url' => 'http://'.$_SERVER['HTTP_HOST'],
        ],
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],
    ]
];
