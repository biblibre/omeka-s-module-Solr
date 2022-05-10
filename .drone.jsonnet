local Pipeline(phpVersion, dbImage) = {
    kind: 'pipeline',
    type: 'docker',
    name: 'php:' + phpVersion + ' ' + dbImage,
    workspace: {
        path: 'omeka-s/modules/Solr',
    },
    steps: [
        {
            name: 'test',
            image: 'biblibre/omeka-s-ci:3.0.2-php' + phpVersion,
            commands: [
                'apt-get update && apt-get install -y libcurl4-openssl-dev libxml2-dev',
                'pecl install solr-2.5.1',
                'docker-php-ext-enable solr',
                'cp -rT /usr/src/omeka-s ../..',
                'git clone --depth 1 https://github.com/biblibre/omeka-s-module-Search.git ../Search',
                "echo 'host = \"db\"\\nuser = \"root\"\\npassword = \"root\"\\ndbname = \"omeka_test\"\\n' > ../../application/test/config/database.ini",
                'php ../../build/composer.phar install',
                'bash -c "cd ../.. && php /usr/local/libexec/wait-for-db.php"',
                '../../vendor/bin/phpunit',
                '../../node_modules/.bin/gulp test:module:cs',
            ],
        },
    ],
    services: [
        {
            name: 'db',
            image: dbImage,
            environment: {
                MYSQL_ROOT_PASSWORD: 'root',
                MYSQL_DATABASE: 'omeka_test',
            },
        },
    ],
};

[
    Pipeline('7.1', 'mysql:5.7'),
    // PHP 7.1 does not work with MySQL 8 default authentication plugin
    // Pipeline('7.1', 'mysql:8.0'),
    Pipeline('7.1', 'mariadb:10.2'),
    Pipeline('7.1', 'mariadb:10.3'),
    Pipeline('7.1', 'mariadb:10.4'),
    Pipeline('7.1', 'mariadb:10.5'),
    Pipeline('7.2', 'mysql:5.7'),
    // PHP 7.2 does not work with MySQL 8 default authentication plugin
    // Pipeline('7.2', 'mysql:8.0'),
    Pipeline('7.2', 'mariadb:10.2'),
    Pipeline('7.2', 'mariadb:10.3'),
    Pipeline('7.2', 'mariadb:10.4'),
    Pipeline('7.2', 'mariadb:10.5'),
    Pipeline('7.3', 'mysql:5.7'),
    // PHP 7.3 does not work with MySQL 8 default authentication plugin
    // Pipeline('7.3', 'mysql:8.0'),
    Pipeline('7.3', 'mariadb:10.2'),
    Pipeline('7.3', 'mariadb:10.3'),
    Pipeline('7.3', 'mariadb:10.4'),
    Pipeline('7.3', 'mariadb:10.5'),
    Pipeline('7.4', 'mysql:5.7'),
    Pipeline('7.4', 'mysql:8.0'),
    Pipeline('7.4', 'mariadb:10.2'),
    Pipeline('7.4', 'mariadb:10.3'),
    Pipeline('7.4', 'mariadb:10.4'),
    Pipeline('7.4', 'mariadb:10.5'),
]
