<?php

namespace luya\console\commands;

use Yii;
use yii\helpers\Console;
use luya\helpers\FileHelper;

/**
 * @see https://github.com/yiisoft/yii2/issues/384
 *
 * @use php yii migrate/create foomigration
 * @use php yii migrate/up
 *
 * @todo create command for module specified, like: php yii postsql/create news foomigration
 *
 * @author nadar
 */
class MigrateController extends \yii\console\controllers\MigrateController
{
    public $migrationFileDirs = array();

    public $moduleMigrationDirectories = array();

    /* public $migrationTable = 'yii_migration'; @TODO rename migration table? */

    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            $this->initModuleMigrationDirectories();

            return true;
        } else {
            return false;
        }
    }

    private function initModuleMigrationDirectories()
    {
        foreach (Yii::$app->modules as $key => $item) {
            $module = Yii::$app->getModule($key);
            $this->moduleMigrationDirectories[$key] = $module->getBasePath().DIRECTORY_SEPARATOR.'migrations';
        }
    }

    private function getModuleMigrationDirectorie($module)
    {
        if (!array_key_exists($module, $this->moduleMigrationDirectories)) {
            return false;
        }

        return $this->moduleMigrationDirectories[$module];
    }

    protected function createMigration($class)
    {
        $file = $this->migrationFileDirs[$class].DIRECTORY_SEPARATOR.$class.'.php';
        require_once $file;

        return new $class();
    }

    /**
     * Create new migration, first param the name, second param the module where the migration should be put in.
     * 
     * @param string $name The name of the migration
     * @param string|boolean $module The module name where the migration should be placed in.
     * @todo replace module param with user teminal selection.
     * @see \yii\console\controllers\BaseMigrateController::actionCreate()
     */
    public function actionCreate($name, $module = false)
    {
        if (!preg_match('/^\w+$/', $name)) {
            throw new Exception('The migration name should contain letters, digits and/or underscore characters only.');
        }

        $name = 'm'.gmdate('ymd_His').'_'.$name;

        if (!$module) {
            $file = $this->migrationPath.DIRECTORY_SEPARATOR.$name.'.php';
        } else {
            if (($folder = $this->getModuleMigrationDirectorie($module)) === false) {
                $this->stdout("\nModule '$module' does not exist.\n", Console::FG_RED);

                return self::EXIT_CODE_ERROR;
            }
            $file = $folder.DIRECTORY_SEPARATOR.$name.'.php';
        }

        if ($this->confirm("Create new migration '$file'?")) {
            $content = $this->renderFile(Yii::getAlias($this->templateFile), ['className' => $name]);
            
            if (!file_exists($folder)) {
                FileHelper::createDirectory($folder, 0775, false);
            }
            
            file_put_contents($file, $content);
            $this->stdout("\nMigration created successfully.\n", Console::FG_GREEN);

            return self::EXIT_CODE_NORMAL;
        }
    }

    /**
     * Returns the migrations that are not applied.
     *
     * @return array list of new migrations
     */
    protected function getNewMigrations()
    {
        $applied = [];

        foreach ($this->getMigrationHistory(null) as $version => $time) {
            $applied[substr($version, 1, 13)] = true;
        }

        $moduleMigrationDirs = array();

        if (count($this->moduleMigrationDirectories) > 0) {
            foreach ($this->moduleMigrationDirectories as $name => $dir) {
                $moduleMigrationDirs[] = $dir;
            }
        }

        $moduleMigrationDirs[] = $this->migrationPath;

        $migrations = [];

        foreach ($moduleMigrationDirs as $moduleMigrationPath) {
            if (!file_exists($moduleMigrationPath)) {
                continue;
            }

            $handle = opendir($moduleMigrationPath);

            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                if (preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/', $file, $matches) && is_file($moduleMigrationPath.DIRECTORY_SEPARATOR.$file) && !isset($applied[$matches[2]])) {
                    $migrations[] = $matches[1];
                    $this->migrationFileDirs[$matches[1]] = $moduleMigrationPath;
                }
            }

            closedir($handle);
        }

        sort($migrations);

        return $migrations;
    }
}
