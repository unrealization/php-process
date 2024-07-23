<?php
use PHPUnit\Framework\TestCase;
use unrealization\Process;

/**
 * Process test case.
 * @covers unrealization\Process
 */
class ProcessTest extends TestCase
{
	public function testProcess()
	{
		$proc = new Process('ls', false);
		$this->assertInstanceOf(Process::class, $proc);
		$this->assertFalse($proc->isRunning());
		$this->assertSame('ls', $proc->getCommand());
		$this->assertNull($proc->getExitCode());
		$this->expectException(\Exception::class);
		$proc->getStatus();
	}

	public function testRestart()
	{
		$proc = new Process('sleep 2', true);
		$this->assertTrue($proc->isRunning());
		$this->expectException(\Exception::class);
		$proc->start();
	}

	public function testKill()
	{
		$proc = new Process('sleep 5', true);
		$this->assertTrue($proc->isRunning());
		$this->assertTrue($proc->kill(SIGTERM));

		while ($proc->isRunning()) {}

		$this->assertFalse($proc->isRunning());
	}

	public function testAutoKill()
	{
		$proc = new Process('sleep 5', true);
		$this->assertTrue($proc->isRunning());
		unset($proc);
	}

	public function testKillNonRunning()
	{
		$proc = new Process('ls', false);
		$this->assertInstanceOf(Process::class, $proc);
		$this->assertFalse($proc->isRunning());
		$this->expectException(\Exception::class);
		$proc->kill(SIGTERM);
	}

	public function testExitCode()
	{
		$proc = new Process('exit 1', true);

		while ($proc->isRunning()) {}

		$exitCode = $proc->getExitCode();
		$this->assertSame(1, $exitCode);

		$proc = new Process('exit 5', true);

		while ($proc->isRunning()) {}

		$exitCode = $proc->getExitCode();
		$this->assertSame(5, $exitCode);
	}

	public function testOutput()
	{
		$proc = new Process('ls '.__DIR__, true);
		$output = '';

		while ($proc->isRunning())
		{
			$output .= $proc->readSTDOUT();
		}

		$this->assertSame('ProcessTest.php'.PHP_EOL, $output);

		$proc = new Process('ls /.test_xxx_test/'.__DIR__, true);
		$output = '';

		while ($proc->isRunning())
		{
			$output .= $proc->readSTDERR();
		}

		$this->assertStringContainsString('No such file or directory', $output);
	}

	public function testInput()
	{
		$proc = new Process('read', true);
		$this->assertTrue($proc->isRunning());
		$proc->writeSTDIN(PHP_EOL);

		while ($proc->isRunning()) {}

		$this->assertFalse($proc->isRunning());
	}
}
