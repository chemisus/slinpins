<?php
class Scope {
    private $providers = array();
    
    public function keys($params, $injects=array()) {
        $keys = array();
        
        foreach ($params as $index=>$param) {
            $keys[$index] = $param->getName();
        }
        
        foreach ($injects as $index=>$key) {
            $keys[$index] = $key;
        }
        
        return $keys;
    }
    
    public function fetch($key) {
        if (!isset($this->providers[$key])) {
            return null;
        }
        
        return $this->providers[$key]($this);
    }
    
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
    
    public function inject(
            callable $value, 
            array $params=array()) {
        
        $scope = $this;
        
        return function ($scope) use ($value, $params) {
            if (is_array($value) && is_string($value[0])) {
                $method = new \ReflectionMethod($value[0], $value[1]);
                
                $keys = $scope->keys($method->getParameters());
                
                $callback = function ($object, $locals) use ($scope, $method, $keys, $params) {
                    $args = $scope->values($keys, $params, $locals);
                    
                    return $method->invokeArgs($object, $args);
                };
            }
            else if (is_array($value)) {
                $method = new \ReflectionMethod($value[0], $value[1]);
                
                $keys = $scope->keys($method->getParameters());
                
                $callback = function ($locals) use ($scope, $method, $keys, $params) {
                    $args = $scope->values($keys, $params, $locals);
                    
                    return $method->invokeArgs($value[0], $args);
                };
            }
            else {
                $method = new \ReflectionFunction($value);
                
                $keys = $scope->keys($method->getParameters());
                
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

    public function instance(
            $value, 
            array $params=array()) {
        
        $scope = $this;
        
        return function ($scope) use ($value, $params) {
            $method = new \ReflectionClass($value);

            $keys = $scope->keys(
                $method->getConstructor() !== null ? 
                $method->getConstructor()->getParameters() : 
                array()
            );

            $callback = function ($locals) use ($scope, $method, $keys, $params) {
                $args = $scope->values($keys, $params, $locals);

                return $method->newInstanceArgs($args);
            };
            
            return function ($locals=array()) use ($callback) {
                return $callback($locals);
            };
        };
    }
    
    public function provider($key, $value) {
        $this->providers[$key] = function ($scope) use ($value) {
            static $provider = null;
            
            if ($provider === null) {
                $provider = $value($scope);
            }
            
            return $provider($scope);
        };
    }
    
    public function constant($key, $value) {
        $this->provider($key, function ($scope) use ($value) {
            return function ($scope) use ($value) {
                return $value;
            };
        });
    }
    
    public function variable($key, $value, $args=array()) {
        $this->provider($key, function ($scope) use ($value, $args) {
            return function ($scope) use ($value, $args) {
                $inject = $scope->inject($value, $args);
                
                $callback = $inject($scope);
                
                return $callback();
            };
        });
    }
    
    public function method($key, $value, $args=array()) {
        $this->provider($key, function ($scope) use ($value, $args) {
            return function ($scope) use ($value, $args) {
                $inject = $scope->inject($value, $args);
                
                $callback = $inject($scope);
                
                return $callback;
            };
        });
    }
    
    public function factory($key, $value, $args=array()) {
        $this->provider($key, function ($scope) use ($value, $args) {
            return function ($scope) use ($value, $args) {
                $instance = $scope->instance($value, $args);
                
                $callback = $instance($scope);
                
                return $callback;
            };
        });
    }
    
    public function service($key, $value, $args=array()) {
        $this->provider($key, function ($scope) use ($value, $args) {
            $callback = $scope->inject($value, $args);
            
            $service = $callback($scope);
            
            return function ($scope) use ($service) {
                return $service;
            };
        });
    }
}
