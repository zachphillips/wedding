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
 * Element type template variable
 */
class ElementTypeVariable extends BaseComponentTypeVariable
{
	/**
	 * Return a key/label list of the element type's sources.
	 *
	 * @return array|false
	 */
	public function getSources()
	{
		return $this->component->getSources();
	}
}
