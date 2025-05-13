<?php

namespace KCTH\Solfege;

use KCTH\Solfege\KCTHSolfegeElementaryViewModifier;
use KCTH\Solfege\KCTHSolfegeView;

/**
 * Vue élémentaire
 */
abstract class KCTHSolfegeElementaryView extends KCTHSolfegeView
{
	protected KCTHSolfegeElementaryViewModifier $Modifier;
	protected array $attributes = [];

	public function __construct(array $attributes = [])
	{
		$this->Modifier = new KCTHSolfegeElementaryViewModifier();

		foreach($attributes as $attr => $value)
			if(!in_array($attr, KCTHSolfegeElementaryViewModifier::RESERVED_ATTRIBUTES))
				$this->attributes[$attr] = $value;
	}

	/**
	 * Retourne un attribut
	 */
	protected function attribute(string|int $attr)
	{
		return $this->attributes[$attr] ?? null;
	}


	////////////////
	// RENDU HTML //
	////////////////
	abstract public function HTML(): string;

	public function show(?string $AS = null): string
	{
		if($AS == null)
			return $this->HTML();

		return $this->{"HTML__" . $AS}();
	}

	///////////////////////////
	// ATTRIBUTS ESTHETIQUEs //
	///////////////////////////

	public function modify(string|array $toModify, $modification = null): KCTHSolfegeElementaryView
	{
		if(is_array($toModify)){
			if($modification == null){
				foreach($toModify as $attr => $value)
					$this->modify($attr, $value);
			}else{
				foreach($toModify as $attr)
					$this->modify($attr, $modification);
			}
		}

		elseif(is_string($toModify)){
			$this->Modifier->{$toModify} = $modification;
		}

		return $this;
	}


	///////////////////////////////////
	// CONTENU DE LA VUE ELEMENTAIRE //
	///////////////////////////////////
	
	public function CONTENT(): string
	{
		if(is_null($this->Modifier->containerTag))
			return $this->Modifier->content;

		return <<< HTML
			<{$this->Modifier->containerTag} class="{$this->Modifier->containerClass}">{$this->Modifier->content}</{$this->Modifier->containerTag}>
		HTML;
	}

	public function CONTENT_CHANGE(?string $newContent): KCTHSolfegeElementaryView
	{
		$this->modify("content", $newContent);
		return $this;
	}

	public function CONTENT_ADD(?string $content): KCTHSolfegeElementaryView
	{
		$this->Modifier->content .= $content;
		return $this;
	}

	public function CONTENT_CLEAR(): KCTHSolfegeElementaryView
	{
		$this->modify("content", "");
		return $this;
	}

}

