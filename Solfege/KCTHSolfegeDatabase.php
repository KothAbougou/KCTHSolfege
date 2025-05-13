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
use \PDO;

/**
 * Base de donnée.
 * @package KCTH\Solfege
 * @author Koth R. Abougou <richard.abougou@gmail.com>
 */
class KCTHSolfegeDatabase extends KCTHSolfege
{
	private $db_name;
	private $db_user;
	private $db_pass;
	private $db_host;

	private $pdo;

	public function __construct($db_name, $db_user, $db_pass, $db_host)
	{
		$this->db_name = $db_name;
		$this->db_user = $db_user;
		$this->db_pass = $db_pass;
		$this->db_host = $db_host;
	}

	//PDO
	private function getPDO(): PDO
	{
		if($this->pdo === null)
		{
			$pdo = new PDO('mysql:dbname='.$this->db_name.';host='.$this->db_host,$this->db_user,$this->db_pass);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->pdo = $pdo;
		}

		return $this->pdo;
	}

	public function beginTransaction(): bool
	{
		return $this->getPDO()->beginTransaction();
	}

	public function getDbName(): string
	{
		return $this->db_name;
	}

	public function query($statement)
	{
		return $this->getPDO()->query($statement);
	}

	public function prepare($statement)
	{
		return $this->getPDO()->prepare($statement);
	}

	public function exec($statement)
	{
		return $this->getPDO()->exec($statement);
	}

	public function setAutoIncrementFromTable(string $table, int $set_increment)
	{
		$req = $this->getPDO()->prepare("ALTER TABLE {$table} AUTO_INCREMENT = ?");
		$req->execute(array($set_increment));
	}

	public function commit(): bool
	{
		return $this->getPDO()->commit();
	}

	public function rollback(): bool
	{
		return $this->getPDO()->rollback();
	}

}