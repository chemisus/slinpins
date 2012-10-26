<?php
class Scope implements ArrayAccess {
    private $parent;
    
    private $providers = array();
    
    private $values = array();

    public function __call($key, $values) {
        $method = $this->get($key);
        
        if ($method === null) {
            echo '<pre>';
            $e = new Exception();
            print_r($e->getTraceAsString());
            echo '</pre>';
        }
        
        return call_user_func_array($method, $values);
    }
    
    public function __get($key) {
        return $this->get($key);
    }
    
    public function __set($key, $value) {
        return $this[$key] = $value;
    }

    public function offsetExists($key) {
        if (isset($this->values[$key])) {
            return true;
        }
        
        if (isset($this->providers[$key])) {
            return true;
        }
        
        if ($this->parent !== null) {
            return $this->parent->offsetExists($key);
        }
        
        return null;
    }

    public function offsetGet($key) {
        return $this->get($key);
    }

    public function offsetSet($key, $value) {
        return $this->provider($key, $value);
    }

    public function offsetUnset($key) {
        unset($this->providers[$key]);
        
        unset($this->values[$key]);
    }
    
    public function get($key, $scope=null) {
        if ($scope === null) {
            $scope = $this;
        }
        
        if (isset($this->values[$key])) {
            return $this->values[$key]($scope);
        }
        
        if (isset($this->providers[$key])) {
            $this->values[$key] = $this->providers[$key]();

            return $this->values[$key]($scope);
        }
        
        if ($this->parent !== null) {
            return $this->parent->get($key, $scope);
        }
        
        return null;
    }
    
    public function __construct($parent=null, $fields=array()) {
        $this->parent = $parent;
        
        $scope = $this;
        
        foreach ($fields as $key=>$value) {
            $this->provider($key, $this->field($value));
        }
        
        $this['scope'] = $this->field($this);
        
        $this['inject'] = $this->field(function ($method, $args=array(), $injects=array()) use ($scope) {
            $invoke = $scope->method($method, $injects);

            $invoke = $invoke();

            $invoke = $invoke($scope);

            if (is_array($method) && !is_object($method[0])) {
                return $invoke($method[0], $args);
            }

            return $invoke($args);
        });
        
        $this['instance'] = $this->field(function ($class, $args=array(), $injects=array()) use ($scope) {
            $provider = $scope->factory($class, $injects);
            
            $invoke = $provider();
            
            $invoke = $invoke($scope);
            
            return $invoke($args);
        });
    }
    
    public function provider($key, $provider) {
        $this->providers[$key] = function () use ($provider) {
            return $provider();
        };
    }
    
    public function field($value) {
        return function () use ($value) {
            return function () use ($value) {
                return $value;
            };
        };
    }
    
    public function method($method, $injects=array()) {
        $scope = $this;
        
        return function () use ($method, $scope, $injects) {
            $invoke = $scope->reflectMethod($method);
            
            $keys = $scope->keys($invoke->getParameters(), $injects);

            if ($invoke instanceof ReflectionFunction) {
                return function ($scope) use ($invoke, $keys) {
                    return function ($args=array()) use ($scope, $invoke, $keys) {
                        $values = $scope->values($keys, $args);
                        
                        return $invoke->invokeArgs($values);
                    };
                };
            }
            
            if (is_object($method[0])) {
                $object = $method[0];
                
                return function ($scope) use ($invoke, $keys, $object) {
                    return function ($args=array()) use ($scope, $invoke, $keys, $object) {
                        $values = $scope->values($keys, $args);

                        return $invoke->invokeArgs($object, $values);
                    };
                };
            }
                
            return function ($scope) use ($invoke, $keys) {
                return function ($object, $args=array()) use ($scope, $invoke, $keys) {
                    $values = $scope->values($keys, $args);

                    return $invoke->invokeArgs($object, $values);
                };
            };
        };
    }
    
    public function factory($class, $injects=array()) {
        $scope = $this;
        
        return function () use ($class, $scope, $injects) {
            $invoke = new ReflectionClass($class);
            
            $params = $invoke->getConstructor() !== null ? $invoke->getConstructor()->getParameters() : array();
            
            $keys = $scope->keys($params, $injects);

            return function ($scope) use ($invoke, $keys) {
                return function ($args=array()) use ($scope, $invoke, $keys) {
                    $values = $scope->values($keys, $args);
                    
                    return $invoke->newInstanceArgs($values);
                };
            };
        };
    }
    
    public function service($service, $injects=array()) {
        $scope = $this;
        
        return function () use ($service, $scope, $injects) {
            if (is_string($service)) {
                $invoke = new ReflectionClass($service);

                $params = $invoke->getConstructor() !== null ? $invoke->getConstructor()->getParameters() : array();

                $keys = $scope->keys($params, $injects);

                $values = $scope->values($keys);

                $instance = $invoke->newInstanceArgs($values);

                return function () use ($instance) {
                    return $instance;
                };
            }
            
            if (is_callable($service)) {
                return function () use ($scope, $service) {
                    static $value;
                    
                    if ($value === null) {
                        $value = $scope->inject($service);
                    }
                    
                    return $value;
                };
            }
        };
    }
    
    public function reflectMethod($method) {
        if (is_array($method)) {
            return new ReflectionMethod($method[0], $method[1]);
        }
        
        return new ReflectionFunction($method);
    }
    
    public function keys($params, $injects) {
        $keys = array();
        
        foreach ($params as $index=>$param) {
            $keys[$index] = $param->getName();
        }
        
        foreach ($injects as $index=>$key) {
            $keys[$index] = $key;
        }
        
        return $keys;
    }
    
    public function values($keys, $args=array()) {
        $values = array();

        foreach ($keys as $index=>$key) {
            if (isset($args[$key])) {
                $values[] = $args[$key];
            }
            else if (isset($args[$index])) {
                $values[] = $args[$index];
            }
            else {
                $values[] = $this->get($key);
            }
        }
        
        return $values;
    }
}
