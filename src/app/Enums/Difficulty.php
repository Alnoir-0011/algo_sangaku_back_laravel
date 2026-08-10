<?php

namespace App\Enums;

enum Difficulty: int
{
    case EASY = 0;
    case NORMAL = 1;
    case DIFFICULT = 2;
    case VERY_DIFFICULT = 3;

    public function label(): string
    {
        return strtolower($this->name);
    }

    public static function fromLabel(string $label): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->label() === $label) {
                return $case;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return array_map(fn (self $difficulty) => $difficulty->label(), self::cases());
    }
}
