<?php

namespace Yosmy;

/**
 * @di\service()
 */
class StartAuthenticationWithCode
{
    /**
     * @var NormalizePhone
     */
    private $normalizePhone;

    /**
     * @var Phone\StartVerification
     */
    private $startVerification;

    /**
     * @var AnalyzePreStartAuthenticationWithCode[]
     */
    private $analyzePreStartAuthenticationWithCodeServices;

    /**
     * @var AnalyzePostStartAuthenticationWithCodeSuccess[]
     */
    private $analyzePostStartAuthenticationWithCodeSuccessServices;

    /**
     * @var AnalyzePostStartAuthenticationWithCodeFail[]
     */
    private $analyzePostStartAuthenticationWithCodeFailServices;

    /**
     * @di\arguments({
     *     analyzePreStartAuthenticationWithCodeServices:         '#yosmy.pre_start_authentication_with_code',
     *     analyzePostStartAuthenticationWithCodeSuccessServices: '#yosmy.post_start_authentication_with_code_success',
     *     analyzePostStartAuthenticationWithCodeFailServices:    '#yosmy.post_start_authentication_with_code_fail'
     * })
     *
     * @param NormalizePhone                                  $normalizePhone
     * @param Phone\StartVerification                         $startVerification
     * @param AnalyzePreStartAuthenticationWithCode[]         $analyzePreStartAuthenticationWithCodeServices
     * @param AnalyzePostStartAuthenticationWithCodeSuccess[] $analyzePostStartAuthenticationWithCodeSuccessServices
     * @param AnalyzePostStartAuthenticationWithCodeFail[]    $analyzePostStartAuthenticationWithCodeFailServices
     */
    public function __construct(
        NormalizePhone $normalizePhone,
        Phone\StartVerification $startVerification,
        array $analyzePreStartAuthenticationWithCodeServices,
        array $analyzePostStartAuthenticationWithCodeSuccessServices,
        array $analyzePostStartAuthenticationWithCodeFailServices
    ) {
        $this->normalizePhone = $normalizePhone;
        $this->startVerification = $startVerification;
        $this->analyzePreStartAuthenticationWithCodeServices = $analyzePreStartAuthenticationWithCodeServices;
        $this->analyzePostStartAuthenticationWithCodeSuccessServices = $analyzePostStartAuthenticationWithCodeSuccessServices;
        $this->analyzePostStartAuthenticationWithCodeFailServices = $analyzePostStartAuthenticationWithCodeFailServices;
    }

    /**
     * @param string $device
     * @param string $country
     * @param string $prefix
     * @param string $number
     * @param string $template
     *
     * @throws Phone\InvalidNumberException
     * @throws DeniedAuthenticationException
     */
    public function start(
        string $device,
        string $country,
        string $prefix,
        string $number,
        string $template
    ) {
        try {
            $phone = $this->normalizePhone->normalize(
                $country,
                $prefix,
                $number
            );
        } catch (Phone\InvalidNumberException $e) {
            foreach ($this->analyzePostStartAuthenticationWithCodeFailServices as $analyzePostStartAuthenticationWithCodeFail) {
                $analyzePostStartAuthenticationWithCodeFail->analyze(
                    $device,
                    $country,
                    $prefix,
                    $number,
                    $e
                );
            }

            throw $e;
        }

        foreach ($this->analyzePreStartAuthenticationWithCodeServices as $analyzePreStartAuthenticationWithCode) {
            try {
                $analyzePreStartAuthenticationWithCode->analyze(
                    $device,
                    $phone->getCountry(),
                    $phone->getPrefix(),
                    $phone->getNumber()
                );
            } catch (DeniedAuthenticationException $e) {
                foreach ($this->analyzePostStartAuthenticationWithCodeFailServices as $analyzePostStartAuthenticationWithCodeFail) {
                    $analyzePostStartAuthenticationWithCodeFail->analyze(
                        $device,
                        $phone->getCountry(),
                        $phone->getPrefix(),
                        $phone->getNumber(),
                        $e
                    );
                }

                throw $e;
            }
        }

        try {
            $this->startVerification->start(
                $phone->getCountry(),
                $phone->getPrefix(),
                $phone->getNumber(),
                $template
            );
        } catch (Phone\VerificationException $e) {
            foreach ($this->analyzePostStartAuthenticationWithCodeFailServices as $analyzePostStartAuthenticationWithCodeFail) {
                $analyzePostStartAuthenticationWithCodeFail->analyze(
                    $device,
                    $phone->getCountry(),
                    $phone->getPrefix(),
                    $phone->getNumber(),
                    $e
                );
            }

            throw new DeniedAuthenticationException($e->getMessage());
        }

        foreach ($this->analyzePostStartAuthenticationWithCodeSuccessServices as $analyzePostStartAuthenticationWithCodeSuccess) {
            $analyzePostStartAuthenticationWithCodeSuccess->analyze(
                $device,
                $phone->getCountry(),
                $phone->getPrefix(),
                $phone->getNumber()
            );
        }
    }
}
