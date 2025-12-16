<?php

declare(strict_types=1);

use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\UnencryptedToken;

require 'vendor/autoload.php';

$tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));
$algorithm    = new Sha256();
$key = InMemory::file(__DIR__ . './/secret.key');

function createSessionToken(): UnencryptedToken
{
    $now   = new DateTimeImmutable();
    return $tokenBuilder
        // Configures the issuer (iss claim)
        ->issuedBy('http://example.com')
        // Configures the audience (aud claim)
        ->permittedFor('http://example.org')
        // Configures the subject of the token (sub claim)
        ->relatedTo('component1')
        // Configures the id (jti claim)
        ->identifiedBy('4f1g23a12aa')
        // Configures the time that the token was issue (iat claim)
        ->issuedAt($now)
        // Configures the time that the token can be used (nbf claim)
        // ->canOnlyBeUsedAfter($now->modify('+1 minute'))
        // Configures the expiration time of the token (exp claim)
        ->expiresAt($now->modify('+1 hour'))
        // Configures a new claim, called "uid"
        // ->withClaim('uid', 1)
        // Configures a new header, called "foo"
        // ->withHeader('foo', 'bar')
        // Builds a new token
        ->getToken($algorithm, $key);
}

echo $token->toString();
