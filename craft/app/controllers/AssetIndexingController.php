<?php
namespace Craft;

/**
 * Craft by Pixel & Tonic
 *
 * @package   Craft
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2013, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 */

/**
 * Handles asset indexing and sizing tasks
 */
class AssetIndexingController extends BaseController
{
	/**
	 * Get an indexing session ID
	 */
	public function actionGetSessionId()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$this->returnJson(array('sessionId' => craft()->assetIndexing->getIndexingSessionId()));
	}

	/**
	 * Start indexing.
	 */
	public function actionStartIndex()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$sourceId = craft()->request->getRequiredPost('sourceId');
		$sessionId = craft()->request->getRequiredPost('session');

		craft()->assetTransforms->cleanUpTransformsForSource($sourceId);
		$this->returnJson(craft()->assetIndexing->getIndexListForSource($sessionId, $sourceId));
	}

	/**
	 * Do the indexing.
	 */
	public function actionPerformIndex()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$sourceId = craft()->request->getRequiredPost('sourceId');
		$sessionId = craft()->request->getRequiredPost('session');
		$offset = craft()->request->getRequiredPost('offset');

		$fileId = craft()->assetIndexing->processIndexForSource($sessionId, $offset, $sourceId);
		$this->returnJson(array('success' => (bool) $fileId));

	}

	/**
	 * Finish the indexing.
	 */
	public function actionFinishIndex()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$sources = craft()->request->getRequiredPost('sources');
		$command = craft()->request->getRequiredPost('command');
		$sessionId = craft()->request->getRequiredPost('sessionId');

		$sources = explode(",", $sources);

		$this->returnJson(craft()->assetIndexing->finishIndex($sessionId, $sources, $command));
	}
}
