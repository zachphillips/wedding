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
 * Base element model class
 */
abstract class BaseElementModel extends BaseModel
{
	protected $elementType;

	private $_content;
	private $_preppedContent;
	private $_tags;

	const ENABLED  = 'enabled';
	const DISABLED = 'disabled';
	const ARCHIVED = 'archived';

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'          => AttributeType::Number,
			//'type'        => array(AttributeType::String, 'default' => $this->elementType),
			'enabled'     => array(AttributeType::Bool, 'default' => true),
			'archived'    => array(AttributeType::Bool, 'default' => false),
			'locale'      => AttributeType::Locale,
			'uri'         => AttributeType::String,
			'dateCreated' => AttributeType::DateTime,
			'dateUpdated' => AttributeType::DateTime,
		);
	}

	/**
	 * Returns the type of element this is.
	 *
	 * @return string
	 */
	public function getElementType()
	{
		return $this->elementType;
	}

	/**
	 * Returns the element's full URL.
	 *
	 * @return string
	 */
	public function getUrl()
	{
		if ($this->uri !== null)
		{
			return UrlHelper::getSiteUrl($this->uri);
		}
	}

	/**
	 * Returns the element's CP edit URL.
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		return false;
	}

	/**
	 * Returns the URL to the element's thumbnail, if there is one.
	 *
	 * @param int|null $size
	 * @return string|false
	 */
	public function getThumbUrl($size = null)
	{
		return false;
	}

	/**
	 * Returns the URL to the element's icon image, if there is one.
	 *
	 * @param int|null $size
	 * @return string|false
	 */
	public function getIconUrl($size = null)
	{
		return false;
	}

	/**
	 * Returns the element's status.
	 *
	 * @return string|null
	 */
	public function getStatus()
	{
		if ($this->archived)
		{
			return static::ARCHIVED;
		}
		else if (!$this->enabled)
		{
			return static::DISABLED;
		}
		else
		{
			return static::ENABLED;
		}
	}

	/**
	 * Returns a new ElementCriteriaModel prepped to return this element's same-type children.
	 *
	 * @param mixed $field
	 * @return ElementCriteriaModel
	 */
	public function getChildren($field = null)
	{
		$criteria = craft()->elements->getCriteria($this->elementType);
		$criteria->childOf($this);
		$criteria->childField($field);
		return $criteria;
	}

	/**
	 * Returns a new ElementCriteriaModel prepped to return this element's same-type parents.
	 *
	 * @param mixed $field
	 * @return ElementCriteriaModel
	 */
	public function getParents($field = null)
	{
		$criteria = craft()->elements->getCriteria($this->elementType);
		$criteria->parentOf($this);
		$criteria->parentField($field);
		return $criteria;
	}

	/**
	 * Is set?
	 *
	 * @param $name
	 * @return bool
	 */
	function __isset($name)
	{
		if (parent::__isset($name))
		{
			return true;
		}

		// Is $name a field handle?
		$field = craft()->fields->getFieldByHandle($name);
		if ($field)
		{
			return true;
		}


		return false;
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 * @throws \Exception
	 * @return mixed
	 */
	function __get($name)
	{
		// Run through the BaseModel/CModel stuff first
		try
		{
			return parent::__get($name);
		}
		catch (\Exception $e)
		{
			// Is $name a field handle?
			$field = craft()->fields->getFieldByHandle($name);
			if ($field)
			{
				return $this->_getPreppedContentForField($field);
			}

			// Fine, throw the exception
			throw $e;
		}
	}

	/**
	 * Returns the raw content saved on this entity.
	 *
	 * @param string|null $fieldHandle
	 * @return mixed
	 */
	public function getRawContent($fieldHandle = null)
	{
		$content = $this->getContent();

		if ($fieldHandle)
		{
			if (isset($content->$fieldHandle))
			{
				return $content->$fieldHandle;
			}
			else
			{
				return null;
			}
		}
		else
		{
			return $content;
		}
	}

	/**
	 * Sets content that's indexed by the field ID.
	 *
	 * @param array $content
	 */
	public function setContentIndexedByFieldId($content)
	{
		$this->_content = new ContentModel();

		foreach ($content as $fieldId => $value)
		{
			$field = craft()->fields->getFieldById($fieldId);
			if ($field)
			{
				$fieldHandle = $field->handle;
				$this->_content->$fieldHandle = $value;
			}
		}
	}

	/**
	 * Sets the content.
	 *
	 * @param array $values
	 */
	public function setContent($values)
	{
		$content = $this->getContent();
		$content->setAttributes($values);
	}

	/**
	 * Populates a new model instance with a given set of attributes.
	 *
	 * @static
	 * @param mixed $values
	 * @return BaseModel
	 */
	public static function populateModel($values)
	{
		// Strip out the element record attributes if this is getting called from a child class
		// based on an Active Record result eager-loaded with the ElementRecord
		if (isset($values['element']))
		{
			$elementAttributes = $values['element'];
			unset($values['element']);
		}

		$model = parent::populateModel($values);

		// Now set those ElementRecord attributes
		if (isset($elementAttributes))
		{
			if (isset($elementAttributes['i18n']))
			{
				$model->setAttributes($elementAttributes['i18n']);
				unset($elementAttributes['i18n']);
			}

			$model->setAttributes($elementAttributes);
		}

		return $model;
	}

	/**
	 * Returns the content for the element.
	 *
	 * @return ContentModel
	 */
	public function getContent()
	{
		if (!isset($this->_content))
		{
			if ($this->id)
			{
				$this->_content = craft()->content->getContent($this->id, $this->locale);
			}

			if (empty($this->_content))
			{
				$this->_content = new ContentModel();
			}
		}

		return $this->_content;
	}

	/**
	 * Returns the prepped content for a given field.
	 *
	 * @param FieldModel $field
	 * @return mixed
	 */
	private function _getPreppedContentForField(FieldModel $field)
	{
		if (!isset($this->_preppedContent) || !array_key_exists($field->handle, $this->_preppedContent))
		{
			$content = $this->getContent();
			$fieldHandle = $field->handle;

			if (isset($content->$fieldHandle))
			{
				$value = $content->$fieldHandle;
			}
			else
			{
				$value = null;
			}

			$fieldType = craft()->fields->populateFieldType($field, $this);

			if ($fieldType)
			{
				$value = $fieldType->prepValue($value);
			}

			$this->_preppedContent[$field->handle] = $value;
		}

		return $this->_preppedContent[$field->handle];
	}
}
