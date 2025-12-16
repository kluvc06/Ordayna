<?php

include "term.php";

use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;

class JWT
{
    private $tokenBuilder;
    private $algorithm;
    private $key;

    function __construct()
    {
        $this->tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));
        $this->algorithm    = new Sha256();
        try {
            $this->key = InMemory::file('./secret.key');
        } catch (Exception $e) {
            fwrite(STDOUT, "Failed to open secrets file\n");
        }
    }

    function createSessionToken(int $user_id): UnencryptedToken
    {

        $now   = new DateTimeImmutable();
        return $this->tokenBuilder
            // Configures the issuer (iss claim)
            ->issuedBy('http://ordayna.website')
            // Configures the audience (aud claim)
            ->permittedFor('http://ordayna.website')
            // Configures the subject of the token (sub claim)
            ->relatedTo('session')
            // Configures the id (jti claim)
            ->identifiedBy(uuidv4())
            // Configures the time that the token was issue (iat claim)
            ->issuedAt($now)
            // Configures the time that the token can be used (nbf claim)
            // ->canOnlyBeUsedAfter($now->modify('+1 minute'))
            // Configures the expiration time of the token (exp claim)
            ->expiresAt($now->modify('+15 day'))
            // Configures a new claim, called "uid"
            ->withClaim('uid', $user_id)
            // Configures a new header, called "foo"
            // ->withHeader('foo', 'bar')
            // Builds a new token
            ->getToken($this->algorithm, $this->key);
    }

    function parseToken(string $token_str): UnencryptedToken|null
    {
        $parser = new Parser(new JoseEncoder());

        try {
            $token = $parser->parse($token_str);
        } catch (CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound $e) {
            return null;
        }
        assert($token instanceof UnencryptedToken);

        return $token;
    }
}

function uuidv4()
{
    $data = random_bytes(16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
