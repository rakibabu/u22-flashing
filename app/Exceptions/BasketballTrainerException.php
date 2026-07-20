<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class BasketballTrainerException extends RuntimeException
{
    public const NotConfigured = 'not_configured';

    public const Unauthorized = 'unauthorized';

    public const NotFound = 'not_found';

    public const Unavailable = 'unavailable';

    public const InvalidResponse = 'invalid_response';

    public function __construct(
        public readonly string $reason,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    public function userMessage(): string
    {
        return match ($this->reason) {
            self::NotConfigured => 'De BasketballTrainer-koppeling is nog niet geconfigureerd.',
            self::Unauthorized => 'BasketballTrainer heeft het integratietoken geweigerd.',
            self::NotFound => 'Het gekoppelde BasketballTrainer-playbook bestaat niet meer of is niet toegankelijk.',
            self::InvalidResponse => 'BasketballTrainer gaf een onverwacht antwoord terug.',
            default => 'BasketballTrainer is tijdelijk niet bereikbaar.',
        };
    }
}
