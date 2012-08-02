<?php
/**
 * Model class for all models
 *
 * @author Zachary Fox
 * @url http://www.zacharyfox.com/blog/php/simple-model-crud-with-php-5-3
 */

abstract class ModelBase
{
    /**
     * Pass properties to construct
     *
     * @param mixed[] $properties The object properties
     * 
     * @throws Exception
     */
    protected function __construct(Array $properties)
    {
        $reflect = new ReflectionObject($this);

        foreach ($reflect->getProperties() as $property) {
            if (!array_key_exists($property->name, $properties)) {
                throw new Exception("Unable to create object. Missing property: {$property->name}");
            }

            $this->{$property->name} = $properties[$property->name];
        }
    }

    /**
     * Get all class properties
     *
     * @return string[]
     */
    protected static function getFields()
    {
        static $fields = array();
        $called_class  = get_called_class();

        if (!array_key_exists($called_class, $fields)) {
            $reflection_class = new ReflectionClass($called_class);

            $properties = array();

            foreach ($reflection_class->getProperties() as $property) {
                $properties[] = $property->name;
            }

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
        return "SELECT " . implode(', ', self::getFields()) . " FROM " . self::get_table_name();
    }

    /**
     * Save this object
     *
     * @return void
     */
    protected function save()
    {
        global $db;
		
        $fields   = self::getFields();
        $replace  = "REPLACE INTO " . self::get_table_name() . "(" . implode(',', $fields) . ")";

        $function = function ($value) {
            return ':' . $value;
        };

        $replace .= " VALUES (" . implode(',', array_map($function, $fields)) . ")";

        $statement = $db->prepare($replace);

        foreach ($fields as $field) {
            $statement->bindParam(":{$field}", $this->$field);
			$statement->bindParam("$field", $this->$field);
			echo ":{$field} = " . $this->$field.'<br>';
        }

        $statement->execute();

		echo '<pre>';
		print_r($statement);
		print_r($this);
    }

    /**
     * Get a single object by id
     *
     * @param integer $id
     * @return Object
     */
    public static function get($id)
    {
        global $db;
		
        $select    = self::getSelect() . " WHERE `id` = :id";
        $statement = $db->prepare($select);

        $statement->bindParam(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
		
        return new static($result[0]);
    }

    /**
     * Get all objects
     *
     * @return Object[]
     */
    public static function getAll()
    {
        global $db;

        $return = array();
        foreach ($db->query(self::getSelect(), PDO::FETCH_ASSOC) as $row) {
            $return[] = new static($row);
        }

        return $return;
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
        global $db;
		
		//$properties['id'] = ($db->query('SELECT MAX(id) FROM ' . self::get_table_name())->fetchColumn() + 1);
		$properties['id'] = (int)$db->lastInsertId() + 1; 
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
     * Delete an object
     *
     * @return void
     */
    public function delete()
    {
        global $db;

        $delete    = "DELETE FROM " . self::get_table_name() . " WHERE `id` = :id";
        $statement = $db->prepare($statement);
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);

        $statement->execute();
    }

	public function get_table_name()
	{
		return strtolower(substr(preg_replace('/([A-Z])/', '_$1', get_called_class()), 1));
	}
}