<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Verify\V1\Service;

use Twilio\Options;
use Twilio\Values;

/**
 * PLEASE NOTE that this class contains beta products that are subject to change. Use them with caution.
 */
abstract class VerificationOptions {
    /**
     * @param string $sendDigits Digits to send when a phone call is started
     * @param string $locale Locale used in the sms or call.
     * @param string $customCode A pre-generated code
     * @param string $amount Amount of the associated PSD2 compliant transaction.
     * @param string $payee Payee of the associated PSD2 compliant transaction.
     * @return CreateVerificationOptions Options builder
     */
    public static function create($sendDigits = Values::NONE, $locale = Values::NONE, $customCode = Values::NONE, $amount = Values::NONE, $payee = Values::NONE) {
        return new CreateVerificationOptions($sendDigits, $locale, $customCode, $amount, $payee);
    }
}

class CreateVerificationOptions extends Options {
    /**
     * @param string $sendDigits Digits to send when a phone call is started
     * @param string $locale Locale used in the sms or call.
     * @param string $customCode A pre-generated code
     * @param string $amount Amount of the associated PSD2 compliant transaction.
     * @param string $payee Payee of the associated PSD2 compliant transaction.
     */
    public function __construct($sendDigits = Values::NONE, $locale = Values::NONE, $customCode = Values::NONE, $amount = Values::NONE, $payee = Values::NONE) {
        $this->options['sendDigits'] = $sendDigits;
        $this->options['locale'] = $locale;
        $this->options['customCode'] = $customCode;
        $this->options['amount'] = $amount;
        $this->options['payee'] = $payee;
    }

    /**
     * Digits to send when a phone call is started, same parameters as in Programmable Voice are supported
     * 
     * @param string $sendDigits Digits to send when a phone call is started
     * @return $this Fluent Builder
     */
    public function setSendDigits($sendDigits) {
        $this->options['sendDigits'] = $sendDigits;
        return $this;
    }

    /**
     * Supported values are af, ar, ca, cs, da, de, el, en, es, fi, fr, he, hi, hr, hu, id, it, ja, ko, ms, nb, nl, pl, pt, pr-BR, ro, ru, sv, th, tl, tr, vi, zh, zh-CN, zh-HK
     * 
     * @param string $locale Locale used in the sms or call.
     * @return $this Fluent Builder
     */
    public function setLocale($locale) {
        $this->options['locale'] = $locale;
        return $this;
    }

    /**
     * Pass in a pre-generated code. Code length can be between 4-10 characters.
     * 
     * @param string $customCode A pre-generated code
     * @return $this Fluent Builder
     */
    public function setCustomCode($customCode) {
        $this->options['customCode'] = $customCode;
        return $this;
    }

    /**
     * Amount of the associated PSD2 compliant transaction. Requires the PSD2 Service flag enabled.
     * 
     * @param string $amount Amount of the associated PSD2 compliant transaction.
     * @return $this Fluent Builder
     */
    public function setAmount($amount) {
        $this->options['amount'] = $amount;
        return $this;
    }

    /**
     * Payee of the associated PSD2 compliant transaction. Requires the PSD2 Service flag enabled.
     * 
     * @param string $payee Payee of the associated PSD2 compliant transaction.
     * @return $this Fluent Builder
     */
    public function setPayee($payee) {
        $this->options['payee'] = $payee;
        return $this;
    }

    /**
     * Provide a friendly representation
     * 
     * @return string Machine friendly representation
     */
    public function __toString() {
        $options = array();
        foreach ($this->options as $key => $value) {
            if ($value != Values::NONE) {
                $options[] = "$key=$value";
            }
        }
        return '[Twilio.Verify.V1.CreateVerificationOptions ' . implode(' ', $options) . ']';
    }
}