<?php namespace SleepingOwl\WithJoin;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait WithJoinTrait
{

	/**
	 * @param array $attributes
	 * @return static
	 */
	public function newFromBuilder($attributes = [])
	{
		$attributes = (array) $attributes;

		$nestedData = [];
		foreach ($attributes as $key => $value){

			$key = str_replace('---', '.', $key);
			Arr::set($nestedData, $key, $value);

		}

		$instance = $this->buildForeignEntity(null, $nestedData);

		return $instance;
	}

	private function buildForeignEntity($entityName = null, $nestedData, $parentInstance = null){
		$prefix = WithJoinEloquentBuilder::$prefix;

		$children = [];
		foreach($nestedData as $key => $data){

			if (strpos($key, $prefix) === 0){
				$newEntityName = str_replace($prefix, '', $key);

				$children[$newEntityName] = $data;

				unset($nestedData[$key]);

			}

		}

		$instance = null;
		if($entityName == null){
			$instance = parent::newFromBuilder($nestedData);
		}else{
			$relationInstance = $parentInstance->$entityName();
			$instance = $relationInstance->getRelated()->newFromBuilder($nestedData);
		}

		foreach($children as $newEntityName => $data){

			$foreign = $this->buildForeignEntity($newEntityName, $data, $instance);

			$instance->setRelation($newEntityName, $foreign);

		}

		return $instance;

	}

	/**
	 * @param  \Illuminate\Database\Query\Builder $query
	 * @return WithJoinEloquentBuilder
	 */
	public function newEloquentBuilder($query)
	{
		return new WithJoinEloquentBuilder($query);
	}

	/**
	 * Being querying a model with eager loading.
	 *
	 * @param  array|string $relations
	 * @return \Illuminate\Database\Eloquent\Builder|WithJoinEloquentBuilder|static
	 */
	public static function with($relations)
	{
		if (is_string($relations)) $relations = func_get_args();
		return parent::with($relations);
	}

	/**
	 * @param  array|string $relations
	 * @return \Illuminate\Database\Eloquent\Builder|WithJoinEloquentBuilder|static
	 */
	public static function includes($relations)
	{
		if (is_string($relations)) $relations = func_get_args();
		$query = parent::with($relations);
		$query->references($relations);
		return $query;
	}

	public function getIncludes()
	{
		return property_exists($this, 'includes') ? $this->includes : null;
	}

}
