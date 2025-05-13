<?php
/**
 * This file is part of the Solfege package.
 * 
 * @copyright (c) KCTH DEVELOPER <solfege@kcth.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 **/

namespace KCTH\Solfege;

use KCTH\Solfege\KCTHSolfege;
use KCTH\Solfege\KCTHSolfegeTemplate as Template;

/**
 * La vue de Solfege
 * @package KCTH\Solfege
 * @author Koth R. Abougou <richard.abougou@gmail.com>
 */
class KCTHSolfegeView extends KCTHSolfege
{	
	/**
	 * Charge la Vue qui à la Template HTML associe la @class KCTH\Solfege\KCTHSolfegeTemplate.
	 * @param  string $view
	 * @param  Template $template Fonction qui retourne la template
	 * @param  array $params Les variables importées
	 */
	public static function render(string $view, Template $template, $params=[])
	{
		global $solfege;
        
        $tp = $template;

        if(count(explode('@', $view))>1)
        {
            $layoutView = self::folderFile($view);
            $layoutName = $layoutView['folder'];
            $view = $layoutView['file'];
        }
        else $layoutName = self::$layoutName;


		//chargement des vues
		ob_start();
		extract($params);
		require(self::$viewPath . $view . self::$viewExtension);
		$template->body(ob_get_clean());
		
		isset($title) ? $template->title($title) : null;

		//chargement du layout
		require(self::$viewPath . self::$layoutPath . $layoutName . self::$viewExtension);
	}

	/**
     * Charge un morceau de Vue qui à du HTML associe la @class KCTH\Solfege\KCTHSolfegePartials
     *
     * ATTENTION le mot clé ${$params['class']} sera réservé à la manipulation de la Partial.
     */
    public static function loadPartials(string $partial_file, array $params = [])
    {
        global $cacheSystem;
        global $solfege;

        $partial_class = $params['class'] ?? 'Partial';
        $condition     = $params['if'] ?? true;
        $foreach       = $params['foreach'] ?? null;
        $foreachAs     = $params['foreach_as'] ?? 'value';
        $foreachIf     = $params['foreach_if'] ?? true;
        $variables     = $params['vars'] ?? [];
        $disabled      = $params['disabled'] ?? false;
        $dependencies  = $params['dependencies'] ?? [];
        $cache         = $params['cache'] ?? null;

        $access        = $params['access'] ?? null;
        $accessible    = true;

        // === Vérification de l'access === //
        
        if($access != null){
            foreach($access as $ACCESS_FUNC => $ROLES)
                if(SELF::ACCESS($ACCESS_FUNC, $ROLES[0], $ROLES[1] ?? null) == false){ 
                    $accessible = false; break; 
                }
        }else $accessible = true;

        // ===  Mise en dépendance de l'itération du Foreach === //
        
        $depForeachAs  = isset($dependencies[':foreach_as']) && $dependencies[':foreach_as'] == true && !empty($foreach) ? 
        $dependencies[':foreach_as'] : false;

        // ===================================================== //


        if($disabled === false && $condition === true && $accessible === true)
        {

            // CACHE START
            if(empty($cache) || (!empty($cache) &&
                $cacheSystem::start($cache['rename'] ?? "tmp/" . self::$viewPartialsPath . $partial_file . ($cache['suffix'] ?? null) . self::$viewExtension, $cache['duration'] ?? false, $cache['condition_toStart'] ?? true)))

            { #cache_scope_start

            // Instanciation de la vue partielle...
            switch($partial_class)
            {
                // ... dans une classe générique

                case 'Partial':
                    $partial_name = "\\app\\template\\partials\\Partial";
                    $partial_class = new $partial_name($dependencies);

                    // alias
                    if(isset($params['as']))
                        ${$params['as']} = $partial_class;

                    break;
                
                // ... dans une classe particulière
                default:
                    $default_var = $params['PARTIAL_DEFAULT_VAR'] ?? false;

                    $partial_name = "\\app\\template\\partials\\{$partial_class}Partial";
                    ${strtolower($partial_class)} = new $partial_name($dependencies);

                    // alias
                    if(isset($params['as']))
                        ${$params['as']} = ${strtolower($partial_class)};

                    // alias par défaut
                    if($default_var === true)
                        $partial = ${strtolower($partial_class)};

                    break;
            }


            // Déclaration des variables de la scope
            $vars2 = [];
            foreach($variables as $key => $variable)
            {
                if(is_string($key)) $vars2[$key] = $variable;
                else extract($variable);
            }
            extract($vars2);

            $__PARTIAL_FILE__ = self::$viewPartialsPath . $partial_file;

            // Chargement des vues partielles
            if(isset($foreach))
            {
                $i = 0;
                foreach($foreach as $key => ${$foreachAs})
                {
                    if($foreachIf === true && !empty(${$foreachAs}))
                    {   
                        // déclare la variable itérative de dépendance.
                        if($depForeachAs === true && !empty(${$foreachAs}))
                        {
                            ${strtolower($partial_class)}::${$foreachAs} = ${$foreachAs};
                            ${strtolower($partial_class)}::$__PARTIAL_FILE__ = $__PARTIAL_FILE__;
                            ${strtolower($partial_class)}::$i = $i;

                        }

                        require(self::$viewPath . self::$viewPartialsPath . $partial_file . self::$viewExtension);

                        $i++;
                    }
                }

            }else{
                if(is_string($partial_class)){
                    ${strtolower($partial_class)}::$__PARTIAL_FILE__ = $__PARTIAL_FILE__;
                }elseif(is_object($partial_class)){
                    $partial_class::$__PARTIAL_FILE__ = $__PARTIAL_FILE__;
                }
                
                require(self::$viewPath . self::$viewPartialsPath . $partial_file . self::$viewExtension);
            }

            }#end_cache_scope

            // CACHE END
            if(SELF::$CACHE_SYSTEM && !empty($cache))
                $cacheSystem::end($cache['condition_toMemorize'] ?? true, $cache['callback'] ?? null);
        }
    }

    /**
     * Charger une vue élémentaire
     * proveneant généralement d'un framework front
     */

    public static function loadElement(string $element_name, array $attributes = [], ?string $from = null)
    {

        $layoutElementaryName = $from ?? self::$layoutElementaryName;

        $element_class = "\\app\\template\\element\\".$layoutElementaryName."{$element_name}ElementaryView";
        return new $element_class($attributes);
    }


    /**
     * @param array $params [
     *  container | content | html | placement | attr | class | style
     * ]
     */
    public static function tooltip($message, array $params = [])
    {
        $CLASS      = $params['class'] ?? null;
        $STYLE      = $params['style'] ?? null;
        $CONTAINER  = $params['container'] ?? "#tooltip";
        $CONTENT    = $params['content'] ?? "tooltip";
        $HTML       = $params['html'] ?? "false";
        $PLACEMENT  = $params['placement'] ?? "bottom";
        $ATTR       = $params['attr'] ?? null;
        $ATTRS      = self::moreAttributes($ATTR);
        $CONDITION  = $params['if'] ?? true;

        return $CONDITION ? <<< HTML
            <span class="{$CLASS}" data-bs-container="$CONTAINER" data-bs-toggle="tooltip" data-bs-html="$HTML" data-bs-placement="$PLACEMENT" data-bs-original-title="$CONTENT" role="button" style="$STYLE" $ATTRS>
            $message
            </span>
        HTML :  NULL;
    }
    
    /**
     * @param array $params [
     *  container | content | placement | custom | trigger | title | class | style | access | if | attr
     * ]
     */
    public static function popoverBtn($message , array $params = []): ?string
    {

        $BTN_CLASS      = $params['class'] ?? "btn-default";
        $BTN_STYLE      = $params['style'] ?? null;
        $ACCESS         = $params['access'] ?? null;
        $CONDITION      = $params['if'] ?? true;

        $CONTAINER      = $params['container'] ?? "body";
        $PLACEMENT      = $params['placement'] ?? "top";
        $CUSTOM         = $params['custom'] ?? null; $CUSTOM = !is_null($CUSTOM) ? "data-bs-custom-class=\"$CUSTOM-popover\"" : null;
        $TRIGGER        = $params['trigger'] ?? null; $TRIGGER = !is_null($TRIGGER) ? "data-bs-trigger=\"$TRIGGER\"" : null;
        $TITLE          = $params['title'] ?? null;
        $CONTENT        = $params['content'] ?? null;

        $ATTR       = $params['attr'] ?? null;
        $ATTRS      = self::moreAttributes($ATTR);

        if($ACCESS !== null)
            foreach($ACCESS as $ACCESS_FUNC => $ROLES)
                if(SELF::ACCESS($ACCESS_FUNC, $ROLES[0], $ROLES[1] ?? null) == false)
                    return null;

        return $CONDITION ? <<< HTML
            <a type="button" class="btn $BTN_CLASS" style="$BTN_STYLE"  data-bs-container="$CONTAINER" data-bs-toggle="popover" data-bs-placement="$PLACEMENT" $CUSTOM $TRIGGER data-bs-title="$TITLE" data-bs-content="$CONTENT" $ATTRS>$message</a>
        HTML : null;
    }

    /**
     * @param array|null $params [
     *  element | modal_class | task-runner | class | style | title | access | if | onclick | toggle_modal | attr
     * ]
     */
    public static function modalBtn(string $message, ?array $params = []): ?string
    {
        $ELEMENT        = $params['element'] ?? "button";
        $MODAL_ID       = $params['modal_class'] ?? '#SaasModal';
        $MODAL_CONTENT  = isset($params['task-runner']) ? "MODAL:{$params['task-runner']}" : null;
        $BTN_CLASS      = $params['class'] ?? "btn-default";
        $BTN_STYLE      = $params['style'] ?? null;
        $BTN_TITLE      = $params['title'] ?? null;
        $ACCESS         = $params['access'] ?? null;
        $CONDITION      = $params['if'] ?? true; 
        $JS_ONCLICK     = $params['onclick'] ?? "window.trw($(this));"; 
        $TOGGLE_MODAL   = $params['toggle_modal'] ?? true; 
        $TOGGLE_MODAL   = $TOGGLE_MODAL ? 'data-bs-toggle="modal"' : null;
        $HREF           = $ELEMENT == "a" ? 'href="javascript:void(0);"' : null;

        $ATTR       = $params['attr'] ?? null;
        $ATTRS      = self::moreAttributes($ATTR);

        $JS_ONCLICK = SELF::JS_ACTION_TREATMENT($JS_ONCLICK);

        if($ACCESS !== null)
            foreach($ACCESS as $ACCESS_FUNC => $ROLES)
                if(SELF::ACCESS($ACCESS_FUNC, $ROLES[0], $ROLES[1] ?? null) == false)
                    return null;

        return $CONDITION ? <<< HTML
            <$ELEMENT $HREF type="button" class="btn $BTN_CLASS" $TOGGLE_MODAL data-bs-target="$MODAL_ID" style="$BTN_STYLE" task-runner="$MODAL_CONTENT" onclick="$JS_ONCLICK" title="$BTN_TITLE" $ATTRS>$message</$ELEMENT>
        HTML : null;
    }

    /**
     * @param array|null $params [
     *  content | class | onclick | style | title | access | if | offcanvas | attr
     * ]
     */
    public static function trElement(string $tag, ?string $TASK_RUNNER, ?array $params = []): ?string
    {
        $TYPE       = $tag == "button" ? 'Type="button"' : null;
        $CONTENT    = $params['content'] ?? null;
        $CLASS      = $params['class'] ?? null;
        $JS_ONCLICK = $params['onclick'] ?? "window.trw($(this));";
        $STYLE      = $params['style'] ?? null;
        $TITLE      = $params['title'] ?? null;
        $ACCESS     = $params['access'] ?? null;
        $CONDITION  = $params['if'] ?? true;    
        $OFFC       = $params['offcanvas'] ?? false; 

        $ATTR       = $params['attr'] ?? null;
        $ATTRS      = self::moreAttributes($ATTR);

        $JS_ONCLICK = SELF::JS_ACTION_TREATMENT($JS_ONCLICK);

        $OFFCANVAS  = $OFFC ? ' data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"' : null;

        if($ACCESS !== null)
            foreach($ACCESS as $ACCESS_FUNC => $ROLES){
                if(SELF::ACCESS($ACCESS_FUNC, $ROLES[0], $ROLES[1] ?? null) == false)
                    return null;
            }
    
        return $CONDITION ? <<< HTML
            <$tag $TYPE class="$CLASS" style="$STYLE" title="$TITLE" task-runner="$TASK_RUNNER" onclick="$JS_ONCLICK"$OFFCANVAS $ATTRS>$CONTENT</$tag>
        HTML : null;
    }

    public static function htmlLabel(?string $content = null, $params = []): string
    {
        $ATTRS = self::moreAttributes($params);

        return <<< HTML
            <label {$ATTRS}>{$content}</label>
        HTML;

    }

    public static function htmlInputGroup(array $contents = []): string
    {
        $CONTENT = "";
        $innerHTML = "";

        foreach($contents as $key => $html)
        {
            switch($key)
            {
                case 'content': $innerHTML = $html; break;

                default:
                    $innerHTML = <<< HTML
                        <span class="input-group-{$key}">{$html}</span>
                    HTML;
                    break;
            }

            $CONTENT .= $innerHTML;
        }
        return <<< HTML
            <div class="input-group">
                {$CONTENT}
            </div>
        HTML;
    }

    /**
     * @param array|null $params [
     *  class | onchange | title | access | name | if | checked | style | id | required | value | placeholder | datalist | disabled | readonly | label | attr
     * ]
     */
    public static function trInput(string $type, ?string $TASK_RUNNER, ?array $params = []): ?string
    {
        $CLASS          = $params['class']       ?? "form-control";
        $JS_ONCHANGE    = $params['onchange']    ??  (!is_null($TASK_RUNNER) && $TASK_RUNNER != "#" ? "window.trw($(this));" : null);
        $TITLE          = $params['title']       ?? null;
        $ACCESS         = $params['access']      ?? null;
        $NAME           = $params['name']        ?? null;
        $CONDITION      = $params['if']          ?? true;    
        $CHECKED        = $params['checked']     ?? false;  $checkATTR = $CHECKED ? "checked" : null;
        $STYLE          = $params['style']       ?? null;   $styleATTR = !is_null($STYLE)       ? "style=\"{$STYLE}\"" : null;
        $ID             = $params['id']          ?? null;   $idATTR    = !is_null($ID)          ? "id=\"{$ID}\"" : null;
        $REQUIRED       = $params['required']    ?? null;   $reqATTR   = !is_null($REQUIRED)    ? "required" : null;
        $VALUE          = $params['value']       ?? null;   $valATTR   = !is_null($VALUE)       ? "value=\"{$VALUE}\"" : null;
        $PLACEHOLDER    = $params['placeholder'] ?? null;   $phATTR    = !is_null($PLACEHOLDER) ? "placeholder=\"{$PLACEHOLDER}\"" : null;
        $DATALIST       = $params['datalist']    ?? null;
        $DISABLED       = isset($params['disabled']) && $params['disabled'] == true ? "disabled" : null;
        $READONLY       = isset($params['readonly']) && $params['readonly'] == true ? "readonly" : null;
        $LABEL          = $params['label'] ?? null;
        $LABEL_PLACEMENT = strtoupper($params['label-placement'] ?? "END");
        $LABEL_START = null;
        $LABEL_END = null;

        $LABEL_P = "LABEL_{$LABEL_PLACEMENT}";
        $$LABEL_P = $LABEL;

        $JS_ONCHANGE = SELF::JS_ACTION_TREATMENT($JS_ONCHANGE);


        $ATTR       = $params['attr'] ?? null;
        $ATTRS      = self::moreAttributes($ATTR);


        if($ACCESS !== null)
            foreach($ACCESS as $ACCESS_FUNC => $ROLES){
                if(SELF::ACCESS($ACCESS_FUNC, $ROLES[0], $ROLES[1] ?? null) == false)
                    return null;
            }

        $DTLIST = "";
        $LIST1 = "";
        if(!is_null($DATALIST))
        {
            $type = "text";
            $LIST1 = !empty($DATALIST['list']) ? "list=\"{$DATALIST['list']}\"" : null;
            $LIST2 = !empty($DATALIST['list']) ? "id=\"{$DATALIST['list']}\"" : null;

            $DATA = $DATALIST['datas'];

            $OPTIONS = "";
            foreach($DATA as $d)
                $OPTIONS .= <<< HTML
                    <option value="$d">
                HTML;

            $DTLIST = <<< HTML
                <datalist $LIST2>
                    $OPTIONS
                </datalist>
            HTML;

        }
    
        $INPUT = <<< HTML
            <input type="$type" $LIST1 name="$NAME" $valATTR $phATTR $idATTR class="$CLASS" $styleATTR title="$TITLE" task-runner="$TASK_RUNNER" onchange="$JS_ONCHANGE" $DISABLED $checkATTR $reqATTR $ATTRS $READONLY/>
        HTML;

        return $CONDITION ? $LABEL_START.$INPUT.$DTLIST.$LABEL_END : null;
    }

    /**
     * @param array|null $params [
     *  class | onchange | title | access | name | if | checked | style | id | required | value | placeholder | datalist | disabled | readonly | label | attr
     * ]
     */
    public static function htmlInput(string $type, ?array $params = []): ?string
    {
        return self::trInput($type, null, $params);
    }

    /**
     * @param array|null $params [
     *  dismissible | class | type | icon | if | button
     * ]
     */
    public static function alert(string $content, array $params = []): ?string
    {
        $DISSMISS   = $params['dismissible'] ?? true;
        $class      = $params['class'] ?? "border-0";
        $type       = $params['type'] ?? "danger";
        $ICON       = $params['icon'] ?? null;
        $CONDITION  = $params['if'] ?? true;
        $BTN_ACTION = $params['button'] ?? null;
        $BTN_ACTION_HTML = "";

        $type_alert = str_replace('-lighten', '', $type);
        $type_alert = str_replace('-transparent', '', $type_alert);

        if(str_ends_with($type, '-lighten') || str_ends_with($type, '-transparent')){
            $text_color = "text-{$type_alert}"; $btn_color = "dark";
        }else{
            $text_color = "text-white"; $btn_color = "white";
        }

        if(is_array($BTN_ACTION)) 
            foreach($BTN_ACTION as $b_action)
                $BTN_ACTION_HTML .= $b_action;
        else $BTN_ACTION_HTML = $BTN_ACTION;

        $CONTENT = $ICON.$content;

        $class_dismissible = $DISSMISS ? "alert-dismissible" : null;
        $BTN_DISMISS = $DISSMISS ? <<< HTML
            <button type="button" class="btn-close btn-close-$btn_color" data-bs-dismiss="alert" aria-label="Close"></button> 
        HTML : null;



        return $CONDITION ? <<< HTML
            <div class="alert alert-$type_alert $class_dismissible bg-$type $class $text_color fade show" role="alert">
                $BTN_DISMISS
                $CONTENT
                $BTN_ACTION_HTML
            </div>
        HTML : null;
    }

    /**
     * @param array|null $params [
     *  content | icon | class | arrow
     * ]
     */
    public static function htmlDropdown(array $params = [])
    {
        $CONTENT    = $params['content'] ?? null;
        $ICON       = $params['icon'] ?? self::icon(SELF::ICON_DOTS);
        $CLASS      = $params['class'] ?? "card-drop";
        $ARROW      = $params['arrow'] ?? "arrow-none";

        $BUTTONS = "";
        foreach($params['buttons'] ?? [] as $button)
            $BUTTONS .= $button;

        return <<< HTML
            <div class="dropdown">
                <a href="#" class="dropdown-toggle $ARROW $CLASS" data-bs-toggle="dropdown" aria-expanded="false">
                    $CONTENT$ICON
                </a>
                <div class="dropdown-menu dropdown-menu-end" style="">
                    $BUTTONS
                </div>
            </div>
        HTML;
    }

    /**
     * @param array|null $params [
     *  btn | class_btn2 | class
     * ]
     */
    public static function btnDropdown(array $params = [])
    {
        $BUTTON     = $params['btn'];
        $CLASS_BTN2 = $params['class_btn2'] ?? "btn-primary";
        $CLASS      = $params['class'] ?? null;

        $BUTTONS = "";
        foreach($params['buttons'] ?? [] as $button)
            $BUTTONS .= $button;
        
        return <<< HTML
            <div class="btn-group {$CLASS}">
                {$BUTTON}
                <button type="button" class="btn dropdown-toggle-split dropdown-toggle {$CLASS_BTN2}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                </button>
                <div class="dropdown-menu dropdown-menu-left">
                    {$BUTTONS}
                </div>
            </div>   
        HTML;
    }

    /**
     * @param array|null $params [
     *  titre | access | name | class | onchange | if | selected | id | style | required | placeholder | disabled | readonly | optgroups | attrr
     * ]
     */
    public static function trSelect(?string $TASK_RUNNER, array $options = [], ?array $params = []): ?string
    {
        $TITLE          = $params['title']       ?? null;
        $ACCESS         = $params['access']      ?? null;
        $NAME           = $params['name']        ?? null;
        $CLASS          = $params['class']       ?? null;
        $JS_ONCHANGE    = $params['onchange']    ?? (!is_null($TASK_RUNNER) && $TASK_RUNNER != "#" ? "window.trw($(this));" : null);
        $CONDITION      = $params['if']          ?? true;
        $SELECTED_VAL   = $params['selected']    ?? null;
        $ID             = $params['id']          ?? null;   $idATTR    = !is_null($ID)          ? "id=\"{$ID}\"" : null; 
        $STYLE          = $params['style']       ?? null;   $styleATTR = !is_null($STYLE)       ? "style=\"{$STYLE}\"" : null;
        $REQUIRED       = $params['required']    ?? null;   $reqATTR   = !is_null($REQUIRED)    ? "required" : null;
        $PLACEHOLDER    = $params['placeholder'] ?? null;   $phATTR    = !is_null($PLACEHOLDER) ? "placeholder=\"{$PLACEHOLDER}\"" : null;
        $DISABLED       = isset($params['disabled']) && $params['disabled'] == true ? "disabled" : null;
        $READONLY       = isset($params['readonly']) && $params['readonly'] == true ? "readonly" : null;

        $OPTGROUPS      = $params['optgroups'] ?? null;

        $ATTR       = $params['attr'] ?? null;
        $ATTRS      = self::moreAttributes($ATTR);

        if($ACCESS !== null)
            foreach($ACCESS as $ACCESS_FUNC => $ROLES){
                if(SELF::ACCESS($ACCESS_FUNC, $ROLES[0], $ROLES[1] ?? null) == false)
                    return null;
            }

        $SELECT = <<< HTML
            <select name="$NAME" $phATTR $idATTR class="form-select $CLASS" $styleATTR title="$TITLE" task-runner="$TASK_RUNNER" onchange="$JS_ONCHANGE" $reqATTR $ATTRS $DISABLED $READONLY/>
        HTML;


        if(empty($OPTGROUPS))
        foreach($options as $value => $option)
        {


            $SELECTED = null;

            if($SELECTED_VAL != null && $SELECTED_VAL != "" && (str_ends_with($value, '::selected') || $value == $SELECTED_VAL)){
                $SELECTED = "selected";
                $value = trim(str_replace("::selected", '', $value));
            }

            $SELECT .= !str_ends_with($value, '::hidden') ? <<< HTML
                <option value="$value" $SELECTED>$option</option>
            HTML : NULL;
        }

        if(!empty($OPTGROUPS))
        foreach($OPTGROUPS as $indexs => $groupName)
        {
            $SELECT .= <<< HTML
                <optgroup label="$groupName">
            HTML;

            foreach(array_slice($options, intval(explode(':', $indexs)[0]), self::offset(intval(explode(':', $indexs)[1]), count($options)-1)) as $value => $option)
            {

                $value = array_search($option, $options);

                $SELECTED = null;

                if($SELECTED_VAL != null && $SELECTED_VAL != "" && (str_ends_with($value, '::selected') || $value == $SELECTED_VAL)){
                    $SELECTED = "selected";
                    $value = trim(str_replace("::selected", '', $value));
                }

                $SELECT .= !str_ends_with($value, '::hidden') ? <<< HTML
                    <option value="$value" $SELECTED>$option</option>
                HTML : NULL;
            }


            $SELECT .= <<< HTML
                </optgroup>
            HTML;
        }


        $SELECT .= <<< HTML
            </select>
        HTML;
    
        return $CONDITION ? $SELECT : null;
    }

    
    protected static function moreAttributes(?array $ATTR = null): string
    {
        $ATTRS = '';

        if(!empty($ATTR))
            foreach($ATTR as $key => $attr)   
                $ATTRS .= " {$key}=\"{$attr}\"";

        return $ATTRS;
    }

    private static function offset($offsetval, $offsetreplace): int
    {
        return $offsetval == -1 ? $offsetreplace : $offsetval;
    }

    public static function htmlSelect(array $options = [], ?array $params = []): ?string
    {
        return self::trSelect(null, $options, $params);
    }

    private static function optgroupTag(string $mode, ?array $optgroups, int $index, int $nbOptions): ?string
    {
        $nbOptions--;
        $nbOptions = strval($nbOptions);
        $index = strval($index);

        if(empty($optgroups)) return null;

        foreach($optgroups as $indexs => $name)
        {
            $indexFROM = explode(':', $indexs)[0];
            $indexTO = explode(':', $indexs)[1];

            if($mode == "open" && $index == $indexFROM)
                return <<< HTML
                    <optgroup label="$name">
                HTML;

            if($mode == "close" && ($index = $nbOptions || $indexTO == "end"))
                return <<< HTML
                    </optgroup>
                HTML;

            return null;
        } 
    }

    PRIVATE STATIC FUNCTION JS_ACTION_TREATMENT($JS): ?string
    {
        if(is_null($JS)) return null;

        switch($JS)
        {
            case str_starts_with($JS, ":ALERT "):
                $message = explode(' ', $JS, 2)[1];
                $message = addslashes($message);
                $TREATMENT = <<< JS
                    alert('$message');
                JS;
                break;

            case ':SIMULATED':
                $TREATMENT = <<< JS
                    alert('Action simulée.');
                JS;
                break;

            case ':NOT_READY':
                $TREATMENT = <<< JS
                    alert('Cette fonctionnalité n\'est pas prête.');
                JS;
                break;

            case ':BLOCKED':
                $TREATMENT = <<< JS
                    alert('Cette fonctionnalité est temporairement bloquée');
                JS;
                break;


            default: $TREATMENT = $JS; break;
        }


        return $TREATMENT;
    }

    public static function nbsp(int $n = 1, int $g = 4)
    {
        $NBSP = "";

        for($i=0; $i < $n; $i ++)
            for ($j=0; $j < $g; $j++)
                $NBSP .= <<< HTML
                    &nbsp;
                HTML;

        return $NBSP;
    }


    // Les icons
    
    public const ICON_DELETE        = "mdi mdi-delete";
    public const ICON_DELETE_2      = "ri-delete-bin-5-fill";
    public const ICON_REMOVE_FILE   = "mdi mdi-file-remove";
    public const ICON_EDIT          = "ri-edit-2-fill";
    public const ICON_EDIT_2        = "mdi mdi-pencil";
    public const ICON_EDIT_3        = "mdi mdi-pencil-box-outline";
    public const ICON_EDIT_4        = "mdi mdi-circle-edit-outline";
    public const ICON_EDIT_ACCOUNT  = "mdi mdi-account-edit";
    public const ICON_ADD           = "uil-plus";
    public const ICON_ADD_2         = "mdi mdi-plus-circle";
    public const ICON_ADD_3         = "mdi mdi-plus-circle-outline";
    public const ICON_ADD_FILE      = "mdi mdi-file-plus";
    public const ICON_ADD_USER      = "mdi mdi-account-plus";
    public const ICON_VALIDATE      = "ri-check-fill";
    public const ICON_CHECK         = "mdi mdi-checkbox-marked-circle-outline";
    public const ICON_RESTORE       = "mdi mdi-restore";
    public const ICON_RESTORE_2     = "mdi mdi-delete-restore";
    public const ICON_RESTORE_FILE  = "mdi mdi-file-restore";
    public const ICON_PHONE         = "mdi mdi-phone";
    public const ICON_PIN           = "ri-map-pin-fill";
    public const ICON_NOTE          = "ri-file-edit-fill";
    public const ICON_NOTE_2        = "ri-file-text-line";
    public const ICON_NOTE_3        = "ri-file-edit-line";
    public const ICON_NOTE_4        = "mdi mdi-file-document-edit-outline";
    public const ICON_NOTE_5        = "mdi mdi-file-document-outline";
    public const ICON_DOCUMENT      = "mdi mdi-file-document-multiple-outline";
    public const ICON_EYE           = "mdi mdi-eye";
    public const ICON_EMAIL         = "mdi mdi-email-fast";
    public const ICON_COPY          = "ri-file-copy-2-fill";
    public const ICON_SIGN          = "mdi mdi-file-sign";
    public const ICON_FIRE          = "ri-fire-line";
    public const ICON_FIRE_2        = "ri-fire-fill";
    public const ICON_FACE_NEUTRAL  = "mdi mdi-emoticon-neutral-outline";
    public const ICON_COLD          = "uil-snowflake-alt";
    public const ICON_EURO          = "uil-euro-circle";
    public const ICON_BOARD         = "mdi mdi-clipboard-edit";
    public const ICON_BOARD_4       = "mdi mdi-clipboard-edit-outline";
    public const ICON_BOARD_1       = "ri-clipboard-fill";
    public const ICON_BOARD_2       = "ri-survey-line";
    public const ICON_BOARD_3       = "ri-survey-fill";
    public const ICON_AROBASE       = "ri-at-line";
    public const ICON_MANAGER       = "mdi mdi-account-tie";
    public const ICON_FOLDER        = "ri-folder-5-fill";
    public const ICON_SETTINGS      = "ri-settings-3-line";
    public const ICON_SETTINGS_2    = "ri-settings-4-line";
    public const ICON_SETTINGS_3    = "ri-settings-4-fill";
    public const ICON_FILTER        = "uil-filter";
    public const ICON_CALENDAR      = "ri-calendar-check-line";
    public const ICON_CALENDAR_2    = "ri-calendar-check-fill";
    public const ICON_CALENDAR_3    = "ri-calendar-event-line";
    public const ICON_CALENDAR_4    = "ri-calendar-event-fill";
    public const ICON_CALENDAR_5    = "mdi mdi-calendar-clock";
    public const ICON_CALENDAR_6    = "mdi mdi-calendar-check";
    public const ICON_TODO          = "mdi mdi-clipboard-check-outline";
    public const ICON_TODO_CHECK    = "mdi mdi-clipboard-check";
    public const ICON_TODO_ALERT    = "mdi mdi-clipboard-alert-outline";
    public const ICON_TODO_WAIT     = "mdi mdi-clipboard-clock-outline";
    public const ICON_HANDSHAKE     = "mdi mdi-handshake";
    public const ICON_ALERT         = "mdi mdi-alert-circle";
    public const ICON_CLOCK         = "mdi mdi-clock-time-four";
    public const ICON_CLOCK_ALERT   = "mdi mdi-clock-alert";
    public const ICON_CLOCK_ALERT_2 = "mdi mdi-clock-alert-outline";
    public const ICON_CLOCK_PLUS    = "mdi mdi-clock-plus";
    public const ICON_CLOCK_PLUS_2  = "mdi mdi-clock-plus-outline";
    public const ICON_LOCK          = "mdi mdi-lock";
    public const ICON_FILE_LOCK     = "mdi mdi-file-lock";
    public const ICON_ARROW_LEFT    = "ri-arrow-left-circle-line";
    public const ICON_ARROW_RIGHT   = "mdi mdi-arrow-right-thin";
    public const ICON_ANGLE_RIGHT   = "uil-angle-right-b";
    public const ICON_PLOT          = "uil-no-entry";
    public const ICON_ARROW_UP_DOWN = "mdi mdi-arrow-up-down";
    public const ICON_ARROW_UP      = "mdi mdi-arrow-up";
    public const ICON_ARROW_UP_2    = "ri-arrow-up-fill";
    public const ICON_ARROW_UP_3    = "ri-arrow-up-circle-fill";
    public const ICON_ARROW_UP_4    = "ri-arrow-up-circle-line";
    public const ICON_ARROW_UP_5    = "mdi mdi-arrow-up-bold-box";
    public const ICON_ARROW_UP_6    = "mdi mdi-arrow-up-box";
    public const ICON_ARROW_UP_7    = "mdi mdi-arrow-up-bold";
    public const ICON_ARROW_UP_8    = "mdi mdi-chevron-up-box";
    public const ICON_ARROW_UP_9    = "mdi mdi-chevron-up";
    public const ICON_ARROW_DOWN    = "mdi mdi-arrow-down";
    public const ICON_ARROW_DOWN_2  = "ri-arrow-down-fill";
    public const ICON_ARROW_DOWN_3  = "ri-arrow-down-circle-fill";
    public const ICON_ARROW_DOWN_4  = "ri-arrow-down-circle-line";
    public const ICON_ARROW_DOWN_5  = "mdi mdi-arrow-down-bold-box";
    public const ICON_ARROW_DOWN_6  = "mdi mdi-arrow-down-box";
    public const ICON_ARROW_DOWN_7  = "mdi mdi-arrow-down-bold";
    public const ICON_ARROW_DOWN_8  = "mdi mdi-chevron-down-box";
    public const ICON_WARNING       = "ri-alert-line";
    public const ICON_INFO          = "mdi mdi-information";
    public const ICON_INFO_2        = "mdi mdi-information-outline";
    public const ICON_INFO_3        = "mdi mdi-information-variant";
    public const ICON_CLOSE         = "mdi mdi-close";
    public const ICON_CLOSE_2       = "mdi mdi-close-box-outline";
    public const ICON_ONGLET_NEW    = "mdi mdi-open-in-new";

    public const ICON_DOWNLOAD      = "mdi mdi-download-circle";

    public const ICON_NETWORK_CHECK    = "mdi mdi-check-network";
    public const ICON_NETWORK_CHECK_2  = "mdi mdi-check-network-outline";
    public const ICON_NETWORK_HELP     = "mdi mdi-help-network";
    public const ICON_NETWORK_HELP_2   = "mdi mdi-help-network-outline";
    public const ICON_NETWORK_PLAY     = "mdi mdi-play-network-outline";
    public const ICON_NETWORK_PLUS     = "mdi mdi-plus-network";
    public const ICON_NETWORK_PLUS_2   = "mdi mdi-plus-network-outline";

    public const ICON_CART = "mdi mdi-cart-variant";
    public const ICON_CART_ADD = "mdi mdi-cart-plus";
    public const ICON_CART_DOWN = "mdi mdi-cart-arrow-down";
    public const ICON_CART_REMOVE = "mdi mdi-cart-remove";
    public const ICON_CART_CHECK = "mdi mdi-cart-check";

    public const ICON_LAYOUT_SIDEBAR = "ri-side-bar-fill";
    public const ICON_LAYOUT_SIDEBAR_2 = "ri-side-bar-line";
    public const ICON_PRINT = "uil-print";

    public const ICON_TM_CLOCK = "mdi mdi-timeline-clock-outline";

    public const ICON_BACK_TO_LEFT = "mdi mdi-arrow-left-top-bold";


    public const ICON_COMMENT_MORE = "mdi mdi-message-plus-outline";

    public const ICON_ARCHIVE = "mdi mdi-archive";
    public const ICON_ARCHIVE_ADD = "mdi mdi-archive-plus-outline";
    public const ICON_ARCHIVE_CHECK = "mdi mdi-archive-check";
    public const ICON_ARCHIVE_REMOVE = "mdi mdi-archive-remove-outline";

    public const ICON_RELOAD = "mdi mdi-reload";

    public const ICON_LOOSE_DEAL = "mdi mdi-briefcase-off-outline";

    public const ICON_REFRESH = " ri-refresh-line";

    public const ICON_VIEW = "mdi mdi-view-dashboard";

    public const ICON_DOTS = "mdi mdi-dots-vertical";

    public const ICON_SEARCH_DEVIS = "mdi mdi-clipboard-search";

    public const ICON_DEVICE = "mdi mdi-devices";

    public const ICON_BANK = "mdi mdi-bank";
    public const ICON_BANK_OUT = "mdi mdi-bank-transfer-out";
    public const ICON_BANK_IN = "mdi mdi-bank-transfer-in";
    public const ICON_BANK_PLUS = "mdi mdi-bank-plus";

    public const ICON_LOCK_CHECK = "mdi mdi-lock-check";
    public const ICON_LOCK_OPEN_ALERT = "mdi mdi-lock-open-alert";
    public const ICON_REPEAT = "mdi mdi-repeat";
    public const ICON_MS_EXCEL = "mdi mdi-microsoft-excel";

    public const ICON_SAVE = "ri-save-2-fill";



    public static function icon(string $icon_class_name, ?string $class = null, array $params = []): string
    {

        $title  = !empty($params['title']) ? self::attr('title', $params['title']) : null;


        return <<< HTML
            <i class="{$icon_class_name} $class" $title></i>
        HTML;
    }

    /**
     * Spinner
     * @param array|null $params [
     *  text | container | id | display 
     * ]
     */
    public static function spinner(string $type, ?string $class = "text-primary", array $params = []): string
    {
        $text = $params['text'] ?? "Chargement...";
        $containerClass = $params['container'] ?? null;

        $id = $params['id'] ?? null;

        $display = $params['display'] ?? "d-flex";

        $MSG = !empty($params['text']) ? <<< HTML
            <span>$text</span>
        HTML : NULL;

        $ms_auto = !empty($params['text']) ? "ms-auto" : null;

        $CONTAINER_START = !empty($params['text']) ? <<< HTML
            <div id="{$id}" class="$display align-items-center {$containerClass}">
            $MSG
        HTML : NULL;
        $CONTAINER_END = !empty($params['text']) ? "</div>" : null;

        $speed = $params['speed'] ?? null;
        $SPEED_ATTR = !is_null($speed) ? "style=\"animation-duration: {$params['speed']}s;\"" : null;



        return <<< HTML
            $CONTAINER_START
            <div class="spinner-{$type} {$class} {$ms_auto}" role="status" {$SPEED_ATTR}>
            </div>
            $CONTAINER_END
        HTML;
    }

    private static function attr($name, $value): string
    {
        return "$name=\"$value\"";
    }
}