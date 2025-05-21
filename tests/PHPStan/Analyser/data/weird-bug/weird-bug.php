<?php

namespace WeirdBug;

// Needed for the extension
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection as PhpstanMethodReflection; // Alias for clarity
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\CallableType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType; // For the second parameter of the closure
use PHPStan\Type\Native\NativeParameterReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\PassedByReference;
use PHPStan\Type\Type;
use PhpParser\Node\Expr\MethodCall; // For the signature of getTypeFromMethodCall

use function PHPStan\Testing\assertType;

class Model {
	/**
	 * @return Builder<static>
	 */
	public static function getBuilder(): Builder
	{
		return new Builder(new static());
	}
}

class SubModel extends Model {}

/**
 * @template T of Model
 */
class Builder {

	/**
	 * @param T $model
	 */
	public function __construct(private Model $model)
	{
	}

	public function methodWithCallback(\Closure $callback): void
	{
		// The callback is expected to return the builder, so capture its result.
		$result = $callback($this, null); // Pass two arguments to match extension's expectation
		// Potentially assert $result type if needed, but primary focus is $builder type in closure.
	}

	// Method that the closure will call.
	public function someMethod(): self
	{
		return $this;
	}
}

// This class was removed in a previous step and needs to be re-added
// with the updated getTypeFromMethodCall logic.
class MethodParameterClosureTypeExtension implements \PHPStan\Type\MethodParameterClosureTypeExtension
{
	public function isMethodSupported(PhpstanMethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Builder::class
			&& $methodReflection->getName() === 'methodWithCallback'
			&& $parameter->getName() === 'callback';
	}

	public function getTypeFromMethodCall(
		PhpstanMethodReflection $methodReflection,
		MethodCall $methodCall, // This is the call to Builder::methodWithCallback
		ParameterReflection $parameter, // This is the 'callback' parameter of methodWithCallback
		Scope $scope
	): ?Type {
		// Define the type for the first parameter of the closure
		$builderParamType = new GenericObjectType(Builder::class, [new ObjectType(SubModel::class)]);

		// The closure itself will be a CallableType
		// Its first parameter is $builderParamType
		// Its second parameter is mixed (for $value)
		// Its return type is $builderParamType (as per the new requirement)
		return new CallableType(
			[
				new NativeParameterReflection('builder', false, $builderParamType, PassedByReference::createNo(), false, null),
				new NativeParameterReflection('value', true, new MixedType(), PassedByReference::createNo(), false, null),
			],
			$builderParamType // This sets the expected return type of the callable
		);
	}
}

class AnotherBuilder { // This class was in the original snippet, keeping it.
	public function someMethod(): self
	{
		return $this;
	}
}

function test(): void
{
	SubModel::getBuilder()->methodWithCallback(function (Builder $builder, $value) {
		assertType('WeirdBug\\Builder<WeirdBug\\SubModel>', $builder);
		// The return type of this closure should now be compatible with Builder<SubModel>
		// due to the extension's modification of CallableType's return.
		return $builder->someMethod();
	});
}
