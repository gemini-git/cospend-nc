<?php

/**
 * Nextcloud - cospend
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net
 * @copyright Julien Veyssier 2019
 */

namespace OCA\Cospend\Db;

use OCP\AppFramework\Db\Entity;

class Project extends Entity {

	protected $userid;
	protected $name;
	protected $email;
	protected $password;
	protected $autoexport;
	protected $lastchanged;
	protected $guestaccesslevel;
	protected $deletiondisabled;
	protected $categorysort;
	protected $paymentmodesort;
	protected $currencyname;

	public function __construct() {
		$this->addType('id', 'string');
		$this->addType('userid', 'string');
		$this->addType('name', 'string');
		$this->addType('email', 'string');
		$this->addType('password', 'string');
		$this->addType('autoexport', 'string');
		$this->addType('lastchanged', 'integer');
		$this->addType('guestaccesslevel', 'integer');
		$this->addType('deletiondisabled', 'integer');
		$this->addType('categorysort', 'string');
		$this->addType('paymentmodesort', 'string');
		$this->addType('currencyname', 'string');
	}
}
