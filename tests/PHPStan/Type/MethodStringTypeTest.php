<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\TestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantStringType;

class MethodStringTypeTest extends TestCase
{

	public function dataIsSuperTypeOf(): array
	{
		return [
			[
				new MethodStringType(),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new MethodStringType(),
				new ConstantStringType(\stdClass::class),
				TrinaryLogic::createNo(),
			],
			[
				new MethodStringType(),
				new ConstantStringType('JustAString'),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(MethodStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

	public function dataAccepts(): iterable
	{
		yield [
			new MethodStringType(),
			new UnionType([
				new MethodStringType(),
				new MethodStringType(),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new MethodStringType(),
			new UnionType([
				new MethodStringType(),
				new StringType(),
			]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new MethodStringType(),
			new MethodStringType(),
			TrinaryLogic::createYes(),
		];

		yield [
			new MethodStringType(),
			new StringType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new MethodStringType(),
			new ConstantStringType('foo'),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new MethodStringType(),
			new IntegerType(),
			TrinaryLogic::createNo(),
		];
	}

	/**
	 * @dataProvider dataAccepts
	 * @param MethodStringType $type
	 * @param Type             $otherType
	 * @param TrinaryLogic     $expectedResult
	 */
	public function testAccepts(MethodStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->accepts($otherType, true);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

	public function dataEquals(): array
	{
		return [
			[
				new MethodStringType(),
				new MethodStringType(),
				true,
			],
			[
				new MethodStringType(),
				new StringType(),
				false,
			],
			[
				new MethodStringType(),
				new ClassStringType(),
				false,
			],
		];
	}

	/**
	 * @dataProvider dataEquals
	 * @param MethodStringType $type
	 * @param Type $otherType
	 * @param bool $expectedResult
	 */
	public function testEquals(MethodStringType $type, Type $otherType, bool $expectedResult): void
	{
		$actualResult = $type->equals($otherType);

		$this->assertSame(
			$expectedResult,
			$actualResult,
			sprintf('%s->equals(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

}
