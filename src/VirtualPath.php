<?php

/**
 * (c) Dennis Meckel
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */

namespace Rayne\VirtualPath;

/**
 * A `VirtualPath` object represents a normalised path.
 * It detects, removes and flags malicious directory traversals.
 *
 * *Example*: The path `hello/../world/../../test` gets normalized to `/test`
 * but also gets the "jail-breaking attempt" flag set.
 *
 * @see JailedPath
 */
class VirtualPath
{
    /**
     * @var bool
     */
    private $hasJailbreakAttempt = false;

    /**
     * @var string[]
     */
    private $segments = [];

    /**
     * @var string
     */
    private $trustedPath;

    /**
     * @var string
     */
    private $untrustedPath;

    /**
     * @param string|mixed $untrustedPath Gets string casted if possible. Otherwise gets flagged as untrusted and replaced by an empty string.
     */
    public function __construct($untrustedPath)
    {
        $this->setUntrustedPath($untrustedPath);
        $this->createPathSegments();

        $this->trustedPath = '/' . implode('/', $this->segments);
    }

    /**
     * @return string Normalised and trusted path relative to the virtual jail.
     * @see VirtualPath::getTrustedPath()
     */
    public function __toString()
    {
        return $this->getTrustedPath();
    }

    /**
     * Converts the untrusted input to string.
     * If converting to string isn't possible
     * the jailbreak attempt flag will be set
     * and an empty string will be defined as untrusted value.
     *
     * @param mixed $untrustedPath
     */
    private function setUntrustedPath($untrustedPath)
    {
        if (is_scalar($untrustedPath) || is_object($untrustedPath) && method_exists($untrustedPath, '__toString')) {
            $this->untrustedPath = (string) $untrustedPath;
        } else {
            $this->untrustedPath = '';

            // Invalid input is interpreted as malicious input.
            $this->hasJailbreakAttempt = true;
        }
    }

    /**
     *
     */
    private function createPathSegments()
    {
        foreach (explode('/', str_replace('\\', '/', $this->untrustedPath)) as $segment) {
            if ($segment === '.' || $segment === '') {
                continue;
            }

            if ($segment === '..') {
                if (null === array_pop($this->segments)) {
                    $this->hasJailbreakAttempt = true;
                }

                continue;
            }

            $this->segments[] = $segment;
        }
    }

    /**
     * @return string[]
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * @return string Normalised and trusted path relative to the virtual jail.
     */
    public function getTrustedPath()
    {
        return $this->trustedPath;
    }

    /**
     * @return string Untrusted and perhaps malicious path.
     */
    public function getUntrustedPath()
    {
        return $this->untrustedPath;
    }

    /**
     * @return bool Whether the original path input is trustworthy.
     */
    public function isTrusted()
    {
        return !$this->hasJailbreakAttempt;
    }

    /**
     * @return VirtualPath Parent of the trusted path. By definition trusted.
     */
    public function buildParent()
    {
        return new self(dirname($this->getTrustedPath()));
    }
}
