<?php

/**
 * (c) Dennis Meckel
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */

namespace Rayne\VirtualPath;

use stdClass;

/**
 *
 */
class VirtualPathTest extends TestCase
{
    public function provideTrustedPaths()
    {
        $cases = [
            ['', '/', []],
            ['/', '/', []],
            ['\\', '/', []],

            ['hello', '/hello', ['hello']],
            ['/hello', '/hello', ['hello']],
            ['\\hello', '/hello', ['hello']],

            // This could be a valid file inside the virtual root!
            ['etc/passwd', '/etc/passwd', ['etc', 'passwd']],
            ['/etc/passwd', '/etc/passwd', ['etc', 'passwd']],

            ['etc\\passwd', '/etc/passwd', ['etc', 'passwd']],
            ['\\etc\\passwd', '/etc/passwd', ['etc', 'passwd']],

            // Backslashes and slashes.
            ['/hello\\world', '/hello/world', ['hello', 'world']],
            ['\\hello/world', '/hello/world', ['hello', 'world']],

            // Relative path traversing without leaving the virtual root.
            ['/hello/../world', '/world', ['world']],
            ['/hello/../world/..', '/', []],
            ['/hello/../world/../again', '/again', ['again']],

            ['\\hello\\..\\world', '/world', ['world']],
            ['\\hello\\..\\world\\..', '/', []],
            ['\\hello\\..\\world\\..\\again', '/again', ['again']],

            // Backslashes and slashes.
            ['/hello\\../world', '/world', ['world']],

            // Absolute Microsoft Windows path.
            ['C:\\\\hello\\world', '/C:/hello/world', ['C:', 'hello', 'world']],

            // Control codes aren't interpreted, altered or removed.
            ["/hello/\rlorem/\nipsum/..", "/hello/\rlorem", ['hello', "\rlorem"]],

            // Whitespaces aren't shortened.
            ['  ', '/  ', ['  ']],
        ];

        // Clone cases, but convert the untrusted path to an object.
        return array_merge($cases, array_map(function ($case) {
            $case[0] = $this->mockStringObject($case[0]);
            return $case;
        }, $cases));
    }

    /**
     * @dataProvider provideTrustedPaths
     * @param mixed $untrustedPath
     * @param string $trustedPath
     * @param string[] $trustedSegments
     */
    public function testTrustedPath($untrustedPath, $trustedPath, $trustedSegments)
    {
        $path = new VirtualPath($untrustedPath);

        $this->assertSame($trustedPath, (string)$path);
        $this->assertSame($trustedPath, $path->getTrustedPath());
        $this->assertSame($trustedSegments, $path->getSegments());

        $this->assertSame((string)$untrustedPath, $path->getUntrustedPath());

        $this->assertTrue($path->isTrusted());
    }

    public function provideJailbreakAttempts()
    {
        $cases = [
            ['..', '/', []],

            // Without root. Slashes.
            ['../', '/', []],
            ['../..', '/', []],
            [str_repeat('../', 32) . 'etc', '/etc', ['etc']],
            [str_repeat('../', 32) . 'etc/passwd', '/etc/passwd', ['etc', 'passwd']],

            // Without root. Backslashes.
            ['..\\', '/', []],
            ['..\\..', '/', []],
            [str_repeat('..\\', 32) . 'etc', '/etc', ['etc']],
            [str_repeat('..\\', 32) . 'etc/passwd', '/etc/passwd', ['etc', 'passwd']],

            // With root. Slashes.
            ['/..', '/', []],
            ['/../', '/', []],
            ['/../..', '/', []],
            [str_repeat('/..', 32) . '/etc', '/etc', ['etc']],
            [str_repeat('/..', 32) . '/etc/passwd', '/etc/passwd', ['etc', 'passwd']],

            // With root. Backslashes.
            ['\\..', '/', []],
            ['\\..\\', '/', []],
            ['\\..\\..', '/', []],
            [str_repeat('\\..', 32) . '\\etc', '/etc', ['etc']],
            [str_repeat('\\..', 32) . '\\etc\\passwd', '/etc/passwd', ['etc', 'passwd']],

            ['/hello/../world/../..', '/', []],
            ['/hello/../../world', '/world', ['world']],

            ['\\hello\\..\\world\\..\\..', '/', []],
            ['\\hello\\..\\..\\world', '/world', ['world']],

            // Control codes aren't interpreted, altered or removed.
            ["Hello/../../\rLorem", "/\rLorem", ["\rLorem"]],

            // Whitespaces aren't shortened.
            ["../  ", "/  ", ['  ']],
        ];

        // Clone cases, but convert the untrusted path to an object.
        return array_merge($cases, array_map(function ($case) {
            $case[0] = $this->mockStringObject($case[0]);
            return $case;
        }, $cases));
    }

    /**
     * @dataProvider provideJailbreakAttempts
     * @param mixed $untrustedPath
     * @param string $trustedPath
     * @param string[] $trustedSegments
     */
    public function testJailbreakAttempt($untrustedPath, $trustedPath, $trustedSegments)
    {
        $path = new VirtualPath($untrustedPath);

        $this->assertSame($trustedPath, (string)$path);
        $this->assertSame($trustedPath, $path->getTrustedPath());
        $this->assertSame($trustedSegments, $path->getSegments());

        $this->assertSame(
            is_object($untrustedPath) ? (string)$untrustedPath : $untrustedPath,
            $path->getUntrustedPath()
        );

        $this->assertFalse($path->isTrusted());
    }

    public function provideInvalidFormats()
    {
        return [
            [[]],
            [['/hello/world']],

            // `stdClass` has no `__toString` method
            // which makes it an invalid choice as path.
            [new stdClass],
        ];
    }

    /**
     * @dataProvider provideInvalidFormats
     * @param mixed $untrustedFormat
     */
    public function testInvalidFormat($untrustedFormat)
    {
        $path = new VirtualPath($untrustedFormat);

        $this->assertFalse($path->isTrusted());
        $this->assertSame('', $path->getUntrustedPath());
        $this->assertSame('/', $path->getTrustedPath());
        $this->assertSame([], $path->getSegments());
    }

    public function provideParents()
    {
        /**
         * The parent of the root directory is the root directory itself.
         */
        $cases = [
            // With root. Slashes.
            ['/', '/', []],
            ['/..', '/', []],
            ['/../..', '/', []],
            ['/Hello World/☺/../..', '/', []],

            // With root. Backslashes.
            ['\\', '/', []],
            ['\\..', '/', []],
            ['\\..\\..', '/', []],
            ['\\Hello World\\☺\\..\\..', '/', []],

            // Without root. Slashes.
            ['', '/', []],
            ['..', '/', []],
            ['../..', '/', []],
            ['Hello World/☺/../..', '/', []],

            // Without root. Backslashes.
            ['', '/', []],
            ['..', '/', []],
            ['..\\..', '/', []],
            ['Hello World\\☺\\..\\..', '/', []],

            // Non-root parents.
            ['/Hello World/☺/Lorem Ipsum', '/Hello World/☺', ['Hello World', '☺']],
            ['/Hello World\\☺/Lorem Ipsum\\', '/Hello World/☺', ['Hello World', '☺']],
            ['/Hello World/☺', '/Hello World', ['Hello World']],
            ['/Hello World/☺/', '/Hello World', ['Hello World']],
            ['/Hello World/☺\\', '/Hello World', ['Hello World']],
            ['/Hello World/☺/..', '/', []],

            // Control codes aren't interpreted, altered or removed.
            ["Hello/\rLorem\nIpsum", '/Hello', ['Hello']],
            ["Hello/\rLorem\nIpsum\backslash", "/Hello/\rLorem\nIpsum", ['Hello', "\rLorem\nIpsum"]],

            // Whitespaces aren't shortened.
            ["  /    ", "/  ", ['  ']],
        ];

        // Clone cases, but convert the untrusted path to an object.
        return array_merge($cases, array_map(function ($case) {
            $case[0] = $this->mockStringObject($case[0]);
            return $case;
        }, $cases));
    }

    /**
     * @dataProvider provideParents
     * @param $childUntrustedPath
     * @param $trustedPath
     * @param $trustedSegments
     */
    public function testParent($childUntrustedPath, $trustedPath, $trustedSegments)
    {
        $parent = (new VirtualPath($childUntrustedPath))->buildParent();

        $this->assertSame(true, $parent->isTrusted());
        $this->assertSame($trustedPath, $parent->getUntrustedPath());

        $this->assertSame($trustedPath, (string)$parent->getTrustedPath());
        $this->assertSame($trustedPath, $parent->getTrustedPath());
        $this->assertSame($trustedSegments, $parent->getSegments());
    }
}
