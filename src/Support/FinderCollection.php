<?php

namespace InterNACHI\Modular\Support;

use ArrayIterator;
use Exception;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Traits\ForwardsCalls;
use IteratorAggregate;
use Symfony\Component\Finder\Finder;
use Traversable;

/**
 * @mixin \Illuminate\Support\LazyCollection
 * @mixin \Symfony\Component\Finder\Finder
 */
class FinderCollection implements IteratorAggregate
{
	use ForwardsCalls;
	
	protected static $prefer_collection_methods = ['filter', 'each'];
	
	/**
	 * @var \Symfony\Component\Finder\Finder|Traversable
	 */
	protected $finder;
	
	/**
	 * @var \Illuminate\Support\LazyCollection
	 */
	protected $collection;
	
	public static function forFiles(): self
	{
		return (new static())->files();
	}
	
	public static function forDirectories(): self
	{
		return (new static())->directories();
	}
	
	public static function empty(): self 
	{
		$collection = new static();
		
		$collection->finder = new ArrayIterator([]);
		
		return $collection;
	}
	
	public function __construct(Finder $finder = null)
	{
		$this->finder = $finder ?? new Finder();
		$this->collection = new LazyCollection();
	}
	
	public function getIterator()
	{
		return $this->collection->getIterator();
	}
	
	public function __call($name, $arguments)
	{
		// If we're working with an empty instance, don't do anything with calls
		if ($this->finder instanceof ArrayIterator) {
			return $this;
		}
		
		// Forward the call either to the Finder or the LazyCollection depending
		// on the method (always giving precedence to the Finder class unless otherwise configured)
		if (is_callable([$this->finder, $name]) && !in_array($name, static::$prefer_collection_methods)) {
			$result = $this->forwardCallTo($this->finder, $name, $arguments);
		} else {
			$this->collection->source = $this->finder;
			$result = $this->forwardCallTo($this->collection, $name, $arguments);
		}
		
		// If we get a Finder object back, update our internal reference and chain
		if ($result instanceof Finder) {
			$this->finder = $result;
			return $this;
		}
		
		// If we get a Collection object back, update our internal reference and chain
		if ($result instanceof LazyCollection) {
			$this->collection = $result;
			return $this;
		}
		
		// Otherwise, just return the new result (in the case of toBase() or sum()-type calls)
		return $result;
	}
}
