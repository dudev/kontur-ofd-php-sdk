<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Auth;

use Dudev\KonturOfdPhpSdk\Http\Transport;

/**
 * Сетевые примитивы получения `auth.sid` — три способа из docs/roadmap.md (M2), ни один не
 * привязывает вызывающего к конкретному сценарию: пароль на бэкенде, пароль на фронте (SDK на
 * бэкенде тогда вообще не вызывается — на бэк уходит готовый Sid, см. `Transport::setSid()`), или
 * ЭП на фронте (`authenticateByCert()`/`approveCert()` тоже можно вызывать откуда угодно — SDK не
 * делает ГОСТ-расшифровку сама, только сетевые вызовы).
 */
final readonly class AuthClient
{
    public function __construct(private Transport $transport) {}

    /** Пароль передаётся в теле запроса как есть (не JSON) — так же, как в `Auth/authenticate-by-pass.rst`. */
    public function authenticateByPass(string $login, string $password): AuthResult
    {
        $response = $this->transport->postToAuthHost('/auth/authenticate-by-pass', $password, ['login' => $login]);

        return new AuthResult(sid: $this->requireString($response, 'Sid'));
    }

    /**
     * $certificateBase64 — сертификат электронной подписи в кодировке Base-64
     * (`Auth/authenticate-by-cert.rst`). Возвращает `EncryptedKey`, зашифрованный на этот
     * сертификат (ГОСТ 28147-89), и ссылку для следующего шага.
     */
    public function authenticateByCert(string $certificateBase64): EncryptedKeyResult
    {
        $response = $this->transport->postToAuthHost(
            '/auth/authenticate-by-cert',
            $certificateBase64,
            ['free' => 'false'],
        );

        $link = $response['Link'] ?? null;
        $href = is_array($link) && is_string($link['Href'] ?? null) ? $link['Href'] : '';

        return new EncryptedKeyResult(
            encryptedKey: $this->requireString($response, 'EncryptedKey'),
            approveCertUrl: $href,
        );
    }

    /**
     * $approveCertUrl — `EncryptedKeyResult::$approveCertUrl` из предыдущего шага (уже содержит
     * `thumbprint` в query — идём по готовой ссылке, не собираем URL сами, см. `Transport::
     * postToAbsoluteUrl()`). $decryptedBytes — результат расшифровки `EncryptedKey` приватным
     * ключом сертификата, в байтовом представлении.
     */
    public function approveCert(string $approveCertUrl, string $decryptedBytes): AuthResult
    {
        $response = $this->transport->postToAbsoluteUrl($approveCertUrl, $decryptedBytes);

        return new AuthResult(
            sid: $this->requireString($response, 'Sid'),
            refreshToken: is_string($response['RefreshToken'] ?? null) ? $response['RefreshToken'] : null,
        );
    }

    /** @param array<string, mixed> $response */
    private function requireString(array $response, string $key): string
    {
        $value = $response[$key] ?? null;
        if (!is_string($value) || $value === '') {
            throw new \UnexpectedValueException(
                sprintf('Expected a non-empty string "%s" in Kontur.OFD response', $key),
            );
        }

        return $value;
    }
}
