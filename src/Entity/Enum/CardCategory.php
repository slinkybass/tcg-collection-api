<?php

namespace App\Entity\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum CardCategory: string implements TranslatableInterface
{
    case POKEMON = 'Pokemon';
    case ENERGY = 'Energy';
    case TRAINER = 'Trainer';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->name, locale: $locale);
    }

    public static function getChoices(): array
    {
        return array_reduce(self::cases(), function ($o, $e) {
            $o[$e->name] = $e->value;
            return $o;
        }, []);
    }
}
