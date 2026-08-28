<?php

namespace App\Enums;

enum AnswerResultStatus: int
{
    case PENDING = 0;
    case CORRECT = 1;
    case INCORRECT = 2;
    case ERROR = 3;

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
        return array_map(fn (self $status) => $status->label(), self::cases());
    }
}
