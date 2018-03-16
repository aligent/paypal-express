<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Config;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class PayPalExpressConfigFactory implements PayPalExpressConfigFactoryInterface
{
    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    protected $identifierGenerator;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @var Mcrypt
     */
    protected $encoder;

    /**
     * @param IntegrationIdentifierGeneratorInterface $identifierGenerator
     * @param LocalizationHelper                      $localizationHelper
     * @param SymmetricCrypterInterface               $encoder
     */
    public function __construct(
        IntegrationIdentifierGeneratorInterface $identifierGenerator,
        LocalizationHelper $localizationHelper,
        SymmetricCrypterInterface $encoder
    ) {
        $this->identifierGenerator = $identifierGenerator;
        $this->localizationHelper  = $localizationHelper;
        $this->encoder             = $encoder;
    }

    /**
     * {@inheritdoc}
     */
    public function createConfig(PayPalExpressSettings $settings)
    {
        return new PayPalExpressConfig(
            $this->getLocalizedValue($settings->getLabels()),
            $this->getLocalizedValue($settings->getShortLabels()),
            $settings->getChannel()->getName(),
            $this->getDecryptedValue($settings->getClientId()),
            $this->getDecryptedValue($settings->getClientSecret()),
            $this->identifierGenerator->generateIdentifier($settings->getChannel()),
            $settings->isSandboxMode()
        );
    }

    /**
     * @param Collection $values
     * @return string
     */
    protected function getLocalizedValue(Collection $values)
    {
        return (string)$this->localizationHelper->getLocalizedValue($values);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function getDecryptedValue($value)
    {
        return (string)$this->encoder->decryptData($value);
    }
}
