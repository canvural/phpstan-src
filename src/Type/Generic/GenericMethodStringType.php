<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Broker\Broker;
use PHPStan\TrinaryLogic;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MethodStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

class GenericMethodStringType extends MethodStringType
{

	private Type $type;

	public function __construct(Type $type)
	{
		$this->type = $type;
	}

	public function getReferencedClasses(): array
	{
		return $this->type->getReferencedClasses();
	}

	public function getGenericType(): Type
	{
		return $this->type;
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('%s<%s>', parent::describe($level), $this->type->describe($level));
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		if ($type instanceof ConstantStringType) {
			return TrinaryLogic::createFromBoolean($this->type->hasMethod($type->getValue())->yes());
		}

		if ($type instanceof self) {
			return $this->type->accepts($type->type, $strictTypes);
		}

		if ($type instanceof MethodStringType) {
			$objectType = new ObjectWithoutClassType();

			return $this->type->accepts($objectType, $strictTypes);
		}

		if ($type instanceof StringType) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createNo();
	}

	public function traverse(callable $cb): Type
	{
		$newType = $cb($this->type);

		if ($newType === $this->type) {
			return $this;
		}

		return new self($newType);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		if ($type instanceof ConstantStringType) {
			$broker = Broker::getInstance();

			if ($broker->hasClass($type->getValue())) {
				return TrinaryLogic::createNo();
			}

			$genericType = $this->type;

			if ($genericType instanceof MixedType) {
				return TrinaryLogic::createMaybe();
			}

			if ($genericType instanceof StaticType) {
				$genericType = $genericType->getStaticObjectType();
			}

			return TrinaryLogic::createFromBoolean($genericType->hasMethod($type->getValue())->yes());
		}

		if ($type instanceof self) {
			return $this->type->isSuperTypeOf($type->type);
		}

		if ($type instanceof StringType) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createNo();
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		if ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType) {
			return $receivedType->inferTemplateTypesOn($this);
		}

		if ($receivedType instanceof ConstantStringType) {
			$typeToInfer = new ObjectType($receivedType->getValue());
		} elseif ($receivedType instanceof self) {
			$typeToInfer = $receivedType->type;
		} elseif ($receivedType instanceof MethodStringType) {
			$typeToInfer = $this->type;
			if ($typeToInfer instanceof TemplateType) {
				$typeToInfer = $typeToInfer->getBound();
			}

			$typeToInfer = TypeCombinator::intersect($typeToInfer, new ObjectWithoutClassType());
		} else {
			return TemplateTypeMap::createEmpty();
		}

		return $this->type->inferTemplateTypes($typeToInfer);
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$variance = $positionVariance->compose(TemplateTypeVariance::createCovariant());

		return $this->type->getReferencedTemplateTypes($variance);
	}

	public function equals(Type $type): bool
	{
		if (! $type instanceof self) {
			return false;
		}

		if (! $this->type->equals($type->type)) {
			return false;
		}

		if (! parent::equals($type)) {
			return false;
		}

		return true;
	}

	/**
	 * @param mixed[] $properties
	 * @return Type
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['type']);
	}

}
