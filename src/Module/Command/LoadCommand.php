<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Module\Command;

use Bitrix\Main\ModuleManager;
use Notamedia\ConsoleJedi\Module\Exception\ModuleException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for module installation/register
 *
 * @author Marat Shamshutdinov <m.shamshutdinov@gmail.com>
 */
class LoadCommand extends ModuleCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this->setName('module:load')
			->setDescription('Load and install module from Marketplace')
			->addOption('no-update', 'nu', InputOption::VALUE_NONE, 'Don\' update module')
			->addOption('no-install', 'ni', InputOption::VALUE_NONE, 'Load only, don\' register module');

		parent::configure();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (!$this->isThrirdParty())
		{
			$output->writeln('<info>Module name seems incorrect: ' . $this->moduleName . '</info>');
		}

		if (ModuleManager::isModuleInstalled($this->moduleName) && $this->moduleExists())
		{
			$output->writeln(sprintf('<comment>Module %s is already registered</comment>', $this->moduleName));
		}
		else
		{
			if (!$this->moduleExists())
			{
				require_once($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/classes/general/update_client_partner.php');

				$output->write('Downloading module... ');

				if (!\CUpdateClientPartner::LoadModuleNoDemand($this->moduleName, $strError, $bStable = "Y", LANGUAGE_ID))
				{
					throw new ModuleException(sprintf('Error occured: %s', $strError), $this->moduleName);
				}

				$output->writeln('<info>done</info>');
			}

			if ($input->getOption('no-update'))
			{
				$updateResult = 0;
			}
			else
			{
				$updateCommand = $this->getApplication()->find('module:update');
				$arguments = [
					'command' => 'module:update',
					'module' => $this->moduleName,
				];
				$registerInput = new ArrayInput($arguments);
				$updateResult = $updateCommand->run($registerInput, $output);
			}

			if ($updateResult === 0 && !$input->getOption('no-install'))
			{
				$registerCommand = $this->getApplication()->find('module:register');
				$arguments = [
					'command' => 'module:register',
					'module' => $this->moduleName,
				];
				$registerInput = new ArrayInput($arguments);

				$registerCommand->run($registerInput, $output);
			}
		}
	}
}