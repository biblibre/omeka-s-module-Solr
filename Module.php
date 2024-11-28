<?php

/*
 * Copyright BibLibre, 2016-2020
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace Solr;

use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\EventManager\Event;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Module\AbstractModule;

class Module extends AbstractModule
{
    public function init(ModuleManager $moduleManager)
    {
        $event = $moduleManager->getEvent();
        $container = $event->getParam('ServiceManager');
        $serviceListener = $container->get('ServiceListener');

        $serviceListener->addServiceManager(
            'Solr\ValueExtractorManager',
            'solr_value_extractors',
            'Solr\Feature\ValueExtractorProviderInterface',
            'getSolrValueExtractorConfig'
        );
        $serviceListener->addServiceManager(
            'Solr\ValueFormatterManager',
            'solr_value_formatters',
            'Solr\Feature\ValueFormatterProviderInterface',
            'getSolrValueFormatterConfig'
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(null, 'Solr\Api\Adapter\SolrNodeAdapter');
        $acl->allow(null, 'Solr\Api\Adapter\SolrMappingAdapter');
        $acl->allow(null, 'Solr\Api\Adapter\SolrSearchFieldAdapter');
        $acl->allow(null, 'Solr\Entity\SolrNode', 'read');
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach('Solr\Querier', 'solr.query', [$this, 'onSolrQuery']);
        $sharedEventManager->attach('Solr\Indexer', 'solr.indexDocument', [$this, 'onSolrIndexDocument']);
    }

    public function onSolrQuery(Event $event)
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');

        $solrQuery = $event->getTarget();
        $solrNode = $event->getParam('solrNode');

        $solrNodeSettings = $solrNode->settings();
        $is_public_field = $solrNodeSettings['is_public_field'];
        $groups_field = $solrNodeSettings['groups_field'] ?? '';

        if (!$acl->userIsAllowed('Omeka\Entity\Resource', 'view-all')) {
            $user = $acl->getAuthenticationService()->getIdentity();
            if ($user && $groups_field && $this->isModuleActive('Group')) {
                $groupUserRepository = $entityManager->getRepository('Group\Entity\GroupUser');
                $groupUsers = $groupUserRepository->findBy(['user' => $user]);
                $groupsIds = array_map(fn ($groupUser) => $groupUser->getGroup()->getId(), $groupUsers);
                if (!empty($groupsIds)) {
                    $fq = sprintf('%s:%s OR %s:(%s)', $is_public_field, 'true', $groups_field, implode(' OR ', $groupsIds));
                    $solrQuery->addFilterQuery($fq);
                } else {
                    $solrQuery->addFilterQuery("$is_public_field:true");
                }
            } else {
                $solrQuery->addFilterQuery("$is_public_field:true");
            }
        }
    }

    public function onSolrIndexDocument(Event $event)
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');

        $document = $event->getTarget();
        $resource = $event->getParam('resource');
        $solrNode = $event->getParam('solrNode');

        if ($this->isModuleActive('Group')) {
            $solrNodeSettings = $solrNode->settings();
            $groups_field = $solrNodeSettings['groups_field'];
            if ($groups_field) {
                $groupResourceRepository = $entityManager->getRepository('Group\Entity\GroupResource');
                $groupResources = $groupResourceRepository->findBy(['resource' => $resource->id()]);
                $groupsIds = array_map(fn ($groupResource) => $groupResource->getGroup()->getId(), $groupResources);
                foreach ($groupsIds as $groupId) {
                    $document->addField($groups_field, (string) $groupId);
                }
            }
        }
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $api = $serviceLocator->get('Omeka\ApiManager');

        $connection->exec("
            CREATE TABLE solr_node (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                uri VARCHAR(255) NOT NULL,
                user VARCHAR(255) DEFAULT '' NOT NULL,
                password VARCHAR(255) DEFAULT '' NOT NULL,
                settings LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
        ");

        $connection->exec("
            CREATE TABLE solr_mapping (
                id INT AUTO_INCREMENT NOT NULL,
                solr_node_id INT NOT NULL,
                resource_name VARCHAR(255) NOT NULL,
                field_name VARCHAR(255) NOT NULL,
                source VARCHAR(255) NOT NULL,
                settings LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)',
                INDEX IDX_A62FEAA6A9C459FB (solr_node_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");

        $connection->exec("
            CREATE TABLE solr_search_field (
                id INT AUTO_INCREMENT NOT NULL,
                solr_node_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                `label` VARCHAR(255) NOT NULL,
                text_fields LONGTEXT DEFAULT NULL,
                string_fields LONGTEXT DEFAULT NULL,
                facet_field VARCHAR(255) DEFAULT NULL,
                sort_field VARCHAR(255) DEFAULT NULL,
                INDEX IDX_7F4FB782A9C459FB (solr_node_id),
                UNIQUE INDEX UNIQ_7F4FB782A9C459FB5E237E06 (solr_node_id, name),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
        ");

        $connection->exec("
            ALTER TABLE solr_mapping ADD CONSTRAINT FK_A62FEAA6A9C459FB
            FOREIGN KEY (solr_node_id) REFERENCES solr_node (id) ON DELETE CASCADE
        ");

        $connection->exec("
            ALTER TABLE solr_search_field ADD CONSTRAINT FK_7F4FB782A9C459FB
            FOREIGN KEY (solr_node_id) REFERENCES solr_node (id) ON DELETE CASCADE;
        ");

        $sql = '
            INSERT INTO `solr_node` (`name`, `uri`, `settings`)
            VALUES ("default", ?, ?)
        ';
        $defaultSettings = [
            'resource_name_field' => 'resource_name_s',
            'sites_field' => 'sites_id_is',
            'is_public_field' => 'is_public_b',
        ];
        $connection->executeQuery($sql, ['http://127.0.0.1:8983/solr/default', json_encode($defaultSettings)]);
        $solrNodeId = $connection->lastInsertId();

        $sql = '
            INSERT INTO `solr_mapping` (`solr_node_id`, `resource_name`, `field_name`, `source`, `settings`)
            VALUES (?, ?, ?, ?, ?)
        ';
        $defaultMappingSettingsJson = json_encode([
            'transformations' => [
                'name' => Transformation\ConvertResourceToString::class,
                'resource_field' => 'title',
            ],
        ]);
        $connection->executeQuery($sql, [$solrNodeId, 'items', 'dcterms_title_txt', 'dcterms:title', $defaultMappingSettingsJson]);
        $connection->executeQuery($sql, [$solrNodeId, 'items', 'dcterms_title_ss', 'dcterms:title', $defaultMappingSettingsJson]);
        $connection->executeQuery($sql, [$solrNodeId, 'items', 'dcterms_title_s', 'dcterms:title', $defaultMappingSettingsJson]);
        $connection->executeQuery($sql, [$solrNodeId, 'items', 'dcterms_creator_txt', 'dcterms:creator', $defaultMappingSettingsJson]);
        $connection->executeQuery($sql, [$solrNodeId, 'items', 'dcterms_creator_ss', 'dcterms:creator', $defaultMappingSettingsJson]);
        $connection->executeQuery($sql, [$solrNodeId, 'items', 'dcterms_subject_txt', 'dcterms:subject', $defaultMappingSettingsJson]);
        $connection->executeQuery($sql, [$solrNodeId, 'items', 'dcterms_subject_ss', 'dcterms:subject', $defaultMappingSettingsJson]);
        $connection->executeQuery($sql, [$solrNodeId, 'items', 'dcterms_description_txt', 'dcterms:description', $defaultMappingSettingsJson]);
        $connection->executeQuery($sql, [$solrNodeId, 'items', 'dcterms_description_ss', 'dcterms:description', $defaultMappingSettingsJson]);
        $connection->executeQuery($sql, [$solrNodeId, 'items', 'dcterms_date_txt', 'dcterms:date', $defaultMappingSettingsJson]);
        $connection->executeQuery($sql, [$solrNodeId, 'items', 'dcterms_date_ss', 'dcterms:date', $defaultMappingSettingsJson]);
        $connection->executeQuery($sql, [$solrNodeId, 'items', 'dcterms_date_s', 'dcterms:date', $defaultMappingSettingsJson]);
        $connection->executeQuery($sql, [$solrNodeId, 'items', 'bibo_content_txt', 'bibo:content', $defaultMappingSettingsJson]);

        $sql = '
            INSERT INTO `solr_search_field` (`solr_node_id`, `name`, `label`, `text_fields`, `string_fields`, `facet_field`, `sort_field`)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ';
        $connection->executeQuery($sql, [$solrNodeId, 'title', 'Title', 'dcterms_title_txt', 'dcterms_title_ss', '', 'dcterms_title_s']);
        $connection->executeQuery($sql, [$solrNodeId, 'creator', 'Creator', 'dcterms_creator_txt', 'dcterms_creator_ss', 'dcterms_creator_ss', '']);
        $connection->executeQuery($sql, [$solrNodeId, 'subject', 'Subject', 'dcterms_subject_txt', 'dcterms_subject_ss', 'dcterms_subject_ss', '']);
        $connection->executeQuery($sql, [$solrNodeId, 'description', 'Description', 'dcterms_description_txt', '', '', '']);
        $connection->executeQuery($sql, [$solrNodeId, 'date', 'Date', 'dcterms_date_txt', 'dcterms_date_ss', 'dcterms_date_ss', 'dcterms_date_s']);
        $connection->executeQuery($sql, [$solrNodeId, 'ocr', 'OCR', 'bibo_content_txt', '', '', '']);
        $connection->executeQuery($sql, [$solrNodeId, 'all_metadata_ocr', 'All metadata + OCR', 'dcterms_title_txt dcterms_creator_txt dcterms_subject_txt dcterms_description_txt dcterms_date_txt bibo_content_txt', '', '', '']);
        $connection->executeQuery($sql, [$solrNodeId, 'all_metadata', 'All metadata', 'dcterms_title_txt dcterms_creator_txt dcterms_subject_txt dcterms_description_txt dcterms_date_txt', 'dcterms_title_ss dcterms_creator_ss dcterms_subject_ss dcterms_description_ss dcterms_date_ss', '', '']);
    }

    public function upgrade($oldVersion, $newVersion,
        ServiceLocatorInterface $serviceLocator)
    {
        $translator = $serviceLocator->get('MvcTranslator');
        $connection = $serviceLocator->get('Omeka\Connection');

        if (version_compare($oldVersion, '0.1.1', '<')) {
            $sql = '
                CREATE TABLE IF NOT EXISTS `solr_node` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NOT NULL,
                    `settings` text,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
            ';
            $connection->exec($sql);
            $sql = '
                INSERT INTO `solr_node` (`name`, `settings`)
                VALUES ("default", ?)
            ';
            $defaultSettings = [
                'client' => [
                    'hostname' => '127.0.0.1',
                    'port' => 8983,
                    'path' => 'solr/default',
                ],
                'resource_name_field' => 'resource_name_s',
            ];
            $connection->executeQuery($sql, [json_encode($defaultSettings)]);
            $solrNodeId = $connection->lastInsertId();

            $sql = '
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_NAME = ?
                  AND CONSTRAINT_TYPE = ?
            ';
            $constraintName = $connection->fetchColumn($sql,
                [$connection->getDatabase(), 'solr_field', 'FOREIGN KEY']);

            $connection->exec('
                ALTER TABLE `solr_field`
                CHANGE COLUMN `label` `description` varchar(255) NULL DEFAULT NULL
            ');
            $connection->exec("
                ALTER TABLE `solr_field`
                DROP FOREIGN KEY `$constraintName`
            ");
            $connection->exec('
                ALTER TABLE `solr_field`
                DROP COLUMN `property_id`
            ');

            $connection->exec('
                ALTER TABLE `solr_field`
                ADD COLUMN `solr_node_id` int(11) unsigned NULL AFTER `id`
            ');
            $connection->executeQuery('
                UPDATE `solr_field`
                SET `solr_node_id` = ?
            ', [$solrNodeId]);
            $connection->exec('
                ALTER TABLE `solr_field`
                MODIFY `solr_node_id` int(11) unsigned NOT NULL
            ');

            $connection->exec('
                ALTER TABLE `solr_field`
                ADD CONSTRAINT `solr_field_fk_solr_node_id`
                    FOREIGN KEY (`solr_node_id`) REFERENCES `solr_node` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ');

            $connection->exec('
                CREATE TABLE IF NOT EXISTS `solr_profile` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `solr_node_id` int(11) unsigned NOT NULL,
                    `resource_name` varchar(255) NOT NULL,
                    PRIMARY KEY (`id`),
                    CONSTRAINT `solr_profile_fk_solr_node_id`
                        FOREIGN KEY (`solr_node_id`) REFERENCES `solr_node` (`id`)
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
            ');

            $connection->exec('
                CREATE TABLE IF NOT EXISTS `solr_profile_rule` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `solr_profile_id` int(11) unsigned NOT NULL,
                    `solr_field_id` int(11) unsigned NOT NULL,
                    `source` varchar(255) NOT NULL,
                    `settings` text,
                    PRIMARY KEY (`id`),
                    CONSTRAINT `solr_profile_rule_fk_solr_profile_id`
                        FOREIGN KEY (`solr_profile_id`) REFERENCES `solr_profile` (`id`)
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `solr_profile_rule_fk_solr_field_id`
                        FOREIGN KEY (`solr_field_id`) REFERENCES `solr_field` (`id`)
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
            ');
        }

        if (version_compare($oldVersion, '0.2.0', '<')) {
            $connection->exec('
                CREATE TABLE IF NOT EXISTS `solr_mapping` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `solr_node_id` int(11) unsigned NOT NULL,
                    `resource_name` varchar(255) NOT NULL,
                    `field_name` varchar(255) NOT NULL,
                    `source` varchar(255) NOT NULL,
                    `settings` text,
                    PRIMARY KEY (`id`),
                    CONSTRAINT `solr_mapping_fk_solr_node_id`
                        FOREIGN KEY (`solr_node_id`) REFERENCES `solr_node` (`id`)
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
            ');

            $connection->exec('
                INSERT INTO `solr_mapping` (`solr_node_id`, `resource_name`, `field_name`, `source`, `settings`)
                SELECT solr_node.id, solr_profile.resource_name, solr_field.name, solr_profile_rule.source, solr_profile_rule.settings
                FROM solr_profile_rule
                    LEFT JOIN solr_profile ON (solr_profile_rule.solr_profile_id = solr_profile.id)
                    LEFT JOIN solr_node ON (solr_profile.solr_node_id = solr_node.id)
                    LEFT JOIN solr_field ON (solr_profile_rule.solr_field_id = solr_field.id)
            ');

            $connection->exec('DROP TABLE IF EXISTS `solr_profile_rule`');
            $connection->exec('DROP TABLE IF EXISTS `solr_profile`');
            $connection->exec('DROP TABLE IF EXISTS `solr_field`');
        }

        if (version_compare($oldVersion, '0.6.0', '<')) {
            $connection->exec("ALTER TABLE solr_mapping DROP FOREIGN KEY solr_mapping_fk_solr_node_id");
            $connection->exec("
                ALTER TABLE solr_node
                    MODIFY id INT AUTO_INCREMENT NOT NULL,
                    MODIFY settings LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)'
            ");
            $connection->exec("
                ALTER TABLE solr_mapping
                    MODIFY id INT AUTO_INCREMENT NOT NULL,
                    MODIFY solr_node_id INT NOT NULL,
                    MODIFY settings LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)'
            ");
            $connection->exec("
                ALTER TABLE solr_mapping ADD CONSTRAINT FK_A62FEAA6A9C459FB
                FOREIGN KEY (solr_node_id) REFERENCES solr_node (id) ON DELETE CASCADE
            ");

            $connection->exec("
                CREATE TABLE solr_search_field (
                    id INT AUTO_INCREMENT NOT NULL,
                    solr_node_id INT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    `label` VARCHAR(255) NOT NULL,
                    text_fields LONGTEXT DEFAULT NULL,
                    string_fields LONGTEXT DEFAULT NULL,
                    facet_field VARCHAR(255) DEFAULT NULL,
                    sort_field VARCHAR(255) DEFAULT NULL,
                    UNIQUE INDEX UNIQ_7F4FB7825E237E06 (name),
                    INDEX IDX_7F4FB782A9C459FB (solr_node_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
            ");

            $connection->exec("
                ALTER TABLE solr_search_field ADD CONSTRAINT FK_7F4FB782A9C459FB
                FOREIGN KEY (solr_node_id) REFERENCES solr_node (id) ON DELETE CASCADE;
            ");
        }

        if (version_compare($oldVersion, '0.9.3', '<')) {
            $connection->exec('ALTER TABLE solr_search_field DROP INDEX UNIQ_7F4FB7825E237E06');
            $connection->exec('ALTER TABLE solr_search_field ADD UNIQUE INDEX UNIQ_7F4FB782A9C459FB5E237E06 (solr_node_id, name)');
        }

        if (version_compare($oldVersion, '0.13.0', '<')) {
            $mappings = $connection->executeQuery('SELECT id, settings FROM solr_mapping')->fetchAll();
            foreach ($mappings as $mapping) {
                $settings = json_decode($mapping['settings'], true);

                $settings['transformations'] = [];

                $data_types = $settings['data_types'] ?? [];
                if (!empty($data_types)) {
                    $settings['transformations'][] = [
                        'name' => 'Solr\Transformation\Filter\DataType',
                        'data_types' => $data_types,
                    ];
                }
                unset($settings['data_types']);

                $resource_field = $settings['resource_field'] ?? 'title';
                $settings['transformations'][] = [
                    'name' => 'Solr\Transformation\ConvertResourceToString',
                    'resource_field' => $resource_field,
                ];
                unset($settings['resource_field']);

                $formatter = $settings['formatter'] ?? '';
                if ($formatter === 'date_range') {
                    $settings['transformations'][] = [
                        'name' => 'Solr\Transformation\ConvertToSolrDateRange',
                        'exclude_unmatching' => '1',
                    ];
                } elseif ($formatter === 'plain_text') {
                    $settings['transformations'][] = [
                        'name' => 'Solr\Transformation\StripHtmlTags',
                    ];
                } elseif ($formatter) {
                    $settings['transformations'][] = [
                        'name' => 'Solr\Transformation\Format',
                        'formatter' => $formatter,
                    ];
                }
                unset($settings['formatter']);

                $connection->update('solr_mapping', ['settings' => json_encode($settings)], ['id' => $mapping['id']]);
            }
        }

        if (version_compare($oldVersion, '0.16.0', '<')) {
            $nodes = $connection->executeQuery('SELECT id, settings FROM solr_node')->fetchAll();
            foreach ($nodes as $node) {
                $settings = json_decode($node['settings'], true);

                $hostname = $settings['client']['hostname'] ?? '127.0.0.1';
                $port = $settings['client']['port'] ?? '8983';
                $path = $settings['client']['path'] ?? 'solr/default';

                $uri = sprintf('http://%s:%s/%s', $hostname, $port, $path);

                $settings['uri'] = $uri;
                $settings['user'] = $settings['client']['login'] ?? '';
                $settings['password'] = $settings['client']['password'] ?? '';

                unset($settings['client']);

                $connection->update('solr_node', ['settings' => json_encode($settings)], ['id' => $node['id']]);
            }
        }

        if (version_compare($oldVersion, '0.17.0', '<')) {
            $connection->exec(<<<SQL
                ALTER TABLE `solr_node`
                ADD COLUMN `uri` VARCHAR(255) NOT NULL AFTER `name`
            SQL);
            $connection->exec(<<<SQL
                ALTER TABLE `solr_node`
                ADD COLUMN `user` VARCHAR(255) DEFAULT '' NOT NULL AFTER `uri`
            SQL);
            $connection->exec(<<<SQL
                ALTER TABLE `solr_node`
                ADD COLUMN `password` VARCHAR(255) DEFAULT '' NOT NULL AFTER `user`
            SQL);

            $nodes = $connection->executeQuery('SELECT id, settings FROM solr_node')->fetchAll();
            foreach ($nodes as $node) {
                $id = $node['id'];
                $settings = json_decode($node['settings'], true);

                $uri = $settings['uri'] ?? '';
                $user = $settings['user'] ?? '';
                $password = $settings['password'] ?? '';

                unset($settings['uri']);
                unset($settings['user']);
                unset($settings['password']);

                $data = [
                    'uri' => $uri,
                    'user' => $user,
                    'password' => $password,
                    'settings' => json_encode($settings),
                ];
                $connection->update('solr_node', $data, ['id' => $id]);
            }
        }
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $connection->exec('DROP TABLE IF EXISTS `solr_search_field`');
        $connection->exec('DROP TABLE IF EXISTS `solr_mapping`');
        $connection->exec('DROP TABLE IF EXISTS `solr_node`');
    }

    protected function isModuleActive($moduleName): bool
    {
        $moduleManager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        if (!$moduleManager->isRegistered($moduleName)) {
            return false;
        }

        return $moduleManager->getModule($moduleName)->getState() === \Omeka\Module\Manager::STATE_ACTIVE;
    }
}
