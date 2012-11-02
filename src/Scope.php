<?php
/**
 *
 * @author      Terrence Howard <chemisus@gmail.com>
 * @package     Slinpins
 * @copyright   (c) 2012, Terrence Howard
 * @version     $Id$
 */

/**
 *
 * @author      Terrence Howard <chemisus@gmail.com>
 * @version     $Id$
 * @since       0.1
 */
class Scope {
    /**
     *
     * @var array
     */
    private $providers = array();

    /**
     * Returns an array containing the keys that should be used to inject into
     * a method.
     *
     * @param   string[]    $array1
     * @param   string[]    $array2
     * @param   string[]    ...
     * 
     * @return  string[]    The keys that will be used to be injected into a
     * method.
     */
    public function keys(array $array1, array $array2=array()) {
        $arrays = \func_get_args();
        
        $keys = \array_shift($arrays);
        
        foreach ($arrays as $array) {
            foreach ($array as $index=>$key) {
                if (isset($array[$index])) {
                    $keys[$index] = $array[$index];
                }

                if (isset($array[$key])) {
                    $keys[$index] = $array[$key];
                }
            }
        }

        return $keys;
    }

    /**
     * 
     * @param   callable    $function
     * @return  \ReflectionFunction
     */
    public function reflectFunction(callable $function) {
        return new \ReflectionFunction($function);
    }

    /**
     * 
     * @param   object|string       $class
     * @param   string              $method
     * @return  \ReflectionMethod
     */
    public function reflectMethod($class, $method) {
        return new \ReflectionMethod($class, $method);
    }

    /**
     * 
     * @param   object|string   $value
     * @return  string[]
     */
    public function reflectConstructor($value) {
        $constructor = new \ReflectionClass($value);

        return $constructor->getConstructor();
    }

    /**
     * 
     * @param   \ReflectionMethod[]     $parameters
     * @return  string[]
     */
    public function parameters($parameters) {
        $names = array();

        foreach ($parameters as $key=>$parameter) {
            $names[$key] = $parameter->getName();
        }

        return $names;
    }

    /**
     * 
     * @param   string      $annotation
     * @return  string[]
     */
    public function annotations($annotation) {
        $pattern = '/^\s*\*\s*(\@inject)\s+(\S+)(?:\s+((?:\d+(?!\S))|(?:\$\S+)))?/';

        $values = \preg_grep($pattern, explode("\n", $annotation || ''));

        $annotations = array();

        foreach (array_values($values) as $index=>$value) {
            $matches = array();

            \preg_match($pattern, $value, $matches);

            if (isset($matches[3])) {
                $annotations[$matches[3]] = $matches[2];
            }
            else {
                $annotations[$index] = $matches[2];
            }
        }

        return $annotations;
    }

    /**
     *
     * @param   string  $key
     * @return  mixed
     */
    public function fetch($key) {
        if (!isset($this->providers[$key])) {
            return null;
        }

        return $this->providers[$key]($this);
    }

    /**
     *
     * @param   string[]    $keys
     * @param   mixed[]     $params
     * @param   mixed[]     $locals
     * @return  mixed[]
     */
    public function values($keys, $params=array(), $locals=array()) {
        $values = array();

        foreach ($keys as $index=>$key) {
            if (isset($locals[$key])) {
                $values[] = $locals[$key];
            }
            else if (isset($locals[$index])) {
                $values[] = $locals[$index];
            }
            else if (isset($params[$key])) {
                $values[] = $params[$key];
            }
            else if (isset($params[$index])) {
                $values[] = $params[$index];
            }
            else {
                $values[] = $this->fetch($key);
            }
        }

        return $values;
    }

    /**
     * 
     * @param   callable    $value
     * @param   mixed[]     $params
     * @param   string[]    $injections
     * @return  \Closure
     */
    public function inject(
            callable $value,
            array $params=array(),
            array $injections=array()) {

        $scope = $this;

        return function ($scope) use ($value, $params, $injections) {
            if (is_array($value)) {
                $method = $scope->reflectMethod($value[0], $value[1]);
            }
            else {
                $method = $scope->reflectFunction($value);
            }

            $parameters = $this->parameters($method->getParameters());

            $annotations = $scope->annotations($method->getDocComment());

            $keys = $scope->keys(
                $parameters,
                $annotations,
                $injections
            );

            if (is_array($value) && is_string($value[0])) {
                $callback = function ($object, $locals) use ($scope, $method, $keys, $params) {
                    $args = $scope->values($keys, $params, $locals);

                    return $method->invokeArgs($object, $args);
                };
            }
            else if (is_array($value)) {
                $callback = function ($locals) use ($scope, $method, $keys, $params) {
                    $args = $scope->values($keys, $params, $locals);

                    return $method->invokeArgs($value[0], $args);
                };
            }
            else {
                $callback = function ($locals) use ($scope, $method, $keys, $params) {
                    $args = $scope->values($keys, $params, $locals);

                    return $method->invokeArgs($args);
                };
            }

            return function ($locals=array()) use ($callback) {
                return $callback($locals);
            };
        };
    }

    /**
     * 
     * @param   string      $value
     * @param   mixed[]     $params
     * @param   string[]    $injections
     * @return  \Closure
     */
    public function instance(
            $value,
            array $params=array(),
            array $injections=array()) {

        $scope = $this;

        return function ($scope) use ($value, $params, $injections) {
            static $method = null;

            static $keys = null;

            if ($method === null) {
                $method = new \ReflectionClass($value);

                $parameters = $this->parameters(
                    $method->getConstructor() !== null ?
                    $method->getConstructor()->getParameters() :
                    array()
                );

                $annotations = $scope->annotations($method->getDocComment());

                $keys = $scope->keys(
                    $parameters,
                    $annotations,
                    $injections
                );
            }

            $callback = function ($locals) use ($scope, $method, $keys, $params) {
                $args = $scope->values($keys, $params, $locals);

                return $method->newInstanceArgs($args);
            };

            return function ($locals=array()) use ($callback) {
                return $callback($locals);
            };
        };
    }

    /**
     *
     * @param   string      $key
     * @param   \Closure    $value
     */
    public function provider($key, $value) {
        $this->providers[$key] = function ($scope) use ($value) {
            static $provider = null;

            if ($provider === null) {
                $provider = $value($scope);
            }

            return $provider($scope);
        };
    }

    /**
     *
     * @param   string  $key
     * @param   mixed   $value
     */
    public function constant($key, $value) {
        $this->provider($key, function ($scope) use ($value) {
            return function ($scope) use ($value) {
                return $value;
            };
        });
    }

    /**
     * 
     * @param   string    $key
     * @param   callable  $value
     * @param   mixed[]   $args
     * @param   string[]  $keys
     */
    public function variable($key, $value, $args=array(), $keys=array()) {
        $this->provider($key, function ($scope) use ($value, $args, $keys) {
            return function ($scope) use ($value, $args, $keys) {
                $inject = $scope->inject($value, $args, $keys);

                $callback = $inject($scope);

                return $callback();
            };
        });
    }

    /**
     *
     * @param   string    $key
     * @param   callable  $value
     * @param   mixed[]   $args
     * @param   string[]  $keys
     */
    public function method($key, $value, $args=array(), $keys=array()) {
        $this->provider($key, function ($scope) use ($value, $args, $keys) {
            return function ($scope) use ($value, $args, $keys) {
                $inject = $scope->inject($value, $args, $keys);

                $callback = $inject($scope);

                return $callback;
            };
        });
    }

    /**
     *
     * @param   string    $key
     * @param   string    $value
     * @param   mixed[]   $args
     * @param   string[]  $keys
     */
    public function factory($key, $value, $args=array(), $keys=array()) {
        $this->provider($key, function ($scope) use ($value, $args, $keys) {
            return function ($scope) use ($value, $args, $keys) {
                $instance = $scope->instance($value, $args, $keys);

                $callback = $instance($scope);

                return $callback;
            };
        });
    }

    /**
     *
     * @param   string    $key
     * @param   callable  $value
     * @param   mixed[]   $args
     * @param   string[]  $keys
     */
    public function service($key, $value, $args=array(), $keys=array()) {
        $this->provider($key, function ($scope) use ($value, $args, $keys) {
            $callback = $scope->inject($value, $args, $keys);

            $service = $callback($scope);

            return function ($scope) use ($service) {
                return $service;
            };
        });
    }
}
