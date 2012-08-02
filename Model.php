<?php
/**
 * Abstract Model class
 *
 * @author Norman Paniagua
 * @url 
 */

abstract class Model
{
	/**
     * Pass properties to construct
     *
     * @param mixed[] $properties The object properties
     * 
     */
	protected function __construct(Array $properties)
	{
		foreach ($properties as $key=>$value):
			$this->$key = $value;
		endforeach;
	}
	
	/**
     * Create the PDO connection if not exists
     *
     * @return void
     */
	protected function connect()
	{
		static $db;
		
		if (is_object($db)) return $db;
		
		return $db = new PDO(sprintf("%s:dbname=%s;host=%s", DB_ENGINE, DB_NAME, DB_HOST), DB_USER, DB_PASS);
	}
	
	/**
     * Get all class properties
     *
     * @return string[]
     */
	protected static function getFields()
	{
		static $fields = array();
		$called_class = get_called_class();
		
		if (!array_key_exists($called_class, $fields))
		{
			global $db;
			
			$db = self::connect();
			
			$sql 		= "SHOW COLUMNS FROM " . self::getTableName();
			$statement	= $db->prepare($sql);
			
			$statement->execute();
			
			$result 	= $statement->fetchAll(PDO::FETCH_ASSOC);
			
			foreach ($result as $property):
				$properties[] = $property['Field'];
			endforeach;
			
			$fields[$called_class] = $properties;
		}
		
		return $fields[$called_class];
	}
	
	/**
     * Get the select statement
     *
     * @return string
     */
    protected static function getSelect()
    {
        return "SELECT `" . implode('`, `', self::getFields()) . "` FROM `" . self::getTableName() . "`";
    }

	/**
     * Get a single object by id
     *
     * @param integer $id
     * @return Object
     */
    public static function first($conditions)
    {
		if (!is_array($conditions)) {
			$options['conditions'] 	= array('`id` = ?', $conditions);
		} elseif (isset($conditions['conditions']) && is_array($conditions['conditions'])) {
			$options 				= $conditions;
		} else {
			$options['conditions'] 	= $conditions;
		}
		
		$options['limit']			= 1;
		
        return array_shift(self::find($options));
    }
	
	/**
     * Get all objects that matches the conditions
     *
     * @return Object[]
     */
	public static function find($options)
    {
        $db = self::connect();
		$result = array();
		
		$function = function ($value)
		{
			return ':' . $value;
		};
		
		$conditions = null;
		
		if (isset($options['conditions']) && is_array($options['conditions']))
		{
			$conditions = $options['conditions'][0];
		}
		
        $select    = self::getSelect() . (isset($conditions) ? ' WHERE ' . $conditions : '') . (isset($options['limit']) ? ' LIMIT ' . $options['limit'] : '');
        $statement = $db->prepare($select);
		
		if ($conditions != null)
		{
			for ($i=1; $i < count($options['conditions']); ++$i):
				$statement->bindParam($i, $options['conditions'][$i]);
			endfor;
		}
        
        $statement->execute();
		
		//print_r($options); print_r($statement);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
		
		foreach ($rows as $row):
			$result[] = new static($row);
		endforeach;
		
        return $result;
    }

	/**
     * Create a new object
     *
     * @param mixed[] $properties Properties
     * 
     * @return Object
     */
    public static function create(Array $properties)
    {
        $db = self::connect();

		$properties['id'] = null;
        $object = new static($properties);
        $object->save();
		
        return $object;
    }

	/**
     * Update an object
     *
     * @param mixed[] $properties Properties
     * 
     * @return void
     */
    public function update(Array $properties)
    {
        foreach ($properties as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
		
        $this->save();
    }

	/**
     * Update a single property
     *
     * @param string $key   Property name
     * @param mixed  $value Property value
     * 
     * @return void
     */
    public function updateProperty($key, $value)
    {
        if (property_exists($this, $key)) {
            $this->$key = $value;
        }
        $this->save();
    }
	
	/**
     * Save this object
     *
     * @return void
     */
    protected function save()
    {
        $db = self::connect();
		
        $fields   = self::getFields();
        $replace  = "REPLACE INTO `" . self::getTableName() . "`(`" . implode('`, `', $fields) . "`)";

        $function = function ($value) {
            return ':' . $value;
        };

        $replace .= " VALUES (" . implode(',', array_map($function, $fields)) . ")";
        $statement = $db->prepare($replace);

        foreach ($fields as $field) {
            $statement->bindParam(":{$field}", $this->$field);
			$statement->bindParam("$field", $this->$field);
        }

        $statement->execute();

		$this->id = (int)$db->lastInsertId();
    }

	/**
     * Delete an object
     *
     * @return void
     */
    public function delete()
    {
        $db = self::connect();
		
        $delete    = "DELETE FROM " . self::getTableName() . " WHERE `id` = :id";
        $statement = $db->prepare($delete);
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
        $statement->execute();
    }

	/**
     * Count all objects that matches the conditions
     *
     * @return void
     */
    public function count($options)
    {
        $db = self::connect();
		$result = array();
		
		$function = function ($value)
		{
			return ':' . $value;
		};
		
		$conditions = null;
		
		if (isset($options['conditions']) && is_array($options['conditions']))
		{
			$conditions = $options['conditions'][0];
		}
		
        $select    = "SELECT COUNT(*) as `count` FROM `" . self::getTableName() . "`" . (isset($conditions) ? ' WHERE ' . $conditions : '') . (isset($options['limit']) ? ' LIMIT ' . $options['limit'] : '');
        $statement = $db->prepare($select);
		
		if ($conditions != null)
		{
			for ($i=1; $i < count($options['conditions']); ++$i):
				$statement->bindParam($i, $options['conditions'][$i]);
			endfor;
		}
        
        $statement->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
		
		foreach ($rows as $row):
			$result[] = new static($row);
		endforeach;
		
        return $result[0]->count;
    }
	
	/**
     * Get the name of the table, add before camelized letters a underline,
	 * if the user define the tableName property the valor of that property
	 * will be returned
     *
     * @return string
     */
	protected static function getTableName()
	{
		$called_class = get_called_class();
		
		if (property_exists(get_called_class(), 'tableName')) 
		{
			$reflection_class = new ReflectionClass($called_class);
			return $reflection_class->tableName;
		}
		
		return strtolower(substr(preg_replace('/([A-Z])/', '_$1', $called_class), 0));
	}
}