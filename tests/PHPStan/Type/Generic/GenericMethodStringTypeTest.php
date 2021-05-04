<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Testing\TestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MethodStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

class GenericMethodStringTypeTest extends TestCase
{

	public function dataIsSuperTypeOf(): iterable
	{
		yield [
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new MethodStringType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new StringType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			TrinaryLogic::createYes(),
		];

		yield [
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new GenericMethodStringType(new ObjectType(\Throwable::class)),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new GenericMethodStringType(new ObjectType(\InvalidArgumentException::class)),
			TrinaryLogic::createYes(),
		];

		yield [
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new GenericMethodStringType(new ObjectType(\stdClass::class)),
			TrinaryLogic::createNo(),
		];

		yield [
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new ConstantStringType(\Exception::class),
			TrinaryLogic::createNo(),
		];

		yield [
			new GenericMethodStringType(new ObjectType(\Throwable::class)),
			new ConstantStringType(\Exception::class),
			TrinaryLogic::createNo(),
		];

		yield [
			new GenericMethodStringType(new ObjectType(\InvalidArgumentException::class)),
			new ConstantStringType(\Exception::class),
			TrinaryLogic::createNo(),
		];

		yield [
			new GenericMethodStringType(new ObjectType(\stdClass::class)),
			new ConstantStringType(\Exception::class),
			TrinaryLogic::createNo(),
		];

		yield [
			new GenericMethodStringType(new StaticType(\Exception::class)),
			new ConstantStringType('getMessage'),
			TrinaryLogic::createYes(),
		];

		yield [
			new GenericMethodStringType(new StaticType(\InvalidArgumentException::class)),
			new ConstantStringType('getMessage'),
			TrinaryLogic::createYes(),
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(GenericMethodStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
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
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new ConstantStringType('getMessage'),
			TrinaryLogic::createYes(),
		];

		yield [
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new ConstantStringType('foo'),
			TrinaryLogic::createNo(),
		];

		yield [
			new GenericMethodStringType(new ObjectType(\DateTime::class)),
			new ConstantStringType(\InvalidArgumentException::class),
			TrinaryLogic::createNo(),
		];

		yield [
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new StringType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new MethodStringType(),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			TrinaryLogic::createYes(),
		];

		yield [
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new GenericMethodStringType(new ObjectType(\DateTime::class)),
			TrinaryLogic::createNo(),
		];

		yield [
			new GenericMethodStringType(TemplateTypeFactory::create(
				TemplateTypeScope::createWithFunction('foo'),
				'T',
				null,
				TemplateTypeVariance::createInvariant()
			)),
			new ConstantStringType('foo'),
			TrinaryLogic::createYes(),
		];

		yield [
			new GenericMethodStringType(TemplateTypeFactory::create(
				TemplateTypeScope::createWithClass('Foo'),
				'T',
				null,
				TemplateTypeVariance::createInvariant()
			)),
			new UnionType([
				new ConstantStringType('add'),
				new ConstantStringType('getMessage'),
			]),
			TrinaryLogic::createYes(),
		];

		yield [
			new GenericMethodStringType(TemplateTypeFactory::create(
				TemplateTypeScope::createWithClass('Foo'),
				'T',
				new ObjectType(\Exception::class),
				TemplateTypeVariance::createInvariant()
			)),
			new ConstantStringType('getMessage'),
			TrinaryLogic::createYes(),
		];

		yield [
			new GenericMethodStringType(TemplateTypeFactory::create(
				TemplateTypeScope::createWithClass('Foo'),
				'T',
				new ObjectType(\Exception::class),
				TemplateTypeVariance::createInvariant()
			)),
			new ConstantStringType('add'),
			TrinaryLogic::createNo(),
		];

		yield [
			new GenericMethodStringType(TemplateTypeFactory::create(
				TemplateTypeScope::createWithClass('Foo'),
				'T',
				new ObjectWithoutClassType(),
				TemplateTypeVariance::createInvariant()
			)),
			new MethodStringType(),
			TrinaryLogic::createYes(),
		];

		yield [
			new GenericMethodStringType(TemplateTypeFactory::create(
				TemplateTypeScope::createWithClass('Foo'),
				'T',
				new ObjectWithoutClassType(),
				TemplateTypeVariance::createInvariant()
			)),
			new GenericMethodStringType(TemplateTypeFactory::create(
				TemplateTypeScope::createWithClass('Boo'),
				'U',
				new ObjectWithoutClassType(),
				TemplateTypeVariance::createInvariant()
			)),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new GenericMethodStringType(TemplateTypeFactory::create(
				TemplateTypeScope::createWithClass('Foo'),
				'T',
				new ObjectWithoutClassType(),
				TemplateTypeVariance::createInvariant()
			)),
			new UnionType([new IntegerType(), new StringType()]),
			TrinaryLogic::createMaybe(),
		];

		yield [
			new GenericMethodStringType(TemplateTypeFactory::create(
				TemplateTypeScope::createWithClass('Foo'),
				'T',
				new ObjectWithoutClassType(),
				TemplateTypeVariance::createInvariant()
			)),
			new BenevolentUnionType([new IntegerType(), new StringType()]),
			TrinaryLogic::createMaybe(),
		];
	}

	/**
	 * @dataProvider dataAccepts
	 */
	public function testAccepts(
		GenericMethodStringType $acceptingType,
		Type $acceptedType,
		TrinaryLogic $expectedResult
	): void
	{
		$actualResult = $acceptingType->accepts($acceptedType, true);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $acceptingType->describe(VerbosityLevel::precise()), $acceptedType->describe(VerbosityLevel::precise()))
		);
	}

	public function dataEquals(): iterable
	{
		yield [
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			true,
		];

		yield [
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new GenericMethodStringType(new ObjectType(\stdClass::class)),
			false,
		];

		yield [
			new GenericMethodStringType(new ObjectType(\Exception::class)),
			new GenericMethodStringType(new StaticType(\Exception::class)),
			false,
		];

		yield [
			new GenericMethodStringType(new StaticType(\Exception::class)),
			new GenericMethodStringType(new StaticType(\Exception::class)),
			true,
		];

		yield [
			new GenericMethodStringType(new StaticType(\Exception::class)),
			new GenericMethodStringType(new StaticType(\stdClass::class)),
			false,
		];
	}

	/**
	 * @dataProvider dataEquals
	 */
	public function testEquals(GenericMethodStringType $type, Type $otherType, bool $expected): void
	{
		$verbosityLevel = VerbosityLevel::precise();
		$typeDescription = $type->describe($verbosityLevel);
		$otherTypeDescription = $otherType->describe($verbosityLevel);

		$actual = $type->equals($otherType);
		$this->assertSame(
			$expected,
			$actual,
			sprintf('%s -> equals(%s)', $typeDescription, $otherTypeDescription)
		);

		$actual = $otherType->equals($type);
		$this->assertSame(
			$expected,
			$actual,
			sprintf('%s -> equals(%s)', $otherTypeDescription, $typeDescription)
		);
	}

}
