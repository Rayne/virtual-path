<?php

/**
 * (c) Dennis Meckel
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */

namespace Rayne\VirtualPath;

/**
 *
 */
class JailedPathTest extends TestCase
{
    /**
     * String casts are not supported as it's not clear
     * whether the absolute or relative path should be returned.
     */
    public function testStringCastNotSupported()
    {
        $this->assertFalse(method_exists(JailedPath::class, '__toString'));
    }

    public function provideJailbreaks()
    {
        $cases = [
            ['/tmp', './..', '/tmp', ''],
            ['/tmp', '../..', '/tmp', ''],
            ['/tmp', '../etc/passwd', '/tmp/etc/passwd', 'etc/passwd'],
            ['/tmp', '.X0-lock/../..', '/tmp', ''],

            // The following case breaks and then enters the jail again.
            // That's technically a jailbreak nonetheless.
            ['/tmp', '../tmp', '/tmp/tmp', 'tmp'],
        ];

        // Clone cases, but convert the untrusted path to an object.
        return array_merge($cases, array_map(function ($case) {
            $case[1] = $this->mockStringObject($case[1]);
            return $case;
        }, $cases));
    }

    /**
     * @dataProvider provideJailbreaks
     * @param mixed $jail
     * @param mixed $path
     * @param string $expectedAbsolutePath
     * @param string $expectedRelativePath
     */
    public function testJailbreaks($jail, $path, $expectedAbsolutePath, $expectedRelativePath)
    {
        $path = new JailedPath($jail, $path);

        $this->assertTrue($path->hasJailbreakAttempt());
        $this->assertSame($expectedAbsolutePath, $path->getAbsolutePath());
        $this->assertSame($jail, $path->getJailPath());
        $this->assertSame($expectedRelativePath, $path->getRelativePath());
    }

    public function provideHarmlessPaths()
    {
        $cases = [
            ['/tmp', '', ''],
            ['/tmp', '.X0-lock', '.X0-lock'],
            ['/tmp', '/.X0-lock', '.X0-lock'],
            ['/tmp', 'etc/passwd', 'etc/passwd'],
            ['/tmp', '/etc/passwd', 'etc/passwd'],

            // Empty relative path.
            ['/tmp', '', ''],
            ['/tmp', '.', ''],
            ['/tmp', '/', ''],
            ['/tmp', './', ''],

            ['/tmp', 'hello/..', ''],
            ['/tmp', './hello/..', ''],
            ['/tmp', '/hello/..', ''],
            ['/tmp', './hello/..', ''],

            // Crazy "Hello World" example.
            ['/tmp', './hello/../world//.//', 'world'],
        ];

        // Clone cases, but convert the untrusted path to an object.
        return array_merge($cases, array_map(function ($case) {
            $case[1] = $this->mockStringObject($case[1]);
            return $case;
        }, $cases));
    }

    /**
     * @dataProvider provideHarmlessPaths
     * @param mixed $jail
     * @param mixed $path
     * @param string $expectedRelativePath
     */
    public function testHarmlessPaths($jail, $path, $expectedRelativePath)
    {
        $path = new JailedPath($jail, $path);

        $this->assertFalse($path->hasJailbreakAttempt());

        $this->assertSame(
            $expectedRelativePath === '' ? $jail : $jail . '/' . $expectedRelativePath,
            $path->getAbsolutePath()
        );

        $this->assertSame($jail, $path->getJailPath());

        $this->assertSame($expectedRelativePath, $path->getRelativePath());
    }
}
