<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\Config;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Entity\Repository\PayPalExpressSettingsRepository;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfig;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigFactoryInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigProvider;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\AuthorizeAndCaptureAction;
use Psr\Log\LoggerInterface;

class PayPalExpressConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PayPalExpressConfigProvider
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected $logger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PayPalExpressConfigFactoryInterface
     */
    protected $factory;

    protected function setUp()
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->factory = $this->createMock(PayPalExpressConfigFactoryInterface::class);

        $this->configProvider = new PayPalExpressConfigProvider($this->doctrine, $this->logger, $this->factory);
    }

    public function testGetPaymentConfigs()
    {
        $fooIntegrationIdentifier = 'paypal_express_1';
        $fooSetting = $this->getSetting(
            'foo integration',
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'BxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'foo label',
            'foo short label',
            true
        );
        $fooConfig = new PayPalExpressConfig(
            'foo label',
            'foo short label',
            'foo integration',
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'BxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'paypal_express_1',
            AuthorizeAndCaptureAction::NAME,
            true
        );

        $barIntegrationIdentifier = 'paypal_express_2';
        $barSetting = $this->getSetting(
            'bar integration',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'DxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'bar label',
            'bar short label',
            false
        );
        $barConfig = new PayPalExpressConfig(
            'bar label',
            'bar short label',
            'bar integration',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'DxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'paypal_express_2',
            AuthorizeAndCaptureAction::NAME,
            false
        );
        $expectedConfigs = [$fooIntegrationIdentifier => $fooConfig, $barIntegrationIdentifier => $barConfig];

        $settings = [$fooSetting, $barSetting];

        $repository = $this->createMock(PayPalExpressSettingsRepository::class);
        $repository->expects($this->once())
            ->method('getEnabledIntegrationsSettings')
            ->willReturn($settings);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(PayPalExpressSettings::class)
            ->willReturn($repository);

        $this->factory->expects($this->exactly(2))
            ->method('createConfig')
            ->willReturnMap(
                [
                    [$fooSetting, $fooConfig],
                    [$barSetting, $barConfig],
                ]
            );

        $this->logger->expects($this->never())
            ->method('critical');

        $actualConfigs = $this->configProvider->getPaymentConfigs();
        $this->assertEquals($expectedConfigs, $actualConfigs);
    }

    public function testGetPaymentConfigsWillLogAnyExceptionAndRecoversAfterIt()
    {
        $expectedExceptionMessage = 'Test Exception Occurred';
        $expectedException = new \RuntimeException($expectedExceptionMessage);

        $fooSetting = $this->getSetting(
            'foo integration',
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'BxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'foo label',
            'foo short label',
            true
        );
        $settings = [$fooSetting];

        $repository = $this->createMock(PayPalExpressSettingsRepository::class);
        $repository->expects($this->once())
            ->method('getEnabledIntegrationsSettings')
            ->willReturn($settings);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(PayPalExpressSettings::class)
            ->willReturn($repository);

        $this->factory->expects($this->any())
            ->method('createConfig')
            ->willThrowException($expectedException);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($expectedExceptionMessage, ['exception' => $expectedException]);

        $actualConfigs = $this->configProvider->getPaymentConfigs();
        $this->assertEquals([], $actualConfigs);
    }

    /**
     * @param string  $name
     * @param string  $clientId
     * @param string  $clientSecret
     * @param string  $labels
     * @param string  $shortLabels
     * @param bool    $isSandbox
     *
     * @return PayPalExpressSettings
     */
    protected function getSetting($name, $clientId, $clientSecret, $labels, $shortLabels, $isSandbox)
    {
        $setting = new PayPalExpressSettings();
        $setting->setClientId($clientId);
        $setting->setClientSecret($clientSecret);

        $localizedFallbackValue = new LocalizedFallbackValue();
        $localizedFallbackValue->setText($labels);
        $localizedFallbackValue->setString($labels);
        $setting->setLabels(new ArrayCollection([$localizedFallbackValue]));

        $localizedFallbackValue = new LocalizedFallbackValue();
        $localizedFallbackValue->setText($shortLabels);
        $localizedFallbackValue->setString($shortLabels);
        $setting->setShortLabels(new ArrayCollection([$localizedFallbackValue]));

        $setting->setSandboxMode($isSandbox);
        $channel = new Channel();
        $channel->setName($name);
        $setting->setChannel($channel);

        return $setting;
    }
}
