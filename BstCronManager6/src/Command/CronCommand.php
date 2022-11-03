<?php
/**
 * NOTICE OF LICENSE
 *
 * @copyright  Copyright (c) 09.10.2020 brainstation GbR
 * @author     Mike Becker<mike@brainstation.de>
 */
namespace BstCronManager6\Command;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\Command\ScheduledTaskRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;

class CronCommand extends Command
{
    protected static $defaultName = 'system:cronmanager:watch';

    /** @var ScheduledTaskRunner */
    private $scheduledTaskRunner;

    /** @var ConsumeMessagesCommand */
    private $messageConsumer;

    public function __construct(
        ScheduledTaskRunner $scheduledTaskRunner,
        ConsumeMessagesCommand $messageConsumer
    )
    {
        $this->scheduledTaskRunner = $scheduledTaskRunner;
        $this->messageConsumer = $messageConsumer;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('memory-limit', 'm', InputOption::VALUE_REQUIRED, 'The memory limit the worker can consume')
            ->addOption('time-limit', 't', InputOption::VALUE_REQUIRED, 'The time limit in seconds the worker can run')
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Seconds to sleep before asking for new messages after no messages were found', 1)
            ->addOption('bus', 'b', InputOption::VALUE_REQUIRED, 'Name of the bus to which received messages should be dispatched (if not passed, bus is determined automatically)')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit the number of received messages')
            ->setDescription('Handle task scheduler and message consumer.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($scheduledTaskRunnerCmd = $this->getApplication()->find($this->scheduledTaskRunner::getDefaultName())) {
            $output->writeln('Start Scheduled Task Runner');
            $scheduledTaskRunnerCmd->run($input, $output);
        }

        if ($messageConsumerCmd = $this->getApplication()->find($this->messageConsumer::getDefaultName())) {
            $output->writeln('Start Message Consumer');
            $messageConsumerCmd->run($input, $output);
        }
    }
}