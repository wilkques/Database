<?php

namespace Wilkques\Database\Connections\Connectors\PDO;

use InvalidArgumentException;
use Wilkques\Helpers\Arrays;

class Connections
{
    /**
     * @param array $config
     * 
     * @return \Wilkques\Database\Connections\Connections
     */
    public function connection($config)
    {
        $driver = Arrays::get($config, 'driver');

        switch ($driver) {
            case 'mysql':
                return \Wilkques\Database\Connections\Connectors\PDO\Drivers\MySqlConnector::connect($config);
                break;
        }

        throw new InvalidArgumentException("Unsupported driver [{$driver}].");
    }

    /**
     * @param array $config
     * 
     * @return \Wilkques\Database\Connections\Connections
     */
    public static function connect($config)
    {
        $instance = new static;

        return call_user_func(array($instance, 'connection'), $config);
    }
}
