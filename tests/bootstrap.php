<?php
/**
 * Test suite bootstrap.
 *
 * @copyright     Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;

require_once 'vendor/autoload.php';

// Path constants to a few helpful things.
define('ROOT', dirname(__DIR__) . DS);
define('CAKE_CORE_INCLUDE_PATH', ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp');
define('CORE_PATH', ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS);
define('CAKE', CORE_PATH . 'src' . DS);
define('TESTS', ROOT . 'tests');
define('APP', ROOT . 'tests' . DS . 'TestApp' . DS);
define('APP_DIR', 'TestApp');
define('WEBROOT_DIR', 'webroot');
define('TMP', APP . 'tmp' . DS);
define('CONFIG', APP . 'config' . DS);
define('WWW_ROOT', APP);
define('CACHE', TMP);
define('LOGS', TMP);

require_once CORE_PATH . 'config/bootstrap.php';

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'TestApp',
    'dir' => 'src'
]);
ConnectionManager::config('default', [
    'className' => 'Cake\Database\Connection',
    'driver' => 'Cake\Database\Driver\Sqlite',
    'database' => APP . 'data.db',
]);

//Plugin::load('Scheduler', ['path' => ROOT]);