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

	public function testKill()
	{
		$proc = new Process('sleep 5', true);
		$this->assertTrue($proc->isRunning());
		$this->assertTrue($proc->kill(SIGTERM));

		while ($proc->isRunning()) {}

		$this->assertFalse($proc->isRunning());
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

		$proc = new Process('ls /xxx/'.__DIR__, true);
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
