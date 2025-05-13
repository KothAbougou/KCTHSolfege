<?php

namespace KCTH\Solfege\tools;

class KCTHSolfegePeriodTool extends KCTHSolfegeTool
{
	private string $dtFrom;
	private string $dtTo;

	PUBLIC CONST PERIOD_STRICT = "Both";
	PUBLIC CONST PERIOD_LEFT = "From";
	PUBLIC CONST PERIOD_RIGHT = "To";
	PUBLIC CONST PERIOD_LENIENT = "Left Or Right";
	

	public function __construct($dtFrom, $dtTo = null)
	{
		$this->dtFrom = $dtFrom;
		$this->dtTo = !is_null($dtTo) ? $dtTo : $dtFrom;
	}

	public function includes(string $dts): bool
	{
		$dts = explode(';', $dts);

		foreach ($dts as $dt)
			if(self::dtCheck($dt) && $dt >= self::dtMoment("first", $this->dtFrom) && $dt <= self::dtMoment("last", $this->dtTo))
				return true;

		return false;
	}
	
	public function includesPeriod(KCTHSolfegePeriodTool $other, string $where = SELF::PERIOD_LENIENT): bool
	{
		$includesPeriod = false;

		switch($where)
		{
			case PERIOD_LEFT: $includesPeriod = $this->includes($other->from()); break;
			case PERIOD_RIGHT: $includesPeriod = $this->includes($other->to()); break;
			case PERIOD_STRICT: $includesPeriod = $this->includes($other->from()) && $this->includes($other->to()); break;
			default: $includesPeriod = $this->includes($other->from()) || $this->includes($other->to()); break;
		}

		return $includesPeriod;
	}

	public function equals(KCTHSolfegeTool $other): bool
	{
		return $this->from() == $other->from() && $this->to() == $other->to();
	}

	public function from(): string
	{
		return $this->dtFrom;
	}

	public function to(): string
	{
		return $this->dtTo;
	}

	public function title(?string $title = "Du :from au :to", $format = "%e %B %Y")
	{
		$from = self::dtString($this->from(), $format);
		$to = self::dtString($this->to(), $format);

		$search = [':from', ':to'];
		$replace = [$from, $to];

		return str_replace($search, $replace, $title);
	}
}
