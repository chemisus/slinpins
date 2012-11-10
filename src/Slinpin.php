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
 */

/**
 * 
 * @author      Terrence Howard <chemisus@gmail.com>
 */
class Slinpins implements Scope {
    const ANNOTATION_PATTERN = '/^\s*\*\s*(\@inject)\s+(\S+)(?:\s+((?:\d+(?!\S))|(?:\$\S+)))?/';
    
    private $providers = array();

    public function get($key) {
        if (!isset($this->providers[$key])) {
            return null;
        }
        
        return $this->providers[$key]->get($this);
    }

    public function provider($key, \Provider $provider) {
        $this->providers[$key] = $provider;
    }

    public function offsetExists($key) {
        return isset($this->providers[$key]);
    }

    public function offsetGet($key) {
        return $this->get($key);
    }

    public function offsetSet($key, $value) {

    }

    public function offsetUnset($key) {

    }

    public function __get($key) {
        return $this->get($key);
    }

    public function __call($key, $params) {
        return $this[$key]($params);
    }

    public function __construct() {
        $scope = $this;
        
        $this->provider('inject', new \MethodProvider(function ($value, $values=array(), $keys=array()) use ($scope) {
            return $scope->invoke($value, $values, $keys);
        }));

        $this->provider('instance', new \MethodProvider(function ($value, $values=array(), $keys=array()) use ($scope) {
            return $scope->construct($value, $values, $keys);
        }));
    }

    public function parameters($parameters) {
        $names = array();

        foreach ($parameters as $key=>$parameter) {
            $names[$key] = $parameter->getName();
        }

        return $names;
    }
    
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
    
    public function keys(array $array1, array $array2=array()) {
        $arrays = \func_get_args();

        $values = \array_values(array_shift($arrays));

        $keys = \array_flip($values);

        foreach ($arrays as $array) {
            foreach ((array)$array as $key=>$value) {
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

    public function values($keys, $values1=array(), $values2=array()) {
        $values = array();

        $arrays = \func_get_args();

        array_shift($arrays);

        $reversed = \array_reverse($arrays);

        foreach ($keys as $index=>$key) {
            $found = false;

            foreach ($reversed as $array) {
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
                $values[] = $this->get($key);
            }
        }

        return $values;
    }
    
    public function invoke($value, $values=array(), $keys=array()) {
        if (is_array($value)) {
            $method = new \ReflectionMethod($value[0], $value[1]);

            return $method->invokeArgs($value[0], (array)$values);
        }

        $method = new \ReflectionFunction($value);
        
        $parameters = $this->parameters($method->getParameters());

        $annotations = $this->annotations($method->getDocComment());

        $keys = $this->keys($parameters, $annotations, $keys);
        
        return $method->invokeArgs($this->values($keys, $values));
    }

    public function construct($value, $values=array(), $keys=array()) {
        $class = new \ReflectionClass($value);

        $constructor = $class->getConstructor();
        
        if ($constructor) {
            $parameters = $this->parameters($constructor->getParameters());

            $annotations = $this->annotations($constructor->getDocComment());

            $keys = $this->keys($parameters, $annotations, $keys);
            
            return $class->newInstanceArgs($this->values($keys, $values));
        }

        return $class->newInstanceWithoutConstructor();
    }
}
