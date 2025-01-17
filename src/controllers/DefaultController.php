<?php
/**
 * Related plugin for Craft CMS 3.x
 *
 * A simple plugin that adds a widget within the Craft CP page sidebar, allowing you to quickly and easily access related entries.
 *
 * @link      https://github.com/reganlawton
 * @copyright Copyright (c) 2019 reganlawton
 */

namespace wrav\related\controllers;

use craft\base\Element;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\web\Response;
use wrav\related\Related;

use Craft;
use craft\web\Controller;
use yii\web\JsonResponseFormatter;

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    reganlawton
 * @package   Related
 * @since     1.0.0
 */
class DefaultController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['index', 'do-something'];

    // Public Methods
    // =========================================================================

    public function actionIndex()
    {
        $data = Craft::$app->request->getQueryParams();
        $allowedSections = Related::getInstance()->getSettings()->allowedSections;
        $allowedCategories = Related::getInstance()->getSettings()->allowedCategories;
        $allowedAssetVolumes = Related::getInstance()->getSettings()->allowedAssetVolumes;
        $shouldFetch = false;

        if(Craft::$app->elements->getElementTypeById($data['id']) === 'craft\elements\Asset') {
            $asset = Craft::$app->elements->getElementById($data['id']);
            $volumeId = $asset->volume->id;
            if(!$allowedAssetVolumes){
                $shouldFetch = true;
            } else if (is_array($allowedAssetVolumes)) {
                if(in_array($volumeId, $allowedAssetVolumes)) {
                    $shouldFetch = true;
                }
            }
        }

        if (!empty($data['sectionId'])) {
            if (!$allowedSections) {
                $shouldFetch = true;
            } else if (is_array($allowedSections)) {
                try {
                    $sectionHandle = Craft::$app->getSections()->getSectionById($data['sectionId'])->handle;
                } catch (\Throwable $exception) {
                    $sectionHandle = '';
                }
                if (in_array($sectionHandle, $allowedSections)) {
                    $shouldFetch = true;
                }
            }
        }
        if (!empty($data['userId'])) {
            $shouldFetch = true;
        }
        if (!empty($data['categoryId'])) {
            if (!$allowedCategories) {
                $shouldFetch = true;
            } else if (is_array($allowedCategories)) {
                try {
                    $categoryHandle = Craft::$app->getCategories()->getGroupById($data['categoryId'])->handle;
                } catch (\Throwable $exception) {
                    $categoryHandle = '';
                }
                if (in_array($categoryHandle, $allowedCategories)) {
                    $shouldFetch = true;
                }
            }
        }

        if ($shouldFetch) {
            $elementId = $data['id'];
            $relations = Related::$plugin->relatedService->getRelated($elementId);

            $data = [
                'count' => count($relations),
                'view' => Craft::$app->view->renderTemplate(
                    'related/_modal',
                    [
                        'relations' => $relations
                    ]
                )
            ];

            $response = Craft::$app->getResponse();
            $formatter = new JsonResponseFormatter();
            $response->data = $data;
            $formatter->format($response);
            $response->data = null;
            $response->format = Response::FORMAT_RAW;

            return $response;
        }

        Craft::$app->end();
    }
}
