<?php
/**
 * This file is part of the Solfege package.
 * 
 * @copyright (c) KCTH DEVELOPER <solfege@kcth.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.public
 **/

namespace KCTH\Solfege;

use KCTH\Solfege\KCTHSolfege;

/**
 * Template de Solfege
 * @package KCTH\Solfege
 * @author Koth R. Abougou <richard.abougou@gmail.com>
 */
abstract class KCTHSolfegeTemplate extends KCTHSolfege
{	
	protected string $elementPath = '../view/elements/';

	protected string $lang;
	protected array $meta;		# name|content
	protected array $link; 		# attr_name=attr_var|...								
	protected string $title;
	protected array $css;
	protected array $head_js;		# ? => public/js/? || src@? => ?
	protected $body;
	protected array $body_js;

	public const META 	= 'meta';
	public const LINK 	= 'link';
	public const CSS 	= 'css';
	public const JS_head = 'head_js';
	public const JS_body = 'body_js';
	

	public function __construct()
	{
		global $solfege;

		$this->lang = $solfege::appLang();
		$this->meta = ['charset|utf-8'];
		$this->link = [
			(!is_null($solfege::appFavicon())) ? 'rel=icon|type=image/png|href='.$solfege::appFavicon() : null
		]; 												
		$this->title = $solfege::appName();
		$this->css = [
			'app@style.css',
			'app@bs-directional-btn/bootstrap-directional-buttons.css'
		];
		$this->head_js = [
			// 'src@src/jQuery/jquery-3.3.1.min.js',
			'src@src/jQuery/jquery-3.7.0.min.js',
			'src@src/printThis/printThis.js',
			'app@script.js',
			'app@searchbar/searchbar_top.js',
			'app@tasks/taskRunnerGlobal.js',
		];
		$this->header = true;
		$this->inContainerMarginTop = $this->HTML_header->height ?? 0;
		$this->footer = true;
		$this->body_js = [
		];
	}

	//GETTERS
	public function getLang()
	{ 
		return $this->lang;
	}

	public function getHeadTags(string $tagName)
	{
		$this->getHtmlTags($tagName);
	}

	public function getBodyTags(string $tagName)
	{
		$this->getHtmlTags($tagName);
	}

	private function getHtmlTags(string $tagName)
	{	
		$filter = array('head_', 'body_');
		$replace = array('','');

		$this->{$tagName} !== [] ?
			require(self::$viewPath . self::$templateTagsPath . str_replace($filter, $replace, $tagName) . self::$viewExtension) : null;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function getBody()
	{
		return $this->body;
	}


	//SETTERS
	public function lang($newLang)
	{
		$this->lang = $newLang;
	}

	public function metaTag($addMetaTag)
	{
		foreach ($addMetaTag as $metaTag)
			array_push($this->meta, $metaTag);
	}

	public function linkTag($addLinkTag)
	{
		foreach ($addLinkTag as $linkTag)
			array_push($this->link, $linkTag);
	}

	public function title($newTitle)
	{
		$newTitle = str_replace('|appName', ' | '.$this->title, $newTitle);
		$this->title = $newTitle;
	}

	public function css($addCSS)
	{
		$addCSS = array_merge($addCSS);

		foreach ($addCSS as $css)
			if(!empty($css))
				if(!is_array($css)){ 
					if(!in_array($css, $this->css)) 
						array_push($this->css, $css);
				}else $this->css($css);
	}

	public function body($newBody)
	{
		$this->body = $newBody;
	}

	public function js($element, $addJS)
	{
		$addJS = array_merge($addJS);


		foreach ($addJS as $js)
			if(!empty($js))
				if(!is_array($js))
					switch($element)
					{
						case 'head':
							if(!in_array($js, $this->head_js)) 
								array_push($this->head_js, $js); 
							break;
						case 'body':
							if(!in_array($js, $this->body_js)) 
								array_push($this->body_js, $js); 
							break;
					}
				else
					switch($element)
					{
						case 'head':
							if(!in_array($js, $this->head_js)) 
								$this->head_js = array_merge($this->head_js, $js); 
							break;
						case 'body':
							if(!in_array($js, $this->body_js)) 
								$this->body_js = array_merge($this->body_js, $js); 
							break;
					}
	}
}