<?php

namespace KCTH\Solfege\event;
use KCTH\Solfege\KCTHSolfege;

class KCTHSolfegeEventListener extends KCTHSolfege
{
	/**
	 * La fonction a exécuter.
	 * @var callable
	 */
	private $callback;

	/**
	 * Donne une priorité au listener
	 * @var integer
	 */
	private int $priority;

	/**
	 * Définit si le listener peut être appelé plusieurs fois.
	 * @var boolean
	 */
	private $once = false;

	/**
	 * Combien de fois le listener a déjà été appelé.
	 * @var integer
	 */
	private $calls = 0;

	/**
	 * Permet de stopper les événement parent
	 * @var boolean
	 */
	public $stopPropagation = false;


	public function __construct(callable $callback, int $priority)
	{
		$this->callback = $callback;
		$this->priority = $priority;
	}


	public function handle(array $params)
	{
		if($this->once && $this->alreadyCalledOver(0))
			return null;

		$this->calls++;

		return call_user_func_array($this->callback, $params);
	}

	public function getPriority(): int
	{
		return $this->priority;
	}

	/**
	 * Indique que le listener ne peut être appelé qu'une fois.
	 */
	public function once(): KCTHSolfegeEventListener
	{
		$this->once = true;

		return $this;
	}

	/**
	 * Permet de stoper l'exécution des événements suivant.
	 */
	public function stopPropagation(): KCTHSolfegeEventListener
	{
		$this->stopPropagation = true;

		return $this;
	}

	private function alreadyCalledOver(int $threshold): bool
	{
		return $this->calls > $threshold;
	}
}