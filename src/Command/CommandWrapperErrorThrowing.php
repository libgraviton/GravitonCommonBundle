<?php
/**
 * wraps a command and throws an exception if there is error output
 */

namespace Graviton\CommonBundle\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel;
use function PHPUnit\Framework\throwException;

/**
 * @author  List of contributors <https://github.com/libgraviton/DeploymentServiceBundle/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 */
class CommandWrapperErrorThrowing extends Command
{
    private const PREFIX = 'graviton:';

    private Kernel $kernel;
    private string $commandName;

    public function __construct(Kernel $kernel, string $commandName)
    {
        $this->kernel = $kernel;
        $this->commandName = $commandName;
        parent::__construct(self::PREFIX.$commandName);
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setDescription('Wraps the command "'.$this->commandName.'" and throws exceptions if needed.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>*** Executing the command "'.$this->commandName.' ***"</info>');
        $output->setDecorated(true);

        try {
            $application = new Application($this->kernel);
            $application->setAutoExit(false);

            $input = new ArrayInput([
                'command' => $this->commandName
            ]);

            $wrappedOutput = new BufferedOutput();
            $wrappedOutput->setDecorated(true);

            $exitCode = $application->run($input, $wrappedOutput);

            // return the output, don't use if you used NullOutput()
            $content = $wrappedOutput->fetch();

            $output->writeln([
                '<info>*** Wrapped command output ***</info>',
                $content
            ]);

            if ($exitCode != 0) {
                throw new WrappedCommandException(
                    'Error in command "'.$this->commandName.'": '.$content
                );
            }

        } catch (\Throwable $t) {
            throw $t;
        }

        return Command::SUCCESS;
    }
}
