<?php

namespace KCTH\Solfege;

class KCTHSolfegeElementaryViewModifier
{
	PUBLIC CONST RESERVED_ATTRIBUTES = [
		'Modifier',
		'content',
		'containerTag',
		'containerClass'
	];

	public string $id = "";
	public string $class = "";
	public string $style = "";
	public string $content = "";
	public ?string $containerTag = null;
	public string $containerClass = "";

	public function addClass(string|array $class): void
	{
		$this->_addElement($this->class, $class);
	}

	public function removeClass(string|array $class): void
	{
		$this->_removeElement($this->class, $class);
	}

	public function addId(string|array $id): void
	{
		$this->_addElement($this->id, $id);
	}

	public function removeId(string|array $id): void
	{
		$this->_removeElement($this->id, $id);
	}

	private function _addElement($attribute, string|array $element): void
	{
		if(is_array($element)){
			foreach($element as $e) $this->_addElement($attribute, $e);

		}elseif(is_string($element)){
			$array = $this->_attrToArray($attribute);
			$array[] = $element;

			$attribute = $this->_attrToString($array);
		}
	}

	private function _removeElement($attribute, string|array $element): void
	{
		if(is_array($element)){
			foreach($element as $e) $this->_removeElement($attribute, $e);

		}elseif(is_string($element)){
			$array = $this->_attrToArray($attribute);
			$index = array_search($element, $array);
			unset($array[$index]);

			$attribute = $this->_attrToString($array);
		}

	}

	private function _attrToArray(string $attr): array
	{
		return explode(' ', $attr);
	}

	private function _attrToString(array $arrayAttr): string
	{
		return implode(' ', $arrayAttr);
	}
}