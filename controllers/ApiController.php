<?php

namespace app\controllers;

use app\apidoc\ApiRenderer;
use Yii;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UnsupportedMediaTypeHttpException;

/**
 * ApiController provides the framework API documentation
 *
 * API documentation is provided in HTML format for all versions of Yii.
 *
 * Version 2.0 provides also json
 */
class ApiController extends Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => 'yii\filters\ContentNegotiator',
                'only' => ['view', 'index', 'entry', 'class-members'],
                'formats' => [
                    'text/html' => Response::FORMAT_HTML,
                    'application/xhtml+xml' => Response::FORMAT_HTML,
                    'application/json' => Response::FORMAT_JSON,
                    'json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }


    public function actionIndex($version)
    {
        return $this->actionView($version, 'index');
    }

    public function actionView($version, $section)
    {
        $this->validateVersion($version);

        switch (Yii::$app->response->format) {
            case Response::FORMAT_HTML:

                $title = '';
                $packages = [];
                if ($version[0] === '1') {
                    $file = Yii::getAlias("@app/data/api-$version/api/$section.html");
                    $packages = unserialize(file_get_contents(Yii::getAlias("@app/data/api-$version/api/packages.txt")));
                    $view = 'view1x';
                    $title = $section !== 'index' ? $section : '';
                } else {
                    $file = Yii::getAlias("@app/data/api-$version/$section.html");
                    $view = 'view2x';
                    $titles = require(Yii::getAlias("@app/data/api-$version/titles.php"));
                    if (isset($titles[$section . '.html'])) {
                        $title = $titles[$section . '.html'];
                    }
                }
                if (!preg_match('/^[\w\-]+$/', $section) || !is_file($file)) {
                    throw new NotFoundHttpException('The requested page was not found.');
                }

                return $this->render($view, [
                    'content' => file_get_contents($file),
                    'section' => $section,
                    'versions' => Yii::$app->params['api.versions'],
                    'version' => $version,
                    'title' => $title,
                    'packages' => $packages,
                ]);

                break;
            case Response::FORMAT_JSON:

                if ($section === 'index') {
                    $apiRenderer = new ApiRenderer([
                        'version' => $version,
                    ]);

                    $classes = Json::decode(file_get_contents(Yii::getAlias("@app/data/api-$version/json/typeNames.json")));
                    foreach($classes as $i => $class) {
                        $classes[$i]['url'] = Yii::$app->request->hostInfo . $apiRenderer->generateApiUrl($class['name']);
                    }

                    return [
                        'classes' => $classes,
                        'version' => $version,
                        'count' => count($classes),
                    ];
                } else {
                    return [
                        'TODO'
                    ];
                }
                break;
        }
        throw new UnsupportedMediaTypeHttpException;
    }

    /**
     * For application/json request, provide all classes of 1.1 and 2.0
     * For Html, just redirect to latest docs.
     */
    public function actionEntry()
    {
        switch (Yii::$app->response->format) {
            case Response::FORMAT_HTML:
                return $this->redirect(['index', 'version' => '2.0'], 301); // Moved Permanently

            case Response::FORMAT_JSON:

                // 2.0 classes
                $apiRenderer = new ApiRenderer([
                    'version' => '2.0',
                ]);

                $classes = Json::decode(file_get_contents(Yii::getAlias("@app/data/api-2.0/json/typeNames.json")));
                foreach($classes as $i => $class) {
                    $classes[$i]['url'] = Yii::$app->request->hostInfo . $apiRenderer->generateApiUrl($class['name']);
                    $classes[$i]['version'] = '2.0';
                }

                // 1.1 classes
                $classes1 = Json::decode(file_get_contents(Yii::getAlias("@app/data/api-1.1/json/typeNames.json")));
                foreach($classes1 as $i => $class) {
                    $classes1[$i]['url'] = Yii::$app->params['api.baseUrl'] . "/1.1/{$class['name']}";
                    $classes1[$i]['version'] = '1.1';
                }

                return [
                    'classes' => array_merge($classes, $classes1),
                ];
        }
        throw new UnsupportedMediaTypeHttpException;
    }

    /**
     * For application/json request, provide all class members of 1.1 and 2.0
     */
    public function actionClassMembers()
    {
        switch (Yii::$app->response->format) {
            case Response::FORMAT_JSON:

                // 2.0 class members
                $apiRenderer = new ApiRenderer([
                    'version' => '2.0',
                ]);

                $members = Json::decode(file_get_contents(Yii::getAlias("@app/data/api-2.0/json/typeMembers.json")));
                foreach($members as $m => $member) {
                    $hash = $member['name'] . ($member['type'] == 'method' ? '()' : '') . '-detail';
                    foreach($members[$m]['implemented'] as $i => $impl) {
                        $members[$m]['implemented'][$i] = [
                            'name' => $impl,
                            'url' => Yii::$app->request->hostInfo . $apiRenderer->generateApiUrl($impl) . "#$hash",
                        ];
                    }
                    $members[$m]['version'] = '2.0';
                }

                // 1.1 classes
                $members1 = Json::decode(file_get_contents(Yii::getAlias("@app/data/api-1.1/json/typeMembers.json")));
                foreach($members1 as $m => $member) {
                    $hash = $member['name'] . '-detail';
                    foreach($members1[$m]['implemented'] as $i => $impl) {
                        $members1[$m]['implemented'][$i] = [
                            'name' => $impl,
                            'url' => Yii::$app->params['api.baseUrl'] . "/1.1/{$impl}#$hash"
                        ];
                    }
//                    $members1[$i]['url'] = ;
                    $members1[$m]['version'] = '1.1';
                }

                return [
                    'members' => array_merge($members, $members1),
                ];
        }
        throw new UnsupportedMediaTypeHttpException;
    }

    /**
     * This action redirects old urls http://www.yiiframework.com/doc-2.0/*.html to the new location.
     */
    public function actionRedirect($section)
    {
        $file = Yii::getAlias("@app/data/api-2.0/$section.html");
        if (!preg_match('/^[\w\-]+$/', $section) || !is_file($file)) {
            throw new NotFoundHttpException('The requested page was not found.');
        }
        return $this->redirect(['view', 'version' => '2.0', 'section' => $section], 301); // Moved Permanently
    }

    protected function validateVersion($version)
    {
        $versions = Yii::$app->params['api.versions'];
        if (!in_array($version, $versions)) {
            // TODO make nicer error page (keep version and language selector)
            throw new NotFoundHttpException('The requested version was not found.');
        }
    }
}
