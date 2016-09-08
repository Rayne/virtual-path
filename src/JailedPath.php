<?php

/**
 * (c) Dennis Meckel
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */

namespace Rayne\VirtualPath;

/**
 * A jailed path is a path with a fixed prefix (the jail path)
 * and an optional normalised relative suffix path.
 *
 * `JailedPath` utilises `VirtualPath` for path normalisation
 * and detecting jailbreak attempts.
 *
 * @see VirtualPath
 */
class JailedPath
{
    /**
     * @var bool
     */
    private $hasJailbreakAttempt = false;

    /**
     * @var string
     */
    private $jailPath;

    /**
     * @var string
     */
    private $relativePath;

    /**
     * @param string $jail Trusted path, e.g. on a local or remote file system.
     * @param mixed $path Untrusted user input which gets normalised.
     */
    public function __construct($jail, $path)
    {
        $virtualPath = new VirtualPath($path);

        $this->jailPath = (string)$jail;
        $this->relativePath = ltrim($virtualPath, '/');
        $this->hasJailbreakAttempt = !$virtualPath->isTrusted();
    }

    /**
     * @return string
     */
    public function getAbsolutePath()
    {
        return $this->relativePath === ''
            ? $this->jailPath
            : rtrim($this->jailPath, '/') . '/' . $this->relativePath;
    }

    /**
     * @return string
     */
    public function getJailPath()
    {
        return $this->jailPath;
    }

    /**
     * @return string
     */
    public function getRelativePath()
    {
        return $this->relativePath;
    }

    /**
     * @return bool
     */
    public function hasJailbreakAttempt()
    {
        return $this->hasJailbreakAttempt;
    }
}
