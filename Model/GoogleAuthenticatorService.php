<?php

namespace Elgentos\Frontend2FA\Model;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;

class GoogleAuthenticatorService extends \Neyamtux\Authenticator\Lib\PHPGangsta\GoogleAuthenticator
{
    /**
     * Get QR Code Image.
     *
     * @param string      $name
     * @param string      $secret
     * @param int         $size
     * @param string|null $issuer
     *
     * @return string
     */
    public function getQrCodeEndroid($name, $secret, $title = null, $params = [])
    {
        $size = !empty($params['size']) && (int) $params['size'] > 0 ? (int) $params['size'] : 200;
        $level = !empty($params['level']) && array_search($params['level'], ['L', 'M', 'Q', 'H']) !== false ? $params['level'] : 'M';

        $text = sprintf('otpauth://totp/%s?secret=%s', $name, $secret);
        if (true === is_string($title)) {
            $text = sprintf('%s&issuer=%s', $text, $title);
        }
        $qrCode = new QrCode($text);
        $qrCode->setSize($size);
        $qrCode->setMargin(0);
        $qrCode->setEncoding(new Encoding('UTF-8'));
        $qrCode->setSize($size);

        $writer = new PngWriter();
        return $writer->write($qrCode)->getString();
    }
}
