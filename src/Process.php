<?php
declare(strict_types=1);
/**
 * @package PHPClassCollection
 * @subpackage Process
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 */
namespace unrealization\PHPClassCollection;
/**
 * @package PHPClassCollection
 * @subpackage Process
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 * @version 1.3.0
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL 2.1
 */
class Process
{
	/**
	 * The command line to be executed
	 * @var string
	 */
	private $command;
	/**
	 * Determins if the process is killed by the destructor or if it should wait for the process to finish
	 * @var bool
	 */
	private $killOnDestruction;
	/**
	 * Process resource
	 * @var resource
	 */
	private $process;
	/**
	 * IO pipes
	 * @var array
	 */
	private $pipes = array();
	/**
	 * Exit code of the process
	 * @var int
	 */
	private $exitCode;
	/**
	 * Read from the given pipe
	 * @param resource $pipe
	 * @return string
	 * @throws \Exception
	 */

	private function readPipe($pipe): string
	{
		if (!is_resource($pipe))
		{
			throw new \Exception('Broken pipe');
		}
		
		stream_set_blocking($pipe, false);
		stream_set_timeout($pipe, 1);
		$response = stream_get_contents($pipe);
		
		if ($response === false)
		{
			throw new \Exception('Nothing to read');
		}
		
		return $response;
	}

	/**
	 * Constructor
	 * @param string $command
	 * @param bool $autoStart
	 * @param bool $killOnDestruction
	 */
	public function __construct(string $command, bool $autoStart = true, bool $killOnDestruction = true)
	{
		$this->command = $command;
		$this->killOnDestruction = $killOnDestruction;

		if ($autoStart === true)
		{
			$this->start();
		}
	}

	/**
	 * Destructor
	 */
	public function __destruct()
	{
		if (($this->killOnDestruction === true) && ($this->isRunning()))
		{
			$this->kill();
		}

		foreach ($this->pipes as $key => $pipe)
		{
			if (is_resource($pipe))
			{
				fclose($this->pipes[$key]);
			}
		}

		if (is_resource($this->process))
		{
			proc_close($this->process);
		}
	}

	/**
	 * Start the process
	 */
	public function start()
	{
		$this->pipes = array();
		$this->process = proc_open($this->command, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $this->pipes);
	}

	/**
	 * Kill the process
	 * @param int $signal
	 */
	public function kill(int $signal = 15)
	{
		if (is_resource($this->process))
		{
			proc_terminate($this->process, $signal);
		}
	}

	/**
	 * Check process status
	 * @return array
	 * @throws \Exception
	 */
	public function getStatus(): array
	{
		if (!is_resource($this->process))
		{
			throw new \Exception('No process is running.');
		}

		$status = proc_get_status($this->process);

		if (($status['running'] == false) && (!isset($this->exitCode)))
		{
			$this->exitCode = $status['exitcode'];
		}

		return $status;
	}

	/**
	 * Check if the process is running
	 * @return bool
	 */
	public function isRunning(): bool
	{
		try
		{
			$status = $this->getStatus();
			return $status['running'];
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	/**
	 * Get the exit code of the application after it has stopped
	 * @return int
	 */
	public function getExitCode(): int
	{
		if (!isset($this->exitCode))
		{
			$this->getStatus();
		}

		return $this->exitCode;
	}

	/**
	 * Get the command executed by the process.
	 * @return string
	 */
	public function getCommand(): string
	{
		return $this->command;
	}

	/**
	 * Write to the process's STDIN
	 * @param string $data
	 * @throws \Exception
	 */
	public function writeSTDIN(string $data)
	{
		if (!is_resource($this->pipes[0]))
		{
			throw new \Exception('Broken pipe');
		}

		fwrite($this->pipes[0], $data);
	}

	/**
	 * Read from the process's STDOUT
	 * @return string
	 * @throws \Exception
	 */
	public function readSTDOUT(): string
	{
		if (!is_resource($this->pipes[1]))
		{
			throw new \Exception('Broken pipe');
		}

		return $this->readPipe($this->pipes[1]);
	}

	/**
	 * Read from the process's STDERR
	 * @return string
	 * @throws \Exception
	 */
	public function readSTDERR(): string
	{
		if (!is_resource($this->pipes[2]))
		{
			throw new \Exception('Broken pipe');
		}

		return $this->readPipe($this->pipes[2]);
	}
}
?>