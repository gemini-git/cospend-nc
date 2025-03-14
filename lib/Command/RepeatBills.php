<?php

/**
 * Nextcloud - Cospend
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2019
 */

namespace OCA\Cospend\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use OCA\Cospend\Service\ProjectService;

class RepeatBills extends Command {

	/**
	 * @var ProjectService
	 */
	private $projectService;

	public function __construct(ProjectService $projectService) {
		parent::__construct();
		$this->projectService = $projectService;
	}

	protected function configure() {
		$this->setName('cospend:repeat-bills')
			->setDescription('Repeat bills if necessary');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int	{
		$repeated = $this->projectService->cronRepeatBills();
		foreach ($repeated as $r) {
			$output->writeln(
				'[Project "'.$r['project_name'].'"] Bill "'.$r['what'].
				'" ('.$r['date_orig'].') repeated on ('.$r['date_repeat'].')'
			);
		}
		return 0;
	}
}
