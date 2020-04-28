<?php

namespace Yosmy\Test;

use PHPUnit\Framework\TestCase;
use LogicException;
use Yosmy;

class StartAuthenticationWithCodeTest extends TestCase
{
    public function testStart()
    {
        $device = 'device';
        $country = 'country';
        $prefix = 'prefix';
        $number = 'number';
        $template = 'template';

        $phone = new Yosmy\Phone\Normalization(
            $country,
            $prefix,
            $number
        );

        $normalizePhone = $this->createMock(Yosmy\NormalizePhone::class);

        $normalizePhone->expects($this->once())
            ->method('normalize')
            ->with(
                $country,
                $prefix,
                $number
            )
            ->willReturn($phone);

        $analyzePreStartAuthenticationWithCodeService = $this->createMock(Yosmy\AnalyzePreStartAuthenticationWithCode::class);

        $analyzePreStartAuthenticationWithCodeService->expects($this->once())
            ->method('analyze')
            ->with(
                $device,
                $phone->getCountry(),
                $phone->getPrefix(),
                $phone->getNumber()
            );

        $startVerification = $this->createMock(Yosmy\Phone\StartVerification::class);

        $startVerification->expects($this->once())
            ->method('start')
            ->with(
                $phone->getCountry(),
                $phone->getPrefix(),
                $phone->getNumber(),
                $template
            );

        $analyzePostStartAuthenticationWithCodeSuccessService = $this->createMock(Yosmy\AnalyzePostStartAuthenticationWithCodeSuccess::class);

        $analyzePostStartAuthenticationWithCodeSuccessService->expects($this->once())
            ->method('analyze')
            ->with(
                $device,
                $phone->getCountry(),
                $phone->getPrefix(),
                $phone->getNumber()
            );

        $startAuthenticationWithCode = new Yosmy\StartAuthenticationWithCode(
            $normalizePhone,
            $startVerification,
            [$analyzePreStartAuthenticationWithCodeService],
            [$analyzePostStartAuthenticationWithCodeSuccessService],
            []
        );

        try {
            $startAuthenticationWithCode->start(
                $device,
                $phone->getCountry(),
                $phone->getPrefix(),
                $phone->getNumber(),
                $template
            );
        } catch (Yosmy\DeniedAuthenticationException | Yosmy\Phone\InvalidNumberException $e) {
            throw new LogicException();
        }
    }

    /**
     * @throws Yosmy\Phone\InvalidNumberException
     */
    public function testStartHavingInvalidNumberException()
    {
        $device = 'device';
        $country = 'country';
        $prefix = 'prefix';
        $number = 'number';

        $e = new Yosmy\Phone\InvalidNumberException();

        $normalizePhone = $this->createMock(Yosmy\NormalizePhone::class);

        $normalizePhone->expects($this->once())
            ->method('normalize')
            ->with(
                $country,
                $prefix,
                $number
            )
            ->willThrowException($e);

        $startVerification = $this->createMock(Yosmy\Phone\StartVerification::class);

        $analyzePostStartAuthenticationWithCodeFailService = $this->createMock(Yosmy\AnalyzePostStartAuthenticationWithCodeFail::class);

        $analyzePostStartAuthenticationWithCodeFailService->expects($this->once())
            ->method('analyze')
            ->with(
                $device,
                $country,
                $prefix,
                $number,
                $e
            );

        $this->expectException(Yosmy\Phone\InvalidNumberException::class);

        $startAuthenticationWithCode = new Yosmy\StartAuthenticationWithCode(
            $normalizePhone,
            $startVerification,
            [],
            [],
            [$analyzePostStartAuthenticationWithCodeFailService]
        );

        try {
            $startAuthenticationWithCode->start(
                $device,
                $country,
                $prefix,
                $number,
                ''
            );
        } catch (Yosmy\Phone\InvalidNumberException $e) {
            throw $e;
        } catch (Yosmy\DeniedAuthenticationException $e) {
            throw new LogicException();
        }
    }

    /**
     * @throws Yosmy\DeniedAuthenticationException
     */
    public function testStartHavingDeniedExceptionOnAnalyzeIn()
    {
        $device = 'device';
        $country = 'country';
        $prefix = 'prefix';
        $number = 'number';

        $phone = new Yosmy\Phone\Normalization(
            $country,
            $prefix,
            $number
        );

        $e = new Yosmy\DeniedAuthenticationException('message');

        $normalizePhone = $this->createMock(Yosmy\NormalizePhone::class);

        $normalizePhone->expects($this->once())
            ->method('normalize')
            ->willReturn($phone);

        $startVerification = $this->createMock(Yosmy\Phone\StartVerification::class);

        $analyzePreStartAuthenticationWithCodeService = $this->createMock(Yosmy\AnalyzePreStartAuthenticationWithCode::class);

        $analyzePreStartAuthenticationWithCodeService->expects($this->once())
            ->method('analyze')
            ->willThrowException($e);

        $analyzePostStartAuthenticationWithCodeFailService = $this->createMock(Yosmy\AnalyzePostStartAuthenticationWithCodeFail::class);

        $analyzePostStartAuthenticationWithCodeFailService->expects($this->once())
            ->method('analyze')
            ->with(
                $device,
                $phone->getCountry(),
                $phone->getPrefix(),
                $phone->getNumber(),
                $e
            );

        $this->expectExceptionObject($e);

        $startAuthenticationWithCode = new Yosmy\StartAuthenticationWithCode(
            $normalizePhone,
            $startVerification,
            [$analyzePreStartAuthenticationWithCodeService],
            [],
            [$analyzePostStartAuthenticationWithCodeFailService]
        );

        try {
            $startAuthenticationWithCode->start(
                $device,
                $country,
                $prefix,
                $number,
                ''
            );
        } catch (Yosmy\DeniedAuthenticationException $e) {
            throw $e;
        } catch (Yosmy\Phone\InvalidNumberException $e) {
            throw new LogicException();
        }
    }

    /**
     * @throws Yosmy\DeniedAuthenticationException
     */
    public function testStartHavingDeniedExceptionOnStartVerification()
    {
        $device = 'device';
        $country = 'country';
        $prefix = 'prefix';
        $number = 'number';

        $message = 'message';

        $phone = new Yosmy\Phone\Normalization(
            $country,
            $prefix,
            $number
        );

        $normalizePhone = $this->createMock(Yosmy\NormalizePhone::class);

        $normalizePhone->expects($this->once())
            ->method('normalize')
            ->willReturn($phone);

        $startVerification = $this->createMock(Yosmy\Phone\StartVerification::class);

        $e = new Yosmy\Phone\VerificationException($message);

        $startVerification->expects($this->once())
            ->method('start')
            ->willThrowException($e);

        $analyzePostStartAuthenticationWithCodeFailService = $this->createMock(Yosmy\AnalyzePostStartAuthenticationWithCodeFail::class);

        $analyzePostStartAuthenticationWithCodeFailService->expects($this->once())
            ->method('analyze')
            ->with(
                $device,
                $phone->getCountry(),
                $phone->getPrefix(),
                $phone->getNumber(),
                $e
            );

        $this->expectExceptionObject(new Yosmy\DeniedAuthenticationException($e->getMessage()));

        $startAuthenticationWithCode = new Yosmy\StartAuthenticationWithCode(
            $normalizePhone,
            $startVerification,
            [],
            [],
            [$analyzePostStartAuthenticationWithCodeFailService]
        );

        try {
            $startAuthenticationWithCode->start(
                $device,
                $country,
                $prefix,
                $number,
                ''
            );
        } catch (Yosmy\DeniedAuthenticationException $e) {
            throw $e;
        } catch (Yosmy\Phone\InvalidNumberException $e) {
            throw new LogicException();
        }
    }
}