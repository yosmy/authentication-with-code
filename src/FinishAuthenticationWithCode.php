<?php

namespace Yosmy;

use Yosmy;
use LogicException;

/**
 * @di\service()
 */
class FinishAuthenticationWithCode
{
    /**
     * @var NormalizePhone
     */
    private $normalizePhone;

    /**
     * @var Yosmy\Phone\CompleteVerification
     */
    private $completeVerification;

    /**
     * @var AnalyzePreFinishAuthenticationWithCode[]
     */
    private $analyzePreFinishAuthenticationWithCodeServices;

    /**
     * @var AnalyzePostFinishAuthenticationWithCodeSuccess[]
     */
    private $analyzePostFinishAuthenticationWithCodeSuccessServices;

    /**
     * @var AnalyzePostFinishAuthenticationWithCodeFail[]
     */
    private $analyzePostFinishAuthenticationWithCodeFailServices;

    /**
     * @di\arguments({
     *     analyzePreFinishAuthenticationWithCodeServices:         '#yosmy.pre_finish_authentication_with_code',
     *     analyzePostFinishAuthenticationWithCodeSuccessServices: '#yosmy.post_finish_authentication_with_code_success',
     *     analyzePostFinishAuthenticationWithCodeFailServices:    '#yosmy.post_finish_authentication_with_code_fail'
     * })
     *
     * @param NormalizePhone                                   $normalizePhone
     * @param Phone\CompleteVerification                       $completeVerification
     * @param AnalyzePreFinishAuthenticationWithCode[]         $analyzePreFinishAuthenticationWithCodeServices
     * @param AnalyzePostFinishAuthenticationWithCodeSuccess[] $analyzePostFinishAuthenticationWithCodeSuccessServices
     * @param AnalyzePostFinishAuthenticationWithCodeFail[]    $analyzePostFinishAuthenticationWithCodeFailServices
     */
    public function __construct(
        NormalizePhone $normalizePhone,
        Phone\CompleteVerification $completeVerification, 
        array $analyzePreFinishAuthenticationWithCodeServices,
        array $analyzePostFinishAuthenticationWithCodeSuccessServices,
        array $analyzePostFinishAuthenticationWithCodeFailServices
    ) {
        $this->normalizePhone = $normalizePhone;
        $this->completeVerification = $completeVerification;
        $this->analyzePreFinishAuthenticationWithCodeServices = $analyzePreFinishAuthenticationWithCodeServices;
        $this->analyzePostFinishAuthenticationWithCodeSuccessServices = $analyzePostFinishAuthenticationWithCodeSuccessServices;
        $this->analyzePostFinishAuthenticationWithCodeFailServices = $analyzePostFinishAuthenticationWithCodeFailServices;
    }

    /**
     * @param string $device
     * @param string $country
     * @param string $prefix
     * @param string $number
     * @param string $code
     *
     * @throws DeniedAuthenticationException
     */
    public function finish(
        string $device,
        string $country,
        string $prefix,
        string $number,
        string $code
    ) {
        try {
            $this->normalizePhone->normalize(
                $country,
                $prefix,
                $number
            );
        } catch (Phone\InvalidNumberException $e) {
            throw new LogicException(null, null, $e);
        }

        foreach ($this->analyzePreFinishAuthenticationWithCodeServices as $analyzePreFinishAuthenticationWithCode) {
            try {
                $analyzePreFinishAuthenticationWithCode->analyze(
                    $device,
                    $country,
                    $prefix,
                    $number
                );
            } catch (DeniedAuthenticationException $e) {
                foreach ($this->analyzePostFinishAuthenticationWithCodeFailServices as $analyzePostFinishAuthenticationWithCodeFail) {
                    $analyzePostFinishAuthenticationWithCodeFail->analyze(
                        $device,
                        $country,
                        $prefix,
                        $number,
                        $e
                    );
                }

                throw $e;
            }
        }

        try {
            $this->completeVerification->complete(
                $country,
                $prefix,
                $number,
                $code
            );
        } catch (Phone\VerificationException $e) {
            foreach ($this->analyzePostFinishAuthenticationWithCodeFailServices as $analyzePostFinishAuthenticationWithCodeFail) {
                $analyzePostFinishAuthenticationWithCodeFail->analyze(
                    $device,
                    $country,
                    $prefix,
                    $number,
                    $e
                );
            }

            throw new DeniedAuthenticationException($e->getMessage());
        }

        foreach ($this->analyzePostFinishAuthenticationWithCodeSuccessServices as $analyzePostFinishAuthenticationWithCodeSuccess) {
            $analyzePostFinishAuthenticationWithCodeSuccess->analyze(
                $device,
                $country,
                $prefix,
                $number
            );
        }
    }
}
