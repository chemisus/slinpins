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
class FactoryProvider implements Provider {
    private $value;

    private $values;

    private $keys;

    public function __construct($value, $values=array(), $keys=array()) {
        $this->value = $value;

        $this->values = $values;

        $this->keys = $keys;
    }

    public function get(\Scope $scope) {
        $value = $this->value;

        return function () use ($scope, $value) {
            return $scope->construct($value, $this->values, $this->keys);
        };
    }
}
