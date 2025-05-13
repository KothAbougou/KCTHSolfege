<?php

namespace KCTH\Solfege\event;
use KCTH\Solfege\KCTHSolfege;
use KCTH\Solfege\event\KCTHSolfegeEventListener as EventListener;

class KCTHSolfegeEventEmitter extends KCTHSolfege
{

	/**
	 * Liste des écouteurs
	 */
	private array $listeners = [];

	/**
	 * Envoyer un événement
	 * @param  string $event  Nom de l'événement
	 * @param  [type] $params Les arguments.
	 **/
	public function emit(string $event, ...$params)
	{
		if($this->listenerExists($event))
			foreach($this->listeners[$event] as $listener)
			{
				$listener->handle($params);

				if($listener->stopPropagation)
					break;
			}
	}

	/**
	 * Ecouter un événement
	 * @param  string   $event    Nom de l'événement
	 * @param  callable $callback 
	 */
	public function on(string $event, callable $callback, int $priority = 0): EventListener
	{
		if(!$this->listenerExists($event))
			$this->listeners[$event] = [];

		$listener = new EventListener($callback, $priority);
		$this->listeners[$event][] = $listener;

		$this->sortListeners($event);

		return $listener;
	}

	public function once(string $event, callable $callback, int $priority = 0): EventListener
	{
		return $this->on($event, $callback, $priority)->once();
	}

	private function listenerExists(string $event): bool
	{
		return array_key_exists($event, $this->listeners);
	}

	private function sortListeners(string $event)
	{
		uasort($this->listeners[$event], function($l1, $l2)
		{
			$a = $l1->getPriority();
			$b = $l2->getPriority();

			if ($a < $b) {
		        return -1;
		    } elseif ($a == $b) {
		        return 0;
		    } else {
		        return 1;
		    }
		});
	}
}