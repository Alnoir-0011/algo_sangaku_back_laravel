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
}
