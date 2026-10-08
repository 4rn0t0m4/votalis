<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\LogRecord;

/**
 * Retire des journaux toute donnée personnelle (e-mail, IP, mot de passe…) :
 * les clés listées sont masquées, et les motifs d'e-mail et d'IP effacés du message.
 */
class ScrubPersonalData
{
    private const KEYS = ['email', 'email_hash', 'ip', 'ip_address', 'password', 'password_confirmation', 'phone', 'user_agent', 'identifier'];

    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if (! $handler instanceof ProcessableHandlerInterface) {
                continue;
            }

            $handler->pushProcessor(function (LogRecord $record): LogRecord {
                return $record->with(
                    message: self::scrubText($record->message),
                    context: self::scrubArray($record->context),
                    extra: self::scrubArray($record->extra),
                );
            });
        }
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    public static function scrubArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(mb_strtolower($key), self::KEYS, true)) {
                $data[$key] = '[masqué]';
            } elseif (is_array($value)) {
                $data[$key] = self::scrubArray($value);
            } elseif (is_string($value)) {
                $data[$key] = self::scrubText($value);
            }
        }

        return $data;
    }

    public static function scrubText(string $text): string
    {
        $text = (string) preg_replace('/[\w.+-]+@[\w-]+(\.[\w-]+)+/u', '[e-mail masqué]', $text);
        $text = (string) preg_replace('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', '[ip masquée]', $text);

        return (string) preg_replace('/\b(?:[0-9a-f]{0,4}:){2,7}[0-9a-f]{1,4}\b/i', '[ip masquée]', $text);
    }
}
