<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OTPHP;

use Assert\Assertion;

final class HOTP extends OTP implements HOTPInterface
{
    /**
     * HOTP constructor.
     *
     * @param string      $label
     * @param string|null $secret
     * @param int         $counter
     * @param string      $digest
     * @param int         $digits
     */
    public function __construct($label, $secret = null, $counter = 0, $digest = 'sha1', $digits = 6)
    {
        parent::__construct($label, $secret, $digest, $digits);
        $this->setCounter($counter);
    }

    /**
     * @param int $counter
     */
    private function setCounter($counter)
    {
        Assertion::integer($counter, 'Counter must be at least 0.');
        Assertion::greaterOrEqualThan($counter, 0, 'Counter must be at least 0.');

        $this->setParameter('counter', $counter);
    }

    /**
     * {@inheritdoc}
     */
    public function getCounter()
    {
        return $this->getParameter('counter');
    }

    /**
     * @param int $counter
     */
    private function updateCounter($counter)
    {
        $this->setCounter($counter);
    }

    /**
     * {@inheritdoc}
     */
    public function getProvisioningUri($google_compatible = true)
    {
        return $this->generateURI('hotp', ['counter' => $this->getCounter()]);
    }

    /**
     * {@inheritdoc}
     */
    public function verify($otp, $counter, $window = null)
    {
        Assertion::string($otp, 'The OTP must be a string');
        Assertion::integer($counter, 'The counter must be an integer');
        Assertion::greaterOrEqualThan($counter, 0, 'The counter must be at least 0.');
        Assertion::nullOrInteger($window, 'The window parameter must be null or an integer');

        if ($counter < $this->getCounter()) {
            return false;
        }

        return $this->verifyOtpWithWindow($otp, $counter, $window);
    }

    /**
     * @param null|int $window
     *
     * @return int
     */
    private function getWindow($window)
    {
        if (null === $window) {
            $window = 0;
        }

        return abs($window);
    }

    /**
     * @param string $otp
     * @param int    $counter
     * @param int    $window
     *
     * @return bool
     */
    private function verifyOtpWithWindow($otp, $counter, $window)
    {
        $window = $this->getWindow($window);

        for ($i = $counter; $i <= $counter + $window; ++$i) {
            if ($this->compareOTP($this->at($i), $otp)) {
                $this->updateCounter($i + 1);

                return true;
            }
        }

        return false;
    }
}
