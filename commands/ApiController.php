<?php

namespace app\commands;

use Yii;
use yii\helpers\Console;
use app\apidoc\ApiRenderer;

/**
 * Generates API documentation for Yii.
 */
class ApiController extends \yii\apidoc\commands\ApiController
{
    public $defaultAction = 'all';
    public $guidePrefix = '';
    protected $version = '2.0';

    /**
     * Generates the API documentation for the specified version of Yii.
     * @param string $version version number, such as 1.1, 2.0
     * @return integer exit status
     */
    public function actionGenerate($version)
    {
        $versions = Yii::$app->params['api.versions'];
        if (!in_array($version, $versions)) {
            $this->stderr("Unknown version $version. Valid versions are " . implode(', ', $versions) . "\n\n", Console::FG_RED);
            return 1;
        }

        $targetPath = Yii::getAlias('@app/data');
        $sourcePath = Yii::getAlias('@app/data');

        if ($version[0] === '2') {
            $source = [
                "$sourcePath/yii-$version/framework",
                "$sourcePath/yii-$version/extensions",
            ];
            $target = "$targetPath/api-$version";
            $this->guide = "/guide/$version/en";

            $this->stdout("Start generating API $version...\n");
            $this->actionIndex($source, $target);
            $this->stdout("Finished API $version.\n\n", Console::FG_GREEN);
        }

        return 0;
    }

    protected function findRenderer($template)
    {
        return new ApiRenderer;
    }
} 