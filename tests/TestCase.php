<?php

/**
 * (c) Dennis Meckel
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */

namespace Rayne\VirtualPath;

use PHPUnit_Framework_MockObject_MockObject;
use stdClass;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param mixed $value
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockStringObject($value)
    {
        $builder = $this->getMockBuilder(stdClass::class);
        $builder->setMethods(['__toString']);

        $mock = $builder->getMock();
        $mock->method('__toString')->willReturn($value);

        return $mock;
    }
}
