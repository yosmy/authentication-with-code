<?php

namespace Yosmy\Test;

use PHPUnit\Framework\TestCase;
use LogicException;
use Yosmy;
use Yosmy\DeniedAuthenticationException;

class FinishAuthenticationWithCodeTest extends TestCase
{
    public function testFinish()
    {
        $device = 'device';
        $country = 'country';
        $prefix = 'prefix';
        $number = 'number';
        $code = 'code';
        $id = 'id';
        $credential = new Yosmy\Credential(
            $id,
            'token',
            $country,
            $prefix,
            $number,
            ['role']
        );

        $normalizePhone = $this->createMock(Yosmy\NormalizePhone::class);

        $normalizePhone->expects($this->once())
            ->method('normalize')
            ->with(
                $country,
                $prefix,
                $number
            );

        $completeVerification = $this->createMock(Yosmy\Phone\CompleteVerification::class);

        $completeVerification->expects($this->once())
            ->method('complete')
            ->with(
                $country,
                $prefix,
                $number,
                $code
            );

        $analyzePreFinishAuthenticationWithCodeServices = $this->createMock(Yosmy\AnalyzePreFinishAuthenticationWithCode::class);

        $analyzePreFinishAuthenticationWithCodeServices->expects($this->once())
            ->method('analyze')
            ->with(
                $device,
                $country,
                $prefix,
                $number
            );

        $analyzePostFinishAuthenticationWithCodeSuccessServices = $this->createMock(Yosmy\AnalyzePostFinishAuthenticationWithCodeSuccess::class);

        $analyzePostFinishAuthenticationWithCodeSuccessServices->expects($this->once())
            ->method('analyze')
            ->with(
                $device,
                $country,
                $prefix,
                $number
            );

        $startRegistration = $this->createMock(Yosmy\StartRegistration::class);

        $startRegistration->expects($this->once())
            ->method('start')
            ->with(
                $device,
                $country,
                $prefix,
                $number
            )
            ->willReturn($id);

        $buildCredential = $this->createMock(Yosmy\BuildCredential::class);

        $buildCredential->expects($this->once())
            ->method('build')
            ->with(
                $id
            )
            ->willReturn($credential);

        $finishAuthenticationWithCode = new Yosmy\FinishAuthenticationWithCode(
            $normalizePhone,
            $completeVerification,
            $startRegistration,
            $buildCredential,
            [$analyzePreFinishAuthenticationWithCodeServices],
            [$analyzePostFinishAuthenticationWithCodeSuccessServices],
            []
        );

        try {
            $expectedCredential = $finishAuthenticationWithCode->finish(
                $device,
                $country,
                $prefix,
                $number,
                $code
            );
        } catch (Yosmy\DeniedAuthenticationException $e) {
            throw new LogicException();
        }

        $this->assertEquals(
            $expectedCredential,
            new Yosmy\Credential(
                $id,
                'token',
                $country,
                $prefix,
                $number,
                ['role']
            )
        );
    }

    public function testFinishHavingInvalidNumberException()
    {
        $device = 'device';
        $country = 'country';
        $prefix = 'prefix';
        $number = 'number';

        $e = new Yosmy\Phone\InvalidNumberException();

        $normalizePhone = $this->createMock(Yosmy\NormalizePhone::class);

        $normalizePhone->expects($this->once())
            ->method('normalize')
            ->willThrowException($e);

        $completeVerification = $this->createMock(Yosmy\Phone\CompleteVerification::class);

        $startRegistration = $this->createMock(Yosmy\StartRegistration::class);

        $buildCredential = $this->createMock(Yosmy\BuildCredential::class);

        $this->expectException(LogicException::class);

        $finishAuthenticationWithCode = new Yosmy\FinishAuthenticationWithCode(
            $normalizePhone,
            $completeVerification,
            $startRegistration,
            $buildCredential,
            [],
            [],
            []
        );

        try {
            $finishAuthenticationWithCode->finish(
                $device,
                $country,
                $prefix,
                $number,
                ''
            );
        } catch (Yosmy\DeniedAuthenticationException $e) {
            throw new LogicException();
        }
    }

    /**
     * @throws DeniedAuthenticationException
     */
    public function testFinishHavingDeniedException()
    {
        $device = 'device';
        $country = 'country';
        $prefix = 'prefix';
        $number = 'number';
        $message = 'message';

        $normalizePhone = $this->createMock(Yosmy\NormalizePhone::class);

        $analyzePreFinishAuthenticationWithCodeServices = $this->createMock(Yosmy\AnalyzePreFinishAuthenticationWithCode::class);

        $e = new Yosmy\DeniedAuthenticationException($message);

        $analyzePreFinishAuthenticationWithCodeServices->expects($this->once())
            ->method('analyze')
            ->willThrowException($e);

        $analyzePostFinishAuthenticationWithCodeFailServices = $this->createMock(Yosmy\AnalyzePostFinishAuthenticationWithCodeFail::class);

        $analyzePostFinishAuthenticationWithCodeFailServices->expects($this->once())
            ->method('analyze')
            ->with(
                $device,
                $country,
                $prefix,
                $number,
                $e
            );

        $completeVerification = $this->createMock(Yosmy\Phone\CompleteVerification::class);

        $startRegistration = $this->createMock(Yosmy\StartRegistration::class);

        $buildCredential = $this->createMock(Yosmy\BuildCredential::class);

        $this->expectExceptionObject($e);

        $finishAuthenticationWithCode = new Yosmy\FinishAuthenticationWithCode(
            $normalizePhone,
            $completeVerification,
            $startRegistration,
            $buildCredential,
            [$analyzePreFinishAuthenticationWithCodeServices],
            [],
            [$analyzePostFinishAuthenticationWithCodeFailServices]
        );

        try {
            $finishAuthenticationWithCode->finish(
                $device,
                $country,
                $prefix,
                $number,
                ''
            );
        } catch (DeniedAuthenticationException $e) {
            throw $e;
        }
    }

    /**
     * @throws DeniedAuthenticationException
     */
    public function testFinishHavingVerificationException()
    {
        $device = 'device';
        $country = 'country';
        $prefix = 'prefix';
        $number = 'number';
        $message = 'message';

        $e = new Yosmy\Phone\VerificationException($message);

        $normalizePhone = $this->createMock(Yosmy\NormalizePhone::class);

        $completeVerification = $this->createMock(Yosmy\Phone\CompleteVerification::class);

        $completeVerification->expects($this->once())
            ->method('complete')
            ->willThrowException($e);

        $startRegistration = $this->createMock(Yosmy\StartRegistration::class);

        $buildCredential = $this->createMock(Yosmy\BuildCredential::class);

        $analyzePostFinishAuthenticationWithCodeFailServices = $this->createMock(Yosmy\AnalyzePostFinishAuthenticationWithCodeFail::class);

        $analyzePostFinishAuthenticationWithCodeFailServices->expects($this->once())
            ->method('analyze')
            ->with(
                $device,
                $country,
                $prefix,
                $number,
                $e
            );

        $this->expectExceptionObject(new DeniedAuthenticationException($message));

        $finishAuthenticationWithCode = new Yosmy\FinishAuthenticationWithCode(
            $normalizePhone,
            $completeVerification,
            $startRegistration,
            $buildCredential,
            [],
            [],
            [$analyzePostFinishAuthenticationWithCodeFailServices]
        );

        try {
            $finishAuthenticationWithCode->finish(
                $device,
                $country,
                $prefix,
                $number,
                ''
            );
        } catch (DeniedAuthenticationException $e) {
            throw $e;
        }
    }
}