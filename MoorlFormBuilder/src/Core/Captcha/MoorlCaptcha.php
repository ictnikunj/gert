<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Captcha;

use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Captcha\AbstractCaptcha;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class MoorlCaptcha extends AbstractCaptcha
{
    public const CAPTCHA_NAME = 'moorlCaptcha';
    public const CAPTCHA_REQUEST_PARAMETER = 'moorl_captcha_confirm';
    public const CAPTCHA_SESSION = 'moorl_captcha_session';
    public const INVALID_CAPTCHA_CODE = 'captcha.basic-captcha-invalid';

    private RequestStack $requestStack;

    private SystemConfigService $systemConfigService;

    public function __construct(RequestStack $requestStack, SystemConfigService $systemConfigService)
    {
        $this->requestStack = $requestStack;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request): bool
    {
        /** @var SalesChannelContext|null $context */
        $context = $request->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $salesChannelId = $context ? $context->getSalesChannelId() : null;

        $activeCaptchas = $this->systemConfigService->get('core.basicInformation.activeCaptchasV2', $salesChannelId);

        if (empty($activeCaptchas) || !\is_array($activeCaptchas)) {
            return false;
        }

        return $request->isMethod(Request::METHOD_POST)
            && \in_array(self::CAPTCHA_NAME, array_keys($activeCaptchas), true)
            && $activeCaptchas[self::CAPTCHA_NAME]['isActive'];
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(Request $request): bool
    {
        $basicCaptchaValue = $request->get(self::CAPTCHA_REQUEST_PARAMETER);

        if ($basicCaptchaValue === null) {
            return false;
        }

        $session = $this->requestStack->getSession();
        $captchaSession = $session->get($request->get('formId') . self::CAPTCHA_SESSION);

        if ($captchaSession === null) {
            return false;
        }

        return strtolower($basicCaptchaValue) === strtolower($captchaSession);
    }

    /**
     * {@inheritdoc}
     */
    public function shouldBreak(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::CAPTCHA_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getViolations(): ConstraintViolationList
    {
        $violations = new ConstraintViolationList();
        $violations->add(new ConstraintViolation(
            '',
            '',
            [],
            '',
            '/' . self::CAPTCHA_REQUEST_PARAMETER,
            '',
            null,
            self::INVALID_CAPTCHA_CODE
        ));

        return $violations;
    }

    private static function getIsSet(array $var, $index, $alt = null)
    {
        if (isset($var[$index])) {
            return $var[$index];
        } else {
            return $alt;
        }
    }

    public static function generate($config): array
    {
        $captcha = '';
        $captchaHeight = self::getIsSet($config, 'MoorlFormBuilder.config.captchaHeight', 48);
        $captchaWidth = self::getIsSet($config, 'MoorlFormBuilder.config.captchaWidth', 160);
        $totalCharacters = self::getIsSet($config, 'MoorlFormBuilder.config.totalCharacters', 5);
        $possibleLetters = self::getIsSet($config, 'MoorlFormBuilder.config.possibleLetters', '123456789mnbvcxzasdfghjklpoiuytrewwq');
        $captchaFont = __DIR__ . '/CaptchaFonts/' . (self::getIsSet($config, 'MoorlFormBuilder.config.captchaFont', 'RobotoMono-Regular')) . '.ttf';
        $randomDots = self::getIsSet($config, 'MoorlFormBuilder.config.randomDots', 50);
        $randomLines = self::getIsSet($config, 'MoorlFormBuilder.config.randomLines', 25);
        $textColor = self::getIsSet($config, 'MoorlFormBuilder.config.textColor', '78979a');
        $noiseColor = self::getIsSet($config, 'MoorlFormBuilder.config.noiseColor', '78979a');
        $backgroundColor = self::getIsSet($config, 'MoorlFormBuilder.config.backgroundColor', 'ffffff');

        $character = 0;
        while ($character < $totalCharacters) {
            $captcha .= substr($possibleLetters, random_int(0, strlen($possibleLetters) - 1), 1);
            $character++;
        }

        $captchaFontSize = $captchaHeight * 0.65;

        $captchaImage = imagecreate($captchaWidth, $captchaHeight);

        $arrayBackgroundColor = self::hextorgb($backgroundColor);
        $backgroundColor = imagecolorallocate($captchaImage, $arrayBackgroundColor['red'], $arrayBackgroundColor['green'], $arrayBackgroundColor['blue']);

        $arrayTextColor = self::hextorgb($textColor);
        $textColor = imagecolorallocate($captchaImage, $arrayTextColor['red'], $arrayTextColor['green'], $arrayTextColor['blue']);

        $arrayNoiseColor = self::hextorgb($noiseColor);
        $imageNoiseColor = imagecolorallocate($captchaImage, $arrayNoiseColor['red'], $arrayNoiseColor['green'], $arrayNoiseColor['blue']);

        for ($captchaDotsCount = 0; $captchaDotsCount < $randomDots; $captchaDotsCount++) {
            imagefilledellipse($captchaImage, random_int(0, $captchaWidth), random_int(0, $captchaHeight), 2, 3, $imageNoiseColor);
        }
        for ($captchaLinesCount = 0; $captchaLinesCount < $randomLines; $captchaLinesCount++) {
            imageline($captchaImage, random_int(0, $captchaWidth), random_int(0, $captchaHeight), random_int(0, $captchaWidth), random_int(0, $captchaHeight), $imageNoiseColor);
        }
        $text_box = imagettfbbox($captchaFontSize, 0, $captchaFont, $captcha);
        $x = intval(($captchaWidth - $text_box[4]) / 2);
        $y = intval(($captchaHeight - $text_box[5]) / 2);
        imagettftext($captchaImage, $captchaFontSize, 0, $x, $y, $textColor, $captchaFont, $captcha);

        return [
            'im' => $captchaImage,
            'captcha' => $captcha,
        ];
    }

    private static function hextorgb($hexstring)
    {
        $integar = hexdec(str_replace('#', '', $hexstring));
        return array("red" => 0xFF & ($integar >> 0x10),
            "green" => 0xFF & ($integar >> 0x8),
            "blue" => 0xFF & $integar
        );
    }
}
