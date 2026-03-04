<?php

declare(strict_types=1);

namespace JWT;

require_once "config.php";

use Config\Config;
use function Error\logError;
use DateTimeImmutable;
use DateTimeZone;
use Exception;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Signer\Blake2b;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\Validation\Constraint\HasClaim;
use Lcobucci\JWT\Validation\Validator;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\RelatedTo;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Ramsey\Uuid\Uuid;

class JWT
{
    private $tokenBuilder;
    private $algorithm;
    private $key;

    function __construct()
    {
        $this->tokenBuilder = new Builder(new JoseEncoder(), ChainedFormatter::default());
        $this->algorithm = new Blake2b();
    }

    public static function init(): JWT|false
    {
        $jwt = new JWT();
        try {
            $jwt->key = InMemory::plainText(Config::$jwt_secret);
        } catch (Exception $e) {
            logError("Failed to initialize JWT Class from Config::\$jwt_secret; Exception: " . $e->getMessage());
            return false;
        }
        return $jwt;
    }

    public function createRefreshToken(int $user_id): UnencryptedToken
    {
        $now = new DateTimeImmutable("now", new DateTimeZone("UTC"));
        return $this->tokenBuilder
            // Configures the issuer (iss claim)
            ->issuedBy('http://ordayna.website')
            // Configures the audience (aud claim)
            ->permittedFor('http://ordayna.website')
            // Configures the subject of the token (sub claim)
            ->relatedTo('refresh')
            // Configures the id (jti claim)
            ->identifiedBy(Uuid::uuid4()->toString())
            // Configures the time that the token was issue (iat claim)
            ->issuedAt($now)
            // Configures the time that the token can be used (nbf claim)
            ->canOnlyBeUsedAfter($now)
            // Configures the expiration time of the token (exp claim)
            ->expiresAt($now->modify('+15 day'))
            // Configures a new claim, called "uid"
            ->withClaim('uid', $user_id)
            // Configures a new header, called "foo"
            // ->withHeader('foo', 'bar')
            // Builds a new token
            ->getToken($this->algorithm, $this->key);
    }

    public function createAccessToken(int $user_id): UnencryptedToken
    {
        $now = new DateTimeImmutable("now", new DateTimeZone("UTC"));
        return $this->tokenBuilder
            // Configures the issuer (iss claim)
            ->issuedBy('http://ordayna.website')
            // Configures the audience (aud claim)
            ->permittedFor('http://ordayna.website')
            // Configures the subject of the token (sub claim)
            ->relatedTo('access')
            // Configures the id (jti claim)
            ->identifiedBy(Uuid::uuid4()->toString())
            // Configures the time that the token was issue (iat claim)
            ->issuedAt($now)
            // Configures the time that the token can be used (nbf claim)
            ->canOnlyBeUsedAfter($now)
            // Configures the expiration time of the token (exp claim)
            ->expiresAt($now->modify('+10 minute'))
            // Configures a new claim, called "uid"
            ->withClaim('uid', $user_id)
            // Configures a new header, called "foo"
            // ->withHeader('foo', 'bar')
            // Builds a new token
            ->getToken($this->algorithm, $this->key);
    }

    /**
     * Returns false on invalid token and true on valid token
     */
    public function validateRefreshToken(UnencryptedToken $token): bool
    {
        $validator = new Validator();

        if (!$validator->validate($token, new IssuedBy("http://ordayna.website"))) {
            return false;
        }
        if (!$validator->validate($token, new PermittedFor("http://ordayna.website"))) {
            return false;
        }
        if (!$validator->validate($token, new RelatedTo("refresh"))) {
            return false;
        }
        if (!$validator->validate($token, new SignedWith($this->algorithm, $this->key))) {
            return false;
        }
        $clock = SystemClock::fromSystemTimezone();
        if (!$validator->validate($token, new StrictValidAt($clock))) {
            return false;
        }
        if (!$validator->validate($token, new HasClaim("uid"))) {
            return false;
        }

        return true;
    }

    /**
     * Returns false on invalid token and true on valid token
     */
    public function validateAccessToken(UnencryptedToken $token): bool
    {
        $validator = new Validator();

        if (!$validator->validate($token, new IssuedBy("http://ordayna.website"))) {
            return false;
        }
        if (!$validator->validate($token, new PermittedFor("http://ordayna.website"))) {
            return false;
        }
        if (!$validator->validate($token, new RelatedTo("access"))) {
            return false;
        }
        if (!$validator->validate($token, new SignedWith($this->algorithm, $this->key))) {
            return false;
        }
        $clock = SystemClock::fromSystemTimezone();
        if (!$validator->validate($token, new StrictValidAt($clock))) {
            return false;
        }
        if (!$validator->validate($token, new HasClaim("uid"))) {
            return false;
        }

        return true;
    }

    public function parseToken(string $token_str): UnencryptedToken|null
    {
        $parser = new Parser(new JoseEncoder());
        try {
            return $parser->parse($token_str);
        } catch (CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound) {
            return null;
        }
    }
}
