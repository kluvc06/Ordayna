<?php
declare(strict_types=1);

include "term.php";

use Lcobucci\Clock\SystemClock;
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
use Lcobucci\JWT\Validation\Constraint\HasClaim;

use Lcobucci\JWT\Validation\Validator;
use Lcobucci\JWT\Validation\Constraint\IdentifiedBy;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\RelatedTo;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;

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

    function createRefreshToken(int $user_id): UnencryptedToken
    {
        $now   = new DateTimeImmutable();
        return $this->tokenBuilder
            // Configures the issuer (iss claim)
            ->issuedBy('http://ordayna.website')
            // Configures the audience (aud claim)
            ->permittedFor('http://ordayna.website')
            // Configures the subject of the token (sub claim)
            ->relatedTo('refresh')
            // Configures the id (jti claim)
            ->identifiedBy(uuidv4())
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

    function createAccessToken(int $user_id): UnencryptedToken
    {
        $now   = new DateTimeImmutable();
        return $this->tokenBuilder
            // Configures the issuer (iss claim)
            ->issuedBy('http://ordayna.website')
            // Configures the audience (aud claim)
            ->permittedFor('http://ordayna.website')
            // Configures the subject of the token (sub claim)
            ->relatedTo('access')
            // Configures the id (jti claim)
            ->identifiedBy(uuidv4())
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
    function validateRefreshToken(UnencryptedToken $token, array $invalid_ids): bool
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

        for ($i = 0; $i < count($invalid_ids); $i++) {
            if ($validator->validate($token, new IdentifiedBy($invalid_ids[$i]))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns false on invalid token and true on valid token
     */
    function validateAccessToken(UnencryptedToken $token): bool
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
