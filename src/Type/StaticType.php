<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Traits\TruthyBooleanTypeTrait;

class StaticType implements StaticResolvableType, TypeWithClassName
{

	use TruthyBooleanTypeTrait;

	/** @var string */
	private $baseClass;

	/** @var \PHPStan\Type\ObjectType */
	private $staticObjectType;

	public function __construct(string $baseClass)
	{
		$this->baseClass = $baseClass;
		$this->staticObjectType = new ObjectType($baseClass);
	}

	public function getClassName(): string
	{
		return $this->baseClass;
	}

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return $this->staticObjectType->getReferencedClasses();
	}

	public function getBaseClass(): string
	{
		return $this->baseClass;
	}

	public function accepts(Type $type): bool
	{
		return $this->staticObjectType->accepts($type);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			return $this->staticObjectType->isSuperTypeOf($type);
		}

		if ($type instanceof ObjectType) {
			return TrinaryLogic::createMaybe()->and($this->staticObjectType->isSuperTypeOf($type));
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return TrinaryLogic::createNo();
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('static(%s)', $this->staticObjectType->describe($level));
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return $this->staticObjectType->canAccessProperties();
	}

	public function hasProperty(string $propertyName): bool
	{
		return $this->staticObjectType->hasProperty($propertyName);
	}

	public function getProperty(string $propertyName, Scope $scope): PropertyReflection
	{
		return $this->staticObjectType->getProperty($propertyName, $scope);
	}

	public function canCallMethods(): TrinaryLogic
	{
		return $this->staticObjectType->canCallMethods();
	}

	public function hasMethod(string $methodName): bool
	{
		return $this->staticObjectType->hasMethod($methodName);
	}

	public function getMethod(string $methodName, Scope $scope): MethodReflection
	{
		return $this->staticObjectType->getMethod($methodName, $scope);
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return $this->staticObjectType->canAccessConstants();
	}

	public function hasConstant(string $constantName): bool
	{
		return $this->staticObjectType->hasConstant($constantName);
	}

	public function getConstant(string $constantName): ConstantReflection
	{
		return $this->staticObjectType->getConstant($constantName);
	}

	public function resolveStatic(string $className): Type
	{
		return new ObjectType($className);
	}

	public function changeBaseClass(string $className): StaticResolvableType
	{
		$thisClass = get_class($this);
		return new $thisClass($className);
	}

	public function isIterable(): TrinaryLogic
	{
		return $this->staticObjectType->isInstanceOf(\Traversable::class);
	}

	public function getIterableKeyType(): Type
	{
		$broker = Broker::getInstance();

		if (!$broker->hasClass($this->baseClass)) {
			return new ErrorType();
		}

		$classReflection = $broker->getClass($this->baseClass);

		if ($classReflection->isSubclassOf(\Iterator::class) && $classReflection->hasNativeMethod('key')) {
			return $classReflection->getNativeMethod('key')->getReturnType();
		}

		if ($classReflection->isSubclassOf(\IteratorAggregate::class) && $classReflection->hasNativeMethod('getIterator')) {
			return RecursionGuard::run($this, function () use ($classReflection) {
				return $classReflection->getNativeMethod('getIterator')->getReturnType()->getIterableKeyType();
			});
		}

		if ($classReflection->isSubclassOf(\Traversable::class)) {
			return new MixedType();
		}

		return new ErrorType();
	}

	public function getIterableValueType(): Type
	{
		$broker = Broker::getInstance();

		if (!$broker->hasClass($this->baseClass)) {
			return new ErrorType();
		}

		$classReflection = $broker->getClass($this->baseClass);

		if ($classReflection->isSubclassOf(\Iterator::class) && $classReflection->hasNativeMethod('current')) {
			return $classReflection->getNativeMethod('current')->getReturnType();
		}

		if ($classReflection->isSubclassOf(\IteratorAggregate::class) && $classReflection->hasNativeMethod('getIterator')) {
			return RecursionGuard::run($this, function () use ($classReflection) {
				return $classReflection->getNativeMethod('getIterator')->getReturnType()->getIterableValueType();
			});
		}

		if ($classReflection->isSubclassOf(\Traversable::class)) {
			return new MixedType();
		}

		return new ErrorType();
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return $this->staticObjectType->isInstanceOf(\ArrayAccess::class);
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		return $this->staticObjectType->getOffsetValueType($offsetType);
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType): Type
	{
		return $this->staticObjectType->setOffsetValueType($offsetType, $valueType);
	}

	public function isCallable(): TrinaryLogic
	{
		return $this->staticObjectType->isCallable();
	}

	public function getCallableParametersAcceptor(Scope $scope): ParametersAcceptor
	{
		return $this->staticObjectType->getCallableParametersAcceptor($scope);
	}

	public function isCloneable(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function toNumber(): Type
	{
		return new ErrorType();
	}

	public function toString(): Type
	{
		return $this->staticObjectType->toString();
	}

	public function toInteger(): Type
	{
		return new ErrorType();
	}

	public function toFloat(): Type
	{
		return new ErrorType();
	}

	public function toArray(): Type
	{
		return $this->staticObjectType->toArray();
	}

	/**
	 * @param mixed[] $properties
	 * @return Type
	 */
	public static function __set_state(array $properties): Type
	{
		return new static($properties['baseClass']);
	}

}
