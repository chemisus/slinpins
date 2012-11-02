<?php
/**
 * Copyright (c) 2012 Terrence Howard <chemius@gmail.com>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy 
 * of this software and associated documentation files (the "Software"), to deal 
 * in the Software without restriction, including without limitation the rights 
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 * copies of the Software, and to permit persons to whom the Software is 
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in 
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE 
 * SOFTWARE.
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
     * @var     string  Regular expression used to parse injection annotations.
     */
    const ANNOTATION_PATTERN = '/^\s*\*\s*(\@inject)\s+(\S+)(?:\s+((?:\d+(?!\S))|(?:\$\S+)))?/';
    
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

        $values = \array_values(array_shift($arrays));
        
        $keys = \array_flip($values);

        foreach ($arrays as $array) {
            foreach ($array as $key=>$value) {
                if (\is_integer($key) && $key < count($values)) {
                    $values[$key] = $value;
                }
                else if (isset($keys[$key])) {
                    $values[$keys[$key]] = $value;
                }
            }
        }

        return $values;
    }

    /**
     * Returns the reflection object for a function.
     *
     * @param   callable    $function
     * @return  \ReflectionFunction
     */
    public function reflectFunction(callable $function) {
        return new \ReflectionFunction($function);
    }

    /**
     * Returns the reflection object for a method.
     *
     * @param   object|string       $class
     * @param   string              $method
     * @return  \ReflectionMethod
     */
    public function reflectMethod($class, $method) {
        return new \ReflectionMethod($class, $method);
    }

    /**
     * Returns the reflection object for a constructor.
     *
     * @param   object|string   $value
     * @return  string[]
     */
    public function reflectConstructor($value) {
        $constructor = new \ReflectionClass($value);

        return $constructor->getConstructor();
    }

    /**
     * Returns the names of each parameter in the array provided.
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
     * Parses out the injection info in an annotation docblock.
     *
     * @param   string      $annotation
     * @return  string[]
     */
    public function annotations($annotation) {
        $pattern = self::ANNOTATION_PATTERN;

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
     * Returns the value of a provider.
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
     * Returns an array containing the values from the providers specified by
     * the keys array.
     *
     * @param   string[]    $keys
     * @param   mixed[]     $values1
     * @param   mixed[]     $values2
     * @param   mixed[]     ...
     * @return  mixed[]
     */
    public function values($keys, $values1=array(), $values2=array()) {
        $values = array();

        $arrays = \func_get_args();

        array_shift($arrays);

        $arrays = \array_reverse($arrays);

        foreach ($keys as $index=>$key) {
            $found = false;

            foreach ($arrays as $array) {
                if (isset($array[$key])) {
                    $values[] = $array[$key];

                    $found = true;

                    break;
                }

                if (isset($array[$index])) {
                    $values[] = $array[$index];

                    $found = true;

                    break;
                }

            }

            if (!$found) {
                $values[] = $this->fetch($key);
            }
        }

        return $values;
    }

    /**
     * Returns a Closure object that when called injects the parameters into
     * the method provided.
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
     * Returns a Closure object that when called injects the parameters into
     * the constructor of the class provided.
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
     * Stores a provider.
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
     * Creates a provider for a constant value.
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
     * Creates a provider for a variable value.
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
     * Creates a provider for a method.
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
     * Creates a provider for a factory.
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
     * Creates a provider for a service.
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
