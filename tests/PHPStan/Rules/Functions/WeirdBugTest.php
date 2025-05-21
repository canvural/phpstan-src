<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ClosureReturnTypeRule>
 */
class WeirdBugTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ClosureReturnTypeRule(new FunctionReturnTypeCheck(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, true, false)));
	}

	public function testClosureReturnTypeRule(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		// Point to the correct analysis target
		$this->analyse([__DIR__ . '/../../Analyser/data/weird-bug/weird-bug.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		// Point to the correct configuration file
		return [
			__DIR__ . '/../../Analyser/data/weird-bug/phpstan.neon',
		];
	}

}
