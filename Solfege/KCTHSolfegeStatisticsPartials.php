<?php

namespace KCTH\Solfege;

use KCTH\Solfege\KCTHSolfegePartials;

/**
 * Vue Partielle liée aux statistiques.
 */
class KCTHSolfegeStatisticsPartials extends KCTHSolfegePartials
{
	public function __construct(array $dependencies = [])
	{
		parent::__construct($dependencies);
	}

	/**
	 * Indicateur
	 */
	public static function statIndicator(string $label = "Indicateur", null|string|int $value = 0, array $params = []): string
	{
		return <<< HTML
			<div class="card widget-flat">
			    <div class="card-body">
			        <div class="float-end">
			            <i class="mdi mdi-currency-eur widget-icon"></i>
			        </div>
			        <h5 class="text-muted fw-normal mt-0" title="{$params['title']}">$label</h5>
			        <h3 class="mt-3 mb-3">$value</h3>
			        <p class="mb-0 text-muted">
			            <span class="text-success me-2">
			                <i class="mdi mdi-arrow-up-bold"></i> 4.87%</span>
			            <span class="text-nowrap">Since last month</span>
			        </p>
			    </div>
			</div>
		HTML;
	}

	/**
	 * Indicateur de progression
	 */
	public static function statProgressionIndicator(string $label = "Indicateur de progression (bientôt)", $value = 8.5, $outof = 10, array $params = ['title' => "Indicateur de progression sur option et par utilisateur"])
	{
		$SELECTOR = self::trSelect("#", [
            '1' => "valeur 1",
            '2' => "valeur 2",
            '3' => "valeur 3"
        ],[
            'selected' => 1,
            'class' => "w-auto ms-1 bg-transparent float-end form-select-sm",
            'style' => "display: inline-block; font-weight: bold;"
        ]);

        $progression = self::percentOf($outof, $value) . "%";

		return <<< HTML
			<div class="card">
			    <div class="card-body">
			        <h4 class="fw-semibold mt-0 mb-3" title="{$params['title']}">$label
			            $SELECTOR

			            <!-- <span class="badge bg-success-lighten text-success fw ms-sm-1"><i class="mdi mdi-trending-up me-1"></i>59%</span> -->
			        </h4>
			        <h5 class="float-end mt-0">$value</h5>
			        <h5 class="fw-normal mt-0 mb-2 h3 text-success">$outof</h5>
			        <div class="progress progress-xl">
			            <div class="progress-bar bg-success" role="progressbar" style="width: $progression;"></div>
			        </div>
			    </div>
			</div>
		HTML;
	}

	/**
	 * Listing
	 */
	public static function statListing(?string $label = "Listing", array $params = []): string
	{
		$DROPDOWN = $params['dropdown'] !== false ?
		 	self::htmlDropdown($params['dropdown']) : null;

		$LABEL = $params['label'] ? <<< HTML
			<div class="d-flex card-header justify-content-between align-items-center">
		        <h4 class="header-title" title="{$params['title']}">$label</h4>
		        $DROPDOWN
		    </div>
		HTML : NULL;

		$CONTENT = self::loadElement("SimpleBar");

		$CONTENT->CONTENT_CHANGE(
			self::loadElement("FlushAccordions", $params[':FlushAccordions'])
				->modify("id", "statFlushAccordions")
				->show()
		)->modify("height", $params['content_height']);


		return <<< HTML
			<div class="card">
			    $LABEL
			    {$CONTENT->show()}
			</div>
		HTML;
	}
}