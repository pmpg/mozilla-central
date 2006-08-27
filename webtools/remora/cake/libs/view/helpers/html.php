<?php
/* SVN FILE: $Id: html.php,v 1.1 2006-08-27 01:54:33 shaver%mozilla.org Exp $ */
/**
 * Html Helper class file.
 *
 * Simplifies the construction of HTML elements.
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs.view.helpers
 * @since			CakePHP v 0.9.1
 * @version			$Revision: 1.1 $
 * @modifiedby		$LastChangedBy: phpnut $
 * @lastmodified	$Date: 2006-08-27 01:54:33 $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Html Helper class for easy use of HTML widgets.
 *
 * HtmlHelper encloses all methods needed while working with HTML pages.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.view.helpers
 */
class HtmlHelper extends Helper {
/*************************************************************************
 * Public variables
 *************************************************************************/

/**#@+
 * @access public
 */
/**
 * Base URL
 *
 * @var string
 */
	var $base = null;
/**
 * URL to current action.
 *
 * @var string
 */
	var $here = null;
/**
 * Parameter array.
 *
 * @var array
 */
	var $params = array();
/**
 * Current action.
 *
 * @var string
 */
	var $action = null;
/**
 * Enter description here...
 *
 * @var array
 */
	var $data = null;
/**
 * Name of model this helper is attached to.
 *
 * @var string
 */
	var $model = null;
/**
 * Enter description here...
 *
 * @var string
 */
	var $field = null;
/**#@-*/
/*************************************************************************
 * Private variables
 *************************************************************************/
/**#@+
 * @access private
 */
/**
 * Breadcrumbs.
 *
 * @var	array
 * @access private
 */
	var $_crumbs = array();
/**
 * Adds a link to the breadcrumbs array.
 *
 * @param string $name Text for link
 * @param string $link URL for link
 */
	function addCrumb($name, $link) {
		$this->_crumbs[] = array($name, $link);
	}
/**
 * Returns a charset META-tag.
 *
 * @param  string  $charset
 * @param  boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function charset($charset, $return = false) {
		return $this->output(sprintf($this->tags['charset'], $charset), $return);
	}
/**
 * Finds URL for specified action.
 *
 * Returns an URL pointing to a combination of controller and action. Param
 * $url can be:
 *	+ Empty - the method will find adress to actuall controller/action.
 *	+ '/' - the method will find base URL of application.
 *	+ A combination of controller/action - the method will find url for it.
 *
 * @param  string  $url		Cake-relative URL, like "/products/edit/92" or "/presidents/elect/4"
 * @param  boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function url($url = null, $return = false) {
		$base = strip_plugin($this->base, $this->plugin);
		if (empty($url)) {
			return $this->here;
		} elseif($url{0} == '/') {
			$output = $base . $url;
		} else {
			$output = $base . '/' . strtolower($this->params['controller']) . '/' . $url;
		}

		return $this->output($output, $return);
	}
/**
 * Creates an HTML link.
 *
 * If $url starts with "http://" this is treated as an external link. Else,
 * it is treated as a path to controller/action and parsed with the
 * HtmlHelper::url() method.
 *
 * If the $url is empty, $title is used instead.
 *
 * @param  string  $title The content of the A tag.
 * @param  string  $url Cake-relative URL, or external URL (starts with http://)
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @param  string  $confirmMessage Confirmation message.
 * @param  boolean $escapeTitle	Whether or not the text in the $title variable should be HTML escaped.
 * @param  boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function link($title, $url = null, $htmlAttributes = null, $confirmMessage = false, $escapeTitle = true, $return = false) {
		if ($escapeTitle === true) {
			$title = htmlspecialchars($title, ENT_QUOTES);
		} elseif (is_string($escapeTitle)) {
			$title = htmlentities($title, ENT_QUOTES);
		}
		$url = $url ? $url : $title;

		if ($confirmMessage) {
			if ($escapeTitle === true || is_string($escapeTitle)) {
				$confirmMessage = htmlspecialchars($confirmMessage, ENT_NOQUOTES);
			} else {
				$confirmMessage = htmlentities($confirmMessage, ENT_NOQUOTES);
			}
			$confirmMessage = htmlentities($confirmMessage, ENT_NOQUOTES);
			$confirmMessage = str_replace("'", "\'", $confirmMessage);
			$confirmMessage = str_replace('"', '&quot;', $confirmMessage);
			$htmlAttributes['onclick']="return confirm('{$confirmMessage}');";
		}

		if (((strpos($url, '://')) || (strpos($url, 'javascript:') === 0) || (strpos($url, 'mailto:') === 0))) {
			$output = sprintf($this->tags['link'], $url, $this->_parseAttributes($htmlAttributes), $title);
		} else {
			$output = sprintf($this->tags['link'], $this->url($url, true), $this->_parseAttributes($htmlAttributes), $title);
		}
		return $this->output($output, $return);
	}
/**
 * Creates a submit widget.
 *
 * @param  string  $caption		Text on submit button
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @param  boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function submit($caption = 'Submit', $htmlAttributes = null, $return = false) {
		$htmlAttributes['value'] = $caption;
		return $this->output(sprintf($this->tags['submit'], $this->_parseAttributes($htmlAttributes, null, '', ' ')), $return);
	}
/**
 * Creates a password input widget.
 *
 * @param  string  $fieldName Name of a field, like this "Modelname/fieldname"
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @param  boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function password($fieldName, $htmlAttributes = null, $return = false) {
		$this->setFormTag($fieldName);
		if (!isset($htmlAttributes['value'])) {
			$htmlAttributes['value'] = $this->tagValue($fieldName);
		}
		if (!isset($htmlAttributes['id'])) {
			$htmlAttributes['id'] = $this->model . Inflector::camelize($this->field);
		}

		if ($this->tagIsInvalid($this->model, $this->field)) {
			if (isset($htmlAttributes['class']) && trim($htmlAttributes['class']) != "") {
				$htmlAttributes['class'] .= ' form_error';
			} else {
				$htmlAttributes['class'] = 'form_error';
			}
		}
		return $this->output(sprintf($this->tags['password'], $this->model, $this->field, $this->_parseAttributes($htmlAttributes, null, ' ', ' ')), $return);
	}
/**
 * Creates a textarea widget.
 *
 * @param  string  $fieldName Name of a field, like this "Modelname/fieldname"
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @param  boolean $return	Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function textarea($fieldName, $htmlAttributes = null, $return = false) {
		$this->setFormTag($fieldName);
		$value = $this->tagValue($fieldName);
		if (!empty($htmlAttributes['value'])) {
			$value = $htmlAttributes['value'];
			unset($htmlAttributes['value']);
		}
		if (!isset($htmlAttributes['id'])) {
			$htmlAttributes['id'] = $this->model . Inflector::camelize($this->field);
		}

		if ($this->tagIsInvalid($this->model, $this->field)) {
			if (isset($htmlAttributes['class']) && trim($htmlAttributes['class']) != "") {
				$htmlAttributes['class'] .= ' form_error';
			} else {
				$htmlAttributes['class'] = 'form_error';
			}
		}
		return $this->output(sprintf($this->tags['textarea'], $this->model, $this->field, $this->_parseAttributes($htmlAttributes, null, ' '), $value), $return);
	}
/**
 * Creates a checkbox widget.
 *
 * @param  string  $fieldName Name of a field, like this "Modelname/fieldname"
 * @deprecated  string  $title
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @param  boolean $return	Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function checkbox($fieldName, $title = null, $htmlAttributes = null, $return = false) {
		$value = $this->tagValue($fieldName);
		$notCheckedValue = 0;

		if (!isset($htmlAttributes['id'])) {
			$htmlAttributes['id'] = $this->model . Inflector::camelize($this->field);
		}

		if (isset($htmlAttributes['checked'])) {
			if ($htmlAttributes['checked'] == 'checked' || intval($htmlAttributes['checked']) === 1 || $htmlAttributes['checked'] === true) {
				$htmlAttributes['checked'] = 'checked';
			} else {
				$htmlAttributes['checked'] = null;
				$notCheckedValue = -1;
			}
		} else {
			if (isset($htmlAttributes['value'])) {
				$htmlAttributes['checked'] = ($htmlAttributes['value'] == $value) ? 'checked' : null;

				if ($htmlAttributes['checked'] == '0') {
					$notCheckedValue = -1;
				}
			} else {
				$model = new $this->model;
				$db =& ConnectionManager::getDataSource($model->useDbConfig);
				$value = $db->boolean($value);
				$htmlAttributes['checked'] = $value ? 'checked' : null;
				$htmlAttributes['value'] = 1;
			}
		}
		if (!isset($htmlAttributes['id'])) {
			$htmlAttributes['id'] = $this->model . Inflector::camelize($this->field);
		}
		$output = $this->hidden($fieldName, array('value' => $notCheckedValue, 'id' => $htmlAttributes['id'] . '_'), true);
		$output .= sprintf($this->tags['checkbox'], $this->model, $this->field, $this->_parseAttributes($htmlAttributes, null, '', ' '));
		return $this->output($output, $return);
	}
/**
 * Creates a link element for CSS stylesheets.
 *
 * @param string $path Path to CSS file
 * @param string $rel Rel attribute. Defaults to "stylesheet".
 * @param array $htmlAttributes Array of HTML attributes.
 * @param boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function css($path, $rel = 'stylesheet', $htmlAttributes = null, $return = false) {
		$url = "{$this->webroot}" . (COMPRESS_CSS ? 'c' : '') . CSS_URL . $this->themeWeb . $path . ".css";
		if ($rel == 'import') {
			return $this->output(sprintf($this->tags['style'], $this->parseHtmlOptions($htmlAttributes, null, '', ' '), '@import url(' . $url . ');'), $return);
		} else {
			return $this->output(sprintf($this->tags['css'], $rel, $url, $this->parseHtmlOptions($htmlAttributes, null, '', ' ')), $return);
		}
	}
/**
 * Creates file input widget.
 *
 * @param string $fieldName Name of a field, like this "Modelname/fieldname"
 * @param array $htmlAttributes Array of HTML attributes.
 * @param boolean $return Wheter this method should return a valueor output it. This overrides AUTO_OUTPUT.
 * @return mixed Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function file($fieldName, $htmlAttributes = null, $return = false) {
		if (strpos($fieldName, '/')) {
			$this->setFormTag($fieldName);
			if (!isset($htmlAttributes['id'])) {
				$htmlAttributes['id'] = $this->model . Inflector::camelize($this->field);
			}
			return $this->output(sprintf($this->tags['file'], $this->model, $this->field, $this->_parseAttributes($htmlAttributes, null, '', ' ')), $return);
		}
		return $this->output(sprintf($this->tags['file_no_model'], $fieldName, $this->_parseAttributes($htmlAttributes, null, '', ' ')), $return);
	}
/**
 * Returns the breadcrumb trail as a sequence of &raquo;-separated links.
 *
 * @param  string  $separator Text to separate crumbs.
 * @param  string  $startText This will be the first crumb, if false it defaults to first crumb in array
 * @param  boolean $return	Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return. If $this->_crumbs is empty, return null.
 */
	function getCrumbs($separator = '&raquo;', $startText = false, $return = false) {
		if (count($this->_crumbs)) {
			$out = array();
			if ($startText) {
				$out[] = $this->link($startText, '/');
			}

			foreach($this->_crumbs as $crumb) {
				$out[] = $this->link($crumb[0], $crumb[1]);
			}
			return $this->output(join($separator, $out), $return);
		} else {
			return null;
		}
	}
/**
 * Creates a hidden input field.
 *
 * @param  string  $fieldName Name of a field, like this "Modelname/fieldname"
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @param  boolean $return	Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT  and $return.
 */
	function hidden($fieldName, $htmlAttributes = null, $return = false) {
		$this->setFormTag($fieldName);
		if (!isset($htmlAttributes['value'])) {
			$htmlAttributes['value'] = $this->tagValue($fieldName);
		}
		if (!isset($htmlAttributes['id'])) {
			$htmlAttributes['id'] = $this->model . Inflector::camelize($this->field);
		}
		return $this->output(sprintf($this->tags['hidden'], $this->model, $this->field, $this->_parseAttributes($htmlAttributes, null, ' ', ' ')), $return);
	}
/**
 * Creates a formatted IMG element.
 *
 * @param string $path Path to the image file, relative to the webroot/img/ directory.
 * @param array	$htmlAttributes Array of HTML attributes.
 * @param boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function image($path, $htmlAttributes = null, $return = false) {
		if (strpos($path, '://')) {
			$url = $path;
		} else {
			$url = $this->webroot . IMAGES_URL . $this->themeWeb . $path;
		}
		return $this->output(sprintf($this->tags['image'], $url, $this->parseHtmlOptions($htmlAttributes, null, '', ' ')), $return);
	}
/**
 * Creates a text input widget.
 *
 * @param string $fieldNamem Name of a field, like this "Modelname/fieldname"
 * @param array $htmlAttributes Array of HTML attributes.
 * @param boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function input($fieldName, $htmlAttributes = null, $return = false) {
		$this->setFormTag($fieldName);
		if (!isset($htmlAttributes['value'])) {
			$htmlAttributes['value'] = $this->tagValue($fieldName);
		}

		if (!isset($htmlAttributes['type'])) {
			$htmlAttributes['type'] = 'text';
		}

		if (!isset($htmlAttributes['id'])) {
			$htmlAttributes['id'] = $this->model . Inflector::camelize($this->field);
		}

		if ($this->tagIsInvalid($this->model, $this->field)) {
			if (isset($htmlAttributes['class']) && trim($htmlAttributes['class']) != "") {
				$htmlAttributes['class'] .= ' form_error';
			} else {
				$htmlAttributes['class'] = 'form_error';
			}
		}
		return $this->output(sprintf($this->tags['input'], $this->model, $this->field, $this->_parseAttributes($htmlAttributes, null, ' ', ' ')), $return);
	}
/**
 * Creates a set of radio widgets.
 *
 * @param  string  $fieldName Name of a field, like this "Modelname/fieldname"
 * @param  array	$options			Radio button options array
 * @param  array	$inbetween		String that separates the radio buttons.
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @param  boolean $return	Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function radio($fieldName, $options, $inbetween = null, $htmlAttributes = array(), $return = false) {

		$this->setFormTag($fieldName);
		$value = isset($htmlAttributes['value']) ? $htmlAttributes['value'] : $this->tagValue($fieldName);
		$out = array();

		foreach($options as $optValue => $optTitle) {
			$optionsHere = array('value' => $optValue);
			$optValue == $value ? $optionsHere['checked'] = 'checked' : null;
			$parsedOptions = $this->parseHtmlOptions(array_merge($htmlAttributes, $optionsHere), null, '', ' ');
			$individualTagName = "{$this->field}_{$optValue}";
			$out[] = sprintf($this->tags['radio'], $this->model, $this->field, $individualTagName, $parsedOptions, $optTitle);
		}

		$out = join($inbetween, $out);
		return $this->output($out ? $out : null, $return);
	}
/**
 * Returns a row of formatted and named TABLE headers.
 *
 * @param array $names		Array of tablenames.
 * @param array $trOptions	HTML options for TR elements.
 * @param array $thOptions	HTML options for TH elements.
 * @param  boolean $return	Wheter this method should return a value
 * @return string
 */
	function tableHeaders($names, $trOptions = null, $thOptions = null, $return = false) {
		$out = array();
		foreach($names as $arg) {
			$out[] = sprintf($this->tags['tableheader'], $this->parseHtmlOptions($thOptions), $arg);
		}

		$data = sprintf($this->tags['tablerow'], $this->parseHtmlOptions($trOptions), join(' ', $out));
		return $this->output($data, $return);
	}
/**
 * Returns a formatted string of table rows (TR's with TD's in them).
 *
 * @param array $data		Array of table data
 * @param array $oddTrOptionsHTML options for odd TR elements
 * @param array $evenTrOptionsHTML options for even TR elements
 * @param  boolean $return	Wheter this method should return a value
 * @return string	Formatted HTML
 */
	function tableCells($data, $oddTrOptions = null, $evenTrOptions = null, $return = false) {
		if (empty($data[0]) || !is_array($data[0])) {
			$data = array($data);
		}
		static $count = 0;

		foreach($data as $line) {
			$count++;
			$cellsOut = array();

			foreach($line as $cell) {
				$cellsOut[] = sprintf($this->tags['tablecell'], null, $cell);
			}
			$options = $this->parseHtmlOptions($count % 2 ? $oddTrOptions : $evenTrOptions);
			$out[] = sprintf($this->tags['tablerow'], $options, join(' ', $cellsOut));
		}
		return $this->output(join("\n", $out), $return);
	}
/**
 * Returns value of $fieldName. Null if the tag does not exist.
 *
 * @param string $fieldName		Fieldname as "Modelname/fieldname" string
 * @return unknown Value of the named tag.
 */
	function tagValue($fieldName) {
		$this->setFormTag($fieldName);
		if (isset($this->params['data'][$this->model][$this->field])) {
			return h($this->params['data'][$this->model][$this->field]);
		} elseif(isset($this->data[$this->model][$this->field])) {
			return h($this->data[$this->model][$this->field]);
		}
		return false;
	}
/**
 * Returns false if given FORM field has no errors. Otherwise it returns the constant set in the array Model->validationErrors.
 *
 * @param string $model Model name as string
 * @param string $field		Fieldname as string
 * @return boolean True on errors.
 */
	function tagIsInvalid($model, $field) {
		return empty($this->validationErrors[$model][$field]) ? 0 : $this->validationErrors[$model][$field];
	}
/**
 * Returns number of errors in a submitted FORM.
 *
 * @return int Number of errors
 */
	function validate() {
		$args = func_get_args();
		$errors = call_user_func_array(array(&$this, 'validateErrors'),  $args);
		return count($errors);
	}
/**
 * Validates a FORM according to the rules set up in the Model.
 *
 * @return int Number of errors
 */
	function validateErrors() {
		$objects = func_get_args();
		if (!count($objects)) {
			return false;
		}

		$errors = array();
		foreach($objects as $object) {
			$errors = array_merge($errors, $object->invalidFields($object->data));
		}
		return $this->validationErrors = (count($errors) ? $errors : false);
	}
/**
 * Returns a formatted error message for given FORM field, NULL if no errors.
 *
 * @param string $field A field name, like "Modelname/fieldname"
 * @param string $text		Error message
 * @return string If there are errors this method returns an error message, else NULL.
 */
	function tagErrorMsg($field, $text) {
		$error = 1;
		$this->setFormTag($field);
		if ($error == $this->tagIsInvalid($this->model, $this->field)) {
			return sprintf('<div class="error_message">%s</div>', is_array($text) ? (empty($text[$error - 1]) ? 'Error in field' : $text[$error - 1]) : $text);
		} else {
			return null;
		}
	}
/**
 * Sets this helper's model and field properties to the slash-separated value-pair in $tagValue.
 *
 * @param string $tagValue A field name, like "Modelname/fieldname"
 */
	function setFormTag($tagValue) {
		return list($this->model, $this->field) = explode("/", $tagValue);
	}
/**#@-*/
/*************************************************************************
 * Private methods
 *************************************************************************/
/**#@+
 * @access private
 */
/**
 * Returns a space-delimited string with items of the $options array. If a
 * key of $options array happens to be one of:
 *	+ 'compact'
 *	+ 'checked'
 *	+ 'declare'
 *	+ 'readonly'
 *	+ 'disabled'
 *	+ 'selected'
 *	+ 'defer'
 *	+ 'ismap'
 *	+ 'nohref'
 *	+ 'noshade'
 *	+ 'nowrap'
 *	+ 'multiple'
 *	+ 'noresize'
 *
 * And its value is one of:
 *	+ 1
 *	+ true
 *	+ 'true'
 *
 * Then the value will be reset to be identical with key's name.
 * If the value is not one of these 3, the parameter is not output.
 *
 * @param  array  $options Array of options.
 * @param  array  $exclude Array of options to be excluded.
 * @param  string $insertBefore String to be inserted before options.
 * @param  string $insertAfter  String to be inserted ater options.
 * @return string
 */
	function _parseAttributes($options, $exclude = null, $insertBefore = ' ', $insertAfter = null) {
		$minimizedAttributes = array('compact', 'checked', 'declare', 'readonly', 'disabled', 'selected', 'defer', 'ismap', 'nohref', 'noshade', 'nowrap', 'multiple', 'noresize');
		if (!is_array($exclude)) {
			$exclude = array();
		}

		if (is_array($options)) {
			$out = array();

			foreach($options as $key => $value) {
				if (!in_array($key, $exclude)) {
					if (in_array($key, $minimizedAttributes) && ($value === 1 || $value === true || $value === 'true' || in_array($value, $minimizedAttributes))) {
						$value = $key;
					} elseif(in_array($key, $minimizedAttributes)) {
						continue;
					}
					$out[] = "{$key}=\"{$value}\"";
				}
			}
			$out = join(' ', $out);
			return $out ? $insertBefore . $out . $insertAfter : null;
		} else {
			return $options ? $insertBefore . $options . $insertAfter : null;
		}
	}
/**#@-*/
/*************************************************************************
 * Renamed methods
 *************************************************************************/
/**
 * @deprecated Name changed to 'textarea'. Version 0.9.2.
 * @see		HtmlHelper::textarea()
 * @param string  $tagName
 * @param integer $cols
 * @param integer $rows
 * @param array	$htmlAttributes Array of HTML attributes.
 * @param boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function areaTag($tagName, $cols = 60, $rows = 10, $htmlAttributes = null, $return = false) {
		$htmlAttributes['cols']=$cols;
		$htmlAttributes['rows']=$rows;
		return $this->textarea($tagName, $htmlAttributes, $return);
	}
/**
 * @deprecated Name changed to 'charset'. Version 0.9.2.
 * @see		HtmlHelper::charset()
 * @param string  $charset
 * @param boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function charsetTag($charset, $return = false) {
		return $this->charset($charset, $return);
	}
/**
 * @deprecated Name changed to 'checkbox'. Version 0.9.2.
 * @see		HtmlHelper::checkbox()
 * @param string  $fieldName If field is to be used for CRUD, this should be modelName/fieldName.
 * @param string  $title
 * @param array	$htmlAttributes Array of HTML attributes.
 * @param boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function checkboxTag($fieldName, $title = null, $htmlAttributes = null, $return = false) {
		return $this->checkbox($fieldName, $title, $htmlAttributes, $return);
	}
/**
 * @deprecated Name changed to 'css'. Version 0.9.2.
 * @see HtmlHelper::css()
 * @param string $path			Path to CSS file
 * @param string $rel			Rel attribute. Defaults to "stylesheet".
 * @param array	$htmlAttributes Array of HTML attributes.
 * @param boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function cssTag($path, $rel = 'stylesheet', $htmlAttributes = null, $return = false) {
		return $this->css($path, $rel, $htmlAttributes, $return);
	}
/**
 * @deprecated Name changed to 'file'. Version 0.9.2.
 * @see HtmlHelper::file()
 * @param  string  $fieldName Name of a field, like this "Modelname/fieldname"
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @param  boolean $return	Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function fileTag($fieldName, $htmlAttributes = null, $return = false) {
		return $this->file($fieldName, $htmlAttributes, $return);
	}
/**
 * @deprecated Name changed to 'hidden'. Version 0.9.2.
 * @see		HtmlHelper::hidden()
 * @param string  $fieldName If field is to be used for CRUD, this should be modelName/fieldName.
 * @param string  $value
 * @param array	$htmlAttributes Array of HTML attributes.
 * @param boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function hiddenTag($tagName, $value = null, $htmlOptions = null) {
		$this->setFormTag($tagName);
		$htmlOptions['value'] = $value ? $value : $this->tagValue($tagName);
		return $this->output(sprintf($this->tags['hidden'], $this->model, $this->field, $this->parseHtmlOptions($htmlOptions, null, '', ' ')));
	}
/**
 * @deprecated Name changed to 'image'. Version 0.9.2.
 * @see		HtmlHelper::image()
 * @param string  $path			Path to the image file.
 * @param string  $alt
 * @param array	$htmlAttributes Array of HTML attributes.
 * @param boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed Either string or boolean value, depends on AUTO_OUTPUT  and $return.
 */
	function imageTag($path, $alt = null, $htmlAttributes = null, $return = false) {
		$htmlAttributes['alt'] = $alt;
		return $this->image($path, $htmlAttributes, $return);
	}
/**
 * @deprecated Name changed to 'input'. Version 0.9.2.
 * @see HtmlHelper::input()
 * @param string $fieldName If field is to be used for CRUD, this  should be modelName/fieldName.
 * @param string $value
 * @param array $htmlAttributes Array of HTML attributes.
 * @param boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed Either string or boolean value, depends on AUTO_OUTPUT  and $return.
 */
	function inputTag($tagName, $size = 20, $htmlOptions = null) {
		$this->setFormTag($tagName);
		$htmlOptions['value'] = isset($htmlOptions['value']) ? $htmlOptions['value'] : $this->tagValue($tagName);
		$this->tagIsInvalid($this->model, $this->field) ? $htmlOptions['class'] = 'form_error' : null;
		return $this->output(sprintf($this->tags['input'], $this->model, $this->field, $this->parseHtmlOptions($htmlOptions, null, '', ' ')));
	}
/**
 * @deprecated Unified with 'link'. Version 0.9.2.
 * @see HtmlHelper::link()
 * @param  string  $title The content of the "a" tag.
 * @param  string  $url
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @param  boolean $escapeTitle	Whether or not the text in the $title variable should be HTML escaped.
 * @param  boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function linkOut($title, $url = null, $htmlAttributes = null, $escapeTitle = true, $return = false) {
		return $this->link($title, $url, $htmlAttributes, false, $escapeTitle, $return);
	}
/**
 * @deprecated Unified with 'link'. Version 0.9.2.
 * @see HtmlHelper::link()
 * @param  string  $title The content of the "a" tag.
 * @param  string  $url
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @param  string  $confirmMessage Confirmation message.
 * @param  boolean $escapeTitle	Whether or not the text in the $title variable should be HTML escaped.
 * @param  boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function linkTo($title, $url, $htmlAttributes = null, $confirmMessage = false, $escapeTitle = true, $return = false) {
		return $this->link($title, $url, $htmlAttributes, $confirmMessage, $escapeTitle, $return);
	}
/**
 * @deprecated Name changed to '_parseAttributes'. Version 0.9.2.
 * @see HtmlHelper::_parseAttributes()
 * @param  array  $options Array of options.
 * @param  array  $exclude Array of options to be excluded.
 * @param  string $insertBefore String to be inserted before options.
 * @param  string $insertAfter  String to be inserted ater options.
 * @return string
 */
	function parseHtmlOptions($options, $exclude = null, $insertBefore = ' ', $insertAfter = null) {
		if (!is_array($exclude))
				$exclude=array();

		if (is_array($options)) {
				$out=array();

				foreach($options as $k => $v) {
					if (!in_array($k, $exclude)) {
						$out[] = "{$k}=\"{$v}\"";
					}
				}
				$out = join(' ', $out);
				return $out ? $insertBefore . $out . $insertAfter : null;
		} else {
				return $options ? $insertBefore . $options . $insertAfter : null;
		}
	}
/**
 * @deprecated Name changed to 'password'. Version 0.9.2.
 * @see HtmlHelper::password()
 * @param  string  $fieldName Name of a field, like this "Modelname/fieldname"
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @param  boolean $return	Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function passwordTag($fieldName, $size = 20, $htmlAttributes = null, $return = false) {
		$args = func_get_args();
		return call_user_func_array(array(&$this,
					"password"),       $args);
	}
/**
 * @deprecated Name changed to 'radio'. Version 0.9.2.
 * @see HtmlHelper::radio()
 * @param  string  $fieldName Name of a field, like this "Modelname/fieldname"
 * @param  array	$options
 * @param  array	$inbetween
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @param  boolean $return	Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return.
 */
	function radioTags($fieldName, $options, $inbetween = null, $htmlAttributes = null, $return = false) {
		return $this->radio($fieldName, $options, $inbetween, $htmlAttributes, $return);
	}
 /**
 * Returns a formatted SELECT element.
 *
 * @param string $fieldName Name attribute of the SELECT
 * @param array $optionElements Array of the OPTION elements (as 'value'=>'Text' pairs) to be used in the SELECT element
 * @param mixed $selected Selected option
 * @param array $selectAttr Array of HTML options for the opening SELECT element
 * @param array $optionAttr Array of HTML options for the enclosed OPTION elements
 * @param boolean $show_empty If true, the empty select option is shown
 * @param  boolean $return         Whether this method should return a value
 * @return string Formatted SELECT element
 */
	function selectTag($fieldName, $optionElements, $selected = null, $selectAttr = null, $optionAttr = null, $showEmpty = true, $return = false) {
		$this->setFormTag($fieldName);
		if ($this->tagIsInvalid($this->model, $this->field)) {
			if (isset($selectAttr['class']) && trim($selectAttr['class']) != "") {
				$selectAttr['class'] .= ' form_error';
			} else {
				$selectAttr['class'] = 'form_error';
			}
		}
		if (!isset($selectAttr['id'])) {
			$selectAttr['id'] = $this->model . Inflector::camelize($this->field);
		}

		if (!is_array($optionElements)) {
			return null;
		}

		if (!isset($selected)) {
			$selected = $this->tagValue($fieldName);
		}

		if (isset($selectAttr) && array_key_exists("multiple", $selectAttr)) {
			$select[] = sprintf($this->tags['selectmultiplestart'], $this->model, $this->field, $this->parseHtmlOptions($selectAttr));
		} else {
			$select[] = sprintf($this->tags['selectstart'], $this->model, $this->field, $this->parseHtmlOptions($selectAttr));
		}

		if ($showEmpty == true) {
			$select[] = sprintf($this->tags['selectempty'], $this->parseHtmlOptions($optionAttr));
		}

		foreach($optionElements as $name => $title) {
			$optionsHere = $optionAttr;

			if (($selected !== null) && ($selected == $name)) {
				$optionsHere['selected'] = 'selected';
			} else if(is_array($selected) && array_key_exists($name, $selected)) {
				$optionsHere['selected'] = 'selected';
			}

			$select[] = sprintf($this->tags['selectoption'], $name, $this->parseHtmlOptions($optionsHere), $title);
		}

		$select[] = sprintf($this->tags['selectend']);
		return $this->output(implode("\n", $select), $return);
	}
/**
 * @deprecated Name changed to 'url'. Version 0.9.2.
 * @see HtmlHelper::url()
 */
	function urlFor($url) {
		return $this->url($url);
	}
/**
 * @deprecated Name changed to 'submit'. Version 0.9.2.
 * @see HtmlHelper::submit()
 */
	function submitTag() {
		$args = func_get_args();
		return call_user_func_array(array(&$this, "submit"), $args);
	}
/*************************************************************************
 * Moved methods
 *************************************************************************/
/**
 * @deprecated Moved to TextHelper. Version 0.9.2.
 */
	function trim() {
		die("Method HtmlHelper::trim() was moved to TextHelper::trim().");
	}
/**
 * @deprecated Moved to JavascriptHelper. Version 0.9.2.
 */
	function javascriptIncludeTag($url) {
		die("Method HtmlHelper::javascriptIncludeTag() was moved to JavascriptHelper::link().");
	}
/**
 * @deprecated Moved to JavascriptHelper. Version 0.9.2.
 */
	function javascriptTag($script) {
		die("Method HtmlHelper::javascriptTag() was moved to JavascriptHelper::codeBlock().");
	}
/*************************************************************************
 * Deprecated methods
 *************************************************************************/
/**
 * Returns an HTML FORM element.
 *
 * @param string $target URL for the FORM's ACTION attribute.
 * @param string $type		FORM type (POST/GET).
 * @param array  $htmlAttributes
 * @return string An formatted opening FORM tag.
 * @deprecated This is very WYSIWYG unfriendly, use HtmlHelper::url() to get contents of "action" attribute. Version 0.9.2.
 */
	function formTag($target = null, $type = 'post', $htmlAttributes = null) {
		$htmlAttributes['action']=$this->UrlFor($target);
		$htmlAttributes['method']=$type == 'get' ? 'get' : 'post';
		$type == 'file' ? $htmlAttributes['enctype'] = 'multipart/form-data' : null;
		$token='';

		if (isset($this->params['_Token']) && !empty($this->params['_Token'])) {
				$token = $this->hidden('_Token/key', array('value' => $this->params['_Token']['key']), true);
		}

		return sprintf($this->tags['form'], $this->parseHtmlOptions($htmlAttributes, null, '')) . $token;
	}

/**
 * Generates a nested unordered list tree from an array.
 *
 * @param array	$data
 * @param array	$htmlAttributes
 * @param string  $bodyKey
 * @param string  $childrenKey
 * @param boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT.
 * @return mixed	Either string or boolean value, depends on AUTO_OUTPUT and $return. If $this->_crumbs is empty, return null.
 * @deprecated This seems useless. Version 0.9.2.
 */
	function guiListTree($data, $htmlAttributes = null, $bodyKey = 'body', $childrenKey = 'children', $return = false) {
		$out="<ul" . $this->_parseAttributes($htmlAttributes) . ">\n";
		foreach($data as $item) {
				$out .= "<li>{$item[$bodyKey]}\n";
				if (isset($item[$childrenKey]) && is_array($item[$childrenKey]) && count($item[$childrenKey])) {
					$out .= $this->guiListTree($item[$childrenKey], $htmlAttributes, $bodyKey, $childrenKey);
				}
				$out .= "</li>\n";
		}
		$out .= "</ul>\n";
		return $this->output($out, $return);
	}

/**
 * Returns a mailto: link.
 *
 * @param string $title Title of the link, or the e-mail address (if the same).
 * @param string $email E-mail address if different from title.
 * @param array  $options
 * @return string Formatted A tag
 * @deprecated This should be done using a content filter. Version 0.9.2.
 */
	function linkEmail($title, $email = null, $options = null) {
		// if no $email, then title contains the email.
		if (empty($email))
				$email=$title;

		$match=array();

		// does the address contain extra attributes?
		preg_match('!^(.*)(\?.*)$!', $email, $match);

		// plaintext
		if (empty($options['encode']) || !empty($match[2])) {
				return sprintf($this->tags['mailto'], $email, $this->parseHtmlOptions($options), $title);
		}
		// encoded to avoid spiders
		else {
				$email_encoded=null;

				for($ii = 0; $ii < strlen($email); $ii++) {
					if (preg_match('!\w!', $email[$ii])) {
						$email_encoded .= '%' . bin2hex($email[$ii]);
					} else {
						$email_encoded .= $email[$ii];
					}
				}

				$title_encoded=null;

				for($ii = 0; $ii < strlen($title); $ii++) {
					$title_encoded .= preg_match('/^[A-Za-z0-9]$/', $title[$ii])
						? '&#x' . bin2hex($title[$ii]) . ';' : $title[$ii];
				}

				return sprintf($this->tags['mailto'],                              $email_encoded,
									$this->parseHtmlOptions($options, array('encode')), $title_encoded);
		}
	}

/**
 * Returns a generic HTML tag (no content).
 *
 * Examples:
 *	+ <code>tag("br") => <br /></code>
 *	+ <code>tag("input", array("type" => "text")) => <input type="text"/></code>
 *
 * @param string $name Name of HTML element
 * @param array  $options HTML options
 * @param bool	$open Is the tag open or closed? (defaults to closed "/>")
 * @returnstring The formatted HTML tag
 * @deprecated This seems useless. Version 0.9.2.
 */
	function tag($name, $options = null, $open = false) {
		$tag = "<$name " . $this->parseHtmlOptions($options);
		$tag .= $open ? ">" : " />";
		return $tag;
	}

/**
 * Returns a generic HTML tag with content.
 *
 * Examples:
 * <code>
 * content_tag("p", "Hello world!") => <p>Hello world!</p>
 * content_tag("div", content_tag("p", "Hello world!"),
 * array("class" => "strong")) => <div class="strong"><p>Hello world!</p></div>
 * </code>
 *
 * @param  string $name	Name of HTML element
 * @param  array  $options HTML options
 * @param  bool	$open	Is the tag open or closed? (defaults to closed "/>")
 * @return string The formatted HTML tag
 * @deprecated This seems useless. Version 0.9.2.
 */
	function contentTag($name, $content, $options = null) {
		return "<$name " . $this->parseHtmlOptions($options) . ">$content</$name>";
	}
/**
 * Returns a SELECT element for days.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @deprecated  string $value
 * @param string $selected Option which is selected.
 * @param array $optionAttr Attribute array for the option elements.
 * @param boolean $showEmpty Show/hide the empty select option
 * @return string
 */
	function dayOptionTag($tagName, $value = null, $selected = null, $selectAttr = null, $optionAttr = null, $showEmpty = true) {
		if (empty($selected) && ($this->tagValue($tagName))) {
			$selected = date('d', strtotime($this->tagValue($tagName)));
		}

		$dayValue = empty($selected) ? date('d') : $selected;
		$days = array('01' => '1', '02' => '2', '03' => '3', '04' => '4', '05' => '5', '06' => '6', '07' => '7', '08' => '8', '09' => '9', '10' => '10', '11' => '11', '12' => '12', '13' => '13', '14' => '14', '15' => '15', '16' => '16', '17' => '17', '18' => '18', '19' => '19', '20' => '20', '21' => '21', '22' => '22', '23' => '23', '24' => '24', '25' => '25', '26' => '26', '27' => '27', '28' => '28', '29' => '29', '30' => '30', '31' => '31');
		$option = $this->selectTag($tagName . "_day", $days, $dayValue, $selectAttr, $optionAttr, $showEmpty);
		return $option;
	}
/**
 * Returns a SELECT element for years
 *
 * @param string $tagName Prefix name for the SELECT element
 * @deprecated  string $value
 * @param integer $minYear First year in sequence
 * @param integer $maxYear Last year in sequence
 * @param string $selected Option which is selected.
 * @param array $optionAttr Attribute array for the option elements.
 * @param boolean $showEmpty Show/hide the empty select option
 * @return string
 */
	function yearOptionTag($tagName, $value = null, $minYear = null, $maxYear = null, $selected = null, $selectAttr = null, $optionAttr = null, $showEmpty = true) {
		if (empty($selected) && ($this->tagValue($tagName))) {
			$selected = date('Y', strtotime($this->tagValue($tagName)));
		}

		$yearValue = empty($selected) ? date('Y') : $selected;
		$currentYear = date('Y');
		$maxYear = is_null($maxYear) ? $currentYear + 11 : $maxYear + 1;
		$minYear = is_null($minYear) ? $currentYear - 60 : $minYear;

		if ($minYear > $maxYear) {
			$tmpYear = $minYear;
			$minYear = $maxYear;
			$maxYear = $tmpYear;
		}

		$minYear = $currentYear < $minYear ? $currentYear : $minYear;
		$maxYear = $currentYear > $maxYear ? $currentYear : $maxYear;

		for($yearCounter = $minYear; $yearCounter < $maxYear; $yearCounter++) {
			$years[$yearCounter] = $yearCounter;
		}

		$option = $this->selectTag($tagName . "_year", $years, $yearValue, $selectAttr, $optionAttr, $showEmpty);
		return $option;
	}
/**
 * Returns a SELECT element for months.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @deprecated  string $value
 * @param string $selected Option which is selected.
 * @param array $optionAttr Attribute array for the option elements.
 * @param boolean $showEmpty Show/hide the empty select option
 * @return string
 */
	function monthOptionTag($tagName, $value = null, $selected = null, $selectAttr = null, $optionAttr = null, $showEmpty = true) {
		if (empty($selected) && ($this->tagValue($tagName))) {
			$selected = date('m', strtotime($this->tagValue($tagName)));
		}
		$monthValue = empty($selected) ? date('m') : $selected;
		$months = array('01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December');

		$option = $this->selectTag($tagName . "_month", $months, $monthValue, $selectAttr, $optionAttr, $showEmpty);
		return $option;
	}
/**
 * Returns a SELECT element for hours.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @deprecated  string $value
 * @param boolean $format24Hours True for 24 hours format
 * @param string $selected Option which is selected.
 * @param array $optionAttr Attribute array for the option elements.
 * @return string
 */
	function hourOptionTag($tagName, $value = null, $format24Hours = false, $selected = null, $selectAttr = null, $optionAttr = null, $showEmpty = true) {
		if (empty($selected) && ($this->tagValue($tagName))) {
			if ($format24Hours) {
				$selected = date('H', strtotime($this->tagValue($tagName)));
			} else {
				$selected = date('g', strtotime($this->tagValue($tagName)));
			}
		}

		if ($format24Hours) {
			$hourValue = !isset($selected) ? date('H') : $selected;
		} else {
			$hourValue = !isset($selected) ? date('g') : $selected;
			if (intval($hourValue) == 0) {
				$hourValue = 12;
			}
		}

		if ($format24Hours) {
			$hours = array('00' => '00', '01' => '01', '02' => '02', '03' => '03', '04' => '04', '05' => '05', '06' => '06', '07' => '07', '08' => '08', '09' => '09', '10' => '10', '11' => '11', '12' => '12', '13' => '13', '14' => '14', '15' => '15', '16' => '16', '17' => '17', '18' => '18', '19' => '19', '20' => '20', '21' => '21', '22' => '22', '23' => '23');
		} else {
			$hours = array('01' => '1', '02' => '2', '03' => '3', '04' => '4', '05' => '5', '06' => '6', '07' => '7', '08' => '8', '09' => '9', '10' => '10', '11' => '11', '12' => '12');
		}

		$option = $this->selectTag($tagName . "_hour", $hours, $hourValue, $selectAttr, $optionAttr, $showEmpty);
		return $option;
	}
/**
 * Returns a SELECT element for minutes.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @deprecated  string $value
 * @param string $selected Option which is selected.
 * @param array $optionAttr Attribute array for the option elements.
 * @return string
 */
	function minuteOptionTag($tagName, $value = null, $selected = null, $selectAttr = null, $optionAttr = null, $showEmpty = true) {
		if (empty($selected) && ($this->tagValue($tagName))) {
			$selected = date('i', strtotime($this->tagValue($tagName)));
		}
		$minValue = !isset($selected) ? date('i') : $selected;

		for($minCount = 0; $minCount < 60; $minCount++) {
			$mins[sprintf('%02d', $minCount)] = sprintf('%02d', $minCount);
		}
		$option = $this->selectTag($tagName . "_min", $mins, $minValue, $selectAttr, $optionAttr, $showEmpty);
		return $option;
	}

/**
 * Returns a SELECT element for AM or PM.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @deprecated  string $value
 * @param string $selected Option which is selected.
 * @param array $optionAttr Attribute array for the option elements.
 * @return string
 */
	function meridianOptionTag($tagName, $value = null, $selected = null, $selectAttr = null, $optionAttr = null, $showEmpty = true) {
		if (empty($selected) && ($this->tagValue($tagName))) {
			$selected = date('a', strtotime($this->tagValue($tagName)));
		}
		$merValue = empty($selected) ? date('a') : $selected;
		$meridians = array('am' => 'am', 'pm' => 'pm');

		$option = $this->selectTag($tagName . "_meridian", $meridians, $merValue, $selectAttr, $optionAttr, $showEmpty);
		return $option;
	}
/**
 * Returns a set of SELECT elements for a full datetime setup: day, month and year, and then time.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @param string $dateFormat DMY, MDY, YMD or NONE.
 * @param string $timeFormat 12, 24, NONE
 * @param string $selected Option which is selected.
 * @param array $optionAttr Attribute array for the option elements.
 * @return string The HTML formatted OPTION element
 */
	function dateTimeOptionTag($tagName, $dateFormat = 'DMY', $timeFormat = '12', $selected = null, $selectAttr = null, $optionAttr = null, $showEmpty = true) {
		$day      = null;
		$month    = null;
		$year     = null;
		$hour     = null;
		$min      = null;
		$meridian = null;

		if (empty($selected)) {
			$selected = $this->tagValue($tagName);
		}

		if (!empty($selected)) {

			if (is_int($selected)) {
				$selected = strftime('%G-%m-%d  %T', $selected);
				$selected = strftime('%G-%m-%d  %H:%M:%S', $selected);
			}

			$meridian = 'am';
			$pos = strpos($selected, '-');
			if($pos !== false){
				$date = explode('-', $selected);
				$days = explode(' ', $date[2]);
				$day = $days[0];
				$month = $date[1];
				$year = $date[0];
			} else {
				$days[1] = $selected;
			}

			if ($timeFormat != 'NONE' && !empty($timeFormat)) {
				$time = explode(':', $days[1]);

				if (($time[0] > 12) && $timeFormat == '12') {
					$time[0] = $time[0] - 12;
					$meridian = 'pm';
				} elseif($time[0] > 12) {
					$meridian = 'pm';
				}

				$hour = $time[0];
				$min = $time[1];
			}
		}

		$elements = array('Day','Month','Year','Hour','Minute','Meridian');
		if (isset($selectAttr['id'])) {
			if (is_string($selectAttr['id'])) {
				// build out an array version
				foreach ($elements as $element) {
					$selectAttrName = 'select' . $element . 'Attr';
					${$selectAttrName} = $selectAttr;
					${$selectAttrName}['id'] = $selectAttr['id'] . $element;
				}
			} elseif (is_array($selectAttr['id'])) {
				// check for missing ones and build selectAttr for each element
				foreach ($elements as $element) {
					$selectAttrName = 'select' . $element . 'Attr';
					${$selectAttrName} = $selectAttr;
					${$selectAttrName}['id'] = $selectAttr['id'][strtolower($element)];
				}
			}
		} else {
			// build the selectAttrName with empty id's to pass
			foreach ($elements as $element) {
				$selectAttrName = 'select' . $element . 'Attr';
				${$selectAttrName} = $selectAttr;
			}
		}

		switch($dateFormat) {
			case 'DMY': // so uses the new selex
				$opt = $this->dayOptionTag($tagName, null, $day, $selectDayAttr, $optionAttr, $showEmpty) . '-' .
				$this->monthOptionTag($tagName, null, $month, $selectMonthAttr, $optionAttr, $showEmpty) . '-' . $this->yearOptionTag($tagName, null, null, null, $year, $selectYearAttr, $optionAttr, $showEmpty);
			break;
			case 'MDY':
				$opt = $this->monthOptionTag($tagName, null, $month, $selectMonthAttr, $optionAttr, $showEmpty) . '-' .
				$this->dayOptionTag($tagName, null, $day, $selectDayAttr, $optionAttr, $showEmpty) . '-' . $this->yearOptionTag($tagName, null, null, null, $year, $selectYearAttr, $optionAttr, $showEmpty);
			break;
			case 'YMD':
				$opt = $this->yearOptionTag($tagName, null, null, null, $year, $selectYearAttr, $optionAttr, $showEmpty) . '-' .
				$this->monthOptionTag($tagName, null, $month, $selectMonthAttr, $optionAttr, $showEmpty) . '-' .
				$this->dayOptionTag($tagName, null, $day, $selectDayAttr, $optionAttr, $showEmpty);
			break;
			case 'NONE':
			default:
				$opt = '';
			break;
		}

		switch($timeFormat) {
			case '24':
				$opt .= $this->hourOptionTag($tagName, null, true, $hour, $selectHourAttr, $optionAttr, $showEmpty) . ':' .
				$this->minuteOptionTag($tagName, null, $min, $selectMinuteAttr, $optionAttr, $showEmpty);
			break;
			case '12':
				$opt .= $this->hourOptionTag($tagName, null, false, $hour, $selectHourAttr, $optionAttr, $showEmpty) . ':' .
				$this->minuteOptionTag($tagName, null, $min, $selectMinuteAttr, $optionAttr, $showEmpty) . ' ' .
				$this->meridianOptionTag($tagName, null, $meridian, $selectMeridianAttr, $optionAttr, $showEmpty);
			break;
			case 'NONE':
			default:
				$opt .= '';
			break;
		}
		return $opt;
	}
}

?>