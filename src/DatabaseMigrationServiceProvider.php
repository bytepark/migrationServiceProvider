<?php
/**
 * File for database migration service provider
 *
 * PHP version 5.3
 *
 * @category   Bytepark
 * @package    Silex
 * @subpackage ServiceProvider
 * @author     bytepark Gmbh <code@bytepark.de>
 * @copyright  2014 - bytepark GmbH
 * @license    http://www.bytepark.de proprietary
 * @link       http://www.bytepark.de
 */

namespace Bytepark\Silex;

use Bytepark\Component\Migration\Lock\FilesystemWithAutoRelease;
use Bytepark\Component\Migration\Manager;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Database migration service provider
 *
 * @category   Bytepark
 * @package    Silex
 * @subpackage ServiceProvider
 * @author     bytepark Gmbh <code@bytepark.de>
 * @license    http://www.bytepark.de proprietary
 * @link       http://www.bytepark.de
 */
class DatabaseMigrationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['migration_service'] = $app->share(function() use ($app) {
            $database = $app['database'];
            $migration = $app['migration'];
            $connection = new $migration['connection_class'](
                $database['host'],
                $database['name'],
                $database['username'],
                $database['password']
            );

            $source = new $migration['source_class'](new \FilesystemIterator($migration['source_filesystem_path']));
            $history = new $migration['history_class']($connection, 'migration_history');
            $lockDateTime = new \DateTime();
            $lockDateTime->add(new \DateInterval(sprintf('PT%dS', intval($migration['lock_timeout']))));
            $lock = new FilesystemWithAutoRelease(new \SplFileInfo($migration['lock_file_path']), $lockDateTime);

            return new Manager($connection, $source, $history, $lock);
        });
    }

    public function boot(Application $app)
    {
        /* @var $migrationService Manager */
        $migrationService = $app['migration_service'];
        $migrationService->dispatch();
    }
}
